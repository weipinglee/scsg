<?php
/**
 * @file Order_Class.php
 * @brief 订单中相关的
 */
class Order_Class
{
	/**
	 * @brief 产生订单ID
	 * @return string 订单ID
	 */
	public static function createOrderNum()
	{
		return date('YmdHis').rand(100000,999999);
	}

	/**
	 * 添加评论商品的机会
	 * @param $order_id 订单ID
	 */
	public static function addGoodsCommentChange($order_id)
	{
		$goodsDB = new IModel('goods');
		//获取订单对象
		$orderDB  = new IModel('order');
		$orderRow = $orderDB->getObj('id = '.$order_id);

		//获取此订单中的商品种类
		$orderGoodsDB        = new IModel('order_goods');
		$where = 'order_id = '.$order_id.' and (is_send =1 OR is_send=2 and refunds_status = 10)';
	
		$orderList           = $orderGoodsDB->query($where);

		//可以允许进行商品评论
		$commentDB = new IModel('comment');

		//对每类商品进行评论开启
		foreach($orderList as $val)
		{
			if($val['comment_id']==-1)continue;
			if(!$goodsDB->getObj('id='.$val['goods_id'],'id'))continue;
			$attr = array(
				'og_id'    => $val['id'],
				'goods_id' => $val['goods_id'],
				'order_no' => $orderRow['order_no'],
				'order_id' => $order_id,
				'user_id'  => $orderRow['user_id'],
				'time'     => date('Y-m-d H:i:s')
			);
			$commentDB->setData($attr);
			$id = $commentDB->add();
			$orderGoodsDB->setData(array('comment_id'=>$id));
			$orderGoodsDB->update('id='.$val['id']);
		}
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
		//获取订单信息
		$orderObj  = new IModel('order');
		$orderRow  = $orderObj->getObj('order_no = "'.$orderNo.'"');

		if(empty($orderRow))
		{
			return false;
		}

		if($orderRow['pay_status'] == 1)
		{
			return $orderRow['id'];
		}
		else if($orderRow['pay_status'] == 0)
		{
			$dataArray = array(
				'status'     => ($orderRow['status'] == 5) ? 5 : 2,
				'pay_time'   => ITime::getDateTime(),
				'pay_status' => 1
			);

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
				'amount'     => $orderRow['order_amount'],
				'time'       => ITime::getDateTime(),
				'payment_id' => $orderRow['pay_type'],
				'pay_status' => 1,
				'if_del'     => 0,
				'note'       => $note,
				'admin_id'   => $admin_id ? $admin_id : 0
			);

			$collectionDocObj->setData($collectionData);
			$collectionDocObj->add();

			//促销活动订单
			if($orderRow['type'] != 0)
			{
				Active::payCallback($orderNo,$orderRow['type']);
			}

			//非货到付款的支付方式
			if($orderRow['pay_type'] != 0)
			{
				//减少库存量
				$orderGoodsDB = new IModel('order_goods');
				$orderGoodsList = $orderGoodsDB->query('order_id = '.$orderRow['id']);
				$orderGoodsListId = array();
				foreach($orderGoodsList as $key => $val)
				{
					$orderGoodsListId[] = $val['id'];
				}
				self::updateStore($orderGoodsListId,'reduce');
			}

			//自提点短信发送
			self::sendTakeself($orderNo);

			//订单付款后短信通知管理员进行订单处理
			$config = new Config('site_config');
			if(isset($config->mobile) && $config->mobile)
			{
				$smsContent = smsTemplate::payFinishToAdmin(array('{orderNo}' => $orderNo));
				Hsms::send($config->mobile,$smsContent);
			}
			return $orderRow['id'];
		}
		else
		{
			return false;
		}
	}

	/**
	 * @brief 自提点短信发送
	 * @param string $orderNo 订单编号
	 */
	public static function sendTakeself($orderNo)
	{
		//获取订单信息
		$orderObj  = new IModel('order');
		$orderRow  = $orderObj->getObj('order_no = "'.$orderNo.'"');

		if(empty($orderRow))
		{
			return false;
		}

		//自提方式短信验证提醒
		if($orderRow['takeself'] > 0)
		{
			$takeselfObj = new IModel('takeself');
			$takeselfRow = $takeselfObj->getObj('id = '.$orderRow['takeself']);
			if($takeselfRow)
			{
				$mobile_code = rand(100000,999999);
				$orderObj->setData(array('checkcode' => $mobile_code));
				$checkResult = $orderObj->update('id = '.$orderRow['id']);
				if($checkResult)
				{
					$smsContent = smsTemplate::takeself(array('{orderNo}' => $orderRow['order_no'],'{address}' => $takeselfRow['address'],'{mobile_code}' => $mobile_code,'{phone}' => $takeselfRow['phone'],'{name}' => $takeselfRow['name']));
					Hsms::send($orderRow['mobile'],$smsContent);
				}
			}
		}
		//普通付款通知
		else
		{
			$smsContent = smsTemplate::payFinishToUser(array('{orderNo}' => $orderNo));
			Hsms::send($orderRow['mobile'],$smsContent);
		}
	}

	/**
	 * 订单商品数量更新操作[公共]
	 * @param array $orderGoodsId ID数据
	 * @param string $type 增加或者减少 add 或者 reduce
	 */
	public static function updateStore($orderGoodsId,$type = 'add')
	{
		$newStoreNums  = 0;
		$updateGoodsId = array();
		$orderGoodsObj = new IModel('order_goods');
		$goodsObj      = new IModel('goods');
		$productObj    = new IModel('products');
		if(!is_array($orderGoodsId))$orderGoodsId = array($orderGoodsId);
		$goodsList     = $orderGoodsObj->query('id in('.join(",",$orderGoodsId).') and is_send = 0','goods_id,product_id,goods_nums');

		foreach($goodsList as $key => $val)
		{
			//货品库存更新
			if($val['product_id'] != 0)
			{
				$productsRow = $productObj->getObj('id = '.$val['product_id'],'store_nums');
				$localStoreNums = $productsRow['store_nums'];

				//同步更新所属商品的库存量
				if(!isset($updateGoodsId[$val['goods_id']])){$updateGoodsId[$val['goods_id']] = 0;}
				$updateGoodsId[$val['goods_id']] += $val['goods_nums'];
				

				$newStoreNums = ($type == 'add') ? $localStoreNums + $val['goods_nums'] : $localStoreNums - $val['goods_nums'];
				$newStoreNums = $newStoreNums > 0 ? $newStoreNums : 0;

				$productObj->setData(array('store_nums' => $newStoreNums));
				$productObj->update('id = '.$val['product_id'],'store_nums');
				
			}
		}
		foreach($goodsList as $key=>$val)
		{
			
			if($val['product_id'] == 0){
				
				if(!isset($updateGoodsId[$val['goods_id']]))$updateGoodsId[$val['goods_id']] = 0;
				$updateGoodsId[$val['goods_id']] += $val['goods_nums'];
				
			}
		}
		//更新统计goods的库存
		if($updateGoodsId)
		{
			$table = $goodsObj->getTableName();
			foreach($updateGoodsId as $key=>$val)
			{
				if($type=='add'){
					$sql = ' UPDATE '.$table.' set `store_nums` = `store_nums` +  '.$val.', sale = sale - '.$val.' where id = '.$key;
				}else{
					$sql = ' UPDATE '.$table.' set `store_nums` = `store_nums` -  '.$val.', sale = sale + '.$val.' where id = '.$key;
				}
				$goodsObj->db_query($sql);
				
			}
		}
	}

	/**
	 * @brief 获取订单扩展数据资料
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
	 * 获取自提点基本信息
	 * @param $id int 自提点id
	 */
	public static function getTakeselfInfo($id)
	{
		$takeselfObj = new IModel('takeself');
		$takeselfRow = $takeselfObj->getObj('id = '.$id);

		$temp = area::name($takeselfRow['province'],$takeselfRow['city'],$takeselfRow['area']);
		$takeselfRow['province_str'] = $temp[$takeselfRow['province']];
		$takeselfRow['city_str']     = $temp[$takeselfRow['city']];
		$takeselfRow['area_str']     = $temp[$takeselfRow['area']];
		return $takeselfRow;
	}

	/**
	 * 获取订单基本信息
	 * @param $orderIdString string 订单ID序列
	 * @param $type string 订单类型 预售时有效
	 */
	public function getOrderInfo($orderIdString,$type)
	{
		$orderObj    = $type ? new IModel('order_presell') : new IModel('order');
		$areaIdArray = array();
		$orderList   = $orderObj->query('id in ('.$orderIdString.')');

		foreach($orderList as $key => $val)
		{
			$temp = area::name($val['province'],$val['city'],$val['area']);
			$orderList[$key]['province_str'] = $temp[$val['province']];
			$orderList[$key]['city_str']     = $temp[$val['city']];
			$orderList[$key]['area_str']     = $temp[$val['area']];
		}

		return $orderList;
	}

	/**
	 * @brief 把订单商品同步到order_goods表中
	 * @param $order_id 订单ID
	 * @param $goodsInfo 商品和货品信息（购物车数据结构,countSum 最终生成的格式）
	 */
	public function insertOrderGoods($order_id,$goodsResult = array())
	{
		$orderGoodsObj = new IModel('order_goods');

		//清理旧的关联数据
		$orderGoodsObj->del('order_id = '.$order_id);

		$goodsArray = array(
			'order_id' => $order_id
		);

		if(isset($goodsResult['goodsList']))
		{
			foreach($goodsResult['goodsList'] as $key => $val)
			{
				//拼接商品名称和规格数据
				$specArray = array('name' => $val['name'],'goodsno' => $val['goods_no'],'value' => '');

				if(isset($val['spec_array']))
				{
					$spec = block::show_spec($val['spec_array']);
					foreach($spec as $skey => $svalue)
					{
						$specArray['value'] .= $skey.':'.$svalue.',';
					}
					$specArray['value'] = trim($specArray['value'],',');
				}

				$goodsArray['product_id']  = $val['product_id'];
				$goodsArray['goods_id']    = $val['goods_id'];
				$goodsArray['img']         = $val['img'];
				$goodsArray['goods_price'] = $val['sell_price'];
				$goodsArray['real_price']  = $val['sell_price'] - $val['reduce'];
				$goodsArray['goods_nums']  = $val['count'];
				$goodsArray['goods_weight']= $val['weight'];
				$goodsArray['goods_array'] = IFilter::addSlash(JSON::encode($specArray));
				$goodsArray['delivery_fee']= $val['deliveryPrice'];
				$goodsArray['save_price']  = $val['insuredPrice'];
				$goodsArray['tax']         = $val['taxPrice'];
				$orderGoodsObj->setData($goodsArray);
				$orderGoodsObj->add();
			}
		}
	}
	/**
	 * @brief 把预售订单商品同步到order_goods_pre表中
	 * @param $order_id 订单ID
	 * @param $goodsInfo 商品和货品信息（购物车数据结构,countSum 最终生成的格式）
	 */
	public function insertOrderGoodsPresell($order_id,$goodsResult = array())
	{
		$orderGoodsObj = new IModel('order_goods_pre');
	
		//清理旧的关联数据
		$orderGoodsObj->del('order_id = '.$order_id);
	
		$goodsArray = array(
				'order_id' => $order_id
		);
	
		if(isset($goodsResult['goodsList']))
		{
			foreach($goodsResult['goodsList'] as $key => $val)
			{
				
	
				$goodsArray['product_id']  = $val['product_id'];
				$goodsArray['goods_id']    = $val['goods_id'];
				$goodsArray['img']         = $val['img'];
				$goodsArray['goods_nums']  = $val['count'];
				$orderGoodsObj->setData($goodsArray);
				$orderGoodsObj->add();
			}
		}
	}
	/**
	 * 生成order_goods表中的good_array字段
	 * @param arr $goodRow 商品数据
	 * @return 
	 */
	protected static function order_goods_spec($goodRow){
		$specArray = array('name' => $goodRow['name'],'goodsno' => $goodRow['goods_no'],'value' => '');
		
		if(isset($goodRow['spec_array']) && $goodRow['spec_array']!='')
		{
			$spec = block::show_spec($goodRow['spec_array']);
			foreach($spec as $skey => $svalue)
			{
				$specArray['value'] .= $skey.':'.$svalue.',';
			}
			$specArray['value'] = trim($specArray['value'],',');
		}
		return IFilter::addSlash(JSON::encode($specArray));
	}
	/**
	 * 获取订单状态
	 * @param $orderRow array('status' => '订单状态','pay_type' => '支付方式ID','distribution_status' => '配送状态','pay_status' => '支付状态')
	 * @return int 订单状态值 0:未知; 1:未付款等待发货(货到付款); 2:等待付款(线上支付); 3:已发货(已付款); 4:已付款等待发货; 5:已取消; 6:已完成(已付款,已收货); 7:已退款; 8:部分发货(不需要付款); 9:部分退款(未发货+部分发货); 10:部分退款(已发货); 11:已发货(未付款);
	 */
	public static function getOrderStatus($orderRow)
	{
		//print_r($orderRow);
		//1,刚生成订单,未付款
		if($orderRow['status'] == 1)
		{
			//选择货到付款
			if($orderRow['pay_type'] == 0)
			{
				if($orderRow['distribution_status'] == 0)
				{
					return 1;//等待发货
				}
				else if($orderRow['distribution_status'] == 1)
				{
					return 11;//已发货
				}
				else if($orderRow['distribution_status'] == 2)
				{
					return 8;//部分发货
				}
			}
			//选择在线支付
			else
			{
				return 2;//等待付款
			}
		}
		//2,已经付款
		else if($orderRow['status'] == 2)
		{
			if($orderRow['distribution_status'] == 7){
				return 20;
			}
			else if($orderRow['pay_status'] == 3){
				return 18;
			}
			else if( $orderRow['pay_status'] == 4){
				return 19;
			}
			elseif($orderRow['distribution_status'] == 0)
			{
				return 4;//已付款等待发货
			}
			else if($orderRow['distribution_status'] == 1)
			{
				return 3;//已付款已发货
			}
			else if($orderRow['distribution_status'] == 2)
			{
				return 8;//部分发货
			}
			else if($orderRow['distribution_status'] == 3)
			{
				return 15;
			}
			else if($orderRow['distribution_status'] == 4 )
			{
				return 16;
			}
			else if($orderRow['distribution_status'] == 6 )
			{
				return 17;
			}
		}
		//3,取消或者作废订单
		else if($orderRow['status'] == 3 || $orderRow['status'] == 4)
		{
			return 5;
		}
		
		//4,完成订单
		else if($orderRow['status'] == 5)
		{
			return 6;
		}
		//5,退款
		else if($orderRow['status'] == 6)
		{
			return 7;
		}
		//6,部分退款
		else if($orderRow['status'] == 7)
		{
			//发货
			if($orderRow['distribution_status'] == 1)
			{
				return 10;
			}
			//未发货
			else
			{
				return 9;
			}
		}
		//换货
		else if($orderRow['status'] == 8)
		{
			if($orderRow['distribution_status'] == 1){
				return 13;//已发货
			}else {
				return 12;//换货处理
			}
		}
		else if($orderRow['status']==9){
			return 25;
		}
		return 0;
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
			return '已付款';
		}
		else if($orderRow['pay_status']==3){
			return '退款待审批';
		}
		else if($orderRow['pay_status']==4){
			return '等待退款';
		}
		else if($orderRow['pay_status']==5){
			return '退款成功';
		}
		return '未知';
	}

	//获取订单类型
	public static function getOrderTypeText($orderRow)
	{
		switch($orderRow['type'])
		{
			case "1":
			{
				return '团购订单';
			}
			break;

			case "2":
			{
				return '闪购订单';
			}
			break;
			case "4" : 
			{
				return '预售订单';	
			}
			break;
			default:
			{
				return '普通订单';
			}
		}
	}

	//获取订单配送状态
	public static function getOrderDistributionStatusText($orderRow)
	{
		if($orderRow['distribution_status'] == 0)
		{
			return '未发货';
		}
		else if($orderRow['status'] == 5){
			return '已收货';
		}
		else if($orderRow['distribution_status'] == 1)
		{
			return '已发货';
		}
		
		else if($orderRow['distribution_status'] == 2)
		{
			return '部分发货';
		}
		else if($orderRow['distribution_status'] == 3){
			return '退货待审批';
		}
		else if($orderRow['distribution_status'] == 4){
			return '退货已收';
		}
		else if($orderRow['distribution_status'] == 5){
			return '退货已收';
		}
		else if($orderRow['distribution_status'] == 6){
			return '换货待审批';
		}
		else if($orderRow['distribution_status'] == 7){
			return '换货已收';
		}
	}

	/**
	 * 获取订单状态问题说明
	 * @param $statusCode int 订单的状态码
	 * @return string 订单状态说明
	 */
	public static function orderStatusText($statusCode)
	{
		$result = array(
			0 => '未知',
			1 => '等待发货',
			2 => '等待付款',
			3 => '已发货',
			4 => '等待发货',
			5 => '已取消',
			6 => '订单完成',
			7 => '已退款',
			8 => '部分发货',
			9 => '部分发货',
			10=> '部分退款',
			11=> '已发货',
			12=> '换货处理',
			13=> '已发货',
			15=> '退货处理',
			16=> '退货处理',
			18=> '退货处理',
			19=> '退货处理',
			20=> '换货处理',
			21=> '订单完成',
			22=> '订单完成',
			25=> '已发货'
				
		);
		return isset($result[$statusCode]) ? $result[$statusCode] : '';
	}
	/**
	 * 获取订单中商品的状态
	 */
	public static function get_order_good_status($orderGoodsRow){
		$refunds_status = $orderGoodsRow['refunds_status'];
		switch($refunds_status){
			case 3 : {
				return '退款申请,等待卖家确认';
			}
			case 4 : {
				return '审核通过，等待退款';
			}
			case 5 : {
				return '退款失败';
			}
			case 6 : {
			   return '退款成功'	;
			}
			case 7 : {
				return '等待退货审核';
			}
			case 8 : {
				return '审核通过，等待退款';
			}
			case 9 : {
				return '退款失败';
			}
			case 10 : {
				return '退款成功';
			}
			case 11 : {
				return '等待换货审核';
			}
			case 12 : {
				return '换货成功';
			}
			case 13 : {
				return '换货失败';
			}
		}

		if($orderGoodsRow['is_send']==0){
			return '未发货';
		}
		else if($orderGoodsRow['status']==5 && $orderGoodsRow['is_send']==1){
			return '已完成';
		}
		else if($orderGoodsRow['is_send']==1){
			return '已发货';
		}
		return '';
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
		if($orderRow['pay_status'] > 0)
		{
			$result[$orderRow['pay_time']] = '订单付款  '.$orderRow['order_amount'];
		}

		//3,订单配送
        if($orderRow['distribution_status'] > 0)
        {
        	$result[$orderRow['send_time']] = '订单发货完成';
    	}

		//4,订单完成
        if($orderRow['status'] == 5)
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
		$sendStatus = 2;//部分发货
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
	 	if($sendStatus==1){
	 		$setData['status'] = 9;
	 	}
	 	$setData['distribution_status'] = $sendStatus;
	 	$setData['send_time'] = ITime::getDateTime();
	 	$tb_order->setData($setData);
	 	$tb_order->update('id='.$order_id);

	 	//生成订单日志
    	$tb_order_log = new IModel('order_log');
    	$tb_order_log->setData(array(
    		'order_id' => $order_id,
    		'user'     => $sendorName,
    		'action'   => '发货',
    		'result'   => '成功',
    		'note'     => '订单【'.$order_no.'】由【'.$sendorSort.'】'.$sendorName.'发货',
    		'addtime'  => date('Y-m-d H:i:s')
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
    	if($sendResult && $sendStatus == 1)
    	{
    		sendgoods::run($paramArray);
    	}
	}

	/**
	 * @biref 是否可以发货操作
	 * @param array $orderRow 订单对象
	 */
	public static function isGoDelivery($orderRow)
	{
		/* 1,已经完全发货
		 * 2,非货到付款，并且没有支付*/
		if($orderRow['distribution_status'] == 1 || ($orderRow['pay_type'] != 0 && $orderRow['pay_status'] == 0))
		{
			return false;
		}
		return true;
	}

	/**
	 * @brief 获取商品发送状态
	 */
	public static function goodsSendStatus($is_send)
	{
		$data = array(0 => '未发货',1 => '已发货',2 => '已退货');
		return isset($data[$is_send]) ? $data[$is_send] : '';
	}

	//获取订单商品信息
	public static function getOrderGoods($order_id)
	{
		$orderGoodsObj        = new IQuery('order_goods');
		$orderGoodsObj->where = "order_id = ".$order_id;
		$orderGoodsObj->fields = 'id,goods_array,goods_id,product_id';
		$orderGoodsList = $orderGoodsObj->find();
		$goodList = array();
		foreach($orderGoodsList as $good)
		{
			$goodList[] = json_decode($good['goods_array']);
		}
		return $goodList;
	}

	/**
	 * @brief 返回检索条件相关信息
	 * @param int $search 条件数组
	 * @return array 查询条件（$join,$where）数据组
	 */
	public static function getSearchCondition($search=false)
	{
		$join  = "left join delivery as d on o.distribution = d.id left join payment as p on o.pay_type = p.id left join user as u on u.id = o.user_id";
		$where = "o.if_del = 0";
		//查询检索过滤
		if($search)
		{
			if(isset($search['name']) && isset($search['keywords']) && $search['name'] && $search['keywords'])
			{
				switch($search['name'])
				{
					case "seller_name":
					{
						$sellerObj = new IModel('seller');
						$sellerRow = $sellerObj->getObj('true_name = "'.$search['keywords'].'"');
						$orderId = array(0);
						if($sellerRow)
						{
							$orderGoodsObj        = new IQuery('order_goods as og');
							$orderGoodsObj->join  = "left join goods as go on og.goods_id = go.id";
							$orderGoodsObj->where = "go.seller_id = ".$sellerRow['id'];
							$orderGoodsObj->distinct= "og.order_id";
							$orderGoodsList = $orderGoodsObj->find();
							foreach($orderGoodsList as $key => $val)
							{
								$orderId[] = $val['order_id'];
							}
							array_shift($orderId);
						}
						$where .= " and o.id in (".join(',',$orderId).")";
					}
					break;

					default:
					{
						$where .= " and o.".$search['name']." = '".$search['keywords']."'";
					}
					break;
				}
			}

			foreach($search as $key => $val)
			{
				if(!in_array($key,array('keywords','name')) && $val!='')
				{
					$where .= " and o.".$key." = ".$val;
				}
			}
		}
		$results = array($join,$where);
		unset($join,$where);
		return $results;
	}

	/**
	 * @brief 是否允许退款申请
	 * @param array $orderRow 订单表的数据结构
	 * @return boolean true or false
	 */
	public static function isRefundmentApply($orderRow)
	{
		//已经付款
		if($orderRow['pay_status']!=0)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * 判断快递单是否签收/
	 * @param $deliveryData array  快递单数据（可能有多个）
	 */
	public static function has_accept($deliveryData){
		foreach($deliveryData as $k=>$v){
			//调用物流接口
			
		}
		return true;
	}
	
	/**
	 * @brief 退款状态
	 * @param int $pay_status 退款单状态数值
	 * @return string 状态描述
	 */
	public static function refundmentText($pay_status,$type)
	{	
		if($type==0){//退货
			$result = array('0' => '退款申请,等待卖家确认中', '1' => '退款失败', '2' => '退款成功','3'=>'请退货','4'=>'等待退货审核','5'=>'验货未通过','6'=>'退款失败（超期未退货）','7'=>'审核通过，等待退款');
			
		}else{//换货
			$result = array('0' => '申请换货', '1' => '换货失败', '2' => '换货成功','3'=>'请退货','4'=>'等待换货审核','5'=>'验货未通过','6'=>'换货失败（超期未退货）','7'=>'等待换货');
		}
		return isset($result[$pay_status]) ? $result[$pay_status] : '';
	}
	/**
	 * 退款类型
	 * @param $is_send int 发货状态
	 * @param $type int 0：退货，1：换货
	 */
	public static function refundmentType($is_send,$type){
		if($type==0){
			if($is_send==0)return '卖家未发货，要求退货';
			if($is_send==1)return '买家收货，要求退货';
			
		}else{
			return '';
		}
	}
	/**
	 * @brief 还原重置订单所使用的道具
	 * @param int $order 订单ID
	 */
	public static function resetOrderProp($order_id)
	{
		$orderDB   = new IModel('order');
		$orderList = $orderDB->query('id in ( '.$order_id.' ) and pay_status = 0 and prop is not null');
		foreach($orderList as $key => $orderRow)
		{
			if(isset($orderRow['prop']) && $orderRow['prop'])
			{
				$propDB = new IModel('prop');
				$propDB->setData(array('is_close' => 0));
				$propDB->update('id = '.$orderRow['prop']);
			}
		}
	}

	/**
	 * @brief 商家对退款申请的处理权限
	 * @param int $refundId 退款单ID
	 * @param int $seller_id 商家ID
	 * @return int 退款权限状态, 0:无权查看；1:只读；2：可读可写
	 */
	public static function isSellerRefund($refundId,$seller_id)
	{
		$refundDB = new IModel('refundment_doc');
		$refundRow= $refundDB->getObj('id = '.$refundId.' and seller_id = '.$seller_id);
		if($refundRow)
		{
			$orderGoodsDB = new IModel('order_goods');
			$orderGoodsRow= $orderGoodsDB->getObj('order_id = '.$refundRow['order_id'].' and goods_id = '.$refundRow['goods_id'].' and product_id = '.$refundRow['product_id']);
			if($orderGoodsRow['is_checkout'] == 1)
			{
				return 1;
			}
			else
			{
				return 2;
			}
		}
		return 0;
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
		
		if(in_array($pay_type,array(3))){
			$paymentInstance = Payment::createPaymentInstance($pay_type);
			$paymentData = Payment::getPaymentInfoForRefund($pay_type,$refundId,$order_id,$amount);
			if(!$res=$paymentInstance->refund($paymentData)) return false;//验签失败
		}
		else if($pay_type==1){//预存款付款打入账户余额
			$obj = new IModel('member');
			$isSuccess = $obj->addNum('user_id = '.$user_id,array('balance'=>$amount));
			$obj->commit();
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
		//更新退款表
		$updateData = array(
				'pay_status'   => 2,
				'dispose_time' => ITime::getDateTime(),
		);
		$refundDB->setData($updateData);
		$refundDB->update('id = '.$refundId);
		
		$orderGoodsRow = $orderGoodsDB->getObj('order_id = '.$order_id.' and goods_id = '.$refundsRow['goods_id'].' and product_id = '.$refundsRow['product_id']);
		
		$order_goods_id = $orderGoodsRow['id'];
		
		//未发货的情况下还原商品库存
		if($orderGoodsRow['is_send'] == 0)
		{
			self::updateStore($order_goods_id,'add');
		}
		//更新order表状态
		$isSendData = $orderGoodsDB->getObj('order_id = '.$order_id.' and id != '.$order_goods_id.' and is_send != 2');
		$orderStatus = 6;//全部退款
		if($isSendData)
		{
			$orderStatus = 7;//部分退款
		}
		$tb_order = new IModel('order');
		$tb_order->setData(array('status' => $orderStatus));
		$tb_order->update('id='.$order_id);
		//更新退款状态，改为已退货
		$orderGoodsDB->setData(array('is_send' => 2));
		$orderGoodsDB->update('id = '.$order_goods_id);
		
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
		));
		$tb_order_log->add();
		return true;
	}
	/**
	 * 积分、经验值、
	 * @$order_id int 订单id
	 * @$user_id int 用户id
	 */
	public static function sendGift($order_id,$user_id){
			$order_db = new IModel('order');
			$memberObj = new IModel('member');
			$orderRow = $order_db->getObj('id='.$order_id,'point,exp,real_amount,pro_reduce,order_no');
			$memberRow=$memberObj->getObj('user_id='.$user_id);
			$exp_add = $point_add = 0;
			$real_amount = $orderRow['real_amount'] + $orderRow['pro_reduce'];
			
			$order_goods_query = new IQuery('order_goods as og');
			
			//计算积分赠送倍数
			$trade_record_db = new IModel('trade_record');
			$acc_no = $trade_record_db->getField('order_no like "%'.$orderRow['order_no'].'"  OR FIND_IN_SET("'.$order_id.'",order_ids)' ,'acc_no');
			$acc_no = intval($acc_no);
			if($acc_no>=621272 && $acc_no<=621738)$point_mul = 2;
			else $point_mul=1;
			
			$order_goods_query->where = 'og.order_id='.$order_id.' and og.is_send=2';
			$order_goods_query->fields = 'og.id';
			$order_goods_query->group = 'og.order_id';
			
			if($order_goods_data = $order_goods_query ->find()){//存在退货
				$order_goods_query->join = 'left join goods as g on g.id=og.goods_id';
				$order_goods_query->fields = 'SUM(g.point) as point ,SUM(g.exp) as exp,SUM(og.real_price*og.goods_nums) as real_amount';
				$order_goods_query->where  = 'og.order_id='.$order_id.' and og.is_send!=2';
				
				if($order_goods_add = $order_goods_query->find()){
					$exp_add = $order_goods_add[0]['exp'];
					$exp_add = $exp_add<0 ? 0 :$exp_add;
					$point_add = $order_goods_add[0]['point'];
					$point_add = $point_add<0 ? 0 : $point_add;
					$real_amount = $order_goods_add[0]['real_amount'];
				}else{
					return false;
				}
				//print_r($order_goods_add);exit;
				
			}else{
				$exp_add = $orderRow['exp'];
				$point_add = $orderRow['point'];
			}
			
			if($point_add!=0){
				$point_add = $point_add * $point_mul;
			}
		//(2)进行促销活动奖励
			$proObj = new ProRule($real_amount,$point_mul);
			$proObj->setUserGroup($memberRow['group_id']);
			$proObj->setAward($user_id);
		
			//(3)增加经验值
			$memberData = array(
					'exp'   => $exp_add,
			);
			//$memberObj->setData($memberData);
			$memberObj->addNum('user_id = '.$user_id,$memberData);
		
			$order_url = IUrl::getHost().IUrl::creatUrl("/ucenter/order_detail/id/{$order_id}");
			//(4)增加积分
			$pointConfig = array(
					'user_id' => $user_id,
					'point'   => $point_add,
					'log'     => '成功购买了订单号：<a href="'.$order_url.'">'.$orderRow['order_no'].'</a>中的商品,奖励积分'.$point_add,
			);
			$pointObj = new Point();
			$pointObj->update($pointConfig);
	}
	/**
	 * 退货不退款，订单中加入新商品
	 * @$refundId int 退货单id
	 * @$new_goods_id int 更换的新商品id
	 * @$new_product_id int 更换的新货品id
	 * @$authorId int 操作的管理员id
	 * @$type  string admin,seller
	 * @return 
	 */
	public static function chg_goods($refundId,$new_goods_id,$new_product_id,$authorId,$type = 'admin'){
		if(!$new_goods_id && !$new_product_id)return false;
		$orderGoodsDB= new IModel('order_goods');
		$refundDB    = new IModel('refundment_doc');
		$order_db = new IModel('order');
		$goods_db = new IModel('goods');
		
		
		//获取goods_id和product_id用于给用户减积分，经验
		$refundsRow = $refundDB->getObj('id = '.$refundId,'order_id,order_no,user_id,goods_id,product_id');
		$order_id   = $refundsRow['order_id'];
		$order_no   = $refundsRow['order_no'];
		$user_id    = $refundsRow['user_id'];
		
		$orderGoodsRow = $orderGoodsDB->getObj('order_id = '.$order_id.' and goods_id = '.$refundsRow['goods_id'].' and product_id = '.$refundsRow['product_id']);
		$order_goods_id = $orderGoodsRow['id'];
		
		//原订单中没有未退货的订单那，状态改为作废
		if(!$orderGoodsDB->getObj('order_id='.$order_id.' and is_send !=2 and id !='.$order_goods_id)){
			$order_db->setData(array('status'=>4));
			$order_db->update('id='.$order_id);
		}
		//原商品更新为退款状态
		$orderGoodsDB->setData(array('is_send' => 2,'refunds_status'=>12));
		$orderGoodsDB->update('id = '.$order_goods_id);
		
		
		//生成新订单
		$orderRow = $order_db->getObj('id='.$order_id);
		$goodsRow = $goods_db->getObj('id='.$refundsRow['goods_id'],'exp,point');
		$new_order_data = array(
			'order_no' => Order_Class::createOrderNum(),
			'user_id' => $orderRow['user_id'],
			'pay_type'=> $orderRow['pay_type'],
			'distribution'=>$orderRow['distribution'],
			'status' => 2,
			'pay_status' => 1,
			'distribution_status' => 0,
			'accept_name' => $orderRow['accept_name'],
			'postcode'    => $orderRow['postcode'],
			'telphone'    => $orderRow['telphone'],
			'country'     => $orderRow['country'],
			'province'    => $orderRow['province'],
			'city'        => $orderRow['city'],
			'area'        => $orderRow['area'],
			'address'     => $orderRow['address'],
			'mobile'      => $orderRow['mobile'],
			'pay_time'    => $orderRow['pay_time'],
			'create_time' => ITime::getDateTime(),
			'exp'         => $goodsRow['exp']*$orderGoodsRow['goods_nums'],
			'point'       => $goodsRow['point']*$orderGoodsRow['goods_nums'],
			'type'        => $orderRow['type'],
			'trade_no'    => $orderRow['trade_no'],
			'takeself'    => $orderRow['takeself'],
			'checkcode'   => $orderRow['checkcode'],
			'active_id'   => $orderRow['active_id'],
			'pro_reduce'  => $orderRow['pro_reduce'],
			'ticket_reduce' => $orderRow['ticket_reduce'],
			'real_amount' => $orderRow['real_amount']
		);
		$order_db->setData($new_order_data);
		$new_order_id = $order_db->add();
		
		//新增order_good
		$new_order_good = array(
			'order_id'=>$new_order_id,
			'goods_id'=>$new_goods_id,
			'product_id'=>$new_product_id,
			'real_price'=>$orderGoodsRow['real_price'],
			'goods_nums'=>$orderGoodsRow['goods_nums'],
			'goods_weight'=>$orderGoodsRow['goods_weight'],
			'delivery_fee'=>$orderGoodsRow['delivery_fee'],
			'save_price'=>$orderGoodsRow['save_price'],
			'img'       => $orderGoodsRow['img']
		);
		if($orderGoodsRow['comment_id']!=0)$new_order_good['comment_id']=-1;
		$product_db = new IModel('products');
		if($new_product_id){//存在货品
			$resData = $product_db->getObj('id='.$new_product_id,'sell_price,spec_array,products_no as goods_no');
		
			$resData = array_merge($resData,$goods_db->getObj('id='.$new_goods_id,'name'));
		}else {
			$resData = $goods_db->getObj('id='.$new_goods_id,'name,sell_price,spec_array,goods_no');
		}
		$new_order_good['goods_price']=$resData['sell_price'];
		$new_order_good['goods_array'] = self::order_goods_spec($resData);
	
		$orderGoodsDB->setData($new_order_good);
		$new_goods_id = $orderGoodsDB->add();
		
		self::updateStore($new_goods_id,'reduce');

		//获取原商品货号
		if($refundsRow['product_id']!=0){
			$old_good_no = $product_db->getField('id='.$refundsRow['product_id'],'products_no');
		}else{
			$old_good_no = $goods_db->getField('id='.$refundsRow['goods_id'],'goods_no');
		}
		
		//生成订单日志
		$authorName = $type == 'admin' ? ISafe::get('admin_name') : ISafe::get('seller_name');
		if($type=='system')$authorName='系统自动';
		$tb_order_log = new IModel('order_log');
		$tb_order_log->setData(array(
				'order_id' => $order_id,
				'user'     => $authorName,
				'action'   => '换货',
				'result'   => '成功',
				'note'     => '商品【'.$old_good_no.'】更换为'.$resData['goods_no'],
				'addtime'  => ITime::getDateTime(),
		));
		$tb_order_log->add();
		return true;
	}
	/**
	 * 是否可以作废订单
	 */
	public static function is_cancle($order_id){
		$orderM = new IModel('order');
		$orderRow = $orderM->getObj('id='.$order_id);
		$period = 48;//有效期6小时
		$now = ITime::getNow();
		$orderTime = ITime::getTime($orderRow['create_time']);
		if($orderRow['pay_status']==1 && $orderRow['status']==6){//已付款、已退款可以作废
			return true;
		}
		//
		if($orderRow['pay_type']==0 || $orderRow['pay_status']==1 || $now-$orderTime<=3600*$period){
			return false;//不可作废
		}
		return true;
	}
	/**
	 * 获取发票状态0:申请发票，1：审核通过，2：寄送
	 * @$status int 总状态参数0：未开，1：
	 */
	public static function getFapiaoStatus($status){
		$statusText = '';
		switch($status){
			case 0 : {
				$statusText = '提交申请';
				break;
			}
			case 1 : {
				$statusText = '已开票';
				break;
			}
		}
		return $statusText;
	}
	/**
	 * 判断订单是否过期
	 * @$start_time  开始时间
	 * @$days int 超过此时间失效
	 * @return bool
	 */
	public static function is_overdue($start_time,$days){
		if(ITime::getTime()-ITime::getTime($start_time)>$days*24*3600){
			return false;
		}
		return true;
	}
	/**
	 * 计算退款金额
	 * @$goodsOrderRow array order_goods信息
	 * @$orderRow array  订单信息
	 * @return float 退款金额
	 */
	public static function get_refund_fee($orderRow,$goodsOrderRow){
		//未发货的时候 退款运费和保价,税金
		$otherFee = 0;
		$goods_db = new IModel('goods');
		$seller_id = $goods_db->getField('id='.$goodsOrderRow['goods_id'],'seller_id');
		if(!$seller_id)$seller_id = 0;
		$order_goods_db = new IQuery('order_goods as og');
		$order_goods_db->join = 'left join goods as g on og.goods_id=g.id ';
		$order_goods_db->where = 'og.order_id='.$goodsOrderRow['order_id'].' and og.id !='.$goodsOrderRow['id'].' and og.is_send!=2 and g.seller_id='.$seller_id;
		$order_goods_db->limit = 1;
		$send_data = $order_goods_db->find();
		if($goodsOrderRow['delivery_id'] == 0 )
		{
			if(empty($send_data)){
				$otherFee += $goodsOrderRow['delivery_fee'] + $goodsOrderRow['save_price']  ;
			}
			$otherFee += $goodsOrderRow['tax'];
		}
		
		$amount = $goodsOrderRow['real_price'] * $goodsOrderRow['goods_nums'];
		//退款额计算：将促销优惠和红包优惠平均分配
		$order_reduce = $orderRow['pro_reduce'] + $orderRow['ticket_reduce'];
		$amount = $amount - $amount * $order_reduce/($orderRow['real_amount']+$orderRow['pro_reduce'])+ $otherFee;
		//rturn$amount = str_replace(',','',$amount);
		$amount = number_format(floatval($amount),2);
		return str_replace(',','',$amount);	
	}
	/**
	 * 退款时修改订单状态，
	 * @param $refunds_status int 退款状态
	 * @param $goodsOrderRow array  订单商品信息	 * 
	 * @param $type int 售后类型 0 ：退货 1：换货
	 */
	public static function order_status_refunds($refunds_status,$goodsOrderRow,$type=0){
		$order_db = new IModel('order');
		$order_goods_db = new IModel('order_goods');
		$setData = array();
		if($refunds_status==0){
			if($goodsOrderRow['is_send']==0){//未发货
				$setData = array('pay_status'=>3);//付款状态：退货待审批
			}else if($goodsOrderRow['is_send']==1){
				if($type==1){
					$setData = array('distribution_status'=>6);//换货待审批
				}else{
					$setData = array('distribution_status'=>3);//发货状态：退货待审批
				}
			}
		}
		else if($refunds_status==7){
			if($goodsOrderRow['is_send']==0){//未发货
				$setData = array('pay_status'=>4);//等待退款
			}else if($goodsOrderRow['is_send']==1){
				if($type==1){
					$setData = array('distribution_status'=>7,'pay_status'=>1);
				}else{
					$setData = array('distribution_status'=>4,'pay_status'=>4);//发货状态：退货已收
				}
				
			}
		}
		else if(in_array($refunds_status,array(1,5))){
			if($goodsOrderRow['is_send']==1){
				$not_send_data = $order_goods_db->getObj('order_id='.$goodsOrderRow['order_id']. ' and is_send=0');
				if($not_send_data){//退款商品已发货，且订单中存在未发货商品
					$setData = array('distribution_status'=>2,'pay_status'=>1);
				}else{
					$setData = array('distribution_status'=>1,'pay_status'=>1);
				}
			}else{
				$has_send_data = $order_goods_db->getObj('order_id='.$goodsOrderRow['order_id']. ' and is_send=1');
				if($has_send_data){
					$setData = array('distribution_status'=>2,'pay_status'=>1);
				}else{
					$setData = array('distribution_status'=>0,'pay_status'=>1);
				}
			}
			
			
		}
		else if($refunds_status==2){
			$order_good_data = $order_goods_db->query('order_id='.$goodsOrderRow['order_id']. ' and is_send !=2 and id != '.$goodsOrderRow['id'],'is_send');
			
			if($type==0){
				if($goodsOrderRow['is_send']==0){//未发货退货
					$setData = array('pay_status'=>5,'distribution_status'=>0);
				}
				else{//已发货退货
					$setData = array('pay_status'=>5,'distribution_status'=>5);
				}
				
			}
			
			$has_send = $not_send = 0;
			if(!empty($order_good_data)){
				foreach($order_good_data as $v){
					if($v['is_send']==0){
						$not_send = 1;
					}
					else if($v['is_send']==1){
						$has_send = 1;
					}
				}
				if($has_send && !$not_send)$setData['status'] = 9;
			}else{
				$setData['status'] = 6;
			}
		}
		else{
			return false;
		}
		if($type==0){
			$order_db->setData($setData);
			$order_db->update('id='.$goodsOrderRow['order_id']);
		}
		
	}
	/**
	 * 退款时修改，order_goods表refunds_status
	 * @param $refunds_status int 退款状态
	 * @param $goodsOrderRow array  订单商品信息	 *
	 * @param $type int 售后类型 0 ：退货 1：换货
	 */
	public static function ordergoods_status_refunds($refunds_status,$goodsOrderRow,$type=0){
		$order_goods_db = new IModel('order_goods');
		$setDataOg = array();
		if($refunds_status==0){
			if($goodsOrderRow['is_send']==0){//未发货
				$setDataOg = array('refunds_status'=>3);
			}else if($goodsOrderRow['is_send']==1){
				if($type==1){
					$setDataOg['refunds_status'] = 11;
				}else{
					$setDataOg['refunds_status'] = 7;
				}
			}
		}
		else if($refunds_status==7){
			if($goodsOrderRow['is_send']==0){//未发货
				$setDataOg['refunds_status'] = 4;
			}else if($goodsOrderRow['is_send']==1){
				if($type==1){
					$setDataOg['refunds_status'] = 12;
				}else{
					$setDataOg['refunds_status'] = 8;
				}
			}
		}
		else if(in_array($refunds_status,array(1,5))){//被拒绝
			if($goodsOrderRow['is_send']==1){
				if($type==0){
					$setDataOg['refunds_status'] = 9;
				}else $setDataOg['refunds_status'] = 13;
				
			}else{
				$setDataOg['refunds_status'] = 0;
			}
				
		}
		else if($refunds_status==2){//成功
			if($type==0){
				if($goodsOrderRow['is_send']==0){//未发货退货
					$setDataOg['refunds_status'] = 6;
				}
				else{//已发货退货
					$setDataOg['refunds_status'] = 10;
				}
	
			}else{
				$setDataOg['refunds_status'] = 13;
			}
		}
		else if($refunds_status==-1){//退换货单取消
			$setDataOg['refunds_status'] = 0;
		}
		else{
			return false;
		}
		$order_goods_db->setData($setDataOg);
		$order_goods_db->update('id='.$goodsOrderRow['id']);
	
	}
	/**
	 * 退货更改订单状态，按照同一订单中退货单状态最低的计算
	 * @param int $refund_id 退款单 id
	 * @param int $pay_status 要更新为的退款单状态
	 */
	public static function get_order_status_refunds($refund_id,$pay_status){
		$status_arr = array(
			'0' => 9,
			'4' => 1,
			'7' => 2,
			'2' => 3,
			'1' => 6,
			'5' => 6
		);
		$refund_db = new IModel('refundment_doc');
		$refund_data = $refund_db->getObj('id='.$refund_id,'order_id,goods_id,product_id');
		
		if(!$refund_data)return false;
		$order_goods_db = new IModel('order_goods');
		$order_goods_data = $order_goods_db->query('order_id='.$refund_data['order_id']);
		
		//找到退款状态最慢的状态，退款拒绝的除外
		$low_pay_status = 0;
		foreach($order_goods_data as $key=>$val){
			if($val['goods_id']==$refund_data['goods_id'] && $val['product_id']==$refund_data['product_id']){
				$order_goods_row = $order_goods_data[$key];
				continue;
			}
			if(in_array($val['refunds_status'],array(3,7)))
			{
				if($status_arr[4]<$status_arr[$low_pay_status])$low_pay_status = 4;
				continue;
			}else if(in_array($val['refunds_status'],array(4,8)))
			{
				if($status_arr[7]<$status_arr[$low_pay_status])$low_pay_status = 7;
				continue;
			}
			else if(in_array($val['refunds_status'],array(6,10)))
			{
				if($status_arr[2]<$status_arr[$low_pay_status])$low_pay_status = 2;
				continue;
			}
				
		}
		if($status_arr[$low_pay_status] > $status_arr[$pay_status])
			$low_pay_status = $pay_status;
		
		self::order_status_refunds($low_pay_status,$order_goods_row);
		
	}

}