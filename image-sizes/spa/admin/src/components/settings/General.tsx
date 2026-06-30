import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { toast } from 'sonner';
import { Switch } from '../ui/switch';
import { Input } from '../ui/input';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '../ui/select';
import ProAlert from '../ui/pro-alert';
import { PluginSettings, savePluginSettings } from '../../api';

import Card from '../ui/card';

interface GeneralProps {
	settings: PluginSettings | null;
	onSave?: () => void;
}

export default function General({ settings, onSave }: GeneralProps) {
	const [saving, setSaving] = useState(false);
	const [imageEditorAlertOpen, setImageEditorAlertOpen] = useState(false);
	const [replaceImagesAlertOpen, setReplaceImagesAlertOpen] = useState(false);

	const [rightClickDisabled, setRightClickDisabled] = useState(
		settings?.right_click_disable ?? false,
	);
	const [lazyLoad, setLazyLoad] = useState(settings?.lazy_load ?? false);
	const [hotlinkProtection, setHotlinkProtection] = useState(
		settings?.hotlink_protection ?? false,
	);
	const [imageEditor, setImageEditor] = useState(
		settings?.image_editor ?? false,
	);
	const [replaceImages, setReplaceImages] = useState(
		settings?.replace_images ?? false,
	);
	const [maxSize, setMaxSize] = useState(settings?.max_size ?? '');
	const [sizeUnit, setSizeUnit] = useState(settings?.max_size_unit ?? 'KB');
	const [maxWidth, setMaxWidth] = useState(settings?.max_width ?? '');
	const [maxHeight, setMaxHeight] = useState(settings?.max_height ?? '');
	const [autoSetFeaturedImage, setAutoSetFeaturedImage] = useState(
		settings?.auto_set_featured_image ?? false,
	);

	const isProActive = window.THUMBPRESS?.pro_active;

	const handleSave = async () => {
		setSaving(true);
		try {
			await savePluginSettings({
				right_click_disable: rightClickDisabled,
				lazy_load: lazyLoad,
				hotlink_protection: hotlinkProtection,
				image_editor: imageEditor,
				replace_images: replaceImages,
				max_size: maxSize,
				max_size_unit: sizeUnit,
				max_width: maxWidth,
				max_height: maxHeight,
				auto_set_featured_image: autoSetFeaturedImage,
			});
			toast.success(__( 'Settings saved successfully.', 'image-sizes' ));
			onSave?.();
		} catch {
			toast.error(__( 'Failed to save settings.', 'image-sizes' ));
		} finally {
			setSaving(false);
		}
	};

	const handleReset = () => {
		setRightClickDisabled(false);
		setLazyLoad(false);
		setHotlinkProtection(false);
		setImageEditor(false);
		setReplaceImages(false);
		setMaxSize('');
		setSizeUnit('KB');
		setMaxWidth('');
		setMaxHeight('');
		setAutoSetFeaturedImage(false);
		toast.success(__( 'Settings reset successfully.', 'image-sizes' ));
	};

	return (
		<div className="flex flex-col">
			<div className="flex items-center justify-between pb-4 border-b border-[#E2E8F0]">
				<div>
					<div className="flex items-center gap-2">
						<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
							Image Editor
						</h4>
						{!isProActive && (
							<button
								type="button"
								onClick={() => setImageEditorAlertOpen(true)}
								className="inline-flex items-center gap-1 px-2 py-1 rounded text-[8px] bg-thumbpress-pro-yellow text-thumbpress-title cursor-pointer"
							>
								<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#a)"><path d="M8.357 8.42H1.431a.494.494 0 0 0 0 .989H8.357a.494.494 0 0 0 0-.99Z" fill="#1C1C1C"/><path d="M9.097 2.055a.494.494 0 0 0-.543.033L6.959 3.285 5.28 1.186A.494.494 0 0 0 4.894 1a.494.494 0 0 0-.387.186L2.828 3.285 1.233 2.088a.494.494 0 0 0-.734.453l.495 4.452a.494.494 0 0 0 .491.431h6.926a.494.494 0 0 0 .491-.431l.495-4.452a.494.494 0 0 0-.308-.486ZM7.914 6.442H1.874L1.554 3.566l1.064.798a.494.494 0 0 0 .682-.092l1.593-1.99 1.592 1.99a.494.494 0 0 0 .683.092l1.064-.798-.318 2.876Z" fill="#1C1C1C"/></g><defs><clipPath id="a"><rect width="10" height="10" fill="#fff"/></clipPath></defs></svg>
								Pro
							</button>
						)}
					</div>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						Enhance images with filters and adjustments to showcase their best
						versions.
					</p>
				</div>
				{!isProActive && (
					<ProAlert
						title="The built-in Image Editor is a Pro feature"
						description="Leaving WordPress just to crop an image breaks your whole workflow. Edit, resize, and re-optimize everything without switching tools."
						buttonText="Upgrade to edit images in WordPress"
						open={imageEditorAlertOpen}
						onClose={() => setImageEditorAlertOpen(false)}
					/>
				)}
				<Switch
					checked={isProActive ? imageEditor : false}
					onCheckedChange={
						isProActive ? (checked) => setImageEditor(checked) : () => setImageEditorAlertOpen(true)
					}
					className={!isProActive ? '!cursor-pointer' : ''}
				/>
			</div>

			<div className="flex items-center justify-between py-4 border-b border-[#E2E8F0]">
				<div>
					<div className="flex items-center gap-2">
						<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
							Replace Images With New Version
						</h4>
						{!isProActive && (
							<button
								type="button"
								onClick={() => setReplaceImagesAlertOpen(true)}
								className="inline-flex items-center gap-1 px-2 py-1 rounded text-[8px] bg-thumbpress-pro-yellow text-thumbpress-title cursor-pointer"
							>
								<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#b)"><path d="M8.357 8.42H1.431a.494.494 0 0 0 0 .989H8.357a.494.494 0 0 0 0-.99Z" fill="#1C1C1C"/><path d="M9.097 2.055a.494.494 0 0 0-.543.033L6.959 3.285 5.28 1.186A.494.494 0 0 0 4.894 1a.494.494 0 0 0-.387.186L2.828 3.285 1.233 2.088a.494.494 0 0 0-.734.453l.495 4.452a.494.494 0 0 0 .491.431h6.926a.494.494 0 0 0 .491-.431l.495-4.452a.494.494 0 0 0-.308-.486ZM7.914 6.442H1.874L1.554 3.566l1.064.798a.494.494 0 0 0 .682-.092l1.593-1.99 1.592 1.99a.494.494 0 0 0 .683.092l1.064-.798-.318 2.876Z" fill="#1C1C1C"/></g><defs><clipPath id="b"><rect width="10" height="10" fill="#fff"/></clipPath></defs></svg>
								Pro
							</button>
						)}
					</div>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						Upload new versions of images and replace the old ones without any
						issues.
					</p>
				</div>
				{!isProActive && (
					<ProAlert
						title="Replacing Image is a Pro feature"
						description="Outdated images live on more pages than you think. Swap in a new version once, and every page updates instantly."
						buttonText="Upgrade to replace images seamlessly"
						open={replaceImagesAlertOpen}
						onClose={() => setReplaceImagesAlertOpen(false)}
					/>
				)}
				<Switch
					checked={isProActive ? replaceImages : false}
					onCheckedChange={
						isProActive ? (checked) => setReplaceImages(checked) : () => setReplaceImagesAlertOpen(true)
					}
					className={!isProActive ? '!cursor-pointer' : ''}
				/>
			</div>

			<div className="flex items-center justify-between py-4 border-b border-[#E2E8F0]">
				<div>
					<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
						Disable Right Click on Image
					</h4>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						Prevent visitors from downloading your images by turning off the
						right-click option on your website.
					</p>
				</div>
				<Switch
					checked={rightClickDisabled}
					onCheckedChange={(checked) => setRightClickDisabled(checked)}
				/>
			</div>

			<div className="flex items-center justify-between py-4 border-b border-[#E2E8F0]">
				<div>
					<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
						Image Size Limit
					</h4>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						Set a limit for maximum image upload size.
					</p>
				</div>
				<div className="flex flex-col items-end gap-1">
					<div className="flex items-center gap-0">
						<Input
							type="number"
							value={maxSize}
							onChange={(e) => setMaxSize(e.target.value)}
							placeholder={__( '120', 'image-sizes' )}
							className="!w-24 !rounded-r-none text-center !p-3 tp-max-size-input"
						/>
						<Select value={sizeUnit} onValueChange={(v) => v && setSizeUnit(v)}>
							<SelectTrigger className="!w-[5.5rem] !rounded-l-none !rounded-r-lg !h-[46px] border-l-0 !border-thumbpress-border bg-white px-3 !py-3 text-sm text-thumbpress-body focus:outline-none focus:!ring-0">
								<SelectValue />
							</SelectTrigger>
							<SelectContent
								side="bottom"
								sideOffset={8}
								align="start"
								alignOffset={0}
								alignItemWithTrigger={false}
							>
								<SelectItem value="KB">KB</SelectItem>
								<SelectItem value="MB">MB</SelectItem>
							</SelectContent>
						</Select>
					</div>
					{settings?.php_upload_max && (
						<span className="text-xs text-[#94A3B8]">{__( 'Server limit:', 'image-sizes' )} {settings.php_upload_max}</span>
					)}
				</div>
			</div>

			<div className="flex items-center justify-between py-4 border-b border-[#E2E8F0]">
				<div>
					<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
						Image Dimension Limit
					</h4>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						Set a limit for maximum image upload dimensions (in pixels).
					</p>
				</div>
				<div className="flex items-center gap-2">
					<Input
						type="number"
						value={maxHeight}
						onChange={(e) => setMaxHeight(e.target.value)}
						placeholder={__( 'Height', 'image-sizes' )}
						className="!w-24 text-center !p-3"
					/>
					<span className="2xl:text-sm lg:text-xs font-medium text-thumbpress-title">X</span>
					<Input
						type="number"
						value={maxWidth}
						onChange={(e) => setMaxWidth(e.target.value)}
						placeholder={__( 'Width', 'image-sizes' )}
						className="!w-24 text-center !p-3"
						min={0}
					/>
				</div>
			</div>

			<div className="flex items-center justify-between py-4 border-b border-[#E2E8F0]">
				<div>
					<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
						{__( 'Lazy load', 'image-sizes' )}
					</h4>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						{__( 'Load images only when they enter the user\'s viewport, improving page speed and reducing bandwidth usage.', 'image-sizes' )}
					</p>
				</div>
				<Switch
					checked={lazyLoad}
					onCheckedChange={(checked) => setLazyLoad(checked)}
				/>
			</div>

			<div className="flex items-center justify-between py-4 border-b border-[#E2E8F0]">
				<div>
					<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
						{__( 'Hotlink Protection', 'image-sizes' )}
					</h4>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						{__( 'Block other websites from embedding your images directly, preventing bandwidth theft from external hotlinking.', 'image-sizes' )}
					</p>
				</div>
				<Switch
					checked={hotlinkProtection}
					onCheckedChange={(checked) => setHotlinkProtection(checked)}
				/>
			</div>

			<div className="flex items-center justify-between py-4 border-b border-[#E2E8F0]">
				<div>
					<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
						{__( 'Auto Set Featured Image', 'image-sizes' )}
					</h4>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						{__( 'Automatically use the first image in post content as the featured image if none is set.', 'image-sizes' )}
					</p>
				</div>
				<Switch
					checked={autoSetFeaturedImage}
					onCheckedChange={(checked) => setAutoSetFeaturedImage(checked)}
				/>
			</div>

			<div className="flex items-center justify-end gap-4 pt-6">
				<button
					onClick={handleReset}
					className="px-8 py-2.5 rounded-lg border border-thumbpress-primary text-thumbpress-primary text-sm font-medium hover:bg-thumbpress-primary/5 transition-colors cursor-pointer"
				>
					{__( 'Reset Options', 'image-sizes' )}
				</button>
				<button
					onClick={handleSave}
					disabled={saving}
					className="px-8 py-2.5 rounded-lg bg-thumbpress-primary text-white text-sm font-medium hover:bg-purple-800 transition-colors disabled:opacity-50 cursor-pointer"
				>
					{__( 'Save Changes', 'image-sizes' )}
				</button>
			</div>
		</div>
	);
}
