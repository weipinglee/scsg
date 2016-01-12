<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>javascript</title>
<script type="text/javascript" charset="UTF-8" src="/xinde/scsg/runtime/_systemjs/jquery/jquery-1.11.3.min.js"></script><script type="text/javascript" charset="UTF-8" src="/xinde/scsg/runtime/_systemjs/jquery/jquery-migrate-1.2.1.min.js"></script>
<script type='text/javascript' src='<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/common.js";?>'></script>
<link rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/afterpay.css";?>" type="text/css">
</head>
<body >

<div class="tanchu">
	<div class="dialog">
		<p class="con countdown" id="timeRemaining-1">请于
			<span id='cd_day_1'></span>天
			<span id='cd_hour_1'></span>小时
			<span id='cd_minute_1'></span>分
			<span id='cd_second_1'></span>秒	
			<input name='endTime' type='hidden' value='<?php echo isset($this->end_time)?$this->end_time:"";?>' />
		</p>
		<?php if(isset($_GET['order_type'])&&$_GET['order_type']==4){?>
		<p class="pay"><i class="succeed"></i>如已经成功支付，请点击：<a class="pay_ok" href='javascript:void(0)' onclick='window.parent.location.href="<?php echo IUrl::creatUrl("/ucenter/preorder_detail/id/".$this->order_id."");?>"'>已完成付款</a></p>
		<p class="pay"><i class="warning"></i>如付款遇到问题，你可以：<a class="pay_error" href='javascript:void(0)' onclick='window.parent.location.href="<?php echo IUrl::creatUrl("/block/doPayPresell/order_id/".$this->order_id."");?>"'>重新支付</a></p>
		<?php }else{?>
		<p class="pay"><i class="succeed"></i>如已经成功支付，请点击：<a class="pay_ok" href='javascript:void(0)' onclick='window.parent.location.href="<?php echo IUrl::creatUrl("/ucenter/order_detail/id/".$this->order_id."");?>"'>已完成付款</a></p>
		<p class="pay"><i class="warning"></i>如付款遇到问题，你可以：<a class="pay_error" href='javascript:void(0)' onclick='window.parent.location.href="<?php echo IUrl::creatUrl("/block/doPay/order_id/".$this->order_id."");?>"'>重新支付</a></p>
		<?php }?>
		<p class="con"><a href='javascript:void(0)' onclick='window.parent.location.href="<?php echo IUrl::creatUrl("/site/help_list/id/4");?>"'>支付常见问题</a></p>
	</div>
</div>
</body>
</html>
