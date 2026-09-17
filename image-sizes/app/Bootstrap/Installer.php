<?php
namespace Codexpert\ThumbPress\Bootstrap;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Models\Hash_Index;

class Installer {

	/**
	 * Run installation routines.
	 */
	public static function install() {
		$installer = new self();

		if ( ! $installer->is_database_up_to_date() ) {
			Hash_Index::install();
			$installer->update_db_version();
		}
	}

	/**
	 * Check if the database is up to date.
	 *
	 * @return bool
	 */
	protected function is_database_up_to_date() {
		$installed_ver = get_option( 'image-sizes_db_version' );
		return version_compare( $installed_ver, THUMBPRESS_VERSION, '=' );
	}

	/**
	 * Update or add the database version to the options table.
	 */
	protected function update_db_version() {
		update_option( 'image-sizes_db_version', THUMBPRESS_VERSION );
	}
}
