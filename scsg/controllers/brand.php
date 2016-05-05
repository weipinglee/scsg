<?php
/**
 * @class Brand
 * @brief 品牌模块
 * @note  后台
 */
class Brand extends IController
{
	public $checkRight  = 'all';
    public $layout='admin';
	private $data = array();

	function init()
	{
		IInterceptor::reg('CheckRights@onCreateAction');
	}

	/**
	 * @brief 品牌分类添加、修改
	 */
	function category_edit()
	{
		$category_id = (int)IReq::get('cid');
		//编辑品牌分类 读取品牌分类信息
		if($category_id)
		{
			$obj_brand_category = new IModel('brand_category');
			$category_info = $obj_brand_category->getObj('id='.$category_id);

			if($category_info)
			{
				$this->catRow = $category_info;
			}
			else
			{
				$this->redirect('category_list');
				Util::showMessage("没有找到相关品牌分类！");
				return;
			}
		}
		$this->redirect('category_edit');
	}

	/**
	 * @brief 保存品牌分类
	 */
	function category_save()
	{
		$id                = IFilter::act(IReq::get('id'),'int');
		$goods_category_id = IFilter::act(IReq::get('goods_category_id'),'int');
		$name              = IFilter::act(IReq::get('name'));

		$category_info = array(
			'name' => $name,
			'goods_category_id' => $goods_category_id
		);
		$tb_brand_category = new IModel('brand_category');
		$tb_brand_category->setData($category_info);

		//更新品牌分类
		if($id)
		{
			$where = "id=".$id;
			$tb_brand_category->update($where);
		}
		//添加品牌分类
		else
		{
			$tb_brand_category->add();
		}
		$this->redirect('category_list');
	}

	/**
	 * @brief 删除品牌分类
	 */
	function category_del()
	{
		$category_id = (int)IReq::get('cid');
		if($category_id)
		{
			$brand_category = new IModel('brand_category');
			$where = "id=".$category_id;
			if($brand_category->del($where))
			{
				$this->redirect('category_list');
			}
			else
			{
				$this->redirect('category_list');
				$msg = "没有找到相关分类记录！";
				Util::showMessage($msg);
			}
		}
		else
		{
			$this->redirect('category_list');
			$msg = "没有找到相关分类记录！";
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 修改品牌
	 */
	function brand_edit()
	{
		$brand_id = (int)IReq::get('bid');
		//编辑品牌 读取品牌信息
		if($brand_id)
		{
			$obj_brand = new IModel('brand');
			$brand_info = $obj_brand->getObj('id='.$brand_id);
			if($brand_info)
			{
				$this->data['brand'] = $brand_info;
			}
			else
			{
				$this->redirect('category_list');
				Util::showMessage("没有找到相关品牌分类！");
				return;
			}
		}

		$this->setRenderData($this->data);
		$this->redirect('brand_edit',false);
	}

	/**
	 * @brief 保存品牌
	 */
	function brand_save()
	{
		$brand_id = IFilter::act(IReq::get('brand_id'),'int');
		$name = IFilter::act(IReq::get('name'));
		$sort = IFilter::act(IReq::get('sort'),'int');
		$url = IFilter::act(IReq::get('url'));
		$category = IFilter::act(IReq::get('category'),'int');
		$description = IFilter::act(IReq::get('description'),'text');

		$tb_brand = new IModel('brand');
		$brand = array(
			'name'=>$name,
			'sort'=>$sort,
			'url'=>$url,
			'description' => $description,
		);

		if($category && is_array($category))
		{
			$categorys = join(',',$category);
			$brand['category_ids'] = $categorys;
		}
		else
		{
			$brand['category_ids'] = '';
		}
		if(isset($_FILES['logo']['name']) && $_FILES['logo']['name']!='')
		{
			$uploadObj = new PhotoUpload();
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();
			if(isset($photoInfo['logo']['img']) && file_exists($photoInfo['logo']['img']))
			{
				$brand['logo'] = $photoInfo['logo']['img'];
			}
		}
		$tb_brand->setData($brand);
		if($brand_id)
		{
			//保存修改分类信息
			$where = "id=".$brand_id;
			$tb_brand->update($where);
		}
		else
		{
			//添加新品牌
			$tb_brand->add();
		}
		$this->brand_list();
	}

	/**
	 * @brief 删除品牌
	 */
	function brand_del()
	{
		$brand_id = (int)IReq::get('bid');
		if($brand_id)
		{
			$tb_brand = new IModel('brand');
			$where = "id=".$brand_id;
			if($tb_brand->del($where))
			{
				$this->brand_list();
			}
			else
			{
				$this->brand_list();
				$msg = "没有找到相关分类记录！";
				Util::showMessage($msg);
			}
		}
		else
		{
			$this->brand_list();
			$msg = "没有找到相关品牌记录！";
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 品牌列表
	 */
	function brand_list()
	{
		//搜索条件
		$search = IFilter::act(IReq::get('search'),'strict');
		$where = array();
		$where_str = '';
		if(isset($search['category_ids']) && $search['category_ids']){
			$where[] = 'find_in_set('.$search["category_ids"].',category_ids)';
		}
		if(isset($search['name']) && $search['name'] && isset($search['keywords']) && $search['keywords']){
			$where[] = $search['name'].' = "'.$search['keywords'].'"';
		}
		
		foreach($where as $k=>$v){
			$where_str .= $v.' and ';
		}
		$where_str .= '1';
		
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$brand_db = new IQuery('brand');
		$brand_db->page   = $page;
		$brand_db->where  = $where_str;
		$this->brand_db = $brand_db;
		$this->search = $search;
		$this->redirect('brand_list');
	}
	/**
	 * @标签管理 列表
	 */
	public function tags_list(){
		$search = IFilter::act(IReq::get('search'),'strict');
		
		$where='';
		if(isset($search['show_index'])&& ($search['show_index']==0 || $search['show_index']==1))
			$where = 'show_index='.$search['show_index'];
		
		$tb_tag = new IModel('commend_tags');
		$this->tags = $tb_tag->query($where);
		$this->redirect('tags_list');
	}
	//标签添加编辑页面
	public function tags_edit(){
		$id = IFilter::act(IReq::get('id'),'int');
		if($id){
			$tb_tag = new IModel('commend_tags');
			$this->tags = $tb_tag->getObj('id='.$id);
			
		}
	
		$this->redirect('tags_edit');
	}
	//标签删除
	public function tags_del(){
		$id = IFilter::act(IReq::get('id'),'int');
		$tb_tag = new IModel('commend_tags');
		$tb_tag->del('id='.$id);
		$this->redirect('tags_list');
		
	}
	//标签保存
	public function tags_save(){
		$tags_id = IFilter::act(IReq::get('tags_id'),'int');
		$name = IFilter::act(IReq::get('name'));
		$sort = IFilter::act(IReq::get('sort'),'int');
		$img = uploadHandle('img');
		$intro = IFilter::act(IReq::get('intro'));
		$is_close = IFilter::act(IReq::get('is_close'),'int');
		$show_index = IFilter::act(IReq::get('show_index'),'int');
		
		$commend = new IModel('commend_tags');
		$tags = array(
			'name'=>$name,
			'sort'=>$sort,
			'is_close'=>$is_close,
			'intro'=>$intro,
			'show_index'=>$show_index
		);
		if($img)$tags['img']=$img;
		$commend->setData($tags);
		if($tags_id){
			$commend->update('id='.$tags_id);
		}else{
			$commend->add();
		}
		$this->redirect('tags_list');
	}
}