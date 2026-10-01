import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { ArrowRight, Search } from 'lucide-react';
import { gradeStyle, type HeroModel } from './model';

/** The one dark block: how healthy the library is, what is worst, and what to click. */
export default function GradeHero( { grade, score, hero, onScan, onFixAll, busy }: {
	grade: string;
	score: number;
	hero: HeroModel;
	onScan: () => void;
	onFixAll: () => void;
	busy: boolean;
} ) {
	const style = gradeStyle( grade );
	const trendColour = hero.trendDir === 'down' ? '#FED7AA' : hero.trendDir === 'up' ? '#A7F3D0' : '#C9BFF0';

	const cta = (
		<>
			{ hero.cta.label }
			<ArrowRight size={ 16 } strokeWidth={ 1.75 } aria-hidden="true" />
		</>
	);

	const ctaClass = 'h-11 px-[22px] rounded-lg bg-white !text-thumbpress-primary text-[15px] font-semibold inline-flex items-center gap-2 no-underline cursor-pointer disabled:opacity-60 disabled:cursor-default';

	return (
		<section aria-label={ __( 'Media Health', 'image-sizes' ) } className="bg-[#1E0F53] rounded-xl px-8 py-7 flex max-md:flex-col md:items-center gap-7 text-white">
			{ hero.never ? (
				<div className="w-[104px] h-[104px] rounded-2xl border-2 border-dashed border-[#8B7BC7] flex items-center justify-center text-[#C9BFF0] shrink-0" aria-hidden="true">
					<Search size={ 36 } strokeWidth={ 1.75 } />
				</div>
			) : (
				<div
					className="w-[104px] h-[104px] rounded-2xl flex items-center justify-center text-[64px] leading-[64px] font-bold shrink-0"
					style={ { background: style.bg, color: style.fg } }
					aria-label={ sprintf(
						/* translators: %s: grade letter. */
						__( 'Grade %s', 'image-sizes' ),
						grade,
					) }
				>
					{ grade }
				</div>
			) }

			<div className="flex-1 flex flex-col gap-2 min-w-0">
				<div className="flex items-center gap-3 flex-wrap">
					<span className="text-xs leading-4 font-semibold tracking-[0.6px] uppercase text-[#C9BFF0]">{ __( 'Media Health', 'image-sizes' ) }</span>
					{ hero.partial && (
						<span className="text-xs leading-4 font-semibold px-2 py-0.5 rounded-full bg-[#33207A] text-[#E9E3FF] border border-[#5B45A8]">
							{ __( 'Partial data', 'image-sizes' ) }
						</span>
					) }
				</div>

				{ ! hero.never && (
					<div className="flex items-baseline gap-3.5 flex-wrap">
						<span className="text-[28px] leading-9 font-bold tabular-nums">
							{ score }<span className="text-base font-medium text-[#C9BFF0]"> / 100</span>
						</span>
						{ hero.trend && (
							<span className="text-[13px] font-medium tabular-nums" style={ { color: trendColour } }>{ hero.trend }</span>
						) }
					</div>
				) }

				<p className="m-0 text-[17px] leading-[26px] text-white max-w-[560px] [text-wrap:pretty]">{ hero.sentence }</p>
			</div>

			<div className="flex flex-col md:items-end gap-2 shrink-0">
				{ hero.cta.scan || hero.cta.fixAll ? (
					<button type="button" onClick={ hero.cta.fixAll ? onFixAll : onScan } disabled={ busy && ! hero.cta.fixAll } className={ ctaClass }>{ cta }</button>
				) : (
					<a href={ `#${ hero.cta.to }` } className={ ctaClass }>{ cta }</a>
				) }
				<span className="text-xs leading-4 text-[#C9BFF0] tabular-nums">{ hero.ctaNote }</span>
			</div>
		</section>
	);
}
