<?php
namespace Thumbpress\Controllers\Admin;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Bootstrap\VersionManager;
use Thumbpress\Traits\Hook;
use Thumbpress\Traits\Asset;

class Init {

	use Hook;
	use Asset;

	/**
	 * Constructor to add all hooks.
	 */
	const MIN_PRO_VERSION = '6.0';

	public function __construct() {
		$this->action( 'admin_enqueue_scripts', array( $this, 'add_assets' ) );
		$this->action( 'admin_notices', array( $this, 'show_fresh_install_notice' ) );
		$this->action( 'admin_notices', array( $this, 'show_pro_outdated_notice' ) );
	}

	public function show_fresh_install_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['page'] ) && 'thumbpress' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_option( 'thumbpress_fresh_install_notice_dismissed', 1 );
			return;
		}

		if ( get_option( 'thumbpress_fresh_install_notice_dismissed' ) ) {
			return;
		}

		// // Only for totally new users — no modules configured yet.
		// if ( get_option( 'thumbpress_modules' ) ) {
		// return;
		// }

		$dashboard_url = admin_url( 'admin.php?page=thumbpress' );
		?>
		<div class="notice notice-info" id="thumbpress-fresh-install-notice" style="padding: 12px 16px; position: relative;">
			<button type="button" class="notice-dismiss" id="thumbpress-fresh-install-dismiss">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'image-sizes' ); ?></span>
			</button>
			<p style="font-size: 14px; margin: 0 0 10px;">
				<strong><?php esc_html_e( 'ThumbPress is installed!', 'image-sizes' ); ?></strong><br>
				<?php esc_html_e( 'Your images are ready to be optimized. Visit the dashboard to get started.', 'image-sizes' ); ?>
			</p>
			<p style="margin: 0;">
				<a href="<?php echo esc_url( $dashboard_url ); ?>" id="thumbpress-fresh-install-visit" class="button button-primary">
					<?php esc_html_e( 'Go to Dashboard', 'image-sizes' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function show_pro_outdated_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( get_option( 'thumbpress_pro_outdated_notice_dismissed' ) ) {
			return;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins     = get_plugins();
		$pro_version = isset( $plugins['thumbpress-pro/thumbpress-pro.php']['Version'] )
			? $plugins['thumbpress-pro/thumbpress-pro.php']['Version']
			: null;

		if ( ! $pro_version ) {
			return;
		}

		if ( version_compare( $pro_version, self::MIN_PRO_VERSION, '>=' ) ) {
			return;
		}

		$update_url = wp_nonce_url(
			self_admin_url( 'update.php?action=upgrade-plugin&plugin=thumbpress-pro/thumbpress-pro.php' ),
			'upgrade-plugin_thumbpress-pro/thumbpress-pro.php'
		);

		?>
		<div class="notice notice-warning" id="thumbpress-pro-outdated-notice" style="padding: 12px 16px; position: relative;">
			<button type="button" class="notice-dismiss" id="thumbpress-pro-outdated-notice-dismiss">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'image-sizes' ); ?></span>
			</button>
			<p style="font-size: 14px; margin: 0 0 10px;">
				<strong><?php esc_html_e( 'ThumbPress Pro update required!', 'image-sizes' ); ?></strong><br>
				<?php
				printf(
					/* translators: 1: installed pro version, 2: required pro version */
					esc_html__( 'ThumbPress requires ThumbPress Pro version %2$s or higher. You have version %1$s. Please update to use all new features.', 'image-sizes' ),
					esc_html( $pro_version ),
					esc_html( self::MIN_PRO_VERSION )
				);
				?>
			</p>
			<p style="margin: 0;">
				<a href="<?php echo esc_url( $update_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Update ThumbPress Pro Now', 'image-sizes' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function add_assets() {
		wp_enqueue_style(
			'thumbpress-admin',
			THUMBPRESS_PLUGIN_URL . 'assets/admin/css/style.css',
			array(),
			THUMBPRESS_VERSION
		);

		$this->enqueue_script(
			'image-sizes_admin',
			THUMBPRESS_PLUGIN_URL . 'assets/admin/js/init.js'
		);

		$this->enqueue_script(
			'thumbpress-notices',
			THUMBPRESS_PLUGIN_URL . 'assets/admin/js/notices.js'
		);

		wp_localize_script(
			'thumbpress-notices',
			'thumbpressNoticesData',
			array(
				'optionUrl' => esc_url_raw( rest_url( 'thumbpress/v1/option' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
			)
		);

		global $thumbpress_menus;

		$version_manager = new VersionManager();

		$this->localize_script(
			'image-sizes_admin',
			'THUMBPRESS',
			array(
				'menus'              => $thumbpress_menus,
				'api_base'           => rest_url( 'thumbpress/v1' ),
				'version_switch_url' => rest_url( 'cx/v1/version-preference' ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'assets_url'         => THUMBPRESS_ASSETS_URL,
				'pro_active'         => apply_filters( 'thumbpress_is_pro_active', defined( 'THUMBPRESS_PRO_VERSION' ) ),
				'is_new_user'        => $version_manager->is_new_user(),
			)
		);
	}
}
