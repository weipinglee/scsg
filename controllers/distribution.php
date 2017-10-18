<?php
class distribution extends IController
{
	public $layout='detail';

	function init()
	{

	}
	
	public function password(){
		$this->redirect('password');
	}

	public function doLogin(){
		$pass = IFilter::act(IReq::get('pass'));
		$deliObj = new IModel('deli_person');
		$id = 1;
		$row = $deliObj->getObj('id='.$id);
		if($row['password']==sha1($pass)){
			$_SESSION['delivery'] = $row['username'];
			$_SESSION['delivery_id'] = $row['id'];
			$this->redirect('order');
		}
		$this->redirect('password');
	}

	public function order(){
		if(!isset($_SESSION['delivery']))
			$this->redirect('password');

		$this->redirect('order');
	}

	//接单操作
	public function acc_order(){
		if(!isset($_SESSION['delivery']))
			$this->redirect('password');
		$deliver_id = $_SESSION['delivery_id'];
		$order_id = IFilter::act(IReq::get('id'));
		$model = new IModel('order');
		$model->setData(array('deliver_id'=>$deliver_id));
		$res = $model->update('id='.$order_id.' and deliver_id=0');
		if($res){
			die(json_encode(array('success'=>1)));
		}
		else{
			die(json_encode(array('success'=>0)));
		}
	}

	/**
	 * 获取订单数据
	 *
	 */
	public function get_orderlist(){
		if(!isset($_SESSION['delivery']))
			$this->redirect('password');
		//搜索条件
		$search = IFilter::act(IReq::get('search'));
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;

		$search['distribution_status'] = IReq::get('distribution_status');
		//条件筛选处理
		list($join,$where) = order_class::getSearchCondition($search);
		//$join = $join.' left join refundment_doc as r on r.order_id=o.id and r.if_del=0 ';
		//$join2 = $join.' left join refundment_doc as r on r.order_id=o.id and r.if_del=0 and r.pay_status in (0,3,4,7)';
		//拼接sql

		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,u.username,p.name as payment_name";
		$orderHandle->page   = $page;
		$orderHandle->where  = $where.' and o.type !=4 ';
		$orderHandle->join   = $join;
		$orderHandle->group = 'o.id';
		$this->search      = $search;
		$this->orderHandle = $orderHandle;

		$order_list = $orderHandle->find();

		unset($order_goods_data);
		//print_r($order_list);
		die(JSON::encode($order_list));
	}
}