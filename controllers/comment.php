<?php
	function comment_list(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.articles.php');
		include_once('inc.presentation.php');
		include_once('inc.spam.php');
		$currentController = str_replace('_','/',__FUNCTION__);
		$commentsPerPage = 20;

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'commentRemove':
				if(!isset($_POST['commentID'])){break;}
				$cID = preg_replace('/[^0-9]*/','',$_POST['commentID']);if(empty($cID)){break;}
				$r = article_comment_deleteWhere('(id = '.$cID.')');
				if(isset($r['errorDescription'])){print_r($r);exit;}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'commentApprove':
				if(!isset($_POST['commentID'])){break;}
				$cID = preg_replace('/[^0-9]*/','',$_POST['commentID']);if(empty($cID)){break;}
				$comment = article_comment_getSingle('(id = '.$cID.')');if(!$comment){break;}
				$params = array('_id_'=>$cID,'commentReview'=>1);
				$r = article_comment_save($params);
				if(isset($r['errorDescription'])){print_r($r);exit;}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'ajax.commentRemove':
				if(!isset($_POST['commentID'])){break;}
				$cID = preg_replace('/[^0-9]*/','',$_POST['commentID']);if(empty($cID)){break;}
				$r = article_comment_deleteWhere('(id = '.$cID.')');
				if(isset($r['errorDescription'])){print_r($r);exit;}
				echo json_encode(array('errorCode'=>'0'));exit;
		}}

		$pagerParams = '?';
		$whereClause = '1';
		if(isset($_GET['spamString'])){
			$id = preg_replace('/[^0-9]*/','',$_GET['spamString']);
			if(!($spamString = spam_string_getSingle('(id = '.$id.')'))){common_r();}
			$whereClause = '(commentUserURL LIKE \'%'.$spamString['spamString'].'%\' OR commentText LIKE \'%'.$spamString['spamString'].'%\')';
			$pagerParams .= 'spamString='.$id.'&';
		}
		$comments = article_comment_getWhere($whereClause,array('order'=>'id DESC,commentTime DESC','limit'=>(($GLOBALS['currentPage']-1)*$commentsPerPage).','.$commentsPerPage));
		$r = article_comment_getSingle(1,array('selectString'=>'count(*) as count'));
		$total = $r['count'];
		$articleIDs = array_map(function($n){return $n['commentChannel'];},$comments);
		$articleIDs = array_unique($articleIDs);
		$articleOBs = articles_getWhere('(id IN ('.implode(',',$articleIDs).'))');

		$TEMPLATE['list.comments'] = '';
		foreach($comments as $comment){
			$comment['html.commentAuthor'] = !empty($comment['commentAuthor']) ? $comment['commentAuthor'] : $comment['commentUserName'];
			if(!$comment['commentReview']){$comment['html.commentReview'] = '<span class="review">No revisado</span>';$comment['html.commentReviewClass'] = 'disabled';}
			if(isset($articleOBs[$comment['commentChannel']])){$comment = array_merge($comment,array('commentArticleTitle'=>$articleOBs[$comment['commentChannel']]['articleTitle'],'commentArticleURL'=>presentation_helper_getArticleURL($articleOBs[$comment['commentChannel']])));}
			$TEMPLATE['list.comments'] .= common_loadSnippet('comment/snippets/comment.node',$comment);
		}

		/* INI-Paginador */
		$pagerParams = substr($pagerParams,0,-1);
		$pager = '<div class="btn-group pager">';
		if($GLOBALS['currentPage'] > 1){$pager .= '<a class="btn btn-small" href="{%baseURL%}'.$currentController.'/page/'.($GLOBALS['currentPage']-1).$pagerParams.'"><i class="icon-chevron-left"></i> Anterior</a>';}
		$pager .= '<a class="btn btn-small" href="{%baseURL%}'.$currentController.'/page/'.($GLOBALS['currentPage']+1).$pagerParams.'">Siguiente <i class="icon-chevron-right"></i></a>';
		$pager .= '</div>';
		$TEMPLATE['pager'] = $pager;
		/* END-Paginador */

		common_renderTemplate('comment/list');
	}

	function comment_spamMatch(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.articles.php');
		include_once('inc.spam.php');

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'spam.string.add':
				$r = spam_string_save($_POST);
				if(isset($r['errorDescription'])){print_r($r);}
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'spam.string.search':
				if(!isset($_POST['id'])){common_r();}
				$id = preg_replace('/[^0-9]*/','',$_POST['id']);
				if(!($spamString = spam_string_getSingle('(id = '.$id.')'))){common_r();}
				$uri = $GLOBALS['baseURL'].'comment/list?spamString='.$id;
				common_r($uri);
		}}

		$spamStrings = spam_string_getWhere(1);
		$TEMPLATE['list.spamStrings'] = '<table><thead><tr><td></td><td>Cadena</td><td>Destino</td><td></td></tr></thead><tbody>';
		foreach($spamStrings as $string){
			$TEMPLATE['list.spamStrings'] .= common_loadSnippet('comment/snippets/spam.match.row',$string);
		}
		$TEMPLATE['list.spamStrings'] .= '<tbody></table>';

		$TEMPLATE['PAGE.MENU'] = common_loadSnippet('comment/snippets/spam.match.menu');
		common_renderTemplate('comment/spam.match');
	}
?>
