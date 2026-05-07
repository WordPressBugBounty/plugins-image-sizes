<?php
use WebPConvert\WebPConvert;

if( ! function_exists( 'thumbpress_convert_image_to_webp' ) ) :
	function thumbpress_convert_image_to_webp( $source ) {
		$file_info = pathinfo( $source );
		$extension = strtolower( $file_info['extension'] );
	
		if ( $extension === 'webp' ) return false;
	
		$base_dir  = $file_info['dirname'];
		$base_name = $file_info['filename'];
		$webp_path = $base_dir . '/' . $base_name . '.webp';
	
		// Increment if file already exists
		$counter = 1;
		while ( file_exists( $webp_path ) ) {
			$webp_path = $base_dir . '/' . $base_name . '-' . $counter . '.webp';
			$counter++;
		}
	
		$options = [];
		WebPConvert::convert( $source, $webp_path, $options );
	
		return $webp_path;
	}
endif;
	

if( ! function_exists( 'thumbpress_generate_webp_file_url' ) ) :
	function thumbpress_generate_webp_file_url( $webp_file_path ) {
		// Assuming WebP file has the same directory structure and name but with a different extension
		$webp_file_path = pathinfo( $webp_file_path, PATHINFO_DIRNAME ) . '/' . pathinfo( $webp_file_path, PATHINFO_FILENAME ) . '.webp';

		// Replace the base directory path with the base URL
		$webp_file_url = str_replace( ABSPATH, home_url( '/' ), $webp_file_path );

		return $webp_file_url;
	}
endif;

/**
 * Get images by image types
 *  
 * @param array $types array of image types
 * @return array $image_groups array of image groups by image types
 */
// if( ! function_exists( 'thumbpress_get_images_by_types' ) ) :
// 	function thumbpress_get_images_by_types( $types = [], $sort_by_group = true ) {
// 		$args = array(
// 			'post_type'     => 'attachment',
// 			'numberposts'   => -1,
// 		);
	
// 		$images         = [];
// 		$image_types    = $types ? $types : [ 'image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/ico', 'image/svg+xml', 'image/bmp', 'image/webp' ];
// 		$attachments    = get_posts( $args );
	
// 		foreach ( $attachments as $key => $attachment ) {
// 			$image_type = get_post_mime_type( $attachment->ID );
	
// 			if ( in_array( $image_type, $image_types ) ) {
// 				if ( $sort_by_group ) {
// 					$images[$image_type][] = $attachment;
// 				}
// 				else {
// 					$images[] = $attachment;
// 				}
// 			}
// 		}
	
// 		return $images;
// 	}
// 	endif;

if( ! function_exists( 'thumbpress_get_images_by_types' ) ) :
	function thumbpress_get_images_by_types( $types = [], $sort_by_group = true ) {
		global $wpdb;
		$image_types = $types ? $types : [ 'image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/ico', 'image/svg+xml', 'image/bmp', 'image/webp' ];

		$query = "SELECT ID, post_mime_type 
		FROM {$wpdb->posts} 
		WHERE post_type = 'attachment' 
		AND post_mime_type IN ('" . implode("','", array_map( 'esc_sql', $image_types )) . "')";

			$attachments = $wpdb->get_results( $query );

			$images = [];

			foreach ( $attachments as $attachment ) {
				if ( $sort_by_group ) {
					$images[$attachment->post_mime_type][] = $attachment;
				} else {
					$images[] = $attachment;
				}
			}

			return $images;
		}

	endif;
	
	
