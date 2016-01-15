<?php
class Preorder extends IController
{
	public $checkRight  = 'all';
	public $layout='admin';
	function init()
	{
		IInterceptor::reg('CheckRights@onCreateAction');
	}
	/**
	 * @brief查看订单
	 */
	public function preorder_show()
	{
		//获得post传来的值
		$order_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($order_id)
		{
			$order_show = new Preorder_Class();
			$data = $order_show->getOrderShow($order_id);
			if($data)
			{
				$this->result = '';
					$rule = new ProRule($data['real_amount']+$data['pro_reduce']);
					$this->result = $rule->getInfo();
				
				//获取地区
				$data['area_addr'] = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']));
	
				$this->setRenderData($data);
				$this->redirect('preorder_show',false);
			}
		}
		
		if(!$data)
		{
			$this->redirect('order_list');
		}
	}
	/**
	 * @brief 保存订单备注
	 */
	public function order_note()
	{
	 	//获得post数据
	 	$order_id = IFilter::act(IReq::get('order_id'),'int');
	 	$note = IFilter::act(IReq::get('note'),'text');

	 	//获得order的表对象
	 	$tb_order =  new IModel('order');
	 	$tb_order->setData(array(
	 		'note'=>$note
	 	));
	 	$tb_order->update('id='.$order_id);
	 	IReq::set('id',$order_id);
	 	$this->redirect('preorder_show/id/'.$order_id);

	}
	/**
	 * @brief 支付订单页面collection_doc
	 */
	public function order_collection()
	{
		//去掉左侧菜单和上部导航
		$this->layout='';
		$order_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($order_id)
		{
			$order_show = new Preorder_Class();
			$data = $order_show->getOrderShow($order_id);
		}
		$this->setRenderData($data);
		$this->redirect('order_collection');
	}
	/**
	 * @brief 退款单页面
	 */
	public function order_refundment()
	{
		//去掉左侧菜单和上部导航
		$this->layout='';
		$orderId   = IFilter::act(IReq::get('id'),'int');
		$refundsId = IFilter::act(IReq::get('refunds_id'),'int');
	
		if($orderId)
		{
			$orderDB = new Preorder_Class();
			$data    = $orderDB->getOrderShow($orderId);
	
			
			if($data['pay_status']==1)$data['refunds_amount']=$data['pre_amount'];
			else if($data['pay_status']==2 && $data['distribution_status']==0)$data['refunds_amount']=$data['order_amount'];
			else if($data['distribution_status']==1)$data['refunds_amount']=$data['real_amount'];
			else $data['refunds_amount'] = 0;
			$this->setRenderData($data);
			
			$this->redirect('order_refundment');
			exit;
		}
		die('订单数据不存在');
	}
	/**
	 * 确认订单页面
	 */
	public function order_makesure(){
		$this->layout='';
		$orderId   = IFilter::act(IReq::get('id'),'int');
		if($orderId)
		{
			$orderDB = new Preorder_Class();
			$data    = $orderDB->getOrderShow($orderId);
			$data['status'] = Preorder_Class::getOrderStatus($data);
			$this->setRenderData($data);
			$this->redirect('order_makesure');
			exit;
		}
		die('订单数据不存在');
	}
	/**
	 * 订单确认处理
	 */
	public function order_makesure_doc(){
		$preorder_db = new IModel('order');
		$order_id   = IFilter::act(IReq::get('id'),'int');
		$order_no = IFilter::act(IReq::get('order_no'));
		$user_id  = IFilter::act(IReq::get('user_id'),'int');
		$sure      = IFilter::act(IReq::get('sure'),'int');
		$goods_id  = IFilter::act(IReq::get('goods_id'),'int');
		if($sure==1){//确认通过
			$status=4;
			$orderData = $preorder_db->getObj('id='.$order_id,'pre_amount,order_amount');
			if($orderData['pre_amount']==$orderData['order_amount'])$status=7;
		}else $status=6;
		
		$preorder_db->setData(array('status'=>$status,'confirm_time'=>ITime::getDateTime()));
		if($preorder_db->update('id='.$order_id.' and status=3')){
			if($status==6){//确认不通过，退款
				if(!$user_id)
				{
					die('<script text="text/javascript">parent.actionCallback("游客无法退款");</script>');
				}
				$amount = $preorder_db->getField('id='.$order_id,'pre_amount');
				$updateData = array(
						'order_no'     => $order_no,
						'order_id'     => $order_id,
						'admin_id'     => $this->admin['admin_id'],
						'pay_status'   => 0,
						'time' => ITime::getDateTime(),
						'amount'       => $amount,
						'user_id'      => $user_id,
				);
				
				Preorder_Class::refundHandle(0,$goods_id,$updateData);
			}
			die('<script text="text/javascript">parent.actionCallback();</script>');
		}else die('<script text="text/javascript">parent.actionCallback("确认失败");</script>');
	}
	/**
	 * @brief 发货订单页面
	 */
	public function order_deliver()
	{
		//去掉左侧菜单和上部导航
		$this->layout='';
		$order_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($order_id)
		{
			$order_show = new Preorder_Class();
			$data = $order_show->getOrderShow($order_id);
		}
		$this->setRenderData($data);
		$this->redirect('order_deliver');
	}
	/**
	 * @brief 发货操作
	 */
	public function order_delivery_doc()
	{
		//获得post变量参数
		$order_id = IFilter::act(IReq::get('id'),'int');
	
		//发送的商品关联
		$sendgoods = IFilter::act(IReq::get('sendgoods'));
	
		if(!$sendgoods)
		{
			die('<script type="text/javascript">parent.actionCallback("请选择要发货的商品");</script>');
		}
	
		Preorder_Class::sendDeliveryGoods($order_id,$sendgoods,$this->admin['admin_id']);
	
		die('<script type="text/javascript">parent.actionCallback();</script>');
	}
	/**
	 * @brief 保存退款单页面
	 */
	public function order_refundment_doc()
	{
		$refunds_id = IFilter::act(IReq::get('refunds_id'),'int');
		$order_id = IFilter::act(IReq::get('id'),'int');
		$order_no = IFilter::act(IReq::get('order_no'));
		$user_id  = IFilter::act(IReq::get('user_id'),'int');
		$amount   = IFilter::act(IReq::get('amount'),'float'); //要退款的金额
		$order_goods_id = IFilter::act(IReq::get('order_goods_id'),'int'); //要退款的商品,如果是用户已经提交的退款申请此数据为NULL,需要获取出来
	
		if(!$user_id)
		{
			die('<script text="text/javascript">parent.actionCallback("游客无法退款");</script>');
		}
		$updateData = array(
				'order_no'     => $order_no,
				'order_id'     => $order_id,
				'admin_id'     => $this->admin['admin_id'],
				'pay_status'   => 0,
				'time' => ITime::getDateTime(),
				'amount'       => $amount,
				'user_id'      => $user_id,
		);
		$res = Preorder_Class::refundHandle($refunds_id,$order_goods_id,$updateData);
		if($res){
			die('<script text="text/javascript">parent.actionCallback();</script>');
		}else{
			die('<script text="text/javascript">parent.actionCallback("退货错误");</script>');
		}
	}

	/**
	 * @brief 预售订单列表
	 */
	public function preorder_list()
	{
		//搜索条件
		$search = IFilter::act(IReq::get('search'),'strict');
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		//条件筛选处理
		list($join,$where) = order_class::getSearchCondition($search);
		//拼接sql
		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,d.name as distribute_name,u.username,p.name as payment_name";
		$orderHandle->page   = $page;
		$orderHandle->where  = $where.' and o.type=4';
		$orderHandle->join   = $join;
	
		$this->search      = $search;
		$this->orderHandle = $orderHandle;
	
		$this->redirect("preorder_list");
	}
	/**
	 * @brief 预售订单删除功能_删除到回收站
	 */
	public function preorder_del()
	{
		//post数据
		$id = IFilter::act(IReq::get('id'),'int');
	
		//生成order对象
		$tb_order = new IModel('order');
		$tb_order->setData(array('if_del'=>1));
		if($id)
		{
			$id = $tb_order->update(Util::joinStr($id));
	
			//获取订单编号
			$orderRs   = $tb_order->query('id in ('.$id.')','order_no');
			$orderData = array();
			foreach($orderRs as $val)
			{
				$orderData[] = $val['order_no'];
			}
	
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"预售订单移除到回收站内",'订单号：'.join(',',$orderData)));
	
			$this->redirect('preorder_list');
		}
		else
		{
			$this->redirect('preorder_list',false);
			Util::showMessage('请选择要删除的数据');
		}
	}
	/**
	 * @brief 订单删除功能_删除回收站中的数据，彻底删除
	 */
	public function order_recycle_del()
	{
		//post数据
		$id = IFilter::act(IReq::get('id'),'int');
	
		//生成order对象
		$tb_order = new IModel('order');
	
		if($id)
		{
			$id = is_array($id) ? join(',',$id) : $id;
	
			Order_class::resetOrderProp($id);
	
			//删除订单
			$tb_order->del('id in ('.$id.')');
	
			//记录日志
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"删除回收站中预售订单",'订单ID：'.$id));
	
			$this->redirect('preorder_recycle_list');
		}
		else
		{
			$this->redirect('preorder_recycle_list',false);
			Util::showMessage('请选择要删除的数据');
		}
	}
	/**
	 * @brief 还原订单列表
	 */
	public function order_recycle_restore()
	{
		//post数据
		$id = IFilter::act(IReq::get('id'),'int');
		//生成order对象
		$tb_order = new IModel('order');
		$tb_order->setData(array('if_del'=>0));
		if(!empty($id))
		{
			$tb_order->update(Util::joinStr($id));
			$this->redirect('preorder_recycle_list');
		}
		else
		{
			$this->redirect('preorder_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
	}
	//订单导出excel 参考订单列表
	public function preorder_report()
	{
		//搜索条件
		$search = IFilter::act(IReq::get('search'),'strict');
		$ids = IFilter::act(IReq::get('ids'));
		//条件筛选处理
		if($ids){
			list($join,$where) = order_class::getSearchCondition();
			$idArr = explode(',',$ids);
			$ids = implode(',',$idArr);
			$where = 'o.id in ('.$ids.')';
		}else{
			list($join,$where) =order_class::getSearchCondition($search);
		}
		//拼接sql
		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,d.name as distribute_name,u.username,p.name as payment_name";
		$orderHandle->join   = $join;
		$orderHandle->where  = $where;
		$orderList = $orderHandle->find();
	
		$strTable ='<table width="500" border="1">';
		$strTable .= '<tr>';
		$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品信息</td>';
		$strTable .= '</tr>';
	
		foreach($orderList as $k=>$val){
			$strTable .= '<tr>';
			$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['order_no'].'</td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['accept_name'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">&nbsp;'.$val['telphone'].'&nbsp;'.$val['mobile'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['payable_amount'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['real_amount'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['payment_name'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.Preorder_Class::getOrderPayStatusText($val).' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.Preorder_Class::getOrderDistributionStatusText($val).' </td>';
	
			$orderGoods = Order_class::getOrderGoods($val['id']);
			$strGoods="";
			foreach($orderGoods as $good){
				$strGoods .= "商品编号：".$good->goodsno." 商品名称：".$good->name;
				if ($good->value!='') $strGoods .= " 规格：".$good->value;
				$strGoods .= "<br />";
			}
			unset($orderGoods);
	
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$strGoods.' </td>';
			$strTable .= '</tr>';
		}
		$strTable .='</table>';
		unset($orderList);
		$reportObj = new report();
		$reportObj->setFileName('order');
		$reportObj->toDownload($strTable);
		exit();
	}

	
}