function Spec_show(){
	this.product = {};
	this.new_product =null;
	this.buy_now_url = buy_now_url;
	this.join_cart_url = join_cart_url;
	this.goods_id    = goods_id;
	this.show_cart_url = show_cart_url;
	this.get_product_url = get_product_url;
	//
	this.init = function(product){
		this.product = product;
		this.new_product = $.extend({},this.product);
		for(var i in this.product){
			this.product[i].spec_array = this.product[i].spec_array.substring(1,this.product[i].spec_array.length-1);
			this.product[i].spec_array = this.product[i].spec_array.replace(/},/g,'}|');
		}
		this.check_spec_allowed();
	}
	//规格转义
	this.spec_escape = function(spec){
		var spec_str = '';
		spec_str = spec.replace(/\"/g,'\\"');
		spec_str = spec_str.replace(/:/g,'\\:');
		spec_str = spec_str.replace(/,/g,'\\,');
		spec_str = spec_str.replace(/{/g,'\\{');
		spec_str = spec_str.replace(/}/g,'\\}');
		spec_str = spec_str.replace(/\//g,'\\/');
		spec_str = spec_str.replace(/\./g,'\\.');
		spec_str = spec_str.replace(/\*/g,'\\*');
		spec_str = spec_str.replace(/\+/g,'\\+');
		return spec_str;
	}
		/**
	 * 检查规格是否可选
	 * @param {Object} _self
	 */
	this.check_spec_allowed = function (){
		var _this= this;
		var product = this.product;
		var curr_spec = [];
		$('a.current').each(function(i){
			curr_spec.push($(this).attr('value'));
		})
		
		$('[name=specCols] a').not('[class~=current]').removeClass('allowed').addClass('not-allowed');//先把所有 未选中元素设为不可选
	
		var spec_show_now = [];//下一步要显示的规格数组
		for(var i in product){
			var pro_arr = product[i].spec_array.split('|');
			var is = 1;
			$.each(curr_spec,function(j,v){
				if(pro_arr.indexOf(v)=='-1')is=0;
			})
			if(product[i].store_nums>0 && is==1){
				$.each(pro_arr,function(index,value){
					spec_show_now.push(value);
				})
				
			}
		
		}
		
		$.each(curr_spec,function(j,v){
			var spec_tem = curr_spec.splice(j,1);
			for(var i in product){
				var pro_arr = product[i].spec_array.split('|');
				var is = 1;
				$.each(curr_spec,function(j,v){
					if(pro_arr.indexOf(v)=='-1')is=0;
				})
				if(product[i].store_nums>0 && is==1){
					$.each(pro_arr,function(index,value){
						spec_show_now.push(pro_arr[j]);
					})
				}
			}
			curr_spec.unshift(spec_tem[0]);
		})
	
		
		$.each(spec_show_now,function(i,v){
			$('[name=specCols] a[value='+_this.spec_escape(v)+']').removeClass('not-allowed').addClass('allowed');
		})
	
	}
	/**
	 * 规格的选择
	 * @param _self 规格本身
	 */
	this.sele_spec = function(_self)
	{
		var new_product = this.new_product;
		var specObj = $.parseJSON($(_self).attr('value'));
		if($(_self).hasClass('not-allowed'))return false;
		//已经为选中状态时
		if($(_self).hasClass('current'))
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
		//检查规格是否选择符合标准
		
		if(this.checkSpecSelected())
		{
			//整理规格值
			var specArray = [];
			$('[name="specCols"]').each(function(){
				specArray.push($(this).find('a.current').attr('value'));
			});
			var specJSON = specArray.join("|");
			for(var i in new_product){
			
				if(new_product[i]['spec_array']==specJSON){
					$('#data_storeNums').text(new_product[i].store_nums);
					this.checkStoreNums();
					
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
	
	/**
	 * 监测库存操作
	 */
	this.checkStoreNums = function ()
	{
		var storeNums = parseInt($.trim($('#data_storeNums').text()));
		if(storeNums > 0)
		{
			this.openBuy();
		}
		else
		{
			this.closeBuy();
		}
	}
	
	/**
	 * 检查规格选择是否符合标准
	 * @return boolen
	 */
	this.checkSpecSelected = function ()
	{
		if($('[name="specCols"]').length === $('[name="specCols"] .current').length)
		{
			return true;
		}
		return false;
	}
	
	//检查购买数量是否合法
	this.checkBuyNums = function ()
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
	/**
	 * 购物车数量的加减
	 * @param code 增加或者减少购买的商品数量
	 */
	this.modified = function (code)
	{
		var buyNums = parseInt($.trim($('#buyNums').val()));
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
	
		$('#buyNums').val(buyNums);
		this.checkBuyNums();
	}
	//禁止购买
		this.closeBuy = function ()
		{
			if($('#buyNowButton').length > 0)
			{
				$('#buyNowButton').attr('disabled','disabled');
				$('#buyNowButton').addClass('disabled');
			}
		
			if($('#joinCarButton').length > 0)
			{
				$('#joinCarButton').attr('disabled','disabled');
				$('#joinCarButton').addClass('disabled');
			}
		}
		
		//开放购买
		this.openBuy = function ()
		{
			if($('#buyNowButton').length > 0)
			{
				$('#buyNowButton').removeAttr('disabled');
				$('#buyNowButton').removeClass('disabled');
			}
		
			if($('#joinCarButton').length > 0)
			{
				$('#joinCarButton').removeAttr('disabled');
				$('#joinCarButton').removeClass('disabled');
			}
		}
		//立即购买按钮
	this.buy_now = function ()
	{
		//对规格的检查
		if(!this.checkSpecSelected())
		{
			tips('请选择商品的规格');
			return;
		}
	
		//设置必要参数
		var buyNums  = parseFloat($.trim($('#buyNums').val()));
		var id = this.goods_id;
		var type = 'goods';
	
		if($('#product_id').val())
		{
			id = $('#product_id').val();
			type = 'product';
		}
		var url = this.buy_now_url;
		url = url.replace('@id@',id).replace('@buyNums@',buyNums).replace('@type@',type);
	
		//页面跳转
		window.location.href = url;
	}
	
	//商品加入购物车
	this.joinCart = function ()
	{
		var _this=this;
		if(!this.checkSpecSelected())
		{
			tips('请先选择商品的规格');
			return;
		}
	
		var buyNums   = parseInt($.trim($('#buyNums').val()));
		var price     = parseFloat($.trim($('#real_price').text()));
		var productId = $('#product_id').val();
		var type      = productId ? 'product' : 'goods';
		var goods_id  = (type == 'product') ? productId :  this.goods_id;
	
		$.getJSON(this.join_cart_url,{"goods_id":goods_id,"type":type,"goods_num":buyNums,"random":Math.random},function(content){
			
			if(content.isError == false)
			{
				//获取购物车信息
				$.getJSON(_this.show_cart_url,{"random":Math.random},function(json)
				{//window.realAlert(JSON.stringify(json));
				$('#product_myCart').show();
					$('[name="mycart_count"]').text(json.count);
					$('[name="mycart_sum"]').text(json.sum);
	
					//展示购物车清单
					$('#product_myCart').show();
	
					//暂闭加入购物车按钮
					$('#joinCarButton').attr('disabled','disabled');
				});
			}
			else
			{
				alert(content.message);
			}
		});
	}
	
}