<?php
namespace Codexpert\ThumbPress\Bootstrap;

defined( 'ABSPATH' ) || exit;

class Activator {

	const REDIRECT_OPTION	= 'thumbpress_activation_redirect';
	const VERSION_OPTION	= 'thumbpress_redirect_version';

	/**
	 * Set at activation when the site has never run ThumbPress, so the redirect can open the checkup.
	 */
	const SETUP_REDIRECT_OPTION = 'thumbpress_setup_redirect';

	/**
	 * The last release this site was redirected to the dashboard for.
	 *
	 * Holds THUMBPRESS_VERSION at the time of the redirect: every announcement up to that release has
	 * been delivered. Only a newer entry in app/Config/whats-new.php re-arms it, so no plain update ever
	 * redirects anyone (#342). The option name predates the announcements file and is kept so existing
	 * stamps stay valid.
	 */
	const ANNOUNCED_OPTION = 'thumbpress_significant_version';

	/**
	 * The last release whose "What's new" popup this site has seen. Holds THUMBPRESS_VERSION at the time
	 * of dismissal; absent means never dismissed. A brand-new site is stamped at first activation.
	 */
	const WHATS_NEW_DISMISSED_OPTION = 'thumbpress_whats_new_dismissed';

	/**
	 * Most items one popup shows when a site skipped several announcing releases.
	 */
	const WHATS_NEW_MAX_ITEMS = 4;

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

		self::maybe_arm_announced_redirect();

		// When the site first met the plugin, for the review prompt. add_option() is a no-op once set.
		add_option( \Codexpert\ThumbPress\API\Review::INSTALLED_OPTION, time() );

		// Set a flag that indicates the plugin has been activated
		update_option( 'thumbpress_activated', true );
	}

	/**
	 * Every announcement, newest release first: version => function returning its items.
	 *
	 * Filterable via `thumbpress_whats_new`, so an add-on can announce its own items.
	 *
	 * @return array<string, callable>
	 */
	public static function announcements() {
		$all = apply_filters( 'thumbpress_whats_new', include THUMBPRESS_PATH . 'app/Config/whats-new.php' );
		$all = is_array( $all ) ? $all : array();

		uksort( $all, static function ( $a, $b ) {
			return version_compare( (string) $b, (string) $a );
		} );

		return $all;
	}

	/**
	 * The newest announcing release this build has reached, or '' when there is none.
	 *
	 * An entry above the running version stays silent, so it can be written before its release ships.
	 *
	 * @param string $running The running plugin version.
	 * @return string
	 */
	public static function announced( $running = THUMBPRESS_VERSION ) {
		foreach ( array_keys( self::announcements() ) as $version ) {
			if ( version_compare( (string) $version, $running, '<=' ) ) {
				return (string) $version;
			}
		}

		return '';
	}

	/**
	 * Arm the dashboard redirect once per announcing release.
	 *
	 * Compared against the site's stamp rather than against the version it is upgrading *from*, so it
	 * does not matter which release the site lands on, nor whether it skipped the announcing one.
	 *
	 * Runs on every request rather than only inside the version-drift branch above: that branch fires
	 * exactly once and cannot retry, so an upgrade request that died before the flag was written would
	 * lose the redirect for good. The flag itself is consumed by maybe_redirect() (loop-guarded).
	 */
	private static function maybe_arm_announced_redirect() {
		$announced = self::announced();

		// Nothing announced, or already redirected for it (an empty stamp compares as lower).
		if ( '' === $announced || version_compare( (string) get_option( self::ANNOUNCED_OPTION, '' ), $announced, '>=' ) ) {
			return;
		}

		update_option( self::ANNOUNCED_OPTION, THUMBPRESS_VERSION );
		update_option( self::REDIRECT_OPTION, true );
	}

	/**
	 * Whether the "What's new" popup should show: an announcement this build has reached that the site
	 * has not dismissed.
	 *
	 * @param string $running The running plugin version.
	 * @return bool
	 */
	public static function whats_new_pending( $running = THUMBPRESS_VERSION ) {
		$announced = self::announced( $running );

		return '' !== $announced && version_compare( (string) get_option( self::WHATS_NEW_DISMISSED_OPTION, '' ), $announced, '<' );
	}

	/**
	 * The items the site has not seen yet: every entry after its stamp up to the running release,
	 * newest first, capped at WHATS_NEW_MAX_ITEMS.
	 *
	 * @param string $running The running plugin version.
	 * @return array
	 */
	public static function whats_new_items( $running = THUMBPRESS_VERSION ) {
		$seen  = (string) get_option( self::WHATS_NEW_DISMISSED_OPTION, '' );
		$items = array();

		foreach ( self::announcements() as $version => $entry ) {
			if ( version_compare( (string) $version, $running, '>' ) || version_compare( (string) $version, $seen, '<=' ) || ! is_callable( $entry ) ) {
				continue;
			}

			foreach ( (array) call_user_func( $entry ) as $item ) {
				$items[] = $item;
			}
		}

		return array_slice( $items, 0, self::WHATS_NEW_MAX_ITEMS );
	}

	/**
	 * Record that the popup has been seen. Written by the server, not the browser: it holds the running
	 * release, so every announcement up to it counts as seen.
	 */
	public static function dismiss_whats_new() {
		update_option( self::WHATS_NEW_DISMISSED_OPTION, THUMBPRESS_VERSION );
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
			delete_option( self::SETUP_REDIRECT_OPTION );
			return;
		}

		$destination = get_option( self::SETUP_REDIRECT_OPTION ) ? '#/setup' : '#/';

		delete_option( self::REDIRECT_OPTION );
		delete_option( self::SETUP_REDIRECT_OPTION );

		// Skip during bulk plugin activation.
		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=thumbpress' . $destination ) );
		exit;
	}

	/**
	 * Remember that this activation is the site's first, so the redirect opens the checkup.
	 *
	 * Decided here, at activation, because by the time maybe_redirect() runs on the next request
	 * activate() has already written the version options that tell a new site from an old one. Called
	 * before the installer, which writes the database version this check reads. A site that was
	 * active before, or has any checkup state, is not new, so re-activating never re-runs it.
	 */
	public static function arm_setup_redirect() {
		if ( false !== get_option( 'image-sizes_db_version', false ) || get_option( self::VERSION_OPTION ) || get_option( 'thumbpress_setup_state' ) ) {
			return;
		}

		update_option( self::SETUP_REDIRECT_OPTION, true );

		// Nothing is new to a site that has only just met the plugin.
		self::dismiss_whats_new();
	}

	public function set_cron() {
		// code...
	}
}
