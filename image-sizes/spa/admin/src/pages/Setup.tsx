import React, { useEffect, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Navigate, useLocation, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { toast } from 'sonner';
import { ArrowRight, Check, FlaskConical, ImageIcon, RefreshCw } from 'lucide-react';
import {
	getDashboardAnalysisStats,
	getDashboardCountsStats,
	getDashboardOptimizationStats,
	getPluginSettings,
	getScanProgress,
	getSetupState,
	saveSetupState,
	savePluginSettings,
	startScan,
	type DashboardAnalysisStats,
	type DashboardCountsStats,
	type DashboardOptimizationStats,
	type ScanProgress,
	type SetupPayload,
	type SiteType,
} from '../api';
import { formatBytes, numberFormat } from '../lib/i18n';
import { useCompressionCheck } from '../components/dashboard/useCompressionCheck';
import { gradeStyle, heroSentence, type DashboardModelStats } from '../components/dashboard/model';
import GradeChip from '../components/dashboard/GradeChip';
import BeforeAfterModal from '../components/setup/BeforeAfterModal';

/** Each screen has its own address (#/setup/grade), so Back, refresh and a shared link all land on the right one. */
const STEPS = [ 'start', 'checking', 'grade', 'photos' ] as const;
type StepName = typeof STEPS[ number ];
type Step = 0 | 1 | 2 | 3;

/** The grade opens on its own after this long, or as soon as the scan finishes. */
const AUTO_GRADE_MS = 8000;
const POLL_MS = 2500;

/** The grade's button waits this long for the photos, then lets you go on without them: a big library can take far longer to scan. */
const PHOTOS_WAIT_MS = 8000;

const SITE_TYPES: { value: SiteType; label: string }[] = [
	{ value: 'blog', label: __( 'Blog or content site', 'image-sizes' ) },
	{ value: 'store', label: __( 'Online store', 'image-sizes' ) },
	{ value: 'photo', label: __( 'Photography or portfolio', 'image-sizes' ) },
	{ value: 'business', label: __( 'Business site', 'image-sizes' ) },
	{ value: 'agency', label: __( 'Agency managing client sites', 'image-sizes' ) },
];

const FREE_SETTINGS: { key: 'webp_on_upload' | 'lazy_load'; label: string }[] = [
	{ key: 'webp_on_upload', label: __( 'WebP for new uploads', 'image-sizes' ) },
	{ key: 'lazy_load', label: __( 'Lazy loading', 'image-sizes' ) },
];

const eyebrow = 'text-xs leading-4 font-semibold tracking-[0.6px] uppercase text-thumbpress-primary';
const primaryButton = 'h-12 px-7 border-0 rounded-lg bg-thumbpress-primary text-white text-base font-semibold inline-flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-default';

/** One line under a category's grade: what is wrong there, in plain words. */
function categoryLine( key: string, s: DashboardModelStats, reclaimable: number, counting: boolean ): string {
	switch ( key ) {
		case 'speed':
			return s.large_images > 0
				? sprintf(
					/* translators: %s: number of images over 1 MB. */
					__( '%s images over 1 MB', 'image-sizes' ),
					numberFormat( s.large_images ),
				)
				: __( 'No image is over 1 MB', 'image-sizes' );
		case 'seo':
			return s.missing_alt > 0
				? sprintf(
					/* translators: %s: number of images with no alt text. */
					__( '%s images Google can’t read (no alt text)', 'image-sizes' ),
					numberFormat( s.missing_alt ),
				)
				: __( 'Every image has alt text', 'image-sizes' );
		case 'storage':
			return ( reclaimable > 0
				? sprintf(
					/* translators: %s: size that could be reclaimed, e.g. "2.1 GB". */
					__( '%s you could get back', 'image-sizes' ),
					formatBytes( reclaimable, true ),
				)
				: __( 'Nothing to reclaim yet', 'image-sizes' ) ) + ( counting ? ' · ' + __( 'duplicates still counting…', 'image-sizes' ) : '' );
		default:
			return s.hotlink ? __( 'Hotlink protection is on', 'image-sizes' ) : __( 'Hotlink protection is off', 'image-sizes' );
	}
}

export default function Setup() {
	const navigate = useNavigate();
	const [ params ] = useSearchParams();
	const requestedMode = params.get( 'mode' );
	const mode: 'fresh' | 'upgrade' | 'manual' = requestedMode === 'upgrade' || requestedMode === 'manual' ? requestedMode : 'fresh';

	const { step: stepParam } = useParams();
	const location = useLocation();
	const known = STEPS.includes( stepParam as StepName );
	const step = ( known ? STEPS.indexOf( stepParam as StepName ) : 0 ) as Step;

	// Keep the query (?mode=) when moving between screens.
	const go = ( to: Step, replace = false ) => navigate( { pathname: `/setup/${ STEPS[ to ] }`, search: location.search }, { replace } );
	const [ payload, setPayload ] = useState< SetupPayload | null >( null );
	const [ counts, setCounts ] = useState< DashboardCountsStats | null >( null );
	const [ optimization, setOptimization ] = useState< DashboardOptimizationStats | null >( null );
	const [ analysis, setAnalysis ] = useState< DashboardAnalysisStats | null >( null );
	const [ progress, setProgress ] = useState< ScanProgress | null >( null );
	const [ settings, setSettings ] = useState< Record< string, boolean > | null >( null );
	const [ siteType, setSiteType ] = useState< SiteType >( 'blog' );
	const [ typeMenu, setTypeMenu ] = useState( false );
	const [ busy, setBusy ] = useState( false );
	const [ applied, setApplied ] = useState( 0 );
	const [ settledGrade, setSettledGrade ] = useState( '' );
	const [ photosWaitOver, setPhotosWaitOver ] = useState( false );
	const startedAt = useRef( 0 );
	const shownGrade = useRef( '' );
	const checkStarted = useRef( false );

	const check = useCompressionCheck( null );

	// The checkup owns the screen: cover the admin chrome, and stop the page behind it scrolling.
	useEffect( () => {
		const before = document.body.style.overflow;
		document.body.style.overflow = 'hidden';

		return () => {
			document.body.style.overflow = before;
		};
	}, [] );

	useEffect( () => {
		( async () => {
			try {
				const res: any = await getSetupState();
				if ( res?.data ) {
					setPayload( res.data );
					setSiteType( res.data.state.site_type || res.data.detected_type );
				}
			} catch {
				// The checkup still works without saved state.
			}
			try {
				const [ c, o, p, s ]: any[] = await Promise.all( [
					getDashboardCountsStats(),
					getDashboardOptimizationStats(),
					getScanProgress(),
					getPluginSettings(),
				] );
				if ( c?.data ) setCounts( c.data );
				if ( o?.data ) setOptimization( o.data );
				if ( p?.data ) setProgress( p.data );
				if ( s?.data ) setSettings( s.data );
			} catch {
				// Numbers fill in as the polls succeed.
			}
		} )();
	}, [] );

	// While the scan runs the numbers move, so the grade screen reads them from the same endpoints as the dashboard.
	useEffect( () => {
		if ( step === 0 ) {
			return;
		}

		let stop = false;

		const read = async () => {
			try {
				const [ p, a, c ]: any[] = await Promise.all( [ getScanProgress(), getDashboardAnalysisStats(), getDashboardCountsStats() ] );
				if ( stop ) return;
				if ( p?.data ) setProgress( p.data );
				if ( a?.data ) setAnalysis( a.data );
				if ( c?.data ) setCounts( c.data );
			} catch {
				// The next poll tells the same story.
			}
		};

		read();
		const timer = setInterval( read, POLL_MS );

		return () => {
			stop = true;
			clearInterval( timer );
		};
	}, [ step ] );

	const scanDone = !! progress && ! progress.is_running && progress.is_ready;

	// The grade opens on its own once there is something to show, or the moment the scan is done.
	useEffect( () => {
		if ( step !== 1 || ! analysis ) {
			return;
		}

		const wait = Math.max( 0, AUTO_GRADE_MS - ( Date.now() - startedAt.current ) );
		// Replace, not push: Back from the grade must not land on a screen that immediately skips forward.
		const timer = setTimeout( () => go( 2, true ), scanDone ? 0 : wait );

		return () => clearTimeout( timer );
	}, [ step, analysis, scanDone ] );

	// Once the library is scanned, test a few photos on copies so the last screen can show them.
	useEffect( () => {
		if ( step >= 2 && scanDone && ! checkStarted.current && ( counts?.total_images ?? 0 ) > 0 ) {
			checkStarted.current = true;
			check.run();
		}
	}, [ step, scanDone, counts?.total_images ] );

	// The grade shown while the scan was still running may not be the final one; say so once.
	useEffect( () => {
		if ( step === 2 && ! shownGrade.current && analysis?.grade ) {
			shownGrade.current = analysis.grade;
		}

		if ( step >= 2 && scanDone && analysis?.grade && shownGrade.current && analysis.grade !== shownGrade.current ) {
			setSettledGrade( analysis.grade );
		}
	}, [ step, scanDone, analysis?.grade ] );

	const photosReady = !! check.result && !! check.samples && check.samples.length > 0;

	useEffect( () => {
		if ( step !== 2 ) {
			return;
		}

		const timer = setTimeout( () => setPhotosWaitOver( true ), PHOTOS_WAIT_MS );

		return () => clearTimeout( timer );
	}, [ step ] );

	// With images to show, hold the button until the photos are ready, the check has failed and there are none to wait for,
	// or the wait is over: then the button goes to the dashboard, the scan carries on there, and turns into "See my photos" if they arrive first.
	const waitingForPhotos = ( counts?.total_images ?? 0 ) > 0 && ! photosReady && ! photosWaitOver && ( ! scanDone || check.running );

	// A screen that needs earlier work sends you back to it: a refreshed or shared link must not strand you.
	useEffect( () => {
		if ( ! progress || ! counts ) {
			return;
		}

		const hasImages = counts.total_images > 0;
		const scanned = progress.is_running || progress.is_ready;

		if ( step === 1 && ! scanned ) {
			go( 0, true );
		} else if ( step === 2 && hasImages && ! scanned ) {
			go( 0, true );
		} else if ( step === 3 && ! photosReady ) {
			go( hasImages && scanned ? 2 : 0, true );
		}
	}, [ step, progress, counts, check.result, check.samples ] );

	const skip = async () => {
		try {
			await saveSetupState( { skipped: true } );
		} finally {
			navigate( '/' );
		}
	};

	const start = async () => {
		setBusy( true );
		try {
			// Take the scan's own answer, or the guard below would read the old "not scanned" and send us back.
			const started: any = await startScan();
			if ( started?.data ) setProgress( started.data );
			saveSetupState( { started: true, mode, site_type: siteType } ).catch( () => {} );
			startedAt.current = Date.now();
			go( 1 );
		} catch {
			toast.error( __( 'Could not start the scan. You can skip setup and try again from the dashboard.', 'image-sizes' ) );
		} finally {
			setBusy( false );
		}
	};

	const changeSiteType = ( value: SiteType ) => {
		setSiteType( value );
		setTypeMenu( false );
		saveSetupState( { site_type: value } ).catch( () => {} );
	};

	const pending = settings ? FREE_SETTINGS.filter( ( item ) => ! settings[ item.key ] ) : [];

	const apply = async () => {
		setBusy( true );
		try {
			if ( pending.length > 0 ) {
				const changes: Record< string, boolean > = {};
				pending.forEach( ( item ) => { changes[ item.key ] = true; } );
				await savePluginSettings( changes );
				setSettings( ( current ) => ( { ...( current ?? {} ), ...changes } ) );
			}

			setApplied( pending.length );

			await saveSetupState( {
				completed: true,
				mode,
				site_type: siteType,
				free_fixes_applied: pending.map( ( item ) => item.key ),
				...( analysis?.grade ? { grade_before: analysis.grade, score_before: analysis.health_score } : {} ),
			} );

			if ( photosReady ) {
				go( 3 );
			} else {
				navigate( '/' );
			}
		} catch {
			toast.error( __( 'Could not save. Nothing was changed.', 'image-sizes' ) );
		} finally {
			setBusy( false );
		}
	};

	const stats: DashboardModelStats | null = counts && analysis
		? {
			scan_running: !! progress?.is_running,
			...counts,
			total_thumbnails: optimization?.total_thumbnails ?? 0,
			unoptimized_images: optimization?.unoptimized_images ?? 0,
			compressed: optimization?.compressed ?? 0,
			not_compressed: optimization?.not_compressed ?? 0,
			...analysis,
		}
		: null;

	// A site that already has Pro is not sold Pro: the upsell links go away, the rest stays.
	const pro = payload?.pro_active ?? !! window.THUMBPRESS?.pro_active;
	const images = counts?.total_images ?? 0;
	const thumbnails = optimization?.total_thumbnails ?? 0;
	const percent = progress?.total ? progress.percent : 0;
	const reclaimable = stats ? stats.duplicate_bytes + ( stats.likely_unused?.bytes ?? 0 ) + ( check.result?.saved_bytes ?? 0 ) : 0;
	const style = gradeStyle( analysis?.grade ?? '' );
	const showBar = step >= 2 && !! progress && ( progress.is_running || scanDone );

	if ( ! known ) {
		return <Navigate to={ { pathname: '/setup/start', search: location.search } } replace />;
	}

	return (
		<div className="fixed inset-0 z-[99999] bg-[#F0F0F1] flex flex-col overflow-y-auto font-inter text-thumbpress-body text-sm leading-5">
			<header className="h-16 shrink-0 flex items-center justify-between px-8 bg-white border-b border-[#E2E8F0]">
				<div className="flex items-center gap-2.5">
					<span className="w-[30px] h-[30px] rounded-full bg-thumbpress-primary text-white flex items-center justify-center"><ImageIcon size={ 16 } strokeWidth={ 1.75 } aria-hidden="true" /></span>
					<span className="text-xl font-bold text-thumbpress-title">{ __( 'ThumbPress', 'image-sizes' ) }</span>
					<span className="text-[13px] text-[#64748B] pl-3 ml-1 border-l border-[#E2E8F0]">{ __( 'Site Image Checkup', 'image-sizes' ) }</span>
				</div>
				<button type="button" onClick={ skip } className="border-0 bg-transparent p-0 font-medium text-thumbpress-body cursor-pointer">{ __( 'Skip setup', 'image-sizes' ) }</button>
			</header>

			<main className={ `grow flex flex-col items-center px-4 pt-16 ${ showBar ? 'pb-24' : 'pb-16' }` }>
				{ /* START */ }
				{ step === 0 && (
					<section className="w-full max-w-[640px] flex flex-col items-center gap-6 text-center pt-16">
						<span className={ eyebrow }>{ __( '60-second checkup', 'image-sizes' ) }</span>
						<h1 className="m-0 text-[40px] leading-[48px] font-bold text-thumbpress-title tracking-[-0.5px]">{ __( 'Let’s check your images.', 'image-sizes' ) }</h1>

						<div className="grid grid-cols-2 w-full bg-white border border-[#E2E8F0] rounded-xl overflow-hidden">
							<div className="p-6 flex flex-col gap-1 border-r border-[#E2E8F0]">
								<span className="text-4xl leading-[44px] font-bold text-thumbpress-title tabular-nums">{ counts ? numberFormat( images ) : '–' }</span>
								<span className="text-[#64748B]">{ __( 'images in your library', 'image-sizes' ) }</span>
							</div>
							<div className="p-6 flex flex-col gap-1">
								<span className="text-4xl leading-[44px] font-bold text-thumbpress-title tabular-nums">{ optimization ? numberFormat( thumbnails ) : '–' }</span>
								<span className="text-[#64748B]">{ __( 'thumbnails WordPress made from them', 'image-sizes' ) }</span>
							</div>
						</div>

						{ counts && images === 0 ? (
							<>
								<p className="m-0 text-[17px] leading-[26px] max-w-[520px]">{ __( 'You’re starting clean. Switch on the free settings now and new uploads stay light from the first one.', 'image-sizes' ) }</p>
								<button type="button" onClick={ () => go( 2 ) } className={ primaryButton }>{ __( 'Choose my settings', 'image-sizes' ) }<ArrowRight size={ 18 } strokeWidth={ 1.75 } aria-hidden="true" /></button>
							</>
						) : (
							<>
								<p className="m-0 text-[17px] leading-[26px] max-w-[520px] [text-wrap:pretty]">{ __( 'We’ll scan your library and show your grade in a few seconds. Nothing changes until you say so.', 'image-sizes' ) }</p>
								<button type="button" onClick={ start } disabled={ busy || ! counts } className={ primaryButton }>{ __( 'Start checkup', 'image-sizes' ) }<ArrowRight size={ 18 } strokeWidth={ 1.75 } aria-hidden="true" /></button>
							</>
						) }
					</section>
				) }

				{ /* CHECKING */ }
				{ step === 1 && (
					<section aria-live="polite" className="w-full max-w-[640px] flex flex-col items-center gap-7 text-center pt-16">
						<span className={ eyebrow }>{ __( '60-second checkup', 'image-sizes' ) }</span>
						<h1 className="m-0 text-[40px] leading-[48px] font-bold text-thumbpress-title tracking-[-0.5px]">{ __( 'Checking your images…', 'image-sizes' ) }</h1>

						<div className="w-full flex flex-col gap-2.5">
							<div className="flex justify-between tabular-nums">
								<span>{ sprintf(
									/* translators: 1: images checked, 2: images in the library. */
									__( '%1$s of %2$s images checked', 'image-sizes' ),
									numberFormat( progress?.processed ?? 0 ),
									numberFormat( progress?.total ?? images ),
								) }</span>
								<span className="font-semibold text-thumbpress-primary">{ percent }%</span>
							</div>
							<div role="progressbar" aria-valuenow={ percent } aria-valuemin={ 0 } aria-valuemax={ 100 } aria-label={ __( 'Checking progress', 'image-sizes' ) } className="h-2 rounded-full bg-[#E4DCFA] overflow-hidden">
								<div className="h-2 rounded-full bg-thumbpress-primary transition-all" style={ { width: `${ percent }%` } } />
							</div>
						</div>

						<div className="w-full grid grid-cols-3 gap-3">
							{ [
								[ analysis?.large_images ?? 0, __( 'over 1 MB', 'image-sizes' ) ],
								[ analysis?.missing_alt ?? 0, __( 'without alt text', 'image-sizes' ) ],
								[ counts?.not_webp ?? 0, __( 'not WebP yet', 'image-sizes' ) ],
							].map( ( [ value, label ] ) => (
								<div key={ String( label ) } className="bg-white border border-[#E2E8F0] rounded-xl p-5 flex flex-col gap-1">
									<span className="text-[32px] leading-10 font-bold text-thumbpress-title tabular-nums">{ numberFormat( value as number ) }</span>
									<span className="text-[#64748B]">{ label }</span>
								</div>
							) ) }
						</div>

						<button type="button" onClick={ () => go( 2, true ) } className={ primaryButton }>{ __( 'See my grade', 'image-sizes' ) }<ArrowRight size={ 18 } strokeWidth={ 1.75 } aria-hidden="true" /></button>
						<p className="m-0 text-[#64748B]">{ __( 'Opens on its own in a few seconds. Duplicates keep counting in the background.', 'image-sizes' ) }</p>
					</section>
				) }

				{ /* GRADE */ }
				{ step >= 2 && (
					<section className="w-full max-w-[720px] flex flex-col gap-6">
						{ images === 0 ? (
							<div className="rounded-2xl bg-[#1E0F53] text-white p-8">
								<p className="m-0 text-xl leading-7 font-semibold">{ __( 'You’re starting clean.', 'image-sizes' ) }</p>
								<p className="m-0 mt-2 text-[#E9E3FF]">{ __( 'There is nothing to grade yet. Your grade appears after your first uploads and a scan.', 'image-sizes' ) }</p>
							</div>
						) : ! stats ? (
							<div className="rounded-2xl bg-white border border-[#E2E8F0] p-8 text-[#64748B]" aria-busy="true">{ __( 'Reading your numbers…', 'image-sizes' ) }</div>
						) : (
							<div className="rounded-2xl overflow-hidden border border-[#E2E8F0] bg-white">
								<div className="bg-[#1E0F53] text-white p-8 flex items-center gap-7 flex-wrap">
									<div className="w-28 h-28 rounded-[18px] flex items-center justify-center text-[80px] leading-[80px] font-bold shrink-0" style={ { background: style.bg, color: style.fg } } aria-label={ sprintf(
										/* translators: %s: grade letter. */
										__( 'Grade %s', 'image-sizes' ),
										analysis?.grade ?? '',
									) }>{ analysis?.grade }</div>
									<div className="flex flex-col gap-2 min-w-0 flex-1">
										<span className="flex items-center gap-2.5 flex-wrap text-[13px] leading-4 font-semibold text-[#C9BFF0] tabular-nums">
											{ sprintf(
												/* translators: %s: score out of 100. */
												__( 'Your image grade · %s / 100', 'image-sizes' ),
												numberFormat( analysis?.health_score ?? 0 ),
											) }
											{ progress?.is_running && <span className="text-xs font-medium px-2 py-0.5 rounded-full border border-[#5B45A8] text-[#E9E3FF]">{ __( 'Partial data', 'image-sizes' ) }</span> }
											<span className="relative">
												<button type="button" onClick={ () => setTypeMenu( ( open ) => ! open ) } aria-haspopup="listbox" aria-expanded={ typeMenu } className="text-xs font-medium px-2 py-0.5 rounded-full border border-[#5B45A8] text-[#E9E3FF] bg-transparent cursor-pointer">
													{ sprintf(
														/* translators: %s: kind of site, e.g. "Online store". */
														__( '%s · change', 'image-sizes' ),
														SITE_TYPES.find( ( type ) => type.value === siteType )?.label ?? '',
													) }
												</button>
												{ typeMenu && (
													<ul role="listbox" className="absolute left-0 top-full mt-1 z-10 list-none m-0 p-1 w-64 bg-white rounded-lg shadow-lg border border-[#E2E8F0] text-thumbpress-title font-normal">
														{ SITE_TYPES.map( ( type ) => (
															<li key={ type.value } role="option" aria-selected={ type.value === siteType }>
																<button type="button" onClick={ () => changeSiteType( type.value ) } className="w-full text-left px-3 py-2 border-0 bg-transparent rounded-md cursor-pointer hover:bg-[#F0EBFF] text-[13px]">{ type.label }</button>
															</li>
														) ) }
													</ul>
												) }
											</span>
										</span>
										<p className="m-0 text-xl leading-7 font-semibold [text-wrap:pretty]">{ heroSentence( stats ) }</p>
										{ check.result && check.result.saved_bytes > 0 && (
											<span className="flex items-center gap-2 flex-wrap text-[15px] text-[#E9E3FF] tabular-nums">
												{ sprintf(
													/* translators: %s: size compression could save, e.g. "2.1 GB". */
													__( 'You could get back ~%s', 'image-sizes' ),
													formatBytes( check.result.saved_bytes, true ),
												) }
												<span
													title={ sprintf(
														/* translators: 1: number of photos tested, 2: percent smaller. */
														__( 'Tested on copies of %1$s of your images: %2$s%% smaller on average. Nothing on your site changed.', 'image-sizes' ),
														numberFormat( check.result.samples_tested ),
														numberFormat( check.result.saved_pct ),
													) }
													className="inline-flex items-center gap-1 text-xs leading-4 font-medium px-2 py-px rounded-full bg-[#33207A] text-[#E9E3FF]"
												>
													<FlaskConical size={ 12 } strokeWidth={ 1.75 } aria-hidden="true" />
													{ __( 'Estimate', 'image-sizes' ) }
												</span>
											</span>
										) }
									</div>
								</div>

								{ ( [ [ 'speed', __( 'Speed', 'image-sizes' ) ], [ 'seo', __( 'SEO', 'image-sizes' ) ], [ 'storage', __( 'Storage', 'image-sizes' ) ], [ 'protect', __( 'Protect', 'image-sizes' ) ] ] as const ).map( ( [ key, name ] ) => (
									<div key={ key } className="flex items-center gap-4 h-[60px] px-8 border-b border-slate-100 last:border-b-0">
										<span className="w-20 font-semibold text-thumbpress-title">{ name }</span>
										<span className="w-[68px] flex"><GradeChip grade={ analysis?.scores?.[ key ]?.grade ?? '' } score={ analysis?.scores?.[ key ]?.score } /></span>
										<span className="tabular-nums">{ categoryLine( key, stats, reclaimable, !! progress?.is_running ) }</span>
									</div>
								) ) }
							</div>
						) }

						{ settledGrade && (
							<div role="status" className="rounded-lg bg-[#FEF3C7] text-[#92400E] px-4 py-3">
								{ sprintf(
									/* translators: %s: grade letter. */
									__( 'The scan finished and counted more. Your grade is now %s.', 'image-sizes' ),
									settledGrade,
								) }
							</div>
						) }

						<div className="rounded-xl border border-[#BBF7D0] bg-[#F0FDF4] px-8 py-[18px] flex items-start gap-4">
							<span className="w-8 h-8 rounded-lg bg-[#D1FAE5] text-[#047857] flex items-center justify-center shrink-0"><Check size={ 16 } strokeWidth={ 2 } aria-hidden="true" /></span>
							<span className="flex flex-col gap-0.5">
								<span className="font-semibold text-thumbpress-title">
									{ ! settings
										? __( 'Reading your settings…', 'image-sizes' )
										: pending.length > 0
											? sprintf(
												/* translators: %s: number of free settings that can be switched on. */
												_n( 'Quick win: %s free setting switches on in one click', 'Quick win: %s free settings switch on in one click', pending.length, 'image-sizes' ),
												numberFormat( pending.length ),
											)
											: __( 'Your free settings are already on', 'image-sizes' ) }
								</span>
								<span className="text-[13px] leading-[18px]">
									{ pending.length > 0
										? sprintf(
											/* translators: %s: names of the settings, joined with a middle dot. */
											__( '%s. They apply to new uploads and visitors; your existing images are not touched.', 'image-sizes' ),
											pending.map( ( item ) => item.label ).join( ' · ' ),
										)
										: __( 'WebP for new uploads and lazy loading are both on.', 'image-sizes' ) }
								</span>
							</span>
						</div>

						<div className="flex justify-end pt-2">
							<div className="flex flex-col items-end gap-2.5">
								{ step === 2 && (
									<button type="button" onClick={ apply } disabled={ busy || ! settings || waitingForPhotos } className={ primaryButton.replace( 'h-12 px-7', 'h-12 px-6 text-[15px]' ) }>
										{ waitingForPhotos
											? __( 'Getting your photos ready…', 'image-sizes' )
											: pending.length > 0
											? ( photosReady ? __( 'Apply free fixes and see my photos', 'image-sizes' ) : __( 'Apply free fixes and go to dashboard', 'image-sizes' ) )
											: ( photosReady ? __( 'See my photos', 'image-sizes' ) : __( 'Go to dashboard', 'image-sizes' ) ) }
										<ArrowRight size={ 16 } strokeWidth={ 1.75 } aria-hidden="true" />
									</button>
								) }
								<span className="text-[13px] text-[#64748B]">
									{ __( 'Nothing else changes.', 'image-sizes' ) }
									{ ! pro && <>{ ' ' }<a href="#/pro?src=wizard-grade" className="font-semibold text-thumbpress-primary">{ __( 'Or see how Pro gets you to an A', 'image-sizes' ) }</a></> }
								</span>
							</div>
						</div>
					</section>
				) }
			</main>

			{ /* Bottom scan bar: hands off to the dashboard's scan panel afterwards. */ }
			{ showBar && progress && (
				<div aria-live="polite" className="fixed left-0 right-0 bottom-0 h-14 bg-white border-t border-[#E2E8F0] flex justify-center z-10">
					<div className="w-full max-w-[720px] px-4 flex items-center gap-3">
						{ progress.is_running ? (
							<>
								<span className="w-[22px] h-[22px] rounded-full bg-[#F0EBFF] text-thumbpress-primary flex items-center justify-center shrink-0"><RefreshCw size={ 13 } strokeWidth={ 1.75 } aria-hidden="true" /></span>
								<span className="text-[13px] whitespace-nowrap tabular-nums">{ sprintf(
									/* translators: 1: images checked, 2: images in the library. */
									__( 'Still scanning in the background · %1$s of %2$s', 'image-sizes' ),
									numberFormat( progress.processed ),
									numberFormat( progress.total ),
								) }</span>
								<div role="progressbar" aria-valuenow={ progress.percent } aria-valuemin={ 0 } aria-valuemax={ 100 } aria-label={ __( 'Scan progress', 'image-sizes' ) } className="grow h-1.5 rounded-full bg-[#E4DCFA] overflow-hidden">
									<div className="h-1.5 rounded-full bg-thumbpress-primary" style={ { width: `${ progress.percent }%` } } />
								</div>
								<span className="text-[13px] font-semibold text-thumbpress-primary tabular-nums">{ progress.percent }%</span>
							</>
						) : (
							<>
								<span className="w-[22px] h-[22px] rounded-full bg-[#D1FAE5] text-[#047857] flex items-center justify-center shrink-0"><Check size={ 13 } strokeWidth={ 2 } aria-hidden="true" /></span>
								<span className="text-[13px] tabular-nums">
									<strong>{ __( 'Scan complete.', 'image-sizes' ) }</strong>{ ' ' }
									{ sprintf(
										/* translators: %s: number of images checked. */
										__( 'All %s images checked.', 'image-sizes' ),
										numberFormat( progress.indexed || progress.total ),
									) }{ ' ' }
									{ settledGrade
										? sprintf(
											/* translators: %s: grade letter. */
											__( 'Your grade is now %s.', 'image-sizes' ),
											settledGrade,
										)
										: sprintf(
											/* translators: %s: grade letter. */
											__( 'Your grade stays %s.', 'image-sizes' ),
											analysis?.grade ?? '',
										) }
								</span>
							</>
						) }
					</div>
				</div>
			) }

			{ step === 3 && check.result && check.samples && (
				<BeforeAfterModal samples={ check.samples } result={ check.result } applied={ applied } pro={ pro } onClose={ () => navigate( '/' ) } />
			) }
		</div>
	);
}
