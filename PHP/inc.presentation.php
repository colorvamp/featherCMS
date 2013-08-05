<?php
	function presentation_helper_getArticlePathByParams($articleYear,$articleMonth,$articleDay,$articleName){return '../db/articles/'.$articleYear.'.'.$articleMonth.'/'.$articleDay.'.'.$articleName.'/';}
	function presentation_helper_getArticleURLByParams($articleYear,$articleMonth,$articleDay,$articleName){return $GLOBALS['baseURL'].$articleYear.'/'.$articleMonth.'/'.$articleDay.'/'.$articleName;}
	function presentation_helper_getArticleURL($article){$d = explode('-',$article['articleDate']);return $GLOBALS['baseURL'].$d[0].'/'.$d[1].'/'.$d[2].'/'.$article['articleName'];}
?>
