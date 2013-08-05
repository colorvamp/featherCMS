<?php
	function article_main(){
		include_once('api.articles.php');
		$r = articles_updateSchema();
		print_r($r);
		exit;
	}

	function article_list(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.articles.php');
		include_once('inc.presentation.php');
		$currentController = str_replace('_','/',__FUNCTION__);
		$articlesPerPage = 20;

		$articles = articles_getWhere(1,array('order'=>'id DESC','limit'=>(($GLOBALS['currentPage']-1)*$articlesPerPage).','.$articlesPerPage));
		$r = articles_getSingle(1,array('selectString'=>'count(*) as count'));
		$total = $r['count'];

		$s = '';
		foreach($articles as $article){
			$article['articleURL'] = presentation_helper_getArticleURL($article);
			$s .= common_loadSnippet('article/snippets/article.node',$article);
		}
		$TEMPLATE['list.articles'] = $s;
		/* INI-Paginador */
		$pager = '<div class="btn-group pager">';
		if($GLOBALS['currentPage'] > 1){$pager .= '<a class="btn btn-small" href="{%assisURL%}'.$currentController.'/page/'.($GLOBALS['currentPage']-1).'">Anterior</a>';}
		$pager .= '<a class="btn btn-small" href="{%assisURL%}'.$currentController.'/page/'.($GLOBALS['currentPage']+1).'">Siguiente</a>';
		$pager .= '</div>';
		$TEMPLATE['pager'] = $pager;
		/* END-Paginador */

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
				$_params = array('fileName','base64string_sum','base64string_len','fragment_string','fragment_num','fragment_sum');
				foreach($_params as $param){if(!isset($_POST[$param]) || $_POST[$param] === ''){print_r(array('errorDescription'=>'INVALID_PARAMS:'.$param,'file'=>__FILE__,'line'=>__LINE__));exit;}}
				$r = article_transfer_fragment($articleOB['id'],$_POST['fileName'],$_POST['base64string_sum'],$_POST['base64string_len'],$_POST['fragment_string'],$_POST['fragment_num'],$_POST['fragment_sum']);
				echo json_encode($r);
				exit;
			case 'articleSaveProps':
				if(!$articleOB){echo json_encode(array('errorDescription'=>'ARTICLE_NOT_FOUND','file'=>__FILE__,'line'=>__LINE__));exit;}
				$_POST['_id_'] = $articleOB['id'];
				$r = articles_save($_POST);
				if(isset($r['errorDescription'])){print_r($r);exit;}
				echo json_encode($r);exit;
			case 'articleSaveText':
				if(!$articleOB){echo json_encode(array('errorDescription'=>'ARTICLE_NOT_FOUND','file'=>__FILE__,'line'=>__LINE__));exit;}
				$_POST['_id_'] = $articleOB['id'];
				$r = articles_save($_POST);
				if(isset($r['errorDescription'])){print_r($r);exit;}
				echo json_encode($r);exit;
		}}

		$TEMPLATE['articleOB'] = $articleOB;

		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/editor.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/editor.signals.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/uploadChain.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/md5.js';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}js/base64.js';
		$TEMPLATE['BLOG_CSS'][] = '{%baseURL%}css/renderbase.css';
		$TEMPLATE['BLOG_TITLE'] = $articleOB['articleTitle'].' by '.$articleOB['user']['authorAlias'];
		common_renderTemplate('article/edit');
	}

	function article_image($aID = false,$imageName = false){
		include_once('api.articles.php');
		$imagePath = $GLOBALS['api']['articles']['dirDB'].$aID.'/images/'.$imageName.'/orig';
		if(!file_exists($imagePath)){exit;}
		$imgProp = @getimagesize($imagePath);
		header('Content-Type: '.$imgProp['mime']);
		readfile($imagePath);exit;
	}
?>
