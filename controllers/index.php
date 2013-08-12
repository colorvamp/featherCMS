<?php
	function index_main(){
		common_renderTemplate('index');
	}

	function index_login(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		if(users_isLogged()){header('Location: '.$GLOBALS['baseURL']);exit;}

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'ajax.userLogin':$r = users_login($_POST['userMail'],$_POST['userPass']);echo json_encode($r);exit;
			case 'userRegister':
				$users = users_getSingle(1);if($users){break;}
				//FIXME: registrar usuario
				break;
		}}

		if(count($_POST)){do{
			include_once('api.users.php');
			$r = users_login($_POST['userMail'],$_POST['userPass']);
			if(isset($r['errorDescription'])){$TEMPLATE['loginWarn'] = $r['errorDescription'];break;}
			header('Location: '.$GLOBALS['baseURL']);exit;
		}while(false);}

		/* INI-print all the allowed users */
		$users = users_getWhere(1,array('indexBy'=>'userMail'));
		if(!$users){return common_renderTemplate('register');}
		$usersGrid = '';
		foreach($users as $user){
			$user['loginName'] = $user['userName'];
			$usersGrid .= common_loadSnippet('snippets/login.user',$user);
		}
		$TEMPLATE['usersGrid'] = $usersGrid;
		/* INI-print all the allowed users */


		$TEMPLATE['BLOG_TITLE'] = 'Login de usuarios';
		$TEMPLATE['HTML_TITLE'] = $TEMPLATE['BLOG_TITLE'];
		$TEMPLATE['HTML_DESCRIPTION'] = 'Login de usuarios';
		$GLOBALS['COMMON']['BASE'] = 'base.login';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}r/js/login.js';
		common_renderTemplate('u/login');
	}
?>
