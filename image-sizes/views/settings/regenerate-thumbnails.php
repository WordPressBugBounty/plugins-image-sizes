<p class="image_sizes-desc"><?php _e( 'Since you updated the thumbnails to be generated, images you\'ll upload in the future will generate thumbnails based on your settings. But, what about the ones you already uploaded? Click the button below and it\'ll regenerate thumbnails of your existing images.', 'image-sizes' ); ?></p>

<div id="image_sizes-regen-wrap" class="image_sizes-regen-thumbs-panel">
	<!-- <p> -->
		<label for="image-sizes_regenerate-thumbs-limit"><?php _e( 'Number of images to process per request:', 'image-sizes' ) ?></label>
		<input type="number" class="cx-field cx-field-number" id="image-sizes_regenerate-thumbs-limit" name="regen-thumbs-limit" value="10" placeholder="<?php _e( 'Images/request. Default is 50', 'image-sizes' ) ?>" required="">
		<!-- <span id="cx-question" title="<?php _e( 'Number of images to process per request. More value means quicker result, but may arise issues on some low configuration servers. Recommended value is 50.', 'image-sizes' ) ?>">🤔</span> -->
	<!-- </p> -->
	<button id="image_sizes-regen-thumbs" class="button button-primary button-hero"><?php _e( 'Regenerate', 'image-sizes' ); ?></button>
</div>
<div id="image_sizes-message" style="display: none;"></div>
