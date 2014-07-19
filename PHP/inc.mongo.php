<?php
	if(!isset($GLOBALS['api']['mongo'])){$GLOBALS['api']['mongo'] = array();}
	$GLOBALS['api']['mongo'] = array_merge(array(
		'db'=>false
	),$GLOBALS['api']['mongo']);

	function mongo_get(){
		if($GLOBALS['api']['mongo']['db'] !== false){
			return $GLOBALS['api']['mongo']['db'];
		}

		$GLOBALS['api']['mongo']['db'] = new MongoClient();
		return $GLOBALS['api']['mongo']['db'];
	}
	function mongo_processWhere($whereClause = ''){
		$find = array();
		if($whereClause === 1 || !$whereClause){return $find;}
		if(preg_match('/^(?<fieldName>[^ ]+) IN \((?<values>[^\)]+)\)$/',$whereClause,$m)){
			$integers = $m['values'][0] != '\'';
			$values = explode(',',str_replace('\'','',$m['values']));
			if($integers){$values = array_map(function($n){return intval($n);},$values);}
			$find[$m['fieldName']] = array('$in'=>$values);
			return $find;
		}
		//if(preg_match('/^(?<fieldName>[^ ]+) IN \((?<values>[^\)]+)\)$/',$whereClause,$m)){

		//}
echo 'Not supported '.$whereClause.PHP_EOL;exit;
		return $find;
	}
	function mongo_getSingle($database = '',$table = '',$whereClause = '',$params = array()){
		$db = mongo_get();
		$find = mongo_processWhere($whereClause);
		$collection = $db->selectCollection($database,$table);
		$row = $collection->findOne($find);
		return $row;
	}
	function mongo_getWhere($database = '',$table = '',$whereClause = '',$params = array()){
		if(!isset($params['indexBy'])){$params['indexBy'] = 'id';}
		$skip = 0;
		$limit = 2000;
		$sort = array('_id'=>1);
		if(isset($params['limit'])){
			$limit = $params['limit'];
			if(strpos($params['limit'],',')){list($skip,$limit) = explode(',',$params['limit']);}
		}
		if(isset($params['order'])){
			$sort = array($params['order']=>1);
			if(strpos($params['order'],' ')){
				//FIXME: TODO
			}
		}
		$db = mongo_get();
		$find = mongo_processWhere($whereClause);
		$collection = $db->selectCollection($database,$table);
		$r = $collection->find($find)->sort($sort)->skip($skip)->limit($limit);

		$rows = array();
		if($r && $params['indexBy'] !== false){foreach($r as $row){$rows[$row[$params['indexBy']]] = $row;}}
		if($r && $params['indexBy'] === false){foreach($r as $row){$rows[] = $row;}}

		return $rows;
	}




	function mongo_connect($database = ''){
		$m = new MongoClient();
		$m->selectDB($database);
		$m->dbName = $database;
		return $m;
	}
	function mongo_autoincrement($m,$table,$field){
		$id = $table.'.'.$field;
		//$m->counters->insert(array('_id'=>$id,'seq'=>0));
print_r($m->counters);
		$m->counters->findAndModify( array('_id'=>$id) , array('$inc'=>array('seq'=>1)) , null , array('new'=>true) );
	}
	function mongo_save(&$collection = false,$row = array(),&$params = array()){
		if(!isset($params['db'])){
			//FIXME:
			$params['db'] = mongo_connect('spoiler');
		}
		if(is_string($collection)){
			$collection = $params['db']->selectCollection($params['db']->dbName,$collection);
		}
		$name = $collection->getName();

		$autoincrements = array();
		if(isset($GLOBALS['tables'][$name])){
			//foreach(){}
			//print_r($GLOBALS['tables'][$name]);
		}
	}
