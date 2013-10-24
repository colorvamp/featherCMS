<?php
	$GLOBALS['tables']['trackingData'] = array('_key_'=>'TEXT UNIQUE','value'=>'TEXT');
	$GLOBALS['tables']['trackingTotalCount'] = array('_trackingURL_'=>'TEXT','_trackingDate_'=>'TEXT','trackingCount'=>'INTEGER','trackingUpdated'=>'TEXT');
	$GLOBALS['tables']['trackingCount'] = array('_trackingURL_'=>'TEXT','_trackingDate_'=>'TEXT','trackingCount'=>'INTEGER');
	$GLOBALS['tables']['tracking'] = array('_id_'=>'INTEGER AUTOINCREMENT','trackingUser'=>'TEXT','trackingIP'=>'TEXT','trackingUserAgent'=>'TEXT','trackingURL'=>'TEXT','trackingReferer'=>'TEXT','trackingDate'=>'DATE','trackingTime'=>'TEXT','trackingHour'=>'INTEGER','trackingStamp'=>'TEXT','trackingTag'=>'TEXT');

	if(!isset($GLOBALS['api']['track'])){$GLOBALS['api']['track'] = array();}
	$GLOBALS['api']['track'] = array_merge($GLOBALS['api']['track'],array(
		'dir.track'=>'../db/api.track/',
		'db.track'=>'../db/api.track.db',
		'db.tmp'=>'../db/api.track.tmp.db',
		'table.track'=>'track'
	));
	if(is_writable('/dev/shm/')){do{
		$f = '/dev/shm/'.$_SERVER['SERVER_NAME'].'/';if(!file_exists($f)){$oldmask = umask(0);$r = @mkdir($f,0777,1);umask($oldmask);if(!$r){break;}}
		$GLOBALS['api']['track']['db.tmp'] = $f.'api.track.tmp.db';
	}while(false);}

	function tracking_touch($params = array(),$db = false){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['track']['db.tmp']);sqlite3_exec('BEGIN',$db);$shouldClose = true;}
		$trackingUser = isset($GLOBALS['user']) ? $GLOBALS['user']['userNick'] : 0;
		$track = array('trackingUser'=>$trackingUser,'trackingIP'=>$_SERVER['REMOTE_ADDR'],'trackingUserAgent'=>(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown'),'trackingURL'=>'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],'trackingReferer'=>(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),'trackingDate'=>date('Y-m-d'),'trackingTime'=>date('H:i:s'),'trackingHour'=>date('G'),'trackingStamp'=>time());
		$r = sqlite3_insertIntoTable($GLOBALS['api']['track']['table.track'],$track,$db,'tracking');
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		if(strpos($track['trackingReferer'],'.google.') && preg_match('/[&\?]q=([^&]+)&/',$track['trackingReferer'],$query)){
			//$query = urldecode($query[1]);
			//FIXME: falta trackingLang y trackingPosition
			//$queryGoogle = array('query'=>$query,'trackingIP'=>$track['trackingIP'],'trackingLang'=>'','trackingPosition'=>0,'trackingDate'=>$track['trackingDate'],'trackingTime'=>$track['trackingTime'],'trackingStamp'=>$track['trackingStamp']);
			//$r = sqlite3_insertIntoTable('queryGoogle',$queryGoogle,$db);
		}
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}sqlite3_close($db);}
		return true;
	}
?>
