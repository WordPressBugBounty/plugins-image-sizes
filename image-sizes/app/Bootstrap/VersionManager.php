<?php
namespace Thumbpress\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VersionManager {

	const OPTION_VERSION          = 'thumbpress_ui_version';
	const OPTION_CHOICE           = 'thumbpress_ui_choice_made';
	const OPTION_NOTICE_DISMISSED = 'thumbpress_version_notice_dismissed';

	/**
	 * Returns the installed pro plugin version, or null if not active.
	 * Reads the file header directly — safe to call before pro constants are defined.
	 *
	 * @return string|null
	 */
	private function get_pro_version() {
		$basename = 'thumbpress-pro/thumbpress-pro.php';
		$active   = (array) get_option( 'active_plugins', array() );

		if ( ! in_array( $basename, $active, true ) ) {
			return null;
		}

		$file = WP_PLUGIN_DIR . '/' . $basename;
		if ( ! file_exists( $file ) ) {
			return null;
		}

		$data = get_file_data( $file, array( 'Version' => 'Version' ) );
		return ! empty( $data['Version'] ) ? $data['Version'] : null;
	}

	/**
	 * Returns true if an old pro plugin (< 6.0) is active.
	 *
	 * @return bool
	 */
	public function is_old_pro() {
		$pro_version = $this->get_pro_version();
		return null !== $pro_version && version_compare( $pro_version, '6.0', '<' );
	}

	/**
	 * Returns true if no old plugin data exists in wp_options.
	 *
	 * @return bool
	 */
	public function is_new_user() {
		return false === get_option( 'thumbpress_modules', false );
	}

	/**
	 * Returns the user's UI preference.
	 *
	 * @return string|null 'new', 'legacy', or null if no choice made.
	 */
	public function get_user_preference() {
		$value = get_option( self::OPTION_VERSION, null );

		if ( in_array( $value, array( 'new', 'legacy' ), true ) ) {
			return $value;
		}

		return null;
	}

	/**
	 * Saves the user's UI version choice.
	 *
	 * @param string $choice 'new' or 'legacy'.
	 * @return bool
	 */
	public function save_preference( $choice ) {
		if ( ! in_array( $choice, array( 'new', 'legacy' ), true ) ) {
			return false;
		}

		update_option( self::OPTION_VERSION, $choice );
		update_option( self::OPTION_CHOICE, true );

		$this->clear_caches();

		return true;
	}

	/**
	 * Clear all size/stats caches so both versions start fresh after a switch.
	 */
	private function clear_caches() {
		$keys = array( 'all_sizes', 'disabled_sizes', 'dashboard_stats' );

		foreach ( $keys as $key ) {
			$prefixed = 'thumbpress_' . $key;
			delete_transient( $prefixed );
			wp_cache_delete( $prefixed, 'thumbpress' );
		}
	}

	/**
	 * Returns true if the version switch notice should be shown.
	 *
	 * @return bool
	 */
	public function should_show_notice() {
		if ( $this->is_new_user() ) {
			return false;
		}

		if ( 'legacy' !== $this->get_version_to_load() ) {
			return false;
		}

		return ! get_option( self::OPTION_NOTICE_DISMISSED, false );
	}

	/**
	 * Dismiss the version notice permanently.
	 */
	public function dismiss_notice() {
		update_option( self::OPTION_NOTICE_DISMISSED, true );
	}

	/**
	 * Determines which version to load.
	 *
	 * @return string 'new' or 'legacy'.
	 */
	public function get_version_to_load() {
		if ( $this->is_old_pro() ) {
			return 'legacy';
		}

		$preference = $this->get_user_preference();

		if ( null !== $preference ) {
			return $preference;
		}

		return 'new';
	}

	/**
	 * Load the legacy plugin from the legacy/ subfolder.
	 */
	public function load_legacy(): void {
		$legacy_dir = THUMBPRESS_PATH . 'legacy/';

		spl_autoload_register( function ( $class ) use ( $legacy_dir ) {
			$map = array(
				'Codexpert\\ThumbPress\\App\\'  => $legacy_dir . 'app/',
				'Codexpert\\ThumbPress\\API\\'  => $legacy_dir . 'api/',
				'Codexpert\\ThumbPress\\'       => $legacy_dir . 'classes/',
				'Codexpert\\Plugin\\'           => $legacy_dir . 'vendor/codexpert/plugin/src/',
				'Pluggable\\Marketing\\'        => $legacy_dir . 'vendor/pluggable/marketing/src/',
				'WebPConvert\\'                 => $legacy_dir . 'vendor/rosell-dk/webp-convert/src/',
				'ImageMimeTypeGuesser\\'        => $legacy_dir . 'vendor/rosell-dk/image-mime-type-guesser/src/',
				'ExecWithFallback\\'            => $legacy_dir . 'vendor/rosell-dk/exec-with-fallback/src/',
			);
			foreach ( $map as $prefix => $dir ) {
				$len = strlen( $prefix );
				if ( strncmp( $prefix, $class, $len ) === 0 ) {
					$file = $dir . str_replace( '\\', '/', substr( $class, $len ) ) . '.php';
					if ( file_exists( $file ) ) {
						require $file;
						return;
					}
				}
			}
		}, true, true );

		require_once $legacy_dir . 'image-sizes.php';
	}

	/**
	 * Register version preference REST endpoints.
	 * Must be available on BOTH legacy and new versions
	 * so the admin notice can always switch between them.
	 */
	public function register_rest_routes() {
		$self = $this;

		register_rest_route( 'cx/v1', '/version-preference', array(
			'methods'             => 'GET',
			'callback'            => function () use ( $self ) {
				return rest_ensure_response( array(
					'preference' => $self->get_user_preference(),
				) );
			},
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );

		register_rest_route( 'cx/v1', '/version-preference', array(
			'methods'             => 'POST',
			'callback'            => function ( $request ) use ( $self ) {
				$preference = sanitize_text_field( $request->get_param( 'preference' ) );

				if ( ! $self->save_preference( $preference ) ) {
					return new \WP_Error( 'invalid_preference', 'Must be "new" or "legacy".', array( 'status' => 400 ) );
				}

				return rest_ensure_response( array(
					'success'    => true,
					'preference' => $preference,
				) );
			},
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );

		register_rest_route( 'cx/v1', '/version-preference/dismiss', array(
			'methods'             => 'POST',
			'callback'            => function () use ( $self ) {
				$self->dismiss_notice();
				return rest_ensure_response( array( 'success' => true ) );
			},
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );
	}
}
