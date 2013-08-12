function init(){

}

function userLogin(h){
	var h = $fix(h).$P({'className':'user'});if(!h){return;}
	var ops = $parseForm(h);if(ops.userMail.length==0 || ops.userPass.length==0){return;}
	ops.subcommand = 'ajax.userLogin';
	ajaxPetition('',$toUrl(ops),function(ajax){var r = jsonDecode(ajax.responseText);
		if(typeof r.errorDescription != 'undefined'){
//FIXME: TODO
alert(ajax.responseText);
return;
		}
		document.location.reload();
	});
}

function expandAvatar(el){
	if(!('$P' in el)){el = $fix(el);}
	var user = el.$P({'className':'user'});if(!user){return;}
	if(parseInt(user.getAttribute('data-expanded')) || el.eEaseH){return;}
	$each(user.parentNode.childNodes,function(k,v){
		if(v.nodeType != 1 || !parseInt(v.getAttribute('data-expanded'))){return;}
		var img = v.$T('IMG')[0];
		eEaseHeight(img,-64,function(img){v.setAttribute('data-expanded',0);});
	});
	user.setAttribute('data-expanded',1);
	var img = el.$T('IMG')[0];

	var loginBox = $_('loginBox',{'.visibility':'hidden'});
	eEaseHeight(img,64,function(img){
		user.appendChild(loginBox);
		eFadein(loginBox,function(loginBox){loginBox.$T('INPUT')[0].focus();});
	});
}
