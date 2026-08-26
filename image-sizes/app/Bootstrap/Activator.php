<?php
namespace Codexpert\ThumbPress\Bootstrap;

defined( 'ABSPATH' ) || exit;

class Activator {

	const REDIRECT_OPTION = 'thumbpress_activation_redirect';
	const VERSION_OPTION     = 'thumbpress_redirect_version';

	/**
	 * Static method for plugin activation tasks.
	 */
	public static function activate() {
		$activator = new self();

		$activator->set_cron();

		self::set_default_options();

		// Detect fresh install or upgrade by comparing stored version.
		$stored_version = get_option( self::VERSION_OPTION, '' );
		if ( $stored_version !== THUMBPRESS_VERSION ) {
			if ( $stored_version ) {
				update_option( 'thumbpress_previous_version', $stored_version );
			}
			update_option( self::VERSION_OPTION, THUMBPRESS_VERSION );
		}

		// Set a flag that indicates the plugin has been activated
		update_option( 'thumbpress_activated', true );
	}

	/**
	 * Seed default values for options that have none yet.
	 */
	private static function set_default_options() {
		$defaults = array(
			'thumbpress_lazy_load'                 => 0,
			'thumbpress_hotlink_protection'        => 0,
			'thumbpress_image_editor'              => 0,
			'thumbpress_replace_images'            => 0,
			'thumbpress_avif_convert_on_upload'    => 0,
			'thumbpress_avif_single_image_convert' => 0,
			'thumbpress_convert_file_formats'      => array( 'jpeg', 'png', 'jpg' ),
			'thumbpress_avif_file_formats'         => array( 'jpeg', 'png', 'jpg', 'webp' ),
		);

		foreach ( $defaults as $key => $value ) {
			if ( get_option( $key ) === false ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Redirect to the dashboard after activation or upgrade.
	 * Hooked to admin_init.
	 */
	public static function maybe_redirect() {
		if ( ! get_option( self::REDIRECT_OPTION ) ) {
			return;
		}

		// Skip AJAX and REST requests — sending a redirect header breaks them.
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// Break potential redirect loop: already on the target page.
		if ( isset( $_GET['page'] ) && 'thumbpress' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			delete_option( self::REDIRECT_OPTION );
			return;
		}

		delete_option( self::REDIRECT_OPTION );

		// Skip during bulk plugin activation.
		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=thumbpress#/' ) );
		exit;
	}

	public function set_cron() {
		// code...
	}
}
