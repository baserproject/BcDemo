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
		// データベース構築
		// ユーザー作成
		// サイト設定
		// DBデータの初期更新
		// プラグインの初期化
		// コアプラグインのインストール
		// プラグイン有効化
		// ページ初期化

		// テーマの配置
		// 　→ サーバサイドのコマンドにて実行

		// キャッシュ削除
		clearAllCache();

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

		// config.css 削除
		$configCssPath = WWW_ROOT . 'files' . DS . 'theme_configs' . DS . 'config.css';
		if(file_exists($configCssPath)) {
			unlink($configCssPath);
		}

		$this->out("デモデータを初期化しました。");

	}


}
