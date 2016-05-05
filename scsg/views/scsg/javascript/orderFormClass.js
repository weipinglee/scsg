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
	this.deliveryMod = 'edit';
	this.paymentMod  = 'edit';
	this.messageMod  = 'exit';

	//当前正在使用的ID
	this.addressActiveId   = '';
	this.deliveryActiveId  = '';
	this.paymentActiveId   = '';
	this.messageActiveData = '';

	//视图切换按钮ID
	this.addressToggleButton  = 'addressToggleButton';
	this.deliveryToggleButton = 'deliveryToggleButton';
	this.paymentToggleButton  = 'paymentToggleButton';
	this.messageToggleButton  = 'messageToggleButton';

	this.orderAmount  = 0;//订单金额
	this.goodsSum     = 0;//商品金额
	this.deliveryPrice= 0;//运费金额
	this.paymentPrice = 0;//支付金额
	this.taxPrice     = 0;//税金
	this.protectPrice = 0;//保价
	this.ticketPrice  = 0;//代金券
	this.weight       = 0;//商品总重量
	
	this.deliveryConf = {};//记录配送信息的json对象
	
	/*
	 * 选择配送方式时调用，设置deliveryConf
	 */
	this.setDeliveryConf = function(json){
		this.deliveryConf = json;
	}

	/**
	 * 算账
	 */
	this.doAccount = function()
	{
		//税金
		this.taxPrice = $('input:checkbox[name="taxes"]:checked').length > 0 ? $('input:checkbox[name="taxes"]:checked').val() : 0;
		//代金券
		this.ticketPrice = $('input:radio[name="ticket_id"]:checked').length > 0 ? $('input:radio[name="ticket_id"]:checked').attr('alt') : 0;
		//最终金额
		this.orderAmount = parseFloat(this.goodsSum) - parseFloat(this.ticketPrice) + parseFloat(this.deliveryPrice) + parseFloat(this.paymentPrice) + parseFloat(this.taxPrice) + parseFloat(this.protectPrice);

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
		
	}

	/**
	 * 初始化JS省份联动菜单
	 */
	this.provinceMenuInit = function()
	{
		createAreaSelect('province',0,'');
		$('[name="city"]').empty();
		$('[name="area"]').empty();
	}

	/**
	 * address初始化
	 * @param defaultAddressId int 默认收货地址的主键索引
	 */
	this.addressInit = function(defaultAddressId)
	{
		if(defaultAddressId>0)
		{
			this.addressActiveId = defaultAddressId;
			$('input:radio[name="radio_address"][value="'+defaultAddressId+'"]').trigger('click');
			this.addressSave();
			return 1;
		}
		else
		{
			$('input:radio[name="radio_address"][value=""]').trigger('click');
			return false;
		}
	}

	/**
	 * address选中时
	 * @param jsonData Object 数据对象
	 */
	this.addressSelected = function(jsonData)
	{
		//刷新修改form部分
		$('#address_form input[type="text"]').each(function()
		{
			this.value = jsonData[this.name];
		});

		//js城市联动
		createAreaSelect('province',0,jsonData.province);
		createAreaSelect('city',jsonData.province,jsonData.city);
		createAreaSelect('area',jsonData.city,jsonData.area);

		//刷新展示table部分
		var showTableHtml = template.render('addressShowTemplate',jsonData);
		$('#addressShowBox').html(showTableHtml);

		//清除之前校验效果
		$('.invalid-msg').remove();
		$('#address_form input:text').removeClass('invalid-text');
		$('#address_form select').removeClass('invalid-text');
	}

	/**
	 * 清空地址数据
	 */
	this.addressEmpty = function()
	{
		//刷新修改form部分
		$('#address_form input[type="text"]').each(function()
		{
			this.value = '';
		});

		//初始化js城市联动
		this.provinceMenuInit();

		//刷新展示table部分
		$('#addressShowBox').empty();
	}

	/**
	 * address模式切换
	 */
	this.addressModToggle = function(type)
	{
		//要切换的模式
		var toggleMod = this.addressMod == 'exit' ? 'edit' : 'exit';

		switch(toggleMod)
		{
			case "edit":
			{
				$('#'+this.addressToggleButton).text('[退出]');
			}
			break;

			case "exit":
			{
				//退出性还原收货地址数据
				if(type != 'save' && this.addressActiveId)
				{
					$('input:radio[name="radio_address"][value="'+this.addressActiveId+'"]').trigger('click');
				}
				$('#'+this.addressToggleButton).text('[修改]');
			}
			break;
		}

		//更新模式
		this.addressMod = toggleMod;

		//展示模式
		$('#address_show_box').toggle();

		//修改模式
		$('#address_often').toggle();
		$('#address_form').toggle();
		$('#address_save_button').toggle();
	}

	/**
	 * 进行数据的校验
	 */
	this.addressCheck = function()
	{
		$('#address_form').trigger('submit',function(){return false;});

		//数据格式不正确
		if($('#address_form .invalid-text').length > 0 || (!$('#address_often radio[name=radio_address]:checked').val() && !$('[name=accept_name]').val()))
		{
			return false;
		}
		return true;
	}

	/**
	 * address保存
	 */
	this.addressSave = function()
	{
		if(this.addressCheck() == false)
		{
			return;
		}

		this.addressActiveId = $('input:radio[name="radio_address"]:checked').val();

		//当保存为临时收货地址时
		if(!this.addressActiveId)
		{
			//新添加的地址
			var jsonData =
			{
				"province_val":$('select[name="province"]>option:selected').text(),
				"city_val":$('select[name="city"]>option:selected').text(),
				"area_val":$('select[name="area"]>option:selected').text(),
			};

			//刷新修改form部分
			$('#address_form input[type="text"]').each(function()
			{
				jsonData[this.name] = this.value;
			});

			var showTableHtml = template.render('addressShowTemplate',jsonData);
			$('#addressShowBox').html(showTableHtml);
		}

		this.addressModToggle('save');

		//获取配送数据并且开启配送方式
		var timeHandle = setInterval(function(){
			if($('[name="area"]').val())
			{
				get_delivery();
				clearInterval(timeHandle);
			}
		},100);
	
		$('#deliveryBox').show('slow');
		if(this.deliveryCheck() && this.paymentCheck()){
			$('#amountBox').show('slow');
			//计算金额
			this.doAccount();
		}
	}

	/**
	 * delivery模式切换
	 */
	this.deliveryInit = function(defaultDeliveryId)
	{
		if(defaultDeliveryId > 0)
		{
			this.deliveryActiveId = defaultDeliveryId;
			var defaultDeliveryItem = $('input[type="radio"][name="delivery_id"][value="'+defaultDeliveryId+'"]');
			defaultDeliveryItem.trigger('click');

			//默认配送方式
			if($('#paymentBox:hidden').length == 1 && this.paytype == 0)
			{
				this.deliverySave();
			}
		}
	}

	/**
	 * delivery选中
	 * @param jsonData Object 配送方式数对象
	 */
	this.deliverySelected = function(jsonData)
	{
		var takeself = $('#takeself3').text();//zi
		if(jsonData.type==2){
			jsonData.takeself = takeself;
		}
		
		var deliveryShowHtml = template.render('deliveryShowTemplate',jsonData);
		$('#deliveryShowBox').html(deliveryShowHtml);
	}

	/**
	 * delivery模式切换
	 */
	this.deliveryModToggle = function()
	{
		//要切换的模式
		var toggleMod = this.deliveryMod == 'exit' ? 'edit' : 'exit';

		switch(toggleMod)
		{
			case "edit":
			{
				$('#'+this.deliveryToggleButton).text('[退出]');
			}
			break;

			case "exit":
			{
				if(!this.deliveryActiveId)
				{
					tips('请选择配送方式，并且进行保存');
					return;
				}
				else if($('input:radio[name="delivery_id"][value="'+this.deliveryActiveId+'"]:checked').length == 0)
				{
					//还原配送方式数据
					$('input:radio[name="delivery_id"][value="'+this.deliveryActiveId+'"]').trigger('click');
				}
				$('#'+this.deliveryToggleButton).text('[修改]');
			}
			break;
		}

		//更新模式
		this.deliveryMod = toggleMod;

		//展示模式
		$('#delivery_show_box').toggle();

		//修改模式
		$('#delivery_form').toggle();
		$('#delivery_save_button').toggle();
	}

	/**
	 * delivery保存检查
	 */
	this.deliveryCheck = function()
	{
		if($('input:radio[name="delivery_id"]:checked').length == 0)
		{
			return false;
		}
		return true;
	}

	/**
	 * delivery保存
	 */
	this.deliverySave = function()
	{
		if(this.deliveryCheck() == false)
		{
			tips('请选择配送方式');
			return;
		}

		this.paytype          = $('input:radio[name="delivery_id"]:checked').attr('paytype');
		this.deliveryActiveId = $('input:radio[name="delivery_id"]:checked').val();
		//自提点选择判断
		if( this.paytype == 2 && $('[name="takeself"]').val() == 0)
		{
			tips('请选择自提点');
			return;
		}

		this.deliveryModToggle();

		//在线支付与货到付款
		if(this.paytype == 1)
		{
			$('#paymentBox').hide('slow');
			this.paymentPrice = 0;

			//开启订单金额
			$('#amountBox').show('slow');
		}
		else
		{
			$('#paymentBox').show('slow');
		}
		
		//计算运费
		get_delivery();

		//计算金额
		this.doAccount();
		
		//设置配送信息
		this.deliverySelected(this.deliveryConf);
		
	}

	/**
	 * payment模式切换
	 */
	this.paymentModToggle = function()
	{
		//要切换的模式
		var toggleMod = this.paymentMod == 'exit' ? 'edit' : 'exit';

		switch(toggleMod)
		{
			case "edit":
			{
				$('#'+this.paymentToggleButton).text('[退出]');
			}
			break;

			case "exit":
			{
				if(!this.paymentActiveId)
				{
					tips('请选择配送方式，并且进行保存');
					return;
				}
				else if($('input:radio[name="payment"][value="'+this.paymentActiveId+'"]:checked').length == 0)
				{
					//还原配送方式数据
					$('input:radio[name="payment"][value="'+this.paymentActiveId+'"]').trigger('click');
				}
				$('#'+this.paymentToggleButton).text('[修改]');
				
			}
			break;
		}

		//更新模式
		this.paymentMod = toggleMod;

		//展示模式
		$('#payment_show_box').toggle();
		
		//选择担保交易时提醒退款到支付宝界面
		if(this.paymentActiveId==7)
			$('#paymentShowBox').append('<tr><th></th><td class="red">由于您使用的是支付宝第三方担保交易，“确认收货、退款”等相关操作结束后需前往支付宝界面再次操作</td></tr>');
		//修改模式
		$('#payment_form').toggle();
		$('#payment_save_button').toggle();
	}

	/**
	 * payment检查
	 * @return boolean
	 */
	this.paymentCheck = function()
	{
		if($('input:radio[name="payment"]:checked').length == 0)
		{
			return false;
		}
		return true;
	}

	/**
	 * payment选择
	 */
	this.paymentSelected = function(jsonData)
	{
		//刷新table部分
		var paymentShowHtml = template.render('paymentShowTemplate',jsonData);
		$('#paymentShowBox').html(paymentShowHtml);
	}

	/**
	 * payment初始化
	 */
	this.paymentInit = function(defaultPaymentId)
	{
		if(defaultPaymentId > 0)
		{
			$('input:radio[name="payment"][value="'+defaultPaymentId+'"]').trigger('click');
			this.paymentSave();
		}
	}

	/**
	 * payment保存
	 */
	this.paymentSave = function()
	{
		if(this.paymentCheck() == false)
		{
			tips('请选择支付方式');
			return;
		}

		this.paymentActiveId = $('input:radio[name="payment"]:checked').val();
		this.paymentModToggle();

		//支付金额
		this.paymentPrice = $('input:radio[name="payment"]:checked').attr('alt');

		if(this.addressCheck()){
			//开启订单金额
		$('#amountBox').show('slow');

		//计算金额
		this.doAccount();
		}
		
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