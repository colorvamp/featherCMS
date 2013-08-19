			<div class="btn mini">@profile</div>
			<div class="btn mini dropdown-toggle"><i class="icon-edit-sign"></i> Permisos <div class="dropdown-menu padded">
				<h4><i class="icon-edit-sign"></i> Permisos de &laquo;{%userName%}&raquo;</h4>
				<p>Marca los permisos que desees que este usuario posea.</p>
				<form method="post">
					<input name="subcommand" value="userModesSave" type="hidden"/>
					<input name="userMail" value="{%userMail%}" type="hidden"/>
					{%html.userModes%}
					<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
				</form>
			</div></div>
			<div class="btn mini dropdown-toggle"><i class="icon-edit-sign"></i> Editar usuario<div class="dropdown-menu padded">
				<h4><i class="icon-edit-sign"></i> Editar usuario</h4>
				<p>Edita la información de este usuario.</p>
				<form method="post">
					<input name="subcommand" value="userInfoSave" type="hidden"/>
					<input name="userMail" value="{%userMail%}" type="hidden"/>
					<table class="form"><tbody>
						<tr><td>Nombre</td><td><div class="inputText"><input type="text" name="userName" value="{%userName%}"/></div></td></tr>
						<tr><td>Nick</td><td><div class="inputText"><input type="text" name="userNick" value="{%userNick%}"/></div></td></tr>
					</tbody></table>
					<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
				</form>
			</div></div>
