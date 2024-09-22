<?php
namespace Codexpert\ThumbPress\App;

use Codexpert\Plugin\Base;
use Codexpert\ThumbPress\Helper;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Modules
 * @author Codexpert <hi@codexpert.io>
 */
class Modules extends Base {

	/**
	 * Loads modules
	 */
	public function init() {

		$modules = thumbpress_modules();

		foreach ( array_keys( get_option( 'thumbpress_modules', [] ) ) as $module ) {

			if( ! isset( $modules[ $module ] ) ) continue;

			$config = $modules[ $module ];

			if(
				( ! isset( $config['pro'] ) || $config['pro'] !== true )
				&& file_exists( $file = THUMBPRESS_DIR . "/modules/{$module}/{$module}.php" )
			) {
				require_once $file;
				$class = "\\Codexpert\\ThumbPress\\Modules\\{$config['class']}"; 
				$obj = new $class;
				/**
				 * Settings filters
				 */
				if( method_exists( $obj, '__settings' ) ) {
					$obj->filter( 'thumbpress-modules_settings_args', '__settings' );
				}
				/**
				 * if free module has some code in pro version
				 */
				if( 
					defined('THUMBPRESS_PRO_DIR') &&
					file_exists( $pro_file = THUMBPRESS_PRO_DIR . "/modules/{$module}/{$module}.php" )
				) {
					require_once $pro_file;
					$class = "\\Codexpert\\ThumbPress_Pro\\Modules\\{$config['class']}"; 
					$obj = new $class;
				}
			}
			elseif(
				defined( 'THUMBPRESS_PRO_DIR' )
				&& file_exists( $file = THUMBPRESS_PRO_DIR . "/modules/{$module}/{$module}.php" )
			) {
				
				require_once $file;
				$class = "\\Codexpert\\ThumbPress_Pro\\Modules\\{$config['class']}";
				
				$obj = new $class;

				/**
				 * Settings filters
				 */
				if( method_exists( $obj, '__settings' ) ) {
					$obj->filter( 'thumbpress-modules_settings_args', '__settings' );
				}
			}

		}
	}
}