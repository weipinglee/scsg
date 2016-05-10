function showBig(m,l){
	//alert(pic.z);
	$('#J_ImgBooth').attr('src',m);
	$('#detShow').find('img').attr('src',l);
}

$(function(){
	$('#bigPic').bind('mousemove',function(e){
		
		var span = $('.ks-imagezoom-lens');
		$('#detShow').css('display','block');
		var pos = $(this).offset();
		var detTrueWidth = $('#detPic img').width();
		var detShowWidth = $('#detShow').width();
		var bigWidth = $('#bigPic').width();
		var bigHeight = $('#bigPic').height();
		var rate = detShowWidth/detTrueWidth;
		var spanWidth = rate*bigWidth;
		if (rate >= 1) {
			$('#detShow').css('display','none');
			return false;
		}
		span.css('display','block');
		
		span.css('width',spanWidth).css('height',spanWidth);
		span.css('left',e.pageX-pos.left-spanWidth/2+'px').css('top',e.pageY-pos.top-spanWidth/2+'px');
		
		pos = span.position();
		if(pos.left<0)span.css('left','0');
		if(pos.left>bigWidth-spanWidth)span.css('left',bigWidth-spanWidth-2+'px');
		if(pos.top<0)span.css('top','0');
		if(pos.top>bigHeight-spanWidth)span.css('top',bigHeight-spanWidth-2+'px');
		pos = span.position();
		$('#detPic img').css('top','-'+pos.top/rate+'px').css('left','-'+pos.left/rate+'px');
		
	})
	$('#bigPic').bind('mouseout',function(){$('.ks-imagezoom-lens').css('display','none');	$('#detShow').css('display','none');})
	
})

