	<div class="btn-group" id="editorConstrols">
		<div class="btn dropdown-toggle">Añadir
			<div class="dropdown-menu padded">
				<h4>Añadir término de filtro</h4>
				<p>Los comentarios que lleguen a las plataformas serán filtrados y/o descartados dependiendo de las coincidencias.</p>
				<form method="post">
					<input name="subcommand" value="spam.string.add" type="hidden"/>
					<div>Texto a detectar</div>
					<div class="inputText"><textarea name="spamString"></textarea></div>
					<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
				</form>
			</div>
		</div>
	</div>
	{%list.spamStrings%}
