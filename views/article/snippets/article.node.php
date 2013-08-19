<div class="articleNode">
	<div class="articleNode_avatar"><img src="{%baseURL%}u/avn/{%articleAuthor%}/32.jpg"></div>
	<div class="articleNode_content">
		<ul class="articleNode_tags"></ul>
		<div class="articleNode_stats"></div>
		<div class="articleNode_link"><a href="{%articleURL%}" target="_blank">{%articleTitle%}</a></div>
		<div class="articleNode_info">
			Por <a href="javascript:">{%articleAuthor%}</a> el {%articleDate%} a las {%articleTime%}
			<div class="btn-group">
				<a class="btn mini" href="{%baseURL%}article/edit/{%id%}"><i class="icon-edit-sign"></i> Editar</a>
				<!--<span class="btn mini" onclick="window.open(&quot;http://spoiler.colorvamp.com/r/cms/article_download/394&quot;,&quot;download&quot;);">Descargar</span>-->
				<span class="btn mini" onclick="_editor.article_remove(394,this.parentNode);"><i class="icon-remove-sign"></i> Eliminar</span>
				<div class="btn mini dropdown-toggle"><i class="icon-picture"></i> Miniatura
					<div class="dropdown-menu padded" ondragover="/*_editor.controls.image_dragover(event);*/" ondrop="/*_editor.controls.image_drop(event,this);*/" onclick="c.thumb_click(event,this);"><form method="post">
						<input type="hidden" name="subcommand" value="articleSetThumb"/>
						<input type="hidden" name="articleID" value="{%id%}"/>
						<textarea class="hidden">{%json.articleImages%}</textarea>
						<h4><i class="icon-picture"></i> Miniatura del artículo</h4>
						<p>Listado de imágenes que contiene el artículo en este momento.</p>
						<table><thead><tr><td></td><td>Título</td></tr></thead><tbody></tbody></table>
						<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button type="submit" class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
					</form></div>
				</div>
			</div>
		</div>
		<div class="articleNode_thumb">{%html.articleThumb%}</div>
		<div class="articleNode_text">{%articleSnippet%}</div>
	</div>
</div>
