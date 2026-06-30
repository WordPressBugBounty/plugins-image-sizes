import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { toast } from 'sonner';
import { Switch } from '../ui/switch';
import { Facebook, Linkedin } from 'lucide-react';
import { PluginSettings, savePluginSettings } from '../../api';

interface SocialPlatform {
	key: string;
	label: string;
	description: string;
	checked: boolean;
	iconBg: string;
	icon: React.ReactNode;
}

interface SocialShareImageProps {
	settings: PluginSettings | null;
	onSave?: () => void;
}

export default function SocialShareImage({ settings, onSave }: SocialShareImageProps) {
	const [saving, setSaving] = useState(false);
	const [facebook, setFacebook] = useState(settings?.social_facebook ?? false);
	const [linkedin, setLinkedin] = useState(settings?.social_linkedin ?? false);
	const [twitter, setTwitter] = useState(settings?.social_twitter ?? false);
	const [pinterest, setPinterest] = useState(
		settings?.social_pinterest ?? false,
	);

	const handleSocialToggle = (platform: string, checked: boolean) => {
		const stateMap: Record<string, (v: boolean) => void> = {
			facebook: setFacebook,
			linkedin: setLinkedin,
			twitter: setTwitter,
			pinterest: setPinterest,
		};
		stateMap[platform](checked);
	};

	const handleSave = async () => {
		setSaving(true);
		try {
			await savePluginSettings({
				social_facebook: facebook,
				social_linkedin: linkedin,
				social_twitter: twitter,
				social_pinterest: pinterest,
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
		setFacebook(false);
		setLinkedin(false);
		setTwitter(false);
		setPinterest(false);
		toast.success(__('Settings reset successfully.', 'image-sizes'));
	};

	const socialPlatforms: SocialPlatform[] = [
		{
			key: 'facebook',
			label: __('Facebook', 'image-sizes'),
			description: __('Generate optimized share images for Facebook posts', 'image-sizes'),
			checked: facebook,
			iconBg: 'bg-[#EFF6FF]',
			icon: <Facebook size={20} color="#2577FF" />,
		},
		{
			key: 'linkedin',
			label: __('LinkedIn', 'image-sizes'),
			description: __('Create professional share images for LinkedIn', 'image-sizes'),
			checked: linkedin,
			iconBg: 'bg-[#EAF8FF]',
			icon: <Linkedin size={20} color="#0077B7" />,
		},
		{
			key: 'twitter',
			label: __('Twitter', 'image-sizes'),
			description: __('Optimize share images for Twitter cards', 'image-sizes'),
			checked: twitter,
			iconBg: 'bg-[#F0F0F0]',
			icon: (
				<svg
					width="18"
					height="18"
					viewBox="0 0 24 24"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				>
					<path
						d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
						fill="#1C1C1C"
					/>
				</svg>
			),
		},
		{
			key: 'pinterest',
			label: __('Pinterest', 'image-sizes'),
			description: __('Generate Pinterest-optimized pin images', 'image-sizes'),
			checked: pinterest,
			iconBg: 'bg-[#FFF0F1]',
			icon: (
				<svg
					width="22"
					height="22"
					viewBox="0 0 22 22"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				>
					<path
						d="M10.75 9.75L6.75 19.75"
						stroke="#D50012"
						strokeWidth="1.5"
						strokeLinecap="round"
						strokeLinejoin="round"
					/>
					<path
						d="M8.72368 15.3224C9.34306 15.5973 10.0287 15.75 10.75 15.75C13.5114 15.75 15.75 13.5114 15.75 10.75C15.75 7.98858 13.5114 5.75 10.75 5.75C7.98858 5.75 5.75 7.98858 5.75 10.75C5.75 11.6608 5.99367 12.5146 6.41921 13.25"
						stroke="#D50012"
						strokeWidth="1.5"
						strokeLinecap="round"
						strokeLinejoin="round"
					/>
					<circle
						cx="10.75"
						cy="10.75"
						r="10"
						stroke="#D50012"
						strokeWidth="1.5"
					/>
				</svg>
			),
		},
	];

	return (
		<div className="flex flex-col">
			{socialPlatforms.map((platform) => (
				<div
					key={platform.key}
					className="flex items-center justify-between py-4 first:pt-0 border-b border-[#E2E8F0]"
				>
					<div className="flex items-center gap-4">
						<div
							className={`w-10 h-10 rounded-lg flex items-center justify-center ${platform.iconBg}`}
						>
							{platform.icon}
						</div>
						<div>
							<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
								{platform.label}
							</h4>
							<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
								{platform.description}
							</p>
						</div>
					</div>
					<Switch
						checked={platform.checked}
						onCheckedChange={(checked) =>
							handleSocialToggle(platform.key, checked)
						}
					/>
				</div>
			))}

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
