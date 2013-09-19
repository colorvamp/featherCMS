<?php
	$GLOBALS['tables']['requests'] = array('_id_'=>'INTEGER AUTOINCREMENT',
		'requestPID'=>'TEXT','requestLock'=>'TEXT',
		'requestModule'=>'TEXT','requestCall'=>'TEXT','requestParams'=>'TEXT','requestStatus'=>'INTEGER',
		'requestUser'=>'INTEGER','requestDate'=>'TEXT','requestTime'=>'TEXT','requestEndDate'=>'TEXT','requestEndTime'=>'TEXT',
		'requestTimeLimit'=>'TEXT','requestMsg'=>'TEXT'
	);
	$GLOBALS['api']['requests'] = array(
		'enabled'=>true,'ghostRequests'=>array(),'lastTick'=>false,
		'db'=>'../db/inc.requests.db','table'=>'requests',
		'dir.proc'=>'../db/proc/','dir.signals'=>'../db/proc/signals/');
	if(file_exists('../../db')){$GLOBALS['api']['requests'] = array_merge($GLOBALS['api']['requests'],array('db'=>'../../db/inc.requests.db','dir.proc'=>'../../db/proc/','dir.signals'=>'../../db/proc/signals/'));}

	function requests_disable(){
		if(!$GLOBALS['api']['requests']['enabled']){return true;}
		$GLOBALS['api']['requests']['enabledOld'] = $GLOBALS['api']['requests']['enabled'];
		$GLOBALS['api']['requests']['enabled'] = false;
		return true;
	}
	function requests_restore(){
		if(!isset($GLOBALS['api']['requests']['enabledOld'])){return true;}
		$GLOBALS['api']['requests']['enabled'] = $GLOBALS['api']['requests']['enabledOld'];
		unset($GLOBALS['api']['requests']['enabledOld']);
		return true;
	}
	function requests_create($params,$db = false){
		if(!$GLOBALS['api']['requests']['enabled']){$id = uniqid();$GLOBALS['api']['requests']['ghostRequests'][$id] = 1;return array('id'=>$id);}
		if(!file_exists($GLOBALS['api']['requests']['dir.proc'])){$oldmask = umask(0);$r = mkdir($GLOBALS['api']['requests']['dir.proc']);umask($oldmask);}
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['requests']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}

		/* Validaciones */
		$_validStatus = array('awaiting'=>0,'running'=>0);
		if(isset($params['requestStatus']) && !isset($_validStatus[$params['requestStatus']])){unset($params['requestStatus']);}
		if(!isset($params['requestStatus'])){$params['requestStatus'] = 'awaiting';}
		/* Si la tarea está pendiente no puede tomar pID aún */
		if($params['requestStatus'] == 'pending' && isset($params['requestPID'])){unset($params['requestPID']);}
		if($params['requestStatus'] == 'running'){
			$params['requestPID'] = getmypid();
			$proc = requests_getSingle('(requestPID = '.$params['requestPID'].')',array('db'=>$db));if(!$proc && $GLOBALS['DB_LAST_QUERY_ERRNO'] == 5){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}
			if($proc){$r = requests_finish($proc['id'],$db);}
		}
		if(isset($params['requestParams']) && is_array($params['requestParams'])){
			//FIXME: si son resources?
			$params['requestParams'] = json_encode($params['requestParams']);
		}
		if(!isset($params['requestDate'])){$params['requestDate'] = date('Y-m-d');}
		if(!isset($params['requestTime'])){$params['requestTime'] = date('H:i:s');}

		include_once('inc.sqlite3.php');
		$r = sqlite3_insertIntoTable($GLOBALS['api']['requests']['table'],$params,$db);
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		$params['id'] = $r['id'];
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}sqlite3_close($db);}

		/* Registramos la función de salida */
		//FIXME: solo deberíamos registrarla una vez
		if($params['requestStatus'] == 'running'){
			$r = register_shutdown_function('requests_callbacks_onShutdown');
			$r = register_tick_function('requests_callbacks_onTick');
			$oldmask = umask(0);$r = file_put_contents($GLOBALS['api']['requests']['dir.proc'].$params['requestPID'],'');umask($oldmask);
		}
		if(isset($GLOBALS['cli'])){
			declare(ticks = 1);
			/* Registramos funciones para CONTROL+C */
			pcntl_signal(SIGINT,function(){exit;});  
			pcntl_signal(SIGTERM,function(){exit;});
		}
		return $params;
	}
	function requests_update($params,$db = false){
if(!$GLOBALS['api']['requests']['enabled']){
//FIXME:TODO
return true;}
		if(isset($params['id'])){$params['_id_'] = $params['id'];unset($params['id']);}
		if(isset($params['requestStatus'])){
			if($params['requestStatus'] == 'running'){$pid = getmypid();$params['requestPID'] = $pid;$oldmask = umask(0);$r = file_put_contents($GLOBALS['api']['requests']['dir.proc'].$pid,'');umask($oldmask);}
			//FIXME: cuando sea finished eliminamos la carpeta
		}
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($db) || !$db){$db = sqlite3_open($GLOBALS['api']['requests']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable('requests',$params,$db);
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();sqlite3_close($db);if(!$r){return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}}
		return true;
	}

	function requests_finish($rID,$db = false){
//FIXME: hacerlo a través de update
		if(!$GLOBALS['api']['requests']['enabled']){unset($GLOBALS['api']['requests']['enabled'][$rID]);return true;}
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['requests']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$params = array('_id_'=>$rID,'requestEndDate'=>date('Y-m-d'),'requestEndTime'=>date('H:i:s'),'requestStatus'=>'finished');
		$r = sqlite3_insertIntoTable('requests',$params,$db);
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();sqlite3_close($db);if(!$r){return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}}
//if(file_exists($GLOBALS['api']['requests']['dir.proc'].$req['requestPID'])){unlink($GLOBALS['api']['requests']['dir.proc'].$req['requestPID']);}
		return true;
	}

	function requests_getSingle($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['requests']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = sqlite3_getSingle($GLOBALS['api']['requests']['table'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function requests_getWhere($whereClause = false,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['requests']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = sqlite3_getWhere($GLOBALS['api']['requests']['table'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function requests_deleteWhere($whereClause = false,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['requests']['db']);$r = sqlite3_exec('BEGIN;',$params['db']);$shouldClose = true;}
		$GLOBALS['DB_LAST_QUERY'] = 'DELETE FROM '.$GLOBALS['api']['requests']['table'].' WHERE '.$whereClause.';';
		$ret = sqlite3_exec($GLOBALS['DB_LAST_QUERY'],$params['db']);
		$GLOBALS['DB_LAST_QUERY_ERRNO'] = $params['db']->lastErrorCode();
		$GLOBALS['DB_LAST_QUERY_ERROR'] = $params['db']->lastErrorMsg();
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$params['db']);sqlite3_close($params['db']);}
		return $ret;
	}
	function requests_getSingle_byPid($pid,$db = false){return requests_getSingle('(requestPID = '.$pid.')',array('db'=>$db));}
	function requests_processExists($pid){return file_exists('/proc/'.$pid);}
	function requests_cleanup($db = false){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['requests']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$rows = requests_getWhere('(requestStatus = \'running\')',array('db'=>$db));
		/* Al menos eliminamos los que no estén corriendo */
		foreach($rows as $k=>$row){if(!requests_processExists($row['requestPID'])){
			$r = requests_finish($row['id'],$db);if(isset($r['errorDescription'])){return $r;}
			if(file_exists($GLOBALS['api']['requests']['dir.proc'].$row['requestPID'])){unlink($GLOBALS['api']['requests']['dir.proc'].$row['requestPID']);}
		}unset($rows[$k]);}
		/* Se entiende que después de la limpia anterior, los procesos que han
		 * quedado es porque aún están corriendo en este momento, enviamos la señal
		 * de terminar la ejecución a los procesos que lleven en ejecución más de
		 * 24h */
		$dateLimit = date('Y-m-d',strtotime('-1 day'));
		foreach($rows as $k=>$row){if($row['requestDate'] < $dateLimit){$r = posix_kill($row['requestPID'],SIGTERM);}}
		/* Eliminamos la información de procesos de hace más de 2 días */
		$GLOBALS['DB_LAST_QUERY'] = 'DELETE FROM requests WHERE requestStatus = \'finished\' AND requestDate < \''.$dateLimit.'\';';
		$r = sqlite3_exec($GLOBALS['DB_LAST_QUERY'],$db);
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();sqlite3_close($db);if(!$r){return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}}
		/* Por último vamos a matar los procesos que no pertenezcan al día actual */
		
		return true;
	}
	function requests_callbacks_onShutdown(){
		chdir(dirname(__FILE__));
		$pid = getmypid();
		$req = requests_getSingle_byPid($pid);
		if($req){$r = requests_finish($req['id']);}
		else{echo 'Process not found: '.$pid.PHP_EOL;}
		$signal_stop = $GLOBALS['api']['requests']['dir.signals'].'stop'.$pid;
		if(file_exists($signal_stop)){unlink($signal_stop);}
	}
	function requests_callbacks_onTick(){
		if(!empty($GLOBALS['api']['requests']['lastTick']) && (time()-$GLOBALS['api']['requests']['lastTick']) < 30){return false;}
		$GLOBALS['api']['requests']['lastTick'] = time();
		chdir(dirname(__FILE__));
		$pid = getmypid();
		if(file_exists($GLOBALS['api']['requests']['dir.signals'].'stop'.$pid)){exit;}
	}
?>
