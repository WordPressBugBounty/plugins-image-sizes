<?php
namespace Codexpert\ThumbPress\API;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Helpers\Utility;
use Codexpert\ThumbPress\Traits\Rest;
use Codexpert\ThumbPress\Traits\Cache;
use Codexpert\ThumbPress\Controllers\Common\Convert_Webp as Convert_Webp_Controller;

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
		$limit       = $limit ? absint( $limit ) : 10;
		$last_id     = absint( $request->get_param( 'last_id' ) );
		$processed   = absint( $request->get_param( 'processed' ) );
		$space_saved = absint( $request->get_param( 'space_saved' ) );
		$chunk_saved = 0;

		$not_found_prev = absint( $request->get_param( 'not_found' ) );
		$failed_prev    = absint( $request->get_param( 'failed' ) );
		$converted_prev = absint( $request->get_param( 'converted' ) );

		$file_formats = $request->get_param( 'file_formats' );
		if ( empty( $file_formats ) ) {
			return $this->response_error( __( 'No file formats selected.', 'image-sizes' ) );
		}

		$image_types = $this->formats_to_mime_types( $file_formats );

		if ( empty( $image_types ) ) {
			return $this->response_error( __( 'No valid file formats selected.', 'image-sizes' ) );
		}

		global $wpdb;

		$mime_in = "'" . implode( "','", array_map( 'esc_sql', $image_types ) ) . "'";

		if ( $last_id === 0 ) {
			$total_attachments = (int) $wpdb->get_var(
				"SELECT COUNT(ID)
				FROM {$wpdb->posts}
				WHERE post_type = 'attachment'
				AND post_mime_type IN ($mime_in)
				AND post_status != 'trash'"
			);
			update_option( 'thumbpress_now_convert_total_image', $total_attachments );
		} else {
			$total_attachments = (int) get_option( 'thumbpress_now_convert_total_image', 0 );
		}

		// Cursor pagination by ID: converted images change mime and drop out of the
		// filter, but we never look back, so each attachment is visited exactly once.
		$query = $wpdb->prepare(
			"
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type IN ($mime_in)
			AND post_status != 'trash'
			AND ID > %d
			ORDER BY ID ASC
			LIMIT %d
		",
			$last_id,
			$limit
		);

		$attachments = $wpdb->get_results( $query );

		if ( ! $attachments ) {
			// First call with no rows means there is nothing to convert at all.
			if ( $last_id === 0 ) {
				return $this->response_error(
					__( 'No images found.', 'image-sizes' ),
					array(
						'status' => 2,
					)
				);
			}

			// Otherwise we've reached the end of the set — report completion.
			update_option( 'thumbpress_convert_last_completed_time', wp_date( 'U' ) );
			$this->delete_cache( 'stat_not_webp' );
			$this->delete_cache( 'stat_not_avif' );
			$this->delete_cache( 'stat_unoptimized' );

			return $this->response_success(
				array(
					'message'     => __( 'Congratulations, Converting Images to WebP is Completed!', 'image-sizes' ),
					'last_id'     => $last_id,
					'processed'   => $processed,
					'progress'    => 100,
					'converted'   => $converted_prev,
					'remaining'   => 0,
					'total'       => $total_attachments,
					'space_saved' => $space_saved,
					'not_found'   => $not_found_prev,
					'failed'      => $failed_prev,
					'is_complete' => true,
				)
			);
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$batch_not_found = 0;
		$batch_failed    = 0;
		$batch_converted = 0;
		$new_last_id     = $last_id;
		$url_rewrites    = array();

		foreach ( $attachments as $attachment ) {
			$img_id      = (int) $attachment->ID;
			$new_last_id = $img_id;
			$main_img    = get_attached_file( $img_id );

			if ( ! $main_img || ! file_exists( $main_img ) ) {
				$batch_not_found++;
				continue;
			}

			$file_info = pathinfo( $main_img );
			$extension = strtolower( $file_info['extension'] ?? '' );
			$main_img  = str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );

			if ( ! file_exists( $main_img ) ) {
				$batch_not_found++;
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

			$webp_file_path = $this->convert_image_to_webp( $main_img );

			if ( is_wp_error( $webp_file_path ) ) {
				// Genuine conversion failure (unsupported/decode/memory) — tracked separately
				// from missing source files so the UI can show a distinct "Failed" count.
				$batch_failed++;
				continue;
			}

			$webp_metadata = wp_generate_attachment_metadata( $img_id, $webp_file_path );

			// Metadata generation failed — roll back. Delete the WebP we just created so a retry
			// doesn't collide into a -1 copy and orphan this one, and leave the original attachment
			// + its files untouched. Deleting the old files now would strand the DB on metadata that
			// still references them (blank thumbnails, "media still says jpeg").
			if ( ! $webp_metadata || is_wp_error( $webp_metadata ) ) {
				if ( file_exists( $webp_file_path ) ) {
					wp_delete_file( $webp_file_path );
				}
				$batch_failed++;
				continue;
			}

			wp_update_attachment_metadata( $img_id, $webp_metadata );

			// Keep _wp_attached_file aligned with metadata['file']. For big images WP scales the
			// converted file and points the attachment at the -scaled copy; forcing the non-scaled
			// path here would orphan the -scaled file on delete and serve the full-size image.
			if ( ! empty( $webp_metadata['file'] ) ) {
				update_post_meta( $img_id, '_wp_attached_file', $webp_metadata['file'] );
			} else {
				update_attached_file( $img_id, $webp_file_path );
			}
			wp_update_post(
				array(
					'ID'             => $img_id,
					'post_mime_type' => 'image/webp',
					'guid'           => wp_get_attachment_url( $img_id ),
				)
			);

			// Delete old files only AFTER the DB points at the new WebP. If the request is
			// interrupted during metadata generation above, the original survives and the DB
			// still resolves it — instead of a webp on disk with the attachment stuck on jpeg.
			foreach ( ( $old_metadata['sizes'] ?? array() ) as $size_name => $size_data ) {
				if ( 'image/svg+xml' == $size_data['mime-type'] ) {
					continue;
				}
				wp_delete_file( $thumb_dir . $size_data['file'] );
			}
			if ( file_exists( $main_img ) ) {
				wp_delete_file( $main_img );
			}
			$scaled_path = str_replace( ".{$extension}", "-scaled.{$extension}", $main_img );
			if ( $scaled_path !== $main_img && file_exists( $scaled_path ) ) {
				wp_delete_file( $scaled_path );
			}

			Utility::refresh_file_meta( $img_id, $webp_file_path );

			// Queue the stored-URL rewrite; flushed as one batch after the loop so a full
			// convert chunk is 3 table scans total, not 3 unindexed LIKE scans per image (#385).
			$url_rewrites[] = array(
				'attachment_id' => $img_id,
				'old_main_path' => $main_img,
				'old_metadata'  => $old_metadata,
			);

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
			$batch_converted++;
		}

		// One rewrite pass for the whole chunk (3 table scans) instead of 3 per image.
		if ( ! empty( $url_rewrites ) ) {
			Utility::replace_attachment_urls_batch( $url_rewrites );
		}

		$processed_new   = $processed + count( $attachments );
		$total_not_found = $not_found_prev + $batch_not_found;
		$total_failed    = $failed_prev + $batch_failed;
		$converted_total = $converted_prev + $batch_converted;
		$is_complete     = ( $processed_new >= $total_attachments );
		$progress        = $total_attachments > 0 ? ( $processed_new / $total_attachments ) * 100 : 100;
		$progress        = $is_complete ? 100 : min( 99, (int) floor( $progress ) );

		// Persist stats so dashboard can read them.
		update_option( 'thumbpress_convert_total_processed', $processed_new );
		update_option( 'thumbpress_convert_total_converted', $converted_total );
		update_option( 'thumbpress_convert_space_saved', $space_saved );
		update_option( 'thumbpress_webp_total_not_found', $total_not_found );
		update_option( 'thumbpress_convert_total_failed', $total_failed );
		thumbpress_add_space_saved( $chunk_saved );

		$message = __( 'Converting Images to WebP...', 'image-sizes' );
		if ( $is_complete ) {
			$message = __( 'Congratulations, Converting Images to WebP is Completed!', 'image-sizes' );
			update_option( 'thumbpress_convert_last_completed_time', wp_date( 'U' ) );
			$this->delete_cache( 'stat_not_webp' );
			$this->delete_cache( 'stat_not_avif' );
			$this->delete_cache( 'stat_unoptimized' );
		}

		return $this->response_success(
			array(
				'message'     => $message,
				'last_id'     => $new_last_id,
				'processed'   => $processed_new,
				'progress'    => $progress,
				'converted'   => $converted_total,
				'remaining'   => max( 0, $total_attachments - $processed_new ),
				'total'       => $total_attachments,
				'space_saved' => $space_saved,
				'not_found'   => $total_not_found,
				'failed'      => $total_failed,
				'is_complete' => $is_complete,
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
		delete_option( 'thumbpress_convert_total_processed' );
		delete_option( 'thumbpress_convert_total_processd' );
		delete_option( 'thumbpress_convert_total_converted' );
		delete_option( 'thumbpress_convert_space_saved' );
		delete_option( 'thumbpress_webp_total_not_found' );
		delete_option( 'thumbpress_convert_total_failed' );

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
				'last_id'      => 0,
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
		$processed = (int) Utility::get_option_with_legacy_fallback(
			'thumbpress_convert_total_processed',
			'thumbpress_convert_total_processd',
			0
		);
		$converted      = (int) get_option( 'thumbpress_convert_total_converted', 0 );
		$total          = (int) get_option( 'thumbpress_now_convert_background_total_images', 0 );
		$completed = Utility::get_option_with_legacy_fallback(
			'thumbpress_convert_last_completed_time',
			'convert_last_completed_time'
		);
		$completed_time = $completed ? date_i18n( 'g:i a F j, Y', $completed ) : '';

		$space_saved = (int) get_option( 'thumbpress_convert_space_saved', 0 );
		$not_found   = (int) get_option( 'thumbpress_webp_total_not_found', 0 );
		$failed      = (int) get_option( 'thumbpress_convert_total_failed', 0 );

		return $this->response_success(
			array(
				'progress'       => round( $progress ),
				'processed'      => $processed,
				'converted'      => $converted,
				'remaining'      => max( 0, $total - $processed ),
				'total'          => $total,
				'space_saved'    => $space_saved,
				'not_found'      => $not_found,
				'failed'         => $failed,
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

		$webp_file_path = $this->convert_image_to_webp( $main_img );

		if ( is_wp_error( $webp_file_path ) ) {
			return $this->response_error( $webp_file_path->get_error_message() );
		}

		$webp_metadata = wp_generate_attachment_metadata( $img_id, $webp_file_path );

		// Metadata generation failed — roll back. Delete the WebP we just created (no orphan, no
		// retry collision) and leave the original attachment + its files untouched. Deleting the
		// old files now would strand the DB on metadata that still references them.
		if ( ! $webp_metadata || is_wp_error( $webp_metadata ) ) {
			if ( file_exists( $webp_file_path ) ) {
				wp_delete_file( $webp_file_path );
			}
			$reason = is_wp_error( $webp_metadata ) ? $webp_metadata->get_error_message() : __( 'metadata generation returned nothing', 'image-sizes' );
			return $this->response_error(
				sprintf(
					/* translators: %s: underlying reason metadata generation failed */
					__( 'Converted the image but could not generate its metadata (%s); rolled back.', 'image-sizes' ),
					$reason
				)
			);
		}

		wp_update_attachment_metadata( $img_id, $webp_metadata );

		// Keep _wp_attached_file aligned with metadata['file']. For big images WP scales the
		// converted file and points the attachment at the -scaled copy; forcing the non-scaled
		// path here would orphan the -scaled file on delete and serve the full-size image.
		if ( ! empty( $webp_metadata['file'] ) ) {
			update_post_meta( $img_id, '_wp_attached_file', $webp_metadata['file'] );
		} else {
			update_attached_file( $img_id, $webp_file_path );
		}
		wp_update_post(
			array(
				'ID'             => $img_id,
				'post_mime_type' => 'image/webp',
				'guid'           => wp_get_attachment_url( $img_id ),
			)
		);

		// Delete old files only AFTER the DB points at the new WebP. If the request is
		// interrupted during metadata generation above, the original survives and the DB
		// still resolves it — instead of a webp on disk with the attachment stuck on jpeg.
		foreach ( ( $old_metadata['sizes'] ?? array() ) as $size_data ) {
			if ( 'image/svg+xml' === $size_data['mime-type'] ) {
				continue;
			}
			wp_delete_file( $thumb_dir . $size_data['file'] );
		}
		if ( file_exists( $main_img ) ) {
			wp_delete_file( $main_img );
		}
		$scaled_path = str_replace( ".{$extension}", "-scaled.{$extension}", $main_img );
		if ( $scaled_path !== $main_img && file_exists( $scaled_path ) ) {
			wp_delete_file( $scaled_path );
		}

		Utility::refresh_file_meta( $img_id, $webp_file_path );

		// Repoint stored URLs (post content, meta, options) at the new file.
		Utility::replace_attachment_urls( $img_id, $main_img, $old_metadata );

		$new_size         = file_exists( $webp_file_path ) ? filesize( $webp_file_path ) : 0;
		$updated_metadata = wp_get_attachment_metadata( $img_id );
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
