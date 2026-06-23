<?php
namespace Codexpert\ThumbPress\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Utility class with static helper functions for general use throughout the plugin.
 */
class Utility {

	const HASH_META_KEY = '_thumbpress_file_hash';
	const SIZE_META_KEY = '_thumbpress_file_size';

	/**
	 * Formats a date string according to WordPress settings.
	 *
	 * @param string $date The date string (e.g., 'Y-m-d H:i:s').
	 * @param string $format Optional. PHP date format. Defaults to WordPress date format setting.
	 * @return string Formatted date string.
	 */
	public static function format_date( $date, $format = '' ) {
		if ( empty( $format ) ) {
			$format = get_option( 'date_format' );
		}
		return date_i18n( $format, strtotime( $date ) );
	}

	/**
	 * Logs messages to a specific log file.
	 *
	 * @param mixed  $message The message to log. If not a string, it will be converted to JSON.
	 * @param string $log_file The log file to write to within the wp-content directory.
	 */
	public static function log_debug( $message, $log_file = 'debug.log' ) {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		if ( ! is_string( $message ) ) {
			$message = json_encode( $message );
		}

		$upload_dir = wp_upload_dir();
		$log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'image-sizes-logs/';
		if ( ! file_exists( $log_path = $log_dir . $log_file ) ) {
			$wp_filesystem->mkdir( dirname( $log_path ) );
			$wp_filesystem->put_contents( $log_path, '', FS_CHMOD_FILE );
		}

		$log_entry = sprintf( "[%s] %s\n", current_time( 'mysql' ), $message );

		$wp_filesystem->put_contents( $log_path, $log_entry, FS_CHMOD_FILE | FILE_APPEND );
	}

	/**
	 * Prints information about a variable in a more readable format.
	 *
	 * @param mixed $data The variable you want to display.
	 * @param bool  $admin_only Should it display in wp-admin area only
	 * @param bool  $hide_adminbar Should it hide the admin bar
	 */
	public static function pri( $data, $admin_only = true, $hide_adminbar = true ) {

		if ( $admin_only && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<pre>';
		if ( is_object( $data ) || is_array( $data ) ) {
			print_r( $data );
		} else {
			var_dump( $data );
		}
		echo '</pre>';

		if ( is_admin() && $hide_adminbar ) {
			echo '<style>#adminmenumain{display:none;}</style>';
		}
	}

	/**
	 * Includes a template file from the 'view' directory.
	 *
	 * @param string $template The template file name.
	 * @param array  $args Optional. An associative array of variables to pass to the template file.
	 */
	public static function get_template( $template, $args = array() ) {

		$extension = pathinfo( $template, PATHINFO_EXTENSION );
		if ( $extension !== 'php' ) {
			$template .= '.php';
		}

		$path = THUMBPRESS_PLUGIN_DIR . 'views/' . $template;

		if ( file_exists( $path ) ) {
			if ( ! empty( $args ) && is_array( $args ) ) {
				foreach ( $args as $key => $value ) {
					${$key} = $value;
				}
			}

			ob_start();

			include $path;

			return ob_get_clean();
		} else {
			error_log( 'Template file not found: ' . $path );
		}
	}

	/**
	 * @param bool $show_cached either to use a cached list of posts or not. If enabled, make sure to wp_cache_delete() with the `save_post` hook
	 */
	public static function get_posts( $args = array(), $show_heading = false, $show_cached = false ) {

		$defaults = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);

		$_args = wp_parse_args( $args, $defaults );

		// use cache
		if ( true === $show_cached && ( $cached_posts = wp_cache_get( "image-sizes_{$_args['post_type']}", 'image-sizes' ) ) ) {
			$posts = $cached_posts;
		}

		// don't use cache
		else {
			$queried = new \WP_Query( $_args );

			$posts = array();
			foreach ( $queried->posts as $post ) :
				$posts[ $post->ID ] = $post->post_title;
			endforeach;

			wp_cache_add( "image-sizes_{$_args['post_type']}", $posts, 'image-sizes', 3600 );
		}

		$posts = $show_heading ? array( '' => sprintf( __( '- Choose a %s -', 'image-sizes' ), $_args['post_type'] ) ) + $posts : $posts;

		return apply_filters( 'image-sizes_get_posts', $posts, $_args );
	}

	public static function get_option( $option, $section, $field, $default = '' ) {

		$key     = "image-sizes-{$option}-{$section}";
		$options = get_option( $key );

		if ( isset( $options[ $field ] ) ) {
			return $options[ $field ];
		}

		return $default;
	}

	/**
	 * Refresh _thumbpress_file_hash + _thumbpress_file_size after a file
	 * has been replaced (conversion, compression, replace, etc.).
	 *
	 * Always resolves to the ORIGINAL image (pre-scale) when WP has stored one,
	 * so the hash/size meta describes the original upload — not -scaled or other derivatives.
	 */
	public static function refresh_file_meta( $attachment_id, $file_path = '' ) {
		$original = function_exists( 'wp_get_original_image_path' ) ? wp_get_original_image_path( $attachment_id ) : false;

		if ( ! $original ) {
			$original = function_exists( 'get_attached_file' ) ? get_attached_file( $attachment_id, true ) : $file_path;
		}

		if ( ! $original || ! file_exists( $original ) ) {
			$original = $file_path;
		}

		if ( ! $original || ! file_exists( $original ) ) {
			return;
		}

		$hash = md5_file( $original );
		if ( $hash ) {
			update_post_meta( $attachment_id, self::HASH_META_KEY, $hash );
		}

		$file_size = filesize( $original );
		if ( $file_size !== false ) {
			update_post_meta( $attachment_id, self::SIZE_META_KEY, round( $file_size / 1024 ) );
		}

		do_action( 'thumbpress_file_meta_refreshed', $attachment_id );
	}

	/**
	 * Rewrite every stored reference to a just-converted/replaced attachment across
	 * post_content, postmeta and options. Call right after refresh_file_meta().
	 *
	 * @param int    $attachment_id
	 * @param string $old_main_path Absolute path of the original (unscaled) main file.
	 * @param array  $old_metadata  Attachment metadata captured BEFORE the swap.
	 * @return array { posts:int, postmeta:int, options:int }
	 */
	public static function replace_attachment_urls( $attachment_id, $old_main_path, $old_metadata = array() ) {
		$empty = array(
			'posts'    => 0,
			'postmeta' => 0,
			'options'  => 0,
		);

		$old_main_path = (string) $old_main_path;
		if ( '' === $old_main_path ) {
			return $empty;
		}

		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return $empty;
		}

		$basedir = wp_normalize_path( $uploads['basedir'] );
		$old_url = str_replace( $basedir, $uploads['baseurl'], wp_normalize_path( $old_main_path ) );

		// Strip any cache-busting query (?_tp_v=) the replace/editor filter may add.
		$new_url = strtok( (string) wp_get_attachment_url( $attachment_id ), '?' );
		if ( ! $new_url ) {
			return $empty;
		}

		$new_metadata = wp_get_attachment_metadata( $attachment_id );
		$old_sizes    = ( is_array( $old_metadata ) && ! empty( $old_metadata['sizes'] ) ) ? $old_metadata['sizes'] : array();
		$new_sizes    = ( is_array( $new_metadata ) && ! empty( $new_metadata['sizes'] ) ) ? $new_metadata['sizes'] : array();
		$old_original = ( is_array( $old_metadata ) && ! empty( $old_metadata['original_image'] ) ) ? $old_metadata['original_image'] : '';

		return self::replace_image_urls( $old_url, $new_url, $old_sizes, $new_sizes, $old_original );
	}

	/**
	 * Replace an image's old URLs with the new ones across post_content, postmeta and
	 * options. Serialized values are unserialized, rewritten and re-serialized so the
	 * byte counts stay valid; slash-escaped (JSON) URLs are handled too. Matching is
	 * exact string replacement on the known file URLs — no pattern guessing.
	 *
	 * @param string $old_url            Original (unscaled) main image URL.
	 * @param string $new_url            New main image URL.
	 * @param array  $old_sizes          Pre-swap metadata['sizes'] (exact old files).
	 * @param array  $new_sizes          Post-swap metadata['sizes'] (exact new files).
	 * @param string $old_original_image Pre-swap metadata['original_image'] basename.
	 * @return array { posts:int, postmeta:int, options:int }
	 */
	public static function replace_image_urls( $old_url, $new_url, $old_sizes = array(), $new_sizes = array(), $old_original_image = '' ) {
		global $wpdb;

		$summary = array(
			'posts'    => 0,
			'postmeta' => 0,
			'options'  => 0,
		);

		$old_url = trim( (string) $old_url );
		$new_url = trim( (string) $new_url );
		if ( '' === $old_url || '' === $new_url ) {
			return $summary;
		}

		$pairs = self::build_url_pairs( $old_url, $new_url, (array) $old_sizes, (array) $new_sizes, (string) $old_original_image );
		if ( empty( $pairs ) ) {
			return $summary;
		}

		list( $search, $replace ) = self::expand_replacement_pairs( $pairs );
		if ( empty( $search ) ) {
			return $summary;
		}

		// Candidate filter on the exact file basenames (main, -scaled, original, every
		// thumbnail) — specific enough to avoid broad full-table scans on short stems,
		// slash-free so it survives plain and slash-escaped storage; the exact
		// search/replace decides which rows actually change.
		$needles = array();
		foreach ( array_keys( $pairs ) as $from_path ) {
			$base = self::path_basename( $from_path );
			if ( '' !== $base ) {
				$needles[ $base ] = true;
			}
		}
		$needles = array_keys( $needles );
		if ( empty( $needles ) ) {
			return $summary;
		}

		list( $where, $params ) = self::build_like_filter( 'post_content', $needles );
		$rows                   = $wpdb->get_results(
			$wpdb->prepare( "SELECT ID, post_content FROM {$wpdb->posts} WHERE {$where}", $params )
		);
		foreach ( (array) $rows as $row ) {
			$updated = self::apply_replacements( $row->post_content, $search, $replace );
			if ( null !== $updated && $updated !== $row->post_content ) {
				$wpdb->update( $wpdb->posts, array( 'post_content' => $updated ), array( 'ID' => $row->ID ) );
				clean_post_cache( $row->ID );
				++$summary['posts'];
			}
		}

		list( $where, $params ) = self::build_like_filter( 'meta_value', $needles );
		$rows                   = $wpdb->get_results(
			$wpdb->prepare( "SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta} WHERE {$where}", $params )
		);
		foreach ( (array) $rows as $row ) {
			$updated = self::apply_replacements( $row->meta_value, $search, $replace );
			if ( null !== $updated && $updated !== $row->meta_value ) {
				$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $updated ), array( 'meta_id' => $row->meta_id ) );
				wp_cache_delete( $row->post_id, 'post_meta' );
				++$summary['postmeta'];
			}
		}

		list( $where, $params ) = self::build_like_filter( 'option_value', $needles );
		$rows                   = $wpdb->get_results(
			$wpdb->prepare( "SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE {$where}", $params )
		);
		$flush_alloptions = false;
		foreach ( (array) $rows as $row ) {
			$updated = self::apply_replacements( $row->option_value, $search, $replace );
			if ( null !== $updated && $updated !== $row->option_value ) {
				$wpdb->update( $wpdb->options, array( 'option_value' => $updated ), array( 'option_id' => $row->option_id ) );
				if ( ! empty( $row->option_name ) ) {
					wp_cache_delete( $row->option_name, 'options' );
					wp_cache_delete( $row->option_name, 'notoptions' );
				}
				$flush_alloptions = true;
				++$summary['options'];
			}
		}
		if ( $flush_alloptions ) {
			wp_cache_delete( 'alloptions', 'options' );
		}

		do_action( 'thumbpress_image_urls_replaced', $old_url, $new_url, $summary );

		return $summary;
	}

	/**
	 * Build the exact old-URL => new-URL map (main, -scaled, original and every
	 * thumbnail size) from the captured metadata. Only known files, no patterns.
	 */
	private static function build_url_pairs( $old_url, $new_url, $old_sizes, $new_sizes, $old_original_image ) {
		$old_path = self::url_path( $old_url );
		$new_path = self::url_path( $new_url );
		if ( '' === $old_path || '' === $new_path ) {
			return array();
		}

		$old_dir  = self::path_dirname( $old_path );
		$new_dir  = self::path_dirname( $new_path );
		$old_ext  = self::path_ext( $old_path );
		$new_ext  = self::path_ext( $new_path );
		$old_base = self::base_filename( $old_url );

		$pairs = array();

		// Main, its -scaled variant and the stored original all point at the new main.
		$pairs[ $old_path ] = $new_path;
		if ( '' !== $old_base && '' !== $old_ext ) {
			$pairs[ $old_dir . '/' . $old_base . '-scaled.' . $old_ext ] = $new_path;
		}
		if ( '' !== $old_original_image ) {
			$pairs[ $old_dir . '/' . $old_original_image ] = $new_path;
		}

		// Each generated thumbnail maps to its same-key new file, else an extension swap.
		foreach ( $old_sizes as $key => $size ) {
			if ( empty( $size['file'] ) ) {
				continue;
			}
			$from = $old_dir . '/' . $size['file'];
			if ( isset( $new_sizes[ $key ]['file'] ) && '' !== $new_sizes[ $key ]['file'] ) {
				$to = $new_dir . '/' . $new_sizes[ $key ]['file'];
			} else {
				$to = $new_dir . '/' . self::swap_ext( $size['file'], $new_ext );
			}
			$pairs[ $from ] = $to;
		}

		$clean = array();
		foreach ( $pairs as $from => $to ) {
			if ( '' !== $from && '' !== $to && $from !== $to ) {
				$clean[ $from ] = $to;
			}
		}

		return $clean;
	}

	/**
	 * Flatten path pairs into search/replace arrays, longest first, adding a
	 * slash-escaped (JSON) form of each so "\/wp-content\/..." is matched too.
	 */
	private static function expand_replacement_pairs( $pairs ) {
		uksort(
			$pairs,
			function ( $a, $b ) {
				return strlen( $b ) - strlen( $a );
			}
		);

		$search  = array();
		$replace = array();

		foreach ( $pairs as $from => $to ) {
			$search[]  = $from;
			$replace[] = $to;

			$from_json = str_replace( '/', '\\/', $from );
			$to_json   = str_replace( '/', '\\/', $to );
			if ( $from_json !== $from ) {
				$search[]  = $from_json;
				$replace[] = $to_json;
			}
		}

		return array( $search, $replace );
	}

	/**
	 * Apply the literal search/replace map to one raw column value, preserving
	 * serialization. Returns the new value, or null when nothing changed.
	 */
	private static function apply_replacements( $raw, $search, $replace ) {
		if ( ! is_string( $raw ) || '' === $raw ) {
			return null;
		}

		$changed = false;
		$value   = self::walk_replace( maybe_unserialize( $raw ), $search, $replace, $changed );

		return $changed ? maybe_serialize( $value ) : null;
	}

	/**
	 * Recursively str_replace inside unserialized data. Nested serialized strings are
	 * unwound rather than touched directly so their byte counts stay valid.
	 */
	private static function walk_replace( $data, $search, $replace, &$changed ) {
		if ( is_string( $data ) ) {
			if ( '' === $data ) {
				return $data;
			}

			if ( is_serialized( $data ) ) {
				$inner = maybe_unserialize( $data );
				if ( $inner !== $data ) {
					$sub   = false;
					$inner = self::walk_replace( $inner, $search, $replace, $sub );
					if ( $sub ) {
						$changed = true;
						return maybe_serialize( $inner );
					}
					return $data;
				}
			}

			$new = str_replace( $search, $replace, $data );
			if ( $new !== $data ) {
				$changed = true;
			}
			return $new;
		}

		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::walk_replace( $value, $search, $replace, $changed );
			}
			return $data;
		}

		if ( is_object( $data ) ) {
			if ( $data instanceof \__PHP_Incomplete_Class ) {
				return $data;
			}
			foreach ( get_object_vars( $data ) as $key => $value ) {
				$data->{$key} = self::walk_replace( $value, $search, $replace, $changed );
			}
			return $data;
		}

		return $data;
	}

	private static function url_path( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		return is_string( $path ) ? $path : '';
	}

	private static function path_dirname( $path ) {
		$pos = strrpos( $path, '/' );
		return false === $pos ? '' : substr( $path, 0, $pos );
	}

	private static function path_ext( $path ) {
		$dot = strrpos( $path, '.' );
		return false === $dot ? '' : substr( $path, $dot + 1 );
	}

	private static function path_basename( $path ) {
		$path = (string) $path;
		$pos  = strrpos( $path, '/' );
		return false === $pos ? $path : substr( $path, $pos + 1 );
	}

	private static function build_like_filter( $column, $needles ) {
		global $wpdb;

		$clauses = array();
		$params  = array();
		foreach ( $needles as $needle ) {
			$clauses[] = "{$column} LIKE %s";
			$params[]  = '%' . $wpdb->esc_like( $needle ) . '%';
		}

		return array( '(' . implode( ' OR ', $clauses ) . ')', $params );
	}

	private static function base_filename( $url ) {
		$path = self::url_path( $url );
		if ( '' === $path ) {
			$path = (string) $url;
		}
		$pos  = strrpos( $path, '/' );
		$base = false === $pos ? $path : substr( $path, $pos + 1 );
		$dot  = strrpos( $base, '.' );
		if ( false !== $dot ) {
			$base = substr( $base, 0, $dot );
		}
		if ( '-scaled' === substr( $base, -7 ) ) {
			$base = substr( $base, 0, -7 );
		}
		return $base;
	}

	private static function swap_ext( $filename, $new_ext ) {
		$dot = strrpos( $filename, '.' );
		return ( false === $dot ? $filename : substr( $filename, 0, $dot ) ) . '.' . $new_ext;
	}
}
