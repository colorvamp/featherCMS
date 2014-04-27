<?php
	function index_main(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.track.php');
		$r = tracking_process();


$db = sqlite3_open($GLOBALS['api']['track']['db.tmp'],SQLITE3_OPEN_READONLY);
$day = date('Y-m-d');
		$whereClause = ' WHERE trackingDate = \''.$day.'\'';
		/* Extracción de datos */
		$r = sqlite3_query('SELECT trackingHour,count(*) as count FROM '.$GLOBALS['api']['track']['table.track'].' '.$whereClause.' GROUP BY trackingHour;',$db);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[$row['trackingHour']] = $row;}}
		/* - Visitantes únicos */
		$r = sqlite3_querySingle('SELECT count(DISTINCT(trackingIP)) as count FROM '.$GLOBALS['api']['track']['table.track'].' '.$whereClause.';',$db);
		$VAR_uniqueVisitors = $r ? $r['count'] : 0;
		/* - Visitantes totales */
		$r = sqlite3_querySingle('SELECT count(*) as count FROM '.$GLOBALS['api']['track']['table.track'].' '.$whereClause.';',$db);
		$VAR_totalPageViews = $r ? $r['count'] : 0;



		$i = 0;while($i < 24){if(!isset($rows[$i])){$rows[$i] = array('trackingHour'=>$i,'count'=>0);}$i++;}
		ksort($rows);
		$maxValue = current($rows);$maxValue = $maxValue['count'];
		$VAR_featherStatsDay = $rows;
		foreach($rows as $row){
			if($maxValue < $row['count']){$maxValue = $row['count'];}
		}

		$imgWidth = 712;$SVG_imgHeight = 132;
		$SVG_topBoxHeight = 37;
		$marginTop = 40+$SVG_topBoxHeight;$marginBottom = 19.5;$marginLeft = 12;$marginRight = 12;
		$SVG_numNodes = 24;
		$nodeWidth = ($SVG_numNodes == 1) ? ($imgWidth-$marginLeft-$marginRight) : ($imgWidth-$marginLeft-$marginRight)/($SVG_numNodes);
		$nodeWidthHalf = $nodeWidth/2;
		$pointHeight = ($maxValue > 0) ? ($SVG_imgHeight-$marginTop-$marginBottom)/$maxValue : 0;
		$maxLineHeight = floor($SVG_imgHeight-($maxValue*$pointHeight)-$marginBottom);
		$i = 0;$left = $marginLeft+$nodeWidthHalf;foreach($rows as $hour=>$row){
			$pointTop = floor($SVG_imgHeight-($row['count']*$pointHeight)-$marginBottom);
			$pointLeft = floor($left)+1;
			$points[] = array('left'=>$pointLeft,'top'=>$pointTop);
			$left += $nodeWidth;
			$i++;
		}
ob_start();
		echo T,'<script type="text/javascript">featherStats.vars.statsDay = ',json_encode($VAR_featherStatsDay),';</script>',N,
		T,'<div class="mainGraph">',N,
		T,T,'<svg width="',$imgWidth,'" height="',$SVG_imgHeight,'" version="1.1" xmlns="http://www.w3.org/2000/svg" onmouseover="featherStats.graph_mouseover(this);" onmouseout="featherStats.graph_mouseout(this);">',N,
	T,T,T,'<defs id="defs4"><linearGradient id="linearGradient3141"><stop style="stop-color:#fefefe;stop-opacity:1" offset="0" id="stop3143"/><stop style="stop-color:#f2f2f2;stop-opacity:1" offset="1" id="stop3145"/></linearGradient><linearGradient x1="0" y1="0" x2="0" y2="',$SVG_topBoxHeight,'" id="linearGradient3147" xlink:href="#linearGradient3141" gradientUnits="userSpaceOnUse" spreadMethod="pad"/></defs>';
	/* Cabecera */
	echo T,T,T,'<polygon points="0.5,0.5   ',($imgWidth-1),'.5,0.5   ',($imgWidth-1),'.5,',($SVG_topBoxHeight),'.5 0.5,',($SVG_topBoxHeight),'.5" fill="white" stroke="#CCC"/>';
	$cutLenght = $imgWidth/4;$left = 0;
	while($left < $imgWidth){
		echo T,'<rect width="',($cutLenght-2),'" height="',$SVG_topBoxHeight,'" x="',($left+1),'" y="1.5" style="fill:url(#linearGradient3147);fill-opacity:1;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1" id="rect2168"/>';
		$left += $cutLenght;
		echo T,'<line x1="',$left,'.5" y1="0" x2="',$left,'.5" y2="',$SVG_topBoxHeight,'" stroke="#AAA" stroke-width="1"/>';
	}
	$offsetLeft = 10;
	echo T,T,T,'<text x="',($offsetLeft),'" y="16" font-family="Arial" font-size="12">Visitantes únicos</text>',N;
	echo T,T,T,'<text x="',($offsetLeft),'" y="30" font-family="Arial" font-size="14" font-weight="bold">',$VAR_uniqueVisitors,'</text>',N;
	$offsetLeft += $cutLenght;
	echo T,T,T,'<text x="',($offsetLeft),'" y="16" font-family="Arial" font-size="12">Impresiones registradas</text>',N;
	echo T,T,T,'<text x="',($offsetLeft),'" y="30" font-family="Arial" font-size="14" font-weight="bold">',$VAR_totalPageViews,' <tspan font-size="10" font-weight="normal" fill="#444">(',($VAR_uniqueVisitors > 0 ? round($VAR_totalPageViews/$VAR_uniqueVisitors,2) : 0),' pag/visitante)</tspan></text>',N;
	$offsetLeft += $cutLenght+10;

		echo T,T,T,'<polygon points="0.5,',$SVG_topBoxHeight,'.5 ',($imgWidth-1),'.5,',$SVG_topBoxHeight,'.5 ',($imgWidth-1),'.5,',($SVG_imgHeight-1),'.5 0.5,',($SVG_imgHeight-1),'.5" fill="white" stroke="#CCC"/>',
		T,T,T,'<line x1="1" y1="',($SVG_imgHeight-19),'.5" x2="',($imgWidth-1),'" y2="',($SVG_imgHeight-19),'.5" stroke="#666" stroke-width="1"/>',
		T,T,T,'<line x1="1" y1="',$maxLineHeight,'.5" x2="',($imgWidth-1),'" y2="',$maxLineHeight,'.5" stroke="#BBB" stroke-width="1" stroke-dasharray="4 4"/>';
		/* Marcas horarias */
		$i = 0;$left = $marginLeft;while($i <= $SVG_numNodes){
			$l = floor($left);
			echo T,T,T,'<line x1="',$l,'.5" y1="',$SVG_topBoxHeight,'" x2="',$l,'.5" y2="',$SVG_imgHeight,'" stroke="#AAA" stroke-width="1"/>';
			$left += $nodeWidth;
			$i++;
		}
		/* Linea de evolución, sin puntos */
		$f = function($n){return $n['left'].','.$n['top'];};
		$p = array_map($f,$points);
		echo T,T,T,'<polyline style="fill:none;stroke:#0077cc;stroke-width:3.5;" points="'.implode(' ',$p).'"/>';
		foreach($points as $k=>$point){
			$px = $point['left'];
			$py = $point['top'];
			echo T,T,T,'<circle style="fill:white;" cx="'.$px.'" cy="'.$py.'" r="4.7"/>',N,
			T,T,T,'<circle style="fill:#0077cc;" cx="'.$px.'" cy="'.$py.'" r="3.6"/>',N;
			$left = $px-$nodeWidthHalf;
			echo T,T,T,'<rect x="',$left,'" y="0" width="',$nodeWidth,'" height="',$SVG_imgHeight,'" fill="transparent" onmouseover="featherStats.indicateDay(this,\'',$k,'\');"/>';
		}
		echo T,T,'</svg>',
		T,'</div>';
$TEMPLATE['html.track.graph'] = ob_get_contents();
ob_end_clean();


		$date = date('Y-m-d');
		$rows = tracking_getWhere('(trackingDate = \''.$date.'\')',array('selectString'=>'trackingURL,count(*) as count','group'=>'trackingURL','order'=>'count DESC','limit'=>40,'indexBy'=>false,'db.file'=>$GLOBALS['api']['track']['db.tmp']));
		$TEMPLATE['html.track.table.rank'] = '<table><tbody>';
		foreach($rows as $row){
			$TEMPLATE['html.track.table.rank'] .= '<tr><td>'.$row['trackingURL'].'</td><td>'.$row['count'].'</td></tr>';
		}
		$TEMPLATE['html.track.table.rank'] .= '</tbody></table>';
		common_renderTemplate('index');
	}

	function index_av($id = '',$s = 32){
		$id = preg_replace('/[^0-9]*/','',$id);
		$s = preg_replace('/[^0-9]*/','',$s);if(empty($s)){$s = 32;}
		$imagePath = $GLOBALS['api']['users']['dir.users'].$id.'/avatar/'.$s.'.jpeg';
		if(!file_exists($imagePath)){$imagePath = '../images/avatars/default/av'.$s.'.jpeg';}
		$imgProp = @getimagesize($imagePath);
		header('Content-Type: '.$imgProp['mime']);
		readfile($imagePath);exit;
	}

	function index_login(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		$GLOBALS['COMMON']['BASE'] = 'base.login';
		if(users_isLogged()){header('Location: '.$GLOBALS['baseURL']);exit;}
		//users_updateSchema();exit;

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'ajax.userLogin':$r = users_login($_POST['userMail'],$_POST['userPass']);echo json_encode($r);exit;
			case 'userRegister':
				$users = users_getSingle(1);if($users){break;}
				if(!isset($_POST['userPass']) || !isset($_POST['userPassR']) || $_POST['userPass'] != $_POST['userPassR']){echo 'passwords mismatch';exit;}
				$r = users_create($_POST);if(isset($r['errorDescription'])){print_r($r);exit;}
				$user = $r;
				/* Activate the new user so he can log into the system */
				$r = users_update($user['userMail'],array('userStatus'=>1,'userModes'=>',regular,admin,','userCode'=>''));
				header('Location: '.$GLOBALS['baseURL']);exit;
		}}

		if(count($_POST)){do{
			include_once('api.users.php');
			$r = users_login($_POST['userMail'],$_POST['userPass']);
			if(isset($r['errorDescription'])){$TEMPLATE['loginWarn'] = $r['errorDescription'];break;}
			header('Location: '.$GLOBALS['baseURL']);exit;
		}while(false);}

		/* INI-print all the allowed users */
		$users = users_getWhere('(userModes LIKE \'%,admin,%\' OR userModes LIKE \'%,writer,%\' OR userModes LIKE \'%,publisher,%\')',array('indexBy'=>'userMail'));
		if(!$users){return common_renderTemplate('u/register');}
		$usersGrid = '';
		foreach($users as $user){
			$user['loginName'] = $user['userName'];
			$usersGrid .= common_loadSnippet('snippets/login.user',$user);
		}
		$TEMPLATE['usersGrid'] = $usersGrid;
		/* INI-print all the allowed users */


		$TEMPLATE['BLOG_TITLE'] = 'Login de usuarios';
		$TEMPLATE['HTML_TITLE'] = $TEMPLATE['BLOG_TITLE'];
		$TEMPLATE['HTML_DESCRIPTION'] = 'Login de usuarios';
		$TEMPLATE['BLOG_JS'][] = '{%baseURL%}r/js/login.js';
		common_renderTemplate('u/login');
	}
	function index_remember($userMail = false,$userCode = false){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		$GLOBALS['COMMON']['BASE'] = 'base.login';
		if($userMail && strpos($userMail,'%40')){$userMail = urldecode($userMail);}

		if(isset($_SESSION['template'])){switch($_SESSION['template']){
			case 'remember.sent':
				unset($_SESSION['template']);
				$TEMPLATE['BLOG_TITLE'] = 'Contraseña enviada';
				return common_renderTemplate('u/remember.sent');
			case 'remember.changed':
				unset($_SESSION['template']);
				$TEMPLATE['BLOG_TITLE'] = 'Contraseña cambiada';
				return common_renderTemplate('u/remember.changed');
		}}

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'user.remember':
				include_once('inc.config.php');
				$configMail = config_get('mail');if(!$configMail){break;}
				if(!isset($_POST['userMail'])){break;}
				$_POST['userMail'] = preg_replace($GLOBALS['api']['users']['reg.mail.clear'],'',$_POST['userMail']);
				$userOB = users_getSingle('(userMail = \''.$_POST['userMail'].'\')');if(!$userOB){break;}
				$newCode = users_helper_generateCode($_POST['userMail']);
				$userOB = users_update($userOB['userMail'],array('userIP'=>$_SERVER['REMOTE_ADDR'],'userCode'=>$newCode));
				//FIXME: esto jode un login por cookies, pueden ejecutarlo para joder a alguien
				$rep = array();
				$rep['recoverLink'] = $GLOBALS['baseURL'].'remember/'.$userOB['userMail'].'/'.$newCode;
				$blob = common_loadSnippet('mail/es.mail.recover.pass',$rep);
				include_once('api.mailing.php');
				$subj = 'Recuperación de contraseña';
				$r = mailing_send(array('mail.username'=>$configMail['emailName'],'mail.password'=>$configMail['emailPass'],'mail.host'=>$configMail['emailHost'],'mail.port'=>$configMail['emailPort']),
					$userOB['userMail'],$subj,$blob);
				if(isset($r['errorDescription'])){print_r($r);exit;}
				$_SESSION['template'] = 'remember.sent';
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
			case 'user.pass':
				if(!$userMail && !$userCode){break;}
				$userMail = preg_replace($GLOBALS['api']['users']['reg.mail.clear'],'',$userMail);
				$userOB = users_getSingle('(userMail = \''.$userMail.'\')');if(!$userOB){break;}
				if($_POST['userPass'] != $_POST['userPassR']){echo 'las contraseñas no coinciden';exit;}
				$newCode = users_helper_generateCode($userMail);
				$userOB = users_update($userMail,array('userPass'=>$_POST['userPass'],'userIP'=>$_SERVER['REMOTE_ADDR'],'userCode'=>$newCode));
				$_SESSION['template'] = 'remember.changed';
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
				header('Location: '.$GLOBALS['baseURL'].'login');exit;
		}}

		if($userMail && $userCode){do{
			$userMail = preg_replace($GLOBALS['api']['users']['reg.mail.clear'],'',$userMail);
			$userOB = users_getSingle('(userMail = \''.$userMail.'\')');if(!$userOB){break;}
			if($userOB['userCode'] != $userCode){break;}
			$TEMPLATE['BLOG_TITLE'] = 'User Remember';
			return common_renderTemplate('u/new.pass');
		}while(false);}

		$TEMPLATE['BLOG_TITLE'] = 'Recordar contraseña';
		return common_renderTemplate('u/remember');
	}
	function index_logout(){
		users_logout();
		header('Location: '.$GLOBALS['baseURL']);exit;
	}

	function index_config(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('inc.config.php');

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'mail.save':
				print_r($_POST);
				$maildata = array('emailName'=>$_POST['emailName'],'emailPass'=>$_POST['emailPass'],'emailHost'=>$_POST['emailHost'],'emailPort'=>$_POST['emailPort']);
				$maildata = json_encode($maildata);
				$r = file_put_contents($GLOBALS['api']['config']['dir.db'].$GLOBALS['api']['config']['file.mail'],$maildata);
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
				break;
			case 'configSave':
				
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
				break;
		}}

		$TEMPLATE['data.mail'] = config_get('mail');

		/* INI-imageSizes */
		$s = '<table class="data"><thead><tr><td>Tamaño</td><td style="width:20px;"></td></tr></thead><tbody>';
		foreach(array('16','32','64','90','136','180','128x64','256x0','620x0','128p64') as $size){
			$s .= '<tr><td>('.$size.')</td><td><input class="checkbox" type="checkbox" name="'.$size.'"/></td></tr>'.PHP_EOL;
		}$s .= '</tbody></table>';
		$TEMPLATE['html.articleImageSizes'] = $s;
		/* END-imageSizes */

		/* INI-cron */
		//$cronBlob = file_get_contents('/etc/crontab');
//echo $cronBlob;exit;
		/* END-cron */
		common_renderTemplate('config');
	}

	function index_search($criteria = ''){
//FIXME: lo ideal sería que buscase en el listado de artículos, por tener un renderizador común
//FIXME: hacerlo con $_GET
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.articles.php');
		include_once('inc.presentation.php');
		include_once('inc.strings.php');
		$currentController = 'search';
		$articlesPerPage = 20;

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'search':
				$baseURL = $GLOBALS['baseURL'].$currentController.'/'.strings_stringToURL($_POST['criteria']);
				header('Location: '.$baseURL);exit;
		}}

		$articles = articles_search($criteria);
		$total = count($articles);
		/* Imágenes de los artículos */
		$images = article_image_getWhere('(articleID IN ('.implode(',',array_keys($articles)).'))');
		foreach($images as $k=>$image){$articles[$image['articleID']]['articleImages'][$k] = $image;}

		$s = '';
		foreach($articles as $article){
			$article['articleURL'] = presentation_helper_getArticleURL($article);
			if(isset($article['articleImages'])){$article['json.articleImages'] = json_encode($article['articleImages']);}
			if(isset($article['articleSnippetImage']) && strlen($article['articleSnippetImage']) > 3){$article['html.articleThumb'] = '<img src="{%baseURL%}article/image/'.$article['id'].'/'.$article['articleSnippetImage'].'/64"/>';}
			$s .= common_loadSnippet('article/snippets/article.node',$article);
		}
		$TEMPLATE['list.articles'] = $s;
		/* INI-Paginador */
		$pager = '<div class="btn-group pager">';
		if($GLOBALS['currentPage'] > 1){$pager .= '<a class="btn btn-small" href="{%assisURL%}'.$currentController.'/page/'.($GLOBALS['currentPage']-1).'">Anterior</a>';}
		$pager .= '<a class="btn btn-small" href="{%assisURL%}'.$currentController.'/page/'.($GLOBALS['currentPage']+1).'">Siguiente</a>';
		$pager .= '</div>';
		$TEMPLATE['pager'] = $pager;
		/* END-Paginador */

		common_renderTemplate('search');
	}
?>
