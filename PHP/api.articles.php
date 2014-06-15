<?php
	$GLOBALS['tables']['articleStorage'] = array('_id_'=>'INTEGER AUTOINCREMENT','articleName'=>'TEXT NOT NULL','articleTitle'=>'TEXT NOT NULL','articleAuthor'=>'TEXT NOT NULL','articleUserAlias'=>'TEXT','articleModes'=>'TEXT','articleDate'=>'TEXT NOT NULL','articleTime'=>'TEXT NOT NULL','articleTags'=>'TEXT','articleSnippet'=>'TEXT',
		'articleText'=>'TEXT','articleSnippetImage'=>'TEXT DEFAULT ""','articleModificationAuthor'=>'TEXT',
		'articleModificationUserAlias'=>'TEXT','articleModificationDate'=>'TEXT','articleCommentsCount'=>'INTEGER','articleHardLink'=>'TEXT','articleIsDraft'=>'INTEGER');
	$GLOBALS['tables']['images'] = array('_articleID_'=>'INTEGER NOT NULL','_imageHash_'=>'TEXT NOT NULL',
		'imageSize'=>'TEXT NOT NULL','imageWidth'=>'TEXT NOT NULL','imageHeight'=>'TEXT',
		'imageMime'=>'TEXT NOT NULL','imageName'=>'TEXT NOT NULL','imageTitle'=>'TEXT','imageDescription'=>'TEXT');
	$GLOBALS['tables']['files'] = array('_articleID_'=>'INTEGER NOT NULL','_fileHash_'=>'TEXT NOT NULL',
		'fileSize'=>'TEXT NOT NULL','fileMime'=>'TEXT NOT NULL','fileName'=>'TEXT',
		'fileName'=>'TEXT NOT NULL','fileTitle'=>'TEXT','fileDescription'=>'TEXT');
	$GLOBALS['tables']['articleMailing'] = array('_id_'=>'INTEGER AUTOINCREMENT','articleMailing'=>'TEXT');
	$GLOBALS['tables']['articleComments'] = array('_id_'=>'INTEGER AUTOINCREMENT','commentChannel'=>'INTEGER',
		'commentAuthor'=>'TEXT','commentUserName'=>'TEXT','commentUserMail'=>'TEXT','commentUserURL'=>'TEXT',
		'commentResponseTo'=>'INTEGER DEFAULT 0','commentMailing'=>'TEXT',
		'commentTags'=>'TEXT','commentModes'=>'TEXT',
		'commentName'=>'TEXT','commentTitle'=>'TEXT','commentText'=>'TEXT NOX NULL',
		'commentTextClean'=>'TEXT NOX NULL','commentIP'=>'TEXT NOT NULL','commentModificationAuthor'=>'TEXT',
		'commentImages'=>'TEXT','commentRating'=>'INTEGER DEFAULT 0','commentVotesCount'=>'INTEGER DEFAULT 0',
		'commentStamp'=>'INTEGER NOT NULL','commentDate'=>'INTEGER NOT NULL','commentTime'=>'INTEGER NOT NULL',
		'commentReview'=>'INTEGER DEFAULT 0');
	$GLOBALS['tables']['bans'] = array('_id_'=>'INTEGER AUTOINCREMENT','banTarget'=>'TEXT','banType'=>'TEXT','banTime'=>'TEXT','banLength'=>'TEXT');
	if(!isset($GLOBALS['api']['articles'])){$GLOBALS['api']['articles'] = array();}
	$GLOBALS['api']['articles'] = array_merge($GLOBALS['api']['articles'],array('db'=>'../db/articles_ES.db','dir.db'=>'../db/articles/',
		'table.articles'=>'articleStorage','table.publishers'=>'publishers','table.images'=>'images','table.files'=>'files','table.comments'=>'articleComments','table.bans'=>'bans'
	));
	if(file_exists('../../db')){
		$GLOBALS['api']['articles'] = array_merge($GLOBALS['api']['articles'],array('db'=>'../../db/articles_ES.db','dir.db'=>'../../db/articles/'));
		$GLOBALS['api']['sqlite3']['dir.cache'] = '../../db/cache/sqlite3/';
	}
	include_once('articles/inc.article.files.php');
	include_once('articles/inc.article.images.php');
	include_once('articles/inc.article.comments.php');

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
		if(!isset($article['articleAuthor']) && isset($GLOBALS['user']['userNick'])){$article['articleAuthor'] = $GLOBALS['user']['userNick'];}
		if(!isset($article['articleAuthor'])){$article['articleAuthor'] = 'dummy';}
		if(!isset($article['articleText'])){$article['articleText'] = '';}
		$article['articleTitle'] = strings_UTF8Encode(html_entity_decode($article['articleTitle'],ENT_QUOTES));
		$article['articleName'] = strings_stringToURL($article['articleTitle']);
		$article['articleTags'] = strings_stringToURL(str_replace(',',' ',$article['articleTags']));$article['articleTags'] = ','.implode(',',array_diff(explode('-',$article['articleTags']),array(''))).',';

		if(strpos($article['articleText'],'<') === false){
			if(!function_exists('markdown_toHTML')){include_once('inc.markdown.php');}
			$article['articleText'] = markdown_toHTML($article['articleText']);
			/* Enlaces de yiutub y demás */
			/*$reps = array();
			$reps['youtu.be'] = array('regex'=>'/<p>http:\/\/youtu.be\/([^&<]+)<\/p>/','replacement'=>'<p class="youtube"><object><param name="movie" value="http://www.youtube.com/v/$1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/$1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true"></embed></object></p>');
			foreach($reps as $k=>$r){$comment['commentText'] = preg_replace($r['regex'],$r['replacement'],$comment['commentText']);}*/
		}

		$article['articleText'] = preg_replace('/^[\xEF\xBB\xBF|\x1A]/','',$article['articleText']);
		$article['articleText'] = preg_replace('/[\r\n]/',PHP_EOL,$article['articleText']);
		/* Necesitamos usar rawurldecode, de otra manera no podemos pasar el símbolo '+' que será convertido en espacios */
		$article['articleText'] = strings_UTF8Encode(rawurldecode($article['articleText']));
		$article['articleText'] = str_replace(array('<br>'),array(''),$article['articleText']);
		$article['articleText'] = preg_replace('/<(\/?)(div)([^>]*)>/','<$1p$3>',$article['articleText']);
		$article['articleText'] = preg_replace('/<(\/?)(span|font)([^>]*)>/','',$article['articleText']);
		$article['articleText'] = preg_replace('/style=.[^\'\"]+./','',$article['articleText']);
		/* Orphan text nodes */
		$article['articleText'] = preg_replace('/(^|<\/(p|h4)>)([^<]+)(<(p|h4)[^>]*>|$)/','$1<p>$3</p>$4',$article['articleText']);
		$article['articleText'] = preg_replace('/<p[^>]*>[ \n\t]*<\/p>/sm','',$article['articleText']);
		$article['articleSnippet'] = article_helper_cleanText($article['articleText']);
		$article['articleSnippet'] = preg_replace('/[\n\r\t]*/','',$article['articleSnippet']);
		$article['articleSnippet'] = preg_replace('/{%[^%]*%?}?/','',$article['articleSnippet']);
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
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}$r = sqlite3_cache_destroy($db,$GLOBALS['api']['articles']['table.articles']);sqlite3_close($db);}

		return $article;
	}
	function articles_remove($articleID,$db = false){
		$articleID = preg_replace('/[^0-9]*/','',$articleID);
//FIXME: usar deleteWhere
		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$article = articles_getSingle('(id = '.$articleID.')',array('db'=>$db));
		if(!$article){if($shouldClose){sqlite3_close($db);}return array('errorDescription'=>'ARTICLE_NOT_FOUND','file'=>__FILE__,'line'=>__LINE__);}
		$GLOBALS['DB_LAST_QUERY'] = 'DELETE FROM '.$GLOBALS['api']['articles']['table.articles'].' WHERE id = '.$articleID.';';
		$r = sqlite3_exec($GLOBALS['DB_LAST_QUERY'],$db);
		if(!$r){$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}$r = sqlite3_cache_destroy($db,$GLOBALS['api']['articles']['table.articles']);sqlite3_close($db);}

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
//FIXME: no
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
		$clean = str_replace(array(PHP_EOL,'</p><p>'),array(' ',' '),$text);
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

//$r = articles_comment_updateSchema();
//echo time();
//var_dump($r);exit;
	function articles_comment_updateSchema(){
		$origTableName = $GLOBALS['api']['articles']['table.comments'];
		$db = sqlite3_open($GLOBALS['api']['articles']['db']);

		$a = sqlite3_query('SELECT * FROM '.$origTableName.';',$db);
		$r = sqlite3_exec('BEGIN;',$db);
		$rows = array();if($r){while($row = $a->fetchArray(SQLITE3_ASSOC)){
			unset($row['commentFollowersCount'],$row['commentRating'],$row['commentVotesCount']);
			if(isset($row['commentMailing'])){$row['commentUserMail'] = $row['commentMailing'];unset($row['commentMailing']);}
			if(isset($row['commentName'])){$row['commentUserName'] = $row['commentName'];unset($row['commentName']);}
			$row['commentUserURL'] = $row['commentTitle'];unset($row['commentTitle']);
			if(isset($row['commentUserMail']) && $row['commentUserMail'] == 'reyloren@yahoo.es'){$row['commentAuthor'] = 'reyloren';unset($row['commentMailing'],$row['commentTitle'],$row['commentName'],$row['commentUserName'],$row['commentUserMail'],$row['commentUserURL']);}
			if(isset($row['commentUserMail']) && $row['commentUserMail'] == 'oscarballo@gmail.com'){$row['commentAuthor'] = 'oscarballo';unset($row['commentMailing'],$row['commentTitle'],$row['commentName'],$row['commentUserName'],$row['commentUserMail'],$row['commentUserURL']);}
			if(isset($row['commentUserMail']) && $row['commentUserMail'] == 'sombra2eternity@gmail.com'){$row['commentAuthor'] = 'impact';unset($row['commentMailing'],$row['commentTitle'],$row['commentName'],$row['commentUserName'],$row['commentUserMail'],$row['commentUserURL']);}
			if(strlen($row['commentTime']) == 10){$row['commentStamp'] = $row['commentTime'];}
			$row['commentDate'] = date('Y-m-d',$row['commentStamp']);
			$row['commentTime'] = date('H:m:s',$row['commentStamp']);
			$r = sqlite3_insertIntoTable($origTableName.'1',$row,$db,$origTableName);
			if(!$r['OK']){sqlite3_close($db);return array('errorCode'=>$r['errno'],'errorDescripcion'=>$r['error'],'query'=>$r['query'],'file'=>__FILE__,'line'=>__LINE__);}
		}}

		$r = sqlite3_exec('COMMIT;',$db);
		$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();
		$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();
print_r($GLOBALS['DB_LAST_QUERY_ERROR']);
var_dump($r);
		$r = sqlite3_exec('BEGIN;',$db);
		$r = sqlite3_exec('DROP TABLE IF EXISTS '.$origTableName.';',$db);
		$r = sqlite3_exec('ALTER TABLE '.$origTableName.'1 RENAME TO '.$origTableName.';',$db);
		$r = sqlite3_exec('COMMIT;',$db);
		sqlite3_close($db);
echo time();
exit;
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
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}sqlite3_close($db);}

		return $ban;
	}
