$(function(){
	$('[type=checkbox]').prop('checked',false);
	$('#ckAll').click(function(){
		$("input[name^='sub']").prop("checked", this.checked);
			if(!this.checked) {
					 $('#origin_price').text(0);
					 $('#discount_price').text(0);	
			}else{
					var total_price = total_discount = 0;
					$("input[name^='sub']").each(function(i){
						var json = JSON.parse($(this).attr('data-json'));
						var num = $('#'+json.type+'_count_'+json.id).val();
						total_price +=mathMul(parseFloat(json.sell_price),num);
						total_discount += mathMul(parseFloat(json.reduce),num);
					})
					$('#origin_price').text(total_price.toFixed(2));
					 $('#discount_price').text(total_discount);	
					
			}
			prom_ajax();
	})
	$('input[name^=sub]').click(function(){
		var $subs = $("input[name^='sub']");
		$('#ckAll').prop("checked" , $subs.length == $subs.filter(":checked").length ? true :false);
		check_goods(this);
	})
  

})
function check_goods(_this){
		var data = $(_this).attr('data-json');
		var dataObj = JSON.parse(data);
		
		var weight_total = parseInt($('#weight').text());
		var origin_price = parseFloat( $('#origin_price').text());
		var discount_price = parseFloat($('#discount_price').text());
		var promotion_price = parseFloat($('#promotion_price').text());
		var sum_price = parseFloat($('#sum_price').text());
		var new_count = parseInt($('#'+dataObj.type+'_count_'+dataObj.id).val());
		var goods_price = mathMul(parseFloat(dataObj.sell_price),new_count);//选中商品的价格*数量
		var goods_reduce = mathMul(parseFloat(dataObj.reduce),new_count);
		if($(_this).prop('checked')){//
			$('#weight').text(mathAdd(weight_total,mathMul(parseInt(dataObj.weight),new_count),2));
			 $('#origin_price').text(mathAdd(origin_price,goods_price,2));
			 $('#discount_price').text(mathAdd(discount_price,goods_reduce,2));
		}else{
			$('#weight').text(mathSub(weight_total,parseInt(mathMul(dataObj.weight,new_count)),2));
			 $('#origin_price').text(mathSub(origin_price,goods_price,2));
			 $('#discount_price').text(mathSub(discount_price,goods_reduce,2));
		}
		
		//促销规则检测
		
		prom_ajax();
		
	}
/*
 * 计算满足的促销规则
 * @finnal_sum 原商品总价减去会员折扣价
 */
function prom_ajax(){
	var final_sum   = mathSub(parseFloat($('#origin_price').text()),parseFloat($('#discount_price').text()));
	var tmpUrl = prom_url;
		tmpUrl = tmpUrl.replace("@random@",Math.random());
		$.getJSON( tmpUrl ,{final_sum:final_sum},function(content)
		{ 
					if(content.promotion.length > 0)
					{
						$('#cart_prompt .indent').remove();

						for(var i = 0;i < content.promotion.length; i++)
						{
							$('#cart_prompt').append('<p class="indent blue">'+content.promotion[i].plan+'，'+content.promotion[i].info+'</p>');
						}
						$('#cart_prompt').show();
					}
					else
					{
						$('#cart_prompt .indent').remove();
						$('#cart_prompt').hide();
					}
					/*开始更新数据 (2)*/
					content.proReduce = content.proReduce.replace(/,/,'');
					//促销活动
					$('#promotion_price').html(content.proReduce);

					//最终金额
					$('#sum_price').html(mathSub(mathSub(parseFloat($('#origin_price').text()),parseFloat($('#discount_price').text())),parseFloat($('#promotion_price').text()),2));

		});
}
//购物车数量改动计算
function cartCount(obj,oldCount)
{
	var type = obj.type;
	var countInput    = $('#'+type+'_count_'+obj.id);
	var countInputVal = parseInt(countInput.val());
	var checkInput   = countInput.parent('div').parent('td').parent('tr').find('[name^=sub]');
	//商品数量大于1件
	if(isNaN(countInputVal) || (countInputVal <= 0))
	{
		alert('购买的数量必须大于1件');
		countInput.val(1);
		cartCount(obj,oldCount);
	}

	//商品数量大于库存量
	else if(countInputVal > parseInt(obj.store_nums))
	{
		alert('购买的数量不能大于此商品的库存量');
		countInput.val(parseInt(obj.store_nums));
		cartCount(obj,oldCount);
	}
	else
	{
		//修改按钮状态
		countInput.attr('disabled',true);
		$('.btn_pay').val('wait');

		//获取之前的购买数量
		if(oldCount == undefined)
		{
			oldCount = countInput.data('oldCount') ? parseInt(countInput.data('oldCount')) : parseInt(obj.count);
		}

		//修改的购买数量
		var changeNum = parseInt(countInput.val()) - oldCount;

		//商品数量没有改动
		if(changeNum == 0)
		{
			//修改按钮状态
			countInput.attr('disabled',false);
			$('.btn_pay').val('ok');
		}

		//更新购物车中此商品的数量
		$.getJSON(cart_url,{"type":type,"goods_id":obj.id,"goods_num":changeNum,"random":Math.random()},function(content){
			if(content.isError == true)
			{
				alert(content.message);
				var countInput = $('#'+type+'_count_'+obj.id);

				//上次数量回填
				if(changeNum < 0)
				{
					countInput.val(parseInt(countInput.val() - changeNum));
				}
				else
				{
					countInput.val(parseInt(countInput.val() + changeNum));
				}

				//修改按钮状态
				countInput.attr('disabled',false);
				$('.btn_pay').val('ok');
			}
			else
			{
				var countInput = $('#'+type+'_count_'+obj.id);

				//缓存旧的购买数量
				countInput.data('oldCount',parseInt(countInput.val()));
				$('#'+type+'_sum_'+obj.id).text(mathMul(mathSub(parseFloat(obj.sell_price),parseFloat(obj.reduce)),parseInt(countInput.val())));
				if(checkInput.prop('checked')){//如果当前商品选中
					var weight_total = parseInt($('#weight').text());
					var origin_price = parseFloat( $('#origin_price').text());
					var discount_price = parseFloat($('#discount_price').text());
					var new_origin_price = mathAdd(origin_price,mathMul(parseFloat(obj.sell_price),changeNum),2);
					var new_discount_price = mathAdd(discount_price,mathMul(parseFloat(obj.reduce),changeNum));
					$('#weight').text(mathAdd(weight_total,mathMul(parseInt(obj.weight),changeNum),2));
					$('#origin_price').text(new_origin_price);
					$('#discount_price').text(new_discount_price);
					prom_ajax(mathSub(new_origin_price,new_discount_price,2));
				}
				//check_goods();
				//修改按钮状态
				countInput.attr('disabled',false);
				$('.btn_pay').val('ok');
			}
		});
	}
}



//增加商品数量
function cart_increase(obj)
{
	//库存超量检查
	var type = obj.type;
	var countInput = $('#'+type+'_count_'+obj.id);
	var oldCount   = parseInt(countInput.val());
	
	if(parseInt(countInput.val()) + 1 > parseInt(obj.store_nums))
	{
		alert('购买的数量大于此商品的库存量');
	}
	else
	{
		if(countInput.attr('disabled') == true)
		{
			return false;
		}
		else
		{
			countInput.attr('disabled',true);
		}
		countInput.val(parseInt(countInput.val()) + 1);
		cartCount(obj,oldCount);
	}
}

//减少商品数量
function cart_reduce(obj)
{
	//库存超量检查
	var type = obj.type;
	var countInput = $('#'+type+'_count_'+obj.id);
	var oldCount   = parseInt(countInput.val());
	if(parseInt(countInput.val()) - 1 <= 0)
	{
		return false;
	}
	else
	{
		if(countInput.attr('disabled') == true)
		{
			return false;
		}
		else
		{
			countInput.attr('disabled',true);
		}
		countInput.val(parseInt(countInput.val()) - 1);
		cartCount(obj,oldCount);
	}
}