<?php
namespace Codexpert\ThumbPress\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminNotice {

	const MIN_PRO_VERSION = '6.0';

	/**
	 * Hook into WordPress.
	 */
	public function init() {
		if ( $this->is_pro_installed_not_activated() ) {
			add_action( 'admin_notices', array( $this, 'render_pro_not_activated_notice' ) );
			add_action( 'wp_ajax_thumbpress_dismiss_pro_not_activated', array( $this, 'dismiss_pro_not_activated_notice' ) );
		}
	}

	public function dismiss_pro_not_activated_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}
		check_ajax_referer( 'thumbpress_dismiss_pro_not_activated' );

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins     = get_plugins();
		$pro_version = isset( $plugins['thumbpress-pro/thumbpress-pro.php']['Version'] )
			? $plugins['thumbpress-pro/thumbpress-pro.php']['Version']
			: '0';

		update_option( 'thumbpress_pro_not_activated_notice_dismissed', $pro_version );
		wp_send_json_success();
	}

	public function render_pro_not_activated_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		if ( ! isset( $plugins['thumbpress-pro/thumbpress-pro.php'] ) ) {
			return;
		}

		$pro_version = $plugins['thumbpress-pro/thumbpress-pro.php']['Version'] ?? '';

		if ( ! $pro_version || version_compare( $pro_version, self::MIN_PRO_VERSION, '<' ) ) {
			return;
		}

		$dismissed_for = get_option( 'thumbpress_pro_not_activated_notice_dismissed', '' );
		if ( $dismissed_for && $dismissed_for === $pro_version ) {
			return;
		}

		$activate_url = wp_nonce_url(
			self_admin_url( 'plugins.php?action=activate&plugin=thumbpress-pro/thumbpress-pro.php' ),
			'activate-plugin_thumbpress-pro/thumbpress-pro.php'
		);
		?>
		<div class="notice notice-warning" id="thumbpress-pro-not-activated-notice" style="padding: 12px 16px; position: relative;">
			<button type="button" class="notice-dismiss" id="thumbpress-pro-not-activated-dismiss">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'image-sizes' ); ?></span>
			</button>
			<p style="font-size: 14px; margin: 0;">
				<strong><?php esc_html_e( 'ThumbPress Pro is installed but not activated!', 'image-sizes' ); ?></strong><br>
				<?php esc_html_e( 'Activate ThumbPress Pro to unlock all premium features including image compression, unused image detection, large image detection, and more.', 'image-sizes' ); ?>
			</p>
			<p style="margin: 10px 0 0;">
				<a href="<?php echo esc_url( $activate_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Activate ThumbPress Pro', 'image-sizes' ); ?>
				</a>
			</p>
		</div>
		<script>
		(function() {
			var btn = document.getElementById( 'thumbpress-pro-not-activated-dismiss' );
			if ( ! btn ) return;
			btn.addEventListener( 'click', function() {
				document.getElementById( 'thumbpress-pro-not-activated-notice' ).style.display = 'none';
				var fd = new FormData();
				fd.append( 'action', 'thumbpress_dismiss_pro_not_activated' );
				fd.append( '_wpnonce', '<?php echo esc_js( wp_create_nonce( 'thumbpress_dismiss_pro_not_activated' ) ); ?>' );
				fetch( ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' } );
			} );
		})();
		</script>
		<?php
	}

	private function is_pro_installed_not_activated() {
		$pro_basename = 'thumbpress-pro/thumbpress-pro.php';

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		if ( ! isset( $plugins[ $pro_basename ] ) ) {
			return false;
		}

		$pro_version = isset( $plugins[ $pro_basename ]['Version'] ) ? $plugins[ $pro_basename ]['Version'] : null;
		if ( ! $pro_version || version_compare( $pro_version, self::MIN_PRO_VERSION, '<' ) ) {
			return false;
		}

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		return ! in_array( $pro_basename, $active_plugins, true );
	}

}
