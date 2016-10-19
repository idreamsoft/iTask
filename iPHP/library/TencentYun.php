<?php
/**
 * 腾讯云万象图片服务 iCMS接口 统一
 */
require dirname(__file__) .'/Tencentyun/Http.php';
require dirname(__file__) .'/Tencentyun/Conf.php';
require dirname(__file__) .'/Tencentyun/Auth.php';
require dirname(__file__) .'/Tencentyun/ImageV2.php';
// require dirname(__file__) .'/Tencentyun/Video.php';
class TencentYun
{

    public function __construct($conf)
    {
        Conf::$SECRET_ID  = $conf['AccessKey'];
        Conf::$SECRET_KEY = $conf['SecretKey'];
        Conf::$APPID      = $conf['AppId'];
    }
    /**
     * [uploadFile 上传文件接口]
     * @param  [type] $filePath [文件路径]
     * @param  [type] $bucket   [自定义空间名称]
     * @param  [type] $key      [null]
     * @return [type]           [description]
     */
    public function uploadFile($filePath,$bucket,$key=null)
    {
        $uploadRet = ImageV2::stat($bucket, $key);
        $uploadRet['code'] && $uploadRet = ImageV2::upload($filePath,$bucket,$key);
        return json_encode(array(
                'error' => $uploadRet['code'],
                'msg'   => $uploadRet
        ));
    }
    public function delete($bucket,$key)
    {
        $ret = ImageV2::del($bucket,$key);
        return json_encode(array(
                'error' => $ret['code'],
                'msg'   => $ret
        ));
    }
}
