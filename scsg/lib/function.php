<?php
/**
 * 将多个键值对的数组转换为搜索条件
 * @$search array 
 * @return str
 */

function getSearchCondition($search){
	
	if(is_array($search)){
		$where='';
		if(isset($search['field'])&&$search['field']&&isset($search['keywords'])&&$search['keywords']){
			$where .= $search['field'] .'="'.$search['keywords'].'" AND ';
		}
		
		foreach($search as $key =>$v){
			if(!in_array($key,array('keywords','field')) && $v!='')
			$where .= $key.' = "'.$v.'" AND ';
		}
		$where = substr($where,0,-4);
		return $where != ''? $where : 1;
	}else{
		return 1;
	}
}
/**
 * 图片上传处理函数
 * @$img_name str 图片名
 * @return str url路径
 */
function uploadHandle($img_name){

	if(isset($_FILES[$img_name]['name']) && $_FILES[$img_name]['name'])
	{
		$uploadObj = new PhotoUpload();
		$uploadObj->setIterance(false);
		$photoInfo = $uploadObj->run();
		if(isset($photoInfo[$img_name]['img']) && file_exists($photoInfo[$img_name]['img']))
		{
			return $photoInfo[$img_name]['img'];
		}
		return 0;
	}
	return 0;
}