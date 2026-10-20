<?php
defined( 'ABSPATH' ) || exit;

/**
 * Returns all supported image mime types for ThumbPress operations.
 *
 * @param array $exclude Mime types to exclude (e.g. array('image/webp') when converting to WebP).
 * @return array
 */
function thumbpress_supported_image_mimes( $exclude = array() ) {
	$mimes = array(
		'image/jpeg',
		'image/jpg',
		'image/png',
		'image/gif',
		'image/bmp',
		'image/webp',
		'image/avif',
	);

	return $exclude ? array_values( array_diff( $mimes, $exclude ) ) : $mimes;
}

/**
 * Add a signed byte delta to the cumulative space-saved counter shared by all operations.
 *
 * A positive value is space freed; a negative value means the output ended up larger than the source.
 * The counter is the net of both, so it never overstates what was really saved.
 *
 * @param int      $bytes Signed bytes saved (negative when files grew). Zero has no effect.
 * @param int|null $grown Files that grew within this delta. Defaults to 1 for a negative single-file delta.
 */
function thumbpress_add_space_saved( $bytes, $grown = null ) {
	$bytes = (int) $bytes;
	$grown = null === $grown ? ( $bytes < 0 ? 1 : 0 ) : max( 0, (int) $grown );

	if ( 0 !== $bytes ) {
		update_option( 'thumbpress_space_saved', (int) get_option( 'thumbpress_space_saved', 0 ) + $bytes );
	}

	if ( $grown > 0 ) {
		update_option( 'thumbpress_files_grown', (int) get_option( 'thumbpress_files_grown', 0 ) + $grown );
	}
}

/**
 * Human-readable signed size, e.g. "1.5 MB" or "-376 KB" (size_format() returns false below zero).
 *
 * @param int $bytes Signed bytes.
 * @return string
 */
function thumbpress_format_size_delta( $bytes ) {
	$bytes = (int) $bytes;
	$label = size_format( abs( $bytes ) );

	return $bytes < 0 ? '-' . $label : $label;
}

/**
 * The instant the promo ends (normal state begins), as a UTC Unix timestamp.
 *
 * This is the EXCLUSIVE end boundary: the promo is active for every moment
 * strictly before it, so the default of `2026-08-10 00:00:00 UTC` keeps the sale
 * live through `2026-08-09 23:59:59 UTC` and flips to normal from midnight — per
 * the campaign spec.
 *
 * Sourced from the `thumbpress_promo_end_date` option (a `Y-m-d H:i:s` string
 * interpreted as UTC) so the boundary can be changed — or the sale extended — by
 * pushing a new option value through the remote-update channel, with no plugin
 * release. Falls back to the hardcoded boundary when the option is unset or
 * malformed.
 *
 * @return int UTC Unix timestamp at which the promo ends.
 */
function thumbpress_promo_end_timestamp() {
	$default = '2026-08-10 00:00:00';
	$end     = (string) get_option( 'thumbpress_promo_end_date', $default );

	$timestamp = strtotime( $end . ' UTC' );
	if ( false === $timestamp ) {
		$timestamp = strtotime( $default . ' UTC' );
	}

	return (int) $timestamp;
}

/**
 * Whether the promotional (Summer Sale) campaign is currently running.
 *
 * Single source of truth for every promo surface — the admin-bar offer, the
 * sidebar CTA styling, the Pro pricing discounts and the exit-intent popup. Once
 * the end date passes the whole promo flips off automatically: no manual step,
 * no release. Filterable for testing or a forced override.
 *
 * @return bool
 */
function thumbpress_promo_active() {
	$active = time() < thumbpress_promo_end_timestamp();

	return (bool) apply_filters( 'thumbpress_promo_active', $active );
}
