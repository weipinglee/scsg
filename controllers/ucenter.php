<?php
/**
 * @brief 用户中心模块
 * @class Ucenter
 * @note  前台
 */
class Ucenter extends IController
{
	public $layout = 'ucenter';

	public function init()
	{
		CheckRights::checkUserRights();

		if(!$this->user)
		{
			$this->redirect('/simple/login');
		}
	}
	
    public function index()
    {
    	$userid = $this->user['user_id'];
    	$where = "o.user_id =".$userid." and if_del= 0 and o.type !=4 ";
    	
    	$order_db = new IQuery('order as o');
    	$order_db->join = 'left join order_goods as og on o.id=og.order_id ';
    	$order_db->group = 'og.order_id';
    	$order_db->where = $where?$where : 1;
    	$order_db->limit  = 6;
    	$order_db->order = 'o.id DESC';
    	$order_db->fields = 'o.*';
    	$this->order_db = $order_db;
        $this->initPayment();
        $this->redirect('index');
    }

	//[用户头像]上传
	function user_ico_upload()
	{
		$result = array(
			'isError' => true,
		);

		if(isset($_FILES['attach']['name']) && $_FILES['attach']['name'] != '')
		{
			$photoObj = new PhotoUpload();
			$photo    = $photoObj->run();

			if($photo['attach']['img'])
			{
				$user_id   = $this->user['user_id'];
				$user_obj  = new IModel('user');
				$dataArray = array(
					'head_ico' => $photo['attach']['img'],
				);
				$user_obj->setData($dataArray);
				$where  = 'id = '.$user_id;
				$isSuss = $user_obj->update($where);

				if($isSuss !== false)
				{
					$result['isError'] = false;
					$result['data'] = IUrl::creatUrl().$photo['attach']['img'];
					ISafe::set('head_ico',$dataArray['head_ico']);
				}
				else
				{
					$result['message'] = '上传失败';
				}
			}
			else
			{
				$result['message'] = '上传失败';
			}
		}
		else
		{
			$result['message'] = '请选择图片';
		}
		echo '<script type="text/javascript">parent.callback_user_ico('.JSON::encode($result).');</script>';
	}

    /**
     * @brief 我的订单列表
     */
    public function order()
    {
    	if(IClient::getDevice()=='pc'){
    	
	    	$chg_time = 7*24*3600;//完成订单后换货期限
	    	$userid = $this->user['user_id'];
	    	$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
	    	$status_array = array(
	    		'1' => array('o.status'=>'=1','pay_status'=>'=0'),//等待付款
	    		'2' => array('o.status'=>'in (3,4) '),//取消订单
	    		'3' => array(//等待发货
	    				array('o.status'=>'=1','pay_type'=>'=0','distribution_status'=>'=0'),
	    				array('o.status'=>'=2','distribution_status'=>'=0')
	    		),
	    		'4' => array(//已发货
	    				array('o.status'=>'in (2,8) ','distribution_status'=>'=1'),
	    				array('o.status'=>'=1','pay_type'=>'=0','distribution_status'=>'=1'),
	    		),
	    		'5' => array(
	    				array('o.status'=>'=1','pay_type'=>'=0','distribution_status'=>'=2'),
	    				//array('status'=>'=7','distribution_status'=>'in (0,2) ')
	    		),
	    		'6' => array(
	    			'o.status'=>'=5'
	    		),
	//     		'7' => array(
	//     			'status' => '=6'
	//     		),
	//     		'8' => array(
	//     				'status'=>'=7','distribution_status'=>'=1'
	//     		)
	    	);
	    	$status_str = $seller_str = '';
	    	$order_no = IFilter::act(IReq::get('order_no'));
	    	$status = IFilter::act(IReq::get('status'),'int');
	    	$beginTime = IFilter::act(IReq::get('beginTime'));
	    	$endTime = IFilter::act(IReq::get('endTime'));
	    	$seller_id = IFilter::act(IReq::get('seller_id'),'int');
	    	if($seller_id){
	    		if($seller_id==2){
	    			$seller_str = ' g.seller_id=0 ';
	    		}else if($seller_id==1){
	    			$seller_str = ' g.seller_id!=0 ';
	    		}
	    	}
	    	if($status && isset($status_array[$status])){
	    		$status_arr = $status_array[$status];
	    		foreach($status_arr as $key=>$val){
	    			if(is_array($val)){
	    				foreach($val as $k=>$v){
	    					$status_str .= $k.' '.$v.' and ';
	    				}
	    				$status_str=substr($status_str,0,-4);
	    				$status_str .= ' OR  ';
	    				
	    			}else{
	    				$status_str .= $key.' '.$val.' and ';
	    			}
	    			
	    		}
	    		$status_str=' ('.substr($status_str,0,-4).') ';
	    	}
	    	$where = "o.user_id =".$userid." and o.if_del= 0 and o.type !=4 ";
	    	$where .= $status_str ? ' and '.$status_str : '';
	    	$where .= $seller_str ? ' and '.$seller_str : '';
	  		 if($beginTime)
			{
				$where .= ' and o.create_time > "'.$beginTime.'"';
			}
			if($endTime)
			{
				$where .= ' and o.create_time < "'.$endTime.'"';
			}
			if($order_no)$where .= ' and o.order_no='.$order_no;
			$order_db = new IQuery('order as o');
			$order_db->join = 'left join order_goods as og on o.id=og.order_id left join goods as g on og.goods_id=g.id left join comment as c on og.comment_id=c.id';
			$order_db->group = 'og.id';
			$order_db->where = $where?$where : 1;
			$order_db->page  = $page;
			$order_db->order = 'o.id DESC';
			$order_db->fields = 'o.*,IF(o.status=5 && TIMESTAMPDIFF(second,o.completion_time,NOW())<'.$chg_time.',1,0) as can_chg,c.status as comment_status,og.id as og_id,og.img,og.goods_id,og.product_id,og.real_price,og.goods_nums,og.goods_array,og.is_send,og.comment_id,og.refunds_status';
			$this->order_db = $order_db;
			//print_r($order_db->find());
	        $this->initPayment();
	        $data['s_beginTime'] = $beginTime;
	        $data['s_endTime'] = $endTime;
	        $data['s_order_no'] = $order_no;
	        $data['s_status'] = $status;
	        $data['s_seller_id'] = $seller_id;
	        $this->setRenderData($data);
	        
    	}
    	$this->redirect('order');
    }
    /**
     * @brief 初始化支付方式
     */
    private function initPayment()
    {
        $payment = new IQuery('payment');
        $payment->fields = 'id,name,type';
        $payments = $payment->find();
        $items = array();
        foreach($payments as $pay)
        {
            $items[$pay['id']]['name'] = $pay['name'];
            $items[$pay['id']]['type'] = $pay['type'];
        }
        $this->payments = $items;
    }
    /**
     * @brief 订单详情
     * @return String
     */
    public function order_detail()
    {
        $id = IFilter::act(IReq::get('id'),'int');

        $orderObj = new order_class();
        $this->order_info = $orderObj->getOrderShow($id,$this->user['user_id']);
        if($this->order_info['type']==4)$this->redirect('preorder_detail/id/'.$this->order_info['id']);
		$this->fapiao_data = array();
		if($this->order_info['invoice']==1){
			$fapiao_db = new IModel('order_fapiao');
			$this->fapiao_data = $fapiao_db->getObj('order_id='.$id);
		}
        if(!$this->order_info)
        {
        	IError::show(403,'订单信息不存在');
        }
        
        
        //获取退货信息
        $siteConfig = new Config('site_config');
        $refunds_seller_time=isset($siteConfig->refunds_seller_time) ? intval($siteConfig['refunds_limit_time']) : 7;
        	
        //获取退款数据
        $refunds_seller_second = $refunds_seller_time*24*3600;
        $tb_refundment = new IQuery('refundment_doc as r');
        $tb_refundment->join = 'left join order_goods as og on r.order_id = og.order_id and r.goods_id=og.goods_id and r.product_id=og.product_id';
        $tb_refundment->where = 'r.if_del=0 and r.order_id ='.$id;
        $tb_refundment->fields = 'og.is_send,og.goods_array,r.*,UNIX_TIMESTAMP(r.time)+'.$refunds_seller_second.'- UNIX_TIMESTAMP(now()) as end_time';
        $tb_refundment->order = 'r.id DESC';
        $tb_refundment->group = 'r.id';
        $this->refunds = $tb_refundment->find();
       
        //获取商品信息
        $tb_order_goods = new IQuery('order_goods as og');
        $tb_order_goods->join = 'left join goods as g on og.goods_id=g.id';
        $tb_order_goods->where = 'og.order_id='.$id;
        $tb_order_goods->group = 'og.id';
        $tb_order_goods->fields = 'g.sell_price,g.point,og.is_send,og.real_price,og.refunds_status,og.id as og_id,og.goods_id,og.img,og.goods_array,og.goods_nums';
        $this->og_data = $tb_order_goods->find();
       	$this->redirect('order_detail',false);
    }
	
    /**
     * @brief 订单详情
     * @return String
     */
    public function preorder_detail()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
    	$presell_db = new IModel('presell');
    	$wei_status = 0;
    	$orderObj = new preorder_class();
    	$this->order_info = $orderObj->getOrderShow($id,$this->user['user_id']);
    	$presellData = $presell_db->getObj('id='.$this->order_info['active_id']);
    	$now = time();
    	if($this->order_info['invoice']==1){
    		$fapiao_db = new IModel('order_fapiao');
    		$this->fapiao_data = $fapiao_db->getObj('order_id='.$id);
    	}
    	if($presellData['wei_type']==1)
    	{
    		$start = strtotime($presellData['wei_start_time']);
    		$end   = strtotime($presellData['wei_end_time']);
    		if($now<$start)
    			$this->wei_status = 0;//不可支付
    		else if($now>=$start&&$now<=$end){
    			$this->wei_status = 1;//可以支付
    		}
    		else{
    			$this->wei_status = 2;//已过期
    		}
    		$this->start = $start;
    		$this->end   = $end;
    	}
    	else {
    		$this->end = strtotime($this->order_info['pay_time']) + $presellData['wei_days']*24*3600;
    		$this->wei_status = $now>$this->end ? 2 : 1;
    	}
    	
    	if(!$this->order_info)
    	{
    		IError::show(403,'订单信息不存在');
    	}
    	$this->setRenderData($presellData);
    	$this->redirect('preorder_detail');
    }
    
    //操作订单状态
	public function order_status()
	{
		$op    = IFilter::act(IReq::get('op'));
		$id    = IFilter::act( IReq::get('order_id'),'int' );
		$model = new IModel('order');

		switch($op)
		{
			case "cancel":
			{
				$model->setData(array('status' => 3));
				if($model->update("id = ".$id." and distribution_status = 0 and status = 1 and user_id = ".$this->user['user_id']))
				{
					//修改红包状态
					$prop_obj = $model->getObj('id='.$id,'prop');
					$prop_id = isset($prop_obj['prop'])?$prop_obj['prop']:'';
					if($prop_id != '')
					{
						$prop = new IModel('prop');
						$prop->setData(array('is_close'=>0));
						$prop->update('id='.$prop_id);
					}
				}
			}
			break;

			case "confirm":
			{
				$model->setData(array('status' => 5,'completion_time' => date('Y-m-d h:i:s')));
				if($model->update("id = ".$id."  and user_id = ".$this->user['user_id']))
				{
					$orderRow = $model->getObj('id = '.$id);

					//确认收货后进行支付
					Order_Class::updateOrderStatus($orderRow['order_no']);

		    		//增加用户评论商品机会
		    		Order_Class::addGoodsCommentChange($id);
		    		
		    		//经验值、积分、代金券发放
		    		Order_Class::sendGift($id,$this->user['user_id']);
					
					$this->redirect('/ucenter/user_group_chg?callback=/ucenter/evaluation');
					

		    		//确认收货以后直接跳转到评论页面
		    		//$this->redirect('evaluation');
				}
			}
			break;
		}
		$this->redirect("order");
	}
	/**
	*更改用户等级
	*/
	public function user_group_chg(){
		$callBack = IReq::get('callback');
		$user_id = $this->user['user_id'];
		$member_db = new IQuery('member as m');
		$member_db->join = 'left join user_group as g  on m.exp >= g.minexp and m.exp <=g.maxexp';
		$member_db->where = 'm.user_id='.$user_id;
		$member_db->fields = 'm.exp,g.id as gid';
		$member_db->limit = 1;
		$member_data = $member_db->find();
		if(!empty($member_data) && isset($member_data[0]['gid']) && $member_data[0]['gid']){
			$group_id = $member_data[0]['gid'];
			$memberDB = new IModel('member');
			$memberDB->setData(array('group_id'=>$group_id));
			$memberDB->update('user_id='.$user_id);
		}
		if($callBack)
		$this->redirect($callBack);
		else $this->redirect('index');
	}
    /**
     * @brief 我的地址
     */
    public function address()
    {
		//取得自己的地址
		$query = new IQuery('address');
        $query->where = 'user_id = '.$this->user['user_id'];
		$address = $query->find();
		$areas   = array();

		if($address)
		{
			foreach($address as $ad)
			{
				$temp = area::name($ad['province'],$ad['city'],$ad['area']);
				if(isset($temp[$ad['province']]) && isset($temp[$ad['city']]) && isset($temp[$ad['area']]))
				{
					$areas[$ad['province']] = $temp[$ad['province']];
					$areas[$ad['city']]     = $temp[$ad['city']];
					$areas[$ad['area']]     = $temp[$ad['area']];
				}
			}
		}

		$this->areas = $areas;
		$this->address = $address;
        $this->redirect('address');
    }
    /**
     * @brief 收货地址管理
     */
	public function address_edit()
	{
		$id = intval(IReq::get('id'));
		$accept_name = IFilter::act(IReq::get('accept_name'));
		$province = intval(IReq::get('province'));
		$city = intval(IReq::get('city'));
		$area = intval(IReq::get('area'));
		$address = IFilter::act(IReq::get('address'));
		$zip = IFilter::act(IReq::get('zip'));
		$telphone = IFilter::act(IReq::get('telphone'));
		$mobile = IFilter::act(IReq::get('mobile'));
		$default = IReq::get('default')!= 1 ? 0 : 1;
        $user_id = $this->user['user_id'];

		$model = new IModel('address');
		$data = array('user_id'=>$user_id,'accept_name'=>$accept_name,'province'=>$province,'city'=>$city,'area'=>$area,'address'=>$address,'zip'=>$zip,'telphone'=>$telphone,'mobile'=>$mobile,'default'=>$default);

        //如果设置为首选地址则把其余的都取消首选
        if($default==1)
        {
            $model->setData(array('default'=>0));
            $model->update("user_id = ".$this->user['user_id']);
        }

		$model->setData($data);

		if($id == '')
		{
			$model->add();
		}
		else
		{
			$model->update('id = '.$id);
		}
		$this->redirect('address');
	}
	//添加地址ajax
	function address_add()
	{
		$id          = IFilter::act(IReq::get('add_id'),'int');
		$accept_name = IFilter::act(IReq::get('accept_name'));
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
		$address     = IFilter::act(IReq::get('address'));
		$zip         = IFilter::act(IReq::get('zip'));
		//$telphone    = IFilter::act(IReq::get('telphone'));
		$mobile      = IFilter::act(IReq::get('mobile'));
        $user_id     = $this->user['user_id'];

        if(!$user_id)
        {
        	die(JSON::encode(array('errCode' => 2)));
        }
		if(!$accept_name){
			die(JSON::encode(array('errCode' => 1,'field'=>'accept_name')));
		}
		if(!$address){
			die(JSON::encode(array('errCode' => 1,'field'=>'address')));
		}
		if(!IValidate::phone($mobile))
    	{
    		die(JSON::encode(array('errCode' => 1,'field'=>'mobile')));
    	}
		if(!IValidate::zip($zip))
    	{
    		die(JSON::encode(array('errCode' => 1,'field'=>'zip')));
    	}
		
		
		//整合的数据，检查数据库中是否存在此收货地址
        $sqlData = array(
        	'user_id'     => $user_id,
        	'accept_name' => $accept_name,
        	'zip'         => $zip,
        	'province'    => $province,
        	'city'        => $city,
        	'area'        => $area,
        	'address'     => $address,
        	'mobile'      => $mobile,
			'default'     => 1
        );
        $model       = new IModel('address');
		
		//将所有收货信息设为非默认
		$model->setData(array('default'=>0));
		$model->update('user_id='.$user_id);

		//执行insert
		$model->setData($sqlData);
		if(!$id)
			$res=$model->add();
		else 
			$res=$model->update('id='.$id.' and user_id='.$user_id);
		
		if($res){
			die(JSON::encode(array('errCode' => 0)));
		}
		else die(JSON::encode(array('errCode' => 3)));
	}
    /**
     * @brief 收货地址删除处理
     */
	public function address_del()
	{
		$id = IFilter::act( IReq::get('id'),'int' );
		$model = new IModel('address');
		$model->del('id = '.$id.' and user_id = '.$this->user['user_id']);
		$this->redirect('address');
	}
    /**
     * @brief 设置默认的收货地址
     */
    public function address_default()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $default = IFilter::string(IReq::get('default'));
        $model = new IModel('address');
        if($default == 1)
        {
            $model->setData(array('default'=>0));
            $model->update("user_id = ".$this->user['user_id']);
        }
        $model->setData(array('default'=>$default));
        $model->update("id = ".$id." and user_id = ".$this->user['user_id']);
        $this->redirect('address');
    }
    /**
     * @brief 设置默认的收货地址(异步获取）
     */
    public function address_default_ajax()
    {
    	$id = IFilter::act( IReq::get('id'),'int' );
    	$model = new IModel('address');
    	
    	$model->setData(array('default'=>0));
    	$model->update("user_id = ".$this->user['user_id']);
    	
    	$model->setData(array('default'=>1));
    	if($model->update("id = ".$id." and user_id = ".$this->user['user_id']))
    		echo 1;
    	else echo 0;
    	
    }
    /**
     * @brief 退款申请页面,（包括换货）
     */
    public function refunds_update()
    {
        $order_goods_id = IFilter::act( IReq::get('order_goods_id'),'int' );
        $user_id        = $this->user['user_id'];
        $type           = IFilter::act(IReq::get('type'),'int');
        $content        = IFilter::act(IReq::get('content'),'text');
        $message        = '请完整填写内容';
        $delivery_com   = IFilter::act(IReq::get('delivery_com'));
        $delivery_code  = IFilter::act(IReq::get('delivery_code'));

        if(!$content)
        {
	        $this->redirect('refunds',false);
	        Util::showMessage($message);
        }

        $orderDB = new IModel('order');
        $goodsOrderDB = new IModel('order_goods');
        
        $goodsOrderRow = $goodsOrderDB->getObj('id = '.$order_goods_id);
        $orderRow = array();
        if($goodsOrderRow){
        	$order_id = $goodsOrderRow['order_id'];
        	$orderRow = $orderDB->getObj("id = ".$order_id." and user_id = ".$user_id);
        }
       

        //判断订单是否付款（已付款且非退款）
        
        if($orderRow )
        {
        		if($type==0){//判断是否可退货
        			if(!Refunds_Class::order_goods_refunds(array_merge($orderRow,$goodsOrderRow))){
        				IError::show(403,'该商品不可退款');
        			}
        		}
        		else if($type==1){//判断是否可换货
        			if(!Refunds_Class::order_goods_chg(array_merge($orderRow,$goodsOrderRow))){
        				IError::show(403,'该商品不可更换');
        			}
        		}
        		//退款单数据
        		$refundsDB = new IModel('refundment_doc');
        		$updateData = array(
					'order_no' => $orderRow['order_no'],
        			'refunds_no' => Order_Class::createOrderNum(),
					'order_id' => $order_id,
					'user_id'  => $user_id,
        			'type'     => $type,
					'time'     => ITime::getDateTime(),
					'content'  => $content,
					'goods_id' => $goodsOrderRow['goods_id'],
					'product_id' => $goodsOrderRow['product_id'],
        			'delivery_com'=> $delivery_com,
        			'delivery_code'=>$delivery_code
				);
        		if($type==0)
        		{
        			$updateData['amount'] = Order_Class::get_refund_fee($orderRow,$goodsOrderRow);
        		}
        		if($goodsOrderRow['is_send']==1)
        		{
        			$updateData['pay_status'] = 4;
        			
        		}
        		else if($goodsOrderRow['is_send']==0){
        			$updateData['pay_status'] = 0;
        		}

				$goodsDB  = new IModel('goods');
        		$goodsRow = $goodsDB->getObj('id = '.$goodsOrderRow['goods_id']);

        		//属于商户的商品
        		if($goodsRow && $goodsRow['seller_id'])
        		{
        			$updateData['seller_id'] = $goodsRow['seller_id'];
        		}

        		//写入数据库
        		$refundsDB->setData($updateData);
        		$refundsDB->add();
        		
        		//更改订单状态
        		if($type==0){//只有退款更新
        			Order_Class::order_status_refunds(0,$goodsOrderRow,$type);
        		}
        		Order_Class::ordergoods_status_refunds(0,$goodsOrderRow,$type);
        		$this->redirect('/site/success');
        		exit;
        	
        }
        else
        {
        	$message = '订单不存在';
        }
        	IError::show(403,$message);
    }
    /**
     * @brief 退款申请删除
     */
    public function refunds_del()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $refundment_doc_db = new IQuery("refundment_doc as r");
        $refundment_doc_db->join = 'left join order_goods as og on og.order_id=r.order_id and og.goods_id=r.goods_id and og.product_id=r.product_id';
        $refundment_doc_db->fields = 'r.type,og.*';
        $refundment_doc_db->where = 'r.id='.$id.' and r.pay_status=0 and r.user_id='.$this->user['user_id'];
        $order_goods_row = $refundment_doc_db->getObj();
      
        if(empty($order_goods_row))Util::showMessage("退款信息不存在");
        $model = new IModel('refundment_doc');
       if($model->del('id='.$id.' and user_id='.$this->user['user_id'])){
       		Order_Class::get_order_status_refunds($id,1);
       		Order_Class::ordergoods_status_refunds(-1,$order_goods_row);
       }
     	 $this->redirect('refunds');
    }
    /**
     * @brief 查看退款申请详情（此处做了更改）
     */
    public function refunds_detail()
    {
    	$siteConfig = new Config('site_config');
    	$timeLimit=isset($siteConfig->refunds_limit_time) ? intval($siteConfig['refunds_limit_time']) : 7;
        $id = IFilter::act( IReq::get('id'),'int' );
        $refundDB = new IModel("refundment_doc");
        $where = "id = ".$id." and user_id = ".$this->user['user_id'];
        $refundRow = $refundDB->getObj($where);
      
        if($refundRow)
        {
        	//获取商品信息
        	$orderGoodsDB = new IModel('order_goods');
        	$orderGoodsRow = $orderGoodsDB->getObj('order_id = '.$refundRow['order_id'].' and goods_id = '.$refundRow['goods_id'].' and product_id = '.$refundRow['product_id']);
        	if($orderGoodsRow && $orderGoodsRow['goods_array'])
        	{
        		$refundRow['goods'] = $orderGoodsRow;
        		$this->data = $refundRow;
        		if($this->data['pay_status']==3){
        			$this->endTime = strtotime($this->data['dispose_time']) + $timeLimit*24*3600;
        			$this->endTime = strtotime('now')>$this->endTime ? false : $this->endTime;
        		}
        		$this->setRenderData($this->data);
        	}
        	else
        	{
	        	$this->redirect('refunds',false);
	        	Util::showMessage("没有找到要退款的商品");
        	}
        	
        	$this->redirect('refunds_detail');
        }
        else
        {
        	$this->redirect('refunds',false);
        	Util::showMessage("退款信息不存在");
        }
    }
    /**
     * @brief 查看退款申请详情
     */
	public function refunds_edit()
	{
		$order_goods_id = IFilter::act(IReq::get('og_id'),'int');
		if($order_goods_id)
		{
			
			$orderDB  = new IQuery('order_goods as og');
			$orderDB->join = 'left join order as o on og.order_id=o.id ';
			$orderDB->where = 'og.id='.$order_goods_id.' and o.user_id='.$this->user['user_id'];
			$orderDB->fields = 'o.order_no,o.pay_type,og.id as order_id,o.real_freight,o.payable_freight,o.status,o.completion_time,o.real_amount,o.pro_reduce,o.order_amount,og.img,og.refunds_status,og.is_send,og.goods_nums,og.real_price,og.goods_id,og.goods_array,og.id as og_id';
			$orderRow = $orderDB->getObj();
			if($orderRow)
			{
				$orderRow['can_refunds'] = Refunds_Class::order_goods_refunds($orderRow);
				$orderRow['can_chg'] = Refunds_Class::order_goods_chg($orderRow);
				
				if($orderRow['can_refunds'] && $orderRow['real_freight']==0 && $orderRow['payable_freight']>0 && $orderRow['is_send']==0){
					$prom = new IModel('promotion');
					$free_freight_price = $prom->getObj('type=0 and award_type=6 and is_close=0','`condition`');
					$free_freight_price = $free_freight_price['condition'];//免运费的额度

					$order_amount = $orderRow['real_amount'] + $orderRow['pro_reduce'];
					$order_goods_db = new IModel('order_goods');
					$refunds_og = $order_goods_db->query('order_id='.$orderRow['order_id'].' and refunds_status in (3,4,6)','real_price');
					$refunds_sum = 0;//未发货已申请退款的金额，包括当前商品
					foreach($refunds_og as $v){
						$refunds_sum += $v['real_price'];
					}
					$refunds_sum += $orderRow['real_price'];
					if($order_amount - $refunds_sum < $free_freight_price){//剩余的钱小于免运费的额度
						$orderRow['freight_text'] = 1;
					}
				}
				if(!$orderRow['can_refunds'] && !$orderRow['can_chg'])$this->redirect('refunds');
				$this->orderRow = $orderRow;
				$this->redirect('refunds_edit');
				exit;
			}
		}
		$this->redirect('refunds');
	}
	/**
	 * 退货处理，用户上传退货单据
	 * 
	 */
	public function refunds_delivery(){
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		$delivery_com = IFilter::act(IReq::get('delivery_com'));
		$delivery_code=IFilter::act(IReq::get('delivery_code'));
		$delivery_img=IFilter::act(IReq::get('delivery_img'));
		$data = array(
				'delivery_com'=>$delivery_com,
				'delivery_code'=>$delivery_code,
				'delivery_time'=>ITime::getDateTime(),
				'pay_status'=>4
				
		);
		if(isset($_FILES['delivery_img']['name']) && $_FILES['delivery_img']['name'])
		{
			$uploadObj = new PhotoUpload();
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();
			if(isset($photoInfo['delivery_img']['img']) && file_exists($photoInfo['delivery_img']['img']))
			{
				$data['delivery_img'] = $photoInfo['delivery_img']['img'];
			}
			
		}
		if($refundment_id){
			$refundsDB = new IModel('refundment_doc');
			$refundsDB->setData($data);
			$refundsDB->update('id='.$refundment_id.' AND user_id='.$this->user['user_id'].' AND pay_status = 3');
		}
		$this->redirect('refunds');
	}
    /**
     * @brief 建议中心
     */
    public function complain_edit()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $title = IFilter::act(IReq::get('title'),'string');
        $content = IFilter::act(IReq::get('content'),'string' );
        $user_id = $this->user['user_id'];
        $model = new IModel('suggestion');
        $model->setData(array('user_id'=>$user_id,'title'=>$title,'content'=>$content,'time'=>date('Y-m-d H:i:s')));
        if($id =='')
        {
            $model->add();
        }
        else
        {
            $model->update('id = '.$id.' and user_id = '.$this->user['user_id']);
        }
        if(IClient::getDevice()=='mobile'){
        	$this->redirect('index');
        }else
        $this->redirect('complain');
    }
    /**
     * @brief 删除消息
     * @param int $id 消息ID
     */
    public function message_del()
    {
        $id = IFilter::act( IReq::get('id') ,'int' );
        $msg = new Mess($this->user['user_id']);
        $msg->delMessage($id);
        $this->redirect('message');
    }
    public function message_read()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $msg = new Mess($this->user['user_id']);
        echo $msg->writeMessage($id,1);
    }

    //[修改密码]修改动作
    function password_edit()
    {
    	$user_id    = $this->user['user_id'];

    	$fpassword  = IReq::get('fpassword');
    	$password   = IReq::get('password');
    	$repassword = IReq::get('repassword');

    	$userObj    = new IModel('user');
    	$where      = 'id = '.$user_id;
    	$userRow    = $userObj->getObj($where);

		if(!preg_match('|\w{6,32}|',$password))
		{
			$message = '密码格式不正确，请重新输入';
		}
    	else if($password != $repassword)
    	{
    		$message  = '二次密码输入的不一致，请重新输入';
    	}
    	else if(md5($fpassword) != $userRow['password'])
    	{
    		$message  = '原始密码输入错误';
    	}
    	else
    	{
    		$passwordMd5 = md5($password);
	    	$dataArray = array(
	    		'password' => $passwordMd5,
	    	);

	    	$userObj->setData($dataArray);
	    	$result  = $userObj->update($where);
	    	if($result)
	    	{
	    		$this->redirect('/site/success?message=密码修改成功');
	    		
	    	}
	    	else
	    	{
	    		$this->redirect('/site/error?msg=密码修改失败');
	    	}
		}
		$this->redirect('/site/error?msg=密码修改失败');
    	//$this->redirect('/ucenter/info');
    }

    //[个人资料]展示 单页
    function info()
    {
    	$user_id = $this->user['user_id'];

    	$userObj       = new IModel('user');
    	$where         = 'id = '.$user_id;
    	$this->userRow = $userObj->getObj($where);

    	$memberObj       = new IModel('member');
    	$where           = 'user_id = '.$user_id;
    	$this->memberRow = $memberObj->getObj($where);

		$this->userGroupRow = array();
		if(isset($this->memberRow['group_id']) && $this->memberRow['group_id'])
		{
	    	$userGroupObj       = new IModel('user_group');
	    	$where              = 'id = '.$this->memberRow['group_id'];
	    	$this->userGroupRow = $userGroupObj->getObj($where);
		}
    	$this->redirect('info');
    }
	/**
	 * 添加用户名
	 */
    function username_add(){
    	$username = IFilter::act(IReq::get('username'));
    	$user_id = $this->user['user_id'];
    	$user_db = new IModel('user');
    	if($user_db->getObj('username="'.$username.'"','id')){
    		$this->redirect('username',false);
    		Util::showMessage('该用户名已注册');
    	}
    		
    	$user_db->setData(array('username'=>$username));
    	$user_db->update('id='.$user_id);
    		$this->redirect('info');
    }
    //[个人资料] 修改 [动作]
    function info_edit_act()
    {
    	$user_id   = $this->user['user_id'];

    	$memberObj = new IModel('member');
    	$where     = 'user_id = '.$user_id;

    	//地区
    	$province = IFilter::act( IReq::get('province','post') ,'string');
    	$city     = IFilter::act( IReq::get('city','post') ,'string' );
    	$area     = IFilter::act( IReq::get('area','post') ,'string' );
    	$areaStr  = ','.$province.','.$city.','.$area.',';

    	$username = IFilter::act(IReq::get('username','post'));
    	if($username){
    		$user = new IModel('user');
    		if(!$user->getObj('username="'.$username.'"','id')){
    			$user->setData(array('username'=>$username));
    			$user->update('id='.$user_id);
    		}
    	}
    	$dataArray       = array(
    		'true_name'    => IFilter::act( IReq::get('true_name') ,'string'),
    		'sex'          => IFilter::act( IReq::get('sex'),'int' ),
    		'birthday'     => IFilter::act( IReq::get('birthday') ),
    		'zip'          => IFilter::act( IReq::get('zip') ,'string' ),
    		'msn'          => IFilter::act( IReq::get('msn') ,'string' ),
    		'qq'           => IFilter::act( IReq::get('qq') , 'string' ),
    		'contact_addr' => IFilter::act( IReq::get('contact_addr'), 'string'),
    		'telephone'    => IFilter::act( IReq::get('telephone'),'string'),
    		'area'         => $areaStr,
    	);

    	$memberObj->setData($dataArray);
    	$memberObj->update($where);
    	$this->info();
    }

    //[账户余额] 展示[单页]
    function withdraw()
    {
    	$user_id   = $this->user['user_id'];

    	$memberObj = new IModel('member','balance');
    	$where     = 'user_id = '.$user_id;
    	$this->memberRow = $memberObj->getObj($where);
    	$this->redirect('withdraw');
    }

	//[账户余额] 提现动作
    function withdraw_act()
    {
    	$user_id = $this->user['user_id'];
    	$amount  = IFilter::act( IReq::get('amount','post') ,'string' );
    	$message = '';

    	$dataArray = array(
    		'name'   => IFilter::act( IReq::get('name','post') ,'string'),
    		'note'   => IFilter::act( IReq::get('note','post'), 'string'),
			'amount' => $amount,
			'user_id'=> $user_id,
			'time'   => ITime::getDateTime(),
    	);

		$mixAmount = 0;
		$memberObj = new IModel('member');
		$where     = 'user_id = '.$user_id;
		$memberRow = $memberObj->getObj($where,'balance');

		//提现金额范围
		if($amount <= $mixAmount)
		{
			$message = '提现的金额必须大于'.$mixAmount.'元';
		}
		else if($amount > $memberRow['balance'])
		{
			$message = '提现的金额不能大于您的帐户余额';
		}
		else
		{
	    	$obj = new IModel('withdraw');
	    	$obj->setData($dataArray);
	    	$obj->add();
	    	$this->redirect('withdraw');
	    	die();
		}

		if($message != '')
		{
			$this->memberRow = array('balance' => $memberRow['balance']);
			$this->withdrawRow = $dataArray;
			$this->redirect('withdraw',false);
			Util::showMessage($message);
		}
    }

    //[账户余额] 提现详情
    function withdraw_detail()
    {
    	$user_id = $this->user['user_id'];

    	$id  = IFilter::act( IReq::get('id'),'int' );
    	$obj = new IModel('withdraw');
    	$where = 'id = '.$id.' and user_id = '.$user_id;
    	$this->withdrawRow = $obj->getObj($where);
    	$this->redirect('withdraw_detail');
    }

    //[提现申请] 取消
    function withdraw_del()
    {
    	$id = IFilter::act( IReq::get('id'),'int' );
    	if($id)
    	{
    		$dataArray   = array('is_del' => 1);
    		$withdrawObj = new IModel('withdraw');
    		$where = 'id = '.$id;
    		$withdrawObj->setData($dataArray);
    		$withdrawObj->update($where);
    	}
    	$this->redirect('withdraw');
    }

    //[余额交易记录]
    function account_log()
    {
    	$user_id   = $this->user['user_id'];

    	$memberObj = new IModel('member','balance');
    	$where     = 'user_id = '.$user_id;
    	$this->memberRow = $memberObj->getObj($where);
    	$this->redirect('account_log');
    }

    //[收藏夹]备注信息
    function edit_summary()
    {
    	$user_id = $this->user['user_id'];

    	$id      = IFilter::act( IReq::get('id'),'int' );
    	$summary = IFilter::act( IReq::get('summary'),'string' );

    	//ajax返回结果
    	$result  = array(
    		'isError' => true,
    	);

    	if(!$id)
    	{
    		$result['message'] = '收藏夹ID值丢失';
    	}
    	else if(!$summary)
    	{
    		$result['message'] = '请填写正确的备注信息';
    	}
    	else
    	{
	    	$favoriteObj = new IModel('favorite');
	    	$where       = 'id = '.$id.' and user_id = '.$user_id;

	    	$dataArray   = array(
	    		'summary' => $summary,
	    	);

	    	$favoriteObj->setData($dataArray);
	    	$is_success = $favoriteObj->update($where);

	    	if($is_success === false)
	    	{
	    		$result['message'] = '更新信息错误';
	    	}
	    	else
	    	{
	    		$result['isError'] = false;
	    	}
    	}
    	echo JSON::encode($result);
    }

    //获取历史浏览数据
    function get_history(&$historyObj)
    {
    	//获取收藏夹信息
    	$page = (isset($_GET['page'])&&(intval($_GET['page'])>0))?intval($_GET['page']):1;
    
    	$historyObj = new IQuery("user_history");
    	$cat_id = intval(IReq::get('cat_id'));
    	$where = '';
    	if($cat_id != 0)
    	{
    		$where = ' and cat_id = '.$cat_id;
    	}
    	$historyObj->where = "user_id = ".$this->user['user_id'].$where;
    	$historyObj->page  = $page;
    	$historyObj->order = "id DESC";
    	$items = $historyObj->find();
    
    	$goodsIdArray   = array();
    	foreach($items as $val)
    	{
    		$goodsIdArray[] = $val['goods_id'];
    	}
    
    	//商品数据
    	if(!empty($goodsIdArray))
    	{
    		$goodsIdStr = join(',',$goodsIdArray);
    		$goodsObj   = new IModel('goods');
    		$goodsList  = $goodsObj->query('id in ('.$goodsIdStr.')','id,name,sell_price,store_nums,img,seller_id');
    	}
    
    	foreach($items as $key => $val)
    	{
    		foreach($goodsList as $gkey => $goods)
    		{
    			if($goods['id'] == $val['goods_id'])
    			{
    				$items[$key]['data'] = $goods;
    
    			}
    		}
    	}
    	foreach($items as $k=>$v){
    		if(!isset($v['data']))unset($items[$k]);
    	}
    	return $items;
    }
    //[收藏夹]获取收藏夹数据
	function get_favorite(&$favoriteObj)
    {
		//获取收藏夹信息
	    $page = (isset($_GET['page'])&&(intval($_GET['page'])>0))?intval($_GET['page']):1;

		$favoriteObj = new IQuery("favorite");
		$cat_id = intval(IReq::get('cat_id'));
		$where = '';
		if($cat_id != 0)
		{
			$where = ' and cat_id = '.$cat_id;
		}
		$favoriteObj->where = "user_id = ".$this->user['user_id'].$where;
		$favoriteObj->page  = $page;
		$items = $favoriteObj->find();

		$goodsIdArray   = array();
		foreach($items as $val)
		{
			$goodsIdArray[] = $val['rid'];
		}

		//商品数据
		if(!empty($goodsIdArray))
		{
			$goodsIdStr = join(',',$goodsIdArray);
			$goodsObj   = new IModel('goods');
			$goodsList  = $goodsObj->query('id in ('.$goodsIdStr.')');
		}

		foreach($items as $key => $val)
		{
			foreach($goodsList as $gkey => $goods)
			{
				if($goods['id'] == $val['rid'])
				{
					$items[$key]['data'] = $goods;

					//效率考虑,让goodsList循环次数减少
					unset($goodsList[$gkey]);
				}
			}

			//如果相应的商品或者货品已经被删除了，
			if(!isset($items[$key]['data']))
			{
				$favoriteModel = new IModel('favorite');
				$favoriteModel->del("id={$val['id']}");
				unset($items[$key]);
			}
		}
		return $items;
    }
    //[收藏夹]获取收藏夹数据
    function get_favorite_ajax()
    {
    	//获取收藏夹信息
    	$page = IReq::get('page') ? intval(IReq::get('page')) : 1;
  
    	$favoriteObj = new IQuery("favorite as f");
    	$favoriteObj->join = 'left join goods as g on f.rid = g.id';
    	$favoriteObj->fields = 'g.id,g.name,g.img,g.sell_price,f.id as fid';
    	$cat_id = intval(IReq::get('cat_id'));
    	$where = '';
    	if($cat_id != 0)
    	{
    		$where = ' and f.cat_id = '.$cat_id;
    	}
    	
    	$favoriteObj->where = "f.user_id = ".$this->user['user_id'].' and (g.is_del = 0 or g.is_del = 4 )'.$where;
    	$favoriteObj->page  = $page;
    	$items = $favoriteObj->find();
 
    	if($favoriteObj->page==0){echo 0;exit;}
    	echo JSON::encode($items);
    }
    //[收藏夹]删除
    function favorite_del()
    {
    	$user_id = $this->user['user_id'];
    	$id      = IReq::get('id');

		if(!empty($id))
		{
			$id = IFilter::act($id,'int');

			$favoriteObj = new IModel('favorite');

			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = 'user_id = '.$user_id.' and id in ('.$idStr.')';
			}
			else
			{
				$where = 'user_id = '.$user_id.' and id = '.$id;
			}

			$favoriteObj->del($where);
			$this->redirect('favorite');
		}
		else
		{
			$this->redirect('favorite',false);
			Util::showMessage('请选择要删除的数据');
		}
    }

    /**
     * 删除浏览历史
     * @$id int 浏览记录id
     */
    function history_del(){
   		 $user_id = $this->user['user_id'];
    	$id      = IReq::get('id');
		if(!empty($id))
		{
			$id = IFilter::act($id,'int');

			$historyObj = new IModel('user_history');

			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = 'user_id = '.$user_id.' and id in ('.$idStr.')';
			}
			else
			{
				$where = 'user_id = '.$user_id.' and id = '.$id;
			}

			$historyObj->del($where);
			$this->redirect('history');
		}
		else
		{
			$this->redirect('history',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
    
    //[我的积分] 单页展示
    function integral()
    {
    	/*获取积分增减的记录日期时间段*/
    	$this->historyTime = IFilter::string( IReq::get('history_time','post') );
    	$defaultMonth = 3;//默认查找最近3个月内的记录

		$lastStamp    = ITime::getTime(ITime::getNow('Y-m-d')) - (3600*24*30*$defaultMonth);
		$lastTime     = ITime::getDateTime('Y-m-d',$lastStamp);

		if($this->historyTime != null && $this->historyTime != 'default')
		{
			$historyStamp = ITime::getDateTime('Y-m-d',($lastStamp - (3600*24*30*$this->historyTime)));
			$this->c_datetime = 'datetime >= "'.$historyStamp.'" and datetime < "'.$lastTime.'"';
		}
		else
		{
			$this->c_datetime = 'datetime >= "'.$lastTime.'"';
		}

    	$memberObj         = new IModel('member');
    	$where             = 'user_id = '.$this->user['user_id'];
    	$this->memberRow   = $memberObj->getObj($where,'point');
    	$this->redirect('integral',false);
    }

    //[我的积分]积分兑换代金券 动作
    function trade_ticket()
    {
    	$ticketId = IFilter::act( IReq::get('ticket_id','post'),'int' );
    	$message  = '';
    	if(intval($ticketId) == 0)
    	{
    		$message = '请选择要兑换的代金券';
    	}
    	else
    	{
    		$nowTime   = ITime::getDateTime();
    		$ticketObj = new IModel('ticket');
    		$ticketRow = $ticketObj->getObj('id = '.$ticketId.' and point > 0 and start_time <= "'.$nowTime.'" and end_time > "'.$nowTime.'"');
    		if(empty($ticketRow))
    		{
    			$message = '对不起，此代金券不能兑换';
    		}
    		else
    		{
	    		$memberObj = new IModel('member');
	    		$where     = 'user_id = '.$this->user['user_id'];
	    		$memberRow = $memberObj->getObj($where,'point');

	    		if($ticketRow['point'] > $memberRow['point'])
	    		{
	    			$message = '对不起，您的积分不足，不能兑换此类代金券';
	    		}
	    		else
	    		{
	    			//生成红包
					$dataArray = array(
						'condition' => $ticketRow['id'],
						'name'      => $ticketRow['name'],
						'card_name' => 'T'.IHash::random(8),
						'card_pwd'  => IHash::random(8),
						'value'     => $ticketRow['value'],
						'start_time'=> $ticketRow['start_time'],
						'end_time'  => $ticketRow['end_time'],
						'is_send'   => 1,
					);
					$propObj = new IModel('prop');
					$propObj->setData($dataArray);
					$insert_id = $propObj->add();

					//用户prop字段值null时
					$memberArray = array('prop' => ','.$insert_id.',');
					$memberObj->setData($memberArray);
					$result      = $memberObj->update('user_id = '.$this->user["user_id"].' and ( prop is NULL or prop = "" )');

					//用户prop字段值非null时
					if(!$result)
					{
						$memberArray = array(
							'prop' => 'concat(prop,"'.$insert_id.',")',
						);
						$memberObj->setData($memberArray);
						$result = $memberObj->update('user_id = '.$this->user["user_id"],'prop');
					}

					//代金券成功
					if($result)
					{
						$pointConfig = array(
							'user_id' => $this->user['user_id'],
							'point'   => '-'.$ticketRow['point'],
							'log'     => '积分兑换代金券，扣除了 -'.$ticketRow['point'].'积分',
						);
						$pointObj = new Point;
						$pointObj->update($pointConfig);
					}
	    		}
    		}
    	}

    	//展示
    	if($message != '')
    	{
    		$this->integral();
    		Util::showMessage($message);
    	}
    	else
    	{
    		$this->redirect('redpacket');
    	}
    }

    /**
     * 余额付款
     * T:支付失败;
     * F:支付成功;
     */
    function payment_balance()
    {
    	$urlStr  = '';
    	$user_id = intval($this->user['user_id']);

    	$return['attach']     = IReq::get('attach');
    	$return['total_fee']  = IReq::get('total_fee');
    	$return['order_no']   = IReq::get('order_no');
    	$return['return_url'] = IReq::get('return_url');
    	$sign                 = IReq::get('sign');
    	if(stripos($return['order_no'],'recharge_') !== false)
    	{
    		IError::show(403,'余额支付方式不能用于在线充值');
    		exit;
    	}

    	if(floatval($return['total_fee']) <= 0 || $return['order_no'] == '' || $return['return_url'] == '')
    	{
    		IError::show(403,'支付参数不正确');
    	}
    	else
    	{
    		$paymentDB  = new IModel('payment');
    		$paymentRow = $paymentDB->getObj('class_name = "balance" ');
    		$pkey       = Payment::getConfigParam($paymentRow['id'],'M_PartnerKey');

	    	//md5校验
	    	ksort($return);
			foreach($return as $key => $val)
			{
				$urlStr .= $key.'='.urlencode($val).'&';
			}

			$encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
			$urlStr .= $user_id.$pkey.$encryptKey;
			if($sign != md5($urlStr))
			{
				IError::show(403,'数据校验不正确');
			}
			else
			{
		    	$memberObj = new IModel('member');
		    	$memberRow = $memberObj->getObj('user_id = '.$user_id);

		    	if(empty($memberRow))
		    	{
		    		IError::show(403,'用户信息不存在');
		    		exit;
		    	}
		    	else if($memberRow['balance'] < $return['total_fee'])
		    	{
		    		IError::show(403,'账户余额不足');
		    		exit;
		    	}
		    	else
		    	{
		    		$orderObj = new IModel('order');
		    		if(stripos($return['order_no'],'merge') === false){
		    			$trueOrderNo   = Preorder_Class::getTrueOrderNo($return['order_no']);
		    			$orderRow = $orderObj->getObj('order_no  = "'.IFilter::act($trueOrderNo).'" and (pay_status = 0 and type!=4 || pay_status in (0,1) and type=4) and user_id = '.$user_id);
		    			
		    			if(empty($orderRow))
		    			{
		    				IError::show(403,'订单已经被处理过，请查看订单状态');
		    				exit;
		    			}
		    			
		    		}
		    		

					$dataArray  = array('balance' => 'balance - '.IFilter::act($return['total_fee']));
					$memberObj->setData($dataArray);
			    	$is_success = $memberObj->update('user_id = '.$user_id,'balance');
			    	if($is_success)
			    	{
			    		$return['is_success'] = 'T';
			    	}
			    	else
			    	{
			    		$return['is_success'] = 'F';
			    	}

			    	ksort($return);

			    	//返还的URL地址
					$responseUrl = '';
					foreach($return as $key => $val)
					{
						$responseUrl .= $key.'='.urlencode($val).'&';
					}
					$nextUrl = urldecode($return['return_url']);
					if(stripos($nextUrl,'?') === false)
					{
						$return_url = $nextUrl.'?'.$responseUrl;
					}
					else
					{
						$return_url = $nextUrl.'&'.$responseUrl;
					}

					//计算要发送的md5校验
					$encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
					$urlStrMD5  = md5($responseUrl.$user_id.$pkey.$encryptKey);

					//拼接进返还的URL中
					$return_url.= 'sign='.$urlStrMD5;
			    	header('location:'.$return_url);
		    	}
			}
    	}
    }
    
    /**
     * 修改手机号码
     */
    function chgPhone()
    {
    	$user_id = $this->user['user_id'];
    
    	$userObj       = new IModel('user');
    	$where         = 'id = '.$user_id;
    	$this->data = array('user_phone'=>$userObj->getField($where,'phone'));
    	
    	$this->setRenderData($this->data);
    	$this->redirect('chgPhone');
    	
    }
    /**
     * 获取手机验证码
     */
    public function getMobileCode(){
    	$phone = IFilter::act(IReq::get('phone'));
    	if(!IValidate::phone($phone)){
    		$res['errorCode']==1;
    		$res['mess']='手机号码填写错误';
    		echo JSON::encode($res);
    		exit();
    	}
		$res = array('errorCode'=>0);
		
		$code = rand(100000,999999);
		ISafe::set('mobileValidate',array('code'=>$code,'phone'=>$phone,'time'=>time()));
		$text = smsTemplate::checkCode(array('{mobile_code}'=>$code)); 
		if(!hsms::send($phone,$text)){
			$res['errorCode']=-1;
			$res['mess']='系统繁忙，请稍候再试';
		}
		
		echo JSON::encode($res);
    }
    
    
    //初次校验验证码
    public function checkMobile(){
    	$res = array('errorCode'=>0);
    	//$res['next']=IUrl::getHost().IUrl::creatUrl("/ucenter/toChgPhone2");
    	//echo JSON::encode($res);exit();
    	$phone = $this->user['phone'];
    	$code = IFilter::act(IReq::get('code','post'));
    	$nextUrl = IFilter::act(IReq::get('nextUrl','post'));
    	if(!$code){
    		$res['errorCode']=1;
    		$res['mess']='验证码不能为空';
    	}
    	else if($codeData = ISafe::get('mobileValidate')){
    		if(time() - $codeData['time']>=1800){
    			$res['errorCode']=2;//验证码过期
    			$res['mess']='验证码已过期，请重新获取';
    		}else if($codeData['phone']!=$this->user['phone']){//非法操作
    			$res['errorCode']=3;
    			$res['mess']='操作非法';
    		}else if($codeData['code'] !=$code){//验证码错误
    			$res['errorCode'] = 4;
    			$res['mess']='验证码错误';
    		}else{//验证正确
    			ISafe::clear('mobileValidate');
    			ISafe::set('mobileValidRes',array('phone'=>$this->user['phone'],'time'=>time()));//session记录验证结果，和时间
    			$res['next']=$nextUrl ? $nextUrl : IUrl::getHost().IUrl::creatUrl("/ucenter/toChgPhone2");
    		}
    	}else{
    		$res['errorCode']=5;//没有验证码
    		$res['mess']='请获取验证码';
    	}
    	echo JSON::encode($res);
    	
    		
    }
    /**
     * 验证第一步是否成功
     * @return bool 
     */
    private function checkFirstStep(){
    	$checkRes = ISafe::get('mobileValidRes');
    	if($checkRes && $this->user['phone']==$checkRes['phone'] &&time()- $checkRes['time']<1800 ){
    		return true;
    	}else{
    		return false;
    	}
    }
    /**
     * 修改手机号第二步
     */
    public function toChgPhone2(){
    	$firstCheck = $this->checkFirstStep();
    	if($firstCheck){
    		$this->redirect('toChgPhone2');
    	}else{
    		$this->redirect('toChgPhone1');
    	}
    		
    }
    /**
     * 修改邮箱第二步
     */
    public function toChgEmail2(){
   		 $firstCheck = $this->checkFirstStep();
    	if($firstCheck){
    		$this->redirect('toChgEmail2');
    	}else{
    		$this->redirect('toChgEmail1');
    	}
    }
    /**
     * 第二步修改手机号提交
     */
    public function checkMobile2(){
    	$newPhone = IFilter::act(IReq::get('newPhone','post'));
    	$code =IFilter::act(IReq::get('code','post'));
    	$res = array('errorCode'=>0);
    	if(!IValidate::phone($newPhone)){
    		$res['errorCode']=2;
    		$res['mess']='请正确填写手机号码';
    	}else if(!$code){
    		$res['errorCode']=3;
    		$res['mess']='请填写验证码';
    	}else{
    		$validData = ISafe::get('mobileValidate');
    		$checkRes = ISafe::get('mobileValidRes');
    		if($checkRes && $this->user['phone']==$checkRes['phone'] &&time()- $checkRes['time']<1800 ){
    			if($validData['phone']==$newPhone && $validData['code']==$code && time()- $validData['time']<1800){//验证通过
    				$user_id = $this->user['user_id'];
    				$userObj       = new IModel('user');
    				$where         = 'id = '.$user_id;
    				$res['id'] = $user_id;
    				$userObj->setData(array('phone'=>$newPhone));
    				if($userObj->getObj('phone="'.$newPhone.'"')){
    					$res['errorCode']=9;//没有验证码
    					$res['mess']='该手机号码已注册';
    				}
    				else if($userObj->update($where)){
    					ISafe::clear('mobileValidate');
    					ISafe::clear('mobileValidRes');
    					ISafe::set('phone',$newPhone);
    					$res['next']=IUrl::getHost().IUrl::creatUrl("/ucenter/toChgPhone3");
    				}else{
    					$res['errorCode']=6;//更新失败
    					$res['mess']='手机号码更新失败';
    				}
    				
    			}else{
    				$res['errorCode']=4;//没有验证码
    				$res['mess']='验证码错误或已过期，请重新验证身份';
    			}
    		}else{
    			$res['errorCode']=5;//没有验证码
    			$res['mess']='验证码错误，请重新验证身份';
    		}
    	}
    	
    	echo JSON::encode($res);
    }
    //获取有效验证码
    public function getEmailCode(){
    	$newEmail = IFilter::act(IReq::get('newEmail','post'));
    	$res = array('errorCode'=>0);
    	if(!IValidate::email($newEmail)){
    		$res['errorCode']=1;//邮箱格式错误
    		$res['mess']='邮箱格式错误';
    		echo JSON::encode($res);exit();
    	}
    	$code = rand(000000,999999);
    	ISafe::set('emailValidate',array('code'=>$code,'email'=>$newEmail,'time'=>time()));
    	$content = mailTemplate::emailCode(array("{code}" => $code));
    	$smtp   = new SendMail();
    	if(!$result = $smtp->send($newEmail,"山城速购用户修改邮箱验证",$content))
    	{
    		$res['errorCode']=3;//没有验证码
    		$res['mess']='系统繁忙，请稍候再试';
    	}
    	echo JSON::encode($res);
    }
    //修改邮箱第二步提交
    public function checkEmail2(){
    	$newEmail = IFilter::act(IReq::get('newEmail','post'));
    	$code =IFilter::act(IReq::get('code','post'));
    	$res = array('errorCode'=>0);
    	if(!IValidate::email($newEmail)){
    		$res['errorCode']=1;//邮箱格式错误
    		$res['mess']='邮箱格式错误';
    		echo JSON::encode($res);exit();
    	}
    	if(!$code){
    		$res['errorCode']=2;
    		$res['mess']='请填写验证码';
    		echo JSON::encode($res);exit();
    	}
    	$validData = ISafe::get('emailValidate');
    	$checkRes = ISafe::get('mobileValidRes');
    	
    	if($checkRes && $this->user['phone']==$checkRes['phone'] &&time()- $checkRes['time']<1800 ){
    		if($validData['email']==$newEmail && $validData['code']==$code && time()- $validData['time']<1800){//验证通过
    			//ISafe::clear('emailValidate');
    			$user_id = $this->user['user_id'];
    			$userObj       = new IModel('user');
    			$where         = 'id = '.$user_id;
    			$res['id'] = $user_id;
    			$userObj->setData(array('email'=>$newEmail));
    			if($userObj->getObj('email="'.$newEmail.'"')){
    				$res['errorCode']=9;
    				$res['mess']='该邮箱已注册';
    			}
    			else if($userObj->update($where)){
					ISafe::clear('mobileValidRes');
    				ISafe::set('email',$newEmail);
    				$res['next']=IUrl::getHost().IUrl::creatUrl("/ucenter/toChgEmail3");
    			}else{
    				echo JSON::encode($res);exit();
    				$res['errorCode']=6;//更新失败
    				$res['mess']='邮箱更新失败';
    			}
    	
    		}else{
    			$res['errorCode']=4;//没有验证码
    			$res['mess']='验证码错误或已过期，请重新获取邮箱验证码';
    		}
    	}else{
    		$res['errorCode']=5;//没有验证码
    		$res['mess']='验证码错误或已过期，请重新验证身份';
    	}
    	echo JSON::encode($res);
    }
    
    //验证用户名是否注册
    public function checkUserIsOne(){
    	$username = IFilter::act(IReq::get('username'),'post');
    	$user = new IModel('user');
    	if($user->getObj('username="'.$username.'"','id')){
    		echo 1;
    	}else echo 0;
    	
    }
    
    //可以补开发票列表
    public function fapiao(){
    	$page = (isset($_GET['page'])&&(intval($_GET['page'])>0))?intval($_GET['page']):1;
    	$siteConfig = new Config('site_config');
    	$fapiao_date = $siteConfig->date_fapiao ? intval($siteConfig->date_fapiao): 30;
    	$fapiaoSec = $fapiao_date * 24 * 3600;
    	$user_id = $this->user['user_id'];
    	$db_order = new IQuery('order');
    	$db_order->where = 'user_id='.$user_id.' and pay_status = 1 AND invoice =0  AND TIMESTAMPDIFF(second,pay_time,NOW()) <= '.$fapiaoSec;
		//计算总条数
    	$db_order->fields = 'count(id) as num';
    	$res = 	$db_order->getObj();
    	$this->num = $res ? $res['num'] : 0;
    	unset($res);
    	
    	$db_order->page = $page;
    	$db_order->pagesize = 15;
    	$db_order->fields = 'id,order_no,pay_time,create_time';
    	$db_order->order  = 'id DESC';
    	
    	//计算允许开发票的订单，已付款且付款时间在规定时间
    	$orderFapiao = $db_order->find();
    	$this->num = count($orderFapiao);
    	$this->orderFapiao = $orderFapiao;
    	$this->db_order = $db_order;
    	unset($db_order);
    	$this->redirect('fapiao');
    }
    //已补开发票列表
    public function fapiao_his(){
    	$page = (isset($_GET['page'])&&(intval($_GET['page'])>0))?intval($_GET['page']):1;
    	$db_fapiao = new IQuery('order_fapiao as f');
    	$db_fapiao->where = 'f.user_id='.$this->user['user_id'];
    	//计算总条数
    	$db_fapiao->fields = 'count(f.id) as num';
    	$res = 	$db_fapiao->getObj();
    	$this->num = $res ? $res['num'] : 0;
    	
    	//获取数据
    	$db_fapiao->join = 'left join order as o on o.id = f.order_id';
    	$db_fapiao->page = $page;
    	$db_fapiao->pagesize = 15;
    	$db_fapiao->fields = 'f.*,o.id as order_id,o.order_no';
    	$db_fapiao->order = 'id DESC';
    	$fapiao_his = $db_fapiao->find();
    	$this->fapiao_his = $fapiao_his;
    	$this->db_fapiao = $db_fapiao;unset($db_fapiao);
    	$this->redirect('fapiao_his');
    }
    //
    public function fapiao_add_act(){
    	$id = IFilter::act(IReq::get('id'),'int');
    	$order_id = IFilter::act(IReq::get('order_id'),'int');
    	$type = IFilter::act(IReq::get('type'),'int');
    	$taitou = IFilter::act(IReq::get('taitou'));
    	$db_order = new IModel('order');
    	if(!$db_order->getObj('id='.$order_id.' and user_id='.$this->user['user_id'])){
    		$this->redirect('fapiao');
    	}
    	$db_fapiao = new IModel('order_fapiao');
    	$data = array(
    				'order_id'=> $order_id,
    				'type'    => $type,
    				'taitou'  => $taitou,
    				'create_time'=> ITime::getDateTime(),
    				'user_id' => $this->user['user_id']
    	);
    
    	if($type==1){
    		$data1 = array(
    				'com' => IFilter::act(IReq::get('com')),
    				'tax_no'=> IFilter::act(IReq::get('tax_no')),
    				'address' => IFilter::act(IReq::get('address')),
    				'telphone' => IFilter::act(IReq::get('telphone')),
    				'bank' => IFilter::act(IReq::get('bank')),
    				'account' => IFilter::act(IReq::get('account')),
    		);
    		unset($data['taitou']);
    		$data = array_merge($data,$data1);
    	}
    	$piao = $db_fapiao->getObj('order_id='.$order_id,'id,status');
    	if($piao){
    		if($piao['status']!=0){
    			$this->redirect('piao_ask',false);
    			Util::show('申请发票信息不能修改');exit;
    		}
    		else{
    			$db_fapiao->setData($data);
    			$db_fapiao->update('order_id='.$order_id);
    		}
    	}
    	else{//第一次添加
    		//更改订单索要发票字段,订单更新为索要发票
    		$db_order->setData(array('invoice'=>1));
    		$db_order->update('id ='.$order_id);
    		
    		//获取卖家id
    		$db_order_goods = new IQuery('order_goods as og');
    		$db_order_goods->join = 'left join goods as g on og.goods_id = g.id';
    		$db_order_goods->where = ' og.order_id = '.$order_id;
    		$db_order_goods->distinct = 'distinct';
    		$db_order_goods->fields = 'g.seller_id';
    		$sellerId = $db_order_goods->find();
    		$data['status']=0;
    		foreach($sellerId as $key=>$v){
    			$data['seller_id'] = $v['seller_id'];
    			$db_fapiao->setData($data);
    			$db_fapiao->add();
    		}
    	}
    	$this->redirect('fapiao');
    	
    }
    public function preorder()
    {
    	$userid = $this->user['user_id'];
    	$order_no = IFilter::act(IReq::get('order_no'));
    	$status = IFilter::act(IReq::get('status'),'int');
    	$beginTime = IFilter::act(IReq::get('beginTime'));
    	$endTime = IFilter::act(IReq::get('endTime'));
    	$seller_id = IFilter::act(IReq::get('seller_id'),'int');
    	$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
    	
    	$status_str = $seller_str = '';
    	if($status){
    		$status_str = $status==12 ? ' o.status in (2,5,6,8)' : ' o.status='.$status;
    	}
    
    	if($seller_id){
    		if($seller_id==2){
    			$seller_str = ' g.seller_id=0 ';
    		}else if($seller_id==1){
    			$seller_str = ' g.seller_id!=0 ';
    		}
    	}

    	$where = "o.user_id =".$userid." and if_del= 0 and o.type=4 ";
    	$where .= $status_str ? ' and '.$status_str : '';
    	$where .= $seller_str ? ' and '.$seller_str : '';
    	if($beginTime)
    	{
    		$where .= ' and o.create_time > "'.$beginTime.'"';
    	}
    	if($endTime)
    	{
    		$where .= ' and o.create_time < "'.$endTime.'"';
    	}
    	if($order_no)$where = ' o.order_no='.$order_no;
    	$order_db = new IQuery('order as o');
    	$order_db->join = 'left join presell as p on o.active_id=p.id left join order_goods as og left join goods as g on g.id=og.goods_id on o.id=og.order_id left join comment as c on og.comment_id = c.id';
    	$order_db->group = 'og.order_id';
    	$order_db->where = $where?$where : 1;
    	$order_db->page  = $page;
    	$order_db->fields = 'o.*,og.goods_id,og.comment_id,c.status as comment_status,p.yu_end_time,p.wei_type,p.wei_start_time,p.wei_end_time,p.wei_days';
    	$order_db->order = 'o.id DESC';
    	$preorder_list = $order_db->find();
    	foreach($preorder_list as $key=>$val){
    		$preorder_list[$key]['can_pay'] = Preorder_Class::can_pay($val)? 1 : 0;
    	}
    	$this->order_db = $order_db;
    	$this->preorder_list = $preorder_list;
    	$this->initPayment();
    	
    	$data['s_beginTime'] = $beginTime;
    	$data['s_endTime'] = $endTime;
    	$data['s_order_no'] = $order_no;
    	$data['s_status'] = $status;
    	$data['s_seller_id'] = $seller_id;
    	$this->setRenderData($data);
    
        $this->initPayment();
        $this->redirect('preorder');

    }
    //操作预售订单状态
    public function preorder_status()
    {
    	$op    = IFilter::act(IReq::get('op'));
    	$id    = IFilter::act( IReq::get('order_id'),'int' );
    	$model = new IModel('order');
    
    	switch($op)
    	{
    		case "cancel":
    			{
    				$model->setData(array('status' => 2));
    				if($model->update("id = ".$id." and status = 1 and user_id = ".$this->user['user_id']))
    				{
    					//修改红包状态
    					$prop_obj = $model->getObj('id='.$id,'prop');
    					$prop_id = isset($prop_obj['prop'])?$prop_obj['prop']:'';
    					if($prop_id != '')
    					{
    						$prop = new IModel('prop');
    						$prop->setData(array('is_close'=>0));
    						$prop->update('id='.$prop_id);
    					}
    				}
    			}
    			break;
    
    		case "confirm":
    			{
    				$model->setData(array('status' => 11,'completion_time' => date('Y-m-d h:i:s')));
    				if($model->update("id = ".$id." and status = 9 and user_id = ".$this->user['user_id']))
    				{
    					$orderRow = $model->getObj('id = '.$id);
    					
    					//增加用户评论商品机会
    					Preorder_Class::addGoodsCommentChange($id);
    					
    					//经验值、积分、代金券发放
    					Order_Class::sendGift($id,$this->user['user_id']);
    					//确认收货以后直接跳转到评论页面
    					$this->redirect('evaluation');
    				}
    			}
    			break;
    	}
    	$this->redirect("preorder_detail/id/$id");
    }
 
}