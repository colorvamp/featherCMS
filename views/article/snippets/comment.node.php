<div class="comment">
	<div class="author">Por {%commentAuthor%}</div>
	<div class="btn-group">
		<div class="btn mini dropdown-toggle"><i class="icon-remove-sign"></i> Eliminar
			<div class="dropdown-menu padded"><form method="post">
				<input type="hidden" name="subcommand" value="commentRemove"/>
				<input type="hidden" name="commentID" value="{%id%}"/>
				<h4><i class="icon-picture"></i> Eliminar</h4>
				<p>Está seguro de eliminar el comentario.</p>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn" onclick="c.removeDialog_accept(event,this);"><i class="icon-ok-sign"></i> Aceptar</button></div>
			</form></div>
		</div>
		<div class="btn mini dropdown-toggle"><i class="icon-remove-sign"></i> Banear IP
			<div class="dropdown-menu padded"><form method="post">
				<input type="hidden" name="subcommand" value="commentBanIP"/>
				<input type="hidden" name="commentID" value="{%id%}"/>
				<h4><i class="icon-picture"></i> Eliminar</h4>
				<p>Está seguro de eliminar el artículo.</p>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
			</form></div>
		</div>
	</div>
	<div class="text">{%commentText%}</div>
</div>
