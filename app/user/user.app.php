<?php
/**
 * @package iCMS
 * @copyright 2007-2015, iDreamSoft
 * @license http://www.idreamsoft.com iDreamSoft
 * @author coolmoo <idreamsoft@qq.com>
 * @$Id: user.app.php 2353 2014-02-13 04:04:49Z coolmoo $
 */
defined('iPHP') OR exit('What are you doing?');

iPHP::app('user.class', 'static');
iPHP::app('user.msg.class', 'static');
class userApp {
	public $methods = array('iCMS', 'home', 'favorite', 'article', 'publish', 'manage', 'profile', 'data', 'hits', 'check', 'follow', 'follower', 'fans', 'login', 'findpwd', 'logout', 'register', 'add_category', 'upload', 'mobileUp', 'config', 'uploadvideo', 'uploadimage', 'catchimage', 'report', 'fav_category', 'ucard', 'pm');
	public $openid = null;
	public $user = array();
	public $me = array();
	private $auth = false;

	public function __construct() {
		$this->auth = user::get_cookie();
		$this->uid = (int) $_GET['uid'];
		$this->ajax = (bool) $_GET['ajax'];
		$this->forward = iS::escapeStr($_GET['forward']);
		$this->forward OR iPHP::get_cookie('forward');
		$this->forward OR $this->forward = iCMS_URL;
		$this->login_uri = user::login_uri();
		// iFS::config($GLOBALS['iCONFIG']['user_fs_conf'])
		iFS::$userid = user::$userid;
		iPHP::assign('forward', $this->forward);
	}
	private function user($userdata = false) {
		$status = array('logined' => false, 'followed' => false, 'isme' => false);
		if ($this->uid) {
			// &uid=
			$this->user = user::get($this->uid);
			empty($this->user) && iPHP::throw404(
				'运行出错！找不到该用户',
				"user:" . $this->uid
			);
		}
		$this->me = user::status(); //判断是否登陆
		if (empty($this->me) && empty($this->user)) {
			iPHP::set_cookie('forward', '', -31536000);
			iPHP::gotourl($this->login_uri);
		}

		if ($this->me) {
			$status['logined'] = true;
			$status['followed'] = (int) user::follow($this->me->uid, $this->user->uid);
			empty($this->user) && $this->user = $this->me;
			if ($this->user->uid == $this->me->uid) {
				$status['isme'] = true;
				$this->user = $this->me;
			}
			iPHP::assign('me', (array) $this->me);
		}
		$this->user->hits_script = iCMS_API . '?app=user&do=hits&uid=' . $this->user->uid;
		iPHP::assign('status', $status);
		iPHP::assign('user', (array) $this->user);
		$userdata && iPHP::assign('userdata', (array) user::data($this->user->uid));
	}

	public function do_iCMS($a = null) {
		$this->do_home();
	}
	public function do_home($category = true) {
		$this->user(true);
		$category && $u['category'] = user::category((int) $_GET['cid'], iCMS_APP_ARTICLE);
		iPHP::append('user', $u, true);
		iPHP::view('iCMS://user/home.htm');
	}
	public function do_fans() {
		$this->do_home();
	}
	public function do_follower() {
		$this->do_home();
	}

	public function do_favorite() {
		$this->do_home();
	}
	public function do_manage() {
		$pgArray = array('publish', 'category', 'article', 'comment', 'inbox', 'favorite', 'share', 'follow', 'fans');
		$pg = iS::escapeStr($_GET['pg']);
		$pg OR $pg = 'article';
		if (in_array($pg, $pgArray)) {
			if ($_GET['pg'] == 'comment') {
				$app_array = iCache::get('iCMS/app/cache_id');
				iPHP::assign('iAPP', $app_array);
			}
			$this->user(true);
			$funname = '__do_manage_' . $pg;
			$class_methods = get_class_methods(__CLASS__);
			in_array($funname, $class_methods) && $this->$funname();
			iPHP::assign('pg', $pg);
			iPHP::assign('pg_file', "./manage/$pg.htm");
			iPHP::view("iCMS://user/manage.htm");
		}
	}
	public function do_profile() {
		$pgArray = array('base', 'avatar', 'setpassword', 'bind', 'custom');
		$pg = iS::escapeStr($_GET['pg']);
		$pg OR $pg = 'base';
		if (in_array($pg, $pgArray)) {
			$this->user();
			iPHP::assign('pg', $pg);
			if ($pg == 'bind') {
				$platform = user::openid(user::$userid);
				iPHP::assign('platform', $platform);
			}
			if ($pg == 'base') {
				iPHP::assign('userdata', (array) user::data(user::$userid));
			}
			iPHP::view("iCMS://user/profile.htm");
		}
	}

	private function __do_manage_article() {
		iPHP::assign('status', isset($_GET['status']) ? (int) $_GET['status'] : '1');
		iPHP::assign('cid', (int) $_GET['cid']);
		iPHP::assign('article', array(
			'manage' => iPHP::router('user:article', '?&'),
			'edit' => iPHP::router('user:publish', '?&'),
		));
	}
	private function __do_manage_favorite() {
		iPHP::assign('favorite', array(
			'fid' => (int) $_GET['fid'],
			'manage' => iPHP::router('user:manage:favorite', '?&'),
		));
	}

	private function __do_manage_publish() {
		$id = (int) $_GET['id'];
		iPHP::app('article.table');
		list($article, $article_data) = articleTable::data($id, 0, user::$userid);
		$cid = empty($article['cid']) ? (int) $_GET['cid'] : $article['cid'];

		if (iPHP_DEVICE !== "desktop" && empty($article)) {
			$article['mobile'] = "1";
		}

		iPHP::assign('article', $article);
		iPHP::assign('article_data', $article_data);
		iPHP::assign('option', $this->select('', $cid));
	}
	/**
	 * [ACTION_manage description]
	 */
	public function ACTION_manage() {
		$this->me = user::status($this->login_uri, "nologin");

		$pgArray = array('publish', 'category', 'article', 'comment', 'message', 'favorite', 'share', 'follow', 'fans');
		$pg = iS::escapeStr($_POST['pg']);
		$funname = '__action_manage_' . $pg;
		//print_r($funname);
		$methods = get_class_methods(__CLASS__);
		if (in_array($pg, $pgArray) && in_array($funname, $methods)) {
			$this->$funname();
		}
	}

	private function __action_manage_category() {
		$name_array = (array) $_POST['name'];
		$cid_array = (array) $_POST['_cid'];
		foreach ($name_array as $cid => $name) {
			$name = iS::escapeStr($name);
			iDB::update("user_category", array('name' => $name),
				array(
					'cid' => $cid,
					'uid' => user::$userid,
					'appid' => iCMS_APP_ARTICLE,
				)
			);
		}
		foreach ($cid_array as $key => $_cid) {
			if (!$name_array[$_cid]) {
				iDB::update("article", array('ucid' => '0'),
					array('userid' => user::$userid)
				);
				iDB::query("
                    DELETE FROM `#iCMS@__user_category`
                    WHERE `cid` = '$_cid'
                    AND `uid`='" . user::$userid . "'
                    AND `appid`='" . iCMS_APP_ARTICLE . "'
                ;");
			}
		}
		if ($_POST['newname']) {
			$_GET['callback'] = 'window.top.callback';
			$_GET['script'] = true;
			$_POST['name'] = $_POST['newname'];
			$this->ACTION_add_category();
		}

		iPHP::success('user:category:update', 'js:1');
	}
	private function __action_manage_publish() {
		$aid = (int) $_POST['id'];
		$cid = (int) $_POST['cid'];
		$_cid = (int) $_POST['_cid'];
		$ucid = (int) $_POST['ucid'];
		$_ucid = (int) $_POST['_ucid'];
		$mobile = (int) $_POST['mobile'];
		$title = iS::escapeStr($_POST['title']);
		$source = iS::escapeStr($_POST['source']);
		$keywords = iS::escapeStr($_POST['keywords']);
		$description = iS::escapeStr($_POST['description']);
		$creative = (int) $_POST['creative'];
		$userid = user::$userid;
		$author = user::$nickname;
		$editor = user::$nickname;

		if (iCMS::$config['user']['post']['seccode']) {
			$seccode = iS::escapeStr($_POST['seccode']);
			iPHP::core("Seccode");
			iSeccode::chcek($seccode, true) OR iPHP::alert('iCMS:seccode:error');
		}

		if (iCMS::$config['user']['post']['interval']) {
			$last_postime = iDB::value("
                SELECT MAX(postime)
                FROM `#iCMS@__article`
                WHERE userid='" . user::$userid . "' LIMIT 1;
            ");

			if ($_SERVER['REQUEST_TIME'] - $last_postime < iCMS::$config['user']['post']['interval']) {
				iPHP::alert('user:publish:interval');
			}
		}

		if ($mobile) {
			$_POST['body'] = ubb2html($_POST['body']);
			$_POST['body'] = trim($_POST['body']);
		}
		$body = iPHP::vendor('CleanHtml', array($_POST['body']));
		empty($title) && iPHP::alert('标题不能为空！');
		empty($cid) && iPHP::alert('请选择所属栏目！');
		empty($body) && iPHP::alert('文章内容不能为空！');

		$fwd = iCMS::filter($title);
		$fwd && iPHP::alert('user:publish:filter_title');
		$fwd = iCMS::filter($description);
		$fwd && iPHP::alert('user:publish:filter_desc');
		$fwd = iCMS::filter($body);
		$fwd && iPHP::alert('user:publish:filter_body');

		$articleApp = iPHP::app("admincp.article.app");

		if (empty($description)) {
			$description = $articleApp->autodesc($body);
		}

		$pubdate = time();
		$postype = "0";

		$category = iCache::get('iCMS/category/' . $cid);
		$status = $category['isexamine'] ? 3 : 1;

		iPHP::import(iPHP_APP_CORE . '/iMAP.class.php');
		iPHP::app('article.table');
		$fields = articleTable::fields($aid);
		$data_fields = articleTable::data_fields($aid);
		if (empty($aid)) {
			$postime = $pubdate;
			$chapter = $hits = $good = $bad = $comments = 0;

			$data = compact($fields);
			$aid = articleTable::insert($data);
			$article_data = compact($data_fields);
			articleTable::data_insert($article_data);

			map::init('category', iCMS_APP_ARTICLE);
			map::add($cid, $aid);
			iDB::query("
                UPDATE `#iCMS@__user_category`
                SET `count` = count+1
                WHERE `cid` = '$ucid'
                AND `uid`='" . user::$userid . "'
                AND `appid`='" . iCMS_APP_ARTICLE . "'
            ");
			user::update_count(user::$userid, 1, 'article');
			$lang = array(
				'1' => 'user:article:add_success',
				'3' => 'user:article:add_examine',
			);
		} else {
			if (articleTable::update(compact($fields),
				array('id' => $aid, 'userid' => user::$userid))) {
				articleTable::data_update(compact($data_fields), array('aid' => $aid));
			}
			map::init('category', iCMS_APP_ARTICLE);
			map::diff($cid, $_cid, $aid);
			if ($ucid != $_ucid) {
				iDB::query("
                    UPDATE `#iCMS@__user_category`
                    SET `count` = count+1
                    WHERE `cid` = '$ucid'
                    AND `uid`='" . user::$userid . "'
                    AND `appid`='" . iCMS_APP_ARTICLE . "'
                ");
				iDB::query("
                    UPDATE `#iCMS@__user_category`
                    SET `count` = count-1
                    WHERE `cid` = '$_ucid'
                    AND `uid`='" . user::$userid . "
                    AND `count`>0'
                    AND `appid`='" . iCMS_APP_ARTICLE . "'
                ");
			}
			$lang = array(
				'1' => 'user:article:update_success',
				'3' => 'user:article:update_examine',
			);
		}
		$url = iPHP::router('user:article');
		iPHP::success($lang[$status], 'url:' . $url);
	}
	private function __action_manage_article() {
		$actArray = array('delete', 'renew', 'trash');
		$act = iS::escapeStr($_POST['act']);
		if (in_array($act, $actArray)) {
			$id = (int) $_POST['id'];
			$id OR iPHP::code(0, 'iCMS:error', 0, 'json');
			$act == "delete" && $sql = "`status` ='2',`postype`='3'";
			$act == "renew" && $sql = "`status` ='1'";
			$act == "trash" && $sql = "`status` ='2'";
			$sql && iDB::query("
                UPDATE `#iCMS@__article`
                SET $sql
                WHERE `userid` = '" . user::$userid . "'
                AND `id`='$id'
                LIMIT 1;
            ");
			iPHP::code(1, 0, 0, 'json');
		}
	}
	private function __action_manage_comment() {
		$act = iS::escapeStr($_POST['act']);
		if ($act == "del") {
			$id = (int) $_POST['id'];
			$id OR iPHP::code(0, 'iCMS:error', 0, 'json');

			$comment = iDB::row("
                SELECT `appid`,`iid`
                FROM `#iCMS@__comment`
                WHERE `userid` = '" . user::$userid . "'
                AND `id`='$id'
            ");

			iPHP::app('apps.class', 'static');
			$table = APPS::get_table($comment->appid);

			iDB::query("
                UPDATE {$table['name']}
                SET comments = comments-1
                WHERE `comments`>0
                AND `{$table['primary']}`='{$comment->iid}'
                LIMIT 1
            ");

			iDB::query("
                DELETE FROM `#iCMS@__comment`
                WHERE `userid` = '" . user::$userid . "'
                AND `id`='$id' LIMIT 1
            ");
			user::update_count(user::$userid, 1, 'comments', '-');
			iPHP::code(1, 0, 0, 'json');
		}
	}
	private function __action_manage_message() {
		$act = iS::escapeStr($_POST['act']);
		if ($act == "del") {
			$id = (int) $_POST['id'];
			$id OR iPHP::code(0, 'iCMS:error', 0, 'json');

			$user = (int) $_POST['user'];
			if ($user) {
				iDB::query("
                    UPDATE `#iCMS@__message`
                    SET `status` ='0'
                    WHERE `userid` = '" . user::$userid . "'
                    AND `friend`='" . $user . "';
                ");
			} elseif ($id) {
				iDB::query("
                    UPDATE `#iCMS@__message`
                    SET `status` ='0'
                    WHERE `userid` = '" . user::$userid . "'
                    AND `id`='$id';
                ");
			}
			iPHP::code(1, 0, 0, 'json');
		}
	}
	private function __action_manage_favorite() {
		$actArray = array('delete');
		$act = iS::escapeStr($_POST['act']);
		if (in_array($act, $actArray)) {
			$id = (int) $_POST['id'];
			$id OR iPHP::code(0, 'iCMS:error', 0, 'json');
			iDB::query("
                DELETE
                FROM `#iCMS@__favorite_data`
                WHERE `uid` = '" . user::$userid . "'
                AND `id`='$id'
                LIMIT 1;
            ");
			iPHP::code(1, 0, 0, 'json');
		}
	}
	/**
	 * [ACTION_profile description]
	 */
	public function ACTION_profile() {
		$this->me = user::status($this->login_uri, "nologin");

		$pgArray = array('base', 'avatar', 'setpassword', 'bind', 'custom');
		$pg = iS::escapeStr($_POST['pg']);
		$funname = '__action_profile_' . $pg;
		$methods = get_class_methods(__CLASS__);
		if (in_array($pg, $pgArray) && in_array($funname, $methods)) {
			$this->$funname();
		}
	}
	private function __action_profile_base() {
		$nickname = iS::escapeStr($_POST['nickname']);
		$gender = iS::escapeStr($_POST['gender']);
		$weibo = iS::escapeStr($_POST['weibo']);
		$province = iS::escapeStr($_POST['province']);
		$city = iS::escapeStr($_POST['city']);
		$year = iS::escapeStr($_POST['year']);
		$month = iS::escapeStr($_POST['month']);
		$day = iS::escapeStr($_POST['day']);
		$constellation = iS::escapeStr($_POST['constellation']);
		$profession = iS::escapeStr($_POST['profession']);
		$isSeeFigure = iS::escapeStr($_POST['isSeeFigure']);
		$height = iS::escapeStr($_POST['height']);
		$weight = iS::escapeStr($_POST['weight']);
		$bwhB = iS::escapeStr($_POST['bwhB']);
		$bwhW = iS::escapeStr($_POST['bwhW']);
		$bwhH = iS::escapeStr($_POST['bwhH']);
		$pskin = iS::escapeStr($_POST['pskin']);
		$phair = iS::escapeStr($_POST['phair']);
		$shoesize = iS::escapeStr($_POST['shoesize']);
		$personstyle = iS::escapeStr($_POST['personstyle']);
		$slogan = iS::escapeStr($_POST['slogan']);

		$personstyle == iPHP::lang('user:profile:personstyle') && $personstyle = "";
		$slogan == iPHP::lang('user:profile:slogan') && $slogan = "";
		$pskin == iPHP::lang('user:profile:pskin') && $pskin = "";
		$phair == iPHP::lang('user:profile:phair') && $phair = "";

		// if($nickname!=user::$nickname){
		//     $has_nick = iDB::value("SELECT uid FROM `#iCMS@__user` where `nickname`='{$nickname}' AND `uid` <> '".user::$userid."'");
		//     $has_nick && iPHP::alert('user:profile:nickname');
		//     $userdata = user::data(user::$userid);
		//     if($userdata->unickEdit>1){
		//         iPHP::alert('user:profile:unickEdit');
		//     }
		//     if($nickname){
		//         iDB::update('user',array('nickname'=>$nickname),array('uid'=>user::$userid));
		//         $unickEdit = 1;
		//     }
		// }
		if ($gender != $this->me->gender) {
			iDB::update('user', array('gender' => $gender), array('uid' => user::$userid));
		}

		$uid = iDB::value("
            SELECT `uid`
            FROM `#iCMS@__user_data`
            WHERE `uid`='" . user::$userid . "' LIMIT 1;
        ");

		$fields = array(
			'weibo', 'province', 'city', 'year', 'month', 'day',
			'constellation', 'profession', 'isSeeFigure',
			'height', 'weight', 'bwhB', 'bwhW', 'bwhH', 'pskin',
			'phair', 'shoesize', 'personstyle', 'slogan', 'coverpic',
		);
		if ($uid) {
			$data = compact($fields);
			$unickEdit && $data['unickEdit'] = 1;
			iDB::update('user_data', $data, array('uid' => user::$userid));
		} else {
			$unickEdit = 0;
			$uid = user::$userid;
			$_fields = array(
				'uid', 'realname', 'unickEdit', 'mobile',
				'enterprise', 'address', 'zip', 'tb_nick',
				'tb_buyer_credit', 'tb_seller_credit', 'tb_type',
				'is_golden_seller',
			);
			$fields = array_merge($fields, $_fields);
			$data = compact($fields);
			iDB::insert('user_data', $data);
		}
		iPHP::success('user:profile:success');
	}
	private function __action_profile_custom() {
		iFS::$watermark = false;
		iFS::$checkFileData = false;
		$dir = get_user_dir(user::$userid, 'coverpic');
		$filename = user::$userid;
		if (iPHP_DEVICE != 'desktop') {
			$filename = 'm_' . user::$userid;
		}
		$F = iFS::upload('upfile', $dir, $filename, 'jpg');
		if (empty($F)) {
			if ($_POST['format'] == 'json') {
				iPHP::code(0, 'user:iCMS:error', 0, 'json');
			} else {
				iPHP::js_callback(array("code" => 0));
			}
		}
		$F OR iPHP::code(0, 'user:iCMS:error', 0, 'json');
		$F['code'] && iDB::update(
			'user_data',
			array('coverpic' => $F["path"]),
			array('uid' => user::$userid)
		);
		$url = iFS::fp($F['path'], '+http');
		if ($_POST['format'] == 'json') {
			iPHP::code(1, 'user:profile:custom', $url, 'json');
		}
		$array = array(
			"code" => $F["code"],
			"value" => $F["path"],
			"url" => $url,
			"fid" => $F["fid"],
			"fileType" => $F["ext"],
			"image" => in_array($F["ext"], array('gif', 'jpg', 'jpeg', 'png')) ? 1 : 0,
			"original" => $F["oname"],
			"state" => ($F['code'] ? 'SUCCESS' : $F['state']),
		);
		iPHP::js_callback($array);
	}
	private function __action_profile_avatar() {
		iFS::$watermark = false;
		iFS::$checkFileData = false;
		$dir = get_user_dir(user::$userid);
		$F = iFS::upload('upfile', $dir, user::$userid, 'jpg');
		if (empty($F)) {
			if ($_POST['format'] == 'json') {
				iPHP::code(0, 'user:iCMS:error', 0, 'json');
			} else {
				iPHP::js_callback(array("code" => 0));
			}
		}
		$url = iFS::fp($F['path'], '+http');
		if ($_POST['format'] == 'json') {
			iPHP::code(1, 'user:profile:avatar', $url, 'json');
		}
		$array = array(
			"code" => $F["code"],
			"value" => $F["path"],
			"url" => $url,
			"fid" => $F["fid"],
			"fileType" => $F["ext"],
			"image" => in_array($F["ext"], array('gif', 'jpg', 'jpeg', 'png')) ? 1 : 0,
			"original" => $F["oname"],
			"state" => ($F['code'] ? 'SUCCESS' : $F['state']),
		);
		iPHP::js_callback($array);
	}

	private function __action_profile_setpassword() {

		iPHP::core("Seccode");
		iSeccode::chcek($_POST['seccode'], true) OR iPHP::alert('iCMS:seccode:error');

		$oldPwd = md5($_POST['oldPwd']);
		$newPwd1 = md5($_POST['newPwd1']);
		$newPwd2 = md5($_POST['newPwd2']);

		$newPwd1 != $newPwd2 && iPHP::alert("user:password:unequal");

		$password = iDB::value("
            SELECT `password`
            FROM `#iCMS@__user`
            WHERE `uid`='" . user::$userid . "' LIMIT 1;
        ");
		$oldPwd != $password && iPHP::alert("user:password:original");
		iDB::query("
            UPDATE `#iCMS@__user`
            SET `password` = '$newPwd1'
            WHERE `uid` = '" . user::$userid . "';
        ");
		iPHP::alert("user:password:modified", 'js:parent.location.reload();');
	}
	public function ACTION_findpwd() {
		$seccode = iS::escapeStr($_POST['seccode']);
		iPHP::core("Seccode");
		iSeccode::chcek($seccode, true) OR iPHP::code(0, 'iCMS:seccode:error', 'seccode', 'json');

		$uid = (int) $_POST['uid'];
		$auth = iS::escapeStr($_POST['auth']);
		if ($auth && $uid) {
			//print_r($_POST);
			$authcode = rawurldecode($auth);
			$authcode = base64_decode($authcode);
			$authcode = authcode($authcode);

			if (empty($authcode)) {
				iPHP::code(0, 'user:findpwd:error', 'uname', 'json');
			}
			list($uid, $username, $password, $timeline) = explode(USER_AUTHASH, $authcode);
			$now = time();
			if ($now - $timeline > 86400) {
				iPHP::code(0, 'user:findpwd:error', 'time', 'json');
			}
			$user = user::get($uid, false);
			if ($username != $user->username || $password != $user->password) {
				iPHP::code(0, 'user:findpwd:error', 'user', 'json');
			}
			$rstpassword = md5(trim($_POST['rstpassword']));
			if ($rstpassword == $user->password) {
				iPHP::code(0, 'user:findpwd:same', 'password', 'json');
			}
			iDB::update("user", array('password' => $rstpassword), array('uid' => $uid));
			iPHP::code(1, 'user:findpwd:success', 0, 'json');
		} else {
			$uname = iS::escapeStr($_POST['uname']);
			$uname OR iPHP::code(0, 'user:findpwd:username:empty', 'uname', 'json');
			$uid = user::check($uname, 'username');
			$uid OR iPHP::code(0, 'user:findpwd:username:noexist', 'uname', 'json');
			$user = user::get($uid, false);
			$user OR iPHP::code(0, 'user:findpwd:username:noexist', 'uname', 'json');

			$authcode = authcode($uid .
				USER_AUTHASH . $user->username .
				USER_AUTHASH . $user->password .
				USER_AUTHASH . time(),
				'ENCODE'
			);
			$authcode = base64_encode($authcode);
			$authcode = rawurlencode($authcode);
			$find_url = iPHP::router('api:user:findpwd', '?&');
			if (iPHP_ROUTER_REWRITE) {
				$find_url = iFS::fp($find_url, '+http');
			}
			$find_url .= 'auth=' . $authcode;
			$config = iCMS::$config['mail'];
			$config['title'] = iCMS::$config['site']['name'];
			$config['subject'] = '[' . $config['title'] . '] 找回密码（重要）！';
			$config['body'] = '
            <p>尊敬的' . $user->nickname . '，您好：</p>
            <br />
            <p>您在' . $config['title'] . '申请找回密码，重设密码地址：</p>
            <a href="' . $find_url . '" target="_blank">' . $find_url . '</a>
            <p>本链接将在24小时后失效！</p>
            <p>如果上面的链接无法点击，您也可以复制链接，粘贴到您浏览器的地址栏内，然后按“回车”打开重置密码页面。</p>
            <p>如果您有其他问题，请联系我们：' . $config['replyto'] . '。</p>
            <p>如果您没有进行过找回密码的操作，请不要点击上述链接，并删除此邮件。</p>
            <p>谢谢！</p>
            ';
			$config['address'] = array(
				array($user->username, $user->nickname),
			);
			//var_dump(iCMS::$config);
			$result = iPHP::vendor('SendMail', array($config));
			if ($result === true) {
				iPHP::code(1, 'user:findpwd:send:success', 'mail', 'json');
			} else {
				iPHP::code(0, 'user:findpwd:send:failure', 'mail', 'json');
			}
		}
	}
	public function ACTION_login() {
		iCMS::$config['user']['login']['enable'] OR iPHP::code(0, 'user:login:forbidden', 'uname', 'json');

		$uname = iS::escapeStr($_POST['uname']);
		$pass = md5(trim($_POST['pass']));
		$remember = (bool) $_POST['remember'] ? ture : false;

		$openid = iS::escapeStr($_POST['openid']);
		$platform = iS::escapeStr($_POST['platform']);

		if (iCMS::$config['user']['login']['seccode']) {
			$seccode = iS::escapeStr($_POST['seccode']);
			iPHP::core("Seccode");
			iSeccode::check($seccode, true) OR iPHP::code(0, 'iCMS:seccode:error', 'seccode', 'json');
		}
		$remember && user::$cookietime = 14 * 86400;
		$user = user::login($uname, $pass, (strpos($uname, '@') === false ? 'nk' : 'un'));
		if ($user === true) {
			if ($openid) {
				iDB::query("
                    INSERT INTO `#iCMS@__user_openid`
                           (`uid`, `openid`, `platform`)
                    VALUES ('" . user::$userid . "', '$openid', '$platform');
                ");
			}
			iPHP::code(1, 0, $this->forward, 'json');
		} else {
			if (iCMS::$config['user']['login']['interval']) {
				$cache_name = "iCMS/error/login." . md5($uname);
				$login_error = iCache::get($cache_name);
				if ($login_error) {
					if ($login_error[1] >= 5) {
						$_field = (strpos($uname, '@') === false ? 'nickname' : 'username');
						iDB::update('user', array('status' => '3'), array($_field => $uname));
						iPHP::code(0, 'user:login:interval', 'uname', 'json');
					} else {
						$login_error[1]++;
					}
				} else {
					$login_error = array($uname, 1);
				}
				iCache::set($cache_name, $login_error, iCMS::$config['user']['login']['interval']);
			}
			// $lang = 'user:login:error';
			// $user && $lang.='_status_'.$user;
			iPHP::code(0, 'user:login:error', 'uname', 'json');
		}
	}

	public function ACTION_register() {
		iCMS::$config['user']['register']['enable'] OR exit(iPHP::lang('user:register:forbidden'));

		$regip = iS::escapeStr(iPHP::getIp());
		$regdate = time();

		if (iCMS::$config['user']['register']['interval']) {
			$ip_regdate = iDB::value("
                SELECT `regdate`
                FROM `#iCMS@__user`
                WHERE `regip`='$regip'
                ORDER BY uid DESC LIMIT 1;");

			if ($ip_regdate - $regdate > iCMS::$config['user']['register']['interval']) {
				iPHP::code(0, 'user:register:interval', 'username', 'json');
			}
		}

		$username = iS::escapeStr($_POST['username']);
		$nickname = iS::escapeStr($_POST['nickname']);
		$gender = ($_POST['gender'] == 'girl' ? 0 : 1);
		$password = md5(trim($_POST['password']));
		$rstpassword = md5(trim($_POST['rstpassword']));
		$refer = iS::escapeStr($_POST['refer']);

		$openid = iS::escapeStr($_POST['openid']);
		$type = iS::escapeStr($_POST['platform']);
		$avatar = iS::escapeStr($_POST['avatar']);

		$province = iS::escapeStr($_POST['province']);
		$city = iS::escapeStr($_POST['city']);

		$agreement = $_POST['agreement'];

		$username OR iPHP::code(0, 'user:register:username:empty', 'username', 'json');
		preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/i", $username) OR iPHP::code(0, 'user:register:username:error', 'username', 'json');
		user::check($username, 'username') && iPHP::code(0, 'user:register:username:exist', 'username', 'json');

		$nickname OR iPHP::code(0, 'user:register:nickname:empty', 'nickname', 'json');
		(cstrlen($nickname) > 20 || cstrlen($nickname) < 4) && iPHP::code(0, 'user:register:nickname:error', 'nickname', 'json');
		user::check($nickname, 'nickname') && iPHP::code(0, 'user:register:nickname:exist', 'nickname', 'json');

		trim($_POST['password']) OR iPHP::code(0, 'user:password:empty', 'password', 'json');
		trim($_POST['rstpassword']) OR iPHP::code(0, 'user:password:rst_empty', 'rstpassword', 'json');
		$password == $rstpassword OR iPHP::code(0, 'user:password:unequal', 'password', 'json');

		if (iCMS::$config['user']['register']['seccode']) {
			$seccode = iS::escapeStr($_POST['seccode']);
			iPHP::core("Seccode");
			iSeccode::chcek($seccode, true) OR iPHP::code(0, 'iCMS:seccode:error', 'seccode', 'json');
		}

		$gid = 0;
		$pid = 0;
		$fans = $follow = $article = $comments = $share = $credit = 0;
		$hits = $hits_today = $hits_yday = $hits_week = $hits_month = 0;
		$lastloginip = $lastlogintime = '';
		$status = 1;
		$fields = array(
			'gid', 'pid', 'username', 'nickname', 'password',
			'gender', 'fans', 'follow', 'article', 'comments',
			'share', 'credit', 'regip', 'regdate', 'lastloginip',
			'lastlogintime', 'hits', 'hits_today', 'hits_yday', 'hits_week',
			'hits_month', 'type', 'status',
		);
		$data = compact($fields);
		$uid = iDB::insert('user', $data);

		user::set_cookie(
			$username,
			$password,
			array('uid' => $uid,
				'username' => $username,
				'nickname' => $nickname,
				'status' => $status,
			)
		);

		if ($openid) {
			$platform = $type;
			iDB::query("
                INSERT INTO `#iCMS@__user_openid`
                       (`uid`, `openid`, `platform`)
                VALUES ('$uid', '$openid', '$platform');
            ");
		}
		if ($avatar) {
			$avatarData = iFS::remote($avatar);
			if ($avatarData) {
				$avatarpath = iFS::fp(get_user_pic($uid), '+iPATH');
				iFS::mkdir(dirname($avatarpath));
				iFS::write($avatarpath, $avatarData);
				iFS::yun_write($avatarpath);
			}
		}

		//user::set_cache($uid);
		iPHP::set_cookie('forward', '', -31536000);
		iPHP::json(array('code' => 1, 'forward' => $this->forward));
	}
	public function ACTION_add_category() {
		$uid = user::$userid;
		$name = iS::escapeStr($_POST['name']);
		empty($name) && iPHP::code(0, 'user:category:empty', 'add_category', 'json');
		$fwd = iCMS::filter($name);
		$fwd && iPHP::code(0, 'user:category:filter', 'add_category', 'json');
		$max = iDB::value("
            SELECT COUNT(cid)
            FROM `#iCMS@__user_category`
            WHERE `uid`='$uid'
            AND `appid`='" . iCMS_APP_ARTICLE . "' LIMIT 1;"
		);
		$max >= 10 && iPHP::code(0, 'user:category:max', 'add_category', 'json');
		$count = 0;
		$appid = iCMS_APP_ARTICLE;
		$fields = array('uid', 'name', 'description', 'count', 'mode', 'appid');
		$data = compact($fields);
		$cid = iDB::insert('user_category', $data);
		$cid && iPHP::code(1, 'user:category:success', $cid, 'json');
		iPHP::code(0, 'user:category:failure', 0, 'json');
	}
	public function ACTION_report() {
		$this->auth OR iPHP::code(0, 'iCMS:!login', 0, 'json');

		$iid = (int) $_POST['iid'];
		$uid = (int) $_POST['userid'];
		$appid = (int) $_POST['appid'];
		$reason = (int) $_POST['reason'];
		$content = iS::escapeStr($_POST['content']);

		$iid OR iPHP::code(0, 'iCMS:error', 0, 'json');
		$uid OR iPHP::code(0, 'iCMS:error', 0, 'json');
		$reason OR $content OR iPHP::code(0, 'iCMS:report:empty', 0, 'json');

		$addtime = time();
		$ip = iPHP::getIp();
		$userid = user::$userid;
		$status = 0;

		$fields = array('appid', 'userid', 'iid', 'uid', 'reason', 'content', 'ip', 'addtime', 'status');
		$data = compact($fields);
		$id = iDB::insert('user_report', $data);
		iPHP::code(1, 'iCMS:report:success', $id, 'json');
	}
	public function ACTION_pm() {
		$this->auth OR iPHP::code(0, 'iCMS:!login', 0, 'json');

		$receiv_uid = (int) $_POST['uid'];
		$content = iS::escapeStr($_POST['content']);

		$receiv_uid OR iPHP::code(0, 'iCMS:error', 0, 'json');
		$content OR iPHP::code(0, 'iCMS:pm:empty', 0, 'json');

		$receiv_name = iS::escapeStr($_POST['name']);
		$send_uid = user::$userid;
		$send_name = user::$nickname;

		$fields = array('send_uid', 'send_name', 'receiv_uid', 'receiv_name', 'content');
		$data = compact($fields);
		msg::send($data, 1);
		iPHP::code(1, 'iCMS:pm:success', $id, 'json');
	}
	public function ACTION_follow() {
		$this->auth OR iPHP::code(0, 'iCMS:!login', 0, 'json');

		$uid = (int) user::$userid;
		$name = user::$nickname;
		$fuid = (int) $_POST['uid'];
		$fname = iS::escapeStr($_POST['name']);
		$follow = (bool) $_POST['follow'];

		$uid OR iPHP::code(0, 'iCMS:error', 0, 'json');
		$fuid OR iPHP::code(0, 'iCMS:error', 0, 'json');

		if ($follow) {
			//1 取消关注
			iDB::query("
                DELETE FROM `#iCMS@__user_follow`
                WHERE `uid` = '$uid'
                AND `fuid`='$fuid'
                LIMIT 1;
            ");
			user::update_count($uid, 1, 'follow', '-');
			user::update_count($fuid, 1, 'fans', '-');
			iPHP::code(1, 0, 0, 'json');
		} else {
			$uid == $fuid && iPHP::code(0, 'user:follow:self', 0, 'json');
			$check = user::follow($uid, $fuid);
			if ($check) {
				iPHP::code(1, 'user:follow:success', 0, 'json');
			} else {
				$fields = array('uid', 'name', 'fuid', 'fname');
				$data = compact($fields);
				iDB::insert('user_follow', $data);
				user::update_count($uid, 1, 'follow');
				user::update_count($fuid, 1, 'fans');
				iPHP::code(1, 'user:follow:success', 0, 'json');
			}
		}
	}
	public function ACTION_favorite() {
		$this->auth OR iPHP::code(0, 'iCMS:!login', 0, 'json');

		$uid = user::$userid;
		$appid = (int) $_POST['appid'];
		$iid = (int) $_POST['iid'];
		$cid = (int) $_POST['cid'];
		$url = iS::escapeStr($_POST['url']);
		$title = iS::escapeStr($_POST['title']);
		$addtime = time();

		$url OR iPHP::code(0, 'iCMS:favorite:url', 0, 'json');

		if (iDB::value("
            SELECT `id`FROM `#iCMS@__user_favorite`
            where `uid`='" . user::$userid . "'
            AND `url`='$url' LIMIT 1;")) {
			iPHP::code(0, 'iCMS:favorite:failure', 0, 'json');
		}

		$fields = array('uid', 'appid', 'cid', 'url', 'title', 'addtime');
		$data = compact($fields);
		$cid = iDB::insert('user_favorite', $data);

		iDB::query("
            UPDATE `#iCMS@__article`
            SET `favorite`=favorite+1
            WHERE `id` ='{$aid}'
            limit 1
        ");
		iPHP::code(1, 'iCMS:favorite:success', 0, 'json');
	}

	public function API_hits($uid = null) {
		$uid === null && $uid = (int) $_GET['uid'];
		if ($uid) {
			$sql = iCMS::hits_sql();
			iDB::query("UPDATE `#iCMS@__user` SET {$sql} WHERE `uid` ='$uid'");
		}
	}
	public function API_check() {
		$name = iS::escapeStr($_GET['name']);
		$value = iS::escapeStr($_GET['value']);
		$a = iPHP::code(1, '', $name);
		switch ($name) {
		case 'username':
			if (!preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/i", $value)) {
				$a = iPHP::code(0, 'user:register:username:error', 'username');
			} else {
				if (user::check($value, 'username')) {
					$a = iPHP::code(0, 'user:register:username:exist', 'username');
				}
			}
			break;
		case 'nickname':
			if (preg_match("/\d/", $value[0]) || cstrlen($value) > 20 || cstrlen($value) < 4) {
				$a = iPHP::code(0, 'user:register:nickname:error', 'nickname');
			} else {
				if (user::check($value, 'nickname')) {
					$a = iPHP::code(0, 'user:register:nickname:exist', 'nickname');
				}
			}
			break;
		case 'password':
			strlen($value) < 6 && $a = iPHP::code(0, 'user:password:error', 'password');
			break;
		case 'seccode':
			iPHP::core("Seccode");
			iSeccode::chcek($value) OR $a = iPHP::code(0, 'iCMS:seccode:error', 'seccode');
			break;
		}
		iPHP::json($a);
	}

	public function API_register() {
		if (iCMS::$config['user']['register']['enable']) {
			iPHP::set_cookie('forward', $this->forward);
			user::status($this->forward, "login");
			iPHP::view('iCMS://user/register.htm');
		} else {
			iPHP::view('iCMS://user/register.close.htm');
		}
	}
	public function API_data($uid = 0) {
		$user = user::status();
		if ($user) {
			$array = array(
				'code' => 1,
				'uid' => $user->uid,
				'url' => $user->url,
				'avatar' => $user->avatar,
				'nickname' => $user->nickname,
			);
			iPHP::json($array);
		} else {
			user::logout();
			iPHP::code(0, 0, $this->forward, 'json');
		}
	}
	public function API_logout() {
		user::logout();
		iPHP::code(1, 0, $this->forward, 'json');
	}
	public function API_findpwd() {
		$auth = iS::escapeStr($_GET['auth']);
		if ($auth) {
			$authcode = rawurldecode($auth);
			$authcode = base64_decode($authcode);
			$authcode = authcode($authcode);

			if (empty($authcode)) {
				exit;
			}
			list($uid, $username, $password, $timeline) = explode(USER_AUTHASH, $authcode);
			$now = time();
			if ($now - $timeline > 86400) {
				exit;
			}
			$user = user::get($uid, false);
			if ($username != $user->username || $password != $user->password) {
				exit;
			}
			unset($user->password);
			iPHP::assign('auth', $auth);
			iPHP::assign('user', (array) $user);
			iPHP::view('iCMS://user/resetpwd.htm');
		} else {
			iPHP::view('iCMS://user/findpwd.htm');
		}
	}
	public function API_login() {
		if (iCMS::$config['user']['login']['enable']) {
			$this->openid();
			iPHP::set_cookie('forward', $this->forward);
			user::status($this->forward, "login");
			iPHP::view('iCMS://user/login.htm');
		} else {
			iPHP::view('iCMS://user/login.close.htm');
		}
	}
	public function API_config() {
		$this->auth OR iPHP::code(0, 'iCMS:!login', 0, 'json');
		$editorApp = iPHP::app("admincp.editor.app");
		$editorApp->do_config();
	}
	public function API_catchimage() {
		$this->auth OR iPHP::code(0, 'iCMS:!login', 0, 'json');
		$editorApp = iPHP::app("admincp.editor.app");
		$editorApp->do_catchimage();
	}
	public function API_uploadimage() {
		$this->auth OR iPHP::code(0, 'iCMS:!login', 0, 'json');
		$editorApp = iPHP::app("admincp.editor.app");
		$editorApp->do_uploadimage();
	}
	public function API_uploadvideo() {
		$this->auth OR iPHP::code(0, 'iCMS:!login', 0, 'json');
		$editorApp = iPHP::app("admincp.editor.app");
		$editorApp->do_uploadvideo();
	} //手机上传
	public function API_mobileUp() {
		$this->auth OR iPHP::code(0, 'iCMS:!login', 0, 'json');
		$F = iFS::upload('upfile');
		$F['path'] && $url = iFS::fp($F['path'], '+http');
		iPHP::js_callback(array(
			'url' => $url,
			'code' => $F['code'],
		));
	}
	public function API_collections() {

		//iPHP::view('iCMS://user/card.htm');
	}
	public function API_ucard() {
		$this->user(true);
		if ($this->auth) {
			$secondary = $this->__secondary();
			iPHP::assign('secondary', $secondary);
		}
		iPHP::view('iCMS://user/card.htm');
	}

	private function __secondary() {
		if ($this->uid == user::$userid) {
			return;
		}

		$follow = user::follow(user::$userid, 'all'); //你的所有关注者
		$fans = user::follow('all', $this->uid); //他的所有粉丝
		$links = array();
		foreach ((array) $fans as $uid => $name) {
			if ($follow[$uid]) {
				$url = user::router($uid, "url");
				$links[$uid] = '<a href="' . $url . '" class="user-link" title="' . $name . '">' . $name . '</a>';
			}
		}
		if (empty($links)) {
			return;
		}
		$_count = count($links);
		$text = ' 也关注Ta';
		if ($_count > 3) {
			$links = array_slice($links, 0, 3);
			$text = ' 等 ' . $_count . ' 人也关注Ta';
		}
		return implode('、', $links) . $text;
	}

	public function select($permission = '', $_cid = "0", $cid = "0", $level = 1) {
		$array = iCache::get('iCMS/category.' . iCMS_APP_ARTICLE . '/array');
		foreach ((array) $array[$cid] AS $root => $C) {
			if ($C['status'] && $C['isucshow'] && $C['issend'] && empty($C['outurl'])) {
				$tag = ($level == '1' ? "" : "├ ");
				$selected = ($_cid == $C['cid']) ? "selected" : "";
				$text = str_repeat("│　", $level - 1) . $tag . $C['name'] . "[cid:{$C['cid']}]" . ($C['outurl'] ? "[∞]" : "");
				$C['isexamine'] && $text .= '[审核]';
				$option .= "<option value='{$C['cid']}' $selected>{$text}</option>";
			}
			$array[$C['cid']] && $option .= $this->select($permission, $_cid, $C['cid'], $level + 1, $url);
		}
		return $option;
	}
	public function openid() {
		if (!isset($_GET['sign'])) {
			return;
		}
		$sign = $_GET['sign'];
		$code = $_GET['code'];
		$state = $_GET['state'];
		$platform_map = array('WX' => 1, 'QQ' => 2, 'WB' => 3, 'TB' => 4);
		$class_name = strtoupper($sign);
		$platform = $platform_map[$class_name];
		$bind = $sign;

		if ($platform) {
			iPHP::app('user.open/' . $class_name . '.class', 'static');
			$api = new $class_name;
			$api->appid = iCMS::$config['open'][$class_name]['appid'];
			$api->appkey = iCMS::$config['open'][$class_name]['appkey'];
			$redirect_uri = rtrim(iCMS::$config['open'][$class_name]['redirect'], '/');
			$api->url = user::login_uri($redirect_uri) . 'sign=' . $sign;

			if (isset($_GET['bind']) && $_GET['bind'] == $sign) {
				$api->get_openid();
			} else {
				$api->callback();
			}

			$userid = user::openid($api->openid, $platform);
			if ($userid) {
				$user = user::get($userid, false);
				user::set_cookie($user->username, $user->password, array(
					'uid' => $userid,
					'username' => $user->username,
					'nickname' => $user->nickname,
					'status' => $user->status,
				)
				);
				$api->cleancookie();
				iPHP::gotourl($this->forward);
			} else {
				if (isset($_GET['bind'])) {
					$user = array();
					$user['openid'] = $api->openid;
					$user['platform'] = $platform;
					$api->cleancookie();
					iPHP::assign('user', $user);
					iPHP::view('iCMS://user/login.htm');
				} else {
					$user = $api->get_user_info();
					$user['openid'] = $api->openid;
					$user['platform'] = $platform;
					if (iDB::value("
                        SELECT `uid`
                        FROM `#iCMS@__user`
                        where `nickname`='" . $user['nickname'] . "' LIMIT 1
                        ")) {
						$user['nickname'] = $sign . '_' . $user['nickname'];
					}
					iPHP::assign('user', $user);
					iPHP::assign('query', compact(array('sign', 'code', 'state', 'bind')));
					iPHP::view('iCMS://user/register.htm');
				}
				exit;
			}
		}
	}
}
