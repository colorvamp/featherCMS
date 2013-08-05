<?php
	$GLOBALS['tables']['systemUsers'] = array('_userMail_'=>'TEXT NOT NULL','userPass'=>'TEXT NOT NULL','userWord'=>'TEXT NOT NULL',
		'userName'=>'TEXT NOT NULL','userRegistered'=>'TEXT NOT NULL',
		'userBirthday'=>'TEXT','userGender'=>'TEXT','userNick'=>'TEXT','userWeb'=>'TEXT','userBio'=>'TEXT','userPhrase'=>'TEXT','userModes'=>'TEXT',
		'userStatus'=>'TEXT','userTags'=>'TEXT','userCode'=>'TEXT');
	$GLOBALS['api']['users'] = array('db'=>'../db/api.users.db','table'=>'systemUsers');
	include_once('inc.sqlite3.php');

	/* Necesitamos una doble sincronización, no podemos depender de un 
	 * repositorio único porque se saturaría por las reiteradas peticiones
	 * de los distintos usuarios.
	 */
	function users_create($data,$db = false){
		$valid = array('userName'=>0,'userBirth'=>0,'userMail'=>0,'userPass'=>0,'userGender'=>0,'userNick'=>0);
		include_once('inc.strings.php');
		foreach($data as $k=>$v){if(!isset($valid[$k])){unset($data[$k]);continue;}$data[$k] = strings_UTF8Encode($v);}
		$pass_a = array('?','$','¿','!','¡','{','}');
	    	$pass_b = array('a','e','i','o','u','b','c','d','f','g','h','j','k','l','m','n','p','q','r','s','t','v','w','x','y','z');
		$magicWordPre = '';for($a=0; $a<4; $a++){$magicWordPre .= $pass_a[array_rand($pass_a)];$magicWordPre .= $pass_b[array_rand($pass_b)];}

		/* Necesitamos tener la conexión con la base de datos desde aquí para las comprobaciones de algunos campos */
		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['users']['db']);sqlite3_exec('BEGIN',$db);$shouldClose = true;}
		$data['userName'] = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚ ,]*/','',$data['userName']);
		if(empty($data['userName'])){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>1,'errorDescription'=>'NAME_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		if(!preg_match('/^[a-z0-9\._\+\-]+@[a-z0-9\.\-]+\.[a-z]{2,6}$/',$data['userMail'])){return array('errorCode'=>1,'errorDescription'=>'EMAIL_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		/* Comprobamos mail duplicado */
		if(users_getSingle('(userMail = \''.$data['userMail'].'\')',array('db'=>$db))){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>1,'errorDescription'=>'EMAIL_DUPLICATED','file'=>__FILE__,'line'=>__LINE__);}
		if(!isset($data['userPass']) || empty($data['userPass'])){$data['userPass'] = '';for($a=0; $a<6; $a++){$data['userPass'] .= $pass_a[array_rand($pass_a)];$data['userPass'] .= $pass_b[array_rand($pass_b)];}}
		$data['userPass'] = sha1($data['userPass']);

		if(!isset($data['userNick']) || empty($data['userNick'])){$data['userNick'] = sha1($data['userMail'].$data['userName']);}
		$date = date('Y-m-d H:i:s');
		$userCode = users_helper_generateCode($data['userMail']);
		$data = array_merge($data,array('userWord'=>$magicWordPre,'userRegistered'=>$date,'userStatus'=>0,'userModes'=>',regular,','userCode'=>$userCode));

		$r = sqlite3_insertIntoTable($GLOBALS['api']['users']['table'],$data,$db);
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		$user = users_getSingle('(userMail = \''.$data['userMail'].'\')',array('db'=>$db));
		$user = array_merge($user,array('userCode'=>$userCode));
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}$r = sqlite3_cache_destroy($db,$GLOBALS['api']['users']['table']);sqlite3_close($db);}
		return $user;
	}
	function users_remove($userMail,$db = false){
		if(!preg_match('/^[a-z0-9\._\+\-]+@[a-z0-9\.\-]+\.[a-z]{2,6}$/',$userMail)){return array('errorDescription'=>'EMAIL_ERROR','file'=>__FILE__,'line'=>__LINE__);}

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['users']['db']);sqlite3_exec('BEGIN',$db);$shouldClose = true;}
		$GLOBALS['DB_LAST_QUERY'] = 'DELETE FROM '.$GLOBALS['api']['users']['table'].' WHERE userMail = \''.$userMail.'\';';
		$r = sqlite3_exec($GLOBALS['DB_LAST_QUERY'],$db);
		$changes = $db->changes();
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}$r = sqlite3_cache_destroy($db,$GLOBALS['api']['users']['table']);sqlite3_close($db);}
		if(!$changes){return array('errorDescription'=>'USER_NOT_FOUND','file'=>__FILE__,'line'=>__LINE__);}

		//FIXME: si existe la carpeta de usuario, debemos eliminarla tb
		$userFolder = '../db/users/'.$userMail;
		if(file_exists($userFolder)){

		}

		return true;
	}
	function users_update($userMail,$data = array(),$db = false){
		include_once('inc.strings.php');
		$data['_userMail_'] = $userMail;
		if(isset($data['userBirth_day']) && isset($data['userBirth_month']) && isset($data['userBirth_year'])){$data['userBirth'] = $data['userBirth_year'].'-'.$data['userBirth_month'].'-'.$data['userBirth_day'];unset($data['userBirth_year'],$data['userBirth_month'],$data['userBirth_day']);}

		/* VALIDATION */
		if(isset($data['userName'])){$data['userName'] = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚ, ]*/','',strings_UTF8Encode($data['userName']));}
		if(isset($data['userPass'])){$data['userPass'] = sha1($data['userPass']);}
		if(isset($data['userBirth'])){$data['userBirth'] = preg_replace('/[^0-9\-]*/','',$data['userBirth']);}
		if(isset($data['userBirth']) && (!preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/',$data['userBirth']) || strtotime($data['userBirth']) < 1)){return array('errorCode'=>2,'errorDescription'=>'USERBIRTH_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		if(isset($data['userLat']) || isset($data['userLng'])){$data['userLocationUpdated'] = time();}

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['users']['db']);sqlite3_exec('BEGIN',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable($GLOBALS['api']['users']['table'],$data,$db);
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		$user = users_getSingle('(userMail = \''.$data['_userMail_'].'\')',array('db'=>$db));
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}$r = sqlite3_cache_destroy($db,$GLOBALS['api']['users']['table']);sqlite3_close($db);}
		if(isset($GLOBALS['user']) && $GLOBALS['user']['userMail'] == $userMail){$GLOBALS['user'] = $user;}
		return $user;
	}
	function users_getSingle($whereClause = false,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['users']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'userMail';}
		$r = sqlite3_getSingle($GLOBALS['api']['users']['table'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function users_getWhere($whereClause = false,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['users']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'userMail';}
		$r = sqlite3_getWhere($GLOBALS['api']['users']['table'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function users_helper_generateCode($userMail){$userCode = sha1($userMail.time().date('Y-m-d H:i:s'));return $userCode;}
	function users_login($userMail,$userPass,$db = false){
		if(empty($userMail)){return false;}
		$userPass = sha1($userPass);

		$shouldClose = false;if($db == false){$db = sqlite3_open($GLOBALS['api']['users']['db']);$shouldClose = true;}
		$user = users_getSingle('(userMail = \''.$db->escapeString($userMail).'\' AND userPass = \''.$db->escapeString($userPass).'\')',array('db'=>$db));
		if(!$user){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>1,'errorDescription'=>'WRONG_USER_OR_PASS','file'=>__FILE__,'line'=>__LINE__);}
		/* Puede que el usuario no esté confirmado, en dicho caso no se permite loguear */
		if(!isset($user['userStatus']) || empty($user['userStatus'])){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>1,'errorDescription'=>'USER_NOT_ACTIVE','file'=>__FILE__,'line'=>__LINE__);}
		//FIXME: TODO
		//$user = users_update($user['id'],array('userIP'=>$_SERVER['REMOTE_ADDR'],'userLastLogin'=>date('Y-m-d H:i:s')),$db,true);
		if($shouldClose){sqlite3_close($db);}
		if(isset($user['errorDescription'])){return $user;}
		$_SESSION['user'] = $GLOBALS['user'] = $user;
		return $user;
	}
	function users_logout(){
		session_destroy();
	}
	function users_isLogged($db = false){
		if(isset($GLOBALS['user']) && is_array($GLOBALS['user'])){return true;}
		if(isset($_SESSION['user']) && is_array($_SESSION['user'])){$GLOBALS['user'] = $_SESSION['user'];$GLOBALS['userPath'] = '../db/users/'.$_SESSION['user']['userMail'].'/';return true;}
		//FIXME: faltaría revisar cookies
		return false;
	}
	function users_checkModes($mode){
		if(!isset($GLOBALS['user'])){return false;}
		return (strpos($GLOBALS['user']['userModes'],','.$mode.',') !== false);
	}
?>
