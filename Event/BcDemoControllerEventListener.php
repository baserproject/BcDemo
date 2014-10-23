<?php
/**
 * BcDemoControllerEventListener
 */
class BcDemoControllerEventListener extends BcControllerEventListener {
	
	public $events = array(
		'SiteConfigs.startup',
		'Blog.Blog.startup',
		'Plugins.startup'
	);
	
/**
 * システム管理の環境情報を表示不能にする
 * 
 * @param CakeEvent $event
 */
	public function siteConfigsStartup(CakeEvent $event) {
		$Controller = $event->subject();
		if($Controller->request->action == 'admin_info') {
			$Controller->notFound();
		}
	}
	
/**
 * モバイルの場合、画像認証が効かないのでコメントを送信しない
 * 
 * @param CakeEvent $event
 */
	public function blogBlogStartup(CakeEvent $event) {
		$Controller = $event->subject();
		// 条件
		// - モバイルサイト
		// - コメントデータが送信されている
		if($Controller->request->action == 'archives' && Configure::read('BcRequest.agent') == 'mobile' && isset($this->request->data['BlogComment'])) {
			$Controller->Session->setFlash('デモンストレーションモードの携帯サイトでコメントは送信できません。');
			$Controller->requestAction('/');
		}
	}
	
/**
 * デモプラグインの無効化不可対応
 * 
 * @param CakeEvent $event
 */
	public function pluginsStartup(CakeEvent $event) {
		$Controller = $event->subject();
		if($Controller->request->action == 'admin_ajax_delete') {
			if($Controller->request->params['pass'][0] == 'BcDemo') {
				$Controller->ajaxError(400, 'デモサイトでデモプラグインは無効化できません。');
			}
		}
	}
	
}