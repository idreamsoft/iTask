<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
 *
 * @author coolmoo <idreamsoft@qq.com>
 * @site http://www.idreamsoft.com
 * @licence http://www.idreamsoft.com/license.php
 * @version 6.0.0
 * @$Id: admincp.class.php 2361 2014-02-22 01:52:39Z coolmoo $
 */
defined('iPHP') OR exit('What are you doing?');

define('iCMS_SUPERADMIN_UID', '1');
define('__ADMINCP__', __SELF__ . '?app');
define('ACP_PATH', iPHP_APP_DIR . '/admincp');
define('ACP_HOST', "http://" . $_SERVER['HTTP_HOST']);

iDB::$show_errors = true;
iPHP::$dialog['title'] = 'iCMS';

iCMS::core('Menu');
iCMS::core('Member');

iMember::$LOGIN_TPL = ACP_PATH;
iMember::$AUTH = 'ADMIN_AUTH';
iMember::$AJAX = iPHP::PG('ajax');

$_GET['do'] == 'seccode' && admincp::get_seccode();

class admincp {
	public static $apps = NULL;
	public static $menu = NULL;
	public static $app = NULL;
	public static $APP_NAME = NULL;
	public static $APP_DO = NULL;
	public static $APP_METHOD = NULL;
	public static $APP_PATH = NULL;
	public static $APP_TPL = NULL;
	public static $APP_FILE = NULL;
	public static $APP_DIR = NULL;
	public static $APP_ARGS = NULL;

	public static function init() {
		// self::check_seccode(); //验证码验证
		iMember::checkLogin(); //用户登陆验证
		self::$menu = new iMenu(); //初始化菜单
		self::MP('ADMINCP', 'page'); //检查是否有后台权限
		self::MP('__MID__', 'page'); //检查菜单ID
		iFS::$userid = iMember::$userid;
	}

	public static function get_seccode() {
		iPHP::core("Seccode");
		iSeccode::run('iACP');
		exit;
	}
	public static function check_seccode() {
		if ($_POST['iACP_seccode'] === iPHP_KEY) {
			return true;
		}

		if ($_POST['username'] && $_POST['password']) {
			iPHP::core("Seccode");
			$seccode = iS::escapeStr($_POST['iACP_seccode']);
			iSeccode::check($seccode, true, 'iACP_seccode') OR iPHP::code(0, 'iCMS:seccode:error', 'seccode', 'json');
		}
	}

	public static function run($args = NULL, $prefix = "do_") {
		self::init();
		$app = $_GET['app'];
		$app OR $app = 'home';
		//in_array($app, self::$apps) OR iPHP::throwException('运行出错！找不到应用程序:' . $app, 1001);
		$do OR $do = $_GET['do'] ? (string) $_GET['do'] : 'iCMS';
		if ($_POST['action']) {
			$do = $_POST['action'];
			$prefix = 'ACTION_';
		}

		self::$APP_NAME = $app;
		self::$APP_DO = $do;
		self::$APP_METHOD = $prefix . $do;
		self::$APP_PATH = ACP_PATH;
		self::$APP_TPL = ACP_PATH . '/template';
		self::$APP_FILE = ACP_PATH . '/' . $app . '.app.php';

		// $ownAdmincp = APPS::check($app,"admincp");
		// if ($ownAdmincp) {
		// 	self::$APP_PATH = iPHP_APP_DIR . '/' . $app;
		// 	self::$APP_TPL = self::$APP_PATH . '/admincp';
		// 	self::$APP_FILE = self::$APP_PATH . '/' . $app . '.admincp.php';
		// }
		strpos($app, '..') === false OR exit('what the fuck');

		define('APP_URI', __ADMINCP__ . '=' . $app);
		define('APP_FURI', APP_URI . '&frame=iPHP');
		define('APP_DOURI', APP_URI . ($do != 'iCMS' ? '&do=' . $do : ''));
		define('APP_BOXID', self::$APP_NAME . '-box');
		define('APP_FORMID', 'iCMS-' . APP_BOXID);

		is_file(self::$APP_FILE) OR iPHP::throwException('运行出错！找不到文件: <b>' . self::$APP_NAME . '.app.php</b>', 1002);
		iPHP::import(self::$APP_FILE);
		$appName = self::$APP_NAME . 'App';
		$ownAdmincp && $appName = self::$APP_NAME . 'Admincp';
		self::$app = new $appName();
		$app_methods = get_class_methods($appName);
		in_array(self::$APP_METHOD, $app_methods) OR iPHP::throwException('运行出错！ <b>' . self::$APP_NAME . '</b> 类中找不到方法定义: <b>' . self::$APP_METHOD . '</b>', 1003);
		$method = self::$APP_METHOD;
		$args === null && $args = self::$APP_ARGS;

		if ($args) {
			if ($args === 'object') {
				return self::$app;
			}
			return self::$app->$method($args);
		} else {
			return self::$app->$method();
		}
	}

	public static function app($app = NULL, $arg = NULL) {
		iPHP::import(ACP_PATH . '/' . $app . '.app.php');
		if ($arg === 'import' || $arg === 'static') {
			return;
		}
		$appName = $app . 'App';
		if ($arg !== NULL) {
			return new $appName($arg);
		}
		return new $appName();
	}

	public static function view($p = NULL, $base = false) {
		if ($p === NULL && self::$APP_NAME) {
			$p = self::$APP_NAME;
			self::$APP_DO && $p .= '.' . self::$APP_DO;
		}
		$path = self::$APP_TPL . '/' . $p . '.php';
		$base && $path = ACP_PATH . '/template/' . $p . '.php';
		return $path;
	}

	public static function fields($data = '') {
		$fields = array();
		$dA = explode('_', $data);
		foreach ((array) $dA as $d) {
			list($f, $v) = explode(':', $d);
			$v == 'now' && $v = time();
			$v = (int) $v;
			$fields[$f] = $v;
		}
		return $fields;
	}
	public static function MP($p, $ret = '') {
		if (self::is_superadmin()) {
			return true;
		}

		self::$menu->power = (array) iMember::$mpower;
		if ($p === '__MID__') {
			$rt1 = $rt2 = $rt3 = true;
			self::$menu->rootid && $rt1 = self::$menu->check_power(self::$menu->rootid);
			self::$menu->parentid && $rt2 = self::$menu->check_power(self::$menu->parentid);
			self::$menu->do_mid && $rt3 = self::$menu->check_power(self::$menu->do_mid);
			if ($rt1 && $rt2 && $rt3) {
				return true;
			}
			self::permission_msg($p, $ret);
		}
		$rt = self::$menu->check_power($p);
		$rt OR self::permission_msg($p, $ret);
		return $rt;
	}
	public static function CP($p, $act = '', $ret = '') {
		if (self::is_superadmin()) {
			return true;
		}

		if ($p === '__CID__') {
			foreach ((array) iMember::$cpower as $key => $_cid) {
				if (!strstr($value, ':')) {
					self::CP($_cid, $act) && $cids[] = $_cid;
				}
			}
			return $cids;
		}

		$act && $p = $p . ':' . $act;

		$rt = iMember::check_power((string) $p, iMember::$cpower);
		$rt OR self::permission_msg($p, $ret);
		return $rt;
	}
	public static function permission_msg($p = '', $ret = '') {
		if ($ret == 'alert') {
			iPHP::alert('您没有相关权限!');
			exit;
		} elseif ($ret == 'page') {
			include self::view("admincp.permission", true);
			exit;
		}
	}
	public static function is_superadmin() {
		return (iMember::$data->gid === iCMS_SUPERADMIN_UID);
	}
	public static function head($navbar = true) {
		$body_class = '';
		if (iCMS::$config['other']['sidebar_enable']) {
			iCMS::$config['other']['sidebar'] OR $body_class = 'sidebar-mini';
			$body_class = iPHP::get_cookie('ACP_sidebar_mini') ? 'sidebar-mini' : '';
		} else {
			$body_class = 'sidebar-display';
		}
		$navbar === false && $body_class = 'iframe ';

		include self::view("admincp.header", true);
		$navbar === true && include self::view("admincp.navbar", true);
	}

	public static function foot() {
		include self::view("admincp.footer", true);
	}
	public static function picBtnGroup($callback, $indexid = 0, $type = 'pic') {
		include self::view("admincp.picBtnGroup", true);
	}

	public static function getProp($field, $val = NULL, /*$default=array(),*/ $out = 'option', $url = "", $type = "") {
		return iPHP::app('prop.admincp')->get_prop($field, $val, $out, $url, $type);
	}
	public static function files_modal_btn($title = '', $click = 'file', $target = 'template_index', $callback = '', $do = 'seltpl', $from = 'modal') {
		$filesApp = admincp::app('files');
		$filesApp->modal_btn($title, $click, $target, $callback, $do, $from);
	}
	public static function callback($id, &$that, $type = null) {
		if ($type === null || $type == 'primary') {
			if ($that->callback['primary']) {
				$PCB = $that->callback['primary'];
				$handler = $PCB[0];
				$params = (array) $PCB[1] + array('indexid' => $id);
				if (is_callable($handler)) {
					call_user_func_array($handler, $params);
				}
			}
		}
		if ($type === null || $type == 'data') {
			if ($that->callback['data']) {
				$DCB = $that->callback['data'];
				$handler = $DCB[0];
				$params = (array) $DCB[1];
				if (is_callable($handler)) {
					call_user_func_array($handler, $params);
				}
			}
		}

	}
}
