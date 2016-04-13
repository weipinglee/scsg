<?php 
class mobileGoods extends IController{

	//获取分类列表
	public function getCategoryList(){
		$m_category=new IModel('category');
		$where=array(
			'parent_id'=>0
		);
		$c_list=$m_category->query();
		
		//$result=$this->getTreeList($c_list);
		$result=$this->getNestedList($c_list);
		
		echo JSON::encode($result);
		
	}
	//递归获取所有父类下子分类,树状形的
	public function getTreeList($list,$pid=0,$deep=0){
		static $treeList=array();
		//var_dump($list);
		foreach($list as $k=>$v){
			if($v['parent_id']==$pid){
			$v['deep']=$deep;
				$treeList[]=$v;
		
			$this->getTreeList($list,$v['id'],$deep+1);
			}

		}
		return $treeList;
	}
	//递归获取所有父类下子分类，嵌套形状的
	public function getNestedList($list,$pid=0){
		$arr = array();
		 $tem = array();
	
 		foreach ($list as $v) {
 			 if ($v['parent_id'] == $pid) {
 			
   			$tem = $this->getNestedList($list, $v['id']);
                        		//判断是否存在子数组
   			 $v['nested'] = $tem;
  			 $arr[] = $v;
 		 }
 		}
 		return $arr;


	}

	//获取所有品牌
	public function getBrandList(){
		$m_brand=new IModel('brand_category');
		$b_list=$m_brand->query();
		echo JSON::encode($b_list);


	}
	//获得所有商品品牌
	public function getGoodsBrand(){
		//取得品牌id
		$id=IFilter::act(IReq::get('id'));
		$m_brand=new IModel('brand');
		$result=$m_brand->query('category_ids like "%'.$id.'%"');
		
		echo JSON::encode($result);
		
	}
	//获取最新，最热，商品
	public function getHotGoods(){
		$type=IFilter::act(IReq::get('type'));
		//var_dump($type);
		$m_goods=new IModel('goods');
		$where='is_del=3';
		$cols='*';
		switch($type){
			case "hot":
			$order="sale";
			break;
			case "new":
			$order="up_time";
			break;
			default :
			$order="";

		}
		
		$desc='DESC';
		$limit=5;
		$hotList=$m_goods->query($where,$cols,$order,$desc,$limit);
		/*foreach($hotList as $k=>$v){
			$v['content']=IFilter::string($v['content']);

		}*/
		echo JSON::encode($hotList);
	}

	//团购数据
	public function getRegiment(){
		//实例化团购表
		$m_regiment=new IModel('regiment');
		/*$where=array(
			'is_close'=>0
		);*/
		$where= 'is_close = 0 and NOW() between start_time and end_time';
		$order= 'id';
		//排序
		//$order='sort';
		//字段
		$cols='*';

		$re_list=$m_regiment->query($where,$cols,$order);
		
		//$result=$this->getTreeList($c_list);
		echo JSON::encode($re_list);
	}

	public function getTuijian(){
		$commend_id=IFilter::act(IReq::get('commend_id'))?IFilter::act(IReq::get('commend_id')):'';
		$page=IFilter::act(IReq::get('page')) ? IFilter::act(IReq::get('page')):1;

		$m_goods=new IQuery('goods as go');
		//默认为2是特价的
		$where='c.commend_id=2';
		$m_goods->where=$where;
		if($commend_id!=''){
			switch ($commend_id) {
				case '3':
					$where='c.commend_id=3';
					break;
				
				case '1':
					$where='c.commend_id=1';
					break;
				case '2':
					$where='c.commend_id=2';
					break;
				case '4':
					$where='c.commend_id=4';
					break;	
			}
			
		}
		$where.=' and go.is_del=0';
		$m_goods->where=$where;
		$m_goods->fileds='go.*';
		$m_goods->join='left join commend_goods as c on go.id=c.goods_id';
		
		$m_goods->page=$page;
		$m_goods->pagesize=4;
		$result=$m_goods->find();
		echo JSON::encode($result);
		
	}
	//每个分类下面的商品
	public function getCatGoods(){
		$id=IFilter::act(IReq::get('cat_id')) ? IFilter::act(IReq::get('cat_id')):2;
		$m_category=new IQuery('category');
		$m_category->fields='id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list=$m_category->find();
		$cat_ids=$this->getTreeList($cat_list,$id);
		$all_id=array();
		foreach($cat_ids as $k=>$v){
			$all_id[]=$v['id'];
		}
		$all_id[]=$id;
		$all_id=implode(',',$all_id);
		//$m_goods->fileds='';
		$m_goods=new IQuery('goods as go');
		$m_goods->where='c.category_id in ('.$all_id.')';
		$m_goods->join='left join category_extend as c on go.id=c.goods_id';
		$result=$m_goods->find();
		echo JSON::encode($result);		

	}
	//获取首页图片的信息
	public function getIndexSlide(){
		$siteConfigObj = new Config("site_config");
		$site_config   = $siteConfigObj->getInfo();
		$index_slide = isset($site_config['index_slide'])? unserialize($site_config['index_slide']) :array();
		echo JSON::encode($index_slide);

	}


}








?>