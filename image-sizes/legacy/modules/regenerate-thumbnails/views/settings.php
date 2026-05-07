<?php 

use Codexpert\ThumbPress\Helper;

$status 			= thumbpress_get_last_action_status_by_module_name( 'regenerate-thumbnails' );
$action_id 			= thumbpress_get_last_action_status_by_module_name( 'regenerate-thumbnails', 'action_id' );
$_progress 			= get_option( "thumbpress_regenerate_progress" ) ?? 0;
$processed_count	= get_option( "thumbpress_regenerate_total_processed" ) ?? 0;
$deleted_count 		= get_option( "thumbpress_regenerate_total_deleted" ) ?? 0;
$created_count 		= get_option( "thumbpress_regenerate_total_created" ) ?? 0;
$progress 			= round( $_progress );
$show_failed 		= $show_no_process = $show_no_results = $show_process = 'none';
$message 			= $background_color = '';
$last_schedule_time = get_option( 'thumbpress_regenerate_last_schedule_time' );
$time_format 		= 'g:i a F j, Y';
$completed_time 	= $last_schedule_time ? date_i18n( $time_format, $last_schedule_time ) : '';

if( in_array( $status, [ 'in-progress', 'pending', 'complete' ] ) ) {
	$show_process = 'flex';
}

elseif( $status == 'failed' ) {
	$show_failed = 'flex';
	$background_color = '#F63D3F0D';
	
}else{
	$show_no_process = 'block';
}

if( $status == "complete" && $processed_count == 0 ) {
	$show_no_results = "flex";
	$show_process 	 = 'none';
}

if( in_array( $status, [ 'in-progress', 'pending' ] ) ) {
	$message = __( 'Regenerating Thumbnails in Background', 'image-sizes' );
}

if( $status == 'complete' ) {
	// Translators: %s is the time when the thumbnail regeneration was completed.
	$message = sprintf( __( 'Regenerating Thumbnails in background was completed at %s', 'image-sizes' ), esc_html( $completed_time ) );
}

if ( $status == 'failed' ) {
	$message = __( 'Regenerating Thumbnails in Background Failed.', 'image-sizes' );
}

?>
<div id="cx-message-optimize-images" class="cx-message">
	<img src="<?php echo esc_url( plugins_url( 'vendor/codexpert/plugin/src/assets/img/checked.png', THUMBPRESS ) ); ?>">
</div>
<div class="thumbpress-submenu">
	<div class="thumbpress-actions-wrapper">
		<div id="image_sizes-regen-left" class="thumbpress-actions-left">
			<div id="info-icon" class="info-icon">
				<img class="info-img" src="<?php echo esc_url( plugins_url( 'assets/img/info.png', THUMBPRESS ) ); ?>">
				<p>
					<?php esc_html_e( "Regenerate Thumbnails is a server-dependent feature and the regeneration process may timeout if you process a large number of images. We suggest doing this process in the background to avoid any issues.", 'image-sizes' ); ?>
				</p>
			</div>
			<label for="image-sizes_regenerate-thumbs-limit">
				<?php esc_html_e( 'Number of images to process per request:', 'image-sizes' ); ?>
			</label>
			<input type="number" class="cx-field cx-field-number thumbpress-action-input" id="image-sizes_regenerate-thumbs-limit" name="regen-thumbs-limit" value="10" placeholder="<?php esc_attr_e( 'Images/request. Default is 50', 'image-sizes' ); ?>" required="">
			<div class="thumbpress-buttons-wrapper">
				<button id="image_sizes-regen-thumbs" class="thumbpress-action-now">
					<?php esc_html_e( 'Regenerate Now', 'image-sizes' ); ?>
				</button>
				<button id="image_sizes-schedule-regen-thumbs" class="thumbpress-action-background">
					<?php esc_html_e( 'Regenerate In Background', 'image-sizes' ); ?>
				</button>
			</div>
			<?php 
				$thumbpress_modules = Helper::get_option( 'thumbpress_modules', 'disable-thumbnails' );

				if ( $thumbpress_modules == 'on' ) {
					?>
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'thumbpress' ], admin_url( 'admin.php' ) ) . '#prevent_image_sizes' ); ?>" class="prevent_image_sizes-back button-hero">
						&#10550; <?php esc_html_e( 'Go to Disable Thumbnails Settings', 'image-sizes' ); ?>
					</a>
					<?php 
				}
			?>
			
		</div>
		<div class="thumbpress-actions-right" style="background-color:<?php echo esc_attr( $background_color ); ?>">
			<p class="thumbpress-processs-message" style="display: <?php echo esc_attr( $show_process ); ?>;">
				<?php echo esc_html( $message ); ?>
			</p>
			<div id="thumbpress-action-result" style="display: <?php echo esc_attr( $show_process ); ?>;">
				<div class="thumbpress-progress-content">
					<div class="thumbpress-progressbar" data-content="<?php esc_attr_e( $progress ) ?>" style="--value: <?php esc_attr_e( $progress ) ?>"></div>
				</div>
				<div id="thumbpress-message">
					<p id="cx-processed">
						<span class="dashicons dashicons-yes-alt cx-icon cx-success"></span>
						<?php
						// Translators: %s is the number of images processed, wrapped in a span element.
						printf( __( 'Processed %s images', 'image-sizes' ), '<span id="processed-count">' . esc_html( $processed_count ) . '</span>' );
						?>
					</p>
					<p id="cx-deleted">
						<span class="dashicons dashicons-yes-alt cx-icon cx-success"></span>
						<?php
						// Translators: %s is the number of deleted images, wrapped in a span element.
						printf( __( 'Deleted %s images.', 'image-sizes' ), '<span id="deleted-count">' . esc_html( $deleted_count ) . '</span>' );
						?>
					</p>
					<p id="cx-created">
						<span class="dashicons dashicons-yes-alt cx-icon cx-success"></span>
						<?php
						printf(
							__( 'Created %s images.', 'image-sizes' ),
							'<span id="created-count">' . esc_html( $created_count ) . '</span>'
						);
						?>
					</p>
				</div>
			</div>
			<div class="thumbpress-action-no-process" style="display: <?php esc_attr_e( $show_no_process ); ?>">
				<img src="<?php echo esc_url( plugins_url( 'assets/img/no-action.png', THUMBPRESS ) ); ?>" alt="no-action">
				<p>
					<?php esc_html_e( 'Please click the button to start detecting images.', 'image-sizes' ); ?>
				</p>
			</div>
			<div class="thumbpress-action-no-result" style="display: <?php esc_attr_e( $show_no_results ); ?>">
				<img src="<?php echo esc_url( plugins_url( 'assets/img/no-images.png', THUMBPRESS ) ); ?>" alt="no-action">
				<p>
					<?php esc_html_e( 'You have no images. Please upload images.', 'image-sizes' ); ?>
				</p>
			</div>
			<div class="thumbpress-action-failed" style="display: <?php esc_attr_e( $show_failed ); ?>;">
				<img src="<?php echo esc_url( plugins_url( 'assets/img/failed-action.png', THUMBPRESS ) ); ?>" alt="failed-action">
				<p class='failed-warning'>
					<?php esc_html_e( 'Something went wrong!', 'image-sizes' ); ?>
				</p>
				<p class='failed-message'>
					<?php esc_html_e( 'Your background action failed. Please try again.', 'image-sizes' ); ?>
				</p>
			</div>
			<?php
			?>
		</div>
	</div>
</div>