<!--
/**
 * This script will inspect a particular MySQL table and auto generate a PHP class for it
 * The PHP class is written out to disk in the same directory as this script
 * Tables should be constructed in a certain way. Specifically:
 * Table names should be plural (persons[correct] vs. person vs. people)
 * Columns that are more than two words should have an underscore between the words (ex. first_name)
 * The PHP class generated will have getters and setters for all columns in the table
 * Getters and setters will be camel case depending on the name of the column.  For example, for a column named first_name
 * a getter and setter are created called getFirstName and setFirstName respectively.
 
 * @author Ravi Budhu
 */
-->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Class Generator</title>
   </head>
    <body>
<?php
require_once("ConnectionFactory.php");
function unUnderscorize($var)
{
	$r = preg_replace("/_/"," $1",$var);
	$t = explode(" ", $r);
	$propertyName = array_shift($t);
	$propertyName .= implode("",array_map("ucfirst",$t));
	return $propertyName;
}

function br($fh)
{
	fwrite($fh, "\n");
}

function tab($fh)
{
	fwrite($fh, "\t");
}

if(isset($_POST["generate"]))
{
	$tableName = $_POST["tableName"];
	$className = $_POST["className"];
	$fh = fopen($className.".php", "w");
	fwrite($fh, "<?php");
	br($fh);
	fwrite($fh, "class " . $className);
	br($fh);
	fwrite($fh, "{");
	br($fh);

	$fields = array();
	$conn = ConnectionFactory::getFactory()->getConnection();
	$query = "DESCRIBE " . $tableName;
	$stmt = $conn->prepare($query);
	$res = $stmt->execute();
	while($row = $stmt->fetch(PDO::FETCH_ASSOC))
	{
		$field = unUnderscorize($row["Field"]);
		array_push($fields, $field);
		tab($fh);
		fwrite($fh, "private $". $field.";");
		br($fh);
	}
	br($fh);
	foreach($fields as $field)
	{
		$f = ucwords($field);
		tab($fh);
		fwrite($fh, "public function get".$f."( )");
		br($fh);
		tab($fh);
		fwrite($fh, "{");
		br($fh);
		tab($fh);tab($fh);
		fwrite($fh, "return \$this->".$field.";");
		br($fh);
		tab($fh);
		fwrite($fh, "}");
		br($fh);
		tab($fh);
		fwrite($fh, "public function set".$f."( $" . $field . " )");
		br($fh);
		tab($fh);
		fwrite($fh, "{");
		br($fh);
		tab($fh);tab($fh);
		fwrite($fh, "\$this->".$field . " = $" . $field.";");
		br($fh);
		tab($fh);
		fwrite($fh, "}");
		br($fh);
	}

	fwrite($fh, "}");
	br($fh);
	fwrite($fh,"?>");
	fclose($fh);
	
	print "File: " . $className . ".php written.";
	print "<br />";
}
?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<table>
<tr>
<td> Table name: </td>
<td><input type="text" id="tableName" name="tableName" /></td>
<td> Class name: </td>
<td><input type="text" id="className" name="className" /></td>
</tr>
</table>
<input type="submit" value="Generate" name="generate" />
</form>
</body>
</html>