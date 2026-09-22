<?php
/**
 * Compression check: compress a few throwaway copies of the library's own images and turn the
 * result into an estimate of what Pro's compression would save. The library is never written to.
 *
 * A run is started, then stepped one sample per request so a slow host never has to fit every
 * sample into a single request, and the screen can show real progress.
 */

namespace Codexpert\ThumbPress\API;

defined( 'ABSPATH' ) || exit;

use Codexpert\ThumbPress\Models\Hash_Index;
use Codexpert\ThumbPress\Traits\Rest;

class Compression_Check {

	use Rest;

	/**
	 * The last finished check, shown on the dashboard until the next one.
	 */
	const RESULT_OPTION = 'thumbpress_compression_check';

	/**
	 * The run in progress: its plan and the samples measured so far.
	 */
	const RUN_OPTION = 'thumbpress_compression_check_run';

	/**
	 * Name of this site's preview folder inside uploads.
	 */
	const DIR_OPTION = 'thumbpress_compression_check_dir';

	/**
	 * Pro's own setting, so the check tests the level a buyer will actually get.
	 */
	const LEVEL_OPTION = 'thumbpress_compress_quality';

	/**
	 * Pro's default "Compression Percentage".
	 */
	const DEFAULT_LEVEL = 80;

	/**
	 * Samples per check, the same ceiling as Pro's preview.
	 */
	const SAMPLES = 5;

	/**
	 * Other candidates tried when a sample cannot be read or decoded.
	 */
	const RETRIES = 2;

	/**
	 * Seconds a step may spend on replacements, the same budget as the batch jobs.
	 */
	const TIME_BUDGET = 15;

	/**
	 * At or above both thresholds the card leads with the one-off saving; below either, it
	 * leads with what Pro keeps saving (thumbnails, new uploads). Both lead to the upgrade.
	 */
	const WORTH_PCT   = 5;
	const WORTH_BYTES = 1048576;

	/**
	 * The formats the check covers, grouped the way they compress.
	 */
	const GROUPS = array(
		'image/jpeg' => 'jpeg',
		'image/jpg'  => 'jpeg',
		'image/png'  => 'png',
		'image/webp' => 'webp',
	);

	/**
	 * Files the preview folder keeps between runs.
	 */
	const GUARD_FILES = array( '.htaccess', 'web.config', 'index.html' );

	/**
	 * Plan a new check, discarding the previews a previous one left behind.
	 */
	public function start() {
		if ( ! Hash_Index::is_ready() ) {
			return $this->response_error( array( 'message' => __( 'Run the library scan first, so the estimate covers every image.', 'image-sizes' ) ) );
		}

		$groups = $this->supported_groups();

		if ( ! $groups ) {
			return $this->response_error( array( 'message' => __( 'Your server has no image editor that can re-save JPEG, PNG or WebP files, so the check cannot run.', 'image-sizes' ) ) );
		}

		$this->discard_previews();

		$library = $this->library( $groups );
		$plan    = $this->plan( $library );

		if ( ! $plan ) {
			$message = array_sum( wp_list_pluck( $library, 'images' ) ) > 0
				? __( 'None of your images have a recorded file size yet. Run the library scan again, then retry.', 'image-sizes' )
				: __( 'There are no uncompressed JPEG, PNG or WebP images to test.', 'image-sizes' );

			return $this->response_error( array( 'message' => $message ) );
		}

		$run = array(
			'token'   => wp_generate_password( 20, false, false ),
			'level'   => $this->level(),
			'plan'    => $plan,
			'samples' => array(),
			'library' => $library,
		);

		update_option( self::RUN_OPTION, $run, false );

		return $this->response_success(
			array(
				'token'   => $run['token'],
				'planned' => count( $plan ),
			)
		);
	}

	/**
	 * Measure one planned sample; the last step also writes the result.
	 *
	 * @param \WP_REST_Request $request Carries `token` and `index`.
	 */
	public function step( $request ) {
		$run   = get_option( self::RUN_OPTION );
		$token = (string) $request->get_param( 'token' );
		$index = absint( $request->get_param( 'index' ) );

		if ( ! is_array( $run ) || empty( $run['token'] ) || ! hash_equals( $run['token'], $token ) || ! isset( $run['plan'][ $index ] ) ) {
			return $this->response_error( array( 'message' => __( 'This check has expired. Start it again.', 'image-sizes' ) ) );
		}

		$planned  = $run['plan'][ $index ];
		$quality  = self::level_to_quality( $run['level'] );
		$sample   = null;

		// Planned IDs and replacements already measured, so no image is sampled twice.
		$tried = array_merge( wp_list_pluck( $run['plan'], 'id' ), wp_list_pluck( $run['samples'], 'id' ) );
		$id       = (int) $planned['id'];
		$deadline = microtime( true ) + self::TIME_BUDGET;

		// A replacement is only tried while there is time left; the first attempt always runs.
		for ( $attempt = 0; $attempt <= self::RETRIES && $id && ( 0 === $attempt || microtime( true ) < $deadline ); $attempt++ ) {
			$sample = $this->measure( $id, $planned['group'], $quality );

			if ( $sample ) {
				break;
			}

			$tried[] = $id;
			$id      = $this->next_candidate( $planned['group'], $id, $tried );
		}

		// Keyed by step, so a retried request replaces its sample instead of counting it twice.
		unset( $run['samples'][ $index ] );

		if ( $sample ) {
			$run['samples'][ $index ] = $sample;
		}

		$done = $index + 1 >= count( $run['plan'] );

		if ( ! $done ) {
			update_option( self::RUN_OPTION, $run, false );

			return $this->response_success(
				array(
					'index'  => $index,
					'done'   => false,
					'sample' => $sample ? $this->present_sample( $sample ) : null,
				)
			);
		}

		delete_option( self::RUN_OPTION );

		// With nothing measured there is no estimate to show, so say so instead of showing zero.
		if ( ! $run['samples'] ) {
			return $this->response_error( array( 'message' => __( 'None of the sampled images could be read, so there is nothing to estimate from. Check that the files still exist in your uploads folder.', 'image-sizes' ) ) );
		}

		$result = $this->aggregate( $run );

		update_option( self::RESULT_OPTION, $result, false );

		return $this->response_success(
			array(
				'index'   => $index,
				'done'    => true,
				'sample'  => $sample ? $this->present_sample( $sample ) : null,
				'samples' => array_map( array( $this, 'present_sample' ), array_values( $run['samples'] ) ),
				'result'  => $result,
			)
		);
	}

	/**
	 * Stream one preview copy to an admin; the folder itself is closed to direct requests.
	 *
	 * @param \WP_REST_Request $request Carries `file`.
	 */
	public function preview( $request ) {
		$path = $this->preview_path( (string) $request->get_param( 'file' ) );

		if ( '' === $path ) {
			return new \WP_Error( 'thumbpress_preview_not_found', __( 'Preview not found.', 'image-sizes' ), array( 'status' => 404 ) );
		}

		$type = wp_check_filetype( $path );

		header( 'Content-Type: ' . $type['type'] );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a response body, not reading it into memory.
		exit;
	}

	/**
	 * Delete the preview copies once the screen is done with them.
	 */
	public function discard() {
		$this->discard_previews();

		return $this->response_success();
	}

	/**
	 * The last finished check, or null when none has run.
	 *
	 * @return array|null
	 */
	public static function get_result() {
		$result = get_option( self::RESULT_OPTION );

		return is_array( $result ) ? $result : null;
	}

	/**
	 * Pro's percentage-to-quality mapping, kept identical so the estimate matches what Pro does.
	 *
	 * @param int $level Compression percentage.
	 * @return int JPEG/WebP quality.
	 */
	public static function level_to_quality( $level ) {
		$level = max( 10, min( 100, (int) $level ) );

		return max( 10, 100 - $level );
	}

	/**
	 * Absolute path of a preview copy, or an empty string when the name is not one of ours.
	 *
	 * @param string $file Preview file name.
	 * @return string
	 */
	public function preview_path( $file ) {
		if ( ! preg_match( '/^[A-Za-z0-9]{20}\.(jpe?g|png|webp)$/', $file ) ) {
			return '';
		}

		$dir = $this->existing_dir();

		if ( '' === $dir ) {
			return '';
		}

		// Both sides resolved, so a symlinked uploads folder still matches.
		$root = realpath( $dir );
		$path = realpath( $dir . '/' . $file );

		if ( false === $root || false === $path || 0 !== strpos( wp_normalize_path( $path ), trailingslashit( wp_normalize_path( $root ) ) ) || ! is_file( $path ) ) {
			return '';
		}

		return $path;
	}

	/**
	 * Aggregate the measured samples into a library-wide estimate.
	 *
	 * Each format's saving is the lower median of its samples, applied only to that format's bytes,
	 * and every figure is rounded down: an estimate that oversells comes back as a refund.
	 *
	 * @param array $run The finished run.
	 * @return array
	 */
	public function aggregate( array $run ) {
		$ratios = array();

		foreach ( $run['samples'] as $sample ) {
			$ratios[ $sample['group'] ][] = $sample['before'] > 0 ? max( 0, $sample['before'] - $sample['after'] ) / $sample['before'] : 0;
		}

		$saved      = 0;
		$measured   = 0;
		$images     = 0;
		$unmeasured = 0;
		$excluded   = false;
		$groups     = array();

		foreach ( $run['library'] as $group => $stats ) {
			$unmeasured += $stats['unmeasured'];

			if ( empty( $ratios[ $group ] ) ) {
				$excluded = $excluded || $stats['bytes'] > 0;
				continue;
			}

			$ratio = self::lower_median( $ratios[ $group ] );

			$saved    += (int) floor( $ratio * $stats['bytes'] );
			$measured += $stats['bytes'];
			$images   += $stats['images'] - $stats['unmeasured'];

			$groups[ $group ] = array(
				'images'  => $stats['images'] - $stats['unmeasured'],
				'bytes'   => $stats['bytes'],
				'samples' => count( $ratios[ $group ] ),
				'ratio'   => $ratio,
			);
		}

		$pct = $measured > 0 ? (int) floor( $saved * 100 / $measured ) : 0;

		return array(
			'saved_bytes'     => $saved,
			'saved_pct'       => $pct,
			'library_bytes'   => $measured,
			'image_count'     => $images,
			'unmeasured'      => $unmeasured,
			'samples_tested'  => count( $run['samples'] ),
			'samples_planned' => count( $run['plan'] ),
			'level'           => (int) $run['level'],
			'quality'         => self::level_to_quality( $run['level'] ),
			'approximate'     => $excluded || $unmeasured > 0 || count( $run['samples'] ) < count( $run['plan'] ),
			'worth'           => $pct >= self::WORTH_PCT && $saved >= self::WORTH_BYTES,
			'groups'          => $groups,
			'measured_at'     => time(),
		);
	}

	/**
	 * Lower median, so an even sample count never rounds the estimate up.
	 *
	 * @param float[] $values
	 * @return float
	 */
	public static function lower_median( array $values ) {
		sort( $values );

		return (float) $values[ (int) floor( ( count( $values ) - 1 ) / 2 ) ];
	}

	/**
	 * Uncompressed candidates per format: how many, their measured bytes, and how many lack a size.
	 *
	 * @param string[] $groups Groups the image editor can re-save.
	 * @return array
	 */
	public function library( array $groups ) {
		global $wpdb;

		$mimes = array_keys( array_intersect( self::GROUPS, $groups ) );
		$in    = implode( ',', array_fill( 0, count( $mimes ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders -- $in holds only %s placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_mime_type AS mime,
				        COUNT(*) AS images,
				        SUM( CAST( sz.meta_value AS UNSIGNED ) ) AS kb,
				        SUM( sz.meta_value IS NULL ) AS unmeasured
				 FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} sz ON sz.post_id = p.ID AND sz.meta_key = '_thumbpress_file_size'
				 WHERE p.post_type = 'attachment'
				 AND p.post_status = 'inherit'
				 AND p.post_mime_type IN ( {$in} )
				 AND NOT EXISTS (
					SELECT 1 FROM {$wpdb->postmeta} o
					WHERE o.post_id = p.ID
					AND o.meta_key IN ( '_thumbpress_optimized', '_thumbpress_optimize_failed' )
				 )
				 GROUP BY p.post_mime_type",
				$mimes
			),
			ARRAY_A
		);
		// phpcs:enable

		$library = array();

		foreach ( (array) $rows as $row ) {
			// The IN () match ignores case, so a stored "image/JPEG" lands here too.
			$group = self::GROUPS[ strtolower( $row['mime'] ) ] ?? '';

			if ( '' === $group ) {
				continue;
			}

			if ( ! isset( $library[ $group ] ) ) {
				$library[ $group ] = array( 'images' => 0, 'bytes' => 0, 'unmeasured' => 0 );
			}

			$library[ $group ]['images']     += (int) $row['images'];
			$library[ $group ]['bytes']      += (int) $row['kb'] * 1024;
			$library[ $group ]['unmeasured'] += (int) $row['unmeasured'];
		}

		return $library;
	}

	/**
	 * Spread the samples across formats by their share of bytes, then across each format's
	 * upload history, rather than taking the newest images, which say little about old ones.
	 *
	 * @param array $library Output of library().
	 * @return array[] Each item: `group`, `id`.
	 */
	public function plan( array $library ) {
		$total = array_sum( wp_list_pluck( $library, 'bytes' ) );

		if ( $total <= 0 ) {
			return array();
		}

		// Largest-remainder split of the samples by byte share.
		$counts    = array();
		$remainder = array();

		foreach ( $library as $group => $stats ) {
			$exact               = self::SAMPLES * $stats['bytes'] / $total;
			$counts[ $group ]    = (int) floor( $exact );
			$remainder[ $group ] = $exact - $counts[ $group ];
		}

		arsort( $remainder );

		foreach ( array_keys( $remainder ) as $group ) {
			if ( array_sum( $counts ) >= self::SAMPLES ) {
				break;
			}

			if ( $library[ $group ]['bytes'] > 0 ) {
				++$counts[ $group ];
			}
		}

		$plan = array();

		foreach ( array_filter( $counts ) as $group => $count ) {
			foreach ( $this->spread( $group, $count ) as $id ) {
				$plan[] = array(
					'group' => $group,
					'id'    => $id,
				);
			}
		}

		return $plan;
	}

	/**
	 * Candidate IDs at evenly spaced points of a format's ID range; each is a primary-key seek.
	 *
	 * @param string $group
	 * @param int    $count
	 * @return int[]
	 */
	private function spread( $group, $count ) {
		global $wpdb;

		list( $where, $args ) = $this->candidate_where( $group );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders -- $where is built from placeholders only.
		$range = $wpdb->get_row( $wpdb->prepare( "SELECT MIN( p.ID ) AS lo, MAX( p.ID ) AS hi FROM {$wpdb->posts} p WHERE {$where}", $args ) );

		$ids  = array();
		$last = 0;

		for ( $i = 0; $range && $range->lo && $i < $count; $i++ ) {
			$target = (int) $range->lo + (int) floor( ( $i + 0.5 ) * ( $range->hi - $range->lo ) / $count );
			$id     = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT p.ID FROM {$wpdb->posts} p WHERE {$where} AND p.ID >= %d ORDER BY p.ID ASC LIMIT 1",
					array_merge( $args, array( max( $target, $last + 1 ) ) )
				)
			);

			if ( ! $id ) {
				break;
			}

			$ids[] = $id;
			$last  = $id;
		}
		// phpcs:enable

		return $ids;
	}

	/**
	 * The next untried candidate of the same format after a sample that could not be read.
	 *
	 * @param string $group
	 * @param int    $after
	 * @param int[]  $tried
	 * @return int 0 when there is none.
	 */
	private function next_candidate( $group, $after, array $tried ) {
		global $wpdb;

		list( $where, $args ) = $this->candidate_where( $group );

		$tried = array_map( 'intval', $tried ) ?: array( 0 );
		$not   = implode( ',', array_fill( 0, count( $tried ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders -- $where and $not are built from placeholders only.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p WHERE {$where} AND p.ID > %d AND p.ID NOT IN ( {$not} ) ORDER BY p.ID ASC LIMIT 1",
				array_merge( $args, array( (int) $after ), $tried )
			)
		);
		// phpcs:enable
	}

	/**
	 * WHERE clause for the images Pro's bulk run would still compress, in one format.
	 *
	 * @param string $group
	 * @return array { string $where, array $args }
	 */
	private function candidate_where( $group ) {
		global $wpdb;

		$mimes = array_keys( self::GROUPS, $group, true );
		$in    = implode( ',', array_fill( 0, count( $mimes ), '%s' ) );

		$where = "p.post_type = 'attachment'
			AND p.post_status = 'inherit'
			AND p.post_mime_type IN ( {$in} )
			AND NOT EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} o
				WHERE o.post_id = p.ID
				AND o.meta_key IN ( '_thumbpress_optimized', '_thumbpress_optimize_failed' )
			)";

		return array( $where, $mimes );
	}

	/**
	 * Compress a copy of one original the way Pro's fallback does, and measure it.
	 *
	 * @param int    $id
	 * @param string $group
	 * @param int    $quality
	 * @return array|null Null when the file cannot be read, decoded or saved.
	 */
	public function measure( $id, $group, $quality ) {
		// Pro compresses the original, so the check does too.
		$source = wp_get_original_image_path( $id );

		if ( ! $source || ! is_readable( $source ) ) {
			return null;
		}

		if ( ! $this->fits_in_memory( $source ) ) {
			return null;
		}

		$dir = $this->dir();

		if ( '' === $dir ) {
			return null;
		}

		$extension = strtolower( (string) pathinfo( $source, PATHINFO_EXTENSION ) );
		$copy      = $dir . '/' . wp_generate_password( 20, false, false ) . '.' . $extension;

		if ( ! preg_match( '/^(jpe?g|png|webp)$/', $extension ) || ! copy( $source, $copy ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- WP_Filesystem is not loaded in REST requests on every host.
			return null;
		}

		$before = (int) filesize( $source );
		$editor = wp_get_image_editor( $copy );

		if ( is_wp_error( $editor ) ) {
			wp_delete_file( $copy );
			return null;
		}

		$editor->set_quality( $quality );
		$saved = $editor->save( $copy );

		if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! is_file( $saved['path'] ) ) {
			wp_delete_file( $copy );
			return null;
		}

		// An `image_editor_output_format` filter (e.g. Performance Lab) makes save() write a
		// different file and leave this one alone. Pro's fallback does the same, so it saves
		// nothing there; the check must not count the other file as a saving.
		if ( wp_normalize_path( $saved['path'] ) !== wp_normalize_path( $copy ) ) {
			wp_delete_file( $saved['path'] );
		}

		clearstatcache( true, $copy );

		// Pro keeps the original bytes when compressing would not shrink the file.
		$after = min( $before, (int) filesize( $copy ) );

		return array(
			'id'     => (int) $id,
			'group'  => $group,
			'file'   => basename( $copy ),
			'before' => $before,
			'after'  => $after,
		);
	}

	/**
	 * The sample as the screen needs it: names, sizes, and links to both versions.
	 *
	 * @param array $sample
	 * @return array
	 */
	public function present_sample( array $sample ) {
		return array(
			'id'          => $sample['id'],
			'name'        => get_the_title( $sample['id'] ),
			'before'      => $sample['before'],
			'after'       => $sample['after'],
			'saved_pct'   => $sample['before'] > 0 ? (int) floor( ( $sample['before'] - $sample['after'] ) * 100 / $sample['before'] ) : 0,
			'before_url'  => wp_get_original_image_url( $sample['id'] ),
			'after_url'   => add_query_arg(
				array(
					'file'     => $sample['file'],
					'_wpnonce' => wp_create_nonce( 'wp_rest' ),
				),
				rest_url( $this->namespace . '/compression-check/preview' )
			),
		);
	}

	/**
	 * Groups whose images the installed editor can load and re-save.
	 *
	 * @return string[]
	 */
	public function supported_groups() {
		$groups = array();

		foreach ( self::GROUPS as $mime => $group ) {
			if ( ! in_array( $group, $groups, true ) && wp_image_editor_supports( array( 'mime_type' => $mime ) ) ) {
				$groups[] = $group;
			}
		}

		return $groups;
	}

	/**
	 * Pro's compression percentage, or its default when Pro has never saved one.
	 *
	 * @return int
	 */
	private function level() {
		$level = (int) get_option( self::LEVEL_OPTION, self::DEFAULT_LEVEL );

		return $level > 0 ? max( 10, min( 100, $level ) ) : self::DEFAULT_LEVEL;
	}

	/**
	 * Whether decoding the image fits under the memory limit (GD decodes into PHP's heap).
	 *
	 * @param string $source
	 * @return bool
	 */
	private function fits_in_memory( $source ) {
		wp_raise_memory_limit( 'image' );

		$dims = wp_getimagesize( $source );

		if ( ! is_array( $dims ) ) {
			return false;
		}

		$decodes_in_php = ! function_exists( '_wp_image_editor_choose' ) || is_a(
			_wp_image_editor_choose( array( 'mime_type' => $dims['mime'] ) ),
			'WP_Image_Editor_GD',
			true
		);

		if ( ! $decodes_in_php ) {
			return true;
		}

		$needed = (int) $dims[0] * (int) $dims[1] * 4 * 2; // Decoded buffer + working copy.
		$limit  = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );

		return $limit <= 0 || memory_get_usage( true ) + $needed <= $limit;
	}

	/**
	 * This site's preview folder, created and closed to direct requests on first use.
	 *
	 * @return string Absolute path, or an empty string when it could not be created.
	 */
	private function dir() {
		$name = get_option( self::DIR_OPTION );

		if ( ! is_string( $name ) || ! preg_match( '/^thumbpress-check-[A-Za-z0-9]{20}$/', $name ) ) {
			// Unguessable, because .htaccess only closes the folder on Apache.
			$name = 'thumbpress-check-' . wp_generate_password( 20, false, false );
			update_option( self::DIR_OPTION, $name, false );
		}

		$dir = $this->uploads_basedir() . '/' . $name;

		if ( ! wp_mkdir_p( $dir ) ) {
			return '';
		}

		$files = array(
			'.htaccess'  => "# ThumbPress: compression check copies, not publicly reachable.\n"
				. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n",
			'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
				. "<configuration>\n\t<system.webServer>\n\t\t<authorization>\n"
				. "\t\t\t<deny users=\"*\" />\n\t\t</authorization>\n\t</system.webServer>\n</configuration>\n",
			'index.html' => '',
		);

		foreach ( $files as $file => $contents ) {
			if ( ! file_exists( $dir . '/' . $file ) ) {
				file_put_contents( $dir . '/' . $file, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP_Filesystem is not loaded in REST requests on every host.
			}
		}

		return $dir;
	}

	/**
	 * The preview folder if it already exists, without creating it.
	 *
	 * @return string
	 */
	private function existing_dir() {
		$name = get_option( self::DIR_OPTION );

		if ( ! is_string( $name ) || ! preg_match( '/^thumbpress-check-[A-Za-z0-9]{20}$/', $name ) ) {
			return '';
		}

		$dir = $this->uploads_basedir() . '/' . $name;

		return is_dir( $dir ) ? $dir : '';
	}

	/**
	 * Delete every preview copy, keeping the files that close the folder.
	 */
	private function discard_previews() {
		$dir = $this->existing_dir();

		if ( '' === $dir ) {
			return;
		}

		foreach ( array_diff( (array) scandir( $dir ), array_merge( array( '.', '..' ), self::GUARD_FILES ) ) as $entry ) {
			if ( is_file( $dir . '/' . $entry ) ) {
				wp_delete_file( $dir . '/' . $entry );
			}
		}
	}

	/**
	 * @return string
	 */
	private function uploads_basedir() {
		$uploads = wp_get_upload_dir();

		return wp_normalize_path( untrailingslashit( $uploads['basedir'] ) );
	}
}
