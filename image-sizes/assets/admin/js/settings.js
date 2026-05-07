jQuery(function($) {

	$('.image-sizes-settings-form').on('reset', function(e){
		e.preventDefault();
		thumbpress_modal();
	    
	    $.ajax({
	    	url: `${THUMBPRESS.api_base}/option`,
	    	type: 'DELETE',
	    	dataType: 'JSON',
	    	data: {
	    		key: $(this).data('option_key')
	    	},
	    	headers: {
	    		'X-WP-Nonce': THUMBPRESS.nonce,
	    	},
	    	success: (resp) => {
	    		console.log('Settings deleted:', resp);
	    		location.reload();
	    	},
	    	error: (err) => {
	    		console.error('Failed to delete settings', err);
				thumbpress_modal(false);
	    	},
	    });
	});

	$('.image-sizes-settings-form').submit(function(e){
		e.preventDefault();
		thumbpress_modal();

		let formData = $(this).serializeArray();
		let data = {};

		// Convert serialized data array into an object
		$.each(formData, function() {
			if (data[this.name]) {
				if (!data[this.name].push) {
					data[this.name] = [data[this.name]];
				}
				data[this.name].push(this.value || '');
			} else {
				data[this.name] = this.value || '';
			}
		});

		$.ajax({
			url: `${THUMBPRESS.api_base}/option`,
			type: 'POST',
			dataType: 'JSON',
			data: {
				key: $(this).data('option_key'),
				value: data
			},
			headers: {
				'X-WP-Nonce': THUMBPRESS.nonce,
			},
			success: (resp) => {
				console.log('Settings saved:', resp);
				thumbpress_modal(false);
			},
			error: (err) => {
				console.error('Failed to save settings', err);
				thumbpress_modal(false);
			},
		});
	});
});