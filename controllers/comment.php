<?php
	function comment_list(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.articles.php');
		include_once('inc.presentation.php');
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

		$comments = article_comment_getWhere(1,array('order'=>'id DESC,commentTime DESC','limit'=>(($GLOBALS['currentPage']-1)*$commentsPerPage).','.$commentsPerPage));
		$articleIDs = array_map(function($n){return $n['commentChannel'];},$comments);
		$articleIDs = array_unique($articleIDs);
		$articleOBs = articles_getWhere('(id IN ('.implode(',',$articleIDs).'))');

		$TEMPLATE['list.comments'] = '';
		foreach($comments as $comment){
$comment['commentDate'] = date('Y-m-d',$comment['commentTime']);
$comment['commentTime'] = date('H:i:s',$comment['commentTime']);
			if(!$comment['commentReview']){$comment['html.commentReview'] = '<span class="review">No revisado</span>';$comment['html.commentReviewClass'] = 'disabled';}
			if(isset($articleOBs[$comment['commentChannel']])){$comment = array_merge($comment,array('commentArticleTitle'=>$articleOBs[$comment['commentChannel']]['articleName'],'commentArticleURL'=>presentation_helper_getArticleURL($articleOBs[$comment['commentChannel']])));}
			$TEMPLATE['list.comments'] .= common_loadSnippet('comment/snippets/comment.node',$comment);
		}

		common_renderTemplate('comment/list');
	}
?>
