<?php
namespace Codexpert\ThumbPress\Modules;

use Codexpert\ThumbPress\Helper;
use Codexpert\Plugin\Base;

class Image_Max_Size extends Base {
	public $slug;
	public $version;
	public $id = 'image-max-size';

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->slug		= 'image-sizes';

		$this->filter( 'wp_handle_upload_prefilter', 'restrict_image_upload_by_size' );
	}

	public function __settings ( $settings ) {
		$settings['sections'][ $this->id ] = [
			'id'        => $this->id,
			'label'     => __( 'Image Upload Limit', 'image-sizes' ),
			'icon'      => 'dashicons-format-image',
			'sticky'    => false,
			'fields'    => [
				[
					'id'        => 'max-size',
					'label' 	=> __( 'Max Size', 'image-sizes' ),
					'type'      => 'number',
					'desc'      => __( 'Enter the maximum size in KB', 'image-sizes' ),
					'disabled'  => false,
					'default'   => 40000,
				],
				[
					'id'        => 'max-width',
					'label' 	=> __( 'Max Width', 'image-sizes' ),
					'type'      => 'number',
					'desc'      => __( 'Enter the maximum width in px', 'image-sizes' ),
					'disabled'  => false,
					'default'   => 8000,
				],
				[
					'id'        => 'max-height',
					'label' 	=> __( 'Max Height', 'image-sizes' ),
					'type'      => 'number',
					'desc'      => __( 'Enter the maximum height in px', 'image-sizes' ),
					'disabled'  => false,
					'default'   => 6000,
				],
			]        
		];

		return $settings;
	}

	public function restrict_image_upload_by_size( $file ) {
		$max_image_size 	= Helper::get_option( 'image-max-size', 'max-size', 40000 );
		$max_image_width 	= Helper::get_option( 'image-max-size', 'max-width', 8000 );
		$max_image_height 	= Helper::get_option( 'image-max-size', 'max-height', 6000 );
		$img_info 			= $file[ 'tmp_name' ] ? getimagesize( $file[ 'tmp_name' ] ) : false; // Check if file is an image

		if ( false !== $img_info ) {
			$image_width 	= $img_info[ 0 ];
			$image_height 	= $img_info[ 1 ];

			// Check if image exceeds the max size
			if ( $max_image_size && $file[ 'size' ] > ( $max_image_size * 1024 ) ) {
				// translators: %s: maximum allowed image size in KB
				$file['error'] = sprintf( __( '[ ThumbPress Alert ] Image exceeds the maximum allowed size of %s KB.', 'image-sizes' ), $max_image_size );
			}

			// Check if image exceeds the max resolution
			if ( $max_image_width && $max_image_height ) {
				if ( $image_width > $max_image_width || $image_height > $max_image_height ) {
					// translators: %1$s: maximum allowed image width in pixels, %2$s: maximum allowed image height in pixels
					$file['error'] = sprintf( __( '[ ThumbPress Alert ] Image exceeds the maximum allowed resolution of %1$sx%2$s pixels.', 'image-sizes' ), $max_image_width, $max_image_height );
				}
			}
		}

		return $file;
	}
}
