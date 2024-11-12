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
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->server	= $this->plugin['server'];
		$this->version	= $this->plugin['Version'];
	}

	/**
	 * Check for action scheduler tables before activation
	 */
	public function check_action_scheduler_tables() {

		$table_report = thumbpress_check_action_tables();

		// check for missing tables
		if( in_array( true, $table_report ) ) :

			// check store table
			if( $table_report['store_table_missing'] ) :
				delete_option( 'schema-ActionScheduler_StoreSchema' );

				$action_store_db 	= new \ActionScheduler_DBStore();
				$action_store_db->init();
			endif;

			// check log table
			if( $table_report['log_table_missing'] ) :
				delete_option( 'schema-ActionScheduler_LoggerSchema' );

				$action_log_db 		= new \ActionScheduler_DBLogger();
				$action_log_db->init();
			endif;

		endif;
	}

	/**
	 * Internationalization
	 */
	public function i18n() {
		load_plugin_textdomain( 'image-sizes', false, THUMBPRESS_DIR . '/languages/' );
	}

	public function upgrade() {
		$current_time = date_i18n('U');
		if( ! get_option( 'image_sizes_black_notice' ) ){
			foreach ( image_sizes_notices_values() as $id => $notice ) {
				$data = [
					'from' => $notice['from'],
					'to' => $notice['to']
				];
			
				$expiration_duration = $notice['to'] - $current_time; 
				set_transient( $id, $data,  $expiration_duration );
			}
			update_option( 'image_sizes_black_notice', 1 );
		}
		
		if( $this->version == get_option( "{$this->slug}_db-version" ) ) return;
		update_option( "{$this->slug}_db-version", $this->version );
		
		delete_option( 'codexpert-blog-json' );
	}

	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		$valid_screens = [ 'upload', 'media', 'dashboard' ];
		if ( ! in_array( $screen->id, $valid_screens ) &&
			!( isset( $_GET[ 'page' ] ) && strpos( $_GET[ 'page' ], 'thumbpress' ) !== false ) ) {
			return;
		}
		$min = defined( 'THUMBPRESS_DEBUG' ) && THUMBPRESS_DEBUG ? '' : '.min';
		
		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/admin.css", THUMBPRESS ), '', time(), 'all' );
		wp_enqueue_style( $this->slug . 'dashboard', plugins_url( "/assets/css/settings/dashboard.css", THUMBPRESS ), '', time(), 'all' );
		wp_enqueue_style( $this->slug . 'google-font', "https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");
		wp_enqueue_style( $this->slug . 'font-awesome', "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css");
		wp_enqueue_script ($this->slug .'font-awesome-js', "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.js", ['jquery'], time(), true);
		
		wp_enqueue_script($this->slug, plugins_url("/assets/js/admin{$min}.js", THUMBPRESS), ['jquery'], time(), true);
		
		wp_enqueue_script('wp-pointer');
		wp_enqueue_style('wp-pointer');

		$max_size_value = get_option( 'thumbpress_max_size_value' );
		$base_url 	= admin_url( 'admin.php' );
		$target_url = add_query_arg( array(
			'page' => 'thumbpress-detect-large-images',
			'thumb-large-image-size' => $max_size_value,
		), $base_url );

		$localized = array(
			'ajaxurl'		=> admin_url( 'admin-ajax.php' ),
			'nonce'			=> wp_create_nonce( $this->slug ),
			'asseturl'		=> THUMBPRESS_ASSET,
			'regen'			=> __( 'Regenerate Now', 'image-sizes' ),
			'regening'		=> __( 'Regenerating..', 'image-sizes' ),
			'detect'		=> __( 'Detect', 'image-sizes' ),
			'detecting'		=> __( 'Detecting', 'image-sizes' ),
			'detectNow'		=> __( 'Detect Now', 'image-sizes' ),
			'detected'		=> __( 'Detected', 'image-sizes' ),
			'analyze'		=> __( 'Analyze', 'image-sizes' ),
			'analyzing'		=> __( 'Analyzing..', 'image-sizes' ),
			'analyzed'		=> __( 'Analyzed', 'image-sizes' ),
			'optimize'		=> __( 'Compress', 'image-sizes' ),
			'compressNow'	=> __( 'Compress Now', 'image-sizes' ),
			'compressing'	=> __( 'Compressing..', 'image-sizes' ),
			'confirm'		=> esc_html__( 'Are you sure you want to delete this? The data and its associated files will be completely erased. This action cannot be undone!', 'image-sizes' ),
			'confirm_all'	=> esc_html__( 'Are you sure you want to delete these? The data and their associated files will be completely erased. This action cannot be undone!', 'image-sizes' ),
			// 'is_welcome'	=> $this->get_pointers(),
			'live_chat'		=> get_option( 'thumbpress_live_chat_enabled' ) == 1,
			'tp_page'		=> isset( $_GET['page'] ) && false !== strpos( $_GET['page'], 'thumbpress' ),
			'name'			=> get_userdata( get_current_user_id() )->display_name,
			'email'			=> get_userdata( get_current_user_id() )->user_email,
			'converting'	=> __( 'Converting', 'image-sizes' ),
			'convertNow'	=> __( 'Convert Now', 'image-sizes' ),
			'target_url'    => $target_url,
		);
		wp_localize_script( $this->slug, 'THUMBPRESS', apply_filters( "{$this->slug}-localized", $localized ) );
	}

	public function action_links( $links ) {
		$this->admin_url = admin_url( 'admin.php' );
	
		$new_links = [
			'settings' => sprintf( '<a href="%1$s">' . __( 'Settings', 'image-sizes' ) . '</a>', add_query_arg( 'page', 'thumbpress', $this->admin_url ) )
		];
		$support = [
			'support' => sprintf( '<a href="%1$s">' . __( 'Support', 'image-sizes' ) . '</a>', 'https://help.codexpert.io/add-ticket/' )
		];
		$documentation = [
			'documentation' => sprintf( '<a href="%1$s">' . __( 'Documentation', 'image-sizes' ) . '</a>', 'https://thumbpress.co/doc-topic/installation/' )
		];
	
		return array_merge( $new_links, $support, $documentation, $links );
	}
	

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		
		if ( $this->plugin['basename'] === $plugin_file ) {
			$plugin_meta['help'] = '<a href="https://help.codexpert.io/" target="_blank" class="cx-help">' . __( 'Help', 'image-sizes' ) . '</a>';
		}

		return $plugin_meta;
	}

	public function footer_text( $text ) {
		if( get_current_screen()->parent_base != $this->slug ) return $text;

		/* translators: %1$s is the plugin name, %2$s is the link to leave a review, %3$s is the rating stars */
		return sprintf( __( 'If you like <strong>%1$s</strong>, please <a href="%2$s" target="_blank">leave us a %3$s rating</a> on WordPress.org! It\'d motivate and inspire us to make the plugin even better!', 'image-sizes' ), $this->name, "https://wordpress.org/support/plugin/{$this->slug}/reviews/?filter=5#new-post", '⭐⭐⭐⭐⭐' );
	}

	public function modal() {
		echo '
		<div id="image-sizes-modal" style="display: none">
			<img id="image-sizes-modal-loader" src="' . esc_attr( THUMBPRESS_ASSET . '/img/loader.gif' ) . '" />
		</div>';
	}

	public function popup_for_feedback() {
		$get_reasons 	= get_reasons();
		$user 			= wp_get_current_user();
	    if ( $user->exists() ) {
	        $admin_email 	= $user->user_email;
	        $name 			= $user->display_name; 
	    }
		
		echo '<div id="feedback-modal" class="feedback-modal plugin-unhappy-survey-overlay">
				<div class="feedback-content plugin-unhappy-survey-modal">
					<span class="close-button">&times;</span>
					<form method="post" class="plugin-unhappy-survey-form">
						<input type="hidden" name="action" value="handle_unhappy_survey">
						<input type="hidden" name="plugin_name" value="image-sizes">
						<input type="hidden" name="email" value="'. esc_attr( $admin_email ) .'">
						<input type="hidden" name="full_name" value="'. esc_attr( $name ) .'">
						<div class="plugin-header">
							<h3 class="heading">' . esc_html( sprintf( __( 'We are sorry to hear that our plugin didn\'t fully meet your expectations.', 'thumbpress' ) ) ) . '</h3>
							<p class="heading">' . __('Could you spare a moment to provide your valuable insights on how we can make it better?<br> It means a lot to us.', 'thumbpress') . '</p>
						</div>
						<div class="plugin-dsm-body">
							<div class="plugin-unhappy-reasons">';
								foreach ( $get_reasons as $key => $label ) {
									echo '<div class="plugin-unhappy-reason reason">
										<label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>
										<input type="checkbox" name="ureason[]" value="' . esc_attr( $label ) . '" id="' . esc_attr( $key ) . '">
									</div>';
								}
			echo '</div>
						<div class="plugin-dsm-reason-details">
							<textarea class="plugin-dsm-reason-details-input" name="explanation" rows="5" placeholder="' . esc_html__('Please Explain', 'thumbpress') . '"></textarea>
						</div>
					</div>
					<div class="plugin-dsm-footer footer">
						<div style="display: flex; justify-content: space-between;">
							<button class="button plugin-dsm-btn plugin-dsm-close">' . esc_html__( 'Skip', 'thumbpress' ) . '</button>
							<button class="button button-primary plugin-dsm-btn plugin-dsm-submit" type="submit">' . esc_html__( 'Submit', 'thumbpress' ) . '</button>
						</div>
					</div>
				</form>
			</div>
		</div>';
	}



	public function admin_notices() {

		if ( !defined( 'THUMBPRESS_PRO' ) && current_user_can( 'manage_options' ) ) {
			
			$current_screen = get_current_screen()->base;

			// if ( $current_screen == 'dashboard' || $current_screen == 'toplevel_page_thumbpress' ) {
			// 	if( isset( $_GET['dismiss'] ) && array_key_exists( $_GET['dismiss'], image_sizes_notices_values() ) ) {
			// 		delete_transient( sanitize_text_field( $_GET['dismiss'] ) );
			// 	}
			// 	$image_count = image_sizes_uncompressed_count(); 
			// 	if ($image_count > 100) {
			// 		foreach ( image_sizes_notices_values() as $id => $notice ) {
			// 			$transient = get_transient( $id );
			// 			$current_time = date_i18n('U');
			// 			//$current_time = strtotime( '2024-09-06 12:00:00' );
			// 			if ($transient && $transient[ 'from' ] < $current_time && $current_time < $transient[ 'to' ]) {
			// 				// if( $transient[ 'from' ] < $current_time && $current_time < $transient[ 'to' ] ) {
			// 					$display_text = ( class_exists( 'WooCommerce' ) && isset( $notice['woo_text'] ) ) ? $notice['woo_text']	: $notice['text'];

			// 					printf(
			// 						'<div class="notice notice-info is-dismissible image-sizes-dismissible-notice">
			// 							<p>
			// 								<img src="%5$s" alt="Logo" style="max-height: 25px; margin-right: 10px; vertical-align: middle;" />
			// 								%1$s
			// 								<a class="notice-dismiss" href="%2$s"></a>
			// 							</p>
			// 							<button class="image-sizes-dismissible-notice-button button-primary" data-id="%3$s">%4$s</button>
			// 						</div>',
			// 						wp_kses_post( $display_text ),
			// 						esc_url( add_query_arg('dismiss', $id ) ),
			// 						esc_attr( $id ),
			// 						esc_html( $notice[ 'button' ] ),
			// 						esc_url( THUMBPRESS_ASSET . '/img/icon.png' )
			// 					);
			// 					break;
			// 				// }	
			// 			}
			// 		}
			// 	}
			// }
			if ( $current_screen == 'dashboard' || $current_screen == 'toplevel_page_thumbpress' ) {
				if( isset( $_GET['dismiss'] ) && array_key_exists( $_GET['dismiss'], image_sizes_notices_values() ) ) {
					delete_transient( sanitize_text_field( $_GET['dismiss'] ) );
				}
				foreach ( image_sizes_notices_values() as $id => $notice ) {
					$transient 		= get_transient( $id );
					$current_time 	= date_i18n('U');
					// $current_time = date_i18n( 'Y/m/d H:i:s', strtotime( '2024-11-20 10:00:00' ) );
					if ( $transient && $transient[ 'from' ] < $current_time && $current_time < $transient[ 'to' ] ) {
						printf(
							'<div class="notice notice-info is-dismissible image-sizes-dismissible-notice">
								<p>
									<a class="notice-dismiss" href="%1$s"></a>
								</p>
								<div class="image-sizes-dismissible-notice-content">
									%5$s
									<div class="button-wrapper">
										<a href="%4$s" class="image-sizes-dismissible-notice-button" data-id="%2$s">%3$s</a>
									</div>
								</div>
							</div>',
							esc_url( add_query_arg( 'dismiss', $id ) ),
							esc_attr( $id ),
							esc_html( $notice[ 'button' ] ),
							esc_url( $notice[ 'url' ] ),
							get_image_sizes_countdown_html( $notice[ 'from' ], $notice[ 'countdown_to' ] )
						);
						break;	
					}
				}
			}			
		}
	}

	public function show_new_button( $section ) {
		// Helper::pri( 'Hello' );
	}

	public function thumbpress_modules_activation() {

		if ( ! get_option( 'thumbpress_modules' ) ) {

			$thumbpress_modules = array(
				'disable-thumbnails' => 'on',
				'regenerate-thumbnails' => 'on',
				'social-share' => 'on',
				'convert-images' => 'on',
			);

			add_option( 'thumbpress_modules', $thumbpress_modules );
	    }
	}
}
