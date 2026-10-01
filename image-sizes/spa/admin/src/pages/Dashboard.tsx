import React, { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';

import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';
import { ScanBar, ScanFooter, useScan } from '../components/dashboard/ScanPanel';
import GradeHero from '../components/dashboard/GradeHero';
import CategoryCard from '../components/dashboard/CategoryCard';
import ProBand from '../components/dashboard/ProBand';
import SavedByPro from '../components/dashboard/SavedByPro';
import ActivityCard from '../components/dashboard/ActivityCard';
import QuickActions from '../components/dashboard/QuickActions';
import CompressionCheckModal from '../components/dashboard/CompressionCheckModal';
import FixEverythingModal from '../components/dashboard/FixEverythingModal';
import CheckupCard from '../components/dashboard/CheckupCard';
import CheckupToast from '../components/dashboard/CheckupToast';
import { useCompressionCheck } from '../components/dashboard/useCompressionCheck';
import { compressionHealthGain } from '../components/dashboard/compression-check';
import { buildActivity, buildCards, buildHero, type DashboardModelStats } from '../components/dashboard/model';
import {
	getDashboardCountsStats,
	getDashboardOptimizationStats,
	getDashboardAnalysisStats,
	getSetupState,
	saveSetupState,
	startScan,
	type ScanProgress,
	type DashboardCountsStats,
	type DashboardOptimizationStats,
	type DashboardAnalysisStats,
	type SetupPayload,
} from '../api';

function DashboardSkeleton() {
	return (
		<>
			<Header title={ __( 'Dashboard', 'image-sizes' ) } />
			<PluginPage>
				<div className="flex flex-col gap-4 animate-pulse" aria-busy="true">
					<div className="h-[160px] bg-[#1E0F53]/90 rounded-xl" />
					<div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
						{ [ ...Array( 4 ) ].map( ( _, i ) => (
							<div key={ i } className="h-[260px] bg-white rounded-xl border border-[#E2E8F0]" />
						) ) }
					</div>
				</div>
			</PluginPage>
		</>
	);
}

const TOAST_KEY = 'thumbpress_checkup_toast_seen';

/** Which checkup's toast has been dismissed; per browser, so a lost value only shows it once more. */
function readToastSeen(): number {
	try {
		return Number( window.localStorage.getItem( TOAST_KEY ) ) || 0;
	} catch {
		return 0;
	}
}

type CachedStats = {
	counts: DashboardCountsStats;
	optimization: DashboardOptimizationStats | null;
	analysis: DashboardAnalysisStats;
};

/**
 * The last numbers this browser saw, drawn at once while fresh ones load, so the dashboard opens without a skeleton.
 * Keyed by site, version and Pro state, so an update or a Pro switch never draws numbers of another shape.
 */
function statsCacheKey(): string {
	const g = window.THUMBPRESS;

	return `thumbpress_dashboard_stats:${ g?.api_base ?? '' }:${ g?.version ?? '' }:${ g?.pro_active ? 'pro' : 'free' }`;
}

function readCachedStats(): CachedStats | null {
	try {
		const cached = JSON.parse( window.localStorage.getItem( statsCacheKey() ) || 'null' );

		return cached?.counts && cached?.analysis ? cached : null;
	} catch {
		return null;
	}
}

function writeCachedStats( stats: CachedStats ) {
	try {
		window.localStorage.setItem( statsCacheKey(), JSON.stringify( stats ) );
	} catch {
		// Private window or full storage: the next load shows the skeleton, nothing else.
	}
}

export default function Dashboard() {
	const [ cached ] = useState( () => readCachedStats() );
	const [ countsStats, setCountsStats ] = useState< DashboardCountsStats | null >( cached?.counts ?? null );
	const [ optimizationStats, setOptimizationStats ] = useState< DashboardOptimizationStats | null >( cached?.optimization ?? null );
	const [ analysisStats, setAnalysisStats ] = useState< DashboardAnalysisStats | null >( cached?.analysis ?? null );
	const [ countsLoading, setCountsLoading ] = useState( ! cached );
	const [ analysisLoading, setAnalysisLoading ] = useState( ! cached );
	const [ scanState, setScanState ] = useState< ScanProgress | null >( null );
	const [ scanBusy, setScanBusy ] = useState( false );
	const [ fixOpen, setFixOpen ] = useState( false );
	const [ setup, setSetup ] = useState< SetupPayload | null >( null );
	const [ toastSeen, setToastSeen ] = useState( () => readToastSeen() );
	const navigate = useNavigate();

	const check = useCompressionCheck( optimizationStats?.compression_check ?? null );

	// Starting from a card hands over to the panel at the top, which owns the polling.
	const requestScan = async () => {
		setScanBusy( true );
		try {
			const res: any = await startScan();
			if ( res?.data ) setScanState( res.data );
			window.dispatchEvent( new CustomEvent( 'thumbpress:scan-started' ) );
			window.scrollTo( { top: 0, behavior: 'smooth' } );
		} catch {
			toast.error( __( 'Could not start the scan.', 'image-sizes' ) );
		} finally {
			setScanBusy( false );
		}
	};

	const refreshAnalysis = async () => {
		try {
			const res: any = await getDashboardAnalysisStats();
			if ( res?.data ) setAnalysisStats( res.data );
		} catch {
			// The last numbers stay on screen.
		}
	};

	const scan = useScan( { onComplete: refreshAnalysis, onState: setScanState } );

	useEffect( () => {
		getSetupState().then( ( res: any ) => res?.data && setSetup( res.data ) ).catch( () => {} );

		// In parallel: each is a full WordPress boot for a few milliseconds of work, so one after another tripled the wait.
		getDashboardCountsStats()
			.then( ( res: any ) => res?.data && setCountsStats( res.data ) )
			.catch( () => {} )
			.finally( () => setCountsLoading( false ) );

		// Only the thumbnail count and the saved check depend on it.
		getDashboardOptimizationStats()
			.then( ( res: any ) => res?.data && setOptimizationStats( res.data ) )
			.catch( () => {} );

		getDashboardAnalysisStats()
			.then( ( res: any ) => res?.data && setAnalysisStats( res.data ) )
			.catch( () => {} )
			.finally( () => setAnalysisLoading( false ) );
	}, [] );

	// Whatever is on screen is what the next visit starts from.
	useEffect( () => {
		if ( countsStats && analysisStats ) {
			writeCachedStats( { counts: countsStats, optimization: optimizationStats, analysis: analysisStats } );
		}
	}, [ countsStats, optimizationStats, analysisStats ] );

	if ( countsLoading || ( analysisLoading && ! analysisStats ) ) {
		return <DashboardSkeleton />;
	}

	if ( ! countsStats || ! analysisStats ) {
		return (
			<>
				<Header title={ __( 'Dashboard', 'image-sizes' ) } />
				<PluginPage>
					<ScanBar scan={ scan } imageCount={ countsStats?.total_images ?? 0 } />
					<ScanFooter scan={ scan } />
				</PluginPage>
			</>
		);
	}

	const pro = !! countsStats.pro_active;
	const scanning = !! scanState?.is_running;

	const stats: DashboardModelStats = {
		scan_running: scanning,
		...countsStats,
		total_thumbnails: optimizationStats?.total_thumbnails ?? 0,
		unoptimized_images: optimizationStats?.unoptimized_images ?? 0,
		compressed: optimizationStats?.compressed ?? 0,
		not_compressed: optimizationStats?.not_compressed ?? 0,
		...analysisStats,
	};

	const cards = buildCards( stats, pro );
	const hero = buildHero( stats, cards );
	const activity = buildActivity( analysisStats.activity, pro );
	const showBand = ! pro && ! hero.never;

	const state = setup?.state;
	const fresh = !! state && ! state.completed_at && ! state.skipped_at && ! state.invite_dismissed_at;
	const justFinished = !! state && state.completed_at > 0 && Date.now() / 1000 - state.completed_at < 1800 && toastSeen !== state.completed_at;

	const dismissCheckup = () => {
		setSetup( ( current ) => current && { ...current, state: { ...current.state, invite_dismissed_at: Math.floor( Date.now() / 1000 ) } } );
		saveSetupState( { invite_dismissed: true } ).catch( () => {} );
	};

	const dismissToast = () => {
		if ( state ) {
			setToastSeen( state.completed_at );
			try {
				window.localStorage.setItem( TOAST_KEY, String( state.completed_at ) );
			} catch {
				// It shows once more on the next visit.
			}
		}
	};

	return (
		<>
			<Header title={ __( 'Dashboard', 'image-sizes' ) } />

			<PluginPage>
				<div className="flex flex-col gap-4 pb-6">
					<ScanBar scan={ scan } imageCount={ countsStats.total_images } />

					{ justFinished && state && <CheckupToast applied={ state.free_fixes_applied.length } onDismiss={ dismissToast } /> }

					<GradeHero
						grade={ analysisStats.grade }
						score={ analysisStats.health_score }
						hero={ hero }
						onScan={ requestScan }
						onFixAll={ () => setFixOpen( true ) }
						busy={ scanBusy || scanning }
					/>

					{ fresh && <CheckupCard unfinished={ !! state && state.started_at > 0 } onDismiss={ dismissCheckup } /> }

					<div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
						{ cards.map( ( card ) => (
							<CategoryCard key={ card.key } card={ card } stats={ stats } navigate={ navigate } onScan={ requestScan } busy={ scanBusy || scanning } />
						) ) }
					</div>

					{ showBand && (
						<ProBand
							result={ check.result }
							running={ check.running }
							runLabel={ check.runLabel }
							onRun={ check.run }
							canSeeSamples={ !! check.samples }
							onSeeSamples={ check.openSamples }
							missedBytes={ analysisStats.missed_bytes }
							missedSince={ analysisStats.missed_since }
							offer={ setup?.offer ?? null }
						/>
					) }

					{ pro && countsStats.total_space_saved > 0 && <SavedByPro bytes={ countsStats.total_space_saved } /> }

					<div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
						<ActivityCard items={ activity } />
						<QuickActions onScan={ requestScan } scanBusy={ scanBusy || scanning } />
					</div>

					<ScanFooter scan={ scan } />
				</div>

				{ fixOpen && (
					<FixEverythingModal
						stats={ stats }
						pro={ pro }
						onClose={ () => setFixOpen( false ) }
						onApplied={ async () => {
							try {
								const [ counts, analysis ]: any[] = await Promise.all( [ getDashboardCountsStats(), getDashboardAnalysisStats() ] );
								if ( counts?.data ) setCountsStats( counts.data );
								if ( analysis?.data ) setAnalysisStats( analysis.data );
							} catch {
								// The dashboard catches up on the next load.
							}
						} }
					/>
				) }

				{ check.showSamples && check.result && check.samples && (
					<CompressionCheckModal
						result={ check.result }
						samples={ check.samples }
						healthGain={ compressionHealthGain( stats.not_compressed, stats.total_images ) }
						onClose={ check.closeSamples }
					/>
				) }
			</PluginPage>
		</>
	);
}
