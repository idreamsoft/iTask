<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
* @version 6.0.0
* @$Id: iCMS.class.php 2412 2014-05-04 09:52:07Z coolmoo $
*/
defined('iPHP') OR exit('What are you doing?');

class iCMS extends iPHP{
    public static $iCache      = null;
    public static $sphinx      = null;

	public static function init(){
        self::$config = iPHP::config();
        iURL::init(self::$config);

        define('iCMS_DIR',       self::$config['router']['DIR']);
        define('iCMS_URL',       self::$config['router']['URL']);
        define('iCMS_PUBLIC_URL',self::$config['router']['public_url']);
        define('iCMS_FS_URL',    self::$config['FS']['url']);
        define('iCMS_REWRITE',   iPHP_ROUTER_REWRITE);
        define('iCMS_API',       iCMS_PUBLIC_URL.'/api.php');
        define('iCMS_API_URL',   iCMS_API.'?app=');
        self::assign_site();
	}
    /**
     * 运行应用程序
     * @param string $app 应用程序名称
     * @param string $do 动作名称
     */
    public static function run($app = NULL,$do = NULL,$args = NULL,$prefix="do_") {
        iPHP::$iTPL->_iTPL_VARS = array(
            'VERSION' => iCMS_VER,
            'API'     => iCMS_API,
            'SAPI'    => iCMS_API_URL,
            'APPID'   => array(
                'ARTICLE'  => iCMS_APP_ARTICLE,
                'CATEGORY' => iCMS_APP_CATEGORY,
                'TAG'      => iCMS_APP_TAG,
                'PUSH'     => iCMS_APP_PUSH,
                'COMMENT'  => iCMS_APP_COMMENT,
                'PROP'     => iCMS_APP_PROP,
                'MESSAGE'  => iCMS_APP_MESSAGE,
                'FAVORITE' => iCMS_APP_FAVORITE,
                'USER'     => iCMS_APP_USER,
            )
        );

        return iPHP::run($app,$do,$args,$prefix);
    }

    public static function API($app = NULL,$do = NULL) {
        $app OR $app = iS::escapeStr($_GET['app']);
        return self::run($app,null,null,'API_');
    }

    public static function hooks($key,$array){
        self::$hooks[$key]  = $array;
    }
    public static function core($fname, $cname = null,$msg = '',$core = null) {
        iPHP::core($fname,$cname,'',iPHP_APP_CORE);
    }
    public static function assign_site(){
        $site          = self::$config['site'];
        $site['title'] = self::$config['site']['name'];
        $site['404']   = iPHP_URL_404;
        $site['url']   = iCMS_URL;
        $site['tpl']   = iPHP_DEFAULT_TPL;
        $site['urls']  = array(
            "tpl"    => iCMS_URL.'/template/'.iPHP_DEFAULT_TPL,
            "public" => iCMS_PUBLIC_URL,
            "user"   => iPHP_ROUTER_USER,
            "res"    => iCMS_FS_URL,
            "ui"     => iCMS_PUBLIC_URL.'/ui',
            "avatar" => iCMS_FS_URL.'avatar/',
            "mobile" => self::$config['template']['mobile']['domain'],
        );
        iPHP::assign('site',$site);
        iPHP::$dialog['title']  = self::$config['site']['name'];
    }
    //------------------------------------
    public static function get_rand_ids($table,$where=null,$limit='10',$primary='id'){
        $whereSQL = $where?"{$where} AND `{$table}`.`{$primary}` >= rand_id":' WHERE `{$table}`.`{$primary}` >= rand_id';
        // $limitNum = rand(2,10);
        // $prelimit = ceil($limit/rand(2,10));
        $randSQL  = "
            SELECT `{$table}`.`{$primary}` FROM `{$table}`
            JOIN (SELECT
                  ROUND(RAND() * (
                      (SELECT MAX(`{$table}`.`{$primary}`) FROM `{$table}`) -
                      (SELECT MIN(`{$table}`.`{$primary}`) FROM `{$table}`)
                    ) + (SELECT MIN(`{$table}`.`{$primary}`) FROM `{$table}`)
                 ) AS rand_id) RAND_DATA
            {$whereSQL}
            LIMIT $limit;
        ";
        $randIdsArray = iDB::all($randSQL);
        // $randIdsArray = null;
        // for ($i=0; $i <=$prelimit; $i++) {
        //     $randIdsArray[$i] = array('id'=>iDB::value($randSQL));
        //     echo iDB::$last_query;
        // }
        return $randIdsArray;
    }
    public static function hits_sql($all=true,$hit=1){
        $timeline = self::timeline();
        // var_dump($timeline);
        $pieces = array();
        $all && $pieces[] = '`hits` = hits+'.$hit;
        foreach ($timeline as $key => $bool) {
            $field = "hits_{$key}";
            if($key=='yday'){
                if($bool==1){
                    $pieces[]="`hits_yday` = hits_today";
                }elseif ($bool>1) {
                    $pieces[]="`hits_yday` = 0";
                }
                continue;
            }
            $pieces[]="`{$field}` = ".($bool?"{$field}+{$hit}":$hit);
        }
        return implode(',', $pieces);
    }
    public static function timeline(){
        $_timeline = iCache::get('iCMS/timeline');
        //list($_today,$_week,$_month) = $_timeline ;
        $time     = $_SERVER['REQUEST_TIME'];
        $today    = get_date($time,"Ymd");
        $yday     = get_date($time-86400+1,"Ymd");
        $week     = get_date($time,"YW");
        $month    = get_date($time,"Ym");
        $timeline = array($today,$week,$month);
        $_timeline[0]==$today OR iCache::set('iCMS/timeline',$timeline,0);
        //var_dump($_timeline,$timeline);
        return array(
            'yday'  => ($today-$_timeline[0]),
            'today' => ($_timeline[0]==$today),
            'week'  => ($_timeline[1]==$week),
            'month' => ($_timeline[2]==$month),
        );
    }

    public static function get_category_ids($cid = "0",$all=true,$root_array=null) {
        $root_array OR $root_array = iCache::get('iCMS/category/rootid');
        $cids = array();
        is_array($cid) OR $cid = explode(',', $cid);
        foreach($cid AS $_id) {
            $cids+=(array)$root_array[$_id];
        }
        if($all){
            foreach((array)$cids AS $_cid) {
                $root_array[$_cid] && $cids+= self::get_category_ids($_cid,$all,$root_array);
            }
        }
        $cids = array_unique($cids);
        $cids = array_filter($cids);
        return $cids;
    }

    public static function sphinx(){
    	iPHP::import(iPHP_APP_CORE.'/sphinx.class.php');

		if(isset($GLOBALS['iSPH'])) return $GLOBALS['iSPH'];

		$hosts				= self::$config['sphinx']['host'];
		$GLOBALS['iSPH']	= new SphinxClient();
		if(strstr($hosts, 'unix:')){
			$hosts	= str_replace("unix://",'',$hosts);
			$GLOBALS['iSPH']->SetServer($hosts);
		}else{
			list($host,$port)=explode(':',$hosts);
			$GLOBALS['iSPH']->SetServer($host,(int)$port);
		}
		return $GLOBALS['iSPH'];
    }
    public static function TBAPI(){
    	iPHP::import(iPHP_APP_CORE.'/tbapi.class.php');

    	if(isset($GLOBALS['TBAPI'])) return $GLOBALS['TBAPI'];

		$GLOBALS['TBAPI'] = new TBAPI;
		return $GLOBALS['TBAPI'];
    }

    //------------------------------------
    public static function gotohtml($fp,$url='') {
        if(iPHP::$iTPL_MODE=='html'||empty($url)||stristr($url, '.php?')||iPHP_DEVICE!='desktop') return;

        @is_file($fp) && iPHP::gotourl($url);
    }

    public static function set_html_url($iurl){
        if(isset($GLOBALS['iPage'])) return;

        $GLOBALS['iPage']['url']  = $iurl->pageurl;
        $GLOBALS['iPage']['html'] = array('enable'=>true,'index'=>$iurl->href,'ext'=>$iurl->ext);
    }
    //过滤
    public static function filter(&$content){
        $filter  = iCache::get('iCMS/word.filter');//filter过滤
        $disable = iCache::get('iCMS/word.disable');//disable禁止

        //禁止关键词
        $subject = $content;
        $pattern = '/(~|`|!|@|\#|\$|%|\^|&|\*|\(|\)|\-|=|_|\+|\{|\}|\[|\]|;|:|"|\'|<|>|\?|\/|,|\.|\s|\n|。|，|、|；|：|？|！|…|-|·|ˉ|ˇ|¨|‘|“|”|々|～|‖|∶|＂|＇|｀|｜|〃|〔|〕|〈|〉|《|》|「|」|『|』|．|〖|〗|【|】|（|）|［|］|｛|｝|°|′|″|＄|￡|￥|‰|％|℃|¤|￠|○|§|№|☆|★|○|●|◎|◇|◆|□|■|△|▲|※|→|←|↑|↓|〓|＃|＆|＠|＾|＿|＼|№|)*/i';
        $subject = preg_replace($pattern, '', $subject);
        foreach ((array)$disable AS $val) {
            $val = trim($val);
            if(strpos($val,'::')!==false){
                list($tag,$start,$end) = explode('::',$val);
                if($tag=='NUM'){
                    $subject = cnum($subject);
                    if (preg_match('/\d{'.$start.','.$end.'}/i', $subject)) {
                        return $val;
                    }
                }
            }else{
                if ($val && preg_match("/".preg_quote($val, '/')."/i", $subject)) {
                    return $val;
                }
            }
        }
        //过滤关键词
        foreach ((array)$filter AS $k =>$val) {
            empty($val[1]) && $val[1]='***';
            $val[0] && $content = preg_replace("/".preg_quote($val[0], '/')."/i",$val[1],$content);
        }
    }

    public static function str_replace_limit($search, $replace, $subject, $limit=-1) {
        preg_match_all ("/<a[^>]*?>(.*?)<\/a>/si", $subject, $matches);//链接不替换
        $linkArray	= array_unique($matches[0]);
        $linkArray && $linkflip	= array_flip($linkArray);
        foreach((array)$linkflip AS $linkHtml=>$linkkey){
            $linkA[$linkkey]='###iCMS_LINK_'.rand(1,1000).'_'.$linkkey.'###';
        }
        $subject = str_replace($linkArray,$linkA,$subject);

        preg_match_all ("/<[\/\!]*?[^<>]*?>/si", $subject, $matches);
        $htmArray	= (array)array_unique($matches[0]);
        $htmArray && $htmflip    = array_flip($htmArray);
        foreach((array)$htmflip AS $kHtml=>$vkey){
            $htmA[$vkey]="###iCMS_HTML_".rand(1,1000).'_'.$vkey.'###';
        }
        $subject = str_replace($htmArray,$htmA,$subject);

        // constructing mask(s)...
        if (is_array($search)) {
            foreach ($search as $k=>$v) {
                $search[$k] = '`' . preg_quote($search[$k],'`') . '`i';
            }
        }else {
            $search = '`' . preg_quote($search,'`') . '`';
        }
        // replacement
        $replace && $replaceflip	= array_flip($replace);
        foreach((array)$replaceflip AS $rk=>$replacekey){
            $replaceA[$replacekey]="###iCMS_REPLACE_".rand(1,1000).'_'.$replacekey.'###';
        }
        // replacement
        $subject = preg_replace($search, $replaceA, $subject, $limit);
        $subject = str_replace($replaceA,$replace,$subject);
//        $subject = preg_replace($search, $replace, $subject, $limit);
        $subject = str_replace($htmA,$htmArray,$subject);
        $subject = str_replace($linkA,$linkArray,$subject);
        return $subject;
    }
}
