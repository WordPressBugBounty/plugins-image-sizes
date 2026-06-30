<?php
namespace Codexpert\ThumbPress\Bootstrap;

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
	 * Returns the user's UI preference (always 'new' — legacy removed).
	 *
	 * @return string
	 */
	public function get_user_preference() {
		return 'new';
	}

	/**
	 * Version switch notice no longer shown — legacy removed.
	 *
	 * @return bool
	 */
	public function should_show_notice() {
		return false;
	}

	/**
	 * Always loads new SPA — legacy removed.
	 *
	 * @return string
	 */
	public function get_version_to_load() {
		return 'new';
	}

}
