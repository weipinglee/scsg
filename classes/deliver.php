<?php

/**
 * @file deliver.php
 * @brief 关于配送管理
 * @author
 * @date 2017-10-19
 * @version 0.1
 */

/**
 * @class deliver
 * @brief 配送管理模块
 */
class Deliver
{
    CONST INIT = 0;//初始
    CONST ACC  = 1;//配送员已接单
    CONST WORK = 2;//开始制作
    CONST COMP = 3;//制作完成，交付配送员

    private $tableName = 'order_deliver';

    private static $obj = null;

    public function __construct()
    {
        self::$obj = new IModel($this->tableName);
    }

    /**
     * 返回订单id的配送状态
     * @param $order_id int 订单id
     * @return array
     */
    public   function getDeliverStatus($order_id)
    {
        self::$obj = new IModel($this->tableName);
        $status = self::$obj->getField('order_id='.$order_id,'status');
        return $status;
    }

    /**
     * 配送员接单操作
     * @param $deliver_id int 配送员id
     * @param $order_id int 订单id
     */
    public function deliver_acc($deliver_id,$order_id)
    {
        $data = array(
            'order_id'=>$order_id,
            'status' => self::ACC,
            'deliver_id' => $deliver_id,
            'acc_time'   => ITime::getDateTime()
        );

       self::$obj->setData($data);
        if(!self::$obj->getField('order_id='.$order_id,'id')){
            $res = self::$obj->add();
        }
        else{
            $res = 1;
        }

        return $res;
    }

    /**
     * 商户通知开始制作
     * @param $order_id int 订单id
     * @return bool
     */
    public function deliver_work($order_id)
    {
        $data = array(
            'status' => self::WORK,
        );

        self::$obj->setData($data);

        if(self::$obj->update('order_id='.$order_id)){
            return true;
        }
        return false;
    }
}