<?php
include 'libraries/adodb/adodb.inc.php';

if(!function_exists ('Load_database'))
{
	function Load_database()
	{
		if(!isset($db))
		{
			$db = ADONewConnection('mysql');
			$db->Connect('10.0.1.4', 'morzo', 'gAgRoWyPLr', 'morzo');
		}
		return $db;
	}
}
?>