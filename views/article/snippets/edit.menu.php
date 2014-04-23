<ul>
	<li class="dropdown-toggle"><i class="icon-chevron-down"></i> Artículo
		<div class="dropdown-menu padded">
			<div class="transitionable" data-state="main"><form method="post">
				<input type="hidden" name="subcommand" value="ajax.article.save.props"/>
				<textarea class="hidden" name="articleText">{%articleOB_articleText%}</textarea>
				<h4>Propiedades del artículo</h4>
				<p>Estas son las propiedades del artículo que estás editando. Ninguna de estas características son imprescindibles para el funcionamiento del blog ya que no todos los blogs necesitan esta información.</p>
				<div>Título de la entrada</div>
				<div class="inputText"><input name="articleTitle" value="{%articleOB_articleTitle.value%}"></div>
				<div>Tags</div>
				<div class="inputText"><input name="articleTags" value="{%articleOB_articleTags%}"></div>
				<div>Enlace Persistente</div>
				<div class="inputText"><input name="articleHardLink" value="{%articleOB_articleHardLink%}"></div>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><div class="btn ajax"><i class="icon-ok-sign"></i> Aceptar</div></div>
			</form></div>
			<div class="transitionable hidden" data-state="working">
				<p>Salvando</p>
			</div>
			<div class="transitionable hidden" data-state="info">

			</div>
		</div>
	</li>
	<li class="dropdown-toggle" data-dropdown-onBeforeOpen="_editor.controls.attachment_open"><i class="icon-file"></i> Archivos
		<div class="dropdown-menu dropdown-uploader padded">
			<div class="transitionable">
				<textarea class="hidden">{%articleOB_articleAttachmentsJSON%}</textarea>
				<h4>Archivos del artículo</h4>
				<p>Listado de <strong>Archivos</strong> que contiene el artículo en este momento. Puede arrastrar los nombres de los archivos al editor del artículo para insertarlos en el mismo.</p>
				<table><thead><tr><td>Título</td></tr></thead><tbody></tbody></table>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><div class="btn"><i class="icon-ok-sign"></i> Aceptar</div></div>
			<div>
		</div>
	</li>
	<li class="dropdown-toggle" data-dropdown-onBeforeOpen="_editor.controls.attachment_open"><i class="icon-align-justify"></i> Párrafo
		<ul class="dropdown-menu item-list">
			<li onclick="_editor.controls.paragraph.select(event);">Seleccionar todo</li>
			<li>Eliminar formato</li>
		</ul>
	</li>
</ul>
