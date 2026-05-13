<?php
namespace Thumbpress\API;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Helpers\Utility;
use Thumbpress\Traits\Rest;
use Thumbpress\Traits\Cache;
use Thumbpress\Controllers\Common\Convert_Webp as Convert_Webp_Controller;

class Convert_Webp {

	use Rest;
	use Cache;

	private function formats_to_mime_types( $formats ) {
		$format_to_mime = array(
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'bmp'  => 'image/bmp',
			'avif' => 'image/avif',
		);

		$mime_types = array();

		foreach ( (array) $formats as $format ) {
			$format = strtolower( trim( $format ) );
			if ( isset( $format_to_mime[ $format ] ) ) {
				$mime_types[] = $format_to_mime[ $format ];
			}
		}

		return array_values( array_unique( $mime_types ) );
	}

	public function convert_now( $request ) {
		$limit       = $request->get_param( 'limit' );
		$offset      = $request->get_param( 'offset' );
		$limit       = $limit ? absint( $limit ) : 10;
		$offset      = $offset ? absint( $offset ) : 0;
		$space_saved = absint( $request->get_param( 'space_saved' ) );
		$chunk_saved = 0;

		$file_formats = $request->get_param( 'file_formats' );
		if ( empty( $file_formats ) ) {
			return $this->response_error( __( 'No file formats selected.', 'image-sizes' ) );
		}

		$image_types = $this->formats_to_mime_types( $file_formats );

		if ( empty( $image_types ) ) {
			return $this->response_error( __( 'No valid file formats selected.', 'image-sizes' ) );
		}

		global $wpdb;

		if ( $offset == 0 ) {
			$total_attachments_query = "
				SELECT COUNT(ID)
				FROM {$wpdb->posts}
				WHERE post_type = 'attachment'
				AND post_mime_type IN ('" . implode( "','", array_map( 'esc_sql', $image_types ) ) . "')
				AND post_status != 'trash'
			";
			$total_attachments       = $wpdb->get_var( $total_attachments_query );
			update_option( 'thumbpress_now_convert_total_image', $total_attachments );
		} else {
			$total_attachments = (int) get_option( 'thumbpress_now_convert_total_image', 0 );
		}

		$query = $wpdb->prepare(
			"
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type IN ('" . implode( "','", array_map( 'esc_sql', $image_types ) ) . "')
			AND post_status != 'trash'
			LIMIT %d
		",
			$limit
		);

		$attachments = $wpdb->get_results( $query );

		if ( ! $attachments ) {
			return $this->response_error(
				__( 'No images found.', 'image-sizes' ),
				array(
					'status' => 2,
				)
			);
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ( $attachments as $attachment ) {
			$img_id   = $attachment->ID;
			$main_img = get_attached_file( $img_id );

			if ( ! $main_img || ! file_exists( $main_img ) ) {
				continue;
			}

			$file_info = pathinfo( $main_img );
			$extension = strtolower( $file_info['extension'] ?? '' );
			$main_img  = str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );

			if ( ! file_exists( $main_img ) ) {
				continue;
			}

			// Calculate old total size before conversion.
			$old_metadata = wp_get_attachment_metadata( $img_id );
			$old_size     = file_exists( $main_img ) ? filesize( $main_img ) : 0;
			$thumb_dir    = dirname( $main_img ) . DIRECTORY_SEPARATOR;
			if ( ! empty( $old_metadata['sizes'] ) ) {
				foreach ( $old_metadata['sizes'] as $thumb ) {
					$thumb_path = $thumb_dir . $thumb['file'];
					if ( file_exists( $thumb_path ) ) {
						$old_size += filesize( $thumb_path );
					}
				}
			}

			// Delete old thumbnails before conversion to avoid name collisions.
			foreach ( ( $old_metadata['sizes'] ?? array() ) as $size_name => $size_data ) {
				if ( 'image/svg+xml' == $size_data['mime-type'] ) {
					continue;
				}
				wp_delete_file( $thumb_dir . $size_data['file'] );
			}

			$webp_file_path = $this->convert_image_to_webp( $main_img );

			// Delete original source file after conversion.
			if ( file_exists( $main_img ) ) {
				wp_delete_file( $main_img );
			}
			// Also delete the scaled version if it exists.
			$scaled_path = str_replace( ".{$extension}", "-scaled.{$extension}", $main_img );
			if ( $scaled_path !== $main_img && file_exists( $scaled_path ) ) {
				wp_delete_file( $scaled_path );
			}

			if ( ! $webp_file_path ) {
				continue;
			}

			$webp_metadata = wp_generate_attachment_metadata( $img_id, $webp_file_path );

			update_attached_file( $img_id, $webp_file_path );
			wp_update_attachment_metadata( $img_id, $webp_metadata );

			$updated_metadata = wp_get_attachment_metadata( $img_id );
			$file_path        = $updated_metadata['file'];
			update_post_meta( $img_id, '_wp_attached_file', $file_path );
			wp_update_post(
				array(
					'ID'             => $img_id,
					'post_mime_type' => 'image/webp',
					'guid'           => wp_get_attachment_url( $img_id ),
				)
			);

			Utility::refresh_file_meta( $img_id, $webp_file_path );

			// Calculate new total size after conversion.
			$new_size     = file_exists( $webp_file_path ) ? filesize( $webp_file_path ) : 0;
			$new_metadata = wp_get_attachment_metadata( $img_id );
			if ( ! empty( $new_metadata['sizes'] ) ) {
				foreach ( $new_metadata['sizes'] as $thumb ) {
					$thumb_path = dirname( $webp_file_path ) . '/' . $thumb['file'];
					if ( file_exists( $thumb_path ) ) {
						$new_size += filesize( $thumb_path );
					}
				}
			}
			$saved        = max( 0, $old_size - $new_size );
			$space_saved += $saved;
			$chunk_saved += $saved;
		}

		$count      = $offset + count( $attachments );
		$progress   = ( $count / $total_attachments ) * 100;
		$progress   = $progress > 100 ? 100 : $progress;
		$new_offset = $offset + count( $attachments );

		// Persist stats so dashboard can read them.
		update_option( 'thumbpress_convert_total_converted', $count );
		update_option( 'thumbpress_convert_space_saved', $space_saved );
		thumbpress_add_space_saved( $chunk_saved );

		$message = __( 'Converting Images to WebP...', 'image-sizes' );
		if ( $progress == 100 ) {
			$message = __( 'Congratulations, Converting Images to WebP is Completed!', 'image-sizes' );
			update_option( 'convert_last_completed_time', wp_date( 'U' ) );
			$this->delete_cache( 'stat_not_webp' );
			$this->delete_cache( 'stat_not_avif' );
			$this->delete_cache( 'stat_unoptimized' );
		}

		return $this->response_success(
			array(
				'message'     => $message,
				'offset'      => $new_offset,
				'progress'    => round( $progress ),
				'converted'   => $count,
				'remaining'   => max( 0, $total_attachments - $count ),
				'total'       => $total_attachments,
				'space_saved' => $space_saved,
			)
		);
	}

	public function convert_background( $request ) {
		$limit = $request->get_param( 'limit' );
		$limit = $limit ? absint( $limit ) : 10;

		$file_formats = $request->get_param( 'file_formats' );
		if ( empty( $file_formats ) ) {
			return $this->response_error( __( 'No file formats selected.', 'image-sizes' ) );
		}

		$image_types = $this->formats_to_mime_types( $file_formats );
		if ( empty( $image_types ) ) {
			return $this->response_error( __( 'No valid file formats selected.', 'image-sizes' ) );
		}

		global $wpdb;

		delete_option( 'thumbpress_convert_progress' );
		delete_option( 'thumbpress_convert_total_processd' );
		delete_option( 'thumbpress_convert_total_converted' );
		delete_option( 'thumbpress_convert_space_saved' );

		$total_attachments_query = "
			SELECT COUNT(ID)
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type IN ('" . implode( "','", array_map( 'esc_sql', $image_types ) ) . "')
			AND post_status != 'trash'
		";
		$total_attachments       = $wpdb->get_var( $total_attachments_query );

		if ( ! $total_attachments ) {
			return $this->response_error( __( 'No images found.', 'image-sizes' ) );
		}

		update_option( 'thumbpress_now_convert_background_total_images', $total_attachments );
		update_option( 'thumbpress_convert_img_val', $limit );

		as_unschedule_all_actions( 'thumbpress_convert_all_image' );
		delete_option( 'thumbpress_webp_cancelled' );

		$action_id = as_schedule_single_action(
			wp_date( 'U' ) - 10,
			'thumbpress_convert_all_image',
			array(
				'offset'       => 0,
				'file_formats' => (array) $file_formats,
			)
		);

		if ( ! $action_id ) {
			return $this->response_error( __( 'Failed to schedule action.', 'image-sizes' ) );
		}

		return $this->response_success(
			array(
				'message'   => __( 'Your images are being converted in the background.', 'image-sizes' ),
				'action_id' => $action_id,
			)
		);
	}

	public function get_progress() {
		$progress       = (float) get_option( 'thumbpress_convert_progress', 0 );
		$processed      = (int) get_option( 'thumbpress_convert_total_processd', 0 );
		$converted      = (int) get_option( 'thumbpress_convert_total_converted', 0 );
		$total          = (int) get_option( 'thumbpress_now_convert_background_total_images', 0 );
		$completed      = get_option( 'convert_last_completed_time' );
		$completed_time = $completed ? date_i18n( 'g:i a F j, Y', $completed ) : '';

		$space_saved = (int) get_option( 'thumbpress_convert_space_saved', 0 );

		return $this->response_success(
			array(
				'progress'       => round( $progress ),
				'processed'      => $processed,
				'converted'      => $converted,
				'remaining'      => max( 0, $total - $processed ),
				'total'          => $total,
				'space_saved'    => $space_saved,
				'is_complete'    => $progress >= 100,
				'completed_time' => $completed_time,
			)
		);
	}

	public function convert_single( $request ) {
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

		// Delete old thumbnails before conversion to avoid name collisions.
		foreach ( ( $old_metadata['sizes'] ?? array() ) as $size_data ) {
			if ( 'image/svg+xml' === $size_data['mime-type'] ) {
				continue;
			}
			wp_delete_file( $thumb_dir . $size_data['file'] );
		}

		$webp_file_path = $this->convert_image_to_webp( $main_img );

		// Delete original source file after conversion attempt.
		if ( file_exists( $main_img ) ) {
			wp_delete_file( $main_img );
		}
		$scaled_path = str_replace( ".{$extension}", "-scaled.{$extension}", $main_img );
		if ( $scaled_path !== $main_img && file_exists( $scaled_path ) ) {
			wp_delete_file( $scaled_path );
		}

		if ( ! $webp_file_path ) {
			return $this->response_error( __( 'Failed to convert image.', 'image-sizes' ) );
		}

		$webp_metadata = wp_generate_attachment_metadata( $img_id, $webp_file_path );

		update_attached_file( $img_id, $webp_file_path );
		wp_update_attachment_metadata( $img_id, $webp_metadata );

		$updated_metadata = wp_get_attachment_metadata( $img_id );
		update_post_meta( $img_id, '_wp_attached_file', $updated_metadata['file'] );
		wp_update_post(
			array(
				'ID'             => $img_id,
				'post_mime_type' => 'image/webp',
				'guid'           => wp_get_attachment_url( $img_id ),
			)
		);

		Utility::refresh_file_meta( $img_id, $webp_file_path );

		$new_size = file_exists( $webp_file_path ) ? filesize( $webp_file_path ) : 0;
		if ( ! empty( $updated_metadata['sizes'] ) ) {
			foreach ( $updated_metadata['sizes'] as $thumb ) {
				$thumb_path = dirname( $webp_file_path ) . '/' . $thumb['file'];
				if ( file_exists( $thumb_path ) ) {
					$new_size += filesize( $thumb_path );
				}
			}
		}

		$saved_bytes = max( 0, $old_size - $new_size );
		if ( $saved_bytes > 0 ) {
			$cumulative = (int) get_option( 'thumbpress_convert_space_saved', 0 );
			update_option( 'thumbpress_convert_space_saved', $cumulative + $saved_bytes );
			thumbpress_add_space_saved( $saved_bytes );
		}

		$this->delete_cache( 'stat_not_webp' );
		$this->delete_cache( 'stat_not_avif' );
		$this->delete_cache( 'stat_unoptimized' );

		return $this->response_success(
			array(
				'message' => __( 'Image converted successfully.', 'image-sizes' ),
			)
		);
	}

	private function convert_image_to_webp( $source ) {
		$webp_controller = new Convert_Webp_Controller();
		return $webp_controller->convert_image_to_webp( $source );
	}
}
