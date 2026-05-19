<?php
namespace Thumbpress\API;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Traits\Rest;
use Thumbpress\Traits\Cache;

class Thumbnails {

	use Rest;
	use Cache;

	/**
	 * Get all registered WordPress image sizes.
	 *
	 * @return WP_REST_Response
	 */
	public function get_all_sizes() {
		$cache_key = 'all_sizes';
		$cached    = $this->get_cache( $cache_key );

		if ( false !== $cached ) {
			return $this->response_success( $cached );
		}

		$all_sizes = $this->compute_all_sizes();
		$this->set_cache( $cache_key, $all_sizes, 86400 );

		return $this->response_success( $all_sizes );
	}

	private function compute_all_sizes() {
		$sizes     = get_intermediate_image_sizes();
		$all_sizes = array();

		global $wpdb;
		$option_keys = array();
		foreach ( $sizes as $size ) {
			$option_keys[] = "{$size}_size_w";
			$option_keys[] = "{$size}_size_h";
			$option_keys[] = "{$size}_crop";
		}

		$placeholders = implode( ',', array_fill( 0, count( $option_keys ), '%s' ) );
		$options = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options}
				 WHERE option_name IN ({$placeholders})",
				...$option_keys
			)
		);

		$option_map = array();
		foreach ( $options as $opt ) {
			$option_map[ $opt->option_name ] = $opt->option_value;
		}

		foreach ( $sizes as $size ) {
			$width  = isset( $option_map[ "{$size}_size_w" ] ) ? $option_map[ "{$size}_size_w" ] : 0;
			$height = isset( $option_map[ "{$size}_size_h" ] ) ? $option_map[ "{$size}_size_h" ] : 0;
			$crop   = isset( $option_map[ "{$size}_crop" ] ) ? $option_map[ "{$size}_crop" ] : '';

			$all_sizes[ $size ] = array(
				'name'   => $size,
				'width'  => $width ? absint( $width ) : 0,
				'height' => $height ? absint( $height ) : 0,
				'crop'   => is_array( @unserialize( $crop ) ) && ! empty( @unserialize( $crop ) ) ? true : false,
			);
		}

		// Add or update custom sizes from theme/plugins
		global $_wp_additional_image_sizes;
		if ( ! empty( $_wp_additional_image_sizes ) ) {
			foreach ( $_wp_additional_image_sizes as $size => $data ) {
				$all_sizes[ $size ] = array(
					'name'   => $size,
					'width'  => absint( $data['width'] ),
					'height' => absint( $data['height'] ),
					'crop'   => ! empty( $data['crop'] ),
				);
			}
		}

		// Always include thumbnail
		if ( ! isset( $all_sizes['thumbnail'] ) ) {
			$all_sizes['thumbnail'] = array(
				'name'   => 'thumbnail',
				'width'  => absint( get_option( 'thumbnail_size_w', 150 ) ),
				'height' => absint( get_option( 'thumbnail_size_h', 150 ) ),
				'crop'   => (bool) get_option( 'thumbnail_crop', 0 ),
			);
		}

		// Add scaled size (if big_image_size_threshold is set)
		$threshold = get_option( 'big_image_size_threshold', 2560 );
		if ( ! empty( $threshold ) ) {
			$all_sizes['scaled'] = array(
				'name'   => 'scaled',
				'width'  => absint( $threshold ),
				'height' => absint( $threshold ),
				'crop'   => false,
			);
		}

		// Add full size (original image)
		$all_sizes['full'] = array(
			'name'   => 'Original Image',
			'width'  => 0,
			'height' => 0,
			'crop'   => false,
		);

		return $all_sizes;
	}

	/**
	 * Get disabled thumbnail sizes.
	 *
	 * @return WP_REST_Response
	 */
	public function get_disabled_sizes() {
		$cache_key = 'disabled_sizes';
		$cached    = $this->get_cache( $cache_key );

		if ( false !== $cached ) {
			return $this->response_success( $cached );
		}

		$disabled = get_option( 'prevent_image_sizes', array() );
		if ( ! is_array( $disabled ) ) {
			$disabled = array();
		}
		$disables = isset( $disabled['disables'] ) ? $disabled['disables'] : array();
		$this->set_cache( $cache_key, $disables, 86400 );

		return $this->response_success( $disables );
	}

	/**
	 * Save disabled thumbnail sizes.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function save_disabled_sizes( $request ) {
		$sizes = $request->get_param( 'sizes' );

		if ( ! is_array( $sizes ) ) {
			return $this->response_error( __( 'Invalid data format. Array expected.', 'image-sizes' ) );
		}

		$updated = update_option( 'prevent_image_sizes', array( 'disables' => $sizes ) );

		if ( $updated ) {
			$this->delete_cache( 'all_sizes' );
			$this->delete_cache( 'disabled_sizes' );
			$this->delete_cache( 'stat_sizes_data' );
			do_action( 'thumbpress_thumbnail_sizes_saved' );
			return $this->response_success( __( 'Disabled sizes saved successfully.', 'image-sizes' ) );
		}

		return $this->response_success( __( 'No changes detected.', 'image-sizes' ) );
	}
}
