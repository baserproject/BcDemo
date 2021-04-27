<?php
/**
 * BcDemoViewEventListener
 */
class BcDemoViewEventListener extends BcViewEventListener {

	public $events = array(
		'Users.afterRender'
	);

	public function usersAfterRender(CakeEvent $event) {

		$View = $event->subject();
		if($View->request->action == 'admin_edit' && $View->data['User']['id'] == '1') {
			$View->output .= <<< END_SCRIPT
<script>
$(function(){
	$("#BtnSave").hide();
});
</script>
END_SCRIPT;
		}


		if($View->request->action == 'admin_login') {
			$View->output .= <<< END_SCRIPT
<script>
$(function(){
	$("#UserName").val("operator");
	$("#UserPassword").val("demodemo");
});
</script>
END_SCRIPT;
		}

	}

}
