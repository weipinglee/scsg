//购物车数量改动计算
function cartCount(type,obj,oldCount)
{
	var countInput    = $('#'+type+'_count_'+obj.id);
	var countInputVal = parseInt(countInput.val());

	//商品数量大于1件
	if(isNaN(countInputVal) || (countInputVal <= 0))
	{
		alert('购买的数量必须大于1件');
		countInput.val(1);
		cartCount(type,obj,oldCount);
	}

	//商品数量小于库存量
	else if(countInputVal > parseInt(obj.store_nums))
	{
		alert('购买的数量不能大于此商品的库存量');
		countInput.val(parseInt(obj.store_nums));
		cartCount(type,obj,oldCount);
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
			return '';
		}

		//更新购物车中此商品的数量
		$.getJSON(join_cart_url,{"type":type,"goods_id":obj.id,"goods_num":changeNum,"random":Math.random()},function(content){
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

				/*变动的价格可能为负数(减少购买量)*/
				var smallSumC   = (mathSub(parseFloat(obj.sell_price),parseFloat(obj.reduce))) * changeNum; //价格变动
				var weightC     = mathMul(parseFloat(obj.weight),changeNum);       //重量变动
				var originC     = mathMul(parseFloat(obj.sell_price),changeNum);   //原始价格变动
				var discountC   = mathMul(parseFloat(obj.reduce),changeNum);       //优惠变动

				/*开始更新数据(1)*/

				//商品小结计算
				var smallSum    = $('#'+type+'_sum_'+obj.id);
				smallSum.html(mathAdd(parseFloat(smallSum.text()),smallSumC));

				//最终重量
				$('#weight').html(mathAdd(parseFloat($('#weight').text()),weightC));

				//原始金额
				$('#origin_price').html(mathAdd(parseFloat($('#origin_price').text()),originC));

				//优惠价
				$('#discount_price').html(mathAdd(parseFloat($('#discount_price').text()),discountC));
			
				//促销规则检测
				var final_sum   = mathSub(parseFloat($('#origin_price').text()),parseFloat($('#discount_price').text()));
				var tmpUrl = promotion_url;
				tmpUrl = tmpUrl.replace("@random@",Math.random());
				$.getJSON( tmpUrl ,{final_sum:final_sum},function(content){
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

					//促销活动
					$('#promotion_price').html(content.proReduce);
					
					//总优惠金额计算
					$('#youhui_price').html(mathAdd(parseFloat($('#discount_price').text()),parseFloat($('#promotion_price').text())));
					//最终金额
					$('#sum_price').html(mathSub(mathSub(parseFloat($('#origin_price').text()),parseFloat($('#discount_price').text())),parseFloat($('#promotion_price').text())));

					//修改按钮状态
					countInput.attr('disabled',false);
					$('.btn_pay').val('ok');
				});
			}
		});
	}
}

//增加商品数量
function cart_increase(type,obj)
{
	//库存超量检查
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
		cartCount(type,obj,oldCount);
	}
}

//减少商品数量
function cart_reduce(type,obj)
{
	//库存超量检查
	var countInput = $('#'+type+'_count_'+obj.id);
	var oldCount   = parseInt(countInput.val());
	if(parseInt(countInput.val()) - 1 <= 0)
	{
		alert('购买的数量必须大于1件');
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
		cartCount(type,obj,oldCount);
	}
}
//购物车商品删除
function cart_del_many(){
	var obj = $('#list .checked');
	var str = '';
	for(var i=0;i<obj.length;i++){
		str += obj.eq(i).attr('typeId')+ '|';
	}
	
	$.ajax({
		type:'post',
		async:true,
		data:{str:str},
		//dataType:'json',
		url:del_cart_url,
		beforeSend:function(){
			
		},
		success:function(data){
			if(data==1){
				window.reload();
			}
		},
		error:function(){
			
		},
		complete:function(){
			
		},
		timeout:1000,
	})
	
}
//[ajax]加入购物车
function joinCart_ajax(id,url,type)
{
	$.getJSON(url,{"goods_id":id,"type":type,"random":Math.random()},function(content){
		if(content.isError == false)
		{
			alt(content.message);
		}
		else
		{
			alt(content.message);
		}
	});
}