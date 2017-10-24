/**
 * 订单对象
 * address:收货地址; delivery:配送方式; payment:支付方式;
 */
function orderFormClass()
{
	//是否为货到付款 0:否; 1:是;
	this.paytype = 0;
	this.freeFreight = 0;
	
	//是否是预售0:不是，1：是
	this.presell = 0;

	//视图状态模式 默认：edit
	this.addressMod  = 'edit';
	//this.deliveryMod = 'edit';
	this.paymentMod  = 'edit';
	this.messageMod  = 'exit';

	//当前正在使用的ID
	this.addressActiveId   = '';
	//this.deliveryActiveId  = '';
	this.paymentActiveId   = '';
	this.messageActiveData = '';

	//视图切换按钮ID
	this.addressToggleButton  = 'addressToggleButton';
	//this.deliveryToggleButton = 'deliveryToggleButton';
	this.paymentToggleButton  = 'paymentToggleButton';
	this.messageToggleButton  = 'messageToggleButton';

	this.orderAmount  = 0;//订单金额
	this.goodsSum     = 0;//商品金额
	this.deliveryPrice= 0;//运费金额
	this.paymentPrice = 0;//支付金额
	this.taxPrice     = 0;//税金
	this.protectPrice = 0;//保价
	this.ticketPrice  = 0;//代金券
	this.box_fee      = 0;//餐盒费
	
	//this.deliveryConf = {};//记录配送信息的json对象
	
	//this.delivery_fee_url = '';
	
	this.tax = function (_this){
		$(_this).toggleClass("show_check");
		if($(_this).hasClass("show_check")){
			$(_this).next('div').show();
			$('input[name=taxes]').prop('checked',true);
		}else{
			$(_this).next('div').hide();
			$('input[name=taxes]').removeAttr('checked');
		}
		this.doAccount();
	}
	
	this.ticket = function(_this){
		$(_this).toggleClass("show_check");
		if($(_this).hasClass("show_check")){
			$(_this).next('div').show();	
		}else{
			$(_this).next('div').hide();
			$('[name="ticket_id"]').attr('checked',false);	
		}
			
		this.doAccount();
	}
	/**
	 * 算账
	 */
	this.doAccount = function()
	{
		this.paymentPrice = !this.paymentPrice ? 0 : this.paymentPrice ;
		//税金
		this.taxPrice = $('span[name="taxes"]').hasClass("show_check")? $('input[name="taxes"]').val() : 0 ;
		//代金券
		this.ticketPrice = $('#ticket_a').hasClass("show_check") && $('input:radio[name="ticket_id"]:checked').length>0 ? $('input:radio[name="ticket_id"]:checked').attr('alt') : 0;
		//最终金额
		this.orderAmount = parseFloat(this.goodsSum) - parseFloat(this.ticketPrice) + parseFloat(this.deliveryPrice) + parseFloat(this.paymentPrice) + parseFloat(this.taxPrice) + parseFloat(this.protectPrice) + parseFloat(this.box_fee);

		this.orderAmount = this.orderAmount <=0 ? 0 : this.orderAmount.toFixed(2);

		//刷新DOM数据
		$('#final_sum').text(this.orderAmount);
		$('[name="ticket_value"]').text(this.ticketPrice);
		$('#delivery_fee_show').text(this.deliveryPrice);
		$('#protect_price_value').text(this.protectPrice);
		$('#payment_value').text(this.paymentPrice);
		$('#tax_fee').text(this.taxPrice);
		if(this.presell){//计算预售金额
			var wei_sum = this.orderAmount - parseFloat($('#pre_sum').text());
			$('#wei_sum').text(wei_sum.toFixed(2));
		}
		$('#final_sums').text(this.orderAmount);
		
	}
	//获取运费
	this.get_delivery  = function ()
	{//window.realAlert(456);
		this.deliveryPrice = 0;
		var orderFormObj = this;
		var url = this.delivery_fee_url;
		var area     = $('[name=area]').val();                                          
		if(!area)
		{
			return;
		}
		var ziti = $('select[name=takeself]').val();
		if(ziti>0){
			this.freeFreight = 1;
		}
		else{
			this.freeFreight = 0;
		}
        $('.js_data_6').hide();
        var _d = []
        ,_in = 0; 
        $('span[id^=deliveryfee_]').each(function(){
            var _this = $(this)
            ,price = 0
            ,_g = []
            ,_group = 0;
			var _deliveryInfo = '';
            _this.parents('.js_seller_diff').find('.js_goods_delivery').each(function(){
                var _t = $(this)
                ,obj = _t.attr('js_data')
                ,dataArray = obj.split("_");
				_deliveryInfo = _deliveryInfo + obj + '|';
               /* $.ajax({
                    type:'post',
                    async:false,
                    data:{"area":area,"deliveryId":dataArray[0],"goodsId":dataArray[1],"productId":dataArray[2],"num":dataArray[3]},
                    dataType:'json',
                    url: _url,
                    success:function(content)
                    {
                        //地区无法送达
                        if(content.if_delivery == 1 || content.error == 1)
                        {
                            alert('您选择地区部分商品无法送达');
                            $('#'+obj).html("<span style='color:red'>无法送达</span>");
                        }
                        else
                        {
                            price += (content.price);
                            var html = parseFloat(content.price).toFixed(2);
                            //允许保价
                            if(content.protect_price > 0)
                            {
                                html += "<br /><label title='￥"+content.protect_price+"'><input type='checkbox' value='"+content.protect_price+"' name='insured["+_in+"]' onchange='selectProtect(this);' class='checks'/>保价</label>";
                            }
                            _t.html(html);
                        }
                        _g.push(content.goodsList);
                        _group = content.group_id;
                    },
                    timeout:1000,
                })*/
                _in++;                                                    
            })

			$.ajax({
				type: 'post',
				async:false,
				data: {"area":area,"delivery_info":_deliveryInfo},
				url:_get_delivery_url,
				success : function(data){
					data  = JSON.parse(data);
					$.each(data.delivery,function(index){
						var content = data.delivery[index];
						//地区无法送达
						if(content.if_delivery == 1 || content.error == 1)
						{
							alert('您选择地区部分商品无法送达');
						}
						else{
							price += (data.delivery[index].price);
						}


					})
					_g.push(data.goodsList);
					_group = data.group_id;
				}

			})
            
            $.ajax({
                type:'post',
                async:false,
                data:{final_sum:final_sum, area:area, group:_group, goodsList:_g},
                dataType:'json',
                url: _delivery_url,
                success:function(jsonData)
                {
                    if(!jsonData.isFreeFreight && orderFormObj.freeFreight==0)
                    {
                        orderFormInstance.deliveryPrice += parseFloat(price);
                        
                        _this.html('￥'+parseFloat(price).toFixed(2));
                    }
                    else
                    {
                        for(var i = 0; i < jsonData.isFreeFreight.length; i ++){
                            _d.push(jsonData.isFreeFreight[i]);
                        }
                        $('.js_data_6').parent('div').siblings('span.yhj').show();
                        orderFormInstance.deliveryPrice += 0;
                        _this.html('免运费');
                    }
                }
            })
            
           
        })                            
		if(_d.length > 0)
        {
            for(var i = 0; i < _d.length; i ++){
                $("#js_data_"+_d[i]).show();
            }
            
        }                                  
		this.doAccount();
	}            
	
	/**
	 * payment检查
	 * @return boolean
	 */
	this.paymentCheck = function()
	{
		if($('select[name=payment]').val == -1)
		{
			return false;
		}
		return true;
	}


	/**
	 * payment保存
	 */
	this.paymentSave = function()
	{

		this.paymentActiveId = $('select[name=payment]').val();
		if(this.paymentActiveId==7)
			$('[name=payment]').after('<p style="color:red;" name="alipay_info">由于您使用的是支付宝第三方担保交易，“确认收货、退款”等相关操作结束后需前往支付宝界面再次操作</p>');
		else
			$('[name=alipay_info]').remove();
		//支付金额
		this.paymentPrice = $('select[name=payment] option:selected').attr('price');

		//计算金额
		this.doAccount();
	}

	/**
	 * message模式切换
	 */
	this.messageModToggle = function()
	{
		//要切换的模式
		var toggleMod = this.messageMod == 'exit' ? 'edit' : 'exit';

		switch(toggleMod)
		{
			case "edit":
			{
				$('#'+this.messageToggleButton).text('[退出]');
			}
			break;

			case "exit":
			{
				//恢复数据
				$('[name="message"]').val(this.messageActiveData);
				$('#'+this.messageToggleButton).text('[修改]');
			}
			break;
		}

		//更新模式
		this.messageMod = toggleMod;

		//展示模式
		$('#message_show_box').toggle();

		//修改模式
		$('#message_form').toggle();
		$('#message_save_button').toggle();
	}

	/**
	 * 留言保存
	 */
	this.messageSave = function()
	{
		var messageData = $('[name="message"]').val();

		//更新table
		$('#messageShowBox').text(messageData);

		//保存到缓存
		this.messageActiveData = messageData;
		this.messageModToggle();
	}

	/**
	 * 检查表单是否可以提交
	 */
	this.isSubmit = function()
	{
		var saveButtonList = $('label[id$="_save_button"]:visible');
		if(saveButtonList.length > 0)
		{
			saveButtonList.first().trigger('focus');
			return false;
		}
		return true;
	}
}