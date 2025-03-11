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
	public function __construct($plugin, $args = []) {
		$this->plugin = $plugin;
		$this->slug = $this->plugin['TextDomain'];
		$this->name = $this->plugin['Name'];
		$this->version = $this->plugin['Version'];
		$this->args = wp_parse_args($args, [
			'server' => 'https://my.pluggable.io'
		]);
	}

	public function dismiss_notice() {
		$response = [
			'status'   => 0,
			'message'  => __( 'Unauthorized!', 'image-sizes' )
		];

		if(!wp_verify_nonce($_POST['_wpnonce'], $this->slug)) {
			wp_send_json($response);
		}

		$screen = sanitize_text_field($_POST['screen']);
		if ( $screen === 'after_aweek_thumbpress' ) {
			update_option( 'thumbpress_notice_dismissed_week', true );
		} else {
			update_option( 'thumbpress_notice_dismissed_' . $screen, true );
		}

		$response['status'] = 1;
		$response['message'] = __( 'Notice Removed', 'image-sizes' );
		wp_send_json($response);
	}

	public function unhappy_servay() {
	    // if (!isset($_POST['unhappy_survey_nonce']) || !wp_verify_nonce($_POST['unhappy_survey_nonce'], 'unhappy_survey_action')) {
	    //     wp_send_json_error(['message' => 'Nonce verification failed']);
	    //     return;
	    // }

	    $full_name 		= sanitize_text_field( $_POST[ 'full_name' ] );
	    $email 			= sanitize_email( $_POST[ 'email' ] );
	    $plugin_name 	= sanitize_text_field( $_POST[ 'plugin_name' ] );
	    $explanation 	= sanitize_textarea_field( $_POST[ 'explanation' ] );
	    $reasons 		= isset( $_POST[ 'ureason' ] ) ? array_map( 'sanitize_text_field', $_POST[ 'ureason' ] ) : [];
	    $reasons_string = implode( ", ", $reasons );
	    $endpoint 		= 'https://my.pluggable.io/?fluentcrm=1&route=contact&hash=d67602e6-db28-49ee-8855-0e126863912a';
	    $body = [
	        'full_name' => $full_name,
	        'email' 	=> $email,
	        'plugin' 	=> $plugin_name,
	        'feedback' 	=> "<strong>Reasons:</strong> " . $reasons_string . " | <strong>Explanation:</strong> " . $explanation,
	    ];

	    $response = wp_remote_post( $endpoint, [
	        'body' 		=> $body,
	        'timeout' 	=> 15,
	        'headers' 	=> [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
	    ]);

	    if ( is_wp_error( $response ) ) {
	        wp_send_json_error( [ 'message' => $response->get_error_message() ] );
	    } else {
	        wp_send_json_success( [ 'message' => 'Feedback submitted successfully' ] );
	    }
	}


	// public function dismiss_pointer() {

	// 	$response = [
	// 		 'status'	=> 0,
	// 		 'message'	=>__( 'Unauthorized!', 'image-sizes' )
	// 	];

	// 	if( ! wp_verify_nonce( $_POST['_wpnonce'], $this->slug ) ) {
	// 	    wp_send_json( $response );
	// 	}

	// 	$add_1_month 	= wp_date('U') + MONTH_IN_SECONDS ;
	// 	update_option( 'thumbpress_pro_notice_recurring_every_1_month', $add_1_month );
	// 	// update_option('thumbpress_pro_notice_1_time', true);

	// 	$response['status'] 	= 1;
	// 	$response['message'] 	= __( 'Pointer Removed', 'image-sizes' );
	// 	wp_send_json( $response );

	// }

	public function image_sizes_dismiss(){		

		if ( 'cx-setup-notice' ==  $_POST['meta_key'] ) {
			update_option( "{$this->slug}_dismiss", 1 );
		}
	}

	public function image_sizes_dismiss_notice_callback() {
		
		if (! wp_verify_nonce($_POST['_wpnonce'], $this->slug )) {
			$response['status'] 	= 0;
			$response['message'] = __('Unauthorized!', 'image-sizes');
			wp_send_json($response);
		}
		$notice_type 	= sanitize_text_field( $_POST[ 'notice_type' ] );
		$url 			= image_sizes_notices_values()[$notice_type]['url'];

		delete_transient( sanitize_text_field( $notice_type ) );

		$response['status'] 	= 1;
		$response['message'] 	= __('Notice Removed!', 'image-sizes');
		$response['url'] 		= $url;
		wp_send_json($response);
	}
}