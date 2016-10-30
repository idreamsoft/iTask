<?php
/**
 * iPHP - i PHP Framework
 * Copyright (c) 2012 iiiphp.com. All rights reserved.
 *
 * @author coolmoo <iiiphp@qq.com>
 * @site http://www.iiiphp.com
 * @licence http://www.iiiphp.com/license
 * @version 1.0.1
 * @package common
 * @$Id: iPHP.php 2330 2014-01-03 05:19:07Z coolmoo $
 */
defined('iPHP') OR exit('What are you doing?');

class iPHP {
	public static $apps       = null;
	public static $app        = null;
	public static $app_name   = null;
	public static $app_do     = null;
	public static $app_method = null;
	public static $app_tpl    = null;
	public static $app_path   = null;
	public static $app_file   = null;
	public static $app_args   = null;
	public static $app_vars   = null;
	public static $config     = array();
	public static $hooks      = array();

	public static $pagenav    = NULL;
	public static $offset     = NULL;
	public static $break      = true;
	public static $dialog     = array();
	public static $iTPL       = NULL;
	public static $iTPL_MODE  = null;
	public static $mobile     = false;
	public static $time_start = false;

	public static function run($app = NULL, $do = NULL, $args = NULL, $prefix = "do_") {
		//empty($app) && $app   = $_GET['app']; //单一入口
		if (empty($app)) {
			$fi = iFS::name(__SELF__);
			$app = $fi['name'];
		}

		if (!in_array($app, (array)self::$apps) && iPHP_DEBUG) {
			iPHP::throw404('运行出错！找不到应用程序: <b>' . $app . '</b>', '0001');
		}
		self::$app_path = iPHP_APP_DIR . '/' . $app;
		self::$app_file = self::$app_path . '/' . $app . '.app.php';
		is_file(self::$app_file) OR iPHP::throw404('运行出错！找不到文件: <b>' . $app . '.app.php</b>', '0002');
		if ($do === NULL) {
			$do = iPHP_APP;
			$_GET['do'] && $do = iS::escapeStr($_GET['do']);
		}
		if ($_POST['action']) {
			$do = iS::escapeStr($_POST['action']);
			$prefix = 'ACTION_';
		}

		self::$app_name = $app;
		self::$app_do = $do;
		self::$app_method = $prefix . $do;
		self::$app_tpl = iPHP_APP_DIR . '/' . $app . '/template';
		self::$app_vars = array(
			"MOBILE" => iPHP::$mobile,
			'COOKIE_PRE' => iPHP_COOKIE_PRE,
			'REFER' => __REF__,
			'CONFIG' => self::$config,
			"APP" => array(
				'NAME' => self::$app_name,
				'DO' => self::$app_do,
				'METHOD' => self::$app_method,
			),
		);
		iPHP::$iTPL->_iTPL_VARS['SAPI'] .= self::$app_name;
		iPHP::$iTPL->_iTPL_VARS += self::$app_vars;
		self::$app = iPHP::app($app);
		if (self::$app_do && self::$app->methods) {
			in_array(self::$app_do, self::$app->methods) OR iPHP::throw404('运行出错！ <b>' . self::$app_name . '</b> 类中找不到方法定义: <b>' . self::$app_method . '</b>', '0003');
			$method = self::$app_method;
			$args === null && $args = self::$app_args;
			if ($args) {
				if ($args === 'object') {
					return self::$app;
				}
				return call_user_func_array(array(self::$app, $method), (array) $args);
			} else {
				method_exists(self::$app, self::$app_method) OR iPHP::throw404('运行出错！ <b>' . self::$app_name . '</b> 类中 <b>' . self::$app_method . '</b> 方法不存在', '0004');
				return self::$app->$method();
			}
		} else {
			iPHP::throw404('运行出错！ <b>' . self::$app_name . '</b> 类中 <b>' . self::$app_method . '</b> 方法不存在', '0005');
		}
	}

	public static function config() {
		$site = iPHP_MULTI_SITE ? $_SERVER['HTTP_HOST'] : iPHP_APP;
		if (iPHP_MULTI_DOMAIN) {
			//只绑定主域
			preg_match("/[^\.\/][\w\-]+\.[^\.\/]+$/", $site, $matches);
			$site = $matches[0];
		}
		iPHP_MULTI_SITE && define('iPHP_APP_SITE', $site);
		strpos($site, '..') === false OR exit('<h1>What are you doing?(code:001)</h1>');

		//config.php 中开启iPHP_APP_CONF后 此处设置无效,
		define('iPHP_APP_CONF', iPHP_CONF_DIR . '/' . $site); //网站配置目录
		define('iPHP_APP_CONFIG', iPHP_APP_CONF . '/config.php'); //网站配置文件
		@is_file(iPHP_APP_CONFIG) OR exit('<h1>' . iPHP_APP . ' 运行出错.找不到"' . $site . '"网站的配置文件!(code:002)</h1>');
		$config = require iPHP_APP_CONFIG;

		//config.php 中开启后 此处设置无效
		defined('iPHP_DEBUG') OR define('iPHP_DEBUG', $config['debug']['php']); //程序调试模式
		defined('iPHP_TPL_DEBUG') OR define('iPHP_TPL_DEBUG', $config['debug']['tpl']); //模板调试
		defined('iPHP_SQL_DEBUG') OR define('iPHP_SQL_DEBUG', $config['debug']['sql']); //模板调试
		defined('iPHP_TIME_CORRECT') OR define('iPHP_TIME_CORRECT', $config['time']['cvtime']);
		defined('iPHP_ROUTER_REWRITE') OR define('iPHP_ROUTER_REWRITE', $config['router']['rewrite']);
		defined('iPHP_APP_SITE') && $config['cache']['prefix'] = iPHP_APP_SITE;

		define('iPHP_ROUTER_USER', $config['router']['user_url']);
		define('iPHP_URL_404', $config['router']['404']); //404定义
		//config.php --END--

		ini_set('display_errors', 'OFF');
		error_reporting(0);

		if (iPHP_DEBUG || iPHP_TPL_DEBUG) {
			ini_set('display_errors', 'ON');
			error_reporting(E_ALL & ~E_NOTICE);
		}

		$timezone = $config['time']['zone'];
		$timezone OR $timezone = 'Asia/Shanghai'; //设置中国时区
		function_exists('date_default_timezone_set') && @date_default_timezone_set($timezone);

		self::multiple_device($config);
		iFS::init($config['FS'], $config['watermark'], 'filedata');
		iCache::init($config['cache']);
		iPHP::template_start();

		iPHP_DEBUG && iDB::$show_errors = true;
		iPHP_TPL_DEBUG && self::clear_compiled_tpl();

		self::$apps = $config['apps'];

		return $config;
	}

	/**
	 * 多终端适配
	 * @param  [type] &$config [系统配置]
	 * @return [type]          [description]
	 */
	private static function multiple_device(&$config) {
		$template = $config['template'];
		if (iPHP::PG('device')) {
			/**
			 * 判断指定设备
			 */
			list($device_name, $def_tpl, $domain) = self::device_check($template['device'], 'device');
		}
		/**
		 * 无指定设备 判断USER_AGENT
		 *
		 */
		if (empty($def_tpl)) {
			list($device_name, $def_tpl, $domain) = self::device_check($template['device'], 'ua');
		}
		/**
		 * 无指定USER_AGENT  判断域名模板
		 *
		 */
		if (empty($def_tpl)) {
			list($device_name, $def_tpl, $domain) = self::device_check($template['device'], 'domain');
		}

		iPHP::$mobile = false;
		if (empty($def_tpl)) {
			//检查是否移动设备
			if (self::device_agent($template['mobile']['agent'])) {
				iPHP::$mobile = true;
				$mobile_tpl = $template['mobile']['tpl'];
				$device_name = 'mobile';
				$def_tpl = $mobile_tpl;
				$domain = $template['mobile']['domain'];
			}
		}

		if (empty($def_tpl)) {
			$device_name = 'desktop';
			$def_tpl = $template['desktop']['tpl'];
			$domain = false;
		}

        define('iPHP_REQUEST_SCHEME',($_SERVER['SERVER_PORT'] == 443)?'https':'http');
        define('iPHP_REQUEST_HOST',iPHP_REQUEST_SCHEME.'://'.($_SERVER['HTTP_X_HTTP_HOST']?$_SERVER['HTTP_X_HTTP_HOST']:$_SERVER['HTTP_HOST']));
        define('iPHP_REQUEST_URI',$_SERVER['REQUEST_URI']);
        define('iPHP_REQUEST_URL',iPHP_REQUEST_HOST.iPHP_REQUEST_URI);
		define('iPHP_ROUTER_URL', $config['router']['URL']);
		$domain && $config['router'] = str_replace($config['router']['URL'], $domain, $config['router']);
		define('iPHP_DEFAULT_TPL', $def_tpl);
		define('iPHP_MOBILE_TPL', $mobile_tpl);
		define('iPHP_DEVICE', $device_name);
		define('iPHP_HOST', $config['router']['URL']);
		header("Access-Control-Allow-Origin: " . iPHP_HOST);
		header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
        self::device_url_check();
    }
    private static function device_url_check(){
        if(stripos(iPHP_REQUEST_URL, iPHP_HOST) === false){
            $redirect_url = str_replace(iPHP_REQUEST_HOST,iPHP_HOST, iPHP_REQUEST_URL);
            header("Expires:1 January, 1970 00:00:01 GMT");
            header("Cache-Control: no-cache");
            header("Pragma: no-cache");
            // header("X-REDIRECT-REF: ".iPHP_REQUEST_URL);
            // header("X-iPHP_HOST: ".iPHP_HOST);
            // header("X-REDIRECT_URL: ".$redirect_url);
            // header("X-STRIPOS: ".(stripos(iPHP_REQUEST_URL, iPHP_HOST) === false));
            // iPHP::http_status(301);
            // exit($redirect_url);
            // iPHP::gotourl($redirect_url);
        }
	}
	private static function device_check($deviceArray = null, $flag = false) {
		foreach ((array) $deviceArray as $key => $device) {
			if ($device['tpl']) {
				$check = false;
				if ($flag == 'ua') {
					$device['ua'] && $check = self::device_agent($device['ua']);
				} elseif ($flag == 'device') {
					$_device = iPHP::PG('device');
					if ($device['ua'] == $_device || $device['name'] == $_device) {
						$check = true;
					}
				} elseif ($flag == 'domain') {
					if (stripos($device['domain'], $_SERVER['HTTP_HOST']) !== false && empty($device['ua'])) {
						$check = true;
					}
				}
				if ($check) {
					return array($device['name'], $device['tpl'], $device['domain']);
				}
			}
		}
	}
	private static function device_agent($user_agent) {
        $user_agent = str_replace(',','|',preg_quote($user_agent,'/'));
        return ($user_agent && preg_match('@'.$user_agent.'@i',$_SERVER["HTTP_USER_AGENT"]));
	}
	public static function template_start() {
		self::import(iPHP_CORE . '/iTemplate.class.php');
		self::$iTPL = new iTemplate();
		self::$iTPL->template_callback = array("iPHP", "tpl_path");
		self::$iTPL->template_dir = iPHP_TPL_DIR;
		self::$iTPL->compile_dir = iPHP_TPL_CACHE;
		self::$iTPL->left_delimiter = '<!--{';
		self::$iTPL->right_delimiter = '}-->';
		self::$iTPL->register_modifier("date", "get_date");
		self::$iTPL->register_modifier("cut", "csubstr");
		self::$iTPL->register_modifier("htmlcut", "htmlcut");
		self::$iTPL->register_modifier("cnlen", "cstrlen");
		self::$iTPL->register_modifier("html2txt", "html2text");
		self::$iTPL->register_modifier("key2num", "key2num");
		//self::$iTPL->register_modifier("pinyin","GetPinyin");
		self::$iTPL->register_modifier("unicode", "get_unicode");
		//self::$iTPL->register_modifier("small","gethumb");
		self::$iTPL->register_modifier("thumb", "small");
		self::$iTPL->register_modifier("random", "random");
		self::$iTPL->register_modifier("fields", "select_fields");
		self::$iTPL->register_block("cache", array("iPHP", "tpl_block_cache"));
		self::$iTPL->assign('GET', $_GET);
		self::$iTPL->assign('POST', $_POST);
	}
	public static function app_ref($app_name = true, $out = false) {
		$app_name === true && $app_name = self::$app_name;
		$rs = iPHP::get_vars($app_name);
		return $rs['param'];
	}
	public static function get_vars($key = null) {
		return self::$iTPL->get_template_vars($key);
	}
	public static function clear_compiled_tpl($file = null) {
		self::$iTPL->clear_compiled_tpl($file);
	}
	public static function assign($key, $value) {
		self::$iTPL->assign($key, $value);
	}
	public static function append($key, $value = null, $merge = false) {
		self::$iTPL->append($key, $value, $merge);
	}
	public static function clear($key) {
		self::$iTPL->clear_assign($key);
	}
	public static function display($tpl) {
		self::$iTPL->display($tpl);
	}
	public static function fetch($tpl) {
		return self::$iTPL->fetch($tpl);
	}
	public static function pl($tpl) {
		if (self::$iTPL_MODE == 'html') {
			return self::$iTPL->fetch($tpl);
		} else {
			self::$iTPL->display($tpl);
			if (iPHP_DEBUG) {
				//echo '<span class="label label-success">内存:'.iFS::sizeUnit(memory_get_usage()).', 执行时间:'.self::timer_stop().'s, SQL执行:'.iDB::$num_queries.'次</span>';
			}
		}
	}
	public static function view($tpl, $p = 'index') {
		$tpl OR self::throw404('运行出错！ 请设置模板文件', '001', 'TPL');
		return self::pl($tpl);

	}
	public static function tpl_block_cache($vars, $content, &$tpl) {
		$vars['id'] OR iPHP::warning('cache 标签出错! 缺少"id"属性或"id"值为空.');
		$cache_time = isset($vars['time']) ? (int) $vars['time'] : -1;
		$cache_name = iPHP_DEVICE . '/part/' . $vars['id'];
		$cache = iCache::get($cache_name);
		if (empty($cache)) {
			if ($content === null) {
				return null;
			}
			$cache = $content;
			iCache::set($cache_name, $content, $cache_time);
			unset($content);
		}
		if ($vars['assign']) {
			$tpl->assign($vars['assign'], $cache);
			return ture;
		}
		if ($content === null) {
			return $cache;
		}
		// return $cache;
	}
	/**
	 * 模板路径
	 * @param  [type] $tpl [description]
	 * @return [type]      [description]
	 */
	public static function tpl_path($tpl) {
		if (strpos($tpl, iPHP_APP . ':/') !== false) {
			$_tpl = str_replace(iPHP_APP . ':/', iPHP_DEFAULT_TPL, $tpl);
			if (@is_file(iPHP_TPL_DIR . "/" . $_tpl)) {
				return $_tpl;
			}

			if (iPHP_DEVICE != 'desktop') {
//移动设备
				$_tpl = str_replace(iPHP_APP . ':/', iPHP_MOBILE_TPL, $tpl); // mobile/
				if (@is_file(iPHP_TPL_DIR . "/" . $_tpl)) {
					return $_tpl;
				}

			}
			$tpl = str_replace(iPHP_APP . ':/', iPHP_APP, $tpl); //iCMS
		} elseif (strpos($tpl, '{iTPL}') !== false) {
			$tpl = str_replace('{iTPL}', iPHP_DEFAULT_TPL, $tpl);
		}
		if (iPHP_DEVICE != 'desktop' && strpos($tpl, iPHP_APP) === false) {
			$current_tpl = dirname($tpl);
			if (!in_array($current_tpl, array(iPHP_DEFAULT_TPL, iPHP_MOBILE_TPL))) {
				$tpl = str_replace($current_tpl . '/', iPHP_DEFAULT_TPL . '/', $tpl);
			}
		}
		if (@is_file(iPHP_TPL_DIR . "/" . $tpl)) {
			return $tpl;
		} else {
			self::throw404('运行出错！ 找不到模板文件 <b>iPHP:://template/' . $tpl . '</b>', '002', 'TPL');
		}
	}
	public static function PG($key) {
		return isset($_POST[$key]) ? $_POST[$key] : $_GET[$key];
	}
	// 获取客户端IP
	public static function getIp($format = 0) {
		if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
			$onlineip = getenv('HTTP_CLIENT_IP');
		} elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
			$onlineip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
			$onlineip = getenv('REMOTE_ADDR');
		} elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
			$onlineip = $_SERVER['REMOTE_ADDR'];
		}
		preg_match("/[\d\.]{7,15}/", $onlineip, $onlineipmatches);
		$ip = $onlineipmatches[0] ? $onlineipmatches[0] : 'unknown';
		if ($format) {
			$ips = explode('.', $ip);
			for ($i = 0; $i < 3; $i++) {
				$ips[$i] = intval($ips[$i]);
			}
			return sprintf('%03d%03d%03d', $ips[0], $ips[1], $ips[2]);
		} else {
			return $ip;
		}
	}
	//设置COOKIE
	public static function set_cookie($name, $value = "", $life = 0, $httponly = false) {
		// $cookiedomain = iPHP_COOKIE_DOMAIN;
		$cookiedomain = '';
		$cookiepath = iPHP_COOKIE_PATH;

		$value = urlencode($value);
		$life = ($life ? $life : iPHP_COOKIE_TIME);
		$name = iPHP_COOKIE_PRE . '_' . $name;

		if (strpos(iPHP_SESSION, 'SESSION') !== false) {
			$_SESSION[$name] = $value;
		}
		if (strpos(iPHP_SESSION, 'COOKIE') !== false) {
			$_COOKIE[$name] = $value;
			$timestamp = time();
			$life = $life > 0 ? $timestamp + $life : ($life < 0 ? $timestamp - 31536000 : 0);
			$path = $httponly && PHP_VERSION < '5.2.0' ? $cookiepath . '; HttpOnly' : $cookiepath;
			$secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
			if (PHP_VERSION < '5.2.0') {
				setcookie($name, $value, $life, $path, $cookiedomain, $secure);
			} else {
				setcookie($name, $value, $life, $path, $cookiedomain, $secure, $httponly);
			}
		}
	}
	//取得COOKIE
	public static function get_cookie($name) {
		$name = iPHP_COOKIE_PRE . '_' . $name;

		if (strpos(iPHP_SESSION, 'COOKIE') !== false) {
			$cvalue = $_COOKIE[$name];
			$cvalue = urldecode($cvalue);
		}
		if (strpos(iPHP_SESSION, 'SESSION') !== false) {
			$svalue = $_SESSION[$name];
			$svalue = urldecode($svalue);
		}
		if (iPHP_SESSION == 'SESSION+COOKIE') {
			if ($cvalue == $svalue) {
				return $svalue;
			} else if ($svalue) {
				return $svalue;
			} else if ($cvalue) {
				return $cvalue;
			}
		} else if (iPHP_SESSION == 'SESSION') {
			return $svalue;
		} else if (iPHP_SESSION == 'COOKIE') {
			return $cvalue;
		}
		return false;
	}

	public static function import($path, $dump = false) {
		$key = str_replace(iPATH, 'iPHP://', $path);
		if ($dump) {
			if (!isset($GLOBALS['_iPHP_REQ'][$key])) {
				$GLOBALS['_iPHP_REQ'][$key] = include $path;
			}
			return $GLOBALS['_iPHP_REQ'][$key];
		}

		if (isset($GLOBALS['_iPHP_REQ'][$key])) {
			return;
		}

		$GLOBALS['_iPHP_REQ'][$key] = true;
		require_once $path;
	}
	public static function core($fname, $cname = null, $msg = '',$core = null) {
		$cname === null && $cname = $fname;
		$cname = 'i' . $cname;
		if (!class_exists($cname)) {
			$core===null && $core = iPHP_CORE;
			$path = $core . '/i' . $fname . '.class.php';
			if (@is_file($path)) {
				self::import($path);
			} else {
				$msg OR $msg = 'file ' . $path . ' not exist';
				self::throwException($msg, 0020);
			}
		}
	}

	public static function app($app = NULL, $args = NULL) {
		$app_dir = $app_name = $app;
		$file_type = 'app';
		if (strpos($app, '.') !== false) {
			list($app_dir, $app_name, $file_type) = explode('.', $app);
			if (empty($file_type)) {
				$file_type = $app_name;
				$app_name = $app_dir;
			}else{
				if($file_type=='admincp'){
					$file_type='subadmincp';
					$obj_name = $app_dir.$app_name . 'Admincp';
					// $app_name = $app_dir.'.'.$app_name;
				}
			}
		}

		$app_file = $app_name.'.'.$file_type;

		switch ($file_type) {
			case 'class':
				$obj_name = $app_name;
				break;
			case 'admincp':
				$obj_name = $app_name . 'Admincp';
				break;
			case 'subadmincp':
				$app_file = $app_dir.'.'.$app_name.'.admincp';
				break;
			case 'table':
				$obj_name = $app_name . 'Table';
				$args = "static";
				break;
			case 'func':
				$args = "include";
				break;
			default:$obj_name = $app_name . 'App';
				break;
		}
		$path = iPHP_APP_DIR . '/' . $app_dir . '/' . $app_file . '.php';
		if (@is_file($path)) {
			self::import($path);
		}else{
			return false;
		}

		if ($args === "include" || $args === "static") {
			return;
		}

		$obj = new $obj_name();
		$args && call_user_func_array(array($obj, '__construct'), (array) $args);
		return $obj;
	}
	public static function vendor($name, $args = null) {
		iPHP::import(iPHP_LIB . '/Vendor.' . $name . '.php');
		if (function_exists($name)) {
			return call_user_func_array($name, $args);
		} else {
			return false;
		}
	}

	public static function throwException($msg, $code) {
		trigger_error(iPHP_APP . ' ' . $msg . '(' . $code . ')', E_USER_ERROR);
	}
	public static function p2num($path, $page = false) {
		$page === false && $page = $GLOBALS['page'];
		if ($page < 2) {
			return str_replace(array('_{P}', '&p={P}'), '', $path);
		}
		return str_replace('{P}', $page, $path);
	}

	public static function router($key, $var = null) {
		if(isset($GLOBALS['ROUTER'])){
			$routerArray = $GLOBALS['ROUTER'];
		}else{
			$path = iPHP_APP_CONF . '/router.json';
			@is_file($path) OR self::throwException($path . ' not exist', 0013);
			$routerArray = json_decode(file_get_contents($path), true);
			$GLOBALS['ROUTER'] = $routerArray;
		}

		if (is_array($key)) {
			$router = $routerArray[$key[0]];
		} else {
			$router = $routerArray[$key];
		}
		$url = iPHP_ROUTER_REWRITE?$router[0]:$router[1];

		if (iPHP_ROUTER_REWRITE && stripos($router, 'uid:') === 0) {
			$url = rtrim(iPHP_ROUTER_USER, '/') . $url;
		}
		if (is_array($key)) {
			if (is_array($key[1])) {
				/* 多个{} 例:/{uid}/{cid}/ */
				preg_match_all('/\{(\w+)\}/i', $url, $matches);
				$url = str_replace($matches[0], $key[1], $url);
			} else {
				$url = preg_replace('/\{\w+\}/i', $key[1], $url);
			}
			$key[2] && $url = $key[2] . $url;
		}

		if ($var == '?&') {
			$url .= iPHP_ROUTER_REWRITE ? '?' : '&';
		}
		$url = str_replace('iCMS_API', iCMS_API, $url);
		return $url;
	}

	public static function lang($string = '', $throw = true) {
		if (empty($string)) {
			return false;
		}

		$keyArray = explode(':', $string);
		$count = count($keyArray);
		list($app, $do, $key, $msg) = $keyArray;

		$fname = $app . '.lang.php';
		$path = iPHP_APP_CORE . '/lang/' . $fname;

		if (!@is_file($path)) {
			if ($throw) {
				self::throwException($fname . ' not exist', 0015);
			} else {
				return $string;
			}
		}

		$langArray = self::import($path, true);

		switch ($count) {
		case 1:return $langArray;
		case 2:return $langArray[$do];
		case 3:return $langArray[$do][$key];
		case 4:return $langArray[$do][$key][$msg];
		}
	}

	public static function throw404($msg = "", $code = "") {
		iPHP_DEBUG && self::throwException($msg, $code);
		self::http_status(404, $code);
		if (defined('iPHP_URL_404')) {
			iPHP_URL_404 && self::gotourl(iPHP_URL_404 . '?url=' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		}
		exit();
	}

	public static function http_status($code, $ECODE = '') {
		static $_status = array(
			// Success 2xx
			200 => 'OK',
			// Redirection 3xx
			301 => 'Moved Permanently',
			302 => 'Moved Temporarily ', // 1.1
            304 => 'Not Modified',
			// Client Error 4xx
			400 => 'Bad Request',
			403 => 'Forbidden',
			404 => 'Not Found',
			// Server Error 5xx
			500 => 'Internal Server Error',
			503 => 'Service Unavailable',
		);
		if (isset($_status[$code])) {
			header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
			$ECODE && header("X-iPHP-ECODE:" . $ECODE);
		}
	}

	public static function map_sql($where, $type = null, $field = 'iid') {
		if (empty($where)) {
			return false;
		}
		$i = 0;
		foreach ($where as $key => $value) {
			$as = ' map';
			$i && $as .= $i;
			$_FROM[] = $key . $as;
			$_WHERE[] = str_replace($key, $as, $value);
			$_FIELD[] = $as . ".`{$field}`";
			$i++;
		}
		$_field = $_FIELD[0];
		$_count = count($_FIELD);
		if ($_count > 1) {
			foreach ($_FIELD as $fkey => $fd) {
				$fkey && array_push($_WHERE, $_field . ' = ' . $fd);
			}
		}
		if ($type == 'join') {
			return array('from' => implode(',', $_FROM), 'where' => implode(' AND ', $_WHERE));
		}
		return 'SELECT ' . $_field . ' AS ' . $field . ' FROM ' . implode(',', $_FROM) . ' WHERE ' . implode(' AND ', $_WHERE);
	}
	public static function get_ids($rs, $field = 'id') {
		if (empty($rs)) {
			return false;
		}
		$resource = array();
		foreach ((array) $rs AS $_vars) {
			if ($field === null) {
				$resource[] = "'" . $_vars . "'";
			} else {
				$resource[] = "'" . $_vars[$field] . "'";
			}
		}
		unset($rs);
		if ($resource) {
			$resource = array_unique($resource);
			$resource = implode(',', $resource);
			return $resource;
		}
		return false;
	}
	public static function where($vars, $field, $not = false, $noand = false, $table = '') {
		if (is_bool($vars) || empty($vars)) {
			return '';
		}

		if (is_array($vars)) {
			foreach ($vars as $key => $value) {
				$vas[] = "'" . addslashes($value) . "'";
			}
			$vars = implode(',', $vas);
			$sql = $not ? " NOT IN ($vars)" : " IN ($vars) ";
		} else {
			$vars = addslashes($vars);
			$sql = $not ? "<>'$vars' " : "='$vars' ";
		}
		$table && $table .= '.';
		$sql = "{$table}`{$field}`" . $sql;
		if ($noand) {
			return $sql;
		}
		$sql = ' AND ' . $sql;
		return $sql;
	}
	public static function str2time($str = "0") {
		$correct = 0;
		$str OR $str = 'now';
		$time = strtotime($str);
		(int) iPHP_TIME_CORRECT && $correct = (int) iPHP_TIME_CORRECT * 60;
		return $time + $correct;
	}
	/**
	 * Starts the timer, for debugging purposes
	 */
	public static function timer_start() {
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		self::$time_start = $mtime[1] + $mtime[0];
	}

	/**
	 * Stops the debugging timer
	 * @return int total time spent on the query, in milliseconds
	 */
	public static function timer_stop() {
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		$time_end = $mtime[1] + $mtime[0];
		$time_total = $time_end - self::$time_start;
		//self::$time_start = $time_end;
		return round($time_total, 4);
	}
	public static function json($a, $break = true, $ret = false) {
		$json = json_encode($a);
		$_GET['callback'] && $json = $_GET['callback'] . '(' . $json . ')';
		$_GET['script'] && exit("<script>{$json};</script>");
		if ($ret) {
			return $json;
		}
		echo $json;
		$break && exit();
	}
	public static function js_callback($a, $callback = null, $node = 'parent') {
		$callback === null && $callback = $_GET['callback'];
		empty($callback) && $callback = 'callback';
		$json = json_encode($a);
		echo "<script>window.{$node}.{$callback}($json);</script>";
		exit;
	}
	public static function code($code = 0, $msg = '', $forward = '', $format = '') {
		strstr($msg, ':') && $msg = self::lang($msg, false);
		$a = array('code' => $code, 'msg' => $msg, 'forward' => $forward);
		if ($format == 'json') {
			self::json($a);
		}
		return $a;
	}
	public static function warning($info) {
		self::msg('warning:#:warning:#:' . $info);
	}
	public static function msg($info, $ret = false) {
		list($label, $icon, $content) = explode(':#:', $info);
		$msg = '<div class="iPHP-msg"><div class="label label-' . $label . '">';
		$icon && $msg .= '<i class="fa fa-' . $icon . '"></i> ';
		if (strpos($content, ':') !== false) {
			$lang = self::lang($content, false);
			$lang && $content = $lang;
		}
		$msg .= $content . '</div></div>';
		if ($ret) {
			return $msg;
		}

		echo $msg;
	}
	public static function js($str = "js:", $ret = false) {
		$type = substr($str, 0, strpos($str, ':'));
		$act = substr($str, strpos($str, ':') + 1);
		switch ($type) {
		case 'js':
			$act && $code = $act;
			$act == "0" && $code = 'iTOP.history.go(-1);';
			$act == "1" && $code = 'iTOP.location.href=iTOP.location.href;';
			break;
		case 'url':
			$act == "1" && $act = __REF__;
			$code = "iTOP.location.href='" . $act . "';";
			break;
		case 'src':$code = "iTOP.$('#iPHP_FRAME').attr('src','" . $act . "');";
			break;
		default:$code = '';
		}

		if ($ret) {
			return $code;
		}

		echo '<script type="text/javascript">' . $code . '</script>';
		self::$break && exit();
	}
	public static function alert($msg, $js = null, $s = 3) {
		if (iPHP::$dialog['alert'] === 'window') {
			self::js("js:window.alert('{$msg}')");
		}
		self::$dialog = array(
			'lock' => true,
			'width' => 360,
			'height' => 120,
		);
		self::dialog('warning:#:warning:#:' . $msg, $js, $s);
	}
	public static function success($msg, $js = null, $s = 3) {
		self::$dialog = array(
			'lock' => true,
			'width' => 360,
			'height' => 120,
		);
		self::dialog('success:#:check:#:' . $msg, $js, $s);
	}
	public static function dialog($info = array(), $js = 'js:', $s = 3, $buttons = null, $update = false) {
		$info = (array) $info;
		$title = $info[1] ? $info[1] : '提示信息';
		$content = $info[0];
		strstr($content, ':#:') && $content = self::msg($content, true);
		$content = addslashes('<table class="ui-dialog-table" align="center"><tr><td valign="middle">' . $content . '</td></tr></table>');

		$options = array(
			"id:'iPHP-DIALOG'", "time:null",
			"title:'" . (self::$dialog['title'] ? self::$dialog['title'] : iPHP_APP) . " - {$title}'",
			"lock:" . (self::$dialog['lock'] ? 'true' : 'false'),
			"width:'" . (self::$dialog['width'] ? self::$dialog['width'] : 'auto') . "'",
			"height:'" . (self::$dialog['height'] ? self::$dialog['height'] : 'auto') . "'",
			"api:'iPHP'",
		);
		//$content && $options[]="content:'{$content}'";
		$auto_func = 'd.close().remove();';
		$func = self::js($js, true);
		if ($func) {
			$buttons OR $options[] = 'okValue: "确 定",ok: function(){' . $func . ';},';
			$auto_func = $func . 'd.close().remove();';
		}
		if (is_array($buttons)) {
			$okbtn = "{value:'确 定',callback:function(){" . $func . "},autofocus: true}";
			foreach ($buttons as $key => $val) {
				$val['id'] && $id = "id:'" . $val['id'] . "',";
				$val['js'] && $func = $val['js'] . ';';
				$val['url'] && $func = "iTOP.location.href='{$val['url']}';";
				$val['src'] && $func = "iTOP.$('#iPHP_FRAME').attr('src','{$val['src']}');return false;";
				$val['target'] && $func = "iTOP.window.open('{$val['url']}','_blank');";
                if($val['close']===false){
                    $func.= "return false;";
                }
                $val['time'] && $s = $val['time'];

                if($func){
                    $buttonA[]="{".$id."value:'".$val['text']."',callback:function(){".$func."}}";
                    $val['next'] && $auto_func = $func;
                }
            }
			//$buttonA[] = $okbtn;
			$button = implode(",", $buttonA);
		}
		$dialog = 'var iTOP = window.top,';
		if ($update) {
			$dialog .= "d = iTOP.dialog.get('iPHP-DIALOG');";
			$auto_func = $func;
		} else {
			$dialog .= 'options = {' . implode(',', $options) . '},d = iTOP.' . iPHP_APP . '.dialog(options);';
			// if(self::$dialog_lock){
			// 	$dialog.='d.showModal();';
			// }else{
			// 	$dialog.='d.show();';
			// }
		}
		$button && $dialog .= "d.button([$button]);";
		$content && $dialog .= "d.content('$content');";

		$s <= 30 && $timeout = $s * 1000;
		$s > 30 && $timeout = $s;
		$s === false && $timeout = false;
		if ($timeout) {
			$dialog .= 'window.setTimeout(function(){' . $auto_func . '},' . $timeout . ');';
		} else {
			$update && $dialog .= $auto_func;
		}
		echo self::$dialog['code'] ? $dialog : '<script>' . $dialog . '</script>';
		self::$break && exit();
	}
	//模板翻页函数
	public static function page($conf) {
		iPHP::core("Pages");
		$conf['lang'] = iPHP::lang(iPHP_APP . ':page');
		$iPages = new iPages($conf);
		if ($iPages->totalpage > 1) {
			$pagenav = $conf['pagenav'] ? strtoupper($conf['pagenav']) : 'NAV';
			$pnstyle = $conf['pnstyle'] ? $conf['pnstyle'] : 0;
			iPHP::$iTPL->_iTPL_VARS['PAGE'] = array(
				$pagenav => $iPages->show($pnstyle),
				'COUNT' => $conf['total'],
				'TOTAL' => $iPages->totalpage,
				'CURRENT' => $iPages->nowindex,
				'PN' => $iPages->nowindex,
				'PREV' => $iPages->prev_page(),
				'NEXT' => $iPages->next_page(),
			);
			iPHP::$iTPL->_iTPL_VARS['PAGES'] = $iPages;
		}
		return $iPages;
	}
	//动态翻页函数
	public static function pagenav($total, $displaypg = 20, $unit = "条记录", $url = '', $target = '') {
		iPHP::core("Pages");
		$pageconf = array(
			'url' => $url,
			'target' => $target,
			'total' => $total,
			'perpage' => $displaypg,
			'total_type' => 'G',
			'lang' => iPHP::lang(iPHP_APP . ':page'),
		);
		$pageconf['lang']['format_left'] = '<li>';
		$pageconf['lang']['format_right'] = '</li>';

		$iPages = new iPages($pageconf);
		self::$offset = $iPages->offset;
		self::$pagenav = '<ul>' .
		self::$pagenav .= $iPages->show(3);
		self::$pagenav .= "<li> <span class=\"muted\">{$total}{$unit} {$displaypg}{$unit}/页 共{$iPages->totalpage}页</span></li>";
		if ($iPages->totalpage > 200) {
			$url = $iPages->get_url(1);
			self::$pagenav .= "<li> <span class=\"muted\">跳到 <input type=\"text\" id=\"pageselect\" style=\"width:24px;height:12px;margin-bottom: 0px;line-height: 12px;\" /> 页 <input class=\"btn btn-small\" type=\"button\" onClick=\"window.location='{$url}&page='+$('#pageselect').val();\" value=\"跳转\" style=\"height: 22px;line-height: 18px;\"/></span></li>";
		} else {
			self::$pagenav .= "<li> <span class=\"muted\">跳到" . $iPages->select() . "页</span></li>";
		}
		self::$pagenav .= '</ul>';
	}
	public static function total($tnkey, $sql, $type = null) {
		$tnkey == 'sql.md5' && $tnkey = md5($sql);
		$tnkey = substr($tnkey, 8, 16);
		$total = (int) $_GET['total_num'];
		if (empty($total) && $type === null && !isset($_GET['total_cahce'])) {
			$total = (int) iCache::get('total/' . $tnkey);
		}
		if (empty($total) || $type === 'nocache' || isset($_GET['total_cahce'])) {
			$total = iDB::value($sql);
			if ($type === null) {
				iCache::set('total/' . $tnkey, $total);
			}
		}
		return $total;
	}
	public static function gotourl($URL = '') {
		$URL OR $URL = __REF__;
		if (headers_sent()) {
			echo '<meta http-equiv=\'refresh\' content=\'0;url=' . $URL . '\'><script type="text/javascript">window.location.replace(\'' . $URL . '\');</script>';
		} else {
			header("Location: $URL");
		}
		exit;
	}

}

function iPHP_ERROR_HANDLER($errno, $errstr, $errfile, $errline) {
	$errno = $errno & error_reporting();
	if ($errno == 0) {
		return;
	}

	defined('E_STRICT') OR define('E_STRICT', 2048);
	defined('E_RECOVERABLE_ERROR') OR define('E_RECOVERABLE_ERROR', 4096);
	$html = "<pre>\n<b>";
	switch ($errno) {
        case E_ERROR:              $html.="Error";                  break;
        case E_WARNING:            $html.="Warning";                break;
        case E_PARSE:              $html.="Parse Error";            break;
        case E_NOTICE:             $html.="Notice";                 break;
        case E_CORE_ERROR:         $html.="Core Error";             break;
        case E_CORE_WARNING:       $html.="Core Warning";           break;
        case E_COMPILE_ERROR:      $html.="Compile Error";          break;
        case E_COMPILE_WARNING:    $html.="Compile Warning";        break;
        case E_USER_ERROR:         $html.="iPHP Error";             break;
        case E_USER_WARNING:       $html.="iPHP Warning";           break;
        case E_USER_NOTICE:        $html.="iPHP Notice";            break;
        case E_STRICT:             $html.="Strict Notice";          break;
        case E_RECOVERABLE_ERROR:  $html.="Recoverable Error";      break;
        default:                   $html.="Unknown error ($errno)"; break;
	}
	$html .= ":</b> $errstr\n";
	if (function_exists('debug_backtrace')) {
		//print "backtrace:\n";
		$backtrace = debug_backtrace();
		foreach ($backtrace as $i => $l) {
			$html .= "[$i] in function <b>{$l['class']}{$l['type']}{$l['function']}</b>";
			$l['file'] && $html .= " in <b>{$l['file']}</b>";
			$l['line'] && $html .= " on line <b>{$l['line']}</b>";
			$html .= "\n";
		}
	}
	$html .= "\n</pre>";
	$html = str_replace('\\', '/', $html);
	$html = str_replace(iPATH, 'iPHP://', $html);
	if (isset($_GET['frame'])) {
		iPHP::$dialog['lock'] = true;
		$html = str_replace("\n", '<br />', $html);
		iPHP::dialog(array("warning:#:warning-sign:#:{$html}", '系统错误!可发邮件到 idreamsoft@qq.com 反馈错误!我们将及时处理'), 'js:1', 30000000);
		exit;
	}
	if ($_POST) {
        if($_POST['ajax']){
            $array = array('code'=>'0','msg'=>$html);
            echo json_encode($array);
        }else{
            $html = str_replace(array("\r", "\\", "\"", "\n", "<b>", "</b>", "<pre>", "</pre>"), array(' ', "\\\\", "\\\"", '\n', ''), $html);
            echo '<script>top.alert("' . $html . '")</script>';
        }
        exit;
    }
    @header('HTTP/1.1 500 Internal Server Error');
    @header('Status: 500 Internal Server Error');
    @header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    @header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    @header("Cache-Control: no-store, no-cache, must-revalidate");
    @header("Cache-Control: post-check=0, pre-check=0", false);
    @header("Pragma: no-cache");
	$html = str_replace("\n", '<br />', $html);
	exit($html);
}
