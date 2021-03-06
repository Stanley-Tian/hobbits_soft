<?php
function connect_mysql()
{
	mysql_query("set names utf8"); //设定编码为utf8，关键！
	$real_host = $_SERVER['SERVER_NAME'];
	$tmp2      = NULL;
	if ($_SERVER['SERVER_NAME'] == "localhost")
	{
		define('DB_HOST', 'localhost');
		define('DB_USER', 'root'); //MySQL 数据库用户名 
		define('DB_PASSWORD', ''); // MySQL 数据库密码
		define('DB_NAME', 'database1'); //定义数据库名
		$tmp2 = 'localhost';
	}
	else if ($_SERVER['SERVER_NAME'] == "8d16eabf.ngrok.io") //本地真域名映射,使得可以在本地在线调试微信信息
	{
		define('DB_HOST', 'localhost');
		define('DB_USER', 'root'); //MySQL 数据库用户名 
		define('DB_PASSWORD', ''); // MySQL 数据库密码
		define('DB_NAME', 'database1'); //定义数据库名
		$tmp2 = 'ngrok';
	}
	else
	{
		//实际上线使用的数据库连接代码
		define('DB_HOST', 'pyhysixjfswn.rds.sae.sina.com.cn:12252'); // MySQL 主机 
		define('DB_USER', 'stanley'); //MySQL 数据库用户名 
		define('DB_PASSWORD', '403107477'); // MySQL 数据库密码
		define('DB_NAME', 'database1'); //定义数据库名
		define('DB_CHARSET', 'utf8'); // 创建数据表时默认的文字编码
		define('DB_COLLATE', ''); // 数据库整理类型。如不确定请勿更改
		$tmp2 = 'sae';
	}
	
	$connect = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD); //连接数据库
	//$connectstatus ="";
	
	if (!$connect)
	{
		$connectstatus = "host地址: " . $real_host . "\n" . "平台: " . $tmp2;
		return $connectstatus .= "无法连接到数据库\n" . mysql_error();
	}
	else
	{
		mysql_select_db(DB_NAME, $connect); //connect to the specific database
		$connectstatus = "host地址: " . $real_host . "\n" . "平台: " . $tmp2;
		return $connectstatus .= "成功链接到数据库\n";
	}
}
class ConnectDataBase
{
	private $db_host, $db_port, $db_user, $db_password, $db_name, $db_charset; //服务器成员变量
	private $environment; //服务器所在环境
	public function __construct()
	//构造函数,用来载入集中不同环境下的服务器接入信息
	{
		$real_host = $_SERVER['SERVER_NAME'];
		//echo $real_host;
		if ($real_host == "localhost")
		{
			$this->db_host     = "localhost";
			$this->db_port     = 3306;
			$this->db_user     = "root";
			$this->db_password = "";
			$this->db_name     = "database1";
			$this->db_charset  = "utf8";
			$this->environment = 'localhost';
		}
		else if ($real_host == "1d536286.ngrok.io") //本地真域名映射,使得可以在本地在线调试微信信息
		{
			$this->db_host     = "localhost";
			$this->db_port     = NULL;
			$this->db_user     = "root";
			$this->db_password = "";
			$this->db_name     = "database1";
			$this->db_charset  = "utf8";
			$this->environment = 'ngrok';
		}
		else
		{
			//实际上线使用的数据库连接代码
			$this->db_host     = "pyhysixjfswn.rds.sae.sina.com.cn";
			$this->db_port     = "12252";
			$this->db_user     = "stanley";
			$this->db_password = "403107477";
			$this->db_name     = "database1";
			$this->db_charset  = "utf8";
			$this->environment = 'SAE';
		}
	}
	public function connect()
	{
		$dsn = "mysql:host=$this->db_host;port=$this->db_port;dbname=$this->db_name"; //不能有空格
		$dbh = new PDO($dsn, $this->db_user, $this->db_password);
		$dbh->exec("SET CHARACTER SET utf8");
		return $dbh;
	}
}
