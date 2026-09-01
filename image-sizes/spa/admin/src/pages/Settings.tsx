import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useSearchParams } from 'react-router-dom';
import { applyFilters } from '@wordpress/hooks';
import { Copy, Check } from 'lucide-react';
import { toast } from 'sonner';

import {
	getPluginSettings,
	getAllThumbnails,
	getDisabledThumbnails,
	PluginSettings,
	ThumbnailsSizes,
	DebugInfo,
} from '../api';

import { Switch } from '../components/ui/switch';
import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';
import {
	Tabs,
	TabsContent,
	TabsList,
	TabsTrigger,
} from '../components/ui/tabs';

import Card from '../components/ui/card';

import General from '../components/settings/General';
import RegenerateThumbnails from '../components/settings/RegenerateThumbnails';
import CompressImages from '../components/settings/CompressImages';
import DuplicateImages from '../components/settings/DuplicateImages';
import ConvertToWebP from '../components/settings/ConvertToWebP';
import ConvertToAvif from '../components/settings/ConvertToAvif';
import Cdn from '../components/settings/Cdn';
import SocialShareImage from '../components/settings/SocialShareImage';
import Debug from '../components/settings/Debug';
import SettingsSkeleton from '../components/settings/SettingsSkeleton';

export default function Settings() {
	const [searchParams, setSearchParams] = useSearchParams();
	const activeTab = searchParams.get('tab') || 'general';

	const [loading, setLoading] = useState(true);
	const [pluginSettings, setPluginSettings] = useState<PluginSettings | null>(
		null,
	);
	const [thumbnailSizes, setThumbnailSizes] = useState<ThumbnailsSizes>({});
	const [disabledSizes, setDisabledSizes] = useState<string[]>([]);
	const [thumbnailsLoaded, setThumbnailsLoaded] = useState(false);
	const [debugInfo, setDebugInfo] = useState<DebugInfo | null>(null);
	const [debugCopied, setDebugCopied] = useState(false);

	useEffect(() => {
		getPluginSettings()
			.then((res) => {
				if (res.success && res.data) setPluginSettings(res.data);
			})
			.finally(() => setLoading(false));
	}, []);

	const refreshSettings = () => {
		getPluginSettings()
			.then((res) => {
				if (res.success && res.data) setPluginSettings(res.data);
			})
			.catch((err) => console.error('Failed to refresh settings:', err));
	};

	useEffect(() => {
		if (activeTab !== 'thumbnails' || thumbnailsLoaded) return;
		Promise.all([getAllThumbnails(), getDisabledThumbnails()]).then(
			([sizesRes, disabledRes]) => {
				if (
					sizesRes.success &&
					sizesRes.data &&
					typeof sizesRes.data !== 'string' &&
					!Array.isArray(sizesRes.data)
				) {
					setThumbnailSizes(sizesRes.data);
				}
				if (
					disabledRes.success &&
					disabledRes.data &&
					typeof disabledRes.data !== 'string'
				) {
					setDisabledSizes(disabledRes.data as string[]);
				}
				setThumbnailsLoaded(true);
			},
		).catch((err) => {
			console.error('Failed to load thumbnails:', err);
			setThumbnailsLoaded(true);
		});
	}, [activeTab, thumbnailsLoaded]);

	const handleTabChange = (slug: string) => {
		setSearchParams({ tab: slug });
	};

	const handleDebugCopy = () => {
		if (!debugInfo) return;
		const text = JSON.stringify(debugInfo, null, 2);
		const finish = () => {
			setDebugCopied(true);
			toast.success(__( 'Debug info copied to clipboard.', 'image-sizes' ));
			setTimeout(() => setDebugCopied(false), 2000);
		};
		if (navigator.clipboard?.writeText) {
			navigator.clipboard.writeText(text).then(finish);
		} else {
			const el = document.createElement('textarea');
			el.value = text;
			el.style.position = 'fixed';
			el.style.opacity = '0';
			document.body.appendChild(el);
			el.select();
			document.execCommand('copy');
			document.body.removeChild(el);
			finish();
		}
	};

	const allToggleable = Object.keys(thumbnailSizes).filter((n) => n !== 'full');
	const allEnabled = allToggleable.length > 0 && allToggleable.every((n) => !disabledSizes.includes(n));

	// Pro registers the real CDN tab (same 'cdn' slug) through
	// thumbpress_settings_tabs once the license is active, so the free promo tab
	// is only added while pro is missing or unlicensed.
	const isProActive = !! window.THUMBPRESS?.pro_active;

	const defaultTabs = [
		{
			label: __( 'General', 'image-sizes' ),
			slug: 'general',
			content: <General settings={pluginSettings} onSave={refreshSettings} />,
			title: __( 'General Settings', 'image-sizes' ),
			tooltip: __( 'General settings for the plugin', 'image-sizes' ),
		},
		{
			label: __( 'Thumbnails', 'image-sizes' ),
			slug: 'thumbnails',
			content: (
				<RegenerateThumbnails
					sizes={thumbnailSizes}
					disabled={disabledSizes}
					onDisabledChange={setDisabledSizes}
					loading={!thumbnailsLoaded}
				/>
			),
			title: __( 'Thumbnail Generation', 'image-sizes' ),
			tooltip: __( 'Disable thumbnail generation for specific image sizes', 'image-sizes' ),
			headerAction: allToggleable.length > 0 ? (
				<Switch
					checked={allEnabled}
					onCheckedChange={(checked) =>
						setDisabledSizes(checked ? [] : allToggleable)
					}
				/>
			) : undefined,
		},
		{
			label: __( 'Duplicate Images', 'image-sizes' ),
			slug: 'duplicate-images',
			content: <DuplicateImages />,
			title: __( 'Duplicate Upload Prevention', 'image-sizes' ),
			tooltip: __( 'Prevent uploading duplicate images to your media library', 'image-sizes' ),
		},
		{
			label: __( 'Compress Images', 'image-sizes' ),
			slug: 'compress-images',
			content: <CompressImages />,
			title: __( 'File Compression Settings', 'image-sizes' ),
			tooltip: __( 'Image compression additional settings', 'image-sizes' ),
		},
		{
			label: __( 'Convert to WebP', 'image-sizes' ),
			slug: 'convert-to-webp',
			content: <ConvertToWebP settings={pluginSettings} onSave={refreshSettings} />,
			title: __( 'Convert to WebP', 'image-sizes' ),
			tooltip: __( 'WebP Image Converter additional settings', 'image-sizes' ),
		},
		{
			label: __( 'Convert to AVIF', 'image-sizes' ),
			slug: 'convert-to-avif',
			content: <ConvertToAvif settings={pluginSettings} onSave={refreshSettings} />,
			title: __( 'Convert to AVIF', 'image-sizes' ),
			tooltip: __( 'AVIF Image Converter additional settings', 'image-sizes' ),
		},
		{
			label: __( 'Social Share Image', 'image-sizes' ),
			slug: 'social-share-image',
			content: <SocialShareImage settings={pluginSettings} onSave={refreshSettings} />,
			title: __( 'Platform Configuration', 'image-sizes' ),
			tooltip: __( 'Manage the image used for social media sharing', 'image-sizes' ),
		},
		...( isProActive ? [] : [ {
			label: __( 'CDN', 'image-sizes' ),
			slug: 'cdn',
			content: <Cdn />,
			title: __( 'CDN Settings', 'image-sizes' ),
			tooltip: __( 'Serve your images from a global edge network', 'image-sizes' ),
		} ] ),
		{
			label: __( 'Debug', 'image-sizes' ),
			slug: 'debug',
			content: <Debug onDataLoaded={setDebugInfo} />,
			title: __( 'Debug Information', 'image-sizes' ),
			tooltip: __( 'System and plugin info for support', 'image-sizes' ),
			headerAction: (
				<button
					onClick={handleDebugCopy}
					disabled={!debugInfo}
					className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#E2E8F0] text-sm text-[#64748B] hover:border-thumbpress-primary hover:text-thumbpress-primary transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
				>
					{debugCopied ? <Check size={14} className="text-green-600" /> : <Copy size={14} />}
					{debugCopied ? __( 'Copied!', 'image-sizes' ) : __( 'Copy as JSON', 'image-sizes' )}
				</button>
			),
		},
	];

	const settingsTabs: any = applyFilters( 'thumbpress_settings_tabs', defaultTabs );

	if ( loading ) return (
		<>
			<Header title={__( 'Settings', 'image-sizes' )} />
			<PluginPage><SettingsSkeleton activeTab={activeTab} /></PluginPage>
		</>
	);

	return (
		<>
			<Header title={__( 'Settings', 'image-sizes' )} />

			<PluginPage>
				<Tabs
					defaultValue={activeTab}
					onValueChange={handleTabChange}
					className="rounded-xl border border-[#E2E8F0] bg-white p-6 h-full"
				>
					<TabsList variant="line" className="w-full overflow-y-auto mb-5">
						{settingsTabs.map((tab: any, index: number) => (
							<TabsTrigger key={index} value={tab.slug}>
								{tab.label}
							</TabsTrigger>
						))}
					</TabsList>

					{settingsTabs.map((tab: any) => (
						<TabsContent key={tab.slug} value={tab.slug} keepMounted={false}>
							<Card title={tab.title} tooltip={tab.tooltip} headerAction={tab.headerAction} className='max-w-[900px]'>
								{tab.content}
							</Card>
						</TabsContent>
					))}
				</Tabs>
			</PluginPage>
		</>
	);
}
