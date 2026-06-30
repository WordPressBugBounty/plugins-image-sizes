import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { toast } from 'sonner';
import { Switch } from '../ui/switch';
import { PluginSettings, savePluginSettings } from '../../api';

interface ConvertToWebPProps {
	settings: PluginSettings | null;
	onSave?: () => void;
}

export default function ConvertToWebP({ settings, onSave }: ConvertToWebPProps) {
	const [saving, setSaving] = useState(false);
	const [webpOnUpload, setWebpOnUpload] = useState(
		settings?.webp_on_upload ?? false,
	);
	const [webpSingleConvert, setWebpSingleConvert] = useState(
		settings?.webp_single_convert ?? false,
	);

	const handleSave = async () => {
		setSaving(true);
		try {
			await savePluginSettings({
				webp_on_upload: webpOnUpload,
				webp_single_convert: webpSingleConvert,
			});
			toast.success(__('Settings saved successfully.', 'image-sizes'));
			onSave?.();
		} catch {
			toast.error(__('Failed to save settings.', 'image-sizes'));
		} finally {
			setSaving(false);
		}
	};

	const handleReset = () => {
		setWebpOnUpload(false);
		setWebpSingleConvert(false);
		toast.success(__('Settings reset successfully.', 'image-sizes'));
	};

	return (
		<div className="flex flex-col">
			<div className="flex items-center justify-between pb-4 border-b border-[#E2E8F0]">
				<div>
					<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
						{__('Convert during upload', 'image-sizes')}
					</h4>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						{__('Automatically convert images to WebP when they are uploaded.', 'image-sizes')}
					</p>
				</div>
				<Switch
					checked={webpOnUpload}
					onCheckedChange={(checked) => setWebpOnUpload(checked)}
				/>
			</div>
			<div className="flex items-center justify-between py-4 border-b border-[#E2E8F0]">
				<div>
					<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
						{__('Single image conversion', 'image-sizes')}
					</h4>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						{__('Convert individual images to WebP directly from the media library.', 'image-sizes')}
					</p>
				</div>
				<Switch
					checked={webpSingleConvert}
					onCheckedChange={(checked) => setWebpSingleConvert(checked)}
				/>
			</div>

			<div className="flex items-center justify-end gap-4 pt-6">
				<button
					onClick={handleReset}
					className="px-8 py-2.5 rounded-lg border border-thumbpress-primary text-thumbpress-primary text-sm font-medium hover:bg-thumbpress-primary/5 transition-colors cursor-pointer"
				>
					{__('Reset Options', 'image-sizes')}
				</button>
				<button
					onClick={handleSave}
					disabled={saving}
					className="px-8 py-2.5 rounded-lg bg-thumbpress-primary text-white text-sm font-medium hover:bg-purple-800 transition-colors disabled:opacity-50 cursor-pointer"
				>
					{__('Save Changes', 'image-sizes')}
				</button>
			</div>
		</div>
	);
}
