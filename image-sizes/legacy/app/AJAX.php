<?php
namespace Codexpert\ThumbPress\App;

use Codexpert\Plugin\Base;
use Codexpert\ThumbPress\Helper;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Admin
 * @author Codexpert <hi@codexpert.io>
 */
class Ajax extends Base {

	public $plugin;
	public $slug;
	public $name;
	public $version;
	public $args;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin  = $plugin;
		$this->slug    = $this->plugin['TextDomain'];
		$this->name    = $this->plugin['Name'];
		$this->version = $this->plugin['Version'];
	}

	public function dismiss_notice() {
		$response = array(
			'status'  => 0,
			'message' => __( 'Unauthorized!', 'image-sizes' ),
		);

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->slug ) ) {
			wp_send_json( $response );
		}

		$screen = sanitize_text_field( $_POST['screen'] );
		if ( $screen === 'after_aweek_thumbpress' ) {
			update_option( 'thumbpress_notice_dismissed_week', true );
		} else {
			update_option( 'thumbpress_notice_dismissed_' . $screen, true );
		}

		$response['status']  = 1;
		$response['message'] = __( 'Notice Removed', 'image-sizes' );
		wp_send_json( $response );
	}



	// public function dismiss_pointer() {

	// $response = [
	// 'status'   => 0,
	// 'message'  =>__( 'Unauthorized!', 'image-sizes' )
	// ];

	// if( ! wp_verify_nonce( $_POST['_wpnonce'], $this->slug ) ) {
	// wp_send_json( $response );
	// }

	// $add_1_month    = wp_date('U') + MONTH_IN_SECONDS ;
	// update_option( 'thumbpress_pro_notice_recurring_every_1_month', $add_1_month );
	// update_option('thumbpress_pro_notice_1_time', true);

	// $response['status']     = 1;
	// $response['message']    = __( 'Pointer Removed', 'image-sizes' );
	// wp_send_json( $response );

	// }

	public function image_sizes_dismiss() {

		if ( 'cx-setup-notice' == $_POST['meta_key'] ) {
			update_option( "{$this->slug}_dismiss", 1 );
		}
	}

	public function image_sizes_dismiss_notice_callback() {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->slug ) ) {
			$response['status']  = 0;
			$response['message'] = __( 'Unauthorized!', 'image-sizes' );
			wp_send_json( $response );
		}
		$notice_type     = sanitize_text_field( $_POST['notice_type'] );
		$allowed_notices = array_keys( image_sizes_notices_values() );
		if ( ! in_array( $notice_type, $allowed_notices, true ) ) {
			$response['status']  = 0;
			$response['message'] = __( 'Invalid notice type.', 'image-sizes' );
			wp_send_json( $response );
		}
		$url = image_sizes_notices_values()[ $notice_type ]['url'];

		delete_transient( $notice_type );

		$response['status']  = 1;
		$response['message'] = __( 'Notice Removed!', 'image-sizes' );
		$response['url']     = $url;
		wp_send_json( $response );
	}

	public function thumbpress_init_notice_handler() {
		// Update the option in the database
		update_option( 'thumbpress_settings_init', 1 );

		wp_send_json_success( 'Option updated successfully' );
	}
}
