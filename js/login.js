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
	$each(user.parentNode.childNodes,function(k,v){if(v.nodeType != 1){return;}$E.classRemove(v,'expand');});

	$E.classAdd(user,'expand');
	var loginBox = $_('loginBox',{'.visibility':'hidden'});
	user.appendChild(loginBox);
	eFadein(loginBox,function(loginBox){loginBox.$T('INPUT')[0].focus();});
}
