<?php
/**
 * Created by PhpStorm.
 * User: wangzhande
 * Date: 2016/6/21
 * Time: 9:49
 */
class mobileSearch extends IController{
    //关键词
    public function getKeyWord(){
        $keyWordObj=new IQuery('keyword');
        $keyWordObj->where='hot=1';
        $keyWordObj->fields='word';
        $keyWordObj->order='`order` asc';
        $res=$keyWordObj->find();
        echo JSON::encode($res);
    }

    /**
     * 关键词搜索列表
     */
    public function searchList(){
        $word=IFilter::act(IReq::get('word','post'));
        if(!$word) {
            $word = IFilter::act(IReq::get('word'));
        }
       // var_dump($word);
       // $time=IFilter::act(IReq::get('time','post'));
       // $page=IFilter::act(IReq::get('page','post'));
        if(preg_match("|^[\w\x7f\s*-\xff*]+$|",$word)){
            //搜索关键字
            $tb_sear     = new IModel('search');
            $search_info = $tb_sear->getObj('keyword = "'.$word.'"','id');
            $wordWhere[]     = ' name like "%'.$word.'%" or find_in_set("'.$word.'",search_words) ';
            $goodsDB = new IModel('goods as go');
            $goodsCondData = $goodsDB->query(join(" and ",$wordWhere),"id");
            $goodsCondId = array();
            foreach($goodsCondData as $key => $val)
            {
                $goodsCondId[] = $val['id'];
            }
            $GoodsId=null;
            $GoodsId = array_unique($goodsCondId);
            $where="(go.is_del=0 or go.is_del=4)";
            if(count($GoodsId)!=0){
            $where .= " and go.id in (".join(',',$GoodsId).") ";
            }else{$where.=' and false';}

            $goodsObj=new IQuery('goods as go');
           // $goodsObj->join='left join commend_goods as c on c.goods_id=go.id';
            $goodsObj->fields   = ' go.id as goods_id,go.name,go.is_del,go.comments,go.grade,go.goods_no,go.sell_price,go.market_price,go.store_nums,go.img,go.sale,go.seller_id,go.sale,go.grade,go.up_time ';
            $goodsObj->where=$where;
            $goodsObj->order='go.sort asc';
            $res=$goodsObj->find();
            if($search_info){
                    $tb_sear->setData(array('num'=>'num + 1'));
                    $tb_sear->update('id='.$search_info['id'],'num');
            }else{
                $tb_sear->setData(array('keyword'=>$word,'num'=>1));
                $tb_sear->add();
            }
            foreach($res as $k=>$v){
                $res[$k]['up_time']=strtotime($v['up_time']);
                $res[$k]['img']='http://v.yqrtv.com:8080/app/'.$v['img'];
            }
            die(JSON::encode($res));

        }else{
            die(JSON::encode(array()));
        }

    }
    public function test(){
        $arr=array(
            'form_title'=>'手术单',
            'form_desc'=>'说明可以出院了',
            'formitemlist'=>
            array(
                array(
            'item_type'=>4,
            'item_title'=>'tt',
            'item_content'=>array('时间'=>'2015-5-16')
                  ),
                array(
                    'item_type'=>4,
                    'item_title'=>'tt',
                    'item_content'=>array('时间'=>'2015-5-16')
                )

            )

        );



    }
}