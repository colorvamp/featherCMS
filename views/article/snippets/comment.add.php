<div class="comment-new">
	<h4>Añadir comentario</h4>
	<p>-</p>
	<form method="post">
		<input type="hidden" name="subcommand" value="comment.add"/>
		<input type="hidden" name="articleID" value="{%articleOB_id%}"/>
		<div class="editor coredown">
			<div class="top">
				<div class="left">Markdown <a class="help" href="{%baseURL%}markdown" target="help"><i class="icon-question"></i></a></div>
				<div class="right">Preview</div>
			</div>
			<div class="content">
				<div class="left"><textarea class="source" name="commentText">Texto ...</textarea></div>
				<div class="right preview"></div>
			</div>
		</div>
		<div><button class="btn" type="submit">Enviar</button></div>
	</form>
</div>
