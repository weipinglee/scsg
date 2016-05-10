<?php
/**
 * @file dbmodel_class.php
 * @brief 数据处理
 * @date 2010-12-14
 * @version 0.6
 */

/**
 * @class IModel
 * @brief 数据处理对象
 */
class IModel
{
	//数据库操作对象
	protected  $db = NULL;
	
	//数据表前缀
	protected $tablePre = '';

	//数据表名称
	protected $tableName = '';

	//要更新的表数据,key:对应表字段; value:数据;
	protected $tableData = array();

	/**
	 * @brief 构造函数,创建数据库对象
	 * @param string $tableName 表名称(当多表操作时以逗号分隔,如：user,goods);
	 */
	public function __construct($tableName='')
	{
		$this->db = IDBFactory::getDB();
		$this->tablePre = isset(IWeb::$app->config['DB']['tablePre']) ? IWeb::$app->config['DB']['tablePre'] : '';
		$tablePre = $this->tablePre;
		//多表处理
		if(stripos($tableName,','))
		{
			$tables = explode(',',$tableName);
			foreach($tables as $val)
			{
				if($this->tableName != '')
					$this->tableName .= ',';

				$this->tableName .= $tablePre.trim($val);
			}
		}

		//单表处理
		else
		{
			$this->tableName = $tablePre.$tableName;
		}
	}
	//更改表名
	public function changeTable($tableName){
		$this->tableName = $this->tablePre . $tableName;
	}
	//获取表名
	public function getTableName(){
		return $this->tableName;
	}
	/**
	 * @brief 设置要更新的表数据
	 * @param array $data key:字段名; value:字段值;
	 */
	public function setData($data)
	{
		if(is_array($data))
			$this->tableData = $data;
		else
			return false;
	}
	/*
	 * @brief 查找
	 * @$where array 查询条件
	 * @$fields array  查询字段
	 */
	public function select($where,$fields='*'){
		if(isset($where) && is_array($where)){
			$condition = '';
			foreach($where as $key=>$v){
				if(is_string($v)&&strpos($v,',')){
					$condition .= '`'.$key.'` in ('.$v.') AND ';
				}else if(is_array($v)){
					$l='';
					$condition .= '`'.$key.'` in (';
					foreach($v as $k){
						$condition .= $k.',';
					}
					$condition = substr($condition,0,-1); 
					$condition .= ') AND ';
				}else{
					$condition .= '`'.$key.'` = '.$v.' AND ';
				}
			}
			$condition = substr($condition,0,-4);
			return $this->query($condition,$fields);
		}else{
			return false;
		}
	}
	/**
	 * @brief 更新
	 * @param  string $where 更新条件
	 * @param  array  $except 非普通数据形式(key值)
	 * @return int or bool int:影响的条数; bool:false错误
	 */
	public function update($where,$except=array())
	{
		$except = is_array($except) ? $except : array($except);

		//获取更新数据
		$tableObj  = $this->tableData;
		$updateStr = '';
		$where     = (strtolower($where) == 'all') ? '' : ' WHERE '.$where;

		foreach($tableObj as $key => $val)
		{
			if($updateStr != '') $updateStr.=' , ';
			if(!in_array($key,$except))
				$updateStr.= '`'.$key.'` = \''.$val.'\'';
			else
				$updateStr.= '`'.$key.'` = '.$val;
		}
		$sql = 'UPDATE '.$this->tableName.' SET '.$updateStr.$where;
	
		return $this->db->query($sql);
	}

	/**
	 * @brief 添加
	 * @return int or bool int:插入的自动增长值 bool:false错误
	 */
	public function add()
	{
		//获取插入的数据
		$tableObj = $this->tableData;

		$insertCol = array();
		$insertVal = array();
		foreach($tableObj as $key => $val)
		{
			$insertCol[] = '`'.$key.'`';
			$insertVal[] = '\''.$val.'\'';
		}
		$sql = 'INSERT INTO '.$this->tableName.' ( '.join(',',$insertCol).' ) VALUES ( '.join(',',$insertVal).' ) ';
		return $this->db->query($sql);
	}

	/**
	 * @brief 删除
	 * @param string $where 删除条件
	 * @return int or bool int:删除的记录数量 bool:false错误
	 */
	public function del($where)
	{
		$where = (strtolower($where) == 'all') ? '' : ' WHERE '.$where;
		$sql   = 'DELETE FROM '.$this->tableName.$where;
		try{
			return $this->db->query($sql);
		}
		catch(Exception $e){
			return 0;
		}
	}

	/**
	 * @brief 获取单条数据
	 * @param string $where 查询条件
	 * @param array or string $cols 查询字段,支持数组格式,如array('cols1','cols2')
	 * @return array 查询结果
	 */
	public function getObj($where = false,$cols = '*')
	{
		$result = $this->query($where,$cols,'','',1);
		if(empty($result))
		{
			return array();
		}
		else
		{
			return $result[0];
		}
	}

	/**
	 * @brief 获取多条数据
	 * @param string $where 查询条件
	 * @param array or string $cols 查询字段,支持数组格式,如array('cols1','cols2')
	 * @param array or string $orderBy 排序字段
	 * @param array or string $desc 排列顺序 值: DESC:倒序; ASC:正序;
	 * @param array or int $limit 显示数据条数 默认(500)
	 * @return array 查询结果
	 */
	public function query($where=false,$cols='*',$orderBy=false,$desc='DESC',$limit=500,$print=0)
	{
		//字段拼接
		if(is_array($cols))
		{
			$colStr = join(',',$cols);
		}
		else
		{
			$colStr = ($cols=='*' || !$cols) ? '*' : $cols;
		}

		$sql = 'SELECT '.$colStr.' FROM '.$this->tableName;

		//条件拼接
		if($where != false) $sql.=' WHERE '.$where;

		//排序拼接
		if($orderBy != false)
		{
			$sql.= ' ORDER BY '.$orderBy;
			$sql.= (strtoupper($desc) == 'DESC') ? ' DESC ':' ASC ';
		}

		//条数拼接
		if($limit != 'all')
		{
			$limit = intval($limit);
			$limit = $limit ? $limit : 500;
			$sql.=' limit ' . $limit;
		}
		if($print)echo $sql;
		return $this->db->query($sql);
	}
	//开启事务
	public function begin_trans(){
		return $this->db->autoCommit();
	}
	//提交事务
	public function commit(){
		return $this->db->commit();
	}

	/**
	 * @brief 写操作回滚
	 */
	public function rollback()
	{
		$this->db->switchLink("w");
		return $this->db->rollback();
	}
	//执行一条sql语句
	public function doSql($sql){
		return $this->db->doSql($sql);
	}
	//获取一个字段的值
	public function getField($where,$field){
		$res = $this->getObj($where,$field);
		return $res ? $res[$field] : array();
	}
	//获取单个字段多条数据，结果类似于array(1,2,3,)
	//@where array 
	public function getFields($where,$field){
		$res = $this->select($where,$field);
		$returnData = array();
		foreach($res as $v){
			if(!in_array($v[$field],$returnData))array_push($returnData,$v[$field]);
		}
		return $returnData;
		
	}
	//
	public function db_query($sql){
		return $this->db->query($sql);
	}
	
	//某个字段累加一个数
	/*
	 * @$where array or str条件
	* @$addArr array 累加的字段和数量array('field'=>$num)
	* @$and  int 1：and连接，0：or连接
	*/
	public function addNum($where,$addArr,$and=1){
		$con = ' WHERE ';
		$and = $and ? 'AND' : 'OR';
		if(isset($where) && is_array($where)){
			foreach($where as $key=>$val){
				$con .= $key . ' = "'.$val.'" ' .$and.' ';
			}
			$con = substr($con,0,-4);
		}else{
			$con .= $where;
		}
		$str='';
		foreach($addArr as $key=>$val){
			$str .= '`'.$key.'`' .'='.$key .' + '.$val. ',';
		}
		$str = substr($str,0,-1);
		$sql = 'UPDATE '.$this->tableName.' SET '.$str.$con;
		return $this->db->query($sql);
	
	}
}