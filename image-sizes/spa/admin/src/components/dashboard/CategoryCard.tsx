import React from 'react';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { ArrowRight, Check, FlaskConical, Gauge, HardDrive, Search, ShieldCheck, type LucideIcon } from 'lucide-react';
import type { NavigateFunction } from 'react-router-dom';
import GradeChip from './GradeChip';
import ProPill from './ProPill';
import SoonTag from './SoonTag';
import type { CategoryKey } from '../../api';
import type { DashboardCard, DashboardLine, DashboardModelStats } from './model';

const ICONS: Record< CategoryKey, LucideIcon > = {
	speed: Gauge,
	seo: Search,
	storage: HardDrive,
	protect: ShieldCheck,
};

function EstimateChip() {
	return (
		<span
			title={ __( 'Estimated from a scan of your posts, pages and settings. Some page builders hide their images from it.', 'image-sizes' ) }
			className="inline-flex items-center gap-[3px] text-xs leading-4 font-medium px-[7px] rounded-full bg-[#F0EBFF] text-thumbpress-primary"
		>
			<FlaskConical size={ 11 } strokeWidth={ 1.75 } aria-hidden="true" />
			{ __( 'Estimate', 'image-sizes' ) }
		</span>
	);
}

function LineBody( { line, cardKey }: { line: DashboardLine; cardKey: string } ) {
	return (
		<div className="flex flex-col gap-0.5 py-2.5 border-t border-slate-100">
			<div className="flex items-center justify-between gap-2 min-h-[22px]">
				<span className="min-w-0 text-thumbpress-body tabular-nums">
					{ line.num
						? <><span className="text-[15px] font-bold text-thumbpress-title">{ line.num }</span>{ ' ' }{ line.label }</>
						: <span className="font-medium text-thumbpress-title">{ line.label }</span> }
				</span>
				{ line.tier === 'pro' && <ProPill src={ `dashboard-tag-${ cardKey }` } link={ ! ( line.to || line.href ) } /> }
				{ line.tier === 'soon' && <SoonTag /> }
				{ line.tier === 'ok' && (
					<span className="inline-flex items-center gap-[3px] shrink-0 text-xs leading-4 font-semibold px-2 py-px rounded-full bg-[#D1FAE5] text-[#047857]">
						<Check size={ 11 } strokeWidth={ 2 } aria-hidden="true" />
						{ line.status }
					</span>
				) }
				{ line.tier === 'free' && (
					<span className="shrink-0 text-xs leading-4 font-semibold px-2 py-px rounded-full border border-[#A7F3D0] text-[#047857]">
						{ __( 'Free fix', 'image-sizes' ) }
					</span>
				) }
			</div>
			{ ( line.note || line.estimate ) && (
				<div className="flex items-center gap-1.5 flex-wrap">
					{ line.note && <span className="text-xs leading-4 text-[#64748B]">{ line.note }</span> }
					{ line.estimate && <EstimateChip /> }
				</div>
			) }
		</div>
	);
}

function Line( { line, cardKey }: { line: DashboardLine; cardKey: string } ) {
	if ( line.to || line.href ) {
		return (
			<a href={ line.href ?? `#${ line.to }` } className="block no-underline !text-inherit hover:bg-slate-50 -mx-2 px-2 rounded-lg">
				<LineBody line={ line } cardKey={ cardKey } />
			</a>
		);
	}

	return <LineBody line={ line } cardKey={ cardKey } />;
}

/**
 * One of the four category cards.
 *
 * A line that used to be a dashboard card still runs through that card's filter, so a Pro release
 * that overrides it keeps working; the default it receives is the line itself.
 */
export default function CategoryCard( { card, stats, navigate, onScan, busy }: {
	card: DashboardCard;
	stats: DashboardModelStats;
	navigate: NavigateFunction;
	onScan: () => void;
	busy: boolean;
} ) {
	const Icon = ICONS[ card.key ];
	const fixInner = (
		<>
			{ card.fix.label }
			<ArrowRight size={ 16 } strokeWidth={ 1.75 } aria-hidden="true" />
		</>
	);
	const fixClass = 'flex items-center gap-1.5 pt-3 border-t border-slate-100 font-semibold text-thumbpress-primary no-underline whitespace-nowrap cursor-pointer bg-transparent border-x-0 border-b-0 text-left disabled:opacity-60';

	return (
		<section className="bg-white border border-[#E2E8F0] rounded-xl px-5 pt-5 pb-4 flex flex-col gap-1 shadow-sm">
			<div className="flex items-center gap-2.5 pb-2">
				<span className="w-9 h-9 rounded-lg bg-[#F0EBFF] text-thumbpress-primary flex items-center justify-center">
					<Icon size={ 20 } strokeWidth={ 1.75 } aria-hidden={ true } />
				</span>
				<h2 className="m-0 grow text-base leading-6 font-semibold text-thumbpress-title">{ card.name }</h2>
				<GradeChip grade={ card.grade } score={ card.score } />
			</div>

			<div className="flex flex-col grow">
				{ card.lines.map( ( line ) => {
					const node = <Line line={ line } cardKey={ card.key } />;

					return (
						<React.Fragment key={ line.key }>
							{ line.filter
								? applyFilters( `thumbpress_dashboard_card_${ line.filter }`, node, stats, navigate ) as React.ReactNode
								: node }
						</React.Fragment>
					);
				} ) }
			</div>

			{ card.fix.scan ? (
				<button type="button" onClick={ onScan } disabled={ busy } className={ fixClass }>{ fixInner }</button>
			) : (
				<a href={ card.fix.href ?? `#${ card.fix.to }` } className={ fixClass }>{ fixInner }</a>
			) }
		</section>
	);
}
