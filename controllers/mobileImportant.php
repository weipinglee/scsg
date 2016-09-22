    <?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/9
 * Time: 11:06
 */
class mobileImportant extends IController
{
    /**
     *加入购物车
     */
    public function addCart()
    {
        $token = IFilter::act(IReq::get('token', 'post'));
        $tokenObj = new IModel('token');
        if (!$tokenInfo = $tokenObj->getObj('token=\'' . $token . '\'')) {
            die(JSON::encode(['code' => 0, 'info' => '请登录']));
        }
        // $goods_id=IFilter::act(IReq::get('goods_id','post'),'int')?IFilter::act(IReq::get('goods_id','post'),'int'):IFilter::act()
        if (IFilter::act(IReq::get('goods_id', 'post'), 'int') == '') {
            $goods_id = IFilter::act(IReq::get('product_id', 'post'), 'int');
            $type = 'product';
        } else {
            $goods_id = IFilter::act(IReq::get('goods_id', 'post'), 'int');
            $type = 'goods';
        }
        if ($goods_id == '') {
            die(JSON::encode(['code' => 0, 'info' => '参数不正确']));
        }

        $goods_num = IFilter::act(IReq::get('goods_num', 'post'), 'int');
        $cartObj = new Cart($tokenInfo['user_id']);
        // $cartObj=new Cart(137);
        $result = $cartObj->add($goods_id, $goods_num, $type);
        if ($result == false) {
            die(JSON::encode(['code' => 0, 'info' => $cartObj->getError()]));
        } else {
            die(JSON::encode(['code' => 1, 'info' => '添加成功']));
        }
    }

    //删除购物车数据
    public function delCart()
    {
        $token = IFilter::act(IReq::get('token', 'post'));
        $tokenObj = new IModel('token');
        if (!$tokenInfo = $tokenObj->getObj('token=\'' . $token . '\'')) {
            die(JSON::encode(['code' => 0, 'info' => '请登录']));
        }
        if (IFilter::act(IReq::get('goods_id', 'post'), 'int') == '') {
            $goods_id = IFilter::act(IReq::get('product_id', 'post'), 'int');
            $type = 'product';
        } else {
            $goods_id = IFilter::act(IReq::get('goods_id', 'post'), 'int');
            $type = 'goods';
        }
        if ($goods_id == '') {
            die(JSON::encode(['code' => 0, 'info' => '参数不正确']));
        }
        $cartObj = new Cart($tokenInfo['user_id']);
        $cartObj->del(0, $type . '-' . $goods_id);
        die(JSON::encode(['code' => 1, 'info' => '删除成功']));
    }

    public function delCartMany()
    {
        $token = IFilter::act(IReq::get('token', 'post'));
        $tokenObj = new IModel('token');
        if (!$tokenInfo = $tokenObj->getObj('token=\'' . $token . '\'')) {
            die(JSON::encode(['code' => 0, 'info' => '请登录']));
        }
    }

    //获取购物车数据
    public function getMyCartInfo()
    {
        $token = IFilter::act(IReq::get('token', 'post'));
        $tokenObj = new IModel('token');
        if (!$tokenInfo = $tokenObj->getObj('token=\'' . $token . '\'')) {
            die(JSON::encode(['code' => 0, 'info' => '请登录']));
        }
        $cartObj = new cart($tokenInfo['user_id']);
        $cartData = $cartObj->getMyCart();
        $countObj = new CountSum();
        $cartList = $countObj->goodsCount($cartData);
        $goodsList = $this->goodsListBySeller($cartList['goodsList']);
        echo JSON::encode($goodsList);
    }

    public function getMyCartInfoIos()
    {
        $token = IFilter::act(IReq::get('token', 'post'));
        $tokenObj = new IModel('token');
        if (!$tokenInfo = $tokenObj->getObj('token=\'' . $token . '\'')) {
               die(JSON::encode(array()));
        }
        $cartObj=new cart($tokenInfo['user_id']);
       // $cartObj = new cart(137);
        $cartData = $cartObj->getMyCart();
        $countObj = new CountSum();
        $cartList = $countObj->goodsCount($cartData);
        if (!empty($cartList['goodsList'])) {
            $goodsList = $cartList['goodsList'][0];
            $result = array();
            foreach ($goodsList as $k => $value) {
                if ($value['seller_id'] == 0) {
                    $goodsList[$k]['seller_name'] = '平台';
                } else {
                    $seller_data = API::run('getSellerInfo', $value['seller_id'], 'true_name');
                    $seller_logo = API::run('getSellerInfo', $value['seller_id'], 'logo_img');
                    $goodsList[$k]['seller_name'] = $seller_data['true_name'];
                    $goodsList[$k]['seller_log'] = $seller_logo['logo_img'];
                }
                $result[$goodsList[$k]['seller_name']] = array();
            }

            foreach ($goodsList as $kk => $vv) {
                foreach ($result as $kkk => $vvv) {
                    if ($kkk == $vv['seller_name']) {
                        $result[$kkk][] = $vv;
                    }
                }

            }
            die(JSON::encode($result));
        } else {
            die(JSON::encode([]));
        }

        //echo "<pre>";
        //print_r($result);
    }


    /*将商品列表按商家分开
 * @return array array('seller_name'=>商家名，'weight'=>商品重量,'total_price'=>总价,[0]=>array(商品数据),)
 */
    private function goodsListBySeller($goodsListCombine)
    {
        $goodsListSeller = array();
        $result = array();
        foreach ($goodsListCombine as $buy => $goodsList) {
            foreach ($goodsList as $key => $value) {
                if (!isset($goodsListSeller[$value['seller_id']])) {
                    $goodsListSeller[$value['seller_id']]['weight'] = 0;
                    $goodsListSeller[$value['seller_id']]['total_price'] = 0;
                    $goodsListSeller[$value['seller_id']]['delivery'] = 0;
                    $goodsListSeller[$value['seller_id']]['seller_id'] = $value['seller_id'];
                    if ($value['seller_id'] == 0) {
                        $goodsListSeller[$value['seller_id']]['seller_name'] = '平台';
                    } else {
                        $seller_data = API::run('getSellerInfo', $value['seller_id'], 'true_name');
                        $seller_logo = API::run('getSellerInfo', $value['seller_id'], 'logo_img');
                        $goodsListSeller[$value['seller_id']]['seller_name'] = $seller_data['true_name'];
                        $goodsListSeller[$value['seller_id']]['logo_img'] = $seller_logo['logo_img'];
                    }
                }

                $goodsListSeller[$value['seller_id']]['total_price'] += (($value['sell_price'] - $value['reduce']) * $value['count']);
                $goodsListSeller[$value['seller_id']]['weight'] += $value['weight'] * $value['count'];
                $goodsListSeller[$value['seller_id']]['delivery'] += $value['delivery'];
                if (isset($value['product_id']) && $value['product_id'] != 0) {
                    $value['style'] = 'product';
                } else {
                    $value['style'] = 'goods';
                }
                $goodsListSeller[$value['seller_id']]['data'][] = $value;
                $result = $goodsListSeller;

                /*$goodsListSeller[$value['seller_id']]['total_price'] +=(($value['sell_price']-$value['reduce'])*$value['count']);
                $goodsListSeller[$value['seller_id']]['weight'] += $value['weight']*$value['count'];
                $goodsListSeller[$value['seller_id']][] = $value;*/
            }
        }
        return array_values($goodsListSeller);
    }

    public function test4()
    {
        $json = '{"promotion":[{"id":"18","type":"1","plan":"促销","info":"购物满￥15 优惠￥2.00","hide":1},{"id":"2","type":"1"
,"plan":"100","info":"购物满￥10 优惠￥1.00","hide":0},{"id":"21","type":"7","plan":"满额送经验","info":"购物满￥50
立加20.00经验","hide":1},{"id":"19","type":"6","plan":"免运费","info":"购物满￥15 免运费","hide":1}],"proReduce":"1
.00"}';
        echo $json;
        echo "<pre>";
        $res = json_decode($json);
        var_dump($res);
    }

    public function test5()
    {
        echo JSON::encode($_POST);
    }

    public function removeCartMany()
    {
        $token = IFilter::act(IReq::get('token', 'post'));
        $tokenObj = new IModel('token');
        if (!$tokenInfo = $tokenObj->getObj('token=\'' . $token . '\'')) {
            die(JSON::encode(['code' => 0, 'info' => '请登录']));
        }
        $data = IFilter::act(IReq::get('str'));
        //   $data='0-goods-60|0-goods-72|0-goods-65';
        $arr = explode('|', $data);
        $delCart = array();
        foreach ($arr as $val) {
            $tem = explode('-', $val);;
            if ($val == '') {
                continue;
            }
            $delCart[$tem[0]][] = $tem[1] . '-' . $tem[2];
        }

        $cartObj = new Cart($tokenInfo['user_id']);
        $delResult = $cartObj->del_many($delCart);
        if ($delResult) {
            die(JSON::encode(['code' => 1, 'info' => '删除成功']));
        } else die(JSON::encode(['code' => 0, 'info' => '删除失败']));
    }

    public function removeCartManyIos()
    {
        $token = IFilter::act(IReq::get('token', 'post'));
        $tokenObj = new IModel('token');
        if (!$tokenInfo = $tokenObj->getObj('token=\'' . $token . '\'')) {
            die(JSON::encode(['code' => 0, 'info' => '请登录']));
        }
        $data = IFilter::act(IReq::get('str'));
        if (!$data) {
            die(JSON::encode(['code' => 0, 'info' => '参数不正确']));
        }
        // $data=$idArray['str'];
        // $data='0-goods-65-1|0-goods-72|0-goods-65';
        // $data=explode('|',$data);
        $delCart = array();
        foreach ($data as $val) {
            $tem = explode('-', $val);;
            if ($val == '') {
                continue;
            }
            $delCart[$tem[0]][] = $tem[1] . '-' . $tem[2];
        }

        $cartObj = new Cart($tokenInfo['user_id']);
        //$cartObj=new Cart(137);
        $delResult = $cartObj->del_many($delCart);
        if ($delResult) {
            die(JSON::encode(['code' => 1, 'info' => '删除成功']));
        } else die(JSON::encode(['code' => 0, 'info' => '删除失败']));

    }

    public function toBalance()
    {
        $token = IFilter::act(IReq::get('token', 'post'));
        $tokenObj = new IModel('token');
        if (!$tokenInfo = $tokenObj->getObj('token=\'' . $token . '\'')) {
            die(JSON::encode(['code' => 0, 'info' => '请登录']));
        }
        //计算商品
        $countSumObj = new CountSum($tokenInfo['user_id']);
        //$countSumObj=new CountSum(136);
        $sub = IFilter::act(IReq::get('sub', 'post'));
        // $sub='0-goods-72-1|0-product-148-1';
        if (!$sub) {
            die(JSON::encode(['code' => 0, 'info' => '参数不合法']));
        }

        $cartData = explode('|', $sub);
        if (empty($cartData)) die(JSON::encode(['code' => 0, 'info' => '没有数据']));
        $finalCartData = array();
        foreach ($cartData as $k => $v) {
            $tmp = explode('-', $v);

            $finalCartData[0][$tmp[1]]['id'][] = intval($tmp[2]);
            $finalCartData[0][$tmp[1]]['data'][intval($tmp[2])] = intval($tmp[3]);
        }
        $cartObj = new Cart($tokenInfo['user_id']);
        //$cartObj=new Cart(136);
        $myCartInfo = $cartObj->getMyCart($finalCartData);
        $res = $countSumObj->goodsCount($myCartInfo);
        if (is_string($res)) {
            die(JSON::encode(['code' => 0, 'info' => '没有数据']));
        }
        if ($res['sum'] == 0) {
            die(JSON::encode(['code' => 0, 'info' => '没有数据']));
        }


        /*      echo "<pre>";
              print_r($myCartInfo);
              print_r($result);*/
        // print_r($result);
        //获取收获地址
        $addObj = new IModel('address');
        // $address=$addObj->getObj('user_id='.$tokenInfo['user_id'].' and default=1');
        $addressList = $addObj->getObj('user_id=136 and shop_address.default =1');
        $temp = area::name($addressList['province'], $addressList['city'], $addressList['area']);
        if (isset($temp[$addressList['province']]) && isset($temp[$addressList['city']]) && isset($temp[$addressList['area']])) {
            $addressList['province_val'] = $temp[$addressList['province']];
            $addressList['city_val'] = $temp[$addressList['city']];
            $addressList['area_val'] = $temp[$addressList['area']];
        }
        $res['address'] = $addressList;
        $final = array();
        $final['final_sum'] = $res['final_sum'];//最终金额
        $final['promotion'] = $res['promotion'];//满足的活动
        $final['proReduce'] = $res['proReduce'];//优惠的金额
        $final['sum'] = $res['sum'];//原金额
        $final['goodsList'] = $this->goodsListBySeller($res['goodsList']);
        // $final['reduce']
        $res['goodsList'] = $this->goodsListBySeller($res['goodsList']);
        $res['freeFreight'] = $res['freeFreight'] ? 1 : 0;
        $res['code'] = 1;
        die(JSON::encode($res));
    }

    public function toBalanceIos()
    {
        $token = IFilter::act(IReq::get('token', 'post'));
        $tokenObj = new IModel('token');
        if (!$tokenInfo = $tokenObj->getObj('token=\'' . $token . '\'')) {
            die(JSON::encode(['code' => 0, 'info' => '请登录']));
        }
        //$tokenInfo['user_id']=137;
        //计算商品
        $countSumObj = new CountSum($tokenInfo['user_id']);
        //  $countSumObj=new CountSum(136);
        $sub = IFilter::act(IReq::get('sub', 'post'));
        //$sub='0-goods-72-1|0-product-148-1';
        if (!$sub) {
            die(JSON::encode(['code' => 0, 'info' => '参数不合法']));
        }
        /*    $sub=[
                 '0-goods-72-1',
                 '0-product-148-1'
             ];*/
        $cartData = $sub;
        if (empty($cartData)) die(JSON::encode(['code' => 0, 'info' => '没有数据']));
        $finalCartData = array();
        foreach ($cartData as $k => $v) {
            $tmp = explode('-', $v);

            $finalCartData[0][$tmp[1]]['id'][] = intval($tmp[2]);
            $finalCartData[0][$tmp[1]]['data'][intval($tmp[2])] = intval($tmp[3]);
        }
        $cartObj = new Cart($tokenInfo['user_id']);
        //$cartObj=new Cart(137);
        $myCartInfo = $cartObj->getMyCart($finalCartData);
        $res = $countSumObj->goodsCount($myCartInfo);
        if (is_string($res)) {
            die(JSON::encode(['code' => 0, 'info' => '没有数据']));
        }
        if ($res['sum'] == 0) {
            die(JSON::encode(['code' => 0, 'info' => '没有数据']));
        }


        /*      echo "<pre>";
              print_r($myCartInfo);
              print_r($result);*/
        // print_r($result);
        //获取收获地址
        $addObj = new IModel('address');
        $addressList = $addObj->getObj('user_id=' . $tokenInfo['user_id'] . ' and `default`=1');
        // $addressList=$addObj->getObj('user_id=136 and shop_address.default =1');
        $temp = area::name($addressList['province'], $addressList['city'], $addressList['area']);
        if (isset($temp[$addressList['province']]) && isset($temp[$addressList['city']]) && isset($temp[$addressList['area']])) {
            $addressList['province_val'] = $temp[$addressList['province']];
            $addressList['city_val'] = $temp[$addressList['city']];
            $addressList['area_val'] = $temp[$addressList['area']];
        }
        $goodsList = $res['goodsList'][0];
        $result = array();
        foreach ($goodsList as $k => $value) {
            if ($value['seller_id'] == 0) {
                $goodsList[$k]['seller_name'] = '平台';
            } else {
                $seller_data = API::run('getSellerInfo', $value['seller_id'], 'true_name');
                $seller_logo = API::run('getSellerInfo', $value['seller_id'], 'logo_img');
                $goodsList[$k]['seller_name'] = $seller_data['true_name'];
                $goodsList[$k]['seller_log'] = $seller_logo['logo_img'];
            }
            $result[$goodsList[$k]['seller_name']] = array();
        }

        foreach ($goodsList as $kk => $vv) {
            foreach ($result as $kkk => $vvv) {
                if ($kkk == $vv['seller_name']) {
                    $result[$kkk][] = $vv;
                }
            }

        }
        $res['address'] = $addressList;
        $final = array();
        $final['final_sum'] = $res['final_sum'];//最终金额
        $final['promotion'] = $res['promotion'];//满足的活动
        $final['proReduce'] = $res['proReduce'];//优惠的金额
        $final['sum'] = $res['sum'];//原金额
        //  $final['goodsList']=$this->goodsListBySeller($res['goodsList']);
        // $final['reduce']
        $res['goodsList'] = $this->goodsListBySeller($res['goodsList']);
        $res['goodsList'] = $result;
        $res['freeFreight'] = $res['freeFreight'] ? 1 : 0;
        $res['code'] = 1;
        die(JSON::encode($res));
    }

    public function getOrder()
    {
        $chg_time = 7 * 24 * 3600;//完成订单后换货期限
        $token = IFilter::act(IReq::get('token', 'post'));
        $tokenObj = new IModel('token');
              if(!$tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
                  die(JSON::encode(['code'=>0,'info'=>'请登录']));
              }
         $userid = $tokenInfo['user_id'];
        //$userid = 29;
        $page = IReq::get('page') ? IFilter::act(IReq::get('page'), 'int') : 1;
        $status_str = $seller_str = '';
        $order_no = IFilter::act(IReq::get('order_no'));
        $status = IFilter::act(IReq::get('status'), 'int');
        $beginTime = IFilter::act(IReq::get('beginTime'));
        $endTime = IFilter::act(IReq::get('endTime'));
        $seller_id = IFilter::act(IReq::get('seller_id'), 'int');
        if ($seller_id) {
            if ($seller_id == 2) {
                $seller_str = ' g.seller_id=0 ';
            } else if ($seller_id == 1) {
                $seller_str = ' g.seller_id!=0 ';
            }
        }
        if ($status && isset($status_array[$status])) {
            $status_arr = $status_array[$status];
            foreach ($status_arr as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $status_str .= $k . ' ' . $v . ' and ';
                    }
                    $status_str = substr($status_str, 0, -4);
                    $status_str .= ' OR  ';

                } else {
                    $status_str .= $key . ' ' . $val . ' and ';
                }

            }
            $status_str = ' (' . substr($status_str, 0, -4) . ') ';
        }
        $where = "o.user_id =" . $userid . " and o.if_del= 0 and o.type !=4 ";
        $where .= $status_str ? ' and ' . $status_str : '';
        $where .= $seller_str ? ' and ' . $seller_str : '';
        if ($beginTime) {
            $where .= ' and o.create_time > "' . $beginTime . '"';
        }
        if ($endTime) {
            $where .= ' and o.create_time < "' . $endTime . '"';
        }
        if ($order_no) $where .= ' and o.order_no=' . $order_no;
        $order_db = new IQuery('order as o');
        $order_db->join = 'left join order_goods as og on o.id=og.order_id left join goods as g on og.goods_id=g.id left join comment as c on og.comment_id=c.id';
        $order_db->group = 'og.id';
        $order_db->where = $where ? $where : 1;
        $order_db->page = $page;
        $order_db->order = 'o.id DESC';
        $order_db->fields = 'o.*,IF(o.status=5 && TIMESTAMPDIFF(second,o.completion_time,NOW())<' . $chg_time . ',1,0) as can_chg,c.status as comment_status,og.id as og_id,og.img,og.goods_id,og.product_id,og.real_price,og.goods_nums,og.goods_array,og.is_send,og.comment_id,og.refunds_status';
        $this->order_db = $order_db;
        //print_r($order_db->find());
        // var_dump($order_db->find());
        $this->initPayment();
        $data['s_beginTime'] = $beginTime;
        $data['s_endTime'] = $endTime;
        $data['s_order_no'] = $order_no;
        $data['s_status'] = $status;
        $data['s_seller_id'] = $seller_id;
        $res = $order_db->find();
        $orderList = array();
        foreach ($res as $k => $v) {
            $res[$k]['order_type']=Order_Class::getOrderStatus($v);
            if($res[$k]['order_type']==25&&$v['comment_status']==0){
                $res[$k]['order_type']=26;//待评价
            }
            if ($v['seller_id'] == 0) {
                $orderList[$v['id']]['seller_name'] = '平台';
            } else {
                $sellerInfo = Api::run('getSellerInfo', $v['seller_id'], 'seller_name');
                $orderList[$v['id']]['seller_name'] = $sellerInfo['seller_name'];
            }
          //  var_dump($k);var_dump($v['id']);
            $red[$k]['img']='http://v.yqrtv.com:8080/app/'.$v['img'];
            $orderList[$v['id']]['order_type'] = $res[$k]['order_type'];
            $orderList[$v['id']]['order_id']=$v['id'];
            $orderList[$v['id']]['order_amount'] = $v['order_amount'];
            $orderList[$v['id']]['payable_freight'] = $v['payable_freight'];
            $orderList[$v['id']]['goods_nums'] = $v['goods_nums'];
            $goods_array = JSON::decode($v['goods_array']);
            $res[$k]['goods_name'] = $goods_array['name'];
            $res[$k]['goodsno'] = $goods_array['goodsno'];
            $res[$k]['goods_value'] = $goods_array['value'];
            $res[$k]['img'] = 'http://v.yqrtv.com:8080/app/' . $v['img'];
            $res[$k]['goods_array'] = JSON::decode($v['goods_array']);
            $orderList[$v['id']]['data'][] = $res[$k];


        }
        die(JSON::encode(array_values($orderList)));
    }
    public function getOrderDetail(){
        $token=IFilter::act(IReq::get('token','post'));
        $tokenObj=new IModel('token');
        if(!$tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
            //die(JSON::encode(['code'=>0,'info'=>"请登录"]));
        }
        $tokenInfo['user_id']=29;
        $order_id=IFilter::act(IReq::get('order_id'),'int');
       // echo JSON::encode($_POST);die;
        if(!$order_id){
            die(JSON::encode(['code'=>0,'info'=>'参数不正确']));
        }
      //  var_dump($order_id);
        $orderObj=new Order_Class();
        $orderInfo=$orderObj->getOrderShow($order_id,$tokenInfo['user_id']);
        if(!$orderInfo){
            die(JSON::encode(['code'=>0,'info'=>'订单信息不存在']));
        }
        if($orderInfo['invoice']==1){
            $fapObj=new IModel('order_fapiao');
            $orderInfo['fapiao']=$fapObj->getObj('order_id='.$order_id);
        }
        //获取商品信息
        $tb_order_goods = new IQuery('order_goods as og');
        $tb_order_goods->join = 'left join goods as g on og.goods_id=g.id';
        $tb_order_goods->where = 'og.order_id='.$order_id;
        $tb_order_goods->group = 'og.id';
        $tb_order_goods->fields = 'g.type,g.sell_price,g.point,og.product_id,og.is_send,og.real_price,og.refunds_status,og.id as og_id,og.goods_id,og.img,og.goods_array,og.goods_nums,og.seller_id';
        $og_data = $tb_order_goods->find();
        foreach($og_data as $key=>$val){
            $og_data[$key]['goods_array']=JSON::decode($val['goods_array']);
            $og_data[$key]['goods_name']=$og_data[$key]['goods_array']['name'];
            $og_data[$key]['goods_no']=$og_data[$key]['goods_array']['goodsno'];
            $og_data[$key]['goods_value']=$og_data[$key]['goods_array']['value'];
            $og_data[$key]['img']='http://v.yqrtv.com:8080/app/'.$val['img'];
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
       $orderInfo['goods_data']=$og_data;
        $url='http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx';
        if(isset($orderInfo['freight'])) {
                $shipperCode = $orderInfo['freight']['freight_type'];
                $LogisticCode = $orderInfo['freight']['delivery_code'];
                $requestData = [
                    'OrderCode' => '',
                    'ShipperCode' => $shipperCode,
                    'LogisticCode' => $LogisticCode
                ];
                $requestData = JSON::encode($requestData);
                $sendDatas = [
                    'EBusinessID' => 1264567,
                    'RequestType' => '1002',
                    'RequestData' => urlencode($requestData),
                    'DataType' => '2',
                ];
                $sendDatas['DataSign'] = $this->encrypt($requestData, 'd804bd82-2f05-4d7b-a592-6e8324d6b4ce');
                $logistics = $this->sendPost('http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx', $sendDatas);
                $logistics=JSON::decode($logistics);
                $orderInfo['logistic'] = $logistics['Traces'];

                $orderInfo['logistic']=array_values($logistics['Traces']);
        }else{
            $orderInfo['logistic']=[];
        }
        echo JSON::encode($orderInfo);
    }
 /*   public function **
 *  post提交数据
 * @param  string $url 请求Url
 * @param  array $datas 提交的数据
 * @return url响应返回的html
 */
    public function sendPost($url, $datas) {
    $temps = array();
    foreach ($datas as $key => $value) {
        $temps[] = sprintf('%s=%s', $key, $value);
    }
    $post_data = implode('&', $temps);
    $url_info = parse_url($url);
    if(!isset($url_info['port'])||$url_info['port']=='')
    {
        $url_info['port']=80;
    }
   //echo $url_info['port'];
    $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
    $httpheader.= "Host:" . $url_info['host'] . "\r\n";
    $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
    $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
    $httpheader.= "Connection:close\r\n\r\n";
    $httpheader.= $post_data;
    $fd = fsockopen($url_info['host'], $url_info['port']);
    fwrite($fd, $httpheader);
    $gets = "";
    $headerFlag = true;
    while (!feof($fd)) {
        if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
            break;
        }
    }
    while (!feof($fd)) {
        $gets.= fread($fd, 128);
    }
    fclose($fd);

    return $gets;
}
    public  function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
    public function confirmOrder(){
        $id=IFilter::act(IReq::get('order_id'),'int');
        $token=IFilter::act(IReq::get('token','post'));
        $tokenObj=new IModel('token');
        if(!$tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
            die(JSON::encode(['code'=>0,'info'=>'请登录']));
        }
        if(!$id){
            die(JSON::encode(['code'=>0,'info'=>"参数不正确"]));
        }
       // $tokenInfo['user_id']=29;
        $model=new IModel('order');
        $model->setData(['status'=>5,'completion_time' => date('Y-m-d h:i:s')]);
        if($model->update("id = ".$id."  and user_id = ".$tokenInfo['user_id'])) {
            $orderRow = $model->getObj('id = ' . $id);

            //确认收货后进行支付
            Order_Class::updateOrderStatus($orderRow['order_no']);

            //增加用户评论商品机会
            Order_Class::addGoodsCommentChange($id);

            //经验值、积分、代金券发放
            Order_Class::sendGift($id, $tokenInfo['user_id']);
            die(JSON::encode(['code'=>1,'info'=>'操作成功']));
        }else{
            die(JSON::encode(['code'=>0,'info'=>'操作失败']));
        }
    }

}