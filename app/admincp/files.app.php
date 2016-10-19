<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
* @version 6.0.0
* @$Id: files.app.php 634 2013-04-03 06:02:53Z coolmoo $
*/
class filesApp{
    function __construct() {
	    $this->from		= iS::escapeStr($_GET['from']);
	    $this->callback	= iS::escapeStr($_GET['callback']);
		$this->click	= iS::escapeStr($_GET['click']);
        $this->target   = iS::escapeStr($_GET['target']);
        $this->format   = iS::escapeStr($_GET['format']);
    	$this->id		= (int)$_GET['id'];
	    $this->callback OR $this->callback	= 'icms';
        $this->upload_max_filesize = get_cfg_var("upload_max_filesize");
    }
	function do_add(){
        admincp::MP('FILE.UPLOAD','page');
		$this->id && $rs = iFS::getFileData('id',$this->id);
		include admincp::view("files.add");
	}
	function do_multi(){
        admincp::MP('FILE.UPLOAD','page');
		$file_upload_limit	= $_GET['UN']?$_GET['UN']:100;
		$file_queue_limit	= $_GET['QN']?$_GET['QN']:10;
		$file_size_limit	= (int)$this->upload_max_filesize;
        $file_size_limit OR iPHP::alert("检测到系统环境脚本上传文件大小限制为{$this->upload_max_filesize},请联系管理员");
        stristr($this->upload_max_filesize,'m') && $file_size_limit    = $file_size_limit*1024;
		include admincp::view("files.multi");
	}
	function do_iCMS(){
        admincp::MP('FILE.MANAGE','page');
    	$sql='WHERE 1=1 ';
        if($_GET['keywords']) {
            if($_GET['st']=="filename") {
                $sql.=" AND `filename` REGEXP '{$_GET['keywords']}'";
            }else if($_GET['st']=="indexid") {
                $sql.=" AND `indexid`='{$_GET['keywords']}'";
            }else if($_GET['st']=="userid") {
                $sql.=" AND `userid` = '{$_GET['keywords']}'";
            }else if($_GET['st']=="ofilename") {
                $sql.=" AND `ofilename` REGEXP '{$_GET['keywords']}'";
            }else if($_GET['st']=="size") {
                $sql.=" AND `size` REGEXP '{$_GET['keywords']}'";
            }
        }
		$_GET['indexid'] 	&& $sql.=" AND `indexid`='{$_GET['indexid']}'";
        $_GET['starttime'] 	&& $sql.=" and `time`>=UNIX_TIMESTAMP('".$_GET['starttime']." 00:00:00')";
        $_GET['endtime'] 	&& $sql.=" and `time`<=UNIX_TIMESTAMP('".$_GET['endtime']." 23:59:59')";

        isset($_GET['userid']) 	&& $uri.='&userid='.(int)$_GET['userid'];

        $orderby	= $_GET['orderby']?iS::escapeStr($_GET['orderby']):"id DESC";
        $maxperpage = $_GET['perpage']>0?(int)$_GET['perpage']:50;
		$total		= iPHP::total(false,"SELECT count(*) FROM `#iCMS@__filedata` {$sql}","G");
        iPHP::pagenav($total,$maxperpage,"个文件");
        $rs     = iDB::all("SELECT * FROM `#iCMS@__filedata` {$sql} order by {$orderby} LIMIT ".iPHP::$offset." , {$maxperpage}");
        $_count = count($rs);
    	include admincp::view("files.manage");
    }
    function do_IO(){
        $udir      = iS::escapeStr($_GET['udir']);
        $name      = iS::escapeStr($_GET['name']);
        $ext       = iS::escapeStr($_GET['ext']);
        iFS::check_ext($ext,0) OR iPHP::json(array('state'=>'ERROR','msg'=>'不允许的文件类型'));
        iFS::$callback = true;
        $_GET['watermark'] OR iFS::$watermark = false;
        $F = iFS::IO($name,$udir,$ext);
        $F ===false && iPHP::json(iFS::$ERROR);
        iPHP::json(array(
            "value"    => $F["path"],
            "url"      => iFS::fp($F['path'],'+http'),
            "fid"      => $F["fid"],
            "fileType" => $F["ext"],
            "image"    => in_array($F["ext"],array('gif','jpg','jpeg','png'))?1:0,
            "original" => $F["oname"],
            "state"    => ($F['code']?'SUCCESS':$F['state'])
        ));
    }
    function do_upload(){
        admincp::MP('FILE.UPLOAD','alert');
//iFS::$checkFileData = true;
    	$_POST['watermark'] OR iFS::$watermark = false;
        iFS::$callback = true;
    	if($this->id){
            iFS::$FileData = iFS::getFileData('id',$this->id);
            $F = iFS::upload('upfile');
            if($F && $F['size']!=iFS::$FileData->size){
                iDB::query("update `#iCMS@__filedata` SET `size`='".$F['size']."' WHERE `id` = '$this->id'");
            }
    	}else{
            $udir = ltrim($_POST['udir'],'/');
            $F    = iFS::upload('upfile',$udir);
    	}
        $array = ($F===false)?iFS::$ERROR:array(
            "value"    => $F["path"],
            "url"      => iFS::fp($F['path'],'+http'),
            "fid"      => $F["fid"],
            "fileType" => $F["ext"],
            "image"    => in_array($F["ext"],array('gif','jpg','jpeg','png'))?1:0,
            "original" => $F["oname"],
            "state"    => ($F['code']?'SUCCESS':$F['state'])
        );
		if($this->format=='json'){
	    	iPHP::json($array);
		}else{
			iPHP::js_callback($array);
		}
    }
    function do_download(){
        iFS::$userid   = false;
        $rs            = iFS::getFileData('id',$this->id);
        iFS::$redirect = true;
        $FileRootPath  = iFS::fp($rs->filepath,"+iPATH");
        iFS::check_ext($rs->filepath,true) OR iPHP::alert('文件类型不合法!');
        iFS::$userid = iMember::$userid;
        $fileresults   = iFS::remote($rs->ofilename);
    	if($fileresults){
    		iFS::mkdir(dirname($FileRootPath));
    		iFS::write($FileRootPath,$fileresults);
            iFS::$watermark = !isset($_GET['unwatermark']);
            iFS::watermark($rs->ext,$FileRootPath);
            iFS::yun_write($FileRootPath);

    		$_FileSize	= strlen($fileresults);
    		if($_FileSize!=$rs->size){
	    		iDB::query("update `#iCMS@__filedata` SET `size`='$_FileSize' WHERE `id` = '$this->id'");
    		}
    		iPHP::success("{$rs->ofilename} <br />重新下载到<br /> {$rs->filepath} <br />完成",'js:1',3);
    	}else{
    		iPHP::alert("下载远程文件失败!",'js:1',3);
    	}
    }
    function do_batch(){
        $idArray = (array)$_POST['id'];
        $idArray OR iPHP::alert("请选择要删除的文件");
        $ids     = implode(',',$idArray);
        $batch   = $_POST['batch'];
    	switch($batch){
    		case 'dels':
				iPHP::$break	= false;
	    		foreach($idArray AS $id){
	    			$this->do_del($id);
	    		}
	    		iPHP::$break	= true;
				iPHP::success('文件全部删除完成!','js:1');
    		break;
		}
	}
    function do_del($id = null){
        admincp::MP('FILE.DELETE','alert');
        $id ===null && $id = $this->id;
        $id OR iPHP::alert("请选择要删除的文件");
        $indexid = (int)$_GET['indexid'];
        $sql     = isset($_GET['indexid'])?"AND `indexid`='$indexid'":"";
        $rs      = iDB::row("SELECT * FROM `#iCMS@__filedata` WHERE `id` = '$id' {$sql} LIMIT 1;");
    	if($rs){
            $rs->filepath = rtrim($rs->path,'/').'/'.$rs->filename.'.'.$rs->ext;
            $FileRootPath = iFS::fp($rs->filepath,"+iPATH");
	    	if(iFS::del($FileRootPath)){
                iDB::query("DELETE FROM `#iCMS@__filedata` WHERE `id` = '$id' {$sql};");
                $msg = 'success:#:check:#:文件删除完成!';
	    		$_GET['ajax'] && iPHP::json(array('code'=>1,'msg'=>$msg));
	    	}else{
	    		$msg	= 'warning:#:warning:#:找不到相关文件,文件删除失败!<hr/>文件相关数据已清除';
	    		$_GET['ajax'] && iPHP::json(array('code'=>0,'msg'=>$msg));
	    	}
			iPHP::dialog($msg,'js:parent.$("#tr'.$id.'").remove();');
    	}
    	$msg	= '文件删除失败!';
    	$_GET['ajax'] && iPHP::json(array('code'=>0,'msg'=>$msg));
    	iPHP::alert($msg);
    }
    function do_mkdir(){
        admincp::MP('FILE.MKDIR') OR iPHP::json(array('code'=>0,'msg'=>'您没有相关权限!'));
    	$name	= $_POST['name'];
        strstr($name,'.')!==false	&& iPHP::json(array('code'=>0,'msg'=>'您输入的目录名称有问题!'));
        strstr($name,'..')!==false	&& iPHP::json(array('code'=>0,'msg'=>'您输入的目录名称有问题!'));
    	$pwd	= trim($_POST['pwd'],'/');
    	$dir	= iFS::path_join(iPATH,iCMS::$config['FS']['dir']);
    	$dir	= iFS::path_join($dir,$pwd);
    	$dir	= iFS::path_join($dir,$name);
    	file_exists($dir) && iPHP::json(array('code'=>0,'msg'=>'您输入的目录名称已存在,请重新输入!'));
    	if(iFS::mkdir($dir)){
    		iPHP::json(array('code'=>1,'msg'=>'创建成功!'));
    	}
		iPHP::json(array('code'=>0,'msg'=>'创建失败,请检查目录权限!!'));
    }
    function explorer($dir=NULL,$type=NULL){
        admincp::MP('FILE.BROWSE','page');
        $res    = iFS::folder($dir,$type);
        $dirRs  = $res['DirArray'];
        $fileRs = $res['FileArray'];
        $pwd    = $res['pwd'];
        $parent = $res['parent'];
        $URI    = $res['URI'];
        $navbar = false;
    	include admincp::view("files.explorer");
    }
    function do_seltpl(){
    	$this->explorer('template');
    }
    function do_browse(){
    	$this->explorer(iCMS::$config['FS']['dir']);
    }
    function do_picture(){
    	$this->explorer(iCMS::$config['FS']['dir'],array('jpg','png','gif','jpeg'));
    }
    function do_editpic(){
        admincp::MP('FILE.EDIT','page');
        $pic       = iS::escapeStr($_GET['pic']);
        //$pic OR iPHP::alert("请选择图片!");
        if($pic){
            $src       = iFS::fp($pic,'+http')."?".time();
            $srcPath   = iFS::fp($pic,'+iPATH');
            $fsInfo    = iFS::info($pic);
            $file_name = $fsInfo->filename;
            $file_path = $fsInfo->dirname;
            $file_ext  = $fsInfo->extension;
            $file_id   = 0;
            $rs        = iFS::getFileData('filename',$file_name);
            if($rs){
                $file_path = $rs->path;
                $file_id   = $rs->id;
                $file_ext  = $rs->ext;
            }
        }else{
            $file_name= md5(uniqid());
            $src      = false;
            $file_ext = 'jpg';
        }
        if($_GET['indexid']){
            $rs = iDB::all("SELECT * FROM `#iCMS@__filedata` where `indexid`='{$_GET['indexid']}' order by `id` ASC LIMIT 100");
            foreach ((array)$rs as $key => $value) {
                $filepath = $value['path'] . $value['filename'] . '.' . $value['ext'];
                $src[] = iFS::fp($filepath,'+http')."?".time();
            }
        }
        if($_GET['pics']){
            $src = explode(',', $_GET['pics']);
            if(count($src)==1){
                $src = $_GET['pics'];
            }
        }
        $max_size  = (int)$this->upload_max_filesize;
        stristr($this->upload_max_filesize,'m') && $max_size = $max_size*1024*1024;
        include admincp::view("files.editpic");
    }
    function do_preview(){
        $_GET['pic'] && $src = iFS::fp($_GET['pic'],'+http');
        include admincp::view("files.preview");
    }
    function do_deldir(){
        admincp::MP('FILE.DELETE','alert');
        $_GET['path'] OR iPHP::alert("请选择要删除的目录");
        strpos($_GET['path'], '..') !== false && iPHP::alert("目录路径中带有..");

        $hash         = md5($_GET['path']);
        $dirRootPath = iFS::fp($_GET['path'],'+iPATH');

        if(iFS::rmdir($dirRootPath)){
            $msg    = 'success:#:check:#:目录删除完成!';
            $_GET['ajax'] && iPHP::json(array('code'=>1,'msg'=>$msg));
        }else{
            $msg    = 'warning:#:warning:#:找不到相关目录,目录删除失败!';
            $_GET['ajax'] && iPHP::json(array('code'=>0,'msg'=>$msg));
        }
        iPHP::dialog($msg,'js:parent.$("#'.$hash.'").remove();');
    }
    function do_delfile(){
        admincp::MP('FILE.DELETE','alert');
        $_GET['path'] OR iPHP::alert("请选择要删除的文件");
        strpos($_GET['path'], '..') !== false && iPHP::alert("文件路径中带有..");

        $hash         = md5($_GET['path']);
        $FileRootPath = iFS::fp($_GET['path'],'+iPATH');
        if(iFS::del($FileRootPath)){
            $msg    = 'success:#:check:#:文件删除完成!';
            $_GET['ajax'] && iPHP::json(array('code'=>1,'msg'=>$msg));
        }else{
            $msg    = 'warning:#:warning:#:找不到相关文件,文件删除失败!';
            $_GET['ajax'] && iPHP::json(array('code'=>0,'msg'=>$msg));
        }
        iPHP::dialog($msg,'js:parent.$("#'.$hash.'").remove();');
    }
    function modal_btn($title='',$click='file',$target='template_index',$callback='',$do='seltpl',$from='modal'){
        $href = __ADMINCP__."=files&do={$do}&from={$from}&click={$click}&target={$target}&callback={$callback}";
        $_title=$title.'文件';
        $click=='dir' && $_title=$title.'目录';
        echo '<a href="'.$href.'" class="btn files_modal" data-toggle="modal" title="选择'.$_title.'"><i class="fa fa-search"></i> 选择</a>';
    }
}
