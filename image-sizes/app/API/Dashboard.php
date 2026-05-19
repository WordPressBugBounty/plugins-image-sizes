<?php
namespace Thumbpress\API;

defined( 'ABSPATH' ) || exit;

use Thumbpress\Helpers\Utility;
use Thumbpress\Traits\Rest;
use Thumbpress\Traits\Cache;

class Dashboard {

	use Rest;
	use Cache;

	/**
	 * Get dashboard statistics.
	 */
	public function get_stats() {
		$stats = $this->compute_stats();
		return $this->response_success( $stats );
	}

	/**
	 * Recompute and cache all dashboard stats (called by Action Scheduler).
	 */
	public function build_cache() {
		$this->compute_stats();
	}

	private function compute_stats() {
		$total_images   = $this->get_total_images();
		$sizes_data     = $this->get_sizes_data();
		$unoptimized    = $this->get_unoptimized_count();
		$space_saved    = $this->get_space_saved();
		$not_compressed = $this->get_not_compressed();
		$not_webp       = $this->get_not_webp();
		$not_avif       = $this->get_not_avif();
		$duplicates     = $this->get_duplicate_count();
		$thumbnails     = $this->count_total_thumbnails();
		$large_images   = $this->count_large_images();

		$stats = array(
			'total_images'       => $total_images,
			'total_sizes'        => $sizes_data['total_sizes'],
			'disabled_sizes'     => $sizes_data['disabled_sizes'],
			'unoptimized_images' => $unoptimized,
			'compressed'         => $total_images - $not_compressed,
			'not_compressed'     => $not_compressed,
			'not_webp'           => $not_webp,
			'not_avif'           => $not_avif,
			'total_space_saved'  => $space_saved,
			'total_thumbnails'   => $thumbnails,
			'unused_images'      => 0,
			'large_images'       => $large_images,
			'lazy_load'          => (bool) get_option( 'thumbpress_lazy_load', 0 ),
			'duplicate_images'   => $duplicates,
			'pro_active'         => apply_filters( 'thumbpress_is_pro_active', defined( 'THUMBPRESS_PRO_VERSION' ) ),
		);

		$stats = apply_filters( 'thumbpress_dashboard_stats', $stats, $total_images );

		$health_data           = $this->calculate_health_score( $stats );
		$stats['health_score'] = $health_data['score'];
		$stats['health_issue'] = $health_data['issue'];
		$stats['quick_facts']  = $this->build_quick_facts( $stats );

		return $stats;
	}

	private function get_total_images() {
		$cached = $this->get_cache( 'stat_total_images' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$value = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'attachment'
			 AND post_mime_type LIKE 'image/%'
			 AND post_status = 'inherit'"
		);

		$this->set_cache( 'stat_total_images', $value, 6 * HOUR_IN_SECONDS, true );
		return $value;
	}

	private function get_sizes_data() {
		$cached = $this->get_cache( 'stat_sizes_data' );
		if ( false !== $cached ) {
			return $cached;
		}

		$registered = array_flip( get_intermediate_image_sizes() );

		global $_wp_additional_image_sizes;
		if ( ! empty( $_wp_additional_image_sizes ) ) {
			foreach ( array_keys( $_wp_additional_image_sizes ) as $size ) {
				$registered[ $size ] = true;
			}
		}

		$threshold = get_option( 'big_image_size_threshold', 2560 );
		if ( ! empty( $threshold ) ) {
			$registered['scaled'] = true;
		}

		$all_size_names = array_keys( $registered );
		$total_sizes    = count( $all_size_names );
		$disabled_data  = get_option( 'prevent_image_sizes', array() );
		$disabled_list  = isset( $disabled_data['disables'] ) ? $disabled_data['disables'] : array();
		$disabled_count = count( array_intersect( $disabled_list, $all_size_names ) );

		$value = array(
			'total_sizes'    => $total_sizes,
			'disabled_sizes' => $disabled_count,
		);

		$this->set_cache( 'stat_sizes_data', $value, 6 * HOUR_IN_SECONDS );
		return $value;
	}

	private function get_unoptimized_count() {
		$cached = $this->get_cache( 'stat_unoptimized' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$value = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
			 WHERE p.post_type = 'attachment'
			 AND p.post_mime_type LIKE 'image/%'
			 AND p.post_mime_type NOT IN ('image/webp', 'image/avif')
			 AND p.post_status = 'inherit'
			 AND NOT EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} pm
				WHERE pm.post_id = p.ID
				AND pm.meta_key = '_thumbpress_optimized'
			 )"
		);

		$this->set_cache( 'stat_unoptimized', $value, 6 * HOUR_IN_SECONDS, true );
		return $value;
	}

	private function get_space_saved() {
		return (int) get_option( 'thumbpress_space_saved', 0 );
	}

	private function get_not_compressed() {
		$cached = $this->get_cache( 'stat_not_compressed' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$value = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
			 WHERE p.post_type = 'attachment'
			 AND p.post_mime_type LIKE 'image/%'
			 AND p.post_status = 'inherit'
			 AND NOT EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} pm
				WHERE pm.post_id = p.ID
				AND pm.meta_key = '_thumbpress_optimized'
			 )"
		);

		$this->set_cache( 'stat_not_compressed', $value, 6 * HOUR_IN_SECONDS, true );
		return $value;
	}

	private function get_not_webp() {
		$cached = $this->get_cache( 'stat_not_webp' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$value = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'attachment'
			 AND post_mime_type LIKE 'image/%'
			 AND post_mime_type != 'image/webp'
			 AND post_status = 'inherit'"
		);

		$this->set_cache( 'stat_not_webp', $value, 6 * HOUR_IN_SECONDS, true );
		return $value;
	}

	private function get_not_avif() {
		$cached = $this->get_cache( 'stat_not_avif' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$value = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'attachment'
			 AND post_mime_type LIKE 'image/%'
			 AND post_mime_type != 'image/avif'
			 AND post_status = 'inherit'"
		);

		$this->set_cache( 'stat_not_avif', $value, 6 * HOUR_IN_SECONDS, true );
		return $value;
	}

	private function get_duplicate_count() {
		$cached = $this->get_cache( 'stat_duplicates' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$value = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
             INNER JOIN (
                 SELECT pm2.meta_value
                 FROM {$wpdb->postmeta} pm2
                 INNER JOIN {$wpdb->posts} p2 ON pm2.post_id = p2.ID
                 WHERE pm2.meta_key = %s
                 AND p2.post_type = 'attachment'
                 AND p2.post_mime_type LIKE 'image/%'
                 AND p2.post_status != 'trash'
                 GROUP BY pm2.meta_value
                 HAVING COUNT(*) > 1
             ) AS dup_hashes ON pm.meta_value = dup_hashes.meta_value
             WHERE p.post_type = 'attachment'
             AND p.post_mime_type LIKE 'image/%'
             AND p.post_status != 'trash'",
				Utility::HASH_META_KEY,
				Utility::HASH_META_KEY
			)
		);

		$this->set_cache( 'stat_duplicates', $value, 6 * HOUR_IN_SECONDS, true );
		return $value;
	}

	/**
	 * Build Quick Facts: negatives first, positives after, max 3 items.
	 */
	private function build_quick_facts( $stats ) {
		$not_compressed  = (int) ( $stats['not_compressed'] ?? 0 );
		$large_images    = (int) ( $stats['large_images'] ?? 0 );
		$not_webp        = (int) ( $stats['not_webp'] ?? 0 );
		$not_avif        = (int) ( $stats['not_avif'] ?? 0 );
		$duplicates      = (int) ( $stats['duplicate_images'] ?? 0 );
		$webp_avif_count = min( $not_webp, $not_avif );

		$all = array(
			array(
				'ok'     => $not_compressed === 0,
				'weight' => 25,
				'text'   => $not_compressed > 0
					? number_format_i18n( $not_compressed ) . ' Images Needs Compression'
					: 'All Images Compressed',
			),
			array(
				'ok'     => $large_images === 0,
				'weight' => 25,
				'text'   => $large_images > 0
					? number_format_i18n( $large_images ) . ' Images (Over 1 MB)'
					: 'No Images Over 1 MB',
			),
			array(
				'ok'     => $webp_avif_count === 0,
				'weight' => 8,
				'text'   => $webp_avif_count > 0
					? number_format_i18n( $webp_avif_count ) . ' Non-WebP/AVIF Images Found'
					: 'All Images in WebP or AVIF Format',
			),
			array(
				'ok'     => $duplicates === 0,
				'weight' => 10,
				'text'   => $duplicates > 0
					? number_format_i18n( $duplicates ) . ' Duplicate Images Found'
					: 'No Duplicate Images Found',
			),
		);

		$all            = apply_filters( 'thumbpress_dashboard_quick_facts', $all, $stats );
		$negatives      = array_values(
			array_filter(
				$all,
				function ( $f ) {
					return ! $f['ok'];
				}
			)
		);
		$positives      = array_values(
			array_filter(
				$all,
				function ( $f ) {
					return $f['ok'];
				}
			)
		);
		$sort_by_weight = function ( $a, $b ) {
			return $b['weight'] - $a['weight'];
		};
		usort( $negatives, $sort_by_weight );
		usort( $positives, $sort_by_weight );

		return array_slice( array_merge( $negatives, $positives ), 0, 3 );
	}

	/**
	 * Count images with file size over 1 MB.
	 */
	private function count_large_images() {
		$cached = $this->get_cache( 'stat_large_images' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$attachment_ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts}
         WHERE post_type = 'attachment'
         AND post_mime_type LIKE 'image/%'
         AND post_status = 'inherit'"
		);

		if ( empty( $attachment_ids ) ) {
			$this->set_cache( 'stat_large_images', 0, 6 * HOUR_IN_SECONDS, true );
			return 0;
		}

		$threshold = 1024 * 1024;
		$count     = 0;

		foreach ( $attachment_ids as $id ) {
			$file = function_exists( 'wp_get_original_image_path' ) ? wp_get_original_image_path( $id ) : false;
			if ( ! $file ) {
				$file = get_attached_file( $id, true );
			}
			if ( $file && file_exists( $file ) && filesize( $file ) > $threshold ) {
				++$count;
			}
		}

		$this->set_cache( 'stat_large_images', $count, 6 * HOUR_IN_SECONDS, true );
		return $count;
	}

	/**
	 * Count total generated thumbnails across all images.
	 */
	private function count_total_thumbnails() {
		$cached = $this->get_cache( 'stat_total_thumbnails' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$attachment_ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts}
         WHERE post_type = 'attachment'
         AND post_mime_type LIKE 'image/%'
         AND post_status = 'inherit'"
		);

		if ( empty( $attachment_ids ) ) {
			$this->set_cache( 'stat_total_thumbnails', 0, 6 * HOUR_IN_SECONDS, true );
			return 0;
		}

		global $wpdb;
		$count = 0;

		$metadata_list = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta}
				 WHERE post_id IN (" . implode( ',', array_map( 'absint', $attachment_ids ) ) . ")
				 AND meta_key = %s",
				'_wp_attachment_metadata'
			)
		);

		foreach ( $metadata_list as $row ) {
			$metadata = maybe_unserialize( $row->meta_value );
			if ( $metadata && ! empty( $metadata['sizes'] ) ) {
				$count += count( $metadata['sizes'] );
			}
		}

		$this->set_cache( 'stat_total_thumbnails', $count, 6 * HOUR_IN_SECONDS, true );
		return $count;
	}

	/**
	 * Calculate health score and primary issue.
	 *
	 * Weights (total 100%):
	 *   WebP/AVIF converted : 15%  (free)
	 *   Thumbnails enabled  : 15%  (free)
	 *   Compressed          : 25%  (free)
	 *   Large images < 1 MB : 25%  (free)
	 *   Duplicate images    : 10%  (free)
	 *   Unused images       : 10%  (pro)
	 */
	private function calculate_health_score( $stats ) {
		$total = (int) $stats['total_images'];

		if ( $total === 0 ) {
			return array(
				'score' => 100,
				'issue' => 'No images in media library',
			);
		}

		$unoptimized           = (int) $stats['unoptimized_images'];
		$not_compressed        = (int) $stats['not_compressed'];
		$large                 = (int) $stats['large_images'];
		$total_sizes           = (int) $stats['total_sizes'];
		$disabled_sizes        = (int) $stats['disabled_sizes'];
		$neither_webp_nor_avif = min( (int) $stats['not_webp'], (int) $stats['not_avif'] );
		$webp_avif_score       = min( 1.0, ( $total - $neither_webp_nor_avif ) / $total ) * 15;
		$thumbnail_score       = $total_sizes > 0 ? min( 1.0, $disabled_sizes / $total_sizes ) * 15 : 15;
		$compress_score        = max( 0.0, min( 1.0, ( $total - $not_compressed ) / $total ) ) * 25;
		$large_score           = max( 0.0, min( 1.0, ( $total - $large ) / $total ) ) * 25;
		$duplicates            = (int) $stats['duplicate_images'];
		$duplicate_score       = max( 0.0, min( 1.0, ( $total - $duplicates ) / $total ) ) * 10;
		$c                     = isset( $stats['health_contributions'] ) ? $stats['health_contributions'] : array();
		$unused_score          = (float) ( $c['unused'] ?? 0 );

		$score = (int) round(
			$webp_avif_score +
			$thumbnail_score +
			$compress_score +
			$large_score +
			$duplicate_score +
			$unused_score
		);

		$score      = max( 0, min( 100, $score ) );
		$unused     = (int) ( $stats['unused_images'] ?? 0 );
		$duplicates = (int) ( $stats['duplicate_images'] ?? 0 );
		$issue      = 'All images optimized';

		if ( $unoptimized > 0 ) {
			$issue = sprintf( '%s images not in WebP/AVIF', number_format_i18n( $unoptimized ) );
		} elseif ( $not_compressed > 0 ) {
			$issue = sprintf( '%s uncompressed images', number_format_i18n( $not_compressed ) );
		} elseif ( $large > 0 ) {
			$issue = sprintf( '%s images over 1 MB', number_format_i18n( $large ) );
		} elseif ( isset( $stats['health_contributions'] ) ) {
			if ( $unused > 0 ) {
				$issue = sprintf( '%s unused images', number_format_i18n( $unused ) );
			} elseif ( $duplicates > 0 ) {
				$issue = sprintf( '%s duplicate images', number_format_i18n( $duplicates ) );
			}
		}

		return array(
			'score' => $score,
			'issue' => $issue,
		);
	}
}
