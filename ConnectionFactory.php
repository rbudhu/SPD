<?php
/**
 * ConnectionFactory
 *
 * @author Ravi Budhu
 */
class ConnectionFactory
{
    private static $factory;
	
	/* local database connection */ 
	private $dbUser = "";
	private $dbName = "";
	private $dbPass = "";
	
	/* remote database connection */
	//private $dbUser = "";
	//private $dbName = "";
	//private $dbPass = "";
	
	private $db;
	
    public static function getFactory()
    {
        if (!self::$factory)
            self::$factory = new ConnectionFactory();
        return self::$factory;
    }

    public function getConnection() 
	{
        if (!$this->db)
		{
			try
			{
				$this->db = new PDO("mysql:host=localhost;dbname=".$this->dbName, $this->dbUser, $this->dbPass, array(
						  PDO::ATTR_PERSISTENT => true));
			}
			catch (PDOException $e) 
			{
				print "Error!: " . $e->getMessage() . "<br/>";
    			die();
			}
		}
		return $this->db;
    }
}
?>