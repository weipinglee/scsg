//获取短信验证码
function getMobileCode(phone){
	if(!phone)return false;
	var btn = $('input[name=getMessCode]');
	var toUrl = getCodeUrl;
	btnCount(btn);
	$.post(toUrl,{phone:phone},function(data){
		if(data.errorCode!=0){
			alt(data.mess);
		}
	},'json');
	
}
//倒计时，btn
function btnCount(btn){
	var sec = 60;
	btn.attr('disabled','true');
	var intervalId = setInterval(function(){
		btn.val('重新获取验证码('+sec+'秒)').css('color','#ccc');
		sec = sec-1;
		if(sec==-1){
			window.clearInterval(intervalId);
			btn.removeAttr('disabled').val('重新获取验证码').removeAttr('style');
		}
	},1000);
}
//验证当前手机账户
function checkMobileNow(){
	var code = $('input[name=code]').val();
	var toUrl = checkMobileUrl;
	$.post(toUrl,{code:code,nextUrl:nextUrl},function(data){
		//window.realAlert(JSON.stringify(data));
		if(data.errorCode==0){
			location.href=data.next;
		}else{
			alert(data.mess);
		}
		
	},'json');
}
//验证新手机
function checkMobileNew(){
	var code = $('input[name=code]').val();
	var newPhone = $('input[name=newPhone]').val();
	
	$.post(checkMobileUrl2,{code:code,newPhone:newPhone},
		function(data){
			if(data.errorCode==0){
			location.href=data.next;
		}else{
			alt(data.mess);
		}
		},'json');
}
//验证新邮箱并注册
function checkEmailNew(){
	var newEmail = $.trim($('input[name=newEmail]').val());	
	var code = $.trim($('input[name=emailCode]').val());
	if(!newEmail || !code)return false;

	$.post(checkEmailUrl,{code:code,newEmail:newEmail},
		function(data){//window.realAlert(JSON.stringify(data));
			if(data.errorCode!=0){
				alert(data.mess);
			}else{
				location.href=data.next;
			}
		},'json'
	);
}
//修改成功后跳转
function successTo(successToUrl){
	var sec = 3;
	var intervalId = setInterval(function(){
		$('.count').text(sec);
		sec--;
		if(sec==-1)location.href=successToUrl;
	},1000)
}
$(function(){
	$('#getNew').on('click',function(){
		var newPhone = $('input[name=newPhone]').val();
		if(!newPhone)alt('请正确填写手机号');
		getMobileCode(newPhone);
	})
	//发送邮箱验证码
	$('input[name=emailCheck]').on('click',function(){
		var newEmail = $('input[name=newEmail]').val();
		if(!newEmail)return false;
		var btn = $(this);
		
		$.post(getCodeUrl,{newEmail:newEmail},function(data){//window.realAlert(JSON.stringify(data));
			if(data.errorCode!=0){
				alert(data.mess);
			}else{
				alert('邮件已发送，请查收');
			}
		},'json');
		
	})
})


