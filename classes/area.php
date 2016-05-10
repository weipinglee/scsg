<?php
/**
 * @file area.php
 * @brief 省市地区调用函数
 * @author 
 * @date 2014/8/6 20:46:52
 * @version 2.6
 * @note
 */

 /**
 * @class area
 * @brief 省市地区调用函数
 */
class area
{
	/**
	 * @brief 根据传入的地域ID获取地域名称，获取的名称是根据ID依次获取的
	 * @param int 地域ID 匿名参数可以多个id
	 * @return array
	 */
	public static function name()
	{
		$result     = array();
		$paramArray = func_get_args();
		$areaDB     = new IModel('areas');
		$areaData   = $areaDB->query("area_id in (".trim(join(',',$paramArray),",").")");
		foreach($areaData as $key => $value)
		{
			$result[$value['area_id']] = $value['area_name'];
		}
		return $result;
	}
	
	/**
	 * 获取省市区地址全称
	 * @param int 区域代码
	 */
	public static function allName(){
		$result = array();
		$paramStr = func_get_args();
		$paramStr = $paramStr[0];
		$areaDB = new IModel('areas');
		if(strlen($paramStr)!=6)
			return false;
		$provinceCode = substr($paramStr,0,2).'0000';
		$cityCode = substr($paramStr,0,4).'00';
		$result['province'] = $areaDB->getField('area_id = '.$provinceCode,'area_name');
		$result['city'] = $areaDB->getField('area_id = '.$cityCode,'area_name');
		$result['area'] = $areaDB->getField('area_id = '.$paramStr,'area_name');
		return $result;
	}
	/**
	 * 获取省市区(将省市区连起来）
	 */
	public static function getNameStr(){
		$paramStr = func_get_args();
		$paramStr = $paramStr[0];
		$areaDB = new IModel('areas');
		if(strlen($paramStr)!=6)
			return false;
		$provinceCode = substr($paramStr,0,2).'0000';
		$cityCode = substr($paramStr,0,4).'00';
		if($provinceCode==$cityCode){
			return $areaDB->getField('area_id = '.$provinceCode,'area_name');
		}
		else if($cityCode==$paramStr)
		{
			return $areaDB->getField('area_id = '.$provinceCode,'area_name').' '.$areaDB->getField('area_id = '.$cityCode,'area_name');
		}
		else{
			return $areaDB->getField('area_id = '.$provinceCode,'area_name').' '.$areaDB->getField('area_id = '.$cityCode,'area_name').' '.$areaDB->getField('area_id = '.$paramStr,'area_name');
		}
		
	}
}