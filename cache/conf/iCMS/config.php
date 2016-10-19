<?php
defined('iPHP') OR exit('Access Denied');
return array (
  'site' =>
  array (
    'name' => '七日志',
    'seotitle' => '用文字记录生活 - 伤感日志/心情日志/qq日志/情感日志/空间日志/非主流日志/日志大全',
    'keywords' => '七日志,微小说,日志大全,qq空间日志,伤心日志,分手日志,爱情日志',
    'description' => '你可以在七日志网发表自己的心情日记，记录您的情感经历，分享喜欢的情感文章。也可以欣赏到大家的心情日记和伤感日志、伤感文章、心情随笔、空间日志和心情散文',
    'icp' => '',
  ),
  'router' =>
  array (
    'URL' => 'http://icms62.idreamsoft.com',
    'DIR' => '/',
    404 => 'http://icms62.idreamsoft.com/404.html',
    'public_url' => 'http://icms62.idreamsoft.com/public',
    'user_url' => 'http://icms62.idreamsoft.com/usercp',
    'html_dir' => '/',
    'html_ext' => '.html',
    'speed' => '50',
    'rewrite' => '0',
    'tag_url' => 'http://icms62.idreamsoft.com/tag',
    'tag_rule' => '{TKEY}/',
    'tag_dir' => '/',
  ),
  'cache' =>
  array (
    'enable' => '1',
    'engine' => 'file',
    'host' => '',
    'time' => '300',
    'compress' => '1',
  ),
  'FS' =>
  array (
    'url' => 'http://res.rizhi7.com/',
    'dir' => '../res',
    'dir_format' => 'Y/m-d/H',
    'allow_ext' => 'gif,jpg,rar,swf,jpeg,png',
    'yun' =>
    array (
      'enable' => '0',
      'local' => '0',
      'sdk' =>
      array (
        'QiNiuYun' =>
        array (
          'Bucket' => '',
          'AccessKey' => '',
          'SecretKey' => '',
        ),
        'TencentYun' =>
        array (
          'AppId' => '',
          'Bucket' => '',
          'AccessKey' => '',
          'SecretKey' => '',
        ),
      ),
    ),
  ),
  'thumb' =>
  array (
    'size' => '',
  ),
  'watermark' =>
  array (
    'enable' => '0',
    'width' => '140',
    'height' => '140',
    'allow_ext' => '',
    'pos' => '0',
    'x' => '0',
    'y' => '0',
    'img' => 'watermark.png',
    'text' => 'iCMS',
    'font' => '',
    'fontsize' => '12',
    'color' => '#000000',
    'transparent' => '80',
  ),
  'user' =>
  array (
    'register' =>
    array (
      'enable' => '1',
      'seccode' => '1',
      'interval' => '3600',
    ),
    'login' =>
    array (
      'enable' => '1',
      'seccode' => '1',
      'interval' => '86400',
    ),
    'post' =>
    array (
      'seccode' => '1',
      'interval' => '600',
    ),
    'agreement' => '',
    'coverpic' => '/ui/coverpic.jpg',
  ),
  'publish' =>
  array (
    'autoformat' => '0',
    'catch_remote' => '0',
    'remote' => '1',
    'autopic' => '1',
    'autodesc' => '1',
    'descLen' => '100',
    'autoPage' => '0',
    'AutoPageLen' => '1000',
    'repeatitle' => '0',
    'showpic' => '1',
  ),
  'comment' =>
  array (
    'enable' => '1',
    'examine' => '1',
    'seccode' => '1',
  ),
  'debug' =>
  array (
    'php' => '1',
    'tpl' => '1',
    'sql' => '0',
  ),
  'time' =>
  array (
    'zone' => 'Asia/Shanghai',
    'cvtime' => '0',
    'dateformat' => 'Y-m-d H:i:s',
  ),
  'apps' =>
  array (
    0 => 'index',
    1 => 'article',
    2 => 'tag',
    3 => 'search',
    4 => 'usercp',
    5 => 'category',
    6 => 'comment',
    7 => 'favorite',
    8 => 'public',
    9 => 'user',
    10 => 'weixin',
  ),
  'other' =>
  array (
    'py_split' => '',
    'keyword_limit' => '-1',
    'sidebar_enable' => '1',
    'sidebar' => '1',
  ),
  'system' =>
  array (
    'patch' => '2',
  ),
  'defaults' =>
  array (
    'source' =>
    array (
      0 => 'asd',
    ),
    'author' =>
    array (
      0 => 'dfg',
    ),
  ),
  'word.filter' =>
  array (
    0 =>
    array (
      0 => '',
    ),
  ),
  'word.disable' =>
  array (
    0 => '小姐联系电话',
  ),
  'sphinx' =>
  array (
    'host' => 'unix:///tmp/sphinx.sock',
    'index' => 'rizhi7_article rizhi7_article_delta',
  ),
  'open' =>
  array (
    'WX' =>
    array (
      'appid' => '',
      'appkey' => '',
      'redirect' => '',
    ),
    'QQ' =>
    array (
      'appid' => '100307545',
      'appkey' => '4c66ff50773286e85a339526a46c5e22',
      'redirect' => '',
      'enable' => true,
    ),
    'WB' =>
    array (
      'appid' => '1667718415',
      'appkey' => 'f8e973deb9a1d49c6cbdf653227632ec',
      'redirect' => '',
      'enable' => true,
    ),
    'TB' =>
    array (
      'appid' => '',
      'appkey' => '',
      'redirect' => '',
    ),
  ),
  'template' =>
  array (
    'index_mode' => '1',
    'index_rewrite' => '0',
    'index' => '{iTPL}/index.htm',
    'index_name' => 'index',
    'desktop' =>
    array (
      'tpl' => 'www/desktop',
    ),
    'mobile' =>
    array (
      'agent' => 'WAP,Smartphone,Mobile,UCWEB,Opera Mini,Windows CE,Symbian,SAMSUNG,iPhone,Android,BlackBerry,HTC,Mini,LG,SonyEricsson,J2ME,MOT',
      'domain' => 'http://m.rizhi7.com',
      'tpl' => 'www/mobile',
    ),
    'device' =>
    array (
      0 =>
      array (
        'name' => 'weixin',
        'ua' => 'MicroMessenger',
        'domain' => 'http://icms62.idreamsoft.com',
        'tpl' => 'www/weixin',
      ),
    ),
  ),
  'api' =>
  array (
    'baidu' =>
    array (
      'sitemap' =>
      array (
        'site' => 'icms62.idreamsoft.com',
        'access_token' => 'EEc7PqStnvqBHLqA',
        'sync' => '1',
      ),
    ),
    'weixin' =>
    array (
      'appid' => '',
      'appsecret' => '',
      'token' => 'rizhi7comapi',
      'name' => '',
      'account' => '',
      'qrcode' => '',
      'subscribe' => '',
      'unsubscribe' => '',
    ),
  ),
  'article' =>
  array (
    'editor' => '0',
    'filter' => '0',
    'pic_center' => '1',
    'pic_next' => '1',
    'pageno_incr' => '',
    'prev_next' => '0',
  ),
  'mail' =>
  array (
    'host' => '',
    'secure' => '',
    'port' => '25',
    'username' => '',
    'password' => '',
    'setfrom' => '',
    'replyto' => '',
  ),
);
