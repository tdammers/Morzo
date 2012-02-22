<h2>Edit recipe <?php echo htmlspecialchars($recipe['recipe']['Name']); ?></h2>
<div id="recipe">
	<span class="action" onclick="save_recipe()">Save</span>
	
	<?php
	if($recipe['recipe']['Allow_fraction_output'] == 1)
		$recipe['recipe']['Allow_fraction_output_checked'] = 'checked=checked';
	else
		$recipe['recipe']['Require_full_cycle_checked'] = '';

	if($recipe['recipe']['Require_full_cycle'] == 1)
		$recipe['recipe']['Require_full_cycle_checked'] = 'checked=checked';
	else
		$recipe['recipe']['Require_full_cycle_checked'] = '';

	echo expand_template(
	'<table>
		<tr>
			<td class="label">Name:</td>
			<td><input type="text" id="recipe_name" value="{Name}" /></td>
		</tr>
		<tr>
			<td class="label">Cycle time:</td>
			<td><input type="number" id="cycle_time" value="{Cycle_time}" /></td>
		</tr>
		<tr>
			<td class="label">Allow fraction output:</td>
			<td><input type="checkbox" id="allow_fraction_output" {Allow_fraction_output_checked} /></td>
		</tr>
		<tr>
			<td class="label">Require full cycle:</td>
			<td><input type="checkbox" id="require_full_cycle" {Require_full_cycle_checked} /></td>
		</tr>
	</table>',
	$recipe['recipe']);
	?>
	
	<span class="action" onclick="add_output()">Add output</span>
	<div id="recipe_outputs">
		<?php
		$output_template = '
		<div class="output" id="{ID}">
			<span class="resource action" data-id="{Resource_ID}" onclick="select_output_resource({ID})">{Resource_Name}</span>
			<input class="amount" type="number" value="{Amount}" />
			<span class="action" onclick="remove_output({ID})">Remove</span>
		</div>';

		foreach ($recipe['recipe_outputs'] as $output) {
			echo expand_template($output_template, $output);
		}
		?>
	</div>

	<span class="action" onclick="add_input()">Add input</span>
	<div id="recipe_inputs">
		<?php
		$input_template = '
		<div class="input" id="{ID}">
			<span class="resource action" data-id="{Resource_ID}" onclick="select_input_resource({ID})">{Resource_Name}</span>
			<input class="amount" type="number" value="{Amount}" />
			From nature: <input type="checkbox" class="from_nature" {From_nature_checked} />
			<span class="action" onclick="remove_input({ID})">Remove</span>
		</div>';

		foreach ($recipe['recipe_inputs'] as $input) {
			if($input['From_nature'] == 1)
				$input['From_nature_checked'] = 'checked=checked';
			else
				$input['From_nature_checked'] = '';
			echo expand_template($input_template, $input);
		}
		?>
	</div>

	<div id="resource_select" style="display: none;">
		<select>
			<?php
			$resource_template = '
				<option value="{ID}">{Name}</option>
			';
			foreach ($resources as $resource) {
				echo expand_template($resource_template, $resource);
			}
			?>
		</select>
	</div>
	<div id="new_output" style="display: none;">
	<?php
		echo expand_template($output_template, $recipe['new_output']);
	?>
	</div>
	<div id="new_input" style="display: none;">
	<?php
		echo expand_template($input_template, $recipe['new_input']);
	?>
	</div>
</div>