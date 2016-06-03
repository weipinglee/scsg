<?php 
/*
  * 注册登录
  * author:wangzhande
 */
class MobileUser extends IController
{

	public function MobileReg()
	{

		$data = array('code' => 1);
		$phone = IFilter::act(IReq::get('phone', 'post'));
		$password = IFilter::act(IReq::get('password', 'post'));
		$validPhoneCode = IFilter::act(Ireq::get('validPhoneCode','post'));
		//$type = IFilter::act(IReq::get('type', 'post'));
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
					$data['token']=$this->setToken($user_id);
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
					//if ($type == 1) $memberArray['status'] = 4;
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
		$res = array('code' => 1,'info'=>'发送成功');
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
			//$where='phone='.$phone;
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
				$data['send_times']=0;
				$mobileObj->setData($data);
				$mobileObj->add();
			}

			$text = smsTemplate::checkCode(array('{mobile_code}' => $text));
/*			$sms=new ChuanglanSMS('N9835706','1329942c');
			$result=$sms->send($phone,$text);
			$result=JSON::decode($result);
/*			if($result['success']==true){
				//发送成功
				$res['code']=1;
				$res['info']='发送成功';
			}else{
				//发送失败
				$res['code']=0;
				$res['info']='发送失败';
			}*/
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

	//用户登录
	public function login_act()
	{
		$login_info = IFilter::act(IReq::get('username', 'post'));
		$password = IFilter::act(IReq::get('password', 'post'));
		$password = md5($password);
		$errTimes = $this->getErrTimes($login_info);
		if($errTimes===false){
			die(JSON::encode(array('code'=>0,'info'=>'账号不存在')));
		}
		$data = array('code' => 1,'info'=>'登录成功');
		if ($login_info == '') {
			$data['code'] = 0;
			$data['info'] = '用户名不能为空';
			die(JSON::encode($data));
		} else if ($password == '') {
			$data['code'] = 0;
			$data['info'] = '密码不能为空';
			die(JSON::encode($data));
		}
		else if ($errTimes > 3) {//二次添加
			$data['code'] = 0;
			$data['info']='密码错误次数太多,请点忘记密码';
			die(JSON::encode($data));
		} else {    //验证已注册用户是否合法
			if ($userRow = CheckRights::isValidUser($login_info, $password)) {    //验证成功后把密码错误次数改为0
				$M = new IModel('user');
				$where = 'phone = "' . $login_info . '" OR email = "' . $login_info . '" OR username = "' . $login_info . '"';
				$M->setData(array('err_times' => 0));
				$M->update($where);
				$data['token'] =$this->setToken($userRow['id']);
				//要生成一个token保存起来
				$memberObj = new IModel('member');
				//设置最后登录时间
				$dataArray = array(
					'last_login' => ITime::getDateTime(),
				);
				$memberObj->setData($dataArray);
				$where = 'user_id = ' . $userRow["id"];
				$memberObj->update($where);
				$memberRow = $memberObj->getObj($where, 'exp');

				//根据经验值分会员组
				$groupObj = new IModel('user_group');
				$groupRow = $groupObj->getObj($memberRow['exp'] . ' between minexp and maxexp and minexp > 0 and maxexp > 0', 'id', 'discount', 'desc');
				if (!empty($groupRow)) {
					$dataArray = array('group_id' => $groupRow['id']);
					$memberObj->setData($dataArray);
					$memberObj->update('user_id = ' . $userRow["id"]);
				}
			} else {

					$M = new Imodel('user');
					$M->addNum(array('username' => $login_info, 'phone' => $login_info, 'email' => $login_info),
					array('err_times' => 1), 0);
					$data['code'] = 0;//密码账号不匹配
					$data['info']='账号密码不匹配，密码错误次数'.($errTimes+1);
				//$data['errorTimes'] = $errTimes + 1;
			}
		}
		echo JSON::encode($data);


	}
	//设置token
	private function setToken($user_id)
	{
		$token=sha1($user_id.time());
		$tokenModel=new IModel('token');
		if($tokenModel->getObj('user_id='.$user_id)){
			$tokenModel->setData(array('user_id'=>$user_id,'token'=>$token));
			$tokenModel->update('user_id='.$user_id);
		} else{
			$tokenModel->setData(array('user_id'=>$user_id,'token'=>$token));
			$tokenModel->add();
		}
		return $token;
	}

	//获取用户密码错误次数
	private function getErrTimes($username){
		$M = new IModel('user');
		$where = 'phone = "'.$username.'" OR username = "'.$username.'" OR email = "'.$username.'"';
		if($res = $M->getObj($where,'err_times')) {
			return  $res['err_times'];
		}else{
			return false;
		}
	}
}


?>