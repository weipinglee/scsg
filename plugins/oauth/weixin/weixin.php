<?php
/**
 * @file weixin.php
 * @brief weixin的oauth协议登录接口
 * @date 2016-5-18 17:26:11
 * @version 0.1
 */

/**
 * @class Weixin
 * @brief Weixin的oauth协议接口
 */
class Weixin extends OauthBase
{
	private $apiId  = '';
	private $AppSecret = '';

	public function __construct($config)
	{
		$this->apiId  = $config['apiId'];
		$this->AppSecret = $config['AppSecret'];
	}

	public function getFields()
	{
		return array(
			'apiId' => array(
                    'label' => 'apiId',
                    'type'  => 'string',
            ),
            'AppSecret'=> array(
                'label' => 'AppSecret',
                'type'  => 'string',
            ),
		);
	}

	//获取登录url地址
	public function getLoginUrl()
	{
        $state = md5(uniqid(rand(), TRUE)); 
        return "https://open.weixin.qq.com/connect/qrconnect?appid={$this->apiId}&redirect_uri=".urlencode(parent::getReturnUrl())."&response_type=code&scope=snsapi_login&state={$state}";
	}

	//获取进入令牌
	public function getAccessToken($parms)
	{
        $code = $parms['code'];   
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->apiId}&secret={$this->AppSecret}&code={$code}&grant_type=authorization_code";
        $response = $this->get_contents($url);
        $result = json_decode($response,true);
        unset($response);
        if(isset($result['openid']) && $result['openid'])
        {
            ISession::set('openid',$result['openid']);
            ISession::set('access_token',$result['access_token']);
            ISession::set('refresh_token',$result['refresh_token']);
        }
        $data = $this->checkAvail(ISession::get('access_token'),ISession::get('openid'));
        if($data['errcode'] != '0' || $data['errmsg'] != 'ok'){
            
            //刷新access_token
            $result = $this->refresh_access_token(ISession::get('refresh_token'));
            ISession::set('openid',$result['openid']);
            ISession::set('access_token',$result['access_token']);
            ISession::set('refresh_token',$result['refresh_token']);
        }
	}
    
    public function get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response =  curl_exec($ch);
        curl_close($ch);

        //-------请求为空
        if(empty($response))
        {
            $this->error->showError("50001");
        }

        return $response;
    }
    
    /**
     * 刷新access_token（如果需要）
     *
     **/
    public function refresh_access_token($refresh_token){

        //三步：刷新access_token（如果需要）
        $refresh_url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=wx7c617be727038b3c&grant_type=refresh_token&refresh_token={$refresh_token}";

        $access_token = $this->get_contents($refresh_url);

        return json_decode($access_token,true);

    }
    
    /**
     *检验授权凭证（access_token）是否有效
     * @param string $access_token
     * @param string $open_id
     **/
    public function checkAvail($access_token='',$openid=''){

         if($access_token && $openid){

             $avail_url = "https://api.weixin.qq.com/sns/auth?access_token={$access_token}&openid={$openid}";

             $avail_data = $this->get_contents($avail_url);

            return json_decode($avail_data, TRUE);
         }

          return FALSE;
    }

	//获取用户数据
	public function getUserInfo()
	{
        $userInfo = $this->get_user_info(ISession::get('access_token'),ISession::get('openid'));
        return $userInfo;
	}

    public function get_user_info($access_token, $openid)
    {
        $info_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        
       $info_data = $this->get_contents($info_url);

       $arr = json_decode($info_data, TRUE);
        $userInfo['openid']   = $arr['openid'];
        $userInfo['unionid']   = $arr['unionid'];
        $userInfo['name'] = isset($arr['nickname']) ? $arr['nickname'] : '';
        return $userInfo;
    }

	public function checkStatus($parms)
	{
		return true;
	}
}