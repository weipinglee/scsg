<?php
class expImodel extends IModel{
	public function update($where,$except=array()){
		
		//获取更新数据
		$tableObj  = $this->tableData;
		$updateStr = '';
		$where     = (strtolower($where) == 'all') ? '' : ' WHERE '.$where;
		
		foreach($tableObj as $key => $val)
		{
				$updateStr.= '`'.$key.'` = '.$val;
		}
		$sql = 'UPDATE '.$this->tableName.' SET '.$updateStr.$where;
		return $this->db->query($sql);
	}

	//获取一个字段的值
	public function getField($where,$field){
		$res = $this->getObj($where,$field);
		return $res[$field];
	}
	//开启事务
	public function begin_trans(){
		return $this->db->autoCommit();
	}
	//提交事务
	public function commit(){
		return $this->db->commit();
	}
	//回滚
	public function rollback(){
		return $this->db->rollback();
	}
}