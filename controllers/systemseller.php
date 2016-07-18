<?php
/**
 * @brief 商家登录控制器
 * @class Seller
 * @datetime 2014/7/19 15:18:56
 */
class SystemSeller extends IController
{
	public $layout = '';

	/**
	 * @brief 商家登录动作
	 */
	public function login()
	{
        $ident = IFilter::act(IReq::get('ident'), 'int');
        $seller_name = IFilter::act(IReq::get('username'));
		$name = IFilter::act(IReq::get('name'));
		$password    = IReq::get('password');
		$captcha = IFilter::act(IReq::get('captcha'));
        $callbackUrl    = IReq::get('callbackUrl','post');
		$message     = '';

		if($seller_name == '')
		{
			$message = '登录名不能为空';
		}
        else if($ident == 1 && $name == '')
        {
            $message = '商家名称不能为空';
        }
		else if($password == '')
		{
			$message = '密码不能为空';
		}
		else if($captcha!=ISafe::get('captcha')){
			$message = '验证码错误';
		}
        else
		{
            if($ident == 1)
            {
                $adminsellerObj = new IQuery('admin_seller as a');
                $adminsellerObj->join = 'join seller as s on a.seller_id = s.id';
                $adminsellerObj->where = 'admin_name = "'.$seller_name.'" and s.seller_name = "'.$name.'" and a.is_del = 0 and s.is_del = 0 and s.is_lock = 0';
                $adminsellerObj->fields = 'a.*';
                $sellerRow = $adminsellerObj->getObj();
                if($sellerRow && ($sellerRow['password'] == md5($password)))
                {
                    $dataArray = array(
                        'last_ip'   => IClient::getIp(),
                        'last_time' => ITime::getDateTime(),
                    );
                    $adminObj = new IModel('admin_seller');
                    $adminObj->setData($dataArray);
                    $where = 'id = '.$sellerRow["id"];
                    $adminObj->update($where);
                    
                    //根据角色分配权限
                    if($sellerRow['role_id'] == 0)
                    {
                        ISafe::set('admin_role_seller_name','超级管理员');
                    }
                    else
                    {
                        $roleObj = new IModel('admin_role');
                        $where   = 'id = '.$sellerRow["role_id"].' and is_del = 0 and seller_id = '.$sellerRow['seller_id'];
                        $roleRow = $roleObj->getObj($where);
                        ISafe::set('admin_role_seller_name',$roleRow['name']);
                    }
                    ISafe::set('seller_id',$sellerRow['seller_id']);
                    ISafe::set('admin_seller_id',$sellerRow['id']);
                    ISafe::set('seller_name',$sellerRow['admin_name']);
                    ISafe::set('seller_pwd',$sellerRow['password']); 
                    
                    if($callbackUrl)
                    {
                        $this->redirect("$callbackUrl");
                    }
                    else
                    {
                        $this->redirect('/seller/index');
                    }
                }
                else
                {
                    $message = '用户名与密码不匹配';
                }
            }
			else
			{
                $sellerObj = new IModel('seller');
                $sellerRow = $sellerObj->getObj('seller_name = "'.$seller_name.'" and is_del = 0 and is_lock = 0');
                if($sellerRow && ($sellerRow['password'] == md5($password)))
                {
                    $dataArray = array(
                        'login_time' => ITime::getDateTime(),
                    );
                    $sellerObj->setData($dataArray);
                    $where = 'id = '.$sellerRow["id"];
                    $sellerObj->update($where);

                    //存入私密数据
                    ISafe::set('admin_role_seller_name','商家');
                    ISafe::set('seller_id',$sellerRow['id']);
                    ISafe::set('seller_name',$sellerRow['seller_name']);
                    ISafe::set('seller_pwd',$sellerRow['password']);
                    if($callbackUrl)
                    {
                        $this->redirect("$callbackUrl");
                    }
                    else
                    {
                        $this->redirect('/seller/index');
                    }   
                }
                else
                {
                    $message = '用户名与密码不匹配';
                }
			}
		}

		if($message != '')
		{
			$this->redirect('index',false);
			Util::showMessage($message);
		}
	}

	//后台登出
	function logout()
	{
    	ISafe::clear('seller_id');
    	ISafe::clear('seller_name');
        ISafe::clear('seller_pwd');
    	ISafe::clear('admin_role_seller_name');
    	ISafe::clearAll();
    	$this->redirect('index');
	}
}