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
		$deliver_id = $_SESSION['delivery_id'];
		$deliverObj = new deliver();
		$this->wait_acc_nums = $deliverObj->waiting_acc_nums();
		$this->uncomplate_nums = $deliverObj->uncomplate_deliver_num($deliver_id);

		$this->status = IFilter::act(IReq::get('status','get')) ? IFilter::act(IReq::get('status','get')) : 0;

		$this->redirect('order');
	}

	//接单操作
	public function acc_order(){
		if(!isset($_SESSION['delivery']))
			$this->redirect('password');
		$deliver_id = $_SESSION['delivery_id'];
		$order_id = IFilter::act(IReq::get('id'));
		$status = IFilter::act(IReq::get('status','post'));

		$orderObj = new IModel('order');
		$orderRow = $orderObj->getObj('id='.$order_id);
		if(!empty($orderRow)){
			$deliverObj = new deliver();
			$res = false;
			$orderStatus = Order_Class::getOrderStatus($orderRow);
			if($status==1){
				if($orderStatus==4){
					$res = $deliverObj->deliver_acc($deliver_id,$order_id);
					$orderObj = new IModel('order');
					$orderObj->setData(array('deliver_id'=>$deliver_id));
					$orderObj->update('id='.$order_id);
				}
				else{
					die(json_encode(array('success'=>0,'info'=>'该状态不能接单')));
				}

			}
			else if($status==2){
				if($orderStatus==3||$orderStatus==6){
					$res = $deliverObj->user_acc($deliver_id,$order_id);
				}
				else{
					die(json_encode(array('success'=>0,'info'=>'该状态不允许此操作')));
				}

			}

			if($res){
				die(json_encode(array('success'=>1)));
			}
			else{
				die(json_encode(array('success'=>0,'操作失败')));
			}
		}
		die(json_encode(array('success'=>0,'info'=>'订单不存在')));

	}

	/**
	 *
	 * 获取订单数据
	 */
	public function get_orderlist(){
		if(!isset($_SESSION['delivery']))
			$this->redirect('password');
		$deliver_id = $_SESSION['delivery_id'];
		//搜索条件
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;

		$join = 'left join user as u on u.id = o.user_id left join order_deliver as d on o.id=d.order_id';
		$time = '2017-10-22 ';
		$where = 'o.if_del=0 and o.create_time>"'.$time.'"';
		$status = IFilter::act(IReq::get('status','post'));
		if($status==1){
			$where .= '  and o.deliver_id=0 and o.status=2 and o.distribution_status=0 ';
		}
		else if($status==2){
			$where .= '  and d.status<4 and  d.deliver_id='.$deliver_id;
		}
		else{
			$where .= '  and o.pay_status=1 ';
		}


		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,u.username,d.status as deliver_status,d.acc_time";
		$orderHandle->page   = $page;
		$orderHandle->pagesize = 10;
		$orderHandle->where  = $where.' and o.type !=4 ';
		$orderHandle->join   = $join;

		$this->orderHandle = $orderHandle;

		$order_list = $orderHandle->find();

		if($orderHandle->page==0){
			$order_list = array();
		}

		unset($order_goods_data);
		$sellerObj = new IModel('seller');
		foreach($order_list as $key=>$item){
			if(!$item['seller_id'])
				$item['seller_id'] = 0;
			$sellerData = $sellerObj->getObj('id='.$item['seller_id'],'logo_img,true_name');//print_r($sellerData);
			$order_list[$key]['seller_name'] = isset($sellerData['true_name']) ? $sellerData['true_name'] : '平台';
			$order_list[$key]['seller_img'] = isset($sellerData['logo_img']) ? $sellerData['logo_img'] : '';
			$order_list[$key]['pay_status'] = Order_Class::getOrderPayStatusText($item);
			$order_list[$key]['total_pay'] = $item['real_amount'] + $item['real_freight'] - $item['pro_reduce'];
		}
		//print_r($order_list);
		die(JSON::encode($order_list));
	}

	/**
	 * @brief 订单详情
	 * @return String
	 */
	public function order_detail()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$orderObj = new order_class();
		$this->order_info = $orderObj->getOrderShow($id);
		if($this->order_info['type']==4)$this->redirect('preorder_detail/id/'.$this->order_info['id']);
		$this->fapiao_data = array();
		if($this->order_info['invoice']==1){
			$fapiao_db = new IModel('order_fapiao');
			$this->fapiao_data = $fapiao_db->getObj('order_id='.$id);
		}
		if(!$this->order_info)
		{
			IError::show(403,'订单信息不存在');
		}


		//获取商品信息
		$tb_order_goods = new IQuery('order_goods as og');
		$tb_order_goods->join = 'left join goods as g on og.goods_id=g.id';
		$tb_order_goods->where = 'og.order_id='.$id;
		$tb_order_goods->group = 'og.id';
		$tb_order_goods->fields = 'g.type,g.sell_price,g.point,og.product_id,og.is_send,og.real_price,og.refunds_status,og.id as og_id,og.goods_id,og.img,og.goods_array,og.goods_nums,og.seller_id';
		$og_data = $tb_order_goods->find();
		foreach($og_data as $key=>$val){
			if($val['seller_id'] <> 0)
			{
				$seller_name = API::run('getSellerInfo',$val['seller_id'],'true_name');
				$seller_logo = API::run('getSellerInfo',$val['seller_id'],'logo_img');
				$og_data[$key]['seller_name'] = $seller_name['true_name'];
				$og_data[$key]['seller_logo'] = $seller_logo['logo_img'];
			}
			else
			{
				$og_data[$key]['seller_name'] = '平台自营';
			}
			//判断所买商品是否分规格
			if($val['product_id'])
			{
				$product = new IModel('products');
				$sell_price = $product->getField('id='.$val['product_id'], 'sell_price');
				$point = $product->getField('id='.$val['product_id'], 'point');
				$og_data[$key]['sell_price'] = $sell_price ? $sell_price : 0;
				$og_data[$key]['point'] = $point ? $point : 0;
			}
			else
			{
				$goods = new IModel('goods');
				$sell_price = $goods->getField('id='.$val['goods_id'], 'sell_price');
				$point = $goods->getField('id='.$val['goods_id'], 'point');
				$og_data[$key]['sell_price'] = $sell_price ? $sell_price : 0;
				$og_data[$key]['point'] = $point ? $point : 0;
			}
		}
		$this->og_data = $og_data;
		$this->redirect('order_detail',false);
	}


}