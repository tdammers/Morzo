function edit_location(id)
{
	$.ajax(
	{
		type: 'POST',
		url: 'world_admin/edit_location',
		data: {
			id: id
		},
		success: function(data)
		{
			if(data !== false)
			{
				$('#edit_location').html(data.data);
			}
		}
	});
}
function add_biome()
{
	$.ajax(
	{
		type: 'POST',
		url: 'world_admin/add_biome',
		data: {
			name: $('#new_biome').val()
		},
		success: function(data)
		{
			if(data !== false)
			{
				$('#biome_list').html(data.data);
			}
		}
	});
}
function add_resource()
{
	$.ajax(
	{
		type: 'POST',
		url: 'world_admin/add_resource',
		data: {
			name: $('#new_resource').val()
		},
		success: function(data)
		{
			if(data !== false)
			{
				$('#resource_list').html(data.data);
			}
		}
	});
}

function toggle_resource(id) {
	var e = $('#'+id);
	if(e.hasClass('selected')) {
		e.removeClass('selected');
	} else {
		e.addClass('selected');
	}
}

function toggle_biome(id) {
	var e = $('#'+id);
	if(e.hasClass('selected') == false) {
		$('#biomes .selected').removeClass('selected');
		e.addClass('selected');
	}
}