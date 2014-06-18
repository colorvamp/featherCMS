<?php
	function tracking_main(){

	}

	function tracking_insights(){
		include_once('inc.track.php');
		$TEMPLATE = &$GLOBALS['TEMPLATE'];

		$date = date('Y-m-d');
		$rows = tracking_getWhere('(trackingDate = \''.$date.'\')',array('order'=>'trackingMS DESC','limit'=>40,'db.file'=>$GLOBALS['api']['track']['db.tmp']));
		$TEMPLATE['html.track.table.insight'] = '<table><tbody>';
		foreach($rows as $row){
			$TEMPLATE['html.track.table.insight'] .= '<tr><td>'.$row['trackingURL'].'</td><td>'.$row['trackingMS'].'</td></tr>';
		}
		$TEMPLATE['html.track.table.insight'] .= '</tbody></table>';
		common_renderTemplate('tracking/insights');
	}
