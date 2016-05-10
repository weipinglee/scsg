<?php

/**
 * 退换货操作类
 * @author wplee
 *
 */
Class Refunds_Class{
	
	/**
	 * @brief 判断商品是否可退货
	 * @$orderGoodsRow array 包含order_goods 和order表数据
	 */
	public static function order_goods_refunds($orderGoodsRow){
		if($orderGoodsRow['order_amount']==0)return false;
		if($orderGoodsRow['refunds_status']==0 && in_array($orderGoodsRow['status'],array(2,7,8,9)))
			return true;
		return false;
	}
	
	/**
	 * @brief 判断商品是否可换货
	 * @$orderGoodsRow array 包含order_goods 和order表数据
	 */
	public static function order_goods_chg($orderGoodsRow){
		$config = new Config('site_config');
		$limit_days = isset($config->chg_limit_days) ? $config->chg_limit_days : 7;
		$limit_time = $limit_days*24*3600;
		if($orderGoodsRow['is_send']!=1){//不是已发货的否定
			return false;
		}
		else if($orderGoodsRow['refunds_status']!=0){//申请过的否定
			return false;
		}
		if($orderGoodsRow['status']==5 && ITime::getTime()-ITime::getTime($orderGoodsRow['completion_time'])<$limit_time )
			return true;//已完成且没有超过换货期限的肯定
		
		 if(in_array($orderGoodsRow['status'],array(2,7,8,9)))
		 {
			return true;
		}
		return false;
	}
}