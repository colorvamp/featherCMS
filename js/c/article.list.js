var c = {
	thumb_click: function(e,elem){
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
	}
};
