<?php

require_once '../models/database.php';

class Resource_model
{
	public function Get_resources()
	{
		$db = Load_database();
		
		$rs = $db->Execute('
			select ID, Name, Measure, Mass, Volume from Resource
			', array());

		if(!$rs)
		{
			return false;
		}
		
		$resources = array();
		foreach ($rs as $row) {
    		$resources[] = $row;
		}
		return $resources;
	}

	public function Get_resource($resource_id) {
		if($resource_id == -1) {
			return false;
		}
		
		$db = Load_database();
		
		$rs = $db->Execute('
			select ID, Name, Is_natural, Measure, Mass, Volume from Resource where ID = ?
			', array($resource_id));

		if(!$rs || $rs->RecordCount() == 0) {
			return false;
		}

		$rs2 = $db->Execute('
			select C.ID, C.Name from Resource_category RC
			join Category C on C.ID = RC.Category_ID
			 where Resource_ID = ?
			', array($resource_id));
		
		$r = array('resource' => $rs->fields, 'categories' => $rs2->GetArray());
		
		return $r;
	}

	public function Save_resource($resource) {
		$db = Load_database();
		
		if($resource['is_natural'] == 'true')
			$resource['is_natural'] = 1;
		else
			$resource['is_natural'] = 0;

		if($resource['id'] == -1) {
			$args = array(	$resource['name'], 
							$resource['is_natural'],
							$resource['measure'],
							$resource['mass'],
							$resource['volume']
						);

			$rs = $db->Execute('
				insert into Resource (Name, Is_natural, Measure, Mass, Volume) values (?, ?, ?, ?, ?)
				', $args);
		} else {
			$args = array(	$resource['name'], 
							$resource['is_natural'],
							$resource['measure'],
							$resource['mass'],
							$resource['volume'],
							$resource['id']
						);

			$rs = $db->Execute('
				update Resource set Name = ?, Is_natural = ?, Measure = ?, Mass = ?, Volume = ? where ID = ?
				', $args);
		}

		if(!$rs) {
			echo $db->ErrorMsg();
			return false;
		}

		return true;
	}
	
	public function Get_measures() {
		$db = Load_database();
		
		$rs = $db->Execute('
			select ID, Name from Measure
			', array());

		if(!$rs) {
			return false;
		}
		
		return $rs->GetArray();
	}

	public function Add_resource_category($resource_id, $category_id) {
		$db = Load_database();
		$query = 'insert into Resource_category(Resource_id, Category_ID)
		values(?, ?)';
		$array = array($resource_id, $category_id);
		$rs = $db->Execute($query, $array);
		return $rs;
	}

	public function Remove_resource_category($resource_id, $category_id) {
		$db = Load_database();
		$query = 'delete from Resource_category where Resource_ID = ? and Category_ID = ?';
		$array = array($resource_id, $category_id);
		$rs = $db->Execute($query, $array);
		return $rs;
	}
}
?>
