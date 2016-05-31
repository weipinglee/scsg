<?php
/**
 * @file site.php
 * @brief
 * @author 
 * @date 2011-03-22
 * @version 0.6
 * @note
 */
/**
 * @brief Site
 * @class Site
 * @note
 */
class Site extends IController
{
    public $layout='site';

	function init()
	{
		CheckRights::checkUserRights();
	}
    
    /**
    * 第三方登录测试页面
    * 
    */
    function login()
    {
       $this->redirect('login'); 
    }

	function index()
	{
		$siteConfigObj = new Config("site_config");
		$site_config   = $siteConfigObj->getInfo();
		$index_slide = isset($site_config['index_slide'])? unserialize($site_config['index_slide']) :array();
		$this->index_slide = $index_slide;
		//print_r($this->index_slide);exit;
		//获取商品分类
		$categoryList = array();
	
		foreach( Api::run('getCategoryListTop') as $key=>$v){
			$categoryList[$key] = $v;
			$categoryList[$key]['child'] = Api::run('getCategoryByParentid',array('#parent_id#',$v['id']),5);
			$categoryList[$key]['goods'] = Api::run('getCategoryExtendList',array('#categroy_id#',$v['id']),20);
			$categoryList[$key]['seller']= Api::run('getSellerListByCat',array('#cat_id#',$v['id']),10);
			
		}
		
		$this->categoryList = $categoryList;
		$this->isIndex = 1;
		unset($categoryList);
		//获取用户喜好产品
		$uid = $this->user ? $this->user['user_id'] : 0;

		$this->user_like_goods = user_like::get_like_cate($uid,6);
		
		//获取团购商品
		 $tuan = new IQuery('regiment as r');
		$tuan->join = 'left join goods as g on r.goods_id=g.id';
        $tuan->fields = 'r.*';
        $tuan->where = 'r.is_close = 0 AND NOW() between r.start_time and r.end_time and g.is_del=0';
        $tuan->order = 'r.id desc';
        $tuan->limit = 3;
        $this->tuanList = $tuan->find();

		$this->redirect('index');
	}
	//闪购页面
	function shangou(){
		$this->logoUrl = 'images/sglogo.png';
		$this->shangou = 1;
		
		$this->shan_list = Api::run('getPromotionList',4);
		$this->count = count($this->shan_list);
		$this->redirect('shangou');
	}
	
	//获取更多闪购信息，返回json
	public function getMoreShan(){
		$start = IFilter::act(IReq::get('start'),'int');
		$limit = $start.',2';
		
		$prom = new IQuery('promotion as p');
		$prom->join = 'left join goods as go on go.id = p.condition';
		$prom->fields = 'p.end_time,p.shan_img,go.img as img,go.name as goods_name,go.sell_price,p.name as name,p.award_value as award_value,go.id as goods_id,p.id as p_id';
		$prom->where = 'p.type = 1 and p.is_close = 0 and go.is_del = 0 and NOW() between start_time and end_time AND go.id is not null';
		$prom->order = 'p_id desc';
		$prom->limit = $limit;
		$promData = $prom->find();
		
		foreach($promData as $key=>$val){
			$promData[$key]['key'] = $key + $start;
			$promData[$key]['end'] = strtotime($val['end_time']);
			$promData[$key]['zhe'] = 10*round($val['award_value']/$val['sell_price'],2);
		}
		echo $promData ? JSON::encode($promData) : 0;
	}
	//团购页面
	public function tuangou(){
		$this->logoUrl = 'images/tglogo.png';
		$this->tuangou = 1;
		$this->todayList = array();
		$this->brandList = array();
		 $tuan = new IQuery('regiment as r');
		$tuan->join = 'left join goods as g on r.goods_id=g.id';
        $tuan->fields = 'r.*';
        $tuan->where = 'r.is_close = 0 AND NOW() between r.start_time and r.end_time and g.is_del=0';
        $tuan->order = 'r.sort asc';
        $tuan->limit = 11;
        $tuanList = $tuan->find();
		$this->count = count($tuanList);
		if($this->count>2){
			$this->todayList = array(array_shift($tuanList),array_shift($tuanList));
			$this->brandList = $tuanList;
		}else{
			$this->todayList = $tuanList;
		}
		$this->redirect('tuangou');
	}
    //获取更多团购
    public function getMoreTuan(){
        $start = IFilter::act(IReq::get('start'),'int');
        $limit = $start.',6';
        $tuan = new IQuery('regiment as r');
		$tuan->join = 'left join goods as g on r.goods_id=g.id';
        $tuan->fields = 'r.*';
        $tuan->where = 'r.is_close = 0 AND NOW() between r.start_time and r.end_time and g.is_del=0';
        $tuan->order = 'r.sort asc';
        $tuan->limit = $limit;
        $tuanData = $tuan->find();
        
        foreach($tuanData as $key=>$val){
            $tuanData[$key]['key'] = $key + $start;
            $tuanData[$key]['end'] = strtotime($val['end_time']) - time();
        }
        echo $tuanData ? JSON::encode($tuanData) : 0;
        
    }
    //团购详情页面
    public function tuan_product(){
        $this->logoUrl = 'images/tglogo.png';
        $id = IFilter::act(IReq::get('active'),'int');
        if(!$id){
            IError::show(403,"传递的参数不正确");
            exit;
        }
        $tuanData = Api::run('getRegimentRowById',array('#id#',$id));
        
        if(!$tuanData){
            IError::show(403,"团购不存在");
            exit;
        }
        
        $goods_id = $tuanData['goods_id'];
        $product_id = $tuanData['product_id'];
        
        
        if(!$goods_id)
        {
            IError::show(403,"传递的参数不正确");
            exit;
        }
        $user_id = $this->user ? $this->user['user_id'] : 0;
        user_like::set_user_history($goods_id,$user_id);
        
        //使用商品id获得商品信息
        $tb_goods = new IModel('goods');
        $goods_info = $tb_goods->getObj('id='.$goods_id." AND is_del=0");
        
        
        //print_r($goods_info);
        if(!$goods_info)
        {
            IError::show(403,"这件商品不存在或已下架");
            exit;
        }
        
        
        $sell_price = $goods_info['sell_price'];
        //品牌名称
        if($goods_info['brand_id'])
        {
            $tb_brand = new IModel('brand');
            $brand_info = $tb_brand->getObj('id='.$goods_info['brand_id']);
            if($brand_info)
            {
                $goods_info['brand'] = $brand_info['name'];
            }
        }
        
        //获取商品分类
        $categoryObj = new IModel('category_extend as ca,category as c');
        $categoryRow = $categoryObj->getObj('ca.goods_id = '.$goods_id.' and ca.category_id = c.id','c.id,c.name');
        $goods_info['category'] = $categoryRow ? $categoryRow['id'] : 0;
        
        //商品图片
        $tb_goods_photo = new IQuery('goods_photo_relation as g');
        $tb_goods_photo->fields = 'p.id AS photo_id,p.img ';
        $tb_goods_photo->join = 'left join goods_photo as p on p.id=g.photo_id ';
        $tb_goods_photo->where =' g.goods_id='.$goods_id;
        $goods_info['photo'] = $tb_goods_photo->find();
        foreach($goods_info['photo'] as $key => $val)
        {
            //对默认第一张图片位置进行前置
            if($val['img'] == $goods_info['img'])
            {
                $temp = $goods_info['photo'][0];
                $goods_info['photo'][0] = $val;
                $goods_info['photo'][$key] = $temp;
            }
        }
        
        //商品是否参加促销活动(团购，抢购)
        $goods_info['promo']     = 'regiment';//团购类型
        $goods_info['active_id'] = $id;//活动id
        
        
        
        //获得扩展属性
        $tb_attribute_goods = new IQuery('goods_attribute as g');
        $tb_attribute_goods->join  = 'left join attribute as a on a.id=g.attribute_id ';
        $tb_attribute_goods->fields=' a.name,g.attribute_value ';
        $tb_attribute_goods->where = "goods_id='".$goods_id."' and attribute_id!=''";
        $tb_attribute_goods->order = "g.id asc";
        $goods_info['attribute'] = $tb_attribute_goods->find();
        
        //[数据挖掘]最终购买此商品的用户ID列表
        $tb_good = new IQuery('order_goods as og');
        $tb_good->join   = 'right join order as o on og.order_id=o.id ';
        $tb_good->fields = 'DISTINCT o.user_id';
        $tb_good->where  = 'og.goods_id = '.$goods_id;
        $tb_good->limit  = 5;
        $bugGoodInfo = $tb_good->find();
        if($bugGoodInfo)
        {
            $shop_goods_array = array();
            foreach($bugGoodInfo as $key => $val)
            {
                $shop_goods_array[] = $val['user_id'];
            }
            $goods_info['buyer_id'] = join(',',$shop_goods_array);
        }
        
        //购买记录
        $tb_shop = new IQuery('order_goods as og');
        $tb_shop->join = 'left join order as o on o.id=og.order_id';
        $tb_shop->fields = 'count(*) as totalNum';
        $tb_shop->where = 'og.goods_id='.$goods_id.' and o.status = 5';
        $shop_info = $tb_shop->find();
        $goods_info['buy_num'] = 0;
        if($shop_info)
        {
            $goods_info['buy_num'] = $shop_info[0]['totalNum'];
        }
        
        //购买前咨询
        $tb_refer    = new IModel('refer');
        $refeer_info = $tb_refer->getObj('goods_id='.$goods_id,'count(*) as totalNum');
        $goods_info['refer'] = 0;
        if($refeer_info)
        {
            $goods_info['refer'] = $refeer_info['totalNum'];
        }
    
        
        
        $tb_product = new IModel('products');
        $countsumInstance = new countsum();
        if($product_id){//如果按规格区分
            $proData = $tb_product->getObj('id='.$product_id,'spec_array,store_nums,sell_price');
            $goods_info = array_merge($goods_info,$proData);
            $group_type = 'product';
            $group_goods_id = $product_id;
            $goods_info['product'] = $tb_product->query('goods_id='.$goods_id.' and id='.$product_id,'id,spec_array,store_nums');
            $goods_info['product'] = JSON::encode($goods_info['product']);
            
        }else{
            //获得商品的价格区间
            $goods_info['maxSellPrice']   = '';
            $goods_info['minSellPrice']   = '';
            $goods_info['minMarketPrice'] = '';
            $goods_info['maxMarketPrice'] = '';
            
            $product_info = $tb_product->getObj('goods_id='.$goods_id,'max(sell_price) as maxSellPrice ,min(sell_price) as minSellPrice,max(market_price) as maxMarketPrice,min(market_price) as minMarketPrice');
            if($product_info)
            {
                $goods_info['maxSellPrice']   = $product_info['maxSellPrice'];
                $goods_info['minSellPrice']   = $product_info['minSellPrice'];
                $goods_info['minMarketPrice'] = $product_info['minMarketPrice'];
                $goods_info['maxMarketPrice'] = $product_info['maxMarketPrice'];
            }
            
            $group_type = 'goods';
            $group_goods_id = $goods_id;
            $goods_info['product'] = $tb_product->query('goods_id='.$goods_id,'id,spec_array,store_nums');
            $goods_info['product'] = JSON::encode($goods_info['product']);
            
        }
        //获得会员价
        $group_price = $countsumInstance->getGroupPrice($group_goods_id,$group_type);
        if($group_price !==null){
            $group_price = floatval($group_price);
            if($group_price < $goods_info['sell_price']){
                $goods_info['group_price'] = $group_price;
            }
        }
        
        //获取标签
        $tb_tag = new IQuery('commend_tags as t');
        $tb_tag->join = 'left join commend_goods as go on t.id = go.commend_id';
        $tb_tag->where = 'go.goods_id = '.$goods_id;
        $tb_tag->fields = 't.name,t.img';
        $tb_tag->limit = 5;
        $goods_info['tag_data'] = $tb_tag->find();
        
        //获取商家信息
        if($goods_info['seller_id'])
        {
            $sellerDB = new IModel('seller');
            $goods_info['seller'] = $sellerDB->getObj('id = '.$goods_info['seller_id']);
        }
        
        //增加浏览次数
        $visit    = ISafe::get('visit');
        $checkStr = "#".$goods_id."#";
        if($visit && strpos($visit,$checkStr) !== false)
        {
        }
        else
        {
            $tb_goods->setData(array('visit' => 'visit + 1'));
            $tb_goods->update('id = '.$goods_id,'visit');
            $visit = $visit === null ? $checkStr : $visit.$checkStr;
            ISafe::set('visit',$visit);
        }
        user_like::add_like_cate($goods_id,$this->user['user_id']);
        
        $this->tuanData = $tuanData;
        unset($tuanData);
        $goods_info['goods_id'] = $goods_id;
        $goods_info['product_id']=$product_id;
        $this->setRenderData($goods_info);
        $this->redirect('tuan_product');
        
    }
    //特价
    public function tejia(){
        $this->logoUrl = 'images/tglogo.png';
        $this->tuangou = 1;
        $this->todayList = array();
        $this->brandList = array();
        $tejiaList = Api::run('getCommendHot','4');
        $this->todayList = $tejiaList;
        $this->count = count($tejiaList);
        /*if($this->count>2){
            $this->todayList = array(array_shift($tejiaList),array_shift($tejiaList));
            $this->brandList = $tejiaList;
        }else{
            $this->todayList = $tejiaList;
        } */
        $this->redirect('tejia');
    }
    //获取更多特价商品
    public function getMoreTejia(){
        $start = IFilter::act(IReq::get('start'),'int');
        $limit = $start.',4';
        $QB = new IQuery('commend_goods as co');
        $QB->join = 'left join goods as go on co.goods_id = go.id';
        $QB->fields = 'go.img,go.sell_price,go.name,go.id,go.market_price,go.sale';
        $QB->where = 'co.commend_id = 3 and go.is_del = 0 AND go.id is not null ';
        $QB->order = 'sort asc,id desc';   
        $QB->limit = $limit;
        $data = $QB->find();
        
        foreach($data as $key=>$val){
            $data[$key]['key'] = $key + $start; 
        }
        echo $data ? JSON::encode($data) : 0;
        
    }
    
	//[首页]商品搜索
	function search_list()
	{
		$this->word = IFilter::act(IReq::get('word'),'text');
		$cat_id     = IFilter::act(IReq::get('cat'),'int');

		if(preg_match("|^[\w\x7f\s*-\xff*]+$|",$this->word))
		{
			//搜索关键字
			$tb_sear     = new IModel('search');
			$search_info = $tb_sear->getObj('keyword = "'.$this->word.'"','id');

			//如果是第一页，相应关键词的被搜索数量才加1
			if($search_info && intval(IReq::get('page')) < 2 )
			{
				//禁止刷新+1
				$allow_sep = "30";
				$flag = false;
				$time = ICookie::get('step');
				if(isset($time))
				{
					if (time() - $time > $allow_sep)
					{
						ICookie::set('step',time());
						$flag = true;
					}
				}
				else
				{
					ICookie::set('step',time());
					$flag = true;
				}
				if($flag)
				{
					$tb_sear->setData(array('num'=>'num + 1'));
					$tb_sear->update('id='.$search_info['id'],'num');
				}
			}
			elseif( !$search_info )
			{
				//如果数据库中没有这个词的信息，则新添
				$tb_sear->setData(array('keyword'=>$this->word,'num'=>1));
				$tb_sear->add();
			}
		}
		else
		{
			IError::show(403,'请输入正确的查询关键词');
		}
		$this->cat_id = $cat_id;
		
		$this->redirect('search_list');
	}

	//[site,ucenter头部分]自动完成
	function autoComplete()
	{
		$word = IFilter::act(IReq::get('word'));
		$isError = true;
		$data    = array();

		if($word != '' && $word != '%' && $word != '_')
		{
			$wordObj  = new IModel('keyword');
			$wordList = $wordObj->query('word like "'.$word.'%" and word != "'.$word.'"','word, goods_nums','','',10);

			if(!empty($wordList))
			{
				$isError = false;
				$data = $wordList;
			}
		}

		//json数据
		$result = array(
			'isError' => $isError,
			'data'    => $data,
		);

		echo JSON::encode($result);
	}

	//[首页]邮箱订阅
	function email_registry()
	{
		$email  = IReq::get('email');
		$result = array('isError' => true);

		if(!IValidate::email($email))
		{
			$result['message'] = '请填写正确的email地址';
		}
		else
		{
			$emailRegObj = new IModel('email_registry');
			$emailRow    = $emailRegObj->getObj('email = "'.$email.'"');

			if(!empty($emailRow))
			{
				$result['message'] = '此email已经订阅过了';
			}
			else
			{
				$dataArray = array(
					'email' => $email,
				);
				$emailRegObj->setData($dataArray);
				$status = $emailRegObj->add();
				if($status == true)
				{
					$result = array(
						'isError' => false,
						'message' => '订阅成功',
					);
				}
				else
				{
					$result['message'] = '订阅失败';
				}
			}
		}
		echo JSON::encode($result);
	}

	//[列表页]商品
	function pro_list()
	{
		$this->catId = IFilter::act(IReq::get('cat'),'int');//分类id

		if($this->catId == 0)
		{
			IError::show(403,'缺少分类ID');
		}

		//查找分类信息
		$catObj       = new IModel('category');
		$this->catRow = $catObj->getObj('id = '.$this->catId);

		if($this->catRow == null)
		{
			IError::show(403,'此分类不存在');
		}

		//获取子分类
		$this->childId = goods_class::catChild($this->catId);
	//	echo $this->childId;echo $this->catId;
		$this->redirect('pro_list');
	}
	
	
	//咨询
	function consult()
	{
		$this->goods_id = IFilter::act(IReq::get('id'),'int');
		$this->callback = IReq::get('callback');

		if($this->goods_id == 0)
		{
			IError::show(403,'缺少商品ID参数');
		}

		$goodsObj   = new IModel('goods');
		$goodsRow   = $goodsObj->getObj('id = '.$this->goods_id);

		if(!$goodsRow)
		{
			IError::show(403,'商品数据不存在');
		}

		//获取次商品的评论数和平均分(保留小数点后一位)
		$goodsRow['apoint']   = $goodsRow['comments'] ? round($goodsRow['grade']/$goodsRow['comments'],1) : 0;
		$goodsRow['comments'] = $goodsRow['comments'];

		$this->goodsRow = $goodsRow;
		$this->redirect('consult');
	}

	//咨询动作
	function consult_act()
	{
		$goods_id   = IFilter::act(IReq::get('goods_id','post'),'int');
		$captcha    = IFilter::act(IReq::get('captcha','post'));
		$question   = IFilter::act(IReq::get('question','post'));
		$type = IFilter::act(IReq::get('type','post'),'int');
		$callback   = IReq::get('callback');
		$message    = '';

    	if($captcha != ISafe::get('captcha'))
    	{
    		$message = '验证码输入不正确';
    	}
    	else if(!trim($question))
    	{
    		$message = '咨询内容不能为空';
    	}
    	else if($goods_id == 0)
    	{
    		$message = '商品ID不能为空';
    	}
    	else
    	{
    		$goodsObj = new IModel('goods');
    		$goodsRow = $goodsObj->getObj('id = '.$goods_id);
    		if(!$goodsRow)
    		{
    			$message = '不存在此商品';
    		}
    	}

		//有错误情况
    	if($message)
    	{
    		$this->callback = $callback;
    		$this->goods_id = $goods_id;
    		$dataArray = array(
    			'question' => $question,
    		);
    		$this->consultRow = $dataArray;

			//渲染goods数据
			$goodsObj   = new IModel('goods');
			$goodsRow   = $goodsObj->getObj('id = '.$this->goods_id);

			//获取次商品的评论数和平均分(保留小数点后一位)
			$goodsRow['apoint']   = $goodsRow['comments'] ? round($goodsRow['grade']/$goodsRow['comments'],1) : 0;
			$goodsRow['comments'] = $goodsRow['comments'];
			$this->goodsRow = $goodsRow;

    		$this->redirect('consult',false);
    		Util::showMessage($message);
    	}
    	else
    	{
			$dataArray = array(
				'question' => $question,
				'goods_id' => $goods_id,
				'type'=>$type,
				'user_id'  => isset($this->user['user_id']) ? $this->user['user_id'] : 0,
				'time'     => ITime::getDateTime(),
			);

			$referObj = new IModel('refer');
			$referObj->setData($dataArray);
			$referObj->add();

			$this->redirect('success?callback=/site/products/id/'.$goods_id);
    	}
	}

	//公告详情页面
	function notice_detail()
	{
		$this->notice_id = IFilter::act(IReq::get('id'),'int');
		if($this->notice_id == '')
		{
			IError::show(403,'缺少公告ID参数');
		}
		else
		{
			$noObj           = new IModel('announcement');
			$this->noticeRow = $noObj->getObj('id = '.$this->notice_id);
			if(empty($this->noticeRow))
			{
				IError::show(403,'公告信息不存在');
			}
			$this->redirect('notice_detail');
		}
	}

	//咨询详情页面
	function article_detail()
	{
		$this->article_id = IFilter::act(IReq::get('id'),'int');
		if($this->article_id == '')
		{
			IError::show(403,'缺少咨询ID参数');
		}
		else
		{
			$articleObj       = new IModel('article');
			$this->articleRow = $articleObj->getObj('id = '.$this->article_id);
			if(empty($this->articleRow))
			{
				IError::show(403,'资讯文章不存在');
				exit;
			}

			//关联商品
			$relationObj = new IQuery('relation as r');
			$relationObj->join   = ' left join goods as go on r.goods_id = go.id ';
			$relationObj->where  = ' r.article_id = '.$this->article_id.' and go.id is not null ';

			$this->relationList  = $relationObj->find();
			$this->redirect('article_detail');
		}
	}

	//商品展示
	function products()
	{
		$goods_id = IFilter::act(IReq::get('id'),'int');

		if(!$goods_id)
		{
			IError::show(403,"传递的参数不正确");
			exit;
		}
		$user_id = $this->user ? $this->user['user_id'] : 0;
		user_like::set_user_history($goods_id,$user_id);
		
		//使用商品id获得商品信息
		$tb_goods = new IModel('goods');
		$goods_info = $tb_goods->getObj('id='.$goods_id." AND (is_del=0 or is_del=4)");
		
 		
		if(!$goods_info)
		{
			IError::show(403,"这件商品不存在或已下架");
			exit;
		}
		$regiment_db = new IModel('regiment');
		$goods_info['regiment'] = $regiment_db->getObj('goods_id='.$goods_id.' and is_close=0 and  TIMESTAMPDIFF(second,start_time,NOW()) >=0 and TIMESTAMPDIFF(second,end_time,NOW())<0');
		
		$product_db = new IModel('products');
		$goods_info['product'] = $product_db->query('goods_id='.$goods_info['id'],'id,spec_array,store_nums');
		$goods_info['product'] = JSON::encode($goods_info['product']);
		if($goods_info['is_del']==4)header('location:'.IUrl::getHost().IUrl::creatUrl('pregoods/products/id/'.$goods_id));
		$sell_price = $goods_info['sell_price'];
		//品牌名称
		if($goods_info['brand_id'])
		{
			$tb_brand = new IModel('brand');
			$brand_info = $tb_brand->getObj('id='.$goods_info['brand_id']);
			if($brand_info)
			{
				$goods_info['brand'] = $brand_info['name'];
			}
		}
		$commend = new IModel('commend_goods');
		$goods_info['commend'] = $commend->getFields(array('goods_id'=>$goods_id),'commend_id');
		
		//获取商品分类
		$categoryObj = new IModel('category_extend as ca,category as c');
		$categoryRow = $categoryObj->getObj('ca.goods_id = '.$goods_id.' and ca.category_id = c.id','c.id,c.name');
		$goods_info['category'] = $categoryRow ? $categoryRow['id'] : 0;
	
		//商品图片
		$tb_goods_photo = new IQuery('goods_photo_relation as g');
		$tb_goods_photo->fields = 'p.id AS photo_id,p.img ';
		$tb_goods_photo->join = 'left join goods_photo as p on p.id=g.photo_id ';
		$tb_goods_photo->where =' g.goods_id='.$goods_id;
		$goods_info['photo'] = $tb_goods_photo->find();
		foreach($goods_info['photo'] as $key => $val)
		{
			//对默认第一张图片位置进行前置
			if($val['img'] == $goods_info['img'])
			{
				$temp = $goods_info['photo'][0];
				$goods_info['photo'][0] = $val;
				$goods_info['photo'][$key] = $temp;
			}
		}

		//商品是否参加促销活动(团购，抢购)
		$goods_info['promo']     = IReq::get('promo')     ? IReq::get('promo') : '';
		$goods_info['active_id'] = IReq::get('active_id') ? IFilter::act(IReq::get('active_id'),'int') : '';
		
		//获得扩展属性
		$tb_attribute_goods = new IQuery('goods_attribute as g');
		$tb_attribute_goods->join  = 'left join attribute as a on a.id=g.attribute_id ';
		$tb_attribute_goods->fields=' a.name,g.attribute_value ';
		$tb_attribute_goods->where = "goods_id='".$goods_id."' and attribute_id!=''";
		$tb_attribute_goods->order = "g.id asc";
		$goods_info['attribute'] = $tb_attribute_goods->find();

		//[数据挖掘]最终购买此商品的用户ID列表
		$tb_good = new IQuery('order_goods as og');
		$tb_good->join   = 'right join order as o on og.order_id=o.id ';
		$tb_good->fields = 'DISTINCT o.user_id';
		$tb_good->where  = 'og.goods_id = '.$goods_id;
		$tb_good->limit  = 5;
		$bugGoodInfo = $tb_good->find();
		if($bugGoodInfo)
		{
			$shop_goods_array = array();
			foreach($bugGoodInfo as $key => $val)
			{
				$shop_goods_array[] = $val['user_id'];
			}
			$goods_info['buyer_id'] = join(',',$shop_goods_array);
		}

		//购买记录
		$tb_shop = new IQuery('order_goods as og');
		$tb_shop->join = 'left join order as o on o.id=og.order_id';
		$tb_shop->fields = 'count(*) as totalNum';
		$tb_shop->where = 'og.goods_id='.$goods_id.' and o.status = 5';
		$shop_info = $tb_shop->find();
		$goods_info['buy_num'] = 0;
		if($shop_info)
		{
			$goods_info['buy_num'] = $shop_info[0]['totalNum'];
		}

		//购买前咨询
		$tb_refer    = new IModel('refer');
		$refeer_info = $tb_refer->getObj('goods_id='.$goods_id,'count(*) as totalNum');
		$goods_info['refer'] = 0;
		if($refeer_info)
		{
			$goods_info['refer'] = $refeer_info['totalNum'];
		}


		//获得商品的价格区间
		$tb_product = new IModel('products');
		$goods_info['maxSellPrice']   = '';
		$goods_info['minSellPrice']   = '';
		$goods_info['minMarketPrice'] = '';
		$goods_info['maxMarketPrice'] = '';
	
		$product_info = $tb_product->getObj('goods_id='.$goods_id,'max(sell_price) as maxSellPrice ,min(sell_price) as minSellPrice,max(market_price) as maxMarketPrice,min(market_price) as minMarketPrice');
		if($product_info)
		{
			$goods_info['maxSellPrice']   = $product_info['maxSellPrice'];
			$goods_info['minSellPrice']   = $product_info['minSellPrice'];
			$goods_info['minMarketPrice'] = $product_info['minMarketPrice'];
			$goods_info['maxMarketPrice'] = $product_info['maxMarketPrice'];
		}
		
		

		//获得会员价
		$countsumInstance = new countsum();
		$group_price = $countsumInstance->getGroupPrice($goods_id,'goods');
		if($group_price !==null){
			$group_price = floatval($group_price);
			if($group_price < $goods_info['sell_price']){
				$goods_info['group_price'] = $group_price;
			}
		}
		//获取标签
		$tb_tag = new IQuery('commend_tags as t');
		$tb_tag->join = 'left join commend_goods as go on t.id = go.commend_id';
		$tb_tag->where = 'go.goods_id = '.$goods_id;
		$tb_tag->fields = 't.name,t.img';
		$tb_tag->limit = 5;
		$goods_info['tag_data'] = $tb_tag->find();
		
		//获取商家信息
		if($goods_info['seller_id'])
		{
			$sellerDB = new IModel('seller');
			$goods_info['seller'] = $sellerDB->getObj('id = '.$goods_info['seller_id'],'id,true_name,email,mobile,logo_img,server_num,point,num');
		}
		//增加浏览次数
		$visit    = ISafe::get('visit');
		$checkStr = "#".$goods_id."#";
		if($visit && strpos($visit,$checkStr) !== false)
		{
		}
		else
		{
			$tb_goods->setData(array('visit' => 'visit + 1'));
			$tb_goods->update('id = '.$goods_id,'visit');
			$visit = $visit === null ? $checkStr : $visit.$checkStr;
			ISafe::set('visit',$visit);
		}
		user_like::add_like_cate($goods_id,$this->user['user_id']);
	//	print_r($goods_info);
	//	print_r($specArray);
		$this->setRenderData($goods_info);
		$this->redirect('products');
	}
	//商品讨论更新
	function discussUpdate()
	{
		$goods_id = IFilter::act(IReq::get('id'),'int');
		$content  = IFilter::act(IReq::get('content'),'text');
		$captcha  = IReq::get('captcha');
		$return   = array('isError' => true , 'message' => '');

		if(!$this->user['user_id'])
		{
			$return['message'] = '请先登录系统';
		}
    	else if($captcha != ISafe::get('captcha'))
    	{
    		$return['message'] = '验证码输入不正确';
    	}
    	else if(trim($content) == '')
    	{
    		$return['message'] = '内容不能为空';
    	}
    	else
    	{
    		$return['isError'] = false;

			//插入讨论表
			$tb_discussion = new IModel('discussion');
			$dataArray     = array(
				'goods_id' => $goods_id,
				'user_id'  => $this->user['user_id'],
				'time'     => date('Y-m-d H:i:s'),
				'contents' => $content,
			);
			$tb_discussion->setData($dataArray);
			$tb_discussion->add();

			$return['time']     = $dataArray['time'];
			$return['contents'] = $content;
			$return['username'] = $this->user['username'];
    	}
    	echo JSON::encode($return);
	}

	//获取货品数据
	function getProduct()
	{
		$jsonData = JSON::decode(IReq::get('specJSON'));
		if(!$jsonData)
		{
			echo JSON::encode(array('flag' => 'fail','message' => '规格值不符合标准'));
			exit;
		}

		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$specJSON = IFilter::act(IReq::get('specJSON'));

		//获取货品数据
		$tb_products = new IModel('products');
		$procducts_info = $tb_products->getObj("goods_id = ".$goods_id." and spec_array = '".$specJSON."'");
		$procducts_info['sell_price'] = $procducts_info['sell_price'];
		//匹配到货品数据
		if(!$procducts_info)
		{
			echo JSON::encode(array('flag' => 'fail','message' => '没有找到相关货品'));
			exit;
		}
		
		
		//获得会员价
		$countsumInstance = new countsum();
		$group_price = $countsumInstance->getGroupPrice($procducts_info['id'],'product');

		//会员价格(与销售价相等则不显示）
		if($group_price !== null && floatval($group_price) < $procducts_info['sell_price'])
		{
			$procducts_info['group_price'] = floatval($group_price);
		}

		echo JSON::encode(array('flag' => 'success','data' => $procducts_info));
	}

	//顾客评论ajax获取
	function comment_ajax()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;
		
		$commentDB = new IQuery('comment as c');
		$commentDB->join   = 'left join goods as go on c.goods_id = go.id AND go.is_del = 0 left join user as u on u.id = c.user_id';
		$commentDB->fields = 'u.head_ico,u.username,c.*';
		$commentDB->where  = 'c.goods_id = '.$goods_id.' and c.status = 1';
		$commentDB->order  = 'c.id desc';
		$commentDB->page   = $page;
		$data     = $commentDB->find();
		$pageHtml = $commentDB->getPageBar("javascript:void(0);",'onclick="comment_ajax([page])"');

		echo JSON::encode(array('data' => $data,'pageHtml' => $pageHtml));
	}
	function comment_ajax2(){
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$type     = IFilter::act(IReq::get('type'));
		echo JSON::encode(Comment_Class::get_comment_byid($goods_id,$type));
	}

	//购买记录ajax获取
	function history_ajax()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;

		$orderGoodsDB = new IQuery('order_goods as og');
		$orderGoodsDB->join   = 'left join order as o on og.order_id = o.id left join user as u on o.user_id = u.id';
		$orderGoodsDB->fields = 'o.user_id,og.goods_price,og.goods_nums,o.create_time as completion_time,u.username,u.email,u.phone';
		$orderGoodsDB->where  = 'og.goods_id = '.$goods_id.' and (o.type != 4 and o.status = 5 OR o.type=4 and o.status in (3,4,7,9,10))';
		$orderGoodsDB->order  = 'o.create_time desc';
		$orderGoodsDB->page   = $page;

		$data = $orderGoodsDB->find();
		foreach($data as $key=>$val){
			if($val['username']!=''){
				$data[$key]['show'] = $val['username'];
			}else if($val['phone']!=''){
				$data[$key]['show'] = user_like::getSecretPhone($val['phone']);
			}else{
				$data[$key]['show'] = user_like::getSecretEmail($val['email']);
			}
		}
		$pageHtml = $orderGoodsDB->getPageBar("javascript:void(0);",'onclick="history_ajax([page])"');

		echo JSON::encode(array('data' => $data,'pageHtml' => $pageHtml));
	}

	//讨论数据ajax获取
	function discuss_ajax()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;

		$discussDB = new IQuery('discussion as d');
		$discussDB->join = 'left join user as u on d.user_id = u.id';
		$discussDB->where = 'd.goods_id = '.$goods_id;
		$discussDB->order = 'd.id desc';
		$discussDB->fields = 'u.username,d.time,d.contents';
		$discussDB->page = $page;

		$data = $discussDB->find();
		$pageHtml = $discussDB->getPageBar("javascript:void(0);",'onclick="discuss_ajax([page])"');

		echo JSON::encode(array('data' => $data,'pageHtml' => $pageHtml));
	}

	//买前咨询数据ajax获取
	function refer_ajax()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;

		$referDB = new IQuery('refer as r');
		$referDB->join = 'left join user as u on r.user_id = u.id';
		$referDB->where = 'r.goods_id = '.$goods_id;
		$referDB->order = 'r.id desc';
		$referDB->fields = 'u.username,u.head_ico,r.time,r.question,r.reply_time,r.answer';
		$referDB->page = $page;

		$data = $referDB->find();
		$pageHtml = $referDB->getPageBar("javascript:void(0);",'onclick="refer_ajax([page])"');

		echo JSON::encode(array('data' => $data,'pageHtml' => $pageHtml));
	}

	//评论列表页
	function comments_list()
	{	
		$id   = IFilter::act(IReq::get("id"),'int');
		$type = IFilter::act(IReq::get("type"));

		$this->data=Comment_Class::get_comment_byid($id,$type,$this);

		$this->redirect('comments_list');
	}

	//提交评论页
	function comments()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		
		if(!$id)
		{
			$goods_id = IFilter::act(IReq::get('goods_id'),'int');
			$order_id = IFilter::act(IReq::get('order_id'),'int');
			$comment_db = new IQuery('comment as c');
			$comment_db->join = 'left join order_goods as og on c.order_id=og.order_id and c.goods_id=og.goods_id';
			$comment_db->where = 'c.order_id='.$order_id.' and og.is_send=1';
			$comment_db->fields = 'c.id';
			$comment_data = $comment_db->getObj();
		
			if(!$comment_data){
				IError::show(403,"传递的参数不完整");
			}
			$id = $comment_data['id'];
		}

		if(!isset($this->user['user_id']) || $this->user['user_id']==null )
		{
			$this->redirect('simple/login?callback=site/comments/id/'.$id);
		}

		$can_submit = Comment_Class::can_comment($id,$this->user['user_id']);
		if($can_submit[0]==-1)
		{
			IError::show(403,"没有这条数据");
		}

		$this->can_submit   = $can_submit[0]==1;//true值
		$this->comment      = $can_submit[1]; //评论数据
		$this->comment_info = Comment_Class::get_comment_info($this->comment['goods_id']);
		$goods_id=$this->comment['goods_id'];
		$goods_db = new IModel('goods');
		$this->goodsRow = $goods_db->getObj('id='.$goods_id);
		//print_r($this->comment);
		$this->redirect("comments");
	}

	/**
	 * @brief 进行商品评论 ajax操作
	 */
	public function comment_add()
	{
		if(!isset($this->user['user_id']) || $this->user['user_id']===null)
		{
			die("未登录用户不能评论");
		}

		if(IReq::get('id')===null)
		{
			die("传递的参数不完整");
		}

		$id               = IFilter::act(IReq::get('id'),'int');
		$data             = array();
		$data['point']    = IFilter::act(IReq::get('point'),'float');
		$data['contents'] = IFilter::act(IReq::get("contents"),'content');
		$data['sellerid'] = IFilter::act(IReq::get('sellerid'),'int');
		$data['status']   = 1;

		if($data['point']==0)
		{
			die("请选择分数");
		}

		$can_submit = Comment_Class::can_comment($id,$this->user['user_id']);
		if($can_submit[0]!=1)
		{
			die("您不能评论此件商品");
		}

		$data['comment_time'] = date("Y-m-d",ITime::getNow());

		$tb_comment = new IModel("comment");
		$tb_comment->setData($data);
		$re=$tb_comment->update("id={$id}");

		if($re)
		{
			//同步更新goods表,comments,grade
			$commentRow = $tb_comment->getObj('id = '.$id);

			$goodsDB = new IModel('goods');
			$goodsDB->setData(array(
				'comments' => 'comments + 1',
				'grade'    => 'grade + '.$data['point'],
			));
			$goodsDB->update('id = '.$commentRow['goods_id'],array('grade','comments'));

			
			
			//更新seller表，point 、num
			$sellerDB = new IModel('seller');
			$sellerDB->setData(array(
				'point'=>'point + '.$data['point'],
				'num'=>'num + 1',
			));
			$sellerDB->update('id = '.$data['sellerid'],array('point','num'));
			echo "success";
		}
		else
		{
			die("评论失败");
		}
	}

	function pic_show()
	{
		$this->layout="";
		$this->redirect("pic_show");
	}

	function help()
	{
		$id = intval(IReq::get("id"));
		$tb_help = new IModel("help");
		$help_row = $tb_help->query("id={$id}");
		if(!$help_row || !is_array($help_row))
		{
			IError::show(404,"您查找的页面已经不存在了");
		}
		$this->help_row = end( $help_row );

		$tb_help_cat = new IModel("help_category");
		$cat_row = $tb_help_cat->query("id={$this->help_row['cat_id']}");
		$this->cat_row = end($cat_row);
		$this->redirect("help");
	}

	function help_list()
	{
		$id = intval(IReq::get("id"));
		$tb_help_cat = new IModel("help_category");
		$cat_row = $tb_help_cat->query("id={$id}");
		if($cat_row)
		{
			$this->cat_row = end($cat_row);
		}
		else
		{
			$this->cat_row = array('id'=>0,'name'=>'站点帮助');
		}
		$this->redirect("help_list");
	}


	
}
