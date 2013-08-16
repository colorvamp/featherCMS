<div class='featherSidebar'>
	{%left.menu%}
</div><div class='featherContent'>
	<div class="btn-group">
		<div class="btn dropdown-toggle">Agregar redactor <div class="dropdown-menu padded">
			<h4>Búsqueda de usuarios</h4>
			<p>Introduce un término de búsqueda, puedes utilizar un identificador de usuario, el correo electrónico o fragmentos del nombre o apellidos. Al marcar el checkbox de la lista convertirás al usuario en Redactor automáticamente.</p>
			<div class="input-search"><div class="input"><input name="userAlias"></div><div class="btn" onclick="c.addWriter_search(event);"><i class="icon-search"></i></div></div>
			<form method="post">
				<input name="subcommand" value="userAddWriters" type="hidden"/>
				<ul id="addWriter_search_result"></ul>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><div class="btn"><i class="icon-ok-sign"></i> Aceptar</div></div>
			</form>
		</div></div>
	</div>
	<div class="list">
		{%list.users%}
	</div>
</div>
