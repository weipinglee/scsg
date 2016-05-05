//form str ±íµ¥Ãû
function checkForm(_form){
	var flag = true;
	var formObj = $('form[name='+_form+']');
	
	var formChild = formObj.find('input,select,textarea');
	
	formChild.each(function(){
			var pattern = $(this).attr("pattern");
            var required = $(this).attr("required")!=undefined;
			if(required && !pattern)
            {
				pattern = "\\S";
				$(this).attr("pattern", pattern);
            }
			if(pattern)
			{
				switch(pattern)
				{
					case 'required': pattern = /\S+/i;break;
					case 'email': pattern = /^\w+([-+.]\w+)*@\w+([-.]\w+)+$/i;break;
					case 'qq':  pattern = /^[1-9][0-9]{4,}$/i;break;
					case 'id': pattern = /^\d{15}(\d{2}[0-9x])?$/i;break;
					case 'ip': pattern = /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/i;break;
					case 'zip': pattern = /^\d{6}$/i;break;
					case 'mobi': pattern = /^1[2|3|4|5|6|7|8|9][0-9]\d{4,8}$/;break;
					case 'phone': pattern = /^((\d{3,4})|\d{3,4}-)?\d{3,8}(-\d+)*$/i;break;
					case 'url': pattern = /^[a-zA-z]+:\/\/(\w+(-\w+)*)(\.(\w+(-\w+)*))+(\/?\S*)?$/i;break;
					case 'date': pattern = /^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)$/i;break;
					case 'datetime': pattern = /^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29) (?:(?:[0-1][0-9])|(?:2[0-3])):(?:[0-5][0-9]):(?:[0-5][0-9])$/i;break;
					case 'int':	pattern = /^\d+$/i;break;
					case 'float': pattern = /^\d+\.?\d*$/i;break;
				}
				if(!$(this).val().match(pattern))
				{
					$(this).focus();
					flag = false;
					return false;
				}
					
			}
			
		}
	)
	return flag;

}