<?php
/**
 * @file payment.php
 * @brief 支付方式 操作类
 * @author 
 * @date 2011-01-20
 * @version 0.6
 * @note
 */

/**
 * @class Payment
 * @brief 支付方式 操作类
 */
//支付状态：支付失败
define ( "PAY_FAILED", - 1);
//支付状态：支付超时
define ( "PAY_TIMEOUT", 0);
//支付状态：支付成功
define ( "PAY_SUCCESS", 1);
//支付状态：支付取消
define ( "PAY_CANCEL", 2);
//支付状态：支付错误
define ( "PAY_ERROR", 3);
//支付状态：支付进行
define ( "PAY_PROGRESS", 4);
//支付状态：支付无效
define ( "PAY_INVALID", 5);

class Payment
{
	/**
	 * @brief 创建支付类实例
	 * @param $payment_id int 支付方式ID
	 * @return 返回支付插件类对象
	 */
	public static function createPaymentInstance($payment_id)
	{
		$paymentRow = self::getPaymentById($payment_id);

		if($paymentRow && isset($paymentRow['class_name']) && $paymentRow['class_name'])
		{
			$class_name = $paymentRow['class_name'];
			$classPath  = IWeb::$app->getBasePath().'plugins/payments/pay_'.$class_name.'/'.$class_name.'.php';
			if(file_exists($classPath))
			{
				require_once($classPath);
				return new $class_name($payment_id);
			}
			else
			{
				IError::show(403,'支付接口类'.$class_name.'没有找到');
			}
		}
		else
		{
			IError::show(403,'支付方式不存在');
		}
	}

	/**
	 * @brief 根据支付方式配置编号  获取该插件的详细配置信息
	 * @param $payment_id int    支付方式ID
	 * @param $key        string 字段
	 * @return 返回支付插件类对象
	 */
	public static function getPaymentById($payment_id,$key = '')
	{
		$paymentDB  = new IModel('payment');
		$paymentRow = $paymentDB->getObj('id = '.$payment_id);

		if($key)
		{
			return isset($paymentRow[$key]) ? $paymentRow[$key] : '';
		}
		return $paymentRow;
	}

	/**
	 * @brief 根据支付方式配置编号  获取该插件的配置信息
	 * @param $payment_id int    支付方式ID
	 * @param $key        string 字段
	 * @return 返回支付插件类对象
	 */
	public static function getConfigParam($payment_id,$key = '')
	{
		$payConfig = self::getPaymentById($payment_id,'config_param');
		if($payConfig)
		{
			$payConfig = JSON::decode($payConfig);
			return isset($payConfig[$key]) ? $payConfig[$key] : '';
		}
		return '';
	}
	/**
	 * 获取支付参数（商户id，密码）
	 * @param unknown $payment_id
	 */
	private static function getPaymentParam($payment_id){
		//最终返回值
		$payment = array();
		
		//初始化配置参数
		$paymentInstance = Payment::createPaymentInstance($payment_id);
		$configParam = $paymentInstance->configParam();
		foreach($configParam as $key => $val)
		{
			$payment[$key] = '';
		}
		
		//获取公共信息
		$paymentRow = self::getPaymentById($payment_id,'config_param');
		if($paymentRow)
		{
			$paymentRow = JSON::decode($paymentRow);
			foreach($paymentRow as $key => $item)
			{
				$payment[$key] = $item;
			}
		}
		return $payment;
	}
	/**
	 * @brief 预售订单的支付
	 * @param $payment_id int 支付方式id
	 * @param $order_id  int  预售订单id
	 * @return array 支付提交信息
	 */
	public static function getPaymentInfoPresell($payment_id,$order_id)
	{
		$payment = self::getPaymentParam($payment_id);
		$order_obj = new IQuery('order as o');
		$order_obj->join = 'left join presell as p on o.active_id = p.id';
		$order_obj->fields = 'o.id,o.order_no,o.status,o.pay_time,o.postscript,o.mobile,o.accept_name,o.postcode,
				o.telphone,o.address,o.pay_status,o.pay_type,o.pre_amount,o.order_amount,o.create_time,
				p.wei_type,p.wei_days,p.wei_start_time,p.wei_end_time';
		$order_obj->where  = 'o.id='.$order_id.' and o.type=4';
		$orderRow = $order_obj->getObj();
	//	print_r($orderRow);exit();
		if(empty($orderRow))
		{
			IError::show(403,'订单信息不正确，不能进行支付');
		}
		$payment_Data = Preorder_Class::get_pay_money($orderRow);//计算订单支付金额，如果不满足支付条件则显示错误
		$payment['M_Amount']    = $payment_Data['M_Amount'];
		$payment['M_OrderNO']   = $payment_Data['M_OrderNO'];
		
		$payment['M_Remark']    = $orderRow['postscript'];
		$payment['M_OrderId']   = $orderRow['id'];
		
		//用户信息
		$payment['P_Mobile']    = $orderRow['mobile'];
		$payment['P_Name']      = $orderRow['accept_name'];
		$payment['P_PostCode']  = $orderRow['postcode'];
		$payment['P_Telephone'] = $orderRow['telphone'];
		$payment['P_Address']   = $orderRow['address'];
		$siteConfigObj = new Config('site_config');
		$site_config   = $siteConfigObj->getInfo();
		
		//交易信息
		$payment['M_Time']      = time();
		$payment['M_Paymentid'] = $payment_id;
		
		//店铺信息
		$payment['R_Address']   = isset($site_config['address']) ? $site_config['address'] : '';
		$payment['R_Name']      = isset($site_config['name'])    ? $site_config['name']    : '';
		$payment['R_Mobile']    = isset($site_config['mobile'])  ? $site_config['mobile']  : '';
		$payment['R_Telephone'] = isset($site_config['phone'])   ? $site_config['phone']   : '';
        $payment['pay_level'] = 2;
		
		return $payment;
		
	}
	/**
	 * @brief 获取订单中的支付信息 M:必要信息; R表示店铺; P表示用户;
	 * @param $payment_id int    支付方式ID
	 * @param $type       string 信息获取方式 order:订单支付;recharge:在线充值;
	 * @param $argument   mix    参数
	 * @return array 支付提交信息
	 */
	public static function getPaymentInfo($payment_id,$type,$argument, $pay_level = 2)
	{
		
		$payment = self::getPaymentParam($payment_id);
		if($type == 'order')
		{
			$order_id = $argument;
            $orderGoodsDB   = new IModel('order_goods');
            
			//获取订单信息
            if($pay_level == 1)
            {
			    $orderParentObj = new IModel('order_parent');
                $orderObj = new IModel('order');

                //判断商品库存                                
                $orderIdList =  $orderObj->getFields(array('pid'=> $order_id), 'id');
                $orderGoodsList = $orderGoodsDB->query('order_id in ('.implode(',', $orderIdList).")");
            }
            else
            {
                $orderObj = $orderParentObj = new IModel('order');
                $orderGoodsList = $orderGoodsDB->query('order_id = '.$order_id);
            }
			$orderRow = $orderParentObj->getObj('id = '.$order_id.' and status = 1');
			if(empty($orderRow))
			{
				IError::show(403,'订单信息不正确，或已支付');
			}                                                                                      
			foreach($orderGoodsList as $key => $val)
			{
                if($val['is_change'] == 0)
                {
				    if(!goods_class::checkStore($val['goods_nums'],$val['goods_id'],$val['product_id']))
				    {
					    IError::show(403,'商品库存不足无法支付，请重新下单');
				    }
                }
			}

			$payment['M_Remark']    = $orderRow['postscript'];
			$payment['M_OrderId']   = $orderRow['id'];
			$payment['M_OrderNO']   = $orderRow['order_no'];
			$payment['M_Amount']    = $orderRow['order_amount'];

			//用户信息
			$payment['P_Mobile']    = $orderRow['mobile'];
			$payment['P_Name']      = $orderRow['accept_name'];
			$payment['P_PostCode']  = $orderRow['postcode'];
			$payment['P_Telephone'] = $orderRow['telphone'];
			$payment['P_Address']   = $orderRow['address'];
            $payment['pay_level'] = $pay_level;
		}
		else if($type == 'recharge')
		{
			if(ISafe::get('user_id') == null)
			{
				IError::show(403,'请登录系统');
			}

			if(!isset($argument['account']) || $argument['account'] <= 0)
			{
				IError::show(403,'请填入正确的充值金额');
			}

			$rechargeObj = new IModel('online_recharge');
			$reData      = array(
				'user_id'     => ISafe::get('user_id'),
				'recharge_no' => Order_Class::createOrderNum(),
				'account'     => $argument['account'],
				'time'        => ITime::getDateTime(),
				'payment_name'=> $argument['paymentName'],
			);
			$rechargeObj->setData($reData);
			$r_id = $rechargeObj->add();

			//充值时用户id跟随交易号一起发送,以"_"分割
			$payment['M_OrderNO'] = 'recharge'.$reData['recharge_no'];
			$payment['M_OrderId'] = $r_id;
			$payment['M_Amount']  = $reData['account'];
			$payment['M_Remark']  = '';
		}
        
		$siteConfigObj = new Config("site_config");
		$site_config   = $siteConfigObj->getInfo();
        

		//交易信息
		$payment['M_Time']      = time();
		$payment['M_Paymentid'] = $payment_id;

		//店铺信息
		$payment['R_Address']   = isset($site_config['address']) ? $site_config['address'] : '';
		$payment['R_Name']      = isset($site_config['name'])    ? $site_config['name']    : '';
		$payment['R_Mobile']    = isset($site_config['mobile'])  ? $site_config['mobile']  : '';
		$payment['R_Telephone'] = isset($site_config['phone'])   ? $site_config['phone']   : '';

		return $payment;
	}
	public static function getPaymentInfoMerge($payment_id,$order_ids){
		$payment = self::getPaymentParam($payment_id);
		//获取订单信息
		$orderObj = new IModel('order');
		//筛选出符合支付条件的订单，并重置order_ids
		$orderList = $orderObj->query('id in( '.$order_ids.') and (type!=4 and status = 1 or (type=4 and status in (1,4)))');
		if(empty($orderList))IError::show(403,'订单信息不存在，不能支付');
		$order_ids='';
		foreach($orderList as $k=>$v){
			$order_ids .= $v['id'].',';
		}
		$order_ids = substr($order_ids,0,-1);
		//判断商品库存
		$orderGoodsDB   = new IQuery('order_goods as og');
		$orderGoodsDB->join = 'left join order as o on o.id=og.order_id';
		$orderGoodsDB->where = 'order_id in( '.$order_ids.')';
		$orderGoodsDB->fields = 'og.*,o.type';
		$orderGoodsList = $orderGoodsDB->find();
		foreach($orderGoodsList as $key => $val)
		{
			if($val['is_change'] == 0){
			    if($val['type']!=4 && !goods_class::checkStore($val['goods_nums'],$val['goods_id'],$val['product_id']))
			    {
				    IError::show(403,'商品库存不足无法支付，请重新下单');
			    }
            }
		}
		
		$payment['M_Remark']    = $orderList[0]['postscript'];
		$payment['M_OrderId']   = $orderList[0]['id'];
		$payment['M_OrderNO']   = 'merge'.Order_Class::createOrderNum();
		$payment['M_Amount']    = 0;
		
		//用户信息
		$payment['P_Mobile']    = $orderList[0]['mobile'];
		$payment['P_Name']      = $orderList[0]['accept_name'];
		$payment['P_PostCode']  = $orderList[0]['postcode'];
		$payment['P_Telephone'] = $orderList[0]['telphone'];
		$payment['P_Address']   = $orderList[0]['address'];
		foreach($orderList as $k=>$v){
			if($v['type']!=4){
				$payment['M_Amount'] += $v['order_amount'];
			}else{
				$payment_Data = Preorder_Class::get_pay_money($v);//计算订单支付金额，如果不满足支付条件则显示错误
				$payment['M_Amount'] += $payment_Data['M_Amount'];
			}
			
		}
		
		$siteConfigObj = new Config("site_config");
		$site_config   = $siteConfigObj->getInfo();
		
		//交易信息
		$payment['M_Time']      = time();
		$payment['M_Paymentid'] = $payment_id;
		
		//店铺信息
		$payment['R_Address']   = isset($site_config['address']) ? $site_config['address'] : '';
		$payment['R_Name']      = isset($site_config['name'])    ? $site_config['name']    : '';
		$payment['R_Mobile']    = isset($site_config['mobile'])  ? $site_config['mobile']  : '';
		$payment['R_Telephone'] = isset($site_config['phone'])   ? $site_config['phone']   : '';
		
		$merge_order = new IModel('merge_order');
		$merge_order->setData(array('order_ids'=>$order_ids,'order_no'=>$payment['M_OrderNO'],'pay_type'=>$payment_id,'time'=>ITime::getDateTime()));
		$merge_order->add();
		
		return $payment;
		
	}
	/**
	 * @获取退款需要订单数据
	 * @$payment_id int 支付方式id
	 * @$order_id  int 订单id
	 * @return array
	 */
	public static function getPaymentInfoForRefund($payment_id,$refundId,$order_id,$money){
		$payment = self::getPaymentParam($payment_id);
		
		$orderObj = new IModel('order');
		$orderRow = $orderObj->getObj('id = '.$order_id,'order_no,trade_no');
		
		if(empty($orderRow))
		{
			IError::show(403,'订单信息不正确，不能退款');
		}
		$payment['M_OrderNO'] = md5($refundId);
        $payment['M_Order_NO'] = $orderRow['order_no'];
		$payment['M_Trade_NO'] = $orderRow['trade_no'];
		$payment['M_Amount']    = $money;
		return $payment;
		
	}
	/**
	 * @获取预售退款需要订单数据
	 * @$payment_id int 支付方式id
	 * @$order_id  int 订单id
	 * @return array
	 */
	public static function getPaymentInfoForPresellRefund($payment_id,$refundId,$order_id,$money){
		$payment = array();
		$orderObj = new IQuery('order as o');
		$orderObj->join = 'left join trade_record as t on CONCAT("pre",o.order_no) = t.order_no OR CONCAT("wei",o.order_no) = t.order_no OR find_in_set(o.id,t.order_ids)';
		$orderObj->where = 'o.id='.$order_id;
		$orderObj->fields = 'o.order_no,o.pre_amount,t.trade_no,t.money as trade_money,o.id';
		$orderObj->limit = 3;
		$orderObj->order = 't.id ASC';
		$orderData = $orderObj->find();
		
		if(empty($orderData))
		{
			IError::show(403,'订单信息不正确，不能退款');
		}
		
		
		foreach($orderData as $key=>$v){
			if($key==0){
				$money1=0;
				if($v['trade_no'])
					$reMoney = $v['trade_money'] - self::getOrigReMoney($v['trade_no']);
				else continue;
				if($reMoney<=0){
					continue;
				}
				$payment[$key]['M_OrderNO'] = 'pre'.md5($refundId);
                
                $payment[$key]['M_Order_NO'] = $v['order_no'];
				$payment[$key]['M_Trade_NO'] = $v['trade_no'];
				if($reMoney >= $money){
					$payment[$key]['M_Amount']    = $money;
					
				}else{
					$payment[$key]['M_Amount']    = $reMoney;
				}
				$money1 = $payment[$key]['M_Amount'];
			}
			if($key==1){
				$reMoney = $v['trade_money'] - self::getOrigReMoney($v['trade_no']);
				if($reMoney<=0){
					continue;
				}
                $payment[$key]['M_OrderNO'] = 'wei'.md5($refundId);
				$payment[$key]['M_Order_NO'] = $v['order_no'];
				$payment[$key]['M_Trade_NO'] = $v['trade_no'];
				$payment[$key]['M_Amount']    = ($money - $money1) <=$reMoney ? $money - $money1 : $reMoney;
			}
			$payment[$key] = array_merge($payment[$key],self::getPaymentParam($payment_id));
		}
		return $payment;
	
	}
	//获取交易已经退款的金额
	public static function getOrigReMoney($trade_no){
		$trade_obj = new IModel('trade_record');
		$orig_data = $trade_obj->query('orig_trade_no = "'.$trade_no.'"', 'money');
		$orig_money = 0;//该交易的已退款金额
		foreach($orig_data as $key=>$v){
			$orig_money += $v['money'];
		}
		return $orig_money;
	}
	//更新在线充值
	public static function updateRecharge($recharge_no)
	{
		$rechargeObj = new IModel('online_recharge');
		$rechargeRow = $rechargeObj->getObj('recharge_no = "'.$recharge_no.'"');
		if(empty($rechargeRow))
		{
			return false;
		}

		if($rechargeRow['status'] == 1)
		{
			return true;
		}

		$dataArray = array(
			'status' => 1,
		);

		$rechargeObj->setData($dataArray);
		$result = $rechargeObj->update('recharge_no = "'.$recharge_no.'"');

		if($result == '')
		{
			return false;
		}

		$money   = $rechargeRow['account'];
		$user_id = $rechargeRow['user_id'];

		$memberObj = new IModel('member');
		$dataArray = array(
			'balance' => 'balance + '.$money
		);
		$memberObj->setData($dataArray);
		$is_success = $memberObj->update('user_id = '.$user_id,'balance');

		if($is_success)
		{
			$log = new AccountLog();
			$config=array(
				'user_id'  => $user_id,
				'event'    => 'recharge',
				'note'     => '用户['.$user_id.']在线充值',
				'num'      => $money,
			);
			$re = $log->write($config);
		}
		return $is_success;
	}
	
}