<?php
/*****************TOP*****************
*                oauth 控制器
* @file          controller/oauth.php
* @package        
* @author        zyn
* @version       0.0.1
* @date          2015-12-1 10:00:42
* @link            
*****************TOP*****************/
class Oauth extends IController
{                                 
    public $layout = 'site_mini';        
    function init()
    {
        CheckRights::checkUserRights();
    }
    
    function txqq(){
        echo "qq登录成功";
    }
    
}
