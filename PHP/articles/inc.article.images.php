<?php
	function article_image_getSingle($whereClause,$params = array()){if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['articles']['db'];}$r = sqlite3_getSingle($GLOBALS['api']['articles']['table.images'],$whereClause,$params);return $r;}
	function article_image_getWhere($whereClause,$params = array()){if(!isset($params['indexBy'])){$params['indexBy'] = 'imageHash';}if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['articles']['db'];}$r = sqlite3_getWhere($GLOBALS['api']['articles']['table.images'],$whereClause,$params);return $r;}
	function article_image_getByHash($fileHash,$params = array()){return article_image_getSingle('(imageHash = \''.preg_replace('/[^a-z0-9]*/','',$fileHash).'\')',$params);}
	function article_image_getPath($articleID,$imageHash){return $GLOBALS['api']['articles']['dir.db'].$articleID.'/images/'.$imageHash.'/';}
	function article_image_save($articleID,$file = array(),$db = false){
		if(!isset($file['filePath']) || !file_exists($file['filePath'])){return array('errorDescription'=>'FILE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);}
		$info = @getimagesize($file['filePath']);if(!$info){return array('errorDescription'=>'INVALID_IMAGE','file'=>__FILE__,'line'=>__LINE__);}
		$fileHash = md5_file($file['filePath']);
		$fileName = (isset($file['fileName']) ? preg_replace('/[\/]*/','',$file['fileName']) : $fileHash);

		$imageOB = array('articleID'=>$articleID,
		'imageHash'=>$fileHash,'imageSize'=>filesize($file['filePath']),'imageWidth'=>$info[0],
		'imageHeight'=>$info[1],'imageMime'=>$info['mime'],
		'imageName'=>$fileName,
		'imageTitle'=>(isset($file['fileTitle']) ? $file['fileTitle'] : ''),
		'imageDescription'=>(isset($file['fileDescription']) ? $file['fileDescription'] : '')
		);

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable($GLOBALS['api']['articles']['table.images'],$imageOB,$db);
		if(isset($r['errorDescription'])){if($shouldClose){sqlite3_close($db);}return $r;}
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		if($shouldClose){sqlite3_close($db,true);if(!$r){return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}}

		/* INI-Compiamos la imagen a nuestro pool */
		$poolPath = $GLOBALS['api']['articles']['dir.db'].$articleID.'/images/orig/';
		if(!file_exists($poolPath)){$oldmask = umask(0);$r = @mkdir($poolPath,0777,1);umask($oldmask);if(!$r){return array('errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}}
		$destPath = $poolPath.$fileHash;$r = copy($file['filePath'],$destPath);
		$r = article_image_thumbs($destPath);
		/* END-Compiamos la imagen */

		return $imageOB;
	}
	function article_image_thumbs($fileNameStamp,$overWrite = false){
		$imgProp = @getimagesize($fileNameStamp);if($imgProp === false){return array('errorDescription'=>'NOT_A_VALID_IMAGE','file'=>__FILE__,'line'=>__LINE__);}

		$pool = $fileNameStamp;
		$fileName = basename($pool);
		$pool = dirname($pool);
		$a = basename($pool);if($a == 'orig'){$pool = dirname($pool);}
		$pool .= '/'.md5_file($fileNameStamp).'/';if(!file_exists($pool)){$oldmask = umask(0);$r = mkdir($pool,0777,1);umask($oldmask);if(!$r){return array('errorCode'=>2,'errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}}

		$fPath = $pool.'orig';
		copy($fileNameStamp,$fPath);
		include_once('inc.images.php');
		$r = image_convert($fPath,'jpeg');

		if($imgProp[0] < 1600 && $imgProp[1] < 1600){$res = image_mimeDecider($imgProp['mime'],$fPath);}
		else{$res = image_tooLarge($fPath,$imgProp);}

		$sizes = array('16','32','64','90','136','180','128x64','256x0','620x0','128p64');
		foreach($sizes as $k=>$size){
			$destPath = $pool.$size.'.jpeg';
			if($overWrite === false && file_exists($destPath)){continue;}
			if(!is_numeric($size[0])){unset($sizes[$k]);continue;}
			if(strpos($size,'x') !== false){$r = image_thumb($res,$destPath,$size);continue;}
			if(strpos($size,'p') !== false){$r = image_thumb_p($res,$destPath,$size);continue;}
			$r = image_square($res,$destPath,$size);
		}

		return true;
	}
