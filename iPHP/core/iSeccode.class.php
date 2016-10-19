<?php
// @header("Expires: -1");
// @header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
// @header("Pragma: no-cache");
// //框架初始化
// define('SECPATH',dirname(strtr(__FILE__,'\\','/'))."/");//绝对路径
// require SECPATH.'../config.php';	//框架初始化配置
// require SECPATH.'../iPHP.php';		//框架文件
// error_reporting(E_ALL ^ E_NOTICE);

// seccode::run();

class iSeccode {
    public static $config = array(
        'size'   => 24,//字体大小
        'width'  => 80,//图片宽度
        'height' => 30,//图片高度
        'line'   => 4, //干扰线数量
        'pixel'  => 90 //干扰点数量
    );
    public static $setcookie = array();

    protected static $noGD   = false;
    protected static $im     = null;
    protected static $code   = null;
    protected static $color  = null;

	//检查验证码
	public static function check($seccode, $destroy = false, $cookie_name = 'seccode') {
		$_seccode = iPHP::get_cookie($cookie_name);
		$_seccode && $cookie_seccode = authcode($_seccode, 'DECODE');
		$destroy && iPHP::set_cookie($cookie_name, '', -31536000);
		if (empty($cookie_seccode) || strtolower($cookie_seccode) != strtolower($seccode)) {
			return false;
		} else {
			return true;
		}
	}
    public static function run($pre=null){
		(extension_loaded('gd') && function_exists('gd_info') && function_exists('imagettftext')) OR self::$noGD = true;
        $name = 'seccode';
        $pre && $name = $pre.'_seccode';
        if(self::$noGD){
            self::icmsChar($name);
        }else{
            self::$code OR self::$code = self::__mkcode();
            iPHP::set_cookie($name, authcode(self::$code, 'ENCODE'));
            self::__image() OR self::icmsChar($name);
        }
    }
    private static function __image(){
        self::__background();
        self::__adulterate();
        self::__font();
        header("Expires: 0".PHP_EOL);
        header("Cache-Control: no-cache".PHP_EOL);
        header("Pragma: no-cache".PHP_EOL);
        if(function_exists('imagejpeg')) {
            header('Content-type:image/jpeg'.PHP_EOL);
            $void = imagejpeg(self::$im);
        } else if(function_exists('imagepng')) {
            header('Content-type:image/png'.PHP_EOL);
            $void = imagepng(self::$im);
        } else if(function_exists('imagegif')) {
            header('Content-type:image/gif'.PHP_EOL);
            $void = imagegif(self::$im);
        } else {
            return false;
        }
        imagedestroy(self::$im);
        return $void;
    }
    //生成随机
    private static function __mkcode() {
        $charset = '123456789abcdefghijkmnpqrstuvwxyzABCDEFGHIJKMNPQRSTUOVWXYZ';
        $_len = strlen($charset)-1;
        for ($i=0;$i<4;$i++) {
            $code.= $charset[rand(0,$_len)];
        }
        return $code;
    }

    //背景
    private static function __background() {
        //创建图片，并设置背景色
        self::$im = @imagecreatetruecolor(self::$config['width'], self::$config['height']);
        for($i = 0;$i < 3;$i++) {
            $start[$i]       = rand(200, 255);
            $end[$i]         = rand(100, 200);
            $step[$i]        = ($end[$i] - $start[$i]) / self::$config['width'];
            self::$color[$i] = $start[$i];
        }

        for($i = 0;$i < self::$config['width'];$i++) {
            $color = imagecolorallocate(self::$im, self::$color[0], self::$color[1], self::$color[2]);
            imageline(self::$im, $i, 0, $i, self::$config['height'], $color);
            self::$color[0] += $step[0];
            self::$color[1] += $step[1];
            self::$color[2] += $step[2];
        }
        self::$color[0] -= 20;
        self::$color[1] -= 20;
        self::$color[2] -= 20;
    }

    private static function __adulterate() {
        $linenums = self::$config['line'];
        for($i=0; $i<$linenums; $i++) {
            $color = imagecolorallocate(self::$im, self::$color[0], self::$color[1], self::$color[2]);
            $x  = rand(0, self::$config['width']);
            $y  = 0;
            $x2 = rand(0,self::$config['width']);
            $y2 = self::$config['height'];

            if($i%2) {
                imagearc(self::$im, $x, $y, $x2,$y2,rand(0, 360), rand(0, 360), $color);
                imagearc(self::$im, $x+1, $y,$x2+1,$y2,rand(0, 360), rand(0, 360), $color);
            } else {
                imageline(self::$im, $x, $y,$x2,$y2, $color);
                imageline(self::$im, $x+1, $y,$x2+1,$y2, $color);
            }
        }
        for ($i=0; $i < self::$config['pixel']; $i++) {
            $color = imagecolorallocate(self::$im, self::$color[0], self::$color[1], self::$color[2]);
            $x = rand(0,self::$config['width']);
            $y = rand(0,self::$config['height']);
            imagesetpixel(self::$im,$x,$y,$color);
            //imagefilledrectangle(self::$im,$x,$y, $x-1, $y-1, $color);
        }
    }

    private static function __font() {
        $font_file  = iPHP_CORE.'/seccode.otf';
        $widthtotal = 0;
        $font       = array();
        $font_size  = self::$config['size'];
        // $ttfb_box = imagettfbbox($font_size,$angle,$font_file,self::$code[0]);
	if (function_exists('imagettftext')) {
	        for ($i=0; $i < 4; $i++) {
	            $x          =(self::$config['width']/4)*$i;
	            $y          = $font_size+rand(0,4);
	            $angle      = mt_rand(0, 20);
	            $text_color = imagecolorallocate(self::$im, self::$color[0], self::$color[1], self::$color[2]);
					imagettftext(self::$im, $font_size, $angle, $x, $y, $text_color, $font_file, self::$code[$i]);
		}
    	} else {
    		return false;
        }
    }
    private static function icmsChar($name){
        iPHP::set_cookie($name, authcode('iCMS', 'ENCODE'));
        header('Content-type:image/gif'.PHP_EOL);
        exit(base64_decode('
        R0lGODlhUAAeALMAAAAAAI+Pj09PTzMzM8zMzK+vr39/fw8PD2ZmZj8/Px8fH9/f35mZmb+/v2ZmZv
        ///yH5BAEHAA8ALAAAAABQAB4AAAT/8MlJq7046827/2AojmS5IYi2MEYiGMyiBYa4GEFWtElvEJoE
        oHBpCADIAxIpAF6QjBACeQkok4qlgnhZHA4yyhQgKIQXhSOgZkEqQF5qZXpANCiNcaoKiE6mA04WBA
        MAexRLdx4BSxWMA2EWC4WHGgYAkCqFfhOGAAkfCgeFFaKRXYWKGQsACqcYXmCIBkeCGgxrQhQEhhy8
        b46CU6obuGwSaw29HEILuhPKXCcHbWxeAyAKlWsPzRvKKc8SyjkcC68P3A8FAOUk3MYnADLiD7HoHu
        qX+B/qpqtkJNR7wOiANBDqElAzoQ6XOwvDBAKwwAhTAH4YEoJiaO0AMEmj/yYMlLBgTKsXxDKyObCx
        hLoHlzhRiClyIiwGCLIsKZNBo4l0x1hhs6DgYzebHBoYKITJVic2CYa6PPYgIgWHFEZuKAmlmoQpP1
        /a+1RhgKyaIwh8eaWO3UEOuASJPRpJGdWjVzFWiDeL5DIPUyLNBTfhyCtx5PrdfRnYg9C+ZeeNrSSR
        Qsh8i4OS7XBE5twH8RpXqHfpLQa7FcQyonxhSktkdyWYYnmh3gJRTi/USn0XUMoJC45kguwIE4DfeH
        d9eViBqwCvjpRoK9CgegA1CdB9ticdw0gCWaZXr04LSXbozQ3oXIJkgEzic/p4X5h+PXtM7ycQ0Etg
        hwEDDfCn1xAq/PkHQ4A/Jajgggw26EEEADs='));
    }
}
