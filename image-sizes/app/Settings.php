<?php
namespace Codexpert\ThumbPress\App;

use Codexpert\ThumbPress\Helper;
use Codexpert\Plugin\Base;
use Codexpert\Plugin\Settings as Settings_API;

/**
 * @package Plugin
 * @subpackage Settings
 * @author Codexpert <hi@codexpert.io>
 */
class Settings extends Base {

	public $plugin;
	public $slug;
	public $name;
	public $version;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->version	= $this->plugin['Version'];
	}
	
	public function init_menu() {
		
		$site_config = [    
			'PHP Version'				=> PHP_VERSION,
			'WordPress Version' 		=> get_bloginfo( 'version' ),
			'Memory Limit'				=> defined( 'WP_MEMORY_LIMIT' ) && WP_MEMORY_LIMIT ? WP_MEMORY_LIMIT : 'Not Defined',
			'Server'					=> $this->sanitize( $_SERVER['SERVER_SOFTWARE'] ),
			'Debug Mode'				=> defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled',
			'Active Plugins'			=> get_option( 'active_plugins' ),
		];

		$settings = [
			'id'            => 'thumbpress',
			'label'         => __( 'ThumbPress', 'image-sizes' ),
			'title'         => sprintf( '%1$s v%2$s', __( 'ThumbPress', 'image-sizes' ), $this->version ),
			'header'        => __( 'ThumbPress', 'image-sizes' ),
			'icon'			=> 'dashicons-format-image',
			'position'		=> 12,
			'sections'      => [
				'thumbpress_modules'	=> [
					'id'        => 'thumbpress_modules',
					'label'     => __( 'Modules', 'image-sizes' ),
					'icon'      => 'dashicons-image-filter',
					'sticky'	=> false,
					'page_load'	=> true,
					'fields'	=> array_map( function( $_module ) {
						$module = [
							'id'	=> $_module['id'],
							'label'	=> $_module['title'],
							'desc'	=> $_module['desc'],
							'type'	=> 'switch'
						];

						return $module;
					}, thumbpress_modules() ),
					'template'  => THUMBPRESS_DIR . '/views/settings/modules.php',
				],           
			],
		];

		new Settings_API( apply_filters( 'thumbpress-modules_settings_args', $settings ) );

		/**
		 * Modules menu
		 */
		// $modules_settings = [
		// 	'id'            => "thumbpress-modules",
		// 	'parent'        => 'thumbpress',
		// 	'label'         => __( 'Modules', 'image-sizes' ),
		// 	'title'         => __( 'Modules', 'image-sizes' ),
		// 	'header'        => __( 'Modules', 'image-sizes' ),
		// 	'icon'      	=> 'dashicons-image-filter',
		// 	'sections'      => [
		// 		'thumbpress_modules'	=> [
		// 			'id'        => 'thumbpress_modules',
		// 			'label'     => __( 'Modules', 'image-sizes' ),
		// 			'icon'      => 'dashicons-image-filter',
		// 			'sticky'	=> false,
		// 			'page_load'	=> true,
		// 			'fields'	=> array_map( function( $_module ) {
		// 				$module = [
		// 					'id'	=> $_module['id'],
		// 					'label'	=> $_module['title'],
		// 					'desc'	=> $_module['desc'],
		// 					'type'	=> 'switch'
		// 				];

		// 				return $module;
		// 			}, thumbpress_modules() ),
		// 			'template'  => THUMBPRESS_DIR . '/views/settings/dashboad.php',
		// 		],           
		// 	],
		// ];

		// new Settings_API( apply_filters( 'thumbpress-modules_settings_args', $modules_settings ) );

		/**********************/

		// if( ! defined( 'THUMBPRESS_PRO' ) ) {

		// 	$upgrade_pro = [
		// 		'id'            => "upgrade-to-pro",
		// 		'parent'        => 'thumbpress',
		// 		'label'         => __( '<b>Get Pro <span style="color: #f77474;">(On Sale)</span></b> ', 'image-sizes' ),
		// 		'title'         => __( 'Get Pro (On Sale)', 'image-sizes' ),
		// 		'header'        => __( 'Get Pro (On Sale)', 'image-sizes' ),
		// 		'priority'      => 100,
		// 		'sections'      => [
		// 			'upgrade-to-pro'=> [
		// 				'id'        => 'upgrade-to-pro',
		// 				'label'     => __( 'Tools', 'image-sizes' ),
		// 				'icon'      => 'dashicons-hammer',
		// 				'no_heading'=> true,
		// 				'hide_form' => true,
		// 				'template'  => THUMBPRESS_DIR . '/views/settings/upgrade-pro.php',
		// 			],           
		// 		],
		// 	];

		// 	new Settings_API( apply_filters( 'submenu_thumbpress_pro', $upgrade_pro ) );
		// }

		if( ! defined( 'THUMBPRESS_PRO' ) ) {

		    $upgrade_pro = [
		        'id'            => "upgrade-to-pro",
		        'parent'        => 'thumbpress',
		        'label'         => __( 'Upgrade to Pro (Up to 60% Off)', 'image-sizes' ), // Remove HTML tags
		        'title'         => __( 'Upgrade to Pro (Up to 60% Off)', 'image-sizes' ),
		        'header'        => __( 'Upgrade to Pro (Up to 60% Off)', 'image-sizes' ),
		        'priority'      => 100,
		        'sections'      => [
		            'upgrade-to-pro'=> [
		                'id'        => 'upgrade-to-pro',
		                'label'     => __( 'Tools', 'image-sizes' ),
		                'icon'      => 'dashicons-hammer',
		                'no_heading'=> true,
		                'hide_form' => true,
		                'template'  => THUMBPRESS_DIR . '/views/settings/upgrade-pro.php',
		            ],           
		        ],
		    ];

		    // Add HTML tags when rendering, not in the translation function
		    $upgrade_pro['label'] = '<span style="font-weight: bold;">Upgrade to Pro (Up to 60% Off)</span></span>';

		    new Settings_API( apply_filters( 'submenu_thumbpress_pro', $upgrade_pro ) );
		}




	}

	public function admin_menu() {
		add_submenu_page(
			'thumbpress',
			__( 'Modules', 'image-sizes' ),
			__( 'Modules', 'image-sizes' ),
			'manage_options',
			'thumbpress',
			function() {}
		);
	}

	public function reset( $option_name ) {
		if( $option_name == 'prevent_image_sizes' ) {
			update_option( '_image-sizes', Helper::default_image_sizes() );
		}
	}

	public function redirect_specific_admin_page() {
		global $pagenow;
		if ( $pagenow == 'admin.php' && isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'upgrade-to-pro' ) {
			wp_redirect( 'https://thumbpress.co/pricing/?utm_source=in+plugin&utm_medium=menu+bar&utm_campaign=8dec24' );
			exit;
		}
	}
}
