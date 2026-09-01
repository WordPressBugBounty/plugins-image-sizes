<?php
namespace Codexpert\ThumbPress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Helpers\Utility;
use Codexpert\ThumbPress\Traits\Hook;
use Codexpert\ThumbPress\Traits\Asset;
use Codexpert\ThumbPress\Traits\Cache;

class Thumbnails {

	use Hook;
	use Asset;
	use Cache;

	/**
	 * Offset watermark the watchdog resumes a stalled background regeneration from.
	 */
	const OFFSET_OPTION = 'thumbpress_regenerate_offset';

	/**
	 * ID of the last attachment processed, so a batch pages by cursor instead of a deep OFFSET.
	 */
	const LAST_ID_OPTION = 'thumbpress_regenerate_last_id';

	const BATCH_TIME_BUDGET = 15;

	const INFLIGHT_OPTION = 'thumbpress_regenerate_inflight';

	const FAILED_IDS_OPTION = 'thumbpress_regenerate_failed_ids';

	const FAILED_IDS_LIMIT = 500;

	public function __construct() {
		$this->filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_image_sizes' ) );
		$this->filter( 'big_image_size_threshold', array( $this, 'filter_big_image_size' ) );
		$this->action( 'thumbpress_regenerate_all_image', array( $this, 'regenerate_all_image' ), 10, 2 );
		$this->action( 'admin_init', array( $this, 'rearm_stalled_regeneration' ) );
		$this->action( 'activated_plugin', array( $this, 'clear_size_caches' ) );
		$this->action( 'deactivated_plugin', array( $this, 'clear_size_caches' ) );
		$this->action( 'upgrader_process_complete', array( $this, 'clear_size_on_plugin_update' ), 10, 2 );
		$this->action( 'add_attachment', array( $this, 'clear_thumbnail_count_cache' ) );
		$this->action( 'delete_attachment', array( $this, 'clear_thumbnail_count_cache' ) );
		$this->filter( 'attachment_fields_to_edit', array( $this, 'display_regenerate_btn' ), 10, 2 );
		$this->action( 'admin_enqueue_scripts', array( $this, 'enqueue_regenerate_script' ) );
	}

	public function enqueue_regenerate_script( $hook ) {
		if ( $hook !== 'post.php' && $hook !== 'upload.php' ) {
			return;
		}

		$this->enqueue_script(
			'thumbpress-regenerate-single',
			THUMBPRESS_PLUGIN_URL . 'assets/admin/js/regenerate-single.js',
			array( 'image-sizes_admin' )
		);
	}

	public function display_regenerate_btn( $form_fields, $post ) {
		if ( strpos( $post->post_mime_type, 'image/' ) !== 0 ) {
			return $form_fields;
		}

		$icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right:4px;vertical-align:middle"><path d="M1 4V10H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M23 20V14H17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M20.49 9C19.9828 7.56678 19.1209 6.28392 17.9845 5.27542C16.8482 4.26693 15.4745 3.56445 13.9917 3.22836C12.5089 2.89227 10.9652 2.93353 9.50241 3.34851C8.03963 3.76349 6.70454 4.53875 5.62 5.6L1 10M23 14L18.38 18.4C17.2955 19.4613 15.9604 20.2365 14.4976 20.6515C13.0348 21.0665 11.4911 21.1077 10.0083 20.7716C8.52547 20.4355 7.15183 19.7331 6.01547 18.7246C4.87911 17.7161 4.01717 16.4332 3.51 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

		$html = sprintf(
			'<button id="thumbpress-regenerate-image" data-image_id="%1$s" class="button thumbpress_img_btn" type="button">%2$s%3$s</button>',
			esc_attr( $post->ID ),
			$icon,
			esc_html__( 'Regenerate Thumbnails', 'image-sizes' )
		);

		$form_fields['thumbpress_regenerate_image'] = array(
			'label' => __( 'Regenerate Thumbnails', 'image-sizes' ),
			'input' => 'html',
			'html'  => $html,
		);

		return $form_fields;
	}

	public function clear_size_on_plugin_update( $upgrader, $options ) {
		if ( isset( $options['type'] ) && 'plugin' === $options['type'] ) {
			$this->clear_size_caches();
		}
	}

	public function clear_size_caches() {
		$this->delete_cache( 'all_sizes' );
		$this->delete_cache( 'disabled_sizes' );
		$this->delete_cache( 'stat_sizes_data' );
	}

	public function clear_thumbnail_count_cache() {
		$this->delete_cache( 'stat_total_thumbnails' );
	}

	/**
	 * Remove disabled sizes from the list of sizes WordPress generates.
	 */
	public function filter_image_sizes( $sizes ) {
		$option   = get_option( 'prevent_image_sizes', array() );
		$disables = isset( $option['disables'] ) ? $option['disables'] : array();

		if ( ! empty( $disables ) ) {
			foreach ( $disables as $disable ) {
				unset( $sizes[ $disable ] );
			}
		}

		return $sizes;
	}

	/**
	 * Disable scaled image if 'scaled' is in the disabled list.
	 */
	public function filter_big_image_size( $threshold ) {
		$option   = get_option( 'prevent_image_sizes', array() );
		$disables = isset( $option['disables'] ) ? $option['disables'] : array();

		return in_array( 'scaled', $disables, true ) ? false : $threshold;
	}

	/**
	 * Regenerate thumbnails for a single attachment.
	 * Shared by foreground (Regenerate::regen_now) and background batch.
	 *
	 * @return array{skipped:bool, thumbs_deleted:int, thumbs_created:int, space_saved:int}
	 */
	public function regenerate_one( $image_id ) {
		$result = array(
			'skipped'        => false,
			'failed'         => false,
			'thumbs_deleted' => 0,
			'thumbs_created' => 0,
			'space_saved'    => 0,
		);

		$main_img = get_attached_file( $image_id );
		if ( ! $main_img || ! file_exists( $main_img ) ) {
			$result['skipped'] = true;
			return $result;
		}

		// The -scaled rendition can carry a different extension than the original.
		$attached_file = $main_img;
		$original      = wp_get_original_image_path( $image_id );
		if ( $original && file_exists( $original ) ) {
			$main_img = $original;
		}
		$old_metadata = wp_get_attachment_metadata( $image_id );
		$thumb_dir    = dirname( $attached_file ) . DIRECTORY_SEPARATOR;

		$old_thumb_size = 0;
		$old_paths      = array();
		if ( ! empty( $old_metadata['sizes'] ) ) {
			foreach ( $old_metadata['sizes'] as $size_data ) {
				if ( isset( $size_data['mime-type'] ) && 'image/svg+xml' === $size_data['mime-type'] ) {
					continue;
				}
				$thumb_path = $thumb_dir . $size_data['file'];
				if ( file_exists( $thumb_path ) ) {
					$old_thumb_size                  += filesize( $thumb_path );
					$old_paths[ $size_data['file'] ]  = $thumb_path;
					++$result['thumbs_deleted'];
				}
			}
		}

		if ( $attached_file !== $main_img && file_exists( $attached_file ) ) {
			$old_thumb_size                          += filesize( $attached_file );
			$old_paths[ basename( $attached_file ) ]  = $attached_file;
			++$result['thumbs_deleted'];
		}

		// Default so the size-calculation block below is safe even if regeneration fails
		// (the if-guard may leave $updated_metadata unset otherwise).
		$updated_metadata = array();

		$new_thumbs = wp_generate_attachment_metadata( $image_id, $main_img );

		// Guard explicitly (WP_Error is truthy) and keep the old renditions on failure.
		if ( $new_thumbs && ! is_wp_error( $new_thumbs ) ) {
			wp_update_attachment_metadata( $image_id, $new_thumbs );

			$updated_metadata = wp_get_attachment_metadata( $image_id );
			if ( ! empty( $updated_metadata['file'] ) ) {
				update_post_meta( $image_id, '_wp_attached_file', $updated_metadata['file'] );
			}
		} else {
			// Metadata generation failed — surface as a distinct "Failed" count.
			$result['failed']         = true;
			$result['thumbs_deleted'] = 0;
			return $result;
		}

		// Count created thumbnails as unique physical files so the tally mirrors
		// thumbs_deleted: dedupe size keys that point at the same file, skip SVG,
		// and include the regenerated -scaled full-size file below.
		$new_thumb_size = 0;
		$created_files  = array();
		if ( ! empty( $updated_metadata['sizes'] ) ) {
			foreach ( $updated_metadata['sizes'] as $new_thumb ) {
				if ( empty( $new_thumb['file'] ) || 'image/svg+xml' === ( $new_thumb['mime-type'] ?? '' ) ) {
					continue;
				}
				if ( isset( $created_files[ $new_thumb['file'] ] ) ) {
					continue;
				}

				$new_thumb_path = $thumb_dir . $new_thumb['file'];
				if ( ! empty( $new_thumb['filesize'] ) ) {
					$new_thumb_size += $new_thumb['filesize'];
					$created_files[ $new_thumb['file'] ] = true;
				} elseif ( file_exists( $new_thumb_path ) ) {
					$new_thumb_size += filesize( $new_thumb_path );
					$created_files[ $new_thumb['file'] ] = true;
				}
			}
		}

		$scaled_file = get_attached_file( $image_id );
		if ( $scaled_file && $scaled_file !== $main_img && file_exists( $scaled_file ) ) {
			$new_thumb_size += filesize( $scaled_file );
			// Mirror the deleted side, which counts the old -scaled file.
			$created_files[ basename( $scaled_file ) ] = true;
		}

		// Delete only now that the fresh metadata is stored, and never what it still names.
		foreach ( $old_paths as $old_file => $old_path ) {
			if ( ! isset( $created_files[ $old_file ] ) && file_exists( $old_path ) ) {
				wp_delete_file( $old_path );
			}
		}

		$result['space_saved']    = max( 0, $old_thumb_size - $new_thumb_size );
		$result['thumbs_created'] = count( $created_files );

		return $result;
	}

	/**
	 * Re-arm a background regeneration whose batch died before scheduling its successor (#466).
	 */
	public function rearm_stalled_regeneration() {
		$total = (int) get_option( 'thumbpress_regenerate_total_image', 0 );
		if ( $total < 1 ) {
			return;
		}

		if ( get_option( 'thumbpress_regenerate_cancelled', false ) ) {
			return;
		}

		if ( (float) get_option( 'thumbpress_regenerate_progress', 0 ) >= 100 ) {
			return;
		}

		$offset = (int) get_option( self::OFFSET_OPTION, 0 );
		if ( $offset >= $total ) {
			return;
		}

		Utility::rearm_batch(
			'thumbpress_regenerate_all_image',
			array(
				'offset'  => $offset,
				'last_id' => (int) get_option( self::LAST_ID_OPTION, 0 ),
			)
		);
	}

	/**
	 * Action Scheduler callback for background regeneration.
	 * Processes a batch then schedules the next batch if not done.
	 */
	public function regenerate_all_image( $offset, $last_id = 0 ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		global $wpdb;

		$last_id           = absint( $last_id );
		$limit             = get_option( 'thumbpress_regenerate_limit', 500 );
		$total_attachments = (int) get_option( 'thumbpress_regenerate_total_image', 0 );
		$thumbs_deleteds   = (int) get_option( 'thumbpress_regenerate_total_deleted', 0 );
		$thumbs_createds   = (int) get_option( 'thumbpress_regenerate_total_created', 0 );

		// No total means no run: cancelled, so finishing it here would restore what cancel deleted.
		if ( $total_attachments < 1 ) {
			return;
		}

		$images = $wpdb->get_results(
			$wpdb->prepare(
				"
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'image%%'
			AND post_status != 'trash'
			AND ID > %d
			ORDER BY ID ASC
			LIMIT %d
		",
				$last_id,
				$limit
			)
		);

		// A dry page means the library shrank mid-run, so the recorded total is never reached.
		if ( count( $images ) === 0 ) {
			$this->finish_regeneration( $offset );
			return;
		}

		$total_processed = (int) get_option( 'thumbpress_regenerate_total_processed', 0 );
		$total_not_found = (int) get_option( 'thumbpress_regenerate_total_not_found', 0 );
		$total_failed    = (int) get_option( 'thumbpress_regenerate_total_failed', 0 );
		$total_space     = (int) get_option( 'thumbpress_regenerate_space_saved', 0 );
		$total_deleted   = $thumbs_deleteds;
		$total_created   = $thumbs_createds;

		$started     = microtime( true );
		$done        = 0;
		$new_last_id = $last_id;
		$inflight    = (int) get_option( self::INFLIGHT_OPTION, 0 );

		foreach ( $images as $image ) {
			// A marker left from the previous run means this image killed it — count it failed and move past.
			if ( $inflight && (int) $image->ID === $inflight ) {
				$inflight = 0;
				delete_option( self::INFLIGHT_OPTION );
				++$done;
				++$total_failed;
				++$total_processed;
				$new_last_id = (int) $image->ID;
				$this->record_failed_image( (int) $image->ID );
				$this->persist_regenerate_progress(
					$offset + $done,
					$total_attachments,
					$total_space,
					$total_processed,
					$total_deleted,
					$total_created,
					$total_not_found,
					$total_failed,
					$new_last_id
				);
				continue;
			}

			update_option( self::INFLIGHT_OPTION, (int) $image->ID );

			$res = $this->regenerate_one( $image->ID );
			++$done;

			delete_option( self::INFLIGHT_OPTION );
			$new_last_id = (int) $image->ID;

			if ( ! empty( $res['skipped'] ) ) {
				++$total_not_found;
			} elseif ( ! empty( $res['failed'] ) ) {
				++$total_failed;
				++$total_processed;
				$this->record_failed_image( (int) $image->ID );
			} else {
				++$total_processed;
				$total_deleted += $res['thumbs_deleted'];
				$total_created += $res['thumbs_created'];
				$total_space   += $res['space_saved'];
				thumbpress_add_space_saved( $res['space_saved'] );
			}

			$this->persist_regenerate_progress(
				$offset + $done,
				$total_attachments,
				$total_space,
				$total_processed,
				$total_deleted,
				$total_created,
				$total_not_found,
				$total_failed,
				$new_last_id
			);

			if ( microtime( true ) - $started >= self::BATCH_TIME_BUDGET ) {
				break;
			}
		}

		$count = $offset + $done;

		if ( $count < $total_attachments && ! get_option( 'thumbpress_regenerate_cancelled', false ) ) {
			as_schedule_single_action(
				wp_date( 'U' ) - 10,
				'thumbpress_regenerate_all_image',
				array(
					'offset'  => $count,
					'last_id' => $new_last_id,
				)
			);
		} else {
			$this->finish_regeneration( $count );
		}
	}

	/**
	 * Close out a run so the progress screen can leave 'in progress'.
	 *
	 * @param int $count Images consumed by the run.
	 */
	private function finish_regeneration( $count ) {
		$total = (int) get_option( 'thumbpress_regenerate_total_image', 0 );

		if ( $count < $total ) {
			update_option( 'thumbpress_regenerate_total_image', $count );
		}

		update_option( 'thumbpress_regenerate_progress', 100 );
		update_option( self::OFFSET_OPTION, $count );
		delete_option( self::INFLIGHT_OPTION );
		update_option( 'thumbpress_regenerate_last_schedule_time', wp_date( 'U' ) );
		$this->clear_thumbnail_count_cache();
	}

	/**
	 * Write the run cursor and counters so a killed batch still advances.
	 *
	 * @param int $count      Images consumed so far across the run.
	 * @param int $total      Total images in the run.
	 * @param int $space      Cumulative bytes saved.
	 * @param int $processed  Cumulative processed count.
	 * @param int $deleted    Cumulative thumbnails deleted.
	 * @param int $created    Cumulative thumbnails created.
	 * @param int $not_found  Cumulative missing-file count.
	 * @param int $failed     Cumulative failed count.
	 * @param int $last_id    ID of the last attachment actually processed.
	 */
	private function persist_regenerate_progress( $count, $total, $space, $processed, $deleted, $created, $not_found, $failed, $last_id ) {
		$progress = $total > 0 ? min( ( $count / $total ) * 100, 100 ) : 100;

		update_option( 'thumbpress_regenerate_space_saved', $space );
		update_option( 'thumbpress_regenerate_progress', $progress );
		update_option( self::OFFSET_OPTION, $count );
		update_option( self::LAST_ID_OPTION, $last_id );
		update_option( 'thumbpress_regenerate_total_processed', $processed );
		update_option( 'thumbpress_regenerate_total_deleted', $deleted );
		update_option( 'thumbpress_regenerate_total_created', $created );
		update_option( 'thumbpress_regenerate_total_not_found', $not_found );
		update_option( 'thumbpress_regenerate_total_failed', $failed );
	}

	/**
	 * Add an attachment to the run's failed list so the UI can show which images were skipped.
	 *
	 * @param int $image_id Attachment ID.
	 */
	private function record_failed_image( $image_id ) {
		$failed = (array) get_option( self::FAILED_IDS_OPTION, array() );

		if ( in_array( $image_id, $failed, true ) || count( $failed ) >= self::FAILED_IDS_LIMIT ) {
			return;
		}

		$failed[] = $image_id;

		update_option( self::FAILED_IDS_OPTION, $failed );
	}
}
