<?php
namespace Codexpert\ThumbPress\Controllers\Admin;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\API\Dashboard;
use Codexpert\ThumbPress\Traits\Hook;
use Codexpert\ThumbPress\Traits\Asset;

class Init {

	use Hook;
	use Asset;

	public function __construct() {
		$this->action( 'admin_enqueue_scripts', array( $this, 'add_assets' ) );
		$this->action( 'admin_notices', array( $this, 'show_fresh_install_notice' ) );
		$this->action( 'admin_footer', array( $this, 'render_toast' ) );

		// Promo offer / Media Health admin-bar notice (mutually exclusive).
		$this->action( 'admin_bar_menu', array( $this, 'add_admin_bar_notice' ), 100 );
		$this->action( 'admin_head', array( $this, 'render_admin_bar_notice_assets' ) );
		$this->action( 'wp_ajax_thumbpress_dismiss_offer', array( $this, 'dismiss_offer' ) );
		$this->action( 'wp_ajax_thumbpress_dismiss_health_notice', array( $this, 'dismiss_health_notice' ) );
	}

	/**
	 * Option recording that the current campaign's offer bar was dismissed.
	 *
	 * Each campaign gets its own option, so dismissing one offer does not
	 * silently hide every future one. Bump this when the campaign changes.
	 */
	const OFFER_DISMISSED_OPTION = 'thumbpress_summer_offer_dismissed';

	/**
	 * Option recording that the Media Health admin-bar notice was dismissed.
	 */
	const HEALTH_NOTICE_DISMISSED_OPTION = 'thumbpress_health_notice_dismissed';

	/**
	 * Add the promo offer OR the Media Health notice to the admin bar.
	 *
	 * The two are mutually exclusive and switch on the promo date gate: while the
	 * campaign is live the upgrade offer shows; once it ends the offer and all of
	 * its styling disappear entirely (no "expired" state) and the Media Health
	 * status notice takes its place.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 * @return void
	 */
	public function add_admin_bar_notice( $wp_admin_bar ) {
		if ( $this->should_show_offer() ) {
			$this->add_offer_node( $wp_admin_bar );
			return;
		}

		if ( $this->should_show_health_notice() ) {
			$this->add_health_notice_node( $wp_admin_bar );
		}
	}

	/**
	 * Inline CSS/JS for whichever admin-bar node is active.
	 *
	 * @return void
	 */
	public function render_admin_bar_notice_assets() {
		if ( $this->should_show_offer() ) {
			$this->render_offer_assets();
			return;
		}

		if ( $this->should_show_health_notice() ) {
			$this->render_health_notice_assets();
		}
	}

	/**
	 * Whether the Summer Special upgrade offer should be shown to the current user.
	 *
	 * Shown only while the promo campaign is live ({@see thumbpress_promo_active()}).
	 * Hidden when Pro is active, when the user lacks the manage_options capability,
	 * or once dismissed (persisted so it never reappears).
	 *
	 * @return bool
	 */
	protected function should_show_offer() {
		if ( ! thumbpress_promo_active() ) {
			return false;
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( apply_filters( 'thumbpress_is_pro_active', defined( 'THUMBPRESS_PRO_VERSION' ) ) ) {
			return false;
		}

		if ( get_option( self::OFFER_DISMISSED_OPTION ) ) {
			return false;
		}

		return (bool) apply_filters( 'thumbpress_show_offer_admin_bar', true );
	}

	/**
	 * Whether the Media Health status notice should be shown.
	 *
	 * Replaces the promo offer once the campaign ends: shown to manage_options
	 * admins when the promo is not active, a score is available, and it has not
	 * been dismissed. Hidden gracefully when there is no score yet.
	 *
	 * @return bool
	 */
	protected function should_show_health_notice() {
		if ( thumbpress_promo_active() ) {
			return false;
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( get_option( self::HEALTH_NOTICE_DISMISSED_OPTION ) ) {
			return false;
		}

		return null !== $this->get_health_score();
	}

	/**
	 * Media Library health score for the admin-bar notice — the exact same value
	 * the dashboard shows, since it reuses Dashboard::get_health_score(). Memoized
	 * per request because the node is queried a few times per render. Null when
	 * there are no images, so the notice hides gracefully.
	 *
	 * @return int|null
	 */
	protected function get_health_score() {
		static $computed = false;
		static $score    = null;

		if ( ! $computed ) {
			$score    = ( new Dashboard() )->get_health_score();
			$computed = true;
		}

		return $score;
	}

	/**
	 * Map a health score to the notice's premium gradient background and an
	 * auto-contrast font color, switched across four score bands (per the campaign
	 * spec): 0–50 red, 51–75 orange, 76–95 lime, 96–100 green. Font stays WCAG-AA
	 * legible — white on the darker red/green, near-black on the lighter orange/lime.
	 *
	 * @param int $score 0–100.
	 * @return array{bg:string,text:string}
	 */
	protected function health_band_colors( $score ) {
		if ( $score <= 50 ) {
			return array( 'bg' => 'linear-gradient(135deg, #E24C5B, #B21E35)', 'text' => '#FFFFFF' );
		}

		if ( $score <= 75 ) {
			return array( 'bg' => 'linear-gradient(135deg, #F6A821, #E17A0E)', 'text' => '#3A2A00' );
		}

		if ( $score <= 95 ) {
			return array( 'bg' => 'linear-gradient(135deg, #BBD94B, #98BE2C)', 'text' => '#26340F' );
		}

		return array( 'bg' => 'linear-gradient(135deg, #21A85E, #128046)', 'text' => '#FFFFFF' );
	}

	/**
	 * Add the Summer Special upgrade offer node to the admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 * @return void
	 */
	protected function add_offer_node( $wp_admin_bar ) {
		$upgrade_url = admin_url( 'admin.php?page=thumbpress#/pro' );

		$offer_text = esc_html__( 'ThumbPress: Summer Special - Up to 48% OFF', 'image-sizes' );

		$title = sprintf(
			'<span class="thumbpress-offer-text">%1$s</span><span class="thumbpress-offer-dismiss" role="button" tabindex="0" aria-label="%2$s" title="%2$s">&times;</span>',
			$offer_text,
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
	 * Add the Media Health status node to the admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 * @return void
	 */
	protected function add_health_notice_node( $wp_admin_bar ) {
		$score         = (int) $this->get_health_score();
		$dashboard_url = admin_url( 'admin.php?page=thumbpress' );

		// The whole node links to the dashboard; "View Details" is underlined to
		// read as the actionable link, per the spec.
		$notice_text = sprintf(
			/* translators: 1: health score percentage, 2: opening span tag, 3: closing span tag. */
			esc_html__( 'Media Health at %1$d%% - %2$sView Details%3$s', 'image-sizes' ),
			$score,
			'<span class="thumbpress-health-link">',
			'</span>'
		);

		$title = sprintf(
			'<span class="thumbpress-health-text">%1$s</span><span class="thumbpress-health-dismiss" role="button" tabindex="0" aria-label="%2$s" title="%2$s">&times;</span>',
			$notice_text,
			esc_attr__( 'Dismiss', 'image-sizes' )
		);

		$wp_admin_bar->add_node(
			array(
				'id'    => 'thumbpress-health-notice',
				'title' => $title,
				'href'  => $dashboard_url,
				'meta'  => array( 'class' => 'thumbpress-health-node' ),
			)
		);
	}

	/**
	 * Inline CSS/JS for the offer admin-bar node: orange styling and dismiss
	 * handling (persists dismissal via admin-ajax so it never returns).
	 *
	 * @return void
	 */
	protected function render_offer_assets() {
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
	 * Inline CSS/JS for the Media Health node: dashboard status-band background
	 * (green/amber/red by score) and dismiss handling via admin-ajax.
	 *
	 * @return void
	 */
	protected function render_health_notice_assets() {
		$colors = $this->health_band_colors( (int) $this->get_health_score() );
		$nonce  = wp_create_nonce( 'thumbpress_dismiss_health_notice' );
		?>
		<style>
			#wpadminbar #wp-admin-bar-thumbpress-health-notice > .ab-item {
				background: <?php echo esc_attr( $colors['bg'] ); ?>;
				color: <?php echo esc_attr( $colors['text'] ); ?> !important;
				font-weight: 600;
			}
			#wpadminbar #wp-admin-bar-thumbpress-health-notice:hover > .ab-item {
				background: <?php echo esc_attr( $colors['bg'] ); ?>;
				color: <?php echo esc_attr( $colors['text'] ); ?> !important;
				opacity: 0.9;
			}
			#wpadminbar #wp-admin-bar-thumbpress-health-notice .thumbpress-health-text {
				font-weight: 700;
			}
			#wpadminbar #wp-admin-bar-thumbpress-health-notice .thumbpress-health-link {
				text-decoration: underline;
			}
			#wpadminbar #wp-admin-bar-thumbpress-health-notice .thumbpress-health-dismiss {
				margin-left: 8px;
				font-size: 16px;
				line-height: 1;
				opacity: 0.85;
				cursor: pointer;
			}
			#wpadminbar #wp-admin-bar-thumbpress-health-notice .thumbpress-health-dismiss:hover {
				opacity: 1;
			}
		</style>
		<script>
			( function() {
				document.addEventListener( 'click', function( e ) {
					var btn = e.target.closest( '#wp-admin-bar-thumbpress-health-notice .thumbpress-health-dismiss' );
					if ( ! btn ) {
						return;
					}
					e.preventDefault();
					e.stopPropagation();

					var node = document.getElementById( 'wp-admin-bar-thumbpress-health-notice' );
					if ( node ) {
						node.parentNode.removeChild( node );
					}

					var body = new URLSearchParams();
					body.append( 'action', 'thumbpress_dismiss_health_notice' );
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
	 * AJAX handler: persist dismissal of the Summer Special offer.
	 *
	 * @return void
	 */
	public function dismiss_offer() {
		check_ajax_referer( 'thumbpress_dismiss_offer', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'image-sizes' ) ), 403 );
		}

		update_option( self::OFFER_DISMISSED_OPTION, 1 );

		wp_send_json_success();
	}

	/**
	 * AJAX handler: persist dismissal of the Media Health notice.
	 *
	 * @return void
	 */
	public function dismiss_health_notice() {
		check_ajax_referer( 'thumbpress_dismiss_health_notice', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'image-sizes' ) ), 403 );
		}

		update_option( self::HEALTH_NOTICE_DISMISSED_OPTION, 1 );

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

		$this->localize_script(
			'image-sizes_admin',
			'THUMBPRESS',
			array(
				'menus'              => $thumbpress_menus,
				'api_base'           => rest_url( 'thumbpress/v1' ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'assets_url'         => THUMBPRESS_ASSETS_URL,
				'pro_active'         => apply_filters( 'thumbpress_is_pro_active', defined( 'THUMBPRESS_PRO_VERSION' ) ),
				'pro_installed'      => defined( 'THUMBPRESS_PRO_VERSION' ),
				'is_new_user'        => ( false === get_option( 'thumbpress_modules', false ) ),
				// Promo campaign gate — the SPA reads server truth instead of computing
				// its own deadline, so pricing/popup flip off in sync with PHP.
				'promo_active'       => thumbpress_promo_active(),
				'promo_end'          => thumbpress_promo_end_timestamp(), // UTC Unix seconds.
				// BCP-47 locale tag for locale-aware number/size formatting in the SPA.
				'locale'             => str_replace( '_', '-', get_locale() ),
			)
		);
	}
}
