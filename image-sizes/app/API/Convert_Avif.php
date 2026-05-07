<?php
namespace Thumbpress\API;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Helpers\Utility;
use Thumbpress\Traits\Rest;
use Thumbpress\Traits\Cache;
use Thumbpress\Controllers\Common\Convert_Avif as Convert_Avif_Controller;

class Convert_Avif {

	use Rest;
	use Cache;

	public function convert_single( $request ) {
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			return $this->response_error( __( 'AVIF conversion requires PHP 8.0 or higher.', 'image-sizes' ) );
		}

		$img_id = absint( $request->get_param( 'image_id' ) );

		if ( ! $img_id ) {
			return $this->response_error( __( 'Invalid image ID.', 'image-sizes' ) );
		}

		$main_img = get_attached_file( $img_id );

		if ( ! $main_img || ! file_exists( $main_img ) ) {
			return $this->response_error( __( 'Source image file not found.', 'image-sizes' ) );
		}

		$file_info = pathinfo( $main_img );
		$extension = strtolower( $file_info['extension'] ?? '' );
		$main_img  = str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );

		if ( ! file_exists( $main_img ) ) {
			return $this->response_error( __( 'Source image file not found.', 'image-sizes' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$old_metadata = wp_get_attachment_metadata( $img_id );
		$thumb_dir    = dirname( $main_img ) . DIRECTORY_SEPARATOR;
		$old_size     = file_exists( $main_img ) ? filesize( $main_img ) : 0;

		if ( ! empty( $old_metadata['sizes'] ) ) {
			foreach ( $old_metadata['sizes'] as $thumb ) {
				$thumb_path = $thumb_dir . $thumb['file'];
				if ( file_exists( $thumb_path ) ) {
					$old_size += filesize( $thumb_path );
				}
			}
		}

		$avif_controller = new Convert_Avif_Controller();
		$avif_file_path  = $avif_controller->convert_image_to_avif( $main_img );

		if ( ! $avif_file_path ) {
			return $this->response_error( __( 'Failed to convert image to AVIF.', 'image-sizes' ) );
		}

		// Delete old files only after successful conversion.
		if ( ! empty( $old_metadata['sizes'] ) ) {
			foreach ( $old_metadata['sizes'] as $old_size_data ) {
				if ( 'image/svg+xml' === $old_size_data['mime-type'] ) {
					continue;
				}
				wp_delete_file( $thumb_dir . $old_size_data['file'] );
			}
		}

		if ( file_exists( $main_img ) ) {
			wp_delete_file( $main_img );
		}
		$scaled_path = str_replace( ".{$extension}", "-scaled.{$extension}", $main_img );
		if ( $scaled_path !== $main_img && file_exists( $scaled_path ) ) {
			wp_delete_file( $scaled_path );
		}

		$avif_metadata = wp_generate_attachment_metadata( $img_id, $avif_file_path );

		update_attached_file( $img_id, $avif_file_path );
		wp_update_attachment_metadata( $img_id, $avif_metadata );

		$updated_metadata = wp_get_attachment_metadata( $img_id );
		update_post_meta( $img_id, '_wp_attached_file', $updated_metadata['file'] );
		wp_update_post(
			array(
				'ID'             => $img_id,
				'post_mime_type' => 'image/avif',
				'guid'           => wp_get_attachment_url( $img_id ),
			)
		);

		Utility::refresh_file_meta( $img_id, $avif_file_path );

		$new_size = file_exists( $avif_file_path ) ? filesize( $avif_file_path ) : 0;
		if ( ! empty( $updated_metadata['sizes'] ) ) {
			foreach ( $updated_metadata['sizes'] as $thumb ) {
				$thumb_path = dirname( $avif_file_path ) . '/' . $thumb['file'];
				if ( file_exists( $thumb_path ) ) {
					$new_size += filesize( $thumb_path );
				}
			}
		}

		$saved_bytes = max( 0, $old_size - $new_size );
		if ( $saved_bytes > 0 ) {
			$cumulative = (int) get_option( 'thumbpress_avif_convert_space_saved', 0 );
			update_option( 'thumbpress_avif_convert_space_saved', $cumulative + $saved_bytes );
			thumbpress_add_space_saved( $saved_bytes );
		}

		$this->delete_cache( 'stat_not_avif' );
		$this->delete_cache( 'stat_not_webp' );
		$this->delete_cache( 'stat_unoptimized' );

		return $this->response_success(
			array(
				'message' => __( 'Image converted to AVIF successfully.', 'image-sizes' ),
			)
		);
	}
}
