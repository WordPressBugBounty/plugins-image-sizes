<?php
/**
 * The Site Image Checkup: where a site is in it, and what it told us about itself.
 */

namespace Codexpert\ThumbPress\API;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Traits\Rest;

class Setup {

	use Rest;

	const OPTION = 'thumbpress_setup_state';

	const SITE_TYPES = array( 'blog', 'store', 'photo', 'business', 'agency' );

	const MODES = array( 'fresh', 'upgrade', 'manual' );

	/**
	 * The state with every key present, so callers never test for a missing one.
	 *
	 * @return array
	 */
	public static function state() {
		$stored = get_option( self::OPTION, array() );

		return array_merge(
			array(
				'version'              => 1,
				'started_at'           => 0,
				'completed_at'         => 0,
				'skipped_at'           => 0,
				'invite_dismissed_at'  => 0,
				'mode'                 => '',
				'site_type'            => '',
				'grade_before'         => '',
				'score_before'         => null,
				'free_fixes_applied'   => array(),
				'offer_expires'        => 0,
			),
			is_array( $stored ) ? $stored : array()
		);
	}

	/**
	 * Best guess at what the site is, so the checkup can start on the likely answer.
	 *
	 * @return string One of SITE_TYPES.
	 */
	public static function detect_site_type() {
		$type = apply_filters( 'thumbpress_detected_site_type', class_exists( 'WooCommerce' ) ? 'store' : 'blog' );

		return in_array( $type, self::SITE_TYPES, true ) ? $type : 'blog';
	}

	/**
	 * A discount to show after the checkup, or null when none is configured.
	 *
	 * A real offer needs a real coupon, which lives outside the plugin, so nothing shows until a site
	 * or a later release supplies one: array( 'code' => 'ABC', 'percent' => 30, 'hours' => 72 ).
	 *
	 * @return array|null
	 */
	public static function offer_config() {
		$offer = apply_filters( 'thumbpress_onboarding_offer', null );

		if ( ! is_array( $offer ) || empty( $offer['code'] ) || empty( $offer['percent'] ) || empty( $offer['hours'] ) ) {
			return null;
		}

		return array(
			'code'    => sanitize_text_field( $offer['code'] ),
			'percent' => (int) $offer['percent'],
			'hours'   => max( 1, (int) $offer['hours'] ),
		);
	}

	/**
	 * What the SPA needs: the state, the detected type and the live offer, if any.
	 *
	 * @return array
	 */
	public static function payload() {
		$state = self::state();
		$offer = self::offer_config();

		return array(
			'state'         => $state,
			'detected_type' => self::detect_site_type(),
			'offer'         => $offer && (int) $state['offer_expires'] > time()
				? array_merge( $offer, array( 'expires' => (int) $state['offer_expires'] ) )
				: null,
			'pro_active'    => (bool) apply_filters( 'thumbpress_is_pro_active', defined( 'THUMBPRESS_PRO_VERSION' ) ),
		);
	}

	public function get_state() {
		return $this->response_success( self::payload() );
	}

	/**
	 * Merge a partial update. Only known keys are read, each one checked, and timestamps are set here.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public function save_state( $request ) {
		$state = self::state();
		$now   = time();

		$site_type = $request->get_param( 'site_type' );
		if ( null !== $site_type && in_array( $site_type, self::SITE_TYPES, true ) ) {
			$state['site_type'] = $site_type;
		}

		$mode = $request->get_param( 'mode' );
		if ( null !== $mode && in_array( $mode, self::MODES, true ) ) {
			$state['mode'] = $mode;
		}

		if ( $request->get_param( 'started' ) && ! $state['started_at'] ) {
			$state['started_at'] = $now;
		}

		if ( $request->get_param( 'skipped' ) ) {
			$state['skipped_at'] = $now;
		}

		if ( $request->get_param( 'invite_dismissed' ) ) {
			$state['invite_dismissed_at'] = $now;
		}

		$grade = $request->get_param( 'grade_before' );
		if ( null !== $grade && preg_match( '/^[A-F]$/', (string) $grade ) ) {
			$state['grade_before'] = $grade;
		}

		$score = $request->get_param( 'score_before' );
		if ( null !== $score ) {
			$state['score_before'] = max( 0, min( 100, (int) $score ) );
		}

		$applied = $request->get_param( 'free_fixes_applied' );
		if ( is_array( $applied ) ) {
			$state['free_fixes_applied'] = array_values( array_unique( array_map( 'sanitize_key', $applied ) ) );
		}

		if ( $request->get_param( 'completed' ) ) {
			$state['completed_at'] = $now;

			$offer = self::offer_config();

			// One offer per site, and never to a site that already paid.
			if ( $offer && ! $state['offer_expires'] && ! apply_filters( 'thumbpress_is_pro_active', defined( 'THUMBPRESS_PRO_VERSION' ) ) ) {
				$state['offer_expires'] = $now + $offer['hours'] * HOUR_IN_SECONDS;
			}
		}

		update_option( self::OPTION, $state, false );

		return $this->response_success( self::payload() );
	}
}
