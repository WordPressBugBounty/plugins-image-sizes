<?php
namespace Codexpert\ThumbPress\Abstracts;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Traits\Hook;
use Codexpert\ThumbPress\Traits\Asset;
use Codexpert\ThumbPress\Traits\Cache;

/**
 * Shared flow for single-image format converters (WebP, AVIF).
 *
 * Concrete converters supply the format-specific bits via get_config() and this
 * base implements the common upload/single-convert/button/size-filter logic. The
 * bulk-background pipeline is NOT shared here — it lives in each format's own
 * controller (WebP in free, AVIF in pro).
 */
abstract class Image_Converter {

	use Hook;
	use Asset;
	use Cache;

	/**
	 * Format-specific configuration.
	 *
	 * @return array {
	 *     @type string $format              Target extension/format, e.g. 'webp' or 'avif'.
	 *     @type string $on_upload_option    Option key that toggles auto-convert on upload.
	 *     @type string $single_option       Option key that toggles the single-convert button.
	 *     @type string $script_handle       Handle for the single-convert admin script.
	 *     @type string $script_file         Filename of the single-convert admin script.
	 *     @type string $button_id           DOM id for the media-library convert button.
	 *     @type string $button_field        attachment_fields_to_edit key for the button.
	 *     @type string $button_label        Translated button/label text.
	 *     @type string $button_icon         Inline SVG markup for the button.
	 *     @type string $already_message     Translated "already this format" error message.
	 *     @type string $save_failed_message Translated save-failed message (sprintf %s).
	 *     @type string $unsupported_message Translated "format unsupported here" message.
	 *     @type bool   $requires_php8       Whether the format needs PHP 8.0+.
	 * }
	 */
	abstract protected function get_config();

	public function __construct() {
		$this->filter( 'wp_handle_upload', array( $this, 'convert_image_on_upload' ) );
		$this->filter( 'attachment_fields_to_edit', array( $this, 'display_convert_image_btn' ), 10, 2 );
		$this->filter( 'intermediate_image_sizes_advanced', array( $this, 'image_sizes' ) );
		$this->filter( 'big_image_size_threshold', array( $this, 'big_image_size' ), 10, 1 );
		$this->action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Map requested source formats to their mime types, excluding the target format.
	 */
	public function get_image_mime_types( $formats ) {
		$format_to_mime = array(
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'bmp'  => 'image/bmp',
			'webp' => 'image/webp',
			'avif' => 'image/avif',
		);

		unset( $format_to_mime[ $this->get_config()['format'] ] );

		$mime_types = array();

		foreach ( (array) $formats as $format ) {
			$format = strtolower( trim( $format ) );
			if ( isset( $format_to_mime[ $format ] ) ) {
				$mime_types[] = $format_to_mime[ $format ];
			}
		}

		return ! empty( $mime_types ) ? array_values( array_unique( $mime_types ) ) : array( 'image/png', 'image/jpeg' );
	}

	/**
	 * Enqueue the single-image convert script on media pages.
	 */
	public function enqueue_scripts( $hook ) {
		if ( $hook !== 'post.php' && $hook !== 'upload.php' ) {
			return;
		}

		$config = $this->get_config();

		if ( ! get_option( $config['single_option'], false ) ) {
			return;
		}

		$this->enqueue_script(
			$config['script_handle'],
			THUMBPRESS_PLUGIN_URL . 'assets/admin/js/' . $config['script_file'],
			array( 'image-sizes_admin' )
		);
	}

	/**
	 * Convert an uploaded image to the target format if the setting is enabled.
	 */
	public function convert_image_on_upload( $file_info ) {
		$config      = $this->get_config();
		$target_mime = 'image/' . $config['format'];

		if ( ! get_option( $config['on_upload_option'], false ) ) {
			return $file_info;
		}

		if ( ! in_array( $file_info['type'], thumbpress_supported_image_mimes( array( $target_mime ) ) ) ) {
			return $file_info;
		}

		$original_img_path = $file_info['file'];
		$size_before       = file_exists( $original_img_path ) ? filesize( $original_img_path ) : 0;
		$converted_path    = $this->convert_image( $original_img_path );

		if ( is_wp_error( $converted_path ) ) {
			return $file_info;
		}

		$size_after  = file_exists( $converted_path ) ? filesize( $converted_path ) : 0;
		$saved_bytes = max( 0, $size_before - $size_after );

		if ( $saved_bytes > 0 ) {
			thumbpress_add_space_saved( $saved_bytes );
		}

		$converted_url = $this->generate_file_url( $converted_path );

		// Delete original image.
		wp_delete_file( $original_img_path );

		$this->after_upload_convert();

		return array(
			'file' => $converted_path,
			'url'  => $converted_url,
			'type' => $target_mime,
		);
	}

	/**
	 * Hook for subclasses to run after a successful on-upload conversion.
	 */
	protected function after_upload_convert() {}

	/**
	 * Display the "Convert to <format>" button in the media library attachment fields.
	 */
	public function display_convert_image_btn( $form_fields, $post ) {
		$config      = $this->get_config();
		$target_mime = 'image/' . $config['format'];

		if ( ! in_array( $post->post_mime_type, thumbpress_supported_image_mimes( array( $target_mime ) ) ) ) {
			return $form_fields;
		}

		if ( ! get_option( $config['single_option'], false ) ) {
			return $form_fields;
		}

		if ( ! empty( $config['requires_php8'] ) && version_compare( PHP_VERSION, '8.0', '<' ) ) {
			$form_fields[ $config['button_field'] ] = array(
				'label' => $config['button_label'],
				'input' => 'html',
				'html'  => '<span style="color:#dc3545;font-weight:500;">&#9888; Requires PHP 8.0+ (current: ' . PHP_VERSION . ')</span>',
			);

			return $form_fields;
		}

		$html = sprintf(
			'<button id="%1$s" data-image_id="%2$s" class="button thumbpress_img_btn" type="button">%3$s%4$s</button>',
			esc_attr( $config['button_id'] ),
			$post->ID,
			$config['button_icon'],
			$config['button_label']
		);

		$form_fields[ $config['button_field'] ] = array(
			'label' => $config['button_label'],
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
	 * Convert a source image to the target format.
	 */
	public function convert_image( $source ) {
		$config    = $this->get_config();
		$format    = $config['format'];
		$mime      = 'image/' . $format;
		$file_info = pathinfo( $source );
		$extension = strtolower( $file_info['extension'] );

		if ( $extension === $format ) {
			return new \WP_Error( 'thumbpress_already_' . $format, $config['already_message'] );
		}

		$base_dir    = $file_info['dirname'];
		$base_name   = $file_info['filename'];
		$target_path = $base_dir . '/' . $base_name . '.' . $format;

		// If a different-format source produced the same target (e.g. foo.jpg + foo.png both → foo.webp),
		// append -1, -2, ... so each source keeps its own converted output.
		if ( file_exists( $target_path ) ) {
			$unique_name = wp_unique_filename( $base_dir, $base_name . '.' . $format );
			$target_path = $base_dir . '/' . $unique_name;
		}

		// Raise memory limit for image ops (WP helper, respects WP_MAX_MEMORY_LIMIT).
		wp_raise_memory_limit( 'image' );

		// Skip if image too big to safely decode — width*height*4 bytes + overhead. Bailing before we
		// touch disk avoids a mid-encode fatal that would leave a half-written file behind.
		$dims = @getimagesize( $source );
		if ( is_array( $dims ) ) {
			$pixels       = (int) $dims[0] * (int) $dims[1];
			$needed_bytes = $pixels * 4 * 2; // Decoded buffer + working copy.
			$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
			$memory_usage = memory_get_usage( true );
			if ( $memory_limit > 0 && ( $memory_usage + $needed_bytes ) > $memory_limit ) {
				return new \WP_Error(
					'thumbpress_memory_limit',
					sprintf(
						/* translators: 1: estimated memory needed in MB, 2: current PHP memory limit in MB */
						__( 'Image is too large to convert within the server memory limit (needs ~%1$d MB, limit %2$d MB). Increase PHP memory_limit or WP_MAX_MEMORY_LIMIT and try again.', 'image-sizes' ),
						(int) ceil( $needed_bytes / MB_IN_BYTES ),
						(int) ceil( $memory_limit / MB_IN_BYTES )
					)
				);
			}
		}

		$editor = wp_get_image_editor( $source );
		if ( is_wp_error( $editor ) ) {
			return new \WP_Error(
				'thumbpress_editor_unavailable',
				sprintf(
					/* translators: %s: underlying image-editor error message */
					__( 'No image editor is available to process this file (%s).', 'image-sizes' ),
					$editor->get_error_message()
				)
			);
		}

		$result = $editor->save( $target_path, $mime );
		if ( is_wp_error( $result ) ) {
			return new \WP_Error(
				'thumbpress_save_failed',
				sprintf(
					$config['save_failed_message'],
					$result->get_error_message()
				)
			);
		}

		// Verify the saved file is actually the target format — some editors silently
		// fall back to the original format when the target is unsupported.
		$saved_path = $result['path'] ?? $target_path;
		$saved_mime = $result['mime-type'] ?? '';

		if ( $saved_mime && $saved_mime !== $mime ) {
			if ( file_exists( $saved_path ) ) {
				wp_delete_file( $saved_path );
			}
			return new \WP_Error(
				'thumbpress_' . $format . '_unsupported',
				$config['unsupported_message']
			);
		}

		return $saved_path;
	}

	/**
	 * Generate the URL for a converted file from its file path.
	 */
	public function generate_file_url( $file_path ) {
		$format     = $this->get_config()['format'];
		$file_path  = pathinfo( $file_path, PATHINFO_DIRNAME ) . '/' . pathinfo( $file_path, PATHINFO_FILENAME ) . '.' . $format;
		$upload_dir = wp_upload_dir();

		return str_replace(
			wp_normalize_path( $upload_dir['basedir'] ),
			$upload_dir['baseurl'],
			wp_normalize_path( $file_path )
		);
	}

	/**
	 * Clear the format-related stat caches.
	 */
	public function clear_caches() {
		$this->delete_cache( 'stat_not_webp' );
		$this->delete_cache( 'stat_not_avif' );
		$this->delete_cache( 'stat_unoptimized' );
	}
}
