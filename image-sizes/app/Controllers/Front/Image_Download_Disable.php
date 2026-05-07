<?php
namespace Thumbpress\Controllers\Front;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Traits\Hook;
use Thumbpress\Traits\Asset;

class Image_Download_Disable {

	use Hook;
	use Asset;

	public function __construct() {
		$this->action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		if ( ! get_option( 'thumbpress_image_download_disable', 0 ) ) {
			return;
		}

		$this->enqueue_script(
			'thumbpress-right-click-disable',
			THUMBPRESS_ASSETS_URL . 'public/js/disable-image.js',
			array( 'jquery' )
		);
	}
}
