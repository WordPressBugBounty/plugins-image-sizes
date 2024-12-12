<?php
namespace Codexpert\ThumbPress\Modules;

use Codexpert\ThumbPress\Helper;
use Codexpert\Plugin\Base;
use Codexpert\Plugin\Settings as Settings_API;

class Regenerate_Thumbnails extends Base {
	public $slug;
	public $version;
	public $id = 'regenerate-thumbnails';

	/**
	 * Constructor
	 */
	public function __construct() {
		
		$this->slug		= 'image-sizes';
		$this->version	= '5.8.7';

		$this->action( 'admin_enqueue_scripts', 'enqueue_scripts' );
		$this->action( 'plugins_loaded', 'init_menu', 11 );
		$this->priv( 'image_sizes-regen-thumbs', 'regen_thumbs' );
		$this->priv( 'thumbpress_schedule_regenerate-thumbs', 'schedule_regenerate' );
		$this->action( 'thumbpress_regenerate_all_image', 'regenerate_all_image' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( "regenerate-thumbnails-js", plugins_url( 'js/admin.js', __FILE__), [ 'jquery' ], $this->version, true );
	}

	public function init_menu() {
		$modules_settings = [
			'id'            => "thumbpress-regenerate-thumbnails",
			'parent'        => 'thumbpress',
			'icon'      	=> 'dashicons-format-gallery',
			'label'         => __( 'Regenerate Thumbnails', 'image-sizes' ),
			'title'         => __( 'Regenerate Thumbnails', 'image-sizes' ),
			'header'        => __( 'Regenerate Thumbnails', 'image-sizes' ),
			'sections'      => [
				'regenerate-thumbnails'	=> [
					'id'        => 'regenerate-thumbnails',
					'label'     => __( 'Regenerate Thumbnails', 'image-sizes' ),
					'icon'      => 'dashicons-format-gallery',
					'sticky'	=> false,
					'fields'	=> [],
					'hide_form'	=> true,
					'template'  => apply_filters( 'thumbpress_regenerate_thumbnails_template', THUMBPRESS_DIR . '/modules/regenerate-thumbnails/views/settings.php' ),
				]
			]
		];

		new Settings_API( apply_filters( 'thumbpress-modules_optimizer_settings_args', $modules_settings ) );
	}

	public function regen_thumbs() {

		$response = [
			'status'	=> 0,
			'message'	=> __( 'Failed', 'image-sizes' ),
		];

		if( ! wp_verify_nonce( $_POST['_nonce'], $this->slug ) ) {
			$response['message'] = __( 'Unauthorized', 'image-sizes' );
			wp_send_json( $response );
		}

		$offset				= $this->sanitize( $_POST['offset'] );
		$limit				= $this->sanitize( $_POST['limit'] );
		$thumbs_deleteds	= $this->sanitize( $_POST['thumbs_deleteds'] );
		$thumbs_createds	= $this->sanitize( $_POST['thumbs_createds'] );

		global $wpdb;

		$images_count 		= $wpdb->get_results( "SELECT `ID` FROM `$wpdb->posts` WHERE `post_type` = 'attachment' AND `post_mime_type` LIKE 'image/%'" );
		$total_images_count = count( $images_count );
		$images 			= $wpdb->get_results( $wpdb->prepare( "SELECT `ID` FROM `$wpdb->posts` WHERE `post_type` = 'attachment' AND `post_mime_type` LIKE 'image/%'  LIMIT %d OFFSET %d", $limit, $offset ) );
		$offsets 			= $offset + count( $images );
		$thumbs_created 	= $thumbs_deleted = $_thumbs_deleteds = $_thumbs_createds = $progress = 0;

		if( ! $images ) {
			$response['status'] 	= 2;
			$response['message'] 	= __( 'No images found.', 'image-sizes' );
			wp_send_json( $response );
		}	
		
		require_once ABSPATH . 'wp-admin/includes/image.php';

		if( $images ) {

			foreach ( $images as $image ) {
				$image_id 		= $image->ID;
				$main_img 		= get_attached_file( $image_id );
				$file_info 		= pathinfo( $main_img );
				$extension 		= strtolower( $file_info['extension'] );
				$main_img 		= str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );

				// remove old thumbnails first
				$old_metadata 	= wp_get_attachment_metadata( $image_id );
				$thumb_dir 		= dirname( $main_img ) . DIRECTORY_SEPARATOR;

				foreach ( $old_metadata['sizes'] as $old_size => $old_size_data ) {
					// For SVG file
					if ( 'image/svg+xml' == $old_size_data['mime-type'] ) {
						continue;
					}
					
					$thumb_path = $thumb_dir . $old_size_data['file'];
					if ( file_exists( $thumb_path ) ) {
						wp_delete_file( $thumb_path );
						$thumbs_deleted++;
					}
				}
				//delete scaled image
				if ( strpos( $file_info['basename'], "-scaled.{$extension}" ) !== false ) {
					wp_delete_file( $thumb_dir . $file_info['basename'] );
					$thumbs_deleted++;
				}

				// generate new thumbnails
				if ( false !== $main_img && file_exists( $main_img ) ) {
					$new_thumbs = wp_generate_attachment_metadata( $image_id, $main_img );

					wp_update_attachment_metadata( $image_id, $new_thumbs );

					$updated_metadata 	= wp_get_attachment_metadata( $image_id );
					$file_path 			= $updated_metadata['file'];
					
					update_post_meta( $image_id, '_wp_attached_file', $file_path );
					$thumbs_created += is_array( $new_thumbs['sizes'] ) ? count( $new_thumbs['sizes'] ) : 0;
				}
			}

			$_thumbs_deleteds 		= $thumbs_deleteds + $thumbs_deleted;
			$_thumbs_createds 		= $thumbs_createds + $thumbs_created;
			$progress 				= ( $offsets / $total_images_count ) * 100;
			$progress 					= $progress > 100 ? 100 : $progress;
		}
		$message 					= __('Regenerating Thumbnails...', 'image-sizes');

		if( $progress == 100 ) {
			$message = __( 'Congratulations, Thumbnail Regeneration is Completed!', 'image-sizes' );
		}

		$response['status'] 			= 1;
		$response['message'] 			= $message;
		$response['offset'] 			= $offsets;
		$response['progress'] 			= $progress;
		$response['thumbs_deleted'] 	= $_thumbs_deleteds;
		$response['thumbs_created'] 	= $_thumbs_createds;
		$response['total_images_count'] = $total_images_count;

		wp_send_json( $response );
	}
	public function schedule_regenerate() {
		
		$response = [
			'status'	=> 0,
			'message'	=> __( 'Failed', 'image-sizes' ),
		];

		if( ! wp_verify_nonce( $_POST['_wpnonce'], $this->slug ) ) {
			$response['message'] = __( 'Unauthorized', 'image-sizes' );
			wp_send_json_error( $response );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		global $wpdb;

		delete_option( 'thumbpress_regenerate_progress' );
		delete_option( 'thumbpress_regenerate_total_processed' );
		delete_option( 'thumbpress_regenerate_total_deleted' );
		delete_option( 'thumbpress_regenerate_total_created' );
		
		 if ( isset( $_POST['limit'] ) ) {
			$limit_value = intval( $_POST['limit'] );
			update_option( 'thumbpress_regenerate_limit', $limit_value );
		}

		$images 		= $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' " ) );
		$total_images 	= count( $images );
		
		update_option( 'thumbpress_regenerate_total_image', $total_images );

		if( ! $total_images ) {
			$response['status'] 	= 2;
			$response['message'] 	= __( 'No images found.', 'image-sizes' );
			wp_send_json( $response );
		}

		$offset 	= 0;
		$action_id 	= as_schedule_single_action( wp_date( 'U' ) + 10, 'thumbpress_regenerate_all_image',  ['offset' => $offset] );

		thumbpress_add_schedule_log( $this->id, $action_id );

		$response['status'] 	= 1;
		$response['message'] 	= __( 'Your images are being regenerating. Please wait...', 'image-sizes' );
		$response['action_id'] 	= $action_id;

		wp_send_json( $response );

	}

	public function regenerate_all_image( $offset ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		global $wpdb;

		$limit 				= get_option( 'thumbpress_regenerate_limit', 500 );
		$total_attachments 	= get_option( 'thumbpress_regenerate_total_image' );
		$thumbs_deleteds	= get_option( 'thumbpress_regenerate_total_deleted' );
		$thumbs_createds	= get_option( 'thumbpress_regenerate_total_created' );

		$images 			= $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'image%'
			LIMIT %d OFFSET %d
		", $limit, $offset );
		$images 			= $wpdb->get_results( $images );
		$action_id 			= thumbpress_get_last_action_status_by_module_name( 'regenerate-thumbnails', 'action_id' );
		$thumbs_created 	= $thumbs_deleted = $_thumbs_deleteds = $_thumbs_createds = $progress = 0;
		if ( count( $images ) > 0 ) {
			foreach ( $images as $image ) {
				$image_id 		= $image->ID;
				$main_img 		= get_attached_file( $image_id );
				$file_info 		= pathinfo( $main_img );
				$extension 		= strtolower( $file_info['extension'] );
				$main_img 		= str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );

				// remove old thumbnails first
				$old_metadata 	= wp_get_attachment_metadata( $image_id );
				$thumb_dir 		= dirname( $main_img ) . DIRECTORY_SEPARATOR;
				foreach ( $old_metadata['sizes'] as $old_size => $old_size_data ) {
					// For SVG file
					if ('image/svg+xml' == $old_size_data['mime-type']) {
						continue;
					}
					
					wp_delete_file( $thumb_dir . $old_size_data['file'] );
					$thumbs_deleted++;
				}

				if ( strpos( $file_info['basename'], "-scaled.{$extension}" ) !== false ) {
					wp_delete_file( $thumb_dir . $file_info['basename'] );
					$thumbs_deleted++;
				}

				// generate new thumbnails
				if ( false !== $main_img && file_exists( $main_img ) ) {
					$new_thumbs = wp_generate_attachment_metadata( $image_id, $main_img );

					
					wp_update_attachment_metadata( $image_id, $new_thumbs );

					$updated_metadata 	= wp_get_attachment_metadata( $image_id );
					$file_path 			= $updated_metadata['file'];

					update_post_meta( $image_id, '_wp_attached_file', $file_path );

					$thumbs_created += count( $new_thumbs['sizes'] );
				}
			}
			$_thumbs_deleteds 	= $thumbs_deleteds + $thumbs_deleted;
			$_thumbs_createds 	= $thumbs_createds + $thumbs_created;
			$count 				= $offset + count( $images );
			$progress 			= ( $count / $total_attachments ) * 100;
			$progress 					= $progress > 100 ? 100 : $progress;

			update_option( 'thumbpress_regenerate_progress', $progress );
			update_option( 'thumbpress_regenerate_total_processed', $count );
			update_option( 'thumbpress_regenerate_total_deleted', $_thumbs_deleteds );
			update_option( 'thumbpress_regenerate_total_created', $_thumbs_createds );
			
			if ( $count < $total_attachments ) {
				$new_offset = $offset + $limit;
				$action_id 	= as_schedule_single_action( wp_date('U') + 10, 'thumbpress_regenerate_all_image', ['offset' => $new_offset] );
				thumbpress_add_schedule_log( $this->id, $action_id );
			}
			else{
				update_option( 'thumbpress_regenerate_last_schedule_time', date_i18n('U') );
			}
		}
	}
}