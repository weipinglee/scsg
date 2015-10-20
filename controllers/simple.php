<?php
/**
 * @file Simple.php
 * @brief
 * @author 
 * @date 2011-03-22
 * @version 0.6
 * @note
 */
/**
 * @brief Simple
 * @class Simple
 * @note
 */
class Simple extends IController
{
    public $layout='site_mini';

	function init()
	{
		CheckRights::checkUserRights();
	}

	function login()
	{
		$this->layout = 'site_reg';
		//如果已经登录，就跳到ucenter页面
		if( ISafe::get('user_id') != null  )
		{
			$this->redirect("/ucenter/index");
		}
		else
		{
			$this->redirect('login');
		}
	}
	
	function reg(){
		$this->layout = 'site_reg';
		$this->redirect('reg');
	}

	//退出登录
    function logout()
    {
    	ISafe::clearAll();
    	$this->redirect('login');
    }
    //设置手机验证码
	function getMobileValidateCode(){
		
		$phone = IFilter::act(IReq::get('phone'));
		$res = array('errorCode'=>0);
		if($phone=='')$res['errorCode']==1;
		if(!$phone)$res['errorCode']==15;
		if($res['errorCode']==0){
			$text = rand(000000,999999);
			ISafe::set('mobileValidate',array('num'=>$text,'time'=>time()));
			$text = smsTemplate::checkCode(array('{mobile_code}'=>$text));
			if(!hsms::send($phone,$text))
				$res['errorCode']=-1;
		}
		echo JSON::encode($res);
		
		
	}
	//验证手机验证码
	function checkMobileValidateCode($num){
		if($mobileValidateSess = Isafe::get('mobileValidate')){
			if(time() - $mobileValidateSess['time']>=1800){//session过期
				return 41;
			}else if($mobileValidateSess['num']!=$num){
				return 2;//错误
			}else return 0;//正确
		}
		else return 7;//没有验证码
	}
    //用户注册
    function reg_act()
    {
    	$data = array('errorCode'=>0);
    	$phone = IFilter::act(IReq::get('phone','post'));
    	$password   = IFilter::act(IReq::get('password','post'));
    	$password2 = IFilter::act(IReq::get('password2','post'));
    	$validPhoneCode = IFilter::act(Ireq::get('validPhoneCode','post'),'int');
    	$type = IFilter::act(IReq::get('type','post'));
    	$email = '';
    	if($type==1){//邮箱注册
    		$email = IFilter::act(IReq::get('email','post'));
    		if(!IValidate::email($email))
    			$data['errorCode']=3;
    	}
    	
    	if(!IValidate::phone($phone))
    	{
    		$data['errorCode']=15;
    	}
	  	else if($password != $password2)
	  	{
	   		$data['errorCode']=4;
	  	}
       	if($data['errorCode']==0){
       		$data['errorCode'] = self::checkMobileValidateCode($validPhoneCode);
       	}
	  	
    	if($data['errorCode']==0 ){
    		$userObj = new IModel('user');
    		
    		if($type==1 && !!$userObj->getObj(" email = '".$email."'",'id')){
    			$data['errorCode']=18;
    		}
    		else if($userObj->getObj('phone = '.$phone,'id')){
    			$data['errorCode']=16;
    		}
    		else
    			{
    				$userArray = array(
    						'email'    => $email,
    						'phone'    => $phone,
    						'password' => md5($password),
    				);
    				$userObj->setData($userArray);
    				$user_id = $userObj->add();
    				$userObj->commit();
    				if($user_id)
    				{
    					$group = new IModel('user_group');
    					$group_id =$group->getField('is_default=1','id');
    					
    					//member表
    					$memberArray = array(
    							'user_id' => $user_id,
    							'time'    => ITime::getDateTime(),
    							'status'  => 1,
    					);
    					if($group_id)$memberArray['group_id']=$group_id;
    					if($type==1)$memberArray['status'] = 4;
    					$memberObj = new IModel('member');
    					$memberObj->setData($memberArray);
    					$memberObj->add();
    			
    					//邮箱激活帐号
    					if($type == 1)
    					{
    						//$data['sendRes']=$this->send_check_mail();
    						
    					}
    					ISafe::set('phone',$phone);
    					ISafe::set('email',$email);
    					ISafe::set('user_id',$user_id);
    					ISafe::set('user_pwd',$userArray['password']);
    				}else{
    					$data['errorCode']=13;
    				}
    				 
    			}
    		
    	}
    	echo JSON::encode($data);
    }

    //用户登录
    function login_act()
    {
    	$login_info = IFilter::act(IReq::get('login_info','post'));
    	$password   = IFilter::act(IReq::get('password','post'));
    	//$remember   = IFilter::act(IReq::get('remember','post'));
    	$autoLogin  = IFilter::act(IReq::get('isAutoLogin','post'));
		
		$password   = md5($password);
		$captcha = IFilter::act(IReq::get('validCode'),'str');
		
		$data=array('errorCode'=>0);
    	if($login_info == '')
    	{
    		$data['errorCode'] = 1;
    	}
    	else if($password==''){
    		$data['errorCode'] = 2;
    	}
    	else if(($errTimes = $this->getErrTimes($login_info))>7){//帐户锁定，打电话解冻
    		$data['errorCode'] = 13;
    	}
    	else if($errTimes>3 && ISafe::get('captcha')!=$captcha){//二次添加
    			$data['errorCode'] = 10;
    	}
    	else
    	{
    		if($userRow = CheckRights::isValidUser($login_info,$password))
    		{	
    			$M = new IModel('user');
    			$where = 'phone = "'.$login_info.'" OR email = "'.$login_info.'" OR username = "'.$login_info.'"';
    			$M->setData(array('err_times'=>0));
    			$M->update($where);
    			
				CheckRights::loginAfter($userRow);

// 				//记住帐号
// 				if($remember == 1)
// 				{
// 					ICookie::set('loginName',$login_info);
// 				}

				//自动登录
				if($autoLogin == 1)
				{
					ICookie::set('autoLogin',$autoLogin);
				}

				
    		}
    		else
    		{
    			//邮箱未验证
    			$userDB = new IModel('user as u,member as m');
    			$userRow= $userDB->getObj(" (u.username = '{$login_info}' or u.email = '{$login_info}' or u.phone = '{$login_info}') and password = '{$password}' and u.id = m.user_id");

				if($userRow)
				{
					if($userRow['status']==4)//邮箱未验证
					{
						$message = "您的邮箱还未验证，请点击下面的链接发送您的邮箱验证邮件！";
						$data['returnUrl'] = IUrl::creatUrl('/site/success?message='.urlencode($message).'&email='.$userRow['email']);
					}
					else if($userRow['status']==3){//后台锁定
						$data['errorCode'] = 9;
					}
					else if($userRow['status']==2){
						$data['errorCode'] = 15;
					}
				}
				else
				{
					$M = new Imodel('user');
					$M->addNum(array('username'=>$login_info,'phone'=>$login_info,'email'=>$login_info),array('err_times'=>1),0);//zi
					$data['errorCode'] = 7;//密码账号不匹配
					$data['errorTimes'] = $errTimes + 1;
				}
    		}
    	}
		echo JSON::encode($data);
    	
    }

    //商品加入购物车[ajax]
    function joinCart()
    {
    	$link       = IReq::get('link');
    	$goods_id   = intval(IReq::get('goods_id'));
    	$goods_num  = IReq::get('goods_num') === null ? 1 : intval(IReq::get('goods_num'));
    	$type       = IFilter::act(IReq::get('type'));

		//加入购物车
    	$cartObj   = new Cart();
    	$addResult = $cartObj->add($goods_id,$goods_num,$type);
    
    	if($link != '')
    	{
    		if($addResult === false)
    		{
    			$this->cart(false);
    			Util::showMessage($cartObj->getError());
    		}
    		else
    		{
    			$this->redirect($link);
    		}
    	}
    	else
    	{
	    	if($addResult === false)
	    	{
		    	$result = array(
		    		'isError' => true,
		    		'message' => $cartObj->getError(),
		    	);
	    	}
	    	else
	    	{
		    	$result = array(
		    		'isError' => false,
		    		'message' => '添加成功',
		    	);
	    	}
	    	echo JSON::encode($result);
    	}
    }

    //根据goods_id获取货品
    function getProducts()
    {
    	$id           = IFilter::act(IReq::get('id'),'int');
    	$productObj   = new IModel('products');
    	$productsList = $productObj->query('goods_id = '.$id,'sell_price,id,spec_array,goods_id','store_nums','desc',7);
		if($productsList)
		{
			foreach($productsList as $key => $val)
			{
				$productsList[$key]['specData'] = Block::show_spec($val['spec_array']);
			}
			echo JSON::encode($productsList);
		}
    }

    //删除购物车
    function removeCart()
    {
    	$link      = IReq::get('link');
    	$goods_id  = IFilter::act(IReq::get('goods_id'),'int');
    	$type      = IReq::get('type');

    	$cartObj   = new Cart();
    	$cartInfo  = $cartObj->getMyCart();
    	$delResult = $cartObj->del($goods_id,$type);

    	if($link != '')
    	{
    		if($delResult === false)
    		{
    			$this->cart(false);
    			Util::showMessage($cartObj->getError());
    		}
    		else
    		{
    			$this->redirect($link);
    		}
    	}
    	else
    	{
	    	if($delResult === false)
	    	{
	    		$result = array(
		    		'isError' => true,
		    		'message' => $cartObj->getError(),
	    		);
	    	}
	    	else
	    	{
		    	$goodsRow = $cartInfo[$type]['data'][$goods_id];
		    	$cartInfo['sum']   -= $goodsRow['sell_price'] * $goodsRow['count'];
		    	$cartInfo['count'] -= $goodsRow['count'];

		    	$result = array(
		    		'isError' => false,
		    		'data'    => $cartInfo,
		    	);
	    	}

	    	echo JSON::encode($result);
    	}
    }
    //购物车删除多个商品
    function removeCartMany(){
    	$data = IFilter::act(IReq::get('str'));
    	if(!$data)return false;
    	$arr = explode('|',$data);
    	foreach($arr as $key=>$v){//echo $v;
    		if($v==''){
    			unset($arr[$key]);
    			continue;
    		}
    		$arr[$key]=explode('_',$v);
    	}
    	
    	$cartObj   = new Cart();
    	$cartInfo  = $cartObj->getMyCart();
    	$delResult = $cartObj->del_many($arr);
    	if($delResult){
    		echo 1;
    	}
    	else echo 0;
    }

    //清空购物车
    function clearCart()
    {
    	$cartObj = new Cart();
    	$cartObj->clear();
    	$this->redirect('cart');
    }

    //购物车div展示
    function showCart()
    {
    	$cartObj  = new Cart();
    	$cartList = $cartObj->getMyCart();
    	
    	$data['data'] = array_merge($cartList['goods']['data'],$cartList['product']['data']);
    	$data['count']= $cartList['count'];
    	$data['sum']  = $cartList['sum'];
    	echo JSON::encode($data);
    }

    //购物车页面及商品价格计算[复杂]
    function cart($redirect = false)
    {
    	//防止页面刷新
    	header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);

		//开始计算购物车中的商品价格
    	$countObj = new CountSum();
    	$result   = $countObj->cart_count();

    	//返回值
    	$this->final_sum = $result['final_sum'];
    	$this->promotion = $result['promotion'];
    	$this->proReduce = $result['proReduce'];
    	$this->sum       = $result['sum'];
    	$this->goodsList = $result['goodsList'];
    	$this->count     = $result['count'];
    	$this->reduce    = $result['reduce'];
    	$this->weight    = $result['weight'];
    	
    	//将商品按商家分开
    	
    	$this->goodsList = $this->goodsListBySeller($this->goodsList);
		//渲染视图
    	$this->redirect('cart',$redirect);
    }
    //将商品列表按商家分开
    private function goodsListBySeller($goodsList){
    	$goodsListSeller = array();
    	foreach($goodsList as $key => $value){
    		if(!isset($goodsListSeller[$value['seller_id']])){
    			$goodsListSeller[$value['seller_id']]['seller_name'] = $value['seller_id']==0 ? '平台':API::run('getSellerInfo',$value['seller_id'],'true_name')['true_name'];
    		}
    		$goodsListSeller[$value['seller_id']][] = $value;
    	}
    	return $goodsListSeller;
    }

    //计算促销规则[ajax]
    function promotionRuleAjax()
    {
    	$promotion = array();
    	$proReduce = 0;

    	//总金额满足的促销规则
    	if($this->user['user_id'])
    	{
    		$final_sum = intval(IReq::get('final_sum'));

    		//获取 user_group
	    	$groupObj = new IModel('member as m,user_group as g');
			$groupRow = $groupObj->getObj('m.user_id = '.$this->user['user_id'].' and m.group_id = g.id','g.*');
			$groupRow['id'] = empty($groupRow) ? 0 : $groupRow['id'];

	    	$proObj = new ProRule($final_sum);
	    	$proObj->setUserGroup($groupRow['id']);

	    	$promotion = $proObj->getInfo();
	    	$proReduce = number_format($final_sum - $proObj->getSum(),2);
    	}

		$result = array(
    		'promotion' => $promotion,
    		'proReduce' => $proReduce,
		);

    	echo JSON::encode($result);
    }

    //购物车寄存功能[写入]
    function deposit_cart_set()
    {
    	$is_ajax = IReq::get('is_ajax');

    	//必须为登录用户
    	if($this->user['user_id'] == null)
    	{
			$callback = "/simple/cart";
    		$this->redirect('/simple/login?callback={$callback}');
    	}

    	//获取购物车中的信息
    	$cartObj    = new Cart();
    	$myCartInfo = $cartObj->getMyCart();

		/*寄存的数据
		格式：goods => array (id => count);
		*/
    	$depositArray = array();

    	if(isset($myCartInfo['goods']['id']) && !empty($myCartInfo['goods']['id']))
    	{
    		foreach($myCartInfo['goods']['id'] as $id)
    		{
    			$depositArray['goods'][$id]   = $myCartInfo['goods']['data'][$id]['count'];
    		}
    	}

    	if(isset($myCartInfo['product']['id']) && !empty($myCartInfo['product']['id']))
    	{
    		foreach($myCartInfo['product']['id'] as $id)
    		{
    			$depositArray['product'][$id] = $myCartInfo['product']['data'][$id]['count'];
    		}
    	}

    	if(empty($depositArray))
    	{
    		$isError = true;
    		$message = '您的购物车中没有商品';
    	}
    	else
    	{
    		$isError = false;
	    	$dataArray   = array(
	    		'user_id'     => $this->user['user_id'],
	    		'content'     => serialize($depositArray),
	    		'create_time' => ITime::getDateTime(),
	    	);

	    	$goodsCarObj = new IModel('goods_car');
	    	$goodsCarRow = $goodsCarObj->getObj('user_id = '.$this->user['user_id']);
	    	$goodsCarObj->setData($dataArray);

	    	if(empty($goodsCarRow))
	    	{
	    		$goodsCarObj->add();
	    	}
	    	else
	    	{
	    		$goodsCarObj->update('user_id = '.$this->user['user_id']);
	    	}
	    	$message = '寄存成功';
    	}

		//ajax方式
    	if($is_ajax == 1)
    	{
    		$result = array(
    			'isError' => $isError,
    			'message' => $message,
    		);

    		echo JSON::encode($result);
    	}

    	//传统跳转方式
    	else
    	{
			//页面跳转
			$this->cart();
	    	if(isset($message))
	    	{
	    		Util::showMessage($message);
	    	}
    	}
    }

    //购物车寄存功能[读取]ajax
    function deposit_cart_get()
    {
    	//isError:0正常;1错误
    	$result = array('isError' => 1,'message' => '');

    	//必须为登录用户
    	if($this->user['user_id'] == null)
    	{
    		$result['message'] = '用户尚未登录';
    		echo JSON::encode($result);
    		return;
    	}

    	$goodsCatObj = new IModel('goods_car');
    	$goodsCarRow = $goodsCatObj->getObj('user_id = '.$this->user['user_id']);

    	if(!isset($goodsCarRow['content']))
    	{
    		$result['message'] = '您没有寄存任何商品';
    		echo JSON::encode($result);
    		return;
    	}

		$depositContent = unserialize($goodsCarRow['content']);

    	//获取购物车中的信息
    	$cartObj    = new Cart();
    	$myCartInfo = $cartObj->getMyCartStruct();

    	if(isset($depositContent['goods']))
    	{
	    	foreach($depositContent['goods'] as $id => $count)
	    	{
	    		$depositGoods = $cartObj->getUpdateCartData($myCartInfo,$id,$count,'goods');
	    		$myCartInfo = $depositGoods;
	    	}
    	}

    	if(isset($depositContent['product']))
    	{
	    	foreach($depositContent['product'] as $id => $count)
	    	{
	    		$depositProducts = $cartObj->getUpdateCartData($myCartInfo,$id,$count,'product');
	    		$myCartInfo = $depositProducts;
	    	}
    	}

    	//写入购物车
    	$cartObj->setMyCart($myCartInfo);
    	$result['isError'] = 0;
    	echo JSON::encode($result);
    }

    //清空寄存购物车
    function deposit_cart_clear()
    {
    	//必须为登录用户
    	if($this->user['user_id'] == null)
    	{
    		$this->redirect('/simple/login?callback=/simple/cart');
    	}

    	$goodsCarObj = new IModel('goods_car');
    	$goodsCarObj->del('user_id = '.$this->user['user_id']);
    	$this->cart();
    	Util::showMessage('操作成功');
    }

    //填写订单信息cart2
    function cart2()
    {
    //	$paymentList=Api::run('getSellerDelivery',array('#seller_id#'=>1));
    	//print_r($paymentList);exit();
		$id        = IFilter::act(IReq::get('id'),'int');
		$type      = IFilter::act(IReq::get('type'));//goods,product
		$promo     = IFilter::act(IReq::get('promo'));
		$active_id = IFilter::act(IReq::get('active_id'),'int');
		$buy_num   = IReq::get('num') ? IFilter::act(IReq::get('num'),'int') : 1;

    	//必须为登录用户
    	if($this->user['user_id'] == null)
    	{
    		if($id == 0 || $type == '')
    		{
    			$this->redirect('/simple/login?tourist&callback=/simple/cart2');
    		}
    		else
    		{
    			$url  = '/simple/login?tourist&callback=/simple/cart2/id/'.$id.'/type/'.$type.'/num/'.$buy_num;
    			$url .= $promo     ? '/promo/'.$promo         : '';
    			$url .= $active_id ? '/active_id/'.$active_id : '';
    			$this->redirect($url);
    		}
    	}

		//游客的user_id默认为0
    	$user_id = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];

		//计算商品
		$countSumObj = new CountSum($user_id);

		
		if($id && $type)//立即购买
		{
			$result = $countSumObj->direct_count($id,$type,$buy_num,$promo,$active_id);
			
			$this->gid       = $id;
			$this->type      = $type;
			$this->num       = $buy_num;
			$this->promo     = $promo;
			$this->active_id = $active_id;
		}
		else//购物车
		{
			//计算购物车中的商品价格
			$result = $countSumObj->cart_count();
			
		}
		
		
		//检查商品合法性或促销活动等有错误
		if( is_string($result))
		{
			IError::show(403,$result);
			exit;
		}
		if($result['sum']==0){
			$this->redirect('cart');
		}
    	//获取收货地址
    	$addressObj  = new IModel('address');
    	$addressList = $addressObj->query('user_id = '.$user_id);

		//更新$addressList数据
    	$this->defaultAddressId = -1;
    	foreach($addressList as $key => $val)
    	{
    		$temp = area::name($val['province'],$val['city'],$val['area']);
    		if(isset($temp[$val['province']]) && isset($temp[$val['city']]) && isset($temp[$val['area']]))
    		{
	    		$addressList[$key]['province_val'] = $temp[$val['province']];
	    		$addressList[$key]['city_val']     = $temp[$val['city']];
	    		$addressList[$key]['area_val']     = $temp[$val['area']];
	    		if($val['default'] == 1)
	    		{
	    			$this->defaultAddressId = $val['id'];
	    		}
    		}
    	}

		//获取用户的道具红包和用户的习惯方式
		$this->prop = array();
		$memberObj = new IModel('member');
		$memberRow = $memberObj->getObj('user_id = '.$user_id,'prop,custom');
		if(Common::activeProp($promo)){//判断活动是否允许使用代金券
			if(isset($memberRow['prop']) && ($propId = trim($memberRow['prop'],',')))
			{
				$porpObj = new IModel('prop');
				$this->prop = $porpObj->query('id in ('.$propId.') and NOW() between start_time and end_time and type = 0 and is_close = 0 and is_userd = 0 and is_send = 1','id,name,value,card_name');
			}
		}else{
			$this->prop_not = true;
		}
		

		if(isset($memberRow['custom']) && $memberRow['custom'])
		{
			$this->custom = unserialize($memberRow['custom']);
		}
		else
		{
			$this->custom = array(
				'payment'  => '',
				'delivery' => '',
				'takeself' => '',
			);
		}

    	//返回值
    	$this->final_sum = $result['final_sum'];
    	$this->promotion = $result['promotion'];
    	$this->proReduce = $result['proReduce'];
    	$this->sum       = $result['sum'];
    	$this->goodsList = $result['goodsList'];
    	$this->count       = $result['count'];
    	$this->reduce      = $result['reduce'];
    	$this->weight      = $result['weight'];
    	$this->freeFreight = $result['freeFreight'];
    	
    	//商品列表按商家分开
    	$this->goodsList = $this->goodsListBySeller($this->goodsList);
    
    	//判断所选商品商家是否支持货到付款,有一个商家不支持则不显示
    	$sellerObj = new IModel('seller');
    	$this->freight_collect=1;
    	$where = array('id'=>array_keys($this->goodsList));
    	$sellerStr = implode(',',$where['id']);//2,1,0
    	//print_r($where);
    	
    	//判断是否支持货到付款
    	if($sellerStr && $sellerObj->query('id in ('.$sellerStr.') and freight_collect = 0')){
    		$this->freight_collect=0;
    	}
    	
		//收货地址列表
		$this->addressList = $addressList;
		
		//获取商品税金
		$this->goodsTax    = $result['tax'];
		
		//print_r($this->goodsList);
		//获取配送方式列表（一个商家不支持则不显示）
		$allDeliveryType = Api::run('getDeliveryList');
		$deli_exe = new IModel('delivery_extend');
		foreach($allDeliveryType as $key=>$val){
			if($deli_exe->getObj(' delivery_id='.$val['id'].' and seller_id in ('.$sellerStr.') and is_open = 0','id')){
				unset($allDeliveryType[$key]);
			}
		}
		
		$this->allDeliveryType = $allDeliveryType;
    	//渲染页面
    	$this->redirect('cart2');
    }
	//手机端选择收货地址
    function address(){
    	if($this->user['user_id']==null)$this->redirect('login');
    	$user_id = $this->user['user_id'];
    	//获取收货地址
    	$addressObj  = new IModel('address');
    	$addressList = $addressObj->query('user_id = '.$user_id);
    	
    	//更新$addressList数据
    	foreach($addressList as $key => $val)
    	{
    		$temp = area::name($val['province'],$val['city'],$val['area']);
    		if(isset($temp[$val['province']]) && isset($temp[$val['city']]) && isset($temp[$val['area']]))
    		{
    			$addressList[$key]['province_val'] = $temp[$val['province']];
    			$addressList[$key]['city_val']     = $temp[$val['city']];
    			$addressList[$key]['area_val']     = $temp[$val['area']];
    		}
    	}
    	$this->addressList = $addressList;
    	$this->redirect('address');
    }

	/**
	 * 生成订单
	 */
    function cart3()
    {
    	$accept_name   = IFilter::act(IReq::get('accept_name'));
    	$province      = IFilter::act(IReq::get('province'),'int');
    	$city          = IFilter::act(IReq::get('city'),'int');
    	$area          = IFilter::act(IReq::get('area'),'int');
    	$address       = IFilter::act(IReq::get('address'));
    	$mobile        = IFilter::act(IReq::get('mobile'));
    	$telphone      = IFilter::act(IReq::get('telphone'));
    	$zip           = IFilter::act(IReq::get('zip'));
    	$delivery_id   = IFilter::act(IReq::get('delivery_id'),'int');
    	$accept_time   = IFilter::act(IReq::get('accept_time'));
    	$payment       = IFilter::act(IReq::get('payment'),'int');
    	$order_message = IFilter::act(IReq::get('message'));
    	$ticket_id     = IFilter::act(IReq::get('ticket_id'),'int');
    	$taxes         = IFilter::act(IReq::get('taxes'),'int');
    	$insured       = IFilter::act(IReq::get('insured'));
    	$tax_title     = IFilter::act(IReq::get('tax_title'),'text');
    	$gid           = IFilter::act(IReq::get('direct_gid'),'int');
    	$num           = IFilter::act(IReq::get('direct_num'),'int');
    	$type          = IFilter::act(IReq::get('direct_type'));//商品或者货品
    	$promo         = IFilter::act(IReq::get('direct_promo'));
    	$active_id     = IFilter::act(IReq::get('direct_active_id'),'int');
    	$takeself      = IFilter::act(IReq::get('takeself'),'int');
    	$order_no      = Order_Class::createOrderNum();
    	$order_type    = 0;
    	$dataArray     = array();
    
		//防止表单重复提交
    	if(IReq::get('timeKey') != null)
    	{
    		if(ISafe::get('timeKey') == IReq::get('timeKey'))
    		{
	    		IError::show(403,'订单数据不能被重复提交');
	    		exit;
    		}
    		else
    		{
    			ISafe::set('timeKey',IReq::get('timeKey'));
    		}
    	}

    	if($province == 0 || $city == 0 || $area == 0)
    	{
    		IError::show(403,'请填写收货地址的省市地区');
    	}

    	if($delivery_id == 0)
    	{
    		IError::show(403,'请选择配送方式');
    	}

    	$user_id = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];

		//配送方式,判断是否为货到付款
 		$deliveryObj = new IModel('delivery');
 		$deliveryRow = $deliveryObj->getObj('id = '.$delivery_id);

		//计算费用
    	$countSumObj = new CountSum($user_id);

    	//直接购买商品方式
    	if($type && $gid)
    	{
    		//计算$gid商品
    		$goodsResult = $countSumObj->direct_count($gid,$type,$num,$promo,$active_id);
    	}
    	else
    	{
			//计算购物车中的商品价格$goodsResult
			$goodsResult = $countSumObj->cart_count();

			//清空购物车
			IInterceptor::reg("cart@onFinishAction");
    	}
    	//print_r($goodsResult);echo '</br>';

    	//判断商品商品是否存在
    	if(is_string($goodsResult) || empty($goodsResult['goodsList']))
    	{
    		IError::show(403,'商品数据错误');
    		exit;
    	}

    	//加入促销活动
    	if($promo && $active_id)
    	{
    		$activeObject = new Active($promo,$active_id,$user_id,$gid,$type,$num);
    		$order_type = $activeObject->getOrderType();
    	}

		//获取红包减免金额
		if($ticket_id != '')
		{
			$memberObj = new IModel('member');
			$memberRow = $memberObj->getObj('user_id = '.$user_id,'prop,custom');

			if(ISafe::get('ticket_'.$ticket_id) == $ticket_id || stripos(','.trim($memberRow['prop'],',').',',','.$ticket_id.',') !== false)
			{
				$propObj   = new IModel('prop');
				$ticketRow = $propObj->getObj('id = '.$ticket_id.' and NOW() between start_time and end_time and type = 0 and is_close = 0 and is_userd = 0 and is_send = 1');
				if(!empty($ticketRow))
				{
					$dataArray['prop'] = $ticket_id;
				}

				//锁定红包状态
				$propObj->setData(array('is_close' => 2));
				$propObj->update('id = '.$ticket_id);
			}
		}

		$paymentObj = new IModel('payment');
		$paymentRow = $paymentObj->getObj('id = '.$payment,'type,name');
		$paymentName= $paymentRow['name'];
		$paymentType= $paymentRow['type'];

		//最终订单金额计算
		$orderData = $countSumObj->countOrderFee($goodsResult,$province,$delivery_id,$payment,$insured,$taxes);
		//print_r($orderData);
		if(is_string($orderData))
		{
			IError::show(403,$orderData);
			exit;
		}

		//生成的订单数据
		$dataArray = array(
			'order_no'            => $order_no,
			'user_id'             => $user_id,
			'accept_name'         => $accept_name,
			'pay_type'            => $payment,
			'distribution'        => $delivery_id,
			'postcode'            => $zip,
			'telphone'            => $telphone,
			'province'            => $province,
			'city'                => $city,
			'area'                => $area,
			'address'             => $address,
			'mobile'              => $mobile,
			'create_time'         => ITime::getDateTime(),
			'postscript'          => $order_message,
			'accept_time'         => $accept_time,
			'exp'                 => $goodsResult['exp'],
			'point'               => $goodsResult['point'],
			'type'                => $order_type,

			//红包道具
			'prop'                => isset($dataArray['prop']) ? $dataArray['prop'] : null,

			//商品价格
			'payable_amount'      => $goodsResult['sum'],//商品原总价
			'real_amount'         => $goodsResult['final_sum'],//商品元总价-促销优惠-闪购/会员价优惠    （未减去红包金额）

			//运费价格
			'payable_freight'     => $orderData['deliveryOrigPrice'],
			'real_freight'        => $orderData['deliveryPrice'],

			//手续费
			'pay_fee'             => $orderData['paymentPrice'],

			//税金
			'invoice'             => $taxes ? 1 : 0,
			'invoice_title'       => $tax_title,
			'taxes'               => $orderData['taxPrice'],

			//优惠价格（包括闪购、会员价差价，红包，促销活动减价）
			'promotions'          => $goodsResult['proReduce'] + $goodsResult['reduce'] + (isset($ticketRow['value']) ? $ticketRow['value'] : 0),

			//促销活动优惠
			'pro_reduce'         => $goodsResult['proReduce'] ,
			//红包减免金额
			'ticket_reduce'      => isset($ticketRow['value']) ? $ticketRow['value'] : 0,
			//订单应付总额（商品final_num加上，税金，运费，再减去红包）
			'order_amount'        => $orderData['orderAmountPrice'] - (isset($ticketRow['value']) ? $ticketRow['value'] : 0),

			//订单保价
			'if_insured'          => $insured ? 1 : 0,
			'insured'             => $orderData['insuredPrice'],

			//自提点ID
			'takeself'            => $takeself,

			//促销活动ID
			'active_id'           => $active_id,
		);

		$dataArray['order_amount'] = $dataArray['order_amount'] <= 0 ? 0 : $dataArray['order_amount'];

		$orderObj  = new IModel('order');
		$orderObj->setData($dataArray);

		$this->order_id = $orderObj->add();

		if($this->order_id == false)
		{
			IError::show(403,'订单生成错误');
		}

		/*将订单中的商品插入到order_goods表*/
    	$orderInstance = new Order_Class();
    	$orderInstance->insertOrderGoods($this->order_id,$orderData['goodsResult']);

		//记录用户默认习惯的数据
		if(!isset($memberRow['custom']))
		{
			$memberObj = new IModel('member');
			$memberRow = $memberObj->getObj('user_id = '.$user_id,'custom');
		}

		$memberData = array(
			'custom' => serialize(
				array(
					'payment'  => $payment,
					'delivery' => $delivery_id,
					'takeself' => $takeself,
				)
			),
		);
		$memberObj->setData($memberData);
		$memberObj->update('user_id = '.$user_id);

		//收货地址的处理
		if($user_id)
		{
			$addressObj = new IModel('address');

			//如果用户之前没有收货地址,那么会自动记录此次的地址信息并且为默认
			$addressRow = $addressObj->getObj('user_id = '.$user_id);
			if(empty($addressRow))
			{
				$addressData = array('default'=>'1','user_id'=>$user_id,'accept_name'=>$accept_name,'province'=>$province,'city'=>$city,'area'=>$area,'address'=>$address,'zip'=>$zip,'telphone'=>$telphone,'mobile'=>$mobile);
				$addressObj->setData($addressData);
				$addressObj->add();
			}
			else
			{
				//如果用户有收货地址,但是没有设置默认项,那么会自动设置此次地址信息为默认
				$radio_address = intval(IReq::get('radio_address'));
				if($radio_address != 0)
				{
					$addressDefRow = $addressObj->getObj('user_id = '.$user_id.' and `default` = 1');
					if(empty($addressDefRow))
					{
						$addressData = array('default' => 1);
						$addressObj->setData($addressData);
						$addressObj->update('user_id = '.$user_id.' and id = '.$radio_address);
					}
				}
			}
		}

		//获取备货时间
		$siteConfigObj = new Config("site_config");
		$site_config   = $siteConfigObj->getInfo();
		$this->stockup_time = isset($site_config['stockup_time'])?$site_config['stockup_time']:2;

		//数据渲染
		$this->order_num   = $dataArray['order_no'];
		$this->final_sum   = $dataArray['order_amount'];
		$this->payment     = $paymentName;
		$this->paymentType = $paymentType;
		$this->delivery    = $deliveryRow['name'];
		$this->tax_title   = $tax_title;
		$this->deliveryType= $deliveryRow['type'];

		//订单金额为0时，订单自动完成
		if($this->final_sum <= 0)
		{
			$order_id = Order_Class::updateOrderStatus($dataArray['order_no']);
			if($order_id)
			{
				if($user_id)
				{
					$this->redirect('/site/success/message/'.urlencode("订单确认成功，等待发货").'/?callback=ucenter/order_detail/id/'.$order_id);
				}
				else
				{
					$this->redirect('/site/success/message/'.urlencode("订单确认成功，等待发货"));
				}
			}
			else
			{
				IError::show(403,'订单修改失败');
			}
		}
		else
		{
			$this->setRenderData($dataArray);
			$this->redirect('cart3');
		}
    }

    //到货通知处理动作
	function arrival_notice()
	{
		$user_id  = IFilter::act(ISafe::get('user_id'),'int');
		$email    = IFilter::act(IReq::get('email'));
		$mobile   = IFilter::act(IReq::get('mobile'));
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$register_time = date('Y-m-d H:i:s');

		if(!$goods_id)
		{
			IError::show(403,'商品ID不存在');
		}

		$model = new IModel('notify_registry');
		$obj = $model->getObj("email = '{$email}' and user_id = '{$user_id}' and goods_id = '$goods_id'");
		if(empty($obj))
		{
			$model->setData(array('email'=>$email,'user_id'=>$user_id,'mobile'=>$mobile,'goods_id'=>$goods_id,'register_time'=>$register_time));
			$model->add();
		}
		else
		{
			$model->setData(array('email'=>$email,'user_id'=>$user_id,'mobile'=>$mobile,'goods_id'=>$goods_id,'register_time'=>$register_time,'notify_status'=>0));
			$model->update('id = '.$obj['id']);
		}
		$this->redirect('/site/success',true);
	}

	/**
	 * @brief 邮箱找回密码进行
	 */
    function find_password_email()
	{
// 		$username = IReq::get('username');
// 		if($username === null || !Util::is_username($username)  )
// 		{
// 			IError::show(403,"请输入正确的用户名");
// 		}

		$email = IReq::get("email");
		if($email === null || !IValidate::email($email ))
		{
			IError::show(403,"请输入正确的邮箱地址");
		}

		$tb_user  = new IModel("user");
		//$username = IFilter::act($username);
		$email    = IFilter::act($email);
		$user     = $tb_user->getObj(" email='{$email}' ");
		if(!$user)
		{
			IError::show(403,"对不起，用户不存在");
		}
		$hash = IHash::md5( microtime(true) .mt_rand());

		//重新找回密码的数据
		$tb_find_password = new IModel("find_password");
		$tb_find_password->setData( array( 'hash' => $hash ,'user_id' => $user['id'] , 'addtime' => time() ) );

		if($tb_find_password->query("`hash` = '{$hash}'") || $tb_find_password->add())
		{
			$url     = IUrl::getHost().IUrl::creatUrl("/simple/restore_password/hash/{$hash}");
			$content = mailTemplate::findPassword(array("{url}" => $url));

			$smtp   = new SendMail();
			$result = $smtp->send($user['email'],"您的密码找回",$content);

			if($result===false)
			{
				IError::show(403,"发信失败,请重试！或者联系管理员查看邮件服务是否开启");
			}
		}
		else
		{
			IError::show(403,"生成HASH重复，请重试");
		}
		$message = "恭喜您，密码重置邮件已经发送！请到您的邮箱中去激活";
		$this->redirect("/site/success/message/".urlencode($message));
	}

	//手机短信找回密码
	function find_password_mobile()
	{
// 		$username = IReq::get('username');
// 		if($username === null || !Util::is_username($username)  )
// 		{
// 			IError::show(403,"请输入正确的用户名");
// 		}

		$mobile = IReq::get("mobile");
		if($mobile === null || !IValidate::mobi($mobile))
		{
			IError::show(403,"请输入正确的电话号码");
		}

		$mobile_code = IReq::get('mobile_code');
		if($mobile_code === null)
		{
			IError::show(403,"请输入短信校验码");
		}

		$userDB = new IModel('user');
		$userRow = $userDB->getObj('phone = "'.$mobile.'" ');
		if($userRow)
		{
			$findPasswordDB = new IModel('find_password');
			$dataRow = $findPasswordDB->getObj('user_id = '.$userRow['id'].' and hash = "'.$mobile_code.'"');
			if($dataRow)
			{
				//短信验证码已经过期
				if(time() - $dataRow['addtime'] > 3600)
				{
					$findPasswordDB->del("user_id = ".$userRow['user_id']);
					IError::show(403,"您的短信校验码已经过期了，请重新找回密码");
				}
				else
				{
					$this->redirect('/simple/restore_password/hash/'.$mobile_code);
				}
			}
			else
			{
				IError::show(403,"您输入的短信校验码错误");
			}
		}
		else
		{
			IError::show(403,"用户名与手机号码不匹配");
		}
	}

	//发送手机验证码短信
	function send_message_mobile()
	{
		//$username = IFilter::act(IReq::get('username'));
		$mobile = IFilter::act(IReq::get('mobile'));

// 		if($username === null || !Util::is_username($username))
// 		{
// 			die("请输入正确的用户名");
// 		}

		if($mobile === null || !IValidate::mobi($mobile))
		{
			die("请输入正确的手机号码");
		}

		$userDB = new IModel('user as u');
		$userRow = $userDB->getObj(' phone = "'.$mobile.'" ');

		if($userRow)
		{
			$findPasswordDB = new IModel('find_password');
			$dataRow = $findPasswordDB->query('user_id = '.$userRow['id'],'*','addtime','desc');
			$dataRow = current($dataRow);

			//120秒是短信发送的间隔
			if( isset($dataRow['addtime']) && (time() - $dataRow['addtime'] <= 120) )
			{
				die("申请验证码的时间间隔过短，请稍候再试");
			}
			$mobile_code = rand(10000,99999);
			$findPasswordDB->setData(array(
				'user_id' => $userRow['id'],
				'hash'    => $mobile_code,
				'addtime' => time(),
			));
			if($findPasswordDB->add())
			{
				$content = smsTemplate::findPassword(array('{mobile_code}' => $mobile_code));
				$result = Hsms::send($mobile,$content);
				if($result == 'success')
				{
					die('success');
				}
				die('短信发送失败');
			}
		}
		else
		{
			die('手机号码不存在');
		}
	}

	/**
	 * @brief 邮箱链接激活验证
	 */
	function restore_password()
	{
		$hash = IFilter::act(IReq::get("hash"));
		if(!$hash)
		{
			IError::show(403,"找不到校验码");
		}
		$tb = new IModel("find_password");
		$addtime = time() - 3600*72;
		$where  = " `hash`='$hash' AND addtime > $addtime ";
		$where .= $this->user['user_id'] ? " and user_id = ".$this->user['user_id'] : "";

		$row = $tb->getObj($where);
		if(!$row)
		{
			IError::show(403,"校验码已经超时");
		}

		$this->formAction = IUrl::creatUrl("/simple/do_restore_password/hash/$hash");
		$this->redirect("restore_password");
	}

	/**
	 * @brief 执行密码修改重置操作
	 */
	function do_restore_password()
	{
		$hash = IFilter::act(IReq::get("hash"));
		if(!$hash)
		{
			IError::show(403,"找不到校验码");
		}
		$tb = new IModel("find_password");
		$addtime = time() - 3600*72;
		$where  = " `hash`='$hash' AND addtime > $addtime ";
		$where .= $this->user['user_id'] ? " and user_id = ".$this->user['user_id'] : "";

		$row = $tb->getObj($where);
		if(!$row)
		{
			IError::show(403,"校验码已经超时");
		}

		//开始修改密码
		$pwd   = IReq::get("password");
		$repwd = IReq::get("repassword");
		if($pwd == null || strlen($pwd) < 6 || $repwd!=$pwd)
		{
			IError::show(403,"新密码至少六位，且两次输入的密码应该一致。");
		}
		$pwd = md5($pwd);
		$tb_user = new IModel("user");
		$tb_user->setData(array("password" => $pwd));
		$re = $tb_user->update("id='{$row['user_id']}'");
		if($re !== false)
		{
			$message = "修改密码成功";
			$tb->del("`hash`='{$hash}'");
			$this->redirect("/site/success/message/".urlencode($message));
			exit;
		}
		IError::show(403,"密码修改失败，请重试");
	}

    //添加收藏夹
    function favorite_add()
    {
    	$goods_id = IFilter::act(IReq::get('goods_id'),'int');
    	$message  = '';

    	if($goods_id == 0)
    	{
    		$message = '商品id值不能为空';
    	}
    	else if(!isset($this->user['user_id']) || !$this->user['user_id'])
    	{
    		$message = '请先登录';
    	}
    	else
    	{
    		$favoriteObj = new IModel('favorite');
    		$goodsRow    = $favoriteObj->getObj('user_id = '.$this->user['user_id'].' and rid = '.$goods_id);
    		if($goodsRow)
    		{
    			$message = '您已经关注过此件商品';
    		}
    		else
    		{
    			$catObj = new IModel('category_extend');
    			$catRow = $catObj->getObj('goods_id = '.$goods_id);
    			$cat_id = $catRow ? $catRow['category_id'] : 0;

	    		$dataArray   = array(
	    			'user_id' => $this->user['user_id'],
	    			'rid'     => $goods_id,
	    			'time'    => ITime::getDateTime(),
	    			'cat_id'  => $cat_id,
	    		);
	    		$favoriteObj->setData($dataArray);
	    		$favoriteObj->add();
	    		$goodsObj = new IModel('goods');
	    		$goodsObj->addNum('id='.$goods_id,array('favorite'=>1));
	    		$message = '关注成功';
    		}
    	}
		$result = array(
			'isError' => true,
			'message' => $message,
		);

    	echo JSON::encode($result);
    }

    //获取oauth登录地址
    public function oauth_login()
    {
    	$id       = IFilter::act(IReq::get('id'),'int');
    	$callback = IFilter::act(IReq::get('callback'),'text');

    	//记录回调地址
    	ISafe::set('callback',$callback);

    	if($id)
    	{
    		$oauthObj = new Oauth($id);
			$result   = array(
				'isError' => false,
				'url'     => $oauthObj->getLoginUrl(),
			);
    		ISession::set('oauth',$id);
    	}
    	else
    	{
			$result   = array(
				'isError' => true,
				'message' => '请选择要登录的平台',
			);
    	}
    	echo JSON::encode($result);
    }

    //获取令牌
    public function oauth_callback()
    {
    	$id = intval(ISession::get('oauth'));
    	if(!$id)
    	{
    		$this->redirect('login');
    		exit;
    	}
    	$oauthObj = new Oauth($id);
    	$result   = $oauthObj->checkStatus($_GET);

    	if($result === true)
    	{
    		$oauthObj->getAccessToken($_GET);
	    	$userInfo = $oauthObj->getUserInfo();

	    	if(isset($userInfo['id']) && isset($userInfo['name']) && $userInfo['id'] != '' &&  $userInfo['name'] != '')
	    	{
	    		$this->bindUser($userInfo,$id);
	    	}
	    	else
	    	{
	    		$this->redirect('login');
	    	}
    	}
    	else
    	{
    		$this->redirect('login');
    	}
    }

    //同步绑定用户数据
    public function bindUser($userInfo,$oauthId)
    {
    	$oauthUserObj = new IModel('oauth_user');
    	$oauthUserRow = $oauthUserObj->getObj("oauth_user_id = '{$userInfo['id']}' and oauth_id = '{$oauthId}' ",'user_id');

    	//没有绑定账号
    	if(empty($oauthUserRow))
    	{
	    	$userObj   = new IModel('user');
	    	$userCount = $userObj->getObj("username = '{$userInfo['name']}'",'count(*) as num');

	    	//没有重复的用户名
	    	if($userCount['num'] == 0)
	    	{
	    		$username = $userInfo['name'];
	    	}
	    	else
	    	{
	    		//随即分配一个用户名
	    		$username = $userInfo['name'].$userCount['num'];
	    	}

	    	ISafe::set('oauth_username',$username);
	    	ISession::set('oauth_id',$oauthId);
	    	ISession::set('oauth_userInfo',$userInfo);

	    	$this->redirect('bind_user');
    	}
    	//存在绑定账号
    	else
    	{
    		$userObj = new IModel('user');
    		$tempRow = $userObj->getObj("id = '{$oauthUserRow['user_id']}'");
    		$userRow = CheckRights::isValidUser($tempRow['username'],$tempRow['password']);
    		CheckRights::loginAfter($userRow);

			//自定义跳转页面
			$callback = ISafe::get('callback');

			if($callback && !strpos($callback,'reg') && !strpos($callback,'login'))
			{
				$this->redirect($callback);
			}
			else
			{
				$this->redirect('/ucenter/index');
			}
    	}
    }

	//绑定已存在用户
    public function bind_exists_user()
    {
    	$login_info     = IReq::get('login_info');
    	$password       = IReq::get('password');
    	$oauth_id       = IFilter::act(ISession::get('oauth_id'));
    	$oauth_userInfo = IFilter::act(ISession::get('oauth_userInfo'));

    	if(!$oauth_id || !isset($oauth_userInfo['id']))
    	{
    		$this->redirect('login');
    		exit;
    	}

    	if($userRow = CheckRights::isValidUser($login_info,md5($password)))
    	{
    		$oauthUserObj = new IModel('oauth_user');

    		//插入关系表
    		$oauthUserData = array(
    			'oauth_user_id' => $oauth_userInfo['id'],
    			'oauth_id'      => $oauth_id,
    			'user_id'       => $userRow['user_id'],
    			'datetime'      => ITime::getDateTime(),
    		);
    		$oauthUserObj->setData($oauthUserData);
    		$oauthUserObj->add();

    		CheckRights::loginAfter($userRow);

			//自定义跳转页面
			$callback = ISafe::get('callback');
			$this->redirect('/site/success?message='.urlencode("登录成功！").'&callback='.$callback);
    	}
    	else
    	{
    		$this->login_info = $login_info;
    		$this->message    = '用户名和密码不匹配';
    		$_GET['bind_type']= 'exists';
    		$this->redirect('bind_user',false);
    	}
    }

	//绑定不存在用户
    public function bind_nexists_user()
    {
    	$username       = IFilter::act(IReq::get('username'));
    	$email          = IFilter::act(IReq::get('email'));
    	$oauth_id       = IFilter::act(ISession::get('oauth_id'));
    	$oauth_userInfo = IFilter::act(ISession::get('oauth_userInfo'));

		/*注册信息校验*/
    	if(IValidate::email($email) == false)
    	{
    		$message = '邮箱格式不正确';
    	}
    	else if(!Util::is_username($username))
    	{
    		$message = '用户名必须是由2-20个字符，可以为字数，数字下划线和中文';
    	}
    	else
    	{
    		$userObj = new IModel('user');
    		$where   = 'email = "'.$email.'" or username = "'.$email.'" or username = "'.$username.'"';
    		$userRow = $userObj->getObj($where);

    		if(!empty($userRow))
    		{
    			if($email == $userRow['email'])
    			{
    				$message = '此邮箱已经被注册过，请重新更换';
    			}
    			else
    			{
    				$message = "此用户名已经被注册过，请重新更换";
    			}
    		}
    		else
    		{
				$userData = array(
					'email'    => $email,
					'username' => $username,
					'password' => md5(ITime::getDateTime()),
				);
				$userObj->setData($userData);
				$user_id = $userObj->add();

				$memberObj  = new IModel('member');
				$memberData = array(
					'user_id'   => $user_id,
					'true_name' => $oauth_userInfo['name'],
					'last_login'=> ITime::getDateTime(),
					'sex'       => isset($oauth_userInfo['sex']) ? $oauth_userInfo['sex'] : 1,
					'time'      => ITime::getDateTime(),
				);
				$memberObj->setData($memberData);
				$memberObj->add();

				$oauthUserObj = new IModel('oauth_user');

				//插入关系表
				$oauthUserData = array(
					'oauth_user_id' => $oauth_userInfo['id'],
					'oauth_id'      => $oauth_id,
					'user_id'       => $user_id,
					'datetime'      => ITime::getDateTime(),
				);
				$oauthUserObj->setData($oauthUserData);
				$oauthUserObj->add();

				$userRow = CheckRights::isValidUser($userData['email'],$userData['password']);
				CheckRights::loginAfter($userRow);

				//自定义跳转页面
				$callback = ISafe::get('callback');
				$this->redirect('/site/success?message='.urlencode("注册成功！").'&callback='.$callback);
    		}
    	}

    	if($message != '')
    	{
    		$this->message = $message;
    		$this->redirect('bind_user',false);
    	}
    }

	/**
	 * @brief 商户的增加动作
	 */
	public function seller_reg()
	{
		$seller_name = IFilter::act(IReq::get('seller_name'));
		$email       = IFilter::act(IReq::get('email'));
		$password    = IFilter::act(IReq::get('password'));
		$repassword  = IFilter::act(IReq::get('repassword'));
		$truename    = IFilter::act(IReq::get('true_name'));
		$phone       = IFilter::act(IReq::get('phone'));
		$mobile      = IFilter::act(IReq::get('mobile'));
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
		$address     = IFilter::act(IReq::get('address'));
		$home_url    = IFilter::act(IReq::get('home_url'));

		if($password == '')
		{
			$errorMsg = '请输入密码！';
		}

		if($password != $repassword)
		{
			$errorMsg = '两次输入的密码不一致！';
		}

		//创建商家操作类
		$sellerDB = new IModel("seller");
		if($sellerDB->getObj("seller_name = '{$seller_name}'"))
		{
			$errorMsg = "登录用户名重复";
		}
		else if($sellerDB->getObj("true_name = '{$truename}'"))
		{
			$errorMsg = "商户真实全称重复";
		}

		//操作失败表单回填
		if(isset($errorMsg))
		{
			$this->sellerRow = $_POST;
			$this->redirect('seller',false);
			Util::showMessage($errorMsg);
		}

		//待更新的数据
		$sellerRow = array(
			'true_name' => $truename,
			'phone'     => $phone,
			'mobile'    => $mobile,
			'email'     => $email,
			'address'   => $address,
			'province'  => $province,
			'city'      => $city,
			'area'      => $area,
			'home_url'  => $home_url,
			'is_lock'   => 1,
		);

		
		//商户资质上传
		if((isset($_FILES['paper_img']['name']) && $_FILES['paper_img']['name']) || (isset($_FILES['logo_img']['name']) && $_FILES['logo_img']['name']))
		{
			$uploadObj = new PhotoUpload();
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();
			if(isset($photoInfo['paper_img']['img']) && file_exists($photoInfo['paper_img']['img']))
			{
				$sellerRow['paper_img'] = $photoInfo['paper_img']['img'];
			}
			if(isset($photoInfo['logo_img']['img']) && file_exists($photoInfo['logo_img']['img']))
			{
				$sellerRow['logo_img'] = $photoInfo['logo_img']['img'];
			}
		}

		$sellerRow['seller_name'] = $seller_name;
		$sellerRow['password']    = md5($password);
		$sellerRow['create_time'] = ITime::getDateTime();

		$sellerDB->setData($sellerRow);
		$sellerDB->add();

		//短信通知商城平台
		$siteConfig = new Config('site_config');
		if($siteConfig->mobile)
		{
			$content = smsTemplate::sellerReg(array('{true_name}' => $truename));
			$result = Hsms::send($mobile,$content);
		}

		$this->redirect('/site/success?message='.urlencode("申请成功！请耐心等待管理员的审核"));
	}

	/**
	 * @brief 发送验证邮箱邮件
	 */
	public function send_check_mail()
	{
		$email = IReq::get('email');
		if(IValidate::email($email) == false)
		{
			return 0;
		}

		$userDB  = new IModel('user');
		$userRow = $userDB->getObj('email = "'.$email.'"');
		$code    = base64_encode($userRow['email']."|".$userRow['id']);
		$url     = IUrl::getHost().IUrl::creatUrl("/simple/check_mail/code/{$code}");
		$content = mailTemplate::checkMail(array("{url}" => $url));

		//发送邮件
		$smtp   = new SendMail();
		$result = $smtp->send($email,"用户注册邮箱验证",$content);
		if($result===false)
		{
			return 0;
		}

		$message = "您的邮箱验证邮件已发送到{$email}！请到您的邮箱中去激活";
		return IUrl::creatUrl('/site/success?message='.urlencode($message).'&email='.$email);//返回url
	}

	/**
	 * @brief 验证邮箱
	 */
	public function check_mail()
	{
		$code = IReq::get("code");
		list($email,$user_id) = explode('|',base64_decode($code));
		$email   = IFilter::act($email);
		$user_id = IFilter::act($user_id,'int');

		$userDB  = new IModel("user");
		$userRow = $userDB->getObj(" email = '{$email}' and id = ".$user_id);
		if($userRow)
		{
			CheckRights::loginAfter($userRow);
			$memberObj = new IModel("member");
			$memberObj->setData(array("status" => 1));
			$memberObj->update("user_id = ".$user_id);
			$message = "恭喜，您的邮箱激活成功！";
		}
		else
		{
			$message = "验证信息有误，请核实！";
		}
		$this->redirect('/site/success?message='.urlencode($message));
	}

	//添加地址ajax
	function address_add()
	{
		$accept_name = IFilter::act(IReq::get('accept_name'));
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
		$address     = IFilter::act(IReq::get('address'));
		$zip         = IFilter::act(IReq::get('zip'));
		$telphone    = IFilter::act(IReq::get('telphone'));
		$mobile      = IFilter::act(IReq::get('mobile'));
        $user_id     = $this->user['user_id'];

        if(!$user_id)
        {
        	die(JSON::encode(array('data' => null)));
        }

		//整合的数据，检查数据库中是否存在此收货地址
        $sqlData = array(
        	'user_id'     => $user_id,
        	'accept_name' => $accept_name,
        	'zip'         => $zip,
        	'telphone'    => $telphone,
        	'province'    => $province,
        	'city'        => $city,
        	'area'        => $area,
        	'address'     => $address,
        	'mobile'      => $mobile,
        );
        $sqlArray = array();
        foreach($sqlData as $key => $val)
        {
        	$sqlArray[] = $key.'="'.$val.'"';
        }

        $model       = new IModel('address');
		$addressRow  = $model->getObj(join(' and ',$sqlArray));

		if($addressRow)
		{
			$result = array('data' => null);
		}
		else
		{
			//获取地区text
			$areaList = area::name($province,$city,$area);

			//执行insert
			$model->setData($sqlData);
			$sqlData['add_id']=$model->add();

			$sqlData['province_val'] = $areaList[$province];
			$sqlData['city_val']     = $areaList[$city];
			$sqlData['area_val']     = $areaList[$area];

			$result = array('data' => $sqlData);
		}
		die(JSON::encode($result));
	}
	//获取用户密码错误次数
	private function getErrTimes($username){
		$M = new IModel('user');
		$where = 'phone = "'.$username.'" OR username = "'.$username.'" OR email = "'.$username.'"';
		if($res = $M->getObj($where,'err_times'))return  $res['err_times'];
	}
	//验证用户密码错误次数ajax(zz)
	public function checkErrTimes(){
		$username = IFilter::act(IReq::get('username'),'str');
		$M = new IModel('user');
		$where = 'phone = "'.$username.'" OR username = "'.$username.'" OR email = "'.$username.'"';
		if($res = $M->getObj($where,'err_times'))echo $res['err_times'];
		else echo 0;
	}
	//检测手机是否注册是否已注册
	public function checkPhoneIsOne(){
		$res = array(
			'checkResult'=>0,
			'showValidCode'=>0
		);
		$phone = IFilter::act(IReq::get('phone'),'str');
		$M = new IModel('user');
		$where = 'phone = "' . $phone.'"';
		$res['checkResult'] = $M->getObj($where,'id') ? 1 : 0;
		
		echo JSON::encode($res);
	}
	//
	public function checkEmailIsOne(){
		$res = array(
				'checkResult'=>0,
				'showValidCode'=>0
		);
		$email = IFilter::act(IReq::get('email'),'str');
		$M = new IModel('user');
		$where = 'email = "' . $email.'"';
		$res['checkResult'] = $M->getObj($where,'id') ? 1 : 0;
		
		echo JSON::encode($res);
	}
	
	//点击支付弹出页面
	function payafter(){
		$this->layout = '';
		if($this->user['user_id'] == null)
		{
			$this->redirect('/simple/login');
		}
		
		$order_id = intval(IReq::get('order_id'));
		if(!$order_id)return false;
		$order = new IModel('order');
		if(!$order_data = $order->getObj('id='.$order_id.' and user_id = '.$this->user['user_id'],'create_time')){
			IError::show(403,"订单不存在");
		}
		$config = new Config('site_config');
		$cancle_days = $config->order_cancel_time;
		$this->end_time = strtotime($order_data['create_time']) + $cancle_days*24*3600;
		$this->order_id = $order_id;
	
		$this->redirect('payafter');
	}
}
