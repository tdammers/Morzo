<h2>Edit product <?php echo htmlspecialchars($product['Name']); ?></h2>
<div id="product">
	<?php
	$categorieshtml = '';
	foreach($categories as $category) {
		$categorieshtml .= expand_template(
			'<span style="margin-right: 5px;">
				{Name} 
				<span class="action" onclick="remove_product_category({ID})">X</span>
			</span>',
			$category
		);
	}
	$categorymenuhtml = '<select id="product_category_select">';
	foreach($category_list as $category) {
		$categorymenuhtml .= expand_template(
			'<option value="{ID}">{Name}</option>',
			$category
		);
	}
	$categorymenuhtml .= '</select>
	<span class="action" onclick="add_product_category()">Add</span>
	';
		
	$product['categorieshtml'] = $categorieshtml;
	$product['categorymenuhtml'] = $categorymenuhtml;
	echo expand_template(
	'<table>
		<tr>
			<td class="label">Name:</td>
			<td><input type="text" id="product_name" value="{Name}" /></td>
		</tr>
		<tr>
			<td class="label">Mass (gram):</td>
			<td><input type="number" id="mass" value="{Mass}" /></td>
		</tr>
		<tr>
			<td class="label">Volume (litre):</td>
			<td><input type="number" id="volume" value="{Volume}" /></td>
		</tr>
		<tr>
			<td class="label">Rot rate:</td>
			<td><input type="number" id="rot_rate" value="{Rot_rate}" /></td>
		</tr>
		<tr>
			<td class="label">Categories:</td>
			<td>{!categorieshtml}</td>
		</tr>
		<tr>
			<td class="label">Add category:</td>
			<td>{!categorymenuhtml}</td>
		</tr>
	</table>',
	$product);
	?>
	<span class="action" style="float: right;" onclick="save_product()">Save</span>
</div>
</div>
