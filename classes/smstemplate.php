<?php
/**
 * @file smstemplate.php
 * @brief 短信发送模板
 * @author 
 * @date 2014/8/11 9:51:51
 * @version 2.9
 */

 /**
 * @class smsTemplate
 * @brief 短信发送模板
 */
class smsTemplate
{
	/**
	 * @brief 订单发货的信息模板
	 * @param array $data 替换的数据
	 */
	public static function sendGoods($data = null)
	{
		$templateString = "您好{user_name},订单号:{order_no},已发货";
		return strtr($templateString,$data);
	}

	/**
	 * @brief 手机找回密码模板
	 * @param array $data 替换的数据
	 */
	public static function findPassword($data = null)
	{
		$templateString = "您的验证码为:{mobile_code},请注意保管!";
		return strtr($templateString,$data);
	}

	/**
	 * @brief 手机短信校验码
	 * @param array $data 替换的数据
	 */
	public static function checkCode($data = null)
	{
		$templateString = "尊敬的顾客，请输入手机动态验证码:{mobile_code}。该验证码有效期30分钟。";
		return strtr($templateString,$data);
	}

	/**
	 * @brief 自提点确认短信
	 * @param array $data 替换的数据
	 */
	public static function takeself($data = null)
	{
		$templateString = "订单已被商家确认，请于9:30点前前往{address}提取您预定的早餐、领取验证码为：{mobile_code}。";
		return strtr($templateString,$data);
	}

	/**
	 * @brief 商户注册提示管理员
	 * @param array $data 替换的数据
	 */
	public static function sellerReg($data = null)
	{
		$templateString = "{true_name},申请加盟到平台,请尽快登录后台进行处理";
		return strtr($templateString,$data);
	}

	/**
	 * @brief 商户处理结果
	 * @param array $data 替换的数据
	 */
	public static function sellerCheck($data = null)
	{
		$templateString = "您申请的店铺{true_name}已{result}，请尽快登录商户后台进行信息完善。";
		return strtr($templateString,$data);
	}

	/**
	 * @brief 订单付款通知管理员
	 */
	public static function payFinishToAdmin($data = null)
	{
		$templateString = "商城订单:{orderNo},已经付款,请尽快登录后台进行处理";
		return strtr($templateString,$data);
	}

	/**
	 * @brief 订单付款通知管理员
	 */
	public static function payFinishToUser($data = null)
	{
		$templateString = "订单已被商家确认，您购买的早餐将于{time}前送达至{address}";

		return strtr($templateString,$data);
	}

	public static function toTakeself($data = null)
	{
		$templateString = "订单号为{orderNo}的订单将配送到这里,领取验证码:{mobile_code}";
		return strtr($templateString,$data);
	}

	public static function orderToSeller($data = null)
	{
		$templateString = "{true_name},买家{member}已付款，请尽快登录后台进行处理";
		return strtr($templateString,$data);
	}


}