<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
* @version 6.0.0
* @$Id: setting.app.php 2365 2014-02-23 16:26:27Z coolmoo $
*/
class settingApp{
    function __construct() {
  //   	$this->apps	= array('index','article','tag','search','usercp','category','comment','favorite');
		// foreach (glob(iPHP_APP_DIR."/*/*.app.php") as $filename) {
  //           $path_parts = pathinfo($filename);
  //           $dirname    = str_replace(iPHP_APP_DIR.'/','',$path_parts['dirname']);
		// 	if (!in_array($dirname,array('admincp','usercp'))) {
  //               $app = str_replace('.app','',$path_parts['filename']);
		// 		in_array($app,$this->apps) OR array_push($this->apps,$app);
		// 	}
		// }
    }
    function do_iCMS(){
    	$config	= $this->get();
    	$config['site']['indexName'] OR $config['site']['indexName'] = 'index';
        $redis    = extension_loaded('redis');
        $memcache = extension_loaded('memcached');
    	include admincp::view("setting");
    }
    /**
     * [do_save 保存配置]
     * @return [type] [description]
     */
    function do_save(){
        $config = iS::escapeStr($_POST['config']);

        iFS::allow_files($config['FS']['allow_ext']) OR iPHP::alert("附件设置 > 允许上传类型设置不合法!");
        iFS::allow_files(trim($config['router']['html_ext'],'.')) OR iPHP::alert('URL设置 > 文件后缀设置不合法!');

        $config['router']['html_ext']   = '.'.trim($config['router']['html_ext'],'.');
        $config['router']['URL']        = trim($config['router']['URL'],'/');
        $config['router']['public_url'] = rtrim($config['router']['public_url'],'/');
        $config['router']['user_url']   = rtrim($config['router']['user_url'],'/');
        $config['router']['tag_url']    = trim($config['router']['tag_url'],'/');
        $config['router']['DIR']        = rtrim($config['router']['DIR'],'/').'/';
        $config['router']['html_dir']   = rtrim($config['router']['html_dir'],'/').'/';
        $config['router']['tag_dir']    = rtrim($config['router']['tag_dir'],'/').'/';
        $config['FS']['url']            = trim($config['FS']['url'],'/').'/';

        foreach ((array)$config['open'] as $platform => $value) {
            if($value['appid'] && $value['appkey']){
                $config['open'][$platform]['enable'] = true;
            }
        }

        $config['apps']	= $this->apps;
    	foreach($config AS $n=>$v){
    		$this->set($v,$n,0);
    	}
    	$this->write($config);
    	iPHP::success('更新完成','js:1');
    }
    /**
     * [cache 更新配置]
     * @return [type] [description]
     */
    function cache(){
        $config         = $this->get();
        $config['apps'] = $this->apps;
        $this->write($config);
    }
    /**
     * [app 其它应用配置接口]
     * @param  integer $appid [应用ID]
     * @param  [sting] $name   [应用名]
     */
    function app($appid=0,$name=null){
        $name===null && $name = admincp::$APP_NAME;
        $config = $this->get($appid,$name);
        include admincp::view($name.".config");
    }
    /**
     * [save 其它应用配置保存]
     * @param  integer $appid [应用ID]
     * @param  [sting] $app   [应用名]
     */
    function save($appid=0,$name=null){
        $name===null   && $name = admincp::$APP_NAME;
        empty($appid) && iPHP::alert("配置程序出错缺少APPID!");
        $config = iS::escapeStr($_POST['config']);
        $this->set($config,$name,$appid,false);
        $this->cache();
        iPHP::success('配置更新完成','js:1');
    }
    /**
     * [get 获取配置]
     * @param  integer $appid [应用ID]
     * @param  [type]  $name   [description]
     * @return [type]       [description]
     */
    function get($appid = NULL, $name = NULL) {
        if ($name === NULL) {
            $sql = $appid === NULL?'':"WHERE `appid`='$appid'";
            $rs  = iDB::all("SELECT * FROM `#iCMS@__config` $sql");
            foreach ($rs AS $c) {
                $value = $c['value'];
                strpos($c['value'], 'a:')===false OR $value = unserialize($c['value']);
                $config[$c['name']] = $value;
            }
            return $config;
        } else {
            $value = iDB::value("SELECT `value` FROM `#iCMS@__config` WHERE `appid`='$appid' AND `name` ='$name'");
            strpos($value, 'a:')===false OR $value = unserialize($value);
            return $value;
        }
    }
    /**
     * [set 更新配置]
     * @param [type]  $v     [description]
     * @param [type]  $n     [description]
     * @param [type]  $appid   [description]
     * @param boolean $cache [description]
     */
    function set($value, $name, $appid, $cache = false) {
        $cache && iCache::set('iCMS/config/' . $name, $value, 0);
        is_array($value) && $value = addslashes(serialize($value));
        $check  = iDB::value("SELECT `name` FROM `#iCMS@__config` WHERE `appid` ='$appid' AND `name` ='$name'");
        $fields = array('appid','name','value');
        $data   = compact ($fields);
        if($check===null){
            iDB::insert('config',$data);
        }else{
            iDB::update('config', $data, array('appid'=>$appid,'name'=>$name));
        }
    }
    /**
     * [write 配置写入文件]
     * @param  [type] $config [description]
     * @return [type]         [description]
     */
    function write($config=null){
        $config===null && $config = $this->get();
        $output = "<?php\ndefined('iPHP') OR exit('Access Denied');\nreturn ";
        $output.= var_export($config,true);
        $output.= ';';
        iFS::write(iPHP_APP_CONFIG,$output);
    }
    /**
     * [update 单个配置更新]
     * @param  [type] $k [description]
     * @return [type]    [description]
     */
    function update($k){
        $this->set(iCMS::$config[$k],$k,0);
        $this->write();
    }
    function view($that){
        include admincp::view('setting',true);
        // $app =
        // print_r(admincp::$APP_NAME);
    }
}
