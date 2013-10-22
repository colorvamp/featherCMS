<?php
	function uploadchain_fragment($params = array(),$base64string_sum,$base64string_len,$fragment_string,$fragmentNum,$fragment_sum){
		$tmpPath = '../tmp/';$tmpPath = $tmpPath.$base64string_sum.'/';if(!file_exists($tmpPath)){$oldmask = umask(0);@mkdir($tmpPath,0777,1);umask($oldmask);}
		if(!file_exists($tmpPath)){return array('errorDescription'=>'NO_TMP_FOLDER','file'=>__FILE__,'line'=>__LINE__);}
		$sourceFile = $tmpPath.'info.json';

		if(!file_exists($sourceFile)){$oldmask = umask(0);$r = @file_put_contents($sourceFile,json_encode(array('params'=>$params)),LOCK_EX);umask($oldmask);if(!$r){return array('errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);}}
		$base64string_sum = preg_replace('/[^a-zA-Z0-9]*/','',$base64string_sum);if(strlen($base64string_sum) != 32){return array('errorDescription'=>'MD5_SUM_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		/* Es posible que la llamada sea solo de cortesía, es decir, que la imagen ya esté subida pero se haya 
		 * cortado el procesamiento de la imagen */
		$totalSize = 0;$files = array();if($handle = opendir($tmpPath)){while(false !== ($file = readdir($handle))){if($file[0]=='0'){$files[] = $file;$totalSize += filesize($tmpPath.$file);}}closedir($handle);}
		if($totalSize == $base64string_len){return uploadchain_unify($base64string_sum);}
		$fragmentNum = preg_replace('/[^0-9]*/','',$fragmentNum);
		$fragmentName = str_pad($fragmentNum,10,'0',STR_PAD_LEFT);
		if(file_exists($tmpPath.$fragmentName)){
//FIXME: imaginemos que solo falta unificar
			//FIXME: devolver los fragmentos que ya existen
			return array('errorDescription'=>'FRAGMENT_ALREADY_EXISTS','totalSize'=>$totalSize,'file'=>__FILE__,'line'=>__LINE__);
		}

		/* Comprobaciones de la string que debemos almacenar */
		$fragment_string = str_replace(' ','+',$fragment_string);if(md5($fragment_string) != $fragment_sum){return array('errorDescription'=>'FRAGMENT_CORRUPT','file'=>__FILE__,'line'=>__LINE__);}
		/* Si lleva una cabecera de imágen debemos eliminarla */
		if(substr($fragment_string,0,11) == 'data:image/'){$comma = strpos($fragment_string,',');$imgType = substr($fragment_string,11,$comma-7-11);$fragment_string = substr($fragment_string,$comma+1);}
		$oldmask = umask(0);$fp = fopen($tmpPath.$fragmentName,'w');fwrite($fp,$fragment_string);fclose($fp);umask($oldmask);

		/* Comprobamos si debemos unificar, tenemos en totalsize el valor total de los ficharos antes
		 * de salvar este último fragmento, solo necesitamos sumarle el tamaño */
		$totalSize += filesize($tmpPath.$fragmentName);
		if($totalSize == $base64string_len){return uploadchain_unify($base64string_sum);}

		return array('errorCode'=>'0','totalSize'=>$totalSize);
	}
	function uploadchain_unify($base64string_sum){
		$tmpPath = '../tmp/'.$base64string_sum.'/';if(!file_exists($tmpPath)){return array('errorDescription'=>'NO_TMP_FOLDER','file'=>__FILE__,'line'=>__LINE__);}

		$files = array();if($handle = opendir($tmpPath)){while(false !== ($file = readdir($handle))){if($file[0]=='0'){$files[] = $file;}}closedir($handle);}
		sort($files);$fp = fopen($tmpPath.'IMAGE_base64','w');foreach($files as $file){fwrite($fp,file_get_contents($tmpPath.$file));unlink($tmpPath.$file);}fclose($fp);
		if(md5_file($tmpPath.'IMAGE_base64') != $base64string_sum){return array('errorDescription'=>'IMAGE_CORRUPT'.md5_file($tmpPath.'IMAGE_base64'),'file'=>__FILE__,'line'=>__LINE__);}
		$totalSize = filesize($tmpPath.'IMAGE_base64');

		$chunkSize = 1024;
		$oldmask = umask(0);
		$src = fopen($tmpPath.'IMAGE_base64','rb');$dst = fopen($tmpPath.'IMAGE_binary','wb');
		while(!feof($src)){fwrite($dst,base64_decode(fread($src,$chunkSize)));}
		fclose($dst);fclose($src);
		unlink($tmpPath.'IMAGE_base64');
		umask($oldmask);

		$imgProp = getimagesize($tmpPath.'IMAGE_binary');
		if($imgProp === false){return array('errorDescription'=>'IMAGE_CORRUPT','file'=>__FILE__,'line'=>__LINE__);}
		$ext = substr($imgProp['mime'],6);
		$tmpName = $tmpPath.'IMAGE_binary.'.$ext;
		rename($tmpPath.'IMAGE_binary',$tmpName);

		return array('imagePath'=>$tmpName,'imageTotalSize'=>$totalSize);
	}
?>
