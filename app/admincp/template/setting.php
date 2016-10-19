<?php /**
 * @package iCMS
 * @copyright 2007-2015, iDreamSoft
 * @license http://www.idreamsoft.com iDreamSoft
 * @author coolmoo <idreamsoft@qq.com>
 * @$Id: setting.php 2412 2014-05-04 09:52:07Z coolmoo $
 */
defined('iPHP') OR exit('What are you doing?');
admincp::head();
?>
<script type="text/javascript">

$(function(){
  $(document).on("click",".del_device",function(){
      $(this).parent().parent().remove();
  });
  $(".add_template_device").click(function(){
    var TD  = $("#template_device"),count = $('.device',TD).length;
    var tdc = $(".template_device_clone").clone(true).removeClass("hide template_device_clone").addClass('device');
    $('input',tdc).removeAttr("disabled").each(function(){
      this.id   = this.id.replace("{key}",count);
      this.name = this.name.replace("{key}",count);
    });
    var fmhref  = $('.files_modal',tdc).attr("href").replace("{key}",count);
    $('.files_modal',tdc).attr("href",fmhref);
    tdc.appendTo(TD);
    return false;
  });
});
function modal_tplfile(el,a){

  if(!el) return;
  if(!a.checked) return;

  var e   = $('#'+el)||$('.'+el);
  var def = $("#template_desktop_tpl").val();
  var val = a.value.replace(def+'/', "{iTPL}/");
  e.val(val);
  return 'off';
}
</script>

<div class="iCMS-container">
  <div class="widget-box">
    <div class="widget-title"> <span class="icon"> <i class="fa fa-cog"></i> </span>
      <ul class="nav nav-tabs" id="setting-tab">
        <li class="active"><a href="#setting-base" data-toggle="tab">基本信息</a></li>
        <li><a href="#setting-tpl" data-toggle="tab">模板</a></li>
        <li><a href="#setting-url" data-toggle="tab">URL</a></li>
        <li><a href="#setting-tag" data-toggle="tab">标签</a></li>
        <li><a href="#setting-cache" data-toggle="tab">缓存</a></li>
        <li><a href="#setting-file" data-toggle="tab">附件</a></li>
        <li><a href="#setting-thumb" data-toggle="tab">缩略图</a></li>
        <li><a href="#setting-watermark" data-toggle="tab">水印</a></li>
        <li><a href="#setting-user" data-toggle="tab">用户</a></li>
        <li><a href="#setting-publish" data-toggle="tab">发布</a></li>
        <li><a href="#setting-comment" data-toggle="tab">评论</a></li>
        <li><a href="#setting-time" data-toggle="tab">时间</a></li>
        <li><a href="#setting-other" data-toggle="tab">其它</a></li>
        <li><a href="#setting-patch" data-toggle="tab">更新</a></li>
        <li><a href="#setting-grade" data-toggle="tab">高级</a></li>
        <li><a href="#setting-mail" data-toggle="tab">邮件</a></li>
        <?php APPS::setting('tabs');?>
      </ul>
    </div>
    <div class="widget-content nopadding iCMS-setting">
      <form action="<?php echo APP_FURI; ?>&do=save" method="post" class="form-inline" id="iCMS-setting" target="iPHP_FRAME">
        <div id="setting" class="tab-content">
          <?php include iPHP_APP_DIR.'/admincp/template/setting.base.php';?>
          <?php
          $app_path = APPS::setting('content');
          foreach ($app_path as $key => $path) {
            include $path;
          }
          ?>
        </div>
        <div class="form-actions">
          <button class="btn btn-primary btn-large" type="submit"><i class="fa fa-check"></i> 保 存</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php admincp::foot();?>
