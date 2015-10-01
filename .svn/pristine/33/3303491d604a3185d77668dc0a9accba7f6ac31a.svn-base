<?php
/**
 * Created by PhpStorm.
 * User: Tevil
 * Date: 2015/9/26
 * Time: 13:52
 */
//echo phpinfo();
include "connect_mysql.php";
$db_obj = new ConnectDataBase();
$a      = $db_obj->connect();

$result = $a->query("SELECT * FROM foot_info");
$row    = $result->fetch();
while (!empty($row))
{
	echo $row["Foot_Size"];
	$row = $result->fetch();
}
$a = NULL;