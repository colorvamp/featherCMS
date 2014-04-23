<tr><td>{%id%}</td><td>{%spamString%}</td><td>{%spamPool%}</td><td>
	<div class="btn-group">
		<div class="btn mini dropdown-toggle"> <i class="icon-cog"></i> Opciones
			<div class="dropdown-menu padded">
				<section>
					<h4>Buscar «Comentarios» con este texto</h4>
					<p>Generará un listado fistrado con los comentarios que contengan la siguiente cadena.</p>
					<form method="post">
						<input name="subcommand" value="spam.string.search" type="hidden"/>
						<input name="id" value="{%id%}" type="hidden"/>
						<div class="btn-group right"><button class="btn mini"><i class="icon-ok-sign"></i> Buscar</button></div>
					</form>
				</section>
				<section>
					<h4>Eliminar esta regla</h4>
					<p>Se eliminará la cadena del filtro de spam.</p>
					<form method="post">
						<input name="subcommand" value="spam.string.remove" type="hidden"/>
						<div class="btn-group right"><button class="btn mini"><i class="icon-remove-sign"></i> Eliminar</button></div>
					</form>
				</section>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cerrar</div></div>
			</div>
		</div>
	</div>
</td></tr>
