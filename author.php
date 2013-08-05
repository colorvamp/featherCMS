<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Establece un nombre de autor para featherCMS</title>
	<link rel="StyleSheet" href="css/author.css" type="text/css" media="screen"/>
</head>
<body>
	<div>
		<p>Es necesario que escojas un nombre que te defina como autor de los artículos que vas a escribir.</p> 
		<p>Este nombre debe ser diferente al del resto de redactores del blog y <b>no puede contener caracteres
			especiales,tildes o espacios</b>, aunque puedes usar el guión bajo "_" como separador entre palabras.</p>

		<?php if(isset($GLOBALS['authorAliasError']) && $GLOBALS['authorAliasError'] != false){echo '<p><b>',$GLOBALS['authorAliasError'],'</b></p>';} ?>

		<form method='POST' action=''>
			<div><input type='text' name='authorAlias'/></div>
			<div><input type='submit' value='guardar cambios'/></div>
		</form>
	</div>
</body>
</html>
