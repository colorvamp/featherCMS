<?php
	$GLOBALS['DB_TRACKING'] = '../db/tracking.db';
	function track_googleQueries(){
		$db = new SQLite3($GLOBALS['DB_TRACKING'],SQLITE3_OPEN_READONLY);
		/* Averiguamos el rango de fecha */
		$a = date('Y');$m = date('m');$d = date('d');
		$f = ((($a % 4 == 0) && ($a % 100 != 0)) || ($a % 400 == 0)) ? 29 : 28;$months = array(31,$f,31,30,31,30,31,31,30,31,30,31);
		$m--;if($m < 1){$a--;$m=12;}if($d > $months[$m-1]){$d = $months[$m-1];}
		$bottomRange = $a.'-'.str_pad($m,2,'0',STR_PAD_LEFT).'-'.$d;

		ob_start();
		echo '<li class=\'block\'>',N,
		T,'<h2>Busquedas de google</h2>',N,
		T,'<table><tbody>',N;
		$r = $db->query('SELECT * FROM tracking WHERE trackingReferer LIKE \'%.google.%\' AND trackingDate >= \''.$bottomRange.'\' ORDER BY trackingDate DESC;');
		if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){
		$m = preg_match('/[&\?]q=([^&]*)&/',$row['trackingReferer'],$query);
		//FIXME: puede estar vacío y haber sido una búsqueda por URL -> &url=http%3A%2F%2Fspoiler...
		if(!$m){continue;}
		echo T,T,'<tr><td><a href="',$row['trackingReferer'],'">',urldecode($query[1]),'</a></td><td>',$row['trackingURL'],'</td></tr>',N;
		}}
		echo T,'</tbody></table>',N;
		$db->close();
		$GLOBALS['BLOG_CONTENT'] = ob_get_contents();
		ob_end_clean();
	}
?>
