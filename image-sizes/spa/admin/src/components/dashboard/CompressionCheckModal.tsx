import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { Crown, ArrowRight, X, HeartPulse, Sparkles } from 'lucide-react';
import { COMPRESSION_CHECK_PRO_LINK, compressionCheckCtaLabel, leadsWithPercent } from './compression-check';
import type { CompressionCheckResult, CompressionCheckSample } from '../../api';
import { numberFormat, formatBytes, percentFormat } from '../../lib/i18n';

/**
 * The check's result: the saving up front, the tested images before and after,
 * and one way forward. Numbers are only ever the measured, rounded-down ones.
 */
const CompressionCheckModal = ( { result, samples, healthGain, onClose }: {
	result: CompressionCheckResult;
	samples: CompressionCheckSample[];
	healthGain: number;
	onClose: () => void;
} ) => {
	const totalBefore = samples.reduce( ( sum, sample ) => sum + sample.before, 0 );
	const totalAfter = samples.reduce( ( sum, sample ) => sum + sample.after, 0 );
	const totalPct = totalBefore > 0 ? Math.floor( ( ( totalBefore - totalAfter ) * 100 ) / totalBefore ) : 0;
	const byPercent = leadsWithPercent( result.saved_bytes );

	return (
		<div className="fixed inset-0 z-50 flex items-center justify-center p-4">
			<div className="absolute inset-0 bg-black/60" onClick={ onClose } />

			<div className="relative w-full max-w-[720px] max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
				{/* Hero */}
				<div className="relative bg-gradient-to-br from-thumbpress-primary to-[#1E0F53] px-8 pt-5 pb-5 text-center">
					<button
						type="button"
						onClick={ onClose }
						aria-label={ __( 'Close', 'image-sizes' ) }
						className="absolute top-4 right-4 w-8 h-8 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/10 cursor-pointer"
					>
						<X size={ 18 } />
					</button>

					<p className="text-xs font-medium uppercase tracking-wider text-white/60 mb-1">
						{ byPercent ? __( 'Make your images', 'image-sizes' ) : __( 'You can save', 'image-sizes' ) }
					</p>
					<p className="text-4xl font-bold text-white leading-none mb-2">
						{ byPercent
							? percentFormat( result.saved_pct )
							: sprintf(
								/* translators: %s: estimated size saved, e.g. "640 MB". */
								__( '~%s', 'image-sizes' ),
								formatBytes( result.saved_bytes, true ),
							) }
							
							{" smaller"}
					</p>
					<p className="text-sm text-white/80">
						{ byPercent
							? __( 'with ThumbPress Pro, plus every thumbnail and new upload', 'image-sizes' )
							: sprintf(
								/* translators: %s: number of images. */
								__( 'across your %s images with ThumbPress Pro', 'image-sizes' ),
								numberFormat( result.image_count ),
							) }
					</p>

					<div className="flex items-center justify-center gap-2 mt-3">
						{ ! byPercent && result.saved_pct > 0 && (
							<span className="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white">
								<Sparkles size={ 12 } />
								{ sprintf(
									/* translators: %s: percentage, e.g. "35%". */
									__( '%s smaller', 'image-sizes' ),
									percentFormat( result.saved_pct ),
								) }
							</span>
						) }
						{ healthGain > 0 && (
							<span className="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white">
								<HeartPulse size={ 12 } />
								{ sprintf(
									/* translators: %s: number of health score points. */
									__( '+%s health score', 'image-sizes' ),
									numberFormat( healthGain ),
								) }
							</span>
						) }
					</div>
				</div>

				{/* Before / after */}
				<div className="px-8 pt-4">
					<table className="w-full border-separate border-spacing-0 text-sm">
						<thead>
							<tr className="text-left text-xs uppercase tracking-wider text-[#777980]">
								<th className="pb-2 font-medium">{ __( 'Before', 'image-sizes' ) }</th>
								<th className="pb-2 font-medium">{ __( 'After', 'image-sizes' ) }</th>
								<th className="pb-2 font-medium text-right">{ __( 'Saved', 'image-sizes' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ samples.map( ( sample ) => (
								<tr key={ sample.id }>
									<td className="py-1.5 pr-4 border-t border-gray-100 align-middle">
										<a href={ sample.before_url } target="_blank" rel="noreferrer" className="flex items-center gap-3 no-underline" title={ sample.name }>
											<img src={ sample.before_url } alt={ sample.name } loading="lazy" className="w-9 h-9 rounded-md object-cover bg-gray-100 shrink-0" />
											<span className="text-thumbpress-title font-medium">{ formatBytes( sample.before ) }</span>
										</a>
									</td>
									<td className="py-1.5 pr-4 border-t border-gray-100 align-middle">
										<a href={ sample.after_url } target="_blank" rel="noreferrer" className="flex items-center gap-3 no-underline" title={ sample.name }>
											<img src={ sample.after_url } alt={ sample.name } loading="lazy" className="w-9 h-9 rounded-md object-cover bg-gray-100 shrink-0" />
											<span className="text-thumbpress-primary font-semibold">{ formatBytes( sample.after ) }</span>
										</a>
									</td>
									<td className="py-1.5 border-t border-gray-100 align-middle text-right">
										<span className="inline-block rounded-full bg-[#E8F7EE] px-2.5 py-1 text-xs font-semibold text-[#138A4B]">
											{ sprintf(
												/* translators: %s: percentage, e.g. "65%". */
												__( '−%s', 'image-sizes' ),
												percentFormat( sample.saved_pct ),
											) }
										</span>
									</td>
								</tr>
							) ) }
						</tbody>
						<tfoot>
							<tr className="font-semibold">
								<td className="pt-2 pr-4 border-t-2 border-gray-200 text-thumbpress-title">{ formatBytes( totalBefore ) }</td>
								<td className="pt-2 pr-4 border-t-2 border-gray-200 text-thumbpress-primary">{ formatBytes( totalAfter ) }</td>
								<td className="pt-2 border-t-2 border-gray-200 text-right text-[#138A4B]">
									{ sprintf(
										/* translators: %s: percentage, e.g. "65%". */
										__( '−%s', 'image-sizes' ),
										percentFormat( totalPct ),
									) }
								</td>
							</tr>
						</tfoot>
					</table>
				</div>

				{/* CTA */}
				<div className="px-8 pt-5 pb-5 text-center">
					<a
						href={ COMPRESSION_CHECK_PRO_LINK }
						onClick={ onClose }
						className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-thumbpress-pro-yellow px-6 py-3 text-base font-semibold !text-thumbpress-title no-underline shadow-md transition hover:brightness-95"
					>
						<Crown size={ 18 } />
						{ compressionCheckCtaLabel() }
						<ArrowRight size={ 18 } />
					</a>

					<p className="mt-2 text-xs text-[#777980]">
						{ sprintf(
							/* translators: %s: number of images tested. */
							__( 'Estimate from %s of your images.', 'image-sizes' ),
							numberFormat( result.samples_tested ),
						) }
						{ ' ' }
						<button type="button" onClick={ onClose } className="underline cursor-pointer">
							{ __( 'Maybe later', 'image-sizes' ) }
						</button>
					</p>
				</div>
			</div>
		</div>
	);
};

export default CompressionCheckModal;
