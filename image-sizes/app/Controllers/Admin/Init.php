<?php
namespace Codexpert\ThumbPress\Controllers\Admin;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Bootstrap\VersionManager;
use Codexpert\ThumbPress\Traits\Hook;
use Codexpert\ThumbPress\Traits\Asset;

class Init {

	use Hook;
	use Asset;

	public function __construct() {
		$this->action( 'admin_enqueue_scripts', array( $this, 'add_assets' ) );
		$this->action( 'admin_notices', array( $this, 'show_fresh_install_notice' ) );
		$this->action( 'admin_footer', array( $this, 'render_toast' ) );

		// World Cup upgrade offer admin bar.
		$this->action( 'admin_bar_menu', array( $this, 'add_offer_admin_bar_node' ), 100 );
		$this->action( 'admin_head', array( $this, 'render_offer_admin_bar_assets' ) );
		$this->action( 'wp_ajax_thumbpress_dismiss_offer', array( $this, 'dismiss_offer_admin_bar' ) );
	}

	/**
	 * Whether the World Cup upgrade offer should be shown to the current user.
	 *
	 * Hidden when Pro is active, when the user lacks the manage_options
	 * capability, or once dismissed (persisted so it never reappears).
	 *
	 * @return bool
	 */
	protected function should_show_offer() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( apply_filters( 'thumbpress_is_pro_active', defined( 'THUMBPRESS_PRO_VERSION' ) ) ) {
			return false;
		}

		if ( get_option( 'thumbpress_worldcup_offer_dismissed' ) ) {
			return false;
		}

		return (bool) apply_filters( 'thumbpress_show_offer_admin_bar', true );
	}

	/**
	 * Add the World Cup upgrade offer node to the admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 * @return void
	 */
	public function add_offer_admin_bar_node( $wp_admin_bar ) {
		if ( ! $this->should_show_offer() ) {
			return;
		}

		$upgrade_url = admin_url( 'admin.php?page=thumbpress#/pro' );

		$title = sprintf(
			'<span class="thumbpress-offer-text">%1$s</span><span class="thumbpress-offer-dismiss" role="button" tabindex="0" aria-label="%2$s" title="%2$s">&times;</span>',
			esc_html__( 'ThumbPress: World Cup offer - up to 48% Off', 'image-sizes' ),
			esc_attr__( 'Dismiss', 'image-sizes' )
		);

		$wp_admin_bar->add_node(
			array(
				'id'    => 'thumbpress-offer',
				'title' => $title,
				'href'  => $upgrade_url,
				'meta'  => array( 'class' => 'thumbpress-offer-node' ),
			)
		);
	}

	/**
	 * Inline CSS/JS for the offer admin-bar node: orange styling and dismiss
	 * handling (persists dismissal via admin-ajax so it never returns).
	 *
	 * @return void
	 */
	public function render_offer_admin_bar_assets() {
		if ( ! $this->should_show_offer() ) {
			return;
		}

		$nonce = wp_create_nonce( 'thumbpress_dismiss_offer' );
		?>
		<style>
			#wpadminbar #wp-admin-bar-thumbpress-offer > .ab-item {
				background: #ea580c;
				color: #fff !important;
				font-weight: 600;
			}
			#wpadminbar #wp-admin-bar-thumbpress-offer:hover > .ab-item {
				background: #c2410c;
				color: #fff !important;
			}
			#wpadminbar #wp-admin-bar-thumbpress-offer .thumbpress-offer-text {
				font-weight: 700;
			}
			#wpadminbar #wp-admin-bar-thumbpress-offer .thumbpress-offer-dismiss {
				margin-left: 8px;
				font-size: 16px;
				line-height: 1;
				opacity: 0.85;
				cursor: pointer;
			}
			#wpadminbar #wp-admin-bar-thumbpress-offer .thumbpress-offer-dismiss:hover {
				opacity: 1;
			}
		</style>
		<script>
			( function() {
				document.addEventListener( 'click', function( e ) {
					var btn = e.target.closest( '#wp-admin-bar-thumbpress-offer .thumbpress-offer-dismiss' );
					if ( ! btn ) {
						return;
					}
					e.preventDefault();
					e.stopPropagation();

					var node = document.getElementById( 'wp-admin-bar-thumbpress-offer' );
					if ( node ) {
						node.parentNode.removeChild( node );
					}

					var body = new URLSearchParams();
					body.append( 'action', 'thumbpress_dismiss_offer' );
					body.append( 'nonce', '<?php echo esc_js( $nonce ); ?>' );

					window.fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: body.toString(),
					} );
				}, true );
			} )();
		</script>
		<?php
	}

	/**
	 * AJAX handler: persist dismissal of the World Cup offer.
	 *
	 * @return void
	 */
	public function dismiss_offer_admin_bar() {
		check_ajax_referer( 'thumbpress_dismiss_offer', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'image-sizes' ) ), 403 );
		}

		update_option( 'thumbpress_worldcup_offer_dismissed', 1 );

		wp_send_json_success();
	}

	public function render_toast() {
		echo '<div id="thumbpress-toast" class="thumbpress-toast" aria-live="polite"></div>';
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
				'pro_installed'      => defined( 'THUMBPRESS_PRO_VERSION' ),
				'is_new_user'        => ( false === get_option( 'thumbpress_modules', false ) ),
			)
		);
	}
}
