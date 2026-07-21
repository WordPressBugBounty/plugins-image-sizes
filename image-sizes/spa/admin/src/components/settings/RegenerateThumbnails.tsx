import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { Switch } from '../ui/switch';
import { saveDisabledThumbnails, ThumbnailsSizes } from '../../api';
import { numberFormat } from '../../lib/i18n';

interface RegenerateThumbnailsProps {
	sizes: ThumbnailsSizes;
	disabled: string[];
	onDisabledChange: (disabled: string[]) => void;
	loading?: boolean;
}

const SKELETON_LABEL_WIDTHS = ['w-28', 'w-36', 'w-32', 'w-44', 'w-32', 'w-36', 'w-36', 'w-32'];

function ThumbnailsLoadingSkeleton() {
	return (
		<div className="flex flex-col">
			{SKELETON_LABEL_WIDTHS.map((lw, i) => (
				<div key={i} className="flex items-center justify-between py-4 border-b border-[#E2E8F0]">
					<div className="flex flex-col gap-2">
						<div className={`animate-pulse bg-[#E2E8F0] rounded h-4 ${lw}`} />
						<div className="animate-pulse bg-[#E2E8F0] rounded h-3 w-72" />
					</div>
					<div className="w-11 h-6 bg-[#E2E8F0] rounded-full animate-pulse flex-shrink-0" />
				</div>
			))}
			<div className="flex items-center justify-end gap-4 pt-6">
				<div className="animate-pulse bg-[#E2E8F0] rounded-lg w-32 h-9" />
				<div className="animate-pulse bg-[#E2E8F0] rounded-lg w-32 h-9" />
			</div>
		</div>
	);
}

export default function RegenerateThumbnails({
	sizes,
	disabled,
	onDisabledChange,
	loading = false,
}: RegenerateThumbnailsProps) {
	const [saving, setSaving] = useState(false);

	const toggleSize = (name: string) => {
		onDisabledChange(
			disabled.includes(name) ? disabled.filter((s) => s !== name) : [...disabled, name],
		);
	};

	const isEnabled = (name: string) => !disabled.includes(name);

	const handleSave = async () => {
		setSaving(true);
		try {
			await saveDisabledThumbnails(disabled);
			toast.success(__('Settings saved successfully.', 'image-sizes'));
		} catch {
			toast.error(__('Failed to save settings.', 'image-sizes'));
		} finally {
			setSaving(false);
		}
	};

	const handleReset = () => {
		onDisabledChange(Object.keys(sizes).filter((name) => name !== 'full'));
		toast.success(__('Settings reset successfully.', 'image-sizes'));
	};

	if (loading) return <ThumbnailsLoadingSkeleton />;

	return (
		<div className="flex flex-col">
			{Object.keys(sizes).length === 0 ? (
				<p className="text-sm text-gray-500 py-8 text-center">
					{__('No thumbnail sizes found.', 'image-sizes')}
				</p>
			) : (
				Object.entries(sizes)
					.sort(([a], [b]) => (a === 'full' ? -1 : b === 'full' ? 1 : 0))
					.map(([name, size]) => (
						<div
							key={name}
							className="flex items-center justify-between py-4 first:pt-0 border-b border-[#E2E8F0]"
						>
							<div>
								<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title capitalize">
									{name === 'full' ? size.name : sprintf( /* translators: %1$s is the size name, %2$s is the width, %3$s is the height. */ __( '%1$s (%2$s×%3$s)', 'image-sizes' ), size.name, numberFormat(size.width), numberFormat(size.height) )}
								</h4>
								{name !== 'full' ? (
									<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
										{isEnabled(name)
											? __('Enabled - This size will be generated for new uploads.', 'image-sizes')
											: __('Disabled - This size will not be generated for new uploads.', 'image-sizes')}
									</p>
								) : (
									<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
										{__('This is the original image size and cannot be disabled.', 'image-sizes')}
									</p>
								)}
							</div>
							<Switch
								checked={name === 'full' ? true : isEnabled(name)}
								onCheckedChange={() => toggleSize(name)}
								disabled={name === 'full'}
							/>
						</div>
					))
			)}

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
