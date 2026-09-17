<?php
/**
 * The library scan: started only by an explicit request, run by Action Scheduler.
 */

namespace Codexpert\ThumbPress\API;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Controllers\Common\Init;
use Codexpert\ThumbPress\Models\Hash_Index;
use Codexpert\ThumbPress\Traits\Rest;

class Scan {

	use Rest;

	const ACTION = 'thumbpress_generate_image_hashes';

	/**
	 * Begin a scan, discarding whatever a previous run left behind.
	 */
	public function start() {
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			return $this->response_error( array( 'message' => __( 'Action Scheduler is unavailable, so a background scan cannot be started.', 'image-sizes' ) ) );
		}

		// The installer only runs on a version change, so the tables may not exist yet.
		Hash_Index::install();

		as_unschedule_all_actions( self::ACTION );
		delete_option( Init::CANCEL_OPTION );

		update_option( 'thumbpress_scan_total', $this->total_images() );
		update_option( 'thumbpress_scan_processed', 0 );
		delete_option( 'thumbpress_scan_completed_at' );

		Hash_Index::mark_stale();

		as_schedule_single_action( wp_date( 'U' ) - 10, self::ACTION, array( 'offset' => 0 ) );

		return $this->response_success( $this->state() );
	}

	/**
	 * Where the current scan has got to.
	 */
	public function progress() {
		return $this->response_success( $this->state() );
	}

	/**
	 * Stop a running scan; the flag is set first so an executing batch stops too.
	 */
	public function cancel() {
		update_option( Init::CANCEL_OPTION, true );

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::ACTION );
		}

		return $this->response_success( $this->state() );
	}

	/**
	 * @return array
	 */
	private function state() {
		$total     = (int) get_option( 'thumbpress_scan_total', 0 );
		$processed = (int) get_option( 'thumbpress_scan_processed', 0 );
		$running   = function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::ACTION );

		return array(
			'is_running'   => $running,
			'is_ready'     => Hash_Index::is_ready(),
			'total'        => $total,
			'processed'    => min( $processed, $total ),
			'percent'      => $total > 0 ? min( 100, (int) floor( $processed / $total * 100 ) ) : 0,
			'indexed'      => Hash_Index::is_ready() ? Hash_Index::indexed_count() : 0,
			'completed_at' => (int) get_option( 'thumbpress_scan_completed_at', 0 ),
		);
	}

	/**
	 * @return int
	 */
	private function total_images() {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				 WHERE post_type = 'attachment' AND post_mime_type LIKE %s AND post_status != 'trash'",
				'image/%'
			)
		);
	}
}
