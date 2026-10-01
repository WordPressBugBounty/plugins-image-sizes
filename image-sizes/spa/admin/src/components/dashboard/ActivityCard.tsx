import React from 'react';
import { __ } from '@wordpress/i18n';
import ProPill from './ProPill';
import type { ActivityItem } from './model';

export default function ActivityCard( { items }: { items: ActivityItem[] } ) {
	return (
		<section aria-label={ __( 'Activity', 'image-sizes' ) } className="lg:col-span-2 bg-white border border-[#E2E8F0] rounded-xl px-6 py-5 flex flex-col gap-1">
			<h2 className="m-0 mb-2 text-lg leading-7 font-semibold text-thumbpress-title">{ __( 'Activity', 'image-sizes' ) }</h2>

			{ items.length === 0 ? (
				<p className="m-0 py-2.5 border-t border-slate-100 text-[#64748B]">{ __( 'Uploads and scans will show up here.', 'image-sizes' ) }</p>
			) : items.map( ( item ) => (
				<div key={ item.key } className="flex items-center gap-4 py-2.5 border-t border-slate-100">
					<span className="w-16 shrink-0 text-xs leading-4 font-medium text-[#64748B]">{ item.when }</span>
					<span className="grow text-thumbpress-body tabular-nums">{ item.text }</span>
					{ item.locked && <ProPill src="dashboard-tag-activity" /> }
				</div>
			) ) }
		</section>
	);
}
