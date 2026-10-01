<?php
/**
 * The review prompt: when to ask, what the site answered, and where unhappy feedback goes.
 */

namespace Codexpert\ThumbPress\API;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Bootstrap\Activator;
use Codexpert\ThumbPress\ThumbPress;
use WP_Error;

class Review {

	/**
	 * Unix time the plugin first ran on this site. Stamped once by Activator::activate().
	 */
	const INSTALLED_OPTION = 'thumbpress_installed_at';

	/**
	 * { status, snoozed_until, later_count, updated_at }
	 */
	const STATE_OPTION = 'thumbpress_review_state';

	/**
	 * Days after install before the first ask. Filterable via thumbpress_review_days.
	 */
	const DAYS = 7;

	/**
	 * How long "Maybe later" stays quiet.
	 */
	const SNOOZE_DAYS = 14;

	/**
	 * "Maybe later" this many times is a no.
	 */
	const MAX_LATER = 2;

	const MAX_FEEDBACK_LENGTH = 2000;

	/**
	 * Statuses that end the prompt for good.
	 */
	const FINAL_STATUSES = array( 'reviewed', 'feedback_sent', 'dismissed' );

	/**
	 * Actions POST /review/state accepts.
	 */
	const ACTIONS = array( 'later', 'never', 'reviewed' );

	/**
	 * The state with every key present, so callers never test for a missing one.
	 *
	 * @return array
	 */
	public static function state() {
		$stored = get_option( self::STATE_OPTION, array() );

		return array_merge(
			array(
				'status'        => '',
				'snoozed_until' => 0,
				'later_count'   => 0,
				'updated_at'    => 0,
			),
			is_array( $stored ) ? $stored : array()
		);
	}

	/**
	 * Whether the prompt is due. Pure: everything it needs is passed in.
	 *
	 * @param int   $now          Current Unix time.
	 * @param int   $installed_at Unix time the plugin first ran here; 0 when unknown.
	 * @param array $state        As returned by state().
	 * @param int   $days         Days to wait after install.
	 * @return bool
	 */
	public static function is_due( $now, $installed_at, $state, $days = self::DAYS ) {
		if ( $installed_at <= 0 || in_array( $state['status'], self::FINAL_STATUSES, true ) ) {
			return false;
		}

		if ( (int) $state['snoozed_until'] > $now ) {
			return false;
		}

		return ( $now - $installed_at ) >= max( 0, (int) $days ) * DAY_IN_SECONDS;
	}

	/**
	 * Whether to show the prompt on this request: due, and nothing else is asking for attention.
	 *
	 * @return bool
	 */
	public static function pending() {
		// Never stack on top of another one-time screen.
		if ( Activator::whats_new_pending() || get_option( Activator::REDIRECT_OPTION ) ) {
			return false;
		}

		// Or in the middle of the checkup.
		$setup = Setup::state();
		if ( $setup['started_at'] && ! $setup['completed_at'] && ! $setup['skipped_at'] ) {
			return false;
		}

		$days = (int) apply_filters( 'thumbpress_review_days', self::DAYS );

		return self::is_due( time(), (int) get_option( self::INSTALLED_OPTION, 0 ), self::state(), $days );
	}

	/**
	 * Record an answer. Only known actions are read; timestamps are set here, not by the browser.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function save_state( $request ) {
		$action = $request->get_param( 'action' );

		if ( ! in_array( $action, self::ACTIONS, true ) ) {
			return new WP_Error( 'thumbpress_review_action', __( 'Unknown action.', 'image-sizes' ), array( 'status' => 400 ) );
		}

		$state = self::state();
		$now   = time();

		if ( 'reviewed' === $action ) {
			$state['status'] = 'reviewed';
		} elseif ( 'never' === $action ) {
			$state['status'] = 'dismissed';
		} else {
			$state['later_count']++;
			$state['snoozed_until'] = $now + self::SNOOZE_DAYS * DAY_IN_SECONDS;

			if ( $state['later_count'] >= self::MAX_LATER ) {
				$state['status'] = 'dismissed';
			}
		}

		$state['updated_at'] = $now;

		update_option( self::STATE_OPTION, $state, false );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Send "not happy" feedback to our CRM. Runs only from an explicit Send click; the sender's name,
	 * email, site URL and the message are what leaves the site (disclosed in readme.txt).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function send_feedback( $request ) {
		$message = trim( sanitize_textarea_field( (string) $request->get_param( 'message' ) ) );
		$email   = sanitize_email( (string) $request->get_param( 'email' ) );

		if ( '' === $message ) {
			return new WP_Error( 'thumbpress_review_message', __( 'Please write a few words first.', 'image-sizes' ), array( 'status' => 400 ) );
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'thumbpress_review_email', __( 'Please enter a valid email so we can reply.', 'image-sizes' ), array( 'status' => 400 ) );
		}

		$plugin = ThumbPress::instance();
		$hash   = (string) $plugin->get_plugin( 'hash_survey', '' );
		$server = (string) $plugin->get_plugin( 'server', '' );
		$url    = apply_filters(
			'thumbpress_feedback_endpoint',
			'' !== $hash && '' !== $server ? "{$server}/?fluentcrm=1&route=contact&hash={$hash}" : ''
		);

		if ( ! $url ) {
			return new WP_Error( 'thumbpress_review_endpoint', __( 'Feedback could not be sent right now.', 'image-sizes' ), array( 'status' => 500 ) );
		}

		$user     = wp_get_current_user();
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'body'    => array(
					'first_name' => $user->first_name,
					'last_name'  => $user->last_name,
					'email'      => $email,
					'plugin'     => 'image-sizes',
					'site_url'   => site_url( '/' ),
					'feedback'   => mb_substr( $message, 0, self::MAX_FEEDBACK_LENGTH ),
				),
			)
		);

		if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) >= 400 ) {
			return new WP_Error( 'thumbpress_review_send', __( 'Feedback could not be sent right now. Please try again.', 'image-sizes' ), array( 'status' => 502 ) );
		}

		$state               = self::state();
		$state['status']     = 'feedback_sent';
		$state['updated_at'] = time();

		update_option( self::STATE_OPTION, $state, false );

		return rest_ensure_response( array( 'success' => true ) );
	}
}
