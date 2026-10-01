import React from 'react';
import { __ } from '@wordpress/i18n';
import { X } from 'lucide-react';

/**
 * The one card that offers the checkup: an invitation for a site that has not taken it, or a way
 * back in for one that started and stopped. Never a redirect and never a modal, and it goes away for good.
 */
export default function CheckupCard( { unfinished, onDismiss }: { unfinished: boolean; onDismiss: () => void } ) {
	return (
		<section aria-label={ __( 'Site Image Checkup', 'image-sizes' ) } className="bg-[#F0EBFF] border border-[#D9CCF7] rounded-xl px-5 py-4 flex items-center gap-4 flex-wrap">
			<span className="text-xs leading-4 font-semibold px-2 py-0.5 rounded-full bg-white text-thumbpress-primary whitespace-nowrap">
				{ unfinished ? __( 'In progress', 'image-sizes' ) : __( 'New', 'image-sizes' ) }
			</span>
			<p className="m-0 grow text-thumbpress-title">
				{ unfinished
					? __( 'You started the checkup but did not finish it. It takes about a minute.', 'image-sizes' )
					: __( 'Your images now get a health grade. Take the 60-second checkup to see yours.', 'image-sizes' ) }
			</p>
			<a href="#/setup?mode=upgrade" className="h-9 px-3.5 border border-thumbpress-primary rounded-lg bg-white flex items-center font-semibold no-underline whitespace-nowrap !text-thumbpress-primary">
				{ unfinished ? __( 'Finish checkup', 'image-sizes' ) : __( 'Check my site', 'image-sizes' ) }
			</a>
			<button type="button" onClick={ onDismiss } aria-label={ __( 'Dismiss', 'image-sizes' ) } className="w-9 h-9 border-0 bg-transparent text-[#64748B] rounded-lg flex items-center justify-center cursor-pointer">
				<X size={ 18 } strokeWidth={ 1.75 } />
			</button>
		</section>
	);
}
