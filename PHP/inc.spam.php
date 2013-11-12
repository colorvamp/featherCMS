<?php
	$GLOBALS['tables']['spamStrings'] = array('_id_'=>'INTEGER AUTOINCREMENT','spamString'=>'TEXT UNIQUE','spamPool'=>'TEXT DEFAULT \'delete\'');
	if(!isset($GLOBALS['api']['articles'])){$GLOBALS['api']['articles'] = array();}
	$GLOBALS['api']['articles'] = array_merge($GLOBALS['api']['articles'],array(
		'table.spamStrings'=>'spamStrings'
	));
	function spam_string_save($params = array(),$db = false){
		$_valid = $GLOBALS['tables']['spamStrings'];
		foreach($params as $k=>$v){if(!isset($_valid[$k])){unset($params[$k]);}}
		include_once('inc.sqlite3.php');

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable($GLOBALS['api']['articles']['table.spamStrings'],$params,$db);
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}sqlite3_close($db);}

		return $params;
	}
	function spam_string_getSingle($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = sqlite3_getSingle($GLOBALS['api']['articles']['table.spamStrings'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function spam_string_getWhere($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = sqlite3_getWhere($GLOBALS['api']['articles']['table.spamStrings'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
?>
