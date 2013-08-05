var _canvas = {
	getSelection: function(){return (window.getSelection) ? window.getSelection() : document.selection;},
	getRange: function(){var s = _canvas.getSelection();if(!s){return null;}return (s.rangeCount > 0) ? s.getRangeAt(0) : document.createRange();},
	getParent: function(el){if(!el.$P){el = $fix(el);}return el.$P({'className':'canvas'});},
	getCaretFromEvent: function(e){
		var range = textNode = offset = false;
		/* standard */if(document.caretPositionFromPoint){range = document.caretPositionFromPoint(e.pageX,e.pageY);textNode = range.offsetNode;offset = range.offset;range = document.createRange();range.setStart(textNode,offset);return range;}
		/* WebKit */if(document.caretRangeFromPoint){range = document.caretRangeFromPoint(e.pageX,e.pageY);textNode = range.startContainer;offset = range.startOffset;return range;}
		return range;
	},
	getParagraph: function(el){if(!el.$P){el = $fix(el);}return el.$P({'tagName':'p'});}
}

var _editor = {
	vars: {'isPublishing':false,'range':false},
	article_range_create: function(startContainer,startOffset,endContainer,endOffset){
		var range = _canvas.getRange();var userSelection = window.getSelection();
		userSelection.removeAllRanges();range.setStart(startContainer,startOffset);
		range.setEnd(endContainer,endOffset);userSelection.addRange(range);
		_editor.vars.range = range;
		//FIXME: salvar
		return range;
	},
	article_range_get: function(e){return _editor.vars.range;},
	article_save: function(e){
		if(_editor.vars.isPublishing){return;}
		_editor.vars.isPublishing = true;
		var anchor = e.target;
		var params = {'subcommand':'articleSaveText','articleText':encodeURIComponent($_('canvas').innerHTML)};
		var i = info_create('editor_publishing',{},anchor).infoContainer;
		$C('DIV',{className:'loadingHolder',innerHTML:'working ... '},i);

		ajaxPetition(window.location.href,$toUrl(params),function(ajax){
			info_destroy(i);_editor.vars.isPublishing = false;
			var r = jsonDecode(ajax.responseText);
			if(parseInt(r.errorCode)>0){
				if(r.errorDescription == 'API_PUBLISHER_ALIAS_ERROR'){alert('API_PUBLISHER_ALIAS_ERROR');return;}
				alert(print_r(r));return;
			}
		});
	},
	article_controls_image_click: function(e,elem){
		elem = $fix(elem);
		var tbody = elem.$T('TBODY');if(!tbody.length){return false;}tbody = $fix(tbody[0]).empty();
		var textarea = elem.$T('TEXTAREA');if(!textarea.length){return false;}textarea = textarea[0];
		if(!textarea.value.length){return false;}
		var images = jsonDecode(textarea.value);
		$each(images,function(k,v){
			var tr = $C('TR',{});
			var a = $C('A',{
				href:VAR_baseURL+'article/image/'+v.articleID+'/'+v.imageHash,
				innerHTML:v.imageTitle ? v.imageTitle : v.imageName,
				ondragstart: function(e){_editor.article_controls_image_anchor_dragstart(e);}
			},$C('TD',{},tr));
			tbody.appendChild(tr);
		});
//alert(print_r(images));
	},
	article_controls_image_anchor_dragstart: function(e){
		var link = e.target;if(link.tagName !== 'A'){return;}
		e.dataTransfer.clearData();
		e.dataTransfer.setData('text/plain','{"type":"imageLink","name":"'+link.innerHTML+'","link":"'+link.href+'"}');
	}
};

_editor.range = {
	save: function(e){
		var range = _canvas.getRange();
		if(range.collapsed){return false;}
		var canvas = $fix(e.target).$P({'className':'canvas'});
		var canvasPos = $getOffsetPosition(canvas);
		var canvasPadLeft = parseInt($getElementStyle(canvas,'padding-left'));
		var canvasPadTop = parseInt($getElementStyle(canvas,'padding-top'));
		var rangeLineHeight = parseInt($getElementStyle(e.target,'line-height'));
		var rangePos = $getOffsetPosition(range);
		/* Siempre termina en .777 porque la selección no toma el espacio inferior de la última linea */
		var rangeLinesCount = Math.ceil(rangePos.height/rangeLineHeight)
		var d = $C('DIV',{'className':'range',
			'.left':(rangePos.left-canvasPos.left+canvasPadLeft)+'px',
			'.top':(rangePos.top-canvasPos.top+canvasPadTop)+'px',
			'.width':rangePos.width+'px','.height':rangePos.height+'px'},canvas);
		_editor.vars.range = range;
	},
	remove: function(e){
		var canvas = $fix(e.target).$P({'className':'canvas'});
		var ranges = $fix(canvas).$L('range');
		$each(ranges,function(k,r){r.parentNode.removeChild(r);});
		_editor.vars.range = false;
	},
	get: function(e){return _editor.vars.range;}
}

_editor.controls = {
	remove: function(e){
		var canvasControls = $_('canvasControls').empty();
	},
	header_accept: function(e){
		var range = _editor.range.get();
		var text = range.extractContents();
		var node = $C('H4');
		node.appendChild(text);
		var par = _canvas.getParagraph(range.startContainer);
		par.parentNode.insertBefore(node,par);
	},
	bold_accept: function(e){
		var range = _editor.range.get();
		var text = range.extractContents();
		var node = $C('B');
		node.appendChild(text);
		range.insertNode(node);
	},
	italic_accept: function(e){
		var range = _editor.range.get();
		var text = range.extractContents();
		var node = $C('I');
		node.appendChild(text);
		range.insertNode(node);
	},
	format_accept: function(e){
		var range = _editor.range.get();
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
		var text = range.extractContents();
		var node = $C('A',{href:linkHref});
		node.appendChild(text);
		range.insertNode(node);
	},
	image_dragover: function(e){e.preventDefault();},
	image_drop: function(e,elem){
		e.preventDefault();
		var h = elem;

		var dt = e.dataTransfer;var files = dt.files;
		$each(files,function(k,file){
			var fd = $C('DIV',{className:'fileTransferNode','.height':0},h);
			var c = $C('DIV',{className:'fileTransferMargin'},fd);
			$C('DIV',{className:'title',innerHTML:'Copying "'+file.name+'" to "Desktop"'},c);
			var line = $C('DIV',{className:'size'},c);
				var curS = $C('SPAN',{innerHTML:'0 bytes'},line);
				$C('SPAN',{innerHTML:' of '+uploadChain.helper_bytesToSize(file.size)+' - '},line);
				var uplT = $C('SPAN',{innerHTML:''},line);//TODO: '6 minutes left'
				var uplS = $C('SPAN',{innerHTML:'(0 KB/s)'},line);
			var pbar = $C('DIV',{className:'progressBar'},c);
			var pbgr = $C('DIV',{className:'background'},pbar);
			var pfgr = $C('DIV',{className:'foreground'},pbar);
			eEaseEnter(fd);
			$uploadUpdate = function(node){
				curS.innerHTML = uploadChain.helper_bytesToSize(node.fragment_actualSize);
				var timeLapse = node.fragment_timeEnd-node.fragment_timeLast;
				var seconds = timeLapse/1000;var uploadRate = uploadChain.helper_bytesToSize(node.fragment_len/seconds)+'/sec';
				uplS.innerHTML = '('+uploadRate+')';
				var progress = $round((node.fragment_actualSize/parseInt(node.base64string_len))*100);
				pfgr.style.width = progress+'%';
			};
			$uploadEnd = function(){
				eEaseLeave(fd,{'callback':function(el){el.parentNode.removeChild(el);}});
			};
			uploadChain.appendFile(file,{'fileName':file.name,'fileSize':file.size,'onUploadUpdate':$uploadUpdate,'onUploadEnd':$uploadEnd});
		});
		info_reflow(h);

		uploadChain.onUploadEnd = function(){};
		uploadChain.upload_processFile();

		return false;
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
