<?php
class refunds{
	
	/**
	 * 判断退款单商品是否发货
	 * @param int $refund_id退货单id
	 * @return int 0:未发货，1：已发货，2：已退回
	 */
	public static function is_send($refund_id){
		$db = new IModel('refundment_doc');
		$res = $db->getObj(' id='.$refund_id,'order_id,goods_id,product_id');
		$db->changeTable('order_goods');
		return $db->getField('order_id = '.$res['order_id'].' and goods_id = '.$res['goods_id'].' and product_id = '.$res['product_id'],'is_send');
	}
	

}