import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Switch } from '../ui/switch';
import ProAlert from '../ui/pro-alert';

export default function DuplicateImages() {
	const [alertOpen, setAlertOpen] = useState(false);
	const openAlert = () => setAlertOpen(true);

	const isProActive = window.THUMBPRESS?.pro_active;

	return (
		<div className="flex flex-col">
			<div className="flex items-center justify-between pb-4 border-b border-[#E2E8F0]">
				<div>
					<div className="flex items-center gap-2">
						<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
							{__('Prevent Duplicate Upload', 'image-sizes')}
						</h4>
						{!isProActive && (
							<button
								type="button"
								onClick={() => setAlertOpen(true)}
								className="inline-flex items-center gap-1 px-2 py-1 rounded text-[8px] bg-thumbpress-pro-yellow text-thumbpress-title cursor-pointer"
							>
								<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#a)"><path d="M8.357 8.42H1.431a.494.494 0 0 0 0 .989H8.357a.494.494 0 0 0 0-.99Z" fill="#1C1C1C"/><path d="M9.097 2.055a.494.494 0 0 0-.543.033L6.959 3.285 5.28 1.186A.494.494 0 0 0 4.894 1a.494.494 0 0 0-.387.186L2.828 3.285 1.233 2.088a.494.494 0 0 0-.734.453l.495 4.452a.494.494 0 0 0 .491.431h6.926a.494.494 0 0 0 .491-.431l.495-4.452a.494.494 0 0 0-.308-.486ZM7.914 6.442H1.874L1.554 3.566l1.064.798a.494.494 0 0 0 .682-.092l1.593-1.99 1.592 1.99a.494.494 0 0 0 .683.092l1.064-.798-.318 2.876Z" fill="#1C1C1C"/></g><defs><clipPath id="a"><rect width="10" height="10" fill="#fff"/></clipPath></defs></svg>
								{__('Pro', 'image-sizes')}
							</button>
						)}
					</div>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						{__('Block uploading images that already exist in your media library', 'image-sizes')}
					</p>
				</div>
				{!isProActive && (
					<ProAlert
						title={__('Duplicate Image Detection is a Pro feature', 'image-sizes')}
						description={__('Duplicate images quietly pile up and bloat your media library. Find and remove every copy you didn\'t even know existed.', 'image-sizes')}
						buttonText={__('Upgrade to detect duplicate images', 'image-sizes')}
						open={alertOpen}
						onClose={() => setAlertOpen(false)}
					/>
				)}
				<Switch
					checked={false}
					onCheckedChange={openAlert}
					className="!cursor-pointer"
				/>
			</div>

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
		</div>
	);
}
