<?php
class ResetController extends BcPluginAppController {
	
	public $components = array('BcManager');
		
/**
 * init
 */
	public function index() {

		$dbConfig = getDbConfig();
		
		// データベース初期化
		if (!$this->_initDb($dbConfig)) {
			$message = "データベースの初期化に失敗しました";
			$this->log($message);
			return;
		}

		// キャッシュ削除
		clearAllCache();

		if (!$this->BcManager->initSystemData($dbConfig)) {
			$message = "システムデータを初期化に失敗しました";
			$this->log($message);
			return;
		}

		// ユーザー作成
		if (!$this->_initDemoUsers()) {
			$message = "ユーザー「operator」の作成に失敗しました";
			$this->log($message);
			return;
		}

		// サイト設定
		if (!$this->_initDemoSiteConfigs()) {
			$message = "システム設定の更新に失敗しました";
			$this->log($message);
			return;
		}

		// DBデータの初期更新
		if (!$this->BcManager->executeDefaultUpdates($dbConfig)) {
			$message = "DBデータの初期更新に失敗しました。";
			$this->log($message);
			return;
		}

		// コアプラグインのインストール
		$dbDataPattern = Configure::read('BcApp.defaultTheme') . '.default';
		if(!$this->BcManager->installCorePlugin($dbConfig, $dbDataPattern)) {
			$message = "プラグインのインストールに失敗しました";
			$this->log($message);
			return;
		}

		// プラグイン有効化
		if(!$this->_enablePlugins()) {
			$message = "デモプラグインの有効化に失敗しました";
			$this->log($message);
			return;
		}

		// テーマの配置
		if (!$this->BcManager->deployTheme()) {
			$message = "テーマの配置に失敗しました。";
			$this->log($message);
			return;
		}

		// テーマに管理画面のアセットへのシンボリックリンクを作成する
		if (!$this->BcManager->deployAdminAssets()) {
			$message = "管理システムのアセットファイルの配置に失敗しました。";
			$this->log($message);
			return;
		}

		// ページ初期化
		// リクエストアクションでBlogControllerを利用する際、
		// BlogContent モデルに、AppModel が利用されていて処理がうまくいかない為、
		// 一旦、ClassRegistry を初期化する
		ClassRegistry::flush();
		$this->request->params['plugin'] = '';
		if (!$this->BcManager->createPageTemplates()) {
			$message = "ページテンプレートの更新に失敗しました";
			$this->log($message);
			return;
		}

		clearAllCache();

		echo true;
		exit();
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
		return $SiteConfig->saveKeyValue($siteConfig);
	}

/**
 * 初期ユーザーの作成
 * 
 * @return boolean 
 */
	protected function _initDemoUsers() {
		
		$ret = true;
		$user['User']['name'] = 'admin';
		$user['User']['password'] = 'demodemo';
		$user['User']['password_1'] = 'demodemo';
		$user['User']['password_2'] = 'demodemo';
		$user['User']['real_name_1'] = 'admin';
		$user['User']['user_group_id'] = 1;
		$ret = $this->BcManager->addDefaultUser($user['User']);

		if ($ret) {
			$ret = true;
			$user['User']['name'] = 'operator';
			$user['User']['password'] = 'demodemo';
			$user['User']['password_1'] = 'demodemo';
			$user['User']['password_2'] = 'demodemo';
			$user['User']['real_name_1'] = 'member';
			$user['User']['user_group_id'] = 2;
			$ret = $this->BcManager->addDefaultUser($user['User']);
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
		$this->BcManager->installPlugin('Uploader');
		return true;
		
	}

/**
 * データベースを初期化する
 * 
 * @param type $dbConfig
 * @param type $reset
 * @param type $dbDataPattern
 * @return type
 * @access public 
 */
	public function _initDb($dbConfig, $reset = true, $dbDataPattern = '') {
		if (!$dbDataPattern) {
			$dbDataPattern = Configure::read('BcApp.defaultTheme') . '.default';
		}

		if ($reset) {
			$this->_deleteTables('baser');
			$this->_deleteTables('plugin');
		}

		return $this->BcManager->constructionDb($dbConfig, $dbDataPattern);
	}


/**
 * テーブルを削除する
 * 
 * @param string $dbConfigKeyName
 * @param array $dbConfig
 * @return boolean
 * @access public
 * TODO 処理を DboSource に移動する
 * TODO コアのテーブルを削除する際、プレフィックスだけでは、プラグインを識別できないので、プラグインのテーブルも削除されてしまう。
 * 		その為、プラグインのテーブルを削除しようとすると存在しない為、Excerptionが発生してしまい。処理が停止してしまうので、
 * 		try で実行し、catch はスルーしている。
 */
	public function _deleteTables($dbConfigKeyName = 'baser', $dbConfig = null) {
		$db = $this->BcManager->_getDataSource($dbConfigKeyName, $dbConfig);
		$dbConfig = $db->config;

		/* 削除実行 */
		// TODO schemaを有効活用すればここはスッキリしそうだが見送り
		$datasource = strtolower(preg_replace('/^Database\/Bc/', '', $db->config['datasource']));
		switch ($datasource) {
			case 'mysql':
				$sources = $db->listSources();
				foreach ($sources as $source) {
					if (preg_match("/^" . $dbConfig['prefix'] . "([^_].+)$/", $source)) {
						$sql = 'DROP TABLE ' . $source;
						try {
							$db->execute($sql);
						} catch (Exception $e) {
						}
					}
				}
				break;

			case 'postgres':
				$sources = $db->listSources();
				foreach ($sources as $source) {
					if (preg_match("/^" . $dbConfig['prefix'] . "([^_].+)$/", $source)) {
						$sql = 'DROP TABLE ' . $source;
						try {
							$db->execute($sql);
						} catch (Exception $e) {
						}
					}
				}
				// シーケンスも削除
				$sql = "SELECT sequence_name FROM INFORMATION_SCHEMA.sequences WHERE sequence_schema = '{$dbConfig['schema']}';";
				$sequences = array();
				try {
					$sequences = $db->query($sql);
				} catch (Exception $e) {
				}
				if ($sequences) {
					$sequences = Hash::extract($sequences, '0.sequence_name');
					foreach ($sequences as $sequence) {
						if (preg_match("/^" . $dbConfig['prefix'] . "([^_].+)$/", $sequence)) {
							$sql = 'DROP SEQUENCE ' . $sequence;
							try {
								$db->execute($sql);
							} catch (Exception $e) {
							}
						}
					}
				}
				break;

			case 'sqlite':
				$sources = $db->listSources();
				foreach ($sources as $source) {
					if (preg_match("/^" . $dbConfig['prefix'] . "([^_].+)$/", $source)) {
						$sql = 'DROP TABLE ' . $source;
						try {
							$db->execute($sql);
						} catch (Exception $e) {
						}
					}
				}
				break;

			case 'csv':
				$folder = new Folder($dbConfig['database']);
				$files = $folder->read(true, true, true);
				foreach ($files[1] as $file) {
					if (basename($file) != 'empty') {
						@unlink($file);
					}
				}
				break;
		}
		return true;
	}

}