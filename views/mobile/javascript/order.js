$(function(){

	//新增收货地址
	$('input[name=address_add]').on('click',function(){
	
		var _this=$(this);
		if(!checkForm('address')){return false;}
		_this.attr('disabled',true);
		var form = _this.parents('form');
		$.ajax({
			type:'post',
			async:false,
			data:form.serialize(),
			dataType:'json',
			url:form.attr('action'),
			beforeSend:function(){
				
			},
			success:function(data){
				if(data.errCode==0){
					location.href=last_url+'?'+Math.random();
				}
				else if(data.errCode==2){
					realAlert('请先登录');
				}else if(data.errCode==1){
					$('[name='+data.field+']').focus();
				}else if(data.errCode==3){
					realAlert('系统繁忙');
				}
			},
			error:function(){
				
			},
			complete:function(){
				_this.removeAttr('disabled');
			},
			timeout:1000,
		})
	})
	
})


//
function address_edit(_this){
	var json_str = $(_this).find('[name^=json_data]').val();
	var json = JSON.parse(json_str);
	createAreaSelect('province',0,json.province);
	createAreaSelect('city',json.province,json.city);
	createAreaSelect('area',json.city,json.area);
	$('input[name=add_id]').val(json.id);
	$('input[name=accept_name]').val(json.accept_name);
	$('input[name=address]').val(json.address);
	$('input[name=zip]').val(json.zip);
	$('input[name=mobile]').val(json.mobile);
}
