if(!window.upload){window.upload = {};}
upload.chain = {
	vars: {files:{},current:false,queue:[]},
	fileAppend: function(file,info){upload.chain.vars.files[file.name] = {'file':file,'info':info};},
	fileUpload: function(name,part){
		if(!upload.chain.vars.files[name]){return false;}
		upload.chain.vars.current = name;

		var file = upload.chain.vars.files[name]['file'];
		var info = upload.chain.vars.files[name]['info'];
		var cbks = upload.chain.vars.files[name]['cbks'];
		if(!part){part = 1;}
		if(part == 1){var event = new CustomEvent('uploadStart',{'detail':{'file':file,'info':info}});dispatchEvent(event);}

		var size = 1024*100;
		var parts = Math.ceil(file.size/size);
		var reader = new FileReader();
		reader.onloadend = function(evt){
			if(evt.target.readyState != FileReader.DONE){return false;}
			var p = {'subcommand':'transfer.fragment','file.name':file.name,'file.size':file.size,'file.info':info,'file.parts':parts,'fragment.num':part,'fragment.src':evt.target.result,'fragment.sum':md5(evt.target.result),'fragment.len':evt.target.result.length};
			ajaxPetition('',$toUrl(p),function(ajax){
				var r = jsonDecode(ajax.responseText);
				if('errorDescription' in r){switch(r.errorDescription){
					case 'FRAGMENT_ALREADY_EXISTS':break;
					default:alert(ajax.responseText);return;
				}}
				var event = new CustomEvent('uploadUpdate',{'detail':{'file':file,'info':info,'progress':(part/parts)}});dispatchEvent(event);
				if(part < parts){return upload.chain.fileUpload(name,(part+1));}
				var event = new CustomEvent('uploadEnd',{'detail':{'response':r,'file':file,'info':info}});dispatchEvent(event);
				upload.chain.vars.files[name] = false;delete upload.chain.vars.files[name];
				upload.chain.vars.current = false;
				upload.chain.start();
			});
		};
		var blob = file.slice(size*(part-1),size*part);
		reader.readAsDataURL(blob);
	},
	start: function(){
		if(upload.chain.vars.current !== false){return false;}
		for(n in upload.chain.vars.files){
			if(!upload.chain.vars.files[n]){continue;}
			upload.chain.fileUpload(n);
			break;
		}
	}
};

function imgDrop(e,h){
	e.preventDefault();var dt = e.dataTransfer;var files = dt.files;
return false;
	if(files.length < 1){return;}
	//var entity = h.getAttribute('data-entity');
	//var callback = h.getAttribute('data-callback');if(!callback){callback = false;}
	//var imgFiles = [];$A(files).each(function(file){if(!file.type.match(/image.*/)){return;}imgFiles.push(file);});
	$each(files,function(k,v){upload.chain.fileAppend(v,{});});
	upload.chain.start();

return false;

	var api = window.location;
	var info = info_create('uploadChain',{},h);
	var h = info.infoContainer;
	$C('H4',{innerHTML:'Subir imagen'},h);
	var d = $C('DIV',{className:'dialog'},h);
	var pb = $C('DIV',{className:'progressBar',innerHTML:'<div class="background"></div><div class="foreground"></div>'},d);
	var pc = $C('DIV',{className:'progressCounter',innerHTML:'0%'},d);

	$A(imgFiles).each(function(file){uploadChain.appendFile(file,{'galleryName':entity,'callback':callback,'progressBar':pb,'progressCounter':pc});});
	uploadChain.onUploadEnd = function(){eFadeout(info,function(el){el.parentNode.removeChild(el);});};
	uploadChain.processFile();
}
function imgDragOver(e){e.preventDefault();}
