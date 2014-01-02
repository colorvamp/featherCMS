<?php
	function u_avn($nick = '',$s = 32){
		$s = preg_replace('/[^0-9]*/','',$s);if(empty($s)){$s = 32;}
		$imagePath = '../images/avatars/default/av'.$s.'.png';
		if(!file_exists($imagePath)){exit;}
		$imgProp = @getimagesize($imagePath);
		header('Content-Type: '.$imgProp['mime']);
		readfile($imagePath);exit;
	}

	function u_list($mod = false){
//FIXME: no todos los usuarios tiene permiso para entrar aquí
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.users.php');
		include_once('inc.presentation.php');
		$currentController = str_replace('_','/',__FUNCTION__);
		//FIXME: recuperar los modes de algún lado
		$modes = array('admin'=>'Administrador','writer'=>'Redactor','publisher'=>'Editor','profile'=>'Perfil');

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'ajax.userSearch':
				$users = users_search($_POST['query']);
				foreach($users as $k=>$user){unset($users[$k]['userPass'],$users[$k]['userWord'],$users[$k]['userCode']);}
				echo json_encode(array('errorCode'=>'0','data'=>$users));
				exit;
			case 'user.filter.apply':common_r($GLOBALS['baseURL'].'u/list/byProfile/'.implode('/',$_POST['modes']));exit;
			case 'user.remember':
				if(!isset($_POST['userMail'])){break;}
				include_once('inc.config.php');
				$configMail = config_get('mail');if(!$configMail){break;}
				if(!isset($_POST['userMail'])){break;}
				$_POST['userMail'] = preg_replace($GLOBALS['api']['users']['reg.mail.clear'],'',$_POST['userMail']);
				$userOB = users_getSingle('(userMail = \''.$_POST['userMail'].'\')');if(!$userOB){break;}
				$newCode = users_helper_generateCode($_POST['userMail']);
				$userOB = users_update($userOB['userMail'],array('userIP'=>$_SERVER['REMOTE_ADDR'],'userCode'=>$newCode));
				//FIXME: esto jode un login por cookies, pueden ejecutarlo para joder a alguien
				$rep = array();
				$rep['recoverLink'] = $GLOBALS['baseURL'].'remember/'.$userOB['userMail'].'/'.$newCode;
				$blob = common_loadSnippet('mail/es.mail.recover.pass',$rep);
				include_once('api.mailing.php');
				$subj = 'Recuperación de contraseña';
				$r = mailing_send(array('mail.username'=>$configMail['emailName'],'mail.password'=>$configMail['emailPass'],'mail.host'=>$configMail['emailHost'],'mail.port'=>$configMail['emailPort']),
					$userOB['userMail'],$subj,$blob);
				if(isset($r['errorDescription'])){print_r($r);exit;}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'userSave':
				$r = users_create($_POST);if(isset($r['errorDescription'])){print_r($r);exit;}
				$user = $r;
				$r = users_update($user['userMail'],array('userStatus'=>1,'userCode'=>''));
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'userModesSave':
				$user = users_getByMails($_POST['userMail']);if(!$user){break;}
				$userModes = array_diff(explode(',',$user['userModes']),array(''));//$userModes = array_flip($userModes);
				foreach($_POST as $key=>$value){if(!isset($modes[$key])){unset($_POST[$key]);}}
				foreach($userModes as $key=>$value){if(isset($modes[$value])){unset($userModes[$key]);}}
				$userModes = array_unique(array_merge($userModes,array_keys($_POST)));
				$userModes = ','.implode(',',$userModes).',';
				$r = users_update($user['userMail'],array('userModes'=>$userModes));
				if(isset($r['errorDescription'])){print_r($r);exit;}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'userInfoSave':
				$user = users_getByMails($_POST['userMail']);if(!$user){break;}
				$r = users_update($user['userMail'],$_POST);
				if(isset($r['errorDescription'])){print_r($r);exit;}
if($user['userNick'] != $r['userNick']){
//FIXME: necesitamos hacer un update en toda la librería
}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
		}}

		$whereClause = 1;
		if($mod){switch($mod){
			case 'byProfile':
				$args = func_get_args();
				array_shift($args);
				$cl = array_map(function($n){return 'userModes LIKE \'%,'.$n.',%\'';},$args);
				$whereClause = '('.implode(' OR ',$cl).')';
				break;
		}}

		$users = users_getWhere($whereClause);
		$adminButtons = common_loadSnippet('u/snippets/u.node.options.admin');
		$s = '';foreach($users as $user){
			/* INI-userModes */
			$m = explode(',',$user['userModes']);$m = array_flip($m);
			$a = '<table class="data"><thead><tr><td>Permisos</td><td style="width:20px;"></td></tr></thead><tbody>';
			foreach($modes as $mode=>$text){$a .= '<tr><td>'.$text.'</td><td><input class="checkbox" type="checkbox" name="'.$mode.'" '.(isset($m[$mode]) ? 'checked="checked"' : '').'/></td></tr>';}
			$a .= '</tbody></table>';
			$user['html.userModes'] = $a;
			/* END-userModes */

			$user['html.options'] = $adminButtons;
			$GLOBALS['replaceIteration'] = 0;
			$s .= common_loadSnippet('u/snippets/u.node',$user);
		}
		$TEMPLATE['list.users'] = $s;
		
		common_renderTemplate('u/list');
	}
?>
