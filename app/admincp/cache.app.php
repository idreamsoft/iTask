<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
* @version 6.0.0
* @$Id: cache.app.php 2365 2014-02-23 16:26:27Z coolmoo $
*/
class cacheApp{
    public $acp = array('setting','prop.admincp','filter.admincp','keywords.admincp');
    function __construct() {
        $this->do_app();
    }
    function do_all(){
        foreach ($this->acp as $key => $acp) {
            $acp = iPHP::app($acp);
            $acp->cache();
        }
        $this->do_menu(false);
        $this->do_allcategory(false);
        $this->do_category(false);
        $this->do_pushcategory(false);
        $this->do_tagcategory(false);
        $this->do_tpl(false);
        iPHP::success('全部缓存更新完成');
    }
    function do_iCMS($dialog=true){
		if (in_array($_GET['acp'], $this->acp)) {
	    	$acp = iPHP::app($_GET['acp']);
	    	$acp->cache();
	    	$dialog && iPHP::success('更新完成');
		}
    }
    function do_menu($dialog=true){
    	admincp::$menu->cache();
    	$dialog && iPHP::success('更新完成','js:1');
    }
    function do_allcategory($dialog=true){
    	$category = iPHP::app('category.class');
    	$category->cache(true);
    	$dialog && iPHP::success('更新完成');
    }
    function do_category($dialog=true){
        $categoryApp = iPHP::app('category.admincp');
        $categoryApp->do_cache($dialog);
    }
    function do_pushcategory($dialog=true){
        $categoryApp = admincp::app('pushcategory');
        $categoryApp->do_cache($dialog);
    }
    function do_tagcategory($dialog=true){
        $categoryApp = admincp::app('tagcategory');
        $categoryApp->do_cache($dialog);
    }
    function do_tpl($dialog=true){
    	iPHP::clear_compiled_tpl();
    	$dialog && iPHP::success('清理完成');
    }
    function do_artCount($dialog=true){
        $app = iPHP::app('category.admincp');
    	$app->recount();
    	$dialog && iPHP::success('更新完成');
    }
    function do_app($dialog=true){
        iPHP::app('apps.class','static');
        APPS::cache();
    }
}
