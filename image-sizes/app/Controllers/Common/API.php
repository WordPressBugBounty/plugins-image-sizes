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
	}
}
