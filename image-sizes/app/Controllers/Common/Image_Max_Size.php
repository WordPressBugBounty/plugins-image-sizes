<?php
namespace Thumbpress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Traits\Hook;

class Image_Max_Size {

	use Hook;

	public function __construct() {
		$this->filter( 'wp_handle_upload_prefilter', array( $this, 'restrict_image_upload_by_size' ) );
	}

	public function restrict_image_upload_by_size( $file ) {
		$options          = get_option( 'thumbpress_image_max_size', array() );
		$max_image_size   = isset( $options['max-size'] ) ? (int) $options['max-size'] : 0;
		$max_size_unit    = isset( $options['max-size-unit'] ) ? $options['max-size-unit'] : 'KB';
		$max_image_width  = isset( $options['max-width'] ) ? (int) $options['max-width'] : 0;
		$max_image_height = isset( $options['max-height'] ) ? (int) $options['max-height'] : 0;

		$img_info = ! empty( $file['tmp_name'] ) ? getimagesize( $file['tmp_name'] ) : false;

		if ( false === $img_info ) {
			return $file;
		}

		// Check file size limit.
		if ( $max_image_size > 0 ) {
			$multiplier     = ( 'MB' === $max_size_unit ) ? 1024 * 1024 : 1024;
			$max_size_bytes = $max_image_size * $multiplier;

			if ( $file['size'] > $max_size_bytes ) {
				$file['error'] = sprintf(
					/* translators: %1$s: maximum allowed image size, %2$s: size unit (KB or MB) */
					__( '[ ThumbPress Alert ] Image exceeds the maximum allowed size of %1$s %2$s.', 'image-sizes' ),
					$max_image_size,
					$max_size_unit
				);
			}
		}

		// Check dimension limits.
		if ( $max_image_width > 0 || $max_image_height > 0 ) {
			$image_width  = $img_info[0];
			$image_height = $img_info[1];

			if ( ( $max_image_width > 0 && $image_width > $max_image_width ) || ( $max_image_height > 0 && $image_height > $max_image_height ) ) {
				$file['error'] = sprintf(
					/* translators: %1$s: maximum allowed image width in pixels, %2$s: maximum allowed image height in pixels */
					__( '[ ThumbPress Alert ] Image exceeds the maximum allowed resolution of %1$sx%2$s pixels.', 'image-sizes' ),
					$max_image_width ?: '∞',
					$max_image_height ?: '∞'
				);
			}
		}

		return $file;
	}
}
