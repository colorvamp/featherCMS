<?php
	function article_main(){
		include_once('api.articles.php');
		$r = articles_updateSchema();
		var_dump($r);
		exit;
	}

	function article_list($mod = ''){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.articles.php');
		include_once('inc.presentation.php');
		include_once('inc.requests.php');
		$currentController = str_replace('_','/',__FUNCTION__);
		$articlesPerPage = 20;

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'articleSetThumb':
				if(!isset($_POST['articleID']) || !isset($_POST['articleImage'])){break;}
				$aID = preg_replace('/[^0-9]*/','',$_POST['articleID']);if(empty($aID)){$aID = false;break;}
				$r = article_thumb_set($aID,$_POST['articleImage']);
				if(isset($r['errorDescription'])){print_r($r);exit;}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'articleRemove':
				if(!isset($_POST['articleID'])){break;}
				$aID = preg_replace('/[^0-9]*/','',$_POST['articleID']);if(empty($aID)){$aID = false;break;}
				$r = articles_remove($aID);if(isset($r['errorDescription'])){print_r($r);exit;}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'ajax.articleRemove':
				if(!isset($_POST['articleID'])){break;}
				$aID = preg_replace('/[^0-9]*/','',$_POST['articleID']);if(empty($aID)){echo json_encode(array('errorDescription'=>'INVALID_ARTICLE_ID','file'=>__FILE__,'line'=>__LINE__));exit;}
				$r = articles_remove($aID);if(isset($r['errorDescription'])){print_r($r);exit;}
				echo json_encode(array('errorCode'=>'0'));exit;
			case 'articlePublish':
				if(!isset($_POST['articleID'])){break;}
				$aID = preg_replace('/[^0-9]*/','',$_POST['articleID']);if(empty($aID)){$aID = false;break;}
				$r = articles_publish($aID);if(isset($r['errorDescription'])){print_r($r);exit;}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'articleUnpublish':
				if(!isset($_POST['articleID'])){break;}
				$aID = preg_replace('/[^0-9]*/','',$_POST['articleID']);if(empty($aID)){$aID = false;break;}
				$r = articles_unpublish($aID);if(isset($r['errorDescription'])){print_r($r);exit;}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'ajax.articlePublishScheduled':
				if(!isset($_POST['articleID'])){break;}
				$aID = preg_replace('/[^0-9]*/','',$_POST['articleID']);if(empty($aID)){echo json_encode(array('errorDescription'=>'INVALID_ARTICLE_ID','file'=>__FILE__,'line'=>__LINE__));exit;}
				$date = preg_replace('/[^0-9\-]*/','',$_POST['articlePublicationDate']);if(!preg_match('/(?<year>[0-9]{4})\-(?<month>[0-9]+)\-(?<day>[0-9]+)/',$date,$m)){echo json_encode(array('errorDescription'=>'INVALID_DATE','file'=>__FILE__,'line'=>__LINE__));exit;}
				$date = $m['year'].'-'.str_pad($m['month'],2,'0',STR_PAD_LEFT).'-'.str_pad($m['day'],2,'0',STR_PAD_LEFT);
				//FIXME: if !strtotime
				$r = articles_publishScheduled($aID,$date);
				echo json_encode(array('errorCode'=>'0'));exit;
		}}

		if(isset($_GET['criteria'])){$mod = 'search';}

		switch($mod){
			case 'draft':
				$articles = articles_getWhere('(articleIsDraft = 1)',array('order'=>'id DESC','limit'=>(($GLOBALS['currentPage']-1)*$articlesPerPage).','.$articlesPerPage));
				$r = articles_getSingle('(articleIsDraft = 1)',array('selectString'=>'count(*) as count'));
				$total = $r['count'];
				break;
			case 'search':
				if(!isset($_GET['criteria'])){echo 45;exit;}
				$articles = articles_search($_GET['criteria']);
				$total = count($articles);
				break;
			default:
				$articles = articles_getWhere(1,array('order'=>'id DESC','limit'=>(($GLOBALS['currentPage']-1)*$articlesPerPage).','.$articlesPerPage));
				$r = articles_getSingle(1,array('selectString'=>'count(*) as count'));
				$total = $r['count'];
		}
		/* Imágenes de los artículos */
		$images = article_image_getWhere('(articleID IN ('.implode(',',array_keys($articles)).'))');
		foreach($images as $k=>$image){$articles[$image['articleID']]['articleImages'][$k] = $image;}
		/* Publicaciones Programadas */
		$reqs = requests_getWhere('(requestLock = \'publishScheduled\')');
		foreach($reqs as $req){$aID = substr($req['requestParams'],2,-2);if(isset($articles[$aID])){$articles[$aID]['articlePublishDate'] = $req['requestDate'];}}

		$s = '';
		foreach($articles as $article){
			$GLOBALS['replaceIteration'] = 0;
			$article['articleURL'] = presentation_helper_getArticleURL($article);
			if(isset($article['articleImages'])){$article['json.articleImages'] = json_encode($article['articleImages']);}
			if(isset($article['articleSnippetImage']) && strlen($article['articleSnippetImage']) > 3){$article['html.articleThumb'] = '<img src="{%baseURL%}article/image/'.$article['id'].'/'.$article['articleSnippetImage'].'/64"/>';}
			if(isset($article['articleIsDraft']) && $article['articleIsDraft']){$article['html.articleIsDraft'] = '<span class="draft">Borrador</span>';$article['html.articleIsDraftClass'] = 'draft';$article['html.option.publish'] = common_loadSnippet('article/snippets/article.node.option.publish');}
			else{$article['html.option.unpublish'] = common_loadSnippet('article/snippets/article.node.option.unpublish');}
			if(isset($article['articlePublishDate'])){$article['html.articlePublishDate'] = '<i class="icon-calendar"></i> El artículo se publicará el '.$article['articlePublishDate'];}
			$s .= common_loadSnippet('article/snippets/article.node',$article);
		}
		$TEMPLATE['list.articles'] = $s;

		/* INI-Paginador */
		$pager = '<div class="btn-group pager">';
		if($GLOBALS['currentPage'] > 1){$pager .= '<a class="btn btn-small" href="{%baseURL%}'.$currentController.'/page/'.($GLOBALS['currentPage']-1).'"><i class="icon-chevron-left"></i> Anterior</a>';}
		$pager .= '<a class="btn btn-small" href="{%baseURL%}'.$currentController.'/page/'.($GLOBALS['currentPage']+1).'">Siguiente <i class="icon-chevron-right"></i></a>';
		$pager .= '</div>';
		$TEMPLATE['pager'] = $pager;
		/* END-Paginador */

		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/uploadChain.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/md5.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/base64.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/widget.calendar.js';
		common_renderTemplate('article/list');
	}

	function article_edit($aID = false){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.articles.php');
		$articleOB = false;
		if($aID){do{
			$aID = preg_replace('/[^0-9]*/','',$aID);
			if(empty($aID)){$aID = false;break;}
			$articleOB = articles_getSingle('(id = '.$aID.')');
			if(!$articleOB){$aID = false;break;}
			$articleOB['user'] = article_author_getByAuthorAlias($articleOB['articleAuthor']);
			$articleOB['articleImages'] = article_image_getWhere('(articleID = '.$aID.')');
			$articleOB['articleImagesJSON'] = json_encode($articleOB['articleImages']);
		}while(false);}

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'transfer_fragment':
				if(!$articleOB){echo json_encode(array('errorDescription'=>'ARTICLE_NOT_FOUND','file'=>__FILE__,'line'=>__LINE__));exit;}
				$_params = array('fileName','base64string_sum','base64string_len','fragment_string','fragment_num','fragment_sum');
				foreach($_params as $param){if(!isset($_POST[$param]) || $_POST[$param] === ''){print_r(array('errorDescription'=>'INVALID_PARAMS:'.$param,'file'=>__FILE__,'line'=>__LINE__));exit;}}
				$r = article_transfer_fragment($articleOB['id'],$_POST['fileName'],$_POST['base64string_sum'],$_POST['base64string_len'],$_POST['fragment_string'],$_POST['fragment_num'],$_POST['fragment_sum']);
				echo json_encode($r);exit;
			case 'ajax.articleSaveProps':
				if(!$articleOB){echo json_encode(array('errorDescription'=>'ARTICLE_NOT_FOUND','file'=>__FILE__,'line'=>__LINE__));exit;}
				$_POST['_id_'] = $articleOB['id'];
				$r = articles_save($_POST);if(isset($r['errorDescription'])){print_r($r);exit;}
				echo json_encode($r);exit;
			case 'articleSaveText':
				if($articleOB){$_POST['_id_'] = $articleOB['id'];}
				if(!$articleOB){$_POST['articleAuthor'] = $GLOBALS['user']['userNick'];}
				$_POST['articleText'] = rawurldecode($_POST['articleText']);
				$_POST['articleText'] = str_replace(array(' class="MsoNormal"',' tabindex="0"'),'',$_POST['articleText']);
				//FIXME: validar los estilos válidos
				/* DEPRECATED for compatibility */
				$_POST['articleText'] = preg_replace('/[\'\"][^\'\"]+(photos\/photo_[0-9]*\.jpeg)[\'\"]/','"$1"',$_POST['articleText']);
				$r = articles_save($_POST);
				if(isset($r['errorDescription'])){print_r($r);exit;}
				echo json_encode(array('errorCode'=>'0','data'=>$r));exit;
		}}

		if($articleOB){
			/* INI-conversion de fotos */
			/** DEPRECATED **/
			$articleOB['articleText'] = preg_replace('/[\'\"](photos\/photo_[0-9]*\.jpeg)[\'\"]/','"{%baseURL%}article/$1"',$articleOB['articleText']);
			/* END-conversion de fotos */
		}

		/* INI-Detección de estilos */
		$cssFile = '../css/renderbase.css';
		if(file_exists($cssFile)){
			$blob = file_get_contents($cssFile);
			$r = preg_match_all('/p\.(?<pRules>[^.: \{]+)/',$blob,$m);
			$pRules = array_unique($m['pRules']);
			$s = '<ul><input name="paragraphStyle" value="" type="radio" selected="selected"/> Sin estilo';foreach($pRules as $pRule){
				$s .= '<li><input name="paragraphStyle" value="'.$pRule.'" type="radio"/> '.$pRule.'</li>';
			}
			$s .= '</ul>';
			$rep = array('style.list'=>$s);
			$TEMPLATE['edit.paragraph'] = common_loadSnippet('article/snippets/article.edit.paragraph',$rep);
		}
		/* END-Detección de estilos */

		$TEMPLATE['articleOB'] = $articleOB;
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/editor.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/editor.signals.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/uploadChain.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/md5.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/base64.js';
		$TEMPLATE['BLOG_CSS'][] = '{%baseURL%}css/renderbase.css';
		$TEMPLATE['BLOG_TITLE'] = ($articleOB) ? $articleOB['articleTitle'].' by '.$articleOB['user']['userNick'] : 'Nuevo artículo';
		common_renderTemplate('article/edit');
	}

	function article_photos($photoName = false){
		/* for compatibility mode*/
		/** DEPRECATED **/
		if(!preg_match('/article\/edit\/(?<aID>[0-9]+)/',$_SERVER['HTTP_REFERER'],$m)){return false;}
		$aID = $m['aID'];
		$aID = preg_replace('/[^0-9]*/','',$aID);if(empty($aID)){return false;}
		include_once('api.articles.php');
		$articleOB = articles_getSingle('(id = '.$aID.')');if(!$articleOB){return false;}
		$time = strtotime($articleOB['articleDate']);
		$imagePath = $GLOBALS['api']['articles']['dirDB'].date('Y.m',$time).'/'.date('d',$time).'.'.$articleOB['articleName'].'/Photos/'.$photoName;
		if(!file_exists($imagePath)){return false;}
		$imgProp = @getimagesize($imagePath);
		header('Content-Type: '.$imgProp['mime']);
		readfile($imagePath);exit;
	}

	function article_image($aID = false,$imageName = false,$imageSize = false){
		include_once('api.articles.php');
		$imagePath = $GLOBALS['api']['articles']['dirDB'].$aID.'/images/'.$imageName.'/';if(!file_exists($imagePath)){exit;}
		if($imageSize){$imageSize = preg_replace('/[^0-9a-z\.]*/','',$imageSize);$imagePath .= $imageSize.'.jpeg';}
		else{$imagePath .= 'orig';}

		$imgProp = @getimagesize($imagePath);
		header('Content-Type: '.$imgProp['mime']);
		readfile($imagePath);exit;
	}
?>
