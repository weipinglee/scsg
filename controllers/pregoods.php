<?php
/**
 * 前台展示预售类
 * @author lenovo
 *
 */
class Pregoods extends IController
{
	public $layout='site';

	function init()
	{
		CheckRights::checkUserRights();
	}
	//预售列表
	public function presell_list(){
		$this->logoUrl = 'images/yulogo.png';
		$this->yushou = 1;
		$presell_db = new IQuery('presell as p');
		$presell_db->join = 'left join goods as g on p.goods_id = g.id';
		$presell_db->where = 'p.is_close=0 and TIMESTAMPDIFF(second,p.yu_end_time,NOW())<0 and  g.is_del=4';
		$presell_db->fields = 'p.*,(UNIX_TIMESTAMP(p.yu_end_time)- UNIX_TIMESTAMP(now())) as end_timestamp,g.sell_price as price,g.img';
		$presell_db->limit = 6;
		$presell_db->order = 'p.id DESC';
		$this->pre_list = $presell_db->find();
		$this->count = count($this->pre_list);
		$this->redirect('presell_list');
		
	}
	//获取更多列表
	public function getMorePresell(){
		$start = IFilter::act(IReq::get('start'),'int');
		$limit = $start.',10';
		
		$presell_db = new IQuery('presell as p');
		$presell_db->join = 'left join goods as g on p.goods_id = g.id';
		$presell_db->where = 'p.is_close=0 and TIMESTAMPDIFF(second,p.yu_end_time,NOW())<0 and  g.is_del=4';
		$presell_db->fields = 'p.*,(UNIX_TIMESTAMP(p.yu_end_time)-UNIX_TIMESTAMP(now())) as end_timestamp,g.sell_price as price';
		$presell_db->order = 'p.id DESC';
		$presell_db->limit = $limit;
		$presellData = $presell_db->find();
		
		foreach($presellData as $key=>$val){
			$presellData[$key]['key'] = $key + $start;
		}
		echo $presellData ? JSON::encode($presellData) : 0;
	}
	/**
	 * 预售商品展示
	 * 
	 */
	function products()
	{
		$this->logoUrl = 'images/yulogo.png';
		$id = IFilter::act(IReq::get('id'),'int');
		$presell = new IModel('presell');
		if(!$id || !$preData = $presell->getObj('goods_id='.$id.' and  TIMESTAMPDIFF(second,yu_end_time,NOW()) <0 ','*,unix_timestamp(yu_end_time)-UNIX_TIMESTAMP(now()) as end_time'))
		{
			IError::show(403,"预售商品不存在");
			exit;
		}
		$money_rate = $preData['money_rate']/100;
		$goods_id = $preData['goods_id'];
		$user_id = $this->user ? $this->user['user_id'] : 0;
		user_like::set_user_history($goods_id,$user_id);
	
		//使用商品id获得商品信息
		$tb_goods = new IModel('goods');
		$goods_info = $tb_goods->getObj('id='.$goods_id." AND is_del=4");
		//
	
		//print_r($goods_info);
		if(!$goods_info)
		{
			IError::show(403,"这件预售商品不存在");
			exit;
		}
		$sell_price = $goods_info['sell_price'];
		//品牌名称
		if($goods_info['brand_id'])
		{
			$tb_brand = new IModel('brand');
			$brand_info = $tb_brand->getObj('id='.$goods_info['brand_id']);
			if($brand_info)
			{
				$goods_info['brand'] = $brand_info['name'];
			}
		}
		$commend = new IModel('commend_goods');
		$goods_info['commend'] = $commend->getFields(array('goods_id'=>$goods_id),'commend_id');
	
		//获取商品分类
		$categoryObj = new IModel('category_extend as ca,category as c');
		$categoryRow = $categoryObj->getObj('ca.goods_id = '.$goods_id.' and ca.category_id = c.id','c.id,c.name');
		$goods_info['category'] = $categoryRow ? $categoryRow['id'] : 0;
	
		//商品图片
		$tb_goods_photo = new IQuery('goods_photo_relation as g');
		$tb_goods_photo->fields = 'p.id AS photo_id,p.img ';
		$tb_goods_photo->join = 'left join goods_photo as p on p.id=g.photo_id ';
		$tb_goods_photo->where =' g.goods_id='.$goods_id;
		$goods_info['photo'] = $tb_goods_photo->find();
		foreach($goods_info['photo'] as $key => $val)
		{
			//对默认第一张图片位置进行前置
			if($val['img'] == $goods_info['img'])
			{
				$temp = $goods_info['photo'][0];
				$goods_info['photo'][0] = $val;
				$goods_info['photo'][$key] = $temp;
			}
		}
	
	
	
		//获得扩展属性
		$tb_attribute_goods = new IQuery('goods_attribute as g');
		$tb_attribute_goods->join  = 'left join attribute as a on a.id=g.attribute_id ';
		$tb_attribute_goods->fields=' a.name,g.attribute_value ';
		$tb_attribute_goods->where = "goods_id='".$goods_id."' and attribute_id!=''";
		$tb_attribute_goods->order = "g.id asc";
		$goods_info['attribute'] = $tb_attribute_goods->find();
	
		
	
		//购买记录
		$tb_shop = new IQuery('order_goods as og');
		$tb_shop->join = 'left join order as o on o.id=og.order_id';
		$tb_shop->fields = 'count(*) as totalNum';
		$tb_shop->where = 'og.goods_id='.$goods_id.' and o.status = 5';
		$shop_info = $tb_shop->find();
		$goods_info['buy_num'] = 0;
		if($shop_info)
		{
			$goods_info['buy_num'] = $shop_info[0]['totalNum'];
		}
	
		//购买前咨询
		$tb_refer    = new IModel('refer');
		$refeer_info = $tb_refer->getObj('goods_id='.$goods_id,'count(*) as totalNum');
		$goods_info['refer'] = 0;
		if($refeer_info)
		{
			$goods_info['refer'] = $refeer_info['totalNum'];
		}
	
	
		//获得商品的价格区间
		$tb_product = new IModel('products');
		$goods_info['maxSellPrice']   = '';
		$goods_info['minSellPrice']   = '';
	
		$product_info = $tb_product->getObj('goods_id='.$goods_id,'max(sell_price) as maxSellPrice ,min(sell_price) as minSellPrice');
		if($product_info)
		{
			$goods_info['maxSellPrice']   = $product_info['maxSellPrice'];
			$goods_info['minSellPrice']   = $product_info['minSellPrice'];
			$goods_info['maxPresellPrice']= self::getPrePrice($product_info['maxSellPrice'],$money_rate);
			$goods_info['minPresellPrice']= self::getPrePrice($product_info['minSellPrice'],$money_rate);
		}
		$goods_info['presellPrice'] = number_format(ceil($money_rate * $goods_info['sell_price']),2);
	
		//获得会员价
		$countsumInstance = new countsum();
		$group_price = $countsumInstance->getGroupPrice($goods_id,'goods');
		if($group_price !==null){
			$group_price = floatval($group_price);
			if($group_price < $goods_info['sell_price']){
				$goods_info['group_price'] = $group_price;
				$goods_info['presellPrice'] = number_format(ceil($money_rate * $goods_info['group_price']),2);
			}
		}
		
		
		//获取尾款支付时间
		if($preData['wei_type']==1){
			$preData['wei_text'] = $preData['wei_start_time'].'至'.$preData['wei_end_time'].'支付尾款';
			
		}else{
			$preData['wei_text'] = '预付款支付后'.$preData['wei_days'].'天内支付尾款';
		}
		$goods_info['preData'] = $preData;
		//获取标签
		$tb_tag = new IQuery('commend_tags as t');
		$tb_tag->join = 'left join commend_goods as go on t.id = go.commend_id';
		$tb_tag->where = 'go.goods_id = '.$goods_id;
		$tb_tag->fields = 't.name,t.img';
		$tb_tag->limit = 5;
		$goods_info['tag_data'] = $tb_tag->find();
	
	
		//获取商家信息
		if($goods_info['seller_id'])
		{
			$sellerDB = new IModel('seller');
			$goods_info['seller'] = $sellerDB->getObj('id = '.$goods_info['seller_id'],'id,true_name,email,mobile,logo_img,server_num,point,num');
		}
	
		//增加浏览次数
		$visit    = ISafe::get('visit');
		$checkStr = "#".$goods_id."#";
		if($visit && strpos($visit,$checkStr) !== false)
		{
		}
		else
		{
			$tb_goods->setData(array('visit' => 'visit + 1'));
			$tb_goods->update('id = '.$goods_id,'visit');
			$visit = $visit === null ? $checkStr : $visit.$checkStr;
			ISafe::set('visit',$visit);
		}
		user_like::add_like_cate($goods_id,$this->user['user_id']);
		
		$this->setRenderData($goods_info);

		$this->redirect('products');
	}
	
	//预售订单信息cart2
	function cart2()
	{
		//	$paymentList=Api::run('getSellerDelivery',array('#seller_id#'=>1));
		//print_r($paymentList);exit();
		$id        = IFilter::act(IReq::get('id'),'int');
		$type      = IFilter::act(IReq::get('type'));//goods,product
		$active_id = IFilter::act(IReq::get('active_id'),'int');
		$prom 	   = 'presell';
		$buy_num   = IReq::get('num') ? IFilter::act(IReq::get('num'),'int') : 1;
		$tourist   = IReq::get('tourist');//游客方式购物
	
		//必须为登录用户
		if($tourist === null && $this->user['user_id'] == null)
		{
			if($id == 0 || $type == '')
			{
				$this->redirect('/simple/login?tourist&callback=/simple/cart2');
			}
			else
			{
				$url  = '/simple/login?tourist&callback=/pregoods/cart2/id/'.$id.'/type/'.$type.'/num/'.$buy_num.'/active_id/'.$active_id;
				
				$this->redirect($url);
			}
		}
	
		//游客的user_id默认为0
		$user_id = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];
	
		//计算商品
		$countSumObj = new CountSum($user_id);
	
		if($id && $type)//立即购买
		{
			
			$result = $countSumObj->presell_count($id,$type,$buy_num,$active_id);
			
				
			$this->gid       = $id;
			$this->type      = $type;
			$this->num       = $buy_num;
			$this->active_id = $active_id;
		}
		else{
			IError::show(403,$result);
			exit;
		}
		//检查商品合法性或促销活动等有错误
		if( is_string($result))
		{
			IError::show(403,$result);
			exit;
		}
		$result['pre_sum'] = self::getPrePrice($result['pre_sum']);
	
		
		if($result['sum']==0){
			$this->redirect('cart');
		}
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
		if(Common::activeProp($prom)){//判断活动是否允许使用代金券
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
		$this->pre_sum     = $result['pre_sum'];
		
		//判断是否支持货到付款
		$this->freight_collect=0;
		
		 
		//收货地址列表
		$this->addressList = $addressList;
		//获取商品税金
		$this->goodsTax    = $result['tax'];
	
		$seller_id = $result['goodsList']['0']['seller_id'];
		if($seller_id==0){
			$this->seller_name = '山城速购';
		}else{
			$seller_db = new IModel('seller');
			$this->seller_name = $seller_db->getField('id='.$seller_id,'true_name,id');
		}
		$this->seller_id = $seller_id;
		//获取配送方式列表
		$deli_db = new IQuery('delivery as d');
		if($seller_id==0){
			$deli_db->where = 'd.status = 1 and d.is_delete = 0';
			$this->allDeliveryType = $deli_db->find();
		}else{
			$deli_db->join = 'left join delivery_extend as ex on d.id = ex.delivery_id';
			$deli_db->where = 'ex.seller_id = '.$seller_id.' and ex.is_open =1 and d.status = 1 and d.is_delete = 0';
			$this->allDeliveryType = $deli_db->find();
		}
		
		//渲染页面
		$this->redirect('cart2');
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
		$ticket_id     = IFilter::act(IReq::get('ticket_id'),'int');//预售禁止使用代金券则为空
		$taxes         = IFilter::act(IReq::get('taxes'),'int');
		$insured       = IFilter::act(IReq::get('insured'));
		$gid           = IFilter::act(IReq::get('direct_gid'),'int');
		$num           = IFilter::act(IReq::get('direct_num'),'int');
		$type          = IFilter::act(IReq::get('direct_type'));//商品或者货品
		$active_id     = IFilter::act(IReq::get('direct_active_id'),'int');
		$takeself      = IFilter::act(IReq::get('takeself'),'int');
		$order_no      = Order_Class::createOrderNum();
		$invoice       = isset($_POST['taxes']) ? 1 : 0;
		$order_type    = 4;//预售订单
		$dataArray     = array();
		$prom          = 'presell';
		$seller_id    = IFilter::act(IReq::get('seller_id'),'int');
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
			$goodsResult = $countSumObj->presell_count($gid,$type,$num,$active_id);
			
				
			$this->gid       = $gid;
			$this->type      = $type;
			$this->num       = $num;
			$this->active_id = $active_id;
		}
		
		if(is_string($goodsResult) || empty($goodsResult['goodsList']))
		{
			IError::show(403,$goodsResult);
			exit;
		}
		$goodsResult['pre_sum'] = self::getPrePrice($goodsResult['pre_sum']);
		
	
		//获取红包减免金额
		if($ticket_id != '' && Common::activeProp($prom))
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
		$orderData = $countSumObj->countOrderFeePresell($goodsResult,$area,$delivery_id,$payment,$insured,$taxes);
		
		
		if(is_string($orderData))
		{
			IError::show(403,$orderData);
			exit;
		}
	
		//生成的订单数据
		$dataArray = array(
		'accept_name'         => $accept_name,
		'distribution'        => $delivery_id,
		'postcode'            => $zip,
		'telphone'            => $telphone,
		'province'            => $province,
		'city'                => $city,
		'area'                => $area,
		'address'             => $address,
		'mobile'              => $mobile,
		
		'order_no'            => $order_no,
		'user_id'             => $user_id,
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
		'invoice'             => $invoice,
		'taxes'               => $orderData['taxPrice'],
	
		//优惠价格（包括闪购、会员价差价，红包，促销活动减价）
		'promotions'          => $goodsResult['proReduce'] + $goodsResult['reduce'] + (isset($ticketRow['value']) ? $ticketRow['value'] : 0),
	
		//促销活动优惠
		'pro_reduce'         => $goodsResult['proReduce'] ,
		//红包减免金额
		'ticket_reduce'      => isset($ticketRow['value']) ? $ticketRow['value'] : 0,
		//订单应付总额（商品final_num加上，税金，运费，再减去红包）
		'order_amount'        => $orderData['orderAmountPrice'] - (isset($ticketRow['value']) ? $ticketRow['value'] : 0),
	
		//
		'pre_amount'         => $goodsResult['pre_sum'],
		//订单保价
		'if_insured'          => $insured ? 1 : 0,
		'insured'             => $orderData['insuredPrice'],
	
		//自提点ID
		'takeself'            => $takeself,
	
		//预售活动ID
		'active_id'           => $active_id,
		'pay_type'            => $payment,
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
		//填写开发票信息
		if($invoice){
			$db_fapiao = new IModel('order_fapiao');
			$fapiao_data = array(
					'order_id'=> $this->order_id,
					'user_id' => $user_id,
					'type'    => IFilter::act(IReq::get('type'),'int'),
					'create_time'=> ITime::getDateTime(),
			);
			if($fapiao_data['type']==0){
				$fapiao_data['taitou'] = IFilter::act(IReq::get('taitou'));
					
			}else{
				$fapiao_data['com'] = IFilter::act(IReq::get('tax_com'));
				$fapiao_data['tax_no']= IFilter::act(IReq::get('tax_no'));
				$fapiao_data['address'] = IFilter::act(IReq::get('tax_address'));
				$fapiao_data['telphone'] = IFilter::act(IReq::get('tax_telphone'));
				$fapiao_data['bank'] = IFilter::act(IReq::get('tax_bank'));
				$fapiao_data['account'] = IFilter::act(IReq::get('tax_account'));
			}
			$fapiao_data['seller_id'] = $seller_id;
			$db_fapiao->setData($fapiao_data);
			$db_fapiao->add();
		}
		
	
		//数据渲染
		$this->order_num   = $dataArray['order_no'];
		$this->final_sum   = $dataArray['order_amount'];
		$this->payment     = $paymentName;
		$this->paymentType = $paymentType;
		$this->delivery    = $deliveryRow['name'];
		$this->deliveryType= $deliveryRow['type'];
		$this->pre_sum     = $goodsResult['pre_sum'];
	
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
	//获取货品数据
	function getProduct()
	{
		$jsonData = JSON::decode(IReq::get('specJSON'));
		if(!$jsonData)
		{
			echo JSON::encode(array('flag' => 'fail','message' => '规格值不符合标准'));
			exit;
		}
	
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$specJSON = IFilter::act(IReq::get('specJSON'));
		$rate = IFilter::act(IReq::get('rate'),'int');
		if($rate)$rate = $rate*0.01;
		//获取货品数据
		$tb_products = new IModel('products');
		$procducts_info = $tb_products->getObj("goods_id = ".$goods_id." and spec_array = '".$specJSON."'");
		$procducts_info['sell_price'] = $procducts_info['sell_price'];
		//匹配到货品数据
		if(!$procducts_info)
		{
			echo JSON::encode(array('flag' => 'fail','message' => '没有找到相关货品'));
			exit;
		}
	
		//获得会员价
		$countsumInstance = new countsum();
		$group_price = $countsumInstance->getGroupPrice($procducts_info['id'],'product');
	
		//会员价格(与销售价相等则不显示）
		if($group_price !== null && floatval($group_price) < $procducts_info['sell_price'])
		{
			$procducts_info['group_price'] = floatval($group_price);
		}
	
		$procducts_info['prePrice'] = isset($procducts_info['group_price']) ? self::getPrePrice($procducts_info['group_price'],$rate) : self::getPrePrice($procducts_info['sell_price'] , $rate);
		$procducts_info['weiPrice'] = isset($procducts_info['group_price']) ? $procducts_info['group_price'] - $procducts_info['prePrice'] : $procducts_info['sell_price'] - $procducts_info['prePrice'];
		echo JSON::encode(array('flag' => 'success','data' => $procducts_info));
	}
	
	//获取预售价格，计算方式：原价*预付比例，再取整
	public static function getPrePrice($price,$rate=1){
		if($price<1)return $price;
		return number_format(ceil($rate * $price),2);
	}
}
