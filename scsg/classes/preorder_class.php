<?php
class Preorder_Class extends Order_Class{
	
	/**
	 * @brief 获取预售订单扩展数据资料
	 * @param $order_id int 订单的id
	 * @param $user_id int 用户id
	 * @return array()
	 */
	public function getOrderShow($order_id,$user_id = 0)
	{
		$where = 'id = '.$order_id;
		if($user_id !== 0)
		{
			$where .= ' and user_id = '.$user_id;
		}
	
		$data = array();
	
		//获得对象
		$tb_order = new IModel('order');
		$data = $tb_order->getObj($where);
		if($data)
		{
			$data['order_id'] = $order_id;
	
			//获取配送方式
			$tb_delivery = new IModel('delivery');
			$delivery_info = $tb_delivery->getObj('id='.$data['distribution']);
			if($delivery_info)
			{
				$data['delivery'] = $delivery_info['name'];
	
				//自提点读取
				if($data['takeself'])
				{
					$data['takeself'] = self::getTakeselfInfo($data['takeself']);
				}
			}
	
			$areaData = area::name($data['province'],$data['city'],$data['area']);
			$data['province_str'] = $areaData[$data['province']];
			$data['city_str']     = $areaData[$data['city']];
			$data['area_str']     = $areaData[$data['area']];
	
			//物流单号
			$tb_delivery_doc = new IQuery('delivery_doc as dd');
			$tb_delivery_doc->join   = 'left join freight_company as fc on dd.freight_id = fc.id';
			$tb_delivery_doc->fields = 'dd.delivery_code,fc.freight_name';
			$tb_delivery_doc->where  = 'order_id = '.$order_id;
			$delivery_info = $tb_delivery_doc->find();
			if($delivery_info)
			{
				$temp = array('freight_name' => array(),'delivery_code' => array());
				foreach($delivery_info as $key => $val)
				{
					$temp['freight_name'][]  = $val['freight_name'];
					$temp['delivery_code'][] = $val['delivery_code'];
				}
				$data['freight']['freight_name']  = join(",",$temp['freight_name']);
				$data['freight']['delivery_code'] = join(",",$temp['delivery_code']);
			}
	
			//获取支付方式
			$tb_payment = new IModel('payment');
			$payment_info = $tb_payment->getObj('id='.$data['pay_type']);
			if($payment_info)
			{
				$data['payment'] = $payment_info['name'];
				$data['paynote'] = $payment_info['note'];
			}
	
			//获取商品总重量和总金额
			$tb_order_goods = new IModel('order_goods');
			$order_goods_info = $tb_order_goods->query('order_id='.$order_id);
			$data['goods_amount'] = 0;
			$data['goods_weight'] = 0;
	
			if($order_goods_info)
			{
				foreach ($order_goods_info as $value)
				{
					$data['goods_amount'] += $value['real_price']   * $value['goods_nums'];
					$data['goods_weight'] += $value['goods_weight'] * $value['goods_nums'];
				}
			}
	
			//获取用户信息
			$query = new IQuery('user as u');
			$query->join = ' left join member as m on u.id=m.user_id ';
			$query->fields = 'u.username,u.email,m.mobile,m.contact_addr,m.true_name';
			$query->where = 'u.id='.$data['user_id'];
			$user_info = $query->find();
			if($user_info)
			{
				$user_info = current($user_info);
				$data['username']     = $user_info['username'];
				$data['email']        = $user_info['email'];
				$data['u_mobile']     = $user_info['mobile'];
				$data['contact_addr'] = $user_info['contact_addr'];
				$data['true_name']    = $user_info['true_name'];
			}
		}
		return $data;
	}
	/**
	 * @brief 订单退款操作
	 * @param int $refundId 退款单ID
	 * @param int $authorId 操作人ID
	 * @param string $type admin:管理员;seller:商家
	 * @return
	 */
	public static function refund($refundId,$authorId,$type = 'admin')
	{
		$orderGoodsDB= new IModel('order_goods');
		$refundDB    = new IModel('refundment_doc');
		$orderDB     = new IModel('order');
	
		
		//获取goods_id和product_id用于给用户减积分，经验
		$refundsRow = $refundDB->getObj('id = '.$refundId);
		$order_id   = $refundsRow['order_id'];
		$order_no   = $refundsRow['order_no'];
		$amount     = $refundsRow['amount'];
		$user_id    = $refundsRow['user_id'];
	
		//获取支付方式
		$pay_type = $orderDB->getField('id='.$order_id,'pay_type');
		if($pay_type==1){//货到付款，余额支付退款到余额
			$obj = new IModel('member');
			$isSuccess = $obj->addNum('user_id = '.$user_id,array('balance'=>$amount));
			if($isSuccess)
			{
				//用户余额进行的操作记入account_log表
				$log = new AccountLog();
				$config = array(
						'user_id'  => $user_id,
						'event'    => 'drawback', //withdraw:提现,pay:余额支付,recharge:充值,drawback:退款到余额
						'num'      => $amount, //整形或者浮点，正为增加，负为减少
						'order_no' => $order_no // drawback类型的log需要这个值
				);
					
				if($type == 'admin')
				{
					$config['admin_id'] = $authorId;
				}
				else if($type == 'seller')
				{
					$config['seller_id'] = $authorId;
				}
					
				$re = $log->write($config);
					
			}else return false;
		}
		else if(in_array($pay_type,array(3))){
			$paymentInstance = Payment::createPaymentInstance($pay_type);
			$paymentData = Payment::getPaymentInfoForPresellRefund($pay_type,$refundId,$order_id,$amount);
			
			foreach($paymentData as $key=>$val){
				if(!$res=$paymentInstance->refund($val)) return false;//验签失败
			}
			
			
		}
		//更新退款表
		$updateData = array(
				'pay_status'   => 2,
				'dispose_time' => ITime::getDateTime(),
		);
		$refundDB->setData($updateData);
		$refundDB->update('id = '.$refundId);
		
		$orderGoodsRow = $orderGoodsDB->getObj('order_id = '.$order_id.' and goods_id = '.$refundsRow['goods_id'].' and product_id = '.$refundsRow['product_id']);
	
		$order_goods_id = $orderGoodsRow['id'];
	
		
		//更新退款状态，改为已退货
		$orderGoodsDB->setData(array('is_send' => 2));
		$orderGoodsDB->update('id = '.$order_goods_id);
		//更新order表状态
		$orderStatus = 10;//全部退款
		
		$orderDB->setData(array('status' => $orderStatus));
		$orderDB->update('id='.$order_id);
	
	
		//生成订单日志
		$authorName = $type == 'admin' ? ISafe::get('admin_name') : ISafe::get('seller_name');
		if($type=='system')$authorName='系统自动';
		$tb_order_log = new IModel('order_log');
		$tb_order_log->setData(array(
				'order_id' => $order_id,
				'user'     => $authorName,
				'action'   => '退款',
				'result'   => '成功',
				'note'     => '订单【'.$order_no.'】退款，退款金额：￥'.$amount,
				'addtime'  => ITime::getDateTime(),
				'order_type'=> 1,
		));
		$tb_order_log->add();
		return true;
	}
	
	/**
	 * 退款操作
	 * @$refunds_id int 退款单id
	 * @$ordergoods_id  int 订单商品id
	 * @$updateData 退款单数据
	 */
	public static function refundHandle($refunds_id,$ordergoods_id,$updateData){
		//无退款申请单，必须生成退款单
		if(!$refunds_id)
		{
			$orderDB = new IModel('order');
			$orderGoodsDB      = new IModel('order_goods');
			$tb_refundment_doc = new IModel('refundment_doc');
			if(!$ordergoods_id)return false;
			$orderGoodsRow = $orderGoodsDB->getObj('id = '.$ordergoods_id);
	
			//插入refundment_doc表
			$updateData['time']       = ITime::getDateTime();
			$updateData['goods_id']   = $orderGoodsRow['goods_id'];
			$updateData['product_id'] = $orderGoodsRow['product_id'];
	
			$goodsDB = new IModel('goods');
			$goodsRow= $goodsDB->getObj('id = '.$orderGoodsRow['goods_id']);
			$updateData['seller_id'] = $goodsRow['seller_id'];
			
			$order_amount = $orderDB->getField('id='.$updateData['order_id'],'order_amount');
			if($updateData['amount']>$order_amount)die('<script text="text/javascript">parent.actionCallback("退款金额不能大于实际支付金额");</script>');
			
			$tb_refundment_doc->setData($updateData);
			$refunds_id = $tb_refundment_doc->add();
			$tb_refundment_doc->commit();
		}
		
		$result = self::refund($refunds_id,$updateData['admin_id'],'admin');
	
	
		if($result)
		{
			//记录操作日志
			$logObj = new log('db');
			if($updateData['admin_id']){
				$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"订单更新为退款",'订单号：'.$updateData['order_no']));
					
			}else
				$logObj->write('operation',array("系统自动:","订单更新为退款",'订单号：'.$updateData['order_no']));
	
			return true;
		}
		else
		{
			return false;
		}
	}
	//获取订单状态
	public static function getOrderStatus($orderRow){
		switch($orderRow['status']){
			case 1 : {
				return '提交订单';
			}
			case 2 : {
				return '订单取消';
				
			}
			case 3 : {
				return '等待后台确认';
			}
			case 4 : {
				return '订单确认';
			}
			case 5 : {
				return '后台未确认，作废';
			}
			case 6 : {
				return '后台确认未通过，作废';
				
			}
			case 7 : {
				return '已支付尾款';
				
			}
			case 8 : {
				return '超期未付尾款';
				
			}
			case 9 : {
				return '已发货';
			}
			case 10 : {
				return '已退款';
			}
			case 11 : {
				return '已完成';
			}
		}
		return '未知';
	}
	//获取发货状态
	public static function getOrderDistributionStatusText($orderRow){
		
		 if($orderRow['distribution_status'] == 1)
		{
			return '已发货';
		}
		else if($orderRow['distribution_status'] == 0)
		{
			return '未发货';
		}
		
	}
	//获取订单支付状态
	public static function getOrderPayStatusText($orderRow)
	{
		if($orderRow['pay_status'] == 0)
		{
			return '未付款';
		}
		else if($orderRow['pay_status'] == 1)
		{
			return '支付预付款';
		}
		else if($orderRow['pay_status'] == 2){
			return '支付尾款';
		}
		return '未知';
	}
	/**
	 * 支付成功后修改订单状态
	 * @param $orderNo  string 订单编号
	 * @param $admin_id int    管理员ID
	 * @param $note     string 收款的备注
	 * @return false or int order_id
	 */
	public static function updateOrderStatus($orderNo,$admin_id = '',$note = '')
	{
		if(stripos($orderNo,'pre') !== false)
		{
			$orderNo = str_replace('pre','',$orderNo);
			
		}else if(stripos($orderNo,'wei') !== false){
			
			$orderNo = str_replace('wei','',$orderNo);
			
		}
		
		
		//获取订单信息
		$orderObj  = new IModel('order');
		$orderRow  = $orderObj->getObj('order_no = "'.$orderNo.'"');
		
		
		if(empty($orderRow))
		{
			return false;
		}
		if($orderRow['pay_status']==0){
			$type    = 1;
			$dataArray = array(
					'status'     => 3,
					'pay_time'   => ITime::getDateTime(),
					'pay_status' => 1
			);
		}else if($orderRow['pay_status']==1){
			$type    = 0;
			$dataArray = array(
					'status'     => 7,
					'pay_time2'   => ITime::getDateTime(),
					'pay_status' => 2
			);
		}
	
		if($orderRow['pay_status'] == 2)
		{
			return $orderRow['id'];
		}
		if($type==1 && $orderRow['pay_status'] == 1)
		{
			return $orderRow['id'];
		}
		
			$orderObj->setData($dataArray);
			$is_success = $orderObj->update('order_no = "'.$orderNo.'"');
			if($is_success == '')
			{
				return false;
			}
	
			//删除订单中使用的道具
			$ticket_id = trim($orderRow['prop']);
			if($ticket_id != '')
			{
				$propObj  = new IModel('prop');
				$propData = array('is_userd' => 1);
				$propObj->setData($propData);
				$propObj->update('id = '.$ticket_id);
			}
	
			if(intval($orderRow['user_id']) != 0)
			{
				$user_id = $orderRow['user_id'];
	
				//获取用户信息
				$memberObj  = new IModel('member');
				$memberRow  = $memberObj->getObj('user_id = '.$user_id,'prop,group_id');
	
				//(1)删除订单中使用的道具
				if($ticket_id != '')
				{
					$finnalTicket = str_replace(','.$ticket_id.',',',',','.trim($memberRow['prop'],',').',');
					$memberData   = array('prop' => $finnalTicket);
					$memberObj->setData($memberData);
					$memberObj->update('user_id = '.$user_id);
				}
	
				
			}
	
			//插入收款单
			$collectionDocObj = new IModel('collection_doc');
			$collectionData   = array(
					'order_id'   => $orderRow['id'],
					'user_id'    => $orderRow['user_id'],
					'time'       => ITime::getDateTime(),
					'payment_id' => $orderRow['pay_type'],
					'if_del'     => 0,
					'note'       => $note,
					'admin_id'   => $admin_id ? $admin_id : 0
			);
			if($type ==1){
				$collectionData['amount'] = $orderRow['pre_amount'];
				$collectionData['pay_status'] = 1;
			}else{
				$collectionData['amount'] = $orderRow['order_amount'] - $orderRow['pre_amount'];
				$collectionData['pay_status'] = 1;
			}
			$collectionDocObj->setData($collectionData);
			$collectionDocObj->add();
	
			return $orderRow['id'];
	
		
	}
	/**
	 * @breif 订单的流向
	 * @param $orderRow array 订单数据
	 * @return array('时间' => '事件')
	 */
	public static function orderStep($orderRow)
	{
		$result = array();
	
		//1,创建订单
		$result[$orderRow['create_time']] = '订单创建';
	
		//2,订单支付
		if($orderRow['pay_time'])
		{
			$result[$orderRow['pay_time']] = '支付预付款  '.$orderRow['pre_amount'];
		}
	
		
		if($orderRow['confirm_time'])
		{
			$result[$orderRow['confirm_time']] = '管理员确认订单';
		}
	
		//4,订单完成
		if($orderRow['pay_time2'])
		{
			$result[$orderRow['pay_time2']] = '支付尾款   '.(number_format($orderRow['order_amount']-$orderRow['pre_amount'],2));
		}
		if($orderRow['send_time'])
		{
			$result[$orderRow['send_time']] = '订单发货完成';
		}
		if($orderRow['completion_time'])
		{
			$result[$orderRow['completion_time']] = '订单完成';
		}
		ksort($result);
		return $result;
	}
	

	
	/**
	 * @brief 商品发货接口
	 * @param string $order_id 订单id
	 * @param array $order_goods_relation 订单与商品关联id
	 * @param int $sendor_id 操作者id
	 * @param string $sendor 操作者所属 admin,seller
	 */
	public static function sendDeliveryGoods($order_id,$order_goods_relation,$sendor_id,$sendor = 'admin')
	{
		$order_no = IFilter::act(IReq::get('order_no'));
	
		$paramArray = array(
				'order_id'      => $order_id,
				'user_id'       => IFilter::act(IReq::get('user_id'),'int'),
				'name'          => IFilter::act(IReq::get('name')),
				'postcode'      => IFilter::act(IReq::get('postcode'),'int'),
				'telphone'      => IFilter::act(IReq::get('telphone')),
				'province'      => IFilter::act(IReq::get('province'),'int'),
				'city'          => IFilter::act(IReq::get('city'),'int'),
				'area'          => IFilter::act(IReq::get('area'),'int'),
				'address'       => IFilter::act(IReq::get('address')),
				'mobile'        => IFilter::act(IReq::get('mobile')),
				'freight'       => IFilter::act(IReq::get('freight'),'float'),
				'delivery_code' => IFilter::act(IReq::get('delivery_code')),
				'delivery_type' => IFilter::act(IReq::get('delivery_type')),
				'note'          => IFilter::act(IReq::get('note'),'text'),
				'time'          => ITime::getDateTime(),
				'freight_id'    => IFilter::act(IReq::get('freight_id'),'int'),
		);
		switch($sendor)
		{
			case "admin":
				{
					$paramArray['admin_id'] = $sendor_id;
	
					$adminDB = new IModel('admin');
					$sendorData = $adminDB->getObj('id = '.$sendor_id);
					$sendorName = $sendorData['admin_name'];
					$sendorSort = '管理员';
				}
				break;
	
			case "seller":
				{
					$paramArray['seller_id'] = $sendor_id;
	
					$sellerDB = new IModel('seller');
					$sendorData = $sellerDB->getObj('id = '.$sendor_id);
					$sendorName = $sendorData['true_name'];
					$sendorSort = '加盟商户';
				}
				break;
		}
	
		//获得delivery_doc表的对象
		$tb_delivery_doc = new IModel('delivery_doc');
		$tb_delivery_doc->setData($paramArray);
		$deliveryId = $tb_delivery_doc->add();
		 
		//订单对象
		$tb_order   = new IModel('order');
		$tbOrderRow = $tb_order->getObj('id = '.$order_id);
	
		//如果支付方式为货到付款，则减少库存
		if($tbOrderRow['pay_type'] == 0)
		{
			//减少库存量
			self::updateStore($order_goods_relation,'reduce');
		}
	
		//更新发货状态
		$orderGoodsDB = new IModel('order_goods');
		$orderGoodsRow = $orderGoodsDB->getObj('is_send = 0 and order_id = '.$order_id,'count(*) as num');
		$sendStatus = 2;
		if(count($order_goods_relation) >= $orderGoodsRow['num'])
		{
			$sendStatus = 1;//全部发货
		}
		foreach($order_goods_relation as $key => $val)
		{
			//商家发货检查商品所有权
			if(isset($paramArray['seller_id']))
			{
				$orderGoodsData = $orderGoodsDB->getObj("id = ".$val);
				$goodsDB = new IModel('goods');
				$sellerResult = $goodsDB->getObj("id = ".$orderGoodsData['goods_id']." and seller_id = ".$paramArray['seller_id']);
				if(!$sellerResult)
				{
					$goodsDB->rollback();
					die('发货的商品信息与商家不符合');
				}
			}
	
			$orderGoodsDB->setData(array(
					"is_send"     => 1,
					"delivery_id" => $deliveryId,
			));
			$orderGoodsDB->update(" id = {$val} ");
		}
	
		//更新发货状态
		$tb_order->setData(array
		 	(
		 			'distribution_status' => $sendStatus,
		 			'send_time'           => ITime::getDateTime(),
		 			'status'              => 9
		 	));
		$tb_order->update('id='.$order_id);
	
		//生成订单日志
		$tb_order_log = new IModel('order_log');
		$tb_order_log->setData(array(
				'order_id' => $order_id,
				'user'     => $sendorName,
				'action'   => '发货',
				'result'   => '成功',
				'note'     => '订单【'.$order_no.'】由【'.$sendorSort.'】'.$sendorName.'发货',
				'addtime'  => date('Y-m-d H:i:s'),
				'order_type'=> 1,
		));
		$sendResult = $tb_order_log->add();
	
		//获取货运公司
		$freightDB  = new IModel('freight_company');
		$freightRow = $freightDB->getObj('id = '.$paramArray['freight_id']);
	
		//发送短信
		$replaceData = array(
				'{user_name}'        => $paramArray['name'],
				'{order_no}'         => $order_no,
				'{sendor}'           => '['.$sendorSort.']'.$sendorName,
				'{delivery_company}' => $freightRow['freight_name'],
				'{delivery_no}'      => $paramArray['delivery_code'],
		);
		$mobileMsg = smsTemplate::sendGoods($replaceData);
		Hsms::send($paramArray['mobile'],$mobileMsg);
		 
		//同步发货接口，如支付宝担保交易等
		if($sendResult && $sendStatus == 1 && $tbOrderRow['pay_type'] == 7)
		{
			sendgoods::run_presell($paramArray);
		}
	}
	//去除预售订单前面 pre 、wei
	public static function getTrueOrderNo($orderNo){
		if(stripos($orderNo,'pre') !== false)
		{
			$orderNo = str_replace('pre','',$orderNo);
		}
		if(stripos($orderNo,'wei') !== false)
		{
			$orderNo = str_replace('wei','',$orderNo);
		}
		return $orderNo;
	}
	/**
	 * 计算预售订单是否可支付
	 * @array $orderRow 订单信息
	 * @return 返回支付信息
	 */
	public static function get_pay_money($orderRow){
		$siteConfigObj = new Config('site_config');
		$cancel_days = $siteConfigObj->preorder_cancel_days;
		$return = array();
		if($orderRow['status']==1 && order_class::is_overdue($orderRow['create_time'],$cancel_days)){
			$return['M_Amount']    = $orderRow['pre_amount'];
			$return['M_OrderNO']   = 'pre'.$orderRow['order_no'];
		}elseif($orderRow['status']==4 ){
			if($orderRow['wei_type']==1){
				if(time()<strtotime($orderRow['wei_start_time']))
					IError::show(403,'预售订单未到支付时间，不能支付');
				if(time()>strtotime($orderRow['wei_end_time']))
					IError::show(403,'预售订单超期未支付尾款，订单已作废');
			}else{
				if(!preorder_class::is_overdue($orderRow['pay_time'],$orderRow['wei_days'])){
					IError::show(403,'预售订单超期未支付尾款，订单已作废');
				}
					
			}
			
			$return['M_Amount']    = $orderRow['order_amount'] - $orderRow['pre_amount'];
			$return['M_OrderNO']   = 'wei'.$orderRow['order_no'];
		}
		else{
			IError::show(403,'订单已过期，不能进行支付');
		}
		return $return;
	}
	/**
	 * 判断预售订单是否可支付
	 * @return 
	 */
	public static function can_pay($orderRow){
		$siteConfigObj = new Config('site_config');
		$cancel_days = $siteConfigObj->preorder_cancel_days;
		$return = array();
		$presell_db = new IModel('presell');
		$presell_row = $presell_db->getObj('id='.$orderRow['active_id'],'wei_type,wei_start_time,wei_end_time,wei_days');
		if(!$presell_row)return false;
		$orderRow = array_merge($presell_row,$orderRow);
		if($orderRow['status']==1 ){
			return true;
		}elseif($orderRow['status']==4 ){
			if($orderRow['wei_type']==1){
				if(time()>strtotime($orderRow['wei_start_time']) && time()<strtotime($orderRow['wei_end_time']))
					return true;
			}else{
				if(preorder_class::is_overdue($orderRow['pay_time'],$orderRow['wei_days']))
					return true;
			}
		}
		
		return false;
	}
	
}