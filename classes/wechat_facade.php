<?php
/**
 * @file wechat_facade.php
 * @brief 微信封装类库
 * @author 
 * @date 2015/3/29 15:15:14
 * @version 3.1
 */
class wechat_facade
{
	//实例
	public static $instance = null;

	/**
	 * @brief 创建分词类库实例
	 */
	private static function createInstance()
	{
		if(self::$instance == null)
		{
			$classFile = IWeb::$app->getBasePath().'plugins/wechat/wechat.php';
			if(is_file($classFile))
			{
				include_once($classFile);
				self::$instance = new wechat();
			}
			else
			{
				die('您的版本不支持微商城');
			}
		}
		return self::$instance;
	}

	//微信推送的相应
	public static function response()
	{
		$instance = self::createInstance();
		return $instance->response();
	}

	//微信获取菜单
	public static function getMenu()
	{
		$instance = self::createInstance();
		return $instance->getMenu();
	}

	/**
	 * @brief 微信设置菜单
	 * @param json $menuData
	 */
	public static function setMenu($menuData)
	{
		$instance = self::createInstance();
		return $instance->setMenu($menuData);
	}
}