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
						<div class="transitionable" data-state="main">
							<h4><i class="icon-time"></i> Publicación Programada</h4>
							<div class="publicationDate">{%html.articlePublishDate%}</div>
							<div class="publicationTime">{%html.articlePublishDate%}</div>
							<div class="calendar"></div>
							<form method="post">
								<input type="hidden" name="subcommand" value="articlePublishScheduled"/>
								<input type="hidden" name="articleID" value="{%id%}"/>
								<input class="publicationTimeInput" type="hidden" name="articlePublicationDate" value=""/>
								<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn btn-accept"><i class="icon-ok-sign"></i> Aceptar</button></div>
							</form>
						</div>
						<div class="transitionable hidden" data-state="saving">
							Guardando ...
						</div>
					</div>
				</div>
