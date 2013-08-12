<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>{%BLOG_TITLE%} - Panel</title>
	<script type="text/javascript" src="{%baseURL%}js/coreJS.402.js"></script>
	{%BLOG_JS%}
	<link rel="StyleSheet" href="{%baseURL%}css/login.css" type="text/css" media="screen"/>
	{%BLOG_CSS%}
</head>
<body onload="if(window.init){window.init();}">
	<div class="loginForm"><div class="userGrid">{%usersGrid%}</div></div>
	<div class="hidden">
		<div id="loginBox" class="whiteBox middleInd">
			<h3>Password</h3>
			<div><input type="password" name="userPass"/></div>
			<button onclick="userLogin(this);">login</button>
		</div>
	</div>
</body>
</html>
