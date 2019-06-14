<?php
App::uses('AppShell', 'Console/Command');
App::uses('BcManagerComponent', 'Controller/Component');

/**
 * Class ResetShell
 *
 * @property BcManagerComponent $BcManager
 */
class ResetShell extends AppShell {
	
/**
 * startup 
 */
	public function startup() {
		parent::startup();
		$this->BcManager = new BcManagerComponent(new ComponentCollection());
	}
	
/**
 * init
 */
	public function main() {

		$dbConfig = getDbConfig();
		
		// データベース初期化
		if (!$this->BcManager->deleteTables('default', $dbConfig)) {
			$message = "データベースの初期化に失敗しました";
			$this->log($message);
			$this->err($message);
			return;
		}

		// データベース構築
		if (!$this->BcManager->constructionDb($dbConfig)) {
			$message = "データベースの構築に失敗しました";
			$this->log($message);
			$this->err($message);
			return;
		}

		// キャッシュ削除
		clearAllCache();

		// ユーザー作成
		if (!$this->_initDemoUsers()) {
			$message = "ユーザー「operator」の作成に失敗しました";
			$this->log($message);
			$this->err($message);
			return;
		}

		// サイト設定
		if (!$this->_initDemoSiteConfigs()) {
			$message = "システム設定の更新に失敗しました";
			$this->log($message);
			$this->err($message);
			return;
		}

		// DBデータの初期更新
		if (!$this->BcManager->executeDefaultUpdates($dbConfig)) {
			$message = "DBデータの初期更新に失敗しました。";
			$this->log($message);
			$this->err($message);
			return;
		}

		// プラグインの初期化
		if(!$this->_initPlugins()) {
			$message = "プラグインの初期化に失敗しました";
			$this->log($message);
			$this->err($message);
			return;
		}
		
		// コアプラグインのインストール
		$dbDataPattern = Configure::read('BcApp.defaultTheme') . '.default';
		if(!$this->BcManager->installCorePlugin($dbConfig, $dbDataPattern)) {
			$this->log('コアプラグインのインストールに失敗しました。');
			$result = false;
		}
		
		// プラグイン有効化
		if(!$this->_enablePlugins()) {
			$message = "デモプラグインの有効化に失敗しました";
			$this->log($message);
			$this->err($message);
			return;
		}
		
		// テーマの配置
		if (!$this->BcManager->deployTheme()) {
			$message = "テーマの配置に失敗しました。";
			$this->log($message);
			$this->err($message);
			return;
		}

		// テーマに管理画面のアセットをデプロイする
		if (!$this->BcManager->deployAdminAssets()) {
			$message = "管理システムのアセットファイルの配置に失敗しました。";
			$this->log($message);
			$this->err($message);
		}

		// ページ初期化
		// リクエストアクションでBlogControllerを利用する際、
		// BlogContent モデルに、AppModel が利用されていて処理がうまくいかない為、
		// 一旦、ClassRegistry を初期化する
		ClassRegistry::flush();
		if (!$this->BcManager->createPageTemplates()) {
			$message = "ページテンプレートの更新に失敗しました";
			$this->log($message);
			$this->err($message);
			return;
		}

		// config.css 削除
		$configCssPath = WWW_ROOT . 'files' . DS . 'theme_configs' . DS . 'config.css';
		if(file_exists($configCssPath)) {
			unlink($configCssPath);
		}

		clearAllCache();

		$this->out("デモデータを初期化しました。");
		
	}
	
/**
 * サイト設定の初期化
 * 
 * @return boolean
 */
	protected function _initDemoSiteConfigs() {
		$SiteConfig = ClassRegistry::init('SiteConfig');
		$siteConfig = $SiteConfig->findExpanded();
		$siteConfig['address'] = '福岡県福岡市博多区博多駅前';
		$siteConfig['admin_theme'] = 'admin-third';
		return $SiteConfig->saveKeyValue($siteConfig);
	}

/**
 * 初期ユーザーの作成
 * 
 * @return boolean 
 */
	protected function _initDemoUsers() {
		
		App::uses('AuthComponent', 'Controller/Component');
		ClassRegistry::flush();
		$User = ClassRegistry::init('User');

		$ret = true;
		$user['User']['name'] = 'admin';
		$user['User']['password'] = 'demodemo';
		$user['User']['password_1'] = 'demodemo';
		$user['User']['password_2'] = 'demodemo';
		$user['User']['real_name_1'] = 'admin';
		$user['User']['user_group_id'] = 1;
		$User->create($user);
		if (!$User->save()) {
			$ret = false;
		}

		$user['User']['name'] = 'operator';
		$user['User']['password'] = 'demodemo';
		$user['User']['password_1'] = 'demodemo';
		$user['User']['password_2'] = 'demodemo';
		$user['User']['real_name_1'] = 'member';
		$user['User']['user_group_id'] = 2;
		$User->create($user);
		if (!$User->save()) {
			$ret = false;
		}

		return $ret;
	}
	
/**
 * 必要なプラグインを有効化する
 * 
 * 存在しない場合には有効化しない
 */
	protected function _enablePlugins() {
		$this->BcManager->installPlugin('BcDemo');
		return true;
	}

/**
 * プラグインを初期化する
 * 
 * @return bool
 */
	protected function _initPlugins() {
		$pluginPath = APP . 'Plugin';
		$Folder = new Folder($pluginPath);
		$files = $Folder->read(true, true, false);
		foreach($files[0] as $file) {
			if($file != 'BcDemo') {
				$Folder->delete(APP . 'Plugin' . DS . $file);
			}
		}
		return true;
	}
	
}