import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Switch } from '../ui/switch';
import ProAlert from '../ui/pro-alert';

/**
 * Free stub for the CDN settings tab.
 *
 * Mirrors the rows pro renders in its own `CdnSettings` component, but every
 * control opens the pro upsell instead of saving. Settings.tsx only mounts this
 * tab while pro is missing or unlicensed — pro registers the real tab (same
 * `cdn` slug) through `thumbpress_settings_tabs`.
 */
export default function Cdn() {
	const [alertOpen, setAlertOpen] = useState(false);
	const openAlert = () => setAlertOpen(true);

	const isProActive = window.THUMBPRESS?.pro_active;

	const proBadge = (
		<button
			type="button"
			onClick={() => setAlertOpen(true)}
			className="inline-flex items-center gap-1 px-2 py-1 rounded text-[8px] bg-thumbpress-pro-yellow text-thumbpress-title cursor-pointer"
		>
			<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#a)"><path d="M8.357 8.42H1.431a.494.494 0 0 0 0 .989H8.357a.494.494 0 0 0 0-.99Z" fill="#1C1C1C"/><path d="M9.097 2.055a.494.494 0 0 0-.543.033L6.959 3.285 5.28 1.186A.494.494 0 0 0 4.894 1a.494.494 0 0 0-.387.186L2.828 3.285 1.233 2.088a.494.494 0 0 0-.734.453l.495 4.452a.494.494 0 0 0 .491.431h6.926a.494.494 0 0 0 .491-.431l.495-4.452a.494.494 0 0 0-.308-.486ZM7.914 6.442H1.874L1.554 3.566l1.064.798a.494.494 0 0 0 .682-.092l1.593-1.99 1.592 1.99a.494.494 0 0 0 .683.092l1.064-.798-.318 2.876Z" fill="#1C1C1C"/></g><defs><clipPath id="a"><rect width="10" height="10" fill="#fff"/></clipPath></defs></svg>
			{__('Pro', 'image-sizes')}
		</button>
	);

	const toggles = [
		{
			label: __('Auto-offload new uploads', 'image-sizes'),
			description: __('Automatically upload newly added images to the CDN.', 'image-sizes'),
		},
		{
			label: __('Keep local copy', 'image-sizes'),
			description: __('When off, the local file is deleted after upload to free disk space. The CDN copy stays.', 'image-sizes'),
		},
		{
			label: __('Rewrite URLs in srcset', 'image-sizes'),
			description: __('Replace srcset attributes so responsive images load from the CDN too.', 'image-sizes'),
		},
		{
			label: __('Rewrite WooCommerce product images', 'image-sizes'),
			description: __('Product, variation, and gallery images served from CDN.', 'image-sizes'),
		},
		{
			label: __('Fallback to local on CDN error', 'image-sizes'),
			description: __('If the CDN is unreachable, serve images from your server instead of breaking the page.', 'image-sizes'),
		},
		{
			label: __('Lazy load CDN images', 'image-sizes'),
			description: __('Apply native lazy loading to images served from the CDN.', 'image-sizes'),
		},
	];

	const inputs = [
		{
			label: __('Exclude file types', 'image-sizes'),
			description: __('Comma-separated extensions that should never offload.', 'image-sizes'),
			value: 'svg, pdf',
		},
		{
			label: __('Exclude image sizes', 'image-sizes'),
			description: __('WordPress image sizes to skip. Useful if you want originals on CDN but keep thumbs local.', 'image-sizes'),
			value: 'thumbnail, medium',
		},
	];

	return (
		<div className="flex flex-col">
			{toggles.map((row, index) => (
				<div
					key={row.label}
					className={`flex items-center justify-between border-b border-[#E2E8F0] ${index === 0 ? 'pb-4' : 'py-4'}`}
				>
					<div>
						<div className="flex items-center gap-2">
							<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
								{row.label}
							</h4>
							{!isProActive && proBadge}
						</div>
						<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
							{row.description}
						</p>
					</div>
					<Switch
						checked={false}
						onCheckedChange={openAlert}
						className="!cursor-pointer"
					/>
				</div>
			))}

			{inputs.map((row) => (
				<div
					key={row.label}
					className="flex items-center justify-between gap-4 py-4 border-b border-[#E2E8F0]"
				>
					<div>
						<div className="flex items-center gap-2">
							<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
								{row.label}
							</h4>
							{!isProActive && proBadge}
						</div>
						<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
							{row.description}
						</p>
					</div>
					<input
						type="text"
						defaultValue={row.value}
						onClick={openAlert}
						onChange={openAlert}
						className="!w-44 flex !rounded-lg !border !border-thumbpress-border bg-white !px-4 !py-2 text-sm focus:!outline-none focus:!shadow-none cursor-pointer"
					/>
				</div>
			))}

			<div className="flex items-center justify-end gap-4 pt-6">
				<button
					onClick={openAlert}
					className="px-8 py-2.5 rounded-lg border border-thumbpress-primary text-thumbpress-primary text-sm font-medium hover:bg-thumbpress-primary/5 transition-colors cursor-pointer"
				>
					{__('Reset Options', 'image-sizes')}
				</button>
				<button
					onClick={openAlert}
					className="px-8 py-2.5 rounded-lg bg-thumbpress-primary text-white text-sm font-medium hover:bg-purple-800 transition-colors cursor-pointer"
				>
					{__('Save Changes', 'image-sizes')}
				</button>
			</div>

			{!isProActive && (
				<ProAlert
					title={__('CDN Offloading is a Pro feature', 'image-sizes')}
					description={__('Serve your images from a global edge network instead of a single server. Pages load faster for visitors everywhere, with zero setup required.', 'image-sizes')}
					buttonText={__('Upgrade to enable CDN', 'image-sizes')}
					open={alertOpen}
					onClose={() => setAlertOpen(false)}
				/>
			)}
		</div>
	);
}
