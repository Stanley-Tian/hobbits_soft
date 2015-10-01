<?php
//echo "hello pc";
include 'connect_mysql.php';
$postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //获取原始xml消息
$postObj = "";
if (!empty($postStr))
{
	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA); //将消息载入为对象
}
//@$connectstatus = connect_mysql(); //连接数据库
$db_obj       = new ConnectDataBase();
$db_handle    = $db_obj->connect(); // connect to the mysql database
$sql_insert   = "insert into foot_info 
(Foot_Size,
Left_Length,
Left_Width_Front,
Left_Width_Back,
Left_Fossa,
Right_Length,
Right_Width_Front,
Right_Width_Back,
Right_Fossa,
Verification_Code,
Create_Time) 
values 
('$postObj->Foot_Size',
'$postObj->Left_Length',
'$postObj->Left_Width_Front',
'$postObj->Left_Width_Back',
'$postObj->Left_Fossa',
'$postObj->Right_Length',
'$postObj->Right_Width_Front',
'$postObj->Right_Width_Back',
'$postObj->Right_Fossa',
'$postObj->Verification_Code',
'$postObj->Create_Time')";
//
$insertstatus = "";
if ($db_handle->exec($sql_insert))
{
	$insertstatus = "脚型数据插入成功";
}
else
{
	$insertstatus = "脚型数据插入失败";
}
$result = $connectstatus . "\n" . //插入数据库连接信息
	$insertstatus . "\n" . //插入脚型数据插入信息
	"";
$result = iconv("utf-8", "gb2312", $result); //解决中文乱码问
echo $result;