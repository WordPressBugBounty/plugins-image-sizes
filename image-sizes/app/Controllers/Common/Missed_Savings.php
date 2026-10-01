<?php
namespace Codexpert\ThumbPress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\API\Compression_Check;
use Codexpert\ThumbPress\Helpers\Utility;
use Codexpert\ThumbPress\Traits\Hook;

/**
 * Adds up what uploads weigh while nothing compresses them, so the dashboard can say what that cost.
 *
 * Only the uploaded bytes are stored. The compression ratio is applied when the number is read,
 * so it follows the latest measurement and is never a figure this class made up.
 */
class Missed_Savings {

	use Hook;

	/**
	 * Unix time the count started.
	 */
	const SINCE_OPTION = 'thumbpress_missed_since';

	/**
	 * Kilobytes uploaded since then.
	 */
	const KB_OPTION = 'thumbpress_missed_kb';

	public function __construct() {
		$this->action( 'add_attachment', array( $this, 'record' ), 20 );
	}

	/**
	 * Count one upload, after hash_on_upload() has measured it.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function record( $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) || apply_filters( 'thumbpress_is_pro_active', defined( 'THUMBPRESS_PRO_VERSION' ) ) ) {
			return;
		}

		// Compression is measured for these formats only, so only these can be counted honestly.
		if ( ! isset( Compression_Check::GROUPS[ strtolower( (string) get_post_mime_type( $attachment_id ) ) ] ) ) {
			return;
		}

		$kb = (int) get_post_meta( $attachment_id, Utility::SIZE_META_KEY, true );

		if ( $kb <= 0 ) {
			return;
		}

		self::begin();

		update_option( self::KB_OPTION, (int) get_option( self::KB_OPTION, 0 ) + $kb, false );
	}

	/**
	 * Start counting if it has not started.
	 *
	 * @return int Unix time the count started.
	 */
	public static function begin() {
		$since = (int) get_option( self::SINCE_OPTION, 0 );

		if ( $since <= 0 ) {
			$since = time();

			// Two requests can get here together; only one add wins, so report what is stored.
			if ( ! add_option( self::SINCE_OPTION, $since, '', 'no' ) ) {
				$since = (int) get_option( self::SINCE_OPTION, $since );
			}
		}

		return $since;
	}

	/**
	 * Bytes uploaded since the count started, less what compression would have taken off.
	 *
	 * @return array{since: int, bytes: int|null} bytes is null until compression has been measured.
	 */
	public static function read() {
		$since  = self::begin();
		$result = Compression_Check::get_result();

		if ( ! is_array( $result ) || empty( $result['saved_pct'] ) ) {
			return array( 'since' => $since, 'bytes' => null );
		}

		$uploaded = (int) get_option( self::KB_OPTION, 0 ) * 1024;

		return array(
			'since' => $since,
			'bytes' => (int) floor( $uploaded * min( 100, max( 0, (float) $result['saved_pct'] ) ) / 100 ),
		);
	}
}
