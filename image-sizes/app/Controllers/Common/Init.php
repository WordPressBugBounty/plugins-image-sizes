<?php
namespace Codexpert\ThumbPress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\API\Dashboard;
use Codexpert\ThumbPress\Helpers\Utility;
use Codexpert\ThumbPress\Traits\Hook;
use Codexpert\ThumbPress\Traits\Asset;
use Codexpert\ThumbPress\Traits\Cache;

class Init {

	use Hook;
	use Asset;
	use Cache;

	/**
	 * Bumping this re-evaluates the backfill once on sites that already flagged it done.
	 */
	const BACKFILL_VERSION = 2;

	/**
	 * Images hashed per batch.
	 */
	const BATCH_SIZE = 500;

	/**
	 * Seconds between batches — long enough for the Action Scheduler ajax chain to drain.
	 */
	const BATCH_DELAY = 60;

	/**
	 * Seconds a batch may spend hashing — half of Action Scheduler's 30s budget.
	 */
	const BATCH_TIME_BUDGET = 15;

	/**
	 * Constructor to add all hooks.
	 */
	public function __construct() {
		$this->action( 'admin_footer', array( $this, 'modal' ) );
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
		$this->action( 'thumbpress_file_meta_refreshed', array( $this, 'clear_file_meta_caches' ) );
		$this->action( 'thumbpress_media_changed', array( $this, 'clear_media_cache' ) );
		$this->action( 'thumbpress_media_changed', array( $this, 'schedule_stat_cache_refresh' ) );
	}

	public function modal() {
		echo '
		<div id="image-sizes-modal" style="display: none">
			<img id="image-sizes-modal-loader" src="' . esc_attr( THUMBPRESS_ASSETS_URL . 'common/img/loader.gif' ) . '" />
		</div>';
	}

	public function add_assets() {
		global $current_screen;

		if ( isset( $current_screen->base ) && ( strpos( $current_screen->base, 'thumbpress' ) !== false || strpos( $current_screen->base, 'image-sizes' ) !== false ) ) {

			$tailwind_css_path = THUMBPRESS_PLUGIN_DIR . 'build/tailwind.css';

			$this->enqueue_style(
				'tailwind-css',
				THUMBPRESS_PLUGIN_URL . 'build/tailwind.css',
				array(),
				file_exists( $tailwind_css_path ) ? filemtime( $tailwind_css_path ) : THUMBPRESS_VERSION
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
	 * Ensure every existing image gets a content hash.
	 *
	 * Re-arms the backfill whenever images are still missing the hash/size meta
	 * and no batch is queued — self-healing when a previous run never finished
	 * (Action Scheduler queue cleared, cron never fired, or a fatal mid-batch).
	 * The old one-shot `thumbpress_hashes_scheduled` gate could strand images
	 * permanently unhashed; unhashed images are excluded from the hash GROUP BY,
	 * so a stalled backfill silently hides genuine duplicates (#427).
	 */
	public function schedule_hash_generation() {
		if ( (int) get_option( 'thumbpress_hashes_backfill_version' ) >= self::BACKFILL_VERSION ) {
			return;
		}

		// Action Scheduler drains its queue over admin-ajax, which fires admin_init — never re-arm from there (#463).
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		if ( as_has_scheduled_action( 'thumbpress_generate_image_hashes' ) ) {
			return;
		}

		if ( ! $this->has_unhashed_images() ) {
			$this->mark_backfill_complete();
			return;
		}

		as_schedule_single_action( wp_date( 'U' ) + self::BATCH_DELAY, 'thumbpress_generate_image_hashes', array( 'offset' => 0 ) );
		update_option( 'thumbpress_hashes_scheduled', true );
	}

	/**
	 * Seconds this batch may spend hashing.
	 */
	protected function batch_time_budget() {
		return self::BATCH_TIME_BUDGET;
	}

	/**
	 * Flag the backfill as finished for this version.
	 */
	private function mark_backfill_complete() {
		update_option( 'thumbpress_hashes_generated', true );
		update_option( 'thumbpress_hashes_backfill_version', self::BACKFILL_VERSION );
	}

	/**
	 * Whether any non-trashed image attachment is still missing the hash or size meta.
	 */
	private function has_unhashed_images() {
		global $wpdb;

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID
			 FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} pm_hash ON p.ID = pm_hash.post_id AND pm_hash.meta_key = %s
			 LEFT JOIN {$wpdb->postmeta} pm_size ON p.ID = pm_size.post_id AND pm_size.meta_key = %s
			 WHERE p.post_type = 'attachment'
			 AND p.post_mime_type LIKE %s
			 AND p.post_status != 'trash'
			 AND ( pm_hash.post_id IS NULL OR pm_size.post_id IS NULL )
			 LIMIT 1",
				Utility::HASH_META_KEY,
				Utility::SIZE_META_KEY,
				'image/%'
			)
		);

		return ! empty( $id );
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

	public function clear_file_meta_caches( $attachment_id = null ) {
		$this->delete_cache( 'stat_duplicates' );
		$this->delete_cache( 'stat_large_images' );
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
	 *
	 * Walks forward by attachment ID rather than re-querying from the top. An image
	 * whose file is unreadable never gets the meta, so a top-anchored query re-selects
	 * it every batch and the chain reschedules itself forever (#463). Plain OFFSET
	 * cannot be used: hashed rows leave the result set, so it would skip live work.
	 *
	 * BATCH_SIZE is a ceiling, not a promise — the loop also stops at BATCH_TIME_BUDGET
	 * so a slow host with multi-MB originals cannot overrun Action Scheduler's 30s limit.
	 *
	 * @param int $offset Highest attachment ID already visited.
	 */
	public function generate_hashes_batch( $offset ) {
		global $wpdb;

		$limit  = self::BATCH_SIZE;
		$offset = absint( $offset );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID
			 FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} pm_hash ON p.ID = pm_hash.post_id AND pm_hash.meta_key = %s
			 LEFT JOIN {$wpdb->postmeta} pm_size ON p.ID = pm_size.post_id AND pm_size.meta_key = %s
			 WHERE p.post_type = 'attachment'
			 AND p.post_mime_type LIKE %s
			 AND p.post_status != 'trash'
			 AND p.ID > %d
			 AND (pm_hash.post_id IS NULL OR pm_size.post_id IS NULL)
			 ORDER BY p.ID ASC
			 LIMIT %d",
				Utility::HASH_META_KEY,
				Utility::SIZE_META_KEY,
				'image/%',
				$offset,
				$limit
			)
		);

		if ( empty( $ids ) ) {
			$this->mark_backfill_complete();
			$this->delete_cache( 'stat_duplicates' );
			return;
		}

		$deadline  = microtime( true ) + $this->batch_time_budget();
		$last      = $offset;
		$processed = 0;

		foreach ( $ids as $id ) {
			Utility::refresh_file_meta( $id );
			$last = (int) $id;
			++$processed;

			// Checked after the first image, so a batch always advances the watermark.
			if ( microtime( true ) >= $deadline ) {
				break;
			}
		}

		if ( $processed < count( $ids ) || count( $ids ) >= $limit ) {
			as_schedule_single_action( wp_date( 'U' ) + self::BATCH_DELAY, 'thumbpress_generate_image_hashes', array( 'offset' => $last ) );
			return;
		}

		$this->mark_backfill_complete();
		$this->delete_cache( 'stat_duplicates' );
	}
}
