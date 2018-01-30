<?php
require_once dirname(__FILE__)."/lib/WxPay.Config.php";
require_once dirname(__FILE__)."/lib/WxPay.Api.php";        
require_once dirname(__FILE__)."/WxPay.NativePay.php";      
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
class scan_wechat extends paymentPlugin
{
    //支付插件名称
    public $name = '微信二维码';

    public function __construct($payment_id)
    {
        parent::__construct($payment_id);
        $this->serverCallbackUrl   = IUrl::getHost().IUrl::creatUrl("/block/wecheat_server_callback/_id/".$payment_id);
    }

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
        return 'http://www.yqtvt.com/site/payCode';
    }
    
    /**
     * @see paymentplugin::getRefundUrl()
     */
    public function getRefundUrl(){
        
    }
    
    public function callback($callbackData,&$paymentId,&$money,&$message,&$orderNo){}
    
    public function serverCallback($callbackData,&$paymentId,&$money,&$message,&$orderNo){}
    public function server_callback($callbackData,&$paymentId,&$money,&$message,&$orderNo,$pay_level)
    {
//        $postXML      = file_get_contents("php://input");
//        $callbackData = $this->converArray($postXML);

        if(isset($callbackData['return_code']) && $callbackData['return_code'] == 'SUCCESS')
        {
            //除去待签名参数数组中的空值和签名参数
            $para_filter = $this->paraFilter($callbackData);

            //对待签名参数数组排序
            $para_sort = $this->argSort($para_filter);

            //生成签名结果
            $mysign = $this->buildMysign($para_sort,Payment::getConfigParam($paymentId,'key'));

            //验证签名
            if($mysign == $callbackData['sign'])
            {
                if($callbackData['result_code'] == 'SUCCESS')
                {
                    $orderNo = $callbackData['out_trade_no'];
                    $money   = $callbackData['total_fee']/100;

                    //记录回执流水号
                    if(isset($callbackData['transaction_id']) && $callbackData['transaction_id'])
                    {
                        $this->recordTradeNo($orderNo,$callbackData['transaction_id'],$pay_level);
                    }
                    self::addTradeData($callbackData,1);//添加交易记录
                    return 1;
                }
                else
                {
                    $message = $callbackData['err_code_des'];
                }
            }
            else
            {
                $message = '签名不匹配';
            }
        }
        return 0;
    }
    
    public function getSendData($payment)
    {  
        /**
         * 流程：
         * 1、调用统一下单，取得code_url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、支付完成之后，微信服务器会通知支付成功
         * 4、在支付成功通知中需要查单确认是否真正支付成功
         */
         $notifyUrl = $this->serverCallbackUrl;

        $notify = new NativePay();
        $payModel = new IModel('payment');
        $payPara = $payModel->getField('id='.$payment['M_Paymentid'], 'config_param');
        $paraData = JSON::decode($payPara);     
        $input = new WxPayUnifiedOrder();
        $input->SetBody($payment['R_Name']);
        $input->SetAttach($payment['R_Name']);
        $M_mchid = $paraData['M_mchid'] ? $paraData['M_mchid'] : WxPayConfig::MCHID;
        $temp = $M_mchid.date("YmdHis");
        $input->SetOut_trade_no($payment['M_OrderNO']);
        $input->SetTotal_fee($payment['M_Amount']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag('');
        $input->SetNotify_url($notifyUrl);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($payment['M_OrderId']);
        $input->SetAppid($paraData['M_merId']);
        $input->SetMch_id($M_mchid);
        if(isset($payment['pay_level']))
        {
            $pay_level = $payment['pay_level'] ? $payment['pay_level'] : 2;
            $input->SetAttach($pay_level);
        }
        $result = $notify->GetPayUrl($input);
        if(isset($pay_level))
        {
            return(array('code_url' => $result["code_url"],'order_id' => $payment['M_OrderNO'], 'product_id' => $temp,'pay_total' => $payment['M_Amount']*100,'pay_level' => $pay_level));
        }
        else
        {
            return(array('code_url' => $result["code_url"],'order_id' => $payment['M_OrderNO'], 'product_id' => $temp,'pay_total' => $payment['M_Amount']*100,'pay_level' => 0));
        }
    }


    public function tradeStatusQuery($payment){
        $payModel = new IModel('payment');
        $payPara = $payModel->getField('id='.$payment['M_Paymentid'], 'config_param');
        $paraData = JSON::decode($payPara);
        $input = new WxPayUnifiedOrder();
        $M_mchid = $paraData['M_mchid'] ? $paraData['M_mchid'] : WxPayConfig::MCHID;
        $temp = $M_mchid.date("YmdHis").rand(1,10000);
        $input->SetAppid($paraData['M_merId']);
        $input->SetMch_id($M_mchid);
       // $orderNo = $M_mchid.'20180124153434';
        $orderNo = $payment['M_Order_NO'];
        $input->SetOut_trade_no($orderNo);
        $input->SetNonce_str(md5($temp));

        $result = WxPayApi::orderQuery($input);
       return $result;



    }



    /**
     * 添加交易记录
     * @param array $tradeData  返回的报文
     * @param int   $asyn  0:同步处理 1：异步回调
     */
    private static function addTradeData($tradeData,$asyn=0,$ids=0){
        $resArr = array(
            'trade_no' 	   => $tradeData['transaction_id'],
            'order_no'     => $tradeData['out_trade_no'],
            'money'        => $tradeData['cash_fee']/100,
            'pay_type'     => 13,
            'trade_type'   => 0,
            'time'         => $tradeData['time_end'],
            'order_ids'    => $ids,
        );

        $resArr['trade_status']=$asyn;
        return self::addTrade($resArr);

    }
    
    //退款
    function refund($payment)
    {
        $payModel = new IModel('payment');
        $payPara = $payModel->getField('id=13', 'config_param');
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
        $result = WxPayApi::refund($input);
        if(isset($result['result_code']) && $result['result_code'] == 'SUCCESS')
        {
            $resArr = array(
                'order_no'     => $payment['M_Order_NO'],
                'trade_no'        => $result['out_refund_no'],
                'orig_trade_no'        => $result['out_trade_no'],
                'trade_type'   => 2,
                'money'        => $result['cash_refund_fee']/100,
                'pay_type'     => 13,
                'trade_status' => 1,
                'time'         => date('Y-m-d H:i:s')
            );
        
            self::addTrade($resArr);
            return true;
        }
        return false;
    }

    /**
     * @brief 从xml到array转换数据格式
     * @param xml $xmlData
     * @return array
     */
    private function converArray($xmlData)
    {
        $result = array();
        $xmlHandle = xml_parser_create();
        xml_parse_into_struct($xmlHandle, $xmlData, $resultArray);

        foreach($resultArray as $key => $val)
        {
            if($val['tag'] != 'XML')
            {
                $result[$val['tag']] = $val['value'];
            }
        }
        return array_change_key_case($result);
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    private function paraFilter($para)
    {
        $para_filter = array();
        foreach($para as $key => $val)
        {
            if($key == "sign" || $key == "sign_type" || $val == "")
            {
                continue;
            }
            else
            {
                $para_filter[$key] = $para[$key];
            }
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    private function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 生成签名结果
     * @param $sort_para 要签名的数组
     * @param $key 支付宝交易安全校验码
     * @param $sign_type 签名类型 默认值：MD5
     * return 签名结果字符串
     */
    private function buildMysign($sort_para,$key,$sign_type = "MD5")
    {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($sort_para);
        //把拼接后的字符串再与安全校验码直接连接起来
        $prestr = $prestr.'&key='.$key;
        //把最终的字符串签名，获得签名结果
        $mysgin = md5($prestr);
        return strtoupper($mysgin);
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private function createLinkstring($para)
    {
        $arg  = "";
        foreach($para as $key => $val)
        {
            $arg.=$key."=".$val."&";
        }

        //去掉最后一个&字符
        $arg = trim($arg,'&');

        //如果存在转义字符，那么去掉转义
        if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
        {
            $arg = stripslashes($arg);
        }

        return $arg;
    }
}

