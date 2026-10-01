import React from 'react';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Check, X } from 'lucide-react';
import { numberFormat } from '../../lib/i18n';

/** Shown once, right after the checkup, so the person landing here knows what just happened. */
export default function CheckupToast( { applied, onDismiss }: { applied: number; onDismiss: () => void } ) {
	return (
		<div role="status" className="bg-white border border-[#A7F3D0] rounded-xl px-4 py-3 flex items-center gap-3 shadow-sm">
			<span className="w-7 h-7 rounded-full bg-[#D1FAE5] text-[#047857] flex items-center justify-center shrink-0"><Check size={ 16 } strokeWidth={ 2 } aria-hidden="true" /></span>
			<span className="grow text-thumbpress-title">
				<strong>{ __( 'Checkup done.', 'image-sizes' ) }</strong>{ ' ' }
				<span className="text-thumbpress-body">
					{ applied > 0
						? sprintf(
							/* translators: %s: number of free settings switched on. */
							_n( '%s free setting is on for new uploads.', '%s free settings are on for new uploads.', applied, 'image-sizes' ),
							numberFormat( applied ),
						)
						: __( 'Your free settings were already on.', 'image-sizes' ) }
				</span>
			</span>
			<button type="button" onClick={ onDismiss } aria-label={ __( 'Dismiss', 'image-sizes' ) } className="w-8 h-8 border-0 bg-transparent text-[#64748B] rounded-lg flex items-center justify-center cursor-pointer">
				<X size={ 16 } strokeWidth={ 1.75 } />
			</button>
		</div>
	);
}
