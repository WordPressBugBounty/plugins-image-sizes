<?php
namespace Codexpert\ThumbPress\Modules;

use Codexpert\ThumbPress\Helper;
use Codexpert\Plugin\Base;
use Codexpert\Plugin\Settings as Settings_API;

class Convert_Images extends Base {
	public $plugin;
	public $slug;
	public $version;
	public $id = 'convert-images';

	/**
	 * Constructor
	 */
	public function __construct() {

		require_once( __DIR__ . '/inc/functions.php' );

		$this->slug		= 'image-sizes';
		$this->version	= '5.8.7';

		$this->action( 'plugins_loaded', 'init_menu', 11 );
		$this->action( 'admin_enqueue_scripts', 'enqueue_scripts' );
		$this->filter( 'wp_handle_upload', 'convert_image_on_upload' );
		$this->filter( 'attachment_fields_to_edit', 'display_convert_image_btn', 10, 2 );
		$this->action( 'thumbpress_convert_all_image', 'convert_all_image' );

		// Ajax Hooks
		$this->priv( 'thumbpress_convert_single_image', 'convert_single_image' );
		$this->priv( 'thumbpress_schedule_image_conversion', 'schedule_image_conversion' );
		$this->priv( 'thumbpress_convert_images', 'convert_images' );

		// Stop regenerating thumbnails
		$this->filter( 'intermediate_image_sizes_advanced', 'image-sizes' );
		$this->filter( 'big_image_size_threshold', 'big_image_size', 10, 1 );
	}

	public function __settings ( $settings ) {
		
		$settings['sections'][ $this->id ] = [
			'id'        => $this->id,
			'label'     => __( 'Convert to WebP', 'image-sizes' ),
			'icon'      => 'dashicons-image-rotate-left',
			'sticky'    => false,
			'fields'    => [
				[
					'id'       => 'convert-img-on-upload',
					'label'    => __( 'Convert Image on Upload', 'image-sizes' ),
					'desc'     => __( 'Enable this if you want to convert your image to webp on upload.', 'image-sizes' ),
					'type'     => 'switch',
					'disabled' => false,
				],
				[
					'id'       => 'convert-img-one-by-one',
					'label'    => __( 'Single Image Conversion', 'image-sizes' ),
					'desc'     => __( 'Enable this if you want to convert your image to webp one by one.', 'image-sizes' ),
					'type'     => 'switch',
					'disabled' => false,
				],
			],
		];

		return $settings;
	}

	public function init_menu() {
		$actions_menu = [
			'id'            => "thumbpress-convert-images",
			'parent'        => 'thumbpress',
			'label'         => __( 'Convert to WebP', 'image-sizes' ),
			'title'         => __( 'Convert to WebP', 'image-sizes' ),
			'header'        => __( 'Convert to WebP', 'image-sizes' ),
			'sections'      => [
				'thumbpress_convert_images'	=> [
					'id'        => 'thumbpress_convert_images',
					'label'     => __( 'Convert to WebP', 'image-sizes' ),
					'icon'      => 'dashicons-image-rotate-left',
					'sticky'	=> false,
					'fields'	=> [],
					'hide_form'	=> true,
					'template'  => THUMBPRESS_DIR . '/modules/convert-images/views/settings.php',
				]
			]
		];

		new Settings_API( apply_filters( 'thumbpress_convert_images_actions_menu', $actions_menu ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'thumbpress-convert-images', plugins_url( 'assets/js/admin.js', __FILE__ ), [ 'jquery' ], $this->version, true );
	}

	public function convert_image_on_upload( $file_info ) {
		if( ! Helper::get_option( 'convert-images', 'convert-img-on-upload', false ) ) return $file_info;

		if( ! in_array( $file_info['type'], ['image/jpeg', 'image/jpg', 'image/png'] ) ) return $file_info;

		$original_img_path 	= $file_info['file'];
		$webp_file_path 	= thumbpress_convert_image_to_webp( $original_img_path );

		if( ! $webp_file_path ) return $file_info;

		$webp_file_url 	    = thumbpress_generate_webp_file_url( $webp_file_path );

		// delete original image
		unlink( $original_img_path );

		return [
			'file'	=> $webp_file_path,
			'url'	=> $webp_file_url,
			'type'	=> 'image/webp',
		];
	}

	public function display_convert_image_btn( $form_fields, $post ) {
		if( ! in_array( $post->post_mime_type, [ 'image/jpeg', 'image/png', 'image/jpg' ] ) ) return $form_fields;

		if( ! Helper::get_option( 'convert-images', 'convert-img-one-by-one', false ) ) return $form_fields;

		$html = sprintf( '<button id="thumbpress-convert-image" data-image_id="%1s" class="button thumbpress_img_btn" type="button"><b>%2s</b></button>', $post->ID, __( 'Convert Image', 'image-sizes' ) );

		$form_fields[ 'thumbpress_convert_image' ] = [
			'label' => sprintf( '%1s', __( 'Convert to WebP', 'image-sizes' ) ),
			'input' => 'html',
			'html'  => $html,
		];

		return $form_fields;
	}

	public function schedule_image_conversion() {
		$response = [
			'status'	=> 0,
			'message'	=> __( 'Failed', 'image-sizes' ),
		];

		if( ! wp_verify_nonce( $_POST['_wpnonce'], $this->slug ) ) {
			$response['message'] = __( 'Unauthorized', 'image-sizes' );
			wp_send_json_error( $response );
		}
		if ( isset( $_POST['convert_val'] ) ) {
			$convert_img = intval( $_POST['convert_val'] );
			update_option( 'thumbpress_convert_img_val', $convert_img );
		}

		delete_option( 'thumbpress_convert_progress');
		delete_option( 'thumbpress_convert_total_processd');
		delete_option( 'thumbpress_convert_total_converted');
		global $wpdb; 
		$image_types 			 = [ 'image/png', 'image/jpeg', 'image/jpg' ];
		$total_attachments_query = "
			SELECT COUNT(ID)
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type IN ('" . implode( "','", array_map( 'esc_sql', $image_types ) ) . "')
			";
		$total_attachments = $wpdb->get_var( $total_attachments_query );

		update_option( 'thumbpress_now_convert_background_total_images', $total_attachments );

		if( ! $total_attachments ) {
			$response['status'] 	= 2;
			$response['message'] 	= __( 'No images found.', 'image-sizes' );
			wp_send_json( $response );
		}	

		global $wpdb;
		$offset 	= 0;
		$action_id 	= as_schedule_single_action( wp_date( 'U' ) + 5, 'thumbpress_convert_all_image', [ 'offset' => $offset ] );

		thumbpress_add_schedule_log( $this->id, $action_id );

		if( ! $action_id ) {
			$response['message'] = __( 'Failed to schedule image conversion', 'image-sizes' );
			wp_send_json_error( $response );
		}

		$response['status'] 	= 1;
		$response['message'] 	= __( 'Your images are being converted. Please wait...', 'image-sizes' );
		$response['action_id'] 	= $action_id;

		wp_send_json( $response );
	}

	public function convert_all_image( $offset ) {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$image_types 			= [ 'image/png', 'image/jpeg', 'image/jpg' ];
		$limit 					= get_option( 'thumbpress_convert_img_val', 100 );
		
		$query = $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type IN ('" . implode( "','", array_map( 'esc_sql', $image_types ) ) . "')
			LIMIT %d
		", $limit );

		$attachments 		= $wpdb->get_results( $wpdb->prepare( $query ) );

		foreach ( $attachments as $attachment ) {
			$img_id 		= $attachment->ID;
			$main_img 		= get_attached_file( $img_id );
			$file_info 		= pathinfo( $main_img );
			$extension 		= strtolower( $file_info['extension'] );
			$main_img 		= str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );
			$webp_file_path = thumbpress_convert_image_to_webp( $main_img );
			$old_metadata 	= wp_get_attachment_metadata( $img_id );
			$thumb_dir 		= dirname( $main_img ) . DIRECTORY_SEPARATOR;

			foreach ( $old_metadata['sizes'] as $old_size => $old_size_data ) {
				if ( 'image/svg+xml' == $old_size_data['mime-type'] ) {
					continue;
				}
				wp_delete_file( $thumb_dir . $old_size_data['file'] );
			}

			if ( strpos( $file_info['basename'], "-scaled.{$extension}" ) !== false ) {
				wp_delete_file( $thumb_dir . $file_info['basename'] );
				wp_delete_file( $thumb_dir . str_replace( "-scaled.{$extension}", ".{$extension}", $file_info['basename'] ) );
			} else {
				$_main_img = get_attached_file( $img_id );
				wp_delete_file( $_main_img );
			}

			if ( ! $webp_file_path ) {
				continue;
			}

			$webp_metadata = wp_generate_attachment_metadata( $img_id, $webp_file_path );

			update_attached_file( $img_id, $webp_file_path );
			wp_update_attachment_metadata( $img_id, $webp_metadata );

			$updated_metadata = wp_get_attachment_metadata( $img_id );
			$file_path = $updated_metadata['file'];
			update_post_meta( $img_id, '_wp_attached_file', $file_path );
			wp_update_post( [ 'ID' => $img_id, 'post_mime_type' => 'image/webp' ] );
		}
		
		$grand_total_attachments 	= get_option('thumbpress_now_convert_background_total_images');
		$count 						= $offset + count( $attachments );
		$progress 					= ( $count / $grand_total_attachments ) * 100;
		$progress 					= $progress > 100 ? 100 : $progress;
		$new_offset 				= $offset + count( $attachments );

		update_option( 'thumbpress_convert_progress', $progress );
		update_option( 'thumbpress_convert_total_processd', $count );
		update_option( 'thumbpress_convert_total_converted', $count );
		
		if ( $progress != 100 ) {
			$action_id 	= as_schedule_single_action( wp_date('U') + 10, 'thumbpress_convert_all_image', ['offset' => $new_offset] );
			thumbpress_add_schedule_log( $this->id, $action_id );
		}else{
			update_option( 'convert_last_completed_time', date_i18n('U') );
		}
	}

	public function convert_single_image() {
		$response = [
			'status'	=> 0,
			'message'	=> __( 'Failed', 'image-sizes' ),
		];

		if( ! wp_verify_nonce( $_POST['_wpnonce'], $this->slug ) ) {
			$response['message'] = __( 'Unauthorized', 'image-sizes' );
			wp_send_json_error( $response );
		}

		$img_id 		= $this->sanitize( $_POST['image_id'] );
		$main_img 		= get_attached_file( $img_id );
		$file_info 		= pathinfo( $main_img );
		$extension 		= strtolower( $file_info['extension'] );
		$main_img 		= str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );
		$webp_file_path = thumbpress_convert_image_to_webp( $main_img );

		// remove old thumbnails first
		$old_metadata 	= wp_get_attachment_metadata( $img_id );
		$thumb_dir 		= dirname( $main_img ) . DIRECTORY_SEPARATOR;
		
		foreach ( $old_metadata['sizes'] as $old_size => $old_size_data ) {
			// For SVG file
			if ( 'image/svg+xml' == $old_size_data['mime-type'] ) {
				continue;
			}
			
			// delete thumbnails
			wp_delete_file( $thumb_dir . $old_size_data['file'] );
		}

		//check scaled image
		if ( strpos( $file_info['basename'], "-scaled.{$extension}" ) !== false ) {
			// delete scaled image
			wp_delete_file( $thumb_dir . $file_info['basename'] );

			// delete original image
			wp_delete_file( $thumb_dir . str_replace( "-scaled.{$extension}", ".{$extension}", $file_info['basename'] ) );
		}
		else {
			// delete original image
			$_main_img 		= get_attached_file( $img_id );
			wp_delete_file( $_main_img );
		}

		if ( ! $webp_file_path ) {
			$response['message'] = __( 'Failed to convert image', 'image-sizes' );
			wp_send_json_error( $response );
		}

		// Load the Regenerate Thumbnails library
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$webp_metadata 	= wp_generate_attachment_metadata( $img_id, $webp_file_path );

		if ( empty( $webp_metadata ) ) {
			$response['message'] = __( 'Failed to update attachment metadata', 'image-sizes' );
			wp_send_json_error( $response );
		}

		update_attached_file( $img_id, $webp_file_path );
		wp_update_attachment_metadata( $img_id, $webp_metadata );

		$updated_metadata 	= wp_get_attachment_metadata( $img_id );
		$file_path 			= $updated_metadata['file'];
		
		update_post_meta( $img_id, '_wp_attached_file', $file_path );

		// Update mime type
		$image_data = array(
			'ID'           		=> $img_id,
			'post_mime_type' 	=> 'image/webp',
		);

		// Update the post into the database
		wp_update_post( $image_data );

		$response['status'] 	= 1;
		$response['message'] 	= __( 'Success', 'image-sizes' );
		wp_send_json_success( $response );
	
	}

	public function convert_images() {

		$response = [
			'status'	=> 0,
			'message'	=> __( 'Failed', 'image-sizes' ),
		];

		if( ! wp_verify_nonce( $_POST['_wpnonce'], $this->slug ) ) {
			$response['message'] = __( 'Unauthorized', 'image-sizes' );	
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		global $wpdb;

		$image_types 			 	= [ 'image/png', 'image/jpeg', 'image/jpg' ];
		$limit 						= $this->sanitize( $_POST['limit'] );
		$offset 					= $this->sanitize( $_POST['offset'] );

		if ( $offset == 0 ) {
			$total_attachments_query = "
			SELECT COUNT(ID)
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type IN ('" . implode( "','", array_map( 'esc_sql', $image_types ) ) . "')
			";
			$total_attachments = $wpdb->get_var( $total_attachments_query );
			update_option( 'thumbpress_now_convert_total_image', $total_attachments );
		}

		$query = $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type IN ('" . implode( "','", array_map( 'esc_sql', $image_types ) ) . "')
			LIMIT %d
		", $limit );

		$attachments 		= $wpdb->get_results( $wpdb->prepare( $query ) );


		if( ! $attachments ) {
			$response['status'] 	= 2;
			$response['message'] 	= __( 'No images found.', 'image-sizes' );
			wp_send_json( $response );
		}	

		foreach ( $attachments as $attachment ) {
			$img_id 		= $attachment->ID;
			$main_img 		= get_attached_file( $img_id );
			$file_info 		= pathinfo( $main_img );
			$extension 		= strtolower( $file_info['extension'] );
			$main_img 		= str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );
			$webp_file_path = thumbpress_convert_image_to_webp( $main_img );
			$old_metadata 	= wp_get_attachment_metadata( $img_id );
			$thumb_dir 		= dirname( $main_img ) . DIRECTORY_SEPARATOR;

			foreach ( $old_metadata['sizes'] as $old_size => $old_size_data ) {
				if ( 'image/svg+xml' == $old_size_data['mime-type'] ) {
					continue;
				}
				wp_delete_file( $thumb_dir . $old_size_data['file'] );
			}

			if ( strpos( $file_info['basename'], "-scaled.{$extension}" ) !== false ) {
				wp_delete_file( $thumb_dir . $file_info['basename'] );
				wp_delete_file( $thumb_dir . str_replace( "-scaled.{$extension}", ".{$extension}", $file_info['basename'] ) );
			} else {
				$_main_img = get_attached_file( $img_id );
				wp_delete_file( $_main_img );
			}

			if ( ! $webp_file_path ) {
				continue;
			}

			$webp_metadata = wp_generate_attachment_metadata( $img_id, $webp_file_path );

			update_attached_file( $img_id, $webp_file_path );
			wp_update_attachment_metadata( $img_id, $webp_metadata );

			$updated_metadata = wp_get_attachment_metadata( $img_id );
			$file_path = $updated_metadata['file'];
			update_post_meta( $img_id, '_wp_attached_file', $file_path );
			wp_update_post( [ 'ID' => $img_id, 'post_mime_type' => 'image/webp' ] );
		}
		
		$grand_total_attachments 	= get_option('thumbpress_now_convert_total_image');
		$count 						= $offset + count( $attachments );
		$progress 					= ( $count / $grand_total_attachments ) * 100;
		$progress 					= $progress > 100 ? 100 : $progress;
		$new_offset 				= $offset + count( $attachments );
		$message 					= __('Converting Images to WebP...', 'image-sizes');

		if( $progress == 100 ) {
			$message = __( 'Congratulations, Converting Images to WebP is Completed!', 'image-sizes' );
		}

		$response['status'] 		= 1;
		$response['message'] 		= $message;
		$response['offset'] 		= $new_offset;
		$response['progress'] 		= $progress;

		wp_send_json( $response );
	}

	public function image_sizes( $sizes ){
		$disables = Helper::get_option( 'prevent_image_sizes', 'disables', [] );

		if( count( $disables ) ) :
			foreach( $disables as $disable ){
				unset( $sizes[ $disable ] );
			}
		endif;
		
		return $sizes;
	}

	public function big_image_size( $threshold ) {
		$disables = Helper::get_option( 'prevent_image_sizes', 'disables', [] );

		return in_array( 'scaled', $disables ) ? false : $threshold;
	}
}