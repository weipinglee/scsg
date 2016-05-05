function showErrInfo(text){
	$("#errorInfo").show().text(text);
}
function hideErrInfo(){
	$("#errorInfo").hide();
}
function showPhoneTipWhenBlur(){
	var phone = $('#mobile').val();
	var a = /^1[0-9]{10}$/;
    if (!a.test(phone)) {
        showErrInfo('格式错误');

    } else {
        hideErrInfo();
		 $.ajax({
            type: "POST",
			dataType:"json",
            url: checkPhoneIsOneUrl,
            data: {
                phone: phone
            },
            success: function(e) {
				if(e.checkResult==1){//手机号码已注册
					showErrInfo('该手机号码已注册');
				}else{
					$('input[name=password]').removeAttr('disabled');
				}
			}
		})
    }
}
 function receiveCode(){

    if ($(".receive_code").hasClass("reacquire_code")) {
        return false

    }
	$(".receive_code").addClass('reacquire_code');
	var phone = $('#mobile').val();
    $.ajax({
        type: "POST",
        url: getMobileCodeUrl,
		dataType:'json',
        async: false,
		data : {phone:phone},
        success: function(a) {
            if (a) {
                if (0 == a.errorCode) {
                    var d = $(".receive_code");
					d.attr("disabled", true);
                    d.html("重新获取验证码(<i>59</i>)");
                    var f = d.find('i').text();
                    var c = setInterval(function() {
                        if (f > 0) {
                            f--;
                            d.find('i').text(f)
                        }
                    },
                    1000);
                    var b = setTimeout(function() {
                        d.removeAttr("disabled").removeClass('reacquire_code').html("重新获取验证码");
                    },
                    f * 1000);
                    return
                } else {
                    if (-1 == a.errorCode) {
                        showErrInfo('网络繁忙，请稍候再试');
                        return
                    } else {
                        showErrInfo('手机号码格式不正确');
                        return
                    }
                }
            }
        },
		complete:function(){
			
		}
    });
    return false
}

function registerByPhoneSubmit(){
	var btn = $('#registerBtn');
	
	var phone = $('#mobile').val();
	var a = /^1[0-9]{10}$/;
    if (!a.test(phone)) {
		showErrInfo('手机格式错误');
		return false;
	}
	var num = $('#code').val();
	var b = /^[0-9]{6}$/;
	if(!b.test(num)){
		showErrInfo('验证码格式错误');
		return false;
	}
	var c = /^[\S]{6,20}$/;
	var pwd = $('input[name=password]').val();
	if(!c.test(pwd)){
		showErrInfo('密码6-20位的非空字符');
		return false;
	}
	if(!$('#protocol').prop('checked')){
		return false;
	}
	btn.attr("disabled", "disabled").text("注册中...");
	var f = {
        phone: phone,
        password: pwd,
        password2: pwd,
        validPhoneCode: num,
        returnUrl: $("#returnUrl").val(),
        type: 0
        //手机注册

    };
	 jQuery.ajax({
        type: 'post',
        async: false,
        data: f,
        dataType: 'json',
        url: regPath,
        beforeSend: function() {

            },
        success: function(k) {
            if (k.errorCode == 0) {
				location.href=returnUrl;

            }
            else {
				btn.removeAttr("disabled").text("注册");
                switch (k.errorCode)
                {
                    case 1:
                    {
                        showErrInfo("不能为空");
                        break;

                    }
                    case 2:
                    {
                        showErrInfo("短信验证码错误");
                        break;

                    }
                    case 41:
                    {
                       showErrInfo("短信验证码已过期，请重新获取");
                        //}
                        break;

                    }
                    case 7:
                    {
                        showErrInfo("请获取短信验证码");
                        break;

                    }
                    case 15:
                    {
                        showErrInfo("格式错误，请输入正确的手机号");
                        break;

                    }
                    case 16:
                    {
                        showErrInfo("该手机号已存在");
                        break;

                    }
                    case 13:
                    {
                        showErrInfo("系统繁忙，请稍后再试");
                        break;
                        return

                    }
                    case 14:
                    {
                        window.location = k.returnUrl;
                        break;

                    }


                }

            }

        },
        error: function() {

            },
        complete: function() {

            },
        timeout: 1000,

    })
}
function login_button_recover(){
	$('#loginBtn').removeAttr('disabled').text('登陆');
}
function loginSubmit()
{  

	var btn = $('#loginBtn');
	btn.attr("disabled", true).text("登录中...");
   hideErrInfo();
    var j = $("input[name=username]").val();
    var p = $("input[name=password]").val();
   // var o = $("#vcd").val();
    if (p == "" || j == "") {
        showErrInfo("请输入账号和密码");
        login_button_recover();
        return false;
    }
	
   if (j.length > 50) {
        showErrInfo("账号长度不能超过50位");
        login_button_recover();
        return false;
    } else {
        if (j.toLowerCase().indexOf("<script") > -1 || j.toLowerCase().indexOf("<\/script") > -1) {
            showErrInfo( "账号中包含非法字符");
            login_button_recover();
            return false
        }
    }
  
    var l = /\s+/;
  
    if (l.test(p)) {
        showErrInfo( "密码不能有空格");
        login_button_recover();
        return false
    }
    var q = {
        login_info: j,
        password: p,
		validCode:$('input[name=validCode]').val(),
        returnUrl: returnUrl
    };
   
	
	$.ajax({
			type:'post',
			async:false,
			data:q,
			dataType:'json',
			url:logPath,
			beforeSend:function(){
				
			},
			success:function(e){
				 if (e) {
				 	if(e.errorCode !=0){
						switch(e.errorCode){
							case 1 :{
								showErrInfo("账号不能为空");
								break;
							}
							case 2 : {
								 showErrInfo( "密码不能为空");
								 break;
							}
							case 13 : {
								showErrInfo("您的账号有安全风险已冻结，请致电"+servicePhone+"解冻");
								break;
							}
							case 10 : {
								ShowValidCode();
								 showErrInfo("验证码错误，请重新输入");
								 break;
							}
							case 9 : {
								showErrInfo( "您的账号已被锁定");
								 break;
							}
							case 15 : {
								showErrInfo("您的账号已被删除");
								 break;
							}
							case 7 : {
								showErrInfo( "账号密码不匹配");
								if(e.errorTimes>3){
									ShowValidCode();
								}
								
								 break;
							}
						}
						
						
					}else{
						if(e.errorCode ==0){
							if(e.returnUrl)
								returnUrl = e.returnUrl;
		                    window.location = returnUrl;
		                    return
						}
					}
					login_button_recover();
				 }
			},
			complete:function(){
				login_button_recover();
			},
			timeout:1000,
		})
	
}
function ShowValidCode(){
	$('.valid_code_box').show();
}
