jQuery(document).ready(function($){
	var thumbpress_uploader;

	$(document).on( 'click', '.thumbpress_upload_image_button', function(e) {
		e.preventDefault();
		var clickedButton    = $(this);
		var associatedInput  = clickedButton.siblings('input[type="text"]').first();
		thumbpress_uploader  = wp.media({
			title:    'Choose Image',
			button:   { text: 'Choose Image' },
			multiple: false
		});
		thumbpress_uploader.on('select', function() {
			var attachment = thumbpress_uploader.state().get('selection').first().toJSON();
			associatedInput.val( attachment.url );
			clickedButton.siblings('.thumbpress_remove_image_button').show();
		});
		thumbpress_uploader.open();
	});

	$(document).on( 'click', '.thumbpress_remove_image_button', function(e) {
		e.preventDefault();
		var clickedButton = $(this);
		clickedButton.siblings('input[type="text"]').first().val('');
		clickedButton.hide();
	});
});
