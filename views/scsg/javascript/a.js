
$(document).ready(function(){
		$('.admanage').each(function(){
			var img = $(this).find('img');
			var w = img.css('width');
			var h = img.css('height');
			var shine = $('.shine').eq(0).clone(true);
			shine.css('width',w).css('height',h).css('cursor','pointer');
			
			$(this).append(shine);
			
		});
	$(".ad-shine  .admanage").on('mouseenter',function(){
		$(this).find(".shine").stop();
		$(this).find(".shine").css("opacity","0.4"); 
		$(this).find(".shine").animate({opacity: '0'},500);
	})
	$('.shine').each(function(){
		
		$(this).on('click',function(){
			$(this).parent('div').find('img').trigger('click');
		})
	})
	
});

