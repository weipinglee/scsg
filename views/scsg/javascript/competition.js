$(document).ready(function(){
	$(".table-striped tr td .captain_a").click(function(){
		$(".table-striped tr td .captain .team_member").hide();
		$(this).parent().find(".team_member").show();
	})
	$(".table-striped tr td .captain .member_title .esc_icon").click(function(){
		$(".table-striped tr td .captain .team_member").hide();
	})
})