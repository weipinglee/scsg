<?php
class user_like{
	
	/**
	 * 通过商品ID获取商品二级分类ID
	 *@$goodId  array 商品id
	 *@return $allCat 商品所属的二级分类id
	 */
	public static function getSecCategory($goodId){
		$M = new IModel('category');
		$where = array('goods_id'=>$goodId);
		$catTopStr = '';
		foreach(Api::run('getCategoryListTop') as $v){
			$catTopStr .= $v['id'].',';
		}
		$catTopStr = substr($catTopStr,0,-1);
		$catSec = $M->getFields(array('parent_id'=>$catTopStr),'id');
		$M->changeTable('category_extend');
		$allCat = $M->getFields($where,'category_id');
		foreach($allCat as $key=>$v){
			if(!in_array($v,$catSec))unset($allCat[$key]);
		}
		return $allCat;
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
			//$sql = 'UPDATE '.$M->getTableName().' SET goods_like = CONCAT(goods_like,'.$catId.') WHERE '.$where;
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
	 */
	public static function get_like_cate($userId){
		$data = self::getData($userId);
		$res = $data!='' ? Api::run('getCategoryExtendListByCategoryid',array('#categroy_id#',$data),2,5) : array();
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
			$history = new IModel('user_history');
			$time = ITime::getDateTime('Y-m-d');
			$data = array('user_id'=>$user_id,'goods_id'=>$goods_id,'time'=>$time);
			$res=$history->getObj('user_id = '.$user_id. ' and goods_id = '.$goods_id.' and DATEDIFF(NOW(),time) < 1','id');
			if(!$res){
				$history->setData($data);
				$history->add();
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
			$history = new IModel('user_history');
			if($res = $history->query('user_id = '.$user_id,'goods_id,time','time'))
				return $res;
			return null;
		}
	} 
	
	
}