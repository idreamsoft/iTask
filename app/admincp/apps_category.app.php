<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
* @version 6.0.0
* @$Id: apps_category.app.php 2374 2014-03-17 11:46:13Z coolmoo $
*/
defined('iPHP') OR exit('What are you doing?');

iPHP::app('category.admincp','import');

class apps_categoryApp extends categoryApp {

    function __construct() {
        parent::__construct();
        $this->category_name   = "节点";
        $this->_app            = 'software';
        $this->_app_name       = '应用程序';
        $this->_app_table      = 'software';
        $this->_app_cid        = 'cid';
        $this->_app_indexTPL   = '{iTPL}/software.index.htm';
        $this->_app_listTPL    = '{iTPL}/software.list.htm';
        $this->_app_contentTPL = '{iTPL}/software.htm';
    }
}
