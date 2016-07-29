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
    
    //手机团购页面
    public function mobileTuangou()
    {
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
        $this->redirect('tuangou',false);
    }
    
	//团购页面
	public function tuangou(){
		$this->logoUrl = 'images/tglogo.png';
		$this->tuangou = 1;
		$this->topList = array();
        $this->onTimeList = array();
		$this->brandList = array();
		$tuan = new IQuery('regiment as r');
        //TOP
		$tuan->join = 'left join goods as g on r.goods_id=g.id';
        $tuan->fields = 'r.*';
        $tuan->where = 'r.is_close = 0 AND NOW() between r.start_time and r.end_time and g.is_del=0 and r.type=0';
        $tuan->order = 'r.sort asc';
        $tuan->limit = 2;
        $topList = $tuan->find();
        if($topList)
        {
            foreach($topList as $k => $v)
            {
                $data = Comment_Class::get_comment_info($v['goods_id']);
                $topList[$k]['comment_num'] = $data['comment_total'] ;
                $topList[$k]['comment_rate'] = $data['comment_total'] ? (round($data['point_grade']['good']/$data['comment_total'],4))*100 : 0;
            }
        }
        $this->topList=$topList;
        //品牌团
        $tuan->join = 'left join goods as g on r.goods_id=g.id';
        $tuan->fields = 'r.*';
        $tuan->where = 'r.is_close = 0 AND NOW() between r.start_time and r.end_time and g.is_del=0 and r.type=1';
        $tuan->order = 'r.sort asc';
        $tuan->limit = 9;
        $brandList = $tuan->find();
        if($brandList)
        {
            foreach($brandList as $k => $v)
            {
                $brandList[$k]['regiment_price'] = (int)$v['regiment_price'];
                $brandList[$k]['sell_price'] = (int)$v['sell_price'];
            }
        }
        $this->brandList = $brandList;
        //整点团
        $mod = new IModel('regiment');
        $timeList = $mod->query('is_close = 0 AND NOW() < start_time and type=2 and unix_timestamp(start_time) <='.strtotime(date('Y-m-d', strtotime('+1 day'))), 'distinct(start_time),end_time', 'start_time', 'ASC', 3);
        $time = $mod->query('is_close = 0 AND NOW() between start_time and end_time and type=2', 'start_time,end_time', 'start_time', 'DESC', 1);
        if($time)
        {
            $time = $time[0];
            array_unshift($timeList, $time);
            if(count($timeList) > 3)
            {
                array_pop($timeList);
            }
            $sign = 1;  //进行中
        }               
        if(count($timeList) < 3)
        {
            $temp = $mod->query('is_close = 0 AND NOW() > end_time and type=2 and unix_timestamp(start_time) >='.strtotime(date('Y-m-d')), 'distinct(start_time),end_time', 'start_time', 'DESC', 3-count($timeList));
            foreach($temp as $v)
            {
                array_unshift($timeList, $v);
                $sign = 0;  //已结束
            }
        }
        if($timeList)
        {
            if(!$time)
            {
               $time = reset($timeList); 
            }   
            $this->timeList = $timeList;
            $this->time = $time['start_time'];
        }
        $this->sign = isset($sign) ? $sign : 2;                          
		$this->redirect('tuangou');
	}
    //获取整点团购数据
    public function getOnTimeList()
    {
        $time = IReq::get('time');
        $sign = IReq::get('sign');  
        $tuan = new IQuery('regiment as r');
        $tuan->join = 'left join goods as g on r.goods_id=g.id';
        $tuan->fields = 'r.*';
        if($sign == 1)
        {
            $tuan->where = "r.is_close = 0 AND r.start_time='{$time}' and r.end_time > NOW() and g.is_del=0 and r.type=2";
        }
        else
        {
            $tuan->where = "r.is_close = 0 AND r.start_time='{$time}' and g.is_del=0 and r.type=2";
        }
        $tuan->order = 'r.sort asc';
        $tuan->limit = 6;
        $data = $tuan->find();
        foreach($data as $k => $v)
        {
            $data[$k]['sign'] = $sign;
            $data[$k]['regiment_price'] = (int)$v['regiment_price'] == $v['regiment_price'] ? (int)$v['regiment_price'] : $v['regiment_price'];
            $data[$k]['sell_price'] = (int)$v['sell_price'] == $v['sell_price'] ? (int)$v['sell_price'] : $v['sell_price'];
        }                
        echo JSON::encode(array('data' => $data));
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
        $tuanData = Api::run('getRegimentOnTimeRowById',array('#id#',$id));
        if($tuanData && $tuanData['type'] <> 2 && strtotime($tuanData['start_time']) > time())
        {
            $tuanData = array();
        }
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
        
        //评论条数
        $comment = new IModel('comment');
        $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and pid = 0', 'count(1) as num');
        $goods_info['comment_num'] = $temp[0]['num'];
        //好评
        $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and point=5 and pid = 0', 'count(1) as num');
        $goods_info['good_comment'] = $temp[0]['num'];
        //中评
        $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and point < 5 and point > 1 and pid = 0', 'count(1) as num');
        $goods_info['middle_comment'] = $temp[0]['num'];
        //差评
        $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and point<2 and pid = 0', 'count(1) as num');
        $goods_info['bad_comment'] = $temp[0]['num'];
        
        //评分
        $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and pid = 0', 'sum(point) as sum');
        $goods_info['grade'] = !!$temp[0]['sum'] ? $temp[0]['sum'] : 0;
        
        //购买记录
        $tb_shop = new IQuery('order_goods as og');
        $tb_shop->join = 'left join order as o on o.id=og.order_id';
        $tb_shop->fields = 'count(*) as totalNum';
        $tb_shop->where = 'og.goods_id='.$goods_id.' and (o.status = 5 or o.status = 11)';
        $shop_info = $tb_shop->find();
        $goods_info['buy_num'] = 0;
        if($shop_info)
        {
            $goods_info['buy_num'] = $shop_info[0]['totalNum'];
        }
        
        $tb_refer    = new IModel('refer');
        //咨询条数
        $num = $tb_refer->getObj('goods_id='.$goods_id.' and pid=0','count(*) as totalNum');
        $goods_info['refer_num'] = $num ? $num['totalNum'] : 0;
        //咨询类型
        $refer_type = new IModel('refer_type');
        $dataList = $refer_type->query('is_open=1', '*', 'sort', 'ASC');
        if($dataList)
        {
            foreach($dataList as $k => $v)
            {
                $refer_info = $tb_refer->getObj('goods_id='.$goods_id.' and pid=0 and type='.$v['id'],'count(*) as totalNum');
                $dataList[$k]['num'] = $refer_info ? $refer_info['totalNum'] : 0;
            }
            $temp = $dataList[0];
            $referType = $temp['id'] ? $temp['id'] : 0;
        }
        $this->type = isset($referType) ? $referType : 0;
        $goods_info['refer'] = $dataList;
    
        
        
        $tb_product = new IModel('products');
        $countsumInstance = new countsum();
        if($product_id){//如果按规格区分
            $proData = $tb_product->getObj('id='.$product_id,'spec_array,store_nums,sell_price,market_price,weight');
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
   
        //组合销售
        $combineList = array();
        if($goods_info['type'] <> 1){
            $combine = new IModel('combine_goods');
            $combineList = $combine->query('goods_id = '.$goods_id.' and status = 1', '*', 'sort', 'asc');
            foreach($combineList as $k => $v)
            {
                if(!$v['combine'])
                {
                    unset($combineList[$k]);
                    continue;
                }
                $goodsList = $tb_goods->query('id in ('.$v['combine'].") AND is_del=0 and type <> 1", 'id,name,combine_price,sell_price,img');
                if($goodsList)
                {
                    $combineList[$k]['goodsList'] = $goodsList;
                }
                else
                {
                    unset($combineList[$k]);
                }   
            }
            if(!empty($combineList))
            {
                $this->mobileCombine = current($combineList); 
            }
        }                      
        $this->combineList = $combineList;
        $this->goodsImg = current($goods_info['photo']);
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
				'seller_id' => $goodsRow['seller_id'],
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
        $tuan = new IModel('regiment');
        $obj = $tuan->getObj('goods_id='.$goods_id.' and is_close=0 and NOW() between start_time and end_time');
        if($obj)
        {
            $this->redirect('/site/tuan_product/active/'.$obj['id']);
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
        
        //评论条数
        $comment = new IModel('comment');
        $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and pid = 0', 'count(1) as num');
        $goods_info['comment_num'] = $temp[0]['num'];
        //好评
        $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and point=5 and pid = 0', 'count(1) as num');
        $goods_info['good_comment'] = $temp[0]['num'];
        //中评
        $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and point < 5 and point > 1 and pid = 0', 'count(1) as num');
        $goods_info['middle_comment'] = $temp[0]['num'];
        //差评
        $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and point<2 and pid = 0', 'count(1) as num');
        $goods_info['bad_comment'] = $temp[0]['num'];
        
        //评分
        $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and pid = 0', 'sum(point) as sum');
        $goods_info['grade'] = !!$temp[0]['sum'] ? $temp[0]['sum'] : 0;

		//购买记录
		$tb_shop = new IQuery('order_goods as og');
		$tb_shop->join = 'left join order as o on o.id=og.order_id';
		$tb_shop->fields = 'count(*) as totalNum';
		$tb_shop->where = 'og.goods_id='.$goods_id.' and (o.status = 5 or o.status = 11)';
		$shop_info = $tb_shop->find();
		$goods_info['buy_num'] = 0;
		if($shop_info)
		{
			$goods_info['buy_num'] = $shop_info[0]['totalNum'];
		}

        $tb_refer    = new IModel('refer');
        //咨询条数
        $num = $tb_refer->getObj('goods_id='.$goods_id.' and pid=0','count(*) as totalNum');
        $goods_info['refer_num'] = $num ? $num['totalNum'] : 0;
        //咨询类型
        $refer_type = new IModel('refer_type');
        $dataList = $refer_type->query('is_open=1', '*', 'sort', 'ASC');
        if($dataList)
        {
            foreach($dataList as $k => $v)
            {
                $refer_info = $tb_refer->getObj('goods_id='.$goods_id.' and pid=0 and type='.$v['id'],'count(*) as totalNum');
                $dataList[$k]['num'] = $refer_info ? $refer_info['totalNum'] : 0;
            }
            $temp = $dataList[0];
            $referType = $temp['id'] ? $temp['id'] : 0;
        }
        $this->type = isset($referType) ? $referType : 0;
		$goods_info['refer'] = $dataList;

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
        
        //组合销售
        $combineList = array();
        if($goods_info['type'] <> 1){
            $combine = new IModel('combine_goods');
            $combineList = $combine->query('goods_id = '.$goods_id.' and status = 1', '*', 'sort', 'asc');
            foreach($combineList as $k => $v)
            {
                if(!$v['combine'])
                {
                    unset($combineList[$k]);
                    continue;
                }
                $goodsList = $tb_goods->query('id in ('.$v['combine'].") AND is_del=0 and type <> 1", 'id,name,combine_price,sell_price,img');
                if($goodsList)
                {
                    $combineList[$k]['goodsList'] = $goodsList;
                }
                else
                {
                    unset($combineList[$k]);
                }   
            }
            if(!empty($combineList))
            {
                $this->mobileCombine = current($combineList); 
            } 
        }                     
        $this->combineList = $combineList;
        $this->goodsImg = current($goods_info['photo']);
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
        $pageSize = IFilter::act(IReq::get('pageSize'),'int');
		echo JSON::encode(Comment_Class::get_comment_byid($goods_id,$type,$pageSize));
	}

    //无限极分类评论查询
    function cir_comment_ajax()
    {
        $goods_id = IFilter::act(IReq::get('goods_id'),'int');
        $page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;
        $type     = IFilter::act(IReq::get('type')) ? IReq::get('type') : 'all';
        $pid     = IFilter::act(IReq::get('pid'),'int') ? IReq::get('pid') : 0;
        
        $commentDB = new IQuery('comment as c');
        $commentDB->join   = 'left join goods as go on c.goods_id = go.id AND go.is_del = 0 left join user as u on u.id = c.user_id';
        $commentDB->fields = 'u.head_ico,u.username,c.*';
        switch($type)
        {
            case 'good': 
                $commentDB->where  = 'c.goods_id = '.$goods_id.' and c.status <> 0 and c.point = 5 and c.pid = '.$pid.' and c.user_id <> -1 and c.p_id <> "0"';
                break;
            case 'middle': 
                $commentDB->where  = 'c.goods_id = '.$goods_id.' and c.status <> 0 and c.point < 5 and c.point > 1 and c.pid = '.$pid.' and c.user_id <> -1 and c.p_id <> "0"';
                break;
            case 'bad': 
                $commentDB->where  = 'c.goods_id = '.$goods_id.' and c.status <> 0 and c.point < 2 and c.pid = '.$pid.' and c.user_id <> -1 and c.p_id <> "0"';
                break;
            default:
                $commentDB->where  = 'c.goods_id = '.$goods_id.' and c.status <> 0 and c.pid = '.$pid.' and c.user_id <> -1 and c.p_id <> "0"';
                break;   
        }
        
        $commentDB->order  = 'c.id desc';
        $commentDB->page   = $page;
        $data     = $commentDB->find();
        $pageHtml = $commentDB->getPageBar("javascript:void(0);",'onclick="cir_comment_ajax([page])"');
        $comment = new IModel('comment');
        $photo = new IModel('comment_photo');
        foreach($data as $k =>$v)
        {              
            $temp = $comment->query('status <> 0 and goods_id = '.$goods_id.' and pid='.$v['id'].' and user_id <> -1 and p_id <> "0"', 'count(1) as num');
            $data[$k]['reply'] = !!$temp ? $temp[0]['num'] : 0;
            
            if($pid == 0)
            {
                //后台回复内容
                $reply = $comment->query("goods_id = $goods_id and p_id LIKE '%,{$v['id']}%' and user_id = -1");
                foreach($reply as $key => $val)
                {
                    if($val['sellerid'])
                    {
                        $seller_name = API::run('getSellerInfo',$val['sellerid'],'true_name');
                    }
                    $reply[$key]['seller_name'] = isset($seller_name['true_name']) ? $seller_name['true_name'] : '山城速购';
                    $temp = $comment->query('goods_id = '.$goods_id.' and pid='.$val['id'].' and user_id <> -1', 'count(1) as num');
                    $reply[$key]['reply'] = !!$temp ? $temp[0]['num'] : 0;
                    unset($seller_name);
                }
                $data[$k]['replyData'] =  $reply;
                
                //查询评论图片
                $data[$k]['photo'] = $photo->query('comment_id='.$v['id'], 'img', 'sort', 'desc');
                
                //追评
                if(!!$v['recontents'])
                {
                    $temp = $comment->getObj("id='{$v['recontents']}'");
                    if($temp)
                    {
                        $data[$k]['replySelf'] = $temp;
                        $data[$k]['replySelfPhoto'] = $photo->query('comment_id='.$temp['id'], 'img', 'sort', 'desc');
                    }
                }
            }
            $data[$k]['username'] = $v['username'] ? $v['username'] : '游客';
        }
        echo JSON::encode(array('data' => $data,'pageHtml' => $pageHtml));
    }
    
    //回复评论
    function comment_reply(){
       if(!isset($this->user['user_id']) || $this->user['user_id']===null)
       {
            $message = array('status' => 0, 'msg' => '您还没有登录');
            echo JSON::encode($message);exit;
       }
       
       $pid     = IFilter::act(IReq::get('pid'),'int');
       $content = IFilter::act(IReq::get('content'),'content');
                                                   
       if(!$pid)
       {      
           $message = array('status' => 0, 'msg' => '传递的参数不完整');
           echo JSON::encode($message);exit;
       }
       
       if(!$content)
       {      
           $message = array('status' => 0, 'msg' => '请输入内容');
           echo JSON::encode($message);exit;
       }
       
       $comment = new IModel('comment');
       $data = $comment->getObj('id='.$pid);
       if(!$data)
       {
           $message = array('status' => 0, 'msg' => '系统错误');
           echo JSON::encode($message);exit;
       }
       unset($data['id']);                         
       $data['pid'] = $pid;
       $data['p_id'] = $data['p_id'].$pid.',';
       $data['contents'] = $content;
       $data['point'] = 0;
       $data['user_id'] = $this->user['user_id'];

       $data['comment_time'] = date("Y-m-d",ITime::getNow());
       $comment->setData($data);      
       $res = $comment->add();    

        if($res)
        {                                                   
            $message = array('status' => 1, 'msg' => '评论成功');
        }
        else
        {
            $message = array('status' => 0, 'msg' => '评论失败');
        } 
        
        echo JSON::encode($message);
    }
    
	//购买记录ajax获取
	function history_ajax()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;

		$orderGoodsDB = new IQuery('order_goods as og');
		$orderGoodsDB->join   = 'left join order as o on og.order_id = o.id left join user as u on o.user_id = u.id';
		$orderGoodsDB->fields = 'o.user_id,og.goods_price,og.goods_nums,o.create_time as completion_time,u.username,u.email,u.phone';
		/*$orderGoodsDB->where  = 'og.goods_id = '.$goods_id.' and (o.status = 5 or o.status = 11)';*/
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
    
    //咨询数据ajax获取
    function cir_refer_ajax()
    {
        $goods_id = IFilter::act(IReq::get('goods_id'),'int');
        $page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;
        $pid     = IFilter::act(IReq::get('pid'),'int') ? IReq::get('pid') : 0;
        
        //咨询类型
        $refer_type = new IModel('refer_type');
        $tid = $refer_type->query('is_open=1', 'id', 'sort', 'ASC', 1);
        $type = IReq::get('type') ? IFilter::act(IReq::get('type'),'int') : $tid[0]['id'];

        $referDB = new IQuery('refer as r');
        $referDB->join = 'left join user as u on r.user_id = u.id';
        $referDB->where = 'r.goods_id = '.$goods_id.' and r.pid='.$pid.' and r.user_id <> -1 and r.type='.$type;
        $referDB->order = 'r.id desc';
        $referDB->fields = 'u.username,u.head_ico,r.id,r.time,r.question,r.reply_time,r.answer';
        $referDB->page = $page;
        $data = $referDB->find();
        $refer = new IModel('refer');
        foreach($data as $k =>$v)
        {
            $temp = $refer->query('goods_id = '.$goods_id.' and pid='.$v['id'].' and user_id <> -1', 'count(1) as num');
            $data[$k]['reply'] = !!$temp ? $temp[0]['num'] : 0;
            
            //后台回复内容
            if($pid == 0)
            {
                $reply = $refer->query("goods_id = $goods_id and p_id LIKE '%,{$v['id']}%' and user_id = -1");
                foreach($reply as $key => $val)
                {
                    if($val['seller_id'])
                    {
                        $seller_name = API::run('getSellerInfo',$val['seller_id'],'true_name');
                    }
                    $reply[$key]['seller_name'] = isset($seller_name['true_name']) ? $seller_name['true_name'] : '山城速购';
                    unset($seller_name);
                    $temp = $refer->query('goods_id = '.$goods_id.' and pid='.$val['id'].' and user_id <> -1', 'count(1) as num');
                    $reply[$key]['reply'] = !!$temp ? $temp[0]['num'] : 0;
                }
                $data[$k]['replyData'] =  $reply;
            }
            $data[$k]['username'] = $v['username'] ? $v['username'] : '游客';
        }  
        $pageHtml = $referDB->getPageBar("javascript:void(0);",'onclick="cir_refer_ajax([page])"');
        echo JSON::encode(array('data' => $data,'pageHtml' => $pageHtml));
    }
    
    //回复咨询
    function question_reply()
    {
        $pid     = IFilter::act(IReq::get('pid'),'int');
        $question = IFilter::act(IReq::get('question'),'content');    
        if(!trim($question))
        {
            $message = array('status' => 0, 'msg' => '咨询内容不能为空');
            echo JSON::encode($message);exit;
        }
        
        $refer = new IModel('refer');
        $data = $refer->getObj('id='.$pid);
        if(!$data)
        {
            $message = array('status' => 0, 'msg' => '系统错误');
            echo JSON::encode($message);exit;
        }
        unset($data['id']);
        $data['question'] = $question;
        $data['pid'] = $pid;
        $data['p_id'] = $data['p_id'].$pid.',';
        $data['user_id'] = isset($this->user['user_id']) ? $this->user['user_id'] : 0;
        $data['time'] = ITime::getDateTime();

        $refer->setData($data);
        $res = $refer->add();

        if($res)
        {
            $message = array('status' => 1, 'msg' => '回复成功');
        }
        else
        {
            $message = array('status' => 0, 'msg' => '回复失败');
        }
        echo JSON::encode($message);
    }

	//评论列表页
	function comments_list()
    {
        $id   = IFilter::act(IReq::get("id"),'int');
        $type = IFilter::act(IReq::get("type"));

        $this->data=Comment_Class::get_comment_byid($id,$type,10,$this);
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
        if($this->comment['status'] == 1)
        {
            $photo = new IModel('comment_photo');
            $this->photoList = $photo->query('comment_id='.$this->comment['id'], 'img', 'sort', 'desc');
        }
		$this->comment_info = Comment_Class::get_comment_info($this->comment['goods_id']);
		$goods_id=$this->comment['goods_id'];
		$goods_db = new IModel('goods');
		$this->goodsRow = $goods_db->getObj('id='.$goods_id);
        $this->upload_url = 'site/comment_img_upload';
		//print_r($this->comment);
		$this->redirect("comments");
	}
    
    /**
     * @brief 评论图片上传的方法
     */
    public function comment_img_upload()
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
		$imgList = IFilter::act(IReq::get('imgList'),'string');
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
            
            //处理评论图片
            if($imgList)
            {
                $imgListArr = explode(',', $imgList);
                $photo = new IModel('comment_photo');
                foreach($imgListArr as $k => $v)
                {
                    $para['comment_id'] = $id;
                    $para['img'] = $v;
                    $para['sort'] = $k;
                    $photo->setData($para);
                    $photo->add();
                }
            }
			echo "success";
		}
		else
		{
			die("评论失败");
		}
	}
    
    /**
     * @brief 进行商品追评 ajax操作
     */
    public function comment_replySelf()
    {
        if(!isset($this->user['user_id']) || $this->user['user_id']===null)
        {
            die("未登录用户不能评论");
        }

        if(IReq::get('pid')===null)
        {
            die("传递的参数不完整");
        }                   
        $tb_comment = new IModel("comment");
        $content = IFilter::act(IReq::get("contents"),'content');      
        $pid               = IFilter::act(IReq::get('pid'),'int');
        $data = $tb_comment->getObj('id='.$pid);
        if(!$data)
        {
            die("系统错误");
        }
        unset($data['id']);                         
        $data['pid'] = $pid;
        $data['p_id'] = 0;
        $data['point'] = 0;                       
        $data['comment_time'] = date("Y-m-d",ITime::getNow());      
        $data['contents'] = $content;                                 
        $imgList = IFilter::act(IReq::get('imgList'),'string');
        $data['status']   = 1;          

        if(!$content && !$imgList)
        {
            die('请输入内容');
        }
        $can_submit = Comment_Class::can_comment($pid,$this->user['user_id']);
        if($can_submit[0]!=1)
        {
            die("您不能评论此件商品");
        }                                    
        $tb_comment->setData($data);
        $re = $tb_comment->add();        
        if($re)
        {
            $comment['status'] = 2;
            $comment['recontents'] = $re;
            $tb_comment->setData($comment);
            $tb_comment->update('id='.$pid);           
            //处理评论图片
            if($imgList)
            {
                $imgListArr = explode(',', $imgList);
                $photo = new IModel('comment_photo');
                foreach($imgListArr as $k => $v)
                {
                    $para['comment_id'] = $re;
                    $para['img'] = $v;
                    $para['sort'] = $k;
                    $photo->setData($para);
                    $photo->add();
                }
            }
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

	function ce(){
		print_r($_SESSION);
	}
    
    //获取组合销售商品信息
    function getCombineInfo()
    {
        $goods_id = IReq::get('id');
        $ids = IReq::get('ids');
        
        $tb_goods = new IModel('goods');
        $product_db = new IModel('products');
        
        //主商品信息
        $goods_info = $tb_goods->getObj('id='.$goods_id." AND is_del=0 and type <> 1", 'id,name,img,spec_array,store_nums,combine_price,sell_price');
        if(!$goods_info)
        {
            die("参数错误!");
        }
        $product_db = new IModel('products');
        $goods_info['product'] = $product_db->query('goods_id='.$goods_info['id'],'id,spec_array,store_nums');
        $goods_info['product'] = JSON::encode($goods_info['product']);
        $spec = 0;
        if($goods_info['spec_array'])
        {
            $spec = 1;
            $specArray = JSON::decode($goods_info['spec_array']);
            foreach($specArray as $k=>$v)
            {
                $specArray[$k]['specVal'] = explode(',',trim($v['value'],','));
            }
            $goods_info['spec_array'] = $specArray;
        }
    
        //商品图片
       /* $tb_goods_photo->where =' g.goods_id='.$goods_id;
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
        //获得扩展属性
        $tb_attribute_goods->where = "goods_id='".$goods_id."' and attribute_id!=''";
        $goods_info['attribute'] = $tb_attribute_goods->find();        */
        
        $goodsList = $tb_goods->query('id in ('.$ids.') AND (is_del=0 or is_del=4)', 'id,name,img,spec_array,store_nums,combine_price,sell_price');
        foreach($goodsList as $k=>$v)
        {
            $v['product'] = $product_db->query('goods_id='.$v['id'],'id,spec_array,store_nums');
            $goodsList[$k]['product'] = JSON::encode($v['product']); 
            if($v['spec_array'])
            {
                $spec = 1;
                $specArray = JSON::decode($v['spec_array']);
                foreach($specArray as $key=>$val)
                {
                    $specArray[$key]['specVal'] = explode(',',trim($val['value'],','));
                }
                $goodsList[$k]['spec_array'] = $specArray;
            }                              
           /* $tb_goods_photo->where =' g.goods_id='.$v['id'];
            $photo = $tb_goods_photo->find();
            foreach($photo as $key => $val)
            {
                //对默认第一张图片位置进行前置
                if($val['img'] == $v['img'])
                {
                    $temp = $photo[0];
                    $photo[0] = $val;
                    $photo[$key] = $temp;
                }
            }
            $goodsList[$k]['photo'] = $photo;
            $tb_attribute_goods->where = "goods_id='".$v['id']."' and attribute_id!=''";
            $goodsList[$k]['attribute'] = $tb_attribute_goods->find();*/
        }                
        array_unshift($goodsList, $goods_info);
        echo JSON::encode(array('data' => $goodsList, 'spec' => $spec));
    }
	function match_sale()
    {
        $goods_id = IFilter::act(IReq::get('id'), 'int');
        $combine = new IModel('combine_goods');
        
        //主商品
        $tb_goods = new IModel('goods');
        $goods_info = $tb_goods->getObj('id='.$goods_id." AND (is_del=0 or is_del=4)");
        if(!$goods_info)
        {
            IError::show(403,"这件商品不存在或已下架");
            exit;
        }
        $product_db = new IModel('products');
        $goods_info['product'] = $product_db->query('goods_id='.$goods_info['id'],'id,spec_array,store_nums');
        $goods_info['product'] = JSON::encode($goods_info['product']);
        if($goods_info['is_del']==4)header('location:'.IUrl::getHost().IUrl::creatUrl('pregoods/products/id/'.$goods_id));
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
        $this->goodsImg = current($goods_info['photo']);
        $this->setRenderData($goods_info);
        $combineList = $combine->query('goods_id = '.$goods_id.' and status = 1', '*', 'sort', 'asc');
        
        foreach($combineList as $k => $v)
        {
            if(!$v['combine'])
            {
                unset($combineList[$k]);
                continue;
            }
            $goodsList = $tb_goods->query('id in ('.$v['combine'].") AND is_del=0 and type <> 1", 'id,name,combine_price,sell_price,img,spec_array,store_nums');
            if($goodsList)
            {
                $combineList[$k]['goodsList'] = $goodsList;
            }
            else
            {
                unset($combineList[$k]);
            }   
        }
        $this->combineList = $combineList;
        $this->redirect('match_sale');
    }
    
    //活动页面
    function active()
    {
        $id = IFilter::act(IReq::get('id'), 'int');
        $active = new IModel('active');
        $detail = $active->getObj('id='.$id.' and status = 1');
        if(!$detail)
        {
            IError::show(403,"活动不存在");
            exit;
        }
        $para = JSON::decode($detail['extendpara']);
        $detail['top'] = isset($para['top']) ? $para['top'] : '';
        $detail['main'] = isset($para['main']) ? $para['main'] : '';
        $detail['float'] = isset($para['float']) ? $para['float'] : '';
        $detail['floatimage'] = isset($para['floatimage']) ? $para['floatimage'] : '';
        $detail['link'] = isset($para['link']) ? $para['link'] : '';
        $detail['topImage'] = isset($para['topImage']) ? $para['topImage'] : '';
        $group = new IModel('active_group');
        $groupDetail = $group->query('active_id='.$id,'*','sort', 'asc');
        $this->group = $groupDetail;
        $goods = new IQuery('group_goods as gg');
        $goods->join = "left join goods as g on gg.goods_id = g.id";
        $goods->fields = "gg.*,g.name,g.img,truncate(g.sell_price,1) as sell_price,truncate(g.market_price,1) as market_price,g.short_desc,g.brand_id";
        $goods->order = 'gg.sort asc';       
        foreach($groupDetail as $k => $v)
        {  
            $goods->where = "(g.is_del = 0 or g.is_del = 4) and (((type = 1 and past_time > now()) or (type = 1 and past_time = '0000-00-00')) or type <> 1) and gg.group_id =".$v['id'];
            $groupDetail[$k]['goodsList'] = $goods->find();
        }
        $this->setRenderData($detail);
        $this->groupDetail = $groupDetail;
        if(IClient::getDevice()=='mobile'){
            $this->redirect('active');
        }
        else
        {
            if($detail['type'] == 1)
            {
                $this->redirect('active1', false);
            }
            elseif($detail['type'] == 2)
            {
                $this->redirect('active2', false);
            }
            elseif($detail['type'] == 3)
            {
                $this->redirect('active3', false);
            }
            else
            {
                $activeList = $active->query('id != '.$id.' and seoimage is not null', '*', 'rand()', 'desc', 2);
                $this->activeList = $activeList;
                $this->redirect('active4', false);
            }
        }                          
    }
    
    //微信公众号支付处理
    function wapPayCode()
    {
        $appId = Payment::getConfigParam(12, 'M_merId');
        $secret = Payment::getConfigParam(12, 'M_certPwd');
        $jsApiParameters=IReq::get('jsApiParameters');
        $para = JSON::decode($jsApiParameters);
        $pay_level=IReq::get('pay_level');
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$secret}";
        $response = $this->get_contents($url);
        $result = json_decode($response,true);
        unset($response);
        unset($url);
        if(isset($result['access_token']) && $result['access_token'])
        {
            ISafe::set('wxPayAccessToken', $result['access_token']);
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$result['access_token']}&type=jsapi";
            unset($result);
            $response = $this->get_contents($url);
            $result = json_decode($response,true);
            var_dump($result);
            if(isset($result['errcode']) && $result['errcode'] == 0)
            {
                $jsapi_ticket = $result['ticket'];
                ISafe::set('wxPayJsapiTicket', $jsapi_ticket);
                var_dump($jsapi_ticket);
                $str = "jsapi_ticket={$jsapi_ticket}&noncestr={$para['nonceStr']}&timestamp={$para['timeStamp']}&url=http://www.yqtvt.com/test/site/wapPayCode";
                var_dump($str);
                $this->signature = sha1($str);
            }
        }
        $this->para = $para;
        $this->pay_level = $pay_level;
        $this->redirect('wapPayCode');
    }
    
    public function get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response =  curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
