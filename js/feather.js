function init(){
	/* INI-dropdown */
	VAR_dropdownToggled = false;
	var dropdownToggles = document.getElementsByClassName('dropdown-toggle');
	$each(dropdownToggles,function(k,v){
		var menu = v.getElementsByClassName('dropdown-menu');if(!menu.length){return;}menu = menu[0];
		var cbOnBeforeOpen = v.getAttribute('data-dropdown-onBeforeOpen');

		var closeButtons = menu.getElementsByClassName('close');
		$each(closeButtons,function(k,el){el.onclick = function(e){e.stopPropagation();menu.style.display = (menu.style.display == 'block') ? 'none' : 'block';VAR_dropdownToggled = (menu.style.display == 'block') ? menu : false;};});
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
	var body = $T('BODY')[0];
//FIXME: mejor event listener
	body.onclick = function(event){
		if(VAR_dropdownToggled){$dropdown.close(VAR_dropdownToggled);}
	}
	/* END-dropdown */
};

function widget(widgetname,params){
	function w(){};
	w.prototype = window[widgetname];
	var f = new w;
	return f.init(params);
}

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
