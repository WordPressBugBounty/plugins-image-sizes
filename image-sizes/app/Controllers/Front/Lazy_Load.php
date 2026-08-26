<?php
namespace Codexpert\ThumbPress\Controllers\Front;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Traits\Hook;

class Lazy_Load {

	use Hook;

	/**
	 * Fallback for core's omission threshold, matching its default of 3.
	 */
	const SKIP_IMAGES = 3;

	/**
	 * Images narrower or shorter than this are chrome, not LCP candidates.
	 */
	const CHROME_MAX_SIZE = 100;

	/**
	 * Opt-out markers agreed between lazy-load plugins, plus other loaders' own attributes.
	 */
	const EXCLUSIONS = array(
		'skip-lazy',           // Cross-plugin convention, also matches data-skip-lazy.
		'no-lazy',             // WP Rocket and Divi, also matches data-no-lazy.
		'lazyload',            // lazysizes, Smush, WP Rocket classes and data-lazyload(ed).
		'data-src',            // lazysizes, Smush, a3, EWWW, also matches data-srcset.
		'data-lazy-src',       // WP Rocket and Jetpack, also matches data-lazy-srcset.
		'data-lazysrc',
		'data-lazy-original',
		'data-litespeed-src',  // LiteSpeed with delayed JS.
		'jetpack-lazy-image',
		'data-bg',             // WP Rocket background images.
		'data-opt-src',        // Optimole.
	);

	public function __construct() {
		$this->action( 'update_option_thumbpress_lazy_load', array( $this, 'purge_page_caches' ) );

		if ( is_admin() || ! get_option( 'thumbpress_lazy_load', 0 ) ) {
			return;
		}

		$this->action( 'template_redirect', array( $this, 'start_buffer' ) );
	}

	/**
	 * Purge page caches on toggle, else the user saves the setting and nothing changes.
	 */
	public function purge_page_caches() {
		$callbacks = array(
			'rocket_clean_domain',           // WP Rocket.
			'w3tc_flush_all',                // W3 Total Cache.
			'wp_cache_clear_cache',          // WP Super Cache.
			'sg_cachepress_purge_cache',     // SiteGround Optimizer.
			'wpo_cache_flush',               // WP-Optimize.
			'swift_performance_clear_all_cache',
			'wp_fastest_cache_clear_all',    // WP Fastest Cache helper, when present.
		);

		foreach ( $callbacks as $callback ) {
			if ( function_exists( $callback ) ) {
				call_user_func( $callback );
			}
		}

		if ( class_exists( 'WpFastestCache' ) && method_exists( 'WpFastestCache', 'deleteCache' ) ) {
			( new \WpFastestCache() )->deleteCache( true );
		}

		if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
			\autoptimizeCache::clearall();
		}

		$actions = array(
			'wpfc_clear_all_cache',              // WP Fastest Cache.
			'litespeed_purge_all',               // LiteSpeed Cache.
			'cache_enabler_clear_complete_cache', // Cache Enabler.
			'breeze_clear_all_cache',            // Breeze.
			'wphb_clear_page_cache',             // Hummingbird.
			'nginx_helper_purge_all',            // Nginx Helper.
			'kinsta_cache_purge_all',            // Kinsta.
			'wpe_cache_purge_all',               // WP Engine.
		);

		foreach ( $actions as $action ) {
			do_action( $action, true );
		}

		do_action( 'thumbpress_lazy_load_toggled' );
	}

	/**
	 * Start output buffering to process all HTML.
	 */
	public function start_buffer() {
		if ( ! $this->should_process() ) {
			return;
		}

		ob_start( array( $this, 'process_html' ) );
	}

	/**
	 * Whether the current request should be buffered and rewritten.
	 *
	 * @return bool
	 */
	public function should_process() {
		$skip = is_admin()
			|| is_feed()
			|| is_embed()
			|| is_preview()
			|| is_customize_preview()
			|| wp_doing_ajax()
			|| wp_is_json_request()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( defined( 'DOING_CRON' ) && DOING_CRON )
			|| ( function_exists( 'amp_is_request' ) && amp_is_request() );

		return ! apply_filters( 'thumbpress_lazy_load_skip_request', $skip );
	}

	/**
	 * Add native loading="lazy" to every img the page renders outside the WP APIs.
	 * This catches hardcoded images, custom field images, theme images, etc.
	 *
	 * @param string $html Buffered page HTML.
	 * @return string
	 */
	public function process_html( $html ) {
		if ( empty( $html ) || false === stripos( $html, '<img' ) ) {
			return $html;
		}

		// Defer to core's own LCP judgement rather than second-guessing it.
		$default = function_exists( 'wp_omit_loading_attr_threshold' ) ? wp_omit_loading_attr_threshold() : self::SKIP_IMAGES;
		$skip    = (int) apply_filters( 'thumbpress_lazy_load_skip_images', $default );
		$seen    = 0;

		// Another loader's <noscript> copies would spend the budget meant for real images.
		$stash = array();

		$html = preg_replace_callback(
			'/<noscript\b[^>]*>.*?<\/noscript>/is',
			function ( $match ) use ( &$stash ) {
				$stash[] = $match[0];

				return '<!--thumbpress-noscript-' . ( count( $stash ) - 1 ) . '-->';
			},
			$html
		);

		$html = preg_replace_callback(
			'/<img\b([^>]*)>/i',
			function ( $match ) use ( $skip, &$seen ) {
				// A logo or avatar must not spend the budget meant for the LCP image.
				if ( $this->is_lcp_candidate( $match[1] ) ) {
					++$seen;

					if ( $seen <= $skip ) {
						return $match[0];
					}
				}

				return $this->add_loading_attr( $match[0], $match[1] );
			},
			$html
		);

		if ( empty( $stash ) ) {
			return $html;
		}

		return preg_replace_callback(
			'/<!--thumbpress-noscript-(\d+)-->/',
			function ( $match ) use ( $stash ) {
				return isset( $stash[ $match[1] ] ) ? $stash[ $match[1] ] : $match[0];
			},
			$html
		);
	}

	/**
	 * Whether the author opted this tag out, or another lazy loader already claimed it.
	 *
	 * @param string $attributes Attribute string of the tag.
	 * @return bool
	 */
	private function is_excluded( $attributes ) {
		foreach ( apply_filters( 'thumbpress_lazy_load_exclusions', self::EXCLUSIONS ) as $marker ) {
			if ( false !== stripos( $attributes, $marker ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a tag is big enough to be the LCP image, so it can spend the skip budget.
	 *
	 * @param string $attributes Attribute string of the tag.
	 * @return bool
	 */
	private function is_lcp_candidate( $attributes ) {
		if ( preg_match( '/\bsrc\s*=\s*["\']\s*data:/i', $attributes ) ) {
			return false;
		}

		$max = (int) apply_filters( 'thumbpress_lazy_load_chrome_max_size', self::CHROME_MAX_SIZE );

		if ( preg_match_all( '/\b(?:width|height)\s*=\s*["\']?(\d+)/i', $attributes, $sizes ) ) {
			foreach ( $sizes[1] as $size ) {
				if ( (int) $size < $max ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Add loading="lazy" to one img tag unless it opts out or already declares one.
	 *
	 * @param string $tag        Full img tag.
	 * @param string $attributes Attribute string of the tag.
	 * @return string
	 */
	private function add_loading_attr( $tag, $attributes ) {
		if ( false === stripos( $attributes, 'src' ) ) {
			return $tag;
		}

		// fetchpriority marks the LCP candidate; loading means the decision is already made.
		if ( preg_match( '/\b(loading|fetchpriority)\s*=/i', $attributes ) ) {
			return $tag;
		}

		if ( $this->is_excluded( $attributes ) ) {
			return $tag;
		}

		$attributes  = rtrim( $attributes );
		$self_closer = '';

		if ( '/' === substr( $attributes, -1 ) ) {
			$attributes  = rtrim( substr( $attributes, 0, -1 ) );
			$self_closer = ' /';
		}

		return '<img' . $attributes . ' loading="lazy"' . $self_closer . '>';
	}
}
