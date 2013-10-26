<div class="articleNode item {%html.articleIsDraftClass%}"><div class="wrapper">
	<div class="articleNode_avatar"><img src="{%baseURL%}u/avn/{%articleAuthor%}/32.jpg"></div>
	<div class="content">
		<ul class="articleNode_tags"></ul>
		<div class="articleNode_stats"></div>
		<div class="link">{%html.articleIsDraft%} <a href="{%articleURL%}" target="_blank">{%articleTitle%}</a></div>
		<div class="info">
			Por <a href="javascript:">{%articleAuthor%}</a> el {%articleDate%} a las {%articleTime%}
			<div class="btn-group">
				<a class="btn mini" href="{%baseURL%}article/edit/{%id%}"><i class="icon-edit-sign"></i> Editar</a>
				<!--<span class="btn mini" onclick="window.open(&quot;http://spoiler.colorvamp.com/r/cms/article_download/394&quot;,&quot;download&quot;);">Descargar</span>-->
				{%html.option.publish%}
				{%html.option.unpublish%}
				<div class="btn mini dropdown-toggle"><i class="icon-picture"></i> Miniatura
					<div class="dropdown-menu padded" ondragover="c.imageDialog_dragover(event);" ondrop="c.imageDialog_drop(event,this);" onclick="c.imageDialog_click(event,this);"><form method="post">
						<input type="hidden" name="subcommand" value="articleSetThumb"/>
						<input type="hidden" name="articleID" value="{%id%}"/>
						<textarea class="hidden">{%json.articleImages%}</textarea>
						<h4><i class="icon-picture"></i> Miniatura del artículo</h4>
						<p>Listado de imágenes que contiene el artículo en este momento.</p>
						<table><thead><tr><td>Título</td><td>Small</td><td>Medium</td><td>Large</td></tr></thead><tbody></tbody></table>
						<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button type="submit" class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
					</form></div>
				</div>
				<div class="btn mini dropdown-toggle"><i class="icon-paper-clip"></i> Archivar
					<div class="dropdown-menu padded"><form method="post">
						<input type="hidden" name="subcommand" value="articleArchive"/>
						<input type="hidden" name="articleID" value="{%id%}"/>
						<h4><i class="icon-paper-clip"></i> Archivar</h4>
						<p>Está seguro de archivar el artículo.</p>
						<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button type="submit" class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
					</form></div>
				</div>
				<a class="btn mini" href="{%baseURL%}article/v/{%id%}"><i class="icon-comments"></i> Comentarios</a>
				<div class="btn mini dropdown-toggle"><i class="icon-remove-sign"></i> Eliminar
					<div class="dropdown-menu padded"><form method="post">
						<input type="hidden" name="subcommand" value="articleRemove"/>
						<input type="hidden" name="articleID" value="{%id%}"/>
						<h4><i class="icon-picture"></i> Eliminar</h4>
						<p>Está seguro de eliminar el artículo.</p>
						<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn" onclick="c.removeDialog_accept(event,this);"><i class="icon-ok-sign"></i> Aceptar</button></div>
					</form></div>
				</div>
			</div>
		</div>
		<div class="articleNode_thumb">{%html.articleThumb%}</div>
		<div class="articleNode_text">{%articleSnippet%}</div>
		<div class="comments">{%html.comments%}</div>
		<div style="display:none;"><form method="post">
			<input type="hidden" name="subcommand" value="commentAdd"/>
			<input type="hidden" name="articleID" value="{%id%}"/>
			<textarea name="commentText" ></textarea>
			<button>Enviar</button>
		</form></div>
	</div>
</div></div>
