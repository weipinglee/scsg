<?php
/**
 * @file zhutong.php
 * @brief **短信发送接口
 * @date 2015/5/30 15:46:38
 * @version 3.3
 */

 /**
 * @class zhutong
 * @brief 短信发送接口 短信后台地址 http://www.ztsms.cn/home
 */
class zhutong extends hsmsBase
{
	private $submitUrl  = "http://www.ztsms.cn:8800/sendSms.do";
	private static $productCode= array(
		1 => '676767',//验证码
		2 => '48661',//通知
		3 => '435227',//营销广告
	);

	/**
	 * @brief 获取config用户配置
	 * @return array
	 */
	public function getConfig()
	{
		$siteConfigObj = new Config("site_config");

		return array(
			'username' => $siteConfigObj->sms_username,
			'userpwd'  => $siteConfigObj->sms_pwd,
		);
	}

	/**
	 * @brief 发送短信
	 * @param string $mobile
	 * @param string $content
	 * @return
	 */
	public function send($mobile,$content)
	{
		$config = self::getConfig();

		$post_data = array(
			'username' => $config['username'],
			'password' => md5($config['userpwd']),
			'content'  => $content,
			'mobile'   => $mobile,
			'productid'=> $this->getProductCode($content),
		);

		$url    = $this->submitUrl;
		$string = '';
		foreach ($post_data as $k => $v)
		{
		   $string .="$k=".urlencode($v).'&';
		}

		$post_string = substr($string,0,-1);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //如果需要将结果直接返回到变量里，那加上这句。
		$result = curl_exec($ch);
		return $this->response($result);
	}

	/**
	 * @brief 解析结果
	 * @param $result 发送结果
	 * @return string success or fail
	 */
	public function response($result)
	{
		if(strpos($result,'1,') === 0)
		{
			return 'success';
		}
		else
		{
			return 'fail';
		}
	}

	/**
	 * @brief 获取参数
	 */
	public function getParam()
	{
		return array(
			"username" => "用户名",
			"userpwd"  => "密码",
			"usersign" => "短信签名",
		);
	}

	/**
	 * @brief 根据短信内容返回产品ID
	 * @param $content
	 */
	public function getProductCode($content)
	{
		if(strpos($content,"验证码") !== false)
		{
			return self::$productCode[1];
		}
		return self::$productCode[2];
	}
}