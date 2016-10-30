<?php /**
* @package iCMS
* @copyright 2007-2010, iDreamSoft
* @license http://www.idreamsoft.com iDreamSoft
* @author coolmoo <idreamsoft@qq.com>
* @$Id: home.php 2393 2014-04-09 13:14:23Z coolmoo $
*/
defined('iPHP') OR exit('What are you doing?');
admincp::head();
?>
<div class="header">
  <h1>兼职编辑任务管理系统</h1>
  <div class="userinfo">
    <h3>兼职编辑1</h3>
    <p>当前账号余额：¥420.2元  </p>
    <p>当前账号积分：1000 （积分可兑换糯米券）</p>
  </div>
</div>
<div class="conta-iner">
  <div class="sidebar">
    <div class="avatar">
      <img src="/app/admincp/ui/img/91ecd481d68fa67f64030829e82e80e2.jpg" alt="">
    </div>
    <div class="navbar">
      <ul>
        <li><a href="/index.php?app=task&do=start"><i class="fa fa-send"></i> <span>开始任务</span></a></li>
        <li><a href="/index.php?app=task&do=my"><i class="fa fa-tasks"></i> <span>我的任务</span></a></li>
        <li><a href="/index.php?app=dashboard"><i class="fa fa-dashboard"></i> <span>数据查看</span></a></li>
        <li><a href="/index.php?app=money"><i class="fa fa-money"></i> <span>我的佣金</span></a></li>
        <li><a href="/index.php?app=gift"><i class="fa fa-gift"></i> <span>积分兑换</span></a></li>
        <li><a href="/index.php?app=profile"><i class="fa fa-user"></i> <span>我的资料</span></a></li>
        <li class="divider"><div style="margin-top:50px;"></div></li>
        <li><a href="/index.php?app=task"><i class="fa fa-dashboard"></i> <span>任务管理</span></a></li>
        <li><a href="/index.php?app=task&do=add"><i class="fa fa-edit"></i> <span>添加任务</span></a></li>
        <li><a href="/index.php?app=category"><i class="fa fa-dashboard"></i> <span>任务分类</span></a></li>
        <li><a href="/index.php?app=category&do=add"><i class="fa fa-edit"></i> <span>添加分类</span></a></li>
        <li><a href="/index.php?app=users"><i class="fa fa-users"></i> <span>用户管理</span></a></li>
        <li><a href="/index.php?app=dashboard"><i class="fa fa-dashboard"></i> <span>数据查看</span></a></li>
        <li><a href="/index.php?app=money"><i class="fa fa-money"></i> <span>财务管理</span></a></li>
        <li class="last"></li>
      </ul>
    </div>
  </div>
  <div class="main">
    <div class="notice alert alert-info">
      <b><i class="fa fa-volume-up"></i></b>
      <p>2016年10月13日满100元佣金已经支付</p>
    </div>
    <div class="dashboard">
      <div class="row-fluid">
      </div>
    </div>
    <div class="taskList">
      <h3 class="mb10">任务列表</h3>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th style="width:60px;">ID</th>
            <th class="span2">任务类别</th>
            <th>任务概况/需求</th>
            <th>奖励/惩罚机制</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td>转载类</td>
            <td>根据要求转载相关文章
              <br />
              <span class="label label-info">当前需求：10400条</span>
            </td>
            <td class="reward">
              <div class="input-prepend">
                <span class="add-on">奖励</span>
                <span class="label label-success">
                  佣金： 0.1元 /篇<br />
                  积分：+1分 /篇
                </span>
              </div>
              <div class="input-prepend">
                <span class="add-on">惩罚</span>
                <span class="label label-important">
                  无佣金
                  <br />
                  积分：-2分 /篇
                </span>
              </div>
            </td>
            <td><a href="/index.php?app=task&do=start&type=1" class="btn btn-large btn-primary" type="button">开始</a></td>
          </tr>
          <tr>
            <td>2</td>
            <td>原创文章</td>
            <td>根据要求撰写相关文章
              <br />
              <span class="label label-info">当前需求：无限制</span>
            </td>
            <td class="reward">
              <div class="input-prepend">
                <span class="add-on">奖励</span>
                <span class="label label-success">
                  佣金： 5元 /篇<br />
                  积分：+10分 /篇
                </span>
              </div>
              <div class="input-prepend">
                <span class="add-on">惩罚</span>
                <span class="label label-important">
                  无佣金
                  <br />
                  积分：-20分 /篇
                </span>
              </div>
            </td>
            <td><a href="/index.php?app=task&do=start&type=2" class="btn btn-large btn-primary" type="button">开始</a></td>
          </tr>
          <tr>
            <td>3</td>
            <td>数据整理</td>
            <td>根据要求对相关数据整理进行归类
              <br />
              <span class="label label-info">当前需求：无限制</span>
            </td>
            <td class="reward">
              <div class="input-prepend">
                <span class="add-on">奖励</span>
                <span class="label label-success">
                  佣金： 0.01元/条<br />
                  积分：+1分/条
                </span>
              </div>
              <div class="input-prepend">
                <span class="add-on">惩罚</span>
                <span class="label label-important">
                  无佣金
                  <br />
                  积分：-1分/条
                </span>
              </div>
            </td>
            <td><a href="/index.php?app=task&do=start&type=3" class="btn btn-large btn-primary" type="button">开始</a></td>
          </tr>
          <tr>
            <td>4</td>
            <td>专题</td>
            <td>根据要求填写专题相关内容
              <br />
              <span class="label label-info">当前需求：5904条</span>
            </td>
            <td class="reward">
              <div class="input-prepend">
                <span class="add-on">奖励</span>
                <span class="label label-success">
                  佣金： 10元/个<br />
                  积分：+20分/个
                </span>
              </div>
              <div class="input-prepend">
                <span class="add-on">惩罚</span>
                <span class="label label-important">
                  无佣金
                  <br />
                  积分：-30分/个
                </span>
              </div>
            </td>
            <td><a href="/index.php?app=task&do=start&type=4" class="btn btn-large btn-primary" type="button">开始</a></td>
          </tr>
          <tr>
            <td>5</td>
            <td>审核</td>
            <td>审核各种任务结果
              <br />
              <span class="label label-info">当前需求：5904条</span>
            </td>
            <td class="reward">
              <div class="input-prepend">
                <span class="add-on">奖励</span>
                <span class="label label-success">
                  佣金： 10元/个<br />
                  积分：+20分/个
                </span>
              </div>
              <div class="input-prepend">
                <span class="add-on">惩罚</span>
                <span class="label label-important">
                  无佣金
                  <br />
                  积分：-30分/个
                </span>
              </div>
            </td>
            <td><a href="/index.php?app=task&do=start&type=5" class="btn btn-large btn-primary" type="button">开始</a></td>
          </tr>
        </tbody>
      </table>
      <div class="task-item">
      </div>
    </div>
  </div>
</div>
<?php admincp::foot();?>
