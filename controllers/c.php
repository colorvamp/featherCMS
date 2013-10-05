<?php
	function c_main($c = '',$f = ''){
//FIXME: hacerlo con $args
		if(substr($c,-4) !== '.php'){$c .= '.php';}
		if(!file_exists($GLOBALS['controllersExte'].$c)){header('Location: '.$GLOBALS['baseURL']);exit;}
		$currentFunctions = get_defined_functions();
		$currentFunctions = $currentFunctions['user'];
		include_once($GLOBALS['controllersExte'].$c);
		$newFunctions = get_defined_functions();
		$newFunctions = $newFunctions['user'];
		$newFunctions = array_diff($newFunctions,$currentFunctions);
print_r($newFunctions);
		/* Vamos a detectar los entry points */
		//$blob = file_get_contents($GLOBALS['controllersExte'].$c);
		//if(){echo $c;exit;}
	}
?>
