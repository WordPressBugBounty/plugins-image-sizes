import React, { useState, useEffect, useRef, useCallback } from 'react';
import { __ } from '@wordpress/i18n';
import { numberFormat, percentFormat, formatBytes } from '../lib/i18n';
import { useNavigate } from 'react-router-dom';
import Card from '../components/ui/card';
import { Image, HardDrive } from 'lucide-react';
import ScanActionButton from '../components/ui/scan-action-button';
import SettingsButton from '../components/ui/settings-button';
import {
	regenerateNow,
	regenerateBackground,
	getRegenerateProgress,
	cancelRegenerate,
	getSettings,
	saveSettings,
	getDashboardStats,
	getDashboardOptimizationStats,
	RegenerateResponse,
} from '../api';

import {
	TotalImagesIcon2,
	TotalThumbnailsIcon2,
	ImageProcessedIcon,
	ImageDeletedIcon,
	ImageCreatedIcon,
	SpaceSavedIcon2,
	FailedIcon,
} from '../components/icons';

import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';
import { Tooltip } from '../components/ui/tooltip';
export default function RegenerateThumbnails({ tooltip }: { tooltip?: string } = {}) {
	const navigate = useNavigate();
	const [chunkSize, setChunkSize] = useState('20');
	const [loading, setLoading] = useState(true);
	const [regenerating, setRegenerating] = useState(false);
	const [hasStarted, setHasStarted] = useState(false);
	const [noImages, setNoImages] = useState(false);
	const [progress, setProgress] = useState(0);
	const [totalImages, setTotalImages] = useState(0);
	const [totalThumbnails, setTotalThumbnails] = useState(0);
	const [regenStats, setRegenStats] = useState({
		processed: 0,
		created: 0,
		deleted: 0,
		space_saved_label: '',
		not_found: 0,
		failed: 0,
	});
	const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);
	const generationRef = useRef(0);

	const stopPolling = useCallback(() => {
		if (pollRef.current) {
			clearInterval(pollRef.current);
			pollRef.current = null;
		}
	}, []);

	const refreshStats = useCallback(() => {
		// Force a fresh thumbnail count (bypass the 6h cache) so the page never
		// shows a stale value on load or after a regeneration completes.
		getDashboardOptimizationStats(true)
			.then((r: any) => {
				if (r?.data?.total_thumbnails != null) {
					setTotalThumbnails(Number(r.data.total_thumbnails) || 0);
				}
			})
			.catch(() => { });
	}, []);

	const pollProgress = useCallback(() => {
		stopPolling();
		pollRef.current = setInterval(async () => {
			try {
				const res = await getRegenerateProgress();
				if (res.success && res.data) {
					setProgress(res.data.progress);
					setTotalImages(res.data.total);
					setRegenStats({
						processed: res.data.processed,
						created: res.data.created,
						deleted: res.data.deleted,
						space_saved_label: res.data.space_saved_label || '',
						not_found: res.data.not_found || 0,
						failed: res.data.failed || 0,
					});
					if (res.data.is_complete) {
						setRegenerating(false);
						stopPolling();
						refreshStats();
					}
				}
			} catch {
				// keep polling
			}
		}, 5000);
	}, [stopPolling, refreshStats]);

	useEffect(() => {
		getDashboardStats()
			.then((res: any) => {
				if (res?.data?.total_images != null) {
					setTotalImages(Number(res.data.total_images) || 0);
				}
			})
			.catch(() => { });

		// Fresh thumbnail count on page open (bypasses the 6h cache).
		refreshStats();

		// Always check live progress first — state option may not be saved if user reloaded early.
		getRegenerateProgress()
			.then((progressRes: any) => {
				if (progressRes?.success && progressRes?.data && progressRes.data.total > 0) {
					setHasStarted(true);
					setProgress(progressRes.data.progress);
					setTotalImages(progressRes.data.total);
					setRegenStats({
						processed: progressRes.data.processed,
						created: progressRes.data.created,
						deleted: progressRes.data.deleted,
						space_saved_label: progressRes.data.space_saved_label || '',
						not_found: progressRes.data.not_found || 0,
						failed: progressRes.data.failed || 0,
					});
					if (!progressRes.data.is_complete) {
						setRegenerating(true);
						pollProgress();
					} else {
						refreshStats();
					}
					setLoading(false);
				} else {
					setLoading(false);
				}
			})
			.catch(() => {
				setLoading(false);
			});

		return () => stopPolling();
	}, []);

	const handleScanAgain = () => {
		generationRef.current++;
		setHasStarted(false);
		setRegenerating(false);
		setNoImages(false);
		setProgress(0);
		setTotalImages(0);
		setRegenStats({
			processed: 0,
			created: 0,
			deleted: 0,
			space_saved_label: '',
			not_found: 0,
			failed: 0,
		});
		stopPolling();
		cancelRegenerate();
		saveSettings('thumbpress_regen_view_state', 'initial');
	};

	const handleRegenerateNow = async () => {
		const myGen = ++generationRef.current;
		setRegenerating(true);
		setHasStarted(true);
		setNoImages(false);
		setProgress(0);
		setRegenStats({
			processed: 0,
			created: 0,
			deleted: 0,
			space_saved_label: '',
			not_found: 0,
			failed: 0,
		});

		let offset = 0;
		let limit = chunkSize ? parseInt(chunkSize) : 20;
		if (!limit || limit < 1) limit = 40;
		let thumbsDeleted = 0;
		let thumbsCreated = 0;
		let spaceSaved = 0;
		let notFound = 0;
		let failed = 0;
		let processedCount = 0;
		let firstChunk = true;

		const processChunk = async () => {
			if (generationRef.current !== myGen) return;

			try {
				const res: RegenerateResponse = await regenerateNow(
					offset,
					limit,
					thumbsDeleted,
					thumbsCreated,
					spaceSaved,
					notFound,
					processedCount,
					failed,
				);

				if (generationRef.current !== myGen) return;

				if (!res.success) {
					setRegenerating(false);
					if (firstChunk) {
						setNoImages(true);
						setHasStarted(true);
					}
					return;
				}

				const data =
					typeof res.data === 'object'
						? res.data
						: {
							message: String(res.data),
							progress: 0,
							offset: 0,
							thumbs_deleted: 0,
							thumbs_created: 0,
							total_images_count: 0,
						};

				if (firstChunk) {
					firstChunk = false;
					if (Number(data.total_images_count) === 0) {
						setRegenerating(false);
						setNoImages(true);
						setHasStarted(true);
						return;
					}
					setHasStarted(true);
				}

				const currentProgress = Number(data.progress) || 0;
				const currentOffset = Number(data.offset) || 0;

				setProgress(currentProgress);
				setTotalImages(Number(data.total_images_count) || 0);
				setRegenStats({
					processed: Number(data.processed_count) || 0,
					created: Number(data.thumbs_created) || 0,
					deleted: Number(data.thumbs_deleted) || 0,
					space_saved_label: data.space_saved_label || '',
					not_found: Number(data.not_found) || 0,
					failed: Number(data.failed) || 0,
				});

				offset = currentOffset;
				thumbsDeleted = Number(data.thumbs_deleted) || 0;
				thumbsCreated = Number(data.thumbs_created) || 0;
				spaceSaved = Number(data.space_saved) || 0;
				notFound = Number(data.not_found) || 0;
				failed = Number(data.failed) || 0;
				processedCount = Number(data.processed_count) || 0;

				if (currentProgress < 100 && currentOffset > 0) {
					setTimeout(processChunk, 500);
				} else {
					setRegenerating(false);
					refreshStats();
				}
			} catch (err) {
				setRegenerating(false);
				console.error('Regenerate chunk failed:', err);
			}
		};

		processChunk();
	};

	const handleRegenerateBackground = async () => {
		try {
			const limit = chunkSize ? parseInt(chunkSize) : 20;
			setRegenerating(true);
			setHasStarted(true);
			setNoImages(false);
			setProgress(0);
			setRegenStats({ processed: 0, created: 0, deleted: 0, space_saved_label: '', not_found: 0, failed: 0 });
			const res = await regenerateBackground(limit);
			if (!res.success || res.data?.total === 0) {
				setRegenerating(false);
				setNoImages(true);
				setHasStarted(true);
				return;
			}
			setHasStarted(true);
			saveSettings('thumbpress_regen_view_state', 'progress');
			setProgress(0);
			setRegenStats({
				processed: 0,
				created: 0,
				deleted: 0,
				space_saved_label: '',
				not_found: 0,
				failed: 0,
			});
			pollProgress();
		} catch (err) {
			setRegenerating(false);
			console.error('Background regeneration failed:', err);
		}
	};

	const circumference = 2 * Math.PI * 80;
	const strokeDashoffset = circumference - (progress / 100) * circumference;

	const StatCard = ({
		icon,
		value,
		label,
		accent,
	}: {
		icon: React.ReactNode;
		value: number | string;
		label: string;
		accent?: 'red';
	}) => (
		<div className="flex items-center gap-3 rounded-xl border border-[#E2E8F0] bg-white px-5 py-4">
			<div className={`w-10 h-10 rounded-lg flex items-center justify-center ${accent === 'red' ? 'bg-[#FDECEC] text-[#FF3A52]' : 'bg-[#F0EBFF]'}`}>
				{icon}
			</div>
			<div>
				<p className="text-2xl font-bold text-thumbpress-title">{value}</p>
				<p className="text-sm text-[#64748B]">{label}</p>
			</div>
		</div>
	);

	const imageIcon = <Image size={20} className="text-thumbpress-primary" />;

	return (
		<>
			<Header title={__('Regenerate Thumbnails', 'image-sizes')} />

			<PluginPage>
				<Card
					className="h-full"
					title={__('Regenerate All Thumbnails', 'image-sizes')}
					description={__('Broken thumbnails hurt layouts and lose visitors. Regenerate once - every image across your site snaps back to the perfect size and crop.', 'image-sizes')}
					headerAction={
						hasStarted && !noImages ? (
							<div className="flex items-center gap-3">
								<ScanActionButton onClick={handleScanAgain} text={__('Generate Again', 'image-sizes')} />
								<SettingsButton onClick={() => navigate('/settings?tab=thumbnails')} />
							</div>
						) : (
							<SettingsButton onClick={() => navigate('/settings?tab=thumbnails')} />
						)
					}
				>
					{loading ? (
						<div className="flex flex-col items-center py-10 animate-pulse">
							<div className="w-[120px] h-[120px] rounded-full bg-gray-100 mb-6" />
							<div className="h-6 w-48 bg-gray-200 rounded mb-3" />
							<div className="h-4 w-72 bg-gray-100 rounded mb-8" />
							<div className="w-full max-w-[675px]">
								<div className="h-4 w-24 bg-gray-200 rounded mb-2" />
								<div className="h-[56px] w-full bg-gray-100 rounded-lg mb-3" />
								<div className="h-4 w-64 bg-gray-100 rounded mx-auto mb-4" />
								<div className="flex justify-center gap-6">
									<div className="h-10 w-[225px] bg-gray-100 rounded-md" />
									<div className="h-10 w-[225px] bg-gray-200 rounded-md" />
								</div>
							</div>
						</div>
					) : !hasStarted || noImages ? (
						<div className="flex flex-col items-center py-10">
							<div>
								<img
									src={
										(window.THUMBPRESS?.assets_url || '') +
										(noImages
											? 'admin/img/no-image.png'
											: 'admin/img/no-search-result.png')
									}
								/>
							</div>

							<h3 className="text-xl font-bold text-thumbpress-title mb-[6px]">
								{noImages ? __('No Images Found', 'image-sizes') : __('Ready to Regenerate Thumbnails?', 'image-sizes')}
							</h3>
							<p className="text-sm text-[#64748B] mb-8">
								{noImages
									? __('There are no images to regenerate.', 'image-sizes')
									: <>
										{__('Click "Regenerate Now" to create fresh thumbnails for all images. Configure your thumbnail sizes in ', 'image-sizes')}{' '}
										<span
											onClick={() => navigate('/settings?tab=thumbnails')}
											className="text-thumbpress-primary cursor-pointer"
										>
											{__('settings', 'image-sizes')}
										</span>{__('.', 'image-sizes')}
									</>}
							</p>

							<div className="w-full max-w-[675px]">
								<div className="flex items-center gap-2 mb-2">
									<label className="text-base font-semibold text-thumbpress-title block">
										{__('Chunk Size', 'image-sizes')}
									</label>
									<Tooltip content={tooltip || __('Number of images processed in a single batch per server request.', 'image-sizes')}>
										<svg className="cursor-help" width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M8.54973 4.97845C8.54973 4.85198 8.65215 4.75046 8.77772 4.75046H10.2223C10.3478 4.75046 10.4503 4.85198 10.4503 4.97845V6.4221C10.4503 6.54767 10.3478 6.65009 10.2223 6.65009H8.77772C8.65215 6.65009 8.54973 6.54767 8.54973 6.4221V4.97845ZM8.54973 8.77772C8.54973 8.65215 8.65215 8.54973 8.77772 8.54973H10.2223C10.3478 8.54973 10.4503 8.65215 10.4503 8.77772V14.0216C10.4503 14.148 10.3478 14.2495 10.2223 14.2495H8.77772C8.65215 14.2495 8.54973 14.148 8.54973 14.0216V8.77772ZM9.5 0C4.25617 0 0 4.25617 0 9.5C0 14.7438 4.25617 19 9.5 19C14.7438 19 19 14.7438 19 9.5C19 4.25617 14.7438 0 9.5 0ZM9.5 17.0995C5.31063 17.0995 1.90055 13.6894 1.90055 9.5C1.90055 5.31063 5.31063 1.90055 9.5 1.90055C13.6894 1.90055 17.0995 5.31063 17.0995 9.5C17.0995 13.6894 13.6894 17.0995 9.5 17.0995Z" fill="#1B2538" />
										</svg>
									</Tooltip>
								</div>

								<input
									type="number"
									placeholder={__('e.g. 50', 'image-sizes')}
									value={chunkSize}
									onChange={(e) => setChunkSize(e.target.value)}
									className="flex w-full !rounded-lg !border !border-thumbpress-border bg-white !px-4 !h-[56px] text-sm placeholder:text-gray-400 focus:!outline-none focus:!shadow-none"
								/>

								<div className="flex justify-center gap-6 mt-8">
									<button
										onClick={handleRegenerateNow}
										disabled={!chunkSize.trim() || regenerating}
										className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 border border-thumbpress-primary bg-white text-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
									>
										{__('Regenerate Now', 'image-sizes')}
									</button>
									<button
										onClick={handleRegenerateBackground}
										disabled={!chunkSize.trim() || regenerating}
										className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 bg-thumbpress-primary text-white border border-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
									>
										{__('Regenerate In Background', 'image-sizes')}
									</button>
								</div>
							</div>
						</div>
					) : (
						<div className="flex flex-col items-center py-6">
							<h2 className="text-2xl font-bold text-thumbpress-title mb-2">
								{progress >= 100
									? __('Image Regeneration Complete', 'image-sizes')
									: __('Image Regeneration in Progress', 'image-sizes')}
							</h2>
							<p className="text-sm text-[#64748B] mb-[6px]">
								{progress >= 100
									? __('All thumbnails have been regenerated successfully.', 'image-sizes')
									: __('Regenerating media library thumbnails using selected sizes.', 'image-sizes')}
								{progress < 100 && (
									<span
										onClick={handleScanAgain}
										className="text-thumbpress-primary cursor-pointer text-sm font-medium ml-1"
									>
										{__('Cancel', 'image-sizes')}
									</span>
								)}
							</p>

							<div className="relative mb-10">
								<svg width="220" height="220" viewBox="0 0 220 220">
									<circle
										cx="110"
										cy="110"
										r="80"
										fill="none"
										stroke="#EAE2FF"
										strokeWidth="30"
									/>
									<circle
										cx="110"
										cy="110"
										r="80"
										fill="none"
										stroke="#40189D"
										strokeWidth="30"
										strokeLinecap="round"
										strokeDasharray={circumference}
										strokeDashoffset={strokeDashoffset}
										transform="rotate(-90 110 110)"
										className="transition-all duration-500"
									/>
								</svg>
								<div className="absolute inset-0 flex items-center justify-center">
									<span className="text-4xl font-bold text-gray-900">
										{ percentFormat( progress ) }
									</span>
								</div>
							</div>

							<div className="grid grid-cols-3 gap-4 w-full max-w-[700px]">
								<StatCard
									icon={<TotalImagesIcon2 />}
									value={ numberFormat( totalImages ) }
									label={__('Total Images', 'image-sizes')}
								/>
								<StatCard
									icon={<ImageProcessedIcon />}
									value={ numberFormat( regenStats.processed ) }
									label={__('Images Processed', 'image-sizes')}
								/>
								<StatCard
									icon={<FailedIcon />}
									value={ numberFormat( regenStats.failed || 0 ) }
									label={__('Images Failed', 'image-sizes')}
									accent="red"
								/>
								<StatCard
									icon={
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" color="currentColor" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
											<path d="M15.5 8C15.7761 8 16 7.77614 16 7.5C16 7.22386 15.7761 7 15.5 7M15.5 8C15.2239 8 15 7.77614 15 7.5C15 7.22386 15.2239 7 15.5 7M15.5 8V7" />
											<path d="M2 2L22 22" />
											<path d="M17.2997 21.2997C16.0187 21.5 14.3303 21.5 12 21.5C7.77027 21.5 5.6554 21.5 4.25276 20.302C4.05358 20.1319 3.86808 19.9464 3.69797 19.7472C2.5 18.3446 2.5 16.2297 2.5 12C2.5 9.66971 2.5 7.98134 2.70033 6.70033M20.0355 20.0355C20.1281 19.943 20.217 19.8468 20.302 19.7472C21.5 18.3446 21.5 16.2297 21.5 12C21.5 7.77027 21.5 5.6554 20.302 4.25276C20.1319 4.05358 19.9464 3.86808 19.7472 3.69797C18.3446 2.5 16.2297 2.5 12 2.5C7.77027 2.5 5.6554 2.5 4.25276 3.69797C4.15317 3.78303 4.057 3.87193 3.96447 3.96447" />
											<path d="M3 16L7.50036 11.5004M21 16L18.5303 13.5303C18.1908 13.1908 17.7302 13 17.25 13C16.7698 13 16.3092 13.1908 15.9697 13.5303L14.75 14.75" />
										</svg>
									}
									value={ numberFormat( regenStats.not_found || 0 ) }
									label={__('Images Not Found', 'image-sizes')}
								/>
								<StatCard
									icon={<TotalThumbnailsIcon2 />}
									value={ numberFormat( totalThumbnails ) }
									label={__('Total Thumbnails', 'image-sizes')}
								/>
								<StatCard
									icon={<ImageDeletedIcon />}
									value={ numberFormat( regenStats.deleted ) }
									label={__('Images Deleted', 'image-sizes')}
								/>
								<StatCard
									icon={<ImageCreatedIcon />}
									value={ numberFormat( regenStats.created ) }
									label={__('Images Created', 'image-sizes')}
								/>
								<StatCard
									icon={<SpaceSavedIcon2 />}
									value={regenStats.space_saved_label || formatBytes(0)}
									label={__('Space Saved', 'image-sizes')}
								/>
							</div>
						</div>
					)}
				</Card>
			</PluginPage>
		</>
	);
}
