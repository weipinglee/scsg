function Tuan_spec_show(){}
Tuan_spec_show.prototype = new Spec_show(); 
Tuan_spec_show.prototype.constructor = Tuan_spec_show; 

var tuan_spec_show = new Tuan_spec_show(); 

tuan_spec_show.product_id = product_id;
tuan_spec_show.min_num = min_num;
tuan_spec_show.max_num = max_num;
//选择规格
tuan_spec_show.sele_spec = function(_self)
{
	var new_product = this.new_product;
	var specObj = $.parseJSON($(_self).attr('value'));
	if($(_self).hasClass('not-allowed'))return false;
	//已经为选中状态时
	if($(_self).attr('class') == 'current')
	{
		$(_self).removeClass('current');
	}
	else
	{
		//清除同行中其余规格选中状态
		$('#specList'+specObj.id).find('a.current').removeClass('current');

		$(_self).addClass('current');
	}
	this.check_spec_allowed();
	if(this.product_id==0 && this.checkSpecSelected()){

			//整理规格值
			var specArray = [];
			$('[name="specCols"]').each(function(){
				specArray.push($(this).find('a.current').attr('value'));
			});
			var specJSON = specArray.join("|");
			for(var i in new_product){
			
				if(new_product[i]['spec_array']==specJSON){
					$('#data_storeNums').text(new_product[i].store_nums);
					//this.checkStoreNums();
				}
			}
			specJSON = specArray.join(",");
			var specJSON = '['+specArray.join(",")+']';

		//获取货品数据并进行渲染
		$.getJSON(this.get_product_url,{"goods_id":this.goods_id,"specJSON":specJSON,"random":Math.random},function(json){
			if(json.flag == 'success')
			{
				var priceHtml = template.render('priceTemplate',json.data);
				$('#price_panel').html(priceHtml);
				//普通货品数据渲染
				$('#product_id').val(json.data.id);
				get_delivery_fee();

			}
			
		});
		
	}

}
tuan_spec_show.buy_now = function()
{
	//对规格的检查
	if(!this.checkSpecSelected())
	{
		tips('请选择商品的规格');
		return;
	}
	var min_num = this.min_num;
	var max_num = this.max_num;
	//设置必要参数
	var buyNums  = parseFloat($.trim($('#buyNums').val()));
	if(min_num!=0 && buyNums<min_num || max_num!=0&&buyNums>max_num ){
		tips('商品数量不能超过'+max_num+',不能少于'+min_num);
		return;
	}
	
	var id = this.goods_id;
	var type = 'goods';

	if($('#product_id').val()!=0)
	{
		id = $('#product_id').val();
		type = 'product';
	}
	var promo_type='groupon';
	var active_id = $('input[name=active_id]').val();
	var url = this.buy_now_url;
	url = url.replace('@id@',id).replace('@buyNums@',buyNums).replace('@type@',type);
	
		url += '/promo/'+promo_type+'/active_id/'+active_id; 
	

	//页面跳转
	window.location.href = url;
}
/**
 * 购物车数量的加减
 * @param code 增加或者减少购买的商品数量
 */
tuan_spec_show.modified = function(code)
{
	var max_num = this.max_num;
	var min_num = this.min_num;
	var buyNums = parseInt($.trim($('#buyNums').val()));
	switch(code)
	{
		case 1:
		{
			if(buyNums>=max_num && max_num!=0){
				$('#J_StockTips').css('display','inline').text('(购买量不能超过'+max_num+'件)').fadeOut(3000);
			}
			if(max_num==0 || buyNums<max_num){
				buyNums++;
			}
			
		}
		break;

		case -1:
		{
			if(min_num>=buyNums && min_num!=0){
				$('#J_StockTips').css('display','inline').text('(购买量不能少于'+min_num+'件)').fadeOut(3000);
			}
			if(min_num<buyNums)
				buyNums--;
			
		}
		break;
	}

	$('#buyNums').val(buyNums);
	this.checkBuyNums();
}
tuan_spec_show.init(product);
