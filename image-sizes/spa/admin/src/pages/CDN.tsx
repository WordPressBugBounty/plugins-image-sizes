import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';
import Card from '../components/ui/card';
import ProAlert from '../components/ui/pro-alert';

/**
 * Free upsell page for CDN offloading.
 *
 * Pro replaces this component through `thumbpress_component_map` once the
 * license is active, and registers its own `/cdn` route + nav item from PHP.
 * The free plugin only ships this route while pro is missing or unlicensed.
 */
export default function CDN() {
	const [alertOpen, setAlertOpen] = useState(false);
	const openAlert = () => setAlertOpen(true);

	const detectImageUrl =
		(window.THUMBPRESS?.assets_url || '') + 'admin/img/no-search-result.png';

	const features = [
		{
			title: __( 'Auto-offload new uploads', 'image-sizes' ),
			description: __( 'Every new image is pushed to the CDN the moment it lands in your media library.', 'image-sizes' ),
		},
		{
			title: __( 'Free up server disk space', 'image-sizes' ),
			description: __( 'Keep a local copy or drop it after upload and serve everything from the edge.', 'image-sizes' ),
		},
		{
			title: __( 'Responsive images included', 'image-sizes' ),
			description: __( 'srcset URLs and WooCommerce product, variation and gallery images are rewritten too.', 'image-sizes' ),
		},
		{
			title: __( 'Automatic local fallback', 'image-sizes' ),
			description: __( 'If the CDN is ever unreachable, images are served from your server instead of breaking the page.', 'image-sizes' ),
		},
	];

	return (
		<>
			<Header title={__('CDN', 'image-sizes')} />

			<PluginPage>
				<Card
					title={__( 'Serve Images From a Global Edge Network', 'image-sizes' )}
					description={__( 'Your images travel from one server to every visitor on earth. Offload them to a CDN and let the closest edge do the work - faster pages, lighter server, no setup.', 'image-sizes' )}
				>
					{/* Empty state */}
					<div className="flex flex-col items-center py-10">
						<img src={detectImageUrl} alt="" />
						<h3 className="text-xl font-bold text-thumbpress-title mb-[6px]">
							{__( 'Ready to Move Your Images to the Edge?', 'image-sizes' )}
						</h3>
						<p className="text-sm text-[#64748B] mb-8">
							{__( 'Connect ThumbPress CDN and every image in your library is delivered from the location nearest your visitor.', 'image-sizes' )}
						</p>

						<div className="w-full max-w-[675px]">
							<div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
								{features.map((feature) => (
									<div
										key={feature.title}
										className="rounded-lg border border-thumbpress-border p-4"
									>
										<h4 className="text-base font-semibold text-thumbpress-title mb-1">
											{feature.title}
										</h4>
										<p className="text-sm text-[#64748B] leading-relaxed">
											{feature.description}
										</p>
									</div>
								))}
							</div>

							<div className="flex justify-center gap-6 mt-8">
								<button
									onClick={openAlert}
									className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 bg-thumbpress-primary text-white border border-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
								>
									{__( 'Enable CDN', 'image-sizes' )}
								</button>
							</div>
						</div>
					</div>

					<ProAlert
						title={__( 'CDN Offloading is a Pro feature', 'image-sizes' )}
						description={__( 'Serve your images from a global edge network instead of a single server. Pages load faster for visitors everywhere, with zero setup required.', 'image-sizes' )}
						buttonText={__( 'Upgrade to enable CDN', 'image-sizes' )}
						open={alertOpen}
						onClose={() => setAlertOpen(false)}
					/>
				</Card>
			</PluginPage>
		</>
	);
}
