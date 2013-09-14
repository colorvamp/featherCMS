				<div class="btn dropdown-toggle" data-dropdown-onBeforeOpen="_editor.controls.paragraph_open">Párrafo
					<div class="dropdown-menu padded">
						<div class="transitionable" data-state="main">
							<h4><i class="icon-time"></i> Aplicar estilos a párrafo</h4>
							<form>{%style.list%}</form>
							<div class="btn-group right"><div class="btn close"><i class="icon-remove-sign"></i> Cancelar</div><button class="btn btn-accept" onclick="_editor.controls.paragraph_accept(event,this);"><i class="icon-ok-sign"></i> Aceptar</button></div>
						</div>
						<div class="transitionable hidden" data-state="e.noParagraph">
							No se ha seleccionado ningún párrafo
						</div>
					</div>
				</div>
