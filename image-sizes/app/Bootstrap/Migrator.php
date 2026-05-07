<?php
namespace Thumbpress\Bootstrap;

defined( 'ABSPATH' ) || exit;

/**
 * Migrates legacy option values to the new version format.
 *
 * Runs once when a user switches from legacy to the new SPA version.
 * Ensures all wp_options data is compatible with the new codebase.
 */
class Migrator {

	const MIGRATION_VERSION_KEY = 'thumbpress_migration_version';
	const CURRENT_MIGRATION     = 3;

	/**
	 * Run all pending migrations.
	 */
	public static function migrate() {
		$current = (int) get_option( self::MIGRATION_VERSION_KEY, 0 );

		if ( $current < 1 ) {
			self::migrate_webp_settings();
			self::migrate_right_click_disable();
			self::migrate_social_share();
			self::migrate_to_prefixed_keys(); // Must run before set_default_options so legacy values win.
			self::set_default_options();
		}

		if ( $current < 2 ) {
			self::migrate_to_prefixed_keys();
		}

		if ( $current < 3 ) {
			self::force_migrate_prefixed_keys();
		}

		update_option( self::MIGRATION_VERSION_KEY, self::CURRENT_MIGRATION );
	}

	/**
	 * Legacy stored WebP convert settings as a single nested array under 'convert-images'.
	 * New version uses separate flat options.
	 *
	 * Legacy: convert-images = { "convert-img-on-upload": true, "convert-img-one-by-one": true }
	 * New:    convert-img-on-upload = 0/1
	 *         thumbpress_single_image_convert = 0/1
	 */
	private static function migrate_webp_settings() {
		$legacy = get_option( 'convert-images' );

		if ( ! is_array( $legacy ) || empty( $legacy ) ) {
			return;
		}

		// Only migrate if the new options don't already exist.
		if ( get_option( 'convert-img-on-upload' ) === false ) {
			$on_upload = ! empty( $legacy['convert-img-on-upload'] ) ? 1 : 0;
			update_option( 'convert-img-on-upload', $on_upload );
		}

		if ( get_option( 'thumbpress_single_image_convert' ) === false ) {
			$single = ! empty( $legacy['convert-img-one-by-one'] ) ? 1 : 0;
			update_option( 'thumbpress_single_image_convert', $single );
		}
	}

	/**
	 * Legacy stored right-click disable as string "on" or empty.
	 * New version uses integer 1/0.
	 *
	 * Legacy: image_download_disable = "on" | "" | null
	 * New:    image_download_disable = 1 | 0
	 */
	private static function migrate_right_click_disable() {
		$legacy = get_option( 'image_download_disable' );

		if ( $legacy === false ) {
			return;
		}

		// Already migrated (numeric value).
		if ( is_numeric( $legacy ) ) {
			return;
		}

		$value = ( $legacy === 'on' ) ? 1 : 0;
		update_option( 'image_download_disable', $value );
	}

	/**
	 * Legacy social share uses "on"/"" strings for toggle values.
	 * New version reads these as-is but normalizes to "on"/"" for consistency.
	 * No transformation needed — just ensure the option exists.
	 */
	private static function migrate_social_share() {
		// Social share format is compatible between versions.
		// Nothing to do here unless we want to normalize.
	}

	/**
	 * Copy all non-prefixed legacy keys to their new thumbpress_* equivalents.
	 *
	 * Old key                  → New key
	 * convert-img-on-upload    → thumbpress_webp_on_upload
	 * image-max-size           → thumbpress_image_max_size
	 * image_download_disable   → thumbpress_image_download_disable
	 * thumbpress-social-share  → thumbpress_social_share
	 */
	private static function migrate_to_prefixed_keys() {
		$map = array(
			'convert-img-on-upload'   => 'thumbpress_webp_on_upload',
			'image-max-size'          => 'thumbpress_image_max_size',
			'image_download_disable'  => 'thumbpress_image_download_disable',
			'thumbpress-social-share' => 'thumbpress_social_share',
		);

		foreach ( $map as $old => $new ) {
			$old_value = get_option( $old );
			if ( $old_value !== false && get_option( $new ) === false ) {
				add_option( $new, $old_value );
			}
		}
	}

	/**
	 * Force-overwrite prefixed keys from legacy values.
	 *
	 * Fixes v1/v2 bug where set_default_options() ran before migrate_to_prefixed_keys(),
	 * causing defaults (0) to win over real legacy values.
	 * Uses update_option() unconditionally when a legacy value exists.
	 */
	private static function force_migrate_prefixed_keys() {
		$map = array(
			'image_download_disable'  => 'thumbpress_image_download_disable',
			'convert-img-on-upload'   => 'thumbpress_webp_on_upload',
			'image-max-size'          => 'thumbpress_image_max_size',
			'thumbpress-social-share' => 'thumbpress_social_share',
		);

		foreach ( $map as $old => $new ) {
			$old_value = get_option( $old );
			if ( $old_value !== false ) {
				update_option( $new, $old_value );
			}
		}
	}

	/**
	 * Set default values for new-only options if they don't exist yet.
	 * This ensures features work correctly for users migrating from legacy.
	 */
	private static function set_default_options() {
		$defaults = array(
			'thumbpress_lazy_load'               => 0,
			'thumbpress_hotlink_protection'       => 0,
			'thumbpress_image_editor'             => 0,
			'thumbpress_replace_images'           => 0,
			'thumbpress_avif_convert_on_upload'    => 0,
			'thumbpress_avif_single_image_convert' => 0,
			'thumbpress_convert_file_formats'     => array( 'jpeg', 'png', 'jpg' ),
			'thumbpress_avif_file_formats'        => array( 'jpeg', 'png', 'jpg', 'webp' ),
		);

		foreach ( $defaults as $key => $value ) {
			if ( get_option( $key ) === false ) {
				add_option( $key, $value );
			}
		}
	}
}
