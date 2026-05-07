<?php
namespace Thumbpress\Controllers\Common;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Helpers\Utility;
use Thumbpress\Traits\Hook;
use Thumbpress\Traits\Asset;
use Thumbpress\Traits\Cache;

class Convert_Avif {

	use Hook;
	use Asset;
	use Cache;

	public function get_image_mime_types_for_avif( $formats ) {
		$format_to_mime = array(
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'bmp'  => 'image/bmp',
			'webp' => 'image/webp',
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
		$this->action( 'thumbpress_convert_all_image_avif', array( $this, 'convert_all_image' ) );
		$this->filter( 'intermediate_image_sizes_advanced', array( $this, 'image_sizes' ) );
		$this->filter( 'big_image_size_threshold', array( $this, 'big_image_size' ), 10, 1 );
		$this->action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue the convert single image to AVIF script on media pages.
	 */
	public function enqueue_scripts( $hook ) {
		if ( $hook !== 'post.php' && $hook !== 'upload.php' ) {
			return;
		}

		if ( ! get_option( 'thumbpress_avif_single_image_convert', false ) ) {
			return;
		}

		$this->enqueue_script(
			'thumbpress-convert-single-avif',
			THUMBPRESS_PLUGIN_URL . 'assets/admin/js/convert-single-avif.js',
			array( 'image-sizes_admin' )
		);
	}

	/**
	 * Convert image to AVIF on upload if setting is enabled.
	 */
	public function convert_image_on_upload( $file_info ) {
		if ( ! get_option( 'thumbpress_avif_convert_on_upload', false ) ) {
			return $file_info;
		}

		if ( ! in_array( $file_info['type'], thumbpress_supported_image_mimes( array( 'image/avif' ) ) ) ) {
			return $file_info;
		}

		$original_img_path = $file_info['file'];
		$size_before       = file_exists( $original_img_path ) ? filesize( $original_img_path ) : 0;
		$avif_file_path    = $this->convert_image_to_avif( $original_img_path );

		if ( ! $avif_file_path ) {
			return $file_info;
		}

		$size_after  = file_exists( $avif_file_path ) ? filesize( $avif_file_path ) : 0;
		$saved_bytes = max( 0, $size_before - $size_after );

		if ( $saved_bytes > 0 ) {
			thumbpress_add_space_saved( $saved_bytes );
		}

		// Cache cleared by add_attachment → Init::clear_media_cache() fired after this filter returns.

		$avif_file_url = $this->generate_avif_file_url( $avif_file_path );

		// Delete original image.
		wp_delete_file( $original_img_path );

		return array(
			'file' => $avif_file_path,
			'url'  => $avif_file_url,
			'type' => 'image/avif',
		);
	}

	/**
	 * Display "Convert to AVIF" button in the media library attachment fields.
	 */
	public function display_convert_image_btn( $form_fields, $post ) {
		if ( ! in_array( $post->post_mime_type, thumbpress_supported_image_mimes( array( 'image/avif' ) ) ) ) {
			return $form_fields;
		}

		if ( ! get_option( 'thumbpress_avif_single_image_convert', false ) ) {
			return $form_fields;
		}

		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			$form_fields['thumbpress_convert_image_avif'] = array(
				'label' => __( 'Convert to AVIF', 'image-sizes' ),
				'input' => 'html',
				'html'  => '<span style="color:#dc3545;font-weight:500;">&#9888; Requires PHP 8.0+ (current: ' . PHP_VERSION . ')</span>',
			);

			return $form_fields;
		}

		$avif_icon = '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.1688 2.5H8.28027C8.245 2.50002 8.21009 2.50709 8.1776 2.5208C8.14552 2.53413 8.11635 2.55361 8.09173 2.57813L4.07813 6.59173C4.05334 6.61652 4.03367 6.64596 4.02027 6.67835C4.00686 6.71075 3.99997 6.74547 4 6.78053V17.2099C4.00035 17.5519 4.13635 17.8798 4.37817 18.1216C4.61998 18.3635 4.94786 18.4996 5.28987 18.5H15.1688C15.5108 18.4996 15.8387 18.3635 16.0805 18.1216C16.3223 17.8798 16.4583 17.5519 16.4587 17.2099V3.79013C16.4583 3.44812 16.3223 3.12022 16.0805 2.87836C15.8387 2.63649 15.5108 2.50042 15.1688 2.5ZM8.0136 3.4104V5.75707C8.01339 5.95767 7.93362 6.15 7.7918 6.29188C7.64997 6.43375 7.45767 6.51358 7.25707 6.51387H4.9104L8.0136 3.4104ZM15.9253 17.2099C15.9251 17.4105 15.8454 17.6028 15.7035 17.7447C15.5617 17.8866 15.3694 17.9664 15.1688 17.9667H5.28987C5.08926 17.9664 4.89696 17.8866 4.75513 17.7447C4.61331 17.6028 4.53355 17.4105 4.53333 17.2099V7.0472H7.25707C7.59908 7.04678 7.92695 6.91071 8.16877 6.66884C8.41058 6.42698 8.54658 6.09908 8.54693 5.75707V3.03333H15.1688C15.3694 3.03362 15.5617 3.11345 15.7035 3.25532C15.8454 3.3972 15.9251 3.58953 15.9253 3.79013V17.2099Z" fill="currentColor"/><path d="M13.4145 8.22852H7.04596C6.8251 8.2288 6.61337 8.31666 6.4572 8.47283C6.30103 8.629 6.21317 8.84073 6.21289 9.06158V14.5706C6.21317 14.7915 6.30103 15.0032 6.4572 15.1594C6.61337 15.3156 6.8251 15.4034 7.04596 15.4037H13.4145C13.6353 15.4034 13.8471 15.3156 14.0032 15.1594C14.1594 15.0032 14.2473 14.7915 14.2476 14.5706V9.06158C14.2473 8.84073 14.1594 8.629 14.0032 8.47283C13.8471 8.31666 13.6353 8.2288 13.4145 8.22852ZM7.04596 8.76185H13.4145C13.494 8.76192 13.5702 8.79352 13.6264 8.84972C13.6826 8.90591 13.7142 8.98211 13.7142 9.06158V12.6848L13.3313 12.32C13.2944 12.2848 13.2483 12.261 13.1983 12.2512C13.1483 12.2415 13.0965 12.2463 13.0492 12.265L11.9524 12.6992L9.18409 10.2176C9.13626 10.1747 9.07457 10.1505 9.01034 10.1495C8.94611 10.1485 8.88367 10.1707 8.83449 10.212L6.74622 11.9666V9.06158C6.74629 8.98211 6.7779 8.90591 6.83409 8.84972C6.89029 8.79352 6.96648 8.76192 7.04596 8.76185ZM13.4145 14.8704H7.04596C6.96648 14.8703 6.89029 14.8387 6.83409 14.7825C6.7779 14.7263 6.74629 14.6501 6.74622 14.5706V12.6632L9.00036 10.7698L11.719 13.2072C11.7559 13.2403 11.8013 13.2624 11.8501 13.2711C11.8989 13.2798 11.9492 13.2748 11.9953 13.2565L13.0868 12.8245L13.7142 13.4224V14.5714C13.7139 14.6508 13.6822 14.7268 13.6261 14.7828C13.5699 14.8388 13.4938 14.8703 13.4145 14.8704Z" fill="currentColor"/><path d="M12.1336 11.6245C12.3724 11.6245 12.6059 11.5536 12.8046 11.4209C13.0032 11.2882 13.158 11.0995 13.2494 10.8788C13.3408 10.6581 13.3647 10.4153 13.3181 10.181C13.2714 9.94672 13.1564 9.73152 12.9875 9.56263C12.8185 9.39373 12.6033 9.27873 12.369 9.23216C12.1347 9.18559 11.8919 9.20954 11.6712 9.301C11.4505 9.39245 11.2619 9.54729 11.1292 9.74593C10.9966 9.94458 10.9258 10.1781 10.9258 10.417C10.9262 10.7372 11.0535 11.0441 11.28 11.2705C11.5064 11.4969 11.8134 11.6242 12.1336 11.6245ZM12.1336 9.74258C12.2669 9.74258 12.3973 9.78214 12.5082 9.85624C12.6191 9.93035 12.7056 10.0357 12.7566 10.1589C12.8077 10.2821 12.821 10.4177 12.795 10.5486C12.769 10.6794 12.7047 10.7995 12.6104 10.8939C12.5161 10.9882 12.3959 11.0524 12.2651 11.0784C12.1343 11.1044 11.9987 11.0911 11.8755 11.04C11.7522 10.989 11.6469 10.9026 11.5728 10.7917C11.4987 10.6808 11.4591 10.5504 11.4591 10.417C11.4594 10.2382 11.5305 10.0668 11.6569 9.94035C11.7833 9.81392 11.9548 9.7428 12.1336 9.74258ZM7.58875 17.0383H6.73542C6.66469 17.0383 6.59686 17.0664 6.54686 17.1164C6.49685 17.1664 6.46875 17.2343 6.46875 17.305C6.46875 17.3757 6.49685 17.4435 6.54686 17.4935C6.59686 17.5436 6.66469 17.5717 6.73542 17.5717H7.58875C7.65947 17.5717 7.7273 17.5436 7.77731 17.4935C7.82732 17.4435 7.85542 17.3757 7.85542 17.305C7.85542 17.2343 7.82732 17.1664 7.77731 17.1164C7.7273 17.0664 7.65947 17.0383 7.58875 17.0383ZM9.42022 17.0383H8.74928C8.67856 17.0383 8.61073 17.0664 8.56072 17.1164C8.51071 17.1664 8.48262 17.2343 8.48262 17.305C8.48262 17.3757 8.51071 17.4435 8.56072 17.4935C8.61073 17.5436 8.67856 17.5717 8.74928 17.5717H9.42022C9.49094 17.5717 9.55877 17.5436 9.60878 17.4935C9.65879 17.4435 9.68688 17.3757 9.68688 17.305C9.68688 17.2343 9.65879 17.1664 9.60878 17.1164C9.55877 17.0664 9.49094 17.0383 9.42022 17.0383Z" fill="currentColor"/></svg>';
		$html      = sprintf(
			'<button id="thumbpress-convert-image-avif" data-image_id="%1$s" class="button thumbpress_img_btn" type="button">%2$s%3$s</button>',
			$post->ID,
			$avif_icon,
			__( 'Convert to AVIF', 'image-sizes' )
		);

		$form_fields['thumbpress_convert_image_avif'] = array(
			'label' => __( 'Convert to AVIF', 'image-sizes' ),
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
	 * Convert a source image to AVIF format.
	 */
	public function convert_image_to_avif( $source ) {
		$file_info = pathinfo( $source );
		$extension = strtolower( $file_info['extension'] );

		if ( $extension === 'avif' ) {
			return false;
		}

		$base_dir  = $file_info['dirname'];
		$base_name = $file_info['filename'];
		$avif_path = $base_dir . '/' . $base_name . '.avif';

		// If a different-format source produced the same target (e.g. foo.jpg + foo.png both → foo.avif),
		// append -1, -2, ... so each source keeps its own converted output.
		if ( file_exists( $avif_path ) ) {
			$unique_name = wp_unique_filename( $base_dir, $base_name . '.avif' );
			$avif_path   = $base_dir . '/' . $unique_name;
		}

		$editor = wp_get_image_editor( $source );
		if ( is_wp_error( $editor ) ) {
			return false;
		}

		$result = $editor->save( $avif_path, 'image/avif' );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		// Verify the saved file is actually AVIF — some editors silently
		// fall back to the original format when AVIF is unsupported.
		$saved_path = $result['path'] ?? $avif_path;
		$saved_mime = $result['mime-type'] ?? '';

		if ( $saved_mime && $saved_mime !== 'image/avif' ) {
			// Editor saved as wrong format — clean up and fail.
			if ( file_exists( $saved_path ) ) {
				wp_delete_file( $saved_path );
			}
			return false;
		}

		return $saved_path;
	}

	/**
	 * Generate the URL for an AVIF file from its file path.
	 */
	public function generate_avif_file_url( $avif_file_path ) {
		$avif_file_path = pathinfo( $avif_file_path, PATHINFO_DIRNAME ) . '/' . pathinfo( $avif_file_path, PATHINFO_FILENAME ) . '.avif';
		$avif_file_url  = str_replace( ABSPATH, home_url( '/' ), $avif_file_path );

		return $avif_file_url;
	}

	/**
	 * Background batch conversion (called by Action Scheduler).
	 */
	public function convert_all_image( $offset, $file_formats = array( 'jpeg', 'png', 'jpg', 'webp' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		global $wpdb;

		$image_types       = $this->get_image_mime_types_for_avif( $file_formats );
		$limit             = (int) get_option( 'thumbpress_avif_convert_img_val', 100 );
		$total_attachments = (int) get_option( 'thumbpress_avif_convert_background_total_images' );
		$space_saved       = (int) get_option( 'thumbpress_avif_convert_space_saved', 0 );

		$query = $wpdb->prepare(
			"
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_status != 'trash'
			AND post_mime_type IN ('" . implode( "','", array_map( 'esc_sql', $image_types ) ) . "')
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
					continue;
				}

				$file_info = pathinfo( $main_img );
				$extension = strtolower( $file_info['extension'] ?? '' );
				$main_img  = str_replace( "-scaled.{$extension}", ".{$extension}", $main_img );

				if ( ! file_exists( $main_img ) ) {
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

				$avif_file_path = $this->convert_image_to_avif( $main_img );

				if ( ! $avif_file_path ) {
					continue;
				}

				// Delete old files only after successful conversion.
				if ( ! empty( $old_metadata['sizes'] ) ) {
					foreach ( $old_metadata['sizes'] as $old_size_data ) {
						if ( 'image/svg+xml' === $old_size_data['mime-type'] ) {
							continue;
						}
						wp_delete_file( $thumb_dir . $old_size_data['file'] );
					}
				}

				// Delete original source file.
				if ( file_exists( $main_img ) ) {
					wp_delete_file( $main_img );
				}
				$scaled_path = str_replace( ".{$extension}", "-scaled.{$extension}", $main_img );
				if ( $scaled_path !== $main_img && file_exists( $scaled_path ) ) {
					wp_delete_file( $scaled_path );
				}

				$avif_metadata = wp_generate_attachment_metadata( $img_id, $avif_file_path );

				update_attached_file( $img_id, $avif_file_path );
				wp_update_attachment_metadata( $img_id, $avif_metadata );

				$updated_metadata = wp_get_attachment_metadata( $img_id );
				$file_path        = $updated_metadata['file'];
				update_post_meta( $img_id, '_wp_attached_file', $file_path );
				wp_update_post(
					array(
						'ID'             => $img_id,
						'post_mime_type' => 'image/avif',
						'guid'           => wp_get_attachment_url( $img_id ),
					)
				);

				Utility::refresh_file_meta( $img_id, $avif_file_path );

				// Calculate new total size after conversion.
				$new_size     = file_exists( $avif_file_path ) ? filesize( $avif_file_path ) : 0;
				$new_metadata = wp_get_attachment_metadata( $img_id );
				if ( ! empty( $new_metadata['sizes'] ) ) {
					foreach ( $new_metadata['sizes'] as $thumb ) {
						$thumb_path = dirname( $avif_file_path ) . '/' . $thumb['file'];
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

			update_option( 'thumbpress_avif_convert_progress', $progress );
			update_option( 'thumbpress_avif_convert_total_processed', $count );
			update_option( 'thumbpress_avif_convert_total_converted', $count );
			update_option( 'thumbpress_avif_convert_space_saved', $space_saved );

			if ( $progress < 100 ) {
				as_schedule_single_action(
					wp_date( 'U' ) - 10,
					'thumbpress_convert_all_image_avif',
					array(
						'offset'       => $new_offset,
						'file_formats' => $file_formats,
					)
				);
			} else {
				update_option( 'thumbpress_avif_convert_last_completed_time', wp_date( 'U' ) );
				$this->delete_cache( 'stat_not_avif' );
				$this->delete_cache( 'stat_not_webp' );
				$this->delete_cache( 'stat_unoptimized' );
			}
		}
	}
}
