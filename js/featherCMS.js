//FIXME: pero también distinto de 'http://'
function w(loc){var l = VAR_cmsURL.length;if(loc.substr(0,l) != VAR_baseURL){loc = VAR_cmsURL+loc;}window.location = loc;}

function $rangeRice(el){
	function getSelection(){return (window.getSelection) ? window.getSelection() : document.selection;}
	function getRange(){var s = this.getSelection();if(!s){return null;}return (s.rangeCount > 0) ? s.getRangeAt(0) : document.createRange();}
	el.getSelection = getSelection;
	el.getRange = getRange;
	return el;
}

function $getSelectionHtml(){
	var html = '';
	if(typeof window.getSelection != 'undefined'){
		var sel = window.getSelection();
		if(sel.rangeCount){var container = document.createElement('div');for(var i = 0,len = sel.rangeCount;i < len;++i){container.appendChild(sel.getRangeAt(i).cloneContents());}html = container.innerHTML;}
	}else if(typeof document.selection != 'undefined'){if(document.selection.type == 'Text'){html = document.selection.createRange().htmlText;}}
	return html;
}

/*
[ ] - Al hacer control+x de un H4 copia el tag, eliminar el formato
*/
var API_AM = 'r/PHP/API_articleManager.php';
var API_GE = 'r/PHP/general.php';
var _canvas = false;
var _editor = {
	vars: {'publishing':false},
	init: function(){
		var editable = $_('editable');
		if(editable){
			_canvas = $rangeRice(editable);
			_canvas.cachedHeight = _canvas.outerHeight();
			if(_canvas.addEventListener){_canvas.addEventListener('DOMNodeInserted',_editor.onChange,false);_canvas.addEventListener('DOMNodeRemoved',_editor.onChange,false);}
			this.helper_canvasNormalize();
			this.article_images_createControls();
			this.article_corrections_rePosition();
		}
	},
	article_load: function(aID){w('article_edit/'+aID+'/');},
	article_save_parseResponse: function(ajax){
		var r = jsonDecode(ajax.responseText);
		if(parseInt(r.errorCode)>0){
			if(r.errorDescription == 'API_PUBLISHER_ALIAS_ERROR'){
				alert('API_PUBLISHER_ALIAS_ERROR');
				return;
				ths.user_setAuthorAlias();return;}
			alert(print_r(r));return;
		}
		/* Salvamos los datos actualizados del artículo en las posiciones del formulario */
		this.article_helper_formData(r.data);
	},
	article_helper_formData: function(data){$each(data,function(key,value){var y = $_('articleWriter_'+key);if(!y){return;}y.value = value;});},
	article_remove: function(articleID,a){
		var info = info_create('editor',{'.width':'400px'},a);
		var h = info.infoContainer.empty();

		$C('H4',{innerHTML:'Eliminar el artículo'},h);
		$C('INPUT',{type:'hidden',name:'command',value:'article_remove'},h);
		$C('INPUT',{type:'hidden',name:'articleID',value:articleID},h);
		$C('P',{innerHTML:'¿Está seguro de querer continuar?'},h);

		var ths = this;
		var bh = $C('UL',{className:'buttonHolder right'},h);
		gnomeButton_create('Cancelar',function(){info_destroy(info);},bh);
		gnomeButton_create('Aceptar',function(){z(info);},bh);

		function z(info){
			var n = $C('DIV',{className:'wodInfoContainer'});
			$C('DIV',{className:'loadingHolder',innerHTML:'Eliminando artículo ...'},n);
			info.transition(n);
			var ops = $toUrl($parseForm(info));
			ths.vars.publishing = true;
			ajaxPetition(API_AM,ops,function(ajax){
				info_destroy(info);ths.vars.publishing = false;
				var r = jsonDecode(ajax.responseText);
				if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				if(a){while(a.parentNode && !a.className.match(/(^| )articleNode( |$)/)){a = a.parentNode;}if(!a.parentNode){return;}a.parentNode.removeChild(a);}
				//FIXME: quizá un efecto de fadeout del elemento mientras hace eHeight a 0
			});
		}
	},
	article_publish: function(articleID,a){
		var info = info_create('editor',{'.width':'400px'},a);
		var h = info.infoContainer.empty();

		$C('H4',{innerHTML:'Publicar el borrador'},h);
		$C('INPUT',{type:'hidden',name:'command',value:'draft_publish'},h);
		$C('INPUT',{type:'hidden',name:'articleID',value:articleID},h);
		$C('P',{innerHTML:'¿Está seguro de querer continuar?'},h);

		var ths = this;
		var bh = $C('UL',{className:'buttonHolder right'},h);
		gnomeButton_create('Cancelar',function(){info_destroy(info);},bh);
		gnomeButton_create('Aceptar',function(){z(info);},bh);

		function z(info){
			var n = $C('DIV',{className:'wodInfoContainer'});
			$C('DIV',{className:'loadingHolder',innerHTML:'Publicando artículo ...'},n);
			info.transition(n);
			var ops = $toUrl($parseForm(info));
			ths.vars.publishing = true;
			ajaxPetition(API_AM,ops,function(ajax){
				info_destroy(info);ths.vars.publishing = false;
				var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				window.location.reload();
				//FIXME: quitar etiquetas etc, quizá informar
			});
		}
	},
	article_store: function(articleID,a){
		var info = info_create('editor',{'.width':'400px'},a);
		var h = info.infoContainer.empty();

		$C('H4',{innerHTML:'Archivar el borrador'},h);
		$C('INPUT',{type:'hidden',name:'command',value:'draft_store'},h);
		$C('INPUT',{type:'hidden',name:'articleID',value:articleID},h);
		$C('P',{innerHTML:'¿Está seguro de querer continuar?'},h);

		var ths = this;
		var bh = $C('UL',{className:'buttonHolder right'},h);
		gnomeButton_create('Cancelar',function(){info_destroy(info);},bh);
		gnomeButton_create('Aceptar',function(){z(info);},bh);

		function z(info){
			var n = $C('DIV',{className:'wodInfoContainer'});
			$C('DIV',{className:'loadingHolder',innerHTML:'Archivando artículo ...'},n);
			info.transition(n);
			var ops = $toUrl($parseForm(info));
			ths.vars.publishing = true;
			ajaxPetition(API_AM,ops,function(ajax){
				info_destroy(info);ths.vars.publishing = false;
				var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				window.location.reload();
				//FIXME: quitar etiquetas etc, quizá informar
			});
		}
	},
	article_search: function(h){var ops = $parseForm($_('searchBlock'));w('article_search/'+ops.str);},
	article_images_createControls: function(){
		var _parent = $fix(_canvas.parentNode);
		var controlsHolder = $_('imageControls').empty();
		var ths = this;$A(_canvas.$T('IMG')).each(function(img){z(img,controlsHolder);});

		function z(img,holder){
			var h = _parent;
			var posParent = $getOffsetPosition(h);
			var posImage = $getOffsetPosition(img);

			//FIXME: la posicion la debería llevar el li
			var li = $C('LI',{},holder);
			var bh = $C('UL',{className:'buttonHolder compact right','.top':(posImage.top-posParent.top+img.height-30)+'px','.right':(h.innerWidth()-(img.offsetLeft+img.width+30))+'px'},h);
			gnomeButton_create('Eliminar imágen',function(e){ths.article_images_controlsRemove(e,img);},bh);
			gnomeButton_create('Pie de foto',function(e){ths.article_images_controlsFooter(e,img);},bh);
		}
	},
	article_images_controlsRemove: function(e,img){
//FIXME: hay que poner confirmación
		var _parent = img.parentNode;
		_parent.parentNode.removeChild(_parent);
	},
	article_images_controlsFooter: function(e,img){
		var a = e.target;
		var info = info_create('editor',{'.width':'400px'},a);
		var h = info.infoContainer.empty();

		var _parent = $fix(img.parentNode);
		var iElements = _parent.$T('I');
		var text = (iElements.length > 0) ? iElements[iElements.length-1].innerHTML : '';

		$C('H4',{innerHTML:'Texto de pie de foto'},h);
		$C('P',{innerHTML:'Insertar un texto de pie de foto'},h);
		$C('TEXTAREA',{name:'imageFooter',value:text},$C('DIV',{className:'inputText'},h));

		var bh = $C('UL',{className:'buttonHolder right'},h);
		gnomeButton_create('Cancelar',function(){info_destroy(info);},bh);
		gnomeButton_create('Aceptar',function(){z(info,img);},bh);

		function z(info,img){
			var ops = $parseForm(info);
			var _parent = $fix(img.parentNode);
			var iElements = _parent.$T('I');

			var i = $C('I',{innerHTML:ops.imageFooter});
			$each(iElements,function(key,elem){if(!elem.nodeType || elem.nodeType != 3){return;}elem.parentNode.removeChild(elem);});
			extend(img,{'title':ops.imageFooter,'alt':ops.imageFooter});
			_parent.appendChild(i);
			info_destroy(info);
		}
	},
	article_corrections_rePosition: function(){
		var corrections = $_('articleCorrections');if(corrections.childNodes.length < 1){return;}
		var globalBound = $getOffsetPosition(_canvas);
		var paragraphs = _canvas.$T('P');

		$each(corrections.childNodes,function(key,elem,num){
			if(!elem.nodeType){return;}
			if(elem.nodeType == 3){return;}
			var paragraphNum = elem.className.match(/paragraph_([0-9]+)/);if(paragraphNum){paragraphNum = paragraphNum[1];}
			var bound = $getOffsetPosition(paragraphs[paragraphNum]);
			$fix(elem,{'.minHeight':bound.height+'px','.top':(bound.top-globalBound.top+5)+'px'});
			//alert(elem);
		});
	},
	article_corrections_togle: function(el){while(el.parentNode && el.tagName != 'LI' && !el.className.match || !el.className.match(/correctionNode/)){el = el.parentNode;}if(!el.parentNode){return;}el.className = (el.className.match(/hidden/)) ? el.className.replace(/ ?hidden ?/,'') : el.className+' hidden';},
	article_corrections_addResponse: function(e){
		var anchor = el = e.target;
		while(el.parentNode && el.tagName != 'LI' && !el.className.match || !el.className.match(/correctionNode/)){el = el.parentNode;}if(!el.parentNode){return;}
		var correctionParent = el.className.match(/correctionNode_([0-9]+)/);if(correctionParent){correctionParent = correctionParent[1];}

		/* Averiguamos el identificador del artículo */
		var articleID = $_('articleWriter_articleID').value;
//FIXME: comprobar que no sea menor de 0 etc

		var h = info_create('editor',{'.width':'400px'},anchor).infoContainer.empty();
		$C('H4',{innerHTML:'Responder a la corrección'},h);
		$C('INPUT',{type:'hidden',name:'command',value:'corrections_save'},h);
		$C('INPUT',{type:'hidden',name:'correctionParent',value:correctionParent},h);
		$C('INPUT',{type:'hidden',name:'correctionArticleID',value:articleID},h);
		$C('TEXTAREA',{name:'correctionDescription',value:''},$C('DIV',{className:'inputText'},h));

		var bh = $C('UL',{className:'buttonHolder right'},h);
		gnomeButton_create('Cancelar',function(){info_destroy(h);},bh);
		gnomeButton_create('Crear',function(){
			var ops = $parseForm(h);
			ajaxPetition(API_AM,$toUrl(ops),function(ajax){
				info_destroy(h);
				var r = jsonDecode(ajax.responseText);
				if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				alert(print_r(r));
			});
		},bh);
	},
	positionRestore: function(){var t = $_('positionFlag');if(!t){return;}_canvas.getRange().setStartAfter(t);t.parentNode.removeChild(t);},
	helper_getParent: function(e){while(e.parentNode && (!e.parentNode.id || e.parentNode.id != 'editable')){e = e.parentNode;}if(!e.parentNode){return false;}return e;},
	helper_getNextBlock: function(elem){
		//FIXME: puede haber más tags
		var nextBlock = false;var currentBlock = elem;
		while(!nextBlock && currentBlock.nextSibling){currentBlock = currentBlock.nextSibling;if(this.helper_elementIsBlock(currentBlock)){nextBlock = currentBlock;}}
		return nextBlock;
	},
	helper_elementIsBlock: function(elem){return (elem && elem.tagName && (elem.tagName == 'P' || elem.tagName == 'H4' || elem.tagName == 'LI' || elem.tagName == 'OL'));},
	helper_selectionCreateRange: function(startContainer,startOffset,endContainer,endOffset){
		var range = _canvas.getRange();var userSelection = window.getSelection();
		userSelection.removeAllRanges();range.setStart(startContainer,startOffset);
		range.setEnd(endContainer,endOffset);userSelection.addRange(range);
		return range;
	},
	helper_canvasNormalize: function(){
		var ths = this;
		$each(_canvas.childNodes,function(k,v){
			if(!v.nodeType){return;}
			if(!ths.helper_elementIsBlock(v)){
				var p = $C('P',{});
				(v.nextSibling) ? _canvas.insertBefore(p,v.nextSibling) : _canvas.appendChild(p);
				p.appendChild(v);
				while(p.nextSibling && !ths.helper_elementIsBlock(p.nextSibling)){p.appendChild(p.nextSibling);}
				ths.helper_paragraphNormalize(p);
				return;
			}else{
				if(v.tagName == 'P'){
					ths.helper_paragraphNormalize(v);
					if(v.childNodes.length < 1){_canvas.removeChild(v);}
				}
			}
		});
		$each(_canvas.$T('BR'),function(k,v){if(!v.nodeType){return;}v.parentNode.removeChild(v);});
	},
	helper_nodeNormalize: function(node){
		var ths = this;
		var nodes = [];
		$each(node.childNodes,function(k,el){nodes.push(el);});
		$each(nodes,function(k,el){
			if(!el){return;}
			if(el.nodeType == 3){return;}
			if(el.childNodes && el.childNodes.length == 0){el.parentNode.removeChild(el);return;}
			/* El child es del mismo tipo que el parent, debemos subirlo */
			if(el.tagName && el.tagName == el.parentNode.tagName){
				ths.helper_nodeNormalize(el);
				while(el.firstChild){el.parentNode.insertBefore(el.firstChild,el);}
				el.parentNode.removeChild(el);return;
			}
		});
		return node;
	},
	helper_nodeMergeSibling: function(node){
		if(node.previousSibling && node.previousSibling.tagName && node.tagName == node.previousSibling.tagName){var prev = node.previousSibling;while(prev.firstChild){node.insertBefore(prev.firstChild,node.firstChild);}prev.parentNode.removeChild(prev);}
		if(node.nextSibling && node.nextSibling.tagName && node.tagName == node.nextSibling.tagName){var next = node.nextSibling;while(next.firstChild){node.appendChild(next.firstChild);}next.parentNode.removeChild(next);}
		return node;
	},
	helper_nodeGetOffsetLength: function(node){
		var range = _canvas.getRange();var userSelection = window.getSelection();
		userSelection.removeAllRanges();range.setStartBefore(node.firstChild);
		range.setEndAfter(node.lastChild);userSelection.addRange(range);
		var tmp = range.cloneRange();return tmp.toString().length;
	},
	helper_paragraphNormalize: function(p){
		var t = p.innerHTML;
		t = t.replace(/<\/?(p|pre|span|br|div|font)[^>]*>/igm,'');
		t = t.replace(/<\![^>]*>/igm,'');
		t = t.replace(/<([ib])( [^>]+)>/igm,'<$1>');
		/* Porque los elementos A deben llevar un href */
		t = t.replace(/<a .*?href=.([^\'\"]+).{1}.*?>/igm,'<a href="$1">');
		p.innerHTML = t;
	},
	command_h4: function(){
		var range = _canvas.getRange();
		var startContainer = range.startContainer;var startOffset = range.startOffset;
		var endContainer = range.endContainer;var endOffset = range.endOffset;
		elem = this.helper_getParent(startContainer);
		if(elem === false){return false;}

		while(endContainer.lastChild){endContainer = endContainer.lastChild;}
		if(endContainer.nodeType != 3){return false;}/* Como por ejemplo un DIV vacío (nueva línea) */

		this.helper_selectionCreateRange(startContainer,startOffset,endContainer,endOffset);
		documentFragment = range.extractContents();
		var H4 = $C('H4',{});
		H4.appendChild(documentFragment);
		elem.parentNode.insertBefore(H4,elem);
//FIXME: si el anterior nodo queda vacío -> eliminar
		var userSelection = window.getSelection();
		userSelection.removeAllRanges();
		range.setStart(H4,0);
		userSelection.addRange(range);
	},
	command_ol: function(){
		var range = _canvas.getRange();
		var startContainer = range.startContainer;var startOffset = range.startOffset;
		var endContainer = range.endContainer;var endOffset = range.endOffset;
		elem = this.helper_getParent(startContainer);
		if(elem === false){return false;}

		while(endContainer.lastChild){endContainer = endContainer.lastChild;}
		if(endContainer.nodeType != 3){return false;}/* Como por ejemplo un DIV vacío (nueva línea) */

		this.helper_selectionCreateRange(startContainer,startOffset,endContainer,endOffset);
		documentFragment = range.extractContents();
		var OL = $C('OL',{});
		var LI = $C('LI',{},OL);
		LI.appendChild(documentFragment);
		elem.parentNode.insertBefore(OL,elem);
//FIXME: si el anterior nodo queda vacío -> eliminar
		var userSelection = window.getSelection();
		userSelection.removeAllRanges();
		range.setStart(LI,0);
		userSelection.addRange(range);
	},
	command_lowTags: function(e,tagCode){
		e.preventDefault();
		var range = _canvas.getRange();
		var startContainer = range.startContainer;var startOffset = range.startOffset;
		var endContainer = range.endContainer;var endOffset = range.endOffset;
		if(range.collapsed){return false;}
//FIXME: quizá en este caso podríamos romper el tag y quitarle el formato
		if(startContainer.parentNode && startContainer.parentNode.tagName && startContainer.parentNode.tagName == tagCode){return;}
		elem = this.helper_getParent(startContainer);
		if(elem === false){return false;}

		while(endContainer.lastChild){endContainer = endContainer.lastChild;}
		if(endContainer.nodeType != 3){return false;}/* Como por ejemplo un DIV vacío (nueva línea) */

		this.helper_selectionCreateRange(startContainer,startOffset,endContainer,endOffset);
		documentFragment = range.extractContents();
		var node = $C(tagCode,{});
		node.appendChild(documentFragment);
		node = this.helper_nodeNormalize(node);
		range.insertNode(node);
		node = this.helper_nodeMergeSibling(node);

		range.setStartBefore(node);
		range.setEndAfter(node);
		var userSelection = window.getSelection();
		userSelection.removeAllRanges();
		userSelection.addRange(range);
	},
	command_removeFormat: function(e){
//FIXME: en ocasiones la seleccion llega al límite del tag html, si es el caso, aumentar la selección para abarcarlo
		e.preventDefault();
		var range = _canvas.getRange();
//FIXME: comprobar que no es collapsed
		var startContainer = range.startContainer;
		var startOffset = range.startOffset;
		var startParent = this.helper_getParent(startContainer);
		var endContainer = range.endContainer;
		var endOffset = range.endOffset;
		var endParent = this.helper_getParent(endContainer);

		if(startParent == endParent && startParent.tagName && startParent.tagName == 'H4'){
			var length = this.helper_nodeGetOffsetLength(startParent);
			/* Estamos de suerte, hay que convertir el H4 entero en un P */
			if(startOffset == 0 && endOffset == length){
				var node = $C('P',{});while(startParent.firstChild){node.appendChild(startParent.firstChild);}
				startParent.parentNode.insertBefore(node,startParent);
				startParent.parentNode.removeChild(startParent);
				return false;
			}
			/* Hay que convertir una parte */
//FIXME: TODO
			return false;
		}

		elem = this.helper_getParent(startContainer);
		if(elem === false){return false;}

		documentFragment = range.extractContents();
		var node = $C('SPAN',{});node.appendChild(documentFragment);
		var text = node.innerHTML.replace(/<[^>]*>/ig,'');
		text = text.replace(/&nbsp;/ig,' ');
		var node = document.createTextNode(text);
//FIXME: hay un caso que me preocupa, cuando no hemos seleccionado correctamente todo el contenido del tag al que le quitamos
//el formato, por lo que quedan tags en torno al range con un espacio como firstChild -> <b> </b>

		/* Cuando el nodo de texto queda huerfano, es decir, el padre es el propio artículo, creamos un nuevo párrafo
		 * para insertar exclusivamente el nodo de texto. */
		if(range.startContainer.tagName && range.startContainer.tagName == 'ARTICLE'){var paragraph = $C('P',{});paragraph.appendChild(node);node = paragraph;}

		range.insertNode(node);
		range.setStartBefore(node);
		range.setEndAfter(node);
		var userSelection = window.getSelection();
		userSelection.removeAllRanges();
		userSelection.addRange(range);
	},
	command_link: function(e){
		e.preventDefault();
		var anchor = e.target;
		var range = _canvas.getRange();
		var startContainer = range.startContainer;var startOffset = range.startOffset;
		var endContainer = range.endContainer;var endOffset = range.endOffset;

//FIXME: quizá en este caso podríamos romper el tag y quitarle el formato
		if(startContainer.parentNode && startContainer.parentNode.tagName && startContainer.parentNode.tagName == 'A'){return;}
		elem = this.helper_getParent(startContainer);
		if(elem === false || range.collapsed){
			var i = info_create('command',{'.width':'400px'},anchor);
			var h = i.infoContainer.empty();
			$C('H4',{innerHTML:'Añadir enlace'},h);
			$C('P',{className:'warn',innerHTML:'Para añadir un enlace necesitas seleccionar un fragmento de texto del documento que estás editando.'},h);
			var bh = $C('UL',{className:'buttonHolder right'},h);
			var b = gnomeButton_create('Cerrar',function(){info_destroy(h);},bh);
			return false;
		}

		while(endContainer.lastChild){endContainer = endContainer.lastChild;}
		if(endContainer.nodeType != 3){return false;}/* Como por ejemplo un DIV vacío (nueva línea) */

		this.helper_selectionCreateRange(startContainer,startOffset,endContainer,endOffset);
		documentFragment = range.cloneContents();
		var node = $C('SPAN',{});node.appendChild(documentFragment);
		var linkName = node.innerHTML;

		var i = info_create('command',{'.width':'400px'},anchor);
		var h = i.infoContainer.empty();
		$C('H4',{innerHTML:'Añadir enlace'},h);
		$C('DIV',{innerHTML:'Nombre que aparecerá en el enlace:'},h);
		$C('INPUT',{name:'linkName',value:linkName},$C('DIV',{className:'inputText'},h));
		$C('DIV',{innerHTML:'Dirección del enlace:'},h);
		$C('INPUT',{name:'linkHref',value:'http://'},$C('DIV',{className:'inputText'},h));

		var ths = this;
		var bh = $C('UL',{className:'buttonHolder right'},h);
		var b = gnomeButton_create('Cerrar',function(){info_destroy(h);},bh);
		gnomeButton_create('Crear',function(){
			var params = $parseForm(h);
			info_destroy(h);
			var editable = $_('editable').focus();
			var range = ths.helper_selectionCreateRange(startContainer,startOffset,endContainer,endOffset);
			var node = $C('A',{innerHTML:params.linkName,href:params.linkHref});
			documentFragment = range.extractContents();
			range.insertNode(node);
		},bh);
	},
	command_paragraph: function(e){
		e.preventDefault();
		var anchor = e.target;
		var range = _canvas.getRange();
		var startContainer = range.startContainer;
		var elem = this.helper_getParent(startContainer);
		if(elem === false){return false;}
		var currentClasses = elem.className.split(' ');

		var i = info_create('command',{'.width':'400px'},anchor);
		var h = i.infoContainer.empty();
		$C('H4',{innerHTML:'Clases del párrafo'},h);
		$C('P',{innerHTML:'Clases preconfiguradas en el CSS para aplicar sobre los diferentes párrafos.'},h);
		$C('UL',{},h);
		$each(VAR_paragraphClasses,function(k,v){
			if(v.value == '' || v.value == ' '){return;}
			var checked = (currentClasses.indexOf(v) > -1) ? 'checked' : '';
			var li = $C('LI',{},h);
			$C('INPUT',{type:'checkbox',className:'checkbox','checked':checked,value:v,onchange:function(){z(this);}},li);
			$C('SPAN',{innerHTML:v},li);
		});

		var bh = $C('UL',{className:'buttonHolder right'},h);
		var b = gnomeButton_create('Cerrar',function(){info_destroy(h);},bh);
		function z(input){if(input.checked){elem.className += ' '+input.value;}else{var patt = new RegExp(' ?'+input.value,'ig');elem.className = elem.className.replace(patt,'');}}
	},
	command_correction: function(e){
		e.preventDefault();
		var anchor = e.target;
		var range = _canvas.getRange();
		var startContainer = range.startContainer;
		var endContainer = range.endContainer;

//FIXME: quizá en este caso podríamos romper el tag y quitarle el formato
		var elem = this.helper_getParent(startContainer);
		if(elem === false || range.collapsed){
			var i = info_create('command',{'.width':'400px'},anchor);
			var h = i.infoContainer.empty();
			$C('H4',{innerHTML:'Crear una corrección para el artículo'},h);
			$C('P',{className:'warn',innerHTML:'Para añadir una corrección necesitas seleccionar al menos un fragmento de texto de uno de los párrafos.'},h);
			$C('P',{className:'warn',innerHTML:'Puedes seleccionar múltiples fragmentos de texto mateniendo pulsada la tecla <b>CONTROL</b>.'},h);
			var bh = $C('UL',{className:'buttonHolder right'},h);
			var b = gnomeButton_create('Cerrar',function(){info_destroy(h);},bh);
			return false;
		}

		/* Averiguamos el número del párrafo */
		var paragraphNum = 0;
		var paragraphs = $fix(elem.parentNode).$T('P');
		$each(paragraphs,function(p,num){if(p == elem){paragraphNum = num;}});

		/* Averiguamos el identificador del artículo */
		var articleID = $_('articleWriter_articleID').value;
//FIXME: comprobar que no sea menor de 0 etc

		var s = _canvas.getSelection();
		var text = elem.innerHTML.replace(/<[^>]*>/ig,'');
		var rangeCount = s.rangeCount-1;var count = -1;
		var displacement = 0;
		while(count < rangeCount){
			count++;
			var range = s.getRangeAt(count);
			var startContainer = range.startContainer;var startOffset = range.startOffset;
			var endContainer = range.endContainer;var endOffset = range.endOffset;
			var p1 = this.helper_getParent(startContainer);var p2 = this.helper_getParent(endContainer);
			/* No permitimos selecciones que incluyan 2 padres */
			if(p1 != p2){continue;}
			var textTmp = text.substring(0,startOffset+displacement)+'<b>'+text.substring(startOffset+displacement,endOffset+displacement)+'</b>'+text.substring(endOffset+displacement);
			displacement += 7;
			text = textTmp;
		}

		var i = info_create('command',{'.width':'400px'},anchor);
		var h = i.infoContainer.empty();
		$C('H4',{innerHTML:'Crear una corrección para el artículo'},h);
		$C('INPUT',{type:'hidden',name:'command',value:'corrections_save'},h);
		$C('INPUT',{type:'hidden',name:'correctionArticleID',value:articleID},h);
		$C('INPUT',{type:'hidden',name:'correctionParagraphNum',value:paragraphNum},h);
		$C('INPUT',{type:'hidden',name:'correctionParagraphText',value:text},h);
		$C('DIV',{innerHTML:'Comentario para la documentar la corrección:'},h);
		$C('TEXTAREA',{name:'correctionDescription',value:''},$C('DIV',{className:'inputText'},h));

		var ths = this;
		var bh = $C('UL',{className:'buttonHolder right'},h);
		var b = gnomeButton_create('Cerrar',function(){info_destroy(h);},bh);
		gnomeButton_create('Crear',function(){
			var ops = $parseForm(h);
			ajaxPetition(API_AM,$toUrl(ops),function(ajax){
				info_destroy(i);
				var r = jsonDecode(ajax.responseText);
				if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				alert(print_r(r));
			});
		},bh);
	},
	keydown: function(e){
		var ths = this;
		var range = _canvas.getRange();
		var startContainer = range.startContainer;
		var startParent = this.helper_getParent(startContainer);
		var startOffset = range.startOffset;
		var userSelection = window.getSelection();

		//FIXME: comprobar si está collapsed

		switch(e.keyCode){
			case 13:/* INTRO */
				/* Si hemos hecho intro sobre un nodo de texto que se encuentra incorrectamente bajo el tag ARTICLE, la seleccion
				 * debe llegar hasta el siguiente nodo de texto o el final en su defecto */
				if(startContainer.tagName == 'ARTICLE'){return false;}
				if(startContainer.nodeType == 3 && startContainer.parentNode.tagName && startContainer.parentNode.tagName == 'ARTICLE'){
					var nextBlock = this.helper_getNextBlock(startContainer);
					userSelection.removeAllRanges();
					range.setStart(startContainer,startOffset);
					if(!nextBlock){range.setEndAfter(_canvas.lastChild);}
					else{range.setEndBefore(nextBlock);}
					userSelection.addRange(range);
//FIXME: actualizar cosas como startParent?
				}else if(startParent.tagName && startParent.tagName == 'OL'){
					/* Obtenemos el nodo LI en el que se encuentra el texto */
					var tmp = startContainer;while(tmp.parentNode && (tmp.nodeType == 3 || (tmp.tagName != 'LI'))){tmp = tmp.parentNode;}
					//FIXME: pero realmente habría que ver si hay un LI:first-child o algo así
					if(!tmp.tagName || tmp.tagName != 'LI'){return false;}
					/* Selección de texto */
					userSelection.removeAllRanges();
					range.setStart(startContainer,startOffset);
					range.setEndAfter(tmp.lastChild);
					userSelection.addRange(range);
					/* Inserción del nodo */
					documentFragment = range.extractContents();
					var elem = $C('LI',{});
					elem.appendChild(documentFragment);
					(tmp == startParent.lastChild) ? startParent.appendChild(elem) : startParent.insertBefore(elem,tmp.nextSibling);
				}else{
					/* Si se encuentra al principio del párrafo, no tiene sentido darle al intro */
					/* Si el contenedor es un contenedor padre y se encuentra vacío no necesitamos hacer nada */
					if(startContainer.nodeType == 1 && startContainer.innerHTML.toString().length == 0){return;}
//					if(startOffset == 0 && startContainer == startParent){alert(1);}
//alert(startContainer.tagName);
//alert(startParent);
					userSelection.removeAllRanges();
					range.setStart(startContainer,startOffset);
					range.setEndAfter(startParent.lastChild);
					userSelection.addRange(range);
				}

				
				if(startParent.tagName && startParent.tagName == 'OL'){
				}else{
					documentFragment = range.extractContents();
					//FIXME: me quedaría mucho más tranquilo si comprobásemos que es P
					var elem = $C('P',{});
					elem.appendChild(documentFragment);
					//FIXME: si estamos al final de article no habrá nextSibling (creo)
					startParent.parentNode.insertBefore(elem,startParent.nextSibling);
				}

				userSelection.removeAllRanges();
				range.setStart(elem,0);
				userSelection.addRange(range);
				return false;
				break;
			case 8:
				//alert(startOffset);
				/* Si estamos al principo de la línea debemos pasar el contenido del párrafo actual al interior del párrafo anterior,
				 * aunque la dificultad está en saber si realmente estamos en la primera posición de un párrafo aunque sea dentro de un
				 * child */
				if(startContainer.tagName == 'ARTICLE'){return false;}
				if(startOffset == 0 && startContainer.nodeType == 1 && startContainer == startParent.parentNode.firstChild){
					/* Si estamos dentro del primer párrafo, en la primera posición, no podemos dejar que se envíe true, porque el 
					 * backspace destruiría este elemento, y necesitamos al menos un párrafo */
					return false;
				}
				if(startOffset == 0 && startParent.previousSibling){
					if(startContainer != startParent){return true;}
					//FIXME: si se normaliza aquí en ocasiones se pierden referencias como previousParent
					//this.helper_canvasNormalize();
					//FIXME: comprobar los childs con previousSiblig (si es la primera posicion todos los padres no tendrán)

					var previousParent = startParent.previousSibling;
					$each(startParent.childNodes,function(k,v){if(v.tagName == 'BR'){v.parentNode.removeChild(v);}});

					/* Si startParent se encuentra vacío o contiene un único nodo de texto directamente eliminamos el nodo y
					 * saltamos al párrafo anterior, esto ocurre por ejemplo si nos posicionamos al final de un párrafo y damos
					 * al intro, el nuevo párrafo que se crea está vacío, por lo que si pulsamos backspace, no necesitamos
					 * mover el contenido de ese nuevo párrafo, podemos simplemente eliminarlo */
					if(!startParent.firstChild || (startParent.childNodes.length == 1 && startParent.firstChild.nodeType == 3 && startParent.firstChild.nodeValue == '')){
						startParent.parentNode.removeChild(startParent);
						userSelection.removeAllRanges();
						if(previousParent.lastChild){range.setStartAfter(previousParent.lastChild);range.setEndAfter(previousParent.lastChild);}
						else{range.setStart(previousParent,0);range.setEnd(previousParent,0);}
						userSelection.addRange(range);
						return false;
					}

					userSelection.removeAllRanges();
					range.setStartBefore(startParent);
					range.setEndAfter(startParent);
					userSelection.addRange(range);

					documentFragment = range.extractContents();
					userSelection.removeAllRanges();
					/* Es posible que previousParent (el anterior elemento al cual pretendemos subir e fragmento) esté vacío por
					 * lo que al tratar de anclar el range al lastChild de ese elemento explotará, si no tiene lastChilds lo ponemos 
					 * en la posición 0 porque el elemento se encuentra seguramente vacío */
					if(previousParent.lastChild){range.setStartAfter(previousParent.lastChild);range.setEndAfter(previousParent.lastChild);}
					else{range.setStart(previousParent,0);range.setEnd(previousParent,0);}
					userSelection.addRange(range);

					var oldFirstChild = documentFragment.firstChild;
//alert(previousParent.nodeType);return false;
					while(documentFragment.firstChild){previousParent.appendChild(documentFragment.firstChild);}
					this.helper_nodeNormalize(previousParent);
					$each(previousParent.childNodes,function(child){ths.helper_nodeMergeSibling(child);});

					return false;
				}
				break;
		}

		if(startContainer.tagName == 'ARTICLE'){
			/* Si hemos hecho intro bajo el tag ARTICLE debemos corregirlo inmediatamente, esto no debería ocurrir */
			var elem = $C('P',{});
			range.insertNode(elem);
			range.setStart(elem,0);
			userSelection.addRange(range);
		}
//alert(e.keyCode);
	},
	onImageLinkDragStart: function(e){
		var link = e.target;
		if(link.tagName !== 'A'){return;}
		e.dataTransfer.clearData();
		e.dataTransfer.setData('text/plain','{"type":"imageLink","name":"'+link.innerHTML+'","link":"'+link.href+'"}');
	},
	onDrop: function(e){
		var ths = this;
		//alert(e.dataTransfer.getData('text/htmlinfo'));return true;
		var encodedData = e.dataTransfer.getData('text/plain');
		var innerData = jsonDecode(encodedData);
		if(innerData.type && innerData.type == 'imageLink'){
			setTimeout(function(){
				var range = _canvas.getRange();
				var startContainer = range.startContainer;var endOffset = range.endOffset;
				var startOffset = endOffset-encodedData.length;
				range = ths.helper_selectionCreateRange(startContainer,startOffset,startContainer,endOffset);
				documentFragment = range.extractContents();
				var startParent = ths.helper_getParent(startContainer);
				var p = $C('P',{className:'articleImage_cleanCenter'});
				//FIXME: poner un loader o algo
				var img = new Image();img.onload = function(){
					p.appendChild(img);

					/* Insertamos el nuevo párrafo en la posición siguiente al párrafo donde hemos
					 * hecho el drop */
					//FIXME: si no existe startParent.nextSibling?
					startParent.parentNode.insertBefore(p,startParent.nextSibling);
					/* Realizamos un sanity check */
					ths.helper_canvasNormalize();
					setTimeout(function(){
						ths.article_images_createControls();
					},800);
				};img.src = innerData.link;
			},0);
		}
	},
	onChange: function(e){
		var currentHeight = _canvas.outerHeight();if(_canvas.cachedHeight == currentHeight){return;}
		//FIXME: TODO actualizar correciones
		//$C('DIV',{innerHTML:currentHeight},$_('searchBlock'));
		//_canvas.cachedHeight = currentHeight;
	},
	onPaste: function(){
		var ths = this;
//FIXME: si no está collapsed?
		var range = _canvas.getRange();
		var startContainer = range.startContainer;
		var startOffset = range.startOffset;
		var startParent = this.helper_getParent(startContainer);

		var d = $C('DIV',{},_canvas);
		range = ths.helper_selectionCreateRange(d,0,d,0);
		
		setTimeout(function(){
			ths.helper_paragraphNormalize(d);
			range = ths.helper_selectionCreateRange(startContainer,startOffset,startContainer,startOffset);
			/* Hay que empezar insertando desde el final porque range.insertNode no va a ir avanzando el cursor
			 * a medida que va insertando, por lo que el nodo que quedaría más próximo al cursor sería el lastChild
			 * por haber sido insertado el último. */
			while(d.lastChild){range.insertNode(d.lastChild);};
			_canvas.removeChild(d);
		},1);
	},
	user_logout: function(){ajaxPetition(API_GE,'command=userLogout&hardLogout=1',function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}document.location.reload();});}
};
