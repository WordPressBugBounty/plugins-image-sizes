<?php
namespace Codexpert\ThumbPress\API;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Helpers\Utility;
use Codexpert\ThumbPress\Traits\Rest;
use Codexpert\ThumbPress\Traits\Cache;
use Codexpert\ThumbPress\Controllers\Common\Convert_Avif as Convert_Avif_Controller;

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

		if ( is_wp_error( $avif_file_path ) ) {
			return $this->response_error( $avif_file_path->get_error_message() );
		}

		$avif_metadata = wp_generate_attachment_metadata( $img_id, $avif_file_path );

		// Metadata generation failed — roll back. Delete the AVIF we just created (no orphan, no
		// retry collision) and leave the original attachment + its files untouched. Deleting the
		// old files now would strand the DB on metadata that still references them.
		if ( ! $avif_metadata || is_wp_error( $avif_metadata ) ) {
			if ( file_exists( $avif_file_path ) ) {
				wp_delete_file( $avif_file_path );
			}
			$reason = is_wp_error( $avif_metadata ) ? $avif_metadata->get_error_message() : __( 'metadata generation returned nothing', 'image-sizes' );
			return $this->response_error(
				sprintf(
					/* translators: %s: underlying reason metadata generation failed */
					__( 'Converted the image to AVIF but could not generate its metadata (%s); rolled back.', 'image-sizes' ),
					$reason
				)
			);
		}

		wp_update_attachment_metadata( $img_id, $avif_metadata );

		// Keep _wp_attached_file aligned with metadata['file']. For big images WP scales the
		// converted file and points the attachment at the -scaled copy; forcing the non-scaled
		// path here would orphan the -scaled file on delete and serve the full-size image.
		if ( ! empty( $avif_metadata['file'] ) ) {
			update_post_meta( $img_id, '_wp_attached_file', $avif_metadata['file'] );
		} else {
			update_attached_file( $img_id, $avif_file_path );
		}
		wp_update_post(
			array(
				'ID'             => $img_id,
				'post_mime_type' => 'image/avif',
				'guid'           => wp_get_attachment_url( $img_id ),
			)
		);

		// Delete old files only AFTER the DB points at the new AVIF. If the request is
		// interrupted during metadata generation above, the original survives and the DB
		// still resolves it — instead of an avif on disk with the attachment stuck on jpeg.
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

		Utility::refresh_file_meta( $img_id, $avif_file_path );

		// Repoint stored URLs (post content, meta, options) at the new file.
		Utility::replace_attachment_urls( $img_id, $main_img, $old_metadata );

		$new_size         = file_exists( $avif_file_path ) ? filesize( $avif_file_path ) : 0;
		$updated_metadata = wp_get_attachment_metadata( $img_id );
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
