<?php

/*
首页数据
author:wangzhande
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
		echo JSON::encode($result);
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
		$m_goods = new IQuery('goods as go');
		$m_goods->fields = 'go.img,go.id,.go.name';
		$m_goods->join = 'left join commend_goods  as c on go.id=c.goods_id';
		$m_goods->where = 'c.commend_id=3';
		$m_goods->order = 'sort DESC';
		$m_goods->limit = '5';
		$result = $m_goods->find();
		foreach ($result as $k => $v) {
			$result[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];

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
		$id = IFilter::act(IReq::get('cat_id')) ? IFilter::act(IReq::get('cat_id')) : 2;
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
		
		foreach ($index_slide as $k => $v) {
			$index_slide[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];
		}
		echo JSON::encode($index_slide);

	}
	//获取猜你喜欢的商品信息
	public function getUserLike() {
		$user_id = IFilter::act(IReq::get('user_id')) ? IFilter::act(IReq::get('user_id')) : 0;
		$likeList = user_like::get_like_cate($user_id, 6);
		echo JSON::encode($likeList);

	}
	public function goodsSearch() {
		$search_list = search_goods::find()->find();

	}

}

?>