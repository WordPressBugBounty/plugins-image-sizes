<?php
namespace Thumbpress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Helpers\Utility;
use Thumbpress\Traits\Hook;
use Thumbpress\Traits\Asset;
use Thumbpress\Traits\Cache;

class Convert_Webp {

	use Hook;
	use Asset;
	use Cache;

	public function clear_webp_caches() {
		$this->delete_cache( 'stat_not_webp' );
		$this->delete_cache( 'stat_not_avif' );
		$this->delete_cache( 'stat_unoptimized' );
	}

	public function get_image_mime_types_for_webp( $formats ) {
		$format_to_mime = array(
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'bmp'  => 'image/bmp',
			'avif' => 'image/avif',
		);

		$mime_types = array();

		foreach ( (array) $formats as $format ) {
			$format = strtolower( trim( $format ) );
			if ( isset( $format_to_mime[ $format ] ) ) {
				$mime_types[] = $format_to_mime[ $format ];
			}
		}

		return ! empty( $mime_types ) ? array_values( array_unique( $mime_types ) ) : array( 'image/png', 'image/jpeg' );
	}

	public function __construct() {
		$this->filter( 'wp_handle_upload', array( $this, 'convert_image_on_upload' ) );
		$this->filter( 'attachment_fields_to_edit', array( $this, 'display_convert_image_btn' ), 10, 2 );
		$this->action( 'thumbpress_convert_all_image', array( $this, 'convert_all_image' ) );
		$this->filter( 'intermediate_image_sizes_advanced', array( $this, 'image_sizes' ) );
		$this->filter( 'big_image_size_threshold', array( $this, 'big_image_size' ), 10, 1 );
		$this->action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue the convert single image script on media pages.
	 */
	public function enqueue_scripts( $hook ) {
		if ( $hook !== 'post.php' && $hook !== 'upload.php' ) {
			return;
		}

		if ( ! get_option( 'thumbpress_single_image_convert', false ) ) {
			return;
		}

		$this->enqueue_script(
			'thumbpress-convert-single',
			THUMBPRESS_PLUGIN_URL . 'assets/admin/js/convert-single-webp.js',
			array( 'image-sizes_admin' )
		);
	}

	/**
	 * Convert image to WebP on upload if setting is enabled.
	 */
	public function convert_image_on_upload( $file_info ) {
		if ( ! get_option( 'thumbpress_webp_on_upload', false ) ) {
			return $file_info;
		}

		if ( ! in_array( $file_info['type'], thumbpress_supported_image_mimes( array( 'image/webp' ) ) ) ) {
			return $file_info;
		}

		$original_img_path = $file_info['file'];
		$size_before       = file_exists( $original_img_path ) ? filesize( $original_img_path ) : 0;
		$webp_file_path    = $this->convert_image_to_webp( $original_img_path );

		if ( ! $webp_file_path ) {
			return $file_info;
		}

		$size_after  = file_exists( $webp_file_path ) ? filesize( $webp_file_path ) : 0;
		$saved_bytes = max( 0, $size_before - $size_after );

		if ( $saved_bytes > 0 ) {
			thumbpress_add_space_saved( $saved_bytes );
		}

		$webp_file_url = $this->generate_webp_file_url( $webp_file_path );

		// Delete original image.
		wp_delete_file( $original_img_path );

		$this->clear_webp_caches();

		return array(
			'file' => $webp_file_path,
			'url'  => $webp_file_url,
			'type' => 'image/webp',
		);
	}

	/**
	 * Display "Convert to WebP" button in the media library attachment fields.
	 */
	public function display_convert_image_btn( $form_fields, $post ) {
		if ( ! in_array( $post->post_mime_type, thumbpress_supported_image_mimes( array( 'image/webp' ) ) ) ) {
			return $form_fields;
		}

		if ( ! get_option( 'thumbpress_single_image_convert', false ) ) {
			return $form_fields;
		}

		$webp_icon = '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.9939 6.30274C16.9947 6.28891 16.9947 6.27505 16.9939 6.26122C16.9845 6.21121 16.9589 6.16546 16.9209 6.1307L13.2687 2.57119C13.233 2.53409 13.1861 2.50914 13.1348 2.5H6.34783C6.1088 2.49995 5.87929 2.59126 5.70858 2.75432C5.53786 2.91737 5.43956 3.13917 5.43478 3.37208V10.1945H3.91304C3.67089 10.1945 3.43865 10.2882 3.26742 10.4551C3.0962 10.622 3 10.8483 3 11.0844V15.2371C3 15.4731 3.0962 15.6995 3.26742 15.8664C3.43865 16.0332 3.67089 16.127 3.91304 16.127H5.43478V17.6101C5.43478 17.8461 5.53098 18.0725 5.70221 18.2394C5.87344 18.4062 6.10567 18.5 6.34783 18.5H16.087C16.3291 18.5 16.5613 18.4062 16.7326 18.2394C16.9038 18.0725 17 17.8461 17 17.6101V6.33834C17 6.33834 16.9939 6.33834 16.9939 6.30274ZM15.9591 6.04171H13.3478V3.49666L15.9591 6.04171ZM3.6087 15.2371V11.0844C3.6087 11.0057 3.64076 10.9302 3.69784 10.8746C3.75491 10.819 3.83233 10.7877 3.91304 10.7877H13.6522C13.7329 10.7877 13.8103 10.819 13.8674 10.8746C13.9245 10.9302 13.9565 11.0057 13.9565 11.0844V15.2371C13.9565 15.3158 13.9245 15.3912 13.8674 15.4469C13.8103 15.5025 13.7329 15.5337 13.6522 15.5337H3.91304C3.83233 15.5337 3.75491 15.5025 3.69784 15.4469C3.64076 15.3912 3.6087 15.3158 3.6087 15.2371ZM16.087 17.9067H6.34783C6.26711 17.9067 6.1897 17.8755 6.13262 17.8199C6.07554 17.7642 6.04348 17.6888 6.04348 17.6101V16.127H13.6522C13.8943 16.127 14.1266 16.0332 14.2978 15.8664C14.469 15.6995 14.5652 15.4731 14.5652 15.2371V11.0844C14.5652 10.8483 14.469 10.622 14.2978 10.4551C14.1266 10.2882 13.8943 10.1945 13.6522 10.1945H6.04348V3.37208C6.04348 3.29341 6.07554 3.21796 6.13262 3.16233C6.1897 3.10671 6.26711 3.07545 6.34783 3.07545H12.7391V6.33834C12.7391 6.41701 12.7712 6.49246 12.8283 6.54809C12.8853 6.60371 12.9628 6.63496 13.0435 6.63496H16.3913V17.6101C16.3913 17.6888 16.3592 17.7642 16.3022 17.8199C16.2451 17.8755 16.1677 17.9067 16.087 17.9067ZM10.913 4.55858C10.913 4.63725 10.881 4.7127 10.8239 4.76833C10.7668 4.82396 10.6894 4.85521 10.6087 4.85521H6.95652C6.8758 4.85521 6.79839 4.82396 6.74131 4.76833C6.68424 4.7127 6.65217 4.63725 6.65217 4.55858C6.65217 4.47991 6.68424 4.40447 6.74131 4.34884C6.79839 4.29321 6.8758 4.26196 6.95652 4.26196H10.6087C10.6894 4.26196 10.7668 4.29321 10.8239 4.34884C10.881 4.40447 10.913 4.47991 10.913 4.55858ZM10.3043 6.33834C10.3043 6.41701 10.2723 6.49246 10.2152 6.54809C10.1581 6.60371 10.0807 6.63496 10 6.63496H6.95652C6.8758 6.63496 6.79839 6.60371 6.74131 6.54809C6.68424 6.49246 6.65217 6.41701 6.65217 6.33834C6.65217 6.25967 6.68424 6.18422 6.74131 6.12859C6.79839 6.07296 6.8758 6.04171 6.95652 6.04171H10C10.0807 6.04171 10.1581 6.07296 10.2152 6.12859C10.2723 6.18422 10.3043 6.25967 10.3043 6.33834ZM9.08696 8.11809C9.08696 8.19676 9.05489 8.27221 8.99782 8.32784C8.94074 8.38347 8.86333 8.41472 8.78261 8.41472H6.95652C6.8758 8.41472 6.79839 8.38347 6.74131 8.32784C6.68424 8.27221 6.65217 8.19676 6.65217 8.11809C6.65217 8.03942 6.68424 7.96398 6.74131 7.90835C6.79839 7.85272 6.8758 7.82147 6.95652 7.82147H8.78261C8.86333 7.82147 8.94074 7.85272 8.99782 7.90835C9.05489 7.96398 9.08696 8.03942 9.08696 8.11809ZM5.73913 14.5964C5.66866 14.5976 5.60005 14.5744 5.54545 14.5309C5.49085 14.4875 5.4538 14.4266 5.44087 14.3591L5.27043 13.5345L5.13043 14.3591C5.1164 14.4261 5.07905 14.4863 5.0247 14.5296C4.97035 14.5728 4.90234 14.5964 4.83217 14.5964C4.76171 14.5976 4.69309 14.5744 4.63849 14.5309C4.5839 14.4875 4.54685 14.4266 4.53391 14.3591L4.06522 12.081C4.05722 12.0428 4.05701 12.0035 4.06461 11.9653C4.07221 11.927 4.08747 11.8906 4.10951 11.8581C4.13156 11.8256 4.15996 11.7976 4.19309 11.7758C4.22622 11.754 4.26344 11.7388 4.30261 11.731C4.34183 11.7224 4.38245 11.7215 4.422 11.7286C4.46156 11.7357 4.49925 11.7504 4.53279 11.772C4.56634 11.7937 4.59506 11.8217 4.61722 11.8544C4.63938 11.8871 4.65452 11.9238 4.66174 11.9624L4.83217 12.787L5.00261 11.9624C5.01554 11.8948 5.05259 11.834 5.10719 11.7905C5.16179 11.7471 5.2304 11.7239 5.30087 11.7251C5.37103 11.7251 5.43904 11.7487 5.49339 11.7919C5.54775 11.8351 5.5851 11.8954 5.59913 11.9624L5.73913 12.787L5.90348 11.9624C5.91147 11.9234 5.92726 11.8864 5.94994 11.8534C5.97262 11.8204 6.00175 11.792 6.03566 11.77C6.06957 11.748 6.10761 11.7327 6.14759 11.7249C6.18758 11.7172 6.22873 11.7173 6.2687 11.7251C6.30866 11.7329 6.34666 11.7482 6.38053 11.7703C6.4144 11.7925 6.44346 11.8208 6.46607 11.8539C6.48868 11.8869 6.50439 11.924 6.5123 11.963C6.52021 12.002 6.52017 12.0421 6.51217 12.081L6.04348 14.3591C6.02818 14.4266 5.98965 14.4869 5.93428 14.5301C5.87891 14.5733 5.81004 14.5967 5.73913 14.5964ZM6.83478 14.2998V12.0217C6.83478 11.943 6.86685 11.8676 6.92392 11.8119C6.981 11.7563 7.05841 11.7251 7.13913 11.7251H8.54522C8.62594 11.7251 8.70335 11.7563 8.76042 11.8119C8.8175 11.8676 8.84956 11.943 8.84956 12.0217C8.84956 12.1004 8.8175 12.1758 8.76042 12.2314C8.70335 12.2871 8.62594 12.3183 8.54522 12.3183H7.44957V12.8641H8.5513C8.63202 12.8641 8.70943 12.8954 8.76651 12.951C8.82359 13.0066 8.85565 13.0821 8.85565 13.1607C8.85565 13.2394 8.82359 13.3149 8.76651 13.3705C8.70943 13.4261 8.63202 13.4574 8.5513 13.4574H7.44957V14.0032H8.5513C8.63202 14.0032 8.70943 14.0344 8.76651 14.09C8.82359 14.1457 8.85565 14.2211 8.85565 14.2998C8.85565 14.3784 8.82359 14.4539 8.76651 14.5095C8.70943 14.5652 8.63202 14.5964 8.5513 14.5964H7.14522C7.0645 14.5964 6.98709 14.5652 6.93001 14.5095C6.87293 14.4539 6.84087 14.3784 6.84087 14.2998H6.83478ZM9.47652 14.5964H10.3043C10.4231 14.5996 10.5413 14.5795 10.6519 14.5374C10.7626 14.4953 10.8634 14.432 10.9485 14.3513C11.0337 14.2705 11.1013 14.174 11.1475 14.0673C11.1937 13.9606 11.2174 13.846 11.2174 13.7303C11.2222 13.5129 11.1442 13.3015 10.9983 13.137C11.1442 12.9725 11.2222 12.7611 11.2174 12.5438C11.2174 12.314 11.1238 12.0937 10.9571 11.9313C10.7904 11.7689 10.5644 11.6776 10.3287 11.6776H9.48261C9.40189 11.6776 9.32448 11.7089 9.2674 11.7645C9.21033 11.8201 9.17826 11.8956 9.17826 11.9742V14.2523C9.17115 14.2951 9.17372 14.3388 9.1858 14.3804C9.19788 14.4221 9.21916 14.4607 9.24817 14.4935C9.27717 14.5263 9.31319 14.5525 9.35369 14.5703C9.39419 14.588 9.43819 14.597 9.48261 14.5964H9.47652ZM9.78087 12.3183H10.2983C10.3725 12.3183 10.4437 12.3471 10.4963 12.3982C10.5488 12.4494 10.5783 12.5188 10.5783 12.5912C10.5783 12.6636 10.5488 12.733 10.4963 12.7842C10.4437 12.8354 10.3725 12.8641 10.2983 12.8641H9.78087V12.3183ZM9.78087 13.4574H10.2983C10.3725 13.4574 10.4437 13.4861 10.4963 13.5373C10.5488 13.5885 10.5783 13.6579 10.5783 13.7303C10.5783 13.8026 10.5488 13.872 10.4963 13.9232C10.4437 13.9744 10.3725 14.0032 10.2983 14.0032H9.78087V13.4574ZM11.8261 14.5964C11.9068 14.5964 11.9842 14.5652 12.0413 14.5095C12.0984 14.4539 12.1304 14.3784 12.1304 14.2998V13.7065H12.5322C12.7985 13.7065 13.054 13.6034 13.2424 13.4198C13.4307 13.2362 13.5365 12.9873 13.5365 12.7277C13.5365 12.468 13.4307 12.2191 13.2424 12.0355C13.054 11.8519 12.7985 11.7488 12.5322 11.7488H11.8261C11.7454 11.7488 11.668 11.78 11.6109 11.8357C11.5538 11.8913 11.5217 11.9668 11.5217 12.0454V14.3235C11.5279 14.398 11.5626 14.4675 11.6191 14.5181C11.6755 14.5687 11.7494 14.5966 11.8261 14.5964ZM12.1304 12.3183H12.5322C12.6371 12.3183 12.7377 12.3589 12.8119 12.4313C12.8861 12.5036 12.9278 12.6017 12.9278 12.7039C12.9278 12.8062 12.8861 12.9043 12.8119 12.9766C12.7377 13.0489 12.6371 13.0895 12.5322 13.0895H12.1304V12.3183Z" fill="currentColor"/></svg>';
		$html      = sprintf(
			'<button id="thumbpress-convert-image" data-image_id="%1$s" class="button thumbpress_img_btn" type="button">%2$s%3$s</button>',
			$post->ID,
			$webp_icon,
			__( 'Convert to WebP', 'image-sizes' )
		);

		$form_fields['thumbpress_convert_image'] = array(
			'label' => __( 'Convert to WebP', 'image-sizes' ),
			'input' => 'html',
			'html'  => $html,
		);

		return $form_fields;
	}

	/**
	 * Filter registered image sizes to remove disabled ones.
	 */
	public function image_sizes( $sizes ) {
		$option   = get_option( 'prevent_image_sizes', array() );
		$disables = isset( $option['disables'] ) ? $option['disables'] : array();

		if ( ! empty( $disables ) ) {
			foreach ( $disables as $disable ) {
				unset( $sizes[ $disable ] );
			}
		}

		return $sizes;
	}

	/**
	 * Disable scaled image if 'scaled' is in the disabled list.
	 */
	public function big_image_size( $threshold ) {
		$option   = get_option( 'prevent_image_sizes', array() );
		$disables = isset( $option['disables'] ) ? $option['disables'] : array();

		return in_array( 'scaled', $disables ) ? false : $threshold;
	}

	/**
	 * Convert a source image to WebP format.
	 */
	public function convert_image_to_webp( $source ) {
		$file_info = pathinfo( $source );
		$extension = strtolower( $file_info['extension'] );

		if ( $extension === 'webp' ) {
			return false;
		}

		$base_dir  = $file_info['dirname'];
		$base_name = $file_info['filename'];
		$webp_path = $base_dir . '/' . $base_name . '.webp';

		// If a different-format source produced the same target (e.g. foo.jpg + foo.png both → foo.webp),
		// append -1, -2, ... so each source keeps its own converted output.
		if ( file_exists( $webp_path ) ) {
			$unique_name = wp_unique_filename( $base_dir, $base_name . '.webp' );
			$webp_path   = $base_dir . '/' . $unique_name;
		}

		// Raise memory limit for image ops (WP helper, respects WP_MAX_MEMORY_LIMIT).
		wp_raise_memory_limit( 'image' );

		// Skip if image too big to safely decode — width*height*4 bytes + overhead.
		$dims = @getimagesize( $source );
		if ( is_array( $dims ) ) {
			$pixels       = (int) $dims[0] * (int) $dims[1];
			$needed_bytes = $pixels * 4 * 2; // Decoded buffer + working copy.
			$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
			$memory_usage = memory_get_usage( true );
			if ( $memory_limit > 0 && ( $memory_usage + $needed_bytes ) > $memory_limit ) {
				return false;
			}
		}

		$editor = wp_get_image_editor( $source );
		if ( is_wp_error( $editor ) ) {
			return false;
		}

		$result = $editor->save( $webp_path, 'image/webp' );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		$saved_path = $result['path'] ?? $webp_path;
		$saved_mime = $result['mime-type'] ?? '';

		if ( $saved_mime && $saved_mime !== 'image/webp' ) {
			if ( file_exists( $saved_path ) ) {
				wp_delete_file( $saved_path );
			}
			return false;
		}

		return $saved_path;
	}

	/**
	 * Generate the URL for a WebP file from its file path.
	 */
	public function generate_webp_file_url( $webp_file_path ) {
		$webp_file_path = pathinfo( $webp_file_path, PATHINFO_DIRNAME ) . '/' . pathinfo( $webp_file_path, PATHINFO_FILENAME ) . '.webp';
		$webp_file_url  = str_replace( ABSPATH, home_url( '/' ), $webp_file_path );

		return $webp_file_url;
	}


	public function convert_all_image( $offset, $file_formats = array( 'jpeg', 'png', 'jpg' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		global $wpdb;

		$image_types       = $this->get_image_mime_types_for_webp( $file_formats );
		$limit             = (int) get_option( 'thumbpress_convert_img_val', 100 );
		$total_attachments = (int) get_option( 'thumbpress_now_convert_background_total_images' );
		$processed_count   = (int) get_option( 'thumbpress_convert_total_processd', 0 );
		$converted_count   = (int) get_option( 'thumbpress_convert_total_converted', 0 );
		$space_saved       = (int) get_option( 'thumbpress_convert_space_saved', 0 );

		$query = $wpdb->prepare(
			"
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type IN ('" . implode( "','", array_map( 'esc_sql', $image_types ) ) . "')
			AND post_status != 'trash'
			LIMIT %d
		",
			$limit
		);

		$attachments = $wpdb->get_results( $query );

		if ( count( $attachments ) > 0 ) {
			$batch_saved = 0;
			foreach ( $attachments as $attachment ) {
				$img_id   = $attachment->ID;
				$main_img = get_attached_file( $img_id );

				if ( ! $main_img || ! file_exists( $main_img ) ) {
					++$processed_count;
					continue;
				}

				$file_info = pathinfo( $main_img );
				$extension = strtolower( $file_info['extension'] ?? '' );
				$main_img  = str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );

				if ( ! file_exists( $main_img ) ) {
					++$processed_count;
					continue;
				}

				$old_metadata = wp_get_attachment_metadata( $img_id );
				$thumb_dir    = dirname( $main_img ) . DIRECTORY_SEPARATOR;

				// Calculate old total size before conversion.
				$old_size = file_exists( $main_img ) ? filesize( $main_img ) : 0;
				if ( ! empty( $old_metadata['sizes'] ) ) {
					foreach ( $old_metadata['sizes'] as $thumb ) {
						$thumb_path = $thumb_dir . $thumb['file'];
						if ( file_exists( $thumb_path ) ) {
							$old_size += filesize( $thumb_path );
						}
					}
				}

				// Delete old thumbnails before conversion to avoid name collisions.
				foreach ( ( $old_metadata['sizes'] ?? array() ) as $size_name => $size_data ) {
					if ( 'image/svg+xml' == $size_data['mime-type'] ) {
						continue;
					}
					wp_delete_file( $thumb_dir . $size_data['file'] );
				}

				$webp_file_path = $this->convert_image_to_webp( $main_img );

				// Delete original source file after conversion.
				if ( file_exists( $main_img ) ) {
					wp_delete_file( $main_img );
				}
				$scaled_path = str_replace( ".{$extension}", "-scaled.{$extension}", $main_img );
				if ( $scaled_path !== $main_img && file_exists( $scaled_path ) ) {
					wp_delete_file( $scaled_path );
				}

				if ( ! $webp_file_path ) {
					continue;
				}

				$webp_metadata = wp_generate_attachment_metadata( $img_id, $webp_file_path );

				update_attached_file( $img_id, $webp_file_path );
				wp_update_attachment_metadata( $img_id, $webp_metadata );

				$updated_metadata = wp_get_attachment_metadata( $img_id );
				$file_path        = $updated_metadata['file'];
				update_post_meta( $img_id, '_wp_attached_file', $file_path );
				wp_update_post(
					array(
						'ID'             => $img_id,
						'post_mime_type' => 'image/webp',
						'guid'           => wp_get_attachment_url( $img_id ),
					)
				);

				Utility::refresh_file_meta( $img_id, $webp_file_path );

				// Calculate new total size after conversion.
				$new_size     = file_exists( $webp_file_path ) ? filesize( $webp_file_path ) : 0;
				$new_metadata = wp_get_attachment_metadata( $img_id );
				if ( ! empty( $new_metadata['sizes'] ) ) {
					foreach ( $new_metadata['sizes'] as $thumb ) {
						$thumb_path = dirname( $webp_file_path ) . '/' . $thumb['file'];
						if ( file_exists( $thumb_path ) ) {
							$new_size += filesize( $thumb_path );
						}
					}
				}
				$space_saved += max( 0, $old_size - $new_size );
				$batch_saved += max( 0, $old_size - $new_size );
			}

			thumbpress_add_space_saved( $batch_saved );

			$count      = $offset + count( $attachments );
			$progress   = ( $count / $total_attachments ) * 100;
			$progress   = min( $progress, 100 );
			$new_offset = $offset + count( $attachments );

			update_option( 'thumbpress_convert_progress', $progress );
			update_option( 'thumbpress_convert_total_processd', $count );
			update_option( 'thumbpress_convert_total_converted', $count );
			update_option( 'thumbpress_convert_space_saved', $space_saved );

			if ( $progress < 100 ) {
				as_schedule_single_action(
					wp_date( 'U' ) - 10,
					'thumbpress_convert_all_image',
					array(
						'offset'       => $new_offset,
						'file_formats' => $file_formats,
					)
				);
			} else {
				update_option( 'convert_last_completed_time', wp_date( 'U' ) );
				$this->clear_webp_caches();
			}
		}
	}
}
