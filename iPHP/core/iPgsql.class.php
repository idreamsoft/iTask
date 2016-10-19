<?php
/**
* iPHP - i PHP Framework
* Copyright (c) 2012 iiiphp.com. All rights reserved.
*
* @author coolmoo <iiiphp@qq.com>
* @site http://www.iiiphp.com
* @licence http://www.iiiphp.com/license
* @version 1.0.1
* @package iDB
* @$Id: iPgsql.class.php 2412 2014-05-04 09:52:07Z coolmoo $
*/
define('OBJECT', 'OBJECT');
define('ARRAY_A', 'ARRAY_A');
define('ARRAY_N', 'ARRAY_N');

defined('SAVEQUERIES') OR define('SAVEQUERIES', true);
defined('iPHP_DB_PORT') OR define('iPHP_DB_PORT', '5432');

class iDB{
    public static $show_errors = false;
    public static $num_queries = 0;
    public static $last_query;
    public static $col_info;
    public static $queries;
    public static $func_call;
    public static $last_result;
    public static $num_rows;
    public static $insert_id;
    public static $link;
    public static $config = null;

    private static $collate;
    private static $time_start;
    private static $last_error ;
    private static $result;

    public static function connect($flag=null) {
        extension_loaded('pgsql') OR die('您的 PHP 环境看起来缺少 MySQL 数据库部分，这对 iPHP 来说是必须的。');

        if(isset($GLOBALS['iPHP_DB'])){
            self::$link = $GLOBALS['iPHP_DB'];
            if(self::$link){
                if(self::$link->ping())
                    return self::$link;
            }
        }

        empty(self::$config) && self::$config = array(
            'HOST'       => iPHP_DB_HOST,
            'USER'       => iPHP_DB_USER,
            'PASSWORD'   => iPHP_DB_PASSWORD,
            'DB'         => iPHP_DB_NAME,
            'CHARSET'    => iPHP_DB_CHARSET,
            'PORT'       => iPHP_DB_PORT,
            'PREFIX'     => iPHP_DB_PREFIX,
            'PREFIX_TAG' => iPHP_DB_PREFIX_TAG
        );

        self::$link = @pg_connect("host=".self::$config['HOST']." port=".self::$config['PORT']." dbname=".self::$config['DB']." user=".self::$config['USER']." password=".self::$config['PASSWORD']);
        self::$link OR self::bail("<h1>数据库链接失败</h1><p>请检查 <em><strong>config.php</strong></em> 的配置是否正确!</p><ul><li>请确认主机支持PostgreSQL?</li><li>请确认用户名和密码正确?</li><li>请确认主机名正确?(一般为localhost)</li></ul><p>如果你不确定这些情况,请询问你的主机提供商.如果你还需要帮助你可以随时浏览 <a href='http://www.iiiphp.com'>iPHP 支持论坛</a>.</p>");
        defined('iPHP_DB_CHARSET') && self::query("set client_encoding to '".iPHP_DB_CHARSET."'");

        if($flag==='link'){
            return self::$link;
        }
        self::$link->connect_errno && self::bail("<h1>数据库连接失败</h1><p>请检查 <em><strong>config.php</strong></em> 的配置是否正确!</p><ul><li>请确认主机支持MySQL?</li><li>请确认用户名和密码正确?</li><li>请确认主机名正确?(一般为localhost)</li></ul><p>如果你不确定这些情况,请询问你的主机提供商.如果你还需要帮助你可以随时浏览 <a href='http://www.iiiphp.com'>iPHP 支持论坛</a>.</p>");

        $GLOBALS['iPHP_DB'] = self::$link;
        self::pre_set();
        if($flag===null){
            self::select_db();
        }
    }
    public static function pre_set() {
    }
    public static function select_db($var=false) {
    }

    // ==================================================================
    //	Basic Query	- see docs for more detail

    public static function query($query,$QT=NULL) {
        if(empty($query)){
            if (self::$show_errors) {
                self::bail("SQL IS EMPTY");
            } else {
                return false;
            }
        }

        self::$link OR self::connect();

        // filter the query, if filters are available
        // NOTE: some queries are made before the plugins have been loaded, and thus cannot be filtered with this method
        $query = str_replace(self::$config['PREFIX_TAG'],self::$config['PREFIX'], trim($query));
        $query = str_replace('`','', $query);

        if(preg_match("/limit\s*\d+,\s*\d+/is",$query)){
            $query = preg_replace("/limit\s*(\d+),\s*(\d+)/is", "limit $2 offset $1", $query);
        }
        // initialise return
        $return_val = 0;
        self::flush();

        // Log how the function was called
        self::$func_call = __CLASS__.'::query("'.$query.'")';

        // Keep track of the last query for debug..
        self::$last_query = $query;

        // Perform the query via std pgsql_query function..
        SAVEQUERIES && self::timer_start();

        self::$result = pg_query(self::$link,$query);


        self::$num_queries++;
        SAVEQUERIES && self::$queries[] = array('sql'=>$query, 'exec_time'=>self::timer_stop());

        if(!self::$result){
            // If there is an error then take note of it..
            return self::print_error();
        }
        self::$num_queries++;

        SAVEQUERIES && self::$queries[] = array( $query, self::timer_stop());

        if($QT=='get') return $result;


        $QH = strtoupper(substr($query,0,strpos($query, ' ')));
        if (in_array($QH,array('INSERT','DELETE','UPDATE','REPLACE','SET','CREATE','DROP','ALTER'))) {
            $rows_affected = pg_affected_rows (self::$result);
            // Take note of the insert_id
            if (in_array($QH,array("INSERT","REPLACE"))) {
                self::$insert_id = pg_last_oid(self::$result);
            }
            // Return number of rows affected
            $return_val = $rows_affected;
        } else {
            if($QT=="field") {
                $i = 0;
                while ($i < pg_num_fields(self::$result)) {
                    self::$col_info[$i] = pg_field_name(self::$result);
                    $i++;
                }
            }else {
                $num_rows = 0;
                while ( $row = pg_fetch_object(self::$result) ) {
                    self::$last_result[$num_rows] = $row;
                    $num_rows++;
                }
                // Log number of rows the query returned
                self::$num_rows = $num_rows;

                // Return number of rows selected
                $return_val = $num_rows;
            }
            pg_free_result(self::$result);
        }

        return $return_val;
    }
    /**
     * Insert an array of data into a table
     * @param string $table WARNING: not sanitized!
     * @param array $data should not already be SQL-escaped
     * @return mixed results of self::query()
     */
    public static function insert($table, $data) {
        $fields = array_keys($data);
        return self::query("INSERT INTO ".iPHP_DB_PREFIX_TAG."{$table} (`" . implode('`,`',$fields) . "`) VALUES ('".implode("','",$data)."')");
    }
    public static function insert_multi($table,$fields,$data) {
        $datasql = array();
        foreach ((array)$data as $key => $d) {
            $datasql[]= "('".implode("','",$d)."')";
        }
        if($datasql){
            return self::query("INSERT INTO ".iPHP_DB_PREFIX_TAG."{$table} (`" . implode('`,`',$fields) . "`) VALUES ".implode(',',$datasql));
        }
    }
    /**
     * Update a row in the table with an array of data
     * @param string $table WARNING: not sanitized!
     * @param array $data should not already be SQL-escaped
     * @param array $where a named array of WHERE column => value relationships.  Multiple member pairs will be joined with ANDs.  WARNING: the column names are not currently sanitized!
     * @return mixed results of self::query()
     */
    public static function update($table, $data, $where) {
        $bits = $wheres = array();
        foreach ( array_keys($data) as $k ){
            $bits[] = "`$k` = '$data[$k]'";
        }
        if ( is_array( $where ) ){
            foreach ( $where as $c => $v )
                $wheres[] = "$c = '" . addslashes( $v ) . "'";
        }else{
            return false;
        }
        return self::query("UPDATE ".iPHP_DB_PREFIX_TAG."{$table} SET " . implode( ', ', $bits ) . ' WHERE ' . implode( ' AND ', $wheres ));
    }
    /**
     * Get one variable from the database
     * @param string $query (can be null as well, for caching, see codex)
     * @param int $x = 0 row num to return
     * @param int $y = 0 col num to return
     * @return mixed results
     */
    public static function val($table, $field, $where) {
        $fields = $wheres = array();
        if ( is_array( $field ) ){
            foreach ( $field as $c => $f )
                $fields[] = "`$f`";
        }else{
            return false;
        }

        if ( is_array( $where ) ){
            foreach ( $where as $c => $v ){
                if(strpos($c,'!')===false){
                    $wheres[] = "$c = '" . addslashes( $v ) . "'";
                }else{
                    $c = str_replace('!', '', $c);
                    $wheres[] = "$c != '" . addslashes( $v ) . "'";
                }
            }
        }else{
            return false;
        }
        return self::value("SELECT ".implode( ', ', $fields )." FROM ".iPHP_DB_PREFIX_TAG."{$table} WHERE " . implode( ' AND ', $wheres ) . ' LIMIT 1;' );
    }
    public static function value($query=null, $x = 0, $y = 0) {
        self::$func_call = __CLASS__."::value(\"$query\",$x,$y)";
        $query && self::query($query);
        // Extract var out of cached results based x,y vals
        if ( !empty( self::$last_result[$y] ) ) {
            $values = array_values(get_object_vars(self::$last_result[$y]));
        }
        // If there is a value return it else return null
        return (isset($values[$x]) && $values[$x]!=='') ? $values[$x] : null;
    }

    /**
     * Get one row from the database
     * @param string $query
     * @param string $output ARRAY_A | ARRAY_N | OBJECT
     * @param int $y row num to return
     * @return mixed results
     */
    public static function row($query = null, $output = OBJECT, $y = 0) {
        self::$func_call = __CLASS__."::row(\"$query\",$output,$y)";
        $query && self::query($query);

        if ( !isset(self::$last_result[$y]) )
            return null;

        if ( $output == OBJECT ) {
            return self::$last_result[$y] ? self::$last_result[$y] : null;
        } elseif ( $output == ARRAY_A ) {
            return self::$last_result[$y] ? get_object_vars(self::$last_result[$y]) : null;
        } elseif ( $output == ARRAY_N ) {
            return self::$last_result[$y] ? array_values(get_object_vars(self::$last_result[$y])) : null;
        } else {
            self::print_error(__CLASS__."::row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N");
        }
    }

    /**
     * Return an entire result set from the database
     * @param string $query (can also be null to pull from the cache)
     * @param string $output ARRAY_A | ARRAY_N | OBJECT
     * @return mixed results
     */
    public static function all($query = null, $output = ARRAY_A) {
        self::$func_call = __CLASS__."::array(\"$query\", $output)";

        $query && self::query($query);

        // Send back array of objects. Each row is an object
        if ( $output == OBJECT ) {
            return self::$last_result;
        } elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
            if ( self::$last_result ) {
                $i = 0;
                foreach( (array) self::$last_result as $row ) {
                    if ( $output == ARRAY_N ) {
                        // ...integer-keyed row arrays
                        $new_array[$i] = array_values( get_object_vars( $row ) );
                    } else {
                        // ...column name-keyed row arrays
                        $new_array[$i] = get_object_vars( $row );
                    }
                    ++$i;
                }
                return $new_array;
            } else {
                return null;
            }
        }
    }

    /**
     * Gets one column from the database
     * @param string $query (can be null as well, for caching, see codex)
     * @param int $x col num to return
     * @return array results
     */
    public static function col($query = null , $x = 0) {
        $query && self::query($query);
        $new_array = array();
        // Extract the column values
        for ( $i=0; $i < count(self::$last_result); $i++ ) {
            $new_array[$i] = self::value(null, $x, $i);
        }
        return $new_array;
    }

    /**
     * Grabs column metadata from the last query
     * @param string $info_type one of name, table, def, max_length, not_null, primary_key, multiple_key, unique_key, numeric, blob, type, unsigned, zerofill
     * @param int $col_offset 0: col name. 1: which table the col's in. 2: col's max length. 3: if the col is numeric. 4: col's type
     * @return mixed results
     */
    public static function col_info($query = null ,$info_type = 'name', $col_offset = -1) {
        $query && self::query($query,"field");
        if ( self::$col_info ) {
            if ( $col_offset == -1 ) {
                $i = 0;
                //var_dump(self::$col_info);
                foreach(self::$col_info as $col ) {
                    $new_array[$i] = $col->{$info_type};
                    $i++;
                }
                return $new_array;
            } else {
                return self::$col_info[$col_offset]->{$info_type};
            }
        }
    }
    public static function version() {
        // Make sure the server has PostgreSQL 4.0
        $v = pg_version(self::$link);
		return $v['client'];
    }
    public static function debug($show=false){

    }

    // ==================================================================
    //  Kill cached query results

    public static function flush() {
        self::$last_result  = array();
        self::$col_info     = null;
        self::$last_query   = null;
    }
    /**
     * Starts the timer, for debugging purposes
     */
    public static function timer_start() {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        self::$time_start = $mtime[1] + $mtime[0];
        return true;
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
        return $time_total;
    }
    // ==================================================================
    //  Print SQL/DB error.

    public static function print_error($error = '') {
        self::$last_error = self::$link->error;
        $error OR $error  = self::$last_error;

        $error = htmlspecialchars($error, ENT_QUOTES);
        $query = htmlspecialchars(self::$last_query, ENT_QUOTES);
        // Is error output turned on or not..
        if ( self::$show_errors ) {
            self::bail("<strong>iPHP database error:</strong> [$error]<br /><code>$query</code>");
        } else {
            return false;
        }
    }
    /**
     * Wraps fatal errors in a nice header and footer and dies.
     * @param string $message
     */
    public static function bail($message){ // Just wraps errors in a nice header and footer
        if ( !self::$show_errors ) {
            return false;
        }
        trigger_error($message,E_USER_ERROR);
    }
}
