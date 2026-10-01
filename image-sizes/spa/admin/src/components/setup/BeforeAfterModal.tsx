import React, { useEffect, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { ArrowRight, Check, ChevronsLeftRight, FlaskConical } from 'lucide-react';
import type { CompressionCheckResult, CompressionCheckSample } from '../../api';
import { formatBytes, numberFormat, percentFormat } from '../../lib/i18n';

/** The extension of a file name, upper-cased, for the size line under the picture. */
function formatOf( name: string ): string {
	const dot = name.lastIndexOf( '.' );

	return dot > -1 ? name.slice( dot + 1 ).toUpperCase() : '';
}

/**
 * The user's own photos, before and after compression, side by side under one slider.
 *
 * Artefacts hide at fit-to-screen, so "View at 100%" shows a 1:1 crop: people who check the pixels
 * trust the number more.
 */
export default function BeforeAfterModal( { samples, result, applied, pro = false, onClose }: {
	samples: CompressionCheckSample[];
	result: CompressionCheckResult;
	/** How many free settings were just switched on. */
	applied: number;
	/** Pro is active: no upsell link. */
	pro?: boolean;
	onClose: () => void;
} ) {
	const [ index, setIndex ] = useState( 0 );
	const [ position, setPosition ] = useState( 50 );
	const [ actual, setActual ] = useState( false );
	const stage = useRef< HTMLDivElement >( null );
	const dialog = useRef< HTMLDivElement >( null );
	const dragging = useRef( false );
	const sample = samples[ index ] ?? samples[ 0 ];

	useEffect( () => {
		dialog.current?.focus();
		const onKey = ( event: KeyboardEvent ) => {
			if ( event.key === 'Escape' ) {
				onClose();
			}
		};
		document.addEventListener( 'keydown', onKey );

		return () => document.removeEventListener( 'keydown', onKey );
	}, [] );

	const moveTo = ( clientX: number ) => {
		const box = stage.current?.getBoundingClientRect();

		if ( box && box.width > 0 ) {
			setPosition( Math.max( 0, Math.min( 100, ( ( clientX - box.left ) / box.width ) * 100 ) ) );
		}
	};

	if ( ! sample ) {
		return null;
	}

	const fit = actual ? 'none' : 'contain';

	return (
		<div className="fixed inset-0 z-[100000] flex items-start justify-center p-4 pt-[min(72px,6vh)] bg-[rgba(20,12,50,0.55)] overflow-y-auto">
			<div
				ref={ dialog }
				role="dialog"
				aria-modal="true"
				aria-labelledby="tp-samples-title"
				tabIndex={ -1 }
				className="w-full max-w-[960px] bg-white rounded-xl shadow-[0_24px_48px_rgba(15,23,42,0.25)] overflow-hidden flex flex-col outline-none"
			>
				<div role="status" className="flex items-center gap-2.5 px-6 py-2.5 bg-[#F0FDF4] border-b border-[#BBF7D0] text-thumbpress-title">
					<span className="w-[22px] h-[22px] rounded-full bg-[#D1FAE5] text-[#047857] flex items-center justify-center shrink-0"><Check size={ 13 } strokeWidth={ 2 } aria-hidden="true" /></span>
					<span>
						{ applied > 0
							? <><strong>{ __( 'Free fixes applied.', 'image-sizes' ) }</strong> { sprintf(
								/* translators: %s: number of settings switched on. */
								_n( '%s setting is on.', '%s settings are on.', applied, 'image-sizes' ),
								numberFormat( applied ),
							) }</>
							: <strong>{ __( 'Your free settings were already on.', 'image-sizes' ) }</strong> }
					</span>
				</div>

				<div className="px-6 py-[18px] border-b border-slate-100">
					<h2 id="tp-samples-title" className="m-0 text-lg leading-7 font-semibold text-thumbpress-title">
						{ result.saved_bytes >= 1048576
							? sprintf(
								/* translators: %s: estimated size Pro would save, e.g. "2.1 GB". */
								__( 'Here’s the ~%s, on your own photos', 'image-sizes' ),
								formatBytes( result.saved_bytes, true ),
							)
							: __( 'Here’s the difference, on your own photos', 'image-sizes' ) }
					</h2>
					<span className="text-[13px] text-[#64748B]">
						{ sprintf(
							/* translators: %s: number of photos compressed. */
							__( 'We compressed %s of your photos on copies. Nothing on your site changed. Drag the handle, or zoom to 100%% and look closely.', 'image-sizes' ),
							numberFormat( samples.length ),
						) }
					</span>
				</div>

				<div className="px-6 py-5 flex flex-col gap-4">
					<div className="flex items-center justify-between gap-3 flex-wrap">
						<div className="flex gap-2" role="tablist" aria-label={ __( 'Sample images', 'image-sizes' ) }>
							{ samples.map( ( item, i ) => (
								<button
									key={ item.id }
									type="button"
									role="tab"
									aria-selected={ i === index }
									aria-label={ sprintf(
										/* translators: 1: sample number, 2: number of samples. */
										__( 'Sample %1$s of %2$s', 'image-sizes' ),
										String( i + 1 ),
										String( samples.length ),
									) }
									onClick={ () => { setIndex( i ); setPosition( 50 ); } }
									className={ `w-16 h-12 rounded-lg p-0 overflow-hidden cursor-pointer bg-slate-100 ${ i === index ? 'border-2 border-thumbpress-primary' : 'border border-[#E2E8F0]' }` }
								>
									<img src={ item.before_url } alt="" className="w-full h-full object-cover" />
								</button>
							) ) }
						</div>

						<div className="flex border border-[#CBD5E1] rounded-lg overflow-hidden">
							<button type="button" aria-pressed={ ! actual } onClick={ () => setActual( false ) } className={ `h-9 px-3.5 border-0 text-[13px] font-semibold cursor-pointer ${ ! actual ? 'bg-[#F0EBFF] text-thumbpress-primary' : 'bg-white text-thumbpress-title' }` }>{ __( 'Fit', 'image-sizes' ) }</button>
							<button type="button" aria-pressed={ actual } onClick={ () => setActual( true ) } className={ `h-9 px-3.5 border-0 border-l border-[#CBD5E1] text-[13px] font-semibold cursor-pointer ${ actual ? 'bg-[#F0EBFF] text-thumbpress-primary' : 'bg-white text-thumbpress-title' }` }>{ __( 'View at 100%', 'image-sizes' ) }</button>
						</div>
					</div>

					<div
						ref={ stage }
						className="relative h-[clamp(240px,42vh,420px)] rounded-[10px] overflow-hidden bg-slate-100 select-none touch-none"
						onPointerDown={ ( event ) => { dragging.current = true; ( event.currentTarget as HTMLElement ).setPointerCapture( event.pointerId ); moveTo( event.clientX ); } }
						onPointerMove={ ( event ) => { if ( dragging.current ) moveTo( event.clientX ); } }
						onPointerUp={ () => { dragging.current = false; } }
					>
						<img src={ sample.after_url } alt={ sprintf(
							/* translators: %s: file name. */
							__( '%s after compression', 'image-sizes' ),
							sample.name,
						) } draggable={ false } className="absolute inset-0 w-full h-full" style={ { objectFit: fit } } />
						<img src={ sample.before_url } alt={ sprintf(
							/* translators: %s: file name. */
							__( '%s before compression', 'image-sizes' ),
							sample.name,
						) } draggable={ false } className="absolute inset-0 w-full h-full" style={ { objectFit: fit, clipPath: `inset(0 ${ 100 - position }% 0 0)` } } />

						<span className="absolute left-4 top-4 text-xs leading-4 font-semibold px-2.5 py-[3px] rounded-full bg-[rgba(15,23,42,0.7)] text-white">{ __( 'Before', 'image-sizes' ) }</span>
						<span className="absolute right-4 top-4 text-xs leading-4 font-semibold px-2.5 py-[3px] rounded-full bg-thumbpress-primary text-white">{ __( 'After', 'image-sizes' ) }</span>
						<span className="absolute left-1/2 bottom-4 -translate-x-1/2 text-xs leading-4 text-[#1E0F53] bg-white/85 px-2.5 py-[3px] rounded-full max-w-[80%] truncate">{ sample.name }</span>

						<div className="absolute top-0 bottom-0 w-0.5 bg-white -translate-x-px pointer-events-none" style={ { left: `${ position }%` } } />
						<button
							type="button"
							role="slider"
							aria-label={ __( 'Compare before and after', 'image-sizes' ) }
							aria-valuemin={ 0 }
							aria-valuemax={ 100 }
							aria-valuenow={ Math.round( position ) }
							onKeyDown={ ( event ) => {
								if ( event.key === 'ArrowLeft' ) setPosition( ( value ) => Math.max( 0, value - 5 ) );
								if ( event.key === 'ArrowRight' ) setPosition( ( value ) => Math.min( 100, value + 5 ) );
							} }
							className="absolute top-1/2 -translate-x-1/2 -translate-y-1/2 w-11 h-11 rounded-full border-0 bg-white text-thumbpress-primary shadow-[0_2px_8px_rgba(15,23,42,0.25)] flex items-center justify-center cursor-ew-resize"
							style={ { left: `${ position }%` } }
						>
							<ChevronsLeftRight size={ 20 } strokeWidth={ 1.75 } aria-hidden="true" />
						</button>
					</div>

					<div className="grid grid-cols-2 gap-4 tabular-nums">
						<div className="flex flex-col gap-0.5">
							<span className="text-xs leading-4 font-medium text-[#64748B]">{ __( 'Before', 'image-sizes' ) }</span>
							<span className="text-xl leading-7 font-bold text-thumbpress-title">{ formatBytes( sample.before ) } <span className="text-sm font-medium text-[#64748B]">{ formatOf( sample.name ) }</span></span>
						</div>
						<div className="flex flex-col gap-0.5 items-end">
							<span className="text-xs leading-4 font-medium text-[#64748B]">{ __( 'After', 'image-sizes' ) }</span>
							<span className="text-xl leading-7 font-bold text-thumbpress-title">~{ formatBytes( sample.after, true ) } <span className="text-sm font-medium text-[#64748B]">{ formatOf( sample.name ) }</span> <span className="text-sm font-bold text-[#047857]">−{ percentFormat( sample.saved_pct ) }</span></span>
						</div>
					</div>
				</div>

				<div className="flex items-center gap-4 px-6 py-4 border-t border-slate-100 bg-[#FAFAFB] flex-wrap">
					<span className="grow flex items-center gap-2 text-[13px] text-[#64748B]">
						<span className="inline-flex items-center gap-1 text-xs leading-4 font-medium px-2 py-px rounded-full bg-[#F0EBFF] text-thumbpress-primary">
							<FlaskConical size={ 12 } strokeWidth={ 1.75 } aria-hidden="true" />
							{ __( 'Estimate', 'image-sizes' ) }
						</span>
						{ sprintf(
							/* translators: 1: number of photos tested, 2: percentage saved on average. */
							__( 'Average across %1$s of your photos: −%2$s', 'image-sizes' ),
							numberFormat( result.samples_tested ),
							percentFormat( result.saved_pct ),
						) }
					</span>
					{ ! pro && <a href="#/pro?src=wizard-samples" className="text-[13px] font-semibold text-thumbpress-primary no-underline">{ __( 'How Pro works', 'image-sizes' ) }</a> }
					<button type="button" onClick={ onClose } className="h-11 px-5 rounded-lg border-0 bg-thumbpress-primary text-white text-[15px] font-semibold inline-flex items-center gap-2 cursor-pointer">
						{ __( 'Go to dashboard', 'image-sizes' ) }
						<ArrowRight size={ 16 } strokeWidth={ 1.75 } aria-hidden="true" />
					</button>
				</div>
			</div>
		</div>
	);
}
