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
			$this->redirect('order');
		}
		$this->redirect('password');
	}

	public function order(){
		if(!isset($_SESSION['delivery']))
			$this->redirect('password');

		$this->redirect('order');
	}

	/**
	 * 获取订单数据
	 *
	 */
	public function get_orderlist(){
		if(!isset($_SESSION['delivery']))
			$this->redirect('password');
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$status = IReq::get('status')? intval(IReq::get('status')) : null;
		$status = 3;
		$order_db = new IQuery('order as o');
		$where = '';
		if($status==1){//待付款
			$where .= ' and ((o.type!=4 and o.status=1)  or (o.type=4 and o.status in (1,4) )) and o.pay_type!=0';

		}else if($status==2){//待发货
			$where .= ' and ((o.type!=4 and o.status=2) or (o.type=4 and o.status=7)) and o.distribution_status=0';
		}
		else if($status==3){//待收货
			$where .= ' and ((type!=4 and status=9)  or (type=4 and status=9))';
		}
		else if($status==4){//待评价
			$where .= ' and ((type!=4 and status=5) or (type=4 and status=11))';
		}
		else if($status==5){//退换货
			$where .= ' and type!=4 and o.status in (6,7,8) ';
		}
		//$order_db->join = 'left join presell as p on p.'
		$order_db->where = ' if_del=0'.$where;
		$order_db->fields = '*';
		$order_db->order ='id DESC';
		$order_db->page = $page;
		$order_data = $order_db->find();
		if($order_db->page==0 || empty($order_data)){echo 0;exit;}
		$ids = '';
		foreach($order_data as $k=>$v){
			$ids .=$v['id'].',';
		}
		$ids = substr($ids,0,-1);

		$_where = '';
		if($status==4){//待评价
			$_where .= ' and c.status = 0';
		}

		$order_goods_db = new IQuery('order_goods as og');
		$order_goods_db->join = ' left join order as o on o.id = og.order_id left join comment as c on og.comment_id=c.id ';
		$order_goods_db->where = 'og.order_id in ('.$ids.')'.$_where;
		$order_goods_db->fields = 'o.status,og.id,og.goods_id,og.seller_id,og.refunds_status,og.order_id,og.goods_nums,og.comment_id,og.img,og.real_price,og.goods_array,og.is_send,og.delivery_id,og.delivery_fee,c.id as cid,c.point,c.status as comment_status';
		$order_goods_data = $order_goods_db->find();

		foreach($order_goods_data as $k=>$v){
			$tem = JSON::decode($v['goods_array']);
			$order_goods_data[$k]['spec'] = $tem['value'];
			$order_goods_data[$k]['name'] = $tem['name'];
			$order_goods_data[$k]['og_status'] = Order_Class::get_order_good_status($v);
		}
		foreach($order_data as $k=>$v){
			if($v['type']!='4'){
				$order_data[$k]['order_status_no'] = Order_Class::getOrderStatus($order_data[$k]);
				$order_data[$k]['order_status'] = Order_Class::orderStatusText($order_data[$k]['order_status_no']);
				$order_data[$k]['can_pay'] = ($v['pay_type']!=0 && $v['status']==1) ? 1 : 0;
			}else{
				$order_data[$k]['order_status_no'] = $v['status'];
				$order_data[$k]['order_status'] = Preorder_Class::getOrderStatus($order_data[$k]);
				$order_data[$k]['can_pay'] = Preorder_Class::can_pay($order_data[$k])? 1:0;
			}

			$order_data[$k]['num']=0;
			if($v['seller_id'])
			{
				$seller_name = API::run('getSellerInfo',$v['seller_id'],'true_name');
				$seller_logo = API::run('getSellerInfo',$v['seller_id'],'logo_img');
				$order_data[$k]['seller_name'] = $seller_name['true_name'];
				$order_data[$k]['seller_logo'] = $seller_logo['logo_img'];
			}
			else
			{
				$order_data[$k]['seller_name'] = '平台自营';
			}
			foreach($order_goods_data as $key=>$val){
				if($val['order_id']==$v['id']){
					$order_goods_data[$key]['can_refund'] = $v['type']!=4 && (Refunds_Class::order_goods_refunds(array_merge($v,$val)) || Refunds_Class::order_goods_chg(array_merge($v,$val))) ? 1 : 0;
					$order_data[$k]['goods_data'][] = $order_goods_data[$key];
					$order_data[$k]['num'] +=$val['goods_nums'];
				}

			}
			if(empty($order_data[$k]['goods_data']))
			{
				unset($order_data[$k]);
			}
		}
		unset($order_goods_data);
		echo JSON::encode($order_data);
	}
}