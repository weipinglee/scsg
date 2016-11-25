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
     * 获取会员组id
     */
     public function getGroupId()
     {
         return $this->group_id;
     }

	/**
	 * 预售计算
	 */
	public function presell_count($id,$type,$buy_num,$active_id,$area=null)
	{
		$buyInfo = array(
				0 => array($type => array('id' => array($id) , 'data' => array($id => array('count' => $buy_num)),'count' => $buy_num))
		);
		$priceInfo = $this->goodsCount($buyInfo,$area);
		$presell_db = new IModel('presell');
		$temp=$presell_db->getObj('id='.$active_id.' and TIMESTAMPDIFF(second,yu_end_time,NOW())<=0','*');
        $priceInfo['pre_rate'] = $temp ? $temp['money_rate'] : '';
		if(!$priceInfo['pre_rate']){
			return '该预售不存在';
		}else{
			$priceInfo['pre_sum'] = $priceInfo['final_sum']* $priceInfo['pre_rate'] /100;
			$priceInfo['pre_sum'] = $priceInfo['pre_sum'] < 0.01 ? 0.01 : $priceInfo['pre_sum'];
		}
        if($temp)
        {
            if($temp['wei_type']==1){
                $priceInfo['wei_text'] = $temp['wei_start_time'].'至'.$temp['wei_end_time'].'支付尾款';
                
            }else{
                $priceInfo['wei_text'] = '预付款支付后'.$temp['wei_days'].'天内支付尾款';
            }
        }
        
		return $priceInfo;
		
		
	}
	/**
	 * @brief 计算商品价格
	 * @param Array $buyInfo ,购物车格式
	 * @param bool $prom 是否允许优惠
	 * @param float $disPrice 商品的活动价格
	 * @return array or bool
	 */
	public function goodsCount($buyInfoList,$area=null,$prom=true)
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
        $this->extend        = 0;       //拆分订单数据

		$user_id      = $this->user_id;
		$group_id     = $this->group_id;
    	$goodsList    = array();
    	$productList  = array();//
        $order_extend = array();
        $goodsIdList = array();
		/*开始计算goods和product的优惠信息 , 会根据条件分析出执行以下哪一种情况:
		 *(1)查看此商品(货品)是否已经根据不同会员组设定了优惠价格;
		 *(2)当前用户是否属于某个用户组中的成员，并且此用户组享受折扣率;
		 *(3)优惠价等于商品(货品)原价;
		 */
		//获取商品或货品数据
		/*Goods 拼装商品优惠价的数据*/
        $goodsListFinal = array();
        foreach($buyInfoList as $buy=>$buyInfo)
        {
    	    if(isset($buyInfo['goods']['id']) && $buyInfo['goods']['id'])
    	    {
    		    //购物车中的商品数据
    		    $goodsIdStr = join(',',$buyInfo['goods']['id']);
    		    $goodsObj   = new IModel('goods as go');
    		    $goodsList  = $goodsObj->query('go.id in ('.$goodsIdStr.')','go.name,go.id as goods_id,go.img,go.sell_price,go.point,go.weight,go.store_nums,go.exp,go.goods_no,0 as product_id,go.seller_id,go.delivery_id,go.combine_price,go.type,go.sign_code');
    		    //开始优惠情况判断
    		    foreach($goodsList as $key => $val)
    		    {
                    $order_extend[$val['seller_id']]['sum'] = isset($order_extend[$val['seller_id']]['sum']) ? $order_extend[$val['seller_id']]['sum'] : 0;
                    $order_extend[$val['seller_id']]['weight'] = isset($order_extend[$val['seller_id']]['weight']) ? $order_extend[$val['seller_id']]['weight'] : 0;
                    $order_extend[$val['seller_id']]['point'] = isset($order_extend[$val['seller_id']]['point']) ? $order_extend[$val['seller_id']]['point'] : 0;
                    $order_extend[$val['seller_id']]['tax'] = isset($order_extend[$val['seller_id']]['tax']) ? $order_extend[$val['seller_id']]['tax'] : 0;
                    $order_extend[$val['seller_id']]['exp'] = isset($order_extend[$val['seller_id']]['exp']) ? $order_extend[$val['seller_id']]['exp'] : 0;
                    $order_extend[$val['seller_id']]['count'] = isset($order_extend[$val['seller_id']]['count']) ? $order_extend[$val['seller_id']]['count'] : 0;
                    $order_extend[$val['seller_id']]['reduce'] = isset($order_extend[$val['seller_id']]['reduce']) ? $order_extend[$val['seller_id']]['reduce'] : 0;
    //     			//检查库存
    //     			if($buyInfo['goods']['data'][$val['goods_id']]['count'] <= 0 || $buyInfo['goods']['data'][$val['goods_id']]['count'] > $val['store_nums'])
    //     			{
    //     				return "商品：".$val['name']."购买数量超出库存，请重新调整购买数量";
    //     			}
				    if(isset($buyInfo['goods']['data'][$val['goods_id']]['active_price']) && $buyInfo['goods']['data'][$val['goods_id']]['active_price'] && $buy == 0){//如果存在活动价格
					    $minPrice = $buyInfo['goods']['data'][$val['goods_id']]['active_price'];
				    }
				    else{
					    $groupPrice                = $this->getGroupPrice($val['goods_id'],'goods');
                        if($buy <> 0)
                        {
                            $minPrice = ($val['combine_price'] && $val['combine_price'] <> '0.00') ? $val['combine_price'] : ($groupPrice ? $groupPrice : $val['sell_price']);
                        }
                        else
                        {
                            $minPrice = $groupPrice ? $groupPrice : $val['sell_price'];
                        }
				    }
                    
    			    $minPrice = min($minPrice,$val['sell_price']);
                    if($buy)
                    {
                        $comObj = new IModel('combine_goods');
                        $goodsList[$key]['combine'] = $comObj->getField('id='.$buy, 'name');
                    }
    			    $goodsList[$key]['reduce'] = $val['sell_price'] - $minPrice;
    			    $goodsList[$key]['count']  = $buyInfo['goods']['data'][$val['goods_id']]['count'];
                    $goodsList[$key]['combine_id'] = $buy;
                    
                    //计算运费
                    $delivery = Delivery::getDelivery(0, $val['delivery_id'], $val['goods_id'], $val['product_id'], $goodsList[$key]['count']);
                    $goodsList[$key]['delivery'] = 0;
                    if(isset($delivery['price']))
                    {
                       $goodsList[$key]['delivery']  += $delivery['price']; 
                    }
                    
    			    $current_sum_all           = $goodsList[$key]['sell_price'] * $goodsList[$key]['count'];
    			    $current_reduce_all        = $goodsList[$key]['reduce']     * $goodsList[$key]['count'];
    			    $goodsList[$key]['sum']    = $current_sum_all - $current_reduce_all;

                    $order_extend[$val['seller_id']]['sum'] += $current_sum_all;
                    $order_extend[$val['seller_id']]['weight'] += $val['weight'] * $goodsList[$key]['count'];
                    $order_extend[$val['seller_id']]['point'] += $val['point']  * $goodsList[$key]['count'];
                    $order_extend[$val['seller_id']]['tax'] += self::getGoodsTax($goodsList[$key]['sum'],$val['seller_id'],$val['goods_id'],'goods');
                    $order_extend[$val['seller_id']]['exp'] += $val['exp']    * $goodsList[$key]['count'];
                    $order_extend[$val['seller_id']]['count'] += $goodsList[$key]['count'];
                    $order_extend[$val['seller_id']]['reduce'] += $current_reduce_all;
    			    //全局统计
		    	    $this->weight += $val['weight'] * $goodsList[$key]['count'];
		    	    $this->point  += $val['point']  * $goodsList[$key]['count'];
		    	    $this->exp    += $val['exp']    * $goodsList[$key]['count'];
		    	    $this->sum    += $current_sum_all;
		    	    $this->reduce += $current_reduce_all;
		    	    $this->count  += $goodsList[$key]['count'];
		    	    $this->tax    += self::getGoodsTax($goodsList[$key]['sum'],$val['seller_id'],$val['goods_id'],'goods');
                    $goodsIdList[$val['goods_id']] = array('sum' => $current_sum_all, 'reduce' => $current_reduce_all,'count' => $goodsList[$key]['count']);
                    $order_extend[$val['seller_id']]['goodsIdList'][$val['goods_id']] = array('sum' => $current_sum_all, 'reduce' => $current_reduce_all,'count' => $goodsList[$key]['count']);
                    $goodsListFinal[$buy][] = $goodsList[$key];
		        }
    	    }

		    /*Product 拼装商品优惠价的数据*/
    	    if(isset($buyInfo['product']['id']) && $buyInfo['product']['id'])
    	    {
    		    //购物车中的货品数据
    		    $productIdStr = join(',',$buyInfo['product']['id']);
    		    $productObj   = new IQuery('products as pro,goods as go');
    		    $productObj->where  = 'pro.id in ('.$productIdStr.') and go.id = pro.goods_id';
    		    $productObj->fields = 'pro.sell_price,pro.weight,pro.id as product_id,pro.spec_array,pro.goods_id,pro.store_nums,pro.products_no as goods_no,go.name,pro.point,pro.combine_price,go.exp,go.img,go.seller_id,go.delivery_id,go.type,pro.sign_code';
    		    $productList  = $productObj->find();
    		    //开始优惠情况判断
    		    foreach($productList as $key => $val)
    		    {
                    $order_extend[$val['seller_id']]['sum'] = isset($order_extend[$val['seller_id']]['sum']) ? $order_extend[$val['seller_id']]['sum'] : 0;
                    $order_extend[$val['seller_id']]['weight'] = isset($order_extend[$val['seller_id']]['weight']) ? $order_extend[$val['seller_id']]['weight'] : 0;
                    $order_extend[$val['seller_id']]['point'] = isset($order_extend[$val['seller_id']]['point']) ? $order_extend[$val['seller_id']]['point'] : 0;
                    $order_extend[$val['seller_id']]['tax'] = isset($order_extend[$val['seller_id']]['tax']) ? $order_extend[$val['seller_id']]['tax'] : 0;
                    $order_extend[$val['seller_id']]['exp'] = isset($order_extend[$val['seller_id']]['exp']) ? $order_extend[$val['seller_id']]['exp'] : 0;
                    $order_extend[$val['seller_id']]['count'] = isset($order_extend[$val['seller_id']]['count']) ? $order_extend[$val['seller_id']]['count'] : 0;
                    $order_extend[$val['seller_id']]['reduce'] = isset($order_extend[$val['seller_id']]['reduce']) ? $order_extend[$val['seller_id']]['reduce'] : 0;
    			    //检查库存
    //     			if($buyInfo['product']['data'][$val['product_id']]['count'] <= 0 || $buyInfo['product']['data'][$val['product_id']]['count'] > $val['store_nums'])
    //     			{
    //     				return "货品：".$val['name']."购买数量超出库存，请重新调整购买数量";
    //     			}

    			    if(isset($buyInfo['product']['data'][$val['product_id']]['active_price']) && $buyInfo['product']['data'][$val['product_id']]['active_price']){//如果存在活动价格
					    $minPrice = $buyInfo['product']['data'][$val['product_id']]['active_price'];
				    }else{
	    			    $groupPrice                  = $this->getGroupPrice($val['product_id'],'product');
                        if($buy <> 0)
                        {
                            $minPrice = ($val['combine_price'] && $val['combine_price'] <> '0.00') ? $val['combine_price'] : ($groupPrice ? $groupPrice : $val['sell_price']);
                        }
                        else
                        {
                            $minPrice = $groupPrice ? $groupPrice : $val['sell_price'];
                        }
				    }
    			    $minPrice = min($minPrice,$val['sell_price']);
    			    if($buy)
                    {
                        $comObj = new IModel('combine_goods');
                        $productList[$key]['combine'] = $comObj->getField('id='.$buy, 'name');
                    }
				    $productList[$key]['reduce'] = $val['sell_price'] - $minPrice;
    			    $productList[$key]['count']  = $buyInfo['product']['data'][$val['product_id']]['count'];
                    $productList[$key]['combine_id'] = $buy;
    			    $current_sum_all             = $productList[$key]['sell_price']  * $productList[$key]['count'];
    			    $current_reduce_all          = $productList[$key]['reduce']      * $productList[$key]['count'];
    			    $productList[$key]['sum']    = $current_sum_all - $current_reduce_all;

                    //计算运费
                    $delivery = Delivery::getDelivery(0, $val['delivery_id'], $val['goods_id'], $val['product_id'], $productList[$key]['count']);
                    $productList[$key]['delivery'] = 0;
                    if(isset($delivery['price']))
                    {
                       $productList[$key]['delivery']  += $delivery['price']; 
                    }
                    $order_extend[$val['seller_id']]['sum'] += $current_sum_all;
                    $order_extend[$val['seller_id']]['weight'] += $val['weight'] * $productList[$key]['count'];
                    $order_extend[$val['seller_id']]['point'] += $val['point']  * $productList[$key]['count'];
                    $order_extend[$val['seller_id']]['tax'] += self::getGoodsTax($productList[$key]['sum'],$val['seller_id'],$val['product_id'],'product');
                    $order_extend[$val['seller_id']]['exp'] += $val['exp']    * $productList[$key]['count'];
                    $order_extend[$val['seller_id']]['count'] += $productList[$key]['count'];
                    $order_extend[$val['seller_id']]['reduce'] += $current_reduce_all;
    			    //全局统计
		    	    $this->weight += $val['weight'] * $productList[$key]['count'];
		    	    $this->point  += $val['point']  * $productList[$key]['count'];
		    	    $this->exp    += $val['exp']    * $productList[$key]['count'];
		    	    $this->sum    += $current_sum_all;
		    	    $this->reduce += $current_reduce_all;
		    	    $this->count  += $productList[$key]['count'];
		    	    $this->tax    += self::getGoodsTax($productList[$key]['sum'],$val['seller_id'],$val['product_id'],'product');
                    $goodsIdList[$val['goods_id']] = array('sum' => $current_sum_all, 'reduce' => $current_reduce_all,'count' => $productList[$key]['count']);
                    $goodsListFinal[$buy][] = $productList[$key];
		            $order_extend[$val['seller_id']]['goodsIdList'][$val['goods_id']] = array('sum' => $current_sum_all, 'reduce' => $current_reduce_all,'count' => $productList[$key]['count']);
                }
    	    }
        }
		$final_sum = $this->sum - $this->reduce;
    	//总金额满足的促销规则
    	if($user_id&&$prom)
    	{
            //拆分订单的促销规则
            foreach($order_extend as $k => $v)
            {
                $proObj = new ProRule($v['sum'] - $v['reduce']);
                $proObj->setUserGroup($group_id);
                $this->isFreeFreight = $proObj->isFreeFreight($area,$v['goodsIdList']);
                $order_extend[$k]['promotion'] = $proObj->getInfo($v['goodsIdList'],$area);
                $order_extend[$k]['proReduce'] = $v['sum'] - $v['reduce'] - $proObj->getSum($v['goodsIdList'], $area);
                unset($proObj);
            }
	    	$proObj = new ProRule($final_sum);
	    	$proObj->setUserGroup($group_id);
	    	$this->isFreeFreight = $proObj->isFreeFreight($area,$goodsIdList);
	    	$this->promotion = $proObj->getInfo($goodsIdList,$area, true);
	    	$this->proReduce = $final_sum - $proObj->getSum($goodsIdList, $area);
    	}
    	else
    	{
            foreach($order_extend as $k => $v)
            {
                $order_extend[$k]['promotion'] = 0;
                $order_extend[$k]['proReduce'] = 0;
            }
	    	$this->promotion = array();
	    	$this->proReduce = 0;
    	}
    	$this->final_sum = $final_sum - $this->proReduce;
        $this->extend  = $order_extend;
    	return array(
    		'final_sum'  => $this->final_sum,//减去会员减价和促销减价后的总价
    		'promotion'  => $this->promotion,//促销信息
    		'proReduce'  => $this->proReduce,//促销规则减价
    		'sum'        => $this->sum,//原总价
    		'goodsList'  => $goodsListFinal,
    		'count'      => $this->count,
    		'reduce'     => $this->reduce,//会员价减价
    		'weight'     => $this->weight,
    		'freeFreight'=> $this->isFreeFreight,
    		'point'      => $this->point,
    		'exp'        => $this->exp,
    		'tax'        => $this->tax,
            'extend'     => $this->extend,
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
	public function cart_count($cartData='', $area = null)
	{
		//获取购物车中的商品和货品信息
    	$cartObj    = new Cart();
    	$myCartInfo = $cartObj->getMyCart($cartData);
    	return $this->goodsCount($myCartInfo, $area);
    }

    //计算非购物车中的商品
    public function direct_count($id,$type,$buy_num = 1,$promo='',$active_id='',$cid='',$area=null)
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
	    		
	    		$buyInfo = array(
	    				0=>array($type => array('id' => array($id) , 'data' => array($id => array('count' => $buy_num,'active_price'=>$disPrice)),'count' => $buy_num))
	    		);
	    		
				//设置优惠价格，如果不存在则优惠价等于商品原价
				
				$result = $this->goodsCount($buyInfo,$area);
				
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
            if(is_numeric($id))
            {
                $buyInfo[0] = array(
                    $type => array('id' => array($id) , 'data' => array($id => array('count' => $buy_num)),'count' => $buy_num)
                );
            }
    		else
            {
                $buyInfo = array();
                $idList = explode('$', $id);
                array_shift($idList);
                $typeList = explode('$', $type);
                array_shift($typeList);
                foreach($idList as $k=>$v)
                {
                    $buyInfo[$cid][$typeList[$k]]['id'][] = $v;
                    $buyInfo[$cid][$typeList[$k]]['data'][$v] = array('count'=>$buy_num);
                }
            }
    		return $this->goodsCount($buyInfo,$area);
    	}
    }

    /**
     * 计算订单信息,其中部分计算都是以商品原总价格计算的$goodsSum
     * @param $goodsResult array CountSum结果集
     * @param $area_id int 区域ID
     * @param $delievery_id int 配送方式ID
     * @param $payment_id int 支付ID
     * @param $is_insured array("goods_id_product_id")部分商品需要报价 or int都设置保价
     * @param $is_invoice int 是否要发票
     * @param $discount float 订单的加价或者减价
     * @return $result 最终的返回数组
     */
    public static function countOrderFee($goodsResult,$area_id,$delivery_id,$payment_id,$is_insured,$is_invoice,$discount = 0)
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
    	$goods_seller_data = array();
    	foreach($goodsResult['goodsList'] as $key => $val){
    		if(!isset($goods_seller_data[$val['seller_id']])){
    			$goods_seller_data[$val['seller_id']]['sum'] = $val['sum'];
    			$goods_seller_data[$val['seller_id']]['weight'] = $val['weight'];
    		}
    		else{
    			$goods_seller_data[$val['seller_id']]['sum'] += $val['sum'];
    			$goods_seller_data[$val['seller_id']]['weight'] += $val['weight'];
    		}
    	}
        if($delivery_id)
        {
    	    foreach($goods_seller_data as $k=>$val){
    		    $deliveryRow = Delivery::getDeliveryWeight($area_id,$delivery_id,$val['weight'],$k,$val['sum']);
    		    //商品无法送达
    		    if($deliveryRow['if_delivery'] == 1)
    		    {
    			    return "您所选购的商品：".$val['name']."，无法送达";
    		    }
    		    $goods_seller_data[$k]['deliveryPrice'] = $deliveryRow['price'];
    		    $goods_seller_data[$k]['insuredPrice'] = $deliveryRow['protect_price'];
    		    $result['deliveryOrigPrice'] += $deliveryRow['price'];
    		    
    		    //商品保价计算
    		    //	if($is_insured == 1 || ( is_array($is_insured) && isset($is_insured[$val['goods_id']."_".$val['product_id']]) ) )
    		    if($is_insured == 1  || ( is_array($is_insured) && isset($is_insured[$k]) ) )
    		    {
    			    $result['insuredPrice'] += $deliveryRow['protect_price'];
    		    }
    		    if(!$goodsResult['freeFreight'])
    		    {
    			    $result['deliveryPrice'] += $deliveryRow['price'];
    		    }
    	    }
        }
        else
        {
            foreach($goods_seller_data as $k=>$val){
                $goods_seller_data[$k]['deliveryPrice'] = 0;
                $goods_seller_data[$k]['insuredPrice'] = 0;
                $result['deliveryOrigPrice'] += 0;
                
                //商品保价计算
                //    if($is_insured == 1 || ( is_array($is_insured) && isset($is_insured[$val['goods_id']."_".$val['product_id']]) ) )
                if($is_insured == 1  || ( is_array($is_insured) && isset($is_insured[$k]) ) )
                {
                    $result['insuredPrice'] += 0;
                }
                if(!$goodsResult['freeFreight'])
                {
                    $result['deliveryPrice'] += 0;
                }
            }
        }
    	foreach($goodsResult['goodsList'] as $key => $val){
    		//商品保价计算
    		//	if($is_insured == 1 || ( is_array($is_insured) && isset($is_insured[$val['goods_id']."_".$val['product_id']]) ) )
    		if($is_insured == 1  )
    		{
    			$goodsResult['goodsList'][$key]['insuredPrice'] = $goods_seller_data[$val['seller_id']]['insuredPrice'];
    		}
    		else
    		{
    			$goodsResult['goodsList'][$key]['insuredPrice'] = 0;
    		}
    		 if($goodsResult['freeFreight'] == true)
    		{
    			  $goodsResult['goodsList'][$key]['deliveryPrice'] = 0;
    		}
    		 else
    		{
    			$goodsResult['goodsList'][$key]['deliveryPrice'] = $goods_seller_data[$val['seller_id']]['deliveryPrice'];
    		}
    		
    		//商品税金计算
    		if($is_invoice == true)
    		{
    			
    			$tempTax = self::getGoodsTax($val['sum'],$val['seller_id']);
    			$goodsResult['goodsList'][$key]['taxPrice'] = $tempTax;
    			$result['taxPrice'] += $tempTax;
    			 
    		}
    		else{
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
     * 计算订单信息,其中部分计算都是以商品原总价格计算的$goodsSum
     * @param $goodsResult array CountSum结果集
     * @param $area_id int 区域ID         
     * @param $payment_id int 支付ID
     * @param $is_insured array("goods_id_product_id")部分商品需要报价 or int都设置保价
     * @param $is_invoice int 是否要发票
     * @param $discount float 订单的加价或者减价
     * @return $result 最终的返回数组
     */
    public static function countOrderFeeee($goodsResult,$area_id,$payment_id,$is_insured,$is_invoice,$discount = 0)
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
            'order_extend' => array()
        );
        $goods_seller_data = array();
        $order_extend = array();
        foreach($goodsResult['goodsList'] as $key => $val){
            $order_extend[$val['seller_id']]['deliveryOrigPrice'] = isset($order_extend[$val['seller_id']]['deliveryOrigPrice']) ? $order_extend[$val['seller_id']]['deliveryOrigPrice'] : 0;
            $order_extend[$val['seller_id']]['insuredPrice'] = isset($order_extend[$val['seller_id']]['insuredPrice']) ? $order_extend[$val['seller_id']]['insuredPrice'] : 0;
            $order_extend[$val['seller_id']]['deliveryPrice'] = isset($order_extend[$val['seller_id']]['deliveryPrice']) ? $order_extend[$val['seller_id']]['deliveryPrice'] : 0;
            $order_extend[$val['seller_id']]['taxPrice'] = isset($order_extend[$val['seller_id']]['taxPrice']) ? $order_extend[$val['seller_id']]['taxPrice'] : 0;
            $order_extend[$val['seller_id']]['sum'] = isset($order_extend[$val['seller_id']]['sum']) ? $order_extend[$val['seller_id']]['sum'] : 0;
            $order_extend[$val['seller_id']]['reduce'] = isset($order_extend[$val['seller_id']]['reduce']) ? $order_extend[$val['seller_id']]['reduce'] : 0;
            $deliveryRow = Delivery::getDelivery($area_id, $val['delivery_id'], $val['goods_id'], $val['product_id'], $val['count']);
            
            if(is_string($deliveryRow) || $deliveryRow['if_delivery'] == 1)
            {
                return "您所选购的商品：".$val['name']."，无法送达";
            }
            $result['deliveryOrigPrice'] += $deliveryRow['price'];
            $order_extend[$val['seller_id']]['deliveryOrigPrice'] += $deliveryRow['price'];
            
            //商品保价计算
            //    if($is_insured == 1 || ( is_array($is_insured) && isset($is_insured[$val['goods_id']."_".$val['product_id']]) ) )
            if($is_insured == 1  || ( is_array($is_insured) && isset($is_insured[$key]) ) )
            {
                $result['insuredPrice'] += $deliveryRow['protect_price'];
                $order_extend[$val['seller_id']]['insuredPrice'] += $deliveryRow['protect_price'];
                $order_extend[$val['seller_id']]['if_insured'] =1;
            }
            if(!$goodsResult['freeFreight'])
            {
                $result['deliveryPrice'] += $deliveryRow['price'];
                $order_extend[$val['seller_id']]['deliveryPrice'] += $deliveryRow['price'];
                $goodsResult['goodsList'][$key]['deliveryPrice'] = $deliveryRow['price'];
            }
            else
            {
                if(is_array($goodsResult['freeFreight']))
                {
                    $proModel = new IModel('promotion');
                    $freeFreight = implode(',', $goodsResult['freeFreight']);
                    $goods = $proModel->query('id in ('.$freeFreight.')', 'goods_id, seller_id');
                    $goodsList = '';
                    foreach($goods as $v)
                    {
                        if($v['goods_id'] == 'all')
                        {
                            $goods = new IModel('goods');
                            $gId = $goods->getFields(array('seller_id' => $v['seller_id'], 'id' => $val['goods_id']), 'id');
                            $goodsList .= $gId  ? ','.implode(',', $gId) : '';
                        }
                        elseif(!empty($v['goods_id']))
                        {
                            $goodsList .= ','.$v['goods_id'];
                        }
                    }
                    if(in_array($val['goods_id'], explode(',',$goodsList)))
                    {
                        $goodsResult['goodsList'][$key]['deliveryPrice'] = 0;
                    }
                    else
                    {
                        $result['deliveryPrice'] += $deliveryRow['price'];
                        $order_extend[$val['seller_id']]['deliveryPrice'] += $deliveryRow['price'];
                        $goodsResult['goodsList'][$key]['deliveryPrice'] = $deliveryRow['price'];
                    }
                }
                else
                {
                    $goodsResult['goodsList'][$key]['deliveryPrice'] = 0;
                }
            }
            $goodsResult['goodsList'][$key]['insuredPrice'] = $deliveryRow['protect_price'];

            //商品税金计算
            if($is_invoice == true)
            {
                if($val['product_id'])
                {
                    $tempTax = self::getGoodsTax($val['sum'],$val['seller_id'],$val['product_id'], 'product');
                }
                else
                {
                    $tempTax = self::getGoodsTax($val['sum'],$val['seller_id'],$val['goods_id'], 'goods');
                }
                $goodsResult['goodsList'][$key]['taxPrice'] = $tempTax;
                $result['taxPrice'] += $tempTax;
                $order_extend[$val['seller_id']]['taxPrice'] += $tempTax;
                 
            }
            else{
                $goodsResult['goodsList'][$key]['taxPrice'] = 0;
            }
            $order_extend[$val['seller_id']]['sum'] += $val['sum'];
            $order_extend[$val['seller_id']]['reduce'] += $val['reduce'];
        }
        foreach($order_extend as $k => $v)
        {            
            //非货到付款的线上支付方式手续费
            if($payment_id != 0)
            {
                $order_extend[$k]['paymentPrice'] = self::getGoodsPaymentPrice($payment_id,$v['sum']-$v['reduce']);
            }
            else
            {
                $order_extend[$k]['paymentPrice'] = 0;
            }
        }

        //最终订单金额计算
        $order_amount = $goodsFinalSum + $result['deliveryPrice'] + $result['insuredPrice'] + $result['taxPrice'] + $result['paymentPrice'] + $discount;
        $result['orderAmountPrice'] = $order_amount <= 0 ? 0 : round($order_amount,2);

        //订单商品刷新
        $result['goodsResult'] = $goodsResult;
        $result['order_extend'] = $order_extend;
        return $result;
    }
    
    /**
     * 计算订单信息，预售订单
     * @param $goodsResult array CountSum结果集
     * @param $province_id int 区域ID
     * @param $delievery_id int 配送方式ID
     * @param $payment_id int 支付ID
     * @param $is_insured array("goods_id_product_id")部分商品需要报价 or int都设置保价
     * @param $is_invoice int 是否要发票
     * @param $discount float 订单的加价或者减价
     * @return $result 最终的返回数组
     */
    public static function countOrderFeePresell($goodsResult,$province_id,$payment_id,$is_insured,$is_invoice,$discount = 0)
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
			$deliveryRow = Delivery::getDelivery($province_id,$val['delivery_id'],$val['goods_id'],$val['product_id'],$val['count']);
			//商品无法送达
			if(!isset($deliveryRow['if_delivery']) || $deliveryRow['if_delivery'] == 1)
			{
				return "您所选购的商品：".$val['name']."，无法送达";
			}

			//商品保价计算
			if($is_insured == 1 || ( is_array($is_insured) && isset($is_insured[$key]) ) )
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

			/*$deliveryRow = Delivery::getDelivery($province_id,$delivery_id,$val['goods_id'],$val['product_id'],$val['count']);
            if($delivery_id)
            {
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
            }
            else
            {
                //商品保价计算
                if($is_insured == 1 || ( is_array($is_insured) && isset($is_insured[$val['goods_id']."_".$val['product_id']]) ) )
                {
                    $goodsResult['goodsList'][$key]['insuredPrice'] = 0;
                    $result['insuredPrice'] += 0;
                }
                else
                {
                    $goodsResult['goodsList'][$key]['insuredPrice'] = 0;
                }

                //商品运费计算
                $result['deliveryOrigPrice'] += 0;
                if($goodsResult['freeFreight'] == true)
                {
                    $goodsResult['goodsList'][$key]['deliveryPrice'] = 0;
                }
                else
                {
                    $result['deliveryPrice'] += 0;
                    $goodsResult['goodsList'][$key]['deliveryPrice'] = 0;
                }
            }*/
			//商品税金计算
	    	if($is_invoice == true)
	    	{
                if($val['product_id'])
                {
                    $tempTax = self::getGoodsTax($val['sum'],$val['seller_id'],$val['product_id'],'products');
                }
	    		else
                {
                    $tempTax = self::getGoodsTax($val['sum'],$val['seller_id'],$val['goods_id'],'goods');
                }
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
     * @param $id int 商品 or 货品ID
     * @param $type string 类型
     * @return $goodsTaxPrice float 商品的税金
     */
    public static function getGoodsTax($goodsSum,$seller_id = 0, $id = 0, $type='goods')
    {
        if($type == 'goods')
        {
            $model = new IModel('goods');
            $default_tax = $model->getField('id='.$id, 'default_tax');
            if($default_tax)
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
            }
            else
            {
                $tax = $model->getField('id='.$id, 'tax');
                $tax_per = $tax ? $tax : 0;
            }
        }
    	else
        {
            $model = new IModel('products');
            $default_tax = $model->getField('id='.$id, 'default_tax');
            if($default_tax)
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
            }
            else
            {
                $tax = $model->getField('id='.$id, 'tax');
                $tax_per = $tax ? $tax : 0;
            }
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
    	$where .= $seller_id          ? " and og.seller_id = ".$seller_id : "";
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