<section>
	<h3>General</h3>
	<form method="post">
		<table><tbody>
			<tr><td>test</td><td><div class="inputText"><input type="text"/></div></td></tr>
		</tbody></table>
	</form>
</section>
<section>
	<h3><i class="icon-mail"></i> Correo</h3>
	<p>Cuenta de correo electrónico que permitirá a la plataforma comunicarse con los usuarios.</p>
	<form method="post">
		<input type="hidden" name="subcommand" value="mail.save"/>
		<table class="form"><tbody>
			<tr><td>Nombre de la cuenta</td><td><input type="text" name="emailName" value="{%data.mail_emailName%}"/></td></tr>
			<tr><td>Contraseña</td><td><input type="text" name="emailPass" value="{%data.mail_emailPass%}"/></td></tr>
			<tr><td>Host</td><td><input type="text" name="emailHost" value="{%data.mail_emailHost%}"/></td></tr>
			<tr><td>Puerto</td><td><input type="text" name="emailPort" value="{%data.mail_emailPort%}"/></td></tr>
		</tbody></table>
		<div class="btn-group"><button class="btn">Salvar</button></div>
	</form>
</section>
<section>
	<h3><i class="icon-picture"></i> Imágenes de artículos</h3>
	<p>Tamaños de las miniaturas generadas para los artículos</p>
	<form method="post">
		{%html.articleImageSizes%}
	</form>
</section>
<section>
	<h3>Cron</h3>
	<p>Programador de tareas</p>
	<form method="post">
		{%html.articleImageSizes%}
	</form>
</section>
