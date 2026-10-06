import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { Eye } from 'lucide-react';
import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';
import Card from '../components/ui/card';
import ProAlert from '../components/ui/pro-alert';
import SettingsButton from '../components/ui/settings-button';
import { getDashboardCountsStats } from '../api';
import { numberFormat } from '../lib/i18n';

/**
 * Free stub for the Watermark page. Every action opens the pro upsell; pro replaces the whole page
 * through `thumbpress_component_map` once its licence is active.
 */
export default function Watermark() {
	const navigate = useNavigate();
	const [totalImages, setTotalImages] = useState<number | null>(null);
	const [alertOpen, setAlertOpen] = useState(false);
	const openAlert = () => setAlertOpen(true);
	const isProActive = window.THUMBPRESS?.pro_active;

	// Pro replaces this whole page through `thumbpress_component_map`. A Pro build older than the
	// watermark replaces nothing, so these actions are reached by someone who already pays, and the
	// upsell dialog is the wrong answer for them (#575).
	const handleProAction = () => {
		if (isProActive) {
			toast.error(__( 'This version of ThumbPress Pro cannot apply a watermark yet. Update ThumbPress Pro to the latest version.', 'image-sizes' ));
			return;
		}

		openAlert();
	};

	// Free can count the library; only Pro tracks which images are watermarked, so those stay blank.
	useEffect(() => {
		getDashboardCountsStats()
			.then((res) => {
				if (res.success) {
					setTotalImages(Number(res.data.total_images) || 0);
				}
			})
			.catch(() => undefined);
	}, []);

	const detectImageUrl =
		(window.THUMBPRESS?.assets_url || '') + 'admin/img/no-search-result.png';

	const statCard = (value: string, label: string) => (
		<div className="flex items-center gap-3 rounded-xl border border-[#E2E8F0] bg-white px-5 py-4">
			<div>
				<p className="text-2xl font-bold text-thumbpress-title">{value}</p>
				<p className="text-sm text-[#64748B]">{label}</p>
			</div>
		</div>
	);

	return (
		<>
			<Header title={__('Watermark', 'image-sizes')} />

			<PluginPage>
				<Card
					title={__( 'Watermark Your Images', 'image-sizes' )}
					description={__( 'Put your name or logo on your photos so they stay yours when they are copied. The untouched original is always kept, so you can change or remove the watermark any time.', 'image-sizes' )}
					headerAction={<SettingsButton onClick={() => navigate('/settings?tab=watermark')} />}
				>
					<div className="flex flex-col items-center py-10">
						<img src={detectImageUrl} alt="" />
						<h3 className="text-xl font-bold text-thumbpress-title mb-[6px]">
							{__( 'Ready to Protect Your Images?', 'image-sizes' )}
						</h3>
						<p className="text-sm text-[#64748B] mb-8 text-center max-w-[520px]">
							{__( 'Choose text or your logo, pick the position, size and opacity, then apply it to your whole library in one run, or to every new upload automatically.', 'image-sizes' )}
						</p>

						<div className="grid grid-cols-3 gap-4 w-full max-w-[700px] mb-8">
							{statCard(null === totalImages ? '—' : numberFormat(totalImages), __( 'Total Images', 'image-sizes' ))}
							{statCard('—', __( 'Watermarked', 'image-sizes' ))}
							{statCard('—', __( 'Not Yet Watermarked', 'image-sizes' ))}
						</div>

						<div className="flex flex-wrap justify-center gap-4">
							<button
								onClick={() => navigate('/settings?tab=watermark&preview=1')}
								className="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-[#D2D2D5] bg-white text-[#4A4C56] py-2.5 w-[225px] cursor-pointer"
							>
								<Eye size={16} strokeWidth={1.75} aria-hidden="true" />
								{__( 'Preview your watermark', 'image-sizes' )}
							</button>
							<button
								onClick={handleProAction}
								className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors bg-thumbpress-primary text-white border border-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
							>
								{__( 'Apply to unmarked images', 'image-sizes' )}
							</button>
							<button
								onClick={handleProAction}
								className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors border border-thumbpress-primary bg-white text-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
							>
								{__( 'Re-apply to all', 'image-sizes' )}
							</button>
						</div>
					</div>

					<ProAlert
						title={__( 'Watermarking is a Pro feature', 'image-sizes' )}
						description={__( 'Put your name or logo on every photo automatically. Choose the position, size and opacity, apply it to your whole library, and undo it any time because the original is kept.', 'image-sizes' )}
						buttonText={__( 'Upgrade to protect your images', 'image-sizes' )}
						open={alertOpen}
						onClose={() => setAlertOpen(false)}
					/>
				</Card>
			</PluginPage>
		</>
	);
}
