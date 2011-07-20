<?php

include 'models/database.php';

if(!class_exists('User_model'))
{
class User_model
{
	public function RegisterQueue($email)
	{
		$db = Load_database();

		$rs = $db->Execute('SELECT ID FROM RegistrationQueue WHERE Email = ?', array($email));
		if(!$rs)
		{
			return false;
		}
		if($rs->RecordCount()>0)
		{
			return 'Duplicate';
		}
		$rs = $db->Execute('INSERT INTO RegistrationQueue (Email) VALUES (?)', array($email));
		if(!$rs)
		{
			return false;
		}
		
		return 'Registered';
	}
	
	public function Register($username, $password)
	{
		$salt = '8s7gh2W9WQ';
		$hashed = hash ("sha256", $password);
		$hashed = hash ("sha256", $hashed.$salt);
		$hashed = hash ("sha256", $hashed.$username);

		$db = Load_database();
		$rs = $db->Execute('INSERT INTO User (Username, Password) VALUES (?, ?)', array($username, $hashed));
		if(!$rs)
		{
			return false;
		}
		
		return 'Registered';
	}
	
	public function Login($username, $password)
	{
//		echo 'Login model';
		//get row from database
		$db = Load_database();

		$rs = $db->Execute('SELECT ID, Password FROM User WHERE Username = ?', array($username));
		if(!$rs)
		{
			return 'Query failed';
		}
		if($rs->RecordCount()!=1)
		{
			return 'Not found';
		}
		
		//hash
		$salt = '8s7gh2W9WQ';
		$hashed = hash ("sha256", $password);
		$hashed = hash ("sha256", $hashed.$salt);
		$hashed = hash ("sha256", $hashed.$username);
		if($rs->fields['Password'] != $hashed)
		{
			return 'Wrong password';
		}

		//login
		
		return $rs->fields['ID'];
	}

	public function Login_openid($openid)
	{
		$db = Load_database();

		$query = '
			SELECT
				u.ID,
				u.Username
			FROM User u
			JOIN User_openID uo ON uo.UserID = u.ID
			WHERE uo.OpenID = ?';
		$rs = $db->Execute($query, array($openid));
		if(!$rs)
		{
			return 'Query failed';
		}
		if($rs->RecordCount()!=1)
		{
			return 'Not found';
		}
		
		return array(
			'ID' => $rs->fields['ID'],
			'Username' => $rs->fields['Username'],
		);
	}

	public function Create_user_openid($username, $openid)
	{
		$db = Load_database();
		
		$db->StartTrans();
		$query = 'INSERT INTO User (Username) VALUES(?)';
		$rs = $db->Execute($query, array($username));
		if(!$rs) {
			$db->FailTrans();
			$db->CompleteTrans();
			return array(
				'success' => false,
				'reason' => $db->ErrorMsg(),
			);
		}
		$query = 'SELECT ID FROM User WHERE Username = ?';
		$rs = $db->Execute($query, array($username));
		if(!$rs) {
			$db->FailTrans();
			$db->CompleteTrans();
			return array(
				'success' => false,
				'reason' => $db->ErrorMsg(),
			);
		}
		if($rs->RecordCount()!=1) {
			$db->FailTrans();
			$db->CompleteTrans();
			return array(
				'success' => false,
				'reason' => 'Could not find user row, weird.',
			);
		}
		$userid = $rs->fields['ID'];

		$query = 'INSERT INTO User_openID (OpenID, UserID) VALUES(?, ?)';
		$rs = $db->Execute($query, array($openid, $userid));
		if(!$rs) {
			$db->FailTrans();
			$db->CompleteTrans();
			return array(
				'success' => false,
				'reason' => $db->ErrorMsg(),
			);
		}
		
		$db->CompleteTrans();
		return array(
			'success' => true,
			'ID' => $userid
		);
	}

	public function User_has_access($user_id, $accessname)
	{
		$db = Load_database();

		$rs = $db->Execute('
			select U.ID
			from User_access UA
			join User U on U.ID = UA.User_ID
			join Access A on A.ID = UA.Access_ID
			where U.ID = ? and A.Accessname = ?
			', array($user_id, $accessname));
		if(!$rs)
		{
			return false;
		}
		if($rs->RecordCount() == 1)
		{
			return true;
		}
		return false;
	}
	
	public function Request_actor($user_id)
	{
		$db = Load_database();

		$rs = $db->Execute('
			update Actor A
			set A.User_ID = ?
			where A.User_ID is null and A.Inhabitable = true
			order by A.ID asc
			limit 1
			', array($user_id));
		
		if(!$rs)
		{
			return false;
		}
		if($db->Affected_Rows() == 1)
		{
			return true;
		}

		return false;
	}
	
	public function Get_actors($user_id)
	{
		$db = Load_database();

		$rs = $db->Execute('
			select A.ID
			from Actor A
			where A.User_ID = ?
			', array($user_id));
		
		if(!$rs)
		{
			return false;
		}
		$actors = array();
		foreach ($rs as $row) {
    		$actors[] = $row['ID'];
		}
		return $actors;
	}

	public function Get_actor($actor_id)
	{
		$db = Load_database();

		$rs = $db->Execute('
			select AN.Name as Name, LN.Name as Location, A.Location_ID as Location_ID
			from Actor A
			left join Actor_name AN on A.ID = AN.Actor_ID and A.ID = AN.Named_actor_ID
			left join Location_name LN on A.ID = LN.Actor_ID and A.Location_ID = LN.Location_ID
			where A.ID = ?
			', array($actor_id));
		
		if(!$rs)
		{
			return false;
		}
		return Array(
			'Name' => $rs->fields['Name'], 
			'Location' => $rs->fields['Location'], 
			'Location_ID' => $rs->fields['Location_ID']
		);
	}
	
	public function Change_location_name($actor_ID, $location_ID, $new_name)
	{
		$db = Load_database();

		$rs = $db->Execute('
			insert into Location_name (Name, Actor_ID, Location_ID) values(?, ?, ?)
			on duplicate key update Name = ?
			', array($new_name, $actor_ID, $location_ID, $new_name));
		
		if(!$rs)
		{
			return false;
		}
		return true;
	}

	public function Change_actor_name($actor_ID, $named_actor_ID, $new_name)
	{
		$db = Load_database();

		$rs = $db->Execute('
			update Actor_name set name = ?
			where Actor_ID = ? and Named_actor_ID = ?
			', array($new_name, $actor_ID, $named_actor_ID));
		
		if(!$rs)
		{
			return false;
		}
		return true;
	}
	
	public function Get_neighbouring_locations($actor_ID)
	{
		$db = Load_database();

		$rs = $db->Execute('
			select L.x, L.y from Location L
			join Actor A on A.Location_ID = L.ID
			where A.ID = ?
			', array($actor_ID));
		
		if(!$rs)
		{
			return false;
		}

		$x = $rs->fields['x'];
		$y = $rs->fields['y'];
		
		$rs = $db->Execute('
			select L.ID, L.x, L.y, LN.Name as Name from Location L
			left join (
				select Name, ILN.Location_ID from Location_name ILN
				join Actor A on ILN.Actor_ID = A.ID
				where A.ID = ?
				) LN on L.ID = LN.Location_ID
			where L.x >= ? and L.y >= ? and L.x <= ? and L.y <= ?
			', array($actor_ID, $x-1, $y-1, $x+1, $y+1));

		if(!$rs)
		{
			return false;
		}
		
		$locations = array();
		foreach ($rs as $row) {
			if($row['x'] == $x && $row['y'] == $y)
				continue;
    		$locations[] = array(
    			'ID' => $row['ID'],
    			'x' => $row['x'] - $x,
    			'y' => $row['y'] - $y,
    			'Name' => $row['Name']
    		);
		}

		return $locations;
	}
	
	public function Create_location($actor_id, $direction)
	{
		$db = Load_database();

		$rs = $db->Execute('
			select L.x, L.y from Location L
			join Actor A on A.Location_ID = L.ID
			where A.ID = ?
			', array($actor_id));
		
		if(!$rs)
		{
			return false;
		}

		$x = $rs->fields['x'];
		$y = $rs->fields['y'];
		if($direction == "east")
			$x++;
		else if($direction == "west")
			$x--;
		else if($direction == "south")
			$y++;
		else if($direction == "north")
			$y--;
		else
			return false;

		$rs = $db->Execute('
			insert into Location (x, y) values(?, ?)
			', array($x, $y));

		if(!$rs)
		{
			return false;
		}
		
		$rs = $db->Execute('
			select ID from Location where x = ? and y = ?
			', array($x, $y));

		if(!$rs)
		{
			return false;
		}

		return $rs->fields['ID'];
	}
}
}
?>
