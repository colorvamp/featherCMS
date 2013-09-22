	<div class="btn-group" id="editorConstrols">
		<div class="btn dropdown-toggle">Artículo
			<div class="dropdown-menu padded">
				<h4>Propiedades del artículo</h4>
				<p>Estas son las propiedades del artículo que estás editando. Ninguna de estas características son imprescindibles para el funcionamiento del blog ya que no todos los blogs necesitan esta información.</p>
				<form method="post">
					<input name="subcommand" value="articleSaveProps" type="hidden">
					<div>Título de la entrada</div>
					<div class="inputText"><input name="articleTitle" value="{%articleOB_articleTitle%}"></div>
					<div>Tags</div>
					<div class="inputText"><input name="articleTags" value="{%articleOB_articleTags%}"></div>
					<div>Enlace Persistente</div>
					<div class="inputText"><input name="articleHardLink" value="{%articleOB_articleHardLink%}"></div>
					<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><div class="btn" onclick="_form.submitAsAjax(event,this)"><i class="icon-ok-sign"></i> Aceptar</div></div>
				</form>
			</div>
		</div>
		<div class="btn dropdown-toggle" data-dropdown-onBeforeOpen="_editor.controls.save_open">Guardar
			<div class="dropdown-menu padded">
				Salvando ...
			</div>
		</div>
		<div class="btn" onclick="_editor.controls.header_accept(event,this);">h4</div>
		<div class="btn" onclick="_editor.controls.bold_accept(event,this);"><b>B</b></div>
		<div class="btn" onclick="_editor.controls.italic_accept(event,this);"><i>I</i></div>
		<div class="btn" onclick="_editor.controls.format_accept(event,this);">Eliminar formato</div>
		<div class="btn dropdown-toggle" onclick="_editor.controls.link_open(event,this);">Enlace
			<div class="dropdown-menu padded">
				<textarea class="hidden">{%articleOB_articleLinksJSON%}</textarea>
				<h4>Crear enlace</h4>
				<p>Insertar un enlace sobre el texto seleccionado.</p>
				<div><input type="text" name="linkHref"/></div>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><div class="btn" onclick="_editor.controls.link_accept(event);"><i class="icon-ok-sign"></i> Aceptar</div></div>
			</div>
		</div>
		{%edit.paragraph%}
		<div class="btn dropdown-toggle" onclick="_editor.controls.image_open(event,this);">Imágenes
			<div class="dropdown-menu padded" ondragover="_editor.controls.image_dragover(event);" ondrop="_editor.controls.image_drop(event,this);">
				<textarea class="hidden">{%articleOB_articleImagesJSON%}</textarea>
				<h4>Imágenes del artículo</h4>
				<p>Listado de imágenes que contiene el artículo en este momento. Puede arrastrar los nombres de las imágenes al editor del artículo para insertarlas en el mismo.</p>
				<table><thead><tr><td>Título</td></tr></thead><tbody></tbody></table>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><div class="btn"><i class="icon-ok-sign"></i> Aceptar</div></div>
			</div>
		</div>
	</div>
	<div class="writer articleNode">
		<div id="canvasControls" class="canvasControls"></div>
		<article id="canvas" class="canvas articleContent" contenteditable="true" onblur="_editor.signals.blur(event);" onmousedown="_editor.signals.mousedown(event);" onmouseup="_editor.signals.mouseup(event);">{%articleOB_articleText%}</article>
	</div>
