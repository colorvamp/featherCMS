<?php
	function c_main(){
		$c = $f = false;
		$args = func_get_args();

		$params = implode('/',$args);
		/* INI-loading resources */
		if(preg_match('/(css|js|images|font)\/.*?\.([a-z]{2,4}$)/',$params,$m)){
			$m[0] = '../../assis/'.$m[0];if(!file_exists($m[0])){exit;}
			switch($m[2]){
				case 'css':header('Content-type: text/css');break;
				case 'js':header('Content-type: application/javascript');break;
				case 'png':header('Content-type: image/png');break;
				case 'gif':header('Content-type: image/gif');break;
				case 'ttf':case 'woff':case 'otf':case 'eot':header('Content-type: application/x-unknown-content-type');break;
			}
			readfile($m[0]);exit;
		}
		/* END-loading resources */

		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		if($args){$c = array_shift($args);}
		if($args){$f = array_shift($args);}

		$controller = $c;
		if(substr($controller,-4) !== '.php'){$controller .= '.php';}
		if(!file_exists($GLOBALS['controllersExte'].$controller)){header('Location: '.$GLOBALS['baseURL']);exit;}
		$currentFunctions = get_defined_functions();
		$currentFunctions = $currentFunctions['user'];
		include_once($GLOBALS['controllersExte'].$controller);
		if($f && function_exists($c.'_'.$f)){
			chdir('../../PHP/');
			$GLOBALS['COMMON']['BASE'] = '../../featherCMS/views/base';
			$GLOBALS['COMMON']['TEMPLATEPATH'] = '../assis/views/';
			$TEMPLATE['BLOG_CSS'][] = '{%baseURL%}c/css/index.css';
			return call_user_func_array($c.'_'.$f,$args);
		}
		$newFunctions = get_defined_functions();
		$newFunctions = $newFunctions['user'];
		$newFunctions = array_diff($newFunctions,$currentFunctions);

		$TEMPLATE['list.functions'] = '';
		foreach($newFunctions as $func){
			$TEMPLATE['list.functions'] .= '<li><a href="{%baseURL%}c/'.str_replace('_','/',$func).'">'.$func.'</a></li>';
		}

		return common_renderTemplate('c/main');
	}
?>
