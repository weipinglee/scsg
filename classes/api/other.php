<?php
/**
 * @file other.php
 * @brief 其他api方法
 * @date 2014/11/4 7:33:34
 * @version 2.8
 */
class APIOther
{
	//获取促销规则
	public function getProrule($arg)
	{
        $datetime = ITime::getDateTime();
        $id = intval($arg);
        $goods = new IModel('goods');
        $seller_id = $goods->getField('id='.$id, 'seller_id');
        $proObj   = new IModel('promotion');
        $where    = 'type = 0 and is_close = 0 and start_time <= "'.$datetime.'" and end_time >= "'.$datetime.'" and (goods_id = "all" or FIND_IN_SET('.$id.',goods_id)) and user_group = "all" and seller_id = '.$seller_id;
        $proList = $proObj->query($where,'*','`condition`');
        $explain  = array();
        foreach($proList as $key => $val)
        {
            $explain[$key]['id'] = $val['id'];
            $explain[$key]['type'] = $val['award_type'];
            $explain[$key]['plan'] = $val['name'];
            $explain[$key]['info'] = $this->typeExplain($val['award_type'],$val['condition'],$val['award_value']);
        }
        return $explain;
	}
    
    /**
     * @brief 奖励类型解释
     * @param int 类型id值
     * @param string 满足条件
     * @param string 奖励数据
     * @return string 类型说明
     */
    private function typeExplain($awardType,$condition,$awardValue)
    {
        switch($awardType)
        {
            case "1":
            {
                return '购物满￥'.$condition.' 优惠￥'.$awardValue;
            }
            break;

            case "2":
            {
                return '购物满￥'.$condition.' 优惠'.$awardValue.'%';
            }
            break;

            case "3":
            {
                return '购物满￥'.$condition.' 增加'.$awardValue.'积分';
            }
            break;

            case "4":
            {
                $ticketObj = new IModel('ticket');
                $where     = 'id = '.$awardValue;
                $ticketRow = $ticketObj->getObj($where);
                if($ticketRow)
                    return '购物满￥'.$condition.' 立得￥'.$ticketRow['value'].'代金券';
                else return '';
            }
            break;

            case "5":
            {
                return '购物满￥'.$condition.' 送赠品';
            }
            break;

            case "6":
            {
                return '购物满￥'.$condition.' 免运费';
            }
            break;

            case "7":
            {
                return '购物满￥'.$condition.' 立加'.$awardValue.'经验';
            }
            break;

            case "8":
            {
                return '满'.$condition.' 件，下一件商品优惠'.$awardValue.'%';
            }
            break;

            case "9":
            {
                return '满'.$condition.' 件，下一件商品优惠￥'.$awardValue;
            }
            break;

            default:
            {
                return null;
            }
            break;
        }
    }

	//获取支付方式
	public function getPaymentList()
	{
		$user_id = ISafe::get('user_id');
		$where = 'status = 0';

		if(!$user_id)
		{
			$where .= " and class_name != 'balance'";
		}

		switch(IClient::getDevice())
		{
			//移动支付
			case IClient::MOBILE:
			{
				$where .= ' and client_type in(2,3) ';

				//如果不是微信客户端,去掉微信专用支付
				if(IClient::isWechat() == false)
				{
					$where .= " and class_name != 'wap_wechat'";
				}
                else
                {
                    $where .= "and class_name != 'trade_alipay'";
                }
			}
			break;

			//pc支付
			case IClient::PC:
			{
				$where .= ' and client_type in(1,3) ';
			}
			break;
		}
		$paymentDB = new IModel('payment');
		return $paymentDB->query($where);
	}

	//线上充值的支付方式
	public function getPaymentListByOnline()
	{
		$where = " type = 1 and status = 0 and class_name not in ('balance','offline') ";
		switch(IClient::getDevice())
		{
			//移动支付
			case IClient::MOBILE:
			{
				$where .= ' and client_type in(2,3) ';

				//如果不是微信客户端,去掉微信专用支付
				if(IClient::isWechat() == false)
				{
					$where .= " and class_name != 'wap_wechat'";
				}
			}
			break;

			//pc支付
			case IClient::PC:
			{
				$where .= ' and client_type in(1,3) ';
			}
			break;
		}

		$paymentDB = new IModel('payment');
		return $paymentDB->query($where);
	}
}