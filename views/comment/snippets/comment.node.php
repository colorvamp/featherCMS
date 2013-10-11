<div class="commentNode {%html.commentReviewClass%}"><div class="wrapper">
	<div class="author">{%html.commentReview%} Por <a href="{%commentURL%}">{%commentAuthor%}</a> el {%commentDate%} a las {%commentTime%}</div>
	<div class="link"><i class="icon-external-link-sign"></i> <a href="{%commentArticleURL%}">{%commentArticleTitle%}</a></div>
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
				<h4><i class="icon-picture"></i> Banear IP</h4>
				<p>Está seguro de querer banear esta IP?</p>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
			</form></div>
		</div>
		<div class="btn mini dropdown-toggle"><i class="icon-ok-sign"></i> Aprobar
			<div class="dropdown-menu padded"><form method="post">
				<input type="hidden" name="subcommand" value="commentApprove"/>
				<input type="hidden" name="commentID" value="{%id%}"/>
				<h4><i class="icon-ok-sign"></i> Aprobar</h4>
				<p>Está seguro de aprobar el artículo.</p>
				<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
			</form></div>
		</div>
	</div>
	<div class="text">{%commentText%}</div>
</div></div>
