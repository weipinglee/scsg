<?php
/**
 * @brief 商家模块
 * @class Seller
 * @author 
 * @datetime 2014/7/19 15:18:56
 */
class Seller extends IController
{
    public $checkRight  = array('check' => 'all','uncheck' => array('index','chg_pass','seller_pass'));
	public $layout = 'seller';
    private $ticketDir = 'backup/ticket';

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
        //获取运费计算方式
        /*$delivery = new IQuery('delivery as d');
        $delivery->join = 'left join delivery_extend as de on d.id = de.delivery_id';
        $delivery->where = 'd.is_delete=0 and de.seller_id='.$this->seller['seller_id'];
        $delivery->fields = 'd.id,d.name';
        $this->delivery = $delivery->find(); */
        $delivery = new IModel('delivery');
        $list = $delivery->query("is_delete=0 and status=1", 'id,name','sort','asc');
        $this->delivery = $list;
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
            $_POST['delivery_id'] = 0;
            if($_POST['is_del']==3 && $_POST['past_time'] <> '0000-00-00' && $_POST['past_time'] < date('Y-m-d'))
            {
                die('该商品已过期,不能申请上架');                  
            }
        }
		$goodsObject = new goods_class($this->seller['seller_id']);
		$goodsObject->update($id,$_POST);

		$callback ? $this->redirect($callback) : $this->redirect("goods_list");
	}
    
    //验证条形码是否重复
    public function checkSignCode()
    {
        $id = IReq::get('id') ? IReq::get('id') : 0;
        $sign = IReq::get('sign');
        $product_id = IReq::get('product_id') ? IReq::get('product_id') : 0;
        $goodsDB = new IModel('goods');
        if($sign && $goodsDB->getField("sign_code = '{$sign}' and id <> {$id}", 'id'))
        {
            echo 0;
        }
        else
        {
            if($product_id)
            {
                $productsDB = new IModel('products');
                if($sign && $productsDB->getField("sign_code = '{$sign}' and id <> {$product_id}", 'id')){
                    echo 0;
                }
            }
            else
            {
               echo 1; 
            }
        }
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
    
    //咨询详情
    public function refer_edit()
    {
        $id = IFilter::act(IReq::get('id'), 'int');
        if(!$id)
        {
            IError::show('咨询不存在');
        }
        $refer = new IQuery('refer as r');
        $refer->join = 'left join user as u on r.user_id = u.id';
        $refer->where = "r.id = {$id}";   
        $refer->fields = 'u.id as uid,u.username,u.head_ico,r.*';                                        
        $reply = $refer->find();
        
        $referDB = new IQuery('refer as r');
        $referDB->join = 'left join user as u on r.user_id = u.id';
        $referDB->where = "r.p_id LIKE '%,{$id},%'";
        $referDB->order = 'r.p_id asc';
        $referDB->fields = 'u.id as uid,u.username,u.head_ico,r.*';                                        
        $replyList = $referDB->find();
        foreach($replyList as $key => $val)
        {
            if($val['user_id'] == '-1')
            {
                $seller_name = API::run('getSellerInfo',$val['seller_id'],'true_name');              
                $replyList[$key]['username'] = isset($seller_name['true_name']) ? $seller_name['true_name'] : '山城速购';
            }                          
            
            if(!$replyList[$key]['username'])
            {
                $replyList[$key]['username'] = '游客';
            }                                                    
        }                        
        $this->reply = $reply[0];                         
        $this->replyList = $replyList;                            
        $this->redirect('refer_edit',false);
    }

	//咨询回复
	public function refer_reply()
	{                                                
		$rid     = IFilter::act(IReq::get('refer_id'),'int');
        $content = IFilter::act(IReq::get('content'));   
        if(!trim($content, ' '))
        {
            $message = array('status' => 0, 'msg' => '回复内容不能为空');
            echo JSON::encode($message);exit;
        }
        
        $refer = new IModel('refer');
        $data = $refer->getObj('id='.$rid);
        if(!$data)
        {
            $message = array('status' => 0, 'msg' => '系统错误');
            echo JSON::encode($message);exit;
        }
        $admin_id = $this->admin['admin_id'];//管理员id
        unset($data['id']);    
        $data['question'] = $content;
        $data['pid'] = $rid;
        $data['admin_id'] = $admin_id;
        $data['p_id'] = $data['p_id'].$rid.',';
        $data['status'] = 1;
        $data['user_id'] = -1;
        $data['seller_id'] = $this->seller['seller_id'];
        $data['time'] = ITime::getDateTime();

        $refer->setData($data);
        $res = $refer->add();
        if($res)
        {
            $refer->setData(array('reply_time'=>ITime::getDateTime(), 'status'=>1));
            $refer->update('id='.$rid, array('id'));
            $this->redirect('refer_list');
        }        
        
	}
    
    //删除咨询
    function refer_del()
    {
        $refer_ids = IReq::get('id');
        $refer_ids = is_array($refer_ids) ? $refer_ids : array($refer_ids);
        if($refer_ids)
        {
            $refer_ids = IFilter::act($refer_ids,'int');
            $ids = implode(',',$refer_ids);
            if($ids)
            {
                $tb_refer = new IModel('refer');
                $where = "id in (".$ids.")";
                $tb_refer->del($where);
            }
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
		$deliver = new deliver();
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
		$strTable .= '<td style="text-align:center;font-size:12px;" width="100">配送状态</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="100">用户名</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">付款时间</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">配送日期</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">配送时间</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">接单状态</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品信息</td>';
		$strTable .= '</tr>';


		foreach($orderList as $k=>$val){
			$deliverStatus= $deliver->getDeliverStatus($val['id']);
			$deliverStatusText = '';
			if($deliverStatus==4){
				$deliverStatusText = '已送达';
			}
			elseif($deliverStatus==1||$deliverStatus==2){
				$deliverStatusText = '已接单';
			}
			else
				$deliverStatusText = '未接单';
			$strTable .= '<tr>';
			$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['order_no'].'</td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.Order_Class::getOrderDistributionStatusText($val).' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['accept_name'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">&nbsp;'.Order_Class::getOrderPayStatusText($val).' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_time'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['deli_day'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['deli_time'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$deliverStatusText.' </td>';

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

		//获取资质图片
		$paperObj = new IModel('seller_paper');
		$this->papers = $paperObj->query('seller_id='.$seller_id);


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
		$store_num_warning         = IFilter::act(IReq::get('store_num_warning'),'int');
		$freight_collect = IFilter::act(IReq::get('freight_collect'),'int');
		$goods_cat = IFilter::act(IReq::get('goods_cat'));
		$goods_cat = !empty($goods_cat) ? implode(',',$goods_cat) : '';
		$paper_img = IFilter::act(IReq::get('img'));
		$paper_imgs = IFilter::act(IReq::get('_imgList'));
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
			'store_num_warning'      => $store_num_warning,
			'freight_collect'=>$freight_collect,
			'goods_cat' => $goods_cat,
			'paper_img' => $paper_img,

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

		//处理多张资质图片
		if($paper_imgs){
			$paperObj = new IModel("seller_paper");
			$paperObj->del("seller_id = ".$seller_id);
			$paper_imgs = explode(',',$paper_imgs);
			foreach($paper_imgs as $val){
				$paperObj->setData(array('seller_id' => $seller_id,'img' => $val));
				$paperObj->add();
			}
		}

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
        
        $regimentObj = new IModel('regiment');
		$dataArray = array(
			'id'        	=> $id,
			'title'     	=> IFilter::act(IReq::get('title','post')),
            'type'          => IFilter::act(IReq::get('type','post'),'int'), 
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
        if($dataArray['type']==2)
        {
            $dataArray['start_time'] = IFilter::act(IReq::get('start_time1','post'));
            $dataArray['end_time'] = IFilter::act(IReq::get('end_time1','post'));
            if(!$dataArray['is_close'])
            {
                if($regimentObj->getObj('type=2 and id <> '.$id.' and is_close=0 and end_time>"'.$dataArray['start_time'].'" and start_time < "'.$dataArray['start_time'].'"') || $regimentObj->getObj('type=2 and id <> '.$id.' and is_close=0 and start_time<"'.$dataArray['end_time'].'" and end_time > "'.$dataArray['end_time'].'"'))
                {
                    $this->regimentRow = $dataArray;
                    $this->redirect('regiment_edit',false);
                    Util::showMessage('该时间段已有整点团购，不能开启新的团购');
                }
            }
        }
        else
        {
            $dataArray['start_time'] = IFilter::act(IReq::get('start_time','post'));
            $dataArray['end_time'] = IFilter::act(IReq::get('end_time','post'));
        }
        if($dataArray['end_time'] <= $dataArray['start_time'])
        {
            $this->regimentRow = $dataArray;
            $this->redirect('regiment_edit',false);
            Util::showMessage('结束时间不能早于开始时间');
        }
        $goods_store_nums = IFilter::act(IReq::get('goods_store_nums', 'post'), 'int');
        if($dataArray['store_nums'] > $goods_store_nums)
        {
            $this->regimentRow = $dataArray;
            $this->redirect('regiment_edit',false);
            Util::showMessage('团购库存量不能大于商品库存量'); 
        }
        if($dataArray['limit_max_count'] <> 0 && $dataArray['limit_max_count'] > $dataArray['store_nums'])
        {
            $this->regimentRow = $dataArray;
            $this->redirect('regiment_edit',false);
            Util::showMessage('团购最大量不能大于团购库存量');
        }
        if($dataArray['limit_max_count'] <> 0 && $dataArray['limit_max_count'] < $dataArray['limit_min_count'])
        {
            $this->regimentRow = $dataArray;
            $this->redirect('regiment_edit',false);
            Util::showMessage('团购最小量不能大于团购最大量');
        }
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
				$countData['orderAmountPrice'] = $countData['orderAmountPrice'] - $countData['deliveryPrice'];
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
			$countData['orderAmountPrice'] = $countData['orderAmountPrice'] - $countData['deliveryPrice'];
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
		$comment = new IQuery('comment as c');
        $comment->join = 'left join goods as goods on c.goods_id = goods.id left join user as u on c.user_id = u.id';
        $comment->where = "c.id = {$cid}";   
        $comment->fields = 'u.id as uid,u.username,u.head_ico,c.*,goods.name';                                        
        $commentInfo = $comment->find();
        
        //查询评论图片
        $photo = new IModel('comment_photo');
        $commentInfo[0]['photo'] = $photo->query('comment_id='.$cid, 'img', 'sort', 'desc');
        
        //追评 
        $comment_DB = new IModel('comment');
        $temp = $comment_DB->getObj("id='{$commentInfo[0]['recontents']}'");        
        if($temp)
        {
            $commentInfo[0]['replySelf'] = $temp;
            $commentInfo[0]['replySelfPhoto'] = $photo->query('comment_id='.$temp['id'], 'img', 'sort', 'desc');
        }         

		$query = new IQuery("comment as c");
        $query->join = "left join user as u on c.user_id = u.id";
        $query->fields = "c.*,u.username";
        $query->where = "c.p_id LIKE '%,{$cid},%'";
        $commentList = $query->find();
        foreach($commentList as $key => $val)
        {
            if($val['user_id'] == '-1')
            {
                $seller_name = API::run('getSellerInfo',$val['sellerid'],'true_name');              
                $commentList[$key]['username'] = isset($seller_name['true_name']) ? $seller_name['true_name'] : '山城速购';
            }                          
            
            if(!$commentList[$key]['username'])
            {
                $commentList[$key]['username'] = '游客';
            }                                                    
        }                        
        $this->comment = $commentInfo[0];                         
        $this->commentList = $commentList;
        $this->redirect('comment_edit');
	}

	/**
	 * @brief 回复评论
	 */
	function comment_update()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$recontent = IFilter::act(IReq::get('recontents'));
        if(!trim($recontent, ' '))
        {   
            $message = array('status' => 0, 'msg' => '回复不能为空');            
            echo JSON::encode($message);exit;
        }

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
		}
        $comment = new IModel('comment');
        $data = $comment->getObj('id='.$id);
        if(!$data)
        {
            $message = array('status' => 0, 'msg' => '系统错误');
            echo JSON::encode($message);exit;
        }
        unset($data['id']);
        $data['pid'] = $id;
        $data['p_id'] = $data['p_id'].$id.',';
        $data['contents'] = $recontent;
        
        $data['status'] = 1;
        $data['user_id'] = -1;
        $data['point'] = 0;
        $data['sellerid'] = $this->seller['seller_id'];
        $data['comment_time'] = ITime::getDateTime();

        $comment->setData($data);
        $res = $comment->add(); 
        if($res)
        {
            $comment->setData(array('recomment_time'=>ITime::getDateTime('Y-m-d')));
            $comment->update('id='.$id);
            $this->redirect('comment_list');
        }
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
	 		$tb_refundment->where = 'c.id='.$refundment_id.' and c.seller_id = '.$this->seller['seller_id'];
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
			$tb_refundment->where = 'c.id='.$refundment_id.' and c.seller_id = '.$this->seller['seller_id'];
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
				$val['is_del'] = 3;
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
            $data['area_groupid'] = isset($data['area_groupid']) ? unserialize($data['area_groupid']) : '' ;
            $area = array();
            if( $data['area_groupid']){
                    
                foreach($data['area_groupid'] as $key=>$val){
                    $tem_arr = explode(';',$val);
                    foreach($tem_arr as $v){
                        if($v!='')
                            $area[$v] = area::getNameStr($v);
                    }
                }
            }
            $this->area = $area;
		}
		else
		{
			die('配送方式不存在');
		}

		$this->data_info = $data;
        $this->redirect('delivery_edit');
	}

	/**
	 * 配送方式修改
	 */
    public function delivery_update()
    {
        //计量方式
        $deli_type   = IFilter::act(IReq::get('deli_type'),'int');
        if($deli_type == 1)
        {
            //首重重量
            $first_weight = IFilter::act(IReq::get('first_weight'),'float');
            //续重重量
            $second_weight = IFilter::act(IReq::get('second_weight'),'float');
            //首重价格
            $first_price = IFilter::act(IReq::get('first_price'),'float');
            //续重价格
            $second_price = IFilter::act(IReq::get('second_price'),'float');
        }
        else
        {
            //首重重量
            $first_weight = IFilter::act(IReq::get('first_num'),'float');
            //续重重量
            $second_weight = IFilter::act(IReq::get('second_num'),'float');
            //首重价格
            $first_price = IFilter::act(IReq::get('first_num_price'),'float');
            //续重价格
            $second_price = IFilter::act(IReq::get('second_num_price'),'float');
        }
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
            'deli_type'    => $deli_type,
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
		$id = IReq::get('id');
        if($id)
        {
            $seller = new IModel('admin_seller');
            if($seller->getObj('id='.$id.' AND password = "'.md5($old_pass).'"')){
                $seller->setData(array('password'=>md5($new_pass)));
                $seller->update('id='.$id);
                $this->redirect('index');
            }else $errorMsg = '原密码不正确！';
        }
		else
        {
            $seller = new IModel('seller');
            $sellerid = $this->seller['seller_id'];
            if($seller->getObj('id='.$sellerid.' AND password = "'.md5($old_pass).'"')){
                $seller->setData(array('password'=>md5($new_pass)));
                $seller->update('id='.$sellerid);
                $this->redirect('seller_edit');
            }else $errorMsg = '原密码不正确！';
        }
		
			
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
		$fapiao_db->where = 'f.seller_id ='. $seller_id.' AND f.status = 0 '.$whereAdd;
		
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
		$fapiao_db->where = 'f.seller_id ='. $seller_id.' AND f.status = 1 '.$whereAdd;
		
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
		$redirect = IFilter::act(IReq::get('redirect'));
		if(!$id || !$money){
			$this->redirect($redirect);
		}
		$db_fa = new IModel('order_fapiao');
		$data=array(
				'money'=>$money,
				'status'=>1
		);
		$db_fa->setData($data);
		$db_fa->update('id='.$id);
		$this->redirect($redirect);
	}
    
    //[促销活动] 添加修改 [单页]
    function pro_rule_edit()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        if($id)
        {
            $promotionObj = new IModel('promotion');
            $where = 'id = '.$id;
            $promotionRow = $promotionObj->getObj($where);
            $promotionRow['area_groupid'] = unserialize($promotionRow['area_groupid']) ;
            $area = array();
            if( $promotionRow['area_groupid']){
                    
                foreach($promotionRow['area_groupid'] as $key=>$val){
                    $tem_arr = explode(';',$val);
                    foreach($tem_arr as $v){
                        if($v!='')
                            $area[$v] = area::getNameStr($v);
                    }
                }
            } 
            $goodsList = array();                             
            if($promotionRow['goods_id'] <> 'all')
            {
                $goods = new IModel('goods');
                $goodsList = $goods->query('id in ('.$promotionRow['goods_id'].')', 'id as goods_id,name,img,goods_no');
            } 
            $this->area = $area;                      
            $this->goodsList = $goodsList;
            $this->promotionRow = $promotionRow;                          
        }
        $this->redirect('pro_rule_edit');
    }

    //[促销活动] 添加修改 [动作]
    function pro_rule_edit_act()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        $award_type = IFilter::act(IReq::get('award_type','post'));
        $promotionObj = new IModel('promotion');

        $group_all    = IReq::get('group_all','post');
        if($group_all == 'all' || empty($group_all))
        {
            $user_group_str = 'all';
        }
        else
        {
            $user_group = IFilter::act(IReq::get('user_group','post'),'int');
            $user_group_str = '';
            if($user_group)
            {
                $user_group_str = join(',',$user_group);
                $user_group_str = ','.$user_group_str.',';
            }
        }                         
        $gId = IReq::get('goods_id');  
        if(IReq::get('select_all') || empty($gId))
        {
            $goods_id = 'all';                           
        }
        else
        {
            $gId = array_unique($gId);
            $goods_id = join(',', $gId);   
        }                                          
        //支持免费配送的地区ID                   
        $area_groupid = $award_type == 6 ? serialize(IReq::get('area_groupid')) : '';
        $dataArray = array(
            'name'          => IFilter::act(IReq::get('name','post')),
            'condition'     => IFilter::act(IReq::get('condition','post')),
            'is_close'      => IFilter::act(IReq::get('is_close','post')),
            'start_time'    => IFilter::act(IReq::get('start_time','post')),
            'end_time'      => IFilter::act(IReq::get('end_time','post')),
            'intro'         => IFilter::act(IReq::get('intro','post'),'text'),
            'award_type'    => $award_type,
			'times_day'     => IFilter::act(IReq::get('times_day','post')),
			'nums_time'     => IFilter::act(IReq::get('nums_time','post')),
            'type'          => 0,
            'user_group'    => $user_group_str,
            'award_value'   => IFilter::act(IReq::get('award_value','post')),
            'seller_id'      => $this->seller['seller_id'],
            'goods_id'      => $goods_id,
            'area_groupid'  => $area_groupid,
        );

        $promotionObj->setData($dataArray);

        if($id)
        {
            $where = 'id = '.$id;
            $promotionObj->update($where);
        }
        else
        {
            $promotionObj->add();
        }
        $this->redirect('pro_rule_list');
    }

    //[促销活动] 删除
    function pro_rule_del()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        if(!empty($id))
        {
            $promotionObj = new IModel('promotion');
            if(is_array($id))
            {
                $idStr = join(',',$id);
                $where = ' id in ('.$idStr.')';
            }
            else
            {
                $where = 'id = '.$id;
            }
            $promotionObj->del($where);
            $this->redirect('pro_rule_list');
        }
        else
        {
            $this->redirect('pro_rule_list',false);
            Util::showMessage('请选择要删除的促销活动');
        }
    }
    
    
    /**
     * @商品组合添加、修改
     */
    function combine_edit()
    {
        $id = IFilter::act(IReq::get('cid'),'int');
        $seller_id = $this->seller['seller_id'];
        if($id)
        {
            $obj = new IModel('combine_goods');
            $this->object = $obj->getObj('id = '.$id);
            if($this->object && $this->object['seller_id'] <> $seller_id)
            {
                $this->combine_list();
            }
            $goods = new IModel('goods');
            $name = $goods->getField('id='.$this->object['goods_id'], 'name');                                        
            $this->goods_name = $name ? $name : '';
            $this->goods_no = $goods->getField('id='.$this->object['goods_id'], 'goods_no');
            
            //获取已选商品列表
            if($this->object['combine'])
            {
                $handle = new IQuery('goods');
                $handle->order    = "sort asc,id desc";
                $handle->fields   = "id,name";
                $handle->where    = "is_del = 0 and id in (".$this->object['combine'].")";
                $this->goods = $handle->find();
            }                                       
        }           
        //获取所有商品列表---过滤已选商品
        $goodsHandle = new IQuery('goods');
        $goodsHandle->order = "sort asc,id desc";
        $goodsHandle->fields = "id,name";
        $where = "is_del = 0 and seller_id=".$seller_id;   
        if($this->object['combine'])
        {
            $where .=  " and id not in (".$this->object['combine'].")";
        }
        $goodsHandle->where =  $where;
        $this->goodsList = $goodsHandle->find();                                                                                       
        $this->redirect('combine_edit');
    }
    
    /**
     * @保存商品组合
     */
    function combine_save()
    {
        //获得post值
        $id = IFilter::act(IReq::get('id'),'int');
        $name = IFilter::act(IReq::get('name'));
        $goods_id = IFilter::act(IReq::get('goods_id'),'int'); 
        $ids = isset($_POST['ids']) ? $_POST['ids'] : '';
        $sort = IFilter::act(IReq::get('sort'),'int');
        $status = IFilter::act(IReq::get('status'),'int', 0);
        $type = IFilter::act(IReq::get('type'),'int', 2);
        $seller_id = $this->seller['seller_id'];
        
        if(!$name)
        {
            $this->combine_list();
            exit;
        }
        $tb = new IModel('combine_goods');
        if($tb->getObj('name = "'.$name.'" and goods_id = '.$goods_id.' and id != '.$id. ' and status = 1'))
        {
            exit('该商品已添加同名组合');
        }      
        $combine = $ids ? implode(',', $ids) : ''; 

        $info = array(
            'name'      => $name,
            'goods_id' => $goods_id,
            'combine' => $combine,
            'sort'      => $sort,
            'status'=> $status,
            'type'=> $type,
            'seller_id'=> $seller_id
        );        
        $tb->setData($info);
        if($id)                                   
        {
            if($seller_id <> $tb->getField('id='.$id, 'seller_id'))
            {
                die('无权限修改');
            }
            $where = "id=".$id;
            $tb->update($where);
        }
        else                                              
        {
            $tb->add();
        }

        $this->redirect('combine_list');
    }
    
    /**
     * @商品组合列表
     */
    function combine_list()
    {                             
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $QB = new IQuery('combine_goods');
        $QB->order    = "sort asc,id desc";
        $QB->where = 'seller_id='.$this->seller['seller_id'];
        $QB->page   = $page;
        $this->QB = $QB;
        $this->redirect('combine_list');
    }
    
    /**
     * @商品组合排序
     */
    public function combine_sort()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        $sort = IFilter::act(IReq::get('sort'),'int');
        $flag = 0;
        if($id)
        {
            $tb = new IModel('combine_goods');
            $info = $tb->getObj('id='.$id);
            if(count($info)>0)
            {
                if($info['sort']!=$sort)
                {
                    $tb->setData(array('sort'=>$sort));
                    if($tb->update('id='.$id))
                    {
                        $flag = 1;
                    }
                }
            }
        }
        echo $flag;
    }
    
    /**
     * @删除商品组合
     */
    function combine_del()
    {
        //post数据
        $id = IFilter::act(IReq::get('cid'),'int');
                          
        $tb = new IModel('combine_goods');
        if($id)
        {
            if($this->seller['seller_id'] <> $tb->getField('id='.$id, 'seller_id'))
            {
                die('无权限删除');
            }
            $tb->del(Util::joinStr($id));
        }
        else
        {
            die('请选择要删除的数据');
        }
        $this->redirect("combine_list");
    }
    
    //[权限管理][角色] 删除
    function role_del()
    {
        $id = IFilter::act( IReq::get('id'),'int' );

        if(!empty($id))
        {
            $obj   = new IModel('admin_role');
            $obj->del('id='.$id.' and seller_id='.$this->seller['seller_id']);
            $this->redirect('role_list');
        }
        else
        {
            $this->redirect('role_list',false);
            Util::showMessage('请选择要操作的角色ID');
        }
    }

    //[权限管理][角色] 角色修改,添加 [单页]
    function role_edit()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        if($id)
        {
            $adminObj = new IModel('admin_role');
            $where = 'id = '.$id.' and seller_id = '.$this->seller['seller_id'];
            $this->roleRow = $adminObj->getObj($where);
            if(!$this->roleRow)
            {
                die('无权限修改');
            }
        }
        
        //获取权限码分组形势
        $rightObj  = new IModel('right_seller');
        $rightData = $rightObj->query('is_del = 0','*','name','asc');

        $rightArray     = array();
        $rightUndefined = array();
        foreach($rightData as $key => $item)
        {
            preg_match('/\[.*?\]/',$item['name'],$localPre);
            if(isset($localPre[0]))
            {
                $arrayKey = trim($localPre[0],'[]');
                $rightArray[$arrayKey][] = $item;
            }
            else
            {
                $rightUndefined[]      = $item;
            }
        }

        $this->rightArray     = $rightArray;
        $this->rightUndefined = $rightUndefined;
        $this->redirect('role_edit');
    }

    //[权限管理][角色] 角色修改,添加 [动作]
    function role_edit_act()
    {
        $id = IFilter::act( IReq::get('id','post') );
        $roleObj = new IModel('admin_role');
        $seller_id = $this->seller['seller_id'];
        //要入库的数据
        $dataArray = array(
            'id'     => $id,
            'name'   => IFilter::string( IReq::get('name','post') ),
            'is_del'   => IFilter::act( IReq::get('is_del','post'), 'int'),
            'seller_id'=> $seller_id,
            'rights' => null,
        );
        
        //检查是否是该商家的角色
        if($id)
        {
            if(!$roleObj->getObj('id='.$id.' and seller_id='.$seller_id, 'id'))
            {
                $this->roleRow = $dataArray;
                $this->redirect('role_edit',false);
                Util::showMessage('无权限修改');
            }
        }
        
        //检查权限码是否为空
        $rights = IFilter::act( IReq::get('right','post') );
        if(empty($rights) || $rights[0]=='')
        {
            $this->roleRow = $dataArray;
            $this->redirect('role_edit',false);
            Util::showMessage('请选择要分配的权限');
        }

        //拼接权限码
        $rightsArray = array();
        $rightObj    = new IModel('right_seller');
        $rightList   = $rightObj->query('id in ('.join(",",$rights).')','`right`');
        foreach($rightList as $key => $val)
        {
            $rightsArray[] = trim($val['right'],',');
        }

        $dataArray['rights'] = empty($rightsArray) ? '' : ','.join(',',$rightsArray).',';
        $roleObj->setData($dataArray);
        if($id)
        {
            $where = 'id = '.$id;
            $roleObj->update($where);
        }
        else
        {
            $roleObj->add();
        }
        $this->redirect('role_list');
    }
    
    //[权限管理][管理员]管理员添加，修改[单页]
    function admin_edit()
    {
        $id =IFilter::act( IReq::get('id'),'int' );
        if($id)
        {
            $adminObj = new IModel('admin_seller');
            $where = 'id = '.$id.' and seller_id='.$this->seller['seller_id'];
            $this->adminRow = $adminObj->getObj($where);
            if(!$this->adminRow)
            {
                die('无权限操作该管理员');
            }
        }
        $this->redirect('admin_edit');
    }
    
    //[权限管理][管理员]检查admin_user唯一性
    function check_admin($name = null,$id = null)
    {
        //php校验$name!=null , ajax校验 $name == null
        $admin_name = ($name==null) ? IReq::get('admin_name','post') : $name;
        $admin_id   = ($id==null)   ? IReq::get('admin_id','post')   : $id;
        $admin_name = IFilter::act($admin_name);
        $admin_id = intval($id);
        $seller_id = $this->seller['seller_id'];

        $adminObj = new IModel('admin_seller');
        $where = 'seller_id = '.$seller_id;
        if($admin_id)
        {
            $where .= ' and admin_name = "'.$admin_name.'" and id != '.$admin_id;
        }
        else
        {
            $where .= ' and admin_name = "'.$admin_name.'"';
        }

        $adminRow = $adminObj->getObj($where);

        if(!empty($adminRow))
        {
            if($name != null)
            {
                return false;
            }
            else
            {
                echo '-1';
            }
        }
        else
        {
            if($name != null)
            {
                return true;
            }
            else
            {
                echo '1';
            }
        }
    }

    //[权限管理][管理员]管理员添加，修改[动作]
    function admin_edit_act()
    {
        $id = IFilter::act( IReq::get('id','post') );
        $seller_id = $this->seller['seller_id'];
        $adminObj = new IModel('admin_seller');

        //错误信息
        $message = null;

        $dataArray = array(
            'id'         => $id,
            'admin_name' => IFilter::string( IReq::get('admin_name','post') ),
            'role_id'    => IFilter::act( IReq::get('role_id','post') ),
            'email'      => IFilter::string( IReq::get('email','post') ),
            'is_del'     => IFilter::act( IReq::get('is_del','post'),'int' ),
            'seller_id'  => $seller_id
        );

        //检查管理员name唯一性
        $isPass = $this->check_admin($dataArray['admin_name'],$id);
        if($isPass == false)
        {
            $message = $dataArray['admin_name'].'管理员已经存在,请更改名字';
        }

        //提取密码 [ 密码设置 ]
        $password   = IReq::get('password','post');
        $repassword = IReq::get('repassword','post');

        //修改操作
        if($id)
        {
            if($password != null || $repassword != null)
            {
                if($password == null || $repassword == null || $password != $repassword)
                {
                    $message = '密码不能为空,并且二次输入的必须一致';
                }
                else
                    $dataArray['password'] = md5($password);
            }

            //有错误
            if($message != null)
            {
                $this->adminRow = $dataArray;
                $this->redirect('admin_edit',false);
                Util::showMessage($message);
            }
            else
            {
                $where = 'id = '.$id;
                $adminObj->setData($dataArray);
                $adminObj->update($where);

                //修改为自身密码时
                if($id == $this->admin['admin_id'])
                {
                    //同步更新safe
                    ISafe::set('admin_name',$dataArray['admin_name']);
                    if(isset($dataArray['password'])){
                        ISafe::set('admin_pwd',$dataArray['password']);
                    }
                
                }
            }
        }
        //添加操作
        else
        {
            if($password == null || $repassword == null || $password != $repassword)
            {
                $message = '密码不能为空,并且二次输入的必须一致';
            }
            else
                $dataArray['password'] = md5($password);

            if($message != null)
            {
                $this->adminRow = $dataArray;
                $this->redirect('admin_edit',false);
                Util::showMessage($message);
            }
            else
            {
                $dataArray['create_time'] = ITime::getDateTime();
                $adminObj->setData($dataArray);
                $adminObj->add();
            }
        }
        $this->redirect('seller_list');
    }

    //[权限管理][管理员]删除
    function admin_del()
    {
        $id = IFilter::act( IReq::get('id') ,'int' );

        if(!empty($id))
        {
            $obj   = new IModel('admin_seller');
            $where = 'id='.$id.' and seller_id='.$this->seller['seller_id'];

            $obj->del($where);
            $this->redirect('seller_list');
        }
        else
        {
            $this->redirect('seller_list',false);
            Util::showMessage('请选择要操作的管理员ID');
        }
    }
    
    //修改代金券状态is_close和is_send
    function ticket_status()
    {
        $status    = IFilter::act(IReq::get('status'));
        $id        = IFilter::act(IReq::get('id'),'int');
        $ticket_id = IFilter::act(IReq::get('ticket_id'));

        if(!empty($id) && $status != null && $ticket_id != null)
        {
            $ticketObj = new IModel('prop');
            if(is_array($id))
            {
                foreach($id as $val)
                {
                    $where = 'id = '.$val;
                    $ticketRow = $ticketObj->getObj($where,$status);
                    if($ticketRow[$status]==1)
                    {
                        $ticketObj->setData(array($status => 0));
                    }
                    else
                    {
                        $ticketObj->setData(array($status => 1));
                    }
                    $ticketObj->update($where);
                }
            }
            else
            {
                $where = 'id = '.$id;
                $ticketRow = $ticketObj->getObj($where,$status);
                if($ticketRow[$status]==1)
                {
                    $ticketObj->setData(array($status => 0));
                }
                else
                {
                    $ticketObj->setData(array($status => 1));
                }
                $ticketObj->update($where);
            }
            $this->redirect('ticket_more_list/ticket_id/'.$ticket_id);
        }
        else
        {
            $this->ticket_id = $ticket_id;
            $this->redirect('ticket_more_list',false);
            Util::showMessage('请选择要修改的id值');
        }
    }

    //[代金券]获取代金券数量
    function getTicketCount($propObj,$id)
    {
        $where     = '`condition` = "'.$id.'"';
        $propCount = $propObj->getObj($where,'count(*) as count');
        return $propCount['count'];
    }

    //[代金券]添加,修改[单页]
    function ticket_edit()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        if($id)
        {
            $ticketObj       = new IModel('ticket');
            $where           = 'id = '.$id;
            $this->ticketRow = $ticketObj->getObj($where);
        }
        $this->redirect('ticket_edit');
    }

    //[代金券]添加,修改[动作]
    function ticket_edit_act()
    {
        $id        = IFilter::act(IReq::get('id'),'int');
        $ticketObj = new IModel('ticket');

        $dataArray = array(
            'name'      => IFilter::act(IReq::get('name','post')),
            'value'     => IFilter::act(IReq::get('value','post')),
            'start_time'=> IFilter::act(IReq::get('start_time','post')),
            'end_time'  => IFilter::act(IReq::get('end_time','post')),
            'point'     => IFilter::act(IReq::get('point','post')),
            'type'     => IFilter::act(IReq::get('type','post'), 'int'),
            'condition'     => IReq::get('condition','post') ? IFilter::act(IReq::get('condition','post'), 'int') : 0,
        );
        if(IFilter::act(IReq::get('start_time','post')) >= IFilter::act(IReq::get('end_time','post')))
        {
            die('请填写正确的时间');       
        }
        if($id)
        {
            if($ticketObj->getField('id = '.$id, 'seller_id') <> $this->seller['seller_id'])
            {
                die('无权限操作');
            }
            $where = 'id = '.$id;
            $ticketObj->setData($dataArray);
            $ticketObj->update($where);
        }
        else
        {
            $dataArray['seller_id'] = $this->seller['seller_id'];
            $ticketObj->setData($dataArray);
            $ticketObj->add();
        }
        $this->redirect('ticket_list');
    }

    //[代金券]生成[动作]
    function ticket_create()
    {
        $propObj   = new IModel('prop');
        $prop_num  = intval(IReq::get('num'));
        $ticket_id = intval(IReq::get('ticket_id'));

        if($prop_num && $ticket_id)
        {
            $prop_num  = ($prop_num > 5000) ? 5000 : $prop_num;
            $ticketObj = new IModel('ticket');
            $where     = 'id = '.$ticket_id;
            $ticketRow = $ticketObj->getObj($where);

            if($ticketRow['seller_id'] <> $this->seller['seller_id'])
            {
                die('无权限操作');
            }
            for($item = 0; $item < intval($prop_num); $item++)
            {
                $dataArray = array(
                    'condition' => $ticket_id,
                    'name'      => $ticketRow['name'],
                    'card_name' => 'T'.IHash::random(8),
                    'card_pwd'  => IHash::random(8),
                    'value'     => $ticketRow['value'],
                    'ticket_condition' => $ticketRow['condition'],
                    'start_time'=> $ticketRow['start_time'],
                    'end_time'  => $ticketRow['end_time'],
                    'seller_id'  => $ticketRow['seller_id'],
                );

                //判断code码唯一性
                $where = 'card_name = \''.$dataArray['card_name'].'\'';
                $isSet = $propObj->getObj($where);
                if(!empty($isSet))
                {
                    $item--;
                    continue;
                }
                $propObj->setData($dataArray);
                $propObj->add();
            }
            $logObj = new Log('db');
            $logObj->write('operation',array("管理员:".$this->seller['seller_name'],"生成了代金券","面值：".$ticketRow['value']."元，数量：".$prop_num."张"));
        }
        $this->redirect('ticket_list');
    }

    //[代金券]删除
    function ticket_del()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        if(!empty($id))
        {
            $ticketObj = new IModel('ticket');
            $propObj   = new IModel('prop');
            $propRow   = $propObj->getObj(" `type` = 0 and `condition` = {$id} and (now() between start_time and end_time) and (is_close = 2 or (is_userd = 0 and is_send = 1)) ");

            if($propRow)
            {
                $this->redirect('ticket_list',false);
                Util::showMessage('无法删除代金券，其下还有正在使用的代金券');
                exit;
            }
            $where = "id = {$id} ";
            $ticketRow = $ticketObj->getObj($where);
            if($ticketRow['seller_id'] <> $this->seller['seller_id'])
            {
                $this->redirect('ticket_list',false);
                Util::showMessage('无权限删除代金券');
                exit;
            }
            if($ticketObj->del($where))
            {
                $where = " `type` = 0 and `condition` = {$id} ";
                $propObj->del($where);

                $logObj = new Log('db');
                $logObj->write('operation',array("管理员:".$this->seller['seller_name'],"删除了一种代金券","代金券名称：".$ticketRow['name']));
            }
            $this->redirect('ticket_list');
        }
        else
        {
            $this->redirect('ticket_list',false);
            Util::showMessage('请选择要删除的id值');
        }
    }

    //[代金券详细]删除
    function ticket_more_del()
    {
        $id        = IFilter::act(IReq::get('id'),'int');
        $ticket_id = IFilter::act(IReq::get('ticket_id'),'int');
        if($id)
        {
            $ticketObj = new IModel('ticket');
            $ticketRow = $ticketObj->getObj('id = '.$ticket_id);
            if($ticketRow['seller_id'] <> $this->seller['seller_id'])
            {
                $this->redirect('ticket_more_list/ticket_id/'.$ticket_id,false);
                Util::showMessage('无权限删除代金券');
                exit;
            }
            $logObj    = new Log('db');
            $propObj   = new IModel('prop');
            if(is_array($id))
            {
                $idStr = join(',',$id);
                $where = ' id in ('.$idStr.')';
                $logObj->write('operation',array("管理员:".$this->seller['seller_name'],"批量删除了实体代金券","代金券名称：".$ticketRow['name']."，数量：".count($id)));
            }
            else
            {
                $where = 'id = '.$id;
                $logObj->write('operation',array("管理员:".$this->seller['seller_name'],"删除了1张实体代金券","代金券名称：".$ticketRow['name']));
            }
            $propObj->del($where);
            $this->redirect('ticket_more_list/ticket_id/'.$ticket_id);
        }
        else
        {
            $this->ticket_id = $ticket_id;
            $this->redirect('ticket_more_list',false);
            Util::showMessage('请选择要删除的id值');
        }
    }

    //[代金券详细] 列表
    function ticket_more_list()
    {
        $this->ticket_id = IFilter::act(IReq::get('ticket_id'),'int');
        $this->redirect('ticket_more_list');
    }

    //[代金券] 输出excel表格
    function ticket_excel()
    {
        //代金券excel表存放地址
        $fileName = $this->ticketDir.'/'.$this->seller['seller_id'].'/t'.date('YmdHis').IHash::random(8).'.xls';
        $ticket_id = IFilter::act(IReq::get('id'));
        $ticket_id_array = array();

        if(!empty($ticket_id))
        {
            $excelStr = '<table><tr><th>名称</th><th>卡号</th><th>密码</th><th>面值</th>
            <th>已被使用</th><th>是否关闭</th><th>是否发送</th><th>开始时间</th><th>结束时间</th><th>商家</th></tr>';

            $propObj = new IModel('prop');
            $where   = 'type = 0';
            if(is_array($ticket_id))
            {
                $ticket_id_array = $ticket_id;
            }
            else
            {
                $ticket_id_array[] = $ticket_id;
            }

            //当代金券数量没有时不允许备份excel
            foreach($ticket_id_array as $key => $tid)
            {
                if($this->getTicketCount($propObj,$tid) == 0)
                {
                    unset($ticket_id_array[$key]);
                }
            }

            if($ticket_id_array)
            {
                $id_num_str = join('","',$ticket_id_array);
            }
            else
            {
                $this->redirect('ticket_list',false);
                Util::showMessage('实体代金券数量为0张，无法备份');
                exit;
            }

            $where.= ' and `condition` in("'.$id_num_str.'")';

            $propList = $propObj->query($where,'*','`condition`','asc','10000');
            $seller = new IModel('seller');
            foreach($propList as $key => $val)
            {
                if($val['seller_id'] == $this->seller['seller_id'])
                {
                    if($this->seller['admin_role_seller_name'] == '商家')
                    {
                        $seller_name = $this->seller['seller_name'];
                    }
                    else
                    {
                        $seller_name = $seller->getField('id='.$this->seller['seller_id'], 'seller_name');
                    }
                    $is_userd = ($val['is_userd']=='1') ? '是':'否';
                    $is_close = ($val['is_close']=='1') ? '是':'否';
                    $is_send  = ($val['is_send']=='1') ? '是':'否';

                    $excelStr.='<tr>';
                    $excelStr.='<td>'.$val['name'].'</td>';
                    $excelStr.='<td>'.$val['card_name'].'</td>';
                    $excelStr.='<td>'.$val['card_pwd'].'</td>';
                    $excelStr.='<td>'.$val['value'].' 元</td>';
                    $excelStr.='<td>'.$is_userd.'</td>';
                    $excelStr.='<td>'.$is_close.'</td>';
                    $excelStr.='<td>'.$is_send.'</td>';
                    $excelStr.='<td>'.$val['start_time'].'</td>';
                    $excelStr.='<td>'.$val['end_time'].'</td>';
                    $excelStr.='<td>'.$seller_name.'</td>';
                    $excelStr.='</tr>';
                }
                else
                {
                    continue;
                }
            }
            $excelStr.='</table>';

            $fileObj = new IFile($fileName,'w+');
            $fileObj->write($excelStr);
            $this->ticket_excel_list();
        }
        else
        {
            $this->redirect('ticket_list',false);
            Util::showMessage('请选择要操作的文件');
        }
    }

    //[代金券] 展示excel文件
    function ticket_excel_list()
    {
        IFile::mkdir($this->ticketDir.'/'.$this->seller['seller_id']);

        $dirArray = array();
        $dirRes   = opendir($this->ticketDir.'/'.$this->seller['seller_id']);
        while($fileName = readdir($dirRes))
        {
            if(!in_array($fileName,IFile::$except))
            {
                $dirArray[$fileName]['name'] = $fileName;
                $dirArray[$fileName]['size'] = filesize($this->ticketDir.'/'.$this->seller['seller_id'].'/'.$fileName);
                $dirArray[$fileName]['time'] = date('Y-m-d',fileatime($this->ticketDir.'/'.$this->seller['seller_id'].'/'.$fileName));
            }
        }
        $this->dirArray = $dirArray;
        $this->redirect('ticket_excel_list',false);
    }

    //[代金券] excel文件删除
    function ticket_excel_del()
    {
        $id = IFilter::act(IReq::get('id'));
        if($id)
        {
            if(is_array($id))
            {
                foreach($id as $val)
                {
                    IFile::unlink($this->ticketDir.'/'.$this->seller['seller_id'].'/'.$val);
                }
            }
            else
            {
                IFile::unlink($this->ticketDir.'/'.$this->seller['seller_id'].'/'.$id);
            }
            $this->ticket_excel_list();
        }
        else
        {
            $this->ticket_excel_list();
            Util::showMessage('请选择要删除的文件');
        }
    }

    //[代金券] excel文件下载
    function ticket_excel_download($fileName = null)
    {
        if($fileName==null)
        {
            $file = IFilter::act(IReq::get('file'),'filename');
        }
        else
        {
            $file = $fileName;
        }

        if($file != null)
        {
            ob_end_clean();
            header('Content-Description: File Transfer');
            header('Content-Length: '.filesize($this->ticketDir.'/'.$this->seller['seller_id'].'/'.$file));
            header('Content-Disposition: attachment; filename='.basename($file));
            readfile($this->ticketDir.'/'.$this->seller['seller_id'].'/'.$file);
            flush();
            ob_flush();
        }
    }

    //[代金券] excel打包
    function ticket_excel_pack()
    {
        if(class_exists('ZipArchive'))
        {
            //获取要打包的文件数组
            $fileArray = IFilter::act(IReq::get('id'));
            if(!empty($fileArray))
            {
                $fileName = 'T_'.date('YmdHis').IHash::random(8).'.zip';
                $zip = new ZipArchive();
                $zip->open($this->ticketDir.'/'.$this->seller['seller_id'].'/'.$fileName,ZIPARCHIVE::CREATE);
                foreach($fileArray as $file)
                {
                    $attachfile = $this->ticketDir.'/'.$this->seller['seller_id'].'/'.$file;
                    $zip->addFile($attachfile,basename($attachfile));
                }
                $zip->close();
                $this->ticket_excel_download($fileName);
                @unlink($this->ticketDir.'/'.$this->seller['seller_id'].'/'.$fileName);
            }
            else
            {
                $this->ticket_excel_list();
                Util::showMessage('请选择要打包的文件');
            }
        }
        else
        {
            $this->ticket_excel_list();
            Util::showMessage('您的php环境没有打包工具类库');
        }
    }

    //[代金券]获取代金券数据
    function getTicketList()
    {
        $ticketObj  = new IModel('ticket');
        $ticketList = $ticketObj->query('seller_id = '.$this->seller['seller_id']);
        foreach($ticketList as $key=>$v){
            $ticketList[$key]['expire'] = 0;
            if(time()>strtotime($v['end_time'])){
                $ticketList[$key]['expire'] = 1;
            }
        }
        echo JSON::encode($ticketList);
    }

	function xiaopiao1(){
		$this->layout='';
		$order_id = IFilter::act( IReq::get('id'),'int' );
		$seller_id = $this->seller['seller_id'];
		$type     = IFilter::act(IReq::get('type'));

		$tb_order =  new IModel('order');
		$where = $type ? ' and type=4' : ' and type !=4';
		$data     = $tb_order->getObj('id='.$order_id.$where);

		if($seller_id)
		{
			$sellerObj   = new IModel('seller');
			$config_info = $sellerObj->getObj('id = '.$seller_id);

			$data['set']['name']   = isset($config_info['true_name'])? $config_info['true_name'] : '';
			$data['set']['phone']  = isset($config_info['phone'])    ? $config_info['phone']     : '';
			$data['set']['email']  = isset($config_info['email'])    ? $config_info['email']     : '';
			$data['set']['url']    = isset($config_info['home_url']) ? $config_info['home_url']  : '';
		}
		else
		{
			$config = new Config("site_config");
			$config_info = $config->getInfo();

			$data['set']['name']   = isset($config_info['name'])  ? $config_info['name']  : '';
			$data['set']['phone']  = isset($config_info['phone']) ? $config_info['phone'] : '';
			$data['set']['email']  = isset($config_info['email']) ? $config_info['email'] : '';
			$data['set']['url']    = isset($config_info['url'])   ? $config_info['url']   : '';
		}

		$data['address']   = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']))."&nbsp;".$data['address'];
		$data['seller_id'] = $seller_id;
		$this->setRenderData($data);
		$this->redirect('xiaopiao1');
	}

	function xiaopiao2(){
		$this->layout='';
		$this->redirect('xiaopiao2');
	}

	function startWork()
	{
		$order_id = IFilter::act(IReq::get('order_id'));
		$deliver = new deliver();
		$res = $deliver->deliver_work($order_id);
		if($res){
			die(json_encode(array('success'=>1)));
		}
		else{
			die(json_encode(array('success'=>0)));
		}
	}
}