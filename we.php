<?php
define("TOKEN", "hobbits");
include 'connect_mysql.php';
include 'wechat_api.php';
$debug     = true;
$wechatObj = new wechatCallbackapi(); //生成类的一个新的实例

if (!isset($_GET['echostr']))
{
	$wechatObj->responseMsg(); //消息实体反馈
}
else
{
	$wechatObj->valid(); //验证请求来自微信
}