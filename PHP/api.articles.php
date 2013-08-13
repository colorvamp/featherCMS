<?php
	$GLOBALS['tables']['articleStorage'] = array('_id_'=>'INTEGER AUTOINCREMENT','articleName'=>'TEXT NOT NULL','articleTitle'=>'TEXT NOT NULL','articleAuthor'=>'TEXT NOT NULL','articleUserAlias'=>'TEXT NOT NULL','articleModes'=>'TEXT','articleDate'=>'TEXT NOT NULL','articleTime'=>'TEXT NOT NULL','articleTags'=>'TEXT','articleSnippet'=>'TEXT',
		'articleText'=>'TEXT',
		'articleSnippetImage'=>'INTEGER','articleModificationAuthor'=>'TEXT',
		'articleModificationUserAlias'=>'TEXT','articleModificationDate'=>'TEXT','articleCommentsCount'=>'INTEGER','articleHardLink'=>'TEXT','articleIsDraft'=>'INTEGER');
	$GLOBALS['tables']['images'] = array('_articleID_'=>'INTEGER NOT NULL','_imageHash_'=>'TEXT NOT NULL',
		'imageSize'=>'TEXT NOT NULL','imageWidth'=>'TEXT NOT NULL','imageHeight'=>'TEXT',
		'imageMime'=>'TEXT NOT NULL','imageName'=>'TEXT NOT NULL','imageTitle'=>'TEXT','imageDescription'=>'TEXT');
	$GLOBALS['DB_ARTICLESTORAGE'] = '../db/articles/';
	$GLOBALS['api']['articles'] = array('db'=>'../db/articles_ES.db','dirDB'=>'../db/articles/','table.articles'=>'articleStorage','table.publishers'=>'publishers','table.images'=>'images');
	if(file_exists('../../db')){$GLOBALS['api']['articles']['db'] = '../../db/articles_ES.db';}

	function articles_helper_getPath($article){
		$d = explode('-',$article['articleDate']);
		$articlePath = '../db/articles/'.$d[0].'.'.$d[1].'/'.$d[2].'.'.$article['articleName'].'/';
		return $articlePath;
	}
	function articles_updateSchema(){
return;
		$origTableName = 'articleStorage';
		$db = sqlite3_open($GLOBALS['api']['articles']['db']);
		$r = sqlite3_exec('BEGIN;',$db);

		$a = sqlite3_query('SELECT * FROM '.$origTableName.';',$db);
		$rows = array();if($r){while($row = $a->fetchArray(SQLITE3_ASSOC)){
			$path = articles_helper_getPath($row);
			if(!isset($row['articleText'])){$file = $path.'index.html';if(file_exists($file)){$row['articleText'] = file_get_contents($file);}}
			$r = sqlite3_insertIntoTable($origTableName.'1',$row,$db,$origTableName);
			if(!$r['OK']){sqlite3_close($db);return array('errorCode'=>$r['errno'],'errorDescripcion'=>$r['error'],'query'=>$r['query'],'file'=>__FILE__,'line'=>__LINE__);}
		}}

		$r = sqlite3_exec('COMMIT;',$db);
		sqlite3_close($db);
	}

	function articles_getSingle($whereClause = false,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'id';}
		$r = sqlite3_getSingle($GLOBALS['api']['articles']['table.articles'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function articles_getWhere($whereClause = false,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'id';}
		$r = sqlite3_getWhere($GLOBALS['api']['articles']['table.articles'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function articles_save($params = array(),$db = false){
		if(isset($params['id'])){$params['_id_'] = $params['id'];unset($params['id']);}
		$_valid = $GLOBALS['tables']['articleStorage'];
		foreach($params as $k=>$v){if(!isset($_valid[$k])){unset($params[$k]);}}
		include_once('inc.strings.php');

		$article = array();
		if(isset($params['_id_'])){do{
			$params['_id_'] = preg_replace('/[^0-9]*/','',$params['_id_']);
			if(empty($params['_id_'])){unset($params['_id_']);break;}
			$article = articles_getSingle('(id = '.$params['_id_'].')');
			if(!$article){unset($params['_id_']);break;}
			$article['_id_'] = $article['id'];unset($article['id']);
		}while(false);}

		$article = array_merge($article,$params);
		$article['articleTitle'] = strings_UTF8Encode($article['articleTitle']);
		$article['articleName'] = strings_stringToURL($article['articleTitle']);
		$article['articleTags'] = strings_stringToURL(str_replace(',',' ',$article['articleTags']));$article['articleTags'] = ','.implode(',',array_diff(explode('-',$article['articleTags']),array(''))).',';
		$article['articleText'] = preg_replace('/^[\xEF\xBB\xBF|\x1A]/','',$article['articleText']);
		$article['articleText'] = preg_replace('/[\r\n?]/',PHP_EOL,$article['articleText']);
		/* Necesitamos usar rawurldecode, de otra manera no podemos pasar el símbolo '+' que será convertido en espacios */
		$article['articleText'] = strings_UTF8Encode(rawurldecode($article['articleText']));
		$article['articleText'] = str_replace(array('<br>'),array(''),$article['articleText']);
		$article['articleText'] = preg_replace(array('/<p[^>]*>[ \n\t]*<\/p>/sm'),array(''),$article['articleText']);
		//FIXME: validaciones
		//FIXME: usuario

		$shouldClose = false;if($db == false){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable($GLOBALS['api']['articles']['table.articles'],$article,$db);
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		$article = articles_getSingle('(id = '.$article['_id_'].')',array('db'=>$db));
		$article['user'] = article_author_getByAuthorAlias($article['articleAuthor'],array('db'=>$db));
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}$r = sqlite3_cache_destroy($db,$GLOBALS['api']['articles']['table.articles']);sqlite3_close($db);}

		return $article;
	}

	function article_image_getSingle($whereClause,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'imageHash';}
		$r = sqlite3_getSingle($GLOBALS['api']['articles']['table.images'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function article_image_getWhere($whereClause,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'imageHash';}
		$r = sqlite3_getWhere($GLOBALS['api']['articles']['table.images'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function article_image_save($articleID,$filePath,$db = false,$props = array()){
		if(!file_exists($filePath)){return array('errorDescription'=>'FILE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);}
		$image = array();
		$imgProp = @getimagesize($filePath);
//FIXME: imageName debería ser parseada
		$image = array('articleID'=>$articleID,
		'imageHash'=>md5_file($filePath),'imageSize'=>filesize($filePath),'imageWidth'=>$imgProp[0],
		'imageHeight'=>$imgProp[1],'imageMime'=>$imgProp['mime'],
		'imageName'=>(isset($props['imageName']) ? $props['imageName'] : ''),
		'imageTitle'=>(isset($props['imageTitle']) ? $props['imageTitle'] : ''),
		'imageDescription'=>(isset($props['imageDescription']) ? $props['imageDescription'] : '')
		);

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable($GLOBALS['api']['articles']['table.images'],$image,$db);
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}$r = sqlite3_cache_destroy($db,$GLOBALS['api']['articles']['table.images']);sqlite3_close($db);}
		return $image;
	}
	function article_transfer_fragment($articleID,$fileName,$base64string_sum,$base64string_len,$fragment_string,$fragmentNum,$fragment_sum){
		$tmpPath = '../tmp/';$tmpPath = $tmpPath.$base64string_sum.'/';if(!file_exists($tmpPath)){$oldmask = umask(0);@mkdir($tmpPath,0777,1);umask($oldmask);}
		if(!file_exists($tmpPath)){return array('errorDescription'=>'NO_TMP_FOLDER','file'=>__FILE__,'line'=>__LINE__);}
		$sourceFile = $tmpPath.'source';if(!file_exists($sourceFile)){$articleID = preg_replace('/[^0-9]*/','',$articleID);
			$r = @file_put_contents($sourceFile,json_encode(array('articleID'=>$articleID,'fileName'=>$fileName)),LOCK_EX);if(!$r){return array('errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}}
		$base64string_sum = preg_replace('/[^a-zA-Z0-9]*/','',$base64string_sum);if(strlen($base64string_sum) != 32){return array('errorDescription'=>'MD5_SUM_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		/* Es posible que la llamada sea solo de cortesía, es decir, que la imagen ya esté subida pero se haya 
		 * cortado el procesamiento de la imagen */
		$totalSize = 0;$files = array();if($handle = opendir($tmpPath)){while(false !== ($file = readdir($handle))){if($file[0]=='0'){$files[] = $file;$totalSize += filesize($tmpPath.$file);}}closedir($handle);}
		if($totalSize == $base64string_len){return article_transfer_unify($base64string_sum);}
		$fragmentNum = preg_replace('/[^0-9]*/','',$fragmentNum);
		$fragmentName = str_pad($fragmentNum,10,'0',STR_PAD_LEFT);
		if(file_exists($tmpPath.$fragmentName)){
//FIXME: imaginemos que solo falta unificar
			//FIXME: devolver los fragmentos que ya existen
			return array('errorDescription'=>'FRAGMENT_ALREADY_EXISTS','file'=>__FILE__,'line'=>__LINE__);
		}

		/* Comprobaciones de la string que debemos almacenar */
		$fragment_string = str_replace(' ','+',$fragment_string);if(md5($fragment_string) != $fragment_sum){return array('errorDescription'=>'FRAGMENT_CORRUPT','file'=>__FILE__,'line'=>__LINE__);}
		/* Si lleva una cabecera de imágen debemos eliminarla */
		if(substr($fragment_string,0,11) == 'data:image/'){$comma = strpos($fragment_string,',');$imgType = substr($fragment_string,11,$comma-7-11);$fragment_string = substr($fragment_string,$comma+1);}
		$fp = fopen($tmpPath.$fragmentName,'w');fwrite($fp,$fragment_string);fclose($fp);

		/* Comprobamos si debemos unificar, tenemos en totalsize el valor total de los ficharos antes
		 * de salvar este último fragmento, solo necesitamos sumarle el tamaño */
		$totalSize += filesize($tmpPath.$fragmentName);
		if($totalSize == $base64string_len){return article_transfer_unify($base64string_sum);}

		return array('totalSize'=>$totalSize);
	}
	function article_transfer_unify($base64string_sum){
		$tmpPath = '../tmp/'.$base64string_sum.'/';if(!file_exists($tmpPath)){return array('errorDescription'=>'NO_TMP_FOLDER','file'=>__FILE__,'line'=>__LINE__);}

		$files = array();if($handle = opendir($tmpPath)){while(false !== ($file = readdir($handle))){if($file[0]=='0'){$files[] = $file;}}closedir($handle);}
		sort($files);$fp = fopen($tmpPath.'IMAGE_base64','w');foreach($files as $file){fwrite($fp,file_get_contents($tmpPath.$file));unlink($tmpPath.$file);}fclose($fp);
		if(md5_file($tmpPath.'IMAGE_base64') != $base64string_sum){return array('errorDescription'=>'IMAGE_CORRUPT'.md5_file($tmpPath.'IMAGE_base64'),'file'=>__FILE__,'line'=>__LINE__);}
		$totalSize = filesize($tmpPath.'IMAGE_base64');

		$chunkSize = 1024;
		$src = fopen($tmpPath.'IMAGE_base64','rb');$dst = fopen($tmpPath.'IMAGE_binary','wb');
		while(!feof($src)){fwrite($dst,base64_decode(fread($src,$chunkSize)));}
		fclose($dst);fclose($src);
		unlink($tmpPath.'IMAGE_base64');

		$imgProp = getimagesize($tmpPath.'IMAGE_binary');
		if($imgProp === false){return array('errorDescription'=>'IMAGE_CORRUPT','file'=>__FILE__,'line'=>__LINE__);}
		$ext = substr($imgProp['mime'],6);
		$tmpName = $tmpPath.'IMAGE_binary.'.$ext;
		rename($tmpPath.'IMAGE_binary',$tmpName);

		/* INI-Movemos la imagen */
		$sourceFile = $tmpPath.'source';
		$fields = json_decode(file_get_contents($sourceFile),1);if(empty($fields)){return array('errorDescription'=>'NO_DEST','file'=>__FILE__,'line'=>__LINE__);}
		$articleID = $fields['articleID'];
		$galPath = $GLOBALS['api']['articles']['dirDB'].'/'.$articleID.'/images/orig/';
		if(!file_exists($galPath)){$oldmask = umask(0);$r = mkdir($galPath,0777,1);umask($oldmask);if(!$r){return array('errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}}
		$imageMD5 = md5_file($tmpName);
		$destPath = $galPath.$imageMD5;$r = rename($tmpName,$destPath);
		/* ahora debemos eliminar la ruta temporal $tmpPath */
		$r = article_helper_removeDir($tmpPath,true);
		$r = article_image_thumbs($destPath);
		$r = article_image_save($articleID,$destPath,false,array('imageName'=>$fields['fileName']));
		/* END-Movemos la imagen */

		return array('errorCode'=>'0','data'=>array('totalSize'=>$totalSize,'image_sum'=>$imageMD5));
	}
	function article_helper_removeDir($path,$avoidCheck=false){
		if(!$avoidCheck){$path = preg_replace('/\/$/','/',$path);if(!file_exists($path) || !is_dir($path)){return;}}
		if($handle = opendir($path)){while(false !== ($file = readdir($handle))){
			if(in_array($file,array('.','..'))){continue;}
			if(is_dir($path.$file)){article_helper_removeDir($path.$file.'/',true);continue;}
			unlink($path.$file);
		}closedir($handle);}
		rmdir($path);
		return true;
	}
	function article_image_thumbs($fileNameStamp,$overWrite = false){
		$imgProp = @getimagesize($fileNameStamp);if($imgProp === false){return array('errorDescription'=>'NOT_A_VALID_IMAGE','file'=>__FILE__,'line'=>__LINE__);}

		$pool = $fileNameStamp;
		$fileName = basename($pool);
		$pool = dirname($pool);
		$a = basename($pool);if($a == 'orig'){$pool = dirname($pool);}
		$pool .= '/'.md5_file($fileNameStamp).'/';if(!file_exists($pool)){$oldmask = umask(0);$r = mkdir($pool,0777,1);umask($oldmask);if(!$r){return array('errorCode'=>2,'errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}}

		$fPath = $pool.'orig';copy($fileNameStamp,$fPath);
		include_once('inc.images.php');
		$r = image_convert($fPath,'jpeg');

		include_once('inc.images.php');
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






	function article_author_getSingle($whereClause,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'userAlias';}
		$r = sqlite3_getSingle($GLOBALS['api']['articles']['table.publishers'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function article_author_getWhere($whereClause,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'userAlias';}
		$r = sqlite3_getWhere($GLOBALS['api']['articles']['table.publishers'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function article_author_getByUserAlias($userAlias,$params = array()){return article_author_getSingle('(userAlias = \''.$userAlias.'\')',$params);}
	function article_author_getByAuthorAlias($authorAlias,$params = array()){return article_author_getSingle('(authorAlias = \''.$authorAlias.'\')',$params);}

	function article_author_setAuthorAlias($userAlias,$authorAlias,$db = false,$noencode = true){
	return;
		if($GLOBALS['userSecurity']['errorCode'] !== 0){$a = array('errorCode'=>99,'errorDescription'=>'NOT_LOGGED_IN','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		if(!file_exists($GLOBALS['FEATHER_CONFIG'])){$a = array('errorCode'=>1,'errorDescription'=>'NO_CONFIG_FILE','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		include_once($GLOBALS['FEATHER_CONFIG']);
		if(!in_array($GLOBALS['userArray']['userAlias'],$GLOBALS['FEATHER_publishers'])){$a = array('errorCode'=>98,'errorDescription'=>'NOT_A_PUBLISHER','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		$userIsAdmin = (isset($GLOBALS['FEATHER_admins']) && in_array($GLOBALS['userArray']['userAlias'],$GLOBALS['FEATHER_admins']));
		$shouldClose = false;if(!$db){$db = new SQLite3($GLOBALS['DB_ARTICLEMANAGER']);$shouldClose = true;}

		$authorAlias = preg_replace('/[^0-9a-zA-Z_]*/','',$authorAlias);
		$exists = articleManager_author_getByAuthorAlias($authorAlias,$db,true);
		if($exists !== false){if($shouldClose){$db->close();}$a = array('errorCode'=>1,'errorDescription'=>'AUTHORALIAS_ALREADY_TAKEN','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		$row = array('userAlias'=>$userAlias,'authorAlias'=>$authorAlias,'articlesCount'=>0,'commentsCount'=>0);
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('publishers',$row,$db);
		if(!$r['OK']){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		if($shouldClose){$db->close();}

		//FIXME: deprecated, pero hasta que no se actualice toda la arquitectura nada
		$userPath = '../../users/'.$userAlias.'/';
		$ar = fopen($userPath.'db/API_articleManager.php','w');fwrite($ar,"<?php\n\$authorAlias = '".$authorAlias."'; ?>");fclose($ar);

		return $noencode ? $row : json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}
?>
