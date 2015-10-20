<?php

/**
 * @brief 短信发送接口
 * @version 3.3
 */

 /**
 * @class Hsms
 * @brief 短信发送接口
 */
class Hsms
{
	private static $smsInstance = null;

	public static function getSmsInstance(){
		
		//单例模式
		if(self::$smsInstance != NULL && is_object(self::$smsInstance))
		{
			return self::$smsInstance;
		}
		
		$platform = self::getPlatForm();
		switch($platform)
		{
			case "jianzhou":
				{
					$classFile = IWeb::$app->getBasePath().'plugins/hsms/jianzhou.php';
					require $classFile;
					return self::$smsInstance = new jianzhou();
					
				}
			case "zhutong":
				{
					$classFile = IWeb::$app->getBasePath().'plugins/hsms/zhutong.php';
					require($classFile);
					return self::$smsInstance = new zhutong();
				}
				break;
	
			default:
				{
					$classFile = IWeb::$app->getBasePath().'plugins/hsms/haiyan.php';
					require($classFile);
					return self::$smsInstance = new haiyan();
				}
		}
	}

	/**
	 * @brief 获取config用户配置
	 * @return array
	 */
	private static function getPlatForm()
	{
		$siteConfigObj = new Config("site_config");
		return $siteConfigObj->sms_platform;
	}

	/**
	 * @brief 发送短信
	 * @param string $mobile
	 * @param string $content
	 * @return success or fail
	 */
	public static function send($mobile,$content)
	{
		self::$smsInstance = self::getSmsInstance();
		if(IValidate::mobi($mobile) && $content)
		{
			$ip = IClient::getIp();
			if($ip)
			{
				$mobileKey = md5($mobile.$ip);
				$sendTime  = ISession::get($mobileKey);
				if($sendTime && time() - $sendTime < 60)
				{
					return false;
				}
				ISession::set($mobileKey,time());
				return self::$smsInstance->send($mobile,$content);
			}
		}
		return false;
	}
}

/**
 * @brief 短信抽象类
 */
abstract class hsmsBase
{
	//短信发送接口
	abstract public function send($mobile,$content);

	//短信发送结果接口
	abstract public function response($result);

	//短信配置参数
	abstract public function getParam();
}