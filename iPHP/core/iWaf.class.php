<?php
/*通用漏洞防护补丁v1.1
来源：阿里云
更新时间：2013-05-25
功能说明：防护XSS,SQL,代码执行，文件包含等多种高危漏洞
*/
defined('iPHP_WAF_POST') OR define('iPHP_WAF_POST',true);// 检测POST

class waf {
	public static $URL_ARRAY = array(
		'xss'=>"\\=\\+\\/v(?:8|9|\\+|\\/)|\\%0acontent\\-(?:id|location|type|transfer\\-encoding)",
	);
	public static $ARGS_ARRAY=array(
		'xss'   =>"[\\'\\\"\\;\\*\\<\\>].*\\bon[a-zA-Z]{3,15}[\\s\\r\\n\\v\\f]*\\=|\\b(?:expression)\\(|\\<script[\\s\\\\\\/]|\\<\\!\\[cdata\\[|\\b(?:eval|alert|prompt|msgbox)\\s*\\(|url\\((?:\\#|data|javascript)",
		'sql'   =>"[^\\{\\s]{1}(\\s|\\b)+(?:select\\b|update\\b|insert(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+into\\b).+?(?:from\\b|set\\b)|[^\\{\\s]{1}(\\s|\\b)+(?:create|delete|drop|truncate|rename|desc)(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+(?:table\\b|from\\b|database\\b)|into(?:(\\/\\*.*?\\*\\/)|\\s|\\+)+(?:dump|out)file\\b|\\bsleep\\([\\s]*[\\d]+[\\s]*\\)|benchmark\\(([^\\,]*)\\,([^\\,]*)\\)|(?:declare|set|select)\\b.*@|union\\b.*(?:select|all)\\b|(?:select|update|insert|create|delete|drop|grant|truncate|rename|exec|desc|from|table|database|set|where)\\b.*(charset|ascii|bin|char|uncompress|concat|concat_ws|conv|export_set|hex|instr|left|load_file|locate|mid|sub|substring|oct|reverse|right|unhex)\\(|(?:master\\.\\.sysdatabases|msysaccessobjects|msysqueries|sysmodules|mysql\\.db|sys\\.database_name|information_schema\\.|sysobjects|sp_makewebtask|xp_cmdshell|sp_oamethod|sp_addextendedproc|sp_oacreate|xp_regread|sys\\.dbms_export_extension)",
		'other' =>"\\.\\.[\\\\\\/].*\\%00([^0-9a-fA-F]|$)|%00[\\'\\\"\\.]"
	);
	public static function filter(){
		$referer      = empty($_SERVER['HTTP_REFERER']) ? array() : array($_SERVER['HTTP_REFERER']);
		$query_string = empty($_SERVER["QUERY_STRING"]) ? array() : array($_SERVER["QUERY_STRING"]);
		self::check_data($query_string,self::$URL_ARRAY);
		self::check_data($_GET,self::$ARGS_ARRAY);
		iPHP_WAF_POST && self::check_data($_POST,self::$ARGS_ARRAY);
		self::check_data($_COOKIE,self::$ARGS_ARRAY);
		self::check_data($referer,self::$ARGS_ARRAY);
	}

	public static function check_data($arr,$v) {
		foreach($arr as $key=>$value){
			if(!is_array($key)){
				self::check($key,$v);
			}else{
				self::check_data($key,$v);
			}

			if(!is_array($value)){
				self::check($value,$v);
			}else{
				self::check_data($value,$v);
			}
		}
	}
	public static function check($str,$v){
		foreach($v as $key=>$value){
			if (preg_match("/".$value."/is",$str)==1||preg_match("/".$value."/is",urlencode($str))==1){
				exit("WAF:what the fuck!!");
			}
		}
	}
}
