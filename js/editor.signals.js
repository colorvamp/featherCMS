if(!window._editor){window._editor = {};}
window.addEventListener('load',function(e){_editor.signals.init();});
_editor.signals = {
	vars: {'paragraph':false},
	init: function(){
		var canvas = $_('canvas');
		canvas.addEventListener('click',function(e){_editor.signals.click(e);});
		canvas.addEventListener('keydown',function(e){_editor.signals.keydown(e);});
		canvas.addEventListener('dragover',function(e){_editor.signals.dragover(e);});
		canvas.addEventListener('drop',function(e){_editor.signals.drop(e);});
		$each(canvas.$T('IMG'),function(k,img){
			img.setAttribute('tabindex',0);
			img.addEventListener('click',function(e){_editor.signals.image.click(e);});
			img.addEventListener('blur',function(e){_editor.signals.image.blur(e);});
		});
		$each(canvas.$T('P'),function(k,p){
			p.setAttribute('tabindex',0);
			p.addEventListener('click',function(e){_editor.signals.p.click(e);});
			p.addEventListener('blur',function(e){_editor.signals.p.blur(e);});
		});
		window.addEventListener('scroll',function(e){_editor.signals.scroll(e);});
	},
	scroll: function(e){
		if((document.documentElement.scrollTop+document.body.scrollTop) > 100){
			$_('editorConstrols',{'.position':'fixed','.top':'20px'});
		}else{
			$_('editorConstrols',{'.position':'relative','.top':'auto'});
		}
	},
	click: function(e){
		var range = _canvas.getRange();
		var userSelection = window.getSelection();
		var startContainer = range.startContainer;
		if(startContainer.tagName == 'ARTICLE' && !startContainer.childNodes.length){
			var node = $C('P');
			node.setAttribute('tabindex',0);
			node.addEventListener('click',function(e){_editor.signals.p.click(e);});
			node.addEventListener('blur',function(e){_editor.signals.p.blur(e);});

			range.insertNode(node);
			node.focus();
			range.setStart(node,0);
			range.collapse(true);
			userSelection.removeAllRanges();			
			userSelection.addRange(range);
			return true;
		}
	},
	keydown: function(e){
		var range = _canvas.getRange();
		var startContainer = range.startContainer;
		var endContainer = range.endContainer;
		var startParent = _canvas.getParagraph(startContainer);
		var startOffset = range.startOffset;
		var endOffset = range.endOffset;
		var userSelection = window.getSelection();
		switch(e.keyCode){
			case 8:/* BACKSPACE */
				if(startOffset == 0 && startParent.previousSibling){
					var prevElem = startParent.previousSibling;
					prevElem.focus();
					/* Eliminamos los saltos de línea que pueda haber al final del párrafo anterior */
					while(prevElem.lastChild && prevElem.lastChild.tagName && prevElem.lastChild.tagName == 'BR'){prevElem.removeChild(prevElem.lastChild);}
					/* Nos posicionamos al final del párrafo anterior */
					if(prevElem.lastChild){range.setStartAfter(prevElem.lastChild);}
					else{range.setStart(prevElem,0);}
					range.collapse(true);
					userSelection.removeAllRanges();
					userSelection.addRange(range);
					/* Movemos todos los childs del elemento que vamos a eliminar */
					while(startParent.firstChild){
						/* Si el nodo que estamos trasportando es un salto de línea, pasamos */
						if(startParent.firstChild.tagName && startParent.firstChild.tagName.toUpperCase() == 'BR'){startParent.removeChild(startParent.firstChild);continue;}
						prevElem.appendChild(startParent.firstChild);
					}
					startParent.parentNode.removeChild(startParent);
					e.preventDefault();
					return false;
				}
				if(startOffset == 0 && !startParent.previousSibling){
					/* No podemos eliminar el primer elemento */
					e.preventDefault();
					return false;
				}
				break;
			case 13:/* INTRO */
				if(startContainer.tagName == 'ARTICLE'){return false;}
				break;
			case 46:/* SUPR */
				if(range.collapsed && endContainer.nodeType == 3 && startOffset == startContainer.nodeValue.length){
					/* Eliminamos los nextSiblings que estén vacíos, algunas veces quedan remanentes de <b> acumulador al final de un párrafo */
					if(endContainer.nextSibling && endContainer.nextSibling.nodeType == 1){while(endContainer.nextSibling && endContainer.nextSibling.innerHTML === ''){endContainer.parentNode.removeChild(endContainer.nextSibling);}}
					if(!endContainer.nextSibling && startParent.nextSibling){
						var nextParent = startParent.nextSibling;
						//FIXME: puede ser un div class="range" porq los ranges (en div) están al final de "article"
						/* Movemos todos los childs del siguiente elemento */
						while(nextParent.firstChild){
							/* Si el nodo que estamos trasportando es un salto de línea, pasamos */
							if(nextParent.firstChild.tagName && nextParent.firstChild.tagName.toUpperCase() == 'BR'){nextParent.removeChild(nextParent.firstChild);continue;}
							startParent.appendChild(nextParent.firstChild);
						}
						nextParent.parentNode.removeChild(nextParent);
						e.preventDefault();
						return false;
					}
				}
				/* Si queda un único contenedor, y es un párrafo no podemos dejar que se elimine */
				if(range.collapsed && endContainer == startParent && !startParent.nextSibling){
					//FIXME: el problema es que el nextSibling es el div del rango
					alert(1);
				}
				break;
			default:
				//alert(e.keyCode);
				break;
		}
	},
	blur: function(e){/*_editor.range.save(e.target);*/},
	mousedown: function(e){_editor.range.touch(e);},
	mouseup: function(e){_editor.range.save(e);},
	dragover: function(e){e.preventDefault();},
	drop: function(e,elem){
		//alert(e.dataTransfer.getData('text/plain'));return true;
		var encodedData = e.dataTransfer.getData('text/plain');
		var innerData = jsonDecode(encodedData);
		if(!innerData || !innerData.type){return true;}
		switch(innerData.type){
			case 'imageLink':_editor.signals.drop_imageLink(e);return true;break;
		}
	},
	drop_imageLink: function(e){
		var encodedData = e.dataTransfer.getData('text/plain');
		var innerData = jsonDecode(encodedData);
		if(!innerData || !innerData.type){return true;}
		e.preventDefault();
		var range = _canvas.getCaretFromEvent(e);
		var startContainer = (range) ? range.startContainer : false;
		var startParent = _canvas.getParagraph(startContainer);

		var p = $C('P',{className:'articleImage_cleanCenter'});
		var img = new Image();img.onload = function(){
			img.title = img.alt = innerData.name;
			img.style.width = '100%';
			p.appendChild(img);
			var i = $C('I',{innerHTML:innerData.name},p);
			img.addEventListener('click',function(e){_editor.signals.image.click(e);});
			//FIXME: falta
			/* Realizamos un sanity check */
			//_editor.helper_canvasNormalize();
		};img.src = innerData.link;

		/* only split text node */
		if(startContainer){
			switch(startContainer.nodeType){
				case 3:
					var replacement = textNode.splitText(offset);
					textNode.parentNode.insertBefore(p,replacement);
					break;
				case 1:
					if(!startParent.nextSibling){startParent.parentNode.appendChild(p);break;}
					startParent.parentNode.insertBefore(p,startParent.nextSibling);
					break;
			}
		}
	}
};

_editor.signals.image = {
	click: function(e){
		var elem = e.target;
		var canvasControls = $_('canvasControls');
		var canvas = $fix(e.target).$P({'className':'canvas'});
		var elemPos = $getOffsetPosition(elem);
		var canvasPos = $getOffsetPosition(canvas);
		var canvasPadLeft = parseInt($getElementStyle(canvas,'padding-left'));
		var canvasPadTop = parseInt($getElementStyle(canvas,'padding-top'));

		var btnGroup = $C('DIV',{'className':'btn-group',
			'.position':'absolute',
			'.left':(elemPos.left-canvasPos.left+canvasPadLeft+3)+'px',
			'.top':(elemPos.top-canvasPos.top+canvasPadTop)+'px'
		},canvasControls);
		$C('DIV',{'className':'btn',innerHTML:'Eliminar imágen',onclick:function(e){_editor.image.remove(e,elem);},onmousedown:function(e){e.stopPropagation();e.preventDefault();}},btnGroup);
	},
	blur: function(e){
		_editor.controls.remove();
	}
};

_editor.signals.p = {
	click: function(e){
		var elem = e.target;
		//if(_editor.signals.vars.paragraph){_editor.signals.vars.paragraph.style.outline = '0';_editor.signals.vars.paragraph = false;}
		//elem.style.outline = '1px dotted #AAA';_editor.signals.vars.paragraph = elem;
	},
	blur: function(e){
		_editor.controls.remove();
	}
};
