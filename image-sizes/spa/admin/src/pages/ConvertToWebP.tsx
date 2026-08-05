import React, { useState, useEffect, useRef } from 'react';
import { __ } from '@wordpress/i18n';
import { numberFormat, percentFormat, formatBytes } from '../lib/i18n';
import { useNavigate } from 'react-router-dom';
import { Image } from 'lucide-react';
import SettingsButton from '../components/ui/settings-button';
import ScanActionButton from '../components/ui/scan-action-button';
import { Checkbox } from '../components/ui/checkbox';
import {
	convertNow,
	convertBackground,
	getConvertProgress,
	cancelConvert,
	getSettings,
	saveSettings,
	getPluginSettings,
} from '../api';

import {
	TotalImagesIcon2,
	ImageProcessedIcon,
	ConvertedIcon,
	RemainingIcon,
	SpaceSavedIcon2,
	FailedIcon,
} from '../components/icons';

import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';
import Card from '../components/ui/card';
import { Tooltip } from '../components/ui/tooltip';

interface FileFormat {
	label: string;
	value: string;
	checked: boolean;
}

const DEFAULT_FORMATS: FileFormat[] = [
	{ label: 'JPEG', value: 'jpeg', checked: false },
	{ label: 'PNG', value: 'png', checked: false },
	{ label: 'JPG', value: 'jpg', checked: true },
	{ label: 'GIF', value: 'gif', checked: false },
	{ label: 'BMP', value: 'bmp', checked: false },
	{ label: 'AVIF', value: 'avif', checked: false },
];

export default function ConvertToWebP({ tooltip }: { tooltip?: string } = {}) {
	const navigate = useNavigate();
	const [formats, setFormats] = useState<FileFormat[]>(DEFAULT_FORMATS);
	const [chunkSize, setChunkSize] = useState('20');
	const [loading, setLoading] = useState(true);
	const [converting, setConverting] = useState(false);
	const [hasStarted, setHasStarted] = useState(false);
	const [noImages, setNoImages] = useState(false);
	const [progress, setProgress] = useState(0);
	const [converted, setConverted] = useState(0);
	const [processed, setProcessed] = useState(0);
	const [remaining, setRemaining] = useState(0);
	const [total, setTotal] = useState(0);
	const [spaceSaved, setSpaceSaved] = useState(0);
	const [notFound, setNotFound] = useState(0);
	const [failed, setFailed] = useState(0);
	const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);
	const generationRef = useRef(0);

	const circumference = 2 * Math.PI * 80;
	const strokeDashoffset = circumference - (progress / 100) * circumference;
	const detectImageUrl = (window.THUMBPRESS?.assets_url || '') + 'admin/img/no-search-result.png';

	useEffect(() => {
		// Reflect the source formats saved on the Settings tab instead of DEFAULT_FORMATS (#443).
		getPluginSettings().then((res) => {
			const saved = res?.data?.webp_file_formats ?? [];
			if (Array.isArray(saved) && saved.length) {
				setFormats((prev) => prev.map((f) => ({ ...f, checked: saved.includes(f.value) })));
			}
		}).catch(() => { });
	}, []);

	useEffect(() => {
		// Always check live progress first — saved state may be stale if user reloaded before API responded.
		getConvertProgress().then((progressRes: any) => {
			if (progressRes?.data && !progressRes.data.is_complete && progressRes.data.total > 0) {
				setHasStarted(true);
				setConverting(true);
				setProgress(progressRes.data.progress);
				setConverted(progressRes.data.converted);
				setProcessed(progressRes.data.processed || 0);
				setRemaining(progressRes.data.remaining || 0);
				setTotal(progressRes.data.total);
				setSpaceSaved(progressRes.data.space_saved || 0);
				setNotFound(progressRes.data.not_found || 0);
				setFailed(progressRes.data.failed || 0);
				pollRef.current = setInterval(async () => {
					const pr = await getConvertProgress();
					if (pr?.data) {
						setProgress(pr.data.progress);
						setConverted(pr.data.converted);
						setProcessed(pr.data.processed || 0);
						setRemaining(pr.data.remaining || 0);
						setTotal(pr.data.total);
						setSpaceSaved(pr.data.space_saved || 0);
						setNotFound(pr.data.not_found || 0);
						setFailed(pr.data.failed || 0);
						if (pr.data.is_complete) {
							if (pollRef.current) clearInterval(pollRef.current);
							setConverting(false);
						}
					}
				}, 3000);
				setLoading(false);
			} else {
				getSettings('thumbpress_webp_view_state').then((res: any) => {
					if (res?.value === 'progress') {
						setHasStarted(true);
						if (progressRes?.data) {
							setProgress(progressRes.data.progress);
							setConverted(progressRes.data.converted);
							setProcessed(progressRes.data.processed || 0);
							setRemaining(progressRes.data.remaining || 0);
							setTotal(progressRes.data.total);
							setSpaceSaved(progressRes.data.space_saved || 0);
							setNotFound(progressRes.data.not_found || 0);
							setFailed(progressRes.data.failed || 0);
						}
					}
				}).catch(() => { }).finally(() => setLoading(false));
			}
		}).catch(() => {
			setLoading(false);
		});

		return () => {
			if (pollRef.current) clearInterval(pollRef.current);
		};
	}, []);

	const handleScanAgain = () => {
		generationRef.current++;
		setHasStarted(false);
		setConverting(false);
		setNoImages(false);
		setProgress(0);
		setConverted(0);
		setProcessed(0);
		setRemaining(0);
		setTotal(0);
		setSpaceSaved(0);
		setNotFound(0);
		if (pollRef.current) clearInterval(pollRef.current);
		cancelConvert();
		saveSettings('thumbpress_webp_view_state', 'initial');
	};

	const toggleFormat = (index: number) => {
		setFormats((prev) =>
			prev.map((f, i) => (i === index ? { ...f, checked: !f.checked } : f))
		);
	};

	const handleConvertNow = () => {
		const myGen = ++generationRef.current;
		const limit = parseInt(chunkSize, 10) || 10;
		const fileFormats = formats.filter((f) => f.checked).map((f) => f.value);
		if (fileFormats.length === 0) return;

		setConverting(true);
		setHasStarted(true);
		setNoImages(false);
		setProgress(0);
		setConverted(0);
		setProcessed(0);
		setRemaining(0);
		setSpaceSaved(0);
		setFailed(0);

		let lastId = 0;
		let currentProcessed = 0;
		let currentConverted = 0;
		let currentSpaceSaved = 0;
		let currentNotFound = 0;
		let currentFailed = 0;
		let firstChunk = true;

		const processChunk = async () => {
			if (generationRef.current !== myGen) return;
			try {
				const res = await convertNow(lastId, limit, fileFormats, currentSpaceSaved, currentNotFound, currentProcessed, currentConverted, currentFailed);
				if (generationRef.current !== myGen) return;
				if (!res?.success) {
					setConverting(false);
					if (firstChunk) {
						setNoImages(true);
						setHasStarted(true);
					}
					return;
				}

				if (res?.data && typeof res.data === 'object') {
					const data = res.data as any;

					if (firstChunk) {
						firstChunk = false;
						if ((data.total || 0) === 0) {
							setConverting(false);
							setNoImages(true);
							setHasStarted(true);
							return;
						}
						setHasStarted(true);
					}

					setProgress(Math.round(data.progress || 0));
					setConverted(data.converted || 0);
					setProcessed(data.processed || 0);
					setRemaining(data.remaining || 0);
					setTotal(data.total || 0);
					setSpaceSaved(data.space_saved || 0);
					setNotFound(Number(data.not_found) || 0);
					setFailed(Number(data.failed) || 0);
					lastId = data.last_id || 0;
					currentProcessed = data.processed || 0;
					currentConverted = data.converted || 0;
					currentSpaceSaved = data.space_saved || 0;
					currentNotFound = Number(data.not_found) || 0;
					currentFailed = Number(data.failed) || 0;

					if (data.is_complete || data.progress === undefined || data.progress >= 100) {
						setConverting(false);
					} else {
						setTimeout(processChunk, 500);
					}
				} else {
					setConverting(false);
				}
			} catch {
				setConverting(false);
			}
		};

		processChunk();
	};

	const handleConvertBackground = async () => {
		const limit = parseInt(chunkSize, 10) || 10;
		const fileFormats = formats.filter((f) => f.checked).map((f) => f.value);
		if (fileFormats.length === 0) return;

		setConverting(true);
		setHasStarted(true);
		setNoImages(false);
		setProgress(0);
		setConverted(0);
		setProcessed(0);
		setRemaining(0);
		setSpaceSaved(0);
		setFailed(0);

		try {
			const res = await convertBackground(limit, fileFormats);
			if (!res?.success || (res?.data && (res.data as any).total === 0)) {
				setConverting(false);
				setNoImages(true);
				setHasStarted(true);
				return;
			}
			if (res?.data) {
				setHasStarted(true);
				saveSettings('thumbpress_webp_view_state', 'progress');
				pollRef.current = setInterval(async () => {
					const progressRes = await getConvertProgress();
					if (progressRes?.data) {
						setProgress(progressRes.data.progress);
						setConverted(progressRes.data.converted);
						setProcessed(progressRes.data.processed || 0);
						setRemaining(progressRes.data.remaining || 0);
						setTotal(progressRes.data.total);
						setSpaceSaved(progressRes.data.space_saved || 0);
						setNotFound(progressRes.data.not_found || 0);
						setFailed(progressRes.data.failed || 0);

						if (progressRes.data.is_complete) {
							if (pollRef.current) clearInterval(pollRef.current);
							setConverting(false);
						}
					}
				}, 3000);
			}
		} catch {
			setConverting(false);
		}
	};

	const StatCard = ({ icon, value, label, accent }: { icon: React.ReactNode, value: number | string; label: string; accent?: 'red' }) => (
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

	return (
		<>
			<Header title={__('Convert to WebP', 'image-sizes')} />

			<PluginPage>
				<Card
					title={__('Convert Images to WebP', 'image-sizes')}
					description={__('Slow images cost you sales. Switch to WebP and cut file sizes by 35% - faster pages, lower bounce rates, and better rankings, zero quality loss.', 'image-sizes')}
					headerAction={hasStarted && progress >= 100 ? (
						<div className="flex items-center gap-3">
							<ScanActionButton onClick={handleScanAgain} text={__('Convert Again', 'image-sizes')} />
							<SettingsButton onClick={() => navigate('/settings?tab=convert-to-webp')} />
						</div>
					) : (
						<SettingsButton onClick={() => navigate('/settings?tab=convert-to-webp')} />
					)}
				>
					{loading ? (
						<div className="flex flex-col items-center py-10 animate-pulse">
							<div className="w-[120px] h-[120px] bg-gray-100 mb-6" />
							<div className="h-6 w-48 bg-gray-200 rounded mb-3" />
							<div className="h-4 w-72 bg-gray-100 rounded mb-8" />
							<div className="w-full max-w-[675px]">
								<div className="h-4 w-32 bg-gray-200 rounded mb-3" />
								<div className="flex gap-5 mb-6">
									{[1, 2, 3, 4, 5, 6].map(i => <div key={i} className="h-4 w-14 bg-gray-100 rounded" />)}
								</div>
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
							<img
								src={noImages ? (window.THUMBPRESS?.assets_url || '') + 'admin/img/no-image.png' : detectImageUrl}
								alt=""
							/>
							<h3 className="text-xl font-bold text-thumbpress-title mb-[6px]">{noImages ? __('No Images Found', 'image-sizes') : __('Ready to Convert to WebP?', 'image-sizes')}</h3>
							<p className="text-sm text-[#64748B] mb-8">{noImages ? __('There are no images to convert to WebP.', 'image-sizes') : __('Select file formats above and click "Convert Now" to start converting images to WebP for faster loading.', 'image-sizes')}</p>

							<div className="w-full max-w-[675px]">
								<div>
									<label className="text-base font-semibold text-thumbpress-title mb-2 block">{__('Select File Format', 'image-sizes')}</label>
									<div className="flex flex-wrap gap-5">
										{formats.map((format, index) => (
											<label key={format.label} className="flex items-center gap-2 cursor-pointer">
												<Checkbox checked={format.checked} onCheckedChange={() => toggleFormat(index)} />
												<span className="text-sm text-thumbpress-body">{format.label}</span>
											</label>
										))}
									</div>
								</div>

								<div className="mt-6">
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
										value={chunkSize}
										onChange={(e) => setChunkSize(e.target.value)}
										placeholder={__('e.g. 50', 'image-sizes')}
										className="flex w-full !rounded-lg !border !border-thumbpress-border bg-white !px-4 !h-[56px] text-sm placeholder:text-gray-400 focus:!outline-none focus:!shadow-none"
									/>
								</div>

								<div className="flex justify-center gap-6 mt-8">
									<button
										onClick={handleConvertNow}
										disabled={!chunkSize.trim() || !formats.some((f) => f.checked) || converting}
										className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 border border-thumbpress-primary bg-white text-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
									>
										{__('Convert Now', 'image-sizes')}
									</button>
									<button
										onClick={handleConvertBackground}
										disabled={!chunkSize.trim() || !formats.some((f) => f.checked) || converting}
										className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 bg-thumbpress-primary text-white border border-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
									>
										{__('Convert In Background', 'image-sizes')}
									</button>
								</div>
							</div>
						</div>
					) : (
						<div className="flex flex-col items-center py-6">
							<h2 className="text-2xl font-bold text-thumbpress-title mb-2">
								{progress >= 100 ? __('WebP Conversion Complete', 'image-sizes') : __('WebP Conversion in Progress', 'image-sizes')}
							</h2>
							<p className="text-sm text-[#64748B] mb-[6px]">
								{progress >= 100 ? __('All images have been converted to WebP.', 'image-sizes') : __('We\'re scanning your media library and converting eligible images to WebP.', 'image-sizes')}
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
									<circle cx="110" cy="110" r="80" fill="none" stroke="#EAE2FF" strokeWidth="30" />
									<circle cx="110" cy="110" r="80" fill="none" stroke="#40189D" strokeWidth="30"
										strokeLinecap="round" strokeDasharray={circumference} strokeDashoffset={strokeDashoffset}
										transform="rotate(-90 110 110)" className="transition-all duration-500" />
								</svg>
								<div className="absolute inset-0 flex items-center justify-center">
									<span className="text-4xl font-bold text-gray-900">{ percentFormat( progress ) }</span>
								</div>
							</div>

							<div className="grid grid-cols-3 gap-4 w-full max-w-[700px]">
								<StatCard icon={<TotalImagesIcon2 />} value={ numberFormat( total ) } label={__('Total Images', 'image-sizes')} />
								<StatCard icon={<ImageProcessedIcon />} value={ numberFormat( processed ) } label={__('Images Processed', 'image-sizes')} />
								<StatCard icon={<FailedIcon />} value={ numberFormat( failed ) } label={__('Images Failed', 'image-sizes')} accent="red" />
								<StatCard
									icon={
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" color="currentColor" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
											<path d="M15.5 8C15.7761 8 16 7.77614 16 7.5C16 7.22386 15.7761 7 15.5 7M15.5 8C15.2239 8 15 7.77614 15 7.5C15 7.22386 15.2239 7 15.5 7M15.5 8V7" />
											<path d="M2 2L22 22" />
											<path d="M17.2997 21.2997C16.0187 21.5 14.3303 21.5 12 21.5C7.77027 21.5 5.6554 21.5 4.25276 20.302C4.05358 20.1319 3.86808 19.9464 3.69797 19.7472C2.5 18.3446 2.5 16.2297 2.5 12C2.5 9.66971 2.5 7.98134 2.70033 6.70033M20.0355 20.0355C20.1281 19.943 20.217 19.8468 20.302 19.7472C21.5 18.3446 21.5 16.2297 21.5 12C21.5 7.77027 21.5 5.6554 20.302 4.25276C20.1319 4.05358 19.9464 3.86808 19.7472 3.69797C18.3446 2.5 16.2297 2.5 12 2.5C7.77027 2.5 5.6554 2.5 4.25276 3.69797C4.15317 3.78303 4.057 3.87193 3.96447 3.96447" />
											<path d="M3 16L7.50036 11.5004M21 16L18.5303 13.5303C18.1908 13.1908 17.7302 13 17.25 13C16.7698 13 16.3092 13.1908 15.9697 13.5303L14.75 14.75" />
										</svg>
									}
									value={ numberFormat( notFound || 0 ) }
									label={__('Images Not Found', 'image-sizes')}
								/>
								<StatCard icon={<ConvertedIcon />} value={ numberFormat( converted ) } label={__('Images Converted', 'image-sizes')} />
								<StatCard icon={<RemainingIcon />} value={ numberFormat( remaining ) } label={__('Images Remaining', 'image-sizes')} />
							<StatCard icon={<SpaceSavedIcon2 />} value={ formatBytes( spaceSaved ) } label={__('Space Saved', 'image-sizes')} />
							</div>

						</div>
					)}
				</Card>
			</PluginPage>
		</>
	);
}
