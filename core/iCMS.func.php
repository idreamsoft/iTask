<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
* @version 6.0.0
* @$Id: iCMS.func.php 2412 2014-05-04 09:52:07Z coolmoo $
*/

function small($sfp,$w='',$h='',$scale=true) {
    if(empty($sfp)){
        echo iCMS_FS_URL.'1x1.gif';
        return;
    }
    if(strpos($sfp,'_')!==false){
        if(preg_match('|.+\d+x\d+\.jpg$|is', $sfp)!=0){
            echo $sfp;
            return;
        }
    }
    $uri = parse_url(iCMS_FS_URL);
    if(stripos($sfp,$uri['host']) === false){
        echo $sfp;
        return;
    }

    if(empty(iCMS::$config['thumb']['size'])){
        echo $sfp;
        return;
    }

    $size_map = explode("\n", iCMS::$config['thumb']['size']);
    $size_map = array_map('trim', $size_map);
    $size_map = array_flip($size_map);
    $size     = $w.'x'.$h;
    if(!isset($size_map[$size])){
        echo $sfp;
        return;
    }

    if(iCMS::$config['FS']['yun']['enable']){
        if(iCMS::$config['FS']['yun']['sdk']['QiNiuYun']['Bucket']){
            echo $sfp.'?imageView2/1/w/'.$w.'/h/'.$h;
            return;
        }
        if(iCMS::$config['FS']['yun']['sdk']['TencentYun']['Bucket']){
            echo $sfp.'?imageView2/2/w/'.$w.'/h/'.$h;
            return;
        }
    }
    echo $sfp.'_'.$size.'.jpg';
}

function baidu_ping($urls) {
    $site          = iCMS::$config['api']['baidu']['sitemap']['site'];
    $access_token  = iCMS::$config['api']['baidu']['sitemap']['access_token'];
    if(empty($site)||empty($access_token)){
        return false;
    }
    $api     ='http://data.zz.baidu.com/urls?site='.$site.'&token='.$access_token;
    $ch      = curl_init();
    $options =  array(
        CURLOPT_URL            => $api,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => implode("\n",(array)$urls),
        CURLOPT_HTTPHEADER     => array('Content-Type: text/plain'),
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $json   = json_decode($result);
    if($json->success){
        return true;
    }
    return $json;
}
function get_pic($src,$size=0,$thumb=0){
    if(empty($src)) return array();

    if(stripos($src, '://')!== false){
        return array(
            'src' => $src,
            'url' => $src,
            'width' => 0,
            'height' => 0,
        );
    }

    $data = array(
        'src' => $src,
        'url' => iFS::fp($src,'+http'),
    );
    if($size){
        $data['width']  = $size['w'];
        $data['height'] = $size['h'];
    }
    if($size && $thumb){
        $data+= bitscale(array(
            "tw" => (int)$thumb['width'],
            "th" => (int)$thumb['height'],
            "w" => (int)$size['w'],
            "h" => (int)$size['h'],
        ));
    }
    return $data;
}
function get_twh($width=null,$height=null){
    $ret    = array();
    $width  ===null OR $ret['width'] = $width;
    $height ===null OR $ret['height'] = $height;
    return $ret;
}
function autoformat($html){
    $html = stripslashes($html);
    $html = preg_replace(array(
    '@on(\w+)=(["\']?)+\\1@is','@style=(["|\']?)+\\1@is',
    '@<script[^>]*>.*?</script>@is','@<style[^>]*>.*?</style>@is',

    '@<br[^>]*>@is',
    '@<div[^>]*>(.*?)</div>@is','@<p[^>]*>(.*?)</p>@is',
    '@<b[^>]*>(.*?)</b>@is','@<strong[^>]*>(.*?)</strong>@is',
    '@<img[^>]+src=(["\']?)(.*?)\\1[^>]*?>@is',
    ),array('','','','',
    "\n[br]\n",
    "$1\n","$1\n",
    "[b]$1[/b]","[b]$1[/b]",
    "\n[img]$2[/img]\n",
    ),$html);

    if (stripos($html,'<embed') !== false){
        preg_match_all("@<embed[^>]*>@is", $html, $embed_match);
        foreach ((array)$embed_match[0] as $key => $value) {
            preg_match("@.*?src\s*=[\"|'|](.*?)[\"|'|]@is", $value, $src_match);
            preg_match("@.*?class\s*=[\"|'|](.*?)[\"|'|]@is", $value, $class_match);
            preg_match("@.*?width\s*=[\"|'|](\d+)[\"|'|]@is", $value, $width_match);
            preg_match("@.*?height\s*=[\"|'|](\d+)[\"|'|]@is", $value, $height_match);
            $embed_width = $width_match[1];
            $embed_height = $height_match[1];
            if($class_match[1]=='edui-faked-music'){
                empty($embed_width) && $embed_width = "400";
                empty($embed_height) && $embed_height = "95";
                $html = str_replace($value,'[music='.$embed_width.','.$embed_height.']'.$src_match[1].'[/music]',$html);
            }else{
                empty($embed_width) && $embed_width = "500";
                empty($embed_height) && $embed_height = "450";
                $html = str_replace($value,'[video='.$embed_width.','.$embed_height.']'.$src_match[1].'[/video]',$html);
            }
        }
    }
    $html = str_replace(array("&nbsp;","　"),'',$html);
    $html = preg_replace('@<[/\!]*?[^<>]*?>@is','',$html);
    $html = ubb2html($html);
    $html = autoclean($html);
    return $html;
}
function ubb2html($content){
    return preg_replace(array(
    '@\[br\]@is',
    '@\[img\](.*?)\[/img\]@is',
    '@\[b\](.*?)\[/b\]@is',
    '@\[url=([^\]]+)\](.*?)\[/url\]@is',
    '@\[url=([^\]|#]+)\](.*?)\[/url\]@is',
    '@\[music=(\d+),(\d+)\](.*?)\[/music\]@is',
    '@\[video=(\d+),(\d+)\](.*?)\[/video\]@is',
    ),array(
    '<br />',
    '<img src="$1" />','<strong>$1</strong>','<a target="_blank" href="$1">$2</a>','$2',
    '<embed type="application/x-shockwave-flash" class="edui-faked-music" pluginspage="http://www.macromedia.com/go/getflashplayer" src="$3" width="$1" height="$2" wmode="transparent" play="true" loop="false" menu="false" allowscriptaccess="never" allowfullscreen="true"/>',
    '<embed type="application/x-shockwave-flash" class="edui-faked-video" pluginspage="http://www.macromedia.com/go/getflashplayer" src="$3" width="$1" height="$2" wmode="transparent" play="true" loop="false" menu="false" allowscriptaccess="never" allowfullscreen="true"/>'
    ),$content);
}
function autoclean($html){
    $elArray = explode("\n",$html);
    $elArray = array_map("trim", $elArray);
    $elArray = array_filter($elArray);
    if(empty($elArray)){
        return false;
    }

    $stack     = array();
    $htmlArray = array();
    foreach($elArray as $hkey=>$el){
        $el = preg_replace('@<img\ssrc=""\s/>@is','',$el);
        $el = trim($el);
        if($el===''){
            continue;
        }
        if($el=="#--iCMS.PageBreak--#"){
            $htmlArray[$hkey] = $el;
            continue;
        }
        if($el=='<br />'){
            $stack['br']++;
            if($stack['br']===1){
                $htmlArray[$hkey] = '<p><br /></p>';
            }
            continue;
        }
        $stack['br'] = 0;
        if(preg_match('@^<[/]*(\w+)>$@is', $el)){
            $stack['el']++;
            if (stripos($ek,'</') !== false){
                $stack['el'] = 0;
            }
            $htmlArray[$hkey] = $el;
            continue;
        }
        if(preg_match('@^<(\w+)>\s*</\\1>$@is', $el)){
            continue;
        }
        $el = preg_replace(array(
            '@(<(\w+)>\s*</\\2>\n*)*@is',
            '@(<[/]*(\w+)></\\1>\n*)*@is',
            '@(<(\w+)>\s*</\\1>\n*)*@is',
        ),'',$el);

        if($el){
            if($stack['el']===1){
                $htmlArray[$hkey] = $el;
            }else{
                $htmlArray[$hkey] = '<p>'.$el.'</p>';
            }
        }
    }
    reset ($htmlArray);
    $html = implode('',(array)$htmlArray);
    return $html;
}
function cnum($subject){
    $searchList = array(
        array('ⅰ','ⅱ','ⅲ','ⅳ','ⅴ','ⅵ','ⅶ','ⅷ','ⅸ','ⅹ'),
        array('㈠','㈡','㈢','㈣','㈤','㈥','㈦','㈧','㈨','㈩'),
        array('①','②','③','④','⑤','⑥','⑦','⑧','⑨','⑩'),
        array('一','二','三','四','五','六','七','八','九','十'),
        array('零','壹','贰','叁','肆','伍','陆','柒','捌','玖','拾'),
        array('Ⅰ','Ⅱ','Ⅲ','Ⅳ','Ⅴ','Ⅵ','Ⅶ','Ⅷ','Ⅸ','Ⅹ','Ⅺ','Ⅻ'),
        array('⑴','⑵','⑶','⑷','⑸','⑹','⑺','⑻','⑼','⑽','⑾','⑿','⒀','⒁','⒂','⒃','⒄','⒅','⒆','⒇'),
        array('⒈','⒉','⒊','⒋','⒌','⒍','⒎','⒏','⒐','⒑','⒒','⒓','⒔','⒕','⒖','⒗','⒘','⒙','⒚','⒛')
    );
    $replace = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20);
    foreach ($searchList as $key => $search) {
        $subject = str_replace($search, $replace, $subject);
    }

    return $subject;
}
function archive_date($date){
    $limit = time() - $date;
    if($limit <= 86400){
        return '今天';
    }else if($limit > 86400 && $limit<=172800){
        return '昨天';
    }else{
        //return get_date($date,'dm');
        return '<span class="day">'.get_date($date,'d').'</span><span class="mon">'.get_date($date,'m').'月</span>';
    }
}
function key2num($resource){
    $sort_key = 0;
    // $_release = array();
    foreach ((array)$resource as $key => $value) {
        $_resource[$sort_key]= $value;
        // $_release[$sort_key] = $value['items'][0];
        ++$sort_key;
    }
    return $_resource;
}

