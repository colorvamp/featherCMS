				<div class="btn mini dropdown-toggle"><i class="icon-cloud-upload"></i> Publicar
					<div class="dropdown-menu padded"><form method="post">
						<input type="hidden" name="subcommand" value="articlePublish"/>
						<input type="hidden" name="articleID" value="{%id%}"/>
						<h4><i class="icon-cloud-upload"></i> Publicar</h4>
						<p>Está seguro de publicar el artículo.</p>
						<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button type="submit" class="btn"><i class="icon-ok-sign"></i> Aceptar</button></div>
					</form></div>
				</div>
				<div class="btn mini dropdown-toggle" onclick="c.publishDialog_open(event,this);"><i class="icon-time"></i>
					<div class="dropdown-menu padded">
						<h4><i class="icon-time"></i> Publicación Programada</h4>
						<div class="publicationTime"></div>
						<div class="calendar"></div>
						<form method="post">
							<input type="hidden" name="subcommand" value="articlePublishScheduled"/>
							<input type="hidden" name="articleID" value="{%id%}"/>
							<input class="publicationTimeInput" type="hidden" name="articlePublicationDate" value="{%articlePublicationDate%}"/>
							<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn" onclick="_form.submitAsAjax(event,this);return false;"><i class="icon-ok-sign"></i> Aceptar</button></div>
						</form>
					</div>
				</div>