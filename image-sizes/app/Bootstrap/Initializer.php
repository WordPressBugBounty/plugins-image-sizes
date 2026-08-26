<?php
namespace Codexpert\ThumbPress\Bootstrap;

defined( 'ABSPATH' ) || exit;

class Initializer {

	/**
	 * Controller class names per context. A new file must be added here to load.
	 */
	const CONTROLLERS = array(
		'Admin'  => array( 'Init', 'Menu' ),
		'Front'  => array( 'Image_Download_Disable', 'Lazy_Load' ),
		'Common' => array( 'API', 'Auto_Featured_Image', 'Convert_Avif', 'Convert_Webp', 'Hotlink_Protection', 'Image_Max_Size', 'Init', 'Media_Buttons', 'Social_Share', 'Thumbnails' ),
	);

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
			foreach ( self::CONTROLLERS['Admin'] as $class_name ) {
				$controller = "\\Codexpert\ThumbPress\\Controllers\\Admin\\{$class_name}";

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
			foreach ( self::CONTROLLERS['Front'] as $class_name ) {
				$controller = "\\Codexpert\ThumbPress\\Controllers\\Front\\{$class_name}";

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
		foreach ( self::CONTROLLERS['Common'] as $class_name ) {
			$controller = "\\Codexpert\ThumbPress\\Controllers\\Common\\{$class_name}";

			if ( class_exists( $controller ) ) {
				new $controller();
			}
		}
	}
}
