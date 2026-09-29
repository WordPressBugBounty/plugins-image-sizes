<?php
namespace Codexpert\ThumbPress\Bootstrap;

defined( 'ABSPATH' ) || exit;

class Activator {

	const REDIRECT_OPTION	= 'thumbpress_activation_redirect';
	const VERSION_OPTION	= 'thumbpress_redirect_version';

	/**
	 * The release worth announcing — reaching it sends admins to the dashboard once.
	 *
	 * Deliberately NOT tied to THUMBPRESS_VERSION. That changes every release; this
	 * changes only when a release is worth a redirect. A site qualifies when it
	 * reaches this version *or anything above it*, whichever release it actually
	 * lands on, so a site that sat on 6.6.0 and updated straight to 6.7.2 still
	 * gets it. The value is then stamped into SIGNIFICANT_VERSION_OPTION.
	 *
	 * To announce a future release, bump this constant in that release's commit:
	 * every site whose stamp is below the new value is redirected once more,
	 * including sites already well past the previous mark. Leave it alone and no
	 * update redirects anyone — that is what keeps #342 (redirect on every single
	 * update) from coming back.
	 */
	const SIGNIFICANT_VERSION = '6.7.0';

	/**
	 * The SIGNIFICANT_VERSION this site has already been redirected for.
	 *
	 * Holds the constant, never THUMBPRESS_VERSION, so the comparison stays stable
	 * across the patch releases that follow a significant one. Absent means the
	 * site has never been redirected for any announcement.
	 */
	const SIGNIFICANT_VERSION_OPTION = 'thumbpress_significant_version';

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

		self::maybe_arm_significant_redirect();

		// Set a flag that indicates the plugin has been activated
		update_option( 'thumbpress_activated', true );
	}

	/**
	 * Arm the dashboard redirect once per significant release.
	 *
	 * Compared against the site's stamp rather than against the version it is
	 * upgrading *from*, so it does not matter which release the site happens to
	 * land on, nor whether it skipped the significant one entirely. The stamp is
	 * written together with the flag, so this fires at most once per bump of
	 * SIGNIFICANT_VERSION.
	 *
	 * Runs on every request rather than only inside the version-drift branch
	 * above: that branch fires exactly once and cannot retry, so an upgrade
	 * request that died before the flag was written would lose the redirect for
	 * good. The flag itself is consumed by maybe_redirect() (loop-guarded).
	 *
	 * The CDN popup is NOT armed here — it shows until the user dismisses it, so
	 * it needs no arming at all. See Init::CDN_ANNOUNCEMENT_DISMISSED_OPTION.
	 */
	private static function maybe_arm_significant_redirect() {
		// Not there yet — the site is still below the announced release.
		if ( version_compare( THUMBPRESS_VERSION, self::SIGNIFICANT_VERSION, '<' ) ) {
			return;
		}

		// Already redirected for this announcement (empty stamp compares as lower).
		if ( version_compare( get_option( self::SIGNIFICANT_VERSION_OPTION, '' ), self::SIGNIFICANT_VERSION, '>=' ) ) {
			return;
		}

		update_option( self::SIGNIFICANT_VERSION_OPTION, self::SIGNIFICANT_VERSION );
		update_option( self::REDIRECT_OPTION, true );
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
