<?php
/**
 * 
 */
Class Admin_common extends IController{
	public $checkRight  = 'all';
	private $data = array();
	public function init()
	{
		IInterceptor::reg('CheckRights@onCreateAction');
	}
	/*
	 * 对数据表字段0、1切换
	 */
	public function set_type(){
		$id = IFilter::act(IReq::get('id'),'int');
		$field = IFilter::regAct(IReq::get('field'),'/^[a-zA-z0-9_-]{2,50}$/');
		$table = IFilter::regAct(IReq::get('table'),'/^[a-zA-z0-9_-]{2,50}$/');
		if($table=='' || $field=='')return false;
		$seller = new IModel($table);
		$dataArr = array($field=>'abs('.$field.'-1)');
		$seller->setData($dataArr);
		$is_result = $seller->update('id='.$id,$field);
		$sellerRow = $seller->getObj('id = "'.$id.'"',$field);
		if($is_result!==false)
		{
			echo JSON::encode(array('isError' => 0,'succ' => $sellerRow[$field]));
		}
		else
		{
			echo JSON::encode(array('isError'=>1,'message'=>'设置失败'));
		}
	}

}