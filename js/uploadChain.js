var uploadChain = {
	vars: {files:[],currentFile:false,uploadQueue:[]},
	onUploadEnd: function(){},
	appendFile: function(file,data){uploadChain.vars.files.push({'file':file,'data':data});},
	upload_processFile: function(){
		if(uploadChain.vars.files.length < 1){uploadChain.onUploadEnd();return true;}
		var file = uploadChain.vars.files.shift();
		//FIXME: usar blob: https://developer.mozilla.org/en-US/docs/Web/API/Blob http://kongaraju.blogspot.com.es/2012/07/large-file-upload-more-than-1gb-using.html
		var reader = new FileReader();
		reader.onloadend = function(){uploadChain.upload_processFile_onloadend(reader.result,file.data);};
		reader.readAsDataURL(file.file);
		return true;
	},
	upload_processFile_onloadend: function(result,data){
		var base64string = result.replace(/^[^,]*,/,'');
		var base64string_sum = md5(base64string);
		var base64string_len = base64string.length;
		var fragment_len = 100000;
		var i = 0;var c = 0;while(i < base64string_len){
			var top = i+fragment_len;if(top > base64string_len){top = base64string_len;}
			var fragment_string = base64string.substring(i,top);
			var fragment_sum = md5(fragment_string);
			//FIXME: no trozear aqui
			var node = {'fileName':data.fileName,'fileRoute':data.fileRoute,'fragment_num':c,'base64string_sum':base64string_sum,'base64string_len':base64string_len,'fragment_string':fragment_string,'fragment_sum':fragment_sum,'fragment_len':fragment_len,'href':(data.href ? data.href : false)};
			if(data.onUploadUpdate){node.onUploadUpdate = data.onUploadUpdate;}
			if(data.onUploadEnd){node.onUploadEnd = data.onUploadEnd;}
			uploadChain.vars.uploadQueue.push(node);
			i+=fragment_len;
			c++;
		}
		uploadChain.upload_fragment(uploadChain.vars.uploadQueue[0]);
	},
	upload_fragment: function(node){
		node.fragment_timeLast = new Date();
		var p = {'subcommand':'transfer_fragment','fileName':node.fileName,'fragment_num':node.fragment_num,'base64string_sum':node.base64string_sum,'base64string_len':node.base64string_len,'fragment_string':node.fragment_string,'fragment_sum':node.fragment_sum,'fragment_len':node.fragment_len};
		var href = node.href ? node.href : window.location.href;
		ajaxPetition(href,$toUrl(p),function(ajax){uploadChain.upload_fragment_callback(ajax,node);});
	},
	upload_fragment_callback: function(ajax,node){
		var r = jsonDecode(ajax.responseText);
		if(r.errorDescription){switch(r.errorDescription){
			case 'FRAGMENT_ALREADY_EXISTS':break;
			//case 'FILE_ALREADY_EXISTS':uploadChain.upload_processFile();return;
			default:alert(print_r(r));return;
		}}
		var actualSize = (r.data && r.data.imageSize) ? parseInt(r.data.imageSize) : parseInt(r.data.totalSize);
		var timeEnd = new Date();/* miliseconds */
		if(node.onUploadUpdate){do{
			/* Free memory */node.fragment_string = false;
			var nodeBack = extend(node,{'fragment_actualSize':actualSize,'fragment_timeEnd':timeEnd});
			if(typeof node.onUploadUpdate == 'function'){node.onUploadUpdate(nodeBack);break;}
			if(typeof node.onUploadEnd == 'string'){var func = window;var funcSplit = p.callback.split('.');for(i = 0;i < funcSplit.length;i++){func = func[funcSplit[i]];}func(nodeBack);}
		}while(false);}
		var c = node.fragment_num+1;if(!r.image_sum && uploadChain.vars.uploadQueue[c]){return uploadChain.upload_fragment(uploadChain.vars.uploadQueue[c]);}
		if(node.onUploadEnd){do{
			/* Free memory */node.fragment_string = false;
			if(typeof node.onUploadEnd == 'function'){node.onUploadEnd(r.data);break;}
			if(typeof node.onUploadEnd == 'string'){var func = window;var funcSplit = p.callback.split('.');for(i = 0;i < funcSplit.length;i++){func = func[funcSplit[i]];}func(r.data);}
		}while(false);}
		uploadChain.vars.uploadQueue = [];
		uploadChain.upload_processFile();
	},
	helper_bytesToSize: function(bytes){var sizes = ['Bytes','KB','MB','GB','TB'];if(bytes == 0){return 'n/a';}var i = parseInt(Math.floor(Math.log(bytes)/Math.log(1024)));return Math.round(bytes/Math.pow(1024,i),2)+' '+sizes[i];}
};
