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
