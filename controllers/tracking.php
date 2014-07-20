<?php
	function tracking_main(){

	}

	function tracking_insights($date = false){
		include_once('inc.track.php');
		include_once('inc.mongo.php');
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		if(!$date || !strtotime($date)){$date = date('Y-m-d');}

		$db = mongo_get();
		$collection = $db->selectCollection('tracebat',$_SERVER['SERVER_NAME']);
		$collection->ensureIndex(array('trackingIP'=>1));
		$collection->ensureIndex(array('trackingUserAgent'=>1));
		$collection->ensureIndex(array('trackingURL'=>1));
		$collection->ensureIndex(array('trackingMS'=>1));
		$collection->ensureIndex(array('trackingDate'=>1));
		$collection->ensureIndex(array('trackingTime'=>1));
		$collection->ensureIndex(array('trackingHour'=>1));

		$hours = array();$i = 0;while($i < 24){if(!isset($hours[$i])){$hours[sprintf('%02s',$i)] = 0;}$i++;}

		$visitsByTimeGreen = $hours;
		$rs = $collection->aggregate(
			array('$match'=>array('trackingDate'=>$date,'trackingMS'=>array('$lt'=>1)))
			,array('$group'=>array('_id'=>'$trackingHour','count'=>array('$sum'=>1)))
		);
		foreach($rs['result'] as $h){$visitsByTimeGreen[sprintf('%02s',$h['_id'])] = $h['count'];}

		$visitsByTimeCaution = $hours;
		$rs = $collection->aggregate(
			array('$match'=>array('trackingDate'=>$date,'trackingMS'=>array('$gte'=>1,'$lt'=>4)))
			,array('$group'=>array('_id'=>'$trackingHour','count'=>array('$sum'=>1)))
		);
		foreach($rs['result'] as $h){$visitsByTimeCaution[sprintf('%02s',$h['_id'])] = $h['count'];}

		$visitsByTimeDanger = $hours;
		$rs = $collection->aggregate(
			array('$match'=>array('trackingDate'=>$date,'trackingMS'=>array('$gte'=>4)))
			,array('$group'=>array('_id'=>'$trackingHour','count'=>array('$sum'=>1)))
		);
		foreach($rs['result'] as $h){$visitsByTimeDanger[sprintf('%02s',$h['_id'])] = $h['count'];}

		include_once('inc.graph.php');
		$svg = graph_lines(array(
			'graph.legend.width'=>60,
			'graph.height'=>160,
			'cell.width'=>26,
			'cell.marginx'=>4,
			'cell.marginy'=>14,
			'bar.indicator'=>true,
			'graph.gradient.from'=>'8cc277',
			'graph.gradient.to'=>'6fa85b',
			'graph'=>array(
				'green'=>$visitsByTimeGreen,
				'caution'=>$visitsByTimeCaution,
				'danger'=>$visitsByTimeDanger
			),
			'graph.colors'=>array(
				array('8cc277','6fa85b'),
				array('2980b9','216896'),
				'f00'
			),
			'header'=>array_keys($hours),
			'table'=>true
		));
		$TEMPLATE['html.track.graph'] = $svg;

		$rows = $collection->find(array('trackingDate'=>$date))->sort(array('trackingMS'=>-1))->limit(40);
		$TEMPLATE['html.track.table.insight'] = '';
		foreach($rows as $row){
			$row['trackingMS'] = round($row['trackingMS'],2);
			$row['trackingTime'] = date('H:i:s',$row['trackingStamp']);
			$TEMPLATE['html.track.table.insight'] .= common_loadSnippet('tracking/snippets/insights.row.info',$row);
		}
		common_renderTemplate('tracking/insights');
	}

	function tracking_hour($date = false,$hour = false){
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

		$rs = $collection->aggregate(
			array('$match'=>array('trackingDate'=>date('Y-m-d'),'trackingMS'=>array('$gt'=>0.9,'$lt'=>4)))
			,array('$group'=>array('_id'=>'$trackingHour','count'=>array('$sum'=>1)))
			//,array('$sort'=>array('count'=>-1))
		);
print_r($rs);
exit;
	}
