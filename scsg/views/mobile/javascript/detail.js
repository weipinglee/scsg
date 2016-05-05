var myScroll;
var BicPicScroll;
var showBig = false;
var goods_id = id;
var pnum = photo_num;
function loaded() {

	document.getElementById("scroller").style.width=200*pnum+'px';
	document.getElementById("scroller1").style.width=300*pnum+'px';
    myScroll = new iScroll('wrapper', {
        snap: true,
        momentum: false,
        hScrollbar: false,
        onScrollEnd: function () {
          
        }
    });
}
//count int 图片数量
function loadedBig(count){
    BicPicScroll = new iScroll('wrapper1', {
        snap: true,
        momentum: false,
		zoom:true,
        hScrollbar: false,
        onScrollEnd: function () {
            num = this.currPageX+1;
            if(num>pnum){
                num = num%pnum;
            }
            $('#indicatorBig').html(num);
            if(this.currPageX==0){
                $('#prev_big').hide();
                $('#next_big').show();
            }else if(this.currPageX==pnum-1){
                $('#next_big').hide();
                $('#prev_big').show();
            }else{
                $('#next_big').show();
                $('#prev_big').show();
            }
        }
    });

}
document.addEventListener('DOMContentLoaded', loaded, false);
window.onload=function(){
	setInterval(function(){ 
      if(myScroll.currPageX==pnum-1){
                myScroll.scrollToPage(0, 0);
            }else{
                myScroll.scrollToPage('next', 0);}
        },5000);
}



function next_pic(){
    if(myScroll.currPageX==pnum-1){
        myScroll.scrollToPage(0, 0);
    }else{
        myScroll.scrollToPage('next', 0);}
}
function prev_pic(){
    if(myScroll.currPageX==0){
        myScroll.scrollToPage(pnum-1, 0);
    }else{
        myScroll.scrollToPage('prev', 0);}
}


//share

function toshare(){
	$(".am-share").addClass("am-modal-active");	
	if($(".sharebg").length>0){
		$(".sharebg").addClass("sharebg-active");
	}else{
		$("body").append('<div class="sharebg"></div>');
		$(".sharebg").addClass("sharebg-active");
	}
	$(".sharebg-active,.share_btn").click(function(){
		$(".am-share").removeClass("am-modal-active");	
		setTimeout(function(){
			$(".sharebg-active").removeClass("sharebg-active");	
			$(".sharebg").remove();	
		},300);
	})
}	
	
function viewBigPicOn(){
	$('article').hide();
	$('footer').hide();
	$('header').hide();
	$('.breadcrumb').hide();
	$('.detail_nav').hide();
	$('.viewbig').show();
	$('.viewbig img').each(function (){
		img=$(this).attr('data-src');
		$(this).attr('src',img);
	});
	loadedBig($('.viewbig img').length);
	showBig=true;
}
function viewBigPicOff(){
	$('article').show();
	$('footer').show();
	$('header').show();
	$('.breadcrumb').show();
	$('.detail_nav').show();
	$('.viewbig').hide();
	$('.layout_nav_path').hide();
	loaded();
	showBig=false;
}

//产品详情页首页导航切换
function show_det1(_this){
		$('.lin').removeClass('lin');
		$(_this).parent('li').addClass('lin');
		$('#det1').removeClass('hidden');
		$('#det_tuijian').removeClass('hidden');
		$('#det2').addClass('hidden');
		$('#comments').addClass('hidden');
}
function show_det2(_this){
	$('.lin').removeClass('lin');
	$(_this).parent('li').addClass('lin');
	$('#det2').removeClass('hidden');
	$('#det_tuijian').removeClass('hidden');
	$('#det1').addClass('hidden');
	$('#comments').addClass('hidden');
	
		$('.desbox').attr({'style':'outline: 0px; padding: 12px 0px; margin: 0px auto 0px 0px; font-size: 14px;width:100%; overflow: hidden; color: rgb(51, 51, 51); line-height: normal; white-space: normal;'});
	$(".desbox img").attr({"style":"width:100%;"});
	$(".ull").attr({"style":"width:100%;"});
	 $("#rightProdDescNavDiv").height(1000);

	$(".ke-zeroborder").css({   "background-size": "100%" , "height": "150px", "width": "100%"});
	$("#description tbody").remove();
	 
	$(".spjs_img img").attr({"style":"width:100%;"});
	$(".spjs_img p").attr({"style":"width:100%;"});  
	
}
function show_comments_list(_this){
	$('.lin').removeClass('lin');
	$(_this).parent('li').addClass('lin');
	$('#det2').addClass('hidden');
	$('#det1').addClass('hidden');
	$('#det_tuijian').addClass('hidden');
	$('#comments').removeClass('hidden');
	if($('#comments .content').text()==''){
	comment_ajax_list('all',1);
	}
}
/**
 * 获取评论数据(详情首页)
 * @type int 评价等级 
 */
function comment_ajax(type)
{
	var url = comments_url;
	$.getJSON(url,{type:type},function(json){
		for(var item in json.comment_list)
		{
			var commentHtml = template.render('commentRowTemplate',json.comment_list[item]);
			$('#commentBox').prepend(commentHtml);
		}
	});
}
/**
 * 显示所有评论
 * @param {Object} type
 * @param {Object} statics
 */
function show_comments(){
	$('a[name=pingjia').trigger('click');
}
/**
 * 获取评论数据（详情页评价）
 * @param type 评论类型
 * @param statics bool 是否加载统计数据
 */
function comment_ajax_list(type,statics){
	var url = comments_url;
	var page = parseInt($('input[name=comment_page]').val());
	$.getJSON(url,{type:type,page:page},function(json)
	{//window.alt(JSON.stringify(json));
	if(json==0)return false;
		json.point_grade.comment_total=json.comment_total;
		if(statics){
			var commentHtml = template.render('comment_statics',json.point_grade);
			$('#comments').prepend(commentHtml);
			$('.anniu div').on('click',function(){
				$('#comments .content ul').remove();
				$('#comments .content hr').remove();
				$('#comments .current').removeClass('current').addClass('other');
				$(this).removeClass('other').addClass('current');
				 $('input[name=comment_page]').val('1');
				comment_ajax_list($(this).attr('type'));
			})
		}
		for(var i in json.comment_list)
		{
			var commentHtml = template.render('comment_list',json.comment_list[i]);
			$('#comments .content').append(commentHtml);
		}
		$('input[name=comment_page]').val(page+1);
		
	});
}



$(function(){
	comment_ajax();//加载评论数据
	
	window.onscroll = function(){
		if ($('#comments').css('display')!='none' && $(document).scrollTop() >= $(document).height() - $(window).height()){
		 	var type = $('.current').attr('type');
			comment_ajax_list(type);
		 }
	}

})


/**
 * 规格的选择
 * @param _self 规格本身
 * @price int 1:不改变价格
 */
function sele_spec(_self,price)
{
	var specObj = $.parseJSON($(_self).find('a').attr('value'));
	var not_chg_price = price;
	//已经为选中状态时
	if($(_self).attr('class') == 'current')
	{
		$(_self).removeClass('current');
	}
	else
	{
		//清除同行中其余规格选中状态
		$('#specList'+specObj.id).find('span.current').removeClass('current');
		$(_self).addClass('current');
	}

	//检查规格是否选择符合标准
	if(checkSpecSelected())
	{
		//整理规格值
		var specArray = [];
		$('[name="specCols"]').each(function(){
			specArray.push($(this).find('span.current a').attr('value'));
		});
		var specJSON = '['+specArray.join(",")+']';

		//获取货品数据并进行渲染
		$.getJSON(product_url,{"goods_id":goods_id,"specJSON":specJSON,"random":Math.random},function(json){
			
			if(json.flag == 'success')
			{
				var goods_data = json.data;
				var price = goods_data.group_price ? goods_data.group_price : goods_data.sell_price;
				if(!not_chg_price)
					$('.tc_cont .price').text('￥'+price);
			
				//普通货品数据渲染
				$('#data_storeNums').text(goods_data.store_nums);
				$('#product_id').val(goods_data.id);

				//库存监测
				checkStoreNums();
			}
			else
			{
				alert(json.message);
				closeBuy();
			}
		});
	}
}

/**
 * 购物车数量的加减
 * @param code 增加或者减少购买的商品数量
 */
function modified(code)
{
	var buyNums = parseInt($.trim($('#buyNums').val()));
	var min_num = $('[name=min_num]').val();
	var max_num = $('[name=max_num]').val();
	switch(code)
	{
		case 1:
		{
			buyNums++;
		}
		break;

		case -1:
		{
			buyNums--;
		}
		break;
	}
	if(min_num && buyNums <min_num)return false;
	if(max_num!=0 && buyNums>max_num)return false;
	$('#buyNums').val(buyNums);
	checkBuyNums();
}
/**
 * 监测库存操作
 */
function checkStoreNums()
{
	var storeNums = parseInt($.trim($('#data_storeNums').text()));
	if(storeNums > 0)
	{
		openBuy();
	}
	else
	{
		closeBuy();
	}
}
//禁止购买
function closeBuy()
{
	if($('#buyNowButton').length > 0)
	{
		$('#buyNowButton').attr('disabled','disabled');
		$('#buyNowButton').addClass('disabled');
	}
}

//开放购买
function openBuy()
{
	if($('#buyNowButton').length > 0)
	{
		$('#buyNowButton').removeAttr('disabled');
		$('#buyNowButton').removeClass('disabled');
	}

}
/**
 * 检查规格选择是否符合标准
 * @return boolen
 */
function checkSpecSelected()
{
	if($('[name="specCols"]').length === $('[name="specCols"] .current').length)
	{
		return true;
	}
	return false;
}

//检查购买数量是否合法
function checkBuyNums()
{
	//购买数量小于0
	var buyNums = parseInt($.trim($('#buyNums').val()));
	
	if(buyNums <= 0)
	{
		$('#buyNums').val(1);
		return;
	}

	//购买数量大于库存
	var storeNums = parseInt($.trim($('#data_storeNums').text()));
	if(buyNums >= storeNums)
	{
		$('#buyNums').val(storeNums);
		return;
	}
}


//立即购买按钮
function buy_now()
{
	//对规格的检查
	if(!checkSpecSelected())
	{
		window.realAlert('请选择商品的规格');
		return;
	}

	//设置必要参数
	var buyNums  = parseFloat($.trim($('#buyNums').val()));
	var id = goods_id;
	var type = 'goods';

	if($('#product_id').val()!=0)
	{
		id = $('#product_id').val();
		type = 'product';
	}
	var promo_type=$('input[name=promo_name]').val();
	var active_id = $('input[name=active_id]').val();
	//var url = '{url:/simple/cart2/id/@id@/num/@buyNums@/type/@type@/promo/$promo/active_id/$active_id}';
	//url = url.replace('@id@',id).replace('@buyNums@',buyNums).replace('@type@',type);
	
	if(promo_type!='presell'){
		var url = direct_buy_url;
		url = url.replace('@id@',id).replace('@buyNums@',buyNums).replace('@type@',type);
		if(promo_type && active_id){
			url += '/promo/'+promo_type+'/active_id/'+active_id; 
		}
	}else{
		var url = presell_buy_url;
		url = url.replace('@id@',id).replace('@buyNums@',buyNums).replace('@type@',type);
		if( active_id){
			url +='/active_id/'+active_id; 
		}
	}
	

	//页面跳转
	window.location.href = url;
}
//商品加入购物车
function joinCart()
{
	if(!checkSpecSelected())
	{
		window.realAlert('请先选择商品的规格');
		return;
	}

	var buyNums   = parseInt($.trim($('#buyNums').val()));
	var productId = $('#product_id').val();
	var type      = productId ? 'product' : 'goods';
	var goods_id  = (type == 'product') ? productId : id;

	$.getJSON(join_cart_url,{"goods_id":goods_id,"type":type,"goods_num":buyNums,"random":Math.random},function(content){
		
		if(content.isError == false)
		{
			window.location.href = cart_url;
		}
		else
		{
			alert(content.message);
		}
	});
}
//加入购物车弹出框
function toshop(callback){
	$("#tc_shop").removeClass("tc_shop").addClass("tc-modal-active");     

	if($(".sharebg").length>0){
		$(".sharebg").addClass("sharebg-active");
	}else{
		//给整个body内增加div层
		$("body").append('<div class="sharebg"></div>');
		$(".sharebg").addClass("sharebg-active");
	}
	$(".sharebg-active,.share_btn").click(function(){
		$("#tc_shop").removeClass("tc-modal-active").addClass("tc_shop");	
		setTimeout(function(){
			$(".sharebg-active").removeClass("sharebg-active");	
			$(".sharebg").remove();	
		},300);
	})
	$('input[name=buyNowButton]').on('click',callback);
	
}	