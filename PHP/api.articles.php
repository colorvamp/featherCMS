<?php
	$GLOBALS['tables']['articleStorage'] = array('_id_'=>'INTEGER AUTOINCREMENT','articleName'=>'TEXT NOT NULL','articleTitle'=>'TEXT NOT NULL','articleAuthor'=>'TEXT NOT NULL','articleUserAlias'=>'TEXT','articleModes'=>'TEXT','articleDate'=>'TEXT NOT NULL','articleTime'=>'TEXT NOT NULL','articleTags'=>'TEXT','articleSnippet'=>'TEXT',
		'articleText'=>'TEXT','articleSnippetImage'=>'TEXT DEFAULT ""','articleModificationAuthor'=>'TEXT',
		'articleModificationUserAlias'=>'TEXT','articleModificationDate'=>'TEXT','articleCommentsCount'=>'INTEGER','articleHardLink'=>'TEXT','articleIsDraft'=>'INTEGER');
	$GLOBALS['tables']['images'] = array('_articleID_'=>'INTEGER NOT NULL','_imageHash_'=>'TEXT NOT NULL',
		'imageSize'=>'TEXT NOT NULL','imageWidth'=>'TEXT NOT NULL','imageHeight'=>'TEXT',
		'imageMime'=>'TEXT NOT NULL','imageName'=>'TEXT NOT NULL','imageTitle'=>'TEXT','imageDescription'=>'TEXT');
	$GLOBALS['tables']['articleComments'] = array('_id_'=>'INTEGER AUTOINCREMENT','commentChannel'=>'INTEGER','commentAuthor'=>'TEXT NOT NULL','commentResponseTo'=>'INTEGER DEFAULT 0','commentFollowersCount'=>'INTEGER DEFAULT 0','commentMailing'=>'TEXT','commentTags'=>'TEXT','commentModes'=>'TEXT',
		'commentName'=>'TEXT','commentTitle'=>'TEXT','commentText'=>'TEXT NOX NULL',
		'commentTextClean'=>'TEXT NOX NULL','commentIP'=>'TEXT NOT NULL','commentModificationAuthor'=>'TEXT',
		'commentImages'=>'TEXT','commentRating'=>'TEXT','commentVotesCount'=>'INTEGER',
		'commentTime'=>'INTEGER NOT NULL','commentReview'=>'INTEGER NOT NULL');
	$GLOBALS['tables']['bans'] = array('_id_'=>'INTEGER AUTOINCREMENT','banTarget'=>'TEXT','banType'=>'TEXT','banTime'=>'TEXT','banLength'=>'TEXT');
	$GLOBALS['api']['articles'] = array('db'=>'../db/articles_ES.db','dir.db'=>'../db/articles/',
		'table.articles'=>'articleStorage','table.publishers'=>'publishers','table.images'=>'images','table.comments'=>'articleComments','table.bans'=>'bans');
	if(file_exists('../../db')){$GLOBALS['api']['articles'] = array_merge($GLOBALS['api']['articles'],array('db'=>'../../db/articles_ES.db','dir.db'=>'../../db/articles/'));}

	function articles_helper_getPath($article){
		$d = explode('-',$article['articleDate']);
		$articlePath = $GLOBALS['api']['articles']['dir.db'].$d[0].'.'.$d[1].'/'.$d[2].'.'.$article['articleName'].'/';
		return $articlePath;
	}
	function articles_updateSchema(){
		$origTableName = 'articleStorage';
		$db = sqlite3_open($GLOBALS['api']['articles']['db']);
		$r = sqlite3_exec('BEGIN;',$db);

		$a = sqlite3_query('SELECT * FROM '.$origTableName.';',$db);
		$rows = array();if($r){while($row = $a->fetchArray(SQLITE3_ASSOC)){
			$path = articles_helper_getPath($row);
			if(isset($row['articleID'])){$row['_id_'] = $row['articleID'];unset($row['articleID']);}
			if(isset($row['id'])){$row['_id_'] = $row['id'];unset($row['id']);}
			if(!isset($row['articleText'])){$file = $path.'index.html';if(file_exists($file)){$row['articleText'] = file_get_contents($file);}}
			$r = sqlite3_insertIntoTable($origTableName.'1',$row,$db,$origTableName);
			if(!$r['OK']){sqlite3_close($db);return array('errorCode'=>$r['errno'],'errorDescripcion'=>$r['error'],'query'=>$r['query'],'file'=>__FILE__,'line'=>__LINE__);}
		}}

		$r = sqlite3_exec('COMMIT;',$db);
		$r = sqlite3_exec('BEGIN;',$db);
		$r = sqlite3_exec('DROP TABLE IF EXISTS '.$origTableName.';',$db);
		$r = sqlite3_exec('ALTER TABLE '.$origTableName.'1 RENAME TO '.$origTableName.';',$db);
		$r = sqlite3_exec('COMMIT;',$db);
		sqlite3_close($db);
	}

	function articles_getSingle($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		if(!isset($params['indexBy'])){$params['indexBy'] = 'id';}
		$r = sqlite3_getSingle($GLOBALS['api']['articles']['table.articles'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function articles_getWhere($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
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
		include_once('inc.sqlite3.php');
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
		if(!isset($article['articleTitle'])){$article['articleTitle'] = 'New Article ('.date('Y-m-d H:i:s').')';}
		if(!isset($article['articleTags'])){$article['articleTags'] = ',';}
		if(!isset($article['articleAuthor'])){$article['articleAuthor'] = 'dummy';}
		$article['articleTitle'] = strings_UTF8Encode($article['articleTitle']);
		$article['articleName'] = strings_stringToURL($article['articleTitle']);
		$article['articleTags'] = strings_stringToURL(str_replace(',',' ',$article['articleTags']));$article['articleTags'] = ','.implode(',',array_diff(explode('-',$article['articleTags']),array(''))).',';
		$article['articleText'] = preg_replace('/^[\xEF\xBB\xBF|\x1A]/','',$article['articleText']);
		$article['articleText'] = preg_replace('/[\r\n?]/',PHP_EOL,$article['articleText']);
		/* Necesitamos usar rawurldecode, de otra manera no podemos pasar el símbolo '+' que será convertido en espacios */
		$article['articleText'] = strings_UTF8Encode(rawurldecode($article['articleText']));
		$article['articleText'] = str_replace(array('<br>'),array(''),$article['articleText']);
		$article['articleText'] = preg_replace('/<(\/?)(div)([^>]*)>/','<$1p$3>',$article['articleText']);
		$article['articleText'] = preg_replace('/<(\/?)(span|font)([^>]*)>/','',$article['articleText']);
		$article['articleText'] = preg_replace('/<p[^>]*>[ \n\t]*<\/p>/sm','',$article['articleText']);
		$article['articleText'] = preg_replace('/style=.[^\'\"]+./','',$article['articleText']);
		/* Orphan text nodes */
		$article['articleText'] = preg_replace('/(^|<\/(p|h4)>)([^<]+)(<(p|h4)[^>]*>|$)/','$1<p>$3</p>$4',$article['articleText']);
		$article['articleSnippet'] = article_helper_cleanText($article['articleText']);
		$article['articleSnippet'] = preg_replace('/[\n\r\t]*/','',$article['articleSnippet']);
		$article['articleSnippet'] = strings_createSnippetWithTags($article['articleSnippet'],500);

		//FIXME: validaciones
		//FIXME: usuario

		if(!isset($params['_id_'])){
			$article = array_merge($article,array('articleDate'=>date('Y-m-d'),'articleTime'=>date('H:i:s'),'articleCommentsCount'=>0,'articleIsDraft'=>1));
		}

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable($GLOBALS['api']['articles']['table.articles'],$article,$db);
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		$article = articles_getSingle('(id = '.$r['id'].')',array('db'=>$db));
		if(!$article){return array('errorDescription'=>'UNKNOWN_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		$article['user'] = article_author_getByAuthorAlias($article['articleAuthor'],array('db'=>$db));
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}$r = sqlite3_cache_destroy($db,$GLOBALS['api']['articles']['table.articles']);sqlite3_close($db);}

		return $article;
	}
	function articles_remove($articleID,$db = false){
		$articleID = preg_replace('/[^0-9]*/','',$articleID);

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$article = articles_getSingle('(id = '.$articleID.')',array('db'=>$db));
		if(!$article){if($shouldClose){sqlite3_close($db);}return array('errorDescription'=>'ARTICLE_NOT_FOUND','file'=>__FILE__,'line'=>__LINE__);}
		$GLOBALS['DB_LAST_QUERY'] = 'DELETE FROM '.$GLOBALS['api']['articles']['table.articles'].' WHERE id = '.$articleID.';';
		$r = sqlite3_exec($GLOBALS['DB_LAST_QUERY'],$db);
		if(!$r){$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}$r = sqlite3_cache_destroy($db,$GLOBALS['api']['articles']['table.articles']);sqlite3_close($db);}

		$articlePath = $GLOBALS['api']['articles']['dir.db'].$articleID.'/';
		if(file_exists($articlePath)){$r = article_helper_removeDir($articlePath);}

		return true;
	}
	function articles_publish($articleID,$db = false){
		$articleID = preg_replace('/[^0-9]*/','',$articleID);
		return articles_save(array('_id_'=>$articleID,'articleDate'=>date('Y-m-d'),'articleTime'=>date('H:i:s'),'articleIsDraft'=>0),$db);
	}
	function articles_unpublish($articleID,$db = false){
		$articleID = preg_replace('/[^0-9]*/','',$articleID);
		return articles_save(array('_id_'=>$articleID,'articleIsDraft'=>1),$db);
	}
	function articles_publishScheduled($articleID,$dateString = false){
		if(!file_exists('inc.requests.php')){return array('errorDescription'=>'REQUESTS_LIB_NOT_FOUND','file'=>__FILE__,'line'=>__LINE__);}
		$articleID = preg_replace('/[^0-9]*/','',$articleID);
		$params = array($articleID);
		include_once('inc.requests.php');
		$r = requests_deleteWhere('(requestLock = \'publishScheduled\' AND requestParams = \''.json_encode($params).'\')');
		$r = requests_create(array('requestLock'=>'publishScheduled','requestModule'=>'api.articles.php','requestCall'=>'articles_publish','requestParams'=>$params,'requestStatus'=>'awaiting','requestDate'=>$dateString,'requestTime'=>'00:01'));
		return $r;
	}
	function articles_archive($articleID,$db = false){
		
	}
	function articles_search($searchString = '',$db = false){
		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$article = articles_getSingle('(articleTitle = \''.$db->escapeString($searchString).'\')',array('db'=>$db));
		if($article){if($shouldClose){sqlite3_close($db);}return array($article['id']=>$article);}

		$searchString = preg_replace('/[^0-9a-zA-ZáéíóúÁÉÍÓÚ ]*/','',$searchString);
		if(!strpos($searchString,' ')){
			$searchStringEscaped = $db->escapeString($searchString);
			$articles = articles_getWhere('(articleTitle LIKE \'%'.$searchStringEscaped.'%\')',array('db'=>$db));
			if($shouldClose){sqlite3_close($db);}
			return $articles;
		}

		/* Comparing anonymus function */
		$o = function($a,$b){if ($a['searchRate'] == $b['searchRate']){return 0;}return ($a['searchRate'] > $b['searchRate']) ? -1 : 1;};

		$letterLimit = 3;
		$searchArray = array_unique(explode(' ',$searchString));
		$searchArrayCount = count($searchArray);
		$searchQueryOR = $searchQueryAND = '(';foreach($searchArray as $element){
			/* Si solo hay una palabra debemos buscar por ella aunque solo tenga 3 letras */
			if(strlen($element) <= $letterLimit && $searchArrayCount > 1){continue;}
			$escapedElement = $db->escapeString($element);
			$searchQueryOR .= '(articleTitle LIKE \'%'.$escapedElement.'%\') OR ';
			$searchQueryAND .= '(articleTitle LIKE \'%'.$escapedElement.'%\') AND ';
		}
		$totalStars = count($searchArrayCount);
		$searchQueryOR = substr($searchQueryOR,0,-4).')';
		$searchQueryAND = substr($searchQueryAND,0,-4).')';

		$articlesA = articles_getWhere($searchQueryAND,array('db'=>$db,'limit'=>200));
		$articlesO = articles_getWhere($searchQueryOR,array('db'=>$db,'limit'=>200));

		/* El valor de $i es decremental porque se estima que las palabras que aparezcan antes en el
		 * criterio de búsqueda tienen más peso */
		if($usersO){
			foreach($usersO as $k=>$user){$i = $totalStars;$usersO[$k]['searchRate'] = 0;foreach($searchArray as $searchItem){$ret = strpos(strtolower($user['articleTitle']),strtolower($searchItem));if($ret !== false){$usersO[$k]['searchRate'] += $i;}$i--;}}
			uasort($usersO,$o);
		}
		if($shouldClose){sqlite3_close($db);}
		return array_merge($articlesA,$articlesO);
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
			return array('errorDescription'=>'FRAGMENT_ALREADY_EXISTS','totalSize'=>$totalSize,'file'=>__FILE__,'line'=>__LINE__);
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
		$galPath = $GLOBALS['api']['articles']['dir.db'].$articleID.'/images/orig/';
		if(!file_exists($galPath)){$oldmask = umask(0);$r = @mkdir($galPath,0777,1);umask($oldmask);if(!$r){return array('errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}}
		$imageMD5 = md5_file($tmpName);
		$destPath = $galPath.$imageMD5;$r = rename($tmpName,$destPath);
		/* ahora debemos eliminar la ruta temporal $tmpPath */
		$r = article_helper_removeDir($tmpPath,true);
		$r = article_image_thumbs($destPath);
		$r = article_image_save($articleID,$destPath,false,array('imageName'=>$fields['fileName']));
		/* END-Movemos la imagen */

		/* Recopilamos las propiedades de la imagen */
		$imageProps = article_image_getSingle('(articleID = '.$articleID.' AND imageHash = \''.$imageMD5.'\')');
		return array('errorCode'=>'0','data'=>$imageProps);
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
	function article_thumb_set($articleID = false,$imageHash = array(),$db = false){
		$_valid = array('articleImageSmall'=>0,'articleImageMedium'=>0,'articleImageLarge'=>0);
		foreach($imageHash as $k=>$v){if(!isset($_valid[$k])){unset($imageHash[$k]);}}
		if(!$imageHash){return array('errorDescription'=>'IMAGE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);}

		$articleID = preg_replace('/[^0-9]*/','',$articleID);
		$articleOB = articles_getSingle('(id = '.$articleID.')');
		if(!$articleOB){return array('errorDescription'=>'ARTICLE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);}
		$images = article_image_getWhere('(articleID = '.$articleID.')');

		foreach($imageHash as $k=>$v){if(!isset($images[$v])){unset($imageHash[$k]);}}
		if(!$imageHash){return array('errorDescription'=>'IMAGE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);}
		$imageHash = json_encode($imageHash);

		$r = articles_save(array('id'=>$articleID,'articleSnippetImage'=>$imageHash),$db);
		return $r;
	}



	function article_helper_cleanText($text){
		$clean = str_replace(array("\n",'</p><p>'),array(' ',' '),$text);
		$clean = preg_replace('/<\/?[^>]+>/','',$clean);
		return $clean;
	}

	function article_author_getSingle($whereClause,$params = array()){
		//TODO
	}
	function article_author_getWhere($whereClause,$params = array()){
		//TODO
	}
	function article_author_getByAuthorAlias($authorAlias,$params = array()){include_once('api.users.php');return users_getSingle('(userNick = \''.$authorAlias.'\')',$params);}

	function article_comment_getSingle($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = sqlite3_getSingle($GLOBALS['api']['articles']['table.comments'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function article_comment_getWhere($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = sqlite3_getWhere($GLOBALS['api']['articles']['table.comments'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function article_comment_deleteWhere($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db']);$shouldClose = true;}
		$r = sqlite3_deleteWhere($GLOBALS['api']['articles']['table.comments'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function article_comment_save($params,$db = false){
		if(isset($params['id'])){$params['_id_'] = $params['id'];unset($params['id']);}
		$_valid = $GLOBALS['tables']['articleComments'];
		foreach($params as $k=>$v){if(!isset($_valid[$k])){unset($params[$k]);}}
		include_once('inc.sqlite3.php');
		include_once('inc.strings.php');

		$comment = array();
		if(isset($params['_id_'])){do{
			$params['_id_'] = preg_replace('/[^0-9]*/','',$params['_id_']);
			if(empty($params['_id_'])){unset($params['_id_']);break;}
			$comment = article_comment_getSingle('(id = '.$params['_id_'].')');
			if(!$comment){unset($params['_id_']);break;}
			$comment['_id_'] = $comment['id'];unset($comment['id']);
		}while(false);}

		$comment = array_merge($comment,$params);
		if(!isset($comment['commentTitle'])){$comment['commentTitle'] = '';}
		if(!isset($comment['commentTags'])){$comment['commentTags'] = ',';}
		if(!isset($comment['commentAuthor'])){$comment['commentAuthor'] = 'dummy';}
		if(!isset($comment['commentChannel'])){$comment['commentChannel'] = '0';}
		if(!isset($comment['commentReview'])){$comment['commentReview'] = '0';}
		if(!isset($comment['commentIP']) && isset($_SERVER['SERVER_ADDR'])){$comment['commentIP'] = $_SERVER['SERVER_ADDR'];}
		if(!isset($comment['_id_'])){$comment = array_merge($comment,array('commentRating'=>0,'commentVotesCount'=>0,'commentTime'=>time()));}

		/* INI-Comprobamos los bans */
		$bans = article_ban_getWhere('(banTarget = \'ip:'.$comment['commentIP'].'\')');
		if($bans){foreach($bans as $ban){
			if($ban['banType'] == 'comments-disabled'){return array('errorDescription'=>'BANNED','file'=>__FILE__,'line'=>__LINE__);}
		}}
		/* END-Comprobamos los bans */


		$comment['commentTitle'] = strings_UTF8Encode($comment['commentTitle']);
		$comment['commentName'] = strings_stringToURL($comment['commentTitle']);
		$comment['commentChannel'] = preg_replace('/[^0-9]*/','',$comment['commentChannel']);
		$comment['commentTags'] = strings_stringToURL(str_replace(',',' ',$comment['commentTags']));$comment['commentTags'] = ','.implode(',',array_diff(explode('-',$comment['commentTags']),array(''))).',';
		if(isset($comment['commentIP'])){$comment['commentIP'] = preg_replace('/[^0-9\.\:]*/','',$comment['commentIP']);}
		$comment['commentText'] = preg_replace('/^[\xEF\xBB\xBF|\x1A]/','',$comment['commentText']);
		$comment['commentText'] = preg_replace('/[\r\n?]/',PHP_EOL,$comment['commentText']);

		if(!strpos($params['commentText'],'<')){
			$comment['commentText'] = comment_markdown2html($comment['commentText']);
			/* Enlaces de yiutub y demás */
			$reps = array();
			$reps['youtu.be'] = array('regex'=>'/<p>http:\/\/youtu.be\/([^&<]+)<\/p>/','replacement'=>'<p class="youtube"><object><param name="movie" value="http://www.youtube.com/v/$1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/$1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true"></embed></object></p>');
			foreach($reps as $k=>$r){$comment['commentText'] = preg_replace($r['regex'],$r['replacement'],$comment['commentText']);}
		}else{
			$reps = array();
			$reps['youtube.com']      = array('regex'=>'/&lt;a href=(&#039;|\'|\")(http:\/\/www.youtube.com[^&]+)(&#039;|\'|\")&gt;([^&]+)&lt;\/a&gt;/','href'=>2,'text'=>4);
			$reps['youtu.be']         = array('regex'=>'/&lt;a href=(&#039;|\'|\")(http:\/\/youtu.be[^&]+)(&#039;|\'|\")&gt;([^&]+)&lt;\/a&gt;/','href'=>2,'text'=>4);
			$reps['es.wikipedia.org'] = array('regex'=>'/&lt;a href=(&#039;|\'|\")(http:\/\/es.wikipedia.org[^&]+)(&#039;|\'|\")&gt;([^&]+)&lt;\/a&gt;/','href'=>2,'text'=>4);

			$comment['commentText'] = trim(stripslashes(strings_toUTF8($comment['commentText'])));
			$comment['commentText'] = str_replace(array('&','<','>'),array('&amp;','&lt;','&gt;'),$comment['commentText']);
			$comment['commentText'] = preg_replace('/&lt;(\/?)([bisp]{1})&gt;/','<$1$2>',$comment['commentText']);
			//FIXME: quitar del final tb
			$comment['commentText'] = preg_replace('/^[ \n\t\r]*/','',$comment['commentText']);
			//FIXME: pasar a la nueva forma
			foreach($reps as $k=>$r){$comment['commentText'] = preg_replace($r['regex'],'<a class=\'link_'.$k.'\' href=\'$'.$r['href'].'\'>$'.$r['text'].'</a>',$comment['commentText']);}
		}

		/* INI-limpieza de texto */
		$comment['commentText'] = preg_replace('/<p[^>]*><\/p[^>]*>/','',$comment['commentText']);
		/* END-limpieza de texto */

		$comment['commentTextClean'] = article_helper_cleanText($comment['commentText']);




//$GLOBALS['tables']['articleComments'] = array('_id_'=>'INTEGER AUTOINCREMENT','commentResponseTo'=>'INTEGER DEFAULT 0',
//'commentFollowersCount'=>'INTEGER DEFAULT 0','commentMailing'=>'TEXT','commentTags'=>'TEXT','commentModes'=>'TEXT',
//		'commentModificationAuthor'=>'TEXT');

if(0){
		/* Si ha entrado shoutResponseTo debemos validarlo */
		if(isset($params['shoutResponseTo'])){do{
			$params['shoutResponseTo'] = preg_replace('/[^0-9]*/','',$params['shoutResponseTo']);
			if(empty($params['shoutResponseTo'])){unset($params['shoutResponseTo']);break;}
			$parentShout = shoutBox_getByID($params['shoutResponseTo'],$db);
			if(!$parentShout){unset($params['shoutResponseTo']);break;}
			if(!empty($parentShout['shoutChannel'])){$params['shoutChannel'] = $parentShout['shoutChannel'];}
			/* Si el padre es un maestro no necesitamos contrastar nada más */
			if(empty($parentShout['shoutResponseTo'])){break;}
			/* Obtenemos el hilo para sacar el padre absoluto, necesitamos hacer algunas comprobaciones, 
			 * * si el primer padre tiene channelView entonces "shoutResponseTo" solo puede ser uno de los padres de canal */
			$thread = shoutBox_getThread($parentShout['shoutResponseTo']);
			if(!$thread){unset($params['shoutResponseTo']);break;}
			$firstParent = current($thread);
			$modes_isChannelView = (strpos($firstParent['shoutModes'],',channelView,') !== false);
			//FIXME: si el padre tiene channelView deberíamos ponerselo tb al hijo
			//FIXME: controlar el mode de exclusividad
			if($modes_isChannelView && !isset($thread[$params['shoutResponseTo']])){
				/* Un canal puede tener unicamente varios "padres" y respuestas a dichos padres, en caso de que
				 * sea una respuesta y no esté dirigida a uno de esos padres no podemos insertar, excepto si
				 * solo hubira un padre */
				if(count($thread) == 1){$params['shoutResponseTo'] = $firstParent['id'];break;}
				return array('errorCode'=>4,'errorDescription'=>'INVALID_PARENT','file'=>__FILE__,'line'=>__LINE__);
			}
		}while(false);}
		if(!isset($params['shoutResponseTo'])){$params['shoutResponseTo'] = 0;}
		if(!isset($params['shoutChannel'])){$params['shoutChannel'] = '';}
		if(!$thread && !empty($params['shoutChannel'])){do{
			$thread = shoutBox_getChannel($params['shoutChannel']);
			if(!$thread){/* Tan solo salimos, posiblemente esté creando un nuevo canal */break;}
			$firstParent = current($thread);
			$modes_isChannelView = (strpos($firstParent['shoutModes'],',channelView,') !== false);
			/* Si no hay modo de canal entonces se trata del modo normal y necesitamos establecer
			 * shoutResponseTo como el primer nodo del canal */
			if(!$modes_isChannelView){$params['shoutResponseTo'] = $firstParent['id'];break;}
			//FIXME: controlar el mode de exclusividad
		}while(false);}

		/* Si no es una respuesta a ningún otro shout necesita título */
		if(!$params['shoutResponseTo'] && empty($params['shoutChannel']) && (strlen($params['shoutTitle']) < 1 || strlen($params['shoutTitle']) < 1) ){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>3,'errorDescription'=>'INVALID_SHOUTTITLE','file'=>__FILE__,'line'=>__LINE__);}
		if($params['shoutResponseTo']){unset($params['shoutTitle'],$params['shoutTitle']);}
		/* END-validaciones */
}

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable($GLOBALS['api']['articles']['table.comments'],$comment,$db,'articleComments');
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		$comment = article_comment_getSingle('(id = '.$r['id'].')',array('db'=>$db));
		if(!$comment){return array('errorDescription'=>'UNKNOWN_ERROR','file'=>__FILE__,'line'=>__LINE__);}
//$article['user'] = article_author_getByAuthorAlias($article['articleAuthor'],array('db'=>$db));
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}sqlite3_close($db);}

		return $comment;
	}

	function comment_markdown2html($text){
		$text = preg_replace('/^[\xEF\xBB\xBF|\x1A]/','',$text);
		$text = preg_replace('/[\r\n]/',"\n",$text);
		$text = preg_replace('/\n{3,}/',"\n\n\n",$text);
//FIXME: usar strpos para mejorar la eficiencia
//echo $text;exit;
		/* Extraemos los enlaces si hubiera alguno */
		$a = preg_match_all('/\[([^\]]+)\]\[([0-9]+)\]/',$text,$linksOBs);
		$b = preg_match_all('/\!\[([^\]]+)\]\[([0-9]+)\]/',$text,$imagesOBs);
		$c = preg_match_all('/  \[(?<linkNum>[0-9]+)\]: (?<linkSrc>[^ \n]+)( .(?<linkTitle>[^\'\"]+).|)/',$text,$linksSrcs);
		$tmp = array();foreach($linksSrcs[0] as $k=>$v){$tmp[$linksSrcs['linkNum'][$k]] = array('linkSrc'=>$linksSrcs['linkSrc'][$k],'linkTitle'=>(isset($linksSrcs['linkTitle'][$k]) ? $linksSrcs['linkTitle'][$k] : ''));$text = str_replace($linksSrcs[0][$k],'',$text);}$linksSrcs = $tmp;
		foreach($imagesOBs[0] as $k=>$v){$imageRef = $imagesOBs[2][$k];$linkSrc = $linksSrcs[$imageRef]['linkSrc'];$linkTitle = $linksSrcs[$imageRef]['linkTitle'];if(empty($linkTitle)){$linkTitle = $imagesOBs[1][$k];}$text = str_replace($imagesOBs[0][$k],'<img src="'.$linkSrc.'" title="'.$linkTitle.'" alt="'.$imagesOBs[1][$k].'"/>',$text);}
		foreach($linksOBs[0] as $k=>$v){$linkRef = $linksOBs[2][$k];$linkSrc = $linksSrcs[$linkRef]['linkSrc'];$linkTitle = $linksSrcs[$imageRef]['linkTitle'];$text = str_replace($linksOBs[0][$k],'<a href="'.$linkSrc.'" title="'.$linkTitle.'">'.$linksOBs[1][$k].'</a>',$text);}
		/* Párrafos */
		$text = explode("\n\n\n",$text);
		$text = '<p>'.implode('</p><p>',$text).'</p>';
		/* Bold */$text = preg_replace('/\*\*([^\*]+)\*\*/','<strong>$1</strong>',$text);
		/* Italic */$text = preg_replace('/\*([^\*]+)\*/','<em>$1</em>',$text);
		/* INI-Blockquote */
		$text = preg_replace('/<p>> ([^<]+)<\/p>/','<blockquote><p>$1</p></blockquote>',$text);
		$a = preg_match_all('/\<blockquote><p>([^<]+)<\/p><\/blockquote>/',$text,$bquotes);
		foreach($bquotes[0] as $k=>$v){$t = $bquotes[1][$k];$t = preg_replace('/\n\n> /',' ',$t);$t = str_replace("\n",' ',$t);$text = str_replace($bquotes[0][$k],'<blockquote><p>'.$t.'</p></blockquote>',$text);}
		/* END-Blockquote */
		/* INI-ul */
		$a = preg_match_all('/\<p> - ([^<]+)<\/p>/',$text,$uls);
		foreach($uls[0] as $k=>$v){$t = $uls[1][$k];$t = explode("\n\n - ",$t);$t = '<ul><li>'.implode('</li><li>',$t).'</li></ul>';$text = str_replace($uls[0][$k],$t,$text);}
		/* END-ul */
		/* INI-ol */
		$a = preg_match_all('/\<p> [0-9]+. ([^<]+)<\/p>/',$text,$ols);
		foreach($ols[0] as $k=>$v){$t = $ols[1][$k];$t = preg_split('/\n\n [0-9]+. /',$t);$t = '<ol><li>'.implode('</li><li>',$t).'</li></ol>';$text = str_replace($ols[0][$k],$t,$text);}
		/* END-ol */
		/* INI-h2 */
		$a = preg_match_all('/\<p>([^\n<]+)\n\n[\-]+<\/p>/',$text,$h2s);
		foreach($h2s[0] as $k=>$v){$t = $h2s[1][$k];$t = '<h2>'.$t.'</h2>';$text = str_replace($h2s[0][$k],$t,$text);}
		/* END-h2 */
		/* INI-hr */
		$a = preg_match_all('/\<p>[\-]+\n\n([^\n<]+)<\/p>/',$text,$hrs);
		foreach($hrs[0] as $k=>$v){$t = $hrs[1][$k];$t = '<hr/><p>'.$t.'</p>';$text = str_replace($hrs[0][$k],$t,$text);}
		/* END-hr */

		return $text;
	}

	function article_ban_getSingle($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = sqlite3_getSingle($GLOBALS['api']['articles']['table.bans'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function article_ban_getWhere($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['articles']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = sqlite3_getWhere($GLOBALS['api']['articles']['table.bans'],$whereClause,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $r;
	}
	function article_ban_save($params,$db = false){
		if(isset($params['id'])){$params['_id_'] = $params['id'];unset($params['id']);}
		$_valid = $GLOBALS['tables']['bans'];
		foreach($params as $k=>$v){if(!isset($_valid[$k])){unset($params[$k]);}}
		if(!$params){return array('errorDescription'=>'INVALID_PARAMS','file'=>__FILE__,'line'=>__LINE__);}

		if(!isset($params['banTime'])){$params['banTime'] = time();}
		if(!isset($params['banLength'])){$params['banLength'] = 'permanent';}

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable($GLOBALS['api']['articles']['table.bans'],$params,$db,'bans');
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		$ban = article_ban_getSingle('(id = '.$r['id'].')',array('db'=>$db));
		if(!$ban){return array('errorDescription'=>'UNKNOWN_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}sqlite3_close($db);}

		return $ban;
	}
?>
