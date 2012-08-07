<?php
/**
 * Query allows you to select, update, insert, and delete
 *
 * @author Ravi Budhu
 */

 /* Change this path to point to the location of the autoload.php script */
include_once($_SERVER["DOCUMENT_ROOT"] . "/php/autoload.php");		
	
class Query
{
    //table name
    private $myTable;
    //only select the things that are in this array
    private $butOnly;
    //ignore things that are in this array
    private $butNot;
    //where clauses
    private $wheres;
	//set clauses for updates
	private $sets;
	//order by clauses
	private $orders;
    //columns
    private $columns;
    //tables
    private $tables;
	//connection
	private $conn;
	//query
	private $myQuery;
	//query type
	private $myQueryType;
	//class
	private $clazz;
	//count
	private $countField;
	
	
	private static $SELECT = 1;
	private static $INSERT = 2;
	private static $UPDATE = 3;
	private static $DELETE = 4;
	private static $COUNT  = 5;
	
    public function __construct($table)
    {
        $this->myTable = strtolower($table);
        $this->butOnly = array();
        $this->butNot = array();
        $this->wheres = array();
		$this->sets = array();
		$this->orders = array();
        $this->columns = array();
        $this->tables = array();
		array_push($this->tables, $table);	
		$this->conn = ConnectionFactory::getFactory()->getConnection();
	}
	
	private function reset()
	{
		$this->butOnly = array();
        $this->butNot = array();
        $this->wheres = array();
		$this->sets = array();
		$this->orders = array();
        $this->columns = array();
        $this->tables = array();
		array_push($this->tables, $this->myTable);
	}
	
	public function &select($c)
	{
		$this->reset();
		$this->myQueryType = self::$SELECT;
		$this->clazz = $c;
		return $this;
	}
	
	public function &insert($c)
	{
		$this->reset();
		$this->myQueryType = self::$INSERT;
		$this->clazz = $c;
		return $this;
	}
	
	public function &update()
	{
		$this->reset();
		$this->myQueryType = self::$UPDATE;
		return $this;
	}
	
	public function &delete()
	{
		$this->reset();
		$this->myQueryType = self::$DELETE;
		return $this;
	}
	
	public function &count($f)
	{
		$this->reset();
		$this->myQueryType = self::$COUNT;
		$this->countField = $f;
		return $this;
	}
	
	public function execute()
	{
		if($this->myQueryType == self::$SELECT)
		{
			return $this->doSelect($this->clazz);
		}
		else if($this->myQueryType == self::$INSERT)
		{
			return $this->doInsert($this->clazz);
		}
		else if($this->myQueryType == self::$UPDATE)
		{
			return $this->doUpdate();
		}
		else if($this->myQueryType == self::$DELETE)
		{
			return $this->doDelete();
		}
		else if($this->myQueryType == self::$COUNT)
		{
			return $this->doCount($this->countField);
		}
	}
	
	private function doUpdate()
	{
		$query = "UPDATE " . $this->myTable;
		$values = array();
		if(count($this->sets) > 0)
		{
			$query .= " SET ";
			$sep = "";
			foreach($this->sets as $setObj)
			{
				$query .= $sep . $setObj->getColumn() . " = ?";
				array_push($values, $setObj->getValue());
				$sep = ", ";
			}
		}
		if(count($this->wheres) > 0)
		{
			$query .= " WHERE ";
			$sep = "";
			foreach($this->wheres as $whereObj)
			{
				$query .= $sep . $whereObj->getColumn() ." = ?";
				array_push($values, $whereObj->getValue());
				$sep = " AND ";
			}
		}
		$stmt = $this->conn->prepare($query);
		$this->setQuery($query);
		return $stmt->execute($values);
	}
	
	public function raw_select($clazz,$query)
	{
		$ret = array();
		$mapping = $this->getNameClassMapping($clazz);
		$stmt = $this->conn->prepare($query);
		$this->setQuery($query);
		$rows = $stmt->execute();
		$rowCount = $stmt->rowCount();
		$class = new ReflectionClass($clazz);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$obj = $class->newInstance();
			foreach($row as $key => $value)
			{
				$propertyName = $this->unUnderscorize($key);
				$setMethod = $mapping[$propertyName][1];
				$setMethod->invoke($obj, $value);
			}
			array_push($ret, $obj);
		}	
		return $ret;
	}
	
	public function raw_insert($query)
	{
		$stmt = $this->conn->prepare($query);
		$this->setQuery($query);
		return $stmt->execute();
	}
	
	public function raw_update($query)
	{
		$stmt = $this->conn->prepare($query);
		$this->setQuery($query);
		return $stmt->execute();
	}
	
	public function raw_delete($query) 
	{
		$stmt = $this->conn->prepare($query);
		$this->setQuery($query);
		return $stmt->execute();

	}
	
	private function doSelect($clazz)
	{
		$query = "SELECT ";
		$mapping = $this->getNameClassMapping($clazz);
		$values = array();
		$ret = array();
		$sep = "";
		foreach ($mapping as $key => $val)
		{
			$appendIt = True;
			$column = $this->myTable . "." . $this->underscorize($key);
			if(count($this->butNot) > 0 && in_array($column, $this->butNot))
				$appendIt = False;
			if(count($this->butOnly) > 0 && !in_array($column, $this->butOnly))
				$appendIt = False;
			if($appendIt)
			{
				$query .= $sep . $column;
				$sep = ",";
			}
		}
		
		$query .= " FROM ";
		$sep = "";
		foreach($this->tables as $table)
		{
			$query .= $sep . $table;
			$sep = ", ";
		}
		
		if(count($this->wheres) > 0)
		{
			$query .= " WHERE ";
			$sep = "";
			foreach($this->wheres as $whereObj)
			{
				$query .= $sep . $whereObj->getColumn() ." = ?";
				array_push($values, $whereObj->getValue());
				$sep = " AND ";
			}
		}
		if(count($this->orders) > 0)
		{
			$query .= " ORDER BY ";
			$sep = "";
			foreach($this->orders as $orderObj)
			{
				$query .= $sep . $orderObj->getColumn() . " " . $orderObj->getOrder();
				$sep = ",";
			}
		}
		$this->setQuery($query);
		$stmt = $this->conn->prepare($query);	
		$rows = $stmt->execute($values);
		$rowCount = $stmt->rowCount();
		$class = new ReflectionClass($clazz);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$obj = $class->newInstance();
			foreach($row as $key => $value)
			{
				$propertyName = $this->unUnderscorize($key);
				$setMethod = $mapping[$propertyName][1];
				$setMethod->invoke($obj, $value);
			}
			array_push($ret, $obj);
		}	
		return $ret;
	
	}
	
	private function doInsert($clazz)
	{
		$query = "INSERT INTO " . $this->myTable . "(";
		$valPart = "VALUES(";
		$mapping = $this->getNameClassMapping($clazz);
		$sep = "";
		$values = array();
		foreach ($mapping as $key => $val)
		{
			$appendIt = True;
			$column = $this->myTable . "." . $this->underscorize($key);
			if(count($this->butNot) > 0 && in_array($column, $this->butNot))
				$appendIt = False;
			if(count($this->butOnly) > 0 && !in_array($column, $this->butOnly))
				$appendIt = False;
			if($appendIt)
			{
				$uKey = $this->underscorize($key);
				$query .= $sep . $uKey;
				$getMethod = $val[0];
				$result = $getMethod->invoke($clazz);
				if(is_string($result) && strlen($result) == 0)
					$result = NULL;
				$values[$uKey] = $result;
				$valPart .= $sep . ":" . $uKey;
				$sep = ",";
			}
		}
		
		$query .= ")";
		$query .= $valPart . ")";
		$stmt = $this->conn->prepare($query);
		
		foreach($values as $key => $val)
		{
			$stmt->bindValue(":".$key, $val);
		}
	
		$this->setQuery($query);
		return $stmt->execute();
	}
	
	function doDelete()
	{
		$query = "DELETE FROM " . $this->myTable;
		$values = array();
		if(count($this->wheres) > 0)
		{
			$query .= " WHERE ";
			$sep = "";
			foreach($this->wheres as $whereObj)
			{
				$query .= $sep . $whereObj->getColumn() ." = ?";
				array_push($values, $whereObj->getValue());
				$sep = " AND ";
			}
		}
		$stmt = $this->conn->prepare($query);
		$this->setQuery($query);
		return $stmt->execute($values);
	}
	
	function doCount($countField)
	{
		$query = "SELECT COUNT(" . $countField . ")" . " FROM " . $this->myTable;
		$values = array();
		if(count($this->wheres) > 0)
		{
			$query .= " WHERE ";
			$sep = "";
			foreach($this->wheres as $whereObj)
			{
				$query .= $sep . $whereObj->getColumn() ." = ?";
				array_push($values, $whereObj->getValue());
				$sep = " AND ";
			}
		}
		$stmt = $this->conn->prepare($query);
		$this->setQuery($query);
		$stmt->execute($values);
		$row = $stmt->fetch();
		return $row[0];		
	}
	
	private function underscorize($var)
	{
		$results = preg_replace("/([A-Z])/"," $1",$var);
		$columnName  = implode("_",explode(" ",strtolower($results)));
		return $columnName;
	}
	
	private function unUnderscorize($var)
	{
		$r = preg_replace("/_/"," $1",$var);
		$t = explode(" ", $r);
		$propertyName = array_shift($t);
		$propertyName .= implode("",array_map("ucfirst",$t));
		return $propertyName;
	}
	
	private function &getNameClassMapping($clazz)
	{
		$map = array();
		$reflector = new ReflectionClass($clazz);
		if(strlen(get_parent_class($clazz)) != 0)
		{
			$parent = new ReflectionClass(get_parent_class($clazz));
			$props = $parent->getProperties();
			foreach($props as $prop)
			{
				try
				{
					$getMethod = $reflector->getMethod("get".ucfirst($prop->getName()));
					$setMethod = $reflector->getMethod("set".ucfirst($prop->getName()));
					$methodArray = array($getMethod, $setMethod);
					$map[$prop->getName()] = $methodArray;
				}
				catch(ReflectionException $e)
				{
					//do nothing
				}
			}
		}
		$props = $reflector->getProperties();
		foreach($props as $prop)
		{
			try
			{
				$getMethod = $reflector->getMethod("get".ucfirst($prop->getName()));
				$setMethod = $reflector->getMethod("set".ucfirst($prop->getName()));
				$methodArray = array($getMethod, $setMethod);
				$map[$prop->getName()] = $methodArray;
			}
			catch(ReflectionException $e)
			{
				//do nothing
			}
		}
		return $map;
	}	
	public function &where($column, $value)
    {
		//could be of the form table.column
        $x = explode(".", $column);
        if(count($x) == 2)
        {
            $this->addTable(strtolower($x[0]));
			$whereObj = new Where($this->underscorize($column),$value);
			array_push($this->wheres,$whereObj);
        }
        else
        {
			$whereObj = new Where($this->myTable . "." . $this->underscorize($column),$value);
            array_push($this->wheres,$whereObj);
        }
		return $this;
    }
	
	public function &set($column, $value)
	{
		//could be of the form table.column
        $x = explode(".", $column);
        if(count($x) == 2)
        {
            $this->addTable(strtolower($x[0]));
			$setObj = new Set($this->underscorize($column),$value);
			array_push($this->sets,$setObj);
        }
        else
        {
			$setObj = new Set($this->myTable . "." . $this->underscorize($column),$value);
            array_push($this->sets,$setObj);
        }
		return $this;
	}
	
	public function &orderBy($column, $order = "DESC")
	{
		//could be of the form table.column
        $x = explode(".", $column);
        if(count($x) == 2)
        {
            $this->addTable(strtolower($x[0]));
			$orderObj = new Order($this->underscorize($column),$order);
			array_push($this->orders,$orderObj);
        }
        else
        {
			$orderObj = new Order($this->myTable . "." . $this->underscorize($column),$order);
            array_push($this->orders,$orderObj);
        }
		return $this;
	}

    public function &butOnly($column)
    {
        //could be of the form table.column
        $x = explode(".", $column);
        if(count($x) == 2)
        {
            $this->addTable(strtolower($x[0]));
            $this->addButOnly(strtolower($x[0]) . "." . $this->underscorize($column));
        }
        else
        {
            $this->addButOnly($this->myTable . "." . $this->underscorize($column));
        }
		return $this;
    }
	public function &butNot($column)
    {
        //could be of the form table.column
        $x = explode(".", $column);
        if(count($x) == 2)
        {
            $this->addTable(strtolower($x[0]));
            $this->addButNot(strtolower($x[0]) . "." . $this->underscorize($column));
        }
        else
        {
            $this->addButNot($this->myTable . "." . $this->underscorize($column));
        }
		return $this;
    }
	
	private function addButNot($column)
	{
		if(!in_array($column, $this->butNot))
		{
			array_push($this->butNot, $column);
		}
	}
	
	private function addButOnly($column)
	{
		if(!in_array($column, $this->butOnly))
		{
			array_push($this->butOnly, $column);
		}
	}
    private function addTable($table)
    {
        if(!in_array($table,$this->tables))
        {
            array_push($this->tables,$table);
        }
    }

    private function addColumn($column)
    {
        if(!in_array($column, $this->columns))
        {
            array_push($this->columns,$column);
        }
    }
	
	private function setQuery($q)
	{
		$this->myQuery = $q;
	}
	
	public function getQuery()
	{
		return $this->myQuery;
	}
}
?>
