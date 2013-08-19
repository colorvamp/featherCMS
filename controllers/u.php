<?php
	function u_avn($nick = '',$s = 32){
		$s = preg_replace('/[^0-9]*/','',$s);if(empty($s)){$s = 32;}
		$imagePath = '../images/avatars/default/av'.$s.'.png';
		if(!file_exists($imagePath)){exit;}
		$imgProp = @getimagesize($imagePath);
		header('Content-Type: '.$imgProp['mime']);
		readfile($imagePath);exit;
	}

	function u_list(){
//FIXME: no todos los usuarios tiene permiso para entrar aquí
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.users.php');
		include_once('inc.presentation.php');
		$currentController = str_replace('_','/',__FUNCTION__);
		//FIXME: recuperar los modes de algún lado
		$modes = array('admin'=>'Administrador','writer'=>'Redactor','publisher'=>'Editor');

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'ajax.userSearch':
				$users = users_search($_POST['query']);
				foreach($users as $k=>$user){unset($users[$k]['userPass'],$users[$k]['userWord'],$users[$k]['userCode']);}
				echo json_encode(array('errorCode'=>'0','data'=>$users));
				exit;
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
				break;
			case 'userInfoSave':
				$user = users_getByMails($_POST['userMail']);if(!$user){break;}
				$r = users_update($user['userMail'],$_POST);
				if(isset($r['errorDescription'])){print_r($r);exit;}
if($user['userNick'] != $r['userNick']){
//FIXME: necesitamos hacer un update en toda la librería
}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
				break;
		}}

		$users = users_getWhere(1);
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
