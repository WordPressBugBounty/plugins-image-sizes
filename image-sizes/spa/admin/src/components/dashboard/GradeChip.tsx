import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { gradeStyle } from './model';

/** A letter, and optionally its score. The letter is always drawn, so colour is never the only signal. */
export default function GradeChip( { grade, score }: { grade: string; score?: number | null } ) {
	const style = gradeStyle( grade );

	if ( ! grade ) {
		return null;
	}

	return (
		<span
			className="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-full text-[13px] font-bold tabular-nums"
			style={ { background: style.bg, color: style.fg } }
			aria-label={ score != null
				? sprintf(
					/* translators: 1: grade letter, 2: score out of 100. */
					__( 'Grade %1$s, %2$s out of 100', 'image-sizes' ),
					grade,
					String( score ),
				)
				: sprintf(
					/* translators: %s: grade letter. */
					__( 'Grade %s', 'image-sizes' ),
					grade,
				) }
		>
			{ grade }
			{ score != null && <span className="font-medium">{ score }</span> }
		</span>
	);
}
