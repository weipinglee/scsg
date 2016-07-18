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
						'mobile'=>$phone,
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
				$data['username']=$userRow['username'];
				$data['head_ico']='http://v.yqrtv.com:8080/app/'.$userRow['head_ico'];
				$data['phone']=$userRow['phone'];
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
			$tokenModel->setData(array('user_id'=>$user_id,'token'=>$token,'time'=>date('Y-m-d H:i:s',time())));
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
	/**
	 *用户帮助接口
	 */
	public function getHelpCat(){
		$helpCat=new IQuery('help_category');
		$helpCat->fields='id,name,image';
		$helpCat->order='`sort` asc';
		$helpCatList=$helpCat->find();
		$helpObj=new IQuery('help');
		$res=array();
		$helpObj->fields='id,cat_id,name';
		$helpObj->order='`sort` asc';
		foreach($helpCatList as $k=>$v){
			$helpObj->where=' cat_id ='.$v['id'];
			$helpObj->limit=6;
			$res[$k]['img']='http://http://v.yqrtv.com:8080/app/'.$v['image'];
			$res[$k]['data']=$helpList=$helpObj->find();
			$res[$k]['id']=$v['id'];
			$res[$k]['name']=$v['name'];
		}
		die(JSON::encode($res));
		//var_dump($res);
	}
	/**
	 *修改收获地址,添加收获地址
	 */
	public function editAdress(){
		$token=IFilter::act(IReq::get('token','post'));
		if($token){
			$tokenObj=new IModel('token');
			if($res=$tokenObj->getObj('token=\''.$token.'\'')){
				$addId=IFilter::act(IReq::get('add_id','post'),'int');
				$accept_name=IFilter::act(IReq::get('accept_name','post'));
				$address=IFilter::act(IReq::get('address','post'));
				$mobile = IFilter::act(IReq::get('mobile','post'));
				$zip=IFilter::act(IReq::get('zip','post'),'int');
				$province=IFilter::act(IReq::get('province','post'),'int');
				$city=IFilter::act(IReq::get('city','post'),'int');
				$area=IFilter::act(IReq::get('area','post','int'));
				if(!$accept_name){
					die(JSON::encode(['code'=>0,'info'=>'请填写收货人姓名']));
				}
				if(!$province){
					die(JSON::encode(['code'=>0,'info'=>'请填写省份']));
				}
				if(!$city){
					die(JSON::encode(['code'=>0,'info'=>'请填写城市']));
				}
				if(!$area){
					die(JSON::encode(['code'=>0,'info'=>'请填写地址']));
				}
				if(!$address){
					die(JSON::encode(['code'=>0,'info'=>'请输入详细地址']));
				}
				if(!IValidate::phone($mobile)){
					die(JSON::encode(['code'=>0,'info'=>'请输入手机号']));
				}
				if(!IValidate::zip($zip)){
					die(JSON::encode(['code'=>0,'info'=>'请输入邮编']));
				}
				$addObj=new IModel('address');
				$data['accept_name']=$accept_name;
				$data['address']=$address;
				$data['mobile']=$mobile;
				$data['zip']=$zip;
				$data['default']=IFilter::act(IReq::get('default','post'),'int')?IFilter::act(IReq::get('default','post'),'int'):0;
				if($data['default']==1){
					$addObj->setData(['default'=>0]);
					$addObj->update('user_id='.$res['user_id']);
				}
				$data['province']=$province;
				$data['city']=$city;
				$data['area']=$area;
				$data['user_id']=$res['user_id'];
				if($addId!=""){
					$addInfo=$addObj->getObj('id='.$addId);
					$data['default']=$addInfo['default'];
					$addObj->setData($data);
					if($addObj->update('id='.$addId)){
						die(JSON::encode(['code'=>1,'info'=>'修改成功']));
					}else{
						die(JSON::encode(['code'=>0,'info'=>'修改失败']));
					}
				}else{
					$addObj->setData($data);
					if($addObj->add()){
						die(JSON::encode(['code'=>1,'info'=>'添加成功']));
					}else{
						die(JSON::encode(['code'=>0,'info'=>'添加失败']));
					}
				}
			}else{
				die(JSON::encode(['code'=>0,'info'=>'请登录']));
			}
		}else{
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}

	}
	/**
	 *修改头像
	 */
	public function userIcoUpload(){
		$token=IFilter::act(IReq::get('token','post'));
		$tokenObj=new IModel('token');
		$user=$tokenObj->getObj('token ='.'\''.$token.'\'');
		if(!$user) {
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}
		if(isset($_FILES['attach']['name']) && $_FILES['attach']['name'] != '')
		{
			$photoObj = new PhotoUpload();
			$photo    = $photoObj->run();

			if($photo['attach']['img'])
			{
				$user_id   = $user['user_id'];
				$user_obj  = new IModel('user');
				$dataArray = array(
					'head_ico' => $photo['attach']['img'],
				);
				$user_obj->setData($dataArray);
				$where  = 'id = '.$user_id;
				$isSuss = $user_obj->update($where);

				if($isSuss !== false)
				{
					die(JSON::encode(['code'=>1,'info'=>'http://http://v.yqrtv.com:8080/app/'.$photo['attach']['img']]));

				}
				else
				{
					die(JSON::encode(['code'=>0,'info'=>'上传失败']));

				}
			}
		}
	}

	/**
	 *修改昵称
	 */
	public function editUserName(){
		$token=IFilter::act(IReq::get('token','post'));
		$tokenObj=new IModel('token');
		$user=$tokenObj->getObj('token ='.'\''.$token.'\'');
		if(!$user){
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}
		$userName=IFilter::act(IReq::get('user_name','post'));
		if(!$userName){
			die(JSON::encode(['code'=>0,'info'=>'请输入用户名']));
		}

		$userObj=new IModel('user');
		if($userObj->where('username='.$userName)){
			die(JSON::encode(['code'=>0,'info'=>'用户名重复']));
		}
		$userObj->setData(['username'=>$userName]);
		$res=$userObj->update('id='.$user['user_id']);
		if($res){
			die(JSON::encode(['code'=>1,'info'=>'修改成功','username'=>$userName]));
		}else{
			die(JSON::encode(['code'=>0,'info'=>'修改失败']));
		}
	}
	/**
	 *反馈意见接口
	 */
	public function addSuggestion(){
		$token=IFilter::act(IReq::get('token','post'));
		$tokenObj=new IModel('token');
		$user=$tokenObj->getObj('token ='.'\''.$token.'\'');
		if(!$user){
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}
		$title=IFilter::act(IReq::get('title','post'));
		$content=IFilter::act(IFilter::get('content','post'));
		$time=date('Y-m-d H:i:s',time());
		if(!$content){
			die(JSON::encode(['code'=>0,'info'=>'内容不能为空']));
		}
		$data=[
			'title'=>$title,
			'content'=>$content,
			'user_id'=>$user['user_id'],
			'time'=>$time
		];
		$suggesion=new IModel('suggestion');
		$suggesion->setData($data);
		if($suggesion->add()){
			die(JSON::encode(['code'=>1,'info'=>'添加成功']));
		}else{
			die(JSON::encode(['code'=>0,'info'=>'添加失败']));
		}

	}
	//修改密码接口
	public function editPassWord(){
		$token=IFilter::act(IReq::get('token','post'));
		$tokenObj=new IModel('token');
		$user=$tokenObj->getObj('token=\''.$token.'\'');
		if(!$user){
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}
		$password=IFilter::act(IReq::get('password','post'));
		if(!IValidate::required($password)){
			die(JSON::encode(['code'=>0,'info'=>'原密码不能为空']));
		}
		$newPassWord=IFilter::act(IReq::get('newpassword','post'));
		if(!IValidate::required($newPassWord)){
			die(JSON::encode(['code'=>0,'info'=>'新密码不能为空']));
		}
		$userObj=new IModel('user');
		$userInfo=$userObj->getObj("id={$user['user_id']}");

		if($userInfo['password']!=md5($password)){
			die(JSON::encode(['code'=>0,'info'=>'密码不正确']));
		}
		$data=[
			'password'=>md5($newPassWord)
		];
		$userObj->setData($data);
		if($userObj->update('id='.$user['user_id'])){
			die(JSON::encode(['code'=>1,'info'=>'修改成功']));
		}else{
			die(JSON::encode(['code'=>0,'info'=>'修改失败']));
		}
	}
	//更改绑定手机
	public function editPhoneFirst(){
		$token=IFilter::act(IReq::get('token'),'post');
		$tokenObj=new IModel('token');
		if($tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
			$userObj=new IModel('user');
			$userInfo=$userObj->getObj('id='.$tokenInfo['user_id']);
			if($userInfo['phone']==""){
				die(JSON::encode(['code'=>0,'info'=>'没有绑定手机']));
			}
			//发送验证码
			$mobileCode=rand(100000,999999);
			$codeObj=new IModel('mobile_code');
			$mobileRes=$codeObj->getObj('phone='.$userInfo['phone']);
			if(!$mobileRes){
				$data=[
					'phone'=>$userInfo['phone'],
					'code'=>$mobileCode,
					'time'=>ITime::getDateTime()
				];
				$codeObj->setData($data);
				$codeObj->add();
			}else{
				$data=[
					'code'=>$mobileCode,
					'time'=>ITime::getDateTime()
				];
				$codeObj->setData($data);
				$codeObj->upDate('phone='.$userInfo['phone']);
			}
			die(JSON::encode(['code'=>1,'info'=>'发送成功']));
		}else{
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}

	}
	//更换绑定手机验证第一步
	public function checkFirstCode(){
		$token=IFilter::act(IReq::get('token','post'));
		$tokenObj=new IModel('token');
		$code=IFilter::act(IReq::get('code','post'));
		if($tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
			$userObj=new IModel('user');
			$userInfo=$userObj->getObj('id='.$tokenInfo['user_id']);
			if($userInfo['phone']==""){
				die(JSON::encode(['code'=>0,'info'=>'没有绑定手机']));
			}
			$checkRes=$this->checkMobileValidateCode($userInfo['phone'],$code);
			die(JSON::encode($checkRes));
		}else{
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}
	}
	//更换手机验证第二步
	public function checkSecondCode(){
		$token=IFilter::act(IReq::get('token','post'));
		$tokenObj=new IModel('token');
		if(!$tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}
		$code=IFilter::act(IReq::get('code','post'));
		$phone=IFilter::act(IReq::get('phone','post'));
		$userObj=new IModel('user');
		if($userObj->getObj('phone='.$phone)){
			die(JSON::encode(['code'=>0,'info'=>'手机号码已经存在']));
		}
		$checkRes=$this->checkMobileValidateCode($phone,$code);
		if($checkRes['code']==0){
			die(JSON::encode($checkRes));
		}
		//$userInfo=$userObj->getObj('id='.$tokenInfo['user_id']);
		$userObj->setData(
			['phone'=>$phone]
		);

		if($userObj->upDate('id='.$tokenInfo['user_id'])){
			die(JSON::encode(['code'=>1,'info'=>'修改成功']));
		}else{
			die(JSON::encode(['code'=>0,'info'=>'修改失败']));
		}
	}
	//获得说明
	public function getExplain(){
		$name=IFilter::act(IReq::get('name','post'));
		$name=IFilter::act(IReq::get('name'));
		//$name='积分说明';
		$helpObj=new IModel('help');
		$helpInfo=$helpObj->getObj('name=\''.$name.'\'');
		if(!$helpInfo){
			die(json_encode(['url'=>'']));
		}
		$url=IUrl::getHost().IUrl::creatUrl('/site/help?id='.$helpInfo['id'].'&device=android&cat_id='.$helpInfo['cat_id']);
		//echo $url;
		die(JSON::encode(['url'=>$url]));
	}
	//积分接口
	public function getPointList(){
		$token=IFilter::act(IReq::get('token','post'));
		$tokenObj=new IModel('token');
		if(!$tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}
		$query = new IQuery('point_log');
		$query->where  = "user_id = ".$tokenInfo['user_id'];
		$query->fields='datetime,value,intro,id';
		$query->order= "id desc";
		$res=$query->find();
		die(JSON::encode($res));
	}
	//地址
	public function getArea(){
		$areaObj=new IQuery('areas');
		$areaObj->where='parent_id=0';
		//ini_set('max_execution_time',3000);
		$areaObj->order='sort asc';
		//$areaObj->where='find_in_set(area_id,getTreeCategory(321000))';
		//$areaObj->limit=100000;
		$province=$areaObj->find();
		foreach($province as $k=>$v){
			$areaObj->where='parent_id='.$v['area_id'];
			$city=$areaObj->find();
			$province[$k]['nested']=$city;
			foreach($province[$k]['nested'] as $kk=>$vv){
				if(!empty($city)){
					$areaObj->where='parent_id='.$vv['area_id'];
					$county=$areaObj->find();
					$province[$k]['nested'][$kk]['nested']=$county;
				}

			}
		}
		die(JSON::encode($province));
	}
	//获得地址列表
	public function getUserAddress(){
		$token=IFilter::act(IReq::get('token','post'));
		$tokenObj=new IModel('token');
		if(!$tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}
		$addObj=new IQuery('address');
		$addObj->fields='id,zip,accept_name,mobile,address,`default` as type';
		$addObj->where='user_id='.$tokenInfo['user_id'];
		$userAddList=$addObj->find();
		die(JSON::encode($userAddList));
	}
	//设置默认地址
	public function setDefaultAdd(){
		$token=IFilter::act(IReq::get('token','post'));
		$tokenObj=new IModel('token');
		if(!$tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}
		$addId=IFilter::act(IReq::get('add_id','post'),'int');
		if($addId==""){
			die(JSON::encode(['code'=>0,'info'=>'数据不合法']));
		}
		$addObj=new IModel('address');
		$addObj->setData(['default'=>0]);
		$addObj->update('user_id='.$tokenInfo['user_id']);
		$addObj->setData(['default'=>1]);
		if($addObj->update('id='.$addId)){
			die(JSON::encode(['code'=>1,'info'=>'设置成功']));
		}else{
			die(JSON::encode(['code'=>0,'info'=>'设置失败']));
		}
	}
	//删除收获地址
	public function delAddress(){
		$token=IFilter::act(IReq::get('token','post'));
		$tokenObj=new IModel('token');
		if(!$tokenInfo=$tokenObj->getObj('token=\''.$token.'\'')){
			die(JSON::encode(['code'=>0,'info'=>'请登录']));
		}
		$addId=IFilter::act(IReq::get('add_id','post'),'int');
		if($addId==""){
			die(JSON::encode(['code'=>0,'info'=>'数据不合法']));
		}
		$addObj=new IModel('address');
		if($addObj->del('id='.$addId)){
			die(JSON::encode(['code'=>1,'info'=>'删除成功']));
		}else{
			die(JSON::encode(['code'=>0,'info'=>'删除失败']));
		}
	}
}


?>