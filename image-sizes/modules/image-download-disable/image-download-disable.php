<?php
namespace Codexpert\ThumbPress\Modules;

use Codexpert\ThumbPress\Helper;
use Codexpert\Plugin\Base;

class Image_Download_Disable extends Base {
	public $slug;
	public $version;
	public $id = 'image_download_disable';

	/**
	 * Constructor
	 */
	public function __construct() {
		
		$this->slug		= 'image-sizes';
		$this->version	= '5.8.7';

		$this->action( 'wp_enqueue_scripts', 'enqueue_scripts_all' );
	}

	public function enqueue_scripts_all() {
		if ( ! get_option( 'image_download_disable', true ) == 'on') return;
		wp_enqueue_script( "right-click-disable-image-js", plugins_url( 'js/disable-image.js', __FILE__), [ 'jquery' ], $this->version, true );
	}
}