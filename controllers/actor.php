<?php
require_once "../controllers/controller.php";

class Actor extends Controller
{
	public function Request_actor()
	{
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}
		$this->Load_model('Actor_model');
		$r = $this->Actor_model->Request_actor($_SESSION['userid']);
		
		echo json_encode($r);
	}
	
	public function Show_actor($actor_id, $tab = 'events')
	{
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			header("Location: /front");
			return;
		}
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			die("This is not the actor you are looking for.");
		}

		$this->Load_model("Travel_model");

		$actor = $this->Actor_model->Get_actor($actor_id);
		if($actor['Name'] == NULL)
		{
			$actor['Name'] = 'Unnamed actor';
		}
		if($actor['Location'] == NULL)
		{
			$actor['Location'] = 'Unnamed location';
		}
		
		$this->Load_controller('Update');
		$time = $this->Update->Get_time_units($actor['Time']);
		
		$tab_view = '';
		if($tab == 'locations') {
			$travel = $this->Travel_model->Get_travel_info($actor_id);
			if($travel) {
				if(!$travel['OriginName'])
					$travel['OriginName'] = 'Unnamed location';
				if(!$travel['DestinationName'])
					$travel['DestinationName'] = 'Unnamed location';
			}
			$locations = $this->Get_neighbouring_locations($actor_id);
			$tab_view = $this->Load_view('locations_tab_view', array('locations' => $locations, 'travel' => $travel, 'actor' => $actor, 'actor_id' => $actor_id), true);
		} elseif ($tab == 'people') {
			$actors = $this->Actor_model->Get_visible_actors($actor_id);
			$tab_view = $this->Load_view('people_tab_view', array('actors' => $actors, 'actor_id' => $actor_id), true);
		} elseif ($tab == 'events') {
			$this->Load_model("Event_model");
			$events = $this->Event_model->Get_events($actor_id);
			$this->Load_model("Language_model");
			foreach ($events as $key => $event) {
				$events[$key]['Time_values'] = $this->Update->Get_time_units($event['Ingame_time']);
				$events[$key]['Text'] = $this->Language_model->Translate_event($events[$key], $actor_id);
			}
			$tab_view = $this->Load_view('events_tab_view', array('events' => $events, 'actor_id' => $actor_id), true);
		} elseif ($tab == 'resources') {
			$this->Load_model("Location_model");
			$resources = $this->Location_model->Get_location_resources($actor['Location_ID']);
			$this->Load_model("Species_model");
			$species = $this->Species_model->Get_location_species($actor['Location_ID']);
			$tab_view = $this->Load_view('resources_tab_view', 
										array(
											'resources' => $resources, 
											'species' => $species, 
											'actor_id' => $actor_id
											), 
										true);
		} elseif ($tab == 'projects') {
			$this->Load_model("Project_model");
			$this->Load_model("Species_model");
			$projects = $this->Project_model->Get_projects($actor_id);
			$hunts = $this->Species_model->Get_hunts($actor_id);
			$recipe_list = $this->Project_model->Get_recipes_without_nature_resource();
			$recipe_selection_view = $this->Load_view('recipe_selection_view', array('recipe_list' => $recipe_list, 'actor_id' => $actor_id), true);
			$tab_view = $this->Load_view('projects_tab_view', array('hunts' => $hunts, 'projects' => $projects, 'actor_id' => $actor_id, 'recipe_selection_view' => $recipe_selection_view), true);
		} elseif ($tab == 'inventory') {
			$actor_inventory = $this->Actor_model->Get_actor_inventory($actor_id);
			$location_inventory = $this->Actor_model->Get_location_inventory($actor_id);
			$tab_view = $this->Load_view('inventory_tab_view', array('actor_inventory' => $actor_inventory, 'location_inventory' => $location_inventory, 'actor_id' => $actor_id), true);
		}
		
		$this->Load_view('actor_view', array('tab' => $tab, 'actor_id' => $actor_id, 'tab_view' => $tab_view, 'time' => $time, 'actor' => $actor), false);
	}
	
	public function Change_actor_name()
	{
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}
		$actor_id = $_POST['actor'];
		$named_actor_id = $_POST['named_actor'];
		$new_name = $_POST['name'];
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
			return;
		}
		$r = $this->Actor_model->Change_actor_name($actor_id, $named_actor_id, $new_name);
		if($r == false) {
			echo json_encode(array('success' => false, 'reason' => 'Could not change actor name'));
			return;
		}
		else {
			if(strlen($new_name) == 0) {
				echo json_encode(array('success' => true, 'data' => 'Unnamed actor'));
			}
			else {
				echo json_encode(array('success' => true, 'data' => $new_name));
			}
		}
	}

	public function Speak() {
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}
		$actor_id = $_POST['actor'];
		$message = $_POST['message'];
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}
		
		$this->Load_model('Event_model');
		$r = $this->Event_model->Save_event('{LNG_Actor_said}', $actor_id, NULL, $message);
		if($r == false) {
			echo json_encode(array('success' => false, 'reason' => 'Could not save your message'));
			return;
		}
		else {
			echo json_encode(array('success' => true));
		}
	}
	
	function Natural_resource_dialog() {
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}

		$this->Load_model('Project_model');
		
		$actor_id = $_POST['actor_id'];
		$resource_id = $_POST['resource'];

		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
			return;
		}

		$recipe_list = $this->Project_model->Get_recipes_with_nature_resource($actor_id, $resource_id);

		$recipe_selection_view = $this->Load_view('recipe_selection_view', array('recipe_list' => $recipe_list, 'actor_id' => $actor_id), true);
		
		echo json_encode(array('success' => true, 'data' => $recipe_selection_view));
	}
	
	function Start_project_form()
	{
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}
		
		$recipe_id = $_POST['recipe_id'];
		$actor_id = $_POST['actor_id'];

		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$this->Load_model('Project_model');
		$recipe = $this->Project_model->Get_recipe($recipe_id);
		
		$start_project_view = $this->Load_view('start_project_view', array('recipe' => $recipe, 'actor_id' => $actor_id), true);

		echo json_encode(array('success' => true, 'data' => $start_project_view));
	}

	function Start_project()
	{
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}
		
		$recipe_id = $_POST['recipe_id'];
		$actor_id = $_POST['actor_id'];

		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$this->Load_model('Project_model');
		$success = $this->Project_model->Start_project($actor_id, $recipe_id, $_POST['supply'] == "true");

		echo json_encode(array('success' => $success));
	}

	function Join_project()
	{
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}
		
		$project_id = $_POST['project_id'];
		$actor_id = $_POST['actor_id'];

		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$this->Load_model('Project_model');
		$success = $this->Project_model->Join_project($actor_id, $project_id);

		echo json_encode(array('success' => $success));
	}

	function Leave_project()
	{
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}
		
		$actor_id = $_POST['actor_id'];

		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$this->Load_model('Project_model');
		$success = $this->Project_model->Leave_project($actor_id);

		echo json_encode(array('success' => $success));
	}

	private function Get_neighbouring_locations($actor_id)
	{
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			return;
		}
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			die("This is not the actor you are looking for.");
		}
		$this->Load_model("Location_model");
		$locations = $this->Location_model->Get_neighbouring_locations($actor_id);
		$east = false;
		$west = false;
		$north = false;
		$south = false;
		foreach ($locations as &$location) {
			if($location['x'] == 1 && $location['y'] == 0)
				$east = true;
			if($location['x'] == -1 && $location['y'] == 0)
				$west = true;
			if($location['x'] == 0 && $location['y'] == 1)
				$south = true;
			if($location['x'] == 0 && $location['y'] == -1)
				$north = true;
		}

		if(!$east)
		{
    		$locations[] = array(
    			'ID' => 'east',
    			'x' => 1,
    			'y' => 0,
    			'Name' => 'Unnamed location'
    		);
		}
		if(!$west)
		{
    		$locations[] = array(
    			'ID' => 'west',
    			'x' => -1,
    			'y' => 0,
    			'Name' => 'Unnamed location'
    		);
		}
		if(!$south)
		{
    		$locations[] = array(
    			'ID' => 'south',
    			'x' => 0,
    			'y' => 1,
    			'Name' => 'Unnamed location'
    		);
		}
		if(!$north)
		{
    		$locations[] = array(
    			'ID' => 'north',
    			'x' => 0,
    			'y' => -1,
    			'Name' => 'Unnamed location'
    		);
		}

		foreach ($locations as &$location) {
			if(!$location['Name'])
				$location['Name'] = 'Unnamed location';
			$location['Direction'] = 90+rad2deg(atan2($location['y'], $location['x']));
			if($location['Direction'] < 0)   $location['Direction'] += 360;
			if($location['Direction'] > 360) $location['Direction'] -= 360;
			if($location['Direction'] < 22.5 || $location['Direction'] >= 337.5)
				$location['Compass'] = 'N';
			else if($location['Direction'] < 22.5+45)
				$location['Compass'] = 'NE';
			else if($location['Direction'] < 22.5+90)
				$location['Compass'] = 'E';
			else if($location['Direction'] < 22.5+135)
				$location['Compass'] = 'SE';
			else if($location['Direction'] < 22.5+180)
				$location['Compass'] = 'S';
			else if($location['Direction'] < 22.5+225)
				$location['Compass'] = 'SW';
			else if($location['Direction'] < 22.5+270)
				$location['Compass'] = 'W';
			else if($location['Direction'] < 22.5+315)
				$location['Compass'] = 'NW';
		}
		function compare_direction($a, $b)
		{
			return $a['Direction'] > $b['Direction'];
		}
		unset($location);
		usort($locations, 'compare_direction');
		return $locations;
	}
	
	public function Point_at_actor() {
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}
		$actor_id = $_POST['actor_id'];
		$pointee_id = $_POST['pointee_id'];
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}
		
		$this->Load_model('Event_model');
		$r = $this->Event_model->Save_event('{LNG_Actor_pointed}',$actor_id, $pointee_id);
		if($r == false) {
			echo json_encode(array('success' => false, 'reason' => 'Could not save your message'));
			return;
		}
		else {
			echo json_encode(array('success' => true));
		}
	}

	public function Attack_actor() {
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}
		$actor_id = $_POST['actor_id'];
		$attacked_actor_id = $_POST['attacked_actor_id'];
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}
		
		$this->Load_model('Event_model');
		$r = $this->Event_model->Save_event('{LNG_Actor_attacked}',$actor_id, $attacked_actor_id);
		if($r == false) {
			echo json_encode(array('success' => false, 'reason' => 'Could not save'));
			return;
		}
		else {
			echo json_encode(array('success' => true));
		}
	}

	public function Whisper() {
		header('Content-type: application/json');
		$this->Load_controller('User');
		if(!$this->User->Logged_in()) {
			echo json_encode(array('success' => false, 'reason' => 'Not logged in'));
			return;
		}
		$actor_id = $_POST['actor_id'];
		$whispree_id = $_POST['whispree_id'];
		$message = $_POST['message'];
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}
		
		$this->Load_model('Event_model');
		$r = $this->Event_model->Save_event('{LNG_Actor_whispered}', $actor_id, $whispree_id, $message, NULL, NULL, true);
		if($r == false) {
			echo json_encode(array('success' => false, 'reason' => 'Could not save your message'));
			return;
		}
		else {
			echo json_encode(array('success' => true));
		}
	}
	
	public function Show_project_details() {
		header('Content-type: application/json');
		$actor_id = $_POST['actor_id'];
		$project_id = $_POST['project_id'];
		
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$this->Load_model('Project_model');
		$project = $this->Project_model->Get_project($project_id, $actor_id);
		$project_details_view = $this->Load_view('project_details_view', array(
									'actor_id' => $actor_id, 
									'project' => $project
								), true);
		
		echo json_encode(array('success' => true, 'data' => $project_details_view));
	}
	
	public function Supply_project() {
		header('Content-type: application/json');
		$actor_id = $_POST['actor_id'];
		$project_id = $_POST['project_id'];
		
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$this->Load_model('Project_model');
		$supply_result = $this->Project_model->Supply_project($project_id, $actor_id);
		
		$project = $this->Project_model->Get_project($project_id, $actor_id);
		$project_details_view = $this->Load_view('project_details_view', array(
									'actor_id' => $actor_id, 
									'project' => $project
								), true);
		
		echo json_encode(array('success' => true, 'data' => $project_details_view));
	}

	public function Cancel_project() {
		header('Content-type: application/json');
		$actor_id = $_POST['actor_id'];
		$project_id = $_POST['project_id'];
		
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$this->Load_model('Project_model');
		$cancel_result = $this->Project_model->Cancel_project($project_id, $actor_id);
		
		echo json_encode(array('success' => $cancel_result));
	}

	public function Drop_resource() {
		header('Content-type: application/json');
		$actor_id = $_POST['actor_id'];
		$resource_id = $_POST['resource_id'];
		$amount = $_POST['amount'];
		
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$drop_result = $this->Actor_model->Drop_resource($actor_id, $resource_id, $amount);
		
		echo json_encode(array('success' => $drop_result));
	}

	public function Pick_up_resource() {
		header('Content-type: application/json');
		$actor_id = $_POST['actor_id'];
		$resource_id = $_POST['resource_id'];
		$amount = $_POST['amount'];
		
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$pick_up_result = $this->Actor_model->Pick_up_resource($actor_id, $resource_id, $amount);
		
		echo json_encode(array('success' => $pick_up_result));
	}

	public function Drop_product() {
		header('Content-type: application/json');
		$actor_id = $_POST['actor_id'];
		$product_id = $_POST['product_id'];
		$amount = $_POST['amount'];
		
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$drop_result = $this->Actor_model->Drop_product($actor_id, $product_id, $amount);

		echo json_encode(array('success' => $drop_result));
	}

	public function Pick_up_product() {
		header('Content-type: application/json');
		$actor_id = $_POST['actor_id'];
		$product_id = $_POST['product_id'];
		$amount = $_POST['amount'];
		
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$pick_up_result = $this->Actor_model->Pick_up_product($actor_id, $product_id, $amount);
		
		echo json_encode(array('success' => $pick_up_result));
	}

	public function Start_hunt() {
		header('Content-type: application/json');
		$actor_id = $_POST['actor_id'];
		$hours = $_POST['hours'];
		$species = $_POST['species'];
		
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$this->Load_model('species_model');
		$result = $this->species_model->Start_hunt($actor_id, $hours, $species);

		echo json_encode($result);
	}

	public function Join_hunt() {
		header('Content-type: application/json');
		$actor_id = $_POST['actor_id'];
		$hunt_id = $_POST['hunt_id'];
		
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$this->Load_model('species_model');
		$result = $this->species_model->Join_hunt($actor_id, $hunt_id);

		echo json_encode($result);
	}

	public function Leave_hunt() {
		header('Content-type: application/json');
		$actor_id = $_POST['actor_id'];
		
		$this->Load_model('Actor_model');
		if(!$this->Actor_model->User_owns_actor($_SESSION['userid'], $actor_id)) {
			echo json_encode(array('success' => false, 'reason' => 'Not your actor'));
		}

		$this->Load_model('species_model');
		$result = $this->species_model->Leave_hunt($actor_id);

		echo json_encode($result);
	}
}
