import React from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { formatBytes, numberFormat } from '../../lib/i18n';

/** Pro users' version of the band: what has been saved so far, from the real running total. */
export default function SavedByPro( { bytes, grown = 0 }: { bytes: number; grown?: number } ) {
	return (
		<section aria-label={ __( 'Saved by Pro', 'image-sizes' ) } className="bg-white border border-[#E2E8F0] rounded-xl px-7 py-6 flex flex-col gap-2">
			<span className="flex items-center gap-2 text-xs leading-4 font-semibold tracking-[0.6px] uppercase text-[#64748B]">
				{ __( 'Saved by Pro', 'image-sizes' ) }
				<span className="text-xs leading-4 font-bold px-[7px] py-px rounded-full bg-thumbpress-pro-yellow text-thumbpress-title tracking-normal">{ __( 'PRO', 'image-sizes' ) }</span>
			</span>
			<span className="text-[28px] leading-9 font-bold text-thumbpress-title tabular-nums">{ formatBytes( bytes ) }</span>
			<span className="text-thumbpress-body">{ __( 'Net space freed so far by compression, conversion and cleanup.', 'image-sizes' ) }</span>
			{ grown > 0 && (
				<span className="text-thumbpress-body">
					{ sprintf(
						// translators: %s is the number of files.
						_n( '%s file got larger and is counted in this total.', '%s files got larger and are counted in this total.', grown, 'image-sizes' ),
						numberFormat( grown )
					) }
				</span>
			) }
		</section>
	);
}
