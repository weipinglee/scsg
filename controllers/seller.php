<?php
/**
 * @brief 商家模块
 * @class Seller
 * @author 
 * @datetime 2014/7/19 15:18:56
 */
class Seller extends IController
{
	public $layout = 'seller';

	/**
	 * @brief 初始化检查
	 */
	public function init()
	{
		IInterceptor::reg('CheckRights@onCreateAction');
	}
    
    //验证二维码
    function checkCode(){
        $order_id = IFilter::act(IReq::get('id'), 'int');
        $good_id = IFilter::act(IReq::get('gId'), 'int');
        $seller_id = IFilter::act(IReq::get('sId'), 'int');
        $good = new IModel('goods');
        $time = $good->getField('id='.$good_id, 'past_time');
        if($time && $time <> '0000-00-00' && $time < date('Y-m-d'))
        {
            exit('该商品已过期');
        }
        if($seller_id <> $this->seller['seller_id'])
        {
            exit('当前登录商家无权限操作该订单');
        }                                                        
        $this->order_deliver($order_id, $good_id);
    }
	/**
	 * @brief 商品添加中图片上传的方法
	 */
	public function goods_img_upload()
	{
		//获得配置文件中的数据
		$config = new Config("site_config");

	 	//调用文件上传类
		$photoObj = new PhotoUpload();
		$photo    = current($photoObj->run());

		//判断上传是否成功，如果float=1则成功
		if($photo['flag'] == 1)
		{
			$result = array(
				'flag'=> 1,
				'img' => $photo['img']
			);
		}
		else
		{
			$result = array('flag'=> $photo['flag']);
		}
		echo JSON::encode($result);
	}
	/**
	 * @brief 商品添加和修改视图
	 */
	public function goods_edit()
	{
		$goods_id = IFilter::act(IReq::get('id'),'int');

		//初始化数据
		$goods_class = new goods_class($this->seller['seller_id']);

		//获取所有商品扩展相关数据
		$data = $goods_class->edit($goods_id);

		if($goods_id && !$data)
		{
			die("没有找到相关商品！");
		}

		$this->setRenderData($data);
		$this->redirect('goods_edit');
	}
	//商品更新动作
	public function goods_update()
	{
		$id       = IFilter::act(IReq::get('id'),'int');
		$callback = IFilter::act(IReq::get('callback'),'url');
		$callback = strpos($callback,'seller/goods_list') === false ? '' : $callback;

		//检查表单提交状态
		if(!$_POST)
		{
			die('请确认表单提交正确');
		}

		//初始化商品数据
		unset($_POST['id']);
		unset($_POST['callback']);
        if($_POST['type'] == 0)
        {
            unset($_POST['past_time']);
        }      
        else
        {
            $_POST['past_time'] = $_POST['past_time'] ? $_POST['past_time'] : '0000-00-00';
            if($_POST['is_del']==3 && $_POST['past_time'] <> '0000-00-00' && $_POST['past_time'] < date('Y-m-d'))
            {
                die('该商品已过期,不能申请上架');                  
            }
        }
		$goodsObject = new goods_class($this->seller['seller_id']);
		$goodsObject->update($id,$_POST);

		$callback ? $this->redirect($callback) : $this->redirect("goods_list");
	}
	//商品列表
	public function goods_list()
	{
		$this->redirect('goods_list');
	}

	//商品列表
	public function goods_report()
	{
		$seller_id = $this->seller['seller_id'];
		$idsArr = IFilter::act(IReq::get('id'));
		
		if(!$idsArr){
			$this->redirect('goods_list',false);
			Util::showMessage('请选择商品数据');
		}
		$ids = implode(',',$idsArr);

		$where  = 'go.seller_id='.$seller_id;
		$where .= ' and go.id in ('.$ids.')';

		$goodHandle = new IQuery('goods as go');
		$goodHandle->order  = "go.id desc";
		$goodHandle->fields = "go.*";
		$goodHandle->where  = $where;
		$goodList = $goodHandle->find();

		//构建 Excel table;
		$strTable ='<table width="500" border="1">';
		$strTable .= '<tr>';
		$strTable .= '<td style="text-align:center;font-size:12px;">商品名称</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="160">分类</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="60">售价</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="60">库存</td>';
		$strTable .= '</tr>';

		foreach($goodList as $k=>$val){
			$strTable .= '<tr>';
			$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['name'].'</td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.goods_class::getGoodsCategory($val['id']).' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['sell_price'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['store_nums'].' </td>';
			$strTable .= '</tr>';
		}
		$strTable .='</table>';
		unset($goodList);
		$reportObj = new report();
		$reportObj->setFileName('goods');
		$reportObj->toDownload($strTable);
		exit();
	}

	//商品删除
	public function goods_del()
	{
		//post数据
	    $id = IFilter::act(IReq::get('id'),'int');

	    //生成goods对象
	    $goods = new goods_class();
	    $goods->seller_id = $this->seller['seller_id'];

	    if($id)
		{
			if(is_array($id))
			{
				foreach($id as $key => $val)
				{
					$goods->del($val);
				}
			}
			else
			{
				$goods->del($id);
			}
		}
		$this->redirect("goods_list");
	}


	//商品状态修改
	public function goods_status()
	{
	    $id        = IFilter::act(IReq::get('id'),'int');
		$is_del    = IFilter::act(IReq::get('is_del'),'int');
		$is_del    = $is_del === 0 ? 3 : $is_del; //不能等于0直接上架
		$seller_id = $this->seller['seller_id'];

		$goodsDB = new IModel('goods');
		$goodsDB->setData(array('is_del' => $is_del));

	    if($id)
		{
			if(is_array($id))
			{
				foreach($id as $key => $val)
				{
					$goodsDB->update("id = ".$val." and seller_id = ".$seller_id);
				}
			}
			else
			{
				$goodsDB->update("id = ".$val." and seller_id = ".$seller_id);
			}
		}
		$this->redirect("goods_list");
	}

	//规格删除
	public function spec_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$idString = is_array($id) ? join(',',$id) : $id;
			$specObj  = new IModel('spec');
			$specObj->del("id in ( {$idString} ) and seller_id = ".$this->seller['seller_id']);
			$this->redirect('spec_list');
		}
		else
		{
			$this->redirect('spec_list',false);
			Util::showMessage('请选择要删除的规格');
		}
	}
	//修改排序
	public function ajax_sort()
	{
		$id   = IFilter::act(IReq::get('id'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');

		$goodsDB = new IModel('goods');
		$goodsDB->setData(array('sort' => $sort));
		$goodsDB->update("id = {$id} and seller_id = ".$this->seller['seller_id']);
	}

	//咨询回复
	public function refer_reply()
	{
		$rid     = IFilter::act(IReq::get('refer_id'),'int');
		$content = IFilter::act(IReq::get('content'),'text');

		if($rid && $content)
		{
			$tb_refer = new IModel('refer');
			$seller_id = $this->seller['seller_id'];//商户id
			$data = array(
				'answer' => $content,
				'reply_time' => date('Y-m-d H:i:s'),
				'seller_id' => $seller_id,
				'status' => 1
			);
			$tb_refer->setData($data);
			$tb_refer->update("id=".$rid);
		}
		$this->redirect('refer_list');
	}
	/**
	 * @brief查看订单
	 */
	public function order_show()
	{
		//获得post传来的值
		$order_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($order_id)
		{
			$order_show = new Order_Class();
			$data = $order_show->getOrderShow($order_id);
			if($data)
			{
				//获得折扣前的价格
			 	$rule = new ProRule($data['real_amount']);
			 	$this->result = $rule->getInfo();

		 		//获取地区
		 		$data['area_addr'] = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']));

			 	$this->setRenderData($data);
				$this->redirect('order_show',false);
			}
		}
		if(!$data)
		{
			$this->redirect('order_list');
		}
	}
	/**
	 * @brief 发货订单页面
	 */
	public function order_deliver($order_id = '', $good_id = '')
	{                                                 
        $order_id = $order_id ? $order_id : IFilter::act(IReq::get('id'),'int');          
		$data = array();
		if($order_id)
		{
            $order = new IModel('order');
            $this->order_status = $order->getField('id='.$order_id, 'status');
			$order_show = new Order_Class();     
			$data = $order_show->getOrderShow($order_id);
            if($good_id)
            {
                $goods = new IModel('goods');
                $this->type = $goods->getField('id='.$good_id, 'type');
            }
            $this->good_id = $good_id;
		}
		$this->setRenderData($data);
		$this->redirect('order_deliver',false);
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
	 		die('请选择要发货的商品');
	 	}

	 	Order_Class::sendDeliveryGoods($order_id,$sendgoods,$this->seller['seller_id'],'seller');
	 	$this->redirect('order_list');
	}

	//订单导出 Excel
	public function order_report()
	{
		$seller_id = $this->seller['seller_id'];
		$condition = Util::search(IFilter::act(IReq::get('search'),'strict'));

		$where  = "go.seller_id = ".$seller_id;
		$where .= $condition ? " and ".$condition : "";

		//拼接sql
		$orderHandle = new IQuery('order_goods as og');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,p.name as payment_name";
		$orderHandle->join   = "left join goods as go on go.id=og.goods_id left join order as o on o.id=og.order_id left join payment as p on p.id = o.pay_type";
		$orderHandle->where  = $where;
		$orderList = $orderHandle->find();

		//构建 Excel table
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
			$strTable .= '<td style="text-align:left;font-size:12px;">'.Order_Class::getOrderPayStatusText($val).' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.Order_Class::getOrderDistributionStatusText($val).' </td>';

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
		//输出成EXcel格式文件并下载
		$reportObj = new report();
		$reportObj->setFileName('order');
		$reportObj->toDownload($strTable);
		exit();
	}

	//修改商户信息
	public function seller_edit()
	{
		$seller_id = $this->seller['seller_id'];
		$sellerDB        = new IModel('seller');
		$this->sellerRow = $sellerDB->getObj('id = '.$seller_id);
		$this->redirect('seller_edit');
		
	}

	/**
	 * @brief 商户的增加动作
	 */
	public function seller_add()
	{
		$seller_id   = $this->seller['seller_id'];
		$email       = IFilter::act(IReq::get('email'));
		$phone       = IFilter::act(IReq::get('phone'));
		$mobile      = IFilter::act(IReq::get('mobile'));
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
		$address     = IFilter::act(IReq::get('address'));
		$account     = IFilter::act(IReq::get('account'));
		$server_num  = IFilter::act(IReq::get('server_num'));
		$home_url    = IFilter::act(IReq::get('home_url'));
		$tax         = IFilter::act(IReq::get('tax'),'float');
		$freight_collect = IFilter::act(IReq::get('freight_collect'),'int');
		$goods_cat = IFilter::act(IReq::get('goods_cat'));
		$goods_cat = !empty($goods_cat) ? implode(',',$goods_cat) : '';
		
		//操作失败表单回填
		if(isset($errorMsg))
		{
			$this->sellerRow = $_POST;
			$this->redirect('seller_edit',false);
			Util::showMessage($errorMsg);
		}

		//待更新的数据
		$sellerRow = array(
			'account'   => $account,
			'phone'     => $phone,
			'mobile'    => $mobile,
			'email'     => $email,
			'address'   => $address,
			'province'  => $province,
			'city'      => $city,
			'area'      => $area,
			'server_num'=> $server_num,
			'home_url'  => $home_url,
			'tax'      => $tax,
			'freight_collect'=>$freight_collect,
			'goods_cat' => $goods_cat,
		);
		//商户资质上传
		if((isset($_FILES['paper_img']['name']) && $_FILES['paper_img']['name']) || (isset($_FILES['logo_img']['name']) && $_FILES['logo_img']['name']))
		{
			$uploadObj = new PhotoUpload();
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();
			if(isset($photoInfo['paper_img']['img']) && file_exists($photoInfo['paper_img']['img']))
			{
				$sellerRow['paper_img'] = $photoInfo['paper_img']['img'];
			}
			if(isset($photoInfo['logo_img']['img']) && file_exists($photoInfo['logo_img']['img']))
			{
				$sellerRow['logo_img'] = $photoInfo['logo_img']['img'];
			}
		}

		//创建商家操作类
		$sellerDB   = new IModel("seller");

		$sellerDB->setData($sellerRow);
		$sellerDB->update("id = ".$seller_id);

		$this->redirect('seller_edit');
	}

	//闪购编辑页面
	public function shan_edit()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			$promotionObj = new IModel('promotion');
			$where = 'id = '.$id;
			$promotionRow = $promotionObj->getObj($where);
			if(empty($promotionRow) || $promotionRow['seller_id']!=$this->seller['seller_id'])
			{
				$this->redirect('shan_list');
			}
		
			if($promotionRow['product_id'])//product模式
			{
				$goodsObj = new IQuery('products as p');
				$goodsObj->join = ' left join goods as g on (p.goods_id = g.id)';
				$goodsObj->where = 'p.id = '.$promotionRow['product_id'];
				$goodsObj->fields = 'p.id as product_id,p.goods_id as goods_id,p.sell_price,p.spec_array,g.name,g.img';
				$goodsRow = $goodsObj->getObj();
		
			}else//good模式
			{
				$goodsObj = new IModel('goods');
				$goodsRow = $goodsObj->getObj('id = '.$promotionRow['condition'],'id as goods_id,name,sell_price,img');
				$goodsRow['spec_array'] = '';
				$goodsRow['product_id'] = 0;
		
			}
			//促销商品
				
			if($goodsRow)
			{
				$result = array(
						'isError' => false,
						'data'    => $goodsRow,
				);
			}
			else
			{
				$result = array(
						'isError' => true,
						'message' => '关联商品被删除，请重新选择要抢购的商品',
				);
			}
		
			$promotionRow['goodsRow'] = JSON::encode($result);
			$this->promotionRow = $promotionRow;
		}
		$this->redirect('shan_edit');
	}
	//闪购删除
	public function shan_del(){
		$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			$promObj     = new IModel('promotion');
		
			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = ' id in ('.$idStr.')';
			}
			else
			{
				$where  = 'id = '.$id;
			}
			$where .= ' and seller_id = '.$this->seller['seller_id'];
			$promObj->del($where);
			$this->redirect('shan_list');
		}
		else
		{
			$this->redirect('shan_list',false);
			Util::showMessage('请选择要删除的id值');
		}
	}
	//闪购页面编辑提交
	public function shan_edit_act(){
		$id = IFilter::act(IReq::get('id'),'int');
		
		$goodsId = IFilter::act(IReq::get('goods_id','post'),'int');
		$productId = IFilter::act(IReq::get('product_id','post'),'int');
		
		//判断商品是否是商户商品
		$good = new IModel('goods');
		$goodRow = $good->getObj('id='.$goodsId.' and seller_id = '.$this->seller['seller_id'],'id');
		if(!$goodRow){
			$this->redirect('shan_list');
			exit();
		}
		$condition = $goodsId;
		$award_value  = IFilter::act(IReq::get('award_value','post'));
		$user_group_str    = 'all';
		
		$dataArray = array(
				'id'         => $id,
				'name'       => IFilter::act(IReq::get('name','post')),
				'condition'  => $condition,
				'product_id'  => $productId,
				'award_value'=> $award_value,
				'is_close'   => IFilter::act(IReq::get('is_close','post')),
				'start_time' => IFilter::act(IReq::get('start_time','post')),
				'end_time'   => IFilter::act(IReq::get('end_time','post')),
				'intro'      => IFilter::act(IReq::get('intro','post')),
				'type'       => 1,
				'award_type' => 0,
				'user_group' => $user_group_str,
				'seller_id'  => $this->seller['seller_id']
		);
		
		if(isset($_FILES['shan_img'])&&$_FILES['shan_img']['name']!='')
			$dataArray['shan_img'] = uploadHandle('shan_img');
		
		if(!$condition || !$award_value)
		{
			$this->promotionRow = $dataArray;
		
			$this->redirect('shan_edit',false);
			Util::showMessage('请添加促销的商品，并为商品填写价格');
		}
		
		$proObj = new IModel('promotion');
		$proObj->setData($dataArray);
		if($id)
		{
			$where = 'id = '.$id;
			$proObj->update($where);
		}
		else
		{
			$proObj->add();
		}
		$this->redirect('shan_list');
	}
	//[团购]添加修改[单页]
	function regiment_edit()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$regimentObj = new IModel('regiment');
			$where       = 'id = '.$id;
			$regimentRow = $regimentObj->getObj($where);
			if(!$regimentRow || $regimentRow['seller_id']!=$this->seller['seller_id'])
			{
				$this->redirect('regiment_list');
			}

			//促销商品
			if($regimentRow['product_id']){
				$goodsObj = new IQuery('products as p');
				$goodsObj->join = ' left join goods as g on (p.goods_id = g.id)';
				$goodsObj->where = 'p.id = '.$regimentRow['product_id'];
				$goodsObj->fields = 'p.id as product_id,p.spec_array,p.store_nums,p.sell_price,g.id as goods_id,g.name';
				$goodsRow = $goodsObj->getObj();
				
			}else{
				$goodsObj = new IModel('goods');
				$goodsRow = $goodsObj->getObj('id = '.$regimentRow['goods_id'],'id as goods_id,name,store_nums,sell_price');
				$goodsRow['spec_array'] = '';
				$goodsRow['product_id'] = 0;
			}

			$result = array(
				'isError' => false,
				'data'    => $goodsRow,
			);
			$regimentRow['goodsRow'] = JSON::encode($result);
			$this->regimentRow = $regimentRow;
		}
		$this->redirect('regiment_edit');
	}

	//[团购]删除
	function regiment_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			$regObj     = new IModel('regiment');

			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = ' id in ('.$idStr.')';
				$uwhere= ' regiment_id in ('.$idStr.')';
			}
			else
			{
				$where  = 'id = '.$id;
				$uwhere = 'regiment_id = '.$id;
			}
			$where .= ' and seller_id = '.$this->seller['seller_id'];
			$regObj->del($where);
			$this->redirect('regiment_list');
		}
		else
		{
			$this->redirect('regiment_list',false);
			Util::showMessage('请选择要删除的id值');
		}
	}

	//[团购]添加修改[动作]
	function regiment_edit_act()
	{
		$id      = IFilter::act(IReq::get('id'),'int');
		$goodsId = IFilter::act(IReq::get('goods_id'),'int');

		$good = new IModel('goods');
		$goodRow = $good->getObj('id='.$goodsId.' and seller_id = '.$this->seller['seller_id'],'id');
		if(!$goodRow){
			$this->redirect('regiment_list');
			exit();
		}
		$dataArray = array(
			'id'        	=> $id,
			'title'     	=> IFilter::act(IReq::get('title','post')),
			'start_time'	=> IFilter::act(IReq::get('start_time','post')),
			'end_time'  	=> IFilter::act(IReq::get('end_time','post')),
			'is_close'      => IFilter::act(IReq::get('is_close','post'),'int'),
			'intro'     	=> IFilter::act(IReq::get('intro','post')),
			'goods_id'      => $goodsId,
			'product_id'    => IFilter::act(IReq::get('product_id','post'),'int'),
			'store_nums'    => IFilter::act(IReq::get('store_nums','post')),
			'limit_min_count' => IFilter::act(IReq::get('limit_min_count','post'),'int'),
			'limit_max_count' => IFilter::act(IReq::get('limit_max_count','post'),'int'),
			'regiment_price'=> IFilter::act(IReq::get('regiment_price','post')),
			'seller_id'     => $this->seller['seller_id']
		);

		if($goodsId)
		{
			$goodsObj = new IModel('goods');
			$where    = 'id = '.$goodsId.' and seller_id = '.$this->seller['seller_id'];
			$goodsRow = $goodsObj->getObj($where);

			//商品信息不存在
			if(!$goodsRow)
			{
				$this->regimentRow = $dataArray;
				$this->redirect('regiment_edit',false);
				Util::showMessage('请选择商户自己的商品');
			}

			//处理上传图片
			if(isset($_FILES['img']['name']) && $_FILES['img']['name'] != '')
			{
				$uploadObj = new PhotoUpload();
				$photoInfo = $uploadObj->run();
				$dataArray['img'] = $photoInfo['img']['img'];
			}
			else
			{
				$dataArray['img'] = $goodsRow['img'];
			}

			$dataArray['sell_price'] = $goodsRow['sell_price'];
		}
		else
		{
			$this->regimentRow = $dataArray;
			$this->redirect('regiment_edit',false);
			Util::showMessage('请选择要关联的商品');
		}

		$regimentObj = new IModel('regiment');
		$regimentObj->setData($dataArray);

		if($id)
		{
			$where = 'id = '.$id;
			$regimentObj->update($where);
		}
		else
		{
			$regimentObj->add();
		}
		$this->redirect('regiment_list');
	}

	//结算单修改
	public function bill_edit()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$billRow = array();

		if($id)
		{
			$billDB  = new IModel('bill');
			$billRow = $billDB->getObj('id = '.$id.' and seller_id = '.$this->seller['seller_id']);
		}

		$this->billRow = $billRow;
		$this->redirect('bill_edit');
	}

	//结算单删除
	public function bill_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$billDB = new IModel('bill');
			$billDB->del('id = '.$id.' and seller_id = '.$this->seller['seller_id'].' and is_pay = 0');
		}

		$this->redirect('bill_list');
	}

	//结算单更新
	public function bill_update()
	{
		$id            = IFilter::act(IReq::get('id'),'int');
		$start_time    = IFilter::act(IReq::get('start_time'));
		$end_time      = IFilter::act(IReq::get('end_time'));
		$apply_content = IFilter::act(IReq::get('apply_content'));

		$billDB = new IModel('bill');

		if($id)
		{
			$billRow = $billDB->getObj('id = '.$id);
			if($billRow['is_pay'] == 0)
			{
				$billDB->setData(array('apply_content' => $apply_content));
				$billDB->update('id = '.$id.' and seller_id = '.$this->seller['seller_id']);
			}
		}
		else
		{
			//判断是否存在未处理的申请
			$isSubmitBill = $billDB->getObj(" seller_id = ".$this->seller['seller_id']." and is_pay = 0");
			if($isSubmitBill)
			{
				$this->redirect('bill_list',false);
				Util::showMessage('请耐心等待管理员结算后才能再次提交申请');
			}

			$queryObject = CountSum::getSellerGoodsFeeQuery($this->seller['seller_id'],$start_time,$end_time,0);
			$countData   = CountSum::countSellerOrderFee($queryObject->find());

			if($countData['orderAmountPrice'] > 0)
			{
				$replaceData = array(
					'{startTime}'     => $start_time,
					'{endTime}'       => $end_time,
					'{goodsNums}'     => count($countData['order_goods_ids']),
					'{goodsSums}'     => $countData['goodsSum'],
					'{deliveryPrice}' => $countData['deliveryPrice'],
					'{protectedPrice}'=> $countData['insuredPrice'],
					'{taxPrice}'      => $countData['taxPrice'],
					'{totalSum}'      => $countData['orderAmountPrice'],
				);

				$billString = AccountLog::sellerBillTemplate($replaceData);
				$data = array(
					'seller_id'  => $this->seller['seller_id'],
					'apply_time' => date('Y-m-d H:i:s'),
					'apply_content' => IFilter::act(IReq::get('apply_content')),
					'start_time' => $start_time,
					'end_time' => $end_time,
					'log' => $billString,
					'order_goods_ids' => join(",",$countData['order_goods_ids']),
				);
				$billDB->setData($data);
				$billDB->add();
			}
			else
			{
				$this->redirect('bill_list',false);
				Util::showMessage('当前时间段内没有任何结算货款');
			}
		}
		$this->redirect('bill_list');
	}

	//计算应该结算的货款明细
	public function countGoodsFee()
	{
		$seller_id   = $this->seller['seller_id'];
		$start_time  = IFilter::act(IReq::get('start_time'));
		$end_time    = IFilter::act(IReq::get('end_time'));

		$queryObject = CountSum::getSellerGoodsFeeQuery($seller_id,$start_time,$end_time,0);
		$countData   = CountSum::countSellerOrderFee($queryObject->find());

		if($countData['orderAmountPrice'] > 0)
		{
			$replaceData = array(
				'{startTime}'     => $start_time,
				'{endTime}'       => $end_time,
				'{goodsNums}'     => count($countData['order_goods_ids']),
				'{goodsSums}'     => $countData['goodsSum'],
				'{deliveryPrice}' => $countData['deliveryPrice'],
				'{protectedPrice}'=> $countData['insuredPrice'],
				'{taxPrice}'      => $countData['taxPrice'],
				'{totalSum}'      => $countData['orderAmountPrice'],
			);

			$billString = AccountLog::sellerBillTemplate($replaceData);
			$result     = array('result' => 'success','data' => $billString);
		}
		else
		{
			$result = array('result' => 'fail','data' => '当前没有任何款项可以结算');
		}

		die(JSON::encode($result));
	}

	/**
	 * @brief 显示评论信息
	 */
	function comment_edit()
	{
		$cid = IFilter::act(IReq::get('cid'),'int');

		if(!$cid)
		{
			$this->comment_list();
			return false;
		}
		$query = new IQuery("comment as c");
		$query->join = "left join goods as goods on c.goods_id = goods.id left join user as u on c.user_id = u.id";
		$query->fields = "c.*,u.username,goods.name,goods.seller_id";
		$query->where = "c.id=".$cid." and goods.seller_id = ".$this->seller['seller_id'];
		$items = $query->find();

		if($items)
		{
			$this->comment = current($items);
			$this->redirect('comment_edit');
		}
		else
		{
			$this->comment_list();
			$msg = '没有找到相关记录！';
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 回复评论
	 */
	function comment_update()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$recontent = IFilter::act(IReq::get('recontents'));
		if($id)
		{
			$commentDB = new IQuery('comment as c');
			$commentDB->join = 'left join goods as go on go.id = c.goods_id';
			$commentDB->where= 'c.id = '.$id.' and go.seller_id = '.$this->seller['seller_id'];
			$checkList = $commentDB->find();
			if(!$checkList)
			{
				IError::show(403,'该商品不属于您，无法对其评论进行回复');
			}

			$updateData = array(
				'recontents' => $recontent,
				'recomment_time' => ITime::getDateTime(),
			);
			$commentDB = new IModel('comment');
			$commentDB->setData($updateData);
			$commentDB->update('id = '.$id);
		}
		$this->redirect('comment_list');
	}

	//商品退款详情
	function refundment_show()
	{
	 	//获得post传来的退款单id值
	 	$refundment_id = IFilter::act(IReq::get('id'),'int');
	 	$data = array();
	 	if($refundment_id)
	 	{
	 		$tb_refundment = new IQuery('refundment_doc as c');
	 		$tb_refundment->join=' left join order as o on c.order_id=o.id left join user as u on u.id = c.user_id';
	 		$tb_refundment->fields = 'o.order_no,o.create_time,u.username,c.*';
	 		$tb_refundment->where = 'c.id='.$refundment_id.' and seller_id = '.$this->seller['seller_id'];
	 		$refundment_info = $tb_refundment->find();
	 		if($refundment_info)
	 		{
	 			$data = current($refundment_info);
	 			$this->setRenderData($data);
	 			$this->redirect('refundment_show',false);
	 		}
	 	}
	 	
	 	if(!$data)
		{
			$this->redirect('refundment_list');
		}
	}
	//商品换货详情
	function refundment_chg_show()
	{
		//获得post传来的退款单id值
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($refundment_id)
		{
			$tb_refundment = new IQuery('refundment_doc as c');
			$tb_refundment->join=' left join order as o on c.order_id=o.id left join user as u on u.id = c.user_id';
			$tb_refundment->fields = 'o.order_no,o.create_time,u.username,c.*';
			$tb_refundment->where = 'c.id='.$refundment_id.' and seller_id = '.$this->seller['seller_id'];
			$refundment_info = $tb_refundment->find();
			if($refundment_info)
			{
				$data = current($refundment_info);
				$this->setRenderData($data);
				$this->redirect('refundment_chg_show',false);
			}
		}
		 
		if(!$data)
		{
			$this->redirect('refundment_chg_list');
		}
	}
	/**
	 * 换货操作
	 * 
	 */
	public function refundment_chg_show_save(){
		//获得post传来的退款单id值
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		$pay_status = IFilter::act(IReq::get('pay_status'),'int');
		$dispose_idea = IFilter::act(IReq::get('dispose_idea'),'text');
		$status=IFilter::act(IReq::get('status'),'int');//原先的pay_status
	
		$type = 1;
		$chg_goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$chg_product_id = IFilter::act(IReq::get('product_id'),'int');
	
		//获得refundment_doc对象
	
		$setData=array(
				'pay_status'   => $pay_status,
				'dispose_idea' => $dispose_idea,
				'dispose_time' => ITime::getDateTime(),
				'admin_id'     => $this->admin['admin_id'],
		);
	
		if($refundment_id)
		{
				
			$tb_refundment_doc = new IModel('refundment_doc');
				
			if($refund_data = $tb_refundment_doc->getObj('id='.$refundment_id,'order_id,pay_status,goods_id,product_id')){
				$order_goods_db = new IModel('order_goods');
				$order_goods_row = $order_goods_db->getObj('order_id='.$refund_data['order_id'].' and goods_id='.$refund_data['goods_id'].' and product_id='.$refund_data['product_id']);
	
				if($pay_status==2){//换货类型且审核通过
					if(!$chg_goods_id){
						$chg_goods_id = $refund_data['goods_id'];
						$chg_product_id = $refund_data['product_id'];
					}
					$chgRes = Order_Class::chg_goods($refundment_id,$chg_goods_id,$chg_product_id,$this->admin['admin_id']);
					if(!$chgRes){
						$this->redirect('refundment_chg_list_handled');
						return false;
					}
					$tb_refundment_doc->setData($setData);
					$tb_refundment_doc->update('id='.$refundment_id);
				}else{//审核不通过
					$tb_refundment_doc->setData($setData);
					$tb_refundment_doc->update('id='.$refundment_id);
					Order_Class::ordergoods_status_refunds(5,$order_goods_row,1);
				}
			}
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('admin_name'),"修改了换货单",'修改的ID：'.$refundment_id));
		}
		$this->redirect('refundment_chg_list_handled');
	
	}
	//商品退款操作
	function refundment_update()
	{
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		$pay_status = IFilter::act(IReq::get('pay_status'),'int');
		$dispose_idea = IFilter::act(IReq::get('dispose_idea'),'text');
		$status=IFilter::act(IReq::get('status'),'int');

		$type = 0;
		$is_send = IFilter::act(IReq::get('is_send'),'int');
		
		//商户处理退款
		if($refundment_id && Order_Class::isSellerRefund($refundment_id,$this->seller['seller_id']) == 2)
		{
			$tb_refundment_doc = new IModel('refundment_doc');
			if($refund_data = $tb_refundment_doc->getObj('id='.$refundment_id,'order_id,user_id,pay_status,goods_id,product_id')){
				$order_id = $refund_data['order_id'];
				$user_id = $refund_data['user_id'];
				$order_goods_db = new IModel('order_goods');
				$order_goods_row = $order_goods_db->getObj('order_id='.$refund_data['order_id'].' and goods_id='.$refund_data['goods_id'].' and product_id='.$refund_data['product_id']);
				$order_goods_row['is_send'] = $is_send;//重置is_send ,因为退款已经把is_send改了，这里需要改回来，计算订单状态
				//print_r($order_goods_row);exit;
				$setData=array(
						'pay_status'   => $pay_status,
						'dispose_idea' => $dispose_idea,
						'dispose_time' => ITime::getDateTime(),
						'admin_id'     => $this->admin['admin_id'],
				);
					
				$tb_refundment_doc->setData($setData);
				$tb_refundment_doc->update('id = '.$refundment_id);
				
				Order_Class::get_order_status_refunds($refundment_id,$pay_status);
				Order_Class::ordergoods_status_refunds($pay_status,$order_goods_row,0);
			}
			
		}
		 $this->redirect('refundment_list');
		
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
			$orderDB = new Order_Class();
			$data    = $orderDB->getOrderShow($orderId);
	
			//已经存退款申请
			if($refundsId)
			{
				$refundsDB  = new IModel('refundment_doc');
				$refundsRow = $refundsDB->getObj('id = '.$refundsId.' and seller_id='.$this->seller['seller_id']);
				if(empty($refundsRow))die('退换货数据不存在');
				$data['refunds'] = $refundsRow;
			}
			$this->setRenderData($data);
			$this->redirect('order_refundment');
			exit;
		}
		
		die('订单数据不存在');
	}

	//商品复制
	function goods_copy()
	{
		$idArray = explode(',',IReq::get('id'));
		$idArray = IFilter::act($idArray,'int');

		$goodsDB     = new IModel('goods');
		$goodsAttrDB = new IModel('goods_attribute');
		$goodsPhotoRelationDB = new IModel('goods_photo_relation');
		$productsDB = new IModel('products');

		$goodsData = $goodsDB->query('id in ('.join(',',$idArray).') and is_share = 1 and is_del = 0 and seller_id = 0','*');
		
		if($goodsData)
		{
			foreach($goodsData as $key => $val)
			{
				//判断是否重复
				if( $goodsDB->getObj('seller_id = '.$this->seller['seller_id'].' and name = "'.$val['name'].'"') )
				{
					die('商品不能重复复制');
				}

				$oldId = $val['id'];

				//商品数据
				unset($val['id'],$val['visit'],$val['favorite'],$val['sort'],$val['comments'],$val['sale'],$val['grade'],$val['is_share']);
				$val['seller_id'] = $this->seller['seller_id'];
				$val['goods_no'] .= '-'.$this->seller['seller_id'];
				$val['content'] = IFilter::addSlash($val['content']);
				$goodsDB->setData($val);
				$goods_id = $goodsDB->add();

				//商品属性
				$attrData = $goodsAttrDB->query('goods_id = '.$oldId);
				if($attrData)
				{
					foreach($attrData as $k => $v)
					{
						unset($v['id']);
						$v['goods_id'] = $goods_id;
						$goodsAttrDB->setData($v);
						$goodsAttrDB->add();
					}
				}

				//商品图片
				$photoData = $goodsPhotoRelationDB->query('goods_id = '.$oldId);
				if($photoData)
				{
					foreach($photoData as $k => $v)
					{
						unset($v['id']);
						$v['goods_id'] = $goods_id;
						$goodsPhotoRelationDB->setData($v);
						$goodsPhotoRelationDB->add();
					}
				}

				//货品
				$productsData = $productsDB->query('goods_id = '.$oldId);
				if($productsData)
				{
					foreach($productsData as $k => $v)
					{
						unset($v['id']);
						$v['products_no'].= '-'.$this->seller['seller_id'];
						$v['goods_id']    = $goods_id;
						$productsDB->setData($v);
						$productsDB->add();
					}
				}
			}
			die('success');
		}
		else
		{
			die('复制的商品不存在');
		}
	}

	/**
	 * @brief 添加/修改发货信息
	 */
	public function ship_info_edit()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get("sid"),'int');
    	if($id)
    	{
    		$tb_ship   = new IModel("merch_ship_info");
    		$ship_info = $tb_ship->getObj("id=".$id." and seller_id = ".$this->seller['seller_id']);
    		if($ship_info)
    		{
    			$this->data = $ship_info;
    		}
    		else
    		{
    			die('数据不存在');
    		}
    	}
    	$this->setRenderData($this->data);
		$this->redirect('ship_info_edit');
	}
	/**
	 * @brief 设置发货信息的默认值
	 */
	public function ship_info_default()
	{
		$id = IFilter::act( IReq::get('id'),'int' );
        $default = IFilter::string(IReq::get('default'));
        $tb_merch_ship_info = new IModel('merch_ship_info');
        if($default == 1)
        {
            $tb_merch_ship_info->setData(array('is_default'=>0));
            $tb_merch_ship_info->update("seller_id = ".$this->seller['seller_id']);
        }
        $tb_merch_ship_info->setData(array('is_default' => $default));
        $tb_merch_ship_info->update("id = ".$id." and seller_id = ".$this->seller['seller_id']);
        $this->redirect('ship_info_list');
	}
	/**
	 * @brief 保存添加/修改发货信息
	 */
	public function ship_info_update()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('sid'),'int');
    	$ship_name = IFilter::act(IReq::get('ship_name'));
    	$ship_user_name = IFilter::act(IReq::get('ship_user_name'));
    	$sex = IFilter::act(IReq::get('sex'),'int');
    	$province =IFilter::act(IReq::get('province'),'int');
    	$city = IFilter::act(IReq::get('city'),'int');
    	$area = IFilter::act(IReq::get('area'),'int');
    	$address = IFilter::act(IReq::get('address'));
    	$postcode = IFilter::act(IReq::get('postcode'),'int');
    	$mobile = IFilter::act(IReq::get('mobile'));
    	$telphone = IFilter::act(IReq::get('telphone'));
    	$is_default = IFilter::act(IReq::get('is_default'),'int');

    	$tb_merch_ship_info = new IModel('merch_ship_info');

    	//判断是否已经有了一个默认地址
    	if(isset($is_default) && $is_default==1)
    	{
    		$tb_merch_ship_info->setData(array('is_default' => 0));
    		$tb_merch_ship_info->update('seller_id = 0');
    	}
    	//设置存储数据
    	$arr['ship_name'] = $ship_name;
	    $arr['ship_user_name'] = $ship_user_name;
	    $arr['sex'] = $sex;
    	$arr['province'] = $province;
    	$arr['city'] =$city;
    	$arr['area'] =$area;
    	$arr['address'] = $address;
    	$arr['postcode'] = $postcode;
    	$arr['mobile'] = $mobile;
    	$arr['telphone'] =$telphone;
    	$arr['is_default'] = $is_default;
    	$arr['is_del'] = 1;
    	$arr['seller_id'] = $this->seller['seller_id'];

    	$tb_merch_ship_info->setData($arr);
    	//判断是添加还是修改
    	if($id)
    	{
    		$tb_merch_ship_info->update('id='.$id." and seller_id = ".$this->seller['seller_id']);
    	}
    	else
    	{
    		$tb_merch_ship_info->add();
    	}
		$this->redirect('ship_info_list');
	}
	/**
	 * @brief 删除发货信息到回收站中
	 */
	public function ship_info_del()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('id'),'int');
		//加载 商家发货点信息
    	$tb_merch_ship_info = new IModel('merch_ship_info');
		if($id)
		{
			$tb_merch_ship_info->del(Util::joinStr($id)." and seller_id = ".$this->seller['seller_id']);
			$this->redirect('ship_info_list');
		}
		else
		{
			$this->redirect('ship_info_list',false);
			Util::showMessage('请选择要删除的数据');
		}
	}

	/**
	 * @brief 配送方式修改
	 */
    public function delivery_edit()
	{
		$data = array();
        $delivery_id = IFilter::act(IReq::get('id'),'int');

        if($delivery_id)
        {
            $delivery = new IModel('delivery_extend');
            $data = $delivery->getObj('delivery_id = '.$delivery_id.' and seller_id = '.$this->seller['seller_id']);
		}
		else
		{
			die('配送方式');
		}

		//获取省份
		$areaData = array();
		$areaDB = new IModel('areas');
		$areaList = $areaDB->query('parent_id = 0');
		foreach($areaList as $val)
		{
			$areaData[$val['area_id']] = $val['area_name'];
		}
		$this->areaList  = $areaList;
		$this->data_info = $data;
		$this->area      = $areaData;
        $this->redirect('delivery_edit');
	}

	/**
	 * 配送方式修改
	 */
    public function delivery_update()
    {
        //首重重量
        $first_weight = IFilter::act(IReq::get('first_weight'),'float');
        //续重重量
        $second_weight = IFilter::act(IReq::get('second_weight'),'float');
        //首重价格
        $first_price = IFilter::act(IReq::get('first_price'),'float');
        //续重价格
        $second_price = IFilter::act(IReq::get('second_price'),'float');
        //是否支持物流保价
        $is_save_price = IFilter::act(IReq::get('is_save_price'),'int');
        //地区费用类型
        $price_type = IFilter::act(IReq::get('price_type'),'int');
        //启用默认费用
        $open_default = IFilter::act(IReq::get('open_default'),'int');
        //支持的配送地区ID
        $area_groupid = serialize(IReq::get('area_groupid'));
        //配送地址对应的首重价格
        $firstprice = serialize(IReq::get('firstprice'));
        //配送地区对应的续重价格
        $secondprice = serialize(IReq::get('secondprice'));
        //保价费率
        $save_rate = IFilter::act(IReq::get('save_rate'),'float');
        //最低保价
        $low_price = IFilter::act(IReq::get('low_price'),'float');
		//配送ID
        $delivery_id = IFilter::act(IReq::get('deliveryId'),'int');
		
        //是否开启
        $is_open = IFilter::act(IReq::get('is_open'),'int');
        
        $deliveryDB  = new IModel('delivery');
        $deliveryRow = $deliveryDB->getObj('id = '.$delivery_id);
        if(!$deliveryRow)
        {
        	die('配送方式不存在');
        }

        $data = array(
        	'first_weight' => $first_weight,
        	'second_weight'=> $second_weight,
        	'first_price'  => $first_price,
        	'second_price' => $second_price,
        	'is_save_price'=> $is_save_price,
        	'price_type'   => $price_type,
        	'open_default' => $open_default,
        	'area_groupid' => $area_groupid,
        	'firstprice'   => $firstprice,
        	'secondprice'  => $secondprice,
        	'save_rate'    => $save_rate,
        	'low_price'    => $low_price,
        	'seller_id'    => $this->seller['seller_id'],
        	'delivery_id'  => $delivery_id,
        	'is_open'      => $is_open,
        );
        $deliveryExtendDB = new IModel('delivery_extend');
        $deliveryExtendDB->setData($data);
        $deliveryObj = $deliveryExtendDB->getObj("delivery_id = ".$delivery_id." and seller_id = ".$this->seller['seller_id']);
        //已经存在了
        if($deliveryObj)
        {
        	$deliveryExtendDB->update('delivery_id = '.$delivery_id.' and seller_id = '.$this->seller['seller_id']);
        }
        else
        {
        	$deliveryExtendDB->add();
        }
		$this->redirect('delivery');
    }
    
	/*
	 * 商品库存累加（zz）
	 * 
	 */
	function store_add(){
		$sellerid = $this->seller['seller_id'];
		echo goods_class::store_chg($_POST,$sellerid) ? 1 : 0;
	}
    
    /**
     * 更改密码
     */
	public function seller_pass(){
		//print_r($_POST);
		$old_pass = IFilter::act(IReq::get('old_pass','post'),'string');
		$new_pass = IFilter::act(IReq::get('new_pass','post'),'string');
		$new_pass2 = IFilter::act(IReq::get('new_pass_2','post'),'string');
		if(strlen($new_pass)<6 || strlen($old_pass)<6)
			$errorMsg = '密码不得少于6位字符！';
		if($new_pass != $new_pass2){
			$errorMsg = '两次密码不一致！';
		}
		
		
		$seller = new IModel('seller');
		$sellerid = $this->seller['seller_id'];
		if($seller->getObj('id='.$sellerid.' AND password = "'.md5($old_pass).'"')){
			$seller->setData(array('password'=>md5($new_pass)));
			$seller->update('id='.$sellerid);
			$this->redirect('seller_edit');
		}else $errorMsg = '原密码不正确！';
			
		//操作失败表单回填
		if(isset($errorMsg))
		{
			$this->redirect('chg_pass',false);
			Util::showMessage($errorMsg);
		}
	}
    
	//保存退款单页
	public function order_refundment_doc()
	{
		$seller_id = $this->seller['seller_id'];
		$refunds_id = IFilter::act(IReq::get('refunds_id'),'int');
		$order_id = IFilter::act(IReq::get('id'),'int');
		$order_no = IFilter::act(IReq::get('order_no'));
		$user_id  = IFilter::act(IReq::get('user_id'),'int');
		$amount   = IFilter::act(IReq::get('amount'),'float'); //要退款的金额
		$order_goods_id = IFilter::act(IReq::get('order_goods_id'),'int'); //要退款的商品,如果是用户已经提交的退款申请此数据为NULL,需要获取出来
		$orderGoodsDB      = new IModel('order_goods');
		$tb_refundment_doc = new IModel('refundment_doc');
		if(!$user_id)
		{
			die('<script text="text/javascript">parent.actionCallback("游客无法退款");</script>');
		}
		if(!$refunds_id  || !Order_Class::isSellerRefund($refunds_id,$seller_id) || !$order_goods_id)
		{
			die('<script text="text/javascript">parent.actionCallback("退货单不存在");</script>');
		}
		$orderGoodsRow = $orderGoodsDB->getObj('id = '.$order_goods_id);
		if($amount>$orderGoodsRow['real_price']*$orderGoodsRow['goods_nums']+$orderGoodsRow['delivery_fee']+$orderGoodsRow['save_price']+$orderGoodsRow['tax']){
			die('<script text="text/javascript">parent.actionCallback("退款金额不得大于实际支付金额");</script>');
			return false;
		}
		$tb_refundment_doc->setData(array('amount'=>$amount));
		$tb_refundment_doc->update('id='.$refunds_id);
		$tb_refundment_doc->commit();
		
		$result = Order_Class::refund($refunds_id,$seller_id,'seller');
		
		
		if($result)
		{
			//记录操作日志
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".ISafe::get('seller_name'),"订单更新为退款",'订单号：'.$order_no));
			die('<script text="text/javascript">parent.actionCallback();</script>');
		}
		else
		{
			die('<script text="text/javascript">parent.actionCallback("退货错误");</script>');
		}
	}
    
	//发票申请列表
	public function fapiao_apply(){
		$search = Util::search(IReq::get('search'));$whereAdd = $search ? " and ".$search : "";
		$seller_id = $this->seller['seller_id'];
		$page=(isset($_GET['page'])&&(intval($_GET['page'])>0))?intval($_GET['page']):1;
		$fapiao_db = new IQuery('order_fapiao as f');
		$fapiao_db->join = 'left join order as o on o.id = f.order_id   left join user as u on u.id = f.user_id';
		$fapiao_db->where = 'seller_id ='. $seller_id.' AND f.status = 0 '.$whereAdd;
		
		$fapiao_db->order = 'f.id DESC';
		$fapiao_db->page = $page;
		$fapiao_db->fields = 'f.*,o.order_no,u.username';
		$this->fapiaoData = $fapiao_db->find();
		$this->db = $fapiao_db;
		$this->redirect('fapiao_apply');
		//print_r($res);
	}
	//发票列表
	public function fapiao_list(){
		$search = Util::search(IReq::get('search'));$whereAdd = $search ? " and ".$search : "";
		$seller_id = $this->seller['seller_id'];
		$page=(isset($_GET['page'])&&(intval($_GET['page'])>0))?intval($_GET['page']):1;
		$fapiao_db = new IQuery('order_fapiao as f');
		$fapiao_db->join = 'left join order as o on o.id = f.order_id   left join user as u on u.id = f.user_id';
		$fapiao_db->where = 'seller_id ='. $seller_id.' AND f.status = 1 '.$whereAdd;
		
		$fapiao_db->order = 'f.id DESC';
		$fapiao_db->page = $page;
		$fapiao_db->fields = 'f.*,o.order_no,u.username';
		$this->fapiaoData = $fapiao_db->find();
	
		$this->db = $fapiao_db;
		$this->redirect('fapiao_list');
	}
    //显示发票详情
	public function fapiao_show(){
		$seller_id = $this->seller['seller_id'];
		$id = IFilter::act(IReq::get('id'),'int');
		$db_fa = new IQuery('order_fapiao as f');
		$db_fa->join = 'left join order as o on o.id = f.order_id  left join user as u on u.id = f.user_id';
		$db_fa->where = 'f.id ='. $id.' AND f.seller_id = '.$seller_id;
		$db_fa->limit = 1;
		$db_fa->fields = 'u.username,o.order_no,o.real_amount,f.*';
		$data = $db_fa->find();
		$data = $data[0];
		if($data['money']==0)$data['money']=$data['real_amount'];
		
		$this->setRenderData($data);
		$this->redirect('fapiao_show');
	}
	//发票处理
	public function fapiao_show_save(){
		$id = IFilter::act(IReq::get('id'),'int');
		$money = IFilter::act(IReq::get('money'),'float');
		if(!$id || !$money){
			$this->redirect('fapiao_apply');
		}
		$db_fa = new IModel('order_fapiao');
		$data=array(
				'money'=>$money,
				'status'=>1
		);
		$db_fa->setData($data);
		$db_fa->update('id='.$id);
		$this->redirect('fapiao_apply');
	}
    
    
}