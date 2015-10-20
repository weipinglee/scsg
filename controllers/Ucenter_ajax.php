<?php
/**
 * 异步获取用户中心的数据
 * @author lenovo
 *
 */
class Ucenter_ajax extends IController
{
	public $layout = '';

	public function init()
	{
		CheckRights::checkUserRights();

		if(!$this->user)
		{
			$this->redirect('/simple/login');
		}
	}
	/*
	 * @brief 异步获取用户积分记录
	 * 
	 */
	public function get_integral(){
		$userid = $this->user['user_id'];
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('point_log');
		$query->where  = "user_id = ".$userid;
		$query->page   = $page;
		$query->order= "id desc";
		$resData = $query->find();
		echo JSON::encode($resData);
	}
	
	
	
}