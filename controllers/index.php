<?php
	function index_main(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('inc.mongo.php');
		include_once('api.track.php');

		/* INI-Graph by hours */
if(0){
		$hours = array();$i = 0;while($i < 24){if(!isset($hours[$i])){$hours[sprintf('%02s',$i)] = 0;}$i++;}
		$db = mongo_get();
		$collection = $db->selectCollection('tracebat',$_SERVER['SERVER_NAME']);
		$collection->ensureIndex(array('trackingIP'=>1));
		$collection->ensureIndex(array('trackingUserAgent'=>1));
		$collection->ensureIndex(array('trackingURL'=>1));
		$collection->ensureIndex(array('trackingMS'=>1));
		$collection->ensureIndex(array('trackingDate'=>1));
		$collection->ensureIndex(array('trackingTime'=>1));
		$collection->ensureIndex(array('trackingHour'=>1));

		$rs = $collection->aggregate(
			array('$match'=>array('trackingDate'=>date('Y-m-d')))
			,array('$group'=>array('_id'=>'$trackingHour','count'=>array('$sum'=>1)))
			//,array('$sort'=>array('count'=>-1))
		);
		foreach($rs['result'] as $h){$hours[sprintf('%02s',$h['_id'])] = $h['count'];}
		include_once('inc.graph.php');
		$svg = graph_bars(array(
			'graph.legend.width'=>60,
			'graph.height'=>160,
			'cell.width'=>26,
			'cell.marginx'=>4,
			'cell.marginy'=>14,
			'bar.indicator'=>true,
			'graph.gradient.from'=>'8cc277',
			'graph.gradient.to'=>'6fa85b',
			'graph'=>array(
				'hours'=>$hours
			),
			'header'=>array_keys($hours)
		));
		$TEMPLATE['html.track.graph'] = $svg;
}
		/* END-Graph by hours */


if( 0 ){
		$db = mongo_get();
		$collection = $db->selectCollection('tracebat',$_SERVER['SERVER_NAME']);
		$visitsInTotal = $collection->find(array('trackingDate'=>date('Y-m-d')))->count();
		$rs = $collection->aggregate(
			array('$match'=>array('trackingDate'=>date('Y-m-d')))
			,array('$group'=>array('_id'=>'$trackingIP'))
			,array('$group'=>array('_id'=>1,'count'=>array('$sum'=>1)))
		);
		$visitsUnique = $rs['result'] ? $rs['result'][0]['count'] : 0;
		$TEMPLATE['html.track.visits.total'] = $visitsInTotal;
		$TEMPLATE['html.track.visits.unique'] = $visitsUnique;

		$db = mongo_get();
		$collection = $db->selectCollection('tracebat',$_SERVER['SERVER_NAME']);
		$rs = $collection->aggregate(
			array('$match'=>array('trackingDate'=>date('Y-m-d')))
			,array('$group'=>array('_id'=>'$trackingURL','count'=>array('$sum'=>1)))
			,array('$sort'=>array('count'=>-1))
			,array('$limit'=>40)
		);

		$TEMPLATE['html.track.table.rank'] = '';
		$domainLength = strlen('http://')+strlen($_SERVER['SERVER_NAME']);
		foreach($rs['result'] as $row){
			$row['_id'] = substr($row['_id'],$domainLength);
			$TEMPLATE['html.track.table.rank'] .= '<tr><td>'.$row['_id'].'</td><td>'.$row['count'].'</td></tr>';
		}
}
		common_renderTemplate('main');
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
				$r = users_update($user['id'],array('userStatus'=>1,'userModes'=>',regular,admin,','userCode'=>''));
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
		common_setBase('base.login');
		common_loadScript('{%w.featherURL%}r/js/login.js');
		common_renderTemplate('u/login');
	}
	function index_remember($userMail = false,$userCode = false){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
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
				$userOB = users_update($userOB['id'],array('userIP'=>$_SERVER['REMOTE_ADDR'],'userCode'=>$newCode));
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
				$userOB = users_update($userOB['id'],array('userPass'=>$_POST['userPass'],'userIP'=>$_SERVER['REMOTE_ADDR'],'userCode'=>$newCode));
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
		common_setBase('base.login');
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
			if(isset($article['articleSnippetImage']) && strlen($article['articleSnippetImage']) > 3){$article['html.articleThumb'] = '<img src="{%w.featherURL%}article/image/'.$article['id'].'/'.$article['articleSnippetImage'].'/64"/>';}
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

