<ul class="selectable" id="recipes">
	<?php
	$template = '<li class="action" id="recipe_{ID}" onclick="show_project_start_form('.$actor_id.', \'{ID}\')">{Name}</li>';
	foreach ($recipe_list as $recipe) {
		echo expand_template($template, $recipe);
	}
	?>
</ul>
<div id="view_recipe"></div>
