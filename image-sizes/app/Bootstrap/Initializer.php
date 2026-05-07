<?php
namespace Thumbpress\Bootstrap;

defined( 'ABSPATH' ) || exit;

class Initializer {

	/**
	 * Initialize the plugin's components.
	 */
	public static function initialize() {
		$initializer = new self();

		$initializer->load_admin_controllers();
		$initializer->load_public_controllers();
		$initializer->load_common_controllers();
	}

	/**
	 * Initialize controllers for wp-admin.
	 */
	private function load_admin_controllers() {
		if ( is_admin() ) {
			$controller_dir = THUMBPRESS_PLUGIN_DIR . 'app/Controllers/Admin/';

			foreach ( glob( $controller_dir . '*.php' ) as $file ) {
				$class_name = basename( $file, '.php' );
				$controller = "\\Thumbpress\\Controllers\\Admin\\{$class_name}";

				if ( class_exists( $controller ) ) {
					new $controller();
				}
			}
		}
	}

	/**
	 * Initialize controllers for public-facing parts of the site.
	 */
	private function load_public_controllers() {
		if ( ! is_admin() ) {
			$controller_dir = THUMBPRESS_PLUGIN_DIR . 'app/Controllers/Front/';

			foreach ( glob( $controller_dir . '*.php' ) as $file ) {
				$class_name = basename( $file, '.php' );
				$controller = "\\Thumbpress\\Controllers\\Front\\{$class_name}";

				if ( class_exists( $controller ) ) {
					new $controller();
				}
			}
		}
	}

	/**
	 * Initialize controllers that operate on both admin and public.
	 */
	private function load_common_controllers() {
		$controller_dir = THUMBPRESS_PLUGIN_DIR . 'app/Controllers/Common/';

		foreach ( glob( $controller_dir . '*.php' ) as $file ) {
			$class_name = basename( $file, '.php' );
			$controller = "\\Thumbpress\\Controllers\\Common\\{$class_name}";

			if ( class_exists( $controller ) ) {
				new $controller();
			}
		}
	}
}
