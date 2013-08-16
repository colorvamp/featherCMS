<div class="userNode">
	<div class="userAvatar"><img src="{%baseURL%}r/images/avatars/default/av64.jpg"/></div>
	<div class="userBody">
		<div><a href="">{%userName%}</a></div>
		<div class="btn-group">
			<div class="btn mini">@profile</div>
			<div class="btn mini dropdown-toggle"><i class="icon-edit-sign"></i> Permisos <div class="dropdown-menu padded">
				<h4><i class="icon-edit-sign"></i> Permisos de &laquo;{%userName%}&raquo;</h4>
				<p>Marca los permisos que desees que este usuario posea.</p>
				<form method="post">
					<input name="subcommand" value="userModesSave" type="hidden"/>
					<input name="userMail" value="{%userMail%}" type="hidden"/>
					{%userModes.html%}
					<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
				</form>
			</div></div>
		</div>
	</div>
</div>
