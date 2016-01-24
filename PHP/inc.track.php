<?php
	$GLOBALS['tables']['tracking'] = array('_id_'=>'INTEGER AUTOINCREMENT','trackingUser'=>'TEXT',
		'trackingIP'=>'TEXT','trackingUserAgent'=>'TEXT','trackingURL'=>'TEXT','trackingMS'=>'INTEGER DEFAULT 0','trackingReferer'=>'TEXT',
		'trackingDate'=>'DATE','trackingTime'=>'TEXT','trackingHour'=>'INTEGER','trackingStamp'=>'TEXT','trackingTag'=>'TEXT');
	$GLOBALS['tables']['tracking.url.date.count'] = array('_trackingURL_'=>'TEXT NOT NULL','_trackingDate_'=>'TEXT NOT NULL','trackingCount'=>'INTEGER');
	if(!isset($GLOBALS['api']['track'])){$GLOBALS['api']['track'] = array();}
	$GLOBALS['api']['track'] = array_merge($GLOBALS['api']['track'],array(
		'dir.track'=>'../db/api.track/',
		'db.track'=>'../db/api.track.db',
		'db.tmp'=>'../db/api.track.tmp.db',
		'table.track'=>'track',
		'table.day.count'=>'day.count','table.month.count'=>'month.count','table.year.count'=>'year.count'
	));
	if(is_writable('/dev/shm/')){do{
		$f = '/dev/shm/'.$_SERVER['SERVER_NAME'].'/';if(!file_exists($f)){$oldmask = umask(0);$r = @mkdir($f,0777,1);umask($oldmask);if(!$r){break;}}
		$GLOBALS['api']['track']['db.tmp'] = $f.'api.track.tmp.db';
	}while(false);}
	if(is_writable('/run/shm/')){do{
		$f = '/run/shm/'.$_SERVER['SERVER_NAME'].'/';if(!file_exists($f)){$oldmask = umask(0);$r = @mkdir($f,0777,1);umask($oldmask);if(!$r){break;}}
		$GLOBALS['api']['track']['db.tmp'] = $f.'api.track.tmp.db';
	}while(false);}
	if(file_exists('../../db')){
		$GLOBALS['api']['track'] = array_merge($GLOBALS['api']['track'],array(
			'db.track'=>'../../db/api.track.db'
		));
	}

	function tracking_mongo_touch($params = array()){
		if(!function_exists('mongo_get')){include_once('inc.mongo.php');}
		$trackOB = array('trackingUser'=>(isset($GLOBALS['user']) ? $GLOBALS['user']['userNick'] : 0),
			'trackingIP'=>$_SERVER['REMOTE_ADDR'],
			'trackingUserAgent'=>utf8_encode(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown'),
			'trackingURL'=>'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
			'trackingMS'=>(isset($params['trackingMS']) ? $params['trackingMS'] : 0),
			'trackingReferer'=>(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
			'trackingDate'=>date('Y-m-d'),'trackingTime'=>date('H:i:s'),'trackingHour'=>date('G'),'trackingStamp'=>time());

		//$db = mongo_get();
		//$collection = $db->selectCollection('tracebat',$_SERVER['SERVER_NAME']);
		/*$collection->ensureIndex(array('trackingIP'=>1));
		$collection->ensureIndex(array('trackingUserAgent'=>1));
		$collection->ensureIndex(array('trackingURL'=>1));
		$collection->ensureIndex(array('trackingMS'=>1));
		$collection->ensureIndex(array('trackingDate'=>1));
		$collection->ensureIndex(array('trackingTime'=>1));
		$collection->ensureIndex(array('trackingHour'=>1));*/
		//$collection->save($trackOB);
		return true;
	}

	function tracking_touch($params = array(),$db = false){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		$track = array('trackingUser'=>(isset($GLOBALS['user']) ? $GLOBALS['user']['userNick'] : 0),
			'trackingIP'=>$_SERVER['REMOTE_ADDR'],
			'trackingUserAgent'=>(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown'),
			'trackingURL'=>'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
			'trackingMS'=>(isset($params['trackingMS']) ? $params['trackingMS'] : 0),
			'trackingReferer'=>(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
			'trackingDate'=>date('Y-m-d'),'trackingTime'=>date('H:i:s'),'trackingHour'=>date('G'),'trackingStamp'=>time());

		$params = array('db.file'=>$GLOBALS['api']['track']['db.tmp']);
		$r = sqlite3_insertIntoTable2($GLOBALS['api']['track']['table.track'],$track,$params,'tracking');
		if(strpos($track['trackingReferer'],'.google.') && preg_match('/[&\?]q=([^&]+)&/',$track['trackingReferer'],$query)){
			//$query = urldecode($query[1]);
			//FIXME: falta trackingLang y trackingPosition
			//$queryGoogle = array('query'=>$query,'trackingIP'=>$track['trackingIP'],'trackingLang'=>'','trackingPosition'=>0,'trackingDate'=>$track['trackingDate'],'trackingTime'=>$track['trackingTime'],'trackingStamp'=>$track['trackingStamp']);
			//$r = sqlite3_insertIntoTable('queryGoogle',$queryGoogle,$db);
		}
		return true;
	}
	function tracking_getSingle($whereClause = false,$params = array()){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['track']['db.track'];}
		return sqlite3_getSingle($GLOBALS['api']['track']['table.track'],$whereClause,$params);
	}
	function tracking_getWhere($whereClause = false,$params = array()){if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['track']['db.track'];}return sqlite3_getWhere($GLOBALS['api']['track']['table.track'],$whereClause,$params);}

	function tracking_process($db = false){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		/* Obtenemos un número limitado de elementos que van a ser procesados */
		$currentDate = date('Y-m-d');
		$rows = tracking_getWhere('(trackingDate < \''.$currentDate.'\')',array('limit'=>50,'order'=>'trackingStamp ASC','db.file'=>$GLOBALS['api']['track']['db.tmp']));
		if(!$rows){return true;}
		if(($row = current($rows)) && !isset($row['trackingMS'])){return tracking_updateSchema();}

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['track']['db.track']);sqlite3_exec('BEGIN',$db);$shouldClose = true;}
		foreach($rows as $k=>$row){
//FIXME: comprobar errores, si hay errores
			/* INI-Count-day */
			$m = sqlite3_getSingle($GLOBALS['api']['track']['table.day.count'],'(trackingURL = \''.$row['trackingURL'].'\' AND trackingDate = \''.$row['trackingDate'].'\')',array('db'=>$db,'db.file'=>$GLOBALS['api']['track']['db.track']));
			$data = array('_trackingURL_'=>$row['trackingURL'],'_trackingDate_'=>$row['trackingDate'],'trackingCount'=>($m ? $m['trackingCount']+1 : 1));
			$r = sqlite3_insertIntoTable2($GLOBALS['api']['track']['table.day.count'],$data,array('db'=>$db),'tracking.url.date.count');
			/* END-Count-day */
			/* INI-Count-month */
			$d = substr($row['trackingDate'],0,-3);
			$m = sqlite3_getSingle($GLOBALS['api']['track']['table.month.count'],'(trackingURL = \''.$row['trackingURL'].'\' AND trackingDate = \''.$d.'\')',array('db'=>$db,'db.file'=>$GLOBALS['api']['track']['db.track']));
			$data = array('_trackingURL_'=>$row['trackingURL'],'_trackingDate_'=>$d,'trackingCount'=>($m ? $m['trackingCount']+1 : 1));
			$r = sqlite3_insertIntoTable2($GLOBALS['api']['track']['table.month.count'],$data,array('db'=>$db),'tracking.url.date.count');
			/* END-Count-month */
			/* INI-Count-year */
			$d = substr($row['trackingDate'],0,-6);
			$m = sqlite3_getSingle($GLOBALS['api']['track']['table.year.count'],'(trackingURL = \''.$row['trackingURL'].'\' AND trackingDate = \''.$d.'\')',array('db'=>$db,'db.file'=>$GLOBALS['api']['track']['db.track']));
			$data = array('_trackingURL_'=>$row['trackingURL'],'_trackingDate_'=>$d,'trackingCount'=>($m ? $m['trackingCount']+1 : 1));
			$r = sqlite3_insertIntoTable2($GLOBALS['api']['track']['table.year.count'],$data,array('db'=>$db),'tracking.url.date.count');
			/* END-Count-year */
			$r = sqlite3_insertIntoTable2($GLOBALS['api']['track']['table.track'],$row,array('db'=>$db),'tracking');
		}
		if($shouldClose){$r = sqlite3_close($db,true);if(!$r){return array('errorCode'=>$GLOBALS['DB_QUERY_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_QUERY_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}}
		$r = sqlite3_deleteWhere($GLOBALS['api']['track']['table.track'],'(id IN ('.implode(',',array_keys($rows)).'))',array('db.file'=>$GLOBALS['api']['track']['db.tmp']));
		return true;
	}

	function tracking_updateSchema($db = false){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if($db == false){$db = sqlite3_open($GLOBALS['api']['track']['db.track']);$shouldClose = true;}
		$r = sqlite3_updateTableSchema($GLOBALS['api']['track']['table.track'],$db,'id','tracking');
		if($shouldClose){sqlite3_close($db);}

		$shouldClose = false;if($db == false){$db = sqlite3_open($GLOBALS['api']['track']['db.tmp']);$shouldClose = true;}
		$r = sqlite3_updateTableSchema($GLOBALS['api']['track']['table.track'],$db,'id','tracking');
		if($shouldClose){sqlite3_close($db);}
		return true;
	}
