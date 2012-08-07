<?php
/**
 * Order
 *
 * @author Ravi Budhu
 */
class Order
{
	private $myColumn;
	private $myOrder;

	public function __construct($column, $order)
	{
		$this->myColumn = $column;
		$this->myOrder = $order;
	}
	
	public function getColumn()
	{
		return $this->myColumn;
	}
	
	public function getOrder()
	{
		return $this->myOrder;
	}
}
?>