//artTemplate模板 {name:组件名字,area_id:选中的地区ID,data:地区的对象}
var areaTemplate = '<%for(var index in data){%>'+'<%var item = data[index]%>'+'  <li><a href="javascript:void(0)" data-value="<%=item.area_id%>"><%=item.area_name%></a></li>'+'<%}%>';

var provinceHtml = 	'<div class="text"><span name="pro_show"></span>  <span name="city_show"></span>  <span name="area_show"></span><div><input type="hidden" name="area_id" /></div><b></b></div> <span style="margin-left:5px;" name="fee_box"></span>'                  
					

					+'<div id="areaShow" class="content" style="height:600px;"><div data-widget="tabs" class="m JD-stock" id="JD-stock">'

								+'<div class="mt">'
								+'    <ul class="tab">'
								+'        <li data-index="0" data-widget="tab-item" class="curr"><a href="#none" class="hover"><em>请选择</em><i></i></a></li>'
								+'        <li data-index="1" data-widget="tab-item" ><a href="#none" class=""><em>请选择</em><i></i></a></li>'
								+'        <li data-index="2" data-widget="tab-item" ><a href="#none" class=""><em>请选择</em><i></i></a></li>'
								+'    </ul>'
								+'    <div class="stock-line"></div>'
								+'</div>'
								+'<div class="mc" data-area="0" data-widget="tab-content" id="stock_province_item">'
								+'    <ul class="area-list" name="province" child="city,area">'
								+'    </ul>'
								+'</div>'
								+'<div class="mc" data-area="1" data-widget="tab-content" id="stock_city_item">'
								+'	 <ul class="area-list" name="city" child="area">'
								
								+'    </ul>'
								+'</div>'
								+'<div class="mc" data-area="2" data-widget="tab-content" id="stock_area_item">'
								+'	 <ul class="area-list" name="area" >'
								+'    </ul>'
								+'</div>'
								+'</div></div>';
/**
 * 生成地域js联动下拉框
 * @param name
 * @param parent_id
 * @param select_id
 */
function createAreaSelect(name,parent_id,select_id)
{
	//生成地区
	$.getJSON(area_url,{"aid":parent_id,"random":Math.random()},function(json)
	{
		$('[name="'+name+'"]').html(template.render('areaTemplate',{"select_id":select_id,"data":json}));
		$('[name='+name+']').find('li').on('click',function(){
			var childName = $(this).parent('ul').attr('child');
			var area_id = $(this).find('a').attr('data-value');
			var area_text = $(this).find('a').text();
			
			switch(name){
				case 'province' : {
					$('[name=pro_show]').text(area_text);
					$('[name=city_show]').text('');
					$('[name=area_show]').text('');
				}
				break;
				case 'city' : {
					$('[name=city_show]').text(area_text);
					$('[name=area_show]').text('');
				}
				break;
				case 'area' : {
					$('[name=area_show]').text(area_text);
					$('input[name=area_id]').val(area_id);
				}
				break;
			}
			
			
			get_delivery_fee(name);
			
			if(!childName)//最后一级
			{
				
				return;
			}
			var childArray = childName.split(',');
			for(var index in childArray)
			{
				$('[name="'+childArray[index]+'"]').empty();
			}
			
			//生成js联动菜单
			createAreaSelect(childArray[0],area_id);
			var tab_index = $('[name='+childArray[0]+']').parent().attr('data-area');
			$('#areaShow ul.tab li[data-index='+tab_index+']').trigger('click');
		})	
	});
}
//获取运费
function get_delivery_fee(name){
	if(name=='province' || name=='city'){
		$('span[name=fee_box').text('运费：');
		return;
	}
	area_id = $('input[name=area_id]').val();
	var buyNums = $('#buyNums').val();
	var productId = $('#product_id').val();
	$.getJSON(delivery_fee_url,{"area":area_id,"goodsId":goods_id,"productId":productId,"num":buyNums},function(content){
		var delivery_fee = 99999;
		for(var i in content){
			if(content[i].price<delivery_fee)
			delivery_fee = content[i].price;
		}
		if(delivery_fee!=99999){
			$('span[name=fee_box').text('运费：'+delivery_fee);
		}
		
	})
}
//运费初始化
function delivery_init(){
	$('[name=pro_show]').text('山西省');
	$('[name=city_show]').text('阳泉市');
	$('[name=area_show]').text('城区');
	$('input[name=area_id]').val('140302');
	get_delivery_fee();
}
$(function(){
	$('#store-selector').append(provinceHtml);
	$('#store-selector .close').click(function(){
		$('#product_myCart').css("z-index","1")
		$('#store-selector').removeClass('hover');
	})
	template.compile("areaTemplate",areaTemplate);
	createAreaSelect('province',0,'');
	
	//切换面板
	$('#areaShow ul.tab li').on('click',function(){
		$('#areaShow .curr').removeClass('curr');
		$(this).addClass('curr');
		var index = $(this).attr('data-index');
		$('#areaShow .mc').addClass('hide');
		$('#areaShow .mc[data-area='+index+']').removeClass('hide');
	})
	//
	$('#store-selector .text').hover(
				function(){
					$('#product_myCart').css("z-index","-1")
					$('#store-selector').addClass('hover');
				},
				function(){
					$('#product_myCart').css("z-index","1")
					$('#store-selector').removeClass('hover');
				}
	)
	$('#areaShow').hover(
			function(){
				$('#product_myCart').css("z-index","-1");
				$('#store-selector').addClass('hover');
			},
			function(){
				$('#product_myCart').css("z-index","1");
				$('#store-selector').removeClass('hover');
			}
	)
	delivery_init();
})
