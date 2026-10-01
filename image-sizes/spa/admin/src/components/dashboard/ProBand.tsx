import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { ArrowRight, FlaskConical, TrendingUp } from 'lucide-react';
import type { CompressionCheckResult } from '../../api';
import { formatBytes, numberFormat, percentFormat, dateFormat } from '../../lib/i18n';
import { COMPRESSION_CHECK_PRO_LINK, compressionCheckCtaLabel, loadTimeSaved } from './compression-check';
import { MISSED_SAVINGS_FLOOR } from './model';

function EstimateChip( { title }: { title: string } ) {
	return (
		<span title={ title } className="inline-flex items-center gap-1 text-xs leading-4 font-medium px-2 py-px rounded-full bg-[#F0EBFF] text-thumbpress-primary">
			<FlaskConical size={ 12 } strokeWidth={ 1.75 } aria-hidden="true" />
			{ __( 'Estimate', 'image-sizes' ) }
		</span>
	);
}

/**
 * The page's one upsell: what Pro would save on this library, measured on copies of its own images.
 *
 * Nothing here is invented. With no measurement it offers the check instead of a number.
 */
export default function ProBand( { result, running, runLabel, onRun, canSeeSamples, onSeeSamples, missedBytes, missedSince, offer }: {
	result: CompressionCheckResult | null;
	running: boolean;
	runLabel: string;
	onRun: () => void;
	canSeeSamples: boolean;
	onSeeSamples: () => void;
	missedBytes: number | null;
	missedSince: number;
	/** A real, running discount from the checkup; null when there is none. */
	offer?: { code: string; percent: number; expires: number } | null;
} ) {
	const showMissed = missedBytes !== null && missedBytes >= MISSED_SAVINGS_FLOOR;

	return (
		<section aria-label={ __( 'Pro would save you', 'image-sizes' ) } className="bg-white border border-[#E2E8F0] rounded-xl px-7 py-6 flex max-lg:flex-col gap-8 lg:items-center">
			<div className="flex-1 flex flex-col gap-2.5 min-w-0">
				<span className="text-xs leading-4 font-semibold tracking-[0.6px] uppercase text-[#64748B]">{ __( 'Pro would save you', 'image-sizes' ) }</span>

				{ result ? (
					<>
						<div className="flex items-baseline gap-3 flex-wrap">
							<span className="text-[28px] leading-9 font-bold text-thumbpress-title tabular-nums">
								{ result.saved_bytes > 0
									? sprintf(
										/* translators: %s: size saved, e.g. "2.1 GB". */
										__( '~%s smaller', 'image-sizes' ),
										formatBytes( result.saved_bytes, true ),
									)
									: sprintf(
										/* translators: %s: how much smaller, e.g. "35%". */
										__( '%s smaller', 'image-sizes' ),
										percentFormat( result.saved_pct ),
									) }
							</span>
							<span className="text-base text-thumbpress-body tabular-nums">
								{ sprintf(
									/* translators: %s: number of images the estimate covers. */
									__( 'across your %s images', 'image-sizes' ),
									numberFormat( result.image_count ),
								) }
								{ loadTimeSaved( result.saved_pct ) !== null && (
									<>
										{ ' ' }
										<span
											title={ __( 'Assumes about 2.5 MB of images on a page and a 9 Mbps connection. Only the percentage is measured on your site.', 'image-sizes' ) }
											className="underline decoration-dotted underline-offset-4 cursor-help"
										>
											{ sprintf(
												/* translators: %s: seconds saved, e.g. "1.4". */
												__( 'and ~%s s faster image load', 'image-sizes' ),
												numberFormat( loadTimeSaved( result.saved_pct ) as number ),
											) }
										</span>
									</>
								) }
							</span>
						</div>
						<div className="flex items-center gap-2 flex-wrap">
							<span className="text-thumbpress-body">
								{ sprintf(
									/* translators: 1: number of images tested, 2: percentage saved on average. */
									__( 'We tested %1$s of your images at Pro’s default quality: %2$s smaller on average.', 'image-sizes' ),
									numberFormat( result.samples_tested ),
									percentFormat( result.saved_pct ),
								) }
							</span>
							<EstimateChip title={ __( 'We compressed copies of your images. Nothing in your library changed.', 'image-sizes' ) } />
						</div>
						<span className="text-xs leading-4 text-[#64748B]">
							{ sprintf(
								/* translators: %s: date the check ran. */
								__( 'Checked %s.', 'image-sizes' ),
								dateFormat( result.measured_at ),
							) }
						</span>
					</>
				) : (
					<p className="m-0 text-[17px] leading-[26px] text-thumbpress-title max-w-[560px]">
						{ running
							? __( 'Testing copies of your images. Your library is not changed.', 'image-sizes' )
							: __( 'Find out how much smaller your images could be. We test copies of a few of them, so nothing in your library changes.', 'image-sizes' ) }
					</p>
				) }

				{ showMissed && (
					<div className="flex items-center gap-2.5 pt-2.5 border-t border-[#E2E8F0] text-thumbpress-body tabular-nums">
						<TrendingUp size={ 16 } strokeWidth={ 1.75 } className="text-[#B45309] shrink-0" aria-hidden="true" />
						<span>
							{ sprintf(
								/* translators: 1: date the count started, 2: size, e.g. "412 MB". */
								__( 'Since %1$s, %2$s more than needed landed on your server. It grows with every upload.', 'image-sizes' ),
								dateFormat( missedSince ),
								formatBytes( missedBytes as number, true ),
							) }
						</span>
					</div>
				) }
			</div>

			<div className="flex flex-col items-stretch gap-2.5 max-lg:w-full lg:w-[250px] shrink-0">
				{ offer && (
					<span className="text-xs leading-4 font-semibold text-thumbpress-title text-center px-2 py-1.5 rounded-lg bg-[#FFF7DB] tabular-nums">
						{ sprintf(
							/* translators: 1: discount percent, 2: coupon code, 3: date and time the offer ends. */
							__( 'Checkup offer: %1$s%% off with code %2$s · ends %3$s', 'image-sizes' ),
							numberFormat( offer.percent ),
							offer.code,
							new Date( offer.expires * 1000 ).toLocaleString( window.THUMBPRESS?.locale || undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' } ),
						) }
					</span>
				) }
				{ result ? (
					<a href={ COMPRESSION_CHECK_PRO_LINK } className="h-10 px-4 border border-thumbpress-primary rounded-lg bg-white !text-thumbpress-primary font-semibold no-underline flex items-center justify-center gap-2">
						{ compressionCheckCtaLabel() }
					</a>
				) : (
					<button
						type="button"
						onClick={ onRun }
						disabled={ running }
						className="h-10 px-4 rounded-lg border-0 bg-thumbpress-primary text-white font-semibold cursor-pointer disabled:opacity-60 disabled:cursor-default"
					>
						{ running ? runLabel : __( 'Check my images', 'image-sizes' ) }
					</button>
				) }
				<span className="text-xs leading-4 text-[#64748B] text-center">{ __( 'Try Pro risk-free for 30 days', 'image-sizes' ) }</span>

				{ result && (
					canSeeSamples ? (
						<button type="button" onClick={ onSeeSamples } className="inline-flex items-center justify-center gap-1.5 pt-1 border-0 bg-transparent font-semibold text-thumbpress-primary cursor-pointer">
							{ __( 'See the samples', 'image-sizes' ) }
							<ArrowRight size={ 16 } strokeWidth={ 1.75 } aria-hidden="true" />
						</button>
					) : (
						<button type="button" onClick={ onRun } disabled={ running } className="inline-flex items-center justify-center gap-1.5 pt-1 border-0 bg-transparent font-semibold text-thumbpress-primary cursor-pointer disabled:opacity-60">
							{ running ? runLabel : __( 'Check again', 'image-sizes' ) }
						</button>
					)
				) }
			</div>
		</section>
	);
}
