//全选
function selectAll(nameVal)
{
	//获取复选框的form对象
	var formObj = $("form:has(:checkbox[name='"+nameVal+"'])");

	//根据form缓存数据判断批量全选方式
	if(formObj.data('selectType')=='' || formObj.data('selectType')==undefined)
	{
		$("input:checkbox[name='"+nameVal+"']:not(:checked)").attr('checked',true);
		formObj.data('selectType','all');
	}
	else
	{
		$("input:checkbox[name='"+nameVal+"']").attr('checked',false);
		formObj.data('selectType','');
	}
}
/**
 * @brief 获取控件元素值的数组形式
 * @param string nameVal 控件元素的name值
 * @param string sort    控件元素的类型值:checkbox,radio,text,textarea,select
 * @return array
 */
function getArray(nameVal,sort)
{
	//要ajax的json数据
	var jsonData = new Array;

	switch(sort)
	{
		case "checkbox":
		$('input:checkbox[name="'+nameVal+'"]:checked').each(
			function(i)
			{
				jsonData[i] = $(this).val();
			}
		);
		break;
	}
	return jsonData;
}
window.siteUrl = location.host=='localhost' ? location.origin+'/iwebshop/' : location.origin+'/';
window.noPicUrl = window.siteUrl+'views/scsg/skin/black/images/front/nopic_435_435.gif';
window.loadding = function(message){var message = message ? message : '正在执行，请稍后...';art.dialog({"id":"loadding","lock":true,"fixed":true,"drag":false}).content(message);}
window.unloadding = function(){art.dialog({"id":"loadding"}).close();}
window.tips = function(mess){art.dialog.tips(mess);}
window.realAlert = window.alert;
window.alert = function(mess){art.dialog.alert(mess);}
window.realConfirm = window.confirm;
window.confirm = function(mess,bnYes,bnNo)
{
	if(bnYes == undefined && bnNo == undefined)
	{
		return eval("window.realConfirm(mess)");
	}
	else
	{
		art.dialog.confirm(
			mess,
			function(){eval(bnYes)},
			function(){eval(bnNo)}
		);
	}
}
/**
 * @brief 删除操作
 * @param object conf
	   msg :提示信息;
	   form:要提交的表单名称;
	   link:要跳转的链接地址;
 */
function delModel(conf)
{
	var ok = null;            //执行操作
	var msg   = '确定要删除么？';//提示信息

	if(conf)
	{
		if(conf.form)
			var ok = 'formSubmit("'+conf.form+'")';
		else if(conf.link)
			var ok = 'window.location.href="'+conf.link+'"';

		if(conf.msg)
			var msg   = conf.msg;
	}
	if(ok==null && document.forms.length >= 1)
		var ok = 'document.forms[0].submit();';

	if(ok!=null)
		window.confirm(msg,ok);
	else
		alert('删除操作缺少参数');
}

//根据表单的name值提交
function formSubmit(formName)
{
	$('form[name="'+formName+'"]').submit();
}

//根据checkbox的name值检测checkbox是否选中
function checkboxCheck(boxName,errMsg)
{
	if($('input[name="'+boxName+'"]:checked').length < 1)
	{
		alert(errMsg);
		return false;
	}
	return true;
}

//倒计时
var countdown=function()
{
	var _self=this;
	this.handle={};
	this.parent={'second':'minute','minute':'hour','hour':'day','day':''};
	this.add=function(id)
	{
		_self.handle.id=setInterval(function(){_self.work(id,'second');},1000);
	};
	this.work=function(id,type)
	{
		if(type=="")
		{
			return false;
		}

		var e = document.getElementById("cd_"+type+"_"+id);
		var value=parseInt(e.innerHTML);
		if( value == 0 && _self.work( id,_self.parent[type] )==false )
		{
			clearInterval(_self.handle.id);
			return false;
		}
		else
		{var val=0;
			if(value==0){
				val=59;
			}else if(value<=10){
				val= '0'+(value-1);
			}else val=value-1;
			e.innerHTML = val;
			return true;
		}
	};
};

//倒计时函数
//min_id 小于min_id的不做处理
var countDown = function(min_id){
		$('.countdown').each(function(){
			var id = $(this).attr('id').split('-')[1];
			if(min_id && id<min_id)return true;
			var temp;
			var endTime = $(this).find('input[name=endTime]').val();
			var now = Date.parse(new Date())/1000;
			var count = endTime - now;
			var day = parseInt(count/(24*3600));
			count=count%(24*3600);
			var hour = (temp = parseInt(count/3600))<10 ? '0'+temp : temp;
			count = count%3600;
			var min = (temp=parseInt(count/60))<10 ? '0'+temp :temp ;
			var sec = (temp=count%60)<10 ? '0' + temp : temp;
			if(day==0){
				$('.day').remove();
			}else{
				$('#cd_day_'+id).text(day);
			}
			
			$('#cd_hour_'+id).text(hour);
			$('#cd_minute_'+id).text(min);
			$('#cd_second_'+id).text(sec);
			
			var count = new countdown();
			count.add(id);
		})
	};
//时分秒倒计时
$(function(){
	countDown();
})
//切换验证码
function changeCaptcha(urlVal)
{
	var radom = Math.random();
	if( urlVal.indexOf("?") == -1 )
	{
		urlVal = urlVal+'/'+radom;
	}
	else
	{
		urlVal = urlVal + '&random'+radom;
	}
	$('#captchaImg').attr('src',urlVal);
}
/*加法函数，用来得到精确的加法结果
 *返回值：arg1加上arg2的精确结果
 */
function mathAdd(arg1,arg2)
{
    var r1,r2,m;
    try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}
    try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}
    m=Math.pow(10,Math.max(r1,r2));
	n=(r1>=r2)?r1:r2;
    var res=(arg1*m+arg2*m)/m;
	return res.toFixed(n);
}

/*减法函数
 *返回值：arg2减arg1的精确结果
 */
function mathSub(arg2,arg1)
{
	var r1,r2,m,n;
	try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}
	try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}
	m=Math.pow(10,Math.max(r1,r2));
	//last modify by deeka
	//动态控制精度长度
	n=(r1>=r2)?r1:r2;
	return ((arg2*m-arg1*m)/m).toFixed(n);
}

/*乘法函数，用来得到精确的乘法结果
 *返回值：arg1乘以arg2的精确结果
 */
function mathMul(arg1,arg2)
{
   var m=0,s1=arg1.toString(),s2=arg2.toString();
    try{m+=s1.split(".")[1].length}catch(e){}
    try{m+=s2.split(".")[1].length}catch(e){}
    return Number(s1.replace(".",""))*Number(s2.replace(".",""))/Math.pow(10,m);
}

/*除法函数，用来得到精确的除法结果
 *返回值：arg1除以arg2的精确结果
 */
function mathDiv(arg1,arg2)
{
    var t1=0,t2=0,r1,r2;
    try{t1=arg1.toString().split(".")[1].length}catch(e){}
    try{t2=arg2.toString().split(".")[1].length}catch(e){}
    with(Math){
        r1=Number(arg1.toString().replace(".",""));
        r2=Number(arg2.toString().replace(".",""));
        return (r1/r2)*pow(10,t2-t1);
    }
}
/*实现事件页面的连接*/
function event_link(url)
{
	window.location.href = url;
}

//延迟执行
function lateCall(t,func)
{
	var _self = this;
	this.handle = null;
	this.func = func;
	this.t=t;

	this.execute = function()
	{
		_self.func();
		_self.stop();
	}

	this.stop=function()
	{
		clearInterval(_self.handle);
	}

	this.start=function()
	{
		_self.handle = setInterval(_self.execute,_self.t);
	}
}

/**
 * 进行商品筛选
 * @param url string 执行的URL
 * @param callback function 筛选成功后执行的回调函数
 */
function searchGoods(url,callback)
{
	var step = 0;
	art.dialog.open(url,
	{
		"id":"searchGoods",
		"title":"商品筛选",
		"okVal":"执行",
		"button":
		[{
			"name":"后退",
			"callback":function(iframeWin,topWin)
			{
				if(step > 0)
				{
					iframeWin.window.history.go(-1);
					this.size(1,1);
					step--;
				}
				return false;
			}
		}],
		"ok":function(iframeWin,topWin)
		{
			if(step == 0)
			{
				iframeWin.document.forms[0].submit();
				step++;
				return false;
			}
			else if(step == 1)
			{
				var goodsList = $(iframeWin.document).find('input[name="id[]"]:checked');

				//添加选中的商品
				if(goodsList.length == 0)
				{
					alert('请选择要添加的商品');
					return false;
				}
				//执行处理回调
				callback(goodsList);
				return true;
			}
		}
	});
}

	//详情页跳转
	function jumpTo(id){
		$('#'+id).trigger('click');
		location.href='#'+id;
	}
//显示客服qq
function showService(){
	$('.tbar-tab-chat').trigger('click');
}

/*异步获取联想关键词*/
function getKeywords(url,_this){
	var word = _this.val();
	var showDiv = $('.words-give');
	if (!word) {
		showDiv.html('').css('display','none');
		return false;
	}
	showDiv.css('display','block');
	$.ajax({
		type:'post',
		async:true,
		data:{word:word},
		dataType:'json',
		url:url,
		success:function(data){
			showDiv.html('');
			if(data.length>0){
				var appendHtml = '';
				for(var i in data){
					var div = '<p>'+data[i].keyword+'</p>';
					appendHtml +=div;
				}
				showDiv.append(appendHtml);
				showDiv.find('p').on('click',function(){
					_this.val($(this).text());
					showDiv.html('').css('display','none');
					location.href = searchUrl+'/'+$(this).text();
				})
			}else{
				showDiv.html('').css('display','none');
			}
			
		},
		error:function(){
			
		},
		complete: function(){
		
		}
	})
}
