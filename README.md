# SPD ("speedy") #
Created by: Ravi Budhu

SPD is a very very simple PHP MySQL database layer for those who do not want to use a framework and want more control over their database back-end and queries.

## Getting Started ##

Clone the repository into a directory on your web server.  Modify the following files as detailed in the files themselves.

<pre>autoload.php</pre>
<pre>Query.php</pre>
<pre>ConnectionFactory.php</pre>

## MySQL Database Configuration ##

SPD relies on the fact that your tables and columns are named a certain way.  
* Table names should be plural.  For example "persons" instead of "people" or "person."  
* Column names made up of multiple words should separate the words with an underscore (_).  For example, "first_name" or "last_name."
* That's it!

## Creating PHP Classes #

Navigate to classgen.php using your browser.  Type in the name of the table you wish to generate a PHP class for and the name of the class.      
Typically the name of the class should be the singular capitalized version of the database table name.  For example, if the database table name is
"persons" the class name should be "Person."   
Click the Generate button to create the class.  The class is written to a file in the same directory as the classgen.php script.  
The PHP class generated will have getters and setters for all columns in the table.    
Getters and setters will be camel case depending on the name of the column.  For example, for a column named first_name a getter and setter are created called getFirstName and setFirstName respectively.

## Executing Queries ##

After you have generated the PHP classes that map to your database tables, you are ready to start quering.  
Near the top of every PHP page in which you are going to execute a database query, you must include this line:
<pre>include_once($_SERVER["DOCUMENT_ROOT"] . "/php/autoload.php");</pre>
Assuming the autoload.php script is located in the "php" subdirectory under your DOCUMENT_ROOT.

### Selecting ###

Create a new Query object with the name of the table you are selecting from.
<code>$q = new Query('persons');</code>

The Query object has some useful select methods on it which can be chained:

<code>select(class name)</code>  
<code>$q->select('Person');</code>

<code>execute()</code>  
<code>$persons = $q->select('Person')->execute();</code>  
Execute executes the query and returns an array of objects

<code>where(column name, column value)</code>  
<code>$q->select('Person')->where('firstName', 'Joe')->execute();</code>   
Notice how column names are camel case.  The generated SQL will turn the camel casing into underscores.  So, firstName becomes first_name.  

<code>butNot(column name)</code>  
<code>$persons = $q->select('Person')->where('firstName', 'Joe')->butNot('lastName')->execute();</code>  
This will select all of the rows where first_name is Joe, but will not fetch the last_name column.   Can be chained like so:   
<code>$persons = $q->select('Person')->where('firstName', 'Joe')->butNot('lastName')->butNot('middleName')->execute();</code>    

<code>butOnly(column name)</code>  
<code>$persons = $q->select('Person')->where('firstName', 'Joe')->butOnly('lastName')->execute();</code>   
This will select all of the rows where first_name is Joe, but will only fetch the last_name column.   Can be chained like so:   
<code>$persons = $q->select('Person')->where('firstName', 'Joe')->butOnly('lastName')->butOnly('middleName')->execute();</code>    

<code>orderBy(column name)</code>
<code>$persons = $q->select('Person')->where('firstName', 'Joe')->orderBy('lastName')->execute();</code>


### Inserting ###

Create a new Query object with the name of the table you want to insert into
<code>$q = new Query('persons');</code>

Create the PHP object, derived from classgen.php, representing the data you want to insert.

<code>
$person = new Person();
$person->setFirstName('Jane');
$person->setLastName('Doe');
</code>

Insert it like so:

<code>$q->insert($person)->execute();</code>

If your table has an auto-increment column (like an id column), then do something like so:

<code>$q->insert($person)->butNot('id')->execute();</code>

The insert method also supports the butNot and butOnly chained methods.

### Updating ###

Create a new Query object with the name of the table you want to update
<code>$q = new Query('persons');</code>


The Query object has some useful update methods on it which can be chained:

<code>update(object)</code>  
<code>$q->update($person)->execute();</code>   

<code>set(column name, column value)</code>  
<code>$q->update($person)->set('firstName', 'Jane')->execute();</code> 

The update query also supports the where, butNot, and butOnly chained methods.

<code>$q->update($person)->set('firstName', 'Jane')->where('lastName', 'Doe')->butNot('id')->execute();</code>

### Deleting ###

Create a new Query object with the name of the table you want to delete from
<code>$q = new Query('persons');</code>

The Query object has some useful delete methods on it which can be chained:

<code>delete()</code>   
<code>$q->delete()->execute()</code>

The delete query supports the where method.

<code>$q->delete()->where('firstName', 'Joe')->where('lastName', 'Blo')->execute();</code>
