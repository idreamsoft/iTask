<?php
/**
* iPHP - i PHP Framework
* Copyright (c) 2012 iiiphp.com. All rights reserved.
*
* @author coolmoo <iiiphp@qq.com>
* @site http://www.iiiphp.com
* @licence http://www.iiiphp.com/license
* @version 1.0.1
* @package iYun
* @$Id: iYun.class.php 2408 2014-04-30 18:58:23Z coolmoo $
*/
class iYun{
    public static $config = null;
    public static $error  = null;

    public static function init($config) {
        self::$config = $config;
    }
    public static function yun($vendor=null) {
        if($vendor===null) return false;

        $conf = self::$config['sdk'][$vendor];
        if($conf['AccessKey'] && $conf['SecretKey']){
            iPHP::import(iPHP_LIB.'/'.$vendor.'.php');
            return new $vendor($conf);
        }else{
            return false;
        }
    }
    public static function write($frp){
        if(!self::$config['enable']) return false;

        foreach ((array)self::$config['sdk'] as $vendor => $conf) {
            $fp     = ltrim(iFS::fp($frp,'-iPATH'),'/');
            $client = self::yun($vendor);
            if($client){
                $res    = $client->uploadFile($frp,$conf['Bucket'],$fp);
                $res    = json_decode($res,true);
                if($res['error']){
                    self::$error[$vendor] = array(
                        'action' => 'write',
                        'code'   => 0,
                        'state'  => 'Error',
                        'msg'    => $res['msg']
                    );
                }
            }
        }
        if(self::$config['local']){
            iFS::del($frp);
        }
    }
    public static function delete($frp) {
        if(!self::$config['enable']) return false;

        foreach ((array)self::$config['sdk'] as $vendor => $conf) {
            $fp     = ltrim(iFS::fp($frp,'-iPATH'),'/');
            $client = self::yun($vendor);
            if($client){
                $res = $client->delete($conf['Bucket'],$fp);
                $res = json_decode($res,true);
                if($res['error']){
                    self::$error[$vendor] = array(
                        'action' => 'delete',
                        'code'   => 0,
                        'state'  => 'Error',
                        'msg'    => $res['msg']
                    );
                }
            }
        }
    }
}
