<?php
/**
 * @brief 公共模块
 * @class Block
 */
class Block extends IController
{
	public $layout='';

	public function init()
	{
		CheckRights::checkUserRights();
	}

 	/**
	 * @brief Ajax获取规格值
	 */
	function spec_value_list()
	{
		// 获取POST数据
		$spec_id = IFilter::act(IReq::get('id'),'int');

		//初始化spec商品模型规格表类对象
		$specObj = new IModel('spec');
		//根据规格编号 获取规格详细信息
		$specData = $specObj->getObj("id = $spec_id");
		if($specData)
		{
			echo JSON::encode($specData);
		}
		else
		{
			//返回失败标志
			echo '';
		}
	}

	//列出筛选商品
	function goods_list()
	{
		//商品检索条件
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$show_num    = IFilter::act( IReq::get('show_num'),'int') ;
		$show_num = $show_num ? $show_num : 10;
		$keywords    = IFilter::act( IReq::get('keywords') );
		$cat_id      = IFilter::act( IReq::get('category_id'),'int');
		$min_price   = IFilter::act( IReq::get('min_price'),'float');
		$max_price   = IFilter::act( IReq::get('max_price'),'float');
		$goods_no    = IFilter::act( IReq::get('goods_no'));
		$is_products = IFilter::act( IReq::get('is_products'),'int');
		$seller_id   = IFilter::act( IReq::get('seller_id'),'int');
        $goods_id    = IFilter::act( IReq::get('goods_id'),'int');
        $exp_presell = IFilter::act( IReq::get('exp_presell'),'int');   //组合销售选择商品时排除预售商品
		$exp_code = IFilter::act( IReq::get('exp_code'),'int');         //组合销售选择商品时排除虚拟商品
		//$tb_goods = new IQuery('goods as go');
		
		$condition = '&show_num='.$show_num;
		if($keywords)$condition .= '&keywords='.$keywords;
		if($cat_id)$condition .= '&category_id='.$cat_id;
		if($min_price)$condition .= '&min_price='.$min_price;
		if($max_price)$condition .= '&max_price='.$max_price;
		if($goods_no)$condition .= '&goods_no='.$goods_no;
		if($is_products)$condition .= '&is_products='.$is_products;
		if($seller_id)$condition .= '&seller_id='.$seller_id;
        if($goods_id)$condition .= '&goods_id='.$goods_id;
        if($exp_presell)$condition .= '&exp_presell='.$exp_presell;
		if($exp_code)$condition .= '&exp_code='.$exp_code;
		//查询条件
		$table_name = 'goods as go';
        
        $where   = $exp_presell ? ' go.is_del = 0 ' : ' (go.is_del = 0 or go.is_del = 4) ';
		$where  .= $exp_code ? ' and go.type <> 1 ' : '';
		$where  .= $goods_id  ? ' and go.id           = '.$goods_id      : '';
		$where  .= isset($seller_id) ? ' and go.seller_id    = '.$seller_id     : '';//此处做了更改
		$where  .= $goods_no  ? ' and go.goods_no     = "'.$goods_no.'"' : '';
		$where  .= $min_price ? ' and go.sell_price  >= '.$min_price     : '';
		$where  .= $max_price ? ' and go.sell_price  <= '.$max_price     : '';
		$where  .= $keywords  ? ' and go.name like    "%'.$keywords.'%"' : '';

		//分类筛选
		if($cat_id)
		{
			$table_name .= ' ,category_extend as ca ';
			$where      .= " and ca.category_id = {$cat_id} and go.id = ca.goods_id ";
		}
		$tb_goods = new IQuery($table_name);
		$tb_goods->where = $where;
		$tb_goods->fields     = 'go.id as goods_id,go.name,go.img,go.store_nums,go.goods_no,go.sell_price,go.spec_array,go.is_del';
		$tb_goods->page = $page;
		$tb_goods->pagesize = $show_num;
		$tb_goods->order    = "go.sort asc,go.id desc";
		//获取商品数据
		$data   = $tb_goods->find();

		//包含货品信息
		if($is_products)
		{
			if($data)
			{
				$goodsIdArray = array();
				foreach($data as $key => $val)
				{
					//有规格有货品
					if($val['spec_array'])
					{
						$goodsIdArray[$key] = $val['goods_id'];
						unset($data[$key]);
					}
				}

				if($goodsIdArray)
				{
					$productFields = "pro.goods_id,go.name,go.img,pro.id as product_id,pro.products_no as goods_no,pro.spec_array,pro.sell_price,pro.store_nums";
					$productDB     = new IModel('goods as go,products as pro');
					$productDate   = $productDB->query('go.id = pro.goods_id and pro.goods_id in('.join(',',$goodsIdArray).')',$productFields);
					$data = array_merge($data,$productDate);
				}
			}
		}
		$this->tb_goods = $tb_goods;
		$this->condition = $condition;
		$this->data = $data;
		$this->type = IFilter::act(IReq::get('type'));//页面input的type类型，比如radio，checkbox
		$this->redirect('goods_list');
	}
	/**
	 * @brief 获取地区
	 */
	public function area_child()
	{
		$parent_id = intval(IReq::get("aid"));
		$areaDB    = new IModel('areas');
		$data      = $areaDB->query("parent_id=$parent_id",'*','sort','asc');
		echo JSON::encode($data);
	}

    //[公共方法]通过解析products表中的spec_array转化为格式：key:规格名称;value:规格值
    public static function show_spec($specJson)
    {
    	$specArray = JSON::decode($specJson);
    	$spec      = array();

    	foreach($specArray as $val)
    	{
    		if($val['type'] == 1)
    		{
    			$spec[$val['name']] = $val['value'];
    		}
    		else
    		{
    			$spec[$val['name']] = '<img src="'.IUrl::creatUrl().$val['value'].'" class="img_border" style="width:15px;height:15px;" />';
    		}
    	}
    	return $spec;
    }
	/**
	 * @brief 获得配送方式ajax
	 */
	public function order_delivery()
    {
    	$productId    = IFilter::act(IReq::get("productId"),'int');
    	$goodsId      = IFilter::act(IReq::get("goodsId"),'int');
    	$area     = IFilter::act(IReq::get("area"),'int');
    	$distribution = IFilter::act(IReq::get("distribution"),'int');//配送方式
    	$num          = IReq::get("num") ? IFilter::act(IReq::get("num"),'int') : 1;
        $final_sum = IReq::get('final_sum') ? IReq::get('final_sum') : 0;
		
		$data = Delivery::getDelivery($area,$distribution,$goodsId,$productId,$num);
        if($productId)
        {
            $model = new IModel('products');
            $sell_price = $model->getField('id='.$productId, 'sell_price');
            $goodsList[$goodsId]['sum'] = $sell_price * $num;
            
            $countSumObj = new CountSum($this->user['user_id']);
            $groupPrice  = $countSumObj->getGroupPrice($productId,'product');
            if($groupPrice){
                $minPrice = $groupPrice;
            }else{
                $minPrice = $sell_price;
            }
            $minPrice = min($minPrice,$sell_price);
            $goodsList[$goodsId]['reduce'] = $num * ($sell_price - $minPrice);
        }
        if($goodsId)
        {
            $model = new IModel('goods');
            $sell_price = $model->getField('id='.$goodsId, 'sell_price');
            $goodsList[$goodsId]['sum'] = $sell_price * $num;
            
            $countSumObj = new CountSum($this->user['user_id']);
            $groupPrice  = $countSumObj->getGroupPrice($goodsId,'goods');
            if($groupPrice){
                $minPrice = $groupPrice;
            }else{
                $minPrice = $sell_price;
            }
            $minPrice = min($minPrice,$sell_price);
            $goodsList[$goodsId]['reduce'] = $num * ($sell_price - $minPrice);
        }                   
        $group_id     = $countSumObj->getGroupId();
        $proObj = new ProRule($final_sum);
        $proObj->setUserGroup($group_id);
        
        $data['isFreeFreight'] = $proObj->isFreeFreight($area, $goodsList);
    	echo JSON::encode($data);
    }
    /**
     * 根据商家、订单重量算运费价格ajax
     */
   	public function order_delivery_seller(){
   		$weight = IFilter::act(IReq::get('weight'),'int');
   		$seller_id = IFilter::act(IReq::get('seller_id'),'int');
   		$area     = IFilter::act(IReq::get("area"),'int');
   		$distribution = IFilter::act(IReq::get("distribution"),'int');//配送方式
   		$total_price = IFilter::act(IReq::get('total_price'),'int');
   		
   		if($distribution)
   		{
   			$data = Delivery::getDeliveryWeight($area,$distribution,$weight,$seller_id,$total_price);
   		}
   		else echo 0;
   		echo JSON::encode($data);
   	}
    
    /**
     * 计算运费价格ajax
     */
    public function order_delivery_count(){
       $goodsId = IFilter::act(IReq::get('goodsId'),'int');
       $productId = IFilter::act(IReq::get('productId'),'int');
       $area     = IFilter::act(IReq::get("area"),'int');
       $deliveryId = IFilter::act(IReq::get("deliveryId"),'int');//配送方式
       $num = IFilter::act(IReq::get('num'),'int');
       $data = Delivery::getDelivery($area, $deliveryId, $goodsId, $productId, $num);
       if(is_string($data))
       {
           unset($data);
           $data['error'] = 1;
       }
       if($productId)
        {
            $model = new IModel('products');
            $sell_price = $model->getField('id='.$productId, 'sell_price');
            $goodsList[$goodsId]['sum'] = $sell_price * $num;
            
            $countSumObj = new CountSum($this->user['user_id']);
            $groupPrice  = $countSumObj->getGroupPrice($productId,'product');
            if($groupPrice){
                $minPrice = $groupPrice;
            }else{
                $minPrice = $sell_price;
            }
            $minPrice = min($minPrice,$sell_price);
            $goodsList[$goodsId]['reduce'] = $num * ($sell_price - $minPrice);
        }
        if($goodsId)
        {
            $model = new IModel('goods');
            $sell_price = $model->getField('id='.$goodsId, 'sell_price');
            $goodsList[$goodsId]['sum'] = $sell_price * $num;
            
            $countSumObj = new CountSum($this->user['user_id']);
            $groupPrice  = $countSumObj->getGroupPrice($goodsId,'goods');
            if($groupPrice){
                $minPrice = $groupPrice;
            }else{
                $minPrice = $sell_price;
            }
            $minPrice = min($minPrice,$sell_price);
            $goodsList[$goodsId]['reduce'] = $num * ($sell_price - $minPrice);
        } 
       $data['group_id'] = $countSumObj->getGroupId();
       $data['goodsList'] = $goodsList;               
       echo JSON::encode($data);
   }
    
   public function order_delivery_free()
   {
       $area = IFilter::act(IReq::get("area"),'int');
       $final_sum = IReq::get('final_sum') ? IReq::get('final_sum') : 0;
       $group = IFilter::act(IReq::get("group"),'int');
       $goodsList = IReq::get("goodsList");
       $temp = array();
       foreach($goodsList as $v)
       {
           foreach($v as $k => $val)
           {
               $temp[$k]['sum'] = $val['sum'];
               $temp[$k]['reduce'] = $val['reduce'];
           }
       }
       $proObj = new ProRule($final_sum);
       $proObj->setUserGroup($group);
       $data['isFreeFreight'] = $proObj->isFreeFreight($area, $temp);         
       echo JSON::encode($data);
   }  
   
   //验证商家是否有免运费活动
   public function check_delivery()
   {
       $memberObj = new IModel('member');
       $user_group = $memberObj->getField('user_id = '.$this->user['user_id'],'group_id');
       $id = IReq::get('id') ? IFilter::act(IReq::get('id'), 'int') : 0;
       $datetime = ITime::getDateTime();
       $prom = new IModel('promotion');
       $promotion = $prom->getObj('type = 0 and is_close = 0 and start_time <= "'.$datetime.'" and end_time >= "'.$datetime.'" and seller_id = '.$id.' and award_type = 6 and (user_group = "all" or FIND_IN_SET('.$user_group.',user_group))');
       echo JSON::encode($promotion);
   } 
       
    /**
     * 合并付款
     */
    public function dopaymerge(){
    	//print_r($_POST);
    	$order_id_arr = IFilter::act(IReq::get('sub'),'int');
    	$order_ids = implode(',',$order_id_arr);
    	$payment_id = IFilter::act(IReq::get('payment'),'int');
    
    	//获取支付方式类库
    	$paymentInstance = Payment::createPaymentInstance($payment_id);
    	$order_db = new IModel('order');
    	$order_list = $order_db->query('id in ('.$order_ids.')','id');
    	if(empty($order_list))
    		IError::show(403,'要支付的订单信息不存在');
    	
    	$sendData = $paymentInstance->getSendDataMerge(Payment::getPaymentInfoMerge($payment_id,$order_ids),true);
    	 
    	$paymentInstance->doPay($sendData);
    	//print_r($order_ids);
    }
    /**
     * 预售订单支付
     */
    public function doPayPresell(){
    	$order_id   = IFilter::act(IReq::get('order_id'),'int');
    	//$siteConfigObj = new Config('site_config');
    	//$cancel_days = $siteConfigObj->pre_order_cancel_days;
    	$order_db   = new IModel('order');
    	$orderRow = $order_db->getObj('id='.$order_id,'order_no,pay_type,pre_amount');
    	$payment_id = $orderRow['pay_type'];
    	if(empty($orderRow))
    	{
    		IError::show(403,'要支付的订单信息不存在');
    	}
    	
    	//获取支付方式类库exit;
    	$paymentInstance = Payment::createPaymentInstance($payment_id);
    	$sendData = $paymentInstance->getSendData(Payment::getPaymentInfoPresell($payment_id,$order_id));
    	
    	$paymentInstance->doPay($sendData);
    }
    //订单是否已支付
    public function has_pay(){
        $order_id = IFilter::act(IReq::get('order_id'),'int');
    	$pay_level = IFilter::act(IReq::get('pay_level'),'int');
        if($pay_level == 1)
        {
            $order_db = new IModel('order_parent');
        }
        else
        {
    	    $order_db = new IModel('order');
        }
    	if($order_db->getObj('id='.$order_id.' and (type!=4 && pay_status=0 || (type=4 && pay_status=0 || type=4 &&  pay_status=1 ))'))
    	{
    		echo 1;exit;
    	}
    	echo 0;
    }
	/**
    * @brief 【重要】进行支付支付方法
    */
	public function doPay()
	{
		//获得相关参数
		$order_id   = IFilter::act(IReq::get('order_id'),'int');
		$recharge   = IReq::get('recharge');
        $payment_id = IFilter::act(IReq::get('payment_id'),'int');
		$pay_level = IReq::get('pay_level') ? IFilter::act(IReq::get('pay_level'),'int') : 2;
		if($order_id)
		{
			//获取订单信息
            if($pay_level == 1)
            {
			    $orderDB  = new IModel('order_parent');
            }
            else
            {
                $orderDB  = new IModel('order');
            }
			$orderRow = $orderDB->getObj('id = '.$order_id);

			if(empty($orderRow))
			{
				IError::show(403,'要支付的订单信息不存在');
			}
			$payment_id = $orderRow['pay_type'];
		}

		//获取支付方式类库
		$paymentInstance = Payment::createPaymentInstance($payment_id);
        
		//在线充值
		if($recharge !== null)
		{
			if($payment_id==7){//担保交易不能充值
				IError::show(403,'担保交易不能用户充值');
			}
			$recharge   = IFilter::act($recharge,'float');
			$paymentRow = Payment::getPaymentById($payment_id);

			//account:充值金额; paymentName:支付方式名字
			$reData   = array('account' => $recharge , 'paymentName' => $paymentRow['name']);
			$sendData = $paymentInstance->getSendData(Payment::getPaymentInfo($payment_id,'recharge',$reData));
		}
		//订单支付
		else if($order_id)
		{
			$sendData = $paymentInstance->getSendData(Payment::getPaymentInfo($payment_id,'order',$order_id, $pay_level));
		}
		else
		{
			IError::show(403,'发生支付错误');
		}
        $paymentInstance->doPay($sendData);                             
    }
	/**
	 * 合并支付同步回调
	 * 
	 */
	public function callback_merge(){
		//从URL中获取支付方式
		$payment_id      = IFilter::act(IReq::get('_id'),'int');
		$paymentInstance = Payment::createPaymentInstance($payment_id);
		
		if(!is_object($paymentInstance))
		{
			IError::show(403,'支付方式不存在');
		}
		
		//初始化参数
		$money   = '';
		$message = '支付失败';
		$orderNo = '';
		$order_ids = '';
		
		//执行接口回调函数
		$callbackData = array_merge($_POST,$_GET);
		unset($callbackData['controller']);
		unset($callbackData['action']);
		unset($callbackData['_id']);
		$return = $paymentInstance->callbackMerge($callbackData,$payment_id,$money,$message,$orderNo,$order_ids);
		if($return==1){
			$order = new IModel('order');
			$order_list = $order->query('id in ('.$order_ids.')','order_no,type');
			foreach($order_list as $k=>$v){
				if($v['type']!=4){
					$order_id = Order_Class::updateOrderStatus($v['order_no']);
				}
				
				else {
					$order_id = Preorder_Class::updateOrderStatus($v['order_no']);
				}
					
			}
			if($order_id)
			{
				$url  = '/site/success/message/'.urlencode("支付成功");
				$url .= ISafe::get('user_id') ? '/?callback=/ucenter/order' : '';
				$this->redirect($url);
				exit;
			}
			else{
				IError::show(403,'订单修改失败');
			}
		}
		else
		{
			$message = $message ? $message : '支付失败';
			IError::show(403,$message);
		}
	}
	public function server_callback_merge(){
		//从URL中获取支付方式
		$payment_id      = IFilter::act(IReq::get('_id'),'int');
		$paymentInstance = Payment::createPaymentInstance($payment_id);
		
		if(!is_object($paymentInstance))
		{
			IError::show(403,'支付方式不存在');
		}
		
		//初始化参数
		$money   = '';
		$message = '支付失败';
		$orderNo = '';
		$order_ids = '';
		
		//执行接口回调函数
		$callbackData = array_merge($_POST,$_GET);
		unset($callbackData['controller']);
		unset($callbackData['action']);
		unset($callbackData['_id']);
		$return = $paymentInstance->serverCallbackMerge($callbackData,$payment_id,$money,$message,$orderNo,$order_ids);
		if($return==1){
			$order = new IModel('order');
			$order_list = $order->query('id in ('.$order_ids.')','order_no,type');
			foreach($order_list as $k=>$v){
				if($v['type']!=4)
				$order_id = Order_Class::updateOrderStatus($v['order_no']);
				else 
					$order_id = Preorder_Class::updateOrderStatus($v['order_no']);
			}
			if($order_id)
			{
				$paymentInstance->notifyStop();
					exit;
			}
			else{
				IError::show(403,'订单修改失败');
			}
		}
		else
		{
			$paymentInstance->notifyStop();
			exit;
		}
	}
	/**
     * @brief 【重要】支付回调[同步]
	 */
	public function callback()
	{
		//从URL中获取支付方式
		$payment_id      = IFilter::act(IReq::get('_id'),'int');
		$paymentInstance = Payment::createPaymentInstance($payment_id);

		if(!is_object($paymentInstance))
		{
			IError::show(403,'支付方式不存在');
		}

		//初始化参数
		$money   = '';
		$message = '支付失败';
		$orderNo = '';

		//执行接口回调函数
		$callbackData = array_merge($_POST,$_GET);
		unset($callbackData['controller']);
		unset($callbackData['action']);
		unset($callbackData['_id']);
		$return = $paymentInstance->callback($callbackData,$payment_id,$money,$message,$orderNo);
		//支付成功
		if($return && !is_array($return))
		{
			//充值方式
			if(stripos($orderNo,'recharge') !== false)
			{
				$recharge_no = str_replace('recharge','',$orderNo);
				if(payment::updateRecharge($recharge_no))
				{
					$this->redirect('/site/success/message/'.urlencode("充值成功").'/?callback=/ucenter/account_log');
					exit;
				}
				IError::show(403,'充值失败');
			}
		}
        elseif(is_array($return))
        {
            if(stripos($orderNo,'pre') !== false || stripos($orderNo,'wei') !== false)
            {
                $order_id = Preorder_Class::updateOrderStatus($orderNo);
                if($order_id)
                {
                    $url  = '/site/success/message/'.urlencode("支付成功");
                    if(IClient::getDevice()=='mobile'){
                        $url .= ISafe::get('user_id') ? '/?callback=/ucenter/order' : '';
                    }
                    else{
                        $url .= ISafe::get('user_id') ? '/?callback=/ucenter/order_detail/id/'.$order_id : '';
                    }
                    
                    $this->redirect($url);
                    exit;
                }
                IError::show(403,'订单修改失败');
            }
            else{
                $order_id = Order_Class::updateOrderStatus($orderNo, '', '', $return['pay_level']);
                if($order_id && !is_array($order_id))
                {
                    $url  = '/site/success/message/'.urlencode("支付成功");
                    if(IClient::getDevice()=='mobile'){
                        $url .= ISafe::get('user_id') ? '/?callback=/ucenter/order' : '';
                    }
                    else{
                        $url .= ISafe::get('user_id') ? '/?callback=/ucenter/order_detail/id/'.$order_id : '';
                    }
                    $this->redirect($url);
                    exit;
                }
                elseif(is_array($order_id))
                {
                    $url  = '/site/success/message/'.urlencode("支付成功");
                    $url .= ISafe::get('user_id') ? '/?callback=/ucenter/order' : '';
                    $this->redirect($url);
                    exit;
                }
                IError::show(403,'订单修改失败');
            }
        }
		//支付失败
		else
		{
			$message = $message ? $message : '支付失败';
			IError::show(403,$message);
		}
	}
    

	/**
     * @brief 【重要】支付回调[异步]
	 */
	function server_callback()
	{
        
		//从URL中获取支付方式
		$payment_id      = IFilter::act(IReq::get('_id'),'int');
		$paymentInstance = Payment::createPaymentInstance($payment_id);

		if(!is_object($paymentInstance))
		{
			die('fail');
		}

		//初始化参数
		$money   = '';
		$message = '支付失败';
		$orderNo = '';

		//执行接口回调函数
		$callbackData = array_merge($_POST,$_GET);
		unset($callbackData['controller']);
		unset($callbackData['action']);
		unset($callbackData['_id']);
		$return = $paymentInstance->serverCallback($callbackData,$payment_id,$money,$message,$orderNo);
 
		
		//支付成功
		if($return && !is_array($return))
		{
			
			//充值方式
			if(stripos($orderNo,'recharge') !== false)
			{
				$recharge_no = str_replace('recharge','',$orderNo);
				if(payment::updateRecharge($recharge_no))
				{
					$paymentInstance->notifyStop();
					exit;
				}
			}
        }
        elseif(is_array($return))
        {
			if(stripos($orderNo,'pre') !== false || stripos($orderNo,'wei') !== false)
			{
				$order_id = Preorder_Class::updateOrderStatus($orderNo);
				if($order_id)
				{
					$paymentInstance->notifyStop();
					exit;
				}
				IError::show(403,'订单修改失败');
			}
			else
			{
				
				$order_id = Order_Class::updateOrderStatus($orderNo, '', '', $return['pay_level']);
				if($order_id)
				{
					$paymentInstance->notifyStop();
					exit;
				}
			}
			
		}
		//支付失败
		else
		{
			$paymentInstance->notifyStop();
			exit;
		}
	}
    
    //微信支付成功回调函数
    public function wecheat_callback()
    {
        $paymentInstance = Payment::createPaymentInstance(13);

        if(!is_object($paymentInstance))
        {
            IError::show(403,'支付方式不存在');
        }
        $orderNo = IReq::get('oId');
        $pay_level = IReq::get('pay_level');
        $tradeNo = IReq::get('id');
        $pay_total = IReq::get('pay_total');
        $paymentInstance->recordTN($orderNo,$tradeNo,$pay_level);
        $resArr = array(
                'order_no'     => $orderNo,
                'trade_no'     => $tradeNo,
                'trade_type'   => 1,
                'money'        => $pay_total/100,
                'pay_type'     => 13,
                'trade_status' => 1,
                'time'         => date('Y-m-d H:i:s')
            );
        $paymentInstance->addTradeData($resArr);
        $msg = array();
        
        //充值方式
        if(stripos($orderNo,'recharge') !== false)
        {
            $recharge_no = str_replace('recharge','',$orderNo);
            if(payment::updateRecharge($recharge_no))
            {
                $msg['status'] = 1;
                $msg['url'] = IUrl::getHost().IUrl::creatUrl('/site/success/message/'.urlencode("充值成功").'/?callback=/ucenter/account_log');
            }
            else
            {
                $msg['status'] = 0;
                $msg['msg'] = '充值失败';
            }
        }
        elseif(stripos($orderNo,'pre') !== false || stripos($orderNo,'wei') !== false)
        {
            $order_id = Preorder_Class::updateOrderStatus($orderNo);
            if($order_id)
            {
                $url  = IUrl::getHost().IUrl::creatUrl('/site/success/message/'.urlencode("支付成功"));
                if(IClient::getDevice()=='mobile'){
                    $url .= ISafe::get('user_id') ? '/?callback=/ucenter/order' : '';
                }
                else{
                    $url .= ISafe::get('user_id') ? '/?callback=/ucenter/order_detail/id/'.$order_id : '';
                }
                
                $msg['status'] = 1;
                $msg['url'] = $url;
            }
            else
            {
                $msg['status'] = 0;
                $msg['msg'] = '充值失败';
            }
        }
        else{
            $order_id = Order_Class::updateOrderStatus($orderNo, '', '', $pay_level);
            if($order_id && !is_array($order_id))
            {
                $url  = IUrl::getHost().IUrl::creatUrl('/site/success/message/'.urlencode("支付成功"));
                if(IClient::getDevice()=='mobile'){
                    $url .= ISafe::get('user_id') ? '/?callback=/ucenter/order' : '';
                }
                else{
                    $url .= ISafe::get('user_id') ? '/?callback=/ucenter/order_detail/id/'.$order_id : '';
                }
                $msg['status'] = 1;
                $msg['url'] = $url;
            }
            elseif(is_array($order_id))
            {
                $url  = IUrl::getHost().IUrl::creatUrl('/site/success/message/'.urlencode("支付成功"));
                $url .= ISafe::get('user_id') ? '/?callback=/ucenter/order' : '';
                $msg['status'] = 1;
                $msg['url'] = $url;
            }
            else
            {
                $msg['status'] = 0;
                $msg['msg'] = '充值失败';
            }
        }
        echo JSON::encode($msg);
    }

	/**
     * @brief 【重要】支付中断处理
	 */
	public function merchant_callback()
	{
		$this->redirect('/site/index');
	}
	/**
	 * 退款异步回调
	 */
	public function server_callback_refund(){
// 		$m = new IModel('ceshi');
// 		$m->setData(array('value'=>'987654','time'=>ITime::getDateTime()));
// 		$m->add();
		//从URL中获取支付方式
		$payment_id      = IFilter::act(IReq::get('_id'),'int');
		$paymentInstance = Payment::createPaymentInstance($payment_id);
		
		if(!is_object($paymentInstance))
		{
			die('fail');
		}
		
		//初始化参数
		$money   = '';
		$message = '退款失败';
		$orderNo = '';
		
		//执行接口回调函数
		$callbackData = array_merge($_POST,$_GET);
		unset($callbackData['controller']);
		unset($callbackData['action']);
		unset($callbackData['_id']);
		$return = $paymentInstance->serverCallback($callbackData,$payment_id,$money,$message,$orderNo);
		$paymentInstance->notifyStop();
		exit();
		
	}
	/**
    * @brief 根据省份名称查询相应的privice
    */
	public function searchPrivice()
	{
		$province = IFilter::act(IReq::get('province'));

		$tb_areas = new IModel('areas');
		$areas_info = $tb_areas->getObj('parent_id = 0 and area_name like "%'.$province.'%"','area_id');
		$result = array('flag' => 'fail','area_id' => 0);
		if($areas_info)
		{
			$result = array('flag' => 'success','area_id' => $areas_info['area_id']);
		}
		echo JSON::encode($result);
	}
    //添加实体代金券
    function add_download_ticket()
    {
    	$isError = true;

    	$ticket_num = IFilter::act(IReq::get('ticket_num'));
        $ticket_pwd = IFilter::act(IReq::get('ticket_pwd'));
    	$final_num = IFilter::act(IReq::get('num'));

    	$propObj = new IModel('prop');
    	$propRow = $propObj->getObj('card_name = "'.$ticket_num.'" and card_pwd = "'.$ticket_pwd.'" and type = 0 and is_userd = 0 and is_send = 1 and is_close = 0 and NOW() between start_time and end_time');

    	if(empty($propRow))
    	{
    		$message = '代金券不可用，请确认代金券的卡号密码并且此代金券从未被使用过';
    	}
    	else
    	{
            if($propRow['ticket_condition'] && $propRow['ticket_condition'] > $final_num)
            {
                $message = '消费达到'.$propRow['ticket_condition'].'才能使用该代金券';
            }
            else
            {
                //登录用户
                if($this->user['user_id'])
                {
                    $memberObj = new IModel('member');
                    $memberRow = $memberObj->getObj('user_id = '.$this->user['user_id'],'prop');
                    if(stripos($memberRow['prop'],','.$propRow['id'].',') !== false)
                    {
                        $message = '代金券已经存在，不能重复添加';
                    }
                    else
                    {
                        $isError = false;
                        $message = '添加成功';

                        if($memberRow['prop'] == '')
                        {
                            $propUpdate = ','.$propRow['id'].',';
                        }
                        else
                        {
                            $propUpdate = $memberRow['prop'].$propRow['id'].',';
                        }

                        $dataArray = array('prop' => $propUpdate);
                        $memberObj->setData($dataArray);
                        $memberObj->update('user_id = '.$this->user['user_id']);
                    }
                }
                //游客方式
                else
                {
                    $isError = false;
                    $message = '添加成功';
                    ISafe::set("ticket_".$propRow['id'],$propRow['id']);
                }
            }
    	}

    	$result = array(
    		'isError' => $isError,
    		'data'    => $propRow,
    		'message' => $message,
    	);

    	echo JSON::encode($result);
    }

	private function alert($msg)
	{
		header('Content-type: text/html; charset=UTF-8');
		echo JSON::encode(array('error' => 1, 'message' => $msg));
		exit;
	}
    /**
     * 筛选用户
     */
    public function filter_user()
    {
		$where   = '';
		$userIds = '';
    	$search  = IFilter::act(IReq::get('search'),'strict');

    	foreach($search as $key => $val)
    	{
    		if($val)
    		{
    			$where .= $key.'"'.$val.'" and ';
    		}
    	}
		$where .= '1';
    	//有筛选条件
    	if($where)
    	{
	    	$userDB = new IQuery('user as u');
	    	$userDB->join  = 'left join member as m on u.id = m.user_id';
	    	$userDB->fields= 'u.id';
	    	$userDB->where = $where;
	    	$userData      = $userDB->find();
	    	$tempArray     = array();
	    	foreach($userData as $key => $item)
	    	{
	    		$tempArray[] = $item['id'];
	    	}
	    	$userIds = join(',',$tempArray);

	    	if(!$userIds)
	    	{
	    		die('<script type="text/javascript">alert("没有找到用户信息,请重新输入条件");window.history.go(-1);</script>');
	    	}
    	}

    	die('<script type="text/javascript">parent.searchUserCallback("'.$userIds.'");</script>');
    }
	/*
	 * @breif ajax添加商品扩展属性
	 * */
	function attribute_init()
	{
		$id = IFilter::act(IReq::get('model_id'),'int');
		$tb_attribute = new IModel('attribute');
		$attribute_info = $tb_attribute->query('model_id='.$id);
		echo JSON::encode($attribute_info);
	}

	//获取商品数据
	public function getGoodsData()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		$productDB = new IQuery('products as p');
		$productDB->join  = 'left join goods as go on go.id = p.goods_id';
		$productDB->where = 'go.id = '.$id;
		$productDB->fields= 'p.*,go.name';
		$productData = $productDB->find();

		if(!$productData)
		{
			$goodsDB   = new IModel('goods');
			$productData = $goodsDB->query('id = '.$id);
		}
		echo JSON::encode($productData);
	}

	//获取商品的推荐标签数据
	public function goodsCommend()
	{
		//商品字符串的逗号间隔
		$id = IFilter::act(IReq::get('id'));
		if($id)
		{
			$idArray = explode(",",$id);
			$idArray = IFilter::act($idArray,'int');
			$id = join(',',$idArray);
		}

		$goodsDB = new IModel('goods');
		$goodsData = $goodsDB->query("id in (".$id.")","id,name");

		$goodsCommendDB = new IModel('commend_goods');
		foreach($goodsData as $key => $val)
		{
			$goodsCommendData = $goodsCommendDB->query("goods_id = ".$val['id']);
			foreach($goodsCommendData as $k => $v)
			{
				$goodsData[$key]['commend'][$v['commend_id']] = 1;
			}
		}
		die(JSON::encode($goodsData));
	}

	//获取自提点数据
	public function getTakeselfList()
	{
		$id   = IFilter::act(IReq::get('id'),'int');
		$type = IFilter::act(IReq::get('type'));
		$takeselfObj = new IQuery('takeself');         
		switch($type)
		{
			case "province":
			{
				$where = "province = ".$id;
				$takeselfObj->group = 'city';
			}
			break;

			case "city":
			{
				$where = "city = ".$id;
				$takeselfObj->group = 'area';
			}
			break;

			case "area":
			{
				$where = "area = ".$id;
			}
			break;

			case "place":
			{
				$where = "id = ".$id;
			}
			break;
		}

        if(isset($where))
        {
              $takeselfObj->where = $where;
        }  
		$takeselfList = $takeselfObj->find();

		foreach($takeselfList as $key => $val)
		{
			$temp = area::name($val['province'],$val['city'],$val['area']);
			$takeselfList[$key]['province_str'] = $temp[$val['province']];
			$takeselfList[$key]['city_str']     = $temp[$val['city']];
			$takeselfList[$key]['area_str']     = $temp[$val['area']];
		}
		die(JSON::encode($takeselfList));
	}

	//自添
	/*
	 * 通过自提点id获取数据，省市转为中文名
	 */
	public function getOneTakeself(){
		$id   = IFilter::act(IReq::get('id'),'int');
		$takeSelf = new IModel('takeself');
		$take = $takeSelf->getObj('id='.$id);
		$area = area::name($take['province'],$take['city'],$take['area']);
		$take['province_str'] = $area[$take['province']];
		$take['city_str'] = $area[$take['city']];
		$take['area_str'] = $area[$take['area']];
		echo JSON::encode($take);
		
	}
	//物流轨迹查询
	public function freight()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$tb_freight = new IQuery('delivery_doc as d');
			$tb_freight->join  = 'left join freight_company as f on f.id = d.freight_id';
			$tb_freight->where = 'd.id = '.$id;
			$tb_freight->fields= 'd.*,f.freight_type';
			$freightData = $tb_freight->find();

			if($freightData)
			{
				$freightData = current($freightData);
				if($freightData['freight_type'] && $freightData['delivery_code'])
				{
					$result = freight_facade::line($freightData['freight_type'],$freightData['delivery_code']);
					if($result['result'] == 'success')
					{
						$this->data = $result['data'];
						$this->redirect('freight');
						exit;
					}
					else
					{
						die(isset($result['reason']) ? $result['reason'] : '物流接口发生错误');
					}
				}
				else
				{
					die('缺少物流信息');
				}
			}
		}
		die('启奏皇上，您所下单御品，微臣已命物流锦衣卫护送【山城速购】');
	}

	//微信API接口地址
	public function wechat()
	{
		$result = wechat_facade::response();
	}
	/**获取联想关键字
	 * 
	 */
	public function getLikeWords(){
		$word = IFilter::act(IReq::get('word'),'strict');
		$sear = new IModel('keyword');
		$data = $sear->query('word like "'.$word.'%"','word as keyword','goods_nums', 'DESC',10);
		echo JSON::encode($data);
	}
    
    public function showCatAd()
    {
        $id = IReq::get('id') ? IReq::get('id') : 0;         
        echo JSON::encode(ad::show('导航右侧', $id, 0, true));
    }
}