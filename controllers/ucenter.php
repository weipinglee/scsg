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
        $this->initPayment();
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

        if(!$this->order_info)
        {
        	IError::show(403,'订单信息不存在');
        }
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
				if($model->update("id = ".$id." and distribution_status = 1 and user_id = ".$this->user['user_id']))
				{
					$orderRow = $model->getObj('id = '.$id);

					//确认收货后进行支付
					Order_Class::updateOrderStatus($orderRow['order_no']);

		    		//增加用户评论商品机会
		    		Order_Class::addGoodsCommentChange($id);

		    		//确认收货以后直接跳转到评论页面
		    		$this->redirect('evaluation');
				}
			}
			break;
		}
		$this->redirect("order_detail/id/$id");
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
        $order_id       = IFilter::act( IReq::get('order_id'),'int' );
        $user_id        = $this->user['user_id'];
        $type           = IFilter::act(IReq::get('type'),'int');
        $content        = IFilter::act(IReq::get('content'),'text');
        $message        = '请完整填写内容';

        if(!$content)
        {
	        $this->redirect('refunds',false);
	        Util::showMessage($message);
        }

        $orderDB = new IModel('order');
        $goodsOrderDB = new IModel('order_goods');
        $orderRow = $orderDB->getObj("id = ".$order_id." and user_id = ".$user_id);

        //判断订单是否付款（已付款且非退款）
        if($orderRow && Order_Class::isRefundmentApply($orderRow))
        {
        	$goodsOrderRow = $goodsOrderDB->getObj('id = '.$order_goods_id.' and order_id = '.$order_id);

        	//判断商品是否已经退货
        	if($goodsOrderRow && $goodsOrderRow['is_send'] != 2)
        	{
        		$refundsDB = new IModel('refundment_doc');

        		//判断是否重复提交申请
        		if($refundsDB->getObj('order_id = '.$order_id.' and goods_id = '.$goodsOrderRow['goods_id'].' and product_id = '.$goodsOrderRow['product_id'].' and if_del = 0 '))
        		{
        			$message = '请不要重复提交申请';
			        $this->redirect('refunds',false);
			        Util::showMessage($message);
        		}

        		//未发货的时候 退款运费和保价,税金
        		$otherFee = 0;
        		if($goodsOrderRow['delivery_id'] == 0)
        		{
        			$otherFee += $goodsOrderRow['delivery_fee'] + $goodsOrderRow['save_price'] + $goodsOrderRow['tax'];
        		}

				//退款单数据
        		$updateData = array(
					'order_no' => $orderRow['order_no'],
					'order_id' => $order_id,
					'user_id'  => $user_id,
        			'type'     => $type,
					'amount'   => $goodsOrderRow['real_price'] * $goodsOrderRow['goods_nums'],
					'time'     => ITime::getDateTime(),
					'content'  => $content,
					'goods_id' => $goodsOrderRow['goods_id'],
					'product_id' => $goodsOrderRow['product_id'],
				);
        		//退款额计算：将促销优惠和红包优惠平均分配
				$order_reduce = $orderRow['pro_reduce'] + $orderRow['ticket_reduce'];
				$updateData['amount'] -= $updateData['amount'] * $order_reduce/($orderRow['real_amount']+$orderRow['pro_reduce'])+ $otherFee;

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

        		$this->redirect('refunds');
        		exit;
        	}
        	else
        	{
        		$message = '此商品已经做了退换货处理，请耐心等待';
        	}
        }
        else
        {
        	$message = '订单未付款';
        }

        $this->redirect('refunds',false);
        Util::showMessage($message);
    }
    /**
     * @brief 退款申请删除
     */
    public function refunds_del()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $model = new IModel("refundment_doc");
        $model->del("id = ".$id." and user_id = ".$this->user['user_id']);
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
		$order_id = IFilter::act(IReq::get('order_id'),'int');
		if($order_id)
		{
			$orderDB  = new IModel('order');
			$orderRow = $orderDB->getObj('id = '.$order_id.' and user_id = '.$this->user['user_id']);
			if($orderRow)
			{
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
	    		ISafe::set('user_pwd',$passwordMd5);
	    		$message = '密码修改成功';
	    	}
	    	else
	    	{
	    		$message = '密码修改失败';
	    	}
		}

    	$this->redirect('password',false);
    	Util::showMessage($message);
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
		    		$trueOrderNo   = Preorder_Class::getTrueOrderNo($return['order_no']);
		    		$orderRow = $orderObj->getObj('order_no  = "'.IFilter::act($trueOrderNo).'" and (pay_status = 0 and type!=4 || pay_status in (0,1) and type=4) and user_id = '.$user_id);
		    		
		    		if(empty($orderRow))
		    		{
		    			IError::show(403,'订单已经被处理过，请查看订单状态');
		    			exit;
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
		if($res['errorCode']==0){
			$code = rand(000000,999999);
			ISafe::set('mobileValidate',array('code'=>$code,'phone'=>$phone,'time'=>time()));
			$text = smsTemplate::checkCode(array('{mobile_code}'=>$code));
			if(!hsms::send($phone,$text)){
				$res['errorCode']=-1;
				$res['mess']='系统繁忙，请稍候再试';
			}
				
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
    	$content = IFilter::act(IReq::get('content'),'int');
    	$db_order = new IModel('order');
    	if(!$db_order->getObj('id='.$order_id.' and user_id='.$this->user['user_id'])){
    		$this->redirect('fapiao');
    	}
    	$db_fapiao = new IModel('order_fapiao');
    	
    	$data = array(
    		'order_id'=> $order_id,
    		'type'    => $type,
    		'taitou'  => $taitou,
    		'content' => $content,
    		'create_time'=> ITime::getDateTime(),
    		'user_id' => $this->user['user_id']
    	);
    	
    	if($id || $id=$db_fapiao->getField('order_id='.$order_id,'id')){//后续编辑
    		$db_fapiao->setData($data);
    		$db_fapiao->update('order_id='.$order_id);
    	}else{//第一次添加
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
    public function preorder(){
    
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
    
    					//确认收货以后直接跳转到评论页面
    					$this->redirect('evaluation');
    				}
    			}
    			break;
    	}
    	$this->redirect("preorder_detail/id/$id");
    }
 
}