<?php
namespace Codexpert\ThumbPress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

class Auto_Featured_Image {

	/**
	 * Post-meta key caching the resolved featured-image id across page views.
	 * Value > 0 is an attachment id; -1 means "computed, no usable image".
	 */
	const CACHE_META_KEY = '_thumbpress_auto_featured_id';

	/**
	 * Request-level memo of resolved featured-image ids, keyed by post id.
	 * Value is an attachment id (int) or false when no usable image exists.
	 */
	private static $resolved_ids = array();

	/**
	 * Request-level memo of the first content-image URL, keyed by post id.
	 * Empty string when the post has no usable <img>.
	 */
	private static $content_images = array();

	public function __construct() {
		add_filter( 'get_post_metadata', array( $this, 'inject_featured_image' ), 10, 4 );
		add_action( 'wp_head', array( $this, 'output_og_image' ) );
		add_action( 'save_post', array( $this, 'clear_cached_featured_id' ) );
	}

	/**
	 * Filter _thumbnail_id meta so themes and SEO plugins see the first
	 * content image as the featured image when no real thumbnail is set.
	 */
	public function inject_featured_image( $value, $post_id, $meta_key, $single ) {
		if ( $meta_key !== '_thumbnail_id' ) {
			return $value;
		}

		if ( ! get_option( 'thumbpress_auto_set_featured_image', 0 ) ) {
			return $value;
		}

		$attachment_id = $this->resolve_featured_id( $post_id );
		if ( ! $attachment_id ) {
			return $value;
		}

		return $single ? (string) $attachment_id : array( (string) $attachment_id );
	}

	/**
	 * Resolve the attachment id to inject for a post with no real thumbnail.
	 * Memoized per request so repeated _thumbnail_id lookups on the same post
	 * (common on archive/shop listings) reuse a single content regex scan and
	 * a single attachment_url_to_postid() query instead of running them again.
	 */
	private function resolve_featured_id( $post_id ) {
		if ( isset( self::$resolved_ids[ $post_id ] ) ) {
			return self::$resolved_ids[ $post_id ];
		}

		if ( $this->has_real_thumbnail( $post_id ) ) {
			return self::$resolved_ids[ $post_id ] = false;
		}

		// Persistent cache across page views, refreshed on save_post. Keeps the
		// content regex + attachment_url_to_postid() query off every request.
		$cached = get_post_meta( $post_id, self::CACHE_META_KEY, true );
		if ( '' !== $cached ) {
			$cached = (int) $cached;
			return self::$resolved_ids[ $post_id ] = $cached > 0 ? $cached : false;
		}

		$image_url     = $this->get_first_content_image( $post_id );
		$attachment_id = $image_url ? (int) attachment_url_to_postid( $image_url ) : 0;

		update_post_meta( $post_id, self::CACHE_META_KEY, $attachment_id > 0 ? $attachment_id : -1 );

		return self::$resolved_ids[ $post_id ] = $attachment_id > 0 ? $attachment_id : false;
	}

	/**
	 * Whether the post already has a real _thumbnail_id. Detaches this filter
	 * around the meta read so the lookup is not re-entrant.
	 */
	private function has_real_thumbnail( $post_id ) {
		remove_filter( 'get_post_metadata', array( $this, 'inject_featured_image' ), 10 );
		$existing = get_post_meta( $post_id, '_thumbnail_id', true );
		add_filter( 'get_post_metadata', array( $this, 'inject_featured_image' ), 10, 4 );

		return ! empty( $existing );
	}

	/**
	 * Invalidate the cached resolution when a post is saved so content edits
	 * (or a newly set real thumbnail) are picked up on the next page view.
	 */
	public function clear_cached_featured_id( $post_id ) {
		delete_post_meta( $post_id, self::CACHE_META_KEY );
	}

	/**
	 * Output og:image meta tag for social sharing when no real thumbnail is set.
	 */
	public function output_og_image() {
		if ( ! is_singular() ) {
			return;
		}

		if ( ! get_option( 'thumbpress_auto_set_featured_image', 0 ) ) {
			return;
		}

		$post_id = get_the_ID();

		if ( $this->has_real_thumbnail( $post_id ) ) {
			return;
		}

		$image_url = $this->get_first_content_image( $post_id );
		if ( ! $image_url ) {
			return;
		}

		echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
	}

	/**
	 * First <img> src in the post content, memoized per request so the regex
	 * scan runs at most once per post even when both the featured-image filter
	 * and the og:image tag need it.
	 */
	private function get_first_content_image( $post_id ) {
		if ( isset( self::$content_images[ $post_id ] ) ) {
			return self::$content_images[ $post_id ];
		}

		$post = get_post( $post_id );
		if ( ! $post || empty( $post->post_content ) ) {
			return self::$content_images[ $post_id ] = '';
		}

		if ( ! preg_match( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $matches ) ) {
			return self::$content_images[ $post_id ] = '';
		}

		return self::$content_images[ $post_id ] = $matches[1];
	}
}
