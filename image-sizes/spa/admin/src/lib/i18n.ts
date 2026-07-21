import { __, sprintf } from '@wordpress/i18n';

/**
 * Locale-aware number and size formatting for the admin SPA.
 *
 * `@wordpress/i18n` does not ship a number formatter, so we format against the
 * WordPress site locale exposed from PHP via wp_localize_script
 * (`window.THUMBPRESS.locale`, a BCP-47 tag such as "bn-BD"). This keeps digits
 * grouped/rendered the way the site locale expects (e.g. Bangla numerals) rather
 * than following the browser's own locale. Falls back to the browser locale when
 * the tag is missing or invalid.
 */

/** BCP-47 locale tag from PHP, or undefined to fall back to the browser locale. */
function wpLocale(): string | undefined {
	const locale = window.THUMBPRESS?.locale;
	return locale ? locale : undefined;
}

/** Format an integer/number for display in the current WordPress locale. */
export function numberFormat( value: number ): string {
	if ( ! Number.isFinite( value ) ) {
		return String( value );
	}
	try {
		return value.toLocaleString( wpLocale() );
	} catch {
		return value.toLocaleString();
	}
}

/**
 * Format a percentage for display, e.g. "80%". The number is locale-formatted and
 * the "%" is part of the (translatable) template.
 */
export function percentFormat( value: number ): string {
	// translators: %s is a locale-formatted number.
	return sprintf( __( '%s%%', 'image-sizes' ), numberFormat( value ) );
}

/**
 * Human-readable, locale-aware, translatable file size (e.g. "1.5 MB").
 * The unit label is translatable and the number is locale-formatted.
 */
export function formatBytes( bytes: number ): string {
	const safe = Number.isFinite( bytes ) && bytes > 0 ? bytes : 0;
	const i = safe === 0 ? 0 : Math.min( 4, Math.floor( Math.log( safe ) / Math.log( 1024 ) ) );
	const scaled = safe / Math.pow( 1024, i );
	const rounded = scaled < 10 ? Math.round( scaled * 10 ) / 10 : Math.round( scaled );
	const num = numberFormat( rounded );

	switch ( i ) {
		case 0:
			// translators: %s is a locale-formatted number of bytes.
			return sprintf( __( '%s B', 'image-sizes' ), num );
		case 1:
			// translators: %s is a locale-formatted number of kilobytes.
			return sprintf( __( '%s KB', 'image-sizes' ), num );
		case 2:
			// translators: %s is a locale-formatted number of megabytes.
			return sprintf( __( '%s MB', 'image-sizes' ), num );
		case 3:
			// translators: %s is a locale-formatted number of gigabytes.
			return sprintf( __( '%s GB', 'image-sizes' ), num );
		default:
			// translators: %s is a locale-formatted number of terabytes.
			return sprintf( __( '%s TB', 'image-sizes' ), num );
	}
}
