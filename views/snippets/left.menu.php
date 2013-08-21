<div class="block mainMenu">
	<h3>Bienvenido a featherCMS</h3>
	<p>Una de las maneras más rápidas de bloggear en grupo</p>
	<ul>
		<li><a href="{%baseURL%}"><i class="icon-compass"></i> Principal</a></li>
		<li><a href="{%baseURL%}article/edit"><i class="icon-edit-sign"></i> Escribir nuevo Artículo</a></li>
		<li><a href="{%baseURL%}article/list"><i class="icon-file-text"></i> Últimos artículos</a></li>
		<li><a href="{%baseURL%}article/list/draft"><i class="icon-file-alt"></i> Últimos borradores</a></li>
		{%left.menu.entries.users%}
		{%left.menu.entries.config%}
	</ul>
</div>
<div class="block">
	<h3>Búscador de artículos</h3>
	<p>Busqueda de artículos</p>
	<form method="post" action="{%baseURL%}search">
		<input name="subcommand" value="search" type="hidden"/>
		<input name="criteria" value="" type="text"/>
		<button type="submit">Buscar</button>
	</form>
</div>
