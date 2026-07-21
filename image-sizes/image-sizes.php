<?php
/**
 * @package Thumbpress
 *
 * Plugin Name:       ThumbPress
 * Plugin URI:        https://wordpress.org/plugins/image-sizes/
 * Description:       WordPress Image Optimization & Media Management Toolkit
 * Version:           6.4.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ThumbPress
 * Author URI:        https://thumbpress.co
 * Text Domain:       image-sizes
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Codexpert\ThumbPress;

use Codexpert\Plugin\Notice;
use Pluggable\Marketing\Survey;
use Pluggable\Marketing\Feature;
use Pluggable\Marketing\Deactivator;

defined( 'ABSPATH' ) || exit;

define( 'THUMBPRESS_VERSION', '6.4.1' );
define( 'THUMBPRESS_FILE', __FILE__ );
define( 'THUMBPRESS_PATH', plugin_dir_path( __FILE__ ) );
define( 'THUMBPRESS_URL', plugin_dir_url( __FILE__ ) );
define( 'THUMBPRESS_ASSETS_PATH', THUMBPRESS_PATH . 'assets/' );
define( 'THUMBPRESS_ASSETS_URL', THUMBPRESS_URL . 'assets/' );
define( 'THUMBPRESS_CACHE_ENABLED', true );

require_once THUMBPRESS_PATH . 'app/Bootstrap/VersionManager.php';
require_once THUMBPRESS_PATH . 'vendor/autoload.php';
require_once THUMBPRESS_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

( new Bootstrap\AdminNotice() )->init();

define( 'THUMBPRESS_PLUGIN_DIR', THUMBPRESS_PATH );
define( 'THUMBPRESS_PLUGIN_URL', THUMBPRESS_URL );

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
		// Opt-in lead collection via pluggable.io / FluentCRM. Data is sent ONLY on explicit user
		// action — clicking "Agree" on the activation survey notice, or "Submit & Deactivate" on the
		// deactivation feedback modal. Nothing is transmitted automatically. Disclosed in readme.txt
		// under "External Services" (what is sent, when, ToS + Privacy Policy links).
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
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load the plugin's translations for PHP strings (menu labels, admin notices,
	 * REST messages). The React bundle's JS strings are wired separately via
	 * wp_set_script_translations() at enqueue time (see Controllers\Admin\Menu).
	 *
	 * Hooked on `init` so it is valid on WordPress 6.7+, which warns when a
	 * textdomain is loaded earlier.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'image-sizes', false, dirname( $this->plugin['basename'] ) . '/languages' );
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
