<?php
namespace Codexpert\ThumbPress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Traits\Hook;

class Hotlink_Protection {

	use Hook;

	public function __construct() {
		// Register rewrite rule for /thumbpress-image/ (Apache .htaccess + WordPress internal routing).
		$this->action( 'init', array( $this, 'register_rewrite' ) );

		// The proxy must only serve files while hotlink protection is enabled.
		// Registering handle_image_request unconditionally let any unauthenticated
		// visitor stream uploads through readfile() even with the feature off (#366).
		if ( ! get_option( 'thumbpress_hotlink_protection', 0 ) ) {
			return;
		}

		// Handle proxy URL at init priority 1 — before any WordPress routing decision.
		// Works on both Apache and Nginx by reading REQUEST_URI directly.
		$this->action( 'init', array( $this, 'handle_image_request' ), 1 );

		// URL rewriting via output buffer only works correctly on Apache.
		// Nginx handles image delivery differently — rewriting URLs breaks CDN and media converters.
		if ( $this->is_apache() ) {
			$this->action( 'template_redirect', array( $this, 'start_buffer' ), 0 );
		}
	}

	private function is_apache() {
		$software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? strtolower( $_SERVER['SERVER_SOFTWARE'] ) : '';
		return strpos( $software, 'apache' ) !== false;
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
		// Defense in depth: never serve through the proxy unless the feature is on.
		if ( ! get_option( 'thumbpress_hotlink_protection', 0 ) ) {
			return;
		}

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
		// Anchor to a real directory boundary — a bare prefix match also accepts sibling
		// dirs whose name starts with the uploads dir name (e.g. uploads-backup) (#366).
		if ( ! $real_path || ! $real_base || strpos( $real_path, $real_base . DIRECTORY_SEPARATOR ) !== 0 ) {
			status_header( 404 );
			exit;
		}

		if ( ! file_exists( $real_path ) ) {
			status_header( 404 );
			exit;
		}

		// Hotlink policy: only a same-site Referer may stream a file through the proxy.
		// A missing, empty, or unparseable Referer is treated as a hotlink attempt and
		// blocked — gating the check behind a non-empty Referer let any client defeat the
		// protection just by omitting the header (rel=noreferrer, a no-referrer policy, or
		// a direct request), so the feature gave little real protection (#367).
		$referer      = ! empty( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
		$referer_host = '' !== $referer ? wp_parse_url( $referer, PHP_URL_HOST ) : '';
		$site_host    = wp_parse_url( home_url(), PHP_URL_HOST );

		$referer_host = $referer_host ? preg_replace( '/^www\./i', '', $referer_host ) : '';
		$site_host    = $site_host ? preg_replace( '/^www\./i', '', $site_host ) : '';

		if ( '' === $referer_host || '' === $site_host || strcasecmp( $referer_host, $site_host ) !== 0 ) {
			status_header( 403 );
			header( 'Content-Type: text/plain' );
			echo esc_html__( 'Hotlinking not allowed.', 'image-sizes' );
			exit;
		}

		// Serve the image. Restrict to known image types so the proxy can't be used to
		// exfiltrate .php/.sql/.log/backups that web-server rules would otherwise block
		// on the uploads directory — readfile() bypasses those rules (#366).
		$mime         = wp_check_filetype( $real_path );
		$content_type = ! empty( $mime['type'] ) ? $mime['type'] : '';

		if ( '' === $content_type || ! in_array( $content_type, thumbpress_supported_image_mimes(), true ) ) {
			status_header( 404 );
			exit;
		}

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
	 * Apache only — never runs on Nginx.
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
