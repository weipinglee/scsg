<?php
require_once dirname(__FILE__)."/lib/WxPay.Config.php";
require_once dirname(__FILE__)."/lib/WxPay.Api.php";        
require_once dirname(__FILE__)."/WxPay.JsApiPay.php";      
/**
 * @file scan_wechat.php
 * @brief 微信二维码插件类
 * @date 2016-6-29 15:23:04
 * @version 1.0.0
 * @note
 */

/**
 * @class scan_wechat
 * @brief 微信二维码插件类
 */
class wap_wechat extends paymentPlugin
{
    //支付插件名称
    public $name = '微信二维码';

    /*
     * @param 获取配置参数
     */
    public function configParam()
    {
        $result = array(
            'M_merId'  => 'APPID',
            'M_mchid'  => '商户号',
            'M_key'  => '商户支付密钥',
            'M_certPwd' => '签名证书密码',
        );
        return $result;
    }
    
    /**
     * @see paymentplugin::notifyStop()
     */
    public function notifyStop()
    {
        echo "success";
    }
    
    /**
     * @see paymentplugin::getSubmitUrl()
     */
    public function getSubmitUrl()
    {
        return 'http://www.yqtvt.com/test/site/wapPayCode';
    }
    
    /**
     * @see paymentplugin::getRefundUrl()
     */
    public function getRefundUrl(){
        
    }
    
    public function callback($callbackData,&$paymentId,&$money,&$message,&$orderNo){}
    
    public function serverCallback($callbackData,&$paymentId,&$money,&$message,&$orderNo){}
    
    public function getSendData($payment)
    { 
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();

        //②、统一下单
        $payModel = new IModel('payment');
        $payPara = $payModel->getField('id='.$payment['M_Paymentid'], 'config_param');
        $paraData = JSON::decode($payPara);
        $input = new WxPayUnifiedOrder();
        $input->SetBody($payment['R_Name']);
        $input->SetAttach($payment['R_Name']);
        $M_mchid = $paraData['M_mchid'] ? $paraData['M_mchid'] : WxPayConfig::MCHID;
        $input->SetOut_trade_no($M_mchid.date("YmdHis"));
        $input->SetTotal_fee($payment['M_Amount']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag('');
        $input->SetNotify_url($this->wapWecheatCallbackUrl);
        $input->SetTrade_type("JSAPI");
        $input->SetProduct_id($payment['M_OrderId']);
        $input->SetAppid($paraData['M_merId']);
        $input->SetMch_id($M_mchid);   
        $input->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        //获取共享收货地址js函数参数
       // $editAddress = $tools->GetEditAddressParameters(); 
        if(isset($payment['pay_level']))
        {
            $pay_level = $payment['pay_level'] ? $payment['pay_level'] : 2;
            return(array('jsApiParameters' => $jsApiParameters,'pay_level' => $pay_level,'order_id' => $payment['M_OrderNO'], 'product_id' => $M_mchid.date("YmdHis"),'pay_total' => $payment['M_Amount']*100,));
        }
        else
        {
            return(array('jsApiParameters' => $jsApiParameters,'order_id' => $payment['M_OrderNO'], 'product_id' => $M_mchid.date("YmdHis"),'pay_total' => $payment['M_Amount']*100,));
        }
    }
    
    //记录交易号
    function recordTN($orderNo, $tradeNo, $pay_level)
    {
        $this->recordTradeNo($orderNo, $tradeNo, $pay_level);
    }
    
    function addTradeData($tradeData)
    {
        $this->addTrade($tradeData);
    }
    
    //退款
    function refund($payment)
    {
        $payModel = new IModel('payment');
        $payPara = $payModel->getField('id=12', 'config_param');
        $paraData = JSON::decode($payPara);     
        $M_mchid = $paraData['M_mchid'] ? $paraData['M_mchid'] : WxPayConfig::MCHID;
        $tradeObj = new IModel('trade_record');
        $total = $tradeObj->getField('trade_no = "'.$payment['M_Trade_NO'].'"', 'money');
        $out_trade_no = $payment["M_Trade_NO"];
        $refund_fee = $payment["M_Amount"]*100;
        $input = new WxPayRefund();
        $input->SetOut_trade_no($out_trade_no);
        $total = $total ? $total : 0;
        $input->SetTotal_fee($total*100);
        $input->SetRefund_fee($refund_fee);
        $input->SetOut_refund_no($M_mchid.date("YmdHis"));
        $input->SetOp_user_id($M_mchid);
        print_r($input);
        $result = WxPayApi::refund($input);
        print_r($payment);
        print_r($result);
        if(isset($result['result_code']) && $result['result_code'] == 'SUCCESS')
        {
            $resArr = array(
                'order_no'     => $payment['M_Order_NO'],
                'trade_no'        => $result['out_refund_no'],
                'orig_trade_no'        => $result['out_trade_no'],
                'trade_type'   => 2,
                'money'        => $result['cash_refund_fee']/100,
                'pay_type'     => 12,
                'trade_status' => 1,
                'time'         => date('Y-m-d H:i:s')
            );
        
            self::addTrade($resArr);
            return true;
        }
        return false;
    }
}

