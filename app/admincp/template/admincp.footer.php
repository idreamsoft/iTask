<?php /**
 * @package iCMS
 * @copyright 2007-2010, iDreamSoft
 * @license http://www.idreamsoft.com iDreamSoft
 * @author coolmoo <idreamsoft@qq.com>
 * @$Id: footer.php 2381 2014-03-21 04:03:07Z coolmoo $
 */
defined('iPHP') OR exit('What are you doing?');
//var_dump(iMember::$cpower);
$memory = memory_get_usage();
?>
  <div class="clearfloat"></div>
  <div class="iCMS-container">
    <span class="label label-success">
      使用内存:<?php echo iFS::sizeUnit($memory);?> 执行时间:<?php echo iPHP::timer_stop();?> s
    </span>
  </div>

<a id="scrollUp" href="#top"></a>

</body>
</html>
