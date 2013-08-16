var c = {
	addWriter_search: function(e){
		var query = $fix(e.target).$P({'className':'input-search'}).$T('input')[0].value;
		var p = $toUrl({'subcommand':'ajax.userSearch','query':query});
		ajaxPetition(location.href,p,function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
			var holder = $_('addWriter_search_result');if(!holder){return;}
			eEaseLeave(holder,{'callback':function(holder){
				holder.empty();
				eEasePrepare(holder);
				$each(r.data,function(userMail,userOB){
					var li = $C('LI',{});
					$C('DIV',{innerHTML:userOB.userName},li);
					holder.appendChild(li);
				});
				eEaseEnter(holder);
			}});
		});
	}

};
