window.addEventListener('load',function(){var ts = document.getElementsByClassName('coredown');$each(ts,function(k,v){_coredown.init(v);});});
var _coredown = {
	init: function(elem){
		if(!elem.$B){elem = $fix(elem);}
		var edt = elem.$L('editor');if(!edt){return false;}edt = edt[0];
		var prv = elem.$L('preview');if(!prv){return false;}prv = prv[0];
		var src = elem.$L('source');if(!src){return false;}src = src[0];
		edt.addEventListener('keyup',function(e){_coredown.signals.keyup(e,src,edt,prv);});
		src.addEventListener('change',function(e){_coredown.signals.change(e,src,edt,prv);});
		/* Cuando está relleno el contenedor de HTML */
		if(src.value == '' && prv.innerHTML.length){var mkdw = _coredown.html2markdown(prv.innerHTML);src.value = mkdw;src.dispatchEvent(new CustomEvent('change'));}

		//var html = _coredown.markdown2html(edt.innerHTML);
		//prv.innerHTML = html;

		/* Controles */
		var top = elem.$L('top');if(top){do{
			top = top[0];if(!top.$B){top = $fix(top);}
			var btnEdt = top.$L('left');if(!btnEdt){break;}btnEdt = btnEdt[0];
			var btnPrv = top.$L('right');if(!btnPrv){break;}btnPrv = btnPrv[0];
			btnEdt.addEventListener('click',function(e){
				$E.classAdd(elem,'showLeft');
				$E.classRemove(elem,'showRight');
			});
			btnPrv.addEventListener('click',function(e){
				$E.classAdd(elem,'showRight');
				$E.classRemove(elem,'showLeft');
			});
		}while(false);}
	},
	markdown2html: function($text){
		$text = $text.replace(/^[\xEF\xBB\xBF|\x1A]/,'');
		$text = $text.replace(/[\r\n]/g,"\n");
		$text = $text.replace(/\n[\n]+/g,"\n\n");

		/* Salvamos los enlaces de referencia */
		$referenceLinks = {};
		var rgx = /^[ ]{0,3}\[([0-9a-z]+)\]: ([^ \n]+)( .([^\'\"]+).|)/gm;
		$ref = rgx.exec($text);while($ref != null){$referenceLinks[$ref[1]] = {'link':$ref[2],'title':$ref[4] ? $ref[4] : ''};$ref = rgx.exec($text);}
		$text = $text.replace(rgx,'');
		/* Párrafos */
		$text = $text.split("\n\n");
		$text = '<p>'+$text.join('</p>\n<p>')+'</p>\n';
		/* INI-hr */$text = $text.replace(/<p>\*[\* ]+<\/p>/gm,'<hr/>\n').replace(/<p>\-[\- ]+<\/p>/gm,'<hr/>\n');
		/* INI-Blockquote */
		$text = $text.replace(/<p>> ([^<]+)<\/p>/g,'<blockquote><p>$1</p></blockquote>');
		$text = $text.replace(/<blockquote><p>([^<]+)<\/p><\/blockquote>/gm,function(m,$t){return '<blockquote><p>'+$t.replace(/\n> /g,' ').replace('\n',' ')+'</p></blockquote>';});
		$text = $text.replace(/<p>&gt; ([^<]+)<\/p>/g,'<blockquote><p>$1</p></blockquote>');
		$text = $text.replace(/<blockquote><p>([^<]+)<\/p><\/blockquote>/gm,function(m,$t){return '<blockquote><p>'+$t.replace(/\n&gt; /g,' ').replace('\n',' ')+'</p></blockquote>';});
		/* INI-ol */$text = $text.replace(/<p>[ ]{0,3}[0-9]+\. ([^<]+)<\/p>/gm,function(m,$t){$t = $t.split(/\n[ ]{0,3}(?:[\-\+\*]|[0-9]+\.) /g);return '<ol><li>'+$t.join('</li><li>')+'</li></ol>';});
		/* INI-ul */$text = $text.replace(/<p>[ ]{0,3}[\-\+\*] ([^<]+)<\/p>/gm,function(m,$t){$t = $t.split(/\n[ ]{0,3}[\-\+\*] /g);return '<ul><li>'+$t.join('</li><li>')+'</li></ul>';});
		/* INI-h1 */$text = $text.replace(/<p>([^\n<]+)\n[\=]+<\/p>/gm,'<h1>$1</h1>');
		/* INI-h2 */$text = $text.replace(/<p>([^\n<]+)\n[\-]+<\/p>/gm,'<h2>$1</h2>');
		/* INI-generic headers */$text = $text.replace(/<p>[ ]*([#]+)[ ]*([^<]+[^#])[ ]*[#]+[ ]*<\/p>/gm,function(m,$n,$t){var l = $n.toString().length;return '<h'+l+'>'+$t+'</h'+l+'>';});
		/* Images */
		$text = $text.replace(/\!\[([^\]]*)\]\((http:[^\) ]+|)( .([^\'\"]*).|)\)/g,'<img src="$2" alt="$1" title="$3"/>');
		/* Links */
		$text = $text.replace(/<(http:[^>]+)>/g,'<a href="$1">$1</a>');
		$text = $text.replace(/\[([^\]]+)\]\((http:[^\) ]+|)( .([^\'\"]*).|)\)/g,'<a href="$2" alt="$4" title="$4">$1</a>');
		/* Reference Links */$text = $text.replace(/\[([^\]]+)\]\[([^\]]*)\]/gm,function(m,$name,$ref){if($ref in $referenceLinks){return '<a href="'+$referenceLinks[$ref]['link']+'" alt="'+$referenceLinks[$ref]['title']+'" title="'+$referenceLinks[$ref]['title']+'">'+$name+'</a>';}return '<a href="">'+$name+'</a>';});
//FIXME: no quiero ver ni un while
		/* Table */
		var rgx = /[^\|<>]+\|[^\n<>]+/gm;
		$row = rgx.exec($text);while($row != null){$t = $row[0];$t = $t.split('|');$each($t,function(k,v){$t[k] = v.trim();});$t = '<tr><td>'+$t.join('</td><td>')+'</td></tr>\n';$text = $text.replace($row[0],$t);$row = rgx.exec($text);}
		$text = $text.replace(/<p>[^<]*<tr>/g,'<table><tbody><tr>');
		$text = $text.replace(/<\/tr>[^<]*<\/p>/g,'</tr></tbody></table>');
		var rgx = /<table[^>]*>[^<]*<tbody[^>]*>[^<]*(<tr>[^\n]+<\/tr>[^<]*)<tr><td>[:\-]+<\/td>.*?<\/tr>/gm;
		$row = rgx.exec($text);
		while($row != null){$t = '<table><thead>'+$row[1].replace(/<td/g,'<th').replace(/td>/g,'th>')+'</thead><tbody>';$text = $text.replace($row[0],$t);$row = rgx.exec($text);}


		/* Bold */$text = $text.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>');
		/* Bold */$text = $text.replace(/__(.*?)__/g,'<strong>$1</strong>');
		/* Italic */$text = $text.replace(/\*(.*?)\*/g,'<em>$1</em>');
		/* Italic */$text = $text.replace(/_(.*?)_/g,'<em>$1</em>');
		return $text;
	},
	html2markdown: function($text){
		$text = $text.replace(/^[\xEF\xBB\xBF|\x1A]/,'');
		$text = $text.replace(/[\r\n]/g,"\n");
		$text = $text.replace(/[\n]+/g,' ');
		$text = $text.replace(/>[ ]*</g,'><');

		/* blockquote */$text = $text.replace(/<blockquote>(.*?)<\/blockquote>/mg,'> $1\n\n');
		/* Table */$text = $text.replace(/<table>(.*?)<\/table>/g,function(m,text,num){
			var lng = 0;var rgx = /<t[dh]>(.*?)<\/t[dh]>/gm;while(ts = rgx.exec(text)){ts[1] = ts[1].toString();if(ts[1].length > lng){lng = ts[1].length;}}
			var cols = 0;var rgx = /<tr>(.*?)<\/tr>/gm;while(ts = rgx.exec(text)){var c = ts[1].match(/<t[dh]>/g);if(c && c.length > cols){cols = c.length;}}
			cpy = text;var rgx = /<t[dh]>(.*?)<\/t[dh]>/gm;while(ts = rgx.exec(text)){ts[1] = ts[1].toString();var df = ts[1].length;var l = lng-ts[1].length;cpy = cpy.replace(ts[0],ts[1]+(new Array(l+1).join(' '))+'|');}text = cpy;
			cpy = cpy.replace(/<\/thead>/g,function(m){var u = '';for(i = 0;i < cols;i++){for(j = 0;j < lng;j++){u += '-';}u += '|';}return u.substr(0,u.length-1)+'\n';});
			cpy = cpy.replace(/<thead>/g,'').replace(/<tr>/g,'').replace(/<[\/]*tbody>/g,'').replace(/<\/tr>/g,'\n').replace(/\|\n/g,'\n');
			return cpy+'\n';
		});
		$text = $text.replace(/<p[^>]*>(.*?)<\/p>/gm,'$1\n\n');
		/* Headers */$text = $text.replace(/<h([0-9]+)>(.*?)<\/h([0-9]+)>/g,function(m,num,text,num){hts = new Array(parseInt(num)+1).join('#');return hts+' '+text+' '+hts+'\n\n';});
		/* ol/ul */$text = $text.replace(/<([ou])l>(.*?)<\/[ou]l>/g,function(m,type,text,num){if(type == 'u'){text = text.replace(/<li>/,'1. ')}text = text.replace(/<li>/g,'* ').replace(/<\/li>/g,'\n');return text+'\n';});
		/* hr */$text = $text.replace(/<hr>/g,'****\n\n');
		/* Bold */$text = $text.replace(/<strong>(.*?)<\/strong>/g,'**$1**');
		/* Bold */$text = $text.replace(/<b>(.*?)<\/b>/g,'**$1**');
		/* Italic */$text = $text.replace(/<em>(.*?)<\/em>/g,'*$1*');
		/* Italic */$text = $text.replace(/<i>(.*?)<\/i>/g,'*$1*');
		/* Links */$text = $text.replace(/<a href=.([^\'\"]*).([^>]*)>([^<]*)<\/a>/gm,
			function(m,url,info,text){
				var t = info.match(/(alt|title)=.([^\'\"]+)./);
				if(!text || !text.length){return '';}/* Remove void links */
				return '['+text+']('+url+' "'+(t ? t[2] : '')+'")';
			});
		/* Images */$text = $text.replace(/<img src=.([^\'\"]*).([^>]*)\/?>/gm,
			function(m,src,info){
				var alt = info.match(/alt=.([^\'\"]+)./);
				var title = info.match(/title=.([^\'\"]+)./);
				return '!['+(alt ? alt[1] : '')+']('+src+' "'+(title ? title[1] : '')+'")';
			});

		$text = $text.replace(/<\/?[^>]>/g,'');
		$text = $text.replace(/\n[ \n]+/g,'\n\n');
		return $text;
	}
};

_coredown.signals = {
	keyup: function(e,src,edt,prv){
		$text = edt.innerHTML;
		$mkdw = $text.replace(/<br[^>]*>/g,'\n');
		src.value = $mkdw;
		$text = _coredown.markdown2html($mkdw);
		prv.innerHTML = $text;
	},
	change: function(e,src,edt,prv){
		$html = _coredown.markdown2html(src.value);
		$text = src.value.replace(/\n/g,'<br>');
		edt.innerHTML = $text;
		prv.innerHTML = $html;
	}
};
