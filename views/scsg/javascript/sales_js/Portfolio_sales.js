/*TAB菜单切换*/
$(function(){		
	//设计案例切换
	$('.title-list li').click(function(){
		var liindex = $('.title-list li').index(this);
		$(this).addClass('on').siblings().removeClass('on');
		$('.product-wrap div.product').eq(liindex).fadeIn(150).siblings('div.product').hide();
		var liWidth = $('.title-list li').width();
		$('.portfolio .title-list p').stop(false,true).animate({'left' : liindex * liWidth + 'px'},300);
	});
	});




/*上下滚动按钮*/

$(document).ready(function(){
	$('.mr_frBtnR,.mr_frBtnL').hover(function(){
			$(this).fadeTo('fast',1);
		},function(){
			$(this).fadeTo('fast',0.7);
	})

})
   
        


	
	
	
	
	
	
	
	
	
	
	