import React from 'react';
import { __ } from '@wordpress/i18n';
import { ChevronRight, FileImage, RefreshCw, Search, SlidersHorizontal, type LucideIcon } from 'lucide-react';

const rowClass = 'flex items-center gap-2.5 h-10 border-t border-slate-100 no-underline !text-thumbpress-title font-medium w-full bg-transparent border-x-0 border-b-0 cursor-pointer text-left disabled:opacity-60 disabled:cursor-default';

export default function QuickActions( { onScan, scanBusy }: { onScan: () => void; scanBusy: boolean } ) {
	const link = ( to: string, label: string, Icon: LucideIcon ) => (
		<a href={ `#${ to }` } className={ rowClass }>
			<Icon size={ 18 } strokeWidth={ 1.75 } className="text-thumbpress-primary" aria-hidden="true" />
			<span className="grow">{ label }</span>
			<ChevronRight size={ 16 } strokeWidth={ 1.75 } className="text-[#64748B]" aria-hidden="true" />
		</a>
	);

	return (
		<section aria-label={ __( 'Quick actions', 'image-sizes' ) } className="bg-white border border-[#E2E8F0] rounded-xl px-6 py-5 flex flex-col gap-1">
			<h2 className="m-0 mb-2 text-lg leading-7 font-semibold text-thumbpress-title">{ __( 'Quick actions', 'image-sizes' ) }</h2>
			{ link( '/thumbnails', __( 'Regenerate thumbnails', 'image-sizes' ), RefreshCw ) }
			{ link( '/convert-to-webp', __( 'Convert to WebP', 'image-sizes' ), FileImage ) }
			<button type="button" onClick={ onScan } disabled={ scanBusy } className={ rowClass }>
				<Search size={ 18 } strokeWidth={ 1.75 } className="text-thumbpress-primary" aria-hidden="true" />
				<span className="grow">{ __( 'Run scan', 'image-sizes' ) }</span>
				<ChevronRight size={ 16 } strokeWidth={ 1.75 } className="text-[#64748B]" aria-hidden="true" />
			</button>
			{ link( '/settings', __( 'Settings', 'image-sizes' ), SlidersHorizontal ) }
		</section>
	);
}
