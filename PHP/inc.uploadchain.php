<?php
	if(!isset($GLOBALS['api']['uploadchain'])){$GLOBALS['api']['uploadchain'] = array();}
	$GLOBALS['api']['uploadchain'] = array_merge($GLOBALS['api']['uploadchain'],array(
		'tmp.files'=>array()
	));

	function uploadchain_fragment($params = array()){
		$tmpPath = '../tmp/';if(!file_exists($tmpPath) || !is_writable($tmpPath)){return array('errorDescription'=>'NO_TMP_FOLDER','file'=>__FILE__,'line'=>__LINE__);}
		if(!isset($params['file_parts'])){return array('errorDescription'=>'FILE_PARTS_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		if(!isset($params['file_name'])){return array('errorDescription'=>'FILE_NAME_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		if(!isset($params['file_size'])){return array('errorDescription'=>'FILE_SIZE_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		$file_parts = preg_replace('/[^0-9]*/','',$params['file_parts']);if(!$file_parts){return array('errorDescription'=>'FILE_PARTS_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		//FIXME:
		$file_name = $params['file_name'];if(!$file_name){return array('errorDescription'=>'FILE_NAME_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		$file_size = preg_replace('/[^0-9]*/','',$params['file_parts']);if(!$file_parts){return array('errorDescription'=>'FILE_SIZE_ERROR','file'=>__FILE__,'line'=>__LINE__);}

		$tmpPath = $tmpPath.md5($file_name).'/';if(!file_exists($tmpPath)){$oldmask = umask(0);$r = @mkdir($tmpPath,0777,1);umask($oldmask);if(!$r){return array('errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}}
		$sourceFile = $tmpPath.'info.json';

		if(file_exists($sourceFile)){$data = json_decode(file_get_contents($sourceFile),1);}
		else{
			$data = array('file.name'=>$file_name,'file.parts'=>$file_parts,'file.size'=>$file_size,'file.fragments'=>array(),'file.info'=>isset($params['info']) ? $params['info'] : array());
			$oldmask = umask(0);$r = @file_put_contents($sourceFile,json_encode($data),LOCK_EX);umask($oldmask);
			if(!$r){return array('errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}	
		}
//FIXME: comprobar que la información se corresponde con el fichero de datos, como numero de partes y tamaño de las partes
//(tamaño partes igual a tamaño/partes)

		$fragment_sum = preg_replace('/[^a-zA-Z0-9]*/','',$params['fragment_sum']);if(strlen($fragment_sum) != 32){return array('errorDescription'=>'FRAGMENT_MD5_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		$fragment_num = preg_replace('/[^0-9]*/','',$params['fragment_num']);if(!$fragment_num){return array('errorDescription'=>'FRAGMENT_NUM_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		$fragment_src = str_replace(' ','+',$params['fragment_src']);
		/* Es posible que la llamada sea solo de cortesía, es decir, que la imagen ya esté subida pero se haya 
		 * cortado el procesamiento de la imagen */
		if(file_exists($tmpPath.$fragment_num)){
//FIXME: imaginemos que solo falta unificar
			//FIXME: devolver los fragmentos que ya existen
			return array('errorDescription'=>'FRAGMENT_ALREADY_EXISTS','file'=>__FILE__,'line'=>__LINE__);
		}

		/* Comprobaciones de la string que debemos almacenar */
		if(md5($fragment_src) != $fragment_sum){return array('errorDescription'=>'FRAGMENT_CORRUPT','file'=>__FILE__,'line'=>__LINE__);}
		/* Si lleva una cabecera debemos eliminarla */
		if(substr($fragment_src,0,5) == 'data:'){$comma = strpos($fragment_src,',');$fragment_src = substr($fragment_src,$comma+1);}
		$oldmask = umask(0);$fp = fopen($tmpPath.$fragment_num,'w');fwrite($fp,$fragment_src);fclose($fp);umask($oldmask);

		/* Escribimos la información en el fichero de datos */
		$data['file.fragments'][$fragment_num] = $fragment_sum;
		$oldmask = umask(0);$r = @file_put_contents($sourceFile,json_encode($data),LOCK_EX);umask($oldmask);

		/* Comprobamos si debemos unificar */
		if($fragment_num == $file_parts){return uploadchain_unify($file_name);}

		return array('errorCode'=>'0');
	}
	function uploadchain_unify($file_name){
		$tmpPath = '../tmp/';if(!file_exists($tmpPath) || !is_writable($tmpPath)){return array('errorDescription'=>'NO_TMP_FOLDER','file'=>__FILE__,'line'=>__LINE__);}
		$tmpPath = $tmpPath.md5($file_name).'/';if(!file_exists($tmpPath)){$oldmask = umask(0);$r = @mkdir($tmpPath,0777,1);umask($oldmask);if(!$r){return array('errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}}
		$sourceFile = $tmpPath.'info.json';if(file_exists($sourceFile)){$data = json_decode(file_get_contents($sourceFile),1);}
		$FILE_binary = $tmpPath.'FILE_binary';

		$oldmask = umask(0);
		$files = array();if($handle = opendir($tmpPath)){while(false !== ($file = readdir($handle))){if(is_numeric($file)){$files[] = $file;}}closedir($handle);}
		sort($files);$fp = fopen($FILE_binary,'w');foreach($files as $file){fwrite($fp,base64_decode(file_get_contents($tmpPath.$file)));unlink($tmpPath.$file);}fclose($fp);
		umask($oldmask);

		$GLOBALS['api']['uploadchain']['tmp.files'][] = $tmpPath;
		$r = register_shutdown_function('uploadchain_callbacks_onShutdown');

		return array('filePath'=>$FILE_binary,'fileName'=>$data['file.name'],'fileSize'=>filesize($FILE_binary));
	}
	function uploadchain_helper_removeDir($path,$avoidCheck=false){
		if(!$avoidCheck){$path = preg_replace('/\/$/','/',$path);if(!file_exists($path) || !is_dir($path)){return;}}
		if($handle = opendir($path)){while(false !== ($file = readdir($handle))){
			if(in_array($file,array('.','..'))){continue;}
			if(is_dir($path.$file)){call_user_func_array(__FUNCTION__,array($path.$file.'/',true));continue;}
			unlink($path.$file);
		}closedir($handle);}
		rmdir($path);
		return true;
	}
	function uploadchain_callbacks_onShutdown(){
		chdir(dirname(__FILE__));
		foreach($GLOBALS['api']['uploadchain']['tmp.files'] as $file){
			if(file_exists($file)){uploadchain_helper_removeDir($file);}
		}
	}
?>
