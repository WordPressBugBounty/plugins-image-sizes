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
		add_action( 'admin_head', array( $this, 'cleanup_legacy_localstorage' ), 1 );

		$manager = new VersionManager();

		if ( $manager->is_old_pro() || $this->is_old_pro_installed_not_active() ) {
			add_action( 'admin_notices', array( $this, 'render_pro_outdated_notice' ) );
			add_action( 'wp_ajax_thumbpress_dismiss_pro_outdated', array( $this, 'dismiss_pro_outdated_notice' ) );

			if ( $manager->is_old_pro() ) {
				add_action( 'admin_notices', static function() {
					remove_action( 'admin_notices', 'Codexpert\ThumbPress_Pro\thumbpress_pro_free_outdated_notice' );
				}, 1 );
			}

			return;
		}

		if ( $this->is_pro_installed_not_activated() ) {
			add_action( 'admin_notices', array( $this, 'render_pro_not_activated_notice' ) );
			add_action( 'wp_ajax_thumbpress_dismiss_pro_not_activated', array( $this, 'dismiss_pro_not_activated_notice' ) );
			return;
		}

		if ( ! $manager->should_show_notice() ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'render' ) );
		add_action( 'admin_head', array( $this, 'enqueue_script' ) );
	}

	public function dismiss_pro_outdated_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'thumbpress_dismiss_pro_outdated' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}
		update_option( 'thumbpress_pro_outdated_notice_dismissed', 1 );
		wp_send_json_success();
	}

	public function render_pro_outdated_notice() {
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

		if ( ! $pro_version || version_compare( $pro_version, self::MIN_PRO_VERSION, '>=' ) ) {
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
				<?php printf(
					/* translators: 1: installed pro version, 2: required pro version */
					esc_html__( 'ThumbPress requires ThumbPress Pro version %2$s or higher. You have version %1$s. Please update to use all new features.', 'image-sizes' ),
					esc_html( $pro_version ),
					'6.0'
				); ?>
			</p>
			<p style="margin: 0;">
				<a href="<?php echo esc_url( $update_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Update ThumbPress Pro Now', 'image-sizes' ); ?>
				</a>
			</p>
		</div>
		<script>
		(function() {
			var btn = document.getElementById( 'thumbpress-pro-outdated-notice-dismiss' );
			if ( ! btn ) return;
			btn.addEventListener( 'click', function() {
				document.getElementById( 'thumbpress-pro-outdated-notice' ).style.display = 'none';
				var fd = new FormData();
				fd.append( 'action', 'thumbpress_dismiss_pro_outdated' );
				fd.append( '_wpnonce', '<?php echo esc_js( wp_create_nonce( 'thumbpress_dismiss_pro_outdated' ) ); ?>' );
				fetch( ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' } );
			} );
		})();
		</script>
		<?php
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

	private function is_old_pro_installed_not_active() {
		$pro_basename   = 'thumbpress-pro/thumbpress-pro.php';
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		if ( in_array( $pro_basename, $active_plugins, true ) ) {
			return false;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		if ( ! isset( $plugins[ $pro_basename ] ) ) {
			return false;
		}

		$pro_version = isset( $plugins[ $pro_basename ]['Version'] ) ? $plugins[ $pro_basename ]['Version'] : null;
		return $pro_version && version_compare( $pro_version, self::MIN_PRO_VERSION, '<' );
	}

	/**
	 * Clean up SPA hash routes from localStorage that break legacy fields.js.
	 */
	public function cleanup_legacy_localstorage() {
		$manager = new VersionManager();
		if ( 'legacy' === $manager->get_version_to_load() ) {
			?>
			<script>
			try {
				var tab = localStorage.getItem('active_cx_tab');
				if ( tab && tab.indexOf('/') !== -1 ) {
					localStorage.removeItem('active_cx_tab');
				}
			} catch(e) {}
			</script>
			<?php
		}
	}

	/**
	 * Print config as inline script in admin head.
	 */
	public function enqueue_script() {
		$manager = new VersionManager();

		$config = array(
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'rest_url'    => esc_url_raw( get_rest_url( null, 'cx/v1/version-preference' ) ),
			'dismiss_url' => esc_url_raw( get_rest_url( null, 'cx/v1/version-preference/dismiss' ) ),
			'current'     => $manager->get_user_preference() ?? 'legacy',
		);

		echo '<script>var thumbpressVersionNotice = ' . wp_json_encode( $config ) . ';</script>';
	}

	/**
	 * Render the admin notice.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$manager = new VersionManager();
		$current = $manager->get_user_preference() ?? 'legacy';
		?>
		<div class="notice notice-info" id="thumbpress-version-choice" style="padding: 12px 16px; position: relative;">
			<?php if ( 'new' === $current ) : ?>
			<button type="button" class="notice-dismiss" id="thumbpress-dismiss-notice">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'image-sizes' ); ?></span>
			</button>
			<?php endif; ?>
			<p style="font-size: 14px; margin: 0 0 10px;">
				<strong><?php esc_html_e( 'A new version of ThumbPress is ready!', 'image-sizes' ); ?></strong><br>
				<?php
				if ( 'new' === $current ) {
					esc_html_e( 'You are using the new dashboard. Switch back to classic anytime.', 'image-sizes' );
				} else {
					esc_html_e( 'Would you like to try the new modern dashboard, or keep using the classic version?', 'image-sizes' );
				}
				?>
			</p>
			<p style="margin: 0;">
				<?php if ( 'new' === $current ) : ?>
					<span class="button button-primary disabled" style="opacity: 0.7; pointer-events: none;">
						&#10003; <?php esc_html_e( 'Using New Version', 'image-sizes' ); ?>
					</span>
					<button type="button" class="button" id="thumbpress-choose-legacy" style="margin-left: 8px;">
						<?php esc_html_e( 'Switch to Classic', 'image-sizes' ); ?>
					</button>
				<?php else : ?>
					<button type="button" class="button button-primary" id="thumbpress-choose-new">
						<?php esc_html_e( 'Try New Version', 'image-sizes' ); ?>
					</button>
					<span class="button disabled" style="margin-left: 8px; opacity: 0.7; pointer-events: none;">
						&#10003; <?php esc_html_e( 'Using Classic', 'image-sizes' ); ?>
					</span>
				<?php endif; ?>
			</p>
		</div>
		<script>
		(function() {
			var config = window.thumbpressVersionNotice || {};

			function choose( preference ) {
				fetch( config.rest_url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': config.nonce
					},
					body: JSON.stringify({ preference: preference }),
					credentials: 'same-origin'
				}).then(function( response ) {
					if ( response.ok ) {
						try { localStorage.removeItem( 'active_cx_tab' ); } catch(e) {}
						window.location.href = window.location.pathname + '?page=thumbpress';
					}
				});
			}

			var newBtn = document.getElementById( 'thumbpress-choose-new' );
			var legacyBtn = document.getElementById( 'thumbpress-choose-legacy' );

			if ( newBtn ) {
				newBtn.addEventListener( 'click', function() { choose( 'new' ); });
			}
			if ( legacyBtn ) {
				legacyBtn.addEventListener( 'click', function() { choose( 'legacy' ); });
			}

			var dismissBtn = document.getElementById( 'thumbpress-dismiss-notice' );
			if ( dismissBtn ) dismissBtn.addEventListener( 'click', function() {
				fetch( config.dismiss_url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': config.nonce
					},
					credentials: 'same-origin'
				}).then(function() {
					document.getElementById( 'thumbpress-version-choice' ).style.display = 'none';
				});
			});
		})();
		</script>
		<?php
	}
}
