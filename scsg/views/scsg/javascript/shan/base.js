/*头部登陆条(包含实体购物券) bengin*/
//获取地址栏参数
function getQueryStringRegExp(name){    
	var reg = new RegExp("(^|\\?|&)"+ name +"=([^&]*)(\\s|&|$)", "i");     
	if(reg.test(location.href)){ 
		var result =  unescape(RegExp.$2.replace(/\+/g, " "));
		return result == "" ? "FALSE" : result;
	}else{
		return "FALSE";
	}
};
//带着当前地址跳转登陆页面
function login(aobj){		
	var strlocation = location.toString();
	var returnLocation;
	var _index = strlocation.indexOf("?");
	var isHasMid = strlocation.indexOf("mid");
	var isHasId = strlocation.indexOf("id");	//关键参数
	var isHasCid = strlocation.indexOf("Cid");	//关键参数
	var isHasRuleId = strlocation.indexOf("ruleid");	//关键参数,x元y件使用
	var tempLocation = "";
	if(_index != -1){
		returnLocation = strlocation.substring(0,_index);
	}else{
		returnLocation = strlocation;
	}
	if(isHasMid != -1){
		returnLocation += "?mid=" + mid;
	}
	if(isHasId != -1){
		tempLocation = returnLocation.indexOf("?") == -1 ? "?" : "&";
		returnLocation += tempLocation + "id=" + getQueryStringRegExp("id");
	}
	if(isHasCid != -1){
		tempLocation = returnLocation.indexOf("?") == -1 ? "?" : "&";
		returnLocation += tempLocation + "Cid=" + getQueryStringRegExp("Cid");
	}
	if(isHasRuleId != -1){
		tempLocation = returnLocation.indexOf("?") == -1 ? "?" : "&";
		returnLocation += tempLocation + "ruleid=" + getQueryStringRegExp("ruleid");
	}
	/*var uri = crossDomain + frontPath + "/Member/LoginForm.do?mid="+ mid +"&returnUrl=" + encodeURIComponent(returnLocation);
	aobj.href=uri;*/
	
	var getPassportUrl = frontPath + "/loginUrl.do?tt=" + Math.random();
	$.getJSON(getPassportUrl, function(data, status) {
		var passportUrl = data.passportLoginUrl;
		var uri = passportUrl + "?mid="+ mid +"&returnUrl=" + encodeURIComponent(returnLocation);
		window.location.href=uri;
	});
}
//获得指定元素指定属性,若一个参数就是取data-uri
function getAttrValueById(id,attr){
	var attrname;
	if(arguments.length == 1){attrname = "data-uri";}else{attrname = attr;}
	var tempValue = $("#" + id).attr(attrname);
	if(tempValue){return tempValue;}
	return null;
}
function gotoMyAccount(id){
	var uri = getAttrValueById(id);
	var url = crossDomain + frontPath+"/Member/index.jsp?mid=" + mid + "&url=" + encodeURIComponent(uri) + "&t=" +Math.random();
	$("#" + id).attr({href:url,target:"_blank"});
}
//获取登录信息
function getTopLoginInfo(){
	var params_L = new Object();
	params_L["random_L"] = Math.random();
	params_L["mid"] = mid;
	var apiUrl = frontPath + "/" + webIndex + "/finclude/includetoplogin.do?" + $.param(params_L) + "&callback=" + "?";
	$("#top_login_span").load(apiUrl);
}

//add by lihongyao 20150414 消息提醒
function setTopIntationMessage(){
	$.post("/Member/getInstationMsgCount.do?t="+Math.random(),function(data){
		var msgcount = data;
		if(msgcount == 0){
			$('#topmsgpng').removeClass("tmsgpng");
			$('#topmsgpng').html('');
		}else{
			$('#topmsgpng').show();
			$('#topmsgpng').addClass("tmsgpng");
			$('#topmsgpng').html(msgcount);
		}
	});
}
	
function setNoPayOrder(){
	$.post("/Member/AjaxMaiRemind.do"+"?t="+Math.random(),function(data){
		var sdata =  eval('(' + data + ')');
		var nopaycount = 0;
		if(sdata.nopayordercount.length > 0){
			nopaycount = sdata.nopayordercount[0];
		}
		if(nopaycount == 0){
			$('#topnopaypng').removeClass("nopaypng");
			$('#topnopaypng').html('');
		}else{
			$('#topnopaypng').show();
			$('#topnopaypng').addClass("nopaypng");
			$('#topnopaypng').html(nopaycount);
		}
	});
}

//初始化我的账户信息
function initMyAccountInfo(){
	getTopLoginInfo();
	$("#myaccount").hover(function(){
		var isFirstDo = $(this).attr("data-first");
		if(isFirstDo == "yes"){
			var apiUrl = frontPath + "/" + webIndex + "/finclude/includetopmyaccount.do?t=" + Math.random() + "&callback=" + "?";
			$("#myaccount_cont").removeClass("loadding").load(apiUrl);
			$(this).attr("data-first","no");
		}
		$(this).addClass("myaccount_curr");
		$("#myaccount_cont").show();
	},function(){
		$(this).removeClass("myaccount_curr");
		$("#myaccount_cont").hide();
	});
}

//设置”我的账户“消息提醒
function setAccountMessage() {
	var count = 0;
	var msgcount = 0;
	var nopayordercount = 0;
	$.ajax({
		type:"POST",
		url:'/Member/getInstationMsgCount.do',
		async:false,
		success:function(data){
			msgcount = data;
			nopayordercount = getNoPayOrder();
		}          
	}); 
	var intmsgcount = 0;
	var intnopayordercount = 0;
	if (!isNaN(parseInt(msgcount))) {
		intmsgcount = parseInt(msgcount);
	}
	if (!isNaN(parseInt(nopayordercount))) {
		intnopayordercount = parseInt(nopayordercount);
	}
	count = intmsgcount + intnopayordercount;
	if(count == 0){
		$('#myaccount').removeClass('has-msg');
		$('#topaccountpng').removeClass("accountpng");
		$('#topaccountpng').html('');
	}else{
		$('#myaccount').addClass('has-msg');
		$('#topaccountpng').show();
		$('#topaccountpng').addClass("accountpng");
		$('#topaccountpng').html(count);
	}
}

//获得”待付款订单“消息条数
function getNoPayOrder(){
  var nopayordercount = 0;
	$.ajax({
		type:"POST",
		url:'/Member/AjaxMaiRemind.do' + '?t=' + Math.random(),
		async:false,
		success:function(data){
	    	var sdata =  eval('(' + data + ')');
			nopayordercount = sdata.nopayordercount;
		}         
	});
	return nopayordercount;
}

//获得窗口可见高winHeight
function findDimensions(){
	var winHeight1=0,winHeight2=0,winHeight3=0,winHeight4=0;
	if(window.innerWidth){winHeight1=window.innerHeight;} 
	if(document.body){if(document.body.clientWidth){winHeight2=document.body.clientHeight;}if(document.body.scrollHeight){winHeight3=document.body.scrollHeight;}}
	if(document.documentElement&&document.documentElement.clientWidth){winHeight4=document.documentElement.clientHeight;}
	winHeight=Math.max(winHeight1,winHeight2,winHeight3,winHeight4);
	return winHeight;
};
//获取当前滚动高度
function getCurrScrollTop(){
	var currScrollTop =  document.body.scrollTop;
	if(currScrollTop == 0)
		currScrollTop = document.documentElement.scrollTop;
	return currScrollTop;
}
//设置背景遮罩层
function showShade(){
	if($("#layoutBg").length > 0){return;}
	var winHeight = findDimensions();
	var e = document.createElement("div");e.id="layoutBg";e.style.cssText="position:absolute;left:0px;top:0px;width:100%;height:"+winHeight+"px;filter:Alpha(Opacity=50);opacity:0.5; background-color:#000;z-index:1000;";
	document.body.appendChild(e);
};
//关闭背景遮罩层
function closeShade(){
	if($("#layoutBg").length > 0){$("#layoutBg").remove()}
}
//重置背景遮罩层
function resizeShade(){
	if($("#layoutBg").length > 0){
		$("#layoutBg").css("height", $(document).height());
		$("#layoutBg").css("width", $(document).width());
	}
}
/*头部小购物车 begin*/
//回调函数矫正小购车商品数量
function callbackSetSmallCartAmount(data){
	if(data && data.totalAmount >= 0){
		//关键参数重置
		$("#smallcart_items_amount").attr("data-count",0);
		$("#smallcart_items_amount").val(data.cartitemsAmount + "");
		$("#smallcart").attr("data-first","yes");
		if(data.totalAmount > 99){
			$(".smallcart_totalamount").html(99 + "+");
		}else{
			$(".smallcart_totalamount").html(data.totalAmount + "");
		}
		$("#totleCount span").html(data.totalAmount + "");
		$("#totalMustPayPrice").html(data.totalMustPayPrice.toFixed(2) + "");
	}
	loadSmallCartContent();
}
//获取购物车商品总数量
function getCartTotalAmount(){
	var uri = crossDomain + frontPath + "/frontendjson/smallcartinfo.do?mid=" + mid + "&callback=?&t=" + Math.random();
	$.getJSON(uri, callbackSetSmallCartAmount);
}

//循环最多3次获取购物车商品总数量的请求。
function getCartTotalAmountForMax(){
	setTimeout(function(){
		var $items_amount_inp = $("#smallcart_items_amount");
		var count = $items_amount_inp.attr("data-count");	//循环计数器,最多循环三次获取购物车数量的请求
		if($items_amount_inp.val() == "init" && count < 3){
			getCartTotalAmount();
			getCartTotalAmountForMax();
			count++;
			$items_amount_inp.attr("data-count",count);
		}else{
			loadSmallCartContent();
		}
	},"300");
}

//删除头部小购物车中商品
function deleteSmallCart(cartId, productid, isPresent, index){
	//emarbox删除购物车监测码 20130903_zhaogangqiang
	try{deleteOneItem(productid);}catch(e){}
//	$("#delsmallcart" + index + "_" + cartId + "_" + productid).hide();
	var params = new Object();
	params["mid"] = mid;
	params["cartid"] = cartId;
	var url='';
	if(isPresent==1){
		params["productid"] = 0;
		params["presentid"] = productid;
		uri = crossDomain + frontPath + "/frontendjson/delpresent.do?" + $.param(params) + "&callback=?";
	}else{
		params["productid"] = productid;
		uri = crossDomain + frontPath + "/frontendjson/delcartitem.do?" + $.param(params) + "&callback=?";
	}
	$.getJSON(uri,callbackSetSmallCartAmount);
}
//删除头部小购物车中X元Y件商品
function deleteSmallCartXY(cartId,ruleId){
//	$("#delsmallcartxy_" + cartId + "_" + ruleId).hide();
	var params = {};
	params["mid"] = mid;
	params["cartid"] = cartId;
	params["ruleid"] = ruleId;
	uri = crossDomain + frontPath + "/delcartitemxy.do?" + $.param(params) + "&callback=?";
	$.getJSON(uri,getCartTotalAmount);
}
function isShowSmallcartScrollBar(amount){
	if(amount == 0){
		$("#smallcart_cont").html("<div class='empty-cart'>购物车中还没有商品，赶紧选购吧！</div>");
		$("#smallcart").attr("data-first","no");
		getWinHeight();
	}else if(amount >10){
		$("#smallcart_scroll").css("overflow-y","scroll");
	};
}
function loadSmallCartContent(){
	var smallcart_items_amount = $("#smallcart_items_amount").val();
	if(smallcart_items_amount == "init"){
		//此处代码出现可能性极低，因初始化时最多可以请求获取购物车数量请求6次
		$("#smallcart_cont").html("<div class='smallcart_amount0'>网络繁忙，请稍候重试~</div>");
		setTimeout(getCartTotalAmount,"500");
		return;
	}
	isShowSmallcartScrollBar(smallcart_items_amount);
	var isFirstDo = $("#smallcart").attr("data-first");
	if(isFirstDo == "yes"){
		var paramsTT = new Object();
		paramsTT["randomT"] = Math.random();
		paramsTT["mid"] = mid;
		
		var url = frontPath + "/" + webIndex + "/finclude/includetopsmallcartcontent.do";
		$.get(url, paramsTT, function(data){
			$("#smallcart_cont").html(data);
			if($("#smallcart_cont").length > 0){
				isShowSmallcartScrollBar(smallcart_items_amount);
				if($(".xy_miniprice").length > 0){
					$(".xy_miniprice").each(function(){
						var xy_miniprice = parseFloat($(this).html());
						$(this).html(xy_miniprice);
					});
				}
			}
			getWinHeight();
		});
		$("#smallcart").attr("data-first","no");
	};
}

//初始化头部小购物车
function initSmallCartInfo(){
	//获取购物车当前商品数
	getCartTotalAmount();
	/*$("#smallcart").click(function(){
			//lazyloadSmallCart
			getCartTotalAmountForMax();
			$("#cartlink").addClass("cartlink_curr");
			$("#cartarrow").addClass("cartarrow_curr");
			$("#smallcart_cont").show();
		},function(){
			$("#cartlink").removeClass("cartlink_curr");
			$("#cartarrow").removeClass("cartarrow_curr");
			$("#smallcart_cont").hide();
	});*/
}
/*头部小购物车 end*/

/*头部轮播*/
$(function(){
	var num=0;
	var list=$(".f_shopping_slide li").length-1;
	function Time(){
		if(num <= list){
			$(".f_shopping_slide li").hide();
			$(".f_shopping_slide li").eq(num).show();
			$(".icon_item span").removeClass("li_hover");
			$(".icon_item span").eq(num).addClass("li_hover");
		}else{
			num = 0;
			$(".f_shopping_slide li").hide();
			$(".f_shopping_slide li").eq(num).show();
			$(".icon_item span").removeClass("li_hover");
			$(".icon_item span").eq(num).addClass("li_hover");
			}
		num++;
		}
		var $div_list=$(".f_shopping_slide li");
		var $li_list=$(".icon_item span");
		$li_list.hover(function(){
			var index=$li_list.index(this);
			$(this).addClass("li_hover").siblings().removeClass("li_hover");
			$(".f_shopping_slide li").eq(index).show().siblings().hide();
			clearInterval(Times);
			num = index;
		},function(){
			Times= setInterval(Time,2000);	
		});
		var Times= setInterval(Time,2000);
})

/*bottomRightBar begin*/
function topFixed(){
	if($("#rightbar").length == 0)return;
	getCurrScrollTop() > 300 ? document.getElementById("rightbar").style.display="block" : document.getElementById("rightbar").style.display="none";
	if(navigator.userAgent.indexOf("MSIE 6.0")>0){
		document.getElementById("rightbar").style.top=document.documentElement.clientHeight+document.documentElement.scrollTop-document.getElementById("rightbar").clientHeight-44+"px";
		document.getElementById("rightbar").style.position="absolute";
	}
}
/*// 右下角悬浮栏
function initRightBar(){
	if($("#rightbar").length == 0)return;
	$("#rightbar a.def").hover(
		function(){
			var tip = $(this).attr("data-tip");
			$(this).addClass("curr").html(tip);
		},function(){
			$(this).removeClass("curr").html("");
		}
	);
	window.onscroll=topFixed,window.resize=topFixed,topFixed();
	initHotDeal();
}*/
//页面打开判断弹出层广告显示方式(显示or隐藏)
var sameBanner = false;
var isShowHotDeal = true;
var t_hotDeal = -1;
function initHotDeal(){
	$("#hot_deal_def").click(showAdv).hover(function(){
		var imgsrc = $("#hot_deal_layer img").length;
		if(imgsrc > 0){
			t_hotDeal = setTimeout(function(){
				if(isShowHotDeal){
					showAdv();
					isShowHotDeal=false;
				}
			},400);
		}
		},function(){clearTimeout(t_hotDeal);isShowHotDeal = true;closeHotDeal() ;});
	
	$("#scancode").click(showAdvcode).hover(function(){
		var imgsrc = $("#code_deal_layer img").length;
		if(imgsrc > 0){
			t_hotDeal = setTimeout(function(){
				if(isShowHotDeal){
					showAdvcode();
					isShowHotDeal=false;
				}
			},400);
		}
	},function(){clearTimeout(t_hotDeal);isShowHotDeal = true;closeCodeDeal();});
	
	$("#hot_deal_close").click(closeHotDeal);
	$("#scancode_close").click(closeCodeDeal);
	/*$("#hot_deal_cur").click(function(){closeHotDeal();isShowHotDeal = false;});
	historyCookie = checkCookie();
	if(!historyCookie || !sameBanner){showAdv();}else{hideAdv();}*/
}
//检查弹出层广告是否当日关闭或更新
function checkCookie(){
	var currentDate = new Date().toLocaleDateString();
	var currentPath = $("#hot_deal_layer .lazyload").attr("original") ? $("#hot_deal_layer .lazyload").attr("original") : "defPath";
	historyCookie = $.cookie("hotDeal_Adv");
	if(historyCookie){var parts = historyCookie.split("^");if(parts.length>1){if(parts[0] == currentDate && parts[1] == currentPath){sameBanner = true;}}}
	if(!sameBanner){$.cookie("hotDeal_Adv",currentDate+"^"+currentPath,{expires:24,path:'/'});}
	return historyCookie;
}
//关闭
function closeHotDeal(){
	$("#hot_deal_layer").fadeOut(400,hideAdv);
	checkCookie();
}
function closeCodeDeal(){
	$("#code_deal_layer").fadeOut(400,hideCodeAdv);
	checkCookie();
}
//隐藏
function hideAdv(){
	$("#hot_deal_layer").hide();
}
function hideCodeAdv(){
	$("#code_deal_layer").hide();
}
//显示
function showAdv(){
	var imgsrc = $("#hot_deal_layer img").length;
	if(imgsrc > 0){
		$("#hot_deal_layer").fadeIn(400);
		var hdiObj = $("#hot_deal_img"), isFirst = hdiObj.attr("data-first");
		if(isFirst == "y"){hdiObj.find(".lazyload").attr("src",hdiObj.find(".lazyload").attr("original"));hdiObj.attr("data-first","n");}
	}
}
function showAdvcode(){
	var imgsrc = $("#code_deal_layer img").length;
	if(imgsrc > 0){
		$("#code_deal_layer").fadeIn(400);
		var hdiObj = $("#hot_deal_img"), isFirst = hdiObj.attr("data-first");
		if(isFirst == "y"){hdiObj.find(".lazyload").attr("src",hdiObj.find(".lazyload").attr("original"));hdiObj.attr("data-first","n");}
	}
}

//分享
$(function(){
	$('.share_icon').click(function(){
		showShade();
		$("#share").show();
		$("#share").attr('data-rule-id', $(this).attr("data-rule-id"))
	})
	$('#share h4 s').click(function(){
		closeShade();
		$(this).parent().parent('#share').hide();  
	})
	$('.share_icon').hover(function(){
		$(this).addClass('i_hover');
	},function(){
		$(this).removeClass('i_hover');
	})
});
//初始化图片lazyload参数配置
function initLazyloadParams(){
	if($("img.lazyload").length == 0) { return; }
	$("img.lazyload").lazyload({
		threshold     : 0,    /*预加载可见高度100px*/
		skip_invisible: false,/*隐藏层图片不参与*/
		failure_limit : 4	  /*页面流中从第几个img标签开始计算*/
	});
}

$(function(){
	initSmallCartInfo();
	initLazyloadParams();
	initHotDeal();
});

//添加站点切换

/*分渲染部分begin*/
function writeResult(siteinfoArray){
	if(siteinfoArray.length >= 1){
		var defCity = siteinfoArray[1];
		$("#defCity").html(defCity);
	}
}
//显示切换分站弹出层
var changeMid=0;
function showChangeSub(){
	if($("#layoutBg").length > 0){
		closeChangeSub();
		return;
	}
	$('.site-p').addClass('site-hover');
	showShade();
	
	//把底部加载好的地区移入头部选择
	var sitecont = $("#topchangesub").html();
	if(sitecont != undefined && sitecont != ""){
		$("#sitecont").html(sitecont);
		$("#topchangesub").html("");
	}
	$("#sitename").addClass("sitename_curr");
	$("#sitecont").show();
	$("#siteconfirm").hide();
	$(".siteinfo").css("z-index","1001");
	
	$("#siteinfo").css({"border-left-color": "#eeeeee", "background-color": "#fff"});	
}
//关闭
function closeChangeSub(){
	setSiteInfoShort();
	if($("#layoutBg").length > 0){
		document.body.removeChild($("#layoutBg")[0]);
	}
	$('.site-p').removeClass('site-hover');
	$("#sitename").removeClass("sitename_curr");
	$("#sitecont").hide();
	$("#siteconfirm").hide();
	$(".siteinfo").css("z-index","999");
	
	$("#siteinfo").css({"border-left-color": "#F5F5F5", "background-color": "transparent"});
}

function setSiteInfoShort(){
	var siteinfo = $.cookie("siteinfo");
	if(!siteinfo){setTimeout(function(){initSiteInfoDelay()},1000);return;}
	var siteinfoArray = $.cookie("siteinfo").split("|");
	var domainValue = ".womai.com";
	if(siteinfoArray.length > 2){domainValue = siteinfoArray[2];}
	$.cookie("siteinfotemp", mid, {path:'/',domain:domainValue,expires:2});

}
function initSiteInfoDelay(){
	var siteinfo = $.cookie("siteinfo");
	if(!siteinfo){setTimeout(function(){initSiteInfoDelay()},1000);return;}//若取不到siteinfo信息就延时1s后再执行，异步返回慢的时候会发生此情况
	var isShowChangeSub = false, siteinfoArray = siteinfo.split("|"), siteinfoshort = $.cookie("siteinfotemp");
	if(siteinfoArray && siteinfoArray.length > 0){
		writeResult(siteinfoArray);
		if(siteinfoArray[0] != mid){isShowChangeSub = true;}
	}
	if(siteinfoshort && siteinfoshort == mid)return;
	if(!judgeSupportSite(mid))return;
	if(isShowChangeSub){showChangeSub();}
}

///确认切换
function changeSubConfirm(currchangemid){
	$("#sitecont").hide();
	changeSubConfirmCommon(currchangemid,"siteconfirm");
}

function changeSubConfirmCommon(currchangemid,confirmLayerId){
	if(currchangemid == mid) {
		closeChangeSub();
		return;
	}
	if(!judgeSupportSite(currchangemid)){
		closeChangeSub();
		comAlert("对不起，本页面" + getSiteNameFromMid(currchangemid) + "暂未上线。");
		return;
	}
	changeMid = currchangemid;
	var uri = crossDomain + frontPath + "/frontendjson/smallcartinfo.do?mid=" + mid + "&callback=?";
	$.getJSON(uri,function(data){
		if(data && data.totalAmount > 0){
			$("#" + confirmLayerId).show();
		}else if(data){
			doChangeSub();
		}
	});
}

function doChangeSub(){
	closeChangeSub();
	var returnUrl = "";
	try{
		frontDomain;
		returnUrl = formatchangesub(changeMid);
	}catch(e){
		returnUrl = window.location.href;
	}
	//拼接参数发送请求
	var params = {};
	params.changeMid = changeMid;
	params.fromMid = mid;
	params.returnUrl = returnUrl;
	var uri = frontPath + "/Site/changeSite.do?" + $.param(params);
	window.location.href = uri;
}

var supportSite = supportSite || [0,100,200];

function judgeSupportSite(currmid){
	for(var i=0; i<supportSite.length; i++){
		if(currmid == supportSite[i]){
			return true;
		}
	}
	return false;
}
function deleteUnselItem(cartId,productid,isPresent,index){
	$("#delsmallcart" + index + "_" + cartId + "_" + productid).hide();
	var params = new Object();
	params["mid"] = mid;
	params["cartid"] = cartId;
	params["productid"] = productid;
	var url=crossDomain + frontPath + "/frontendjson/delunselcartitem.do?" + $.param(params) + "&callback=?";
	$.getJSON(url,params,callbackSetSmallCartAmount);
	
}
function getSiteNameFromMid(mid){
	var siteName = "";
	switch(Number(mid)){
		case 0:siteName="华北站";break;
		case 100:siteName="华东站";break;
		case 200:siteName="华南站";break;
		default:siteName="华北站";break;
	}
	return siteName;
}
$.fn.extend({
	//华北站点击事件
	siteShowSubcontClick:function(cont,layer){
		$(this).click(function(){
			if($(this).hasClass("cur")){
				document.onclick = function(e) {
	   	 		   e = window.event || e; // 兼容IE7
		 		   obj = $(e.srcElement || e.target);
		  		   // 点击区域位于当前节点
		 		   if ($(obj).is(".site_cont")) {
					  $(this).removeClass("cur");
					  $(cont).hide();
				      $(layer).hide();
			       }
				   if ($(obj).is(".closebtn")){
					  $(this).addClass("cur");
			    	  $(cont).show();
				      $(layer).show();
				   }
                 }
			}else{
				$(this).addClass("cur");
				$(cont).show();
				$(layer).show();
			};
		});
	},
	//菜单hover
	showSubcontHover:function(submenu){
		$(this).hover(function(){
			$(this).addClass("cur");
			$(this).find(submenu).show();	
		},function(){
			$(this).removeClass("cur");
			$(this).find(submenu).hide();	
		});
	}
})
/*----------------------------------------------------------------------*/
$(function(){
//导航
	$(".shan-nav").find("li").hover(function(){
		$(this).addClass("current").siblings().removeClass("current");	
	},function(){
		$(this).removeClass("current").parent().find('li').eq(0).addClass("current");
	})
})
$(window).scroll(function(){
	//悬浮购物车20141217
	/*var winHeight = $(window).height();
	var shanOffsetTop = $('#j-shan-all-infor').offset().top;
	shanOffsetTop -= 35;
	$('.float-cart-con').height(winHeight);
	if($(window).scrollTop()>shanOffsetTop){
		$('.float-cart-con').show();
	}else {
		$('.float-cart-con').hide();
	}*/
	 //鼠标滑动指定高度 顶显示
  	 if(($(window).scrollTop())>200){
  		 $('#to-top').css('visibility','visible');
      }else{
  		$('#to-top').css('visibility','hidden'); 
  	 }
})

//悬浮购物车20150304
$(function(){
	//页面加载时获取悬浮购物车的高度
	getWinHeight();
	var screenwidth = screen.width;
	$(window).resize(function(){
		getWinHeight();
	})
	var cartCon = $('.float-cart-r');
	
	//绑定购物车点击 悬浮状态
	$(".cart-infor .cont").bind({
		click: function(e){ 
			getCartTotalAmountForMax();
			if (cartCon.attr('data-visible')==0){
				$('.float-cart-con').stop().animate({right:'0'},500);
				$(this).addClass('hover2');	
				if(screenwidth < 1210){
					$(this).parents('.cart-infor-parent').addClass('cart-infor-click');
					$(this).parents('.cart-infor-parent').find('.link_info').addClass('info-bottom-click');
					$(this).parents('.cart-infor-parent').find('.cu').addClass('cu-bottom-click');
				}
				cartCon.attr('data-visible','1')
			}else{
				$('.float-cart-con').stop().animate({right:'-285px'},500);
				$(this).removeClass('hover2');	
				if(screenwidth < 1210){
					$(this).parents('.cart-infor-parent').removeClass('cart-infor-click');
					$(this).parents('.cart-infor-parent').find('.link_info').removeClass('info-bottom-click');
					$(this).parents('.cart-infor-parent').find('.cu').removeClass('cu-bottom-click');
				}
				cartCon.attr('data-visible','0')
			}
			e.stopPropagation();
	    },
		mouseover: function(){
			if(screenwidth < 1210){
				$(this).parents('.cart-infor-parent').addClass('cart-infor-hover');
				$(this).parents('.cart-infor-parent').find('.link_info').addClass('info-bottom-hover');
				$(this).parents('.cart-infor-parent').find('.cu').addClass('cu-bottom-hover');
			}
			$(this).addClass('hover');	
		},  
		mouseout: function(){
			if(screenwidth < 1210){
				$(this).parents('.cart-infor-parent').removeClass('cart-infor-hover');
				$(this).parents('.cart-infor-parent').find('.link_info').removeClass('info-bottom-hover');
				$(this).parents('.cart-infor-parent').find('.cu').removeClass('cu-bottom-hover');
			}
			$(this).removeClass('hover');
		}  
 	 });
	
	$(".cart-infor-parent").bind({
		mouseover: function(){
			if(screenwidth < 1210){
				$(this).addClass('cart-infor-hover');
				$(this).find('.link_info').addClass('info-bottom-hover');
				$(this).find('.cu').addClass('cu-bottom-hover');
			}
			$(this).addClass('hover');	
				
		},  
		mouseout: function(){
			if(screenwidth < 1210){
				$(this).removeClass('cart-infor-hover');
				$(this).find('.link_info').removeClass('info-bottom-hover');
				$(this).find('.cu').removeClass('cu-bottom-hover');
			}
			$(this).removeClass('hover');
		} 
	}); 
	 $('.float-cart-con').click(function(e){
		 e.stopPropagation();
	 })
	 $('#to-top').click(function(){
		 $('html,body').animate({scrollTop:0},300);
	 })
	 $(document).click(function() {
		$('.float-cart-con').stop().animate({right:'-285px'},500);
		cartCon.attr('data-visible','0')
		if(screenwidth < 1210){
			$('.cart-infor-parent').removeClass('cart-infor-hover');
			$('.cart-infor-parent').removeClass('cart-infor-click');
			$('.cart-infor-parent').removeClass('cart-infor-click');
			$('.cart-infor-parent').find('.cu').removeClass('cu-bottom-click');
			$('.info-bottom').find('.link_info').not('.top').removeClass('info-bottom-click');
		}
		$(".cart-infor .cont").removeClass('hover2'); 
	}); 
	 
	 $('.float-cart-r li').live('hover',function(event){ 
		 if(event.type=='mouseover'){ 
			 $(this).find('.pro-price a').css('display','block');
		 }else{ 
			 $(this).find('.pro-price a').css('display','none');
		 } 
	 });
})
//获取悬浮购物车显示的高度
function getWinHeight(){
	var winHeight = $(window).height();
	$('.cart-infor-parent,.float-cart-r,.empty-cart').height(winHeight);
	var marginTop = Math.floor(winHeight*0.15);
	$('.cart-infor').css('margin-top',marginTop);
	var specialHeight = winHeight - ($('.float-cart-r .total').height()+40) - 45;
	var ulHeight = $('.float-cart-r ul').height();
	if( ulHeight >= specialHeight){
		$('.float-cart-r ul').height(specialHeight);
		$('.float-cart-r ul').addClass('special');
	}else{
		$('.float-cart-r ul').height(ulHeight);
		$('.float-cart-r ul').removeClass('special');
	}
}
//数字转换为文字形式
function convertToChinese(num){
	var N = [
			    "零", "一", "二", "三", "四", "五", "六", "七", "八", "九"
			];
    var str = num.toString();
    var len = num.toString().length;
    var C_Num = [];
    for(var i = 0; i < len; i++){
        C_Num.push(N[str.charAt(i)]);
    }
    return C_Num.join('');
}