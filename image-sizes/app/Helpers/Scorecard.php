<?php
/**
 * The media health scorecard: four category scores and the letter grade.
 *
 * One class, so the dashboard, the setup wizard and the weekly email agree on every number.
 * The overall score is still the weighted health score `API\Dashboard::calculate_health_score()`
 * computes; this class only adds the four sub-scores and maps every score to a letter.
 */

namespace Codexpert\ThumbPress\Helpers;

defined( 'ABSPATH' ) || exit;

class Scorecard {

	const SPEED   = 'speed';
	const SEO     = 'seo';
	const STORAGE = 'storage';
	const PROTECT = 'protect';

	/**
	 * Score at or above which each letter is awarded, best first.
	 */
	const BANDS = array(
		'A' => 90,
		'B' => 80,
		'C' => 65,
		'D' => 50,
	);

	/**
	 * Letter for a 0-100 score. Letters only: no plus or minus, so it reads the same in every locale.
	 *
	 * @param int|float|null $score Score, or null when it could not be measured.
	 * @return string A-F, or an empty string for null.
	 */
	public static function grade( $score ) {
		if ( null === $score ) {
			return '';
		}

		foreach ( self::BANDS as $letter => $floor ) {
			if ( $score >= $floor ) {
				return $letter;
			}
		}

		return 'F';
	}

	/**
	 * Four category scores plus the letter for each.
	 *
	 * Speed, SEO and Storage read the scan's index, so they are null until there is data to score.
	 * Inputs that need a service we do not have yet (the LCP image, share-image coverage) carry no
	 * penalty rather than an invented one.
	 *
	 * @param array $stats {
	 *     @type int   $total_images
	 *     @type int   $large_images       Originals over 1 MB.
	 *     @type int   $not_compressed
	 *     @type int   $not_webp
	 *     @type int   $not_avif
	 *     @type bool  $lazy_load
	 *     @type int   $missing_alt
	 *     @type int   $bad_names          Filenames like IMG_4021 or a bare hash.
	 *     @type int   $reclaimable_bytes  Duplicates, likely-unused and the estimated compression saving.
	 *     @type int   $library_bytes      Size of every original.
	 *     @type bool  $hotlink
	 *     @type bool  $right_click
	 *     @type bool  $watermark          Pro.
	 *     @type bool  $watermark_available Whether any watermark tool exists to switch on.
	 *     @type bool  $scored             Whether the index has data for the first three categories.
	 * }
	 * @return array<string, array{score: int|null, grade: string}>
	 */
	public static function categories( array $stats ) {
		$scored = ! empty( $stats['scored'] ) && (int) ( $stats['total_images'] ?? 0 ) > 0;

		$scores = array(
			self::SPEED   => $scored ? self::speed( $stats ) : null,
			self::SEO     => $scored ? self::seo( $stats ) : null,
			self::STORAGE => $scored ? self::storage( $stats ) : null,
			self::PROTECT => self::protect( $stats ),
		);

		$out = array();

		foreach ( $scores as $key => $score ) {
			$out[ $key ] = array(
				'score' => $score,
				'grade' => self::grade( $score ),
			);
		}

		return $out;
	}

	/**
	 * 100 minus what the heavy, uncompressed and old-format images cost, and a missing lazy load.
	 *
	 * @param array $s Stats, see categories().
	 * @return int
	 */
	public static function speed( array $s ) {
		$total = max( 1, (int) $s['total_images'] );

		$large        = self::share( $s['large_images'] ?? 0, $total );
		$uncompressed = self::share( $s['not_compressed'] ?? 0, $total );
		$not_modern   = self::share( min( (int) ( $s['not_webp'] ?? 0 ), (int) ( $s['not_avif'] ?? 0 ) ), $total );

		return self::clamp( 100 - 40 * $large - 25 * $uncompressed - 20 * $not_modern - ( empty( $s['lazy_load'] ) ? 10 : 0 ) );
	}

	/**
	 * 100 minus what missing alt text and meaningless filenames cost.
	 *
	 * @param array $s Stats, see categories().
	 * @return int
	 */
	public static function seo( array $s ) {
		$total = max( 1, (int) $s['total_images'] );

		return self::clamp( 100 - 60 * self::share( $s['missing_alt'] ?? 0, $total ) - 25 * self::share( $s['bad_names'] ?? 0, $total ) );
	}

	/**
	 * The share of the library that is not needed, turned around so a leaner library scores higher.
	 *
	 * @param array $s Stats, see categories().
	 * @return int
	 */
	public static function storage( array $s ) {
		$library = (int) ( $s['library_bytes'] ?? 0 );

		if ( $library <= 0 ) {
			return 100;
		}

		return self::clamp( 100 - 100 * min( 1, max( 0, (int) ( $s['reclaimable_bytes'] ?? 0 ) ) / $library ) );
	}

	/**
	 * Points per control that is on: hotlink 50, right-click 25, watermark 25 (Pro).
	 *
	 * The watermark only counts once a watermark tool exists (`watermark_available`). Until then the
	 * score is out of the two controls that can be switched on, so a site that has both on is a full
	 * 100 rather than stuck at 75 for a feature nobody can turn on.
	 *
	 * @param array $s Stats, see categories().
	 * @return int
	 */
	public static function protect( array $s ) {
		$available = ! empty( $s['watermark_available'] );
		$max       = 75 + ( $available ? 25 : 0 );

		return self::clamp(
			100 * (
				( empty( $s['hotlink'] ) ? 0 : 50 ) +
				( empty( $s['right_click'] ) ? 0 : 25 ) +
				( $available && ! empty( $s['watermark'] ) ? 25 : 0 )
			) / $max
		);
	}

	/**
	 * @param int|float $part  Images affected.
	 * @param int       $total Images in the library.
	 * @return float 0-1.
	 */
	private static function share( $part, $total ) {
		return max( 0, min( 1, (int) $part / $total ) );
	}

	/**
	 * @param int|float $score Raw score.
	 * @return int
	 */
	private static function clamp( $score ) {
		return (int) max( 0, min( 100, round( $score ) ) );
	}
}
