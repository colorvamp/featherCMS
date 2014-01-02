if(!window.upload){window.upload = {};}
upload.feather = {
	init: function(e){
		var uploaders = document.getElementsByClassName('dropdown-uploader');
		$each(uploaders,function(k,v){
			v.addEventListener('dragover',upload.feather.onDragOver);
			v.addEventListener('drop',upload.feather.onDrop);
		});
	},
	onDragOver: function(e){e.preventDefault();return false;},
	onDrop: function(e){
		e.preventDefault();
		var el = e.target;if(!el.$P){el = $fix(el);}
		var ddw = el.$P({'className':'dropdown-menu'});

		ddw.evtUploadStart  = addEventListener('uploadStart',function(e){return upload.feather.onUploadStart(e.detail.file,ddw);});
		ddw.evtUploadUpdate = addEventListener('uploadUpdate',function(e){return upload.feather.onUploadUpdate(e.detail.file,e.detail.progress,ddw);});
		ddw.evtUploadEnd    = addEventListener('uploadEnd',function(e){return upload.feather.onUploadEnd(e.detail.file,e.detail.response,ddw);});

		var dt = e.dataTransfer;var files = dt.files;
		if(files.length < 1){return;}
		$each(files,function(k,v){upload.chain.fileAppend(v,{});});
		upload.chain.start();
	},
	onUploadStart: function(file,ddw){
		/* Si ya existe el control no tenemos necesidad de volverlo a pintar */
		var uploadControl = ddw.$L('uploadControl');if(uploadControl.length){return false;}

		var fd = $C('DIV',{className:'uploadControl','.height':0},ddw);
		var c = $C('DIV',{className:'fileTransferMargin'},fd);
		$C('DIV',{className:'title',innerHTML:'Copying "'+file.name+'" to "Desktop"'},c);
		var line = $C('DIV',{className:'size'},c);
			var curS = $C('SPAN',{innerHTML:'0 bytes'},line);
			//$C('SPAN',{innerHTML:' of '+uploadChain.helper_bytesToSize(file.size)+' - '},line);
			var uplT = $C('SPAN',{innerHTML:''},line);//TODO: '6 minutes left'
			var uplS = $C('SPAN',{innerHTML:'(0 KB/s)'},line);
		$C('DIV',{className:'bar'},$C('DIV',{className:'progress'},c));
		eEaseEnter(fd);
	},
	onUploadUpdate: function(file,progress,ddw){
		//FIXME: si la barra no existe, hay que crearla
		var control = ddw.$L('uploadControl');if(!control){return false;}control = control[0];
		var progBar = control.$L('bar');if(!progBar){return false;}progBar = progBar[0];
		progBar.style.width = $round(progress*100)+'%';
	},
	onUploadEnd: function(file,response,ddw){
		var tbody = ddw.$T('TBODY');if(!tbody.length){return false;}tbody = $fix(tbody[0]).empty();
		var textarea = ddw.$T('TEXTAREA');if(!textarea.length){return false;}textarea = textarea[0];if(!textarea.value.length){textarea.value = '[]';}
		var files = (textarea.value != '[]') ? jsonDecode(textarea.value) : {};
		files[response.fileHash] = response;
		textarea.value = jsonEncode(files);

//FIXME: esto no va aquí, usarlo enganchado en el propio dropdown
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
	}
};
window.addEventListener('load',function(e){upload.feather.init();});
