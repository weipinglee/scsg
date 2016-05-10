
function getData(obj,url){
	$('.loading-imgS img').show();
	$('.loading-imgS p').hide();
	
	$.ajax({
		type:'post',
		async:false,
		data:obj,
		dataType:'json',
		url:url,
		beforeSend:function(){
			
		},
		success:function(data){
			if(data==0){
				$('.loading-imgS p').text('没有更多数据');
			}else{
				for(var i in data){
					var newPro = template.render(temp,data[i]);
					$('#dataList').append(newPro);
				}
				$('input[name=page]').val(obj.page+1);
			}
		},
		complete:function(){
			$('.loading-imgS img').hide();
			$('.loading-imgS p').show();
		},
		timeout:1000,
	})
}
//获取更多产品
function showMorePro(){
	
	var page = parseInt($('input[name=page]').val());
	var childCat = $('input[name=childCat]').val();
	var order = $('input[name=order]').val();
	var obj = {
		page:page,childCat:childCat,order:order
	};
	
	getData(obj,getMoreUrl);
}
//获取更多搜索产品
function showMoreSearch(){
	var page = parseInt($('input[name=page]').val());
	var word = $('input[name=word_ajax]').val();
	var order = $('input[name=order]').val();
	var cat_id = $('input[name=cat_id]').val();
	var obj = {
		page:page,word:word,order:order,cat_id:cat_id
	};
	getData(obj,getMoreUrl);
}

