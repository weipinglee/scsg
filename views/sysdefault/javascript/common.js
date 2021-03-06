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
	this.parent={'second':'minute','minute':'hour','hour':""};
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
		{
			e.innerHTML = (value==0?59:(value-1));
			return true;
		}
	};
};

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
    return (arg1*m+arg2*m)/m;
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


/**
 * @brief 商品分类弹出框
 * @param string urlValue 提交地址
 * @param string categoryName 商品分类name值
 */
function selectGoodsCategory(urlValue,categoryName)
{
	//根据表单里面的name值生成分类ID数据
	var result = [];
	$('[name="'+categoryName+'"]').each(function()
	{
		result.push(this.value);
	});
	art.dialog.data('categoryValue',result);

	art.dialog.open(urlValue,{
		title:'选择商品分类',
		okVal:'确定',
		ok:function(iframeWin, topWin)
		{
			var categoryObject = [];
			var categoryWhole  = art.dialog.data('categoryWhole');
			var categoryValue  = art.dialog.data('categoryValue');
			for(var item in categoryWhole)
			{
				item = categoryWhole[item];
				if(categoryValue.indexOf(item['id']) != -1)
				{
					categoryObject.push(item);
				}
			}
			createGoodsCategory(categoryObject);
		},
		cancel:function()
		{
			return true;
		}
	})
}

//生成商品分类
function createGoodsCategory(categoryObj)
{
	if(!categoryObj)
	{
		return;
	}

	$('#__categoryBox').empty();
	for(var item in categoryObj)
	{
		item = categoryObj[item];
		var goodsCategoryHtml = template.render('categoryButtonTemplate',{'templateData':item});
		$('#__categoryBox').append(goodsCategoryHtml);
	}
}

//设置字段状态0和1切换
/*
 * @id int 数据id
 * @field str 字段
 * @table str 操作的表
 * @obj object 
 * 
 */
function set_type(id,field,table,obj)
{
	var rd = Math.random();
	url = window.siteUrl + 'admin_common/set_type/';
	$.getJSON(url,{id:id,field:field,table:table},function(content){
		if(content.isError ==  false)
		{
			if(content.succ == 0)
			{
				obj.innerHTML = '是';
				$(obj).removeClass('blue');
				$(obj).addClass('red2');
			}
			else
			{
				obj.innerHTML = '否';
				$(obj).removeClass('red2');
				$(obj).addClass('blue');
			}
		}
		else
		{
			alert(content.message);
		}
	});
}

/*
 * 编辑页面自动嵌入数据
 */
function edit_info_init(data,formName){
	/*if(areaTemplate){
		//初始化地域联动
		template.compile("areaTemplate",areaTemplate);
		//城市设置
		if(data.area)
			createAreaSelect('province',0,data.province);
			createAreaSelect('city',data.province,data.city);
			createAreaSelect('area',data.city,data.area);
		else{
			createAreaSelect('province',0,"");
		}
	}*/
	//修改模式
	var formObj = new Form(formName);
	formObj.init(data);

	//锁定字段一旦注册无法修改
	if(data.lockField)
	{
		var lockCols =data.lockField;
		for(var index in lockCols)
		{
			$('input:text[name="'+lockCols[index]+'"]').addClass('readonly');
			$('input:text[name="'+lockCols[index]+'"]').attr('readonly',true);
		}
	}
		

}
//根据规格字符串得到规格数据
function getSpec(spec_array){
	var spec_num = 0;
	for(var i=0;i<spec_array.length;i++){
		if(spec_array[i]=='{')spec_num++;
	}
	var s = 0;
	var spec_str = '';
	for(var i=0;i<spec_num;i++){
		var sta = spec_array.indexOf('{',s);
		var end = spec_array.indexOf('}',s);
		var s = end+1;
		var sub = spec_array.slice(sta,s);
		var a = $.parseJSON(sub);
		if(a.type==2){//图片
			spec_str += a.name + ':' + "<img height='40px' src='"+siteUrl+a.value+"'/></br>";
		}else{
			spec_str += a.name +':'+ a.value + '</br>';
		}
		
	}
	return spec_str;
}