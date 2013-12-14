function init(){
	/* INI-dropdown */
	VAR_dropdownToggled = false;
	var dropdownToggles = document.getElementsByClassName('dropdown-toggle');
	$each(dropdownToggles,function(k,v){
		var menu = v.getElementsByClassName('dropdown-menu');if(!menu.length){return;}menu = menu[0];
		var cbOnBeforeOpen = v.getAttribute('data-dropdown-onBeforeOpen');

		var buttons = menu.getElementsByClassName('close');$each(buttons,function(k,el){el.addEventListener('click',function(e){e.stopPropagation();$dropdown.close(el);});});
		var buttons = menu.getElementsByClassName('remove');$each(buttons,function(k,el){el.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();return $dropdown.nodeRemove(el);});});
		var buttons = menu.getElementsByClassName('ajax');$each(buttons,function(k,el){el.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();return $dropdown.nodeAjax(el);});});

		menu.onmousedown = $dropdown.mousedown;
		if(menu.onclick){menu.onclick_callback = menu.onclick;}
		menu.onclick = function(e){e.stopPropagation();};
		v.addEventListener('click',function(e){
			e.stopPropagation();
			var isOpened = (menu.style.display == 'block');
			if(isOpened){return $dropdown.close(menu);}
			if(cbOnBeforeOpen){$execByString(cbOnBeforeOpen,[e,v]);}

			if(menu.onclick_callback){menu.onclick_callback(e,v);}

			$E.classAdd(v,'active');
			menu.style.display = (menu.style.display == 'block') ? 'none' : 'block';
			VAR_dropdownToggled = (menu.style.display == 'block') ? menu : false;
			var pos = $getOffsetPosition(menu);var rpos = ($T('BODY')[0].offsetWidth)-pos.left-pos.width;
			/* If the infoBox is out the page, fix it to the right border */
			if(rpos < 10){menu.style.left = menu.offsetLeft+rpos-10+'px';}
		});
	});
	addEventListener('click',function(event){if(VAR_dropdownToggled){$dropdown.close(VAR_dropdownToggled);}});
	/* END-dropdown */
};

extend($dropdown,{
	nodeRemove: function(elem){
		if(!elem.$B){el = $fix(elem);}
		var ddw  = elem.$P({'className':'dropdown-menu'});if(!ddw){return false;}
		var node = elem.$P({'className':'node'});if(!node){return false;}
		var form = ddw.$T('form');if(!form.length){return false;}form = $fix(form[0]);
		$transition.toState(elem,'working',function(q){});

		var params = $toUrl($parseForm(form));
		ajaxPetition(window.location.href,params,function(ajax){
			var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
			if('html' in r){$transition.setHTMLByState(elem,'info',r.html);}
			$fx.leaveVertical(node,{'callback':function(n){n.parentNode.removeChild(n);}});
		});

		return false;
	},
	nodeAjax: function(elem){
		if(!elem.$B){el = $fix(elem);}
		elem = elem.$P({'className':'dropdown-menu'});if(!elem){return false;}
		var form = elem.$T('form');if(!form.length){return false;}form = $fix(form[0]);

		$transition.toState(elem,'working',function(q){});
		var params = $toUrl($parseForm(form));
		ajaxPetition(window.location.href,params,function(ajax){
			var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
			if('html' in r){$transition.setHTMLByState(elem,'info',r.html);}
			if('location' in r){window.location.href = r.location;return false;}

			$execWhenTrue(function(){return elem.getAttribute('data-ready') == 'true';},function(){
				$transition.toState(elem,'info',function(q){});
			});
		});

		return false;
	}
});

var widgets = {};
function widget(widgetname,params){
	$findFunc = function(l){var func = window;var funcSplit = l.split('.');var e = true;for(i = 0;i < funcSplit.length;i++){if(!func[funcSplit[i]]){e = false;break;}func = func[funcSplit[i]];}return e ? func : false;};
	if(!(func = $findFunc(widgetname))){return false;}
	function w(){};
	w.prototype = func;
	var f = new w;
	return f.init(params);
}

var iface = {
	markdwon_save: function(e,elem){
		if(!elem.$B){elem = $fix(elem);}
		var ddw = elem.$L('dropdown-menu');if(!ddw){return false;}ddw = ddw[0];
		var params = {'subcommand':'ajax.article.save.text','articleText':encodeURIComponent($_('source').value)};
		ajaxPetition(window.location.href,$toUrl(params),function(ajax){
			var r = jsonDecode(ajax.responseText);
			if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
			$dropdown.close(ddw);
			//if(!window.location.href.match(/\/[0-9]+/)){window.location.href = window.location.href+'/'+r.data.id;}
		});
	},
	removeDialog_accept: function(e,elem){
		e.preventDefault();
		if(!elem.$P){elem = $fix(elem);}
		var holder = elem.$P({'className':'item'});

		_form.submitAsAjax(e,elem,function(r){
			if(!holder){return false;}
			$fx.leave(holder,{'callback':function(el){el.parentNode.removeChild(el);}});
		});
		return false;
	}
};

var _form = {
	pools: {},
	confirm: function(e){
		e.preventDefault();var link = e.target;var el = link.parentNode;el.style.position = 'relative';var hrf = link.href;
		var i = info_create('confirm',{'.width':'300px'},el).infoContainer.empty();
		$C('DIV',{innerHTML:'¿Estás seguro de querer continuar?'},i);
		var f = $C('FORM',{action:hrf,method:'POST'},i);
		$C('INPUT',{type:'hidden',name:'validated',value:1},f);

		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancelar',function(e){e.preventDefault();info_destroy(i);return false;},d);
		gnomeButton_create('Aceptar',function(){_form.submit(f);},d);

		return false;
	},
	submit: function(el){var form = $fix(el).$P({'tagName':'form'});if(!form){return;}form.submit();},
	submitAsAjax: function(e,el,callback){
		var form = $fix(el).$P({'tagName':'form'});if(!form){return;}
		var params = $parseForm(form);params['subcommand'] = 'ajax.'+params['subcommand'];
		var href = (form.action && form.action.length) ? form.action : window.location.href;
		ajaxPetition(href,$toUrl(params),function(ajax){
			var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
			if(callback){do{
				if(typeof callback == 'function'){callback(r);break;}
			}while(false);}
		});
		return false;
	},
	submitAsURL: function(el){while(el.parentNode && el.tagName!='FORM'){el = el.parentNode;}if(!el.parentNode){return;}var url = el.action.replace(/\/$/,'')+'/';var ops = $parseForm($fix(el));$each(ops,function(k,v){url+=v+'/';});window.location = url;}
};
