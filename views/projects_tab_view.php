<div id="recipe_menu_container">
	<span class="action" id="open_recipe_menu" onclick="toggle_recipe_menu(<?php echo $actor_id;?>);">Start a new project</span>
	<div id="recipe_menu_content" style="display: none;">
		<span class="action" onclick="toggle_recipe_menu();">Close recipe menu</span>
		<?php echo $recipe_selection_view;?>
	</div>
</div>

<div id="projects_feedback"></div>
<div id="projects">
	<table class="project_list">
		<?php
		$row_template = '
			<tr id="project_row_{id}">
				<td>
					<span class="action" onclick="show_project({actor_id}, {id});">{name}</span>
				</td>
				<td class="{active_class}">
					({progress_percent}%)
				</td>
				<td>
					{!Join/Leave}
				</td>
			</tr>';
		foreach ($projects as $project) {
			if($project["Joined"] == 0) {
				$joinleave = '<span class="action" onclick="join_project({actor_id}, {id})">Join</span>';
			} else {
				$joinleave = '<span class="action" onclick="leave_project({actor_id})">Leave</span>';
			}
			if($project["Active"] == 0) {
				$active_class = 'inactive_project';
			} else {
				$active_class = 'active_project';
			}
			$vars = array(
				'Join/Leave' => $joinleave,
				'id' => $project["ID"],
				'name' => $project["Recipe_Name"],
				'progress_percent' => 100 * $project["Progress"] / $project["Cycle_time"],
				'actor_id' => $actor_id,
				'active_class' => $active_class
			);
			echo expand_template($row_template, $vars);
		}
		echo '
			<tr id="project_details_row" style="display: none;">
				<td id="project_details_container" colspan="3" style="max-width: 300px;">
					Here comes the details, later, when it is implemented. Need to do a bit of styling on this table to make things loook alright.
				</td>
			</tr>';
		?>
	</table>
</div>
