import React, { useEffect, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { ArrowRight, Check, X } from 'lucide-react';
import { Checkbox } from '../ui/checkbox';
import ProPill from './ProPill';
import SoonTag from './SoonTag';
import { getPluginSettings, savePluginSettings } from '../../api';
import { formatBytes, numberFormat } from '../../lib/i18n';
import { COMPRESSION_CHECK_PRO_LINK } from './compression-check';
import { gradeFor, gradeStyle, proProjection, type DashboardModelStats } from './model';

type FixKey = 'webp_on_upload' | 'lazy_load' | 'hotlink_protection' | 'right_click_disable';

interface FreeFix {
	key: FixKey;
	label: string;
	note: string;
	/** Pre-checked: changes nothing a visitor would notice. The others change what visitors can do. */
	recommended: boolean;
}

const FREE_FIXES: FreeFix[] = [
	{
		key: 'webp_on_upload',
		label: __( 'Convert new uploads to WebP', 'image-sizes' ),
		note: __( 'Every new image is converted as it arrives.', 'image-sizes' ),
		recommended: true,
	},
	{
		key: 'lazy_load',
		label: __( 'Turn on lazy loading', 'image-sizes' ),
		note: __( 'Images load as visitors scroll to them.', 'image-sizes' ),
		recommended: true,
	},
	{
		key: 'hotlink_protection',
		label: __( 'Stop other sites embedding your images', 'image-sizes' ),
		note: __( 'Adds rules to your server config. Sites that embed your images will stop showing them.', 'image-sizes' ),
		recommended: false,
	},
	{
		key: 'right_click_disable',
		label: __( 'Block right-click on images', 'image-sizes' ),
		note: __( 'A light deterrent only. Some visitors find it annoying.', 'image-sizes' ),
		recommended: false,
	},
];

interface ProRow {
	key: string;
	num: string;
	label: string;
	note: string;
	to: string;
	/** Pro does not do this yet: shown, linked to the hand-made fix, never sold. */
	soon?: boolean;
}

function proRows( s: DashboardModelStats ): ProRow[] {
	const rows: ProRow[] = [];

	if ( s.large_images > 0 ) {
		rows.push( { key: 'large', num: numberFormat( s.large_images ), label: _n( 'heavy image to compress', 'heavy images to compress', s.large_images, 'image-sizes' ), note: __( 'Over 1 MB each. The main thing slowing your pages.', 'image-sizes' ), to: '/large-images' } );
	}
	if ( s.not_compressed > 0 ) {
		rows.push( { key: 'compress', num: numberFormat( s.not_compressed ), label: _n( 'image not compressed', 'images not compressed', s.not_compressed, 'image-sizes' ), note: __( 'Compress them all in one run.', 'image-sizes' ), to: '/compress-images' } );
	}
	if ( s.missing_alt > 0 ) {
		rows.push( { key: 'alt', num: numberFormat( s.missing_alt ), label: _n( 'image with no alt text', 'images with no alt text', s.missing_alt, 'image-sizes' ), note: __( 'Google can’t read them.', 'image-sizes' ), to: 'upload.php?mode=list', soon: true } );
	}
	if ( s.duplicate_images > 0 ) {
		rows.push( {
			key: 'duplicate',
			num: numberFormat( s.duplicate_images ),
			label: _n( 'duplicate image to merge', 'duplicate images to merge', s.duplicate_images, 'image-sizes' ),
			note: sprintf(
				/* translators: %s: size of the repeat files. */
				__( '%s of repeat files.', 'image-sizes' ),
				formatBytes( s.duplicate_bytes ),
			),
			to: '/duplicate-images',
		} );
	}
	if ( s.unused_images > 0 || ( s.likely_unused && s.likely_unused.count > 0 ) ) {
		rows.push( {
			key: 'unused',
			num: s.unused_images > 0 ? numberFormat( s.unused_images ) : '~' + numberFormat( s.likely_unused?.count ?? 0 ),
			label: s.unused_images > 0
				? _n( 'unused image to review', 'unused images to review', s.unused_images, 'image-sizes' )
				: _n( 'likely unused image to review', 'likely unused images to review', s.likely_unused?.count ?? 0, 'image-sizes' ),
			note: __( 'Nothing is removed until you confirm.', 'image-sizes' ),
			to: '/unused-images',
		} );
	}

	return rows;
}

/**
 * Everything the dashboard found, in one place: the free fixes that apply right here, and what Pro
 * would do with this site's own numbers.
 */
export default function FixEverythingModal( { stats, pro, onClose, onApplied }: {
	stats: DashboardModelStats;
	pro: boolean;
	onClose: () => void;
	onApplied: () => void;
} ) {
	const [ current, setCurrent ] = useState< Partial< Record< FixKey, boolean > > | null >( null );
	const [ picked, setPicked ] = useState< Partial< Record< FixKey, boolean > > >( {} );
	const [ busy, setBusy ] = useState( false );
	const [ applied, setApplied ] = useState< string[] >( [] );
	const dialog = useRef< HTMLDivElement >( null );
	const opener = useRef< Element | null >( document.activeElement );

	useEffect( () => {
		getPluginSettings()
			.then( ( res ) => {
				const data: any = res?.data ?? {};
				const state: Partial< Record< FixKey, boolean > > = {};
				const start: Partial< Record< FixKey, boolean > > = {};

				for ( const fix of FREE_FIXES ) {
					state[ fix.key ] = !! data[ fix.key ];
					start[ fix.key ] = ! data[ fix.key ] && fix.recommended;
				}

				setCurrent( state );
				setPicked( start );
			} )
			.catch( () => setCurrent( {} ) );
	}, [] );

	useEffect( () => {
		dialog.current?.focus();
		const onKey = ( event: KeyboardEvent ) => {
			if ( event.key === 'Escape' ) {
				onClose();
			}
		};
		document.addEventListener( 'keydown', onKey );
		const back = opener.current;

		return () => {
			document.removeEventListener( 'keydown', onKey );
			( back as HTMLElement | null )?.focus?.();
		};
	}, [] );

	const open = FREE_FIXES.filter( ( fix ) => current && current[ fix.key ] === false );
	const chosen = open.filter( ( fix ) => picked[ fix.key ] );
	const rows = proRows( stats );
	const projected = proProjection( stats );
	const projectedGrade = gradeFor( projected );
	const nowStyle = gradeStyle( stats.grade );
	const nextStyle = gradeStyle( projectedGrade );
	const showProjection = ! pro && rows.length > 0 && projected > stats.health_score;

	const apply = async () => {
		setBusy( true );
		try {
			const payload: Partial< Record< FixKey, boolean > > = {};
			for ( const fix of chosen ) {
				payload[ fix.key ] = true;
			}
			await savePluginSettings( payload );
			setApplied( chosen.map( ( fix ) => fix.label ) );
			setCurrent( ( state ) => ( { ...state, ...payload } ) );
			onApplied();
		} catch {
			toast.error( __( 'Could not save. Nothing was changed.', 'image-sizes' ) );
		} finally {
			setBusy( false );
		}
	};

	return (
		<div className="fixed inset-0 z-50 flex items-center justify-center p-4">
			<div className="absolute inset-0 bg-black/60" onClick={ onClose } />

			<div
				ref={ dialog }
				role="dialog"
				aria-modal="true"
				aria-labelledby="tp-fix-title"
				tabIndex={ -1 }
				className="relative w-full max-w-[800px] max-h-[90vh] flex flex-col rounded-2xl bg-white shadow-2xl outline-none overflow-hidden"
			>
				<div className="overflow-y-auto grow min-h-0">
				<div className="flex items-start justify-between gap-4 px-8 pt-7 pb-4">
					<div>
						<h2 id="tp-fix-title" className="m-0 text-2xl leading-8 font-semibold text-thumbpress-title">{ __( 'Fix everything', 'image-sizes' ) }</h2>
						<p className="m-0 mt-1 text-thumbpress-body">{ __( 'Nothing changes until you apply it. Your images are not touched by the settings below.', 'image-sizes' ) }</p>
					</div>
					<button type="button" onClick={ onClose } aria-label={ __( 'Close', 'image-sizes' ) } className="w-9 h-9 rounded-lg border-0 bg-transparent text-[#64748B] flex items-center justify-center cursor-pointer shrink-0">
						<X size={ 18 } strokeWidth={ 1.75 } />
					</button>
				</div>

				{ showProjection && (
					<div className="mx-8 mb-5 rounded-xl bg-[#F0EBFF] border border-[#D9CCF7] px-5 py-4 flex items-center gap-4 flex-wrap">
						<span className="inline-flex items-center gap-3">
							<span className="w-12 h-12 rounded-xl flex items-center justify-center text-3xl font-bold" style={ { background: nowStyle.bg, color: nowStyle.fg } } aria-label={ sprintf(
								/* translators: %s: grade letter. */
								__( 'Grade %s now', 'image-sizes' ),
								stats.grade,
							) }>{ stats.grade }</span>
							<ArrowRight size={ 20 } strokeWidth={ 1.75 } className="text-thumbpress-primary" aria-hidden="true" />
							<span className="w-12 h-12 rounded-xl flex items-center justify-center text-3xl font-bold" style={ { background: nextStyle.bg, color: nextStyle.fg } } aria-label={ sprintf(
								/* translators: %s: grade letter. */
								__( 'Grade %s with Pro', 'image-sizes' ),
								projectedGrade,
							) }>{ projectedGrade }</span>
						</span>
						<span className="text-thumbpress-title font-medium tabular-nums">
							{ sprintf(
								/* translators: 1: score now, 2: score with Pro. */
								__( 'From %1$s to about %2$s with Pro', 'image-sizes' ),
								numberFormat( stats.health_score ),
								numberFormat( projected ),
							) }
						</span>
						<span className="text-xs leading-4 text-[#64748B]">{ __( 'Estimate from the health score’s own weights, if every open item below were fixed.', 'image-sizes' ) }</span>
					</div>
				) }

				<div className="px-8 pb-2">
					<h3 className="m-0 mb-2 text-base leading-6 font-semibold text-thumbpress-title">{ __( 'Fix now, free', 'image-sizes' ) }</h3>

					{ current === null ? (
						<p className="m-0 py-3 text-[#64748B]">{ __( 'Reading your settings…', 'image-sizes' ) }</p>
					) : (
						<ul className="list-none m-0 p-0">
							{ FREE_FIXES.map( ( fix ) => {
								const on = current[ fix.key ] === true;

								if ( on ) {
									return (
										<li key={ fix.key } className="flex items-start gap-3 py-3 border-t border-slate-100">
											<span className="w-5 h-5 rounded-full bg-[#D1FAE5] text-[#047857] flex items-center justify-center shrink-0"><Check size={ 12 } strokeWidth={ 2.5 } aria-hidden="true" /></span>
											<span className="grow">
												<span className="block font-medium text-thumbpress-title">{ fix.label }</span>
												<span className="block text-xs leading-4 text-[#64748B] mt-0.5">{ __( 'Already on.', 'image-sizes' ) }</span>
											</span>
										</li>
									);
								}

								return (
									<li key={ fix.key } className="border-t border-slate-100">
										<label className="flex items-start gap-3 py-3 cursor-pointer">
											<Checkbox
												checked={ !! picked[ fix.key ] }
												onCheckedChange={ ( value ) => setPicked( ( state ) => ( { ...state, [ fix.key ]: !! value } ) ) }
											/>
											<span className="grow">
												<span className="block font-medium text-thumbpress-title">{ fix.label }</span>
												<span className="block text-xs leading-4 text-[#64748B] mt-0.5">{ fix.note }</span>
											</span>
										</label>
									</li>
								);
							} ) }

							{ stats.not_webp > 0 && (
								<li className="flex items-start gap-3 py-3 border-t border-slate-100">
									<span className="w-5 shrink-0" />
									<a href="#/convert-to-webp" onClick={ onClose } className="grow no-underline">
										<span className="block font-medium text-thumbpress-primary">
											{ sprintf(
												/* translators: %s: number of images not in WebP. */
												__( 'Convert %s existing images to WebP', 'image-sizes' ),
												numberFormat( stats.not_webp ),
											) }
										</span>
										<span className="block text-xs leading-4 text-[#64748B] mt-0.5">{ __( 'Runs from its own page, in the background if you like.', 'image-sizes' ) }</span>
									</a>
								</li>
							) }

							<li className="flex items-start gap-3 py-3 border-t border-slate-100">
								<span className="w-5 shrink-0" />
								<a href="#/settings?tab=thumbnails" onClick={ onClose } className="grow no-underline">
									<span className="block font-medium text-thumbpress-primary">{ __( 'Choose which thumbnail sizes to stop making', 'image-sizes' ) }</span>
									<span className="block text-xs leading-4 text-[#64748B] mt-0.5">{ __( 'Only you know which sizes your theme uses, so this stays a choice.', 'image-sizes' ) }</span>
								</a>
							</li>
						</ul>
					) }

					{ applied.length > 0 && (
						<div role="status" className="mt-2 rounded-lg bg-[#D1FAE5] text-[#047857] px-4 py-3 font-medium">
							{ sprintf(
								/* translators: %s: number of settings applied. */
								_n( 'Done. %s setting is on.', 'Done. %s settings are on.', applied.length, 'image-sizes' ),
								numberFormat( applied.length ),
							) }
						</div>
					) }
				</div>

				{ rows.length > 0 && (
					<div className="px-8 pt-4 pb-2">
						<h3 className="m-0 mb-2 text-base leading-6 font-semibold text-thumbpress-title">
							{ pro ? __( 'Also on this site', 'image-sizes' ) : __( 'Pro would also', 'image-sizes' ) }
						</h3>
						<ul className="list-none m-0 p-0">
							{ rows.map( ( row ) => (
								<li key={ row.key } className="flex items-center gap-3 py-3 border-t border-slate-100">
									<a href={ row.soon ? row.to : `#${ row.to }` } onClick={ onClose } className="grow no-underline !text-inherit">
										<span className="block text-thumbpress-body tabular-nums"><span className="text-[15px] font-bold text-thumbpress-title">{ row.num }</span> { row.label }</span>
										<span className="block text-xs leading-4 text-[#64748B] mt-0.5">{ row.note }</span>
									</a>
									{ row.soon ? <SoonTag /> : ! pro && <ProPill src="fix-everything-tag" /> }
								</li>
							) ) }
						</ul>
					</div>
				) }

				</div>

				<div className="flex max-sm:flex-col sm:items-center justify-between gap-4 px-8 py-5 border-t border-slate-100 shrink-0 bg-white">
					{ ! pro ? (
						<div className="flex flex-col gap-1">
							<a href={ COMPRESSION_CHECK_PRO_LINK.replace( 'compression-check', 'fix-everything' ) } onClick={ onClose } className="font-semibold text-thumbpress-primary no-underline">
								{ __( 'See what Pro does', 'image-sizes' ) }
							</a>
							<span className="text-xs leading-4 text-[#64748B]">{ __( 'Try Pro risk-free for 30 days', 'image-sizes' ) }</span>
						</div>
					) : <span /> }

					<button
						type="button"
						onClick={ apply }
						disabled={ busy || chosen.length === 0 }
						className="h-11 px-6 rounded-lg border-0 bg-thumbpress-primary text-white text-[15px] font-semibold cursor-pointer disabled:opacity-50 disabled:cursor-default"
					>
						{ chosen.length > 0
							? sprintf(
								/* translators: %s: number of free fixes ticked. */
								_n( 'Apply %s free fix', 'Apply %s free fixes', chosen.length, 'image-sizes' ),
								numberFormat( chosen.length ),
							)
							: __( 'Apply free fixes', 'image-sizes' ) }
					</button>
				</div>
			</div>
		</div>
	);
}
