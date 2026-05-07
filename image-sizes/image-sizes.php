<?php
/**
 * @package Thumbpress
 *
 * Plugin Name:       ThumbPress
 * Plugin URI:        https://wordpress.org/plugins/image-sizes/
 * Description:       WordPress Image Optimization & Media Management Toolkit
 * Version:           6.0.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ThumbPress
 * Author URI:        https://thumbpress.co
 * Text Domain:       image-sizes
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Thumbpress;

use Codexpert\Plugin\Notice;
use Pluggable\Marketing\Survey;
use Pluggable\Marketing\Feature;
use Pluggable\Marketing\Deactivator;

defined( 'ABSPATH' ) || exit;

define( 'THUMBPRESS_VERSION', '6.0.2' );
define( 'THUMBPRESS_FILE', __FILE__ );
define( 'THUMBPRESS_PATH', plugin_dir_path( __FILE__ ) );
define( 'THUMBPRESS_URL', plugin_dir_url( __FILE__ ) );
define( 'THUMBPRESS_ASSETS_PATH', THUMBPRESS_PATH . 'assets/' );
define( 'THUMBPRESS_ASSETS_URL', THUMBPRESS_URL . 'assets/' );
define( 'THUMBPRESS_CACHE_ENABLED', true );

require_once THUMBPRESS_PATH . 'app/Bootstrap/VersionManager.php';
require_once THUMBPRESS_PATH . 'vendor/autoload.php';

( new Bootstrap\AdminNotice() )->init();

add_action( 'rest_api_init', __NAMESPACE__ . '\\thumbpress_version_routes' );
function thumbpress_version_routes() {
	( new Bootstrap\VersionManager() )->register_rest_routes();
}

// Gate: if the user chose the legacy version, load it and stop.
$thumbpress_vm = new Bootstrap\VersionManager();
if ( 'legacy' === $thumbpress_vm->get_version_to_load() ) {
	$thumbpress_vm->load_legacy();
	return;
}

define( 'THUMBPRESS_PLUGIN_DIR', THUMBPRESS_PATH );
define( 'THUMBPRESS_PLUGIN_URL', THUMBPRESS_URL );

require_once THUMBPRESS_PATH . 'legacy/libraries/action-scheduler/action-scheduler.php';

/**
 * Main ThumbPress plugin class.
 *
 * @since 6.0
 * @package Thumbpress
 */
final class ThumbPress {

	/**
	 * Singleton instance.
	 *
	 * @var ThumbPress|null
	 */
	public static $_instance;

	/**
	 * Plugin configuration and metadata.
	 *
	 * @var array
	 */
	public array $plugin;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->define();
		$this->includes();
		$this->hook();
	}

	/**
	 * Define plugin variables.
	 *
	 * @return void
	 */
	public function define(): void {
		$this->plugin                     = get_plugin_data( THUMBPRESS_FILE, true, false );
		$this->plugin['basename']         = plugin_basename( THUMBPRESS_FILE );
		$this->plugin['file']             = THUMBPRESS_FILE;
		$this->plugin['server']           = apply_filters( 'thumbpress_server', 'https://my.pluggable.io' );
		$this->plugin['min_php']          = '7.4';
		$this->plugin['min_wp']           = '5.0';
		$this->plugin['hash_deactivator'] = 'f490a1f1-c3a1-4d3a-bc2a-70d4b405aa11';
		$this->plugin['hash_survey']      = '55b6c7ca-9102-495f-a6bd-581285447c0a';
	}

	/**
	 * Include additional dependencies.
	 *
	 * @return void
	 */
	public function includes(): void {
		if ( is_admin() ) {
			new Notice( $this->plugin );
			new Survey( $this->plugin );
			new Deactivator( $this->plugin );
		}
	}

	/**
	 * Register plugin hooks.
	 *
	 * @return void
	 */
	public function hook(): void {
		register_activation_hook( THUMBPRESS_FILE, array( $this, 'install' ) );
		register_deactivation_hook( THUMBPRESS_FILE, array( $this, 'uninstall' ) );

		add_action( 'admin_init', array( $this, 'redirect' ) );
		add_action( 'plugins_loaded', array( $this, 'activate' ) );
		add_action( 'plugins_loaded', array( $this, 'initialize' ) );
	}

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public function install(): void {
		Bootstrap\Installer::install();
		update_option( Bootstrap\Activator::REDIRECT_OPTION, true );
	}

	/**
	 * Redirect after activation.
	 *
	 * @return void
	 */
	public function redirect(): void {
		Bootstrap\Activator::maybe_redirect();
	}

	/**
	 * Run activator on plugins_loaded.
	 *
	 * @return void
	 */
	public function activate(): void {
		Bootstrap\Activator::activate();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function initialize(): void {
		Bootstrap\Initializer::initialize();
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public function uninstall(): void {
		Bootstrap\Uninstaller::uninstall();
	}

	/**
	 * Get singleton instance.
	 *
	 * @return ThumbPress
	 */
	public static function instance(): self {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

ThumbPress::instance();
