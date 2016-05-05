<?php
/**
 * @file sonline.php
 * @brief 在线客服插件
 * @author wplee
 * @date 2015/9/16 11:45:17
 * @version 1.0.0
 */
class Sonline
{
	
	private static $qqUrl = 'http://wpa.qq.com/msgrd?v=3&uin={$qqNum}&site=qq&menu=yes';
	
	//通过qq号获取临时会话url
	public static function getChatUrl($qq){
		return str_replace('{$qqNum}',$qq,self::$qqUrl);
	}
	/**
	 * 获取平台客服数据
	 * @return array 客服数据数组
	 */
	public static function getService()
	{
		$siteConfig = new Config("site_config");
		$tel = $siteConfig->phone;
		$qqSer = $siteConfig->service_online;
		if(!$qqSer)
		{
			return null;
		}
		$qqArray = unserialize($qqSer);
		$tempArray = array();
		foreach($qqArray as $key=>$val)
		{
			if(!$val['qq'] || !$val['name'])
			{
				continue;
			}
			$tempArray['qq'][$key]['num'] = $val['qq'];
			$tempArray['qq'][$key]['name'] = $val['name'];
			$tempArray['qq'][$key]['link'] = self::getChatUrl($val['qq']);
		}
		if(!$tempArray)
		{
			return null;
		}
		$tempArray['tel']=$tel;
		return $tempArray;

		
	}

	/**
	 * @brief 展示qq联系代码
	 * @param string $qqNum QQ号码
	 */
	public static function qqShow($qqNum)
	{
		if(!$qqNum)
		{
			return;
		}
echo <<< OEF
	<a href="http://wpa.qq.com/msgrd?v=3&uin={$qqNum}&site=qq&menu=yes" target="_blank">
		<img border="0" alt="立即联系" src="http://wpa.qq.com/pa?p=2:{$qqNum}:41 &r=0.22914223582483828">
	</a>
OEF;
	}
}