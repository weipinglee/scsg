<?php 
	class mobileGoodsInfo extends IController{
		//获取配送方式
		public function getDelivery(){
			$m_delivery=new IQuery('delivery');
			//$result=$m_goods->getObj('is_delete=0 and status=1','*');
			$m_delivery->where='is_delete=0 and status=1';
			$result=$m_delivery->find();
			echo JSON::encode($result);
		}
		//获得评论内容
		public function getComment(){
			$id=IFilter::act(IReq::get('id'));
			$m_comment=new IQuery('comment as c');
			$m_comment->fields='u.username,u.head_ico,c.*';
			$m_comment->join='left join user  as u on u.id=c.user_id';
			$m_comment->where='c.goods_id='.$id.' and c.status=1';
			$result=$m_comment->find();
			//var_dump($result);
			echo JSON::encode($result);
		}
		public function getGoodsInfo(){
			$goods_id = IFilter::act(IReq::get('goods_id'),'int');
			$data=array();
			if(!$goods_id)
			{
				$goods_id=IFilter::act(IReq::get('goods_id','post'),'int');
				if(!$goods_id){
				$data['code']=0;
				$data['info']='传递的参数不正确';
				echo JSON::encode($data);
				exit;
				}
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
			$goods_info['goods_id']=$goods_id;
			$preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';
			preg_match_all($preg,$goods_info['content'],$goods_info['introduce']);
			$goods_info['content']=$goods_info['introduce'][1];
			foreach($goods_info['content'] as $k=>$v){
				$tmp=array();
				$tmp['img']='http://v.yqrtv.com:8080/app/'.$v;
				$goods_info['content'][$k]=$tmp;
			};
			unset($goods_info['introduce']);
			$goods_info['spec']='';
			if($goods_info['spec_array']!=""){
				$goods_info['spec_array']=json_decode($goods_info['spec_array']);
				$pack=array();
				$a=0;
				foreach($goods_info['spec_array'] as $k=>$v){

					$pack[$a]['spec_id']=$v->id;
					$pack[$a]['name']=$v->name;
					$pack[$a]['type']=$v->type;
					$f=$v->value;
					//var_dump($v);
					//var_dump($f);
					$value=explode(',',$f);
					//var_dump($value);

					if($v->type==2){
						foreach($value as $k=>$v){
							$value[$k]='http://v.yqrtv.com:8080/app/'.$v;
						}
					}
					$tmp=array();
					foreach($value as $kk=>$vv){
						$tmp[$kk]['spec_value']=$vv;
					}
					$pack[$a]['value']=$tmp;
					$a++;
				}
				$goods_info['spec_array'] = $pack;
			}else{
				$goods_info['spec_array']=array();
			}
			//团购
			$regiment_db = new IModel('regiment');
			$goods_info['regiment'] = $regiment_db->getObj('goods_id='.$goods_id.' and is_close=0 and  TIMESTAMPDIFF(second,start_time,NOW()) >=0 and TIMESTAMPDIFF(second,end_time,NOW())<0');
			//关联货品表获得商品的规格，库存
			$product_db = new IModel('products');
			$goods_info['product'] = $product_db->query('goods_id='.$goods_info['id'],'id,spec_array,store_nums,sell_price');
			$goods_info['product'] = $goods_info['product'];
			foreach($goods_info['product'] as $k=>$v){
				$goods_info['product'][$k]['spec_array']=json_decode($v['spec_array']);
			}
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

			$goods_info['img']='http://v.yqrtv.com:8080/app/'.$goods_info['img'];
			//处理图片地址
			foreach($goods_info['photo'] as $k=>$v){
				$goods_info['photo'][$k]['img']='http://v.yqrtv.com:8080/app/'.$v['img'];

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

			//透过用户id列表获得商品数据
	 		 $tb_order->join='left join order as o on og.order_id=o.id left join goods as lg on lg.id=og.goods_id and lg.is_del=0';
	 		 $tb_order->where='o.user_id in ('.$goods_info['buyer_id'].') and lg.id is not null';
	 		 $tb_order->fields='DISTINCT lg.id,lg.sell_price as price,lg.img,lg.name';
	 		 $tb_order->order='o.completion_time desc';
	 		 $tb_order->limit=5;
	 		 $goods_info['buyer_info']=$tb_order->find();
			}
			//处理图片地址
			if(!empty($goods_info['buyer_info'])){
				foreach($goods_info['buyer_info'] as $k=>$v){
					$goods_info['buyer_info'][$k]['img']='http://v.yqrtv.com:8080/app/'.$v['img'];
					$goods_info['buyer_info'][$k]['goods_id']=$v['id'];
					unset($goods_info['buyer_info'][$k]['id']);
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
				$goods_info['seller']['logo_img']='http://v.yqrtv.com:8080/app/'.$goods_info['seller']['logo_img'];
			}
			user_like::add_like_cate($goods_id,$this->user['user_id']);
			//获得评论内容
			$m_comment=new IQuery('comment as c');
			$m_comment->fields='u.username,u.head_ico,c.*';
			$m_comment->join='left join user  as u on u.id=c.user_id';

			$m_comment->where='c.goods_id='.$goods_id.' and c.status=1';
			$commentInfo=$m_comment->find();
			$goods_info['comment']=$commentInfo;
			$commentImgObj=new IQuery('comment_photo as cp');
			if(!empty($goods_info['comment'])){
				foreach($goods_info['comment'] as $k=>$v){
					$goods_info['comment'][$k]['head_ico']='http://v.yqrtv.com:8080/app/'.$v['head_ico'];
					$commentImgObj->where='comment_id='.$v['id'];
					$commentImgObj->fields='cp.id,cp.img';
					$commentImgs=$commentImgObj->find();
					if($commentImgs) {
						foreach ($commentImgs as $kk => $vv) {
							$commentImgs[$kk]['img'] = 'http://v.yqrtv.com:8080/app/' . $vv['img'];
						}
					}
					$goods_info['comment'][$k]['img']=$commentImgs;
				}
			}
			//分享的url
			$goods_info['sharurl']='http://v.yqrtv.com:8080/app/site/products?id='.$goods_id;
			$goods_info['code']=1;

			$goods_info['dealId']=$goods_info['id'];
			echo JSON::encode($goods_info);
			//var_dump($goods_info);
		}

		public function addComment(){
			$token=IFilter::act(IReq::get('token','post'));
			$tokenObj=new IModel('token');
			if(!$tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
				die(JSON::encode(['code'=>0,'info'=>'未登录的用户不能评论']));
			}
			$userId=$tokenInfo['user_id'];
			$id=IFilter::act(IReq::get('id','post'),'int');
			if(!$id){
				die(JSON::encode(['code'=>0,'info'=>'参数不正确']));
			}
			$data['point']=IFilter::act(IReq::get('point','post'),'float');
			$data['contents']=IFilter::act(IReq::get('content','post'));
			$data['sellerid']=IFilter::act(IReq::get('sellerid','post'),'int');
			$data['status']=1;
			if($data['point']==0){
				die(JSON::encode(['code'=>0,'info'=>'请选择评分']));
			}
			$can_submit=Comment_Class::can_comment($id,$userId);
			if($can_submit[0]!=1){
				die(JSON::encode(['code'=>0,'info'=>'您不能评论此商品']));
			}
			$data['comment_time']=date('Y-m-d',ITime::getNow());
			$commentObj=new IModel('comment');
			$commentObj->setData($data);
			$res=$commentObj->update("id={$id}");
			//编辑成功
			if($res){
				//同步更新goods表，comments,grade
				$commentRow=$commentObj->getObj("id={$id}");
				$goodsObj=new IModel('goods');
				$goodsObj->setData(
					['comments'=>'comments+1','grade'=>'grade+'.$data['point']]
				);
				$goodsObj->update('id='.$commentRow['goods_id']);
				//更新seller表，point,num
				$sellerObj=new IModel('seller');
				$sellerObj->setData(
					['point'=>'point+'.$data['point'],'num'=>'num+1']
				);
				$sellerObj->update('id='.$data['sellerid']);
				//if(isset($_FILES['comment_img'])&&$_FILES['comment_img']['name']!=''){
					$upObj=new PhotoUpload();
					$imgRes=$upObj->run();
					if(!empty($imgRes)){
						$commentImgObj=new IModel('comment_photo');
						foreach($imgRes as $k=>$v){
							$imgData['comment_id']=$id;
						//	var_dump($v);

							$imgData['img']=$v['img'];
							$imgData['sort']=$k;
							$commentImgObj->setData($imgData);
							$commentImgObj->add();

						}
					}
				//}
				die(JSON::encode(['code'=>1,'info'=>'评论成功']));
			}else{
				die(JSON::encode(['code'=>0,'info'=>'评论失败']));
			}

		}
		public function getProductInfo(){
			$goods_id=IFilter::act(IReq::get('goods_id','post'),'int');
			if(isset($_POST['spec_array'])&&!empty($_POST['spec_array'])){
				foreach($_POST['spec_array'] as $k=>$v){
					foreach($v as $kk=>$vv){
						$spec_array[$k]['id']=$v[0];
						$spec_array[$k]['type']=$v[2];
						if($v[2]==2){
							$start=strpos($v[3],'upload');
							$spec_array[$k]['value']=substr($v[3],$start);
						}else{
							$spec_array[$k]['value']=$v[3];
						}
						$spec_array[$k]['name']=$v[1];
					}
				}
			}else{
				die(JSON::encode(['code'=>0,'info'=>'规格值不符合标准']));
			}


			$spec_array=JSON::encode($spec_array);

			//获取货品数据
			$tb_products = new IModel('products');
			$procducts_info = $tb_products->getObj("goods_id = ".$goods_id." and spec_array = '".$spec_array."'","id as products_id,goods_id,products_no,sell_price,store_nums,market_price");

			//匹配到货品数据
			if(!$procducts_info)
			{
				echo JSON::encode(array('code' => '0','info' => '没有找到相关货品或库存为0'));
				die;
			}
			//获得会员价
			$countsumInstance = new countsum();
			$group_price = $countsumInstance->getGroupPrice($procducts_info['products_id'],'product');

			//会员价格(与销售价相等则不显示）
			if($group_price !== null && floatval($group_price) < $procducts_info['sell_price'])
			{
				$procducts_info['group_price'] = floatval($group_price);
			}
			$procducts_info['code']=1;
			die(JSON::encode($procducts_info));
		}

		public function getProductInfoIos(){
			$goods_id=IFilter::act(IReq::get('goods_id','post'),'int');
			$spec_array=array();

			if(isset($_POST['spec_array'])&&!empty($_POST['spec_array'])){

				foreach($_POST['spec_array']['spec_id'] as $k=>$v){
					$spec_array[$k]['id']=$v;
					$spec_array[$k]['type']=$_POST['spec_array']['type'][$k];
					if($_POST['spec_array']['type'][$k]==2){
						$start=strpos($_POST['spec_array']['values'][$k],'upload');
						$spec_array[$k]['value']=substr($_POST['spec_array']['values'][$k],$start);
					}else{
						$spec_array[$k]['value']=$_POST['spec_array']['values'][$k];
					}
					$spec_array[$k]['name']=$_POST['spec_array']['name'][$k];
				}

			}else{
				die(JSON::encode(['code'=>0,'info'=>'规格值不符合标准']));
			}

			$spec_array=JSON::encode($spec_array);

			//获取货品数据
			$tb_products = new IModel('products');
			$procducts_info = $tb_products->getObj("goods_id = ".$goods_id." and spec_array = '".$spec_array."'","id as products_id,goods_id,products_no,sell_price,store_nums,market_price");

			//匹配到货品数据
			if(!$procducts_info)
			{
				echo JSON::encode(array('code' => '0','info' => '没有找到相关货品或库存为0'));
				die;
			}
			//获得会员价
			$countsumInstance = new countsum();
			$group_price = $countsumInstance->getGroupPrice($procducts_info['products_id'],'product');

			//会员价格(与销售价相等则不显示）
			if($group_price !== null && floatval($group_price) < $procducts_info['sell_price'])
			{
				$procducts_info['group_price'] = floatval($group_price);
			}
			$procducts_info['code']=1;
			die(JSON::encode($procducts_info));
		}
	}
