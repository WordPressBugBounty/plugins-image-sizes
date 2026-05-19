<?php
namespace Thumbpress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

class Auto_Featured_Image {

	public function __construct() {
		add_filter( 'get_post_metadata', array( $this, 'inject_featured_image' ), 10, 4 );
		add_action( 'wp_head', array( $this, 'output_og_image' ) );
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

		remove_filter( 'get_post_metadata', array( $this, 'inject_featured_image' ), 10 );
		$existing = get_post_meta( $post_id, '_thumbnail_id', true );
		add_filter( 'get_post_metadata', array( $this, 'inject_featured_image' ), 10, 4 );

		if ( $existing ) {
			return $value;
		}

		$image_url = $this->get_first_content_image( $post_id );
		if ( ! $image_url ) {
			return $value;
		}

		$attachment_id = attachment_url_to_postid( $image_url );
		if ( ! $attachment_id ) {
			return $value;
		}

		return $single ? (string) $attachment_id : array( (string) $attachment_id );
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

		remove_filter( 'get_post_metadata', array( $this, 'inject_featured_image' ), 10 );
		$existing = get_post_meta( $post_id, '_thumbnail_id', true );
		add_filter( 'get_post_metadata', array( $this, 'inject_featured_image' ), 10, 4 );

		if ( $existing ) {
			return;
		}

		$image_url = $this->get_first_content_image( $post_id );
		if ( ! $image_url ) {
			return;
		}

		echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
	}

	private function get_first_content_image( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || empty( $post->post_content ) ) {
			return '';
		}

		if ( ! preg_match( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $matches ) ) {
			return '';
		}

		return $matches[1];
	}
}
