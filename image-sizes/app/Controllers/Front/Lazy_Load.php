<?php
namespace Thumbpress\Controllers\Front;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Traits\Hook;
use Thumbpress\Traits\Asset;

class Lazy_Load {

	use Hook;
	use Asset;

	public function __construct() {
		if ( is_admin() || ! get_option( 'thumbpress_lazy_load', 0 ) ) {
			return;
		}

		$this->action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		$this->action( 'template_redirect', array( $this, 'start_buffer' ) );
	}

	/**
	 * Enqueue the IntersectionObserver lazy load script.
	 */
	public function enqueue_scripts() {
		$this->enqueue_script(
			'thumbpress-lazy-load',
			THUMBPRESS_ASSETS_URL . 'public/js/lazy-load.js',
			array(),
			null,
			array( 'in_footer' => true )
		);
	}

	/**
	 * Start output buffering to process all HTML.
	 */
	public function start_buffer() {
		ob_start( array( $this, 'process_html' ) );
	}

	/**
	 * Process the full HTML output — convert img src to data-src for ALL images.
	 * This catches hardcoded images, custom field images, theme images, etc.
	 */
	public function process_html( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}

		// Match all img tags.
		$html = preg_replace_callback(
			'/<img\b([^>]*)>/i',
			function ( $match ) {
				$attributes = $match[1];

				// Skip images that already have data-src (already processed).
				if ( strpos( $attributes, 'data-src' ) !== false ) {
					return $match[0];
				}

				// Skip images with no src.
				if ( ! preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $attributes, $src_match ) ) {
					return $match[0];
				}

				$original_src = $src_match[1];

				// Move src to data-src, set src to transparent placeholder.
				$attributes = preg_replace(
					'/\bsrc\s*=\s*["\'][^"\']+["\']/i',
					'src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="' . esc_attr( $original_src ) . '"',
					$attributes
				);

				// Also handle srcset — move to data-srcset.
				if ( preg_match( '/\bsrcset\s*=\s*["\']([^"\']+)["\']/i', $attributes, $srcset_match ) ) {
					$attributes = preg_replace(
						'/\bsrcset\s*=\s*["\'][^"\']+["\']/i',
						'data-srcset="' . esc_attr( $srcset_match[1] ) . '"',
						$attributes
					);
				}

				// Remove any native loading="lazy" since we handle it with JS.
				$attributes = preg_replace( '/\bloading\s*=\s*["\'][^"\']*["\']/i', '', $attributes );

				// Add our lazy class.
				if ( preg_match( '/\bclass\s*=\s*["\']([^"\']*)["\']/', $attributes ) ) {
					$attributes = preg_replace(
						'/\bclass\s*=\s*["\']([^"\']*)["\']/',
						'class="$1 thumbpress-lazy"',
						$attributes
					);
				} else {
					$attributes .= ' class="thumbpress-lazy"';
				}

				return '<img' . $attributes . '>';
			},
			$html
		);

		return $html;
	}
}
