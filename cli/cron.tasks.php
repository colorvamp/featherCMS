<?php
	include_once('inc.cli.php');
	include_once('inc.requests.php');

	$request = requests_getSingle('(requestStatus = \'awaiting\')');
	if(!$request){return true;}

	$pid = getmypid();
	$r = requests_update(array('_id_'=>$request['id'],'requestStatus'=>'running'));
	if(isset($r['errorDescription'])){return $r;}

	/* Registramos las funciones de soporte */
	$r = register_shutdown_function('requests_callbacks_onShutdown');
	$r = register_tick_function('requests_callbacks_onTick');

	/* Deshabilitamos las requests para evitar que se creen request o locks hijos */
	requests_disable();

	include_once($request['requestModule']);
	$timeStart = microtime(1);
	$args = json_decode($request['requestParams'],1);
	ob_start();$ret = call_user_func_array($request['requestCall'],$args);$warns = ob_get_contents();ob_end_clean();
	ob_start();print_r($ret);$return = ob_get_contents();ob_end_clean();
	$timeEnd = microtime(1);
	requests_restore();
print_r($request);
?>
