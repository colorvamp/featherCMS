<?php
	if(!isset($GLOBALS['api']['config'])){$GLOBALS['api']['config'] = array();}
	$GLOBALS['api']['config'] = array_merge($GLOBALS['api']['config'],array('dir.db'=>'../db/',
		'file.mail'=>'mail.cfg'
	));
	if(file_exists('../../db/')){$GLOBALS['api']['config']['dir.db'] = '../../db/';}

	function config_get($name){
		if(!isset($GLOBALS['api']['config']['file.'.$name])){return false;}
		$src = $GLOBALS['api']['config']['dir.db'].$GLOBALS['api']['config']['file.'.$name];
		if(!file_exists($src)){return false;}
		return json_decode(file_get_contents($src),1);
	}
?>
