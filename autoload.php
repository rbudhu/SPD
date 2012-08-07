<?php
/**
 * Autoload
 * Include this file in every page where you execute 
 * a database query using the Query class
 *
 * @author Ravi Budhu
 */
if(!function_exists("__autoload"))
	{
		 function __autoload($class_name) 
			{
				/* Change the following two paths to point to where your PHP classes are */
				if(file_exists($_SERVER["DOCUMENT_ROOT"]."/php/" . $class_name . ".php"))
					require_once($_SERVER["DOCUMENT_ROOT"]."/php/" . $class_name . ".php");
			}
	}
?>