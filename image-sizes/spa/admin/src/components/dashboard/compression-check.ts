import { __ } from '@wordpress/i18n';

/** Where the compression check sends people, tagged so a purchase can be traced back to it. */
export const COMPRESSION_CHECK_PRO_LINK = '#/pro?src=compression-check';

/** Pro installed but not activated needs a license, not a sale. */
export function compressionCheckCtaLabel(): string {
	const proInstalled = !! window.THUMBPRESS?.pro_installed;
	const proActive = !! window.THUMBPRESS?.pro_active;

	return proInstalled && ! proActive
		? __( 'Activate license to compress', 'image-sizes' )
		: __( 'Compress all with Pro', 'image-sizes' );
}

/** Below this the size saved looks small, so the headline shows how much smaller instead. */
export const LOW_SAVING_BYTES = 1048576;

/** Lead with the percentage when the size saved would undersell compression. */
export function leadsWithPercent( savedBytes: number ): boolean {
	return savedBytes < LOW_SAVING_BYTES;
}

/** Health points compression can still add: its 25-point share, for the images not yet compressed. */
export function compressionHealthGain( notCompressed: number, totalImages: number ): number {
	return totalImages > 0 ? Math.floor( ( 25 * notCompressed ) / totalImages ) : 0;
}

/** Assumed weight of the images on one page: a stated assumption, not something measured on this site. */
export const PAGE_IMAGE_BYTES = 2.5 * 1024 * 1024;

/** Assumed connection speed: 9 Mbps, a typical fast 4G link. */
export const CONNECTION_BYTES_PER_SECOND = ( 9 * 1000 * 1000 ) / 8;

/**
 * Seconds a page's images would load faster, rounded down, or null when it is too small to mention.
 *
 * Only the measured saving (the share of a photo's bytes compression removes) is from this site; the
 * page weight and the connection speed are assumptions, which is why the figure is always labelled.
 */
export function loadTimeSaved( savedPct: number ): number | null {
	const seconds = ( Math.max( 0, Math.min( 100, savedPct ) ) / 100 ) * PAGE_IMAGE_BYTES / CONNECTION_BYTES_PER_SECOND;
	const rounded = Math.floor( seconds * 10 ) / 10;

	return rounded >= 0.1 ? rounded : null;
}
