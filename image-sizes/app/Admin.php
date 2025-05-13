<?php
namespace Codexpert\ThumbPress\App;

use Codexpert\Plugin\Base;
use Codexpert\ThumbPress\Helper;
use Codexpert\ThumbPress\Notice;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Admin
 * @author Codexpert <hi@codexpert.io>
 */
class Admin extends Base {

	public $plugin;
	public $slug;
	public $name;
	public $server;
	public $version;
	public $admin_url;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin  = $plugin;
		$this->slug    = $this->plugin['TextDomain'];
		$this->name    = $this->plugin['Name'];
		$this->server  = $this->plugin['server'];
		$this->version = $this->plugin['Version'];
	}

	/**
	 * Check for action scheduler tables before activation
	 */
	public function check_action_scheduler_tables() {

		$table_report = thumbpress_check_action_tables();

		// check for missing tables
		if ( in_array( true, $table_report ) ) :

			// check store table
			if ( $table_report['store_table_missing'] ) :
				delete_option( 'schema-ActionScheduler_StoreSchema' );

				$action_store_db = new \ActionScheduler_DBStore();
				$action_store_db->init();
			endif;

			// check log table
			if ( $table_report['log_table_missing'] ) :
				delete_option( 'schema-ActionScheduler_LoggerSchema' );

				$action_log_db = new \ActionScheduler_DBLogger();
				$action_log_db->init();
			endif;

		endif;
	}

	public function add_body_class( $classes ) {

		$classes .= ' thumbpress';
		$classes .= defined( 'THUMBPRESS_PRO' ) ? ' thumbpress-pro' : '';

		return $classes;
	}

	/**
	 * Internationalization
	 */
	public function i18n() {
		load_plugin_textdomain( 'image-sizes', false, THUMBPRESS_DIR . '/languages/' );
	}

	public function thumbpress_sale_notice() {
		if ( ! defined( 'THUMBPRESS_PRO' ) ) {
			$notice_id	= 'thumbpress-mothers_day_campaign';
			$url        = 'https://thumbpress.co/pricing/?utm_source=In-plugin&utm_medium=offer+notice&utm_campaign=mothersday+2025';
			$logo_url = THUMBPRESS_ASSET . '/img/banner-section/logo.png';

			if( get_option( 'thumbpress-mothers_day_campaign_dismissed' ) !== false ) {
				return;
			}

			$sale_notice = new Notice( $notice_id );
			$expiry_timestamp = strtotime( '2025-05-14 23:59:59' );
			$sale_notice->set_expiry( $expiry_timestamp );

			$allowed_html = [
				'span' => array(
					'class' => true
				)
			];

			$message = '
					<div class="thumbpress-mothersday-deals-notice-content">
						<img src="' . esc_url( $logo_url ) . '" alt="Thumbpress" class="thumbpress-notice-image" >
						
						<div class="tp-timer-wrapper">
							<div class="tp-timer">
								<div class="tp-count">
									<span id="days"></span>
									<label>DAY</label>
								</div>

								<div class="tp-count">
									<span id="hours"></span>
									<label>HRS</label>
								</div>

								<div class="tp-count">
									<span id="minutes"></span>
									<label>MIN</label>
								</div>
								
								<div class="tp-count">
									<span id="seconds"></span>
									<label>SEC</label>
								</div>
							</div>
						</div>

						<p class="notice-subtitle">' . wp_kses( sprintf( "Last Chance: Mother's Day <span>Lifetime Deal</span> Ending Soon...", 'thumbpress' ), $allowed_html ) . '</p>
						
						<a href="' . esc_url( $url ) . '" class="notice-cta-button" data-id="' . esc_attr( $notice_id ) . '"  target="_blank">
						' . __( 'Grab Now', 'thumbpress' ) . '
						</a>
					</div>
			';

			$sale_notice->set_message( $message );
			$sale_notice->set_screens( array( 'dashboard', 'toplevel_page_thumbpress', 'thumbpress_page_thumbpress-regenerate-thumbnails', 'thumbpress_page_thumbpress-convert-images' ) );
			$sale_notice->render();	
		}
	}

	public function show_admin_notices() {
		if ( false !== get_option( 'thumbpress_settings_init' ) ) {
			return;
		}

		if ( 'toplevel_page_thumbpress' == get_current_screen()->base ) {
			update_option( 'thumbpress_settings_init', 1 );
		} else {
			printf(
				'<div class="notice notice-warning is-dismissible thumbpress-notice"><p>%s</p></div>',
				sprintf(
					/* Translators: %s is the link to the setup wizard */
					__( 'Congratulations on installing <strong>ThumbPress</strong>!🎉 You\'re just a few steps away from optimizing your images. <a href="%s"><strong>Click here</strong></a> to enable modules and get started! 🚀', 'thumbpress' ),
					esc_url( admin_url( 'admin.php?page=thumbpress' ) )
				)
			);

			echo '<style>.thumbpress-notice { background-color: #5be8ff52;} .thumbpress-notice p {font-size: 14px;}</style>';
		}
	}

	public function show_easycommerce_notice() {
		if ( false !== get_option( 'thumbpress_settings_init' ) && ! defined( 'THUMBPRESS_PRO' ) && current_user_can( 'manage_options' ) ) {
			$data_id    = 'thumbpress-easycommerce_campain';
			$url        = 'https://easycommerce.dev/?utm_source=wp+dashboard&utm_medium=thumbpress+notice&utm_campaign=introducing+easycommerce';
			$image_path = THUMBPRESS_ASSET . '/img/banner-section/tp-logo.png';

			$notice = new Notice( $data_id );

			$notice->set_intervals( array( 0 ) ); // Show at 0s (immediately)
			$notice->set_expiry( 3 * DAY_IN_SECONDS ); // Don't show after 3 days

			$message = '   
				<div class="thumbpress-dismissible-notice-content">
					<img src="' . $image_path . '" alt="thumbpress" class="thumbpress-notice-image" >
					<p class="thumbpress-notice-title">Introducing <span>EasyCommerce</span> -  A Revolutionary WordPress Ecommerce Plugin</p>
					<div class="button-wrapper">
						<a href="' . esc_url( $url ) . '" class="thumbpress-dismissible-notice-button" data-id="' . esc_attr( $data_id ) . '">Check it Out</a>
					</div>
				</div>';

			$notice->set_message( $message );
			$notice->set_screens( array( 'dashboard' ) );
			$notice->render();
		}
	}

	public function upgrade() {
		$current_time = date_i18n( 'U' );
		if ( ! get_option( 'image_sizes_year_notice' ) ) {
			foreach ( image_sizes_notices_values() as $id => $notice ) {
				$data = array(
					'from' => $notice['from'],
					'to'   => $notice['to'],
				);

				$expiration_duration = $notice['to'] - $current_time;
				set_transient( $id, $data, $expiration_duration );
			}
			update_option( 'image_sizes_year_notice', 1 );
		}

		if ( $this->version == get_option( "{$this->slug}_db-version" ) ) {
			return;
		}
		update_option( "{$this->slug}_db-version", $this->version );

		delete_option( 'codexpert-blog-json' );
	}

	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$screen        = get_current_screen();
		$valid_screens = array( 'upload', 'media', 'dashboard' );
		if ( ! in_array( $screen->id, $valid_screens ) &&
			! ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'thumbpress' ) !== false ) ) {
			return;
		}
		$min = defined( 'THUMBPRESS_DEBUG' ) && THUMBPRESS_DEBUG ? '' : '.min';

		wp_enqueue_style( $this->slug, plugins_url( '/assets/css/admin.css', THUMBPRESS ), '', time(), 'all' );
		wp_enqueue_style( $this->slug . 'dashboard', plugins_url( '/assets/css/settings/dashboard.css', THUMBPRESS ), '', time(), 'all' );
		wp_enqueue_style( $this->slug . 'google-font', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap' );
		wp_enqueue_style( $this->slug . 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css' );
		wp_enqueue_script( $this->slug . 'font-awesome-js', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.js', array( 'jquery' ), time(), true );

		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/admin{$min}.js", THUMBPRESS ), array( 'jquery' ), time(), true );

		wp_enqueue_script( 'wp-pointer' );
		wp_enqueue_style( 'wp-pointer' );

		$max_size_value = get_option( 'thumbpress_max_size_value' );
		$base_url       = admin_url( 'admin.php' );
		$target_url     = add_query_arg(
			array(
				'page'                   => 'thumbpress-detect-large-images',
				'thumb-large-image-size' => $max_size_value,
			),
			$base_url
		);

		$localized = array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( $this->slug ),
			'asseturl'    => THUMBPRESS_ASSET,
			'regen'       => __( 'Regenerate Now', 'image-sizes' ),
			'regening'    => __( 'Regenerating..', 'image-sizes' ),
			'detect'      => __( 'Detect', 'image-sizes' ),
			'detecting'   => __( 'Detecting', 'image-sizes' ),
			'detectNow'   => __( 'Detect Now', 'image-sizes' ),
			'detected'    => __( 'Detected', 'image-sizes' ),
			'analyze'     => __( 'Analyze', 'image-sizes' ),
			'analyzing'   => __( 'Analyzing..', 'image-sizes' ),
			'analyzed'    => __( 'Analyzed', 'image-sizes' ),
			'optimize'    => __( 'Compress', 'image-sizes' ),
			'compressNow' => __( 'Compress Now', 'image-sizes' ),
			'compressing' => __( 'Compressing..', 'image-sizes' ),
			'confirm'     => esc_html__( 'Are you sure you want to delete this? The data and its associated files will be completely erased. This action cannot be undone!', 'image-sizes' ),
			'confirm_all' => esc_html__( 'Are you sure you want to delete these? The data and their associated files will be completely erased. This action cannot be undone!', 'image-sizes' ),
			// 'is_welcome' => $this->get_pointers(),
			'live_chat'   => get_option( 'thumbpress_live_chat_enabled' ) == 1,
			'tp_page'     => isset( $_GET['page'] ) && false !== strpos( $_GET['page'], 'thumbpress' ),
			'name'        => get_userdata( get_current_user_id() )->display_name,
			'email'       => get_userdata( get_current_user_id() )->user_email,
			'converting'  => __( 'Converting', 'image-sizes' ),
			'convertNow'  => __( 'Convert Now', 'image-sizes' ),
			'target_url'  => $target_url,
		);
		wp_localize_script( $this->slug, 'THUMBPRESS', apply_filters( "{$this->slug}-localized", $localized ) );
	}

	public function action_links( $links ) {
		$this->admin_url = admin_url( 'admin.php' );

		$new_links['settings'] = sprintf( '<a href="%2$s" target="_blank">%1$s</a>', __( 'Settings', 'image-sizes' ), add_query_arg( 'page', 'thumbpress', $this->admin_url ) );
		$new_links['support']  = sprintf( '<a href="%2$s" target="_blank">%1$s</a>', __( 'Support', 'image-sizes' ), 'https://help.codexpert.io/add-ticket/' );
		$new_links['docs']     = sprintf( '<a href="%2$s" target="_blank">%1$s</a>', __( 'Docs', 'image-sizes' ), 'https://thumbpress.co/doc-topic/installation/' );

		return array_merge( $new_links, $links );
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( $this->plugin['basename'] === $plugin_file ) {
			$plugin_meta['help'] = '<a href="https://help.codexpert.io/" target="_blank" class="cx-help">' . __( 'Help', 'image-sizes' ) . '</a>';
		}

		return $plugin_meta;
	}

	public function footer_text( $text ) {
		if ( get_current_screen()->parent_base != $this->slug ) {
			return $text;
		}

		/* translators: %1$s is the plugin name, %2$s is the link to leave a review, %3$s is the rating stars */
		return sprintf( __( 'If you like <strong>%1$s</strong>, please <a href="%2$s" target="_blank">leave us a %3$s rating</a> on WordPress.org! It\'d motivate and inspire us to make the plugin even better!', 'image-sizes' ), $this->name, "https://wordpress.org/support/plugin/{$this->slug}/reviews/?filter=5#new-post", '⭐⭐⭐⭐⭐' );
	}

	public function modal() {
		echo '
		<div id="image-sizes-modal" style="display: none">
			<img id="image-sizes-modal-loader" src="' . esc_attr( THUMBPRESS_ASSET . '/img/loader.gif' ) . '" />
		</div>';
	}

	public function thumbpress_modules_activation() {

		if ( ! get_option( 'thumbpress_modules' ) ) {

			$thumbpress_modules = array(
				'disable-thumbnails'    => 'on',
				'regenerate-thumbnails' => 'on',
				'social-share'          => 'on',
				'convert-images'        => 'on',
			);

			add_option( 'thumbpress_modules', $thumbpress_modules );
		}
	}
}
