$(function(){

	//新增收货地址
	$('input[name=address_add]').on('click',function(){
		var _this=$(this);
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
				if(data.data!=null){
					location.reload();
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
//选择收货地址
function address_select(_this){
	var addressId = $(_this).find('input').val();
	$.ajax({
		type:'post',
		async:false,
		data:{id:addressId},
		//dataType:'json',
		url:address_default_url,
		beforeSend:function(){
			
		},
		success:function(data){
			if(data==1)
				history.go(-1);
			
		},
		error:function(){
			
		},
		complete:function(){
			
		},
		timeout:1000,
	})
}
