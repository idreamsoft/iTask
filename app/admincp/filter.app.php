<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
* @version 6.0.0
* @$Id: filter.app.php 2372 2014-03-16 07:24:56Z coolmoo $
*/
class filterApp{
    private $setting;
    function __construct() {
        $this->setting = admincp::app('setting');
    }
    function do_iCMS(){
        $filter  = $this->setting->get(0,'word.filter');
        $disable = $this->setting->get(0,'word.disable');
        foreach((array)$filter AS $k=>$val) {
            $filterArray[$k]=implode("=",(array)$val);
        }
    	include admincp::view("filter");
    }
    function do_save(){
        $filter  = explode("\n",$_POST['filter']);
        $disable = explode("\n",$_POST['disable']);
        $disable = array_unique($disable);

        foreach($filter AS $k=> $val) {
            $filterArray[$k] = explode("=",$val);
        }
        $this->setting->set($filterArray,'word.filter',0,true);
        $this->setting->set($disable,'word.disable',0,true);
        $this->cache();
        iPHP::success('更新完成');
    }
    function cache(){
        $filter  = $this->setting->get(0,'word.filter');
        $disable = $this->setting->get(0,'word.disable');
        foreach((array)$filter AS $k=>$val) {
            $filterArray[$k]=implode("=",(array)$val);
        }
    	iCache::set('iCMS/word.filter',$filterArray,0);
    	iCache::set('iCMS/word.disable',$disable,0);
    }
}
