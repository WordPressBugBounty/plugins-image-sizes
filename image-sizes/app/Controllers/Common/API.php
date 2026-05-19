<?php
namespace Thumbpress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use WP_REST_Server;
use Thumbpress\API\Regenerate;
use Thumbpress\API\Thumbnails;
use Thumbpress\API\Convert_Webp;
use Thumbpress\API\Convert_Avif;
use Thumbpress\API\Settings;
use Thumbpress\API\Dashboard;
use Thumbpress\Traits\Hook;
use Thumbpress\Traits\Auth;
use Thumbpress\Traits\Rest;

class API {

	use Hook;
	use Auth;
	use Rest;

	/**
	 * Constructor to add all hooks.
	 */
	public function __construct() {
		$this->action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}

	public function register_endpoints() {

		/**
		 * Thumbnails API - Get all registered image sizes
		 */
		register_rest_route(
			$this->namespace,
			'/thumbnails',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( new Thumbnails(), 'get_all_sizes' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/thumbnails/disabled',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( new Thumbnails(), 'get_disabled_sizes' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/thumbnails/disabled',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( new Thumbnails(), 'save_disabled_sizes' ),
				'args'                => array(
					'sizes' => array(
						'description' => __( 'Array of disabled size names', 'image-sizes' ),
						'required'    => true,
						'type'        => 'array',
					),
				),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		/**
		 * Regenerate Thumbnails APIs
		 */
		register_rest_route(
			$this->namespace,
			'/regenerate/now',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( new Regenerate(), 'regen_now' ),
				'args'                => array(
					'offset'          => array(
						'description' => __( 'Offset for pagination', 'image-sizes' ),
						'type'        => 'integer',
						'default'     => 0,
					),
					'limit'           => array(
						'description' => __( 'Number of images to process', 'image-sizes' ),
						'type'        => 'integer',
						'default'     => 40,
					),
					'thumbs_deleteds' => array(
						'description' => __( 'Previously deleted thumbnails count', 'image-sizes' ),
						'type'        => 'integer',
						'default'     => 0,
					),
					'thumbs_createds' => array(
						'description' => __( 'Previously created thumbnails count', 'image-sizes' ),
						'type'        => 'integer',
						'default'     => 0,
					),
				),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/regenerate/background',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( new Regenerate(), 'regen_background' ),
				'args'                => array(
					'limit' => array(
						'description' => __( 'Batch size for background processing', 'image-sizes' ),
						'type'        => 'integer',
						'default'     => 500,
					),
				),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/regenerate/progress',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( new Regenerate(), 'get_progress' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/regenerate/single',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( new Regenerate(), 'regen_single' ),
				'args'                => array(
					'image_id' => array(
						'description' => __( 'Attachment ID to regenerate', 'image-sizes' ),
						'required'    => true,
						'type'        => 'integer',
					),
				),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/regenerate/cancel',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => function () {
					as_unschedule_all_actions( 'thumbpress_regenerate_all_image' );
					delete_option( 'thumbpress_regenerate_progress' );
					delete_option( 'thumbpress_regenerate_total_processed' );
					delete_option( 'thumbpress_regenerate_total_deleted' );
					delete_option( 'thumbpress_regenerate_total_created' );
					delete_option( 'thumbpress_regenerate_total_image' );
					return rest_ensure_response( array( 'success' => true ) );
				},
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/convert/cancel',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => function () {
					update_option( 'thumbpress_webp_cancelled', true );
					as_unschedule_all_actions( 'thumbpress_convert_all_image' );
					delete_option( 'thumbpress_convert_progress' );
					delete_option( 'thumbpress_convert_total_processd' );
					delete_option( 'thumbpress_convert_total_converted' );
					delete_option( 'thumbpress_convert_space_saved' );
					delete_option( 'thumbpress_convert_total_image' );
					return rest_ensure_response( array( 'success' => true ) );
				},
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		/**
		 * Convert Images APIs
		 */
		register_rest_route(
			$this->namespace,
			'/convert/now',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( new Convert_Webp(), 'convert_now' ),
				'args'                => array(
					'offset' => array(
						'description' => __( 'Offset for pagination', 'image-sizes' ),
						'type'        => 'integer',
						'default'     => 0,
					),
					'limit'  => array(
						'description' => __( 'Number of images to process', 'image-sizes' ),
						'type'        => 'integer',
						'default'     => 10,
					),
				),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/convert/background',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( new Convert_Webp(), 'convert_background' ),
				'args'                => array(
					'limit' => array(
						'description' => __( 'Batch size for background processing', 'image-sizes' ),
						'type'        => 'integer',
						'default'     => 10,
					),
				),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/convert/progress',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( new Convert_Webp(), 'get_progress' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/convert/single',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( new Convert_Webp(), 'convert_single' ),
				'args'                => array(
					'image_id' => array(
						'description' => __( 'Attachment ID to convert', 'image-sizes' ),
						'required'    => true,
						'type'        => 'integer',
					),
				),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		/**
		 * Convert to AVIF APIs
		 */
		register_rest_route(
			$this->namespace,
			'/convert-avif/single',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( new Convert_Avif(), 'convert_single' ),
				'args'                => array(
					'image_id' => array(
						'description' => __( 'Attachment ID to convert', 'image-sizes' ),
						'required'    => true,
						'type'        => 'integer',
					),
				),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		/**
		 * Settings APIs
		 */
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( new Settings(), 'get' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( new Settings(), 'save' ),
				'args'                => array(
					'right_click_disable' => array(
						'type' => 'boolean',
					),
					'lazy_load'           => array(
						'type' => 'boolean',
					),
					'hotlink_protection'  => array(
						'type' => 'boolean',
					),
					'max_size'            => array(
						'type' => 'string',
					),
					'max_size_unit'       => array(
						'type' => 'string',
					),
					'max_width'           => array(
						'type' => 'string',
					),
					'max_height'          => array(
						'type' => 'string',
					),
					'image_editor'        => array(
						'type' => 'boolean',
					),
					'replace_images'      => array(
						'type' => 'boolean',
					),
				),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		/**
		 * Generic Option API
		 */
		register_rest_route(
			$this->namespace,
			'/option',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => function ( $request ) {
					$key = sanitize_text_field( $request->get_param( 'key' ) );
					if ( ! $key ) {
						return rest_ensure_response( array( 'value' => null ) );
					}
					return rest_ensure_response( array( 'value' => get_option( $key, null ) ) );
				},
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/option',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => function ( $request ) {
					$key   = sanitize_text_field( $request->get_param( 'key' ) );
					$value = $request->get_param( 'value' );
					if ( ! $key ) {
						return rest_ensure_response( array( 'success' => false ) );
					}
					update_option( $key, $value );
					return rest_ensure_response( array( 'success' => true ) );
				},
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		/**
		 * Dashboard Stats API
		 */
		register_rest_route(
			$this->namespace,
			'/dashboard/stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( new Dashboard(), 'get_stats' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);

		/**
		 * Debug Info API
		 */
		register_rest_route(
			$this->namespace,
			'/debug/info',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_debug_info' ),
				'permission_callback' => array( $this, 'is_admin' ),
			)
		);
	}

	public function get_debug_info() {
		global $wpdb;

		// MySQL version
		$mysql_version = $wpdb->get_var( 'SELECT VERSION()' );

		// Active plugins with name + version
		$all_plugins    = get_plugins();
		$active_slugs   = get_option( 'active_plugins', array() );
		$active_plugins = array();
		foreach ( $active_slugs as $slug ) {
			if ( isset( $all_plugins[ $slug ] ) ) {
				$p                = $all_plugins[ $slug ];
				$active_plugins[] = array(
					'name'    => $p['Name'],
					'version' => $p['Version'],
					'slug'    => $slug,
				);
			}
		}

		// Active theme
		$theme       = wp_get_theme();
		$parent      = $theme->parent();
		$active_theme = array(
			'name'    => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
		);
		if ( $parent ) {
			$active_theme['parent'] = array(
				'name'    => $parent->get( 'Name' ),
				'version' => $parent->get( 'Version' ),
			);
		}

		// ThumbPress settings
		$prevent      = get_option( 'prevent_image_sizes', array() );
		$disabled     = isset( $prevent['disables'] ) ? $prevent['disables'] : array();
		$max_size_raw = get_option( 'thumbpress_image_max_size', array() );
		$tp_settings  = array(
			'lazy_load'          => (bool) get_option( 'thumbpress_lazy_load', false ),
			'right_click_disable'=> (bool) get_option( 'image_download_disable', false ),
			'hotlink_protection' => (bool) get_option( 'thumbpress_hotlink_protection', false ),
			'webp_on_upload'     => (bool) get_option( 'convert-img-on-upload', false ),
			'avif_on_upload'     => (bool) get_option( 'thumbpress_avif_convert_on_upload', false ),
			'disabled_sizes'     => $disabled,
			'max_file_size'      => isset( $max_size_raw['max-size'] ) && $max_size_raw['max-size'] !== '' ? $max_size_raw['max-size'] . ' ' . ( $max_size_raw['max-size-unit'] ?? 'KB' ) : 'Not set',
			'max_dimensions'     => ( isset( $max_size_raw['max-width'] ) && $max_size_raw['max-width'] ) ? $max_size_raw['max-width'] . 'x' . ( $max_size_raw['max-height'] ?? '0' ) . 'px' : 'Not set',
			'thumbpress_version' => defined( 'THUMBPRESS_VERSION' ) ? THUMBPRESS_VERSION : get_plugin_data( WP_PLUGIN_DIR . '/image-sizes/image-sizes.php' )['Version'] ?? 'Unknown',
			'pro_active'         => defined( 'THUMBPRESS_PRO_VERSION' ),
			'pro_version'        => defined( 'THUMBPRESS_PRO_VERSION' ) ? THUMBPRESS_PRO_VERSION : null,
			'license_status'     => apply_filters( 'thumbpress_debug_license_status', null ),
			'license_key_set'    => apply_filters( 'thumbpress_debug_license_key_set', false ),
		);

		$total_images = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'attachment'
			 AND post_mime_type LIKE 'image/%'
			 AND post_status = 'inherit'"
		);

		$data = array(
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version'       => PHP_VERSION,
			'mysql_version'     => $mysql_version,
			'server_software'   => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) : 'Unknown',
			'wp_debug'          => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'wp_debug_display'  => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY,
			'wp_debug_log'      => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
			'wp_memory_limit'   => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : ini_get( 'memory_limit' ),
			'php_memory_limit'  => ini_get( 'memory_limit' ),
			'php_max_execution' => ini_get( 'max_execution_time' ),
			'php_upload_max'    => ini_get( 'upload_max_filesize' ),
			'total_images'      => $total_images,
			'active_plugins'    => $active_plugins,
			'active_theme'      => $active_theme,
			'thumbpress'        => $tp_settings,
		);

		return rest_ensure_response( array( 'success' => true, 'data' => $data ) );
	}
}
