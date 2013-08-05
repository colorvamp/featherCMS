<?php
	function die_default_image(){
	    //43byte 1x1 transparent pixel gif
	    header('content-type: image/gif');
	    echo base64_decode('R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
	}

	function image_mimeDecider($mime,$path){
		if(!function_exists('imagecreatefromjpeg')){echo 'No está instalada la libreria php5_gd';exit;}
		switch($mime){
	    		case 'image/gif':if(!($image = @imagecreatefromgif($path))){return false;}; break;
			case 'image/jpeg':if(!($image = @imagecreatefromjpeg($path))){return false;}; break;
			case 'image/png':if(!($image = @imagecreatefrompng($path))){return false;}; break;
			default: return false;
		}
		return $image;
	}

	function image_getResource($path){
		$imgProp = getimagesize($path);
		if($imgProp === false){return false;}
		switch($imgProp['mime']){
	    		case 'image/gif':if(!($im = @imagecreatefromgif($path))){return false;};break;
			case 'image/jpeg':if(!($im = @imagecreatefromjpeg($path))){return false;};break;
			case 'image/png':if(!($im = @imagecreatefrompng($path))){return false;};break;
			default:return false;
		}
		return $im;
	}

	function image_resize($imagePath,$thumbname,$maxWidth,$maxHeight,$adjust='max'){
		$imgProp = getimagesize($imagePath);
		$image = image_mimeDecider($imgProp['mime'],$imagePath);
		$image = imageResource_resize($image,$maxWidth,$maxHeight,$adjust);
		imagejpeg($image,dirname($imagePath).'/'.$thumbname,90);
		return true;
	}

	function image_crop($imagePath,$thumbname,$width,$height){
		$imgProp = getimagesize($imagePath);
		$image = image_mimeDecider($imgProp['mime'],$imagePath);
		$image = imageResource_crop($image,$width,$height);
		imagejpeg($image,dirname($imagePath).'/'.$thumbname,90);
		return true;
	}


	/* Esta funcion escala la imagen que le indicamos a los valores introducidos
	 * sin repetar la proporción 
	 * ===NO ESTA PROBADA===
	 */
	function image_scale($imagePath,$thumbname,$width,$height){
		$imgArr = getimagesize($imagePath);
		switch($imgArr["mime"]){
	    		case "image/gif": $image = imagecreatefromgif($imagePath); break;
			case "image/jpeg": $image = imagecreatefromjpeg($imagePath); break;
			case "image/png": if(!($image = @imagecreatefrompng($imagePath))){return false;}; break;
			default: return;
		}

		list($imgWidth,$imgHeight) = $imgArr;

		$tempimg = imagecreatetruecolor($width,$height);
		imagecopyresampled($tempimg,$image,0,0,0,0,$width,$height,$imgWidth,$imgHeight);
		imagejpeg($tempimg,dirname($imagePath)."/".$thumbname,90);
		return;
	}

	function imageResource_resize($res,$maxWidth = 0,$maxHeight = 0,$adjust='max'){
		$imgWidth = imagesx($res);
		$imgHeight = imagesy($res);
		if($imgWidth === false || $imgHeight === false){return false;}

		$imgRatio = $imgWidth/$imgHeight;
		if($maxWidth != 0 && $maxHeight != 0){$maxRatio = $maxWidth/$maxHeight;}

		switch(true){
			case ($maxWidth == 0):$maxWidth = $imgWidth * ($maxHeight/$imgHeight);break;
			case ($maxHeight == 0):$maxHeight = $imgHeight * ($maxWidth/$imgWidth);break;
			case ($adjust == 'max'):if($imgRatio>$maxRatio){$maxHeight = $imgHeight * ($maxWidth/$imgWidth);}else{$maxWidth = $imgWidth * ($maxHeight/$imgHeight);}break;
			case ($adjust == 'min'):if($imgRatio>$maxRatio){$maxWidth = $imgWidth * ($maxHeight/$imgHeight);}else{$maxHeight = $imgHeight * ($maxWidth/$imgWidth);}break;
			default:return false;
		}

		$new = imagecreatetruecolor($maxWidth,$maxHeight);
		imagecopyresampled($new,$res,0,0,0,0,$maxWidth,$maxHeight,$imgWidth,$imgHeight);
		return $new;
	}

	function imageResource_scale($res,$w,$h){
		$imgW = imagesx($res);$imgH = imagesy($res);
		$new = imagecreatetruecolor($w,$h);
		imagecopyresampled($new,$res,0,0,0,0,$w,$h,$imgW,$imgH);
		return $new;
	}

	function imageResource_crop($res,$width,$height){
		$imgWidth = imagesx($res);
		$imgHeight = imagesy($res);

		$xini=floor(($imgWidth-$width)/2);
		$yini=floor(($imgHeight-$height)/2);

		$image = imagecreatetruecolor($width,$height);
		imagecopy($image,$res,0,0,$xini,$yini,$width,$height);
		return $image;
	}

	function imageResource_processSize($im,$size){
		if(!is_numeric($size[0])){unset($sizes[$k]);continue;}
		if(strpos($size,'x') !== false){list($w,$h) = explode('x',$size);$im = imageResource_resize($im,$w,$h,'min');if($w != 0 && $h != 0){$im = imageResource_crop($im,$w,$h);}return $im;}
		if(strpos($size,'m') !== false){list($w,$h) = explode('m',$size);$im = imageResource_resize($im,$w,$h,'min');if($w != 0 && $h != 0){$im = imageResource_crop($im,$w,$h);}return $im;}
		//$r = gallery_helper_square($res,$destPath,$size);
	}

	function imageResource_save($im,$path){
		//FIXME: hacerlo con strrpos
		$nImagePath = preg_replace('/(jpg$|jpeg$|png$|gif$)/i','',$path);
		$ext = substr($path,strlen($nImagePath));
		switch($ext){
			case 'jpg':case 'JPG':imagejpeg($im,$nImagePath.'jpg',90);break;
			case 'jpeg':case 'JPEG':imagejpeg($im,$nImagePath.'jpeg',90);break;
			case 'png':case 'PNG':imagepng($im,$nImagePath.'png',9);break;
		}
		return $im;
	}

	function image_convert($path,$to){
		$imgArr = getimagesize($path);
		$image = image_mimeDecider($imgArr['mime'],$path);
		list($imgWidth,$imgHeight) = $imgArr;

		$tempimg = imagecreatetruecolor($imgWidth,$imgHeight);
		imagecopyresampled($tempimg,$image,0,0,0,0,$imgWidth,$imgHeight,$imgWidth,$imgHeight);
		$nImagePath = preg_replace('/\.(jpg$|jpeg$|png$|gif$)/i','',$path);
		switch($to){
			case 'jpg':case 'jpeg':case 'JPG':case 'JPEG':imagejpeg($tempimg,$nImagePath.'.jpeg',90);break;
			case 'png':case 'PNG':imagepng($tempimg,$nImagePath.'.png',9);break;
		}
		return true;
	}

	function image_square($res,$dPath,$size){if(is_dir($dPath)){return false;}$res = imageResource_resize($res,$size,$size,'min');$res = imageResource_crop($res,$size,$size);$r = imageResource_save($res,$dPath);$r = imagedestroy($res);return true;}
	function image_thumb($res,$dPath,$size){if(is_dir($dPath)){return false;}list($w,$h) = explode('x',$size);$res = imageResource_resize($res,$w,$h,'min');if($w != 0 && $h != 0){$res = imageResource_crop($res,$w,$h);}$r = imageResource_save($res,$dPath);$r = imagedestroy($res);return true;}
	function image_thumb_p($res,$dPath,$size){if(is_dir($dPath)){return false;}list($w,$h) = explode('p',$size);$res = imageResource_resize($res,$w,$h,'max');$r = imageResource_save($res,$dPath);$r = imagedestroy($res);return true;}
	function image_tooLarge($iPath,$imgProp){
		$a = pathinfo($iPath);
		$resPath = $a['dirname'].'/'.$a['basename'].'.jpeg';
		if(is_dir($resPath)){return false;}
		if(!file_exists($resPath)){$r = shell_exec('/usr/bin/convert '.$iPath.' -resize 1600x1600 '.$resPath);}
		if(!isset($a['extension'])){rename($resPath,$iPath);$resPath = $iPath;}
		$res = image_mimeDecider('image/jpeg',$resPath);
		return $res;
	}
?>
