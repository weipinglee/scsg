jQuery(document).ready(function($){
	//open popup
	$('.cd-popup-trigger').on('click', function(event){
		event.preventDefault();
		$('.cd-popup').addClass('is-visible');
	});
	
	//close popup
	$('.cd-popup').on('click', function(event){
		if( $(event.target).is('.cd-popup-close') || $(event.target).is('.cd-popup') ) {
			event.preventDefault();
			$(this).removeClass('is-visible');
		}
	});
	//close popup when clicking the esc keyboard button
	$(document).keyup(function(event){
    	if(event.which=='27'){
    		$('.cd-popup').removeClass('is-visible');
	    }
    });
});









jQuery(document).ready(function($){
	//open popup
	$('.qd-popup-trigger').on('click', function(event){
		event.preventDefault();
		$('.qd-popup').addClass('qs-visible');
	});
	
	//close popup
	$('.qd-popup').on('click', function(event){
		if( $(event.target).is('.qd-popup-close') || $(event.target).is('.qd-popup') ) {
			event.preventDefault();
			$(this).removeClass('qs-visible');
		}
	});
	//close popup when clicking the esc keyboard button
	$(document).keyup(function(event){
    	if(event.which=='27'){
    		$('.qd-popup').removeClass('qs-visible');
	    }
    });
});







jQuery(document).ready(function($){
	//open popup
	$('.wd-popup-trigger').on('click', function(event){
		event.preventDefault();
		$('.wd-popup').addClass('ws-visible');
	});
	
	//close popup
	$('.wd-popup').on('click', function(event){
		if( $(event.target).is('.wd-popup-close') || $(event.target).is('.wd-popup') ) {
			event.preventDefault();
			$(this).removeClass('ws-visible');
		}
	});
	//close popup when clicking the esc keyboard button
	$(document).keyup(function(event){
    	if(event.which=='27'){
    		$('.wd-popup').removeClass('ws-visible');
	    }
    });
});

