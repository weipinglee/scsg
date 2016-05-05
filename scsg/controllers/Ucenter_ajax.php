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
		$type = IReq::get('type') ? IFilter::act(IReq::get('type')) : 'all';
		$query = new IQuery('point_log');
		$where = '';
		if($type=='plus')$where = ' and value > 0';
		else if($type=='minus')$where = ' and value < 0';
		$query->where  = "user_id = ".$userid.$where;
		$query->page   = $page;
		$query->order= "id desc";
		$resData = $query->find();
		if($query->page==0){echo 0;exit;}
		foreach($resData as $k=>$v){
			if($v['value']>0)$resData[$k]['value']='+'.$v['value'];
		}
		echo JSON::encode($resData);
	}
	

	/**
	 * 获取订单数据
	 * 
	 */
	public function get_orderlist(){
		$userid = $this->user['user_id'];
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$status = IReq::get('status')? intval(IReq::get('status')) : null;
		$order_db = new IQuery('order as o');
		$where = '';
		if($status==1){//待付款
			$where .= ' and (o.type!=4 and o.status=1 and o.pay_type!=0  or o.type=4 and o.status in (1,4) )';
			
		}else if($status==2){//待发货
			$where .= ' and (o.type!=4 and o.status=2 and o.distribution_status=0 or o.type=4 and o.status=7)';
		}
		else if($status==3){//待收货
			$where .= ' and (type!=4 and status=9  or type=4 and status=9)';
		}
		else if($status==4){//待评价
			$where .= ' and (type!=4 and status=5 or type=4 and status=11)';
		}
		//$order_db->join = 'left join presell as p on p.'
		$order_db->where = 'user_id='.$userid.' and if_del=0'.$where;
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
		
		$order_goods_db = new IQuery('order_goods as og');
		$order_goods_db->join = ' left join order as o on o.id = og.order_id left join comment as c on og.comment_id=c.id ';
		$order_goods_db->where = 'og.order_id in ('.$ids.')';
		$order_goods_db->fields = 'o.status,og.id,og.goods_id,og.refunds_status,og.order_id,og.goods_nums,og.comment_id,og.img,og.real_price,og.goods_array,og.is_send,og.delivery_id,og.delivery_fee,c.id as cid,c.point,c.status as comment_status';
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
			foreach($order_goods_data as $key=>$val){
				if($val['order_id']==$v['id']){
					$order_goods_data[$key]['can_refund'] = $v['type']!=4 && (Refunds_Class::order_goods_refunds(array_merge($v,$val)) || Refunds_Class::order_goods_chg(array_merge($v,$val))) ? 1 : 0;
					$order_data[$k]['goods_data'][] = $order_goods_data[$key];
					$order_data[$k]['num'] +=$val['goods_nums'];
				}
				
			}
		}
		//print_r($order_data);
		unset($order_goods_data);
		echo JSON::encode($order_data);
	} 
	/**
	 * 获取评论列表
	 */
	public function pingjia_list(){
		$user_id = $this->user['user_id'];
		$status  = IFilter::act(IReq::get('status'),'int');//0:未评价，1：已评价
		$query = Api::run('getUcenterEvaluation',$user_id,$status);
		$pingjia = $query->find();
		
		if($query->page==0){echo 0;exit;}
	
		echo JSON::encode($pingjia);
	}
	/**
	 * 签到送积分
	 */
	public function sign_add_point(){
		$config = new Config('site_config');
		$point = isset($config->sign_point) ? $config->sign_point : 5;
		$user_id = $this->user['user_id'];
		$member_db = new IModel('member');
		$member_db->setData(array('sign_date'=>ITime::getDateTime()));
		if($member_db->update('user_id='.$user_id.' and (sign_date IS NULL  or DATEDIFF(now(),sign_date)>=1)')){
			$pointConfig = array(
					'user_id' => $user_id,
					'point'   => $point,
					'log'     => '签到送'.$point.'积分',
			);
			$pointObj = new Point();
			if($pointObj->update($pointConfig)){
				echo JSON::encode(array('point'=>$point));
			}else echo 2;
		}
		else{
			 echo 0;
		}
		
	}
	
}