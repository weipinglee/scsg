<?php
/**
 * @file countsum.php
 * @brief 计算购物车中的商品价格
 * @date 2011-02-24
 * @version 0.6
 */
class CountSum
{
	//用户ID
	public $user_id = '';

	//用户组ID
	public $group_id = '';

	//用户组折扣
	public $group_discount = '';

	/**
	 * 构造函数
	 */
	public function __construct($user_id = null)
	{
		$this->user_id = $user_id ? $user_id : ISafe::get('user_id');

		//获取用户组ID及组的折扣率
		if($this->user_id != null)
		{
			$groupObj = new IModel('member as m , user_group as g');
			$groupRow = $groupObj->getObj('m.user_id = '.$this->user_id.' and m.group_id = g.id','g.*');
			if($groupRow)
			{
				$this->group_id       = $groupRow['id'];
				$this->group_discount = $groupRow['discount'] * 0.01;
			}
		}
	}

	/**
	 * 获取会员组价格
	 * @param $id   int    商品或货品ID
	 * @param $type string goods:商品; product:货品
	 * @return float 价格
	 */
	public function getGroupPrice($id,$type = 'goods')
	{
		if(!$this->group_id)
		{
			return null;
		}

		//1,查询特定商品的组价格
		$groupPriceDB = new IModel('group_price');
		if($type == 'goods')
		{
			$discountRow = $groupPriceDB->getObj('goods_id = '.$id.' and group_id = '.$this->group_id,'price');
		}
		else
		{
			$discountRow = $groupPriceDB->getObj('product_id = '.$id.' and group_id = '.$this->group_id,'price');
		}

		if($discountRow)
		{
			return $discountRow['price'];
		}

		//2,根据会员折扣率计算商品折扣
		if($this->group_discount)
		{
			if($type == 'goods')
			{
				$goodsDB  = new IModel('goods');
				$goodsRow = $goodsDB->getObj('id = '.$id,'sell_price');
				return $goodsRow ? Util::priceFormat($goodsRow['sell_price'] * $this->group_discount) : null;
			}
			else
			{
				$productDB  = new IModel('products');
				$productRow = $productDB->getObj('id = '.$id,'sell_price');
				return $productRow ? Util::priceFormat($productRow['sell_price'] * $this->group_discount) : null;
			}
		}
		return null;
	}

	/**
	 * 预售计算
	 */
	public function presell_count($id,$type,$buy_num,$active_id)
	{
		$config = new Config('site_config');
		$not_prom = $config->pesell_promotion;//是否支持优惠活动
		$buyInfo = array(
				$type => array('id' => array($id) , 'data' => array($id => array('count' => $buy_num)),'count' => $buy_num)
		);
		$priceInfo = $this->goodsCount($buyInfo,$not_prom);
		$presell_db = new IModel('presell');
		$priceInfo['pre_rate']=$presell_db->getField('id='.$active_id.' and TIMESTAMPDIFF(second,yu_end_time,NOW())<=0','money_rate');
		if(!$priceInfo['pre_rate']){
			return '该预售不存在';
		}else{
			$priceInfo['pre_sum'] = $priceInfo['final_sum']* $priceInfo['pre_rate'] /100;
			$priceInfo['pre_sum'] = $priceInfo['pre_sum'] < 0.01 ? 0.01 : $priceInfo['pre_sum'];
		}
		
		return $priceInfo;
		
		
	}
	/**
	 * @brief 计算商品价格
	 * @param Array $buyInfo ,购物车格式
	 * @param bool $prom 是否允许优惠
	 * @return array or bool
	 */
	public function goodsCount($buyInfo,$prom=true)
	{
		$this->sum           = 0;       //原始总额(优惠前)
		$this->final_sum     = 0;       //应付总额(优惠后)
    	$this->weight        = 0;       //总重量
    	$this->reduce        = 0;       //减少总额
    	$this->count         = 0;       //总数量
    	$this->promotion     = array(); //促销活动规则文本
    	$this->proReduce     = 0;       //促销活动规则优惠额
    	$this->point         = 0;       //增加积分
    	$this->exp           = 0;       //增加经验
    	$this->isFreeFreight = false;   //是否免运费
    	$this->tax           = 0;       //商品税金

		$user_id      = $this->user_id;
		$group_id     = $this->group_id;
    	$goodsList    = array();
    	$productList  = array();//

		/*开始计算goods和product的优惠信息 , 会根据条件分析出执行以下哪一种情况:
		 *(1)查看此商品(货品)是否已经根据不同会员组设定了优惠价格;
		 *(2)当前用户是否属于某个用户组中的成员，并且此用户组享受折扣率;
		 *(3)优惠价等于商品(货品)原价;
		 */

		//获取商品或货品数据
		/*Goods 拼装商品优惠价的数据*/
    	if(isset($buyInfo['goods']['id']) && $buyInfo['goods']['id'])
    	{
    		//购物车中的商品数据
    		$goodsIdStr = join(',',$buyInfo['goods']['id']);
    		$goodsObj   = new IModel('goods as go');
    		$goodsList  = $goodsObj->query('go.id in ('.$goodsIdStr.')','go.name,go.id as goods_id,go.img,go.sell_price,go.point,go.weight,go.store_nums,go.exp,go.goods_no,0 as product_id,go.seller_id');

    		//开始优惠情况判断
    		foreach($goodsList as $key => $val)
    		{
    			//检查库存
    			if($buyInfo['goods']['data'][$val['goods_id']]['count'] <= 0 || $buyInfo['goods']['data'][$val['goods_id']]['count'] > $val['store_nums'])
    			{
    				return "商品：".$val['name']."购买数量超出库存，请重新调整购买数量";
    			}
				
    			$groupPrice                = $this->getGroupPrice($val['goods_id'],'goods');
    			if($groupPrice){
    				$minPrice = $groupPrice;
    			}else{
    				$minPrice = $val['sell_price'];
    			}
    			$minPrice = min($minPrice,$val['sell_price']);
    			$goodsList[$key]['reduce'] = $val['sell_price'] - $minPrice;
    			$goodsList[$key]['count']  = $buyInfo['goods']['data'][$val['goods_id']]['count'];
    			$current_sum_all           = $goodsList[$key]['sell_price'] * $goodsList[$key]['count'];
    			$current_reduce_all        = $goodsList[$key]['reduce']     * $goodsList[$key]['count'];
    			$goodsList[$key]['sum']    = $current_sum_all - $current_reduce_all;

    			//全局统计
		    	$this->weight += $val['weight'] * $goodsList[$key]['count'];
		    	$this->point  += $val['point']  * $goodsList[$key]['count'];
		    	$this->exp    += $val['exp']    * $goodsList[$key]['count'];
		    	$this->sum    += $current_sum_all;
		    	$this->reduce += $current_reduce_all;
		    	$this->count  += $goodsList[$key]['count'];
		    	$this->tax    += self::getGoodsTax($goodsList[$key]['sum'],$val['seller_id']);
		    }
    	}

		/*Product 拼装商品优惠价的数据*/
    	if(isset($buyInfo['product']['id']) && $buyInfo['product']['id'])
    	{
    		//购物车中的货品数据
    		$productIdStr = join(',',$buyInfo['product']['id']);
    		$productObj   = new IQuery('products as pro,goods as go');
    		$productObj->where  = 'pro.id in ('.$productIdStr.') and go.id = pro.goods_id';
    		$productObj->fields = 'pro.sell_price,pro.weight,pro.id as product_id,pro.spec_array,pro.goods_id,pro.store_nums,pro.products_no as goods_no,go.name,go.point,go.exp,go.img,go.seller_id';
    		$productList  = $productObj->find();

    		//开始优惠情况判断
    		foreach($productList as $key => $val)
    		{
    			//检查库存
    			if($buyInfo['product']['data'][$val['product_id']]['count'] <= 0 || $buyInfo['product']['data'][$val['product_id']]['count'] > $val['store_nums'])
    			{
    				return "货品：".$val['name']."购买数量超出库存，请重新调整购买数量";
    			}
    			
    			
    			$groupPrice                  = $this->getGroupPrice($val['product_id'],'product');
    			if($groupPrice){
    				$minPrice = $groupPrice;
    			}else{
    				$minPrice = $val['sell_price'];
    			}
    			$minPrice = min($minPrice,$val['sell_price']);
    			
				$productList[$key]['reduce'] = $val['sell_price'] - $minPrice;
    			$productList[$key]['count']  = $buyInfo['product']['data'][$val['product_id']]['count'];
    			$current_sum_all             = $productList[$key]['sell_price']  * $productList[$key]['count'];
    			$current_reduce_all          = $productList[$key]['reduce']      * $productList[$key]['count'];
    			$productList[$key]['sum']    = $current_sum_all - $current_reduce_all;

    			//全局统计
		    	$this->weight += $val['weight'] * $productList[$key]['count'];
		    	$this->point  += $val['point']  * $productList[$key]['count'];
		    	$this->exp    += $val['exp']    * $productList[$key]['count'];
		    	$this->sum    += $current_sum_all;
		    	$this->reduce += $current_reduce_all;
		    	$this->count  += $productList[$key]['count'];
		    	$this->tax    += self::getGoodsTax($productList[$key]['sum'],$val['seller_id']);
		    }
    	}

    	$final_sum = $this->sum - $this->reduce;

    	//总金额满足的促销规则
    	if($user_id&&$prom)
    	{
	    	$proObj = new ProRule($final_sum);
	    	$proObj->setUserGroup($group_id);
	    	$this->isFreeFreight = $proObj->isFreeFreight();
	    	$this->promotion = $proObj->getInfo();
	    	$this->proReduce = $final_sum - $proObj->getSum();
    	}
    	else
    	{
	    	$this->promotion = array();
	    	$this->proReduce = 0;
    	}

    	$this->final_sum = $final_sum - $this->proReduce;

    	return array(
    		'final_sum'  => $this->final_sum,//减去会员减价和促销减价后的总价
    		'promotion'  => $this->promotion,//促销信息
    		'proReduce'  => $this->proReduce,//促销规则减价
    		'sum'        => $this->sum,//原总价
    		'goodsList'  => array_merge($goodsList,$productList),
    		'count'      => $this->count,
    		'reduce'     => $this->reduce,//会员价和闪购价减价
    		'weight'     => $this->weight,
    		'freeFreight'=> $this->isFreeFreight,
    		'point'      => $this->point,
    		'exp'        => $this->exp,
    		'tax'        => $this->tax,
    	);
	}
	//获取闪购价
	/*
	 * @$goods_id int 商品id
	 * @$product_id int 货品id,非货品为0
	 * @return float 返回最低闪购价格
	 */
	public function getShanPrice($goods_id,$product_id=0){
		if($goods_id){
			$promotion = new IModel('promotion as p');
			if(!$this->group_id)$this->group_id = 0;
			$where = 'type = 1 AND is_close = 0 AND `condition` = '.$goods_id . ' AND product_id = '.$product_id.' AND now() between start_time and end_time AND  (FIND_IN_SET('.$this->group_id.',user_group) OR user_group = "all")';
			if($promoData = $promotion->getObj($where,'min(award_value) as shan_price,id,name'))
				return $promoData['shan_price'];
		}
		return false;
	}
	//购物车计算
	public function cart_count()
	{
		//获取购物车中的商品和货品信息
    	$cartObj    = new Cart();
    	$myCartInfo = $cartObj->getMyCart();

    	return $this->goodsCount($myCartInfo);
    }

    //计算非购物车中的商品
    public function direct_count($id,$type,$buy_num = 1,$promo='',$active_id='')
    {
    	
    	/*开启促销活动*/
    	if($promo && $active_id)
    	{
			//开启促销活动
	    	$activeObject = new Active($promo,$active_id,$this->user_id,$id,$type,$buy_num);
	    	$activeResult = $activeObject->checkValid();
	    	if($activeResult === true)
	    	{
	    		$typeRow  = $activeObject->originalGoodsInfo;
	    		$disPrice = $activeObject->activePrice;

				//设置优惠价格，如果不存在则优惠价等于商品原价
				$typeRow['reduce'] = $typeRow['sell_price'] - $disPrice;
				$typeRow['count']  = $buy_num;
				$typeRow['sum']    = $disPrice * $buy_num;

				//拼接返回数据
				$result = array(
					'final_sum'   => $typeRow['sum'],
					'promotion'   => array(),
					'proReduce'   => 0,
					'sum'         => $typeRow['sell_price'] * $buy_num,
					'goodsList'   => array($typeRow),
					'count'       => $buy_num,
					'reduce'      => $typeRow['reduce'] * $buy_num,
					'weight'      => $typeRow['weight'] * $buy_num,
					'point'       => $typeRow['point']  * $buy_num,
					'exp'         => $typeRow['exp']    * $buy_num,
					'freeFreight' => false,
					'tax'         => 0
				);
				return $result;
	    	}
	    	else
	    	{
	    		//报错信息
				return $activeResult;
	    	}
    	}
    	/*正常购买流程*/
    	else
    	{
    		$buyInfo = array(
    			$type => array('id' => array($id) , 'data' => array($id => array('count' => $buy_num)),'count' => $buy_num)
    		);
    		return $this->goodsCount($buyInfo);
    	}
    }

    /**
     * 计算订单信息,其中部分计算都是以商品原总价格计算的$goodsSum
     * @param $goodsResult array CountSum结果集
     * @param $province_id int 省份ID
     * @param $delievery_id int 配送方式ID
     * @param $payment_id int 支付ID
     * @param $is_insured array("goods_id_product_id")部分商品需要报价 or int都设置保价
     * @param $is_invoice int 是否要发票
     * @param $discount float 订单的加价或者减价
     * @return $result 最终的返回数组
     */
    public static function countOrderFee($goodsResult,$province_id,$delivery_id,$payment_id,$is_insured,$is_invoice,$discount = 0)
    {
    	$goodsFinalSum = $goodsResult['final_sum'];

    	//最终的返回数组
    	$result = array(
    		//原本运费
    		'deliveryOrigPrice' => 0,

    		//实际运费
    		'deliveryPrice' => 0,

    		//保价
    		'insuredPrice' => 0,

    		//税金
    		'taxPrice' => 0,

    		//支付手续费
    		'paymentPrice' => 0,

    		//最终订单金额
    		'orderAmountPrice' => 0,

    		//商品列表
    		'goodsResult' => array(),
    	);

		foreach($goodsResult['goodsList'] as $key => $val)
		{
			$deliveryRow = Delivery::getDelivery($province_id,$delivery_id,$val['goods_id'],$val['product_id'],$val['count']);

			//商品无法送达
			if($deliveryRow['if_delivery'] == 1)
			{
				return "您所选购的商品：".$val['name']."，无法送达";
			}

			//商品保价计算
			if($is_insured == 1 || ( is_array($is_insured) && isset($is_insured[$val['goods_id']."_".$val['product_id']]) ) )
			{
				$goodsResult['goodsList'][$key]['insuredPrice'] = $deliveryRow['protect_price'];
				$result['insuredPrice'] += $deliveryRow['protect_price'];
			}
			else
			{
				$goodsResult['goodsList'][$key]['insuredPrice'] = 0;
			}

			//商品运费计算
			$result['deliveryOrigPrice'] += $deliveryRow['price'];
			if($goodsResult['freeFreight'] == true)
			{
				$goodsResult['goodsList'][$key]['deliveryPrice'] = 0;
			}
			else
			{
				$result['deliveryPrice'] += $deliveryRow['price'];
				$goodsResult['goodsList'][$key]['deliveryPrice'] = $deliveryRow['price'];
			}

			//商品税金计算
	    	if($is_invoice == true)
	    	{
	    		$tempTax = self::getGoodsTax($val['sum'],$val['seller_id']);
	    		$goodsResult['goodsList'][$key]['taxPrice'] = $tempTax;
	    		$result['taxPrice'] += $tempTax;
	    	}
	    	else
	    	{
	    		$goodsResult['goodsList'][$key]['taxPrice'] = 0;
	    	}
		}

		//非货到付款的线上支付方式手续费
		if($payment_id != 0)
		{
			$result['paymentPrice'] = self::getGoodsPaymentPrice($payment_id,$goodsFinalSum);
		}

		//最终订单金额计算
		$order_amount = $goodsFinalSum + $result['deliveryPrice'] + $result['insuredPrice'] + $result['taxPrice'] + $result['paymentPrice'] + $discount;
		$result['orderAmountPrice'] = $order_amount <= 0 ? 0 : round($order_amount,2);

		//订单商品刷新
		$result['goodsResult'] = $goodsResult;

		return $result;
    }

    /**
     * 获取商品的税金
     * @param $goodsSum float 商品总价格
     * @param $seller_id int 商家ID
     * @return $goodsTaxPrice float 商品的税金
     */
    public static function getGoodsTax($goodsSum,$seller_id = 0)
    {
    	if($seller_id)
    	{
    		$sellerDB = new IModel('seller');
    		$sellerRow= $sellerDB->getObj('id = '.$seller_id);
    		$tax_per  = $sellerRow['tax'];
    	}
    	else
    	{
			$siteConfigObj = new Config("site_config");
			$site_config   = $siteConfigObj->getInfo();
			$tax_per       = isset($site_config['tax']) ? $site_config['tax'] : 0;
    	}
		$goodsTaxPrice = $goodsSum * ($tax_per * 0.01);
		return round($goodsTaxPrice,2);
    }

    /**
     * 获取商品金额的支付费用
     * @param $payment_id int 支付方式ID
     * @param $goodsSum float 商品总价格
     * @return $goodsPayPrice
     */
    public static function getGoodsPaymentPrice($payment_id,$goodsSum)
    {
		$paymentObj = new IModel('payment');
		$paymentRow = $paymentObj->getObj('id = '.$payment_id,'poundage,poundage_type');

		if($paymentRow)
		{
			if($paymentRow['poundage_type'] == 1)
			{
				//按照百分比
				return $goodsSum * ($paymentRow['poundage'] * 0.01);
			}
			//按照固定金额
			return $paymentRow['poundage'];
		}
		return 0;
    }

	/**
	 * @brief 获取商户订单货款结算
	 * @param int $seller_id 商户ID
	 * @param datetime $start_time 订单开始时间
	 * @param datetime $end_time 订单结束时间
	 * @param string $is_checkout 是否已经结算 0:未结算; 1:已结算; null:不限
	 * @param IQuery 结果集对象
	 */
    public static function getSellerGoodsFeeQuery($seller_id = '',$start_time = '',$end_time = '',$is_checkout = '')
    {
    	$where  = "og.is_send = 1 and o.pay_type != 0 and o.pay_status = 1";
    	$where .= $is_checkout !== '' ? " and is_checkout = ".$is_checkout : "";
    	$where .= $seller_id          ? " and seller_id = ".$seller_id : "";
    	$where .= $start_time         ? " and o.create_time >= '{$start_time}' " : "";
    	$where .= $end_time           ? " and o.create_time <= '{$end_time}' "   : "";

    	$orderGoodsDB = new IQuery('order_goods as og');
    	$orderGoodsDB->join  = "left join goods as go on go.id = og.goods_id left join order as o on o.id = og.order_id";
    	$orderGoodsDB->order = "o.id desc";
    	$orderGoodsDB->where = $where;
    	$orderGoodsDB->fields= "o.order_no,og.*,o.create_time,go.seller_id";
    	return $orderGoodsDB;
    }

	/**
	 * @brief 计算商户货款及其他费用
	 * @param array $orderGoodsList 订单商品关联
	 * @return array('goodsSum' => 商品总额,'deliveryPrice' => 运费, 'insuredPrice' => 保价, 'taxPrice' => 税金 ,'orderAmountPrice' => 统计总价,'order_goods_ids' => 关联表IDS)
	 */
    public static function countSellerOrderFee($orderGoodsList)
    {
    	$result = array(
			'goodsSum'         => 0,
			'deliveryPrice'    => 0,
			'insuredPrice'     => 0,
			'taxPrice'         => 0,
			'orderAmountPrice' => 0,
			'order_goods_ids'  => array(),
    	);

    	if($orderGoodsList && is_array($orderGoodsList))
    	{
    		foreach($orderGoodsList as $key => $item)
    		{
				$result['goodsSum']         += $item['real_price'] * $item['goods_nums'];
				$result['deliveryPrice']    += $item['delivery_fee'];
				$result['insuredPrice']     += $item['save_price'];
				$result['taxPrice']         += $item['tax'];
				$result['orderAmountPrice'] += $item['real_price'] * $item['goods_nums'] + $item['delivery_fee'] + $item['save_price'] + $item['tax'];
				$result['order_goods_ids'][] = $item['id'];
    		}
    	}
    	return $result;
    }
}