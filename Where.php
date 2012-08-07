<?php
/**
 * Where
 *
 * @author Ravi Budhu
 */
class Where
{
	private $myColumn;
	private $myValue;

	public function __construct($column, $value)
	{
		$this->myColumn = $column;
		$this->myValue = $value;
	}
	
	public function getColumn()
	{
		return $this->myColumn;
	}
	
	public function getValue()
	{
		return $this->myValue;
	}
}
?>