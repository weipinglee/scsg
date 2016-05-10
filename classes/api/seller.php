<?php
/**
 * @file seller.php
 * @brief 商家API
 * @date 2014/10/12 13:59:44
 * @version 2.7
 */
class APISeller
{
	//商户信息
	public function getSellerInfo($id,$cols='*')
	{
		$query = new IModel('seller');
		$info  = $query->getObj("id=".$id,$cols);
		return $info;
	}

	//获取商户列表
	public function getSellerList()
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('seller');
		$query->where = 'is_del = 0';
		$query->order = 'id desc';
		$query->page  = $page;
		return $query;
	}
}