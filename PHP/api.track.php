<?php
	include_once('inc.track.php');
	$GLOBALS['tables']['trackingData'] = array('_key_'=>'TEXT UNIQUE','value'=>'TEXT');
	$GLOBALS['tables']['trackingTotalCount'] = array('_trackingURL_'=>'TEXT','_trackingDate_'=>'TEXT','trackingCount'=>'INTEGER','trackingUpdated'=>'TEXT');
	$GLOBALS['tables']['trackingCount'] = array('_trackingURL_'=>'TEXT','_trackingDate_'=>'TEXT','trackingCount'=>'INTEGER');
	
?>
