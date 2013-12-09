	<div class="btn-group">
		<div class="btn dropdown-toggle"><i class="icon-plus"></i> Crear nuevo usuario <div class="dropdown-menu padded">
			<h4>Crear nuevo usuario</h4>
			<p>El nuevo usuario se incorporará al sistema automáticamente, no será necesaria la activación del mismo.</p>
			<form method="post">
				<input name="subcommand" value="userSave" type="hidden"/>
				<table class="form"><tbody>
					<tr><td>Nombre</td><td><div class="inputText"><input type="text" name="userName"></div></td></tr>
					<tr><td>Mail</td><td><div class="inputText"><input type="text" name="userMail"/></div></td></tr>
					<tr><td>Nick</td><td><div class="inputText"><input type="text" name="userNick"/></div></td></tr>
					<tr><td>Password</td><td><div class="inputText"><input type="text" name="userPass"/></div></td></tr>
				</tbody></table>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button type="submit" class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
			</form>
		</div></div>
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
		<div class="btn dropdown-toggle">Filtrar <div class="dropdown-menu padded">
			<h4>Filtro de usuarios</h4>
			<p>Introduce un término de búsqueda, puedes utilizar un identificador de usuario, el correo electrónico o fragmentos del nombre o apellidos. Al marcar el checkbox de la lista convertirás al usuario en Redactor automáticamente.</p>
			<form method="post">
				<input name="subcommand" value="user.filter.apply" type="hidden"/>
				<div class="table">
					<div><input type="checkbox" name="modes[]" value="admin"/> Administrador</div>
					<div><input type="checkbox" name="modes[]" value="writer"/> Redactor</div>
					<div><input type="checkbox" name="modes[]" value="publisher"/> Editor</div>
					<div><input type="checkbox" name="modes[]" value="profile"/> Perfil</div>
				</div>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button type="submit" class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
			</form>
		</div></div>
	</div>
	<div class="list">
		{%list.users%}
	</div>
