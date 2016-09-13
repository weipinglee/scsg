<?php

/*
商品数据
 */
class mobileGoods extends IController {

	//获取分类列表
	public function getAllCat() {
		$m_category = new IModel('category');
		$where = array(
			'parent_id' => 0,
		);
		$c_list = $m_category->query();
		$result = $this->getNestedList($c_list);
		//echo JSON::encode($result);
		var_dump($result);
	}
	//获取顶级分类
	public function getCatList() {
		$m_category = new IQuery('category');
		$m_category->where = 'parent_id=0';

		$m_category->order = 'sort asc';

		$cateList = $m_category->find();
		echo JSON::encode($cateList);

	}
	//递归获取所有父类下子分类,树状形的
	public function getTreeList($list, $pid = 0, $deep = 0) {
		static $treeList = array();
		//var_dump($list);
		foreach ($list as $k => $v) {
			if ($v['parent_id'] == $pid) {
				$v['deep'] = $deep;
				$treeList[] = $v;

				$this->getTreeList($list, $v['id'], $deep + 1);
			}

		}
		return $treeList;
	}
	//递归获取所有父类下子分类，嵌套形状的
	public function getNestedList($list, $pid = 0) {
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
	public function getBrandList() {
		$m_brand = new IModel('brand');
		$b_list = $m_brand->query();
		echo JSON::encode($b_list);

	}
	//获得所有商品品牌
	public function getGoodsBrand() {
		//取得品牌id
		$id = IFilter::act(IReq::get('cat_id'));
		$m_brand = new IModel('brand as b');
		/*$m_brand->fileds=''*/
		$result = $m_brand->query('category_ids like "%' . $id . '%"');
		echo JSON::encode($result);
	}
	//获取最新，最热，商品
	public function getHotGoods() {
		$type = IFilter::act(IReq::get('type'));
		//var_dump($type);
		$m_goods = new IModel('goods');
		$where = 'is_del=3';
		$cols = '*';
		switch ($type) {
			case "hot":
				$order = "sale";
				break;
			case "new":
				$order = "up_time";
				break;
			default:
				$order = "";

		}
		$desc = 'DESC';
		$limit = 5;
		$hotList = $m_goods->query($where, $cols, $order, $desc, $limit);
		foreach ($hotList as $k => $v) {
			$hotList[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];
		}

		echo JSON::encode($hotList);
	}
	//首页团购数据
	public function getRegiment() {
		//实例化团购表
		$m_regiment = new IQuery('regiment as r');
		$m_regiment->where = 'is_close = 0 and NOW() between start_time and end_time';
		$m_regiment->order = 'id';
		//排序
		//$order='sort';
		//字段
		$m_regiment->fields = "r.id as goods_id,r.title,r.start_time,r.end_time,r.regiment_price,sell_price,r.img";

		$re_list = $m_regiment->find();
		foreach ($re_list as $k => $v) {
			$re_list[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($re_list);
	}
	//特价商品
	public function getTejia() {
		$m_goods = new IQuery('commend_goods as co');
		$m_goods->fields = 'go.img,go.sell_price,go.name,go.id as goods_id,go.market_price';
		$m_goods->join = 'left join goods as go on co.goods_id = go.id';
		$m_goods->where = 'co.commend_id = 3 and go.is_del = 0 AND go.id is not null';
		$m_goods->order = 'sort asc,goods_id desc';
		//$m_goods->limit = '5';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			//$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];
			  $result[$k]['img']='http://v.yqrtv.com:8080/app/'.$v['img'];
		}
		echo JSON::encode($result);
	}
	public function getTuijian() {
		$commend_id = IFilter::act(IReq::get('commend_id'), 'int') ? IFilter::act(IReq::get('commend_id'), 'int') : '';
		var_dump($commend_id);
		$page = IFilter::act(IReq::get('page'), 'int') ? IFilter::act(IReq::get('page'), 'int') : 1;
		$m_goods = new IQuery('goods as go');
		//默认是特价商品
		$where = 'c.commend_id=2';
		if ($commend_id != '') {
			$where = 'c.commend_id=' . $commend_id;
		}
		$where .= ' and go.is_del=0';
		$m_goods->where = $where;
		$m_goods->fileds = 'go.*';
		$m_goods->join = 'left join commend_goods as c on go.id=c.goods_id';

		$m_goods->page = $page;
		$m_goods->pagesize = 4;
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];
		}

		echo JSON::encode($result);

	}
	//每个分类下面的商品
	public function getCatGoods() {
		$id=IFilter::act(IReq::get('cat_id','get'),'int');
		if(!$id) {
			$id = IFilter::act(IReq::get('cat_id', 'post')) ? IFilter::act(IReq::get('cat_id', 'post')) : 2;
		}
		$page=IFilter::act(IReq::get('page','post')) ? IFilter::act(IReq::get('page','post')) : 1;

		$m_category = new IQuery('category');
		$m_category->fields = 'id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list = $m_category->find();
		$cat_ids = $this->getTreeList($cat_list, $id);
		$all_id = array();
		foreach ($cat_ids as $k => $v) {
			$all_id[] = $v['id'];
		}
		$all_id[] = $id;
		$all_id = implode(',', $all_id);
		//$m_goods->fileds='';

		$m_goods = new IQuery('goods as go');
		$m_goods->distinct='1';
		$m_goods->where = 'c.category_id in (' . $all_id . ') AND (go.is_del=0 or go.is_del=4) ';
		$m_goods->fields='go.id as goods_id,go.name,go.is_del,go.comments,go.grade,go.goods_no,go.sell_price,go.market_price,go.store_nums,go.img,go.sale,go.seller_id,go.sale,go.grade,go.up_time';
		$m_goods->join = 'left join category_extend as c on go.id=c.goods_id';
		$m_goods->page=$page;
		$m_goods->order='go.sort asc';

		$result = $m_goods->find();
	 	$totalPage=$m_goods->paging->getTotalPage();
		if($totalPage<$page){
			die(JSON::encode([]));
		}
		foreach ($result as $k => $v) {
			$result[$k]['up_time']=strtotime($v['up_time']);
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($result);

	}

	/**
	 *粮油分类下面的数据
     */
	public function getLiangyouList(){
		$id = 47;
		$m_category = new IQuery('category');
		$m_category->fields = 'id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list = $m_category->find();
		$cat_ids = $this->getTreeList($cat_list, $id);
		$all_id = array();
		foreach ($cat_ids as $k => $v) {
			$all_id[] = $v['id'];
		}
		$all_id[] = $id;
		$all_id = implode(',', $all_id);
		//$m_goods->fileds='';

		$m_goods = new IQuery('goods as go');
		$m_goods->distinct='1';

		$m_goods->where = 'c.category_id in (' . $all_id . ') AND (go.is_del=0 or go.is_del=4) ';
		$m_goods->fields='go.id as goods_id,go.sale,go.sell_price,go.img,go.name';
		$m_goods->join = 'left join category_extend as c on go.id=c.goods_id';
		$m_goods->order='go.sort asc';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($result);

	}

	/**
	 *日化清洁下面的数据
     */
	public function getRihuaList(){
		$id = 6;
		$m_category = new IQuery('category');
		$m_category->fields = 'id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list = $m_category->find();
		$cat_ids = $this->getTreeList($cat_list, $id);
		$all_id = array();
		foreach ($cat_ids as $k => $v) {
			$all_id[] = $v['id'];
		}
		$all_id[] = $id;
		$all_id = implode(',', $all_id);
		//$m_goods->fileds='';

		$m_goods = new IQuery('goods as go');
		$m_goods->distinct='1';

		$m_goods->where = 'c.category_id in (' . $all_id . ') AND (go.is_del=0 or go.is_del=4) ';
		$m_goods->fields='go.id as goods_id,go.sale,go.sell_price,go.img,go.name';
		$m_goods->join = 'left join category_extend as c on go.id=c.goods_id';
		$m_goods->order='go.sort asc';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($result);

	}

	/**
	 *个人护理分类下面的数据
     */
	public function getHuliList(){
		$id = 23;
		$m_category = new IQuery('category');
		$m_category->fields = 'id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list = $m_category->find();
		$cat_ids = $this->getTreeList($cat_list, $id);
		$all_id = array();
		foreach ($cat_ids as $k => $v) {
			$all_id[] = $v['id'];
		}
		$all_id[] = $id;
		$all_id = implode(',', $all_id);
		//$m_goods->fileds='';

		$m_goods = new IQuery('goods as go');
		$m_goods->distinct='1';

		$m_goods->where = 'c.category_id in (' . $all_id . ') AND (go.is_del=0 or go.is_del=4) ';
		$m_goods->fields='go.id as goods_id,go.sale,go.sell_price,go.img,go.name';
		$m_goods->join = 'left join category_extend as c on go.id=c.goods_id';
		$m_goods->order='go.sort asc';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($result);

	}

	/**
	 *本地农产品分类数据
     */
	public function getBendiList(){
		$id =2;
		$m_category = new IQuery('category');
		$m_category->fields = 'id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list = $m_category->find();
		$cat_ids = $this->getTreeList($cat_list, $id);
		$all_id = array();
		foreach ($cat_ids as $k => $v) {
			$all_id[] = $v['id'];
		}
		$all_id[] = $id;
		$all_id = implode(',', $all_id);
		//$m_goods->fileds='';

		$m_goods = new IQuery('goods as go');
		$m_goods->distinct='1';

		$m_goods->where = 'c.category_id in (' . $all_id . ') AND (go.is_del=0 or go.is_del=4) ';
		$m_goods->fields='go.id as goods_id,go.sale,go.sell_price,go.img,go.name';
		$m_goods->join = 'left join category_extend as c on go.id=c.goods_id';
		$m_goods->order='go.sort asc';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($result);

	}

	/**
	 *食品饮料分类数据
     */
	public function getShipinList(){
		$id = 20;
		$m_category = new IQuery('category');
		$m_category->fields = 'id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list = $m_category->find();
		$cat_ids = $this->getTreeList($cat_list, $id);
		$all_id = array();
		foreach ($cat_ids as $k => $v) {
			$all_id[] = $v['id'];
		}
		$all_id[] = $id;
		$all_id = implode(',', $all_id);
		//$m_goods->fileds='';

		$m_goods = new IQuery('goods as go');
		$m_goods->distinct='1';

		$m_goods->where = 'c.category_id in (' . $all_id . ') AND (go.is_del=0 or go.is_del=4) ';
		$m_goods->fields='go.id as goods_id,go.sale,go.sell_price,go.img,go.name';
		$m_goods->join = 'left join category_extend as c on go.id=c.goods_id';
		$m_goods->order='go.sort asc';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($result);

	}

	/**
	 *养生臻品分类数据
     */
	public function getYangshengList(){
		$id =16;
		$m_category = new IQuery('category');
		$m_category->fields = 'id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list = $m_category->find();
		$cat_ids = $this->getTreeList($cat_list, $id);
		$all_id = array();
		foreach ($cat_ids as $k => $v) {
			$all_id[] = $v['id'];
		}
		$all_id[] = $id;
		$all_id = implode(',', $all_id);
		//$m_goods->fileds='';

		$m_goods = new IQuery('goods as go');
		$m_goods->distinct='1';

		$m_goods->where = 'c.category_id in (' . $all_id . ') AND (go.is_del=0 or go.is_del=4) ';
		$m_goods->fields='go.id as goods_id,go.sale,go.sell_price,go.img,go.name';
		$m_goods->join = 'left join category_extend as c on go.id=c.goods_id';
		$m_goods->order='go.sort asc';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($result);

	}

	/**
	 *海外潮货
     */
	public function getHaiwaiList(){
		$id = 46;
		$m_category = new IQuery('category');
		$m_category->fields = 'id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list = $m_category->find();
		$cat_ids = $this->getTreeList($cat_list, $id);
		$all_id = array();
		foreach ($cat_ids as $k => $v) {
			$all_id[] = $v['id'];
		}
		$all_id[] = $id;
		$all_id = implode(',', $all_id);
		//$m_goods->fileds='';

		$m_goods = new IQuery('goods as go');
		$m_goods->distinct='1';

		$m_goods->where = 'c.category_id in (' . $all_id . ') AND (go.is_del=0 or go.is_del=4) ';
		$m_goods->fields='go.id as goods_id,go.sale,go.sell_price,go.img,go.name';
		$m_goods->join = 'left join category_extend as c on go.id=c.goods_id';
		$m_goods->order='go.sort asc';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($result);

	}

	/**
	 *数码家电分类数据
     */
	public function getShumaList(){
		$id =370;
		$m_category = new IQuery('category');
		$m_category->fields = 'id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list = $m_category->find();
		$cat_ids = $this->getTreeList($cat_list, $id);
		$all_id = array();
		foreach ($cat_ids as $k => $v) {
			$all_id[] = $v['id'];
		}
		$all_id[] = $id;
		$all_id = implode(',', $all_id);
		//$m_goods->fileds='';

		$m_goods = new IQuery('goods as go');
		$m_goods->distinct='1';

		$m_goods->where = 'c.category_id in (' . $all_id . ') AND (go.is_del=0 or go.is_del=4) ';
		$m_goods->fields='go.id as goods_id,go.sale,go.sell_price,go.img,go.name';
		$m_goods->join = 'left join category_extend as c on go.id=c.goods_id';
		$m_goods->order='go.sort asc';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($result);

	}

	/**
	 *获取乡村旅游的数据
     */
	public function getLvyouList(){
		$id = 371;
		$m_category = new IQuery('category');
		$m_category->fields = 'id,parent_id';
		//$m_goods->where='id='.$id;
		$cat_list = $m_category->find();
		$cat_ids = $this->getTreeList($cat_list, $id);
		$all_id = array();
		foreach ($cat_ids as $k => $v) {
			$all_id[] = $v['id'];
		}
		$all_id[] = $id;
		$all_id = implode(',', $all_id);
		//$m_goods->fileds='';

		$m_goods = new IQuery('goods as go');
		$m_goods->distinct='1';

		$m_goods->where = 'c.category_id in (' . $all_id . ') AND (go.is_del=0 or go.is_del=4) ';
		$m_goods->fields='go.id as goods_id,go.sale,go.sell_price,go.img,go.name';
		$m_goods->join = 'left join category_extend as c on go.id=c.goods_id';
		$m_goods->order='go.sort asc';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

		}
		echo JSON::encode($result);

	}
	//获取首页图片的信息
	public function getIndexSlide() {
		$siteConfigObj = new Config("site_config");
		$site_config = $siteConfigObj->getInfo();
		$index_slide = isset($site_config['index_slide']) ? unserialize($site_config['index_slide']) : array();
		$tem = array();
		foreach ($index_slide as $k => $v) {
			$tem[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];
		}
		echo JSON::encode($tem);

	}
	//获取猜你喜欢的商品信息
	public function getUserLike() {
		//$user_id = IFilter::act(IReq::get('user_id')) ? IFilter::act(IReq::get('user_id')) : 0;
		$token=IFilter::act(IReq::get('token','post'));
		if($token){
			$tokenObj=new IModel('token');
			$user_id=$tokenObj->getObj('token=\''.$token.'\'','user_id');
			$likeList=user_like::get_like_cate($user_id,6);
		}else{
			$likeList=user_like::get_like_cate(0,6);
		}
		foreach($likeList as $k=>$v){
			$likeList[$k]['goods_id']=$v['id'];
			unset($likeList[$k]['id']);
			$likeList[$k]['img']='http://v.yqrtv.com:8080/app/'.$v['img'];
		}
		die(JSON::encode($likeList));
	}
	//获得团购列表(今日团，品牌团，整点团 )
	public function getRegimentList(){
		$topList=array();
		$brandList=array();
		$onTimeList=array();
		$tuan=new IQuery('regiment as r');
		//Top
		$tuan->join='left join goods as g on r.goods_id=g.id';
		$tuan->fields='r.*';
		$tuan->where='r.is_close=0 and now() between r.start_time and r.end_time and g.is_del=0 and r.type=0';
		$tuan->order='r.sort desc';
		//$tuan->limit='';
		$topList['data']=$tuan->find();

		if($topList['data']) {
			foreach ($topList['data'] as $k => $v) {
				$data = Comment_Class::get_comment_info($v['goods_id']);
				$topList['data'][$k]['comment_num']=$data['comment_total'];
				$topList['data'][$k]['comment_tate']=$data['comment_total']?(round($data['point_grade']['good']/$data['comment_total'],4))*100:0;
				$topList['data'][$k]['img']='http://v.yqrtv.com:8080/app/'.$v['img'];
			}
		}
		$topList['name']='今日团';
		$topList['time']='';
		//品牌团
		$tuan->join='left join goods as g on r.goods_id=g.id';
		$tuan->fields="r.*";
		$tuan->where='r.is_close=0 and now() between r.start_time and r.end_time and g.is_del=0 and r.type=1';
		$tuan->order='r.sort desc';
		$brandList['data']=$tuan->find();
		if($brandList['data']) {
			foreach ($brandList['data'] as $k => $v) {
				$data = Comment_Class::get_comment_info($v['goods_id']);
				$brandList['data'][$k]['comment_num']=$data['comment_total'];
				$brandList['data'][$k]['comment_tate']=$data['comment_total']?(round($data['point_grade']['good']/$data['comment_total'],4))*100:0;
				$brandList['data'][$k]['img']='http://v.yqrtv.com:8080/app/'.$v['img'];
			}
		}
		$brandList['time']='';
		$brandList['name']='品牌团';
		//整点团
		$tuan->where='r.is_close=0 and now() between r.start_time and end_time and g.is_del=0 and r.type=2';
		$onTimeList['data']=$tuan->find();
		$onTimeList['time']='';
		if($onTimeList['data']){
			foreach($onTimeList['data'] as $k=>$v){
				$data=Comment_Class::get_comment_info($v['goods_id']);
				$onTimeList['data'][$k]['comment_num']=$data['comment_total'];
				$onTimeList['data'][$k]['comment_tate']=$data['comment_total']?(round($data['point_grade']['good']/$data['comment_total'],4))*100:0;
				$onTimeList['time']=$v['start_time'];
				$onTimeList['data'][$k]['img']='http://v.yqrtv.com:8080/app/'.$v['img'];
			}
		}
		$onTimeList['name']='整点团';
		$res=[$topList,$brandList,$onTimeList];
		echo JSON::encode($res);
	}

}

?>