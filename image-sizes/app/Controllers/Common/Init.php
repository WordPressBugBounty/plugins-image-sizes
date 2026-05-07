<?php
namespace Thumbpress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Thumbpress\API\Dashboard;
use Thumbpress\Helpers\Utility;
use Thumbpress\Traits\Hook;
use Thumbpress\Traits\Asset;
use Thumbpress\Traits\Cache;

class Init {

	use Hook;
	use Asset;
	use Cache;

	/**
	 * Constructor to add all hooks.
	 */
	public function __construct() {
		$this->action( 'wp_head', array( $this, 'modal' ) );
		$this->action( 'admin_head', array( $this, 'modal' ) );
		$this->action( 'wp_enqueue_scripts', array( $this, 'add_assets' ) );
		$this->action( 'admin_enqueue_scripts', array( $this, 'add_assets' ) );
		$this->action( 'admin_init', array( $this, 'schedule_hash_generation' ) );
		$this->action( 'admin_init', array( $this, 'schedule_initial_stat_cache_build' ) );
		$this->action( 'add_attachment', array( $this, 'hash_on_upload' ) );
		$this->action( 'add_attachment', array( $this, 'clear_media_cache' ) );
		$this->action( 'add_attachment', array( $this, 'schedule_stat_cache_refresh' ), 20 );
		$this->action( 'delete_attachment', array( $this, 'clear_media_cache' ) );
		$this->action( 'delete_attachment', array( $this, 'schedule_stat_cache_refresh' ), 20 );
		$this->action( 'thumbpress_thumbnail_sizes_saved', array( $this, 'clear_media_cache' ) );
		$this->action( 'thumbpress_generate_image_hashes', array( $this, 'generate_hashes_batch' ) );
		$this->action( 'thumbpress_build_stat_cache', array( $this, 'build_stat_cache' ) );
		$this->action( 'thumbpress_file_meta_refreshed', array( $this, 'clear_duplicate_cache' ) );
	}

	public function modal() {
		echo '
		<div id="image-sizes-modal" style="display: none">
			<img id="image-sizes-modal-loader" src="' . esc_attr( THUMBPRESS_ASSETS_URL . 'common/img/loader.gif' ) . '" />
		</div>';
	}

	public function add_assets() {
		global $current_screen;

		if ( isset( $current_screen->base ) && ( strpos( $current_screen->base, 'thumbpress' ) !== false || strpos( $current_screen->base, 'image-sizes' ) !== false ) || ! is_admin() ) {

			$this->enqueue_script(
				'tailwind-css',
				THUMBPRESS_PLUGIN_URL . 'build/tailwind.bundle.js'
			);

			$this->enqueue_script(
				'image-sizes_common',
				THUMBPRESS_ASSETS_URL . 'common/js/init.js'
			);

			$this->enqueue_style(
				'image-sizes_common',
				THUMBPRESS_ASSETS_URL . 'common/css/init.css'
			);
		}
	}

	/**
	 * Schedule batch hash generation for all existing images (runs once).
	 */
	public function schedule_hash_generation() {
		if ( get_option( 'thumbpress_hashes_scheduled' ) ) {
			return;
		}

		if ( ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		if ( as_has_scheduled_action( 'thumbpress_generate_image_hashes' ) ) {
			return;
		}

		as_schedule_single_action( wp_date( 'U' ) + 5, 'thumbpress_generate_image_hashes', array( 'offset' => 0 ) );
		update_option( 'thumbpress_hashes_scheduled', true );
	}

	/**
	 * One-time initial build of dashboard stat caches after hashes are ready.
	 */
	public function schedule_initial_stat_cache_build() {
		if ( get_option( 'thumbpress_stats_prewarmed' ) ) {
			return;
		}

		if ( ! get_option( 'thumbpress_hashes_generated' ) ) {
			return;
		}

		if ( ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		if ( as_has_scheduled_action( 'thumbpress_build_stat_cache' ) ) {
			return;
		}

		as_schedule_single_action( wp_date( 'U' ) - 10, 'thumbpress_build_stat_cache' );
		update_option( 'thumbpress_stats_prewarmed', true );
	}

	/**
	 * Schedule an immediate stat cache rebuild after media is added or deleted.
	 */
	public function schedule_stat_cache_refresh() {
		if ( ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		if ( as_has_scheduled_action( 'thumbpress_build_stat_cache' ) ) {
			return;
		}

		as_schedule_single_action( wp_date( 'U' ) - 10, 'thumbpress_build_stat_cache' );
	}

	public function clear_duplicate_cache( $attachment_id = null ) {
		$this->delete_cache( 'stat_duplicates' );
	}

	/**
	 * Action Scheduler callback: recompute and cache all dashboard stats.
	 */
	public function build_stat_cache() {
		( new Dashboard() )->build_cache();
	}

	/**
	 * Hash + size a newly uploaded image and store in post meta.
	 */
	public function hash_on_upload( $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		Utility::refresh_file_meta( $attachment_id );
		$this->clear_media_cache();
	}

	/**
	 * Clear caches when media is added or deleted.
	 */
	public function clear_media_cache( $attachment_id = null ) {
		$keys = array(
			'all_sizes',
			'disabled_sizes',
			'stat_total_images',
			'stat_unoptimized',
			'stat_not_compressed',
			'stat_not_webp',
			'stat_not_avif',
			'stat_duplicates',
			'stat_large_images',
			'stat_total_thumbnails',
		);

		foreach ( $keys as $key ) {
			$this->delete_cache( $key );
		}
	}

	/**
	 * Background batch: generate hashes for images missing the meta.
	 */
	public function generate_hashes_batch( $offset ) {
		global $wpdb;

		$limit = 200;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID
			 FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} pm_hash ON p.ID = pm_hash.post_id AND pm_hash.meta_key = %s
			 LEFT JOIN {$wpdb->postmeta} pm_size ON p.ID = pm_size.post_id AND pm_size.meta_key = %s
			 WHERE p.post_type = 'attachment'
			 AND p.post_mime_type LIKE %s
			 AND p.post_status != 'trash'
			 AND (pm_hash.post_id IS NULL OR pm_size.post_id IS NULL)
			 LIMIT %d",
				Utility::HASH_META_KEY,
				Utility::SIZE_META_KEY,
				'image/%',
				$limit
			)
		);

		if ( empty( $ids ) ) {
			update_option( 'thumbpress_hashes_generated', true );
			return;
		}

		foreach ( $ids as $id ) {
			Utility::refresh_file_meta( $id );
		}

		if ( count( $ids ) >= $limit ) {
			as_schedule_single_action( wp_date( 'U' ) + 5, 'thumbpress_generate_image_hashes', array( 'offset' => 0 ) );
		} else {
			update_option( 'thumbpress_hashes_generated', true );
			$this->delete_cache( 'stat_duplicates' );
		}
	}
}
