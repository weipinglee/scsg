<?php
/**
 * @file ProRule.php
 * @brief 促销规则处理类
 * @author 
 * @date 2011-03-10
 * @version 0.6
 */

/**
 * @class ProRule
 * @brief 促销活动规则奖励
          奖励方式分为 (1)现金奖励,(2)赠品奖励。
          (1)现金奖励就是直接减少订单总额中的金钱数
          (2)赠品奖励就是订单支持成功后，系统自动发送的赠品
 */
class ProRule
{
	//商品总金额
	private $sum;

	//用户组
	private $user_group = null;

	//现金促销规则奖励仅一次 true:1次;false:多次不限
	public $isCashOnce = false;

	//赠品促销规则奖励仅一次 true:1次;false:多次不限
	public $isGiftOnce = false;

	//现金促销规则奖励方式 1减金额 2奖励折扣
	private $cash_award_type = array(1,2);

	//赠品促销规则奖励方式 3赠送积分 4赠送代金券 5赠送赠品 6免运费 7赠送经验值
	private $gift_award_type = array(3,4,5,6,7);
	
	//积分倍数
	private $point_mul = 1;

	private $user_id = 0;

	private $proObj = null;

	private $incUsedTimes = 0;//是否增加促销规则使用次数



	/**
	 * @brief 构造函数 初始化商品金额
	 * @param float $sum 商品金额
	 * @param int $point_mul 积分倍数
	 */
	public function __construct($sum=0,$point_mul=1)
	{
		//商品金额必须为数字
		if(!is_numeric($sum))
		{
			IError::show(403,array('message'=>'order sum must a num'));
		}
		$this->sum = $sum;
		$this->point_mul = $point_mul;
		$this->proObj = new IModel('promotion');
	}

	/**
	 * 设置增加使用次数
	 */
	public function setincTimes(){
		$this->incUsedTimes = 1;
	}

	/**
	 * 判断当前用户当天使用某个促销规则是否超次数
	 * @param $prom_id int 促销id
	 * @return bool 是否超次数 true:超次数
	 */
	public function checkOutTimes($prom_id)
	{
		$limit_times = $this->proObj->getField('id='.$prom_id,'times_day');//获取促销规则的每日限制使用次数
		if($limit_times==0){//不限制
			return false;
		}
		//获取当前用户当天已使用的次数
		$promUserObj = new IModel('prom_user');
		$date = ITime::getDateTime('Y-m-d');
		$usedTimes = $promUserObj->getObj('user_id='.$this->user_id.' and prom_id='.$prom_id.' and date="'.$date.'"','times,id');

		if(!isset($usedTimes['times']) || $limit_times>$usedTimes['times']){
			if($this->incUsedTimes){//生成订单时增加使用次数

				if(isset($usedTimes['id'])){
					$data = array(
							'times'=>$usedTimes['times'] + 1 ,
					);
					$promUserObj->setData($data);
					 $promUserObj->update('id='.$usedTimes['id']);
				}
				else{
					$data = array(
							'times'=>1,
							'prom_id' => $prom_id,
							'user_id' => $this->user_id,
							'date' => $date,
					);
					$promUserObj->setData($data);
					$promUserObj->add();
				}

			}
			return false;
		}
		return true;

	}

	/**
	 * 增加促销规则使用次数
	 * @param $prom_id int 促销规则id
	 * @param $user_id int 用户id
	 * @return int
	 */
	public function addUsedTimes($prom_id,$user_id){
		$promUserObj = new IModel('prom_user');
		$date = ITime::getDateTime('Y-m-d');
		$usedTimes = $promUserObj->getObj('user_id='.$user_id.' and prom_id='.$prom_id.' and date="'.$date.'"','times,id');

		if(isset($usedTimes['id'])){
			$data = array(
					'times'=>$usedTimes['times'] + 1 ,
			);
			$promUserObj->setData($data);
			return $promUserObj->update('id='.$usedTimes['id']);
		}
		else{
			$data = array(
					'times'=>1,
					'prom_id' => $prom_id,
					'user_id' => $user_id,
					'date' => $date,
			);
			$promUserObj->setData($data);
			return $promUserObj->add();
		}


	}


	/**
	 * @brief 设置用户组兼用户名
	 * @param string 用户组
	 */
	public function setUserGroup($groupId,$user_id=0)
	{
		$this->user_id  = $user_id;
		$this->user_group = $groupId;
	}

	/**
	 * @brief 获取现金促销规则优惠后的金额
	 * @return float 优惠后金额
	 */
	public function getSum($goodsIdList=array(), $area = null)
	{
		//获取现金奖励信息
		$cashInfo = $this->getAwardInfo($this->cash_award_type,$this->isCashOnce,$goodsIdList, $area);
		if(!empty($cashInfo))
		{
			//执行现金奖励运算
			return $this->cashAction($cashInfo);
		}
		else
		{
			return $this->sum;
		}
	}

	/**
	 * @brief 进行赠品促销规则的奖励
	 * @param int $user_id 用户的ID值
	 */
	public function setAward($user_id, $goodsList = array())
	{
		//获取赠品奖励信息
		$giftInfo = $this->getAwardInfo($this->gift_award_type,$this->isGiftOnce,$goodsList);
		
		return $this->giftAction($giftInfo,$user_id);
	}

	/**
	 * @brief 获取促销规则的数据
	 * @return array plan:活动方案名称; info:具体促销信息;
	 */
	public function getInfo($goodsIdList = array(), $area = null)
	{
		$explain  = array();
		$giftInfo = $this->getAwardInfo($this->gift_award_type,$this->isGiftOnce, $goodsIdList, $area);
		$cashInfo = $this->getAwardInfo($this->cash_award_type,$this->isCashOnce, $goodsIdList, $area);
		$allInfo  = is_array($cashInfo) ? array_merge($cashInfo,$giftInfo) : array();       
		foreach($allInfo as $key => $val)
		{
            $explain[$key]['id'] = $val['id'];
			$explain[$key]['type'] = $val['award_type'];
			$explain[$key]['plan'] = $val['name'];
            $explain[$key]['info'] = $this->typeExplain($val['award_type'],$val['condition'],$val['award_value']);
			$explain[$key]['hide'] = isset($val['hide']) ? $val['hide'] : 0;
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

			default:
			{
				return null;
			}
			break;
		}
	}
         
	/**
	 * @brief 是否减免订单的运费
	 * @return bool true:减免; false:不减免
	 */
	public function isFreeFreight($area = null, $goodsList = array())
	{
		$proList = $this->satisfyPromotion(6, $goodsList, $area);
		if(!empty($proList))
		{                   
            foreach($proList as $v)
            {
                if(!isset($v['hide']))
                {
                    $list[] = $v['id'];
                }  
            }
			return isset($list) ? $list : false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @brief 根据商品金额获取所满足的$award_type类别促销规则信息
	 * @param int $award_type 奖励类别 1减金额 2奖励折扣 3赠送积分 4赠送代金券 5赠送赠品 6免运费 7赠送经验
	 * @return array 促销规则信息
	 */
	private function satisfyPromotion($award_type = null, $goodsIdList = array(), $area = null)
	{
		//print_r($goodsIdList);
        $final_sum = $this->sum;
		$datetime = ITime::getDateTime();
		$proObj   = $this->proObj;
		$where    = '`condition` between 0 and '.$final_sum.' and type = 0 and is_close = 0 and start_time <= "'.$datetime.'" and end_time >= "'.$datetime.'"';
		//奖励类别分析
		if($award_type != null)
		{
			$where.=' and award_type in ('.$award_type.')';
		}
		//用户组
		if($this->user_group != null)
		{
			$where.=' and (user_group = "all" or FIND_IN_SET('.$this->user_group.',user_group))';
		}
		else
		{
			$where.=' and user_group = "all" ';
		}                  
		$proList = $proObj->query($where,'*','`condition`');
        $proListTemp = $proList;
        $temp = array_keys($goodsIdList);//print_r($goodsIdList);
        foreach($proListTemp as $k => $v)
        {
            $gId = array();
            if($v['goods_id'] == 'all')
            {
                $goods = new IModel('goods');
                $gId = $goods->getFields(array('seller_id' => $v['seller_id'], 'id' => implode(',', $temp)), 'id');
            }                             
            elseif($v['goods_id'])
            {
                $gId = explode(',', $v['goods_id']);
            }
            $common = array_intersect($temp, $gId);
            if($common)
            {
                $sumNum = 0;
                $reduceNum = 0;
				$maxNum = 0;
                foreach($common as $val)
                {
					$maxNum = $goodsIdList[$val]['num']>$maxNum ? $goodsIdList[$val]['num'] : $maxNum;
                    $sumNum += $goodsIdList[$val]['sum'];
                    $reduceNum += $goodsIdList[$val]['reduce'];
                }
                $proList[$k]['sum'] = $sumNum - $reduceNum;
                if(($sumNum - $reduceNum) < $v['condition'])
                {
                    $proList[$k]['hide'] = 1;
                }
				if($proList[$k]['nums_time']!=0 && $maxNum>$proList[$k]['nums_time'])
				{
					$proList[$k]['hide'] = 1;
				}


            }
            else
            {   
                $proList[$k]['hide'] = 1;
            }      
        }

        if($area)
        {
            $proListTemp = $proList;
            $temp = array_keys($goodsIdList); 
            foreach($proListTemp as $k => $v)
            {
                $area_groupid = unserialize($v['area_groupid']);
                if(!empty($area_groupid))
                {
                    foreach($area_groupid as $key => $val)
                    { 
                        if(strpos($val, ';'.$area.';') === false && strpos($val,';'.substr($area,0,2).'0000;') === false)
                        {
                            $proList[$k]['hide'] = 1;
                        }
                    }
                }  
            }
        }


		//去除超次数限制的促销规则
		foreach($proList as $key=>$val){
			if($this->checkOutTimes($val['id'])){//检查活动参加次数是否超限
				unset($proList[$key]);
			}
		}
		return $proList;
	}
	/**
	 * @brief 获取商品金额不满足的促销规则
	 * 
	 */
	public function notSatisfyPromotion($award_type = null)
	{
		$datetime = ITime::getDateTime();
		$proObj   = new IModel('promotion');
		$where    = '`condition` > '.$this->sum.' and type = 0 and is_close = 0 and start_time <= "'.$datetime.'" and end_time >= "'.$datetime.'"';
		
		//奖励类别分析
		if($award_type != null)
		{
			$where.=' and award_type in ('.$award_type.')';
		}
		
		//用户组
		if($this->user_group != null)
		{
			$where.=' and (user_group = "all" or FIND_IN_SET('.$this->user_group.',user_group))';
		}
		else
		{
			$where.=' and user_group = "all" ';
		}
		
		$proList = $proObj->getObj($where,'*','`condition`','ASC');
		if(empty($proList))return false;
		$explain['type'] = $proList['award_type'];
		$explain['plan'] = $proList['name'];
		$explain['condition'] = $proList['condition'];
		$explain['info'] = $this->typeExplain($proList['award_type'],$proList['condition'],$proList['award_value']);
	
		return $explain;
	}
	/**
	 * @brief 现金促销规则奖励操作
	 * @param array 现金促销规则奖励信息
	 * @return float 处理后金额
	 */
	private function cashAction($cashArray)
	{
		$sum = $this->sum;
		foreach($cashArray as $val)
		{
            if(!isset($val['hide']))
            {
			    $award_type  = $val['award_type'];
			    $award_value = $val['award_value'];

			    switch($award_type)
			    {
				    //减少总额数
				    case "1":
				    {
					    $sum = $sum - $award_value;
				    }
				    break;

				    //减少百分比
				    case "2":
				    {
					    $sum = isset($val['sum']) ? $sum - ($val['sum'] * ($award_value/100)) : $sum - ($sum * ($award_value/100));
				    }
				    break;
                }
			}
		}
		return $sum;
	}
	//赠送礼物
	public function giftSend($giftArray,$user_id){
		$array = array();
		if(isset($giftArray['point']))
			$array[] = array('award_type'=>'3','award_value'=>$giftArray['point']);
		if(isset($giftArray['ticket']))
			$array[] = array('award_type'=>'4','award_value'=>$giftArray['ticket']);
		if(isset($giftArray['exp']))
			$array[] = array('award_type'=>'7','award_value'=>$giftArray['exp']);
		
		$this->giftAction($array,$user_id);
	}
	/**
	 * @brief 赠品促销规则奖励操作
	 * @param array 赠品促销规则奖励信息
	 */
	private function giftAction($giftArray,$user_id)
	{                             
		foreach($giftArray as $key => $val)
		{
            if(!isset($val['hide']) || $val['hide'] <> 1)
            {                        
			    $award_type  = $val['award_type'];
			    $award_value = $val['award_value'];
			    switch($award_type)
			    {
				    //积分
				    case "3":
				    {
					    $award_value = $award_value * $this->point_mul;
					    $pointConfig = array(
						    'user_id' => $user_id,
						    'point'   => $award_value,
						    'log'     => '促销奖励，消费满'.$this->sum.'元，奖励'.$award_value.'积分',
					    );
					    $pointObj = new Point;
					    $pointObj->update($pointConfig);
				    }
				    break;

				    //代金券
				    case "4":
				    {
					    /*(1)修改prop表*/
					    $ticketObj = new IModel('ticket');
					    $where     = 'id = '.$award_value;
					    $ticketRow = $ticketObj->getObj($where);

					    //奖励的红包没有过期
					    $time = ITime::getDateTime();
					    if(($time > $ticketRow['start_time']) && ($time < $ticketRow['end_time']))
					    {
						    $dataArray = array(
							    'condition' => $award_value,
							    'name'      => $ticketRow['name'],
							    'card_name' => 'T'.IHash::random(8),
							    'card_pwd'  => IHash::random(8),
                                'ticket_condition' => $ticketRow['condition'],
							    'value'     => $ticketRow['value'],
							    'start_time'=> $ticketRow['start_time'],
							    'end_time'  => $ticketRow['end_time'],
							    'is_send'   => 1,
                                'seller_id' => $ticketRow['seller_id']
						    );
						    $propObj = new IModel('prop');
						    $propObj->setData($dataArray);
						    $insert_id = $propObj->add();

						    /*(2)修改member表*/
						    $memberObj = new IModel('member');

						    //用户prop字段值null时
						    $memberArray = array('prop' => ','.$insert_id.',');
						    $memberObj->setData($memberArray);
						    $result      = $memberObj->update('user_id = '.$user_id.' and ( prop is NULL or prop = "" )');

						    //用户prop字段值非null时
						    if(!$result)
						    {
							    $memberArray = array(
								    'prop' => 'concat(prop,"'.$insert_id.',")',
							    );
							    $memberObj->setData($memberArray);
							    $memberObj->update('user_id = '.$user_id,'prop');
						    }
					    }
				    }
				    break;

				    //赠送经验
				    case "7":
				    {
					    $memberObj   = new IModel('member');
					    $memberArray = array(
						    'exp' => 'exp + '.$award_value,
					    );
					    $memberObj->setData($memberArray);
					    $memberObj->update('user_id = '.$user_id,'exp');
				    }
				    break;
			    }
            }
		}
		
	}

	/**
	 * @brief 获取奖励信息
	 * @param array $award_type 奖励类型数组值
     * @param bool  $is_once    奖励方案是否允许叠加
	 * @param array  $goodsIdList    营销方式绑定的商品ID
	 * @return array            奖励信息
	 */
	private function getAwardInfo($award_type,$is_once,$goodsIdList = array(), $area = null)
	{
		$awardInfo = array();

		//获取所有现金促销规则奖励信息
        $award_type_str = join(',',$award_type);
		$allAwardInfo   = $this->satisfyPromotion($award_type_str,$goodsIdList, $area,null);
		//当现金奖励仅为一次时，奖励优惠最大化
		if(!empty($allAwardInfo))
		{
			if($is_once == true)
			{
                foreach($allAwardInfo as $k => $v)
                {
                    if(!isset($awardInfo[$v['seller_id']]))
                    {
                        $awardInfo[$v['seller_id']] = $v;
                    }
                }
			}
			else
			{
				$awardInfo = $allAwardInfo;
			}
		}
		return $awardInfo;
	}
}