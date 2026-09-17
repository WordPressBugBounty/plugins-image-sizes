<?php
namespace Codexpert\ThumbPress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\API\Dashboard;
use Codexpert\ThumbPress\Helpers\Utility;
use Codexpert\ThumbPress\Models\Hash_Index;
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
	const BATCH_SIZE = 5000;

	/**
	 * Seconds between batches — long enough for the Action Scheduler ajax chain to drain.
	 */
	const BATCH_DELAY = 5;

	/**
	 * Seconds a batch may spend hashing — half of Action Scheduler's 30s budget.
	 */
	const BATCH_TIME_BUDGET = 15;

	/**
	 * Set before the queue is cleared so an executing batch stops re-scheduling.
	 */
	const CANCEL_OPTION = 'thumbpress_scan_cancelled';

	/**
	 * Whether file-meta cache invalidation is held back for the duration of a batch.
	 *
	 * @var bool
	 */
	private static $suspend_file_meta_cache_clear = false;

	/**
	 * Constructor to add all hooks.
	 */
	public function __construct() {
		$this->action( 'admin_footer', array( $this, 'modal' ) );
		$this->action( 'admin_enqueue_scripts', array( $this, 'add_assets' ) );
		$this->action( 'add_attachment', array( $this, 'hash_on_upload' ) );
		$this->action( 'add_attachment', array( $this, 'clear_media_cache' ) );
		$this->action( 'delete_attachment', array( $this, 'clear_media_cache' ) );
		$this->action( 'thumbpress_thumbnail_sizes_saved', array( $this, 'clear_media_cache' ) );
		$this->action( 'thumbpress_generate_image_hashes', array( $this, 'generate_hashes_batch' ) );
		$this->action( 'thumbpress_build_stat_cache', array( $this, 'build_stat_cache' ) );
		$this->action( 'thumbpress_file_meta_refreshed', array( $this, 'clear_file_meta_caches' ) );
		$this->action( 'thumbpress_media_changed', array( $this, 'clear_media_cache' ) );
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

	public function clear_file_meta_caches( $attachment_id = null ) {
		if ( self::$suspend_file_meta_cache_clear ) {
			return;
		}

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

		if ( get_option( self::CANCEL_OPTION, false ) ) {
			return;
		}

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID
			 FROM {$wpdb->posts} p
			 WHERE p.post_type = 'attachment'
			 AND p.post_mime_type LIKE %s
			 AND p.post_status != 'trash'
			 AND p.ID > %d
			 ORDER BY p.ID ASC
			 LIMIT %d",
				'image/%',
				$offset,
				$limit
			)
		);

		if ( empty( $ids ) ) {
			$this->complete_scan();
			return;
		}

		// One query for the batch's meta, so an already-hashed image costs a lookup, not a read.
		update_meta_cache( 'post', $ids );

		$deadline  = microtime( true ) + $this->batch_time_budget();
		$last      = $offset;
		$processed = 0;
		$rows      = array();

		// Per image this drops the duplicate cache once per row, so the count is never warm.
		self::$suspend_file_meta_cache_clear = true;

		foreach ( $ids as $id ) {
			$id   = (int) $id;
			$hash = get_post_meta( $id, Utility::HASH_META_KEY, true );
			$size = get_post_meta( $id, Utility::SIZE_META_KEY, true );

			// Never hashed: read the file once, the expensive case the time budget bounds.
			if ( empty( $hash ) || '' === $size ) {
				Utility::refresh_file_meta( $id );
				$hash = get_post_meta( $id, Utility::HASH_META_KEY, true );
				$size = get_post_meta( $id, Utility::SIZE_META_KEY, true );
			}

			$last = $id;
			++$processed;

			if ( ! empty( $hash ) ) {
				$rows[] = array( $id, $hash, (int) $size, Hash_Index::count_sizes( wp_get_attachment_metadata( $id ) ) );
			}

			// Checked after the first image, so a batch always advances the watermark.
			if ( microtime( true ) >= $deadline ) {
				break;
			}
		}

		self::$suspend_file_meta_cache_clear = false;

		// One statement for the batch; per image it was four round trips.
		Hash_Index::bulk_put( $rows );

		update_option( 'thumbpress_scan_processed', (int) get_option( 'thumbpress_scan_processed', 0 ) + $processed );

		if ( $processed < count( $ids ) || count( $ids ) >= $limit ) {
			if ( ! get_option( self::CANCEL_OPTION, false ) ) {
				as_schedule_single_action( wp_date( 'U' ) + self::BATCH_DELAY, 'thumbpress_generate_image_hashes', array( 'offset' => $last ) );
			}
			return;
		}

		$this->complete_scan();
	}

	/**
	 * Flag the walk as finished: the meta is complete and the index may be read.
	 */
	private function complete_scan() {
		$this->mark_backfill_complete();

		// The final empty batch adds nothing, so the bar would stop one batch short.
		update_option( 'thumbpress_scan_processed', (int) get_option( 'thumbpress_scan_total', 0 ) );

		// The only GROUP BY left: once per library, in the background, on our own table.
		Hash_Index::rebuild_groups();
		Hash_Index::mark_ready();

		update_option( 'thumbpress_scan_completed_at', wp_date( 'U' ) );
		delete_option( self::CANCEL_OPTION );

		$this->clear_file_meta_caches();
	}
}
