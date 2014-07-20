<?php
	function tracking_main(){

	}

	function tracking_insights(){
		include_once('inc.track.php');
		include_once('inc.mongo.php');
		$TEMPLATE = &$GLOBALS['TEMPLATE'];

		$db = mongo_get();
		$collection = $db->selectCollection('tracebat',$_SERVER['SERVER_NAME']);
		$collection->ensureIndex(array('trackingIP'=>1));
		$collection->ensureIndex(array('trackingUserAgent'=>1));
		$collection->ensureIndex(array('trackingURL'=>1));
		$collection->ensureIndex(array('trackingMS'=>1));
		$collection->ensureIndex(array('trackingDate'=>1));
		$collection->ensureIndex(array('trackingTime'=>1));
		$collection->ensureIndex(array('trackingHour'=>1));

		$date = date('Y-m-d');
		$rows = $collection->find(array('trackingDate'=>$date))->sort(array('trackingMS'=>-1))->limit(40);

		$TEMPLATE['html.track.table.insight'] = '<table style="width:100%;"><tbody>';
		foreach($rows as $row){
			$TEMPLATE['html.track.table.insight'] .= '<tr><td>'.$row['trackingURL'].'</td><td>'.$row['trackingMS'].'</td></tr>';
		}
		$TEMPLATE['html.track.table.insight'] .= '</tbody></table>';
		common_renderTemplate('tracking/insights');
	}
