function getMoreTuan(toUrl){
	$('.loading-imgS img').show();
	$('.loading-imgS p').hide();
	var start = parseInt($('input[name=start]').val());
	var getUrl = toUrl;
	$.ajax({
		type:'post',
		async:false,
		data:{start:start},
		dataType:'json',
		url:getUrl,
		beforeSend:function(){
			
		},
		success:function(data){
			if(data==0){
						$('.loading-imgS p').text('没有更多数据');
			}
			else{
				for(var i in data){
					var newProm = template.render('tuan_box',data[i]);
					$('#tuan_box2 ul').append(newProm);
				}
				$('input[name=start]').val(start+parseInt(i)+1);
			}
		},
		complete:function(){
			$('.loading-imgS img').hide();
			$('.loading-imgS p').show();
		},
		timeout:1000,
	})
}
window.onscroll = function(){
	 if ($(document).scrollTop() >= $(document).height() - $(window).height()){
	 	getMoreTuan(moreTuanUrl);
	 }
}