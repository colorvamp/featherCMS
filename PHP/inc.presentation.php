<?php
	function presentation_helper_getArticlePathByParams($articleYear,$articleMonth,$articleDay,$articleName){return '../db/articles/'.$articleYear.'.'.$articleMonth.'/'.$articleDay.'.'.$articleName.'/';}
	function presentation_helper_getArticleURLByParams($articleYear,$articleMonth,$articleDay,$articleName){return $GLOBALS['indexURL'].'/'.$articleYear.'/'.$articleMonth.'/'.$articleDay.'/'.$articleName;}
	function presentation_helper_getArticleURL($article){$d = explode('-',$article['articleDate']);return $GLOBALS['indexURL'].'/'.$d[0].'/'.$d[1].'/'.$d[2].'/'.$article['articleName'];}
	function presentation_main(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		$TEMPLATE['left.menu'] = common_loadSnippet('snippets/left.menu');
		if(users_checkModes('admin')){
			$GLOBALS['TEMPLATE']['left.menu.entries.config'] = '<li><a href="{%baseURL%}config"><i class="icon-cog"></i> Configuración</a></li>';
			$GLOBALS['TEMPLATE']['left.menu.entries.users'] = '<li><a href="{%baseURL%}u/list"><i class="icon-user"></i> Listado de usuarios</a></li>';
		}

		/* Calendario de publicaciones */
		$month = date('m');$year = date('Y');
		$start_day = gmmktime(0,0,0,$month,1,$year); 
		$start_day_number = date('w',$start_day)-1;if($start_day_number < 0){$start_day_number += 7;}
		$days_in_month = date('t',$start_day);
		$currentDay = date('d');

		$s = N.J.'<div class="widgetCalendar">'.N.J.'<table class="body"><tbody><tr><td class="dayName"></td><td class="dayName">Lun</td><td class="dayName">Mar</td><td class="dayName">Mie</td><td class="dayName">Jue</td><td class="dayName">Vie</td><td class="dayName">Sab</td><td class="dayName">Dom</td></tr>'.N.J.T.'<tr><td class="weekNumber"></td>'.N;
		for($x=0;$x<$start_day_number;$x++){$s .= '<td class="emptyDay"></td>';}
		for($x=1;$x<=$days_in_month;$x++){
			if(($x+$start_day_number-1)%7 == 0){$s .= '</tr>'.N.J.T.'<tr><td class="weekNumber"></td>';}
			$colDate = $year.'-'.str_pad($month,2,'0',STR_PAD_LEFT).'-'.str_pad($x,2,'0',STR_PAD_LEFT);
			$class = '';
			if($x == $currentDay){$class = 'currentDay';}
			$s .= '<td class="'.$class.'">'.$x.'</td>';
		}
		while((($days_in_month+$start_day_number)%7) != 0){$s .= '<td class="emptyDay"></td>';$days_in_month++;}
		$s .= '</tr>'.N.J.'</tbody></table>'.N.T.T.T.'</div>'.N;
		$TEMPLATE['left.menu.calendar'] = $s;

		$TEMPLATE['left.menu.controllers'] = '';
		/* Vamos a detectar controladores externos */
		if(file_exists($GLOBALS['controllersExte']) && $d = opendir($GLOBALS['controllersExte'])){
			$controllers = array();while(false !== ($f = readdir($d))){if($f[0] != '.'){$controllers[] = substr($f,0,-4);}}
			foreach($controllers as $controller){$TEMPLATE['left.menu.controllers'] .= '<li><a href="{%baseURL%}c/'.$controller.'">'.$controller.'</a></li>';}
		}
	}
	function presentation_article($article){
		$GLOBALS['replaceIteration'] = 0;
		$article['articleURL'] = presentation_helper_getArticleURL($article);
		if(isset($article['articleImages'])){$article['json.articleImages'] = json_encode($article['articleImages']);}
		if(isset($article['articleSnippetImage']) && strlen($article['articleSnippetImage']) > 3 && substr($article['articleSnippetImage'],0,1) == '{'){
			$im = json_decode($article['articleSnippetImage'],1);
			if(isset($im['articleImageSmall'])){$article['html.articleThumb'] = '<img src="{%baseURL%}article/image/'.$article['id'].'/'.$im['articleImageSmall'].'/64"/>';}
		}

		if(isset($article['articleIsDraft']) && $article['articleIsDraft']){$article['html.articleIsDraft'] = '<span class="draft">Borrador</span>';$article['html.articleIsDraftClass'] = 'draft';$article['html.option.publish'] = common_loadSnippet('article/snippets/article.node.option.publish');}
		else{$article['html.option.unpublish'] = common_loadSnippet('article/snippets/article.node.option.unpublish');}

		if(isset($article['articlePublishDate'])){$article['html.articlePublishDate'] = '<i class="icon-calendar"></i> El artículo se publicará el '.$article['articlePublishDate'];}
		if(isset($article['comments'])){
			$article['html.comments'] = '';
			foreach($article['comments'] as $comment){
				$comment['html.comment.class'] = '';
				if(!$comment['commentReview']){$comment['html.comment.class'] = 'disabled';}
				$article['html.comments'] .= common_loadSnippet('article/snippets/comment.node',$comment);
			}
		}
		$article['articleSnippet'] = preg_replace('/\{%image:[^%]*%?\}?/sm',' ',$article['articleSnippet']);
		return common_loadSnippet('article/snippets/article.node',$article);
	}
?>
