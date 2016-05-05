
$(function(){
	shan_bind_func();
});

function shan_bind_func(){
	$('.shan-all-li').children('li').hover(
		function(){$(this).addClass('hover')},
		function(){$(this).removeClass('hover')}
	)
}
function loadPresell(toUrl){
	$('#nextgroup').addClass('loading');
	var start = parseInt($('input[name=start]').val());
	var getUrl = toUrl;
	$.ajax({
		type:'post',
		async:true,
		data:{start:start},
		dataType:'json',
		url:getUrl,
		beforeSend:function(){
			
		},
		success:function(data){
			if(data){
				for(var i in data){
					data[i]['key']=start+parseInt(i)+1;
					var newProm = template.render('template',data[i]);
					$('.shan-all-li').append(newProm);
				}
				shan_bind_func();
				countDown(start);
				$('input[name=start]').val(start+parseInt(i)+1);
			}
			
			
		},
		complete:function(){
			$('#nextgroup').removeClass('loading');
			
		},
		timeout:1000,
	})
}


