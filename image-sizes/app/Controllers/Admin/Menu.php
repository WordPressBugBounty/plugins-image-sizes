<?php
namespace Thumbpress\Controllers\Admin;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Traits\Hook;
use Thumbpress\Traits\Asset;
use Thumbpress\Traits\Menu as Menu_Trait;

class Menu {

	use Hook;
	use Asset;
	use Menu_Trait;

	/**
	 * Constructor to add all hooks.
	 */
	public function __construct() {
		$this->action( 'admin_enqueue_scripts', array( $this, 'add_assets' ) );
		$this->action( 'admin_menu', array( $this, 'register' ) );
	}

	public function add_assets() {
		global $current_screen;

		if ( strpos( $current_screen->base, 'thumbpress' ) !== false ) {

			$this->enqueue_script(
				'image-sizes_main-menu',
				THUMBPRESS_PLUGIN_URL . 'build/admin.bundle.js',
				array( 'wp-element', 'wp-hooks', 'image-sizes_common' )
			);

			// Localize dynamic nav items for the React sidebar.
			$nav_items = $this->get_nav_items();
			$routes    = $this->get_routes();

			wp_localize_script(
				'image-sizes_main-menu',
				'thumbpress_nav',
				array(
					'items'  => $nav_items,
					'routes' => $routes,
				)
			);

			$this->enqueue_style(
				'image-sizes_settings',
				THUMBPRESS_ASSETS_URL . 'admin/css/settings.css'
			);
		}
	}

	public function register() {
		$slug = 'thumbpress';

		$this->add_menu(
			__( 'ThumbPress', 'image-sizes' ),
			__( 'ThumbPress', 'image-sizes' ),
			'manage_options',
			$slug,
			array( $this, 'render_spa' ),
			'dashicons-format-image',
			2
		);

		// All submenus point to the same page slug.
		// React Router handles the actual routing via hash.
		$submenus = array(
			array(
				'label' => __( 'Dashboard', 'image-sizes' ),
				'hash'  => '',
			),
			array(
				'label' => __( 'Thumbnails', 'image-sizes' ),
				'hash'  => '#/thumbnails',
			),
			array(
				'label' => __( 'Unused Images', 'image-sizes' ),
				'hash'  => '#/unused-images',
			),
			array(
				'label' => __( 'Duplicate Images', 'image-sizes' ),
				'hash'  => '#/duplicate-images',
			),
			array(
				'label' => __( 'Large Images', 'image-sizes' ),
				'hash'  => '#/large-images',
			),
			array(
				'label' => __( 'Compress Images', 'image-sizes' ),
				'hash'  => '#/compress-images',
			),
			array(
				'label' => __( 'Convert to WebP', 'image-sizes' ),
				'hash'  => '#/convert-to-webp',
			),
			array(
				'label' => __( 'Convert to AVIF', 'image-sizes' ),
				'hash'  => '#/convert-to-avif',
			),
			array(
				'label' => __( 'Trashed Files', 'image-sizes' ),
				'hash'  => '#/trashed-files',
			),
			array(
				'label' => __( 'Settings', 'image-sizes' ),
				'hash'  => '#/settings',
			),
			array(
				'label' => __( 'Pro', 'image-sizes' ),
				'hash'  => '#/pro',
			),
		);

		/**
		 * Filter the admin submenus for ThumbPress.
		 * Pro plugins can add their own submenu items here.
		 *
		 * Each item: array( 'label' => string, 'hash' => string )
		 *
		 * @param array $submenus The submenu items.
		 */
		$submenus = apply_filters( 'thumbpress_admin_submenus', $submenus );

		foreach ( $submenus as $submenu ) {
			$this->add_submenu(
				$slug,
				$submenu['label'],
				$submenu['label'],
				'manage_options',
				$slug . $submenu['hash'],
				array( $this, 'render_spa' )
			);
		}
	}

	/**
	 * Single SPA container for all pages.
	 */
	public function render_spa() {
		// Empty wrap div so WordPress places admin_notices here, not inside the SPA.
		echo '<div class="wrap"><h2></h2></div>';
		printf(
			'<div id="image-sizes_render">%s</div>',
			__( 'Loading..', 'image-sizes' )
		);
	}

	/**
	 * Get the nav items for the React sidebar.
	 * Extensible via the 'thumbpress_nav_items' filter.
	 *
	 * Each item: array( 'to' => route_path, 'label' => display_label, 'icon' => lucide_icon_name )
	 *
	 * @return array
	 */
	private function get_nav_items() {
		$pro_unlocked = apply_filters( 'thumbpress_is_pro_active', defined( 'THUMBPRESS_PRO_VERSION' ) );

		$items = array(
			array(
				'to'    => '/',
				'label' => __( 'Dashboard', 'image-sizes' ),
				'icon'  => 'DashboardIcon',
			),
			array(
				'to'    => '/thumbnails',
				'label' => __( 'Thumbnails', 'image-sizes' ),
				'icon'  => 'ThumbnailsIcon',
			),
			array(
				'to'    => '/unused-images',
				'label' => __( 'Unused Images', 'image-sizes' ),
				'icon'  => 'UnusedImagesIcon',
				'pro'   => ! $pro_unlocked,
			),
			array(
				'to'    => '/duplicate-images',
				'label' => __( 'Duplicate Images', 'image-sizes' ),
				'icon'  => 'DuplicateImageIcon',
				'pro'   => ! $pro_unlocked,
			),
			array(
				'to'    => '/large-images',
				'label' => __( 'Large Images', 'image-sizes' ),
				'icon'  => 'LargeImageIcon',
				'pro'   => ! $pro_unlocked,
			),
			array(
				'to'    => '/compress-images',
				'label' => __( 'Compress Images', 'image-sizes' ),
				'icon'  => 'CompressImageIcon',
				'pro'   => ! $pro_unlocked,
			),
			array(
				'to'    => '/convert-to-webp',
				'label' => __( 'Convert to WebP', 'image-sizes' ),
				'icon'  => 'ConvertToWebPIcon',
			),
			array(
				'to'    => '/convert-to-avif',
				'label' => __( 'Convert to AVIF', 'image-sizes' ),
				'icon'  => 'ConvertToAvifIcon',
				'pro'   => ! $pro_unlocked,
			),
			array(
				'to'    => '/trashed-files',
				'label' => __( 'Trashed Files', 'image-sizes' ),
				'icon'  => 'TrashIcon',
				'pro'   => ! $pro_unlocked,
			),
			array(
				'to'    => '/settings',
				'label' => __( 'Settings', 'image-sizes' ),
				'icon'  => 'SettingsIcon',
			),
			array(
				'to'    => '/pro',
				'label' => __( 'Pro', 'image-sizes' ),
				'icon'  => 'ProIcon',
			),
		);

		return apply_filters( 'thumbpress_nav_items', $items );
	}

	/**
	 * Get the routes for React Router.
	 * Extensible via the 'thumbpress_routes' filter.
	 *
	 * Each item: array( 'path' => route_path, 'component' => component_name )
	 * Pro plugins can register their own routes here.
	 *
	 * @return array
	 */
	private function get_routes() {
		$routes = array(
			array(
				'path'      => '/',
				'component' => 'Dashboard',
			),
			array(
				'path'      => '/thumbnails',
				'component' => 'RegenerateThumbnails',
			),
			array(
				'path'      => '/unused-images',
				'component' => 'DetectUnusedImages',
			),
			array(
				'path'      => '/duplicate-images',
				'component' => 'DetectDuplicateImages',
			),
			array(
				'path'      => '/large-images',
				'component' => 'DetectLargeImages',
			),
			array(
				'path'      => '/compress-images',
				'component' => 'CompressImages',
			),
			array(
				'path'      => '/convert-to-webp',
				'component' => 'ConvertToWebP',
			),
			array(
				'path'      => '/convert-to-avif',
				'component' => 'ConvertToAvif',
			),
			array(
				'path'      => '/trashed-files',
				'component' => 'TrashFiles',
			),
			array(
				'path'      => '/settings',
				'component' => 'Settings',
			),
			array(
				'path'      => '/pro',
				'component' => 'Pro',
			),
		);

		return apply_filters( 'thumbpress_routes', $routes );
	}
}
