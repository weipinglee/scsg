var sourceJson=[
               {ico:"ic1.png",name:"个人中心"},	  
               {ico:"ic2.png",name:"客服QQ"},
			   {ico:"ic3.png",name:"常见问题"},
			   {ico:"ic4.png",name:"我的关注"},			   
			   {ico:"ic5.png",name:"返回顶部"}
			   ];
JQuery(function(){
	var beside=$("#beside");
	var height=$(window).height();
	var rightsead =$("#rightsead");
	
	beside.find("li").hover(function(){
		$(this).stop().animate({marginLeft:"0px"},300);
	},function(){$(this).stop().animate({marginLeft:"75px"},300);
		
		
	});
	$(document.body).append(beside);
	beside.animate({top:height/3+"px"},300);

	

});

