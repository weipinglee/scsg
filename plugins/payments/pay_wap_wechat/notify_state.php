<?php
header('Content-Type:text/html;charset=utf-8');
ini_set('date.timezone','Asia/Shanghai');
require_once dirname(__FILE__)."/lib/WxPay.Api.php";
$id = $_POST['id'];
$msg = array();
if($id == 0)
{
    $msg['state'] = 'fail';
    $msg['info'] = '参数错误';
}
else
{
    $input = new WxPayOrderQuery();
    $input->SetOut_trade_no($id);
    $r = WxPayApi::orderQuery($input);
    if(isset($r['err_code']) && $r['err_code'])
    {
        $msg['state'] = 'fail';
        $msg['info'] = $r['err_code'];
    }
    elseif(isset($r['trade_state']) && $r['trade_state'])
    {
        $msg['state'] = $r['trade_state'];
        $msg['info'] = $r['trade_state'];
    }
    
}
echo json_encode($msg);