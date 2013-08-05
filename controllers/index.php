<?php
	function index_main(){
		common_renderTemplate('index');
	}

	function index_login(){
		//FIXME: si hay usuario logueado redireccionamos al login
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		if(count($_POST)){do{
			include_once('api.users.php');
			$r = users_login($_POST['userMail'],$_POST['userPass']);
			if(isset($r['errorDescription'])){$TEMPLATE['loginWarn'] = $r['errorDescription'];break;}
			header('Location: '.$GLOBALS['baseURL']);exit;
		}while(false);}

		$TEMPLATE['BLOG_TITLE'] = 'Login de usuarios';
		$TEMPLATE['HTML_TITLE'] = $TEMPLATE['BLOG_TITLE'];
		$TEMPLATE['HTML_DESCRIPTION'] = 'Login de usuarios';
		common_renderTemplate('u/login');
	}
?>
