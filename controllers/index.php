<?php
	function index_main(){
		common_renderTemplate('index');
	}

	function index_login(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		$GLOBALS['COMMON']['BASE'] = 'base.login';
		if(users_isLogged()){header('Location: '.$GLOBALS['baseURL']);exit;}

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'ajax.userLogin':$r = users_login($_POST['userMail'],$_POST['userPass']);echo json_encode($r);exit;
			case 'userRegister':
				$users = users_getSingle(1);if($users){break;}
				if(!isset($_POST['userPass']) || !isset($_POST['userPassR']) || $_POST['userPass'] != $_POST['userPassR']){echo 'passwords mismatch';exit;}
				$r = users_create($_POST);if(isset($r['errorDescription'])){print_r($r);exit;}
				$user = $r;
				/* Activate the new user so he can log into the system */
				$r = users_update($user['userMail'],array('userStatus'=>1,'userModes'=>',regular,admin,','userCode'=>''));
				header('Location: '.$GLOBALS['baseURL']);exit;
		}}

		if(count($_POST)){do{
			include_once('api.users.php');
			$r = users_login($_POST['userMail'],$_POST['userPass']);
			if(isset($r['errorDescription'])){$TEMPLATE['loginWarn'] = $r['errorDescription'];break;}
			header('Location: '.$GLOBALS['baseURL']);exit;
		}while(false);}

		/* INI-print all the allowed users */
		$users = users_getWhere('(userModes LIKE \'%,admin,%\' OR userModes LIKE \'%,writer,%\' OR userModes LIKE \'%,publisher,%\')',array('indexBy'=>'userMail'));
		if(!$users){return common_renderTemplate('u/register');}
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
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}r/js/login.js';
		common_renderTemplate('u/login');
	}

	function index_config(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'configSave':
				
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
				break;
		}}

		/* INI-imageSizes */
		$s = '<table class="data"><thead><tr><td>Tamaño</td><td style="width:20px;"></td></tr></thead><tbody>';
		foreach(array('16','32','64','90','136','180','128x64','256x0','620x0','128p64') as $size){
			$s .= '<tr><td>('.$size.')</td><td><input class="checkbox" type="checkbox" name="'.$size.'"/></td></tr>'.PHP_EOL;
		}$s .= '</tbody></table>';
		$TEMPLATE['html.articleImageSizes'] = $s;
		/* END-imageSizes */
		common_renderTemplate('config');
	}
?>
