var c = {
	imageDialog_click: function(e,elem){
		elem = $fix(elem);
		var tbody = elem.$T('TBODY');if(!tbody.length){return false;}tbody = $fix(tbody[0]).empty();
		var textarea = elem.$T('TEXTAREA');if(!textarea.length){return false;}textarea = textarea[0];
		if(!textarea.value.length){return false;}
		var images = jsonDecode(textarea.value);
		$each(images,function(k,v){
			var tr = $C('TR',{});
			$C('INPUT',{type:'radio',name:'articleImage',value:v.imageHash},$C('TD',{},tr));
			var a = $C('A',{
				href:VAR_baseURL+'article/image/'+v.articleID+'/'+v.imageHash,
				innerHTML:v.imageTitle ? v.imageTitle : v.imageName,
				ondragstart: function(e){_editor.article_controls_image_anchor_dragstart(e);}
			},$C('TD',{},tr));
			tbody.appendChild(tr);
		});
	},
	imageDialog_dragover: function(e){e.preventDefault();},
	imageDialog_drop: function(e,elem){
		e.preventDefault();
		var h = elem;
		var props = $parseForm(elem);
		var articleID = props['articleID'] ? props['articleID'] : false;

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
			uploadChain.appendFile(file,{'fileName':file.name,'fileSize':file.size,'href':VAR_baseURL+'article/edit/'+articleID,'onUploadUpdate':$uploadUpdate,'onUploadEnd':$uploadEnd});
		});
		info_reflow(h);

		uploadChain.onUploadEnd = function(){};
		uploadChain.upload_processFile();

		return false;
	},
	removeDialog_accept: function(e,elem){
		e.preventDefault();
		if(!elem.$P){elem = $fix(elem);}
		form.submitAsAjax(e,elem,function(r){
			var holder = elem.$P({'className':'articleNode'});
			eEaseLeave(holder,{'callback':function(el){el.parentNode.removeChild(el);}});
		});
		return false;
	}
};
