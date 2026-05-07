<?php
namespace Thumbpress\Bootstrap;

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

		// Migrate legacy option values to new format.
		Migrator::migrate();

		// Detect fresh install or upgrade by comparing stored version.
		$stored_version = get_option( self::VERSION_OPTION, '' );
		if ( $stored_version !== THUMBPRESS_VERSION ) {
			if ( $stored_version ) {
				update_option( 'thumbpress_previous_version', $stored_version );
			}
			update_option( self::REDIRECT_OPTION, true );
			update_option( self::VERSION_OPTION, THUMBPRESS_VERSION );
		}

		// Set a flag that indicates the plugin has been activated
		update_option( 'thumbpress_activated', true );
	}

	/**
	 * Redirect to the dashboard after activation or upgrade.
	 * Hooked to admin_init.
	 */
	public static function maybe_redirect() {
		if ( ! get_option( self::REDIRECT_OPTION ) ) {
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
