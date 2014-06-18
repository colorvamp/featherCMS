<?php
	ini_set('display_errors',1);
	error_reporting(E_ALL);
	date_default_timezone_set('Europe/Madrid');
	$HERE_localhost = $_SERVER['SERVER_NAME'] == 'localhost';
	$HERE_hosted = false;

	$GLOBALS['indexURL'] = 'http://'.$_SERVER['SERVER_NAME'];
	$GLOBALS['baseURL'] = $GLOBALS['indexURL'].'/';
	/* For feathers hosted in projects */
	if(($pos = strpos($_SERVER['REQUEST_URI'],'/feather/'))){$HERE_hosted = true;$len = $pos+strlen('/feather');$GLOBALS['baseURL'] = 'http://'.$_SERVER['SERVER_NAME'].substr($_SERVER['REQUEST_URI'],0,$len).'/';$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'],$len);}

	$params = parse_url($_SERVER['REQUEST_URI']);$params = $params['path'];
	if($HERE_localhost && !$HERE_hosted){$params = substr($params,strlen($filepath));}
	$controllersBase = dirname(__FILE__).'/controllers/';
	$controllersExte = dirname(__FILE__).'/../assis/controllers/';

	/* INI-loading other resources */
	if(preg_match('/(css|js|images|font|apps)\/.*?\.([a-z]{2,4}$)/',$params,$m)){do{if(!file_exists($m[0])){break;}
		switch($m[2]){
			case 'css':header('Content-type: text/css');break;
			case 'js':header('Content-type: application/javascript');break;
			case 'png':header('Content-type: image/png');break;
			case 'gif':header('Content-type: image/gif');break;
			case 'jpg':case 'jpeg':header('Content-type: image/jpeg');break;
			case 'woff':header('Content-type: application/x-font-woff');break;
			case 'ttf':case 'otf':case 'eot':header('Content-type: application/x-unknown-content-type');break;
		}
		readfile($m[0]);exit;
	}while(false);}
	/* END-loading resources */

	/* INI-Obtenemos la paginación */
	$GLOBALS['currentPage'] = 1;if(preg_match('/page\/([0-9]+)$/',$params,$m)){$params = substr($params,0,-strlen($m[0]));$GLOBALS['currentPage'] = $m[1];if($GLOBALS['currentPage'] < 1){$GLOBALS['currentPage'] = 1;}}
	/* END-Obtenemos la paginación */
	$params = explode('/',$params);
	$params = array_values(array_diff($params,array('')));

	session_start();
	chdir(dirname(__FILE__).'/PHP/');
	if(!defined('T')){define('T',"\t");}
	if(!defined('N')){define('N',"\n");}
	if(!defined('J')){define('J',"\t\t\t\t");}
	$GLOBALS['TEMPLATE'] = array('baseURL'=>$GLOBALS['baseURL'],'indexURL'=>$GLOBALS['indexURL']);

	include_once('inc.common.php');
	include_once('inc.presentation.php');
	include_once('api.users.php');
	if(!is_writable('../db') && !is_writable('../../db')){echo 'database folder is not writable';exit;}
	$r = users_isLogged();
	$_nologin = array('login'=>0,'remember'=>0,'av'=>0);
	if(!$r && (!$params || !isset($_nologin[$params[0]])) ){header('Location: '.$GLOBALS['baseURL'].'login');exit;}
	if($r){$GLOBALS['TEMPLATE']['user'] = $GLOBALS['user'];}

	do{

		/* Sacamos la función que debemos llamar */
		$controller = array_shift($params);
		if($controller == NULL){$controller = 'index';}
		$controllerPath = $controllersBase.$controller.'.php';
		if(!file_exists($controllerPath)){array_unshift($params,$controller);$controller = 'index';$controllerPath = $controllersBase.$controller.'.php';}

		include_once($controllerPath);
		$command = $unshift = array_shift($params);
		if($command == NULL){$command = $controller.'_main';break;}

		$command = $controller.'_'.$command;if(function_exists($command)){break;}
		if(isset($unshift)){array_unshift($params,$unshift);}
		$command = $controller.'_main';if(function_exists($command)){break;}
	}while(false);

	presentation_main();
	$c = str_replace('_','.',$command);
	$customJS = $GLOBALS['COMMON']['dir.js'].$c.'.js';if(file_exists($customJS)){$GLOBALS['TEMPLATE']['BLOG_JS'][] = '{%baseURL%}r/js/c/'.$c.'.js';}
	$customCSS = $GLOBALS['COMMON']['dir.css'].$c.'.css';if(file_exists($customCSS)){$GLOBALS['TEMPLATE']['BLOG_CSS'][] = '{%baseURL%}r/css/c/'.$c.'.css';}
	$r = call_user_func_array($command,$params);

	echo $GLOBALS['OUTPUT'];
	exit;

