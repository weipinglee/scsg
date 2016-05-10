<?php
/**
 * @file article.php
 * @brief 订单中配送方式的计算
 * @date 2011-02-24
 * @version 0.6
 */
class Delivery
{
	//首重重量
	private static $firstWeight  = 0;

	//次重重量
	private static $secondWeight = 0;

	/**
	 * 根据重量计算给定价格
	 * @param $weight float 总重量
	 * @param $firstFee float 首重费用
	 * @param $second float 次重费用
	 */
	private static function getFeeByWeight($weight,$firstFee,$secondFee)
	{
		$firstFee = (float)$firstFee;
		$secondFee = (float)$secondFee;
		//当商品重量小于或等于首重的时候
		if($weight <= self::$firstWeight)
		{
			return $firstFee;
		}

		//当商品重量大于首重时，根据次重进行累加计算
		$num = ceil(($weight - self::$firstWeight)/self::$secondWeight);
		return $firstFee + $secondFee * $num;
	}

	/**
	 * @brief 配送方式计算管理模块
	 * @param $area    int 区域的ID
	 * @param $delivery_id int 配送方式ID
	 * @param $goods_id    int 商品ID
	 * @param $product_id  int 货品ID
	 * @param $num         int 商品数量
	 * @return array(if_delivery => 0:支持配送;1:不支持配送; price => 运费;protect_price => 保价;)
	 */
	public static function getDelivery($area,$delivery_id,$goods_id,$product_id = 0,$num = 1)
	{
		$goodsRow = $product_id > 0 ? Api::run("getProductInfo",array('#id#',$product_id)) : Api::run("getGoodsInfo",array('#id#',$goods_id));

		//获取默认的配送方式信息
		$delivery    = new IModel('delivery');
		$deliveryRow = $delivery->getObj('is_delete = 0 and status = 1 and id = '.$delivery_id);
		if(!$deliveryRow)
		{
			return "配送方式不存在";
		}

		//商家商品
		if(isset($goodsRow['seller_id']) && $goodsRow['seller_id'])
		{
			$deliveryExtendDB = new IModel('delivery_extend');
			$deliverySellerRow = $deliveryExtendDB->getObj('delivery_id = '.$delivery_id.' and seller_id = '.$goodsRow['seller_id']);
			//使用商家配置的物流运费
			if($deliverySellerRow)
			{
				$deliveryRow = $deliverySellerRow;
			}
		}

 		//设置首重和次重
 		self::$firstWeight          = $deliveryRow['first_weight'];
 		self::$secondWeight         = $deliveryRow['second_weight'];
 		$weight                     = $num * $goodsRow['weight'];
		$deliveryRow['if_delivery'] = '0';

 		//当配送方式是统一配置的时候，不进行区分地区价格
		$area_groupid = unserialize($deliveryRow['area_groupid']);
		//print_r($area_groupid);
 		if($deliveryRow['price_type'] == 0 || !is_array($area_groupid))
 		{
 			$deliveryRow['price'] = self::getFeeByWeight($weight,$deliveryRow['first_price'],$deliveryRow['second_price']);
 		}
 		//当配送方式为指定区域和价格的时候
 		else
 		{
			$matchKey = $matchKeyArea = $matchKeyCity = $matchKeyProvince = '';
			$flag     = false;

			//每项都是以';'隔开的省份ID
			//$area_groupid = unserialize($deliveryRow['area_groupid']);
			foreach($area_groupid as $key => $result)
			{
				//匹配到了特殊的省份运费价格
				if(strpos($result,';'.$area.';') !== false && $matchKeyArea==='')
				{
					$matchKeyArea = $key;
					$flag     = true;
				}
				else if(strpos($result,';'.substr($area,0,4).'00;') !== false && $matchKeyCity===''){
					$matchKeyCity = $key;
					$flag     = true;
					
				}
				else if(strpos($result,';'.substr($area,0,2).'0000;') !== false )
				{
					$matchKeyProvince = $key;
					$flag     = true;
				}
			}

			//匹配到了特殊的省份运费价格
			if($flag)
			{
				if($matchKeyArea){
					$matchKey = $matchKeyArea;
				}else if($matchKeyCity){
					$matchKey =$matchKeyCity;
				}else {
					$matchKey = $matchKeyProvince;
				}
				//echo $matchKey;
				//获取当前省份特殊的运费价格
				$firstprice  = unserialize($deliveryRow['firstprice']);
				$secondprice = unserialize($deliveryRow['secondprice']);

				$deliveryRow['price'] = self::getFeeByWeight($weight,$firstprice[$matchKey],$secondprice[$matchKey]);
			}
			else
			{
     			//判断是否设置默认费用了
     			if($deliveryRow['open_default'] == 1)
     			{
     				$deliveryRow['price'] = self::getFeeByWeight($weight,$deliveryRow['first_price'],$deliveryRow['second_price']);
     			}
     			else
     			{
     				$deliveryRow['price']       = '0';
     				$deliveryRow['if_delivery'] = '1';
     			}
			}
 		}

 		//计算保价
 		if($deliveryRow['is_save_price'] == 1)
 		{
 			$goodsSum                     = $num * $goodsRow['sell_price'];
 			$tempProtectPrice             = $goodsSum * ($deliveryRow['save_rate'] * 0.01);
 			$deliveryRow['protect_price'] = ($tempProtectPrice <= $deliveryRow['low_price']) ? $deliveryRow['low_price'] : $tempProtectPrice;
 		}
 		else
 		{
 			$deliveryRow['protect_price'] = 0;
 		}
 		
     	return $deliveryRow;
	}
	
	/**
	 * @brief 根据重量地区计算订单运费
	 * @param $area    int 区域的ID
	 * @param $delivery_id int 配送方式ID
	 * @param $weight    int  订单商品总重量
	 * $seller_id int 商家id
	 * @$total_price float 总价
	 * @return array(if_delivery => 0:支持配送;1:不支持配送; price => 运费;protect_price => 保价;)
	 */
	public static function getDeliveryWeight($area,$delivery_id,$weight,$seller_id,$total_price)
	{
		
		//获取默认的配送方式信息
		$delivery    = new IModel('delivery');
		$deliveryRow = $delivery->getObj('is_delete = 0 and status = 1 and id = '.$delivery_id);
		if(!$deliveryRow)
		{
			return "配送方式不存在";
		}
	
		//商家商品
		if($seller_id!=0)
		{
			$deliveryExtendDB = new IModel('delivery_extend');
			$deliverySellerRow = $deliveryExtendDB->getObj('delivery_id = '.$delivery_id.' and seller_id = '.$seller_id);
			//使用商家配置的物流运费
			if($deliverySellerRow)
			{
				$deliveryRow = $deliverySellerRow;
			}
		}
	
		//设置首重和次重
		self::$firstWeight          = $deliveryRow['first_weight'];
		self::$secondWeight         = $deliveryRow['second_weight'];
		$deliveryRow['if_delivery'] = '0';
	
		//当配送方式是统一配置的时候，不进行区分地区价格
		$area_groupid = unserialize($deliveryRow['area_groupid']);
		if($deliveryRow['price_type'] == 0 || !is_array($area_groupid))
		{
			$deliveryRow['price'] = self::getFeeByWeight($weight,$deliveryRow['first_price'],$deliveryRow['second_price']);
		}
		//当配送方式为指定区域和价格的时候
		else
		{
			$matchKey = $matchKeyArea = $matchKeyCity = $matchKeyProvince = '';
			$flag     = false;
	
			//每项都是以';'隔开的省份ID
			//$area_groupid = unserialize($deliveryRow['area_groupid']);
			foreach($area_groupid as $key => $result)
			{
				//匹配到了特殊的省份运费价格
				if(strpos($result,';'.$area.';') !== false && $matchKeyArea=='')
				{
					$matchKeyArea = $key;
					$flag     = true;
				}
				else if(strpos($result,';'.substr($area,0,4).'00;') !== false && $matchKeyCity==''){
					$matchKeyCity = $key;
					$flag     = true;
						
				}
				else if(strpos($result,';'.substr($area,0,2).'0000;') !== false )
				{
					$matchKeyProvince = $key;
					$flag     = true;
				}
			}
	
			//匹配到了特殊的省份运费价格
			if($flag)
			{
				if($matchKeyArea){
					$matchKey = $matchKeyArea;
				}else if($matchKeyCity){
					$matchKey =$matchKeyCity;
				}else {
					$matchKey = $matchKeyProvince;
				}
				//获取当前省份特殊的运费价格
				$firstprice  = unserialize($deliveryRow['firstprice']);
				$secondprice = unserialize($deliveryRow['secondprice']);
	
				$deliveryRow['price'] = self::getFeeByWeight($weight,$firstprice[$matchKey],$secondprice[$matchKey]);
			}
			else
			{
				//判断是否设置默认费用了
				if($deliveryRow['open_default'] == 1)
				{
					$deliveryRow['price'] = self::getFeeByWeight($weight,$deliveryRow['first_price'],$deliveryRow['second_price']);
				}
				else
				{
					$deliveryRow['price']       = '0';
					$deliveryRow['if_delivery'] = '1';
				}
			}
		}
	
		//计算保价
		if($deliveryRow['is_save_price'] == 1)
		{
			$goodsSum                     = $total_price;
			$tempProtectPrice             = $goodsSum * ($deliveryRow['save_rate'] * 0.01);
			$deliveryRow['protect_price'] = ($tempProtectPrice <= $deliveryRow['low_price']) ? $deliveryRow['low_price'] : $tempProtectPrice;
		}
		else
		{
			$deliveryRow['protect_price'] = 0;
		}
		return $deliveryRow;
	}
}