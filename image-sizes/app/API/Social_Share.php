<?php
namespace Thumbpress\API;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Traits\Rest;

class Social_Share {

	use Rest;

	public function get_settings() {
		$social_share = get_option( 'thumbpress_social_share', array() );

		return $this->response_success(
			array(
				'facebook'  => isset( $social_share['enable_fb_share_img'] ) && $social_share['enable_fb_share_img'] === 'on',
				'linkedin'  => isset( $social_share['enable_ln_share_img'] ) && $social_share['enable_ln_share_img'] === 'on',
				'twitter'   => isset( $social_share['enable_tw_share_img'] ) && $social_share['enable_tw_share_img'] === 'on',
				'pinterest' => isset( $social_share['enable_pin_share_img'] ) && $social_share['enable_pin_share_img'] === 'on',
			)
		);
	}

	public function save_settings( $request ) {
		$facebook  = $request->get_param( 'facebook' );
		$linkedin  = $request->get_param( 'linkedin' );
		$twitter   = $request->get_param( 'twitter' );
		$pinterest = $request->get_param( 'pinterest' );

		$current = get_option( 'thumbpress_social_share', array() );

		if ( $facebook !== null ) {
			$current['enable_fb_share_img'] = $facebook ? 'on' : '';
		}
		if ( $linkedin !== null ) {
			$current['enable_ln_share_img'] = $linkedin ? 'on' : '';
		}
		if ( $twitter !== null ) {
			$current['enable_tw_share_img'] = $twitter ? 'on' : '';
		}
		if ( $pinterest !== null ) {
			$current['enable_pin_share_img'] = $pinterest ? 'on' : '';
		}

		update_option( 'thumbpress_social_share', $current );

		return $this->response_success(
			array(
				'message' => __( 'Settings saved successfully.', 'image-sizes' ),
			)
		);
	}
}
