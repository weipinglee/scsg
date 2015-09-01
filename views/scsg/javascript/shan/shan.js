//�����İ�
$(function(){
	//���ÿ�խ����ʽ 
	var  screenwidth = screen.width;
	if(screenwidth < 1210){
		$("body").addClass("thousand");
	}else{
		$("body").removeClass("thousand");
	};
	
	//�̼���ҳ�ֲ�ͼ����Ч��
	var n = 0;
	var i = 0;
	var count = $(".banner-adv").find("li").length;
	var t = setInterval(showAuto,3000);
	$(".banner-adv li:not(:first-child)").hide();
	$(".banner-icon").find("li").mouseover(function() {
		if(!$(this).hasClass("current")){
			i = $(this).index();
			if (i >= count) return;
			$(this).addClass("current").siblings().removeClass("current");
			$(".banner-adv li").filter(":visible").hide().parent().children().eq(i).stop(true,true).fadeIn(600);
		};
	});
	$(".banner-adv li, .banner-icon li").hover(
		function(){
			clearInterval(t);
		}, 
		function(){
			t = setInterval(showAuto,3000);
		}
	);
	function showAuto(){
		if(count == 1){
			return;
		}
		i = i >= (count - 1) ? 0 : ++i;	
		$(".banner-adv li").filter(":visible").hide().parent().children("li").eq(i).stop(true,true).fadeIn(600);
		$(".banner-icon").find("li").eq(i).addClass("current").siblings().removeClass("current");
	};
	//��ȡ�ֲ�ͼIcon��left
	var domWidth =$(window).width();
	if(screenwidth < 1210){
		var leftWidth = (domWidth - 1000)/2;
		$(".banner-icon").css("left",leftWidth);
		
	}else{
		var leftWidth = (domWidth - 1210)/2;
		$(".banner-icon").css("left",leftWidth);
	};
	//������Ʒ ��ͣЧ��
	$(".shan-all-li").find("li").not(".end").hover(function(){
		$(this).addClass("hover");
	},function(){
		$(this).removeClass("hover");
	});
	
})

// ��������� �ֲ�
$(function(){
	var j = 0;
	var num = $(".shan-intro-child").length;
	var shan = setInterval(showShanIntro,3000);
	$(".shan-intro-icon").find("li").mouseover(function() {
		if(!$(this).hasClass("current")){
			j = $(this).index();
			if (j >= num) return;
			$(this).addClass("current").siblings().removeClass("current");
			$(".shan-intro-child").filter(":visible").hide().parent().find(".shan-intro-child").eq(j).stop(true,true).fadeIn(600);
		};
	});
	$(".shan-intro-child, .shan-intro-icon li").hover(
		function(){
			clearInterval(shan);
		}, 
		function(){
			shan = setInterval(showShanIntro,3000);
		}
	);
	function showShanIntro(){
		j = j >= (num - 1) ? 0 : ++j;	
		$(".shan-intro-child").filter(":visible").hide().parent().find(".shan-intro-child").eq(j).stop(true,true).fadeIn(600);
		$(".shan-intro-icon").find("li").eq(j).addClass("current").siblings().removeClass("current");
	};
})


$(function(){
	//�б�ҳ ��Ʒ�б���ͣЧ��
	$(".list-content").find("li").hover(function(){
		$(this).addClass("hover");
	},function(){
		$(this).removeClass("hover");
	});
	//�б�ҳ banner left
	var windWidth =$(window).width();
	var listLeftWidth =(windWidth - 317)/2;
	$(".banner-date").css("left",listLeftWidth);
})
//����ʱ  ������******
function currentProductTimer(){
	var id, time, d, h, m ,s;
	for(var i=0;i<recommend_pidArr.length;i++){
		id = "timeRemaining_" + recommend_pidArr[i] + "_" + i;
		block = document.getElementById(id);
		if(recommend_endTimeArr[i] > 0){
			endtime = recommend_endTimeArr[i];
			d = Math.floor(endtime/(1000*60*60*24));
			h = Math.floor(endtime/(1000*60*60))%24;
			m = Math.floor(endtime/(1000*60))%60;
			s = Math.floor(endtime/(1000))%60;
			block.innerHTML = "ʣ��" + d + "��" + h+"Сʱ "+m+"�� "+s+"��";
			recommend_endTimeArr[i]-=1000;
		}else{
			window.location.reload(true);
		}
	}
}
//����ʱ ******
function initProductCurrentTimer(){
	if(typeof recommend_pidArr != 'undefined'){
		setInterval("currentProductTimer()", 1000);
	}
}

//����ʱ******
function currentRuleTimer(){
	var id, time, d, h, m ,s;
	for(var i=0;i<rule_Arr.length;i++){
		id = "timeRemaining_" + rule_Arr[i];
		block = document.getElementById(id);
		if(rule_endTimeArr[i] > 0){
			endtime = rule_endTimeArr[i];
			d = Math.floor(endtime/(1000*60*60*24));
			h = Math.floor(endtime/(1000*60*60))%24;
			m = Math.floor(endtime/(1000*60))%60;
			s = Math.floor(endtime/(1000))%60;
			block.innerHTML = "ʣ��" + d + "��" + h+"Сʱ "+m+"�� "+s+"��";
			rule_endTimeArr[i]-=1000;
		}else{
			window.location.reload(true);
		}
	}
}
//����ʱ ******
function initRuelCurrentTimer(){
	if(typeof rule_Arr != 'undefined'){
		setInterval("currentRuleTimer()", 1000);
	}
}
//��ƷԤ�� ����
$(function(){
   var offset_top = $(".hot-herald").offset().top;
   var isIE6=$.browser.msie && parseInt($.browser.version)==6;
   var detail_top = $(document).height()-($('.shan-footer').outerHeight()+50)-$(".hot-herald").height();//50��shan-footer��margin-top:50px
   $(window).scroll(function(){
        if(($(window).scrollTop())>detail_top){
            if(isIE6){
                $("html").css('backgroundAttachment','fixed')
                $(".hot-herald").css({ "position": "absolute" ,"z-index":"1000" });
                $(".hot-herald")[0].style.setExpression('top', 'eval((document.documentElement).scrollTop + ' + (detail_top-$(window).scrollTop()) + ') + "px"');
                }
            else{
                $(".hot-herald").css({ "position": "fixed", "top":(detail_top-$(window).scrollTop()) ,"z-index":"1000" });
            }
        }else if($(window).scrollTop()>offset_top){
            if(isIE6){
                $("html").css('backgroundAttachment','fixed')
                $(".hot-herald").css({ "position": "absolute" ,"z-index":"1000" });
                $(".hot-herald")[0].style.setExpression('top', 'eval((document.documentElement).scrollTop) + "px"');
            }else{
                $(".hot-herald").css({ "position": "fixed", "top": "0" ,"z-index":"1000" });
            }
        }else{
            $(".hot-herald").css({ "position": "static", "top": "0","z-index":"0" });
        }
   });
})


$(document).ready(function(){
	LoadProductPrice();
});
//������Ʒ����
var recommend_pidArr = recommend_pidArr || [];
//���������ؼ۸�
function LoadProductPrice(){
	if(usergroupid == -1){
		usergroupid = 100;
		var userinfo = jQuery.cookie("userinfo");
		if(userinfo != null && userinfo.indexOf("|") != -1){
			var userinfoArray = userinfo.split("|");
			usergroupid = userinfoArray[3];
		}
	}
	var params = {};
	params.ids = recommend_pidArr.join(",");
	var prices = ["buyPrice","WMPrice","VIPPrice"];
	params.prices = prices.join(",");
	params.defData = "n";//�Ƿ���ҪĬ������
	params.mid = mid;
	params.usergroupid = usergroupid;
	var apiUrl = priceServer + "/open/productlist.do?" + $.param(params) + "&callback=" + "?";
	$.getJSON(apiUrl, function(data, status) {
		if(!valiLoadProductsData(data)){
			return;
		}
		var pid = "";
		var result = data.result;
		var itemPriceDiv = [];
		var buyPrice = "", WMPrice="",VIPPrice="" ,discount="";
		for(var i = 0;i < result.length;i++){
			itemPriceDiv.push("���޼۸�");
			pid = result[i].id;
			buyPrice = result[i].price.buyPrice == undefined ? "" : result[i].price.buyPrice.priceValue;
			WMPrice = result[i].price.WMPrice == undefined ? "" : result[i].price.WMPrice.priceValue;
			VIPPrice = result[i].price.VIPPrice == undefined ? "" : result[i].price.VIPPrice.priceValue;
			if(VIPPrice && usergroupid == 102){
				WMPrice = VIPPrice;
			}
			if(buyPrice && WMPrice){
				discount = parseFloat(buyPrice)/parseFloat(WMPrice);
				discount = discount * 100;
				discount = Math.ceil(discount);
				discount = (discount/10).toFixed(1);
			}
			if(buyPrice){
				itemPriceDiv = [];
				itemPriceDiv.push("<div class='price fl'>");
				itemPriceDiv.push("<span>��</span><strong>" + parseFloat(buyPrice) + "</strong>");
				itemPriceDiv.push("</div>");
				itemPriceDiv.push("<div class='discount fl'>");
				itemPriceDiv.push("<dl>");
				if(discount && discount < 9.5){
					itemPriceDiv.push("<dt>" + discount + "��</dt>");
					if(parseFloat(WMPrice) > parseFloat(buyPrice)){
						itemPriceDiv.push("<dd>��" + parseFloat(WMPrice) + "</dd>");
					}
				}
				 
				itemPriceDiv.push("</dl>");
				itemPriceDiv.push("</div>");
				itemPriceDiv.push("<div class='clear'></div>");
			}
			if(!result[i].sellable){
				$("#recommend_itemPrice_" + pid + "_" + i).siblings("div .fr").find("#noInfo").show();
				$("#recommenditem-sold-out-" + pid + "_" + i).find("a").after("<span class='recommend_sold-out'></span>");
				$("#recommend_itemPrice_" + pid + "_" + i).siblings("div .fr").find("#goInfo").hide();
				$("#recommenditem-sold-out-" + pid + "_" + i).find("a").removeAttr("href");//ȥ��ͼƬ�ĳ�����
				$("#recommend_itemPrice_" + pid + "_" + i).siblings("div .fr").find("a").removeAttr("href");//ȥ����ť�ĳ����ӣ�ȥ���ƣ������꣩
			}
			$("#recommend_itemPrice_" + pid + "_" + i).html(itemPriceDiv.join(""));
			itemPriceDiv = [];
		}
	});
}

//Ʒ�����������£��۸����
function LoadMainPrice(idName,main_pidArr,main_mapIndex){
	if(usergroupid == -1){
		usergroupid = 100;
		var userinfo = jQuery.cookie("userinfo");
		if(userinfo != null && userinfo.indexOf("|") != -1){
			var userinfoArray = userinfo.split("|");
			usergroupid = userinfoArray[3];
		}
	}
	var params = {};
	params.ids = main_pidArr.join(",");
	var prices = ["buyPrice","WMPrice","VIPPrice"];
	params.prices = prices.join(",");
	params.defData = "n";//�Ƿ���ҪĬ������
	params.mid = mid;
	params.usergroupid = usergroupid;
	var apiUrl = priceServer + "/open/productlist.do?" + $.param(params) + "&callback=" + "?";
	$.getJSON(apiUrl, function(data, status) {
		if(!valiLoadProductsData(data)){
			return;
		}
		var pid = "";
		var result = data.result;
		var itemPriceDiv = [];
		var buyPrice = "", WMPrice="",VIPPrice="" ,discount="";
		for(var i = 0;i < result.length;i++){
			itemPriceDiv.push("���޼۸�");
			pid = result[i].id;
			buyPrice = result[i].price.buyPrice == undefined ? "" : result[i].price.buyPrice.priceValue;
			WMPrice = result[i].price.WMPrice == undefined ? "" : result[i].price.WMPrice.priceValue;
			VIPPrice = result[i].price.VIPPrice == undefined ? "" : result[i].price.VIPPrice.priceValue;
			if(VIPPrice && usergroupid == 102){
				WMPrice = VIPPrice;
			}
			if(buyPrice && WMPrice){
				discount = parseFloat(buyPrice)/parseFloat(WMPrice);
				discount = discount * 100;
				discount = Math.ceil(discount);
				discount = (discount/10).toFixed(1);
			}
			if(buyPrice){
				itemPriceDiv = [];
				itemPriceDiv.push("<div class='shan-pro-price'>");
				itemPriceDiv.push("<div class='price fl'><span>��</span><strong>" + parseFloat(buyPrice) + "</strong></div>");
				if(discount && discount < 9.5){
					itemPriceDiv.push("<div class='discount fl'>" + discount + "��</div>");
					itemPriceDiv.push("<div class='clear'></div>");
				}
				itemPriceDiv.push("</div>");
				if(discount < 9.5){
					if(parseFloat(WMPrice) > parseFloat(buyPrice)){
						itemPriceDiv.push(" <div class='old-price'>��" + parseFloat(WMPrice) + "</div>");
					}
				}
				
			}
			if(!result[i].sellable){
				$("#" + idName + "sold-out-" + pid + "_" + main_mapIndex.getValue(i)).after("<span class='" + idName + "sold-out'></span>");
				$("#" + idName + "sold-out-" + pid + "_" + main_mapIndex.getValue(i)).siblings("dl").find("a").removeAttr("href");//ȥ����ť�ĳ����ӣ�ȥ���ƣ������꣩
			}
			$("#" + idName + "itemPrice_" + pid + "_" + main_mapIndex.getValue(i)).html(itemPriceDiv.join(""));
			itemPriceDiv = [];
		}
	});
}
//У��ص�����data��Ч��
function valiLoadProductsData(data){
	if(data == undefined || data.result == undefined
		|| Object.prototype.toString.apply(data.result) != "[object Array]"
		|| data.result.length == 0)
		return false;
	return true;
}
var brandsalemapIndex = brandsalemapIndex || [];
var brandsale_Arr = brandsale_Arr || [];
var dailyvarietyIndex = dailyvarietyIndex || [];
var dailyvariety_Arr = dailyvariety_Arr || [];
var finallysappedupIndex = finallysappedupIndex || [];
var finallysnappedup_Arr = finallysnappedup_Arr || [];

var brandsaleMapMax = 0;//Ʒ��
var dailyvarietyMapMax = 0;//ÿ��
var finallysappedupMapMax = 0;//���
var brandsalemapIndex = new Object();
brandsalemapIndex.put = function(key,value){
	brandsalemapIndex[key] = value;
}
brandsalemapIndex.getValue = function(key){
	return brandsalemapIndex[key];
}
var dailyvarietyIndex = new Object();
dailyvarietyIndex.put = function(key,value){
	dailyvarietyIndex[key] = value;
}
dailyvarietyIndex.getValue = function(key){
	return dailyvarietyIndex[key];
}
var finallysappedupIndex = new Object();
finallysappedupIndex.put = function(key,value){
	finallysappedupIndex[key] = value;
}
finallysappedupIndex.getValue = function(key){
	return finallysappedupIndex[key];
}

$(function(){
	var isHerald = false;
	var isWenjuan = false;
	var isFWRX = false;
	if($(".herald-adv") && $.trim($(".herald-adv").html()).length <= 0){
		$(".herald-adv").hide();
		isHerald = true;
	}
	if($(".wenjuan") && $.trim($(".wenjuan").html()).length <= 0){
		$(".wenjuan").hide();
		isWenjuan = true;
	}
	if($(".herald-item2") && $.trim($(".herald-item2").html()).length <= 0){
		$(".herald-item2").hide();
		isFWRX = true;
	}
	if(isHerald && isWenjuan && isFWRX){
		$(".hot-herald").hide();
	}
	
})
