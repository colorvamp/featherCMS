<?php
	function mailing_ec($s,$expected_response)
	{
	    $server_response = '';
	    while (substr($server_response, 3, 1) != ' ')
	    {
		if (!($server_response = fgets($s, 256)))
		    echo 'Couldn\'t get mail server response codes. Please contact the forum administrator.', __FILE__, __LINE__;
	    }
	    if (!(substr($server_response, 0, 3) == $expected_response))
		echo 'Unable to send e-mail. Please contact the forum administrator with the following error message reported by the SMTP server: "'.$server_response.'"', __FILE__, __LINE__;
	}
 
 
	function mailing_send($params = array(),$to,$subject,$body,$headers = ''){
		$recipients = array();
		if(is_array($to)){$recipients = $to;}
		if(is_string($to)){do{
			if(strpos($to,',')){$recipients = explode(',', $to);
//FIXME: dar soporte a direcciones del tipo Marcos <sombra2eternity@gmail.com>, el nombre será útil en subject
break;}
			$recipients = array($to);
		}while(false);}
		if(!$recipients){return array('errorDescription'=>'INVALID_RECEIVERS','file'=>__FILE__,'line'=>__LINE__);}
	   
		$user = $params['mail.username'];
		$pass = $params['mail.password'];
		$smtp_host = $params['mail.host'];
		$smtp_port = $params['mail.port'];
		$CR = "\r\n";

		$s = fsockopen($smtp_host,$smtp_port,$errno,$error,15);
		if(!$s){return array('errorDescription'=>$error,'file'=>__FILE__,'line'=>__LINE__);}
		mailing_ec($s,'220');
		fwrite($s,'EHLO '.$smtp_host.$CR);
		mailing_ec($s,'250');
		fwrite($s,'AUTH LOGIN'.$CR);
		mailing_ec($s,'334');
		fwrite($s,base64_encode($user).$CR);
		mailing_ec($s,'334');
		fwrite($s,base64_encode($pass).$CR);
		mailing_ec($s,'235');
		fwrite($s,'MAIL FROM: <'.$user.'>'.$CR);
		mailing_ec($s, '250');
		/* Añadimos los destinatarios */
		foreach($recipients as $email){
			fwrite($s,'RCPT TO: <'.$email.'>'.$CR);
			mailing_ec($s,'250');
		}

		fwrite($s,'DATA'.$CR);
		mailing_ec($s,'354');

		$mime_boundary = md5(time());
		$headers = 'From: Mail <'.$user.'>' . $CR;
		$headers .= 'MIME-Version: 1.0'. $CR;
		$headers .= 'Content-Type: multipart/mixed; boundary="'.$mime_boundary.'"'.$CR;
		$message = "--".$mime_boundary. "\n";
		$message .= "Content-Type: text/html; charset=utf-8". $CR;
		$message .= "Content-Transfer-Encoding: 7bit". $CR.$CR;
		$message .= $body.$CR.$CR;

		fwrite($s,'Subject: '.$subject.$CR.'To: <'.implode('>, <',$recipients).'>'.$CR.$headers.$CR.$CR.$message.$CR);
		fwrite($s,'.'.$CR);
		mailing_ec($s,'250');
		fwrite($s,'QUIT'.$CR);
		fclose($s);
	 	return true;
	}
?>
