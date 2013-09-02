<?php
	function presentation_helper_getArticlePathByParams($articleYear,$articleMonth,$articleDay,$articleName){return '../db/articles/'.$articleYear.'.'.$articleMonth.'/'.$articleDay.'.'.$articleName.'/';}
	function presentation_helper_getArticleURLByParams($articleYear,$articleMonth,$articleDay,$articleName){return $GLOBALS['baseURL'].$articleYear.'/'.$articleMonth.'/'.$articleDay.'/'.$articleName;}
	function presentation_helper_getArticleURL($article){$d = explode('-',$article['articleDate']);return $GLOBALS['baseURL'].$d[0].'/'.$d[1].'/'.$d[2].'/'.$article['articleName'];}
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
	}

function presentation_sidebar_calendar(){
		$month = date('m');$year = date('Y');
		echo N,T,T,'<h3>Calendario de ArtÃ­culos</h3>',N,
		T,T,T,'<div class=\'calendar\'>',N,T,T,T,T,'<table><thead><tr><td>Lun</td><td>Mar</td><td>Mie</td><td>Jue</td><td>Vie</td><td>Sab</td><td>Dom</td></tr></thead><tbody>',N,T,T,T,T,T,'<tr>',N;

		$start_day = gmmktime(0,0,0,$month,1,$year); 
		$start_day_number = date('w',$start_day)-1;if($start_day_number < 0){$start_day_number += 7;}
		$days_in_month = date('t',$start_day);
		$currentDay = date('d');

		for($x=0;$x<$start_day_number;$x++){echo '<td>x</td>';}
		for($x=1;$x<=$days_in_month;$x++){
			if(($x+$start_day_number-1)%7==0){echo '</tr>',N,T,T,T,T,T,'<tr>';}
			$colDate = $year.'-'.str_pad($month,2,'0',STR_PAD_LEFT).'-'.str_pad($x,2,'0',STR_PAD_LEFT);
			$class = '';
			if($x == $currentDay){$class = 'currentDay';}
			echo '<td class=\'',$class,'\'><div>',$x,'</div></td>';
		}
		while((($days_in_month+$start_day_number)%7)!=0){echo '<td>x</td>';$days_in_month++;}
		echo '</tr>',N,T,T,T,T,'</tbody></table>',N,T,T,T,'</div>',N;
	}
?>
