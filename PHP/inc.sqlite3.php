<?php
	$GLOBALS['SQLITE3'] = array('database'=>'database.db','databases'=>array(),'cachePath'=>'../db/cache/sqlite3/','queryRetries'=>20,'useCache'=>true);
	function sqlite3_open($filePath = false,$mode = 6){
		/* Mode 6 = (SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE) */
		$oldmask = umask(0);
		if(!file_exists($filePath)){$r = file_put_contents($filePath,'');if($r === false){return false;}}
		$filePath = realpath($filePath);
		$fileSum = md5($filePath);
		$fileName = basename($filePath);
		try{$db = new SQLite3($filePath,$mode);}
		catch(Exception $e){
			include_once('API_notifications.php');
			notifications_add(array('notificationTitle'=>'unable to open database','notificationFile'=>__FILE__,'notificationLine'=>__LINE__,'notificationModule'=>'sqlite'));
			return false;
		}
		$GLOBALS['SQLITE3']['databases'][$fileSum] = array('resource'=>$db,'filePath'=>$filePath,'fileName'=>$fileName,'fileSum'=>$fileSum);
		if($mode == 6 && !filesize($filePath)){$r = sqlite3_exec('PRAGMA main.page_size = 4096;PRAGMA main.cache_size=10000;PRAGMA main.locking_mode=EXCLUSIVE;PRAGMA main.synchronous=NORMAL;PRAGMA main.journal_mode=WAL;PRAGMA temp_store=MEMORY;',$db);}
		else if($mode == 6){$r = sqlite3_exec('PRAGMA temp_store=MEMORY;',$db);}
		umask($oldmask);
		return $db;
	}

	function sqlite3_close(&$db = false){
		foreach($GLOBALS['SQLITE3']['databases'] as $sum=>$database){
			if($database['resource'] === $db){unset($GLOBALS['SQLITE3']['databases'][$sum]);}
		}
		$db->close();
		$db = false;
		return true;
	}

	function sqlite3_cache_set($db,$table,$query,$data){
		$dbObj = false;foreach($GLOBALS['SQLITE3']['databases'] as $sum=>$database){if($database['resource'] === $db){$dbObj = $database;}}
		if(!$dbObj){return false;}
		$cachePath = $GLOBALS['SQLITE3']['cachePath'].'/'.$dbObj['fileSum'].'/'.md5($table).'/';
		if(!file_exists($cachePath)){$r = mkdir($cachePath,0777,1);}
		$cacheFile = $cachePath.md5($query);
		$r = file_put_contents($cacheFile,json_encode($data));
		return true;
	}
	function sqlite3_cache_get($db,$table,$query){
		$dbObj = false;foreach($GLOBALS['SQLITE3']['databases'] as $sum=>$database){if($database['resource'] === $db){$dbObj = $database;}}
		if(!$dbObj){return false;}
		$cacheFile = $GLOBALS['SQLITE3']['cachePath'].$dbObj['fileSum'].'/'.md5($table).'/'.md5($query);
		if(!file_exists($cacheFile)){return false;}
		return json_decode(file_get_contents($cacheFile),1);
	}
	function sqlite3_cache_destroy($db,$table = false,$query = false){
		$dbObj = false;foreach($GLOBALS['SQLITE3']['databases'] as $sum=>$database){if($database['resource'] === $db){$dbObj = $database;}}
		if(!$dbObj){return false;}
		$cachePath = $GLOBALS['SQLITE3']['cachePath'].'/'.$dbObj['fileSum'].'/';
		if($table != false){$cachePath .= md5($table).'/';}
		if($query != false){$cachePath .= md5($query);}
		if(!file_exists($cachePath)){return false;}
		sqlite3_helper_rm($cachePath);
		return true;
	}
	function sqlite3_config_cacheDisable(){
		if(!$GLOBALS['SQLITE3']['useCache']){return true;}
		$GLOBALS['SQLITE3']['useCacheOld'] = $GLOBALS['SQLITE3']['useCache'];
		$GLOBALS['SQLITE3']['useCache'] = false;
	}
	function sqlite3_config_cacheRestore(){
		if(!isset($GLOBALS['SQLITE3']['useCacheOld'])){return true;}
		$GLOBALS['SQLITE3']['useCache'] = $GLOBALS['SQLITE3']['useCacheOld'];
		unset($GLOBALS['SQLITE3']['useCacheOld']);
	}
	function sqlite3_helper_rm($path,$avoidCheck=false){
		//FIXME: el preg_Replace no tiene sentido
		if(!$avoidCheck){$path = preg_replace('/\/$/','/',$path);if(!file_exists($path)){return;}}
		if(!is_dir($path)){unlink($path);}
		if($handle = opendir($path)){while(false !== ($file = readdir($handle))){if(in_array($file,array('.','..'))){continue;}if(is_dir($path.$file)){sqlite3_helper_rm($path.$file.'/',true);continue;}unlink($path.$file);}closedir($handle);}
		rmdir($path);
	}

	function sqlite3_query($q,$db){$oldmask = umask(0);$r = @$db->query($q);$secure = 0;while($secure < 5 && !$r && $db->lastErrorCode() == 5){usleep(200000);$r = @$db->query($q);$secure++;}umask($oldmask);return $r;}
	function sqlite3_querySingle($q,$db){$oldmask = umask(0);$r = @$db->querySingle($q,1);$secure = 0;while($secure < $GLOBALS['SQLITE3']['queryRetries'] && !$r && $db->lastErrorCode() == 5){usleep(200000);$r = @$db->querySingle($q,1);$secure++;}umask($oldmask);return $r;}
	function sqlite3_exec($q,$db){$oldmask = umask(0);$r = @$db->exec($q);$secure = 0;while($secure < $GLOBALS['SQLITE3']['queryRetries'] && !$r && $db->lastErrorCode() == 5){usleep(200000);$r = @$db->exec($q);$secure++;}umask($oldmask);return $r;}
	function sqlite3_tableExists($tableName,$db = false){$row = sqlite3_querySingle('SELECT * FROM sqlite_master WHERE name = \''.$tableName.'\';',$db);return $row;}

	function sqlite3_createTable($tableName,$array,$db = false){
//FIXME: rehacer
		$shouldClose = false;if(!$db){$shouldClose = true;$db = sqlite3_open($GLOBALS['SQLITE3']['database']);}

		$query = 'CREATE TABLE \''.$tableName.'\' (';
		$tableKeys = array();
		$hasAutoIncrement = false;
		foreach($array as $k=>$v){
			if(preg_match('/^_[a-zA-Z0-9]*_$/',$k)){
				$key = preg_replace('/^_|_$/','',$k);
				if(strpos($v,'INTEGER AUTOINCREMENT') !== false){$query .= '\''.$key.'\' INTEGER PRIMARY KEY AUTOINCREMENT,';continue;}
				$query .= '\''.$key.'\' '.$v.',';$tableKeys[] = $key;continue;
			}
			$query .= '\''.$k.'\' '.$v.',';
		}
		if(count($tableKeys) > 0){$query .= 'PRIMARY KEY ('.implode(',',$tableKeys).'),';}
		$query = substr($query,0,-1).');';

		$r = @$db->exec($query);
		$ret = array('OK'=>$r,'error'=>$db->lastErrorMsg(),'errno'=>$db->lastErrorCode(),'query'=>$query);
		if($shouldClose){sqlite3_close($db);}
		return $ret;
	}

	function sqlite3_insertIntoTable($tableName,$array,$db = false,$aTableName = false){
		$shouldClose = false;if(!$db){$shouldClose = true;$db = sqlite3_open($GLOBALS['SQLITE3']['database']);sqlite3_exec('BEGIN',$db);}
		$tableKeys = array();foreach($array as $key=>$value){$array[$key] = $db->escapeString($value);if($key[0] == '_' && $key[strlen($key)-1] == '_'){$newkey = substr($key,1,-1);$tableKeys[$newkey] = $array[$newkey] = $value;unset($array[$key]);}}

		$query = 'INSERT INTO \''.$tableName.'\' ';
		$tableIds = $tableValues = '(';
		/* SQL uses single quotes to delimit string literals. */
		foreach($array as $key=>$value){$tableIds .= '\''.$key.'\',';$tableValues .= '\''.$value.'\',';}
		$tableIds = substr($tableIds,0,-1).')';$tableValues = substr($tableValues,0,-1).')';
		$query .= $tableIds.' VALUES '.$tableValues;

		$r = sqlite3_exec($query,$db);
		if(!$r && $db->lastErrorCode() == 1){
			if(!isset($GLOBALS['tables'][$tableName]) && !isset($GLOBALS['tables'][$aTableName])){if($shouldClose){sqlite3_close($db);}return array('OK'=>false,'id'=>false,'error'=>$db->lastErrorMsg(),'errno'=>$db->lastErrorCode(),'query'=>$query);}
			$ret = sqlite3_createTable($tableName,($aTableName ? $GLOBALS['tables'][$aTableName] : $GLOBALS['tables'][$tableName]),$db);if(!$ret['OK']){if($shouldClose){sqlite3_close($db);}return $ret;}
			$r = sqlite3_exec($query,$db);
		}

		$lastID = $db->lastInsertRowID();
		if(!$r && $db->lastErrorCode() == 19 && count($tableKeys)){
			if(substr($db->lastErrorMsg(),0,7) == 'column '){
				$columnName = substr($db->lastErrorMsg(),7,-14);
				if(!isset($tableKeys[$columnName])){return array('OK'=>$r,'id'=>$lastID,'error'=>$db->lastErrorMsg(),'errno'=>$db->lastErrorCode(),'query'=>$query);}
			}
			$query = 'UPDATE \''.$tableName.'\' SET ';
			$tableKeysValues = array_keys($tableKeys);
			foreach($array as $key=>$value){if(isset($tableKeys[$key])){continue;}$query .= '\''.$key.'\'=\''.$value.'\',';}
			$query = substr($query,0,-1).' WHERE';
			foreach($tableKeys as $k=>$v){$query .= ' '.$k.' = \''.$v.'\' AND';}
			$query = substr($query,0,-4).';';
			$r = sqlite3_exec($query,$db);
			$lastID = array_shift($tableKeys);
		}

		$ret = array('OK'=>$r,'id'=>$lastID,'error'=>$db->lastErrorMsg(),'errno'=>$db->lastErrorCode(),'query'=>$query);
		/* Da lo mismo que no se esté usando caché explícitamente, si se actualiza esta tabla debemos
		 * eliminar cualquier rastro de caché para evitar datos inválido al hacer consultas que podrian estar cacheadas */
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('OK'=>false,'errno'=>$GLOBALS['DB_LAST_ERRNO'],'error'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}$r = sqlite3_cache_destroy($db,$tableName);sqlite3_close($db);}
		return $ret;
	}

	function sqlite3_getSingle($tableName = false,$whereClause = false,$params = array()){
		if(!isset($params['indexBy'])){$params['indexBy'] = 'id';}
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['SQLITE3']['database'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$selectString = '*';if(isset($params['selectString'])){$selectString = $params['selectString'];}
		$GLOBALS['DB_LAST_QUERY'] = 'SELECT '.$selectString.' FROM '.$tableName.' '.(($whereClause !== false) ? 'WHERE '.$whereClause : '');
		if(isset($params['group'])){$GLOBALS['DB_LAST_QUERY'] .= ' GROUP BY '.$params['db']->escapeString($params['group']);}
		if(isset($params['order'])){$GLOBALS['DB_LAST_QUERY'] .= ' ORDER BY '.$params['db']->escapeString($params['order']);}
		if(isset($params['limit'])){$GLOBALS['DB_LAST_QUERY'] .= ' LIMIT '.$params['db']->escapeString($params['limit']);}
		$row = sqlite3_querySingle($GLOBALS['DB_LAST_QUERY'],$params['db']);
		$GLOBALS['DB_LAST_QUERY_ERRNO'] = $params['db']->lastErrorCode();
		$GLOBALS['DB_LAST_QUERY_ERROR'] = $params['db']->lastErrorMsg();
		if($shouldClose){sqlite3_close($params['db']);}
		return $row;
	}
	function sqlite3_getWhere($tableName = false,$whereClause = false,$params = array()){
		if(!isset($params['indexBy'])){$params['indexBy'] = 'id';}
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['DB_FILMCATALOG'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$selectString = '*';if(isset($params['selectString'])){$selectString = $params['selectString'];}
		$GLOBALS['DB_LAST_QUERY'] = 'SELECT '.$selectString.' FROM '.$tableName.' '.(($whereClause !== false) ? 'WHERE '.$whereClause : '');
		if(isset($params['group'])){$GLOBALS['DB_LAST_QUERY'] .= ' GROUP BY '.$params['db']->escapeString($params['group']);}
		if(isset($params['order'])){$GLOBALS['DB_LAST_QUERY'] .= ' ORDER BY '.$params['db']->escapeString($params['order']);}
		if(isset($params['limit'])){$GLOBALS['DB_LAST_QUERY'] .= ' LIMIT '.$params['db']->escapeString($params['limit']);}
		$r = sqlite3_query($GLOBALS['DB_LAST_QUERY'],$params['db']);
		$GLOBALS['DB_LAST_QUERY_ERRNO'] = $params['db']->lastErrorCode();
		$GLOBALS['DB_LAST_QUERY_ERROR'] = $params['db']->lastErrorMsg();
		$rows = array();
		if($r && $params['indexBy'] !== false){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[$row[$params['indexBy']]] = $row;}}
		if($r && $params['indexBy'] === false){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}
		if($shouldClose){sqlite3_close($params['db']);}
		return $rows;
	}

	//FIXME: rehacer
	/* $origTableName = string - $tableSchema = string ($GLOBALS['tables'][$tableSchema]) */
	function sqlite3_updateTableSchema($origTableName,$db = false,$tableID = 'id',$schemaName = false){
		$tableName = $origTableName;if(!$schemaName){$schemaName = $tableName;}
		$tableSchema = $GLOBALS['tables'][$schemaName];

		/* Averiguamos las keys automáticamente */
		$tableKeys = array();foreach($tableSchema as $key=>$value){if($key[0] == '_' && $key[strlen($key)-1] == '_'){$newkey = substr($key,1,-1);$tableKeys[$newkey] = $key;}}
		//print_r($tableKeys);

		$shouldClose = false;
		$fields = implode(',',array_diff(array_keys($tableSchema),array_values($tableKeys)));
		foreach($tableKeys as $k=>$v){$fields .= ','.$k.' as '.$v;}
		$continue = true;while($continue){
			//FIXME: esto podría ser querySingle
			$r = @$db->query('SELECT '.$fields.' FROM '.$origTableName);
			if($r){break;}
			if(!$r && substr($db->lastErrorMsg(),0,14) == 'no such column'){
				$errorField = substr($db->lastErrorMsg(),16);
				$fields = preg_replace('/(^|,)'.$errorField.'(,|$)/',',',$fields);
				if($fields[0] == ','){$fields = substr($fields,1);}
				continue;
			}
			if(!$r){
				echo 'error '.$db->lastErrorMsg();exit;
			}
		}

		$db->exec('BEGIN;');
		$r = $db->exec('ALTER TABLE '.$origTableName.' RENAME TO '.$origTableName.'_backup;');
		if(!$r){return json_encode(array('errorCode'=>1));}

		$a = sqlite3_query('SELECT '.$fields.' FROM '.$origTableName.'_backup;',$db);
		$rows = array();if($r){while($row = $a->fetchArray(SQLITE3_ASSOC)){
			$r = sqlite3_insertIntoTable($tableName,$row,$db);
			if(!$r['OK']){if($shouldClose){$db->close();}return array('errorCode'=>$r['errno'],'errorDescripcion'=>$r['error'],'query'=>$r['query'],'file'=>__FILE__,'line'=>__LINE__);}
		}}

		$oldCount = sqlite3_querySingle('SELECT count(*) as count FROM '.$origTableName.'_backup;',$db);
		$newCount = sqlite3_querySingle('SELECT count(*) as count FROM '.$origTableName.';',$db);
		if($oldCount['count'] != $newCount['count']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>3,'errorDescripcion'=>'COUNT_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		$r = $db->exec('DROP TABLE IF EXISTS '.$origTableName.'_backup');
		$db->exec('COMMIT;');

		$r = sqlite3_cache_destroy($db,$origTableName);
		if($shouldClose){$db->close();}
		return json_encode(array("errorCode"=>(int)0));
	}
?>
