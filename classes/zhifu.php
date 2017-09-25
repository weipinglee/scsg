<?php
/**
 * @file article.php
 * @brief 关于文章管理
 * @author 
 * @date 2011-02-14
 * @version 0.6
 */

 /**
 * @class article
 * @brief 文章管理模块
 */
class Zhifu
{
	/**
	 * @param $pass string 要验证的支付密码
	 * @param $user_id int 用户id
	 * @return bool
	 */
	public  function checkPayPass($pass,$user_id)
	{echo $user_id;
		$userObj  = new IModel('user');
		$userData = $userObj->getObj('id = '.$user_id);
		if(sha1($pass)==$userData['pay_secret']){
			return true;
		}
		return false;
	}
}