<?php
namespace Thumbpress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Traits\Hook;

class Hotlink_Protection {

	use Hook;

	public function __construct() {
		// Register rewrite rule for /thumbpress-image/ (Apache .htaccess + WordPress internal routing).
		$this->action( 'init', array( $this, 'register_rewrite' ) );

		// Handle proxy URL at init priority 1 — before any WordPress routing decision.
		// Works on both Apache and Nginx by reading REQUEST_URI directly.
		$this->action( 'init', array( $this, 'handle_image_request' ), 1 );

		if ( ! get_option( 'thumbpress_hotlink_protection', 0 ) ) {
			return;
		}

		$this->action( 'template_redirect', array( $this, 'start_buffer' ), 0 );
	}

	/**
	 * Register WordPress rewrite rule for /thumbpress-image/ proxy URL.
	 * On Apache: written to .htaccess by flush_rewrite_rules().
	 * On Nginx: /thumbpress-image/ path doesn't exist as a file, so try_files falls through to index.php.
	 */
	public function register_rewrite() {
		add_rewrite_tag( '%thumbpress_image%', '(.+)' );
		add_rewrite_rule( '^thumbpress-image/(.+)$', 'index.php?thumbpress_image=$1', 'top' );
	}

	/**
	 * Handle proxied image requests — check referer and serve or block.
	 * Hooked at init priority 1: fires before any WordPress routing decision.
	 * Works on Apache and Nginx — reads REQUEST_URI directly, no stored rewrite rule needed.
	 */
	public function handle_image_request() {
		$image_path = '';

		// Check /thumbpress-image/path URL format (works on both Apache + Nginx).
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$uri    = strtok( wp_unslash( $_SERVER['REQUEST_URI'] ), '?' );
			$prefix = '/thumbpress-image/';
			if ( 0 === strpos( $uri, $prefix ) ) {
				$image_path = substr( $uri, strlen( $prefix ) );
			}
		}

		// Legacy: ?thumbpress_image=path query string format.
		if ( empty( $image_path ) && ! empty( $_GET['thumbpress_image'] ) ) {
			$image_path = $_GET['thumbpress_image'];
		}

		if ( empty( $image_path ) ) {
			return;
		}

		$image_path = sanitize_text_field( wp_unslash( $image_path ) );
		$upload_dir = wp_get_upload_dir();
		$file_path  = trailingslashit( $upload_dir['basedir'] ) . $image_path;

		// Security: ensure the resolved path is within uploads.
		$real_path = realpath( $file_path );
		$real_base = realpath( $upload_dir['basedir'] );
		if ( ! $real_path || ! $real_base || strpos( $real_path, $real_base ) !== 0 ) {
			status_header( 404 );
			exit;
		}

		if ( ! file_exists( $real_path ) ) {
			status_header( 404 );
			exit;
		}

		// Only check referer when protection is enabled.
		if ( get_option( 'thumbpress_hotlink_protection', 0 ) && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$referer_host = wp_parse_url( wp_unslash( $_SERVER['HTTP_REFERER'] ), PHP_URL_HOST );
			$site_host    = wp_parse_url( home_url(), PHP_URL_HOST );

			if ( $referer_host && $site_host ) {
				$referer_host = preg_replace( '/^www\./i', '', $referer_host );
				$site_host    = preg_replace( '/^www\./i', '', $site_host );

				if ( strcasecmp( $referer_host, $site_host ) !== 0 ) {
					status_header( 403 );
					header( 'Content-Type: text/plain' );
					echo esc_html__( 'Hotlinking not allowed.', 'image-sizes' );
					exit;
				}
			}
		}

		// Serve the image.
		$mime         = wp_check_filetype( $real_path );
		$content_type = ! empty( $mime['type'] ) ? $mime['type'] : 'application/octet-stream';

		header( 'Content-Type: ' . $content_type );
		header( 'Content-Length: ' . filesize( $real_path ) );
		header( 'Cache-Control: public, max-age=31536000' );
		readfile( $real_path );
		exit;
	}

	/**
	 * Start output buffering to rewrite image URLs.
	 * Only runs on the frontend — admin pages never need URL rewriting.
	 */
	public function start_buffer() {
		if ( is_admin() ) {
			return;
		}
		ob_start( array( $this, 'rewrite_image_urls' ) );
	}

	/**
	 * Rewrite upload image URLs to go through the proxy endpoint.
	 * Uses /thumbpress-image/ pretty URL — works on both Apache and Nginx.
	 */
	public function rewrite_image_urls( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}

		$upload_dir = wp_get_upload_dir();
		$upload_url = $upload_dir['baseurl'];
		$proxy_base = home_url( '/thumbpress-image/' );

		// Also handle alternate http/https scheme variant to catch mixed-scheme HTML.
		$upload_url_alt = preg_replace( '/^https?/', 'http' === wp_parse_url( $upload_url, PHP_URL_SCHEME ) ? 'https' : 'http', $upload_url );

		$html = str_replace( $upload_url . '/', $proxy_base, $html );
		$html = str_replace( $upload_url, $proxy_base, $html );

		if ( $upload_url_alt !== $upload_url ) {
			$html = str_replace( $upload_url_alt . '/', $proxy_base, $html );
			$html = str_replace( $upload_url_alt, $proxy_base, $html );
		}

		return $html;
	}
}
