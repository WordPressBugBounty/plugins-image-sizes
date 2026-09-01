import React, { useEffect, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useNavigate } from 'react-router-dom';
import { applyFilters } from '@wordpress/hooks';
import {
	FileImage,
	Download,
	Search,
	Maximize,
	Copy,
	ToggleLeft,
	Zap,
	FileType,
} from 'lucide-react';
import {
	TotalImagesIcon,
	TotalThumbnailsIcon,
	UnoptimizedIcon,
	SpaceSavedIcon,
	WebpFileIcon,
	AvifFileIcon,
	ThumbnailDisabledIcon,
	LazyLoadingIcon,
	LargeImagesIcon,
	UnusedImagesIconPro,
	CompressedImagesIcon,
	DuplicateImagesIcon,
} from '../components/icons';

import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';
import {
	getDashboardCountsStats,
	getDashboardOptimizationStats,
	getDashboardAnalysisStats,
	type DashboardCountsStats,
	type DashboardOptimizationStats,
	type DashboardAnalysisStats,
	type DashboardStats,
} from '../api';

import LockedFeatureCard from '../components/dashboard/LockedFeatureCard';
import InfoCard from '../components/dashboard/InfoCard';
import HealthScoreCard from '../components/dashboard/HealthScoreCard';
import { numberFormat, formatBytes } from '../lib/i18n';

function InfoCardSkeleton({ hasArrow = false }: { hasArrow?: boolean }) {
	return (
		<div className="bg-white rounded-xl border border-gray-100 2xl:p-6 p-4 animate-pulse">
			<div className="flex items-center justify-between mb-3">
				<div className="flex items-center gap-2">
					<div className="w-8 h-8 rounded-lg bg-gray-200" />
					<div className="h-5 w-32 bg-gray-200 rounded" />
				</div>
				{hasArrow && <div className="w-10 h-10 rounded-xl bg-gray-100" />}
			</div>
			<div className="h-9 w-16 bg-gray-200 rounded mb-2" />
			<div className="h-4 w-44 bg-gray-100 rounded" />
		</div>
	);
}

function HealthScoreCardSkeleton() {
	return (
		<div className="bg-[#1E0F53] rounded-xl px-10 py-6 flex items-center gap-12 animate-pulse 2xl:w-full lg:w-[430px]">
			<div className="flex flex-col shrink-0 items-start">
				<div className="h-4 w-14 bg-white/20 rounded mb-1" />
				<div className="h-7 w-36 bg-white/20 rounded mb-4" />
				<div className="w-[110px] h-[110px] 2xl:w-[190px] 2xl:h-[190px] rounded-full border-[20px] border-white/20 flex items-center justify-center">
					<div className="h-10 w-16 bg-white/20 rounded" />
				</div>
				<div className="flex items-center gap-2 mt-4">
					<div className="h-4 w-14 bg-white/20 rounded" />
					<div className="h-6 w-20 bg-white/20 rounded-full" />
				</div>
			</div>
			<div className="flex flex-col flex-1">
				<div className="h-4 w-24 bg-white/20 rounded mb-6" />
				<div className="border-l border-white/20 pl-4 flex flex-col gap-5">
					{[1, 2, 3].map(i => (
						<div key={i} className="flex items-center gap-3">
							<div className="w-6 h-6 rounded-full bg-white/20 shrink-0" />
							<div className="h-4 bg-white/20 rounded w-52" />
						</div>
					))}
				</div>
			</div>
		</div>
	);
}

function DashboardSkeleton() {
	return (
		<>
			<Header title={__('Dashboard', 'image-sizes')} />
			<PluginPage>
				<div className="grid grid-cols-1 md:grid-cols-[1fr_auto] 2xl:gap-5 lg:gap-4 2xl:mb-5 lg:mb-4">
					<HealthScoreCardSkeleton />
					<div className="grid grid-cols-2 2xl:gap-5 lg:gap-4 2xl:min-w-[826px] lg:min-w-[427px]">
						{[...Array(4)].map((_, i) => <InfoCardSkeleton key={i} />)}
					</div>
				</div>
				<div className="grid 2xl:grid-cols-4 lg:grid-cols-3 gap-5">
					{[...Array(8)].map((_, i) => <InfoCardSkeleton key={i} hasArrow />)}
				</div>
			</PluginPage>
		</>
	);
}

export default function Dashboard() {
	const [countsStats, setCountsStats] = useState<DashboardCountsStats | null>(null);
	const [optimizationStats, setOptimizationStats] = useState<DashboardOptimizationStats | null>(null);
	const [analysisStats, setAnalysisStats] = useState<DashboardAnalysisStats | null>(null);
	const [countsLoading, setCountsLoading] = useState(true);
	const [optimizationLoading, setOptimizationLoading] = useState(true);
	const [analysisLoading, setAnalysisLoading] = useState(true);
	const navigate = useNavigate();

	useEffect(() => {
		(async () => {
			try {
				const res: any = await getDashboardCountsStats();
				if (res?.data) setCountsStats(res.data);
			} finally {
				setCountsLoading(false);
			}
			try {
				const res: any = await getDashboardOptimizationStats();
				if (res?.data) setOptimizationStats(res.data);
			} finally {
				setOptimizationLoading(false);
			}
			try {
				const res: any = await getDashboardAnalysisStats();
				if (res?.data) setAnalysisStats(res.data);
			} finally {
				setAnalysisLoading(false);
			}
		})();
	}, []);

	if (countsLoading) {
		return <DashboardSkeleton />;
	}

	if (!countsStats) {
		return (
			<>
				<Header title={__('Dashboard', 'image-sizes')} />
				<PluginPage></PluginPage>
			</>
		);
	}

	const mergedStats: DashboardStats = {
		...countsStats,
		total_thumbnails: optimizationStats?.total_thumbnails ?? 0,
		unoptimized_images: optimizationStats?.unoptimized_images ?? 0,
		compressed: optimizationStats?.compressed ?? 0,
		not_compressed: optimizationStats?.not_compressed ?? 0,
		large_images: analysisStats?.large_images ?? 0,
		duplicate_images: analysisStats?.duplicate_images ?? 0,
		unused_images: analysisStats?.unused_images ?? 0,
		health_score: analysisStats?.health_score ?? 0,
		health_issue: analysisStats?.health_issue ?? '',
		quick_facts: analysisStats?.quick_facts ?? [],
	};

	return (
		<>
			<Header title={__('Dashboard', 'image-sizes')} />

			<PluginPage>

				{/* Top row: Health Score + stat cards */}
				<div className="grid grid-cols-1 md:grid-cols-[1fr_auto] 2xl:gap-5 lg:gap-4 2xl:mb-5 lg:mb-4">
					{/* ANALYSIS — needs health_score + quick_facts */}
					{(analysisLoading || !analysisStats)
						? <HealthScoreCardSkeleton />
						: <HealthScoreCard score={analysisStats.health_score} stats={mergedStats} />
					}

					<div className="grid grid-cols-2 2xl:gap-5 lg:gap-4 2xl:min-w-[826px] lg:min-w-[427px]">
						{/* COUNTS */}
						<InfoCard
							customIcon={<TotalImagesIcon />}
							title={__('Total Images', 'image-sizes')}
							value={numberFormat(countsStats.total_images)}
							sub={__('In media library', 'image-sizes')}
						/>
						{/* OPTIMIZATION */}
						{optimizationLoading ? <InfoCardSkeleton /> : (
							<InfoCard
								customIcon={<TotalThumbnailsIcon />}
								title={__('Total Thumbnails', 'image-sizes')}
								value={numberFormat(optimizationStats!.total_thumbnails)}
								sub={__('Thumbnails found', 'image-sizes')}
							/>
						)}
						{/* OPTIMIZATION */}
						{optimizationLoading ? <InfoCardSkeleton /> : (
							<InfoCard
								customIcon={<UnoptimizedIcon />}
								title={__('Unoptimized Images', 'image-sizes')}
								value={numberFormat(optimizationStats!.unoptimized_images)}
								sub={__('Images yet to be fixed', 'image-sizes')}
							/>
						)}
						{/* COUNTS */}
						<InfoCard
							customIcon={<SpaceSavedIcon />}
							title={__('Space Saved', 'image-sizes')}
							value={formatBytes(countsStats.total_space_saved)}
							sub={__('Worth of space saved', 'image-sizes')}
						/>
					</div>
				</div>

				{/* Feature Cards Grid — per-card filters, each gated on its own loading group */}
				<div className="grid 2xl:grid-cols-4 lg:grid-cols-3 2xl:gap-5 lg:gap-4">
					{/* ANALYSIS */}
					{analysisLoading ? <InfoCardSkeleton hasArrow /> : applyFilters(
						'thumbpress_dashboard_card_large',
						<LockedFeatureCard icon={<LargeImagesIcon />} title={__('Large Images', 'image-sizes')} description={__('Images (over 1 MB)', 'image-sizes')} value={numberFormat(mergedStats.large_images)} actionLabel={__('Upgrade to Compress', 'image-sizes')} />,
						mergedStats, navigate,
					) as React.ReactNode}

					{/* ANALYSIS */}
					{analysisLoading ? <InfoCardSkeleton hasArrow /> : applyFilters(
						'thumbpress_dashboard_card_unused',
						<LockedFeatureCard icon={<UnusedImagesIconPro />} title={__('Unused Images', 'image-sizes')} description={__('Images with no detected usage', 'image-sizes')} />,
						mergedStats, navigate,
					) as React.ReactNode}

					{/* OPTIMIZATION */}
					{optimizationLoading ? <InfoCardSkeleton hasArrow /> : applyFilters(
						'thumbpress_dashboard_card_compress',
						<LockedFeatureCard icon={<CompressedImagesIcon />} title={__('Uncompressed Images', 'image-sizes')} description={__('Images need compression', 'image-sizes')} value={numberFormat(mergedStats.not_compressed)} actionLabel={__('Upgrade to Compress', 'image-sizes')} />,
						mergedStats, navigate,
					) as React.ReactNode}

					{/* ANALYSIS */}
					{analysisLoading ? <InfoCardSkeleton hasArrow /> : applyFilters(
						'thumbpress_dashboard_card_duplicate',
						<LockedFeatureCard icon={<DuplicateImagesIcon />} title={__('Duplicate Images', 'image-sizes')} description={__('Duplicate images found', 'image-sizes')} value={numberFormat(mergedStats.duplicate_images)} actionLabel={__('Upgrade to Merge', 'image-sizes')} />,
						mergedStats, navigate,
					) as React.ReactNode}

					{/* COUNTS — always ready */}
					{applyFilters(
						'thumbpress_dashboard_card_avif',
						<LockedFeatureCard icon={<AvifFileIcon />} title={__('Convert to AVIF', 'image-sizes')} description={__('Non-AVIF images found', 'image-sizes')} value={numberFormat(mergedStats.not_avif)} actionLabel={__('Upgrade to Convert', 'image-sizes')} />,
						mergedStats, navigate,
					) as React.ReactNode}

					{/* COUNTS */}
					<InfoCard
						customIcon={<WebpFileIcon />}
						title={__('Convert to WebP', 'image-sizes')}
						value={numberFormat(countsStats.not_webp ?? 0)}
						sub={__('Non-WebP images found', 'image-sizes')}
						onClick={() => navigate('/convert-to-webp')}
					/>

					{/* COUNTS */}
					<InfoCard
						customIcon={<ThumbnailDisabledIcon />}
						title={__('Thumbnail Disabled', 'image-sizes')}
						value={sprintf( /* translators: %1$s is the number of disabled sizes, %2$s is the total number of sizes. */ __( '%1$s/%2$s', 'image-sizes' ), numberFormat(countsStats.disabled_sizes), numberFormat(countsStats.total_sizes) )}
						sub={__("These sizes won't be generated", 'image-sizes')}
						onClick={() => navigate('/settings?tab=thumbnails')}
					/>

					{/* COUNTS */}
					<InfoCard
						customIcon={<LazyLoadingIcon />}
						title={__('Lazy Loading', 'image-sizes')}
						value={countsStats.lazy_load ? __('Active', 'image-sizes') : __('Inactive', 'image-sizes')}
						sub={sprintf( /* translators: %s is the lazy-load status (On or Off). */ __( 'Status: %s', 'image-sizes' ), countsStats.lazy_load ? __( 'On', 'image-sizes' ) : __( 'Off', 'image-sizes' ) )}
						onClick={() => navigate('/settings?tab=general')}
					/>
				</div>
			</PluginPage>
		</>
	);
}
