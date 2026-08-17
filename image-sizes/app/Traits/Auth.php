<?php
namespace Codexpert\ThumbPress\Traits;

defined( 'ABSPATH' ) || exit;

trait Auth {

	/**
	 * Check if sandbox/test mode is enabled.
	 *
	 * @return bool True if sandbox mode is enabled, false otherwise.
	 */
	protected function is_sandbox_mode() {
		return defined( 'THUMBPRESS_SANDBOX' ) && THUMBPRESS_SANDBOX;
	}

	/**
	 * Check if the current user is an administrator.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool True if sandbox mode is enabled or the user has administrator capabilities, false otherwise.
	 */
	public function is_admin( $request ) {
		return $this->is_sandbox_mode() || current_user_can( 'manage_options' );
	}
}
