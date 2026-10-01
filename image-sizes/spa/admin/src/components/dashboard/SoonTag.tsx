import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * For a fix Pro does not have yet. Deliberately not a link and not the PRO tag: nothing here can be
 * bought today, so it must not send anyone to the pricing page.
 */
export default function SoonTag() {
	return (
		<span
			title={ __( 'Pro does not fix this automatically yet.', 'image-sizes' ) }
			className="inline-flex items-center shrink-0 text-xs leading-4 font-medium px-2 py-px rounded-full border border-[#CBD5E1] text-[#64748B] whitespace-nowrap"
		>
			{ __( 'Autofix coming to Pro', 'image-sizes' ) }
		</span>
	);
}
