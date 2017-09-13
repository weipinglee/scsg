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

	public function getSellerPapers($seller_id){
		$query = new IModel('seller_paper');
		$info = $query->query("seller_id=".$seller_id);
		return $info;
	}

	/**
	 * 查找商户列表
	 * @param string $where 筛选条件
	 * @return IQuery
	 */

	public function getSellerList($where='')
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('seller');
		$query->where = $where=='' ? 'is_del = 0' : 'is_del = 0 AND '.$where;
		$query->order = 'id desc';
		$query->page  = $page;
		return $query;
	}
}