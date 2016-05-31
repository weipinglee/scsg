<?php
/**
 * @brief 订单模块
 * @class Order
 * @note  后台
 */
class Order extends IController
{
	public $checkRight  = 'all';
	public $layout='admin';
	function init()
	{
		IInterceptor::reg('CheckRights@onCreateAction');
	}
                
    //验证二维码
    function checkCode(){
        $order_id = IFilter::act(IReq::get('id'), 'int');
        $good_id = IFilter::act(IReq::get('gId'), 'int');
        $good = new IModel('goods');
        $time = $good->getField('id='.$good_id, 'past_time');
        if($time && $time <> '0000-00-00' && $time < date('Y-m-d'))
        {
            exit('该商品已过期');
        }         
        $this->order_show($order_id, $good_id);
    }
    
	/**
	 * @brief查看订单
	 */
	public function order_show($order_id = '', $good_id = '')
	{
		//获得post传来的值
		$order_id = $order_id ? $order_id : IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($order_id)
		{
			$order_show = new Order_Class();
			$data = $order_show->getOrderShow($order_id);
			if($data)
			{
				//获得折扣前的价格
			 	$rule = new ProRule($data['real_amount']+$data['pro_reduce']);
			 	$this->result = $rule->getInfo();

		 		//获取地区
		 		$data['area_addr'] = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']));
                $this->good_id = $good_id;
			 	$this->setRenderData($data);
				$this->redirect('order_show',false);
			}
		}
		if(!$data)
		{
			$this->redirect('order_list');
		}
	}
	/**
	 * @brief查看收款单
	 */
	public function collection_show()
	{
		//获得post传来的收款单id值
		$collection_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($collection_id)
		{
			$tb_collection = new IQuery('collection_doc as c ');
			$tb_collection->join=' left join order as o on c.order_id=o.id left join payment as p on c.payment_id = p.id left join user as u on u.id = c.user_id';
			$tb_collection->fields = 'o.order_no,p.name as pname,o.create_time,p.type,u.username,c.amount,o.pay_time,c.admin_id,c.note';
			$tb_collection->where = 'c.id='.$collection_id;
			$collection_info = $tb_collection->find();
			if($collection_info)
			{
				$data = $collection_info[0];

				$this->setRenderData($data);
				$this->redirect('collection_show',false);
			}
		}
		if(count($data)==0)
		{
			$this->redirect('order_collection_list');
		}
	}


	/**
	 * @brief查看退款单
	 */
	public function refundment_show()
	{
	 	//获得post传来的退款单id值
	 	$refundment_id = IFilter::act(IReq::get('id'),'int');
	 	$data = array();
	 	if($refundment_id)
	 	{
	 		$tb_refundment = new IQuery('refundment_doc as c');
	 		$tb_refundment->join=' left join order as o on c.order_id=o.id left join user as u on u.id = c.user_id';
	 		$tb_refundment->fields = 'o.order_no,o.create_time,u.username,c.*';
	 		$tb_refundment->where = 'c.id='.$refundment_id;
	 		$refundment_info = $tb_refundment->find();
	 		if($refundment_info)
	 		{
	 			$data = current($refundment_info);
	 			$this->setRenderData($data);
	 			$this->redirect('refundment_show',false);
	 		}
	 	}

	 	if(!$data)
		{
			$this->redirect('order_refundment_list');
		}
	}
	public function refundment_list(){
		$where = ' and  1 ';
		$order_no = IFilter::act(IReq::get('order_no'));
		//筛选、
		$beginTime = IFilter::act(IReq::get('beginTime'));
		$endTime = IFilter::act(IReq::get('endTime'));
		$data['beginTime'] = $beginTime;
		$data['endTime'] = $endTime;
		$data['order_no'] = $order_no;
		
		if($beginTime)
		{
			$where .= ' and rd.time > "'.$beginTime.'"';
		}
		if($endTime)
		{
			$where .= ' and rd.time < "'.$endTime.'"';
		}
		if($order_no){
			$where .= ' and rd.order_no = "'.$order_no.'"';
		}
		$this->setRenderData($data);
		$this->where = $where;
		$this->redirect('refundment_list');
	}
	public function refundment_chg_list(){
		$where = ' and  1 ';
		$order_no = IFilter::act(IReq::get('order_no'));
		//筛选、
		$beginTime = IFilter::act(IReq::get('beginTime'));
		$endTime = IFilter::act(IReq::get('endTime'));
		$data['beginTime'] = $beginTime;
		$data['endTime'] = $endTime;
		$data['order_no'] = $order_no;
	
		if($beginTime)
		{
			$where .= ' and rd.time > "'.$beginTime.'"';
		}
		if($endTime)
		{
			$where .= ' and rd.time < "'.$endTime.'"';
		}
		if($order_no){
			$where .= ' and rd.order_no = "'.$order_no.'"';
		}
		$this->setRenderData($data);
		$this->where = $where;
		$this->redirect('refundment_chg_list');
	}
	public function fapiao_list(){
		$where = ' and  1 ';
		$order_no = IFilter::act(IReq::get('order_no'));
		$data['order_no'] = $order_no;
		
		if($order_no){
			$where .= ' and o.order_no = "'.$order_no.'"';
		}
		$this->setRenderData($data);
		$this->where = $where;
		$this->redirect('fapiao_list');
	}
	public function fapiao(){
		$where = ' and  1 ';
		$order_no = IFilter::act(IReq::get('order_no'));
		$data['order_no'] = $order_no;
		
		if($order_no){
			$where .= ' and o.order_no = "'.$order_no.'"';
		}
		$this->setRenderData($data);
		$this->where = $where;
		$this->redirect('fapiao');
	}
	/**
	 * @brief查看申请退款单
	 */
	public function refundment_doc_show()
	{
	 	//获得post传来的申请退款单id值
	 	$refundment_id = IFilter::act(IReq::get('id'),'int');
	 	if($refundment_id)
	 	{
	 		$refundsDB = new IModel('refundment_doc');
	 		$data = $refundsDB->getObj('id = '.$refundment_id);
	 		if($data)
	 		{
	 			$this->setRenderData($data);
	 			$this->redirect('refundment_doc_show',false);
	 			exit;
	 		}
	 	}

	 	$this->redirect('refundment_list');
	}
	
	/**
	 * @brief查看换货单
	 */
	public function refundment_chged_show()
	{
		//获得post传来的申请退款单id值
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		if($refundment_id)
		{
			$refundsDB = new IModel('refundment_doc');
			$data = $refundsDB->getObj('id = '.$refundment_id);
			if($data)
			{
				$this->setRenderData($data);
				$this->redirect('refundment_chged_show',false);
				exit;
			}
		}
	
		$this->redirect('refundment_list');
	}
	/**
	 * @brief查看换货单
	 */
	public function refundment_apply_show()
	{
		//获得post传来的申请退款单id值
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		if($refundment_id)
		{
			$refundsDB = new IModel('refundment_doc');
			$data = $refundsDB->getObj('id = '.$refundment_id);
			if($data)
			{
				$this->setRenderData($data);
				$this->redirect('refundment_apply_show',false);
				exit;
			}
		}
	
		$this->redirect('refundment_list');
	}
	/**
	 * @brief查看申请换货单
	 */
	public function refundment_chg_show()
	{
		//获得post传来的申请退款单id值
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		if($refundment_id)
		{
			$refundsDB = new IModel('refundment_doc');
			$data = $refundsDB->getObj('id = '.$refundment_id);
			if($data)
			{
				$this->setRenderData($data);
				$this->redirect('refundment_chg_show',false);
				exit;
			}
		}
	
		$this->redirect('refundment_list');
	}
	//删除申请退款单
	public function refundment_doc_del()
	{
		//获得post传来的申请退款单id值
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		if(is_array($refundment_id))
		{
			$refundment_id = implode(",",$refundment_id);
		}
		if($refundment_id)
		{
			$tb_refundment_doc = new IModel('refundment_doc');
			$tb_refundment_doc->setData(array('if_del' => 1));
			$tb_refundment_doc->update("id IN ($refundment_id)");
		}

		$logObj = new log('db');
		$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"退款单移除到回收站",'移除的ID：'.$refundment_id));

		$this->redirect('refundment_list');
	}

	/**
	 * 更新申请换货单
	 */
	public function refundment_chg_show_save(){
		//获得post传来的退款单id值
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		$pay_status = IFilter::act(IReq::get('pay_status'),'int');
		$dispose_idea = IFilter::act(IReq::get('dispose_idea'),'text');
		$status=IFilter::act(IReq::get('status'),'int');//原先的pay_status
		
		$type = 1;
		$chg_goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$chg_product_id = IFilter::act(IReq::get('product_id'),'int');
		
		//获得refundment_doc对象
		
		$setData=array(
				'pay_status'   => $pay_status,
				'dispose_idea' => $dispose_idea,
				'dispose_time' => ITime::getDateTime(),
				'admin_id'     => $this->admin['admin_id'],
		);
		
		if($refundment_id)
		{
			
			$tb_refundment_doc = new IModel('refundment_doc');
			
			if($refund_data = $tb_refundment_doc->getObj('id='.$refundment_id,'order_id,pay_status,goods_id,product_id')){
				$order_goods_db = new IModel('order_goods');
				$order_goods_row = $order_goods_db->getObj('order_id='.$refund_data['order_id'].' and goods_id='.$refund_data['goods_id'].' and product_id='.$refund_data['product_id']);
		
				if($pay_status==2){//换货类型且审核通过
					if(!$chg_goods_id){
						$chg_goods_id = $refund_data['goods_id'];
						$chg_product_id = $refund_data['product_id'];
					}
					$chgRes = Order_Class::chg_goods($refundment_id,$chg_goods_id,$chg_product_id,$this->admin['admin_id']);
					if(!$chgRes){
						$this->redirect('refundment_chg_list');
						return false;
					}
					$tb_refundment_doc->setData($setData);
					$tb_refundment_doc->update('id='.$refundment_id);
				}else{//审核不通过
					$tb_refundment_doc->setData($setData);
					$tb_refundment_doc->update('id='.$refundment_id);
					Order_Class::ordergoods_status_refunds(5,$order_goods_row,1);
				}
			}
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"修改了换货单",'修改的ID：'.$refundment_id));
		}
		 $this->redirect('refundment_chg_list');
		
	}
	/**
	 * @brief更新申请退款单
	 */
	public function refundment_doc_show_save()
	{
		//获得post传来的退款单id值
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		$pay_status = IFilter::act(IReq::get('pay_status'),'int');
		$dispose_idea = IFilter::act(IReq::get('dispose_idea'),'text');
		$status=IFilter::act(IReq::get('status'),'int');//原先的pay_status
		$order_goods_db = new IModel('order_goods');
		$type = 0;
		$setData=array(
				'pay_status'   => $pay_status,
				'dispose_idea' => $dispose_idea,
				'dispose_time' => ITime::getDateTime(),
				'admin_id'     => $this->admin['admin_id'],
		);
		
		if($refundment_id)
		{
			$tb_refundment_doc = new IModel('refundment_doc');
			
			$tb_refundment_doc->setData($setData);
			$tb_refundment_doc->update('id='.$refundment_id);
			
			$refundment_row = $tb_refundment_doc->getObj('id='.$refundment_id,'order_id,goods_id,product_id');
			$goodsOrderRow = $order_goods_db->getObj('order_id='.$refundment_row['order_id'].' and goods_id='.$refundment_row['goods_id'].' and product_id ='.$refundment_row['product_id'],'is_send,id');
			Order_Class::get_order_status_refunds($refundment_id,$pay_status);
			Order_Class::ordergoods_status_refunds($pay_status,$goodsOrderRow,$type);
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"修改了退货单",'修改的ID：'.$refundment_id));
		}
		$this->redirect('refundment_list');
		
	}
	/**
	 * @brief查看发货单
	 */
	public function delivery_show()
	{
	 	//获得post传来的发货单id值
	 	$delivery_id = IFilter::act(IReq::get('id'),'int');
	 	$data = array();
	 	if($delivery_id)
	 	{
	 		$tb_delivery = new IQuery('delivery_doc as c ');
	 		$tb_delivery->join=' left join order as o on c.order_id=o.id left join delivery as p on c.delivery_type = p.id left join user as u on u.id = c.user_id';
	 		$tb_delivery->fields = 'c.id as id,o.order_no,c.order_id,p.name as pname,o.create_time,u.username,c.name,c.province,c.city,c.area,c.address,c.mobile,c.telphone,c.postcode,c.freight,c.delivery_code,c.time,c.note ';
	 		$tb_delivery->where = 'c.id='.$delivery_id;
	 		$delivery_info = $tb_delivery->find();
	 		if($delivery_info)
	 		{
	 			$data = current($delivery_info);
	 			$data['country'] = join("-",area::name($data['province'],$data['city'],$data['area']));

	 			$this->setRenderData($data);
	 			$this->redirect('delivery_show',false);
	 		}
	 	}

	 	if(!$data)
		{
			$this->redirect('order_delivery_list');
		}
	}
	/**
	 * @brief 支付订单页面collection_doc
	 */
	public function order_collection()
	{
	 	//去掉左侧菜单和上部导航
	 	$this->layout='';
	 	$order_id = IFilter::act(IReq::get('id'),'int');
	 	$data = array();
	 	if($order_id)
	 	{
	 		$order_show = new Order_Class();
	 		$data = $order_show->getOrderShow($order_id);
	 	}
	 	$this->setRenderData($data);
	 	$this->redirect('order_collection');
	}
	/**
	 * @brief 保存支付订单页面collection_doc
	 */
	public function order_collection_doc()
	{
	 	//获得订单号
	 	$order_no = IFilter::act(IReq::get('order_no'));
	 	$note     = IFilter::act(IReq::get('note'));

	 	if(Order_Class::updateOrderStatus($order_no,$this->admin['admin_id'],$note))
	 	{
		 	//生成订单日志
	    	$tb_order_log = new IModel('order_log');
	    	$tb_order_log->setData(array(
	    		'order_id' =>IFilter::act(IReq::get('id'),'int'),
	    		'user' =>$this->admin['admin_name'],
	    		'action' =>'付款',
	    		'result' =>'成功',
	    		'note' =>'订单【'.$order_no.'】付款'.IFilter::act(IReq::get('amount'),'float').'元',
	    		'addtime'=>date('Y-m-d H:i:s',time()+1000)
	    	));
	    	$tb_order_log->add();

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"订单更新为已付款","订单号：".$order_no.'，已经确定付款'));
	 		echo '<script type="text/javascript">parent.actionCallback();</script>';
	 	}
	 	else
	 	{
	 		echo '<script type="text/javascript">parent.actionFailCallback();</script>';
	 	}
	}
	/**
	 * @brief 退款单页面
	 */
	public function order_refundment()
	{
		//去掉左侧菜单和上部导航
		$this->layout='';
		$orderId   = IFilter::act(IReq::get('id'),'int');
		$refundsId = IFilter::act(IReq::get('refunds_id'),'int');
		if($orderId)
		{
			$orderDB = new Order_Class();
			$data    = $orderDB->getOrderShow($orderId);
			$refundsDB  = new IModel('refundment_doc');
			//已经存退款申请
			if($refundsId)
			{
				
				$refundsRow = $refundsDB->getObj('id = '.$refundsId);
				$data['refunds'] = $refundsRow;
				
			}else{
				$order_goods_db = new IQuery('order_goods as og');
				$order_goods_db->join = 'left join order as o on o.id=og.order_id left join refundment_doc as r on r.order_id=o.id and r.goods_id=og.goods_id and r.product_id=og.product_id and r.type=0';
				$order_goods_db->where = 'o.id='.$orderId.' and r.pay_status in(0,4,7) ';
				$order_goods_db->fields = 'og.*,o.pro_reduce,o.ticket_reduce,o.real_amount,o.pro_reduce,r.id as refunds_id,r.amount';
				$order_goods_data = $order_goods_db->find();
				$data['order_goods_data'] = $order_goods_data;
			}
			$this->setRenderData($data);
			$this->redirect('order_refundment');
			exit;
		}
		die('订单数据不存在');
	}

	/**
	 * @brief 保存退款单页面
	 */
	public function order_refundment_doc()
	{
		$refunds_id = IFilter::act(IReq::get('refunds_id'),'int');
		$order_id = IFilter::act(IReq::get('id'),'int');
		$order_no = IFilter::act(IReq::get('order_no'));
		$user_id  = IFilter::act(IReq::get('user_id'),'int');
		$amount   = IFilter::act(IReq::get('amount'),'float'); //要退款的金额
		$order_goods_id = IFilter::act(IReq::get('order_goods_id'),'int'); //要退款的商品,如果是用户已经提交的退款申请此数据为NULL,需要获取出来

		if(!$user_id)
		{
			die('<script text="text/javascript">parent.actionCallback("游客无法退款");</script>');
		}

		$orderGoodsDB      = new IModel('order_goods');
		$tb_refundment_doc = new IModel('refundment_doc');
		
		$orderGoodsRow = $orderGoodsDB->getObj('id = '.$order_goods_id);
		if($amount>$orderGoodsRow['real_price']*$orderGoodsRow['goods_nums']+$orderGoodsRow['delivery_fee']+$orderGoodsRow['save_price']+$orderGoodsRow['tax']){
			die('<script text="text/javascript">parent.actionCallback("退款金额不得大于实际支付金额");</script>');
			return false;
		}
		//无退款申请单，必须生成退款单
		if(!$refunds_id)
		{
			if(!$order_goods_id)return false;
			$updateData = array(
					'order_no'     => $order_no,
					'order_id'     => $order_id,
					'admin_id'     => $this->admin['admin_id'],
					'pay_status'   => 2,
					'dispose_time' => ITime::getDateTime(),
					'dispose_idea' => '退款成功',
					'amount'       => $amount,
					'user_id'      => $user_id,
			);

			//插入refundment_doc表
			$updateData['time']       = ITime::getDateTime();
			$updateData['goods_id']   = $orderGoodsRow['goods_id'];
			$updateData['product_id'] = $orderGoodsRow['product_id'];

			$goodsDB = new IModel('goods');
			$goodsRow= $goodsDB->getObj('id = '.$orderGoodsRow['goods_id']);
			$updateData['seller_id'] = $goodsRow['seller_id'];

			$tb_refundment_doc->setData($updateData);
			$refunds_id = $tb_refundment_doc->add();
			$tb_refundment_doc->commit();
		}else{
			$tb_refundment_doc->setData(array('amount'=>$amount));
			$tb_refundment_doc->update('id='.$refunds_id);
			$tb_refundment_doc->commit();
		}
		
			$result = Order_Class::refund($refunds_id,$this->admin['admin_id'],'admin');
			if($orderGoodsRow['is_send']==1){
				//增加用户评论商品机会
				Order_Class::addGoodsCommentChange($order_id);
				
			}
			Order_Class::get_order_status_refunds($refunds_id,2);
			Order_Class::ordergoods_status_refunds(2,$orderGoodsRow,0);
		if($result)
		{
			
			//记录操作日志
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"订单更新为退款",'订单号：'.$order_no));
			die('<script text="text/javascript">parent.actionCallback();</script>');
		}
		else
		{
			die('<script text="text/javascript">parent.actionCallback("退货错误");</script>');
		}
	}
	/**
	 * @brief 保存订单备注
	 */
	public function order_note()
	{
	 	//获得post数据
	 	$order_id = IFilter::act(IReq::get('order_id'),'int');
	 	$note = IFilter::act(IReq::get('note'),'text');

	 	//获得order的表对象
	 	$tb_order =  new IModel('order');
	 	$tb_order->setData(array(
	 		'note'=>$note
	 	));
	 	$tb_order->update('id='.$order_id);
	 	IReq::set('id',$order_id);
	 	$this->redirect('order_show/id/'.$order_id);

	}
	/**
	 * @brief 保存顾客留言
	 */
	public function order_message()
	{
		//获得post数据
		$order_id = IFilter::act(IReq::get('order_id'),'int');
		$user_id = IFilter::act(IReq::get('user_id'),'int');
		$title = IFilter::act(IReq::get('title'));
		$content = IFilter::act(IReq::get('content'),'text');

		//获得message的表对象
		$tb_message =  new IModel('message');
		$tb_message->setData(array(
			'title'=>$title,
			'content' =>$content,
			'time'=>date('Y-m-d H:i:s')
		));
		$message_id = $tb_message->add();
		//获的mess类
		$message = new Mess($user_id);
		$message->writeMessage($message_id);
		IReq::set('id',$order_id);
		$this->order_show();
	}
	/**
	 * @brief 完成或作废订单页面
	 **/
	public function order_complete()
	{
		//去掉左侧菜单和上部导航
		$this->layout='';
		$order_id = IFilter::act(IReq::get('id'),'int');
		$type     = IFilter::act(IReq::get('type'),'int');
		$order_no = IFilter::act(IReq::get('order_no'));
		
		
		if($type==4 && !Order_Class::is_cancle($order_id)){//不可作废
			echo 0;
			return false;
		}
		//oerder表的对象
		$tb_order = new IModel('order');
		$tb_order->setData(array(
			'status'          => $type,
			'completion_time' => ITime::getDateTime(),
		));
		
		$tb_order->update('id='.$order_id);

		//生成订单日志
		$tb_order_log = new IModel('order_log');
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
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"订单更新为完成",'订单号：'.$order_no));
		}
		else
		{
			Order_class::resetOrderProp($order_id);

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"订单更新为作废",'订单号：'.$order_no));
		}

		$tb_order_log->setData(array(
			'order_id' => $order_id,
			'user'     => $this->admin['admin_name'],
			'action'   => $action,
			'result'   => '成功',
			'note'     => $note,
			'addtime'  => ITime::getDateTime(),
		));
		$tb_order_log->add();
		die('success');
	}
	/**
	 * @brief 发货订单页面
	 */
	public function order_deliver()
	{
		//去掉左侧菜单和上部导航
		$this->layout='';
		$order_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($order_id)
		{
			$order_show = new Order_Class();
			$data = $order_show->getOrderShow($order_id);
		}
		$this->setRenderData($data);
		$this->redirect('order_deliver');
	}
	/**
	 * @brief 发货操作
	 */
	public function order_delivery_doc()
	{
	 	//获得post变量参数
	 	$order_id = IFilter::act(IReq::get('id'),'int');

	 	//发送的商品关联
	 	$sendgoods = IFilter::act(IReq::get('sendgoods'));

	 	if(!$sendgoods)
	 	{
	 		die('<script type="text/javascript">parent.actionCallback("请选择要发货的商品");</script>');
	 	}

	 	Order_Class::sendDeliveryGoods($order_id,$sendgoods,$this->admin['admin_id']);

		die('<script type="text/javascript">parent.actionCallback();</script>');
	}
	/**
	 * @brief 保存修改订单
	 */
    public function order_update()
    {
    	//获取必要数据
    	$order_id = IFilter::act(IReq::get('id'),'int');

    	//生成order数据
    	$dataArray['invoice_title'] = IFilter::act(IReq::get('invoice_title'));
    	$dataArray['invoice']       = IFilter::act(IReq::get('invoice'),'int');
    	$dataArray['if_insured']    = IFilter::act(IReq::get('if_insured'));
    	$dataArray['pay_type']      = IFilter::act(IReq::get('pay_type'),'int');
    	$dataArray['accept_name']   = IFilter::act(IReq::get('accept_name'));
    	$dataArray['postcode']      = IFilter::act(IReq::get('postcode'));
    	$dataArray['telphone']      = IFilter::act(IReq::get('telphone'));
    	$dataArray['province']      = IFilter::act(IReq::get('province'),'int');
    	$dataArray['city']          = IFilter::act(IReq::get('city'),'int');
    	$dataArray['area']          = IFilter::act(IReq::get('area'),'int');
    	$dataArray['address']       = IFilter::act(IReq::get('address'));
    	$dataArray['mobile']        = IFilter::act(IReq::get('mobile'));
    	$dataArray['discount']      = IFilter::act(IReq::get('discount'),'float');
    	$dataArray['postscript']    = IFilter::act(IReq::get('postscript'));
    	$dataArray['distribution']  = IFilter::act(IReq::get('distribution'),'int');
    	$dataArray['accept_time']   = IFilter::act(IReq::get('accept_time'));

		$goods_id   = IFilter::act(IReq::get('goods_id'));
		$product_id = IFilter::act(IReq::get('product_id'));
		$goods_nuns = IFilter::act(IReq::get('goods_nums'));
		if(!$goods_id || !$dataArray['accept_name'] || !$dataArray['area'] || !$dataArray['address'] || !$dataArray['mobile']){
			$this->orderRow = $dataArray;
			$this->redirect('order_edit',false);
			Util::showMessage('请完善订单信息');
		}
		foreach($goods_nuns as $v){
			if($v==0){$this->redirect('order_edit');
			Util::showMessage('商品数量不能为0');}
		}
		//设置订单持有者
		$username = IFilter::act(IReq::get('username'));
		$userDB   = new IModel('user');
		$userRow  = $userDB->getObj('username = "'.$username.'"');
		$dataArray['user_id'] = isset($userRow['id']) ? $userRow['id'] : 0;

		//拼接要购买的商品或货品数据,组装成固有的数据结构便于计算价格
		$length = count($product_id);
		$buyInfo = array(
			'goods' => array('id' => array() , 'data' => array()),
			'product' => array('id' => array() , 'data' => array())
		);
		
		for($i = 0;$i < $length;$i++)
		{
			//货品数据
			if(intval($product_id[$i]) > 0)
			{
				$buyInfo['product']['id'][] = $product_id[$i];
				$buyInfo['product']['data'][$product_id[$i]] = array('count' => $goods_nuns[$i]);
			}
			//商品数据
			else
			{
				$buyInfo['goods']['id'][] = $goods_id[$i];
				$buyInfo['goods']['data'][$goods_id[$i]] = array('count' => $goods_nuns[$i]);
			}
		}

		//开始算账
		$countSumObj = new CountSum();
		$goodsResult = $countSumObj->goodsCount($buyInfo);
		$orderFee    = $countSumObj->countOrderFee($goodsResult,$dataArray['province'],$dataArray['distribution'],$dataArray['pay_type'],$dataArray['if_insured'],$dataArray['invoice'],$dataArray['discount']);

		//获取原价的运费
		$dataArray['payable_freight']= $orderFee['deliveryOrigPrice'];
		$dataArray['payable_amount'] = $goodsResult['sum'];
		$dataArray['real_amount']    = $goodsResult['final_sum'];
		$dataArray['real_freight']   = $orderFee['deliveryPrice'];
		$dataArray['insured']        = $orderFee['insuredPrice'];
		$dataArray['pay_fee']        = $orderFee['paymentPrice'];
		$dataArray['taxes']          = $orderFee['taxPrice'];
		$dataArray['promotions']     = $goodsResult['proReduce']+$goodsResult['reduce'];
		$dataArray['order_amount']   = $orderFee['orderAmountPrice'];
		$dataArray['exp']            = $goodsResult['exp'];
		$dataArray['point']          = $goodsResult['point'];

    	//生成订单
    	$orderDB = new IModel('order');

    	//修改操作
    	if($order_id)
    	{
    		$orderDB->setData($dataArray);
    		$orderDB->update('id = '.$order_id);

    		//获取订单信息
    		$orderRow = $orderDB->getObj('id = '.$order_id);

			//记录日志信息
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"修改了订单信息",'订单号：'.$orderRow['order_no']));
    	}
    	//添加操作
    	else
    	{
    		$dataArray['create_time'] = date('Y-m-d H:i:s');
    		$dataArray['order_no']    = Order_Class::createOrderNum();

    		$orderDB->setData($dataArray);
    		$order_id = $orderDB->add();

			//记录日志信息
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"添加了订单信息",'订单号：'.$dataArray['order_no']));
    	}

    	//同步order_goods表
    	$orderInstance = new Order_Class();
    	$orderInstance->insertOrderGoods($order_id,$orderFee['goodsResult']);

    	$this->redirect('order_list');
    }
	/**
	 * @brief 修改订单
	 */
	public function order_edit()
    {
    	$data = array();

    	//获得order_id的值
		$order_id = IFilter::act(IReq::get('id'),'int');
		if($order_id)
		{
			$orderDB = new IModel('order');
			$data    = $orderDB->getObj('id = '.$order_id);
			$this->orderRow = $data;

			//获取订单中的商品信息
			$orderGoodsDB         = new IQuery('order_goods as og');
			$orderGoodsDB->join   = "left join goods as go on og.goods_id = go.id left join products as p on p.id = og.product_id";
			$orderGoodsDB->fields = "go.id,go.name,p.spec_array,p.id as product_id,og.real_price,og.goods_nums";
			$orderGoodsDB->where  = "og.order_id = ".$order_id;
			$this->orderGoods     = $orderGoodsDB->find();

			//获取用户名
			if($data['user_id'])
			{
				$userDB  = new IModel('user');
				$userRow = $userDB->getObj("id = ".$data['user_id']);
				$this->username = isset($userRow['username']) ? $userRow['username'] : '';
			}
		}
		$this->redirect('order_edit');
    }
    /**
     * @brief 订单列表
     */
    public function order_list()
    {
		//搜索条件
		$search = IFilter::act(IReq::get('search'),'strict');
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		//条件筛选处理
		list($join,$where) = order_class::getSearchCondition($search);
		//$join = $join.' left join refundment_doc as r on r.order_id=o.id and r.if_del=0 ';
		//$join2 = $join.' left join refundment_doc as r on r.order_id=o.id and r.if_del=0 and r.pay_status in (0,3,4,7)';
		//拼接sql
		
		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,d.name as distribute_name,u.username,p.name as payment_name";
		$orderHandle->page   = $page;
		$orderHandle->where  = $where.' and o.type !=4 ';
		$orderHandle->join   = $join;
		$orderHandle->group = 'o.id';
		$this->search      = $search;
		$this->orderHandle = $orderHandle;
		
		$order_list = $orderHandle->find();
		$this->order_list = $order_list;
		$order_list_new = array();
		$order_ids = '';
		foreach($order_list as $k=>$v){
			$order_ids .= $v['id'].',';
		}
		$order_ids = $order_ids=='' ? '' : substr($order_ids,0,-1);
		
		$refundment_doc_db = new IModel('refundment_doc');
		if($order_ids!='')
		$refund_data = $refundment_doc_db->query('order_id in ('.$order_ids.')','id,pay_status,type,order_id');
		else $refund_data = array();
		//print_r($refund_data);exit;
		$order_refund = array();
		if(!empty($refund_data)){
			foreach($refund_data as $k=>$v){
				if(in_array($v['pay_status'],array(0,3,4,7)))
					$order_refund[$v['order_id']][$v['type']]['refunding'] = $v['id'];
				else{
					$order_refund[$v['order_id']][$v['type']]['refunded'] = $v['id'];
				}
			}
		}
		
		$this->order_refund = $order_refund;
	
		$this->redirect("order_list");
    }
   
    /**
     * @brief 订单删除功能_删除到回收站
     */
    public function order_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
	
    	//生成order对象
    	$tb_order = new IModel('order');
    	$tb_order->setData(array('if_del'=>1));
    	if($id)
		{
			$id = $tb_order->update(Util::joinStr($id));

			//获取订单编号
			$orderRs   = $tb_order->query('id in ('.$id.')','order_no');
			$orderData = array();
			foreach($orderRs as $val)
			{
				$orderData[] = $val['order_no'];
			}

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"订单移除到回收站内",'订单号：'.join(',',$orderData)));

			$this->redirect('order_list');
		}
		else
		{
			$this->redirect('order_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
	/**
     * @brief 收款单删除功能_删除到回收站
     */
    public function collection_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('collection_doc');
    	$tb_order->setData(array('if_del'=>1));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"收款单移除到回收站内",'收款单ID：'.join(',',$id)));

			$this->redirect('order_collection_list');
		}
		else
		{
			$this->redirect('order_collection_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
    /**
     * 收款单列表
     */
    public function order_collection_list(){
    	$where = ' and  1 ';
    	$order_no = IFilter::act(IReq::get('order_no'));
    	$username = IFilter::act(IReq::get('username'));
    
    	
    	//筛选、
    	$beginTime = IFilter::act(IReq::get('beginTime'));
    	$endTime = IFilter::act(IReq::get('endTime'));
    	$data['beginTime'] = $beginTime;
    	$data['endTime'] = $endTime;
    	$data['order_no'] = $order_no;
    	$data['username'] = $username;
    	
    	if($beginTime)
    	{
    		$where .= ' and c.time > "'.$beginTime.'"';
    	}
    	if($endTime)
    	{
    		$where .= ' and c.time < "'.$endTime.'"';
    	}
    	if($order_no){
    		$where .= ' and o.order_no = "'.$order_no.'"';
    	}
    	if($username){
    		$where .= ' and u.username = "'.$username.'"';
    	}
    	$this->setRenderData($data);
    	$this->where = $where;
    	$this->redirect('order_collection_list');
    } 
	/**
     * @brief 收款单删除功能_删除回收站中的数据，彻底删除
     */
    public function collection_recycle_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('collection_doc');
    	if($id)
		{
			$tb_order->del(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"删除回收站内的收款单",'收款单ID：'.join(',',$id)));

			$this->redirect('collection_recycle_list');
		}
		else
		{
			$this->redirect('collection_recycle_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
	/**
	 * @brief 还原还款单列表
	 */
    public function collection_recycle_restore()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('collection_doc');
    	$tb_order->setData(array('if_del'=>0));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"恢复了回收站内的收款单",'收款单ID：'.join(',',$id)));

			$this->redirect('collection_recycle_list');
		}
		else
		{
			$this->redirect('collection_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
    }
    public function order_refundment_list(){
    	$where = ' and  1 ';
    	$order_no = IFilter::act(IReq::get('order_no'));
    	$username = IFilter::act(IReq::get('username'));
    	
    	 
    	//筛选、
    	$beginTime = IFilter::act(IReq::get('beginTime'));
    	$endTime = IFilter::act(IReq::get('endTime'));
    	$data['beginTime'] = $beginTime;
    	$data['endTime'] = $endTime;
    	$data['order_no'] = $order_no;
    	$data['username'] = $username;
    	 
    	if($beginTime)
    	{
    		$where .= ' and c.dispose_time > "'.$beginTime.'"';
    	}
    	if($endTime)
    	{
    		$where .= ' and c.dispose_time < "'.$endTime.'"';
    	}
    	if($order_no){
    		$where .= ' and c.order_no = "'.$order_no.'"';
    	}
    	if($username){
    		$where .= ' and u.username = "'.$username.'"';
    	}
    	$this->setRenderData($data);
    	$this->where = $where;
    	$this->redirect('order_refundment_list');
    }
    /**
     * 换货单列表
     */
    public function order_refundment_chg_list(){
    	$where = ' and  1 ';
    	$order_no = IFilter::act(IReq::get('order_no'));
    	$username = IFilter::act(IReq::get('username'));
    	 //筛选、
    	$beginTime = IFilter::act(IReq::get('beginTime'));
    	$endTime = IFilter::act(IReq::get('endTime'));
    	$data['beginTime'] = $beginTime;
    	$data['endTime'] = $endTime;
    	$data['order_no'] = $order_no;
    	$data['username'] = $username;
    
    	if($beginTime)
    	{
    		$where .= ' and c.dispose_time > "'.$beginTime.'"';
    	}
    	if($endTime)
    	{
    		$where .= ' and c.dispose_time < "'.$endTime.'"';
    	}
    	if($order_no){
    		$where .= ' and c.order_no = "'.$order_no.'"';
    	}
    	if($username){
    		$where .= ' and u.username = "'.$username.'"';
    	}
    	$this->setRenderData($data);
    	$this->where = $where;
    	$this->redirect('order_refundment_chg_list');
    }
	/**
	 * @brief 退款单删除功能_删除到回收站
	 */
    public function refundment_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('refundment_doc');
    	$tb_order->setData(array('if_del'=>1));
    	if(!empty($id))
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"退款单移除到回收站内",'退款单ID：'.join(',',$id)));

			$this->redirect('order_refundment_list');
		}
		else
		{
			$this->redirect('order_refundment_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
	/**
	 * @brief 退款单删除功能_删除回收站中的数据，彻底删除
	 */
    public function refundment_recycle_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('refundment_doc');
    	if(!empty($id))
		{
			$tb_order->del(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"删除了回收站内的退款单",'退款单ID：'.join(',',$id)));

			$this->redirect('refundment_recycle_list');
		}
		else
		{
			$this->redirect('refundment_recycle_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
	/**
	 * @brief 还原还款单列表
	 */
    public function refundment_recycle_restore()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('refundment_doc');
    	$tb_order->setData(array('if_del'=>0));
    	if(!empty($id))
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"还原了回收站内的还款单",'还款单ID：'.join(',',$id)));

			$this->redirect('refundment_recycle_list');
		}
		else
		{
			$this->redirect('refundment_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
    }
    /**
     * @brief 发货单删除功能_删除到回收站
     */
    public function delivery_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('delivery_doc');
    	$tb_order->setData(array('if_del'=>1));
    	if(!empty($id))
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"发货单移除到回收站内",'发货单ID：'.join(',',$id)));

			$this->redirect('order_delivery_list');
		}
		else
		{
			$this->redirect('order_delivery_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
    public function order_delivery_list(){
    	$where = ' and  1 ';
    	$order_no = IFilter::act(IReq::get('order_no'));
    	$username = IFilter::act(IReq::get('username'));
    	//筛选、
    	$beginTime = IFilter::act(IReq::get('beginTime'));
    	$endTime = IFilter::act(IReq::get('endTime'));
    	$data['beginTime'] = $beginTime;
    	$data['endTime'] = $endTime;
    	$data['order_no'] = $order_no;
    	$data['username'] = $username;
    	
    	if($beginTime)
    	{
    		$where .= ' and c.time > "'.$beginTime.'"';
    	}
    	if($endTime)
    	{
    		$where .= ' and c.time < "'.$endTime.'"';
    	}
    	if($order_no){
    		$where .= ' and o.order_no = "'.$order_no.'"';
    	}
    	if($username){
    		$where .= ' and m.username = "'.$username.'"';
    	}
    	$this->setRenderData($data);
    	$this->where = $where;
    	$this->redirect('order_delivery_list');
    }
	/**
     * @brief 发货单删除功能_删除回收站中的数据，彻底删除
     */
    public function delivery_recycle_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('delivery_doc');
    	if(!empty($id))
		{
			$tb_order->del(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"删除了回收站中的发货单",'发货单ID：'.join(',',$id)));

			$this->redirect('delivery_recycle_list');
		}
		else
		{
			$this->redirect('delivery_recycle_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
	/**
	 * @brief 还原发货单列表
	 */
    public function delivery_recycle_restore()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('delivery_doc');
    	$tb_order->setData(array('if_del'=>0));
    	if(!empty($id))
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"还原了回收站中的发货单",'发货单ID：'.join(',',$id)));

			$this->redirect('delivery_recycle_list');
		}
		else
		{
			$this->redirect('delivery_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
    }
    /**
     * @brief 订单删除功能_删除回收站中的数据，彻底删除
     */
    public function order_recycle_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');

    	//生成order对象
    	$tb_order = new IModel('order');

    	if($id)
		{
			$id = is_array($id) ? join(',',$id) : $id;

			Order_class::resetOrderProp($id);

			//删除订单
			$tb_order->del('id in ('.$id.')');

			//记录日志
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"删除回收站中订单",'订单ID：'.$id));

			$this->redirect('order_recycle_list');
		}
		else
		{
			$this->redirect('order_recycle_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
    /**
	 * @brief 还原订单列表
	 */
    public function order_recycle_restore()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('order');
    	$tb_order->setData(array('if_del'=>0));
    	if(!empty($id))
		{
			$tb_order->update(Util::joinStr($id));
			$this->redirect('order_recycle_list');
		}
		else
		{
			$this->redirect('order_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
    }
	/**
	 * @brief 订单打印模板修改
	 */
    public function print_template()
    {
		//获取根目录路径
		$path = IWeb::$app->getBasePath().'views/'.$this->theme.'/order';
    	//获取 购物清单模板
		$ifile_shop = new IFile($path.'/shop_template.html');
		$arr['ifile_shop']=$ifile_shop->read();
		//获取 配货单模板
		$ifile_pick = new IFile($path."/pick_template.html");
		$arr['ifile_pick']=$ifile_pick->read();

		$this->setRenderData($arr);
		$this->redirect('print_template');
    }
	/**
	 * @brief 订单打印模板修改保存
	 */
    public function print_template_update()
    {
		// 获取POST数据
    	$con_shop = IReq::get("con_shop");
		$con_pick = IReq::get("con_pick");

    	//获取根目录路径
		$path = IWeb::$app->getBasePath().'views/'.$this->theme.'/order';
    	//保存 购物清单模板
		$ifile_shop = new IFile($path.'/shop_template.html','w');
		if(!($ifile_shop->write($con_shop)))
		{
			$this->redirect('print_template',false);
			Util::showMessage('保存购物清单模板失败！');
		}
		//保存 配货单模板
		$ifile_pick = new IFile($path."/pick_template.html",'w');
		if(!($ifile_pick->write($con_pick)))
		{
			$this->redirect('print_template',false);
			Util::showMessage('保存配货单模板失败！');
		}
		//保存 合并单模板
    	$ifile_merge = new IFile($path."/merge_template.html",'w');
		if(!($ifile_merge->write($con_shop.$con_pick)))
		{
			$this->redirect('print_template',false);
			Util::showMessage('购物清单和配货单模板合并失败！');
		}

		$this->setRenderData(array('where'=>''));
		$this->redirect('order_list');
	}

	//购物单
	public function shop_template()
	{
		$this->layout='print';
		$order_id = IFilter::act( IReq::get('id'),'int' );
		$seller_id= IFilter::act( IReq::get('seller_id'),'int' );
		$type     = IFilter::act(IReq::get('type'));

		$tb_order =  new IModel('order');
		$where = $type ? ' and type=4' : ' and type !=4';
		$data     = $tb_order->getObj('id='.$order_id.$where);

		if($seller_id)
		{
			$sellerObj   = new IModel('seller');
			$config_info = $sellerObj->getObj('id = '.$seller_id);

	     	$data['set']['name']   = isset($config_info['true_name'])? $config_info['true_name'] : '';
	     	$data['set']['phone']  = isset($config_info['phone'])    ? $config_info['phone']     : '';
	     	$data['set']['email']  = isset($config_info['email'])    ? $config_info['email']     : '';
	     	$data['set']['url']    = isset($config_info['home_url']) ? $config_info['home_url']  : '';
		}
		else
		{
			$config = new Config("site_config");
			$config_info = $config->getInfo();

	     	$data['set']['name']   = isset($config_info['name'])  ? $config_info['name']  : '';
	     	$data['set']['phone']  = isset($config_info['phone']) ? $config_info['phone'] : '';
	     	$data['set']['email']  = isset($config_info['email']) ? $config_info['email'] : '';
	     	$data['set']['url']    = isset($config_info['url'])   ? $config_info['url']   : '';
		}

		$data['address']   = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']))."&nbsp;".$data['address'];
		$data['seller_id'] = $seller_id;
		$this->setRenderData($data);
		$this->redirect("shop_template");
	}
	//发货单
	public function pick_template()
	{
		$this->layout='print';
		$order_id = IFilter::act( IReq::get('id'),'int' );
		$seller_id= IFilter::act( IReq::get('seller_id'),'int' );

		$type     = IFilter::act(IReq::get('type'));

		$tb_order =  new IModel('order');
		$where = $type ? ' and type=4' : ' and type !=4';
		$data     = $tb_order->getObj('id='.$order_id.$where);

 		//获取地区
 		$data['address'] = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']))."&nbsp;".$data['address'];
		$data['seller_id'] = $seller_id;

		$this->setRenderData($data);
		$this->redirect('pick_template');
	}
	//合并购物单和发货单
	public function merge_template()
	{
		$this->layout='print';
		$order_id = IFilter::act(IReq::get('id'),'int');
		$seller_id= IFilter::act( IReq::get('seller_id'),'int' );

		$type     = IFilter::act(IReq::get('type'));
		$tb_order =  new IModel('order');
		$where = $type ? ' and type=4' : ' and type !=4';
		$data     = $tb_order->getObj('id='.$order_id.$where);

		if($seller_id)
		{
			$sellerObj   = new IModel('seller');
			$config_info = $sellerObj->getObj('id = '.$seller_id);

	     	$data['set']['name']   = isset($config_info['true_name'])? $config_info['true_name'] : '';
	     	$data['set']['phone']  = isset($config_info['phone'])    ? $config_info['phone']     : '';
	     	$data['set']['email']  = isset($config_info['email'])    ? $config_info['email']     : '';
	     	$data['set']['url']    = isset($config_info['home_url']) ? $config_info['home_url']  : '';
		}
		else
		{
			$config = new Config("site_config");
			$config_info = $config->getInfo();

	     	$data['set']['name']   = isset($config_info['name'])  ? $config_info['name']  : '';
	     	$data['set']['phone']  = isset($config_info['phone']) ? $config_info['phone'] : '';
	     	$data['set']['email']  = isset($config_info['email']) ? $config_info['email'] : '';
	     	$data['set']['url']    = isset($config_info['url'])   ? $config_info['url']   : '';
		}

 		//获取地区
 		$data['address'] = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']))."&nbsp;".$data['address'];
		$data['seller_id'] = $seller_id;

		$this->setRenderData($data);
		$this->redirect("merge_template");
	}
	/**
	 * @brief 添加/修改发货信息
	 */
	public function ship_info_edit()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get("sid"),'int');
    	if($id)
    	{
    		$tb_ship   = new IModel("merch_ship_info");
    		$ship_info = $tb_ship->getObj("id=".$id." and seller_id = 0");
    		if($ship_info)
    		{
    			$this->data = $ship_info;
    		}
    		else
    		{
    			die('数据不存在');
    		}
    	}
    	$this->setRenderData($this->data);
		$this->redirect('ship_info_edit');
	}
	/**
	 * @brief 设置发货信息的默认值
	 */
	public function ship_info_default()
	{
		$id = IFilter::act( IReq::get('id'),'int' );
        $default = IFilter::string(IReq::get('default'));
        $tb_merch_ship_info = new IModel('merch_ship_info');
        if($default == 1)
        {
            $tb_merch_ship_info->setData(array('is_default'=>0));
            $tb_merch_ship_info->update("seller_id = 0");
        }
        $tb_merch_ship_info->setData(array('is_default'=>$default));
        $tb_merch_ship_info->update("id = ".$id." and seller_id = 0");
        $this->redirect('ship_info_list');
	}
	/**
	 * @brief 保存添加/修改发货信息
	 */
	public function ship_info_update()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('sid'),'int');
    	$ship_name = IFilter::act(IReq::get('ship_name'));
    	$ship_user_name = IFilter::act(IReq::get('ship_user_name'));
    	$sex = IFilter::act(IReq::get('sex'),'int');
    	$province =IFilter::act(IReq::get('province'),'int');
    	$city = IFilter::act(IReq::get('city'),'int');
    	$area = IFilter::act(IReq::get('area'),'int');
    	$address = IFilter::act(IReq::get('address'));
    	$postcode = IFilter::act(IReq::get('postcode'),'int');
    	$mobile = IFilter::act(IReq::get('mobile'));
    	$telphone = IFilter::act(IReq::get('telphone'));
    	$is_default = IFilter::act(IReq::get('is_default'),'int');

    	$tb_merch_ship_info = new IModel('merch_ship_info');

    	//判断是否已经有了一个默认地址
    	if(isset($is_default) && $is_default==1)
    	{
    		$tb_merch_ship_info->setData(array('is_default' => 0));
    		$tb_merch_ship_info->update('seller_id = 0');
    	}
    	//设置存储数据
    	$arr['ship_name'] = $ship_name;
	    $arr['ship_user_name'] = $ship_user_name;
	    $arr['sex'] = $sex;
    	$arr['province'] = $province;
    	$arr['city'] =$city;
    	$arr['area'] =$area;
    	$arr['address'] = $address;
    	$arr['postcode'] = $postcode;
    	$arr['mobile'] = $mobile;
    	$arr['telphone'] =$telphone;
    	$arr['is_default'] = $is_default;
    	$arr['is_del'] = 1;
    	$arr['seller_id'] = 0;

    	$tb_merch_ship_info->setData($arr);
    	//判断是添加还是修改
    	if($id)
    	{
    		$tb_merch_ship_info->update('id='.$id." and seller_id = 0");
    	}
    	else
    	{
    		$tb_merch_ship_info->add();
    	}
		$this->redirect('ship_info_list');
	}
	/**
	 * @brief 删除发货信息到回收站中
	 */
	public function ship_info_del()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('id'),'int');

		//加载 商家发货点信息
    	$tb_merch_ship_info = new IModel('merch_ship_info');
    	$tb_merch_ship_info->setData(array('is_del' => 0));
		if($id)
		{
			$tb_merch_ship_info->update(Util::joinStr($id)." and seller_id = 0");
			$this->redirect('ship_info_list');
		}
		else
		{
			$this->redirect('ship_info_list',false);
			Util::showMessage('请选择要删除的数据');
		}
	}
	/**
	 * @brief 还原回收站的信息到列表
	 */
	public function recycle_restore()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('id'),'int');
		//加载 商家发货点信息
    	$tb_merch_ship_info = new IModel('merch_ship_info');
    	$tb_merch_ship_info->setData(array('is_del' => 1));
		if($id)
		{
			$tb_merch_ship_info->update(Util::joinStr($id)." and seller_id = 0");
			$this->redirect('ship_recycle_list');
		}
		else
		{
			$this->redirect('ship_recycle_list',false);
		}
	}
	/**
	 * @brief 删除收货地址的信息
	 */
	public function recycle_del()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('id'),'int');
		//加载 商家发货点信息
    	$tb_merch_ship_info = new IModel('merch_ship_info');
		if($id)
		{
			$tb_merch_ship_info->del(Util::joinStr($id).' and seller_id = 0');
			$this->redirect('ship_recycle_list');
		}
		else
		{
			$this->redirect('ship_recycle_list',false);
			Util::showMessage('请选择要删除的数据');
		}
	}

	//快递单背景图片上传
	public function expresswaybill_upload()
	{
		$result = array(
			'isError' => true,
		);

		if(isset($_FILES['attach']['name']) && $_FILES['attach']['name'] != '')
		{
			$photoObj = new PhotoUpload();
			$photo    = $photoObj->run();

			$result['isError'] = false;
			$result['data']    = $photo['attach']['img'];
		}
		else
		{
			$result['message'] = '请选择图片';
		}

		echo '<script type="text/javascript">parent.photoUpload_callback('.JSON::encode($result).');</script>';
	}

	//快递单添加修改
	public function expresswaybill_edit()
	{
		$id = intval(IReq::get('id'));

		$this->expressRow = array();

		//修改模式
		if($id)
		{
			$expressObj       = new IModel('expresswaybill');
			$this->expressRow = $expressObj->getObj('id = '.$id);
		}

		$this->redirect('expresswaybill_edit');
	}

	//快递单添加修改动作
	public function expresswaybill_edit_act()
	{
		$id           = intval(IReq::get('id'));
		$printExpress = IReq::get('printExpress');
		$name         = IFilter::act(IReq::get('express_name'));
		$width        = intval(IReq::get('width'));
		$height       = intval(IReq::get('height'));
		$background   = IFilter::act(IReq::get('printBackground'));
		$background   = ltrim($background,IUrl::creatUrl(''));

		if(!$printExpress)
		{
			$printExpress = array();
		}

		if(!$name)
		{
			die('快递单的名称不能为空');
		}

		$expressObj     = new IModel('expresswaybill');

		$data = array(
			'config'     => serialize($printExpress),
			'name'       => $name,
			'width'      => $width,
			'height'     => $height,
			'background' => $background,
		);

		$expressObj->setData($data);

		//修改模式
		if($id)
		{
			$is_result = $expressObj->update('id = '.$id);
		}
		else
		{
			$is_result = $expressObj->add();
		}
		echo $is_result === false ? '操作失败' : 'success';
	}

	//快递单删除
	public function expresswaybill_del()
	{
		$id = intval(IReq::get('id'));
		$expressObj = new IModel('expresswaybill');
		$expressObj->del('id = '.$id);
		$this->redirect('print_template/tab_index/3');
	}

	//选择快递单打印类型
	public function expresswaybill_template()
	{
		$this->layout = 'print';
    	$data = array();
		$type = IFilter::act(IReq::get('type'));
    	//获得order_id的值
		$order_id = IFilter::act(IReq::get('id'),'int');
		$order_id = is_array($order_id) ? join(',',$order_id) : $order_id;

		if(!$order_id)
		{
			$this->redirect('order_list');
			exit;
		}

		$ord_class       = new Order_Class();
 		$this->orderInfo = $ord_class->getOrderInfo($order_id,$type);

		$this->redirect('expresswaybill_template');
	}

	//打印快递单
	public function expresswaybill_print()
	{
		$config_conver = array();
		$this->layout  = 'print';

		$order_id     = IFilter::act(IReq::get('order_id'));
		$seller_id    = IFilter::act(IReq::get('seller_id'),'int');
		$express_id   = intval(IReq::get('express_id'));
		$expressObj   = new IModel('expresswaybill');
		$expressRow   = $expressObj->getObj('id = '.$express_id);

		if(empty($expressRow))
		{
			die('不存在此快递单信息');
		}

		$expressConfig     = unserialize($expressRow['config']);
		$expresswaybillObj = new Expresswaybill();

		$config_conver       = $expresswaybillObj->conver($expressConfig,$order_id,$seller_id);
		$this->config_conver = str_replace('trackingLeft','letterSpacing',$config_conver);

		$this->order_id      = $order_id;
		$this->expressRow    = $expressRow;
		$this->redirect('expresswaybill_print');
	}

	//订单导出excel 参考订单列表
	public function order_report()
	{
		//搜索条件
		$search = IFilter::act(IReq::get('search'),'strict');
		$ids = IFilter::act(IReq::get('ids'));
		//条件筛选处理
		if($ids){
			list($join,$where) = order_class::getSearchCondition();
			$idArr = explode(',',$ids);
			$ids = implode(',',$idArr);
			$where = 'o.id in ('.$ids.')';
		}else{
			list($join,$where) =order_class::getSearchCondition($search);
		}
		//拼接sql
		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,d.name as distribute_name,u.username,p.name as payment_name";
		$orderHandle->join   = $join;
		$orderHandle->where  = $where;
		$orderList = $orderHandle->find();

		$strTable ='<table width="500" border="1">';
		$strTable .= '<tr>';
		$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品信息</td>';
		$strTable .= '</tr>';

		foreach($orderList as $k=>$val){
			$strTable .= '<tr>';
			$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['order_no'].'</td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['accept_name'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">&nbsp;'.$val['telphone'].'&nbsp;'.$val['mobile'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['payable_amount'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['real_amount'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['payment_name'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.Order_Class::getOrderPayStatusText($val).' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.Order_Class::getOrderDistributionStatusText($val).' </td>';

			$orderGoods = Order_class::getOrderGoods($val['id']);
			$strGoods="";
			foreach($orderGoods as $good){
				$strGoods .= "商品编号：".$good->goodsno." 商品名称：".$good->name;
				if ($good->value!='') $strGoods .= " 规格：".$good->value;
				$strGoods .= "<br />";
			}
			unset($orderGoods);

			$strTable .= '<td style="text-align:left;font-size:12px;">'.$strGoods.' </td>';
			$strTable .= '</tr>';
		}
		$strTable .='</table>';
		unset($orderList);
		$reportObj = new report();
		$reportObj->setFileName('order');
		$reportObj->toDownload($strTable);
		exit();
	}
	
	/**
	 * 展示发票详情
	 *
	 */
	public function fapiao_show(){
		$id = IFilter::act(IReq::get('id'),'int');
		$db_fa = new IQuery('order_fapiao as f');
		$db_fa->join = 'left join order as o on o.id = f.order_id left join seller as s on f.seller_id = s.id left join user as u on u.id = f.user_id';
		$db_fa->where = 'f.id ='. $id;
		$db_fa->limit = 1;
		$db_fa->fields = 's.true_name,u.username,o.order_no,o.real_amount,f.*';
		$data = $db_fa->find();
		$data = $data[0];
		if(!$data['true_name']){
			$config = new config('site_config');
			$data['true_name'] = $config->name;
		}
		if($data['money']==0)$data['money']=$data['real_amount'];
		
		$this->fapiao =$data;
		$this->redirect('fapiao_show');
		
		
		
	}
	/**
	 * 开票处理
	 */
	public function fapiao_show_save(){
		$id = IFilter::act(IReq::get('id'),'int');
		$seller_id = 0;
		$money = IFilter::act(IReq::get('money'),'float');
		if(!$id || !$money){
			$this->redirect('fapiao');
		}
		$db_fa = new IModel('order_fapiao');
		$data=array(
			'money'=>$money,
			'status'=>1
		);
		$db_fa->setData($data);
		$db_fa->update('id='.$id);
		$this->redirect('fapiao');
	}
	public function ship_info_list(){
		$this->redirect('ship_info_list');
	}
	
	//发票删除
	public function fapiao_del(){
		$id = IFilter::act(IReq::get('id'),'int');
		$action = IFilter::act(IReq::get('toaction'));
		if(is_array($id) && !empty($id)){
			$fapiao_db = new IModel('order_fapiao');
			$fapiao_db ->del('id in ('.implode(',',$id).')');
		}
		$this->redirect($action);
	}
}