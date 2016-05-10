jQuery(document).ready(function($){
	//open popup
	$('.pop').on('click', function(event){
		var tourl = $(this).attr('tourl');
		$('.wd-popup a[name=todo]').attr('href',tourl);
		$('.wd-popup p').text($(this).attr('alt'));
		event.preventDefault();
		$('.wd-popup').addClass('ws-visible');
	});
	
	//close popup
	$('.wd-popup').on('click', function(event){
		if( $(event.target).is('.wd-popup-close') || $(event.target).is('.wd-popup') ) {
			event.preventDefault();
			$(this).find('a[name=todo]').attr('href','');
			$(this).removeClass('ws-visible');
		}
	});
	//close popup when clicking the esc keyboard button
	$(document).keyup(function(event){
    	if(event.which=='27'){
			$('.wd-popup a[name=todo]').attr('href','');
    		$('.wd-popup').removeClass('ws-visible');
	    }
    });
});
//合并付款弹出页面
jQuery(document).ready(function($){
	//open popup
	$('.button').on('click', function(event){
		$(".wds-popup").css('display','block');
	});
	//close popup
	$('.wds-popup').on('click', function(event){
		if( $(event.target).is('.wds-popup-close') || $(event.target).is('.wds-popup') ) {
			event.preventDefault();
			$(".wds-popup").css('display','none');
		}
	});
	//close popup when clicking the esc keyboard button
	$(document).keyup(function(event){
    	if(event.which=='27'){
			$(".wds-popup").css('display','none');
	    }
    });
});