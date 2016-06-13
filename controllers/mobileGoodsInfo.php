<?php

/**
 * 展示商品详情类
 * Class mobileGoodsInfo
 * author:wangzhande
 * date:2016-6-7
 */
class mobileGoodsInfo extends IController{
	//获取商品详情页信息
	public function getGoodsInfo(){
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$data=array();
		if(!$goods_id)
		{	$data['code']=0;
			$data['info']='传递的参数不正确';
			echo JSON::encode($data);
			exit;
		}
		$user_id = $this->user ? $this->user['user_id'] : 0;
		//把当前商品的数据添加到用户喜欢的数据表中
		user_like::set_user_history($goods_id,$user_id);
		//使用商品id获得商品信息
		$tb_goods = new IModel('goods');
		//获得当前商品的详情
		$goods_info = $tb_goods->getObj('id='.$goods_id." AND (is_del=0 or is_del=4)");
		if(!$goods_info)
		{	$data['code']=0;
			$data['info']='这件商品不存在或已下架';
			echo JSON::encode($data);
			exit;
		}
		//团购
		$regiment_db = new IModel('regiment');
		$goods_info['regiment'] = $regiment_db->getObj('goods_id='.$goods_id.' and is_close=0 and  TIMESTAMPDIFF(second,start_time,NOW()) >=0 and TIMESTAMPDIFF(second,end_time,NOW())<0');
		//关联货品表获得商品的规格，库存
		$product_db = new IModel('products');
		$goods_info['product'] = $product_db->query('goods_id='.$goods_info['id'],'id,spec_array,store_nums');
		$goods_info['product'] = $goods_info['product'];
		//获取品牌信息
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
		//获得当前商品的推荐类型
		$goods_info['commend'] = $commend->getFields(array('goods_id'=>$goods_id),'commend_id');

		//获取商品分类名字
		$categoryObj = new IModel('category_extend as ca,category as c');
		$categoryRow = $categoryObj->getObj('ca.goods_id = '.$goods_id.' and ca.category_id = c.id','c.id,c.name');

		$goods_info['cate_id'] = $categoryRow ? $categoryRow['id'] : 0;
		$goods_info['cate_name']=$categoryRow ?$categoryRow['name']:'';
		//获取商品图片 相册表关联商品相册关系表
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
		$goods_info['img']='http://localhost/iweb2/'.$goods_info['img'];
		//处理图片地址
		foreach($goods_info['photo'] as $k=>$v){
			$goods_info['photo'][$k]['img']='http://localhost/iweb2/'.$v['img'];

		}
		//商品是否参加促销活动(团购，抢购)
		$goods_info['promo']     = IReq::get('promo')     ? IReq::get('promo') : '';
		$goods_info['active_id'] = IReq::get('active_id') ? IFilter::act(IReq::get('active_id'),'int') : '';
		//获得扩展属性
		$tb_attribute_goods = new IQuery('goods_attribute as g');
		$tb_attribute_goods->join  = 'left join attribute as a on a.id=g.attribute_id ';
		$tb_attribute_goods->fields=' a.name,g.attribute_value ';
		$tb_attribute_goods->where = "goods_id='".$goods_id."' and attribute_id!=''";
		$tb_attribute_goods->order = "g.id asc";
		$goods_info['attribute'] = $tb_attribute_goods->find();
		//[数据挖掘]获取最终购买此商品的用户ID列表
		$tb_order = new IQuery('order_goods as og');
		$tb_order->join   = 'right join order as o on og.order_id=o.id ';
		$tb_order->fields = 'DISTINCT o.user_id';
		$tb_order->where  = 'og.goods_id = '.$goods_id;
		$tb_order->limit  = 5;
		$bugGoodInfo = $tb_order->find();
		if($bugGoodInfo)
		{
			$shop_goods_array = array();
			foreach($bugGoodInfo as $key => $val)
			{
				$shop_goods_array[] = $val['user_id'];
			}
			$goods_info['buyer_id'] = join(',',$shop_goods_array);
		}
		//透过用户id列表获得商品数据

		$tb_order->join='left join order as o on og.order_id=o.id left join goods as lg on lg.id=og.goods_id and lg.is_del=0';
		$tb_order->where='o.user_id in ('.$goods_info['buyer_id'].') and lg.id is not null';
		$tb_order->fields='DISTINCT lg.id,lg.sell_price as price,lg.img,lg.name';
		$tb_order->order='o.completion_time desc';
		$tb_order->limit=5;
		$goods_info['buyer_info']=$tb_order->find();
		//处理图片地址
		if(!empty($goods_info['buyer_info'])){
			foreach($goods_info['buyer_info'] as $k=>$v){
				$goods_info['buyer_info'][$k]['img']='http://localhost/iweb2/'.$v['img'];
			}

		}
		//获得该商品的购买记录
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
		//实例化咨询表，查询当前商品的咨询总数
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
		$goods_info['minMarketPrice'] = '';
		$goods_info['maxMarketPrice'] = '';

		$product_info = $tb_product->getObj('goods_id='.$goods_id,'max(sell_price) as maxSellPrice ,min(sell_price) as minSellPrice,max(market_price) as maxMarketPrice,min(market_price) as minMarketPrice');
		if($product_info)
		{
			$goods_info['maxSellPrice']   = $product_info['maxSellPrice'];
			$goods_info['minSellPrice']   = $product_info['minSellPrice'];
			$goods_info['minMarketPrice'] = $product_info['minMarketPrice'];
			$goods_info['maxMarketPrice'] = $product_info['maxMarketPrice'];
		}
		//获得会员价
		$countsumInstance = new countsum();
		$group_price = $countsumInstance->getGroupPrice($goods_id,'goods');
		if($group_price !==null){
			$group_price = floatval($group_price);
			if($group_price < $goods_info['sell_price']){
				$goods_info['group_price'] = $group_price;
			}
		}
		//获取标签
		$tb_tag = new IQuery('commend_tags as t');
		$tb_tag->join = 'left join commend_goods as go on t.id = go.commend_id';
		$tb_tag->where = 'go.goods_id = '.$goods_id;
		$tb_tag->fields = 't.name,t.img';
		$tb_tag->limit = 5;
		$goods_info['tag_data'] = $tb_tag->find();

		//如果是商家商品，获取商家信息
		if($goods_info['seller_id'])
		{
			$sellerDB = new IModel('seller');
			//获取商家的id，名称，邮箱，手机号，logo图片，客服号码，总评分，评价总数
			$goods_info['seller'] = $sellerDB->getObj('id = '.$goods_info['seller_id'],'id,true_name,email,mobile,logo_img,server_num,point,num');
			$goods_info['seller']['logo_img']='http://localhost/iweb2/'.$goods_info['seller']['logo_img'];
		}
		user_like::add_like_cate($goods_id,$this->user['user_id']);
		//获得评论内容
		$m_comment=new IQuery('comment as c');
		$m_comment->fields='u.username,u.head_ico,c.*';
		$m_comment->join='left join user  as u on u.id=c.user_id';

		$m_comment->where='c.goods_id='.$goods_id.' and c.status=1';
		$commentInfo=$m_comment->find();
		$goods_info['comment']=$commentInfo;
		if(!empty($goods_info['comment'])){
			foreach($goods_info['comment'] as $k=>$v){
				$goods_info['comment'][$k]['head_ico']='http://localhost/iweb2/'.$v['head_ico'];
			}
		}
		$goods_info['code']=1;
		echo JSON::encode($goods_info);

	}
}


?>