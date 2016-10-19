<?php
/**
* iPHP - i PHP Framework
* Copyright (c) 2012 iiiphp.com. All rights reserved.
*
* @author coolmoo <iiiphp@qq.com>
* @site http://www.iiiphp.com
* @licence http://www.iiiphp.com/license
* @version 1.0.1
* @package iSession
* @$Id: iSession.class.php 2290 2013-11-21 03:49:19Z coolmoo $
* CREATE TABLE `sessions` (
*   `session_id` varchar(255) NOT NULL DEFAULT '',
*   `expires` int(10) unsigned NOT NULL DEFAULT '0',
*   `data` text,
*   PRIMARY KEY (`session_id`)
* ) ENGINE=MyISAM DEFAULT CHARSET=utf8
*/

class iSession {
    // session-lifetime
    public static $lifeTime = null;

    public static function open($savePath, $sessName) {
        // get session-lifetime
        if(iSession::$lifeTime === null && function_exists('ini_get')){
            iSession::$lifeTime = @ini_get("session.gc_maxlifetime");
        }
        if(empty(iSession::$lifeTime)){
            iSession::$lifeTime = (int)iPHP_COOKIE_TIME;
        }

        if(defined('iDB')){
            exit("iSession requires iDB class");
        }
        return true;
    }
    public static function close() {
        iSession::gc();
        // close database-connection
        iDB::flush();
        return true;
    }
    public static function read($session_id) {
        $data = iDB::value("
            SELECT data FROM ".iPHP_DB_PREFIX_TAG."sessions
            WHERE session_id = '$session_id'
            AND expires > ".time()
        );
        // return data or an empty string at failure
        if($data){
            return $data;
        }
        return '';
    }
    public static function write($session_id,$data) {
        // new session-expire-time
        $expires = time() + iSession::$lifeTime;
        // is a session with this id in the database?
        $res = iDB::value("
            SELECT `expires` FROM ".iPHP_DB_PREFIX_TAG."sessions
            WHERE session_id = '$session_id'"
        );
        // if yes,
        if($res) {
            // ...update session-data
            iDB::query("
                UPDATE ".iPHP_DB_PREFIX_TAG."sessions
                SET expires = '$expires',
                data = '$data'
                WHERE session_id = '$session_id'"
            );
        } else {// if no session-data was found,
            // create a new row
            iDB::query("
                INSERT INTO ".iPHP_DB_PREFIX_TAG."sessions
                (session_id,expires,data)
                VALUES('$session_id','$expires','$data')"
            );
        }
        return true;
    }
    public static function destroy($session_id) {
        // delete session-data
        iDB::query("
            DELETE FROM ".iPHP_DB_PREFIX_TAG."sessions
            WHERE session_id = '$session_id'"
        );
        return true;
    }
    public static function gc($sessMaxLifeTime=0) {
        // delete old sessions
        iDB::query("
            DELETE FROM ".iPHP_DB_PREFIX_TAG."sessions
            WHERE expires < ".time()
        );
        return true;
    }
}

//memcached
// ini_set("session.save_handler", "memcached");
// ini_set("session.save_path", "127.0.0.1:11211");

// redis
// ini_set("session.save_handler", "redis");
// ini_set("session.save_path", "tcp://127.0.0.1:6379");
// ini_set("session.save_path", "tcp://IPADDRESS:PORT?auth=REDISPASSWORD");
// ini_set("session.save_path", "tcp://host1:6379?weight=1, tcp://host2:6379?weight=2&timeout=2.5, tcp://host3:6379?weight=2");
// ini_set("session.save_path", "unix:///var/run/redis/redis.sock?persistent=1&weight=1&database=0");
//
if(function_exists('ini_set')){
    @ini_set('session.use_cookies',1);
    @ini_set('session.gc_probability',1);
    @ini_set('session.gc_divisor',100);
    @ini_set('session.gc_maxlifetime',iPHP_COOKIE_TIME);
    @ini_set('session.cookie_lifetime',iPHP_COOKIE_TIME);
}

session_set_save_handler(
    array('iSession','open'),
    array('iSession','close'),
    array('iSession','read'),
    array('iSession','write'),
    array('iSession','destroy'),
    array('iSession','gc')
);
session_start();
