<?php
	function article_file_getSingle($whereClause,$params = array()){if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['articles']['db'];}return sqlite3_getSingle($GLOBALS['api']['articles']['table.files'],$whereClause,$params);}
	function article_file_getWhere($whereClause,$params = array()){if(!isset($params['indexBy'])){$params['indexBy'] = 'fileHash';}if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['articles']['db'];}return sqlite3_getWhere($GLOBALS['api']['articles']['table.files'],$whereClause,$params);}
	function article_file_getByHash($fileHash,$params = array()){return article_file_getSingle('(fileHash = \''.preg_replace('/[^a-z0-9]*/','',$fileHash).'\')',$params);}
	function article_file_deleteWhere($whereClause,$params = array()){if(!isset($params['indexBy'])){$params['indexBy'] = 'fileHash';}if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['articles']['db'];}$r = sqlite3_deleteWhere($GLOBALS['api']['articles']['table.files'],$whereClause,$params);return $r;}
	function article_file_save($articleID,$file = array(),$db = false){
		if(!isset($file['filePath']) || !file_exists($file['filePath'])){return array('errorDescription'=>'FILE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);}
		$finfo = finfo_open(FILEINFO_MIME);list($fileMime) = explode('; ',finfo_file($finfo,$file['filePath']));finfo_close($finfo);
		$fileHash = md5_file($file['filePath']);
		$fileName = (isset($file['fileName']) ? preg_replace('/[\/]*/','',$file['fileName']) : $fileHash);

		$fileOB = array('articleID'=>$articleID,
		'fileHash'=>$fileHash,'fileSize'=>filesize($file['filePath']),'fileMime'=>$fileMime,
		'fileName'=>$fileName,
		'fileTitle'=>(isset($file['fileTitle']) ? $file['fileTitle'] : ''),
		'fileDescription'=>(isset($file['fileDescription']) ? $file['fileDescription'] : '')
		);

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable($GLOBALS['api']['articles']['table.files'],$fileOB,$db);
		if(isset($r['errorDescription'])){if($shouldClose){sqlite3_close($db);}return $r;}
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		if($shouldClose){sqlite3_close($db,true);if(!$r){return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}}

		/* INI-Movemos el fichero */
		$sourceFile = $file['filePath'];
		$poolPath = $GLOBALS['api']['articles']['dir.db'].$articleID.'/files/';
		if(!file_exists($poolPath)){$oldmask = umask(0);$r = @mkdir($poolPath,0777,1);umask($oldmask);if(!$r){return array('errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}}
		$destPath = $poolPath.$fileHash;$r = rename($file['filePath'],$destPath);$file['filePath'] = $destPath;
		if($fileMime == 'image/png' || $fileMime == 'image/jpeg'){$r = article_image_save($articleID,$file);if(isset($r['errorDescription'])){return $r;}}
		/* END-Movemos el fichero */

		return $fileOB;
	}
