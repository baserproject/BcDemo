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
		if($Controller->request->action == 'mobile_archives' && isset($Controller->request->data['BlogComment'])) {
			$Controller->Session->setFlash('デモンストレーションモードの携帯サイトでコメントは送信できません。');
			$Controller->redirect('/');
		}
	}
	
/**
 * デモプラグインの無効化不可対応
 * 
 * @param CakeEvent $event
 */
	public function pluginsStartup(CakeEvent $event) {
		$Controller = $event->subject();
		$action = $Controller->request->params['action'];
		switch ($action) {
			case 'admin_ajax_delete':
				if($Controller->request->params['pass'][0] == 'BcDemo') {
					$Controller->ajaxError(400, 'デモサイトでデモプラグインは無効化できません。');
				}
				break;
			case 'admin_ajax_batch':
				$pluginIds = $Controller->request->data['ListTool']['batch_targets'];
				$plugins = $Controller->Plugin->find('list', ['fields' => ['id', 'name'], 'conditions' => ['id' => $pluginIds]]);
				if(in_array('BcDemo', $plugins)) {
					$Controller->ajaxError(400, 'デモサイトでデモプラグインは無効化できません。');
				}
				break;
		}
	}
	
}