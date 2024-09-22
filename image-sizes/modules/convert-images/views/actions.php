<div id="cx-message-convert-images" class="cx-message">
	<img src="<?php echo esc_url( plugins_url( 'vendor/codexpert/plugin/src/assets/img/checked.png', THUMBPRESS ) ); ?>">
</div>
<div>
	<div class="thumbpress-actions-wrapper">
	<div class="thumbpress-actions-left">
		<div id="info-icon" class="info-icon">
			<img class="info-img" src="<?php echo esc_url( plugins_url( 'modules/convert-images/img/info.png', THUMBPRESS ) ); ?>">
			<p><?php echo esc_html__( "Alert! Please note that all the images on your website will be converted to WebP format and you cannot undo this action later. Do not take this action unless you're sure about it. If you want to convert a specific image, you can do it from media library." ); ?></p>
		</div>
		<label for="thumbpress-convert-limit">
			<?php _e( 'Number of images to process per request:', 'image-sizes' ) ?>
		</label>
		<input type="number" class="cx-field cx-field-number thumbpress-action-input" id="thumbpress-convert-limit" name="regen-thumbs-limit" value="10" placeholder="<?php _e( 'Images/request. Default is 50', 'image-sizes' ) ?>" required="">
		<div class="thumbpress-buttons-wrapper">
			<button id="thumbpress-convert-now" class="thumbpress-action-now button-hero button" type="button">
				<?php echo esc_html__( 'Convert Now', 'image-sizes' ); ?>
			</button>
			<button id="thumbpress-convert-background" class="thumbpress-action-background" type="button">
				<?php echo esc_html__( 'Convert in Background', 'image-sizes' ); ?>
			</button>
		</div>
	</div>
	<div class="thumbpress-actions-right">
		<div id="thumbpress-action-now-result" style="display: none;">
			<div class="thumbpress-progress-panel">
				<div class="thumbpress-progress-content" style="width:0%">
					<span>0%</span>
				</div>
			</div>
			<div id="thumbpress-pro-message">
				<p id="cx-processed">
					<span class="dashicons dashicons-yes-alt cx-icon cx-success"></span>
					<?php
					printf(
						__( '%s images converted to WebP.', 'thumbpress-pro' ),
						'<span id="processed-count">0</span>',
					);
					?>
				</p>
			</div>
		</div>
		<div id="thumbpress-action-no-result" style="display: none;">
			<h3>
				<?php _e( 'No PNG, JPEG or JPG images left to convert.', 'thumbpress-pro' ); ?>
			</h3>
		</div>
		<?php
		$status 	= thumbpress_get_last_action_status_by_module_name('convert-images');
		$progress 	= get_option("thumbpress_convert_progress");
		$title 		= $text = $status_class = $progress_bar = '';

		if( $status == 'in-progress' || $status == 'pending' ) {
			$title 			= __( 'PROCESSING...', 'image-sizes' );
			$text 			= __( 'Your images are being converted to WebP format. Please wait.', 'image-sizes' );
			$status_class 	= 'processing';
			$progress_bar 	= "<div class='progress'>
			<div class='bar' style='width: " . intval( $progress ) . "%;'>
			<p class='percent'>" . intval( $progress ) . "%</p>
			</div>
			</div>";
		} elseif( $status == 'failed' ) {
			$title 			= __( 'FAILED', 'image-sizes' );
			$text 			= __( 'Image conversion to WebP was unsuccessful. Please try again.', 'image-sizes' );
			$status_class 	= 'failed';
		} elseif( $status == 'complete' && $progress == 100 ) {
			$title 			= __( 'COMPLETED', 'image-sizes' );
			$text 			= __( 'Your images have been successfully converted to WebP.', 'image-sizes' );
			$status_class 	= 'complete';
			$progress_bar  	= "<div class='progress'>
			<div class='bar' style='width: " . intval( $progress ) . "%;'>
			<p class='percent'>" . intval( $progress ) . "%</p>
			</div>
			</div>";
		}

		?>
		<div id="thumbress-action-background-result">
			<div id="thumbress-result-image">
				<img src="<?php echo esc_url( plugins_url('modules/convert-images/img/convart-icon.png', THUMBPRESS ) ); ?>">
			</div>
			<?php if ( $title ): ?>
				<h2 class="image_sizes-status <?php echo $status_class; ?>">
					<?php echo esc_html( $title ); ?>
				</h2>
				<?php if ( isset( $progress_bar ) ): ?>
					<?php echo $progress_bar; ?>
				<?php endif; ?>
			<?php endif; ?>
			<p class="image_sizes-desc">
				<?php echo esc_html( $text ); ?>
			</p>
		</div>
	</div>
</div>