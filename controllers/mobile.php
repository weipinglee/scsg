<?php
class Mobile extends IController
{
	public $layout='site';

	function init()
	{
		CheckRights::checkUserRights();
	}
	
	function getMoreProlist(){
		$childCat = IFilter::act(IReq::get('childCat'));
		$pagesize = 10;
		$goodsObj = search_goods::find(array('category_extend' => $childCat),$pagesize);
		self::getGoodsList($goodsObj);
	}
	//获取更多搜索产品
	function getMoreSearchList(){
		$pagesize = 3;
		$word = IFilter::act(IReq::get('word'));
		$cat_id = IFilter::act(IReq::get('cat_id'),'int');
		$defaultWhere = array('search' => $word , 'category_extend' => $cat_id );
		$goodsObj = search_goods::find($defaultWhere,$pagesize);
		self::getGoodsList($goodsObj);
		
	}
	
	function getGoodsList($goodsObj){
		$resultData = $goodsObj->find();
		if($goodsObj->page==0){
			echo 0;exit;
		}
		$seller = new IModel('seller');
		$seller_arr = array();
		$seller_arr[0]='平台自营';
		foreach($resultData as $k=>$v){
			if(!isset($seller_arr[$v['seller_id']])){
				$seller_arr[$v['seller_id']]=$seller->getField('id='.$v['seller_id'],'true_name');
			}
		}
		
		echo JSON::encode($resultData);exit;
	}
	
}