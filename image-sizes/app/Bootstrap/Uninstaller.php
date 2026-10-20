<?php
namespace Codexpert\ThumbPress\Bootstrap;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Models\Database;

class Uninstaller {

	/**
	 * Run installation routines.
	 */
	public static function uninstall() {
		$uninstaller = new self();

		$uninstaller->remove_db_version();
		$uninstaller->remove_hotlink_protection();
	}

	/**
	 * Take the hotlink protection rules back out of both .htaccess files and switch the
	 * setting off, so the 403 rules do not outlive the plugin that manages them.
	 */
	protected function remove_hotlink_protection() {
		update_option( 'thumbpress_hotlink_protection', 0 );

		// Nothing was ever written to .htaccess on other servers (see Settings::save()).
		$software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? strtolower( $_SERVER['SERVER_SOFTWARE'] ) : '';
		if ( false === strpos( $software, 'apache' ) ) {
			return;
		}

		$upload_dir = wp_get_upload_dir();

		$this->remove_hotlink_block( ABSPATH . '.htaccess' );
		$this->remove_hotlink_block( trailingslashit( $upload_dir['basedir'] ) . '.htaccess', true );
	}

	/**
	 * Strip the ThumbPress hotlink block from one .htaccess file.
	 *
	 * @param string $htaccess        Full path of the file.
	 * @param bool   $delete_if_empty Delete the file when the block was all it held.
	 */
	protected function remove_hotlink_block( $htaccess, $delete_if_empty = false ) {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $wp_filesystem || ! $wp_filesystem->exists( $htaccess ) || ! $wp_filesystem->is_writable( $htaccess ) ) {
			return;
		}

		$content = (string) $wp_filesystem->get_contents( $htaccess );
		$pattern = '/\n?# BEGIN ThumbPress Hotlink Protection.*?# END ThumbPress Hotlink Protection\n?/s';

		// Leave files that never held our block exactly as they are.
		if ( ! preg_match( $pattern, $content ) ) {
			return;
		}

		$content = preg_replace( $pattern, '', $content );

		if ( $delete_if_empty && '' === trim( $content ) ) {
			wp_delete_file( $htaccess );
		} else {
			$wp_filesystem->put_contents( $htaccess, $content, FS_CHMOD_FILE );
		}
	}

	/**
	 * Remove the database version from the options table.
	 */
	protected function remove_db_version() {
		delete_option( 'image-sizes_db_version' );
	}
}
