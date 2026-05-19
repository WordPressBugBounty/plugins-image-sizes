<?php
namespace Thumbpress\API;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Traits\Rest;

class Settings {

	use Rest;

	/**
	 * Get all plugin settings.
	 */
	public function get() {
		$image_max_size          = get_option( 'thumbpress_image_max_size', array() );
		$right_click_disable     = get_option( 'thumbpress_image_download_disable', 0 );
		$lazy_load               = get_option( 'thumbpress_lazy_load', 0 );
		$hotlink_protection      = get_option( 'thumbpress_hotlink_protection', 0 );
		$image_editor            = get_option( 'thumbpress_image_editor', 0 );
		$replace_images          = get_option( 'thumbpress_replace_images', 0 );
		$auto_set_featured_image = get_option( 'thumbpress_auto_set_featured_image', 0 );

		// WebP settings.
		$webp_on_upload      = get_option( 'thumbpress_webp_on_upload', 0 );
		$webp_single_convert = get_option( 'thumbpress_single_image_convert', 0 );
		$webp_file_formats   = get_option( 'thumbpress_convert_file_formats', array( 'jpeg', 'png', 'jpg' ) );

		// AVIF settings.
		$avif_on_upload      = get_option( 'thumbpress_avif_convert_on_upload', 0 );
		$avif_single_convert = get_option( 'thumbpress_avif_single_image_convert', 0 );

		// Social share settings.
		$social_share = get_option( 'thumbpress_social_share', array() );

		return $this->response_success(
			array(
				'php_upload_max'          => ini_get( 'upload_max_filesize' ),
				'right_click_disable'     => (bool) $right_click_disable,
				'lazy_load'               => (bool) $lazy_load,
				'hotlink_protection'      => (bool) $hotlink_protection,
				'image_editor'            => (bool) $image_editor,
				'replace_images'          => (bool) $replace_images,
				'max_size'                => isset( $image_max_size['max-size'] ) ? $image_max_size['max-size'] : '',
				'max_size_unit'           => isset( $image_max_size['max-size-unit'] ) ? $image_max_size['max-size-unit'] : 'KB',
				'max_width'               => isset( $image_max_size['max-width'] ) ? $image_max_size['max-width'] : '',
				'max_height'              => isset( $image_max_size['max-height'] ) ? $image_max_size['max-height'] : '',
				'webp_on_upload'          => (bool) $webp_on_upload,
				'webp_single_convert'     => (bool) $webp_single_convert,
				'webp_file_formats'       => (array) $webp_file_formats,
				'avif_on_upload'          => (bool) $avif_on_upload,
				'avif_single_convert'     => (bool) $avif_single_convert,
				'social_facebook'         => ! empty( $social_share['enable_fb_share_img'] ) && $social_share['enable_fb_share_img'] === 'on',
				'social_linkedin'         => ! empty( $social_share['enable_ln_share_img'] ) && $social_share['enable_ln_share_img'] === 'on',
				'social_twitter'          => ! empty( $social_share['enable_tw_share_img'] ) && $social_share['enable_tw_share_img'] === 'on',
				'social_pinterest'        => ! empty( $social_share['enable_pin_share_img'] ) && $social_share['enable_pin_share_img'] === 'on',
				'auto_set_featured_image' => (bool) $auto_set_featured_image,
			)
		);
	}

	/**
	 * Save plugin settings.
	 */
	public function save( $request ) {
		$right_click_disable = $request->get_param( 'right_click_disable' );
		$lazy_load           = $request->get_param( 'lazy_load' );
		$hotlink_protection  = $request->get_param( 'hotlink_protection' );
		$max_size            = $request->get_param( 'max_size' );
		$max_size_unit       = $request->get_param( 'max_size_unit' );
		$max_width           = $request->get_param( 'max_width' );
		$max_height          = $request->get_param( 'max_height' );

		// Right-click disable.
		if ( $right_click_disable !== null ) {
			update_option( 'thumbpress_image_download_disable', $right_click_disable ? 1 : 0 );
		}

		// Lazy load.
		if ( $lazy_load !== null ) {
			update_option( 'thumbpress_lazy_load', $lazy_load ? 1 : 0 );
		}

		// Hotlink protection.
		if ( $hotlink_protection !== null ) {
			$previous = get_option( 'thumbpress_hotlink_protection', 0 );
			update_option( 'thumbpress_hotlink_protection', $hotlink_protection ? 1 : 0 );

			if ( (bool) $previous !== (bool) $hotlink_protection ) {
				if ( $hotlink_protection ) {
					$this->add_hotlink_rules();
					$this->add_uploads_htaccess();
				} else {
					$this->remove_hotlink_rules();
					$this->remove_uploads_htaccess();
				}
				// Flush WordPress rewrite rules so /thumbpress-image/ pretty URL is written
				// to .htaccess (Apache) and registered for WordPress routing (Nginx).
				flush_rewrite_rules( true );
			}
		}

		// Pro settings (only save if pro is active).
		if ( defined( 'THUMBPRESS_PRO_VERSION' ) ) {
			$image_editor   = $request->get_param( 'image_editor' );
			$replace_images = $request->get_param( 'replace_images' );

			if ( $image_editor !== null ) {
				update_option( 'thumbpress_image_editor', $image_editor ? 1 : 0 );
			}

			if ( $replace_images !== null ) {
				update_option( 'thumbpress_replace_images', $replace_images ? 1 : 0 );
			}
		}

		// WebP settings.
		$webp_on_upload = $request->get_param( 'webp_on_upload' );
		if ( $webp_on_upload !== null ) {
			update_option( 'thumbpress_webp_on_upload', $webp_on_upload ? 1 : 0 );
		}

		$webp_single_convert = $request->get_param( 'webp_single_convert' );
		if ( $webp_single_convert !== null ) {
			update_option( 'thumbpress_single_image_convert', $webp_single_convert ? 1 : 0 );
		}

		$webp_file_formats = $request->get_param( 'webp_file_formats' );
		if ( $webp_file_formats !== null ) {
			$allowed           = array( 'jpeg', 'png', 'jpg', 'avif', 'gif' );
			$webp_file_formats = array_values( array_intersect( (array) $webp_file_formats, $allowed ) );
			update_option( 'thumbpress_convert_file_formats', $webp_file_formats );
		}

		// AVIF settings.
		$avif_on_upload = $request->get_param( 'avif_on_upload' );
		if ( $avif_on_upload !== null ) {
			update_option( 'thumbpress_avif_convert_on_upload', $avif_on_upload ? 1 : 0 );
		}

		$avif_single_convert = $request->get_param( 'avif_single_convert' );
		if ( $avif_single_convert !== null ) {
			update_option( 'thumbpress_avif_single_image_convert', $avif_single_convert ? 1 : 0 );
		}

		// Social share settings.
		$social_fields  = array(
			'social_facebook'  => 'enable_fb_share_img',
			'social_linkedin'  => 'enable_ln_share_img',
			'social_twitter'   => 'enable_tw_share_img',
			'social_pinterest' => 'enable_pin_share_img',
		);
		$social_share   = get_option( 'thumbpress_social_share', array() );
		$social_changed = false;
		foreach ( $social_fields as $param_key => $option_key ) {
			$value = $request->get_param( $param_key );
			if ( $value !== null ) {
				$social_share[ $option_key ] = $value ? 'on' : '';
				$social_changed              = true;
			}
		}
		if ( $social_changed ) {
			update_option( 'thumbpress_social_share', $social_share );
		}

		// Auto set featured image.
		$auto_set_featured_image = $request->get_param( 'auto_set_featured_image' );
		if ( $auto_set_featured_image !== null ) {
			update_option( 'thumbpress_auto_set_featured_image', $auto_set_featured_image ? 1 : 0 );
		}

		// Image size and dimension limits.
		if ( $max_size !== null || $max_width !== null || $max_height !== null || $max_size_unit !== null ) {
			$current = get_option( 'thumbpress_image_max_size', array() );

			if ( $max_size !== null ) {
				$current['max-size'] = sanitize_text_field( $max_size );
			}
			if ( $max_size_unit !== null ) {
				$current['max-size-unit'] = sanitize_text_field( $max_size_unit );
			}
			if ( $max_width !== null ) {
				$current['max-width'] = sanitize_text_field( $max_width );
			}
			if ( $max_height !== null ) {
				$current['max-height'] = sanitize_text_field( $max_height );
			}

			update_option( 'thumbpress_image_max_size', $current );
		}

		do_action( 'thumbpress_settings_saved' );

		return $this->response_success(
			array(
				'message' => __( 'Settings saved successfully.', 'image-sizes' ),
			)
		);
	}

	/**
	 * Add hotlink protection rules to .htaccess.
	 * Rules are inserted BEFORE the WordPress block so Apache processes them
	 * before static file serving — otherwise image files bypass the rules entirely.
	 */
	private function add_hotlink_rules() {
		$htaccess = ABSPATH . '.htaccess';

		if ( ! is_writable( $htaccess ) ) {
			return;
		}

		$site_url = wp_parse_url( home_url(), PHP_URL_HOST );
		$rules    = "# BEGIN ThumbPress Hotlink Protection\n";
		$rules   .= "<IfModule mod_rewrite.c>\n";
		$rules   .= "RewriteEngine On\n";
		$rules   .= "RewriteCond %{HTTP_REFERER} !^\$\n";
		$rules   .= 'RewriteCond %{HTTP_REFERER} !^https?://(www\\.)?' . preg_quote( $site_url, '/' ) . " [NC]\n";
		$rules   .= "RewriteCond %{REQUEST_URI} ^/wp-content/uploads/ [NC]\n";
		$rules   .= "RewriteRule \\.(jpg|jpeg|png|gif|webp|avif|svg)$ - [F,NC,L]\n";
		$rules   .= "</IfModule>\n";
		$rules   .= "# END ThumbPress Hotlink Protection\n";

		$content = file_get_contents( $htaccess );

		// Always remove existing rules first so we can re-insert in the correct position.
		$pattern = '/\n?# BEGIN ThumbPress Hotlink Protection.*?# END ThumbPress Hotlink Protection\n?/s';
		$content = preg_replace( $pattern, '', $content );

		// Insert BEFORE WordPress rules — static files are served before those rules run.
		if ( strpos( $content, '# BEGIN WordPress' ) !== false ) {
			$content = str_replace( '# BEGIN WordPress', $rules . "\n# BEGIN WordPress", $content );
		} else {
			$content = rtrim( $content ) . "\n\n" . $rules;
		}

		file_put_contents( $htaccess, $content );
	}

	/**
	 * Create/update wp-content/uploads/.htaccess with hotlink protection rules.
	 * Provides a second layer of Apache protection closer to the requested files.
	 */
	private function add_uploads_htaccess() {
		$upload_dir = wp_get_upload_dir();
		$htaccess   = trailingslashit( $upload_dir['basedir'] ) . '.htaccess';

		if ( ! is_writable( dirname( $htaccess ) ) ) {
			return;
		}

		$site_url = wp_parse_url( home_url(), PHP_URL_HOST );
		$rules    = "# BEGIN ThumbPress Hotlink Protection\n";
		$rules   .= "<IfModule mod_rewrite.c>\n";
		$rules   .= "RewriteEngine On\n";
		$rules   .= "RewriteCond %{HTTP_REFERER} !^\$\n";
		$rules   .= 'RewriteCond %{HTTP_REFERER} !^https?://(www\\.)?' . preg_quote( $site_url, '/' ) . " [NC]\n";
		$rules   .= "RewriteRule \\.(jpg|jpeg|png|gif|webp|avif|svg)$ - [F,NC,L]\n";
		$rules   .= "</IfModule>\n";
		$rules   .= "# END ThumbPress Hotlink Protection\n";

		$content = file_exists( $htaccess ) ? file_get_contents( $htaccess ) : '';

		$pattern = '/\n?# BEGIN ThumbPress Hotlink Protection.*?# END ThumbPress Hotlink Protection\n?/s';
		$content = preg_replace( $pattern, '', $content );
		$content = $rules . ltrim( $content );

		file_put_contents( $htaccess, $content );
	}

	/**
	 * Remove ThumbPress rules from wp-content/uploads/.htaccess.
	 */
	private function remove_uploads_htaccess() {
		$upload_dir = wp_get_upload_dir();
		$htaccess   = trailingslashit( $upload_dir['basedir'] ) . '.htaccess';

		if ( ! file_exists( $htaccess ) || ! is_writable( $htaccess ) ) {
			return;
		}

		$content = file_get_contents( $htaccess );
		$pattern = '/\n?# BEGIN ThumbPress Hotlink Protection.*?# END ThumbPress Hotlink Protection\n?/s';
		$content = preg_replace( $pattern, '', $content );

		if ( '' === trim( $content ) ) {
			wp_delete_file( $htaccess );
		} else {
			file_put_contents( $htaccess, $content );
		}
	}

	/**
	 * Remove hotlink protection rules from .htaccess.
	 */
	private function remove_hotlink_rules() {
		$htaccess = ABSPATH . '.htaccess';

		if ( ! is_writable( $htaccess ) ) {
			return;
		}

		$content = file_get_contents( $htaccess );
		$pattern = '/\n?# BEGIN ThumbPress Hotlink Protection.*?# END ThumbPress Hotlink Protection\n?/s';
		$content = preg_replace( $pattern, '', $content );

		file_put_contents( $htaccess, $content );
	}
}
