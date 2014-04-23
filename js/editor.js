var _canvas = {
	getSelection: function(){return (window.getSelection) ? window.getSelection() : document.selection;},
	getRange: function(){var s = _canvas.getSelection();if(!s){return null;}return (s.rangeCount > 0) ? s.getRangeAt(0) : document.createRange();},
	getParent: function(el){if(!el.$P){el = $fix(el);}return el.$P({'className':'canvas'});},
	getCaretFromEvent: function(e){
		var range = textNode = offset = false;
		/* standard */if(document.caretPositionFromPoint){range = document.caretPositionFromPoint(e.clientX,e.clientY);textNode = range.offsetNode;offset = range.offset;range = document.createRange();range.setStart(textNode,offset);return range;}
		/* WebKit */if(document.caretRangeFromPoint){range = document.caretRangeFromPoint(e.clientX,e.clientY);textNode = range.startContainer;offset = range.startOffset;return range;}
		return range;
	},
	getParagraph: function(el){if(!el.$P){el = $fix(el);}return el.$P({'tagName':'p'});},
	createRange: function(startContainer,startOffset,endContainer,endOffset){var range = document.createRange();range.setStart(startContainer,startOffset);range.setEnd(endContainer,endOffset);return range;},
	createRangeBetweenElements: function(startContainer,endContainer){var range = document.createRange();range.setStartBefore(startContainer);range.setEndAfter(endContainer);return range;}
}

var _editor = {
	vars: {'isPublishing':false,'range':false},
	article_controls_image_anchor_dragstart: function(e){
		var link = e.target;if(link.tagName !== 'A'){return;}
		e.dataTransfer.clearData();
		e.dataTransfer.setData('text/plain','{"type":"imageLink","name":"'+link.innerHTML+'","link":"'+link.href+'"}');
	}
};

_editor.range = {
	vars: {firstClick:false},
	touch: function(e){
		//e.preventDefault();e.stopPropagation();
		_editor.range.remove(e);
		var caret = _canvas.getCaretFromEvent(e);
		_editor.range.vars.firstClick = caret;
	},
	save: function(e){
		//e.preventDefault();e.stopPropagation();
		_editor.range.remove(e);
		if(!_editor.range.vars.firstClick){return false;}
		var firstClick = _editor.range.vars.firstClick;
		var caret = _canvas.getCaretFromEvent(e);
		var range = _canvas.createRange(firstClick.startContainer,firstClick.startOffset,caret.startContainer,caret.startOffset);


		var range = _canvas.getRange();
		var canvas = $fix(e.target).$P({'className':'canvas'});if(!canvas){alert(e.target);return false;}
		var canvasPos = $getOffsetPosition(canvas);
		var rangeLineHeight = parseInt($getElementStyle(e.target,'line-height'));
		var rangePos = $getOffsetPosition(range);
		/* Siempre termina en .777 porque la selección no toma el espacio inferior de la última linea */
		var rangeLinesCount = Math.ceil(rangePos.height/rangeLineHeight);
		var ranges = $_('ranges');
		var d = $C('DIV',{'className':'range',
			'.left':(rangePos.left-canvasPos.left)+'px',
			'.top':(rangePos.top-canvasPos.top)+'px',
			'.width':rangePos.width+'px','.height':rangePos.height+'px'},ranges);
//if(range.collapsed){$_('log').innerHTML += '<div>collapsed</div>';}
//$_('log').innerHTML += '<div>'+ranges.childNodes.length+'</div>';
		_editor.vars.range = range;
	},
	remove: function(e){
		var ranges = $_('ranges').childNodes;
		$each(ranges,function(k,r){r.parentNode.removeChild(r);});
		_editor.vars.range = false;
	},
	get: function(e){return _editor.vars.range;}
}

_editor.controls = {
	save_open: function(e,elem){
		if(_editor.vars.isPublishing){return;}
		_editor.vars.isPublishing = true;

		if(!elem.$B){elem = $fix(elem);}
		var ddw = elem.$L('dropdown-menu');if(!ddw){return false;}ddw = ddw[0];

		var params = {'subcommand':'articleSaveText','articleText':encodeURIComponent($_('canvas').innerHTML)};
		ajaxPetition(window.location.href,$toUrl(params),function(ajax){
			_editor.vars.isPublishing = false;
			var r = jsonDecode(ajax.responseText);
			if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
			$dropdown.close(ddw);
			if(!window.location.href.match(/\/[0-9]+/)){window.location.href = window.location.href+'/'+r.data.id;}
		});
	},
	remove: function(e){
		var canvasControls = $_('canvasControls').empty();
	},
	header_accept: function(e){
		var range = _editor.range.get();
		if(!range.startContainer){return;}
		if(range.collapsed){return false;}
		var text = range.extractContents();
		var node = $C('H4');
		node.appendChild(text);
		var par = _canvas.getParagraph(range.startContainer);
		par.parentNode.insertBefore(node,par);
	},
	bold_accept: function(e){
		var range = _editor.range.get();
		if(!range.startContainer){return;}
		if(range.collapsed){return false;}
		var text = range.extractContents();
		var node = $C('B');
		node.appendChild(text);
		range.insertNode(node);
	},
	italic_accept: function(e){
		var range = _editor.range.get();
		if(!range.startContainer){return;}
		if(range.collapsed){return false;}
		var text = range.extractContents();
		var node = $C('I');
		node.appendChild(text);
		range.insertNode(node);
	},
	format_accept: function(e){
		var range = _editor.range.get();
		if(!range.startContainer){return;}
		if(range.collapsed){return false;}
		var text = range.extractContents();
		var node = $C('SPAN',{});
		node.appendChild(text);
		var text = node.innerHTML.replace(/<[^>]*>/ig,'');
		text = text.replace(/&nbsp;/ig,' ');
		var node = document.createTextNode(text);
		range.insertNode(node);
	},
	link_open: function(e,elem){
		
	},
	link_accept: function(e){
		var el = e.target;
		var info = $fix(el).$P({'className':'dropdown-menu'});
		var params = $parseForm(info);
		var linkHref = params.linkHref;
		//FIXME: comprobar que sea o no un enlace real
		var range = _editor.range.get();
		if(!range.startContainer){return;}
		if(range.collapsed){return false;}
		var text = range.extractContents();
		var node = $C('A',{href:linkHref});
		node.appendChild(text);
		range.insertNode(node);
	},
	paragraph_open: function(e,elem){
		if(!elem.$B){elem = $fix(elem);}
		var range = _editor.range.get();
		var ddw = elem.$L('dropdown-menu');if(!ddw){return false;}ddw = ddw[0];
		if(!range.startContainer){$transition.toState(ddw,'e.noParagraph');return;}
		$transition.toState(ddw,'main');

		/* Detectamos el estilo actual del párrafo */
		if(!ddw.$B){ddw = $fix(ddw);}
		var par = _canvas.getParagraph(range.startContainer);
		var inputs = ddw.$T('INPUT');if(!inputs){return;}
		inputs[0].checked = 'checked';
		$each(inputs,function(k,v){if($E.classHas(par,v.value)){v.checked = 'checked';}});
	},
	paragraph_accept: function(e){
		var el = e.target;if(!el.$P){el = $fix(el);}
		var info = el.$P({'className':'dropdown-menu'});
		var params = $parseForm(info);
		var className = false;$each(params,function(k,v){className = v;});
		var range = _editor.range.get();
		if(!range.startContainer){return;}
		var par = _canvas.getParagraph(range.startContainer);
		par.className = '';
		if(className != ''){$E.classAdd(par,className);}
		$dropdown.close(el);
	},
	attachment_open: function(e,elem){
		elem = $fix(elem);
		var tbody = elem.$T('TBODY');if(!tbody.length){return false;}tbody = $fix(tbody[0]).empty();
		var textarea = elem.$T('TEXTAREA');if(!textarea.length){return false;}textarea = textarea[0];
		if(!textarea.value.length){return false;}
		var files = jsonDecode(textarea.value);
		$each(files,function(k,file){
			var fileHref = VAR_baseURL+'article/file/'+file.articleID+'/'+file.fileHash;
			var tr = $C('TR',{});
			var a = $C('A',{target:'blank',href:fileHref,innerHTML:file.fileName},$C('TD',{},tr));
			if(file.fileMime.substring(0,6) == 'image/'){
				a.addEventListener('dragstart',function(e){_editor.article_controls_image_anchor_dragstart(e);});
			}
			tbody.appendChild(tr);
		});
	},
};

_editor.controls.paragraph = {
	select: function(e){
		var range = _editor.range.get();
		if(!range.startContainer){return;}
		var paragraph = _canvas.getParagraph(range.startContainer);
		var range = _canvas.createRangeBetweenElements(paragraph.firstChild,paragraph.lastChild);
//FIXME: hacerlo con api
		var userSelection = window.getSelection();
		userSelection.removeAllRanges();
		userSelection.addRange(range);
	}
};

_editor.image = {
	remove: function(e,elem){
		var p = _canvas.getParagraph(elem);
		p.parentNode.removeChild(p);
		/* Once removed, we destroy the image controls */
		_editor.controls.remove();
	}
}
