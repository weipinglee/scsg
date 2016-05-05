	$(document).ready(function(){
			$(".ptfp").click(function(){
				$("#bk_cont").hide();
				$("#zj_cont").hide();
				$("#pt_cont").show();
				$("#ptfps").addClass("fis");
				$("#bkfps"). removeClass("fis");
				$("#zjss"). removeClass("fis");
			});
			$(".bkfp").click(function(){
				$("#bk_cont").show();
				$("#zj_cont").hide();
				$("#pt_cont").hide();
				$("#ptfps").removeClass("fis");
				$("#bkfps"). addClass("fis");
				$("#zjss"). removeClass("fis");
			});
			$(".zjs").click(function(){
				$("#bk_cont").hide();
				$("#zj_cont").show();
				$("#pt_cont").hide();
				$("#ptfps").removeClass("fis");
				$("#bkfps"). removeClass("fis");
				$("#zjss"). addClass("fis");
			});
		});