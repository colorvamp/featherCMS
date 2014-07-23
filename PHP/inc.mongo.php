<?php
	if(!isset($GLOBALS['api']['mongo'])){$GLOBALS['api']['mongo'] = array();}
	$GLOBALS['api']['mongo'] = array_merge(array(
		'db'=>false,
		'collections'=>[]
	),$GLOBALS['api']['mongo']);

	function mongo_client_get(){
		if($GLOBALS['api']['mongo']['db'] !== false){
			return $GLOBALS['api']['mongo']['db'];
		}

		$GLOBALS['api']['mongo']['db'] = new MongoClient();
		return $GLOBALS['api']['mongo']['db'];
	}
	function mongo_collection_get($dbName = '',$table = '',$params = array()){
		if(isset($GLOBALS['api']['mongo']['collection'][$dbName][$table])){return $GLOBALS['api']['mongo']['collection'][$dbName][$table];}
		$client = ($GLOBALS['api']['mongo']['db']) ? $GLOBALS['api']['mongo']['db'] : mongo_client_get();

		//FIXME: hacer índices
		return ($GLOBALS['api']['mongo']['collection'][$dbName][$table] = $client->selectCollection($dbName,$table));
	}

	function mongo_processCondition($cond = ''){
		switch(true){
			case preg_match('/^(?<field>[^ ]+) = (?<value>[^\)]+)$/',$cond,$m):return array($m['field']=>$m['value']);
			case preg_match('/^(?<fieldName>[^ ]+) IS NULL$/',$cond,$m):return array('$exists'=>true);
			default:
				echo 'Not supported condition '.$cond.PHP_EOL;exit;
		}
	}
	function mongo_processWhere($whereClause = ''){
		$find = array();
		switch(true){
			case ($whereClause === 1 || !$whereClause || $whereClause === '1'):return $find;
			case preg_match('/^[\(]*(?<field>[^ ]+) = (?<value>[^\)]+)[\)]*$/',$whereClause,$m):
				if($m['value'][0] !== '\'' && $m['value'][0] !== '"' && is_numeric($m['value'])){$m['value'] = floatval($m['value']);}
				return array($m['field']=>$m['value']);
			case preg_match('/^[\(]*(?<fieldName>[^ ]+) IN \((?<values>[^\)]+)\)[\)]*$/',$whereClause,$m):
				$integers = $m['values'][0] != '\'';
				$values = explode(',',str_replace('\'','',$m['values']));
				if($integers){$values = array_map(function($n){return intval($n);},$values);}
				$find[$m['fieldName']] = array('$in'=>$values);
				return $find;
			case preg_match('/^[\(]*(?<cond1>[^\)]+) OR (?<cond2>[^\)]+)[\)]*$/',$whereClause,$m):
				$cond1 = mongo_processCondition($m['cond1']);
				$cond2 = mongo_processCondition($m['cond2']);
				$find = array('$or'=>array($cond1,$cond2));
				return $find;
			default:
				echo 'Not supported '.$whereClause.PHP_EOL;exit;
		}
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
			if(($p = strpos($params['order'],' '))){
				/* Support for 'ORDER field (ASC|DESC)' */
				$field = substr($params['order'],0,$p);
				$o = substr($params['order'],$p+1);
				$sort = array($field=>($o == 'ASC') ? 1 : -1);
			}
		}

		$db = mongo_get();
		$find = mongo_processWhere($whereClause);
		$collection = $db->selectCollection($database,$table);

		if(isset($params['selectString']) && preg_match('/count\((?<field>[^\)]+)\) as (?<alias>[^, ])/',$params['selectString'],$m)){
			$params['selectString'] = str_replace($m[0],',',$params['selectString']);
			$selectString = explode(',',$params['selectString']);
			$selectString = array_diff($selectString,array(''));
			$selectString = array_fill_keys($selectString,1);
			if(!isset($selectString[$params['indexBy']])){$params['indexBy'] = false;}

			if($m['field'] == '*'){$m['field'] = 'id';}
			if(isset($params['group'])){$m['field'] = $params['group'];}
			$pipeline = array();
			if($find){$pipeline[] = array('$match'=>$find);}
			$pipeline[] = array('$group'=>array('_id'=>'$'.$m['field'],$m['alias']=>array('$sum'=>1)));
			if($sort){$pipeline[] = array('$sort'=>$sort);}
			if($skip){$pipeline[] = array('$skip'=>$skip);}
			if($limit){$pipeline[] = array('$limit'=>$limit);}
			$r = $collection->aggregate($pipeline);
			$countResult = $r['result'];

			$tmp = array();foreach($countResult as $result){$tmp[$result['_id']] = $result[$m['alias']];}$countResult = $tmp;
			$values = array_keys($countResult);
			$find = array($m['field']=>array('$in'=>$values));
			$r = $collection->find($find,$selectString);
			$r = iterator_to_array($r);
			foreach($r as &$row){
				if(!isset($countResult[$row[$m['field']]])){$row[$m['alias']] = 0;continue;}
				$row[$m['alias']] = $countResult[$row[$m['field']]];
			}
		}else{
			$r = $collection->find($find)->sort($sort)->skip($skip)->limit($limit);
		}

		$rows = array();
		if($r && $params['indexBy'] !== false){foreach($r as $row){$rows[$row[$params['indexBy']]] = $row;}}
		if($r && $params['indexBy'] === false){foreach($r as $row){$rows[] = $row;}}

		return $rows;
	}



	function mongo_get(){
		if($GLOBALS['api']['mongo']['db'] !== false){
			return $GLOBALS['api']['mongo']['db'];
		}

		$GLOBALS['api']['mongo']['db'] = new MongoClient();
		return $GLOBALS['api']['mongo']['db'];
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
