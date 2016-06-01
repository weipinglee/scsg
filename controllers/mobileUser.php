<?php 
/*
  * 注册登录
  * author:wangzhande
 */
class MobileUser extends IController
{

	public function mobileReg()
	{

		$data = array('code' => 1);
		$phone = IFilter::act(IReq::get('phone', 'post'));
		$password = IFilter::act(IReq::get('password', 'post'));
		$validPhoneCode = IFilter::act(Ireq::get('validPhoneCode','post'));
		$type = IFilter::act(IReq::get('type', 'post'));
		if (!IValidate::phone($phone)) {  //手机格式不 正确的话
			$data['code'] = 0;
			$data['info']='手机格式不正确';
			die(JSON::encode($data));
		}
		if (!IValidate::required($password)) {
			//密码不能为空
			$data['code'] = 0;
			$data['info']='密码不能为空';
		}

		//如果上面的验证都听过
		if ($data['code'] == 1) {
			//验证手机验证码 41是过期 0 是正确 2是错误  7是没有验证码
			$data = self::checkMobileValidateCode($phone,$validPhoneCode);
		}
		if ($data['code'] == 1) {
			$userObj = new IModel('user');
			//判断手机是否存在
			if ($userObj->getObj('phone = ' . $phone, 'id')) {
				$data['code'] = 0;
				$data['info']='该手机号已注册';
			} else {
				$userArray = array(
					'phone' => $phone,
					'password' => md5($password),
				);
				//插入注册用户数据
				$userObj->setData($userArray);
				$user_id = $userObj->add();
				//$userObj->commit();
				//插入成功
				if ($user_id) {
					//实例化用户组表，
					$group = new IModel('user_group');
					//获得默认的分组id；
					$group_id = $group->getField('is_default=1', 'id');

					//member表
					$memberArray = array(
						'user_id' => $user_id,
						'time' => ITime::getDateTime(),
						'status' => 1,
					);
					if ($group_id) $memberArray['group_id'] = $group_id;
					if ($type == 1) $memberArray['status'] = 4;
					//用户信息表
					$memberObj = new IModel('member');
					//把用户信息放到用户信息表
					$memberObj->setData($memberArray);
					$memberObj->add();
				} else {
					//插入失败
					$data['code'] = 0;
					$data['info']='插入失败';
				}

			}

		}

		echo JSON::encode($data);

	}

	public function getMobileValidateCode()
	{
		$phone = IFilter::act(IReq::get('phone','post'));
		$res = array('code' => 1);
		if ($phone == '') {
			$res['code'] = 0;
			$res['info']='手机号码不能为空';
		}
		if (!IValidate::phone($phone)) {
			$res['code'] = 0;
			$res['info']='手机号不正确';
		}
		if ($res['code'] == 1) {
			$text = rand(100000, 999999);
			$mobileObj=new IModel('mobile_code');
			$where='phone='.$phone;
			$mobileRes=$mobileObj->getObj('phone='.$phone);
			$data=array(
				'phone'=>$phone,
				'code'=>$text,
				'time'=>ITime::getDateTime()
			);
			if($mobileRes) {
				$mobileObj->setData($data);
				$mobileObj->update('phone='.$phone);
			}else{
				$mobileObj->setData($data);
			}

			$text = smsTemplate::checkCode(array('{mobile_code}' => $text));
			$sms=new ChuanglanSMS('N9081709','9fd2cd94');
			$result=$sms->send($phone,$text);
			$result=JSON::decode($result);
			if($result['success']==true){
				//发送成功
				$res['code']=1;
				$res['info']='发送成功';
			}else{
				//发送失败
				$res['code']=0;
				$res['info']='发送失败';
			}
		}
		echo JSON::encode($res);


	}

	//验证手机验证码
	public function checkMobileValidateCode($phone,$num)
	{
		$mobileObj=new IModel('mobile_code');
		$mobileValidateSess=$mobileObj->getObj('phone='.$phone);
		//return array('code'=>1,'info'=>'验证码正确');
		if($mobileValidateSess){
			if(time()-strtotime($mobileValidateSess['time'])>=1800){//session过期

				 $res=array('code'=>0,'info'=>'验证码超时');
				return $res;
			} else if ($mobileValidateSess['code'] != $num) {
					$res=array('code'=>0,'info'=>'验证码错误');//错误
					return $res;
			} else {
				$res=array('code'=>1,'info'=>'验证码正确');
				//正确以后清空
						$data=array(
						'code'=>'',
					);
				$mobileObj->setData($data);
				$mobileObj->update('phone='.$phone);
					return $res;
			}//正确
		} else return array('code'=>0,'info'=>'没有验证码');//没有验证码
	}


}


?>