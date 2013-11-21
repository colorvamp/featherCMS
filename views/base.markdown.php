<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>{%BLOG_TITLE%} - Panel</title>
	<script type="text/javascript" src="{%baseURL%}js/coreJS.402.js"></script>
	<script type="text/javascript" src="{%baseURL%}js/feather.js"></script>
	{%BLOG_JS%}
	<link rel="StyleSheet" href="{%baseURL%}css/cms.css" type="text/css" media="screen"/>
	<link rel="StyleSheet" href="{%baseURL%}css/font-awesome.min.css" type="text/css" media="screen"/>
	{%BLOG_CSS%}
	<script>VAR_baseURL = '{%baseURL%}';</script>
</head>
<body onload="if(window.init){window.init();}">
	<div class="header">
		{%top.menu%}
		<a class="user" href="{%baseURL%}logout"><i class="icon-off"></i> Logout</a>
	</div>
	<div class="body">
		{%MAIN%}
	</div>
	<div class="footer">
		<p>Copyright © 2010 sombra2eternity for colorvamp.com</p>
	</div>
</body>
</html>
