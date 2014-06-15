<?php
	function article_comment_getSingle($whereClause = false,$params = array()){
		include_once('inc.sqlite3.php');
//FIXME: ya no se hace así
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
		include_once('inc.spam.php');

		$comment = array();
		if(isset($params['_id_'])){do{
			$params['_id_'] = preg_replace('/[^0-9]*/','',$params['_id_']);
			if(empty($params['_id_'])){unset($params['_id_']);break;}
			$comment = article_comment_getSingle('(id = '.$params['_id_'].')');
			if(!$comment){unset($params['_id_']);break;}
			$comment['_id_'] = $comment['id'];unset($comment['id']);
		}while(false);}

		$t = time();
		$comment = array_merge($comment,$params);
		if(!isset($comment['commentTitle'])){$comment['commentTitle'] = '';}
		if(!isset($comment['commentTags'])){$comment['commentTags'] = ',';}
		if(!isset($comment['commentAuthor']) && !isset($comment['commentUserName'])){$comment['commentAuthor'] = 'dummy';}
		if(!isset($comment['commentChannel'])){$comment['commentChannel'] = '0';}
		if(!isset($comment['commentReview'])){$comment['commentReview'] = '0';}
		if(!isset($comment['commentIP']) && isset($_SERVER['REMOTE_ADDR'])){$comment['commentIP'] = $_SERVER['REMOTE_ADDR'];}
		if(!isset($comment['_id_'])){$comment = array_merge($comment,array('commentRating'=>0,'commentVotesCount'=>0,'commentStamp'=>$t,'commentDate'=>date('Y-m-d',$t),'commentTime'=>date('H:m:s',$t)));}
		if(!isset($comment['commentText'])){return array('errorDescription'=>'EMPTY_TEXT','file'=>__FILE__,'line'=>__LINE__);}

		/* INI-Comprobamos los bans */
		$bans = article_ban_getWhere('(banTarget = \'ip:'.$comment['commentIP'].'\')');
		if($bans){foreach($bans as $ban){
			if($ban['banType'] == 'comments-disabled'){return array('errorDescription'=>'BANNED','file'=>__FILE__,'line'=>__LINE__);}
		}}
		/* END-Comprobamos los bans */

		/* INI-Filtramos span */
		//FIXME: esto habría que hacerlo con $limit y hacelo en bloques de 1000 o así
		//FIXME: habría que tener en cuenta spampool
		$textLength = strlen($comment['commentText']);
		$spamStrings = spam_string_getWhere(1);
		foreach($spamStrings as $string){
			if(isset($comment['commentUserURL']) && strpos($comment['commentUserURL'],$string['spamString']) !== false){sleep(10);return array('errorDescription'=>'BANNED','file'=>__FILE__,'line'=>__LINE__);}
			if(strlen($string['spamString'] > $textLength)){continue;}
			if(strpos($comment['commentText'],$string['spamString']) !== false){sleep(10);return array('errorDescription'=>'BANNED','file'=>__FILE__,'line'=>__LINE__);}
		}
		/* END-Filtramos span */

		$comment['commentTitle'] = strings_UTF8Encode($comment['commentTitle']);
		$comment['commentName'] = strings_stringToURL($comment['commentTitle']);
		$comment['commentChannel'] = preg_replace('/[^0-9]*/','',$comment['commentChannel']);
		$comment['commentTags'] = strings_stringToURL(str_replace(',',' ',$comment['commentTags']));$comment['commentTags'] = ','.implode(',',array_diff(explode('-',$comment['commentTags']),array(''))).',';
		if(isset($comment['commentIP'])){$comment['commentIP'] = preg_replace('/[^0-9\.\:]*/','',$comment['commentIP']);}
		$comment['commentText'] = preg_replace('/^[\xEF\xBB\xBF|\x1A]/','',$comment['commentText']);
		$comment['commentText'] = preg_replace('/\r?\n/',PHP_EOL,$comment['commentText']);

		if(strpos($comment['commentText'],'<') === false){
			if(!function_exists('markdown_toHTML')){include_once('inc.markdown.php');}
			$comment['commentText'] = markdown_toHTML($comment['commentText']);
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
			$comment['commentText'] = strip_tags($comment['commentText'],'<p><a><strong><em><ol><ul><li><blockquote>');
//FIXME: hay que tener cuidado con los <a>
//FIXME: hacerlo con preg_replace_callback
			//$comment['commentText'] = preg_replace('/&lt;(\/?)([bisp]{1})&gt;/','<$1$2>',$comment['commentText']);
			//FIXME: quitar del final tb
			$comment['commentText'] = preg_replace('/^[ \n\t\r]*/','',$comment['commentText']);
			//FIXME: pasar a la nueva forma
			foreach($reps as $k=>$r){$comment['commentText'] = preg_replace($r['regex'],'<a class=\'link_'.$k.'\' href=\'$'.$r['href'].'\'>$'.$r['text'].'</a>',$comment['commentText']);}
		}

		/* INI-limpieza de texto */
		$comment['commentText'] = preg_replace('/<p[^>]*><\/p[^>]*>/','',$comment['commentText']);
		/* END-limpieza de texto */

		$comment['commentTextClean'] = article_helper_cleanText($comment['commentText']);

		$shouldClose = false;if(!$db){$db = sqlite3_open($GLOBALS['api']['articles']['db']);$r = sqlite3_exec('BEGIN;',$db);$shouldClose = true;}
		$r = sqlite3_insertIntoTable($GLOBALS['api']['articles']['table.comments'],$comment,$db,'articleComments');
		if(!$r['OK']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);}
		$comment = article_comment_getSingle('(id = '.$r['id'].')',array('db'=>$db));
		if(!$comment){return array('errorDescription'=>'UNKNOWN_ERROR','file'=>__FILE__,'line'=>__LINE__);}
//$article['user'] = article_author_getByAuthorAlias($article['articleAuthor'],array('db'=>$db));
		if($shouldClose){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();if(!$r){sqlite3_close($db);return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}sqlite3_close($db);}

		return $comment;
	}
