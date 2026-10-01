import React from 'react';
import { __ } from '@wordpress/i18n';
import { Lock } from 'lucide-react';

const PILL_CLASS = 'inline-flex items-center gap-[3px] shrink-0 text-xs leading-4 font-bold px-[7px] py-px rounded-full bg-thumbpress-pro-yellow !text-thumbpress-title no-underline';

/**
 * The "PRO" tag. A link to the Pro page by default, tagged with where it was clicked, so a purchase
 * can be traced back to the surface that sent the buyer. Inside another link it must be plain text
 * (`link={ false }`): links do not nest, and the row around it already goes somewhere.
 */
export default function ProPill( { src, lock = true, link = true }: { src: string; lock?: boolean; link?: boolean } ) {
	const inner = (
		<>
			{ lock && <Lock size={ 11 } strokeWidth={ 1.75 } aria-hidden="true" /> }
			{ __( 'PRO', 'image-sizes' ) }
		</>
	);

	if ( ! link ) {
		return <span className={ PILL_CLASS }>{ inner }</span>;
	}

	return (
		<a href={ `#/pro?src=${ src }` } title={ __( 'See what Pro includes', 'image-sizes' ) } className={ `${ PILL_CLASS } hover:opacity-80` }>
			{ inner }
		</a>
	);
}
