<?php
class user_like{
	
	/**
	 * 通过商品ID获取商品二级分类ID
	 *@$goodId  array 商品id
	 *@return  商品所属最小分类id
	 */
	public static function getSecCategory($goodId){
		$categoryDB = new IQuery('category_extend as ce');
		$categoryDB->join = 'left join category as ca on ce.category_id = ca.id';
		$categoryDB->where = 'ce.goods_id = '.$goodId;
		$categoryDB->fields = 'ce.category_id,ca.parent_id';
		$res = $categoryDB->find();
		$goodsCat=array();
		if(!empty($res)){
			foreach($res as $key=>$val){
				$goodsCat[$val['parent_id']] = $val['category_id'];
			}
			foreach($goodsCat as $k=>$v){
				if(!isset($goodsCat[$v]))
					return $v;
			}
		}
		return 0;
	}
	/**把用户操作的分类写到数据库或者session
	 * @$goodsId array 商品id
	 * @$userid str 登陆用户id
	 */
	public static function add_like_cate($goodsId,$userId){
		$catId = self::getSecCategory($goodsId);
		if($userId){
			$M = new IModel('member');
			$where = 'user_id='.$userId;
			$data = $M->getField($where,'goods_like');
			$data = self::handleData($data,$catId);
			$M->setData(array('goods_like'=>$data));
			$M->update($where);
		}
		else {
			if(!isset($_SESSION['goods_like']))$_SESSION['goods_like']='';
			$_SESSION['goods_like'] = self::handleData($_SESSION['goods_like'],$catId);
		}
	}
	//处理字段数据
	/*
	 * @$old str 以’，’分割 ，
	 * @$catId array or str 
	 * @return array 处理后的数据
	 */
	private static function handleData($data,$catId){
		$typeNum = 1;//存储分类数量
		$data = explode(',',$data);
		if(is_array($catId)){
			foreach($catId as $id){
				if(!in_array($id,$data))
					array_unshift($data,$id);
			}
		} else{
			if(!in_array($catId,$data))array_unshift($data,$catId);
		}
		$data = array_slice($data,0,$typeNum);
		return implode(',',$data);
	}
	/**
	 * 获取用户喜好字段数据str
	 * @$userId 用户登陆Id,未登陆为0
	 */
	
 	private static function getData($userId){
 		if($userId){//已登陆
 			$M = new IModel('member');
 			$where = 'user_id='.$userId;
 			return $M->getField($where,'goods_like');
 				
 		}else {
 			return isset($_SESSION['goods_like']) ? $_SESSION['goods_like'] : '';
 		}
 	}
	/**
	 * 获取用户喜好产品
	 * @$userId 用户登陆Id,未登陆为0
	 * @ array  用户喜好产品列表，不足则添加系统推荐商品
	 */
	public static function get_like_cate($userId,$num=6){
		$data = self::getData($userId);
		$res=array();
		if(!empty($data)){
			$cateDB = new IQuery('category_extend as ca');
			$cateDB->join =  'left join goods as go on ca.goods_id = go.id';
			$cateDB->where = 'go.is_del = 0 and ca.category_id in ('.$data.')';
			$cateDB->fields = 'go.name,go.img,go.id,go.sell_price,go.market_price';
			$cateDB->limit = $num;
			$res = $cateDB->find();
			
		}
		$count=count($res);
		if($count<$num){
			$recomGoods = Api::run('getCommendRecom',$num-$count);
			$res = array_merge($res,$recomGoods);
		}
		return $res;
	}
	
	/**
	 * @记录用户浏览记录同一天同一产品不做重复记录
	 * @
	 */
	public static function set_user_history($goods_id,$user_id=false){
		if(!$user_id){
			ISession::add('user_history',array('goods_id'=>$goods_id,'time'=>ITime::getDateTime('Y-m-d')));
		}else{
			$history = new IQuery('category_extend as ca');
			$time = ITime::getDateTime('Y-m-d');
			$data = array('user_id'=>$user_id,'goods_id'=>$goods_id,'time'=>$time);
			$history->join = 'left join user_history as h on (h.goods_id = ca.goods_id and  h.user_id = '.$user_id. ' and DATEDIFF(NOW(),h.time) < 1)';
			$history->fields = 'h.time,ca.category_id';
			$history->where= ' ca.goods_id = '.$goods_id;
			$history->limit = 1;
			$hisData = $history->find();
			//print_r($hisData);
			if($hisData){//商品有分类
				if(!$hisData[0]['time']){//当日未访问
					$data['cat_id'] = isset($hisData['category_id'])?$hisData['category_id'] : 0;
					$history = new IModel('user_history');
					$history->setData($data);
					$history->add();
				}
				return false;
			}
			$his = new IModel('user_history');
			if(!$his->getObj('goods_id='.$goods_id.' and user_id = '.$user_id.' and DATEDIFF(NOW(),time) < 1','id')){
				$his->setData($data);
				$his->add();
			}
		}
	}
	
	/**
	 * @获取浏览历史登陆和未登陆两种方式
	 * @$user_id 用户id
	 * @return arr 浏览历史数据
	 */
	public static function get_user_history($user_id=false){
		if(!$user_id)
			return ISession::get('user_history');
		else{
			$history = new IQuery('user_history');
			$history->where = 'user_id = '.$user_id;
			$history->fields = 'goods_id,time';
			$history->group = 'goods_id';
			$history->order = 'id DESC';
			$history->limit = 6;
			$res = $history->find();
			if($res)
				return $res;
			return null;
		}
	} 
	//获取隐藏的手机号
	public static function getSecretPhone($phone){
		if($phone)
			return substr_replace($phone,'****',3,4);
		return false;
	}
	
	//获取隐藏的邮箱
	public static function getSecretEmail($email){
		if($email){
			$pos = strpos($email,'@');
			$subPos = $pos>=3 ? 3 : $pos;
			return substr($email,0,$subPos).preg_replace('/[\w.-]*@/','****@',$email);
		}
		return false;
	
	}	
	
}