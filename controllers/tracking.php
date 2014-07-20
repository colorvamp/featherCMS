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

		$TEMPLATE['html.track.table.insight'] = '';
		foreach($rows as $row){
			$row['trackingMS'] = round($row['trackingMS'],2);
			$TEMPLATE['html.track.table.insight'] .= common_loadSnippet('tracking/snippets/insights.row.info',$row);
		}
		common_renderTemplate('tracking/insights');
	}
