<?php /**
* @package iCMS
* @copyright 2007-2010, iDreamSoft
* @license http://www.idreamsoft.com iDreamSoft
* @author coolmoo <idreamsoft@qq.com>
* @$Id: setting.php 2412 2014-05-04 09:52:07Z coolmoo $
*/
defined('iPHP') OR exit('What are you doing?');
?>
<script>
$(function () {
  $("#weixin_token_make").click(function(event) {
    var token = iCMS.random(20);
    $("#weixin_token").val(token);
    $("#weixin_interface").val('<?php echo iCMS::$config['router']['public_url'] ; ?>/api.php?app=weixin&do=interface&api_token='+token);
  });
  $("#weixin_token").keypress(function(event) {
    $("#weixin_interface").val('<?php echo iCMS::$config['router']['public_url'] ; ?>/api.php?app=weixin&do=interface&api_token='+this.value);
  });
})
</script>
  <div id="setting-weixin" class="tab-pane hide">
    <h3>微信公众平台</h3>
    <span class="help-inline">
      申请地址:https://mp.weixin.qq.com/
    </span>
    <div class="clearfloat">
    </div>
    <div class="input-prepend">
      <span class="add-on">
        appID
      </span>
      <input type="text" name="config[api][weixin][appid]" class="span3" id="weixin_appid" value="<?php echo $config['api']['weixin']['appid'] ; ?>"/>
    </div>
    <div class="clearfloat mt10">
    </div>
    <div class="input-prepend">
      <span class="add-on">
        appsecret
      </span>
      <input type="text" name="config[api][weixin][appsecret]" class="span3" id="weixin_appsecret" value="<?php echo $config['api']['weixin']['appsecret'] ; ?>"/>
    </div>
    <div class="clearfloat mt10">
    </div>
    <div class="input-prepend input-append">
      <span class="add-on">
        Token(令牌)
      </span>
      <input type="text" name="config[api][weixin][token]" class="span3" id="weixin_token" value="<?php echo $config['api']['weixin']['token'] ; ?>"/>
      <a class="btn" id="weixin_token_make">
        生成令牌
      </a>
    </div>
    <div class="clearfloat mt10">
    </div>
    <div id="wxmp_interface">
      <div class="input-prepend input-append">
        <span class="add-on">
          接口URL
        </span>
        <input disabled type="text" class="span7" id="weixin_interface" value="<?php echo iCMS::$config['router']['public_url'] ; ?>/api.php?app=weixin&do=interface&api_token=<?php echo $config['api']['weixin']['token']?$config['api']['weixin']['token']:'Token(令牌)' ; ?>"/>
        <a class="btn" href="http://www.idreamsoft.com/doc/iCMS/weixin_interface.html" target="_blank">
          <i class="fa fa-question-circle"></i> 配置帮助
        </a>
      </div>
      <div class="clearfloat mt10">
      </div>
    </div>
    <div class="clearfloat mt10">
    </div>
    <div class="input-prepend">
      <span class="add-on">
        名称
      </span>
      <input type="text" name="config[api][weixin][name]" class="span3" id="weixin_name" value="<?php echo $config['api']['weixin']['name'] ; ?>"/>
    </div>
    <div class="clearfloat mt10">
    </div>
    <div class="input-prepend">
      <span class="add-on">
        微信号
      </span>
      <input type="text" name="config[api][weixin][account]" class="span3" id="weixin_account" value="<?php echo $config['api']['weixin']['account'] ; ?>"/>
    </div>
    <div class="clearfloat mt10">
    </div>
    <div class="input-prepend">
      <span class="add-on">
        二维码
      </span>
      <input type="text" name="config[api][weixin][qrcode]" class="span3" id="weixin_qrcode" value="<?php echo $config['api']['weixin']['qrcode'] ; ?>"/>
    </div>
    <span class="help-inline">
      公众号的二维码链接
    </span>
    <hr />
    <div class="input-prepend">
      <span class="add-on">
        关注事件
      </span>
      <textarea name="config[api][weixin][subscribe]" id="weixin_subscribe" class="span6" style="height: 90px;"><?php echo $config['api']['weixin']['subscribe'] ; ?></textarea>
    </div>
    <div class="clearfloat"></div>
    <span class="help-inline">
      用户未关注时，进行关注后的信息回复，留空将使用系统默认信息回复
    </span>
    <div class="clearfloat mt10">
    </div>
    <div class="input-prepend">
      <span class="add-on">
        取消关注
      </span>
      <textarea name="config[api][weixin][unsubscribe]" id="weixin_unsubscribe" class="span6" style="height: 90px;"><?php echo $config['api']['weixin']['unsubscribe'] ; ?></textarea>
    </div>
    <div class="clearfloat"></div>
    <span class="help-inline">
      用户取消关注后的信息回复，留空将使用系统默认信息回复
    </span>
    <div class="mt20">
    </div>
    <div class="alert alert-block">
      <h4>注意事项</h4>
      微信功能目前只能接收关键字并自动回复相关信息．其它功能在开发中．．．．
    </div>
  </div>
