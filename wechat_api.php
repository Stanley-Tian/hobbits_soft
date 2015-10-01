<?php
/**
 * Created by PhpStorm.
 * User: Tevil
 * Date: 2015/9/28
 * Time: 15:19
 */
class wechatCallbackapi
{
	private $user_status;
	//验证签名
	public function valid()
	{
		$echoStr   = $_GET["echostr"];
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce     = $_GET["nonce"];
		$token     = TOKEN;
		$tmpArr    = array(
			$token,
			$timestamp,
			$nonce
		);
		sort($tmpArr);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		if ($tmpStr == $signature)
		{
			header('content-type:text');
			echo $echoStr; //返回值
			exit;
		}
	}
	//响应消息
	public function responseMsg()
	{
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //获取原始xml消息
		if (!empty($postStr))
		{
			$this->logger("R " . $postStr);
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA); //将消息载入为对象
			$RX_TYPE = trim($postObj->MsgType); //获取收到消息的类型
			
			//消息类型分离
			switch ($RX_TYPE)
			{
				case "event":
					$result = $this->receiveEvent($postObj);
					break;
				case "text":
					$result = $this->receiveText($postObj);
					break;
				default:
					$result = "unknown msg type: " . $RX_TYPE;
					break;
			}
			$this->logger("T " . $result);
			echo $result; //返回已经格式化的xml消息
		}
		else
		{
			echo "";
			exit;
		}
	}
	//接收事件消息
	private function receiveEvent($object)
	{
		$content = "";
		switch ($object->Event)
		{
			case "subscribe":
				$content = "欢迎关注!\n请输入小票上的验证码。";
				break;
			case "unsubscribe":
				$content = "取消关注";
				break;
			case "SCAN":
				$content = "扫描场景 " . $object->EventKey;
				break;
			case "CLICK":
				switch ($object->EventKey)
				{
					case "COMPANY":
						$content   = array();
						$content[] = array(
							"Title" => "多图文1标题",
							"Description" => "",
							"PicUrl" => "http://discuz.comli.com/weixin/weather/icon/cartoon.jpg",
							"Url" => "http://m.cnblogs.com/?u=txw1958"
						);
						break;
					default:
						$content = "点击菜单：" . $object->EventKey;
						break;
				}
				break;
			case "LOCATION":
				$content = "上传位置：纬度 " . $object->Latitude . ";经度 " . $object->Longitude;
				break;
			case "VIEW":
				$content = "跳转链接 " . $object->EventKey;
				break;
			case "MASSSENDJOBFINISH":
				$content = "消息ID：" . $object->MsgID . "，结果：" . $object->Status . "，粉丝数：" . $object->TotalCount . "，过滤：" . $object->FilterCount . "，发送成功：" . $object->SentCount . "，发送失败：" . $object->ErrorCount;
				break;
			default:
				$content = "receive a new event: " . $object->Event;
				break;
		}
		if (is_array($content))
		{
			if (isset($content[0]))
			{
				$result = $this->transmitNews($object, $content);
			}
			else if (isset($content['MusicUrl']))
			{
				$result = $this->transmitMusic($object, $content);
			}
		}
		else
		{
			$result = $this->transmitText($object, $content);
		}
		
		return $result;
	}
	//█████████████████████████████████████████████████████████████
	//判断所输入的数是否为四位数字
	private function four_num($input)
	{
		$input_status = preg_match('/^\b\d{4}\b$/', $input); //正则表达式匹配,若是四位数字则返回1,否则返回0
		if ($input_status == 1)
			return true;
		else
			return false;
	}
	//http请求
	public function https_request($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);
		$jsoninfo = json_decode($output, true);
		return $jsoninfo;
	}
	//获取access token值
	private function acquire_access_token($appid, $appsecret)
	{
		$url          = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
		$jsoninfo     = $this->https_request($url);
		$access_token = $jsoninfo["access_token"];
		return $access_token;
	}
	//接收文本消息
	private function user_valid($openid, $time_range, $db_handle)
	//获取用户最近一次的数据绑定时间,并判断其是否为$time_range以内,以判定用户是否合法
	{
		$db_result        = $db_handle->query("SELECT Create_Time FROM foot_info
                                        WHERE User_WE_ID = '$openid'
                                        ORDER BY Create_Time DESC");
		//$this->content .= $db_result->rowCount();
		$single_foot_info = $db_result->fetch();
		$last_time        = $single_foot_info["Create_Time"]; //用户上一次的脚型时间
		//$this->content .= $last_time;
		$cur_time         = time();
		$cur_time-$last_time;
		if (($cur_time-$last_time) <= $time_range) //用过认证过频
		{
			return false;
		}
		else //正常更新脚型
			return true;
	}
	public $content;
	private function receiveText($object)
	{
		$keyword = trim($object->Content);
		//自动回复模式
		if ($this->four_num($keyword))
		{
			$passed            = true;
			//连接数据库
			$db_obj            = new ConnectDataBase();
			$db_handle         = $db_obj->connect();
			$verification_code = $keyword;
			$openid            = $object->FromUserName; //用户经过加密的id
			$db_result         = $db_handle->query("SELECT * FROM foot_info WHERE Verification_Code = '$verification_code'"); //检查是否存在该条数据
			
			if ($this->user_valid($openid, 24*60*60, $db_handle) == false) //判断用户是否在一天之内多次输入验证码
			//若验证码正确,但是用户已经绑定并在短时间内再次输入验证码.
			{
				$passed = false;
				$this->content .= "您已绑定过脚型数据,回复\"查询\"查看您的脚型数据。";
			}
			elseif ($db_result->rowCount() < 1)
			//若不存在当前验证码数据,提示验证码输入错误
			{
				$passed = false; //不存在该条数据
				$this->content .= "验证码输入有误,请重新输入!";
			}
			else
			{
				//插入用户openid并清空验证码
				$sql_update = "UPDATE	foot_info
						  SET		User_WE_ID = '$openid',Verification_Code =''
						  WHERE		Verification_Code = '$verification_code'";
				$db_handle->query($sql_update); //插入微信OpenID,并将验证码归零
				//获取access token
				$appid     = "wx9cf1f02344396f84";
				$appsecret = "f64d2ce3c8a8c13e6e8a09fa1298b0a0";
				//$access_token = $this->acquire_access_token($appid,$appsecret);
				////////////////////////////////////////////////////////////////////
				
				if ($passed)
				{
					$this->content .= "脚型数据绑定成功!" . "\n" . "回复\"查询\"查看您的脚型数据。";
				}
				else
				{
					$this->content .= "验证码输入有误,请重新输入!";
				}
				
			}
			
			$global_status = "error code: ".$db_handle->errorInfo()[0]."\n".
			"error info: ".$db_handle->errorInfo()[1].$db_handle->errorInfo()[2]."\n".
			"passed: ".$passed."\n".
			//"acs tkn: ".$access_token."\n".
			"";
			
			$this->content .= $global_status;
			$result = $this->transmitText($object, $this->content);
			return $result;
			
		}
		else if (strstr($keyword, "查询")) //strstr(a,b)表示若a中包含b
		{
			//$connect_status = connect_mysql();
			$db_obj    = new ConnectDataBase();
			$db_handle = $db_obj->connect();
			//$content .= $connect_status;
			$openid    = $object->FromUserName; //用户经过加密的id
			$db_result = $db_handle->query("SELECT * FROM foot_info WHERE User_WE_ID = '$openid'"); //
			if ($db_result->rowCount() < 1) //若未搜索到数据
			{
				$this->content .= "您还未进行脚型测量。";
			}
			else
			{
				$row               = $db_result->fetch();
				//尺寸转换
				$Left_Length       = $row["Left_Length"]/10;
				$Left_Width_Front  = $row["Left_Width_Front"]/10;
				$Left_Width_Back   = $row["Left_Width_Back"]/10;
				$Left_Fossa        = $row["Left_Fossa"]*100;
				$Right_Length      = $row["Right_Length"]/10;
				$Right_Width_Front = $row["Right_Width_Front"]/10;
				$Right_Width_Back  = $row["Right_Width_Back"]/10;
				$Right_Fossa       = $row["Right_Fossa"]*100;
				
				$this->content .= "您的脚型信息:" . "\n" . "鞋号: " . $row["Foot_Size"] . "\n" . "左脚长: " . $Left_Length . " cm" . "\n" . "左脚前宽: " . $Left_Width_Front . " cm" . "\n" . "左脚后宽: " . $Left_Width_Back . " cm" . "\n" . "左脚脚窝比例: " . $Left_Fossa . "%" . "\n" . "右脚长: " . $Right_Length . " cm" . "\n" . "右脚前宽: " . $Right_Width_Front . " cm" . "\n" . "右脚后宽: " . $Right_Width_Back . " cm" . "\n" . "右脚脚窝比例: " . $Right_Fossa . "%" . "\n" . "";
			}
			$result = $this->transmitText($object, $this->content);
			return $result;
		}
	}
	//█████████████████████████████████████████████████████████████
	//回复文本消息
	private function transmitText($object, $content)
	{
		$xmlTpl = " <xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[text]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					</xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content); //依次替换xml中的%s.第一个参数为格式,后面逐一替换
		return $result;
	}
	//日志记录
	private function logger($log_content)
	{
		if (isset($_SERVER['HTTP_APPNAME']))
		//SAE
		{
			sae_set_display_errors(false);
			sae_debug($log_content);
			sae_set_display_errors(true);
		}
		else if ($_SERVER['REMOTE_ADDR'] != "127.0.0.1")
		//LOCAL
		{
			$max_size     = 10000;
			$log_filename = "log.xml";
			if (file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size))
			{
				unlink($log_filename);
			}
			file_put_contents($log_filename, date('H:i:s') . " " . $log_content . "\r\n", FILE_APPEND);
		}
	}
}