<?php
namespace Thumbpress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Traits\Hook;
use Thumbpress\Traits\Asset;
use Thumbpress\Traits\Cache;

class Thumbnails {

	use Hook;
	use Asset;
	use Cache;

	public function __construct() {
		$this->filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_image_sizes' ) );
		$this->filter( 'big_image_size_threshold', array( $this, 'filter_big_image_size' ) );
		$this->action( 'thumbpress_regenerate_all_image', array( $this, 'regenerate_all_image' ) );
		$this->action( 'thumbpress_thumbnail_sizes_saved', array( $this, 'clear_size_caches' ) );
		$this->action( 'activated_plugin', array( $this, 'clear_size_caches' ) );
		$this->action( 'deactivated_plugin', array( $this, 'clear_size_caches' ) );
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

		return in_array( 'scaled', $disables ) ? false : $threshold;
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
			'thumbs_deleted' => 0,
			'thumbs_created' => 0,
			'space_saved'    => 0,
		);

		$main_img = get_attached_file( $image_id );
		if ( ! $main_img || ! file_exists( $main_img ) ) {
			$result['skipped'] = true;
			return $result;
		}

		$file_info    = pathinfo( $main_img );
		$extension    = strtolower( $file_info['extension'] );
		$main_img     = str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );
		$old_metadata = wp_get_attachment_metadata( $image_id );
		$thumb_dir    = dirname( $main_img ) . DIRECTORY_SEPARATOR;

		$old_thumb_size = 0;
		if ( ! empty( $old_metadata['sizes'] ) ) {
			foreach ( $old_metadata['sizes'] as $size_data ) {
				if ( isset( $size_data['mime-type'] ) && 'image/svg+xml' === $size_data['mime-type'] ) {
					continue;
				}
				$thumb_path = $thumb_dir . $size_data['file'];
				if ( file_exists( $thumb_path ) ) {
					$old_thumb_size += filesize( $thumb_path );
					wp_delete_file( $thumb_path );
					++$result['thumbs_deleted'];
				}
			}
		}

		if ( strpos( $file_info['basename'], "-scaled.{$extension}" ) !== false ) {
			$scaled_path = $thumb_dir . $file_info['basename'];
			if ( file_exists( $scaled_path ) ) {
				$old_thumb_size += filesize( $scaled_path );
				wp_delete_file( $scaled_path );
				++$result['thumbs_deleted'];
			}
		}

		$new_thumbs = wp_generate_attachment_metadata( $image_id, $main_img );
		wp_update_attachment_metadata( $image_id, $new_thumbs );

		$updated_metadata = wp_get_attachment_metadata( $image_id );
		if ( ! empty( $updated_metadata['file'] ) ) {
			update_post_meta( $image_id, '_wp_attached_file', $updated_metadata['file'] );
		}

		$new_thumb_size = 0;
		if ( ! empty( $updated_metadata['sizes'] ) ) {
			foreach ( $updated_metadata['sizes'] as $new_thumb ) {
				if ( ! empty( $new_thumb['filesize'] ) ) {
					$new_thumb_size += $new_thumb['filesize'];
				} else {
					$new_thumb_path = $thumb_dir . $new_thumb['file'];
					if ( file_exists( $new_thumb_path ) ) {
						$new_thumb_size += filesize( $new_thumb_path );
					}
				}
			}
		}

		$scaled_file = get_attached_file( $image_id );
		if ( $scaled_file && $scaled_file !== $main_img && file_exists( $scaled_file ) ) {
			$new_thumb_size += filesize( $scaled_file );
		}

		$result['space_saved']    = max( 0, $old_thumb_size - $new_thumb_size );
		$result['thumbs_created'] = is_array( $new_thumbs['sizes'] ?? null ) ? count( $new_thumbs['sizes'] ) : 0;

		return $result;
	}

	/**
	 * Action Scheduler callback for background regeneration.
	 * Processes a batch then schedules the next batch if not done.
	 */
	public function regenerate_all_image( $offset ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		global $wpdb;

		$limit             = get_option( 'thumbpress_regenerate_limit', 500 );
		$total_attachments = get_option( 'thumbpress_regenerate_total_image' );
		$thumbs_deleteds   = (int) get_option( 'thumbpress_regenerate_total_deleted', 0 );
		$thumbs_createds   = (int) get_option( 'thumbpress_regenerate_total_created', 0 );

		$images = $wpdb->get_results(
			$wpdb->prepare(
				"
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'image%%'
			AND post_status != 'trash'
			LIMIT %d OFFSET %d
		",
				$limit,
				$offset
			)
		);

		if ( count( $images ) === 0 ) {
			return;
		}

		$thumbs_created    = 0;
		$thumbs_deleted    = 0;
		$batch_space_saved = 0;

		foreach ( $images as $image ) {
			$res                = $this->regenerate_one( $image->ID );
			$thumbs_deleted    += $res['thumbs_deleted'];
			$thumbs_created    += $res['thumbs_created'];
			$batch_space_saved += $res['space_saved'];
		}

		thumbpress_add_space_saved( $batch_space_saved );

		$total_deleted = $thumbs_deleteds + $thumbs_deleted;
		$total_created = $thumbs_createds + $thumbs_created;
		$count         = $offset + count( $images );
		$progress      = ( $count / $total_attachments ) * 100;
		$progress      = min( $progress, 100 );

		$cumulative_space = (int) get_option( 'thumbpress_regenerate_space_saved', 0 );
		update_option( 'thumbpress_regenerate_space_saved', $cumulative_space + $batch_space_saved );
		update_option( 'thumbpress_regenerate_progress', $progress );
		update_option( 'thumbpress_regenerate_total_processed', $count );
		update_option( 'thumbpress_regenerate_total_deleted', $total_deleted );
		update_option( 'thumbpress_regenerate_total_created', $total_created );

		if ( $count < $total_attachments ) {
			$new_offset = $offset + $limit;
			as_schedule_single_action( wp_date( 'U' ) - 10, 'thumbpress_regenerate_all_image', array( 'offset' => $new_offset ) );
		} else {
			update_option( 'thumbpress_regenerate_last_schedule_time', wp_date( 'U' ) );
			$this->clear_thumbnail_count_cache();
		}
	}
}
