<?php
/**
 * @file hookCreateAction.php
 * @brief 对action动作进行拦截，对部分需要钩子的action设置代码调用
 *        钩子名称为： function 控制器ID_动作ID,遇到此动作时优先调用钩子方法
 * @author 
 * @date 2015/5/26 23:01:46
 * @version 3.2
 */
class hookCreateAction extends IInterceptorBase
{
	private static $hookAction = array(
		'ucenter_refunds'=>array('order_refundment_list',
				'ucenter_refunds_detail',
				'order_order_refundment_list',
				'order_refundment_doc_show',
				'seller_refundment_list',
				'seller_refundment_show'
		),
		'ucenter_order' =>array('order_order_list'),
	);
	
	//根据控制器ID和动作ID生成钩子方法名
	public static function getHookRule()
	{
		$ctrlId  = IWeb::$app->getController()->getId();
		$actionId= IWeb::$app->getController()->getAction()->getId();
		return join('_',array($ctrlId,$actionId));
	}

	//createAction拦截器统一入口
	public static function onCreateAction()
	{
		$hookName = self::getHookRule();
		if(method_exists(__CLASS__,$hookName))
		{
			call_user_func(array(__CLASS__,$hookName));
		}
// 		foreach(self::$hookAction as $k=>$v){
// 			if( in_array($hookName,$v)){
// 				call_user_func(array(__CLASS__,$k));
// 				break;
// 			}
				
// 		}
	}
	public static function order_order_list(){
		self::ucenter_order();
	}

	public static function order_refundment_list(){
		self::ucenter_refunds();
	}
    
    //平台后台商品列表
    public static function goods_goods_list()
    {
        $goods = new IModel('goods');
        $ids = $goods->query("type=1 and is_del=0 and past_time <> '0000-00-00' and past_time<DATE_FORMAT(NOW(),'%Y-%m-%d')", 'id');
        $idList = array();
        foreach($ids as $k => $v)
        {
            $idList[] = $v['id'];
        }
        unset($ids);
        //打开商品列表页，自动下架过期商品
        if($idList)
        {
            $goods->setData(array('is_del'=>2));
            $goods->update('id in ('.join(',', $idList).')');
        }
        unset($idList);
    }
    
    //商家后台商品列表
    public static function seller_goods_list()
    {
        $goods = new IModel('goods');
        $ids = $goods->query("type=1 and is_del=0 and past_time <> '0000-00-00' and past_time<DATE_FORMAT(NOW(),'%Y-%m-%d')", 'id');
        $idList = array();
        foreach($ids as $k => $v)
        {
            $idList[] = $v['id'];
        }
        unset($ids);
        
        //打开商品列表页，自动下架过期商品
        if($idList)
        {
            $goods->setData(array('is_del'=>2));
            $goods->update('id in ('.join(',', $idList).')');
        }
        unset($idList);
    }
    
	//用户中心退款列表 
	public static function ucenter_refunds()
	{
		$siteConfigObj = new Config('site_config');
		$site_config = $siteConfigObj->getInfo();
		$refunds_limit_time=isset($site_config['refunds_limit_time']) ? intval($site_config['refunds_limit_time']) : 7;
		$refunds_seller_time=isset($site_config['refunds_limit_time']) ? intval($site_config['refunds_limit_time']) : 7;
		$refundment_db = new IModel('refundment_doc');
		
		$refunds_limit_second = $refunds_limit_time*24*3600;
		$refunds_seller_second = $refunds_seller_time*24*3600;
		//超期未退货状态更改
		$refunds_db = new IQuery('refundment_doc as r');
		$refunds_db->join = 'left join order_goods as og on r.order_id=og.order_id and r.goods_id=og.goods_id and r.product_id=og.product_id left join order as o on o.id=r.order_id';
		$refunds_db->where = "r.if_del = 0 and r.type=0 and r.pay_status in (0,4) and TIMESTAMPDIFF(second,r.time,NOW()) >= {$refunds_seller_second}";
		$refunds_db->fields = 'r.pay_status,r.id as rid,og.*';
		$resData = $refunds_db->find();
		//$refunds_db->where = "if_del = 0 and pay_status=4 and TIMESTAMPDIFF(second,delivery_time,NOW()) >= {$refunds_seller_second}";
		
		//后台超期未审核，自动打钱
		
		if(!empty($resData)){
			
			foreach($resData as $k=>$v){
				try{
					Order_Class::refund($v['rid'],'','system');
					
					if($v['is_send']==1){
						Order_Class::addGoodsCommentChange($v['order_id']);
					}
					Order_Class::get_order_status_refunds($v['rid'],2);
					Order_Class::ordergoods_status_refunds(2,$v,0);
					$refundment_db->setData(array('pay_status'=>2));
					$refundment_db->update('id='.$v['rid']);
				}
				catch(Exception $e){
					$e->getMessage();
				}
				
			}
		}
		//超期未审核的换货单
		$refunds_data = $refundment_db->query("if_del=0 and type=1 and pay_status=4 and TIMESTAMPDIFF(second,time,NOW()) >= {$refunds_seller_second}");
		if(!empty($refunds_data)){
			foreach($refunds_data as $k=>$v){
				try{
					$chgRes = Order_Class::chg_goods($v['id'],$v['goods_id'],$v['product_id'],'system');
					$refundment_db->setData(array('pay_status'=>2));
					$refundment_db->update('id='.$v['id']);
				}
				catch(Exception $e){
						
				}
			}
		}
		
		
		
	}
	//用户中心订单列表
	public static function ucenter_order()
	{
		self::ucenter_refunds();
		
		$siteConfigObj = new Config('site_config');
		$site_config = $siteConfigObj->getInfo();
		$order_cancel_time = isset($site_config['order_cancel_time']) ? intval($site_config['order_cancel_time']) : 3;
		$order_finish_time = isset($site_config['order_finish_time']) ? intval($site_config['order_finish_time']) : 20;

		$refunds_limit_time=isset($site_config['refunds_limit_time']) ? intval($site_config['refunds_limit_time']) : 7;
		
		
		$orderModel = new IModel('order');
		$order_cancel_second = $order_cancel_time*24*3600;
		$order_finish_second = $order_finish_time*24*3600;
		$orderCancelData  = $order_cancel_time > 0 ? $orderModel->query(" if_del = 0 and type!=4 and pay_type != 0 and status in(1) and TIMESTAMPDIFF(second,create_time,NOW()) >= {$order_cancel_second} ","id,order_no,4 as type_data") : array();
		$orderCreateData  = $order_finish_time > 0 ? $orderModel->query(" if_del = 0 and type!=4 and distribution_status = 1 and status in(9,1,2) and TIMESTAMPDIFF(second,send_time,NOW()) >= {$order_finish_second} ","id,order_no,5 as type_data") : array();

		$resultData = array_merge($orderCreateData,$orderCancelData);
		if($resultData)
		{
			$tb_order = new IModel('order');
			$tb_order_log = new IModel('order_log');
			foreach($resultData as $key => $val)
			{
				$type     = $val['type_data'];
				$order_id = $val['id'];
				$order_no = $val['order_no'];

				//oerder表的对象
				
				$tb_order->setData(array(
					'status'          => $type,
					'completion_time' => ITime::getDateTime(),
				));
				$tb_order->update('id='.$order_id);

				//生成订单日志
				
				$action = '作废';
				$note   = '订单【'.$order_no.'】作废成功';

				if($type=='5')
				{
					$action = '完成';
					$note   = '订单【'.$order_no.'】完成成功';

					//完成订单并且进行支付
					Order_Class::updateOrderStatus($order_no);

					//增加用户评论商品机会
					Order_Class::addGoodsCommentChange($order_id);

					$logObj = new log('db');
					$logObj->write('operation',array("系统自动","订单更新为完成",'订单号：'.$order_no));
				}
				else
				{
					Order_class::resetOrderProp($order_id);

					$logObj = new log('db');
					$logObj->write('operation',array("系统自动","订单更新为作废",'订单号：'.$order_no));
				}

				$tb_order_log->setData(array(
					'order_id' => $order_id,
					'user'     => "系统自动",
					'action'   => $action,
					'result'   => '成功',
					'note'     => $note,
					'addtime'  => ITime::getDateTime(),
				));
				$tb_order_log->add();
			}
		}
		
	}
	public static function ucenter_preorder_detail(){
		self::ucenter_preorder();
	}
	public static function preorder_preorder_list(){
		self::ucenter_preorder();
	}
	public static function preorder_preorder_show(){
		self::ucenter_preorder();
	}
	//用户中心预售订单
	public static function ucenter_preorder(){
		$siteConfigObj = new Config('site_config');
		$site_config = $siteConfigObj->getInfo();
		//获取订单取消，完成天数
		$order_cancel_time = isset($site_config['order_cancel_time']) ? intval($site_config['order_cancel_time']) : 3;
		$order_finish_time = isset($site_config['order_finish_time']) ? intval($site_config['order_finish_time']) : 20;
		$order_cancel_second = $order_cancel_time*24*3600;//天数换成秒数
		$order_finish_second = $order_finish_time*24*3600;
		
		$orderModel = new IModel('order');
		
		$orderCancelData  = $order_cancel_time > 0 ? $orderModel->query(" if_del = 0 and type=4 and status in(1) and TIMESTAMPDIFF(second,create_time,NOW()) >= {$order_cancel_second} ","id,order_no,2 as type_data") : array();
		$orderCreateData  = $order_finish_time > 0 ? $orderModel->query(" if_del = 0 and type=4 and status in(9) and TIMESTAMPDIFF(second,send_time,NOW()) >= {$order_finish_second} ","id,order_no,11 as type_data") : array();
			
		$order_db = new IQuery('order as o');
		//超期未支付预付款
		$order_db->join = 'left join presell as p on o.active_id = p.id';
		$where  = 'o.type=4 and o.if_del = 0 and o.status=1 ';
		$where .= ' and TIMESTAMPDIFF(second,p.yu_end_time,NOW()) >0';
		$order_db->where = $where;
		$order_db->fields = 'o.id,o.order_no,2 as type_data';
		$orderCancelDatayu = $order_db->find();
		
		//超期未支付尾款订单
		$order_db->join = 'left join presell as p on o.active_id = p.id';
		$where  = 'o.type=4 and o.if_del = 0 and o.status=4 and p.money_rate!=100 ';
		$where .= ' and (p.wei_type = 0 and TIMESTAMPDIFF(second,o.pay_time,NOW()) >= p.wei_days*24*3600 OR ';
		$where .= ' p.wei_type=1 and TIMESTAMPDIFF(second,p.wei_end_time,NOW()) >0 )';
		$order_db->where = $where;
		$order_db->fields = 'o.id,o.order_no,8 as type_data';
		$orderCancelDataWei = $order_db->find();
		
		//超期未确认订单
		$order_db = new IQuery('order as o');
		$order_db->join = 'left join presell as p on o.active_id = p.id left join order_goods as og on o.id = og.order_id ';
		$where  = 'o.type=4 and o.if_del=0 and o.status = 3 ';
		$where .= ' and (p.sure_type = 0 and TIMESTAMPDIFF(second,o.pay_time,NOW()) >= p.sure_days*24*3600 OR ';
		$where .= ' p.sure_type = 1 and TIMESTAMPDIFF(second,p.sure_end,NOW()) > 0 )';
		$order_db->where = $where;
		$order_db->fields = 'o.id,o.order_no,o.pre_amount,o.user_id,og.id as order_goods_id,5 as type_data';
		$orderCancelDataSure = $order_db->find();
		$resultData = array_merge($orderCreateData,$orderCancelData,$orderCancelDatayu,$orderCancelDataWei,$orderCancelDataSure);
		
		if($resultData)
		{
			$tb_order = new IModel('order');
			$tb_order_log = new IModel('order_log');
			foreach($resultData as $key => $val)
			{
				$type     = $val['type_data'];
				$order_id = $val['id'];
				$order_no = $val['order_no'];
					
				//oerder表的对象
					
				$tb_order->setData(array(
						'status'          => $type,
						'completion_time' => ITime::getDateTime(),
				));
				$tb_order->update('id='.$order_id);
					
				//生成订单日志
					
				$action = '作废';
				$note   = '订单【'.$order_no.'】作废成功';
					
				if($type=='11')
				{
					$action = '完成';
					$note   = '订单【'.$order_no.'】完成成功';
						
					//完成订单并且进行支付
				//	Order_Class::updateOrderStatus($order_no);
						
					//增加用户评论商品机会
					Order_Class::addGoodsCommentChange($order_id);
						
					$logObj = new log('db');
					$logObj->write('operation',array("系统自动","订单更新为完成",'订单号：'.$order_no));
				}
				else if($type=='2')
				{
					Order_class::resetOrderProp($order_id);
						
					$logObj = new log('db');
					$logObj->write('operation',array("系统自动","订单更新为作废",'订单号：'.$order_no));
				}
				else if($type=='5'){
					$updateData = array(
							'order_no'     => $order_no,
							'order_id'     => $order_id,
							'admin_id'     => 0,
							'pay_status'   => 0,
							'time' => ITime::getDateTime(),
							'amount'       => $val['pre_amount'],
							'user_id'      => $val['user_id'],
					);
					Order_class::resetOrderProp($order_id);
					Preorder_Class::refundHandle(0,$val['order_goods_id'],$updateData);
					$logObj = new log('db');
					$logObj->write('operation',array("系统自动","订单更新为作废",'订单号：'.$order_no));
				}
				else if($type=='8'){
					$logObj = new log('db');
					$logObj->write('operation',array("系统自动","订单更新为作废",'订单号：'.$order_no));
				}
					
				$tb_order_log->setData(array(
						'order_id' => $order_id,
						'user'     => "系统自动",
						'action'   => $action,
						'result'   => '成功',
						'note'     => $note,
						'addtime'  => ITime::getDateTime(),
				));
				$tb_order_log->add();
			}
		}
		
	}
	public static function site_index(){
		self::pregoods_presell_list();
	}
	//自动将到期的预售产品改为下架
	public static function pregoods_presell_list(){
		$db_presell = new IModel('presell');
		if($presell_list = $db_presell->query('is_close=0 and TIMESTAMPDIFF(second,yu_end_time,NOW()) >0','goods_id')){
			$ids = '';
			foreach($presell_list as $val){
				$ids .= $val['goods_id'].',';
			}
			$ids = substr($ids,0,-1);
			$goods_db = new IModel('goods');
			$goods_db->setData(array('is_del'=>2));
			$goods_db->update('id in ('.$ids.')');
		}
			
	}
	public static function site_search_list(){
		self::pregoods_presell_list();
	}
	public static function site_products(){
		self::pregoods_presell_list();
	}
}