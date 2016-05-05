<?php
/**
 * @file pay_balance.php
 * @brief 账户余额支付接口
 * @date 2011-01-27
 * @version 0.6
 * @note
 */

 /**
 * @class balance
 * @brief 账户余额支付接口
 */
class balance extends paymentPlugin
{
	//插件名称
    public $name = '账户余额支付';

	/**
	 * @see paymentplugin::getSubmitUrl()
	 */
    public function getSubmitUrl()
    {
    	return IUrl::getHost() . IUrl::creatUrl('/ucenter/payment_balance');
    }

    /**
     * 获取退款提交地址
     */
    public function getRefundUrl(){
    
    }
    public function getSendDataMerge($payment){
    	$partnerId  = $payment['M_PartnerId'];
    	$partnerKey = $payment['M_PartnerKey'];
		$user_id    = ISafe::get('user_id');

		$return['attach']     = $payment['M_Paymentid'];
		$return['total_fee']  = $payment['M_Amount'];
		$return['order_no']   = $payment['M_OrderNO'];
		$return['return_url'] = $this->callbackUrlMerge;

		$urlStr = '';

		ksort($return);
		foreach($return as $key => $val)
		{
			$urlStr .= $key.'='.urlencode($val).'&';
		}

		$encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
		$urlStr .= $user_id . $partnerKey . $encryptKey;
		$return['sign'] = md5($urlStr);
    	return $return;
    }
	/**
	 * @see paymentplugin::getSendData()
	 */
    public function getSendData($payment)
    {
    	$partnerId  = $payment['M_PartnerId'];
    	$partnerKey = $payment['M_PartnerKey'];
		$user_id    = ISafe::get('user_id');

		$return['attach']     = $payment['M_Paymentid'];
		$return['total_fee']  = $payment['M_Amount'];
		$return['order_no']   = $payment['M_OrderNO'];
		$return['return_url'] = $this->callbackUrl;

		$urlStr = '';

		ksort($return);
		foreach($return as $key => $val)
		{
			$urlStr .= $key.'='.urlencode($val).'&';
		}

		$encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
		$urlStr .= $user_id . $partnerKey . $encryptKey;
		$return['sign'] = md5($urlStr);

        return $return;
    }
	/**
	 *合并支付回调
	 */
    public function callbackMerge($ExternalData,&$paymentId,&$money,&$message,&$orderNo,&$order_ids)
    {
    	$partnerKey = Payment::getConfigParam($paymentId,'M_PartnerKey');
    	$user_id    = ISafe::get('user_id');
    	
    	ksort($ExternalData);
    	
    	$temp = array();
    	foreach($ExternalData as $k => $v)
    	{
    		if($k!='sign')
    		{
    			$temp[] = $k.'='.urlencode($v);
    		}
    	}
    	
    	$encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
    	$testStr = join('&',$temp).'&'.$user_id.$partnerKey.$encryptKey;
    	
    	$orderNo = $ExternalData['order_no'];
    	$money   = $ExternalData['total_fee'];
    	
    	if($ExternalData['sign'] == md5($testStr))
    	{
    		//支付单号
    		switch($ExternalData['is_success'])
    		{
    			case 'T':
    				{
    					
    					$merge_order_db = new IModel('merge_order');
    					$order_ids = $merge_order_db->getField('order_no="'.$orderNo.'"','order_ids');
    					$this->recordTradeNoForMerge($order_ids,'',$paymentId);
    					
    					$log    = new AccountLog();
    					if(stripos($orderNo,'merge') !== false){
    						$config = array(
    								'user_id'  => $user_id,
    								'event'    => 'pay',
    								'note'     => '通过余额支付方式进行合并商品购买',
    								'num'      => '-'.$money,
    								'order_id' => $orderNo,
    						);
    					}
    					else{
    						$config = array(
    								'user_id'  => $user_id,
    								'event'    => 'pay',
    								'note'     => '通过余额支付方式进行商品购买',
    								'num'      => '-'.$money,
    								'order_id' => $orderNo,
    						);
    					}	
    					
    					$log->write($config);
    					return true;
    				}
    				break;
    	
    			case 'F':
    				{
    					return false;
    				}
    				break;
    		}
    	}
    	else
    	{
    		$message = '校验码不正确';
    	}
    	return false;
    }
	/**
	 * @see paymentplugin::callback()
	 */
    public function callback($ExternalData,&$paymentId,&$money,&$message,&$orderNo)
    {
        $partnerKey = Payment::getConfigParam($paymentId,'M_PartnerKey');
        $user_id    = ISafe::get('user_id');

		ksort($ExternalData);

		$temp = array();
        foreach($ExternalData as $k => $v)
        {
            if($k!='sign')
            {
                $temp[] = $k.'='.urlencode($v);
            }
        }

        $encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
        $testStr = join('&',$temp).'&'.$user_id.$partnerKey.$encryptKey;

        $orderNo = $ExternalData['order_no'];
        $money   = $ExternalData['total_fee'];

        if($ExternalData['sign'] == md5($testStr))
        {
            //支付单号
            switch($ExternalData['is_success'])
            {
                case 'T':
                {
					$log    = new AccountLog();
					$config = array(
						'user_id'  => $user_id,
						'event'    => 'pay',
						'note'     => '通过余额支付方式进行商品购买',
						'num'      => '-'.$money,
						'order_id' => $orderNo,
					);
					$log->write($config);
                	return true;
                }
                break;

                case 'F':
                {
                	return false;
                }
                break;
            }
        }
        else
        {
        	$message = '校验码不正确';
        }
        return false;
    }

	/**
	 * @see paymentplugin::serverCallback()
	 */
    public function serverCallback($ExternalData,&$paymentId,&$money,&$message,&$orderNo){}

	/**
	 * @see paymentplugin::notifyStop()
	 */
    public function notifyStop(){}
}