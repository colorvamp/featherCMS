<?php
	function index_main(){
		common_renderTemplate('index');
	}

	function index_login(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		$GLOBALS['COMMON']['BASE'] = 'base.login';
		if(users_isLogged()){header('Location: '.$GLOBALS['baseURL']);exit;}

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

	function index_config(){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'configSave':
				
				header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL']);exit;
				break;
		}}

		/* INI-imageSizes */
		$s = '<table class="data"><thead><tr><td>Tamaño</td><td style="width:20px;"></td></tr></thead><tbody>';
		foreach(array('16','32','64','90','136','180','128x64','256x0','620x0','128p64') as $size){
			$s .= '<tr><td>('.$size.')</td><td><input class="checkbox" type="checkbox" name="'.$size.'"/></td></tr>'.PHP_EOL;
		}$s .= '</tbody></table>';
		$TEMPLATE['html.articleImageSizes'] = $s;
		/* END-imageSizes */

		/* INI-cron */
		$cronBlob = file_get_contents('/etc/crontab');
echo $cronBlob;exit;
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
