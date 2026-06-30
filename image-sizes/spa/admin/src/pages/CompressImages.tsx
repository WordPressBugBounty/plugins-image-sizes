import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';
import Card from '../components/ui/card';
import ProAlert from '../components/ui/pro-alert';
import { Tooltip } from '../components/ui/tooltip';

export default function CompressImages({ tooltip }: { tooltip?: string } = {}) {
	const [alertOpen, setAlertOpen] = useState(false);
	const openAlert = () => setAlertOpen(true);

	const [chunkSize, setChunkSize] = useState('20');
	const [compression, setCompression] = useState('80');

	const detectImageUrl =
		(window.THUMBPRESS?.assets_url || '') + 'admin/img/no-search-result.png';

	return (
		<>
			<Header title={__('Compress Images', 'image-sizes')} />

			<PluginPage>
				<Card
					title="Compress Your Images"
					description="Uncompressed images are the #1 reason sites fail Core Web Vitals. Compress your library in minutes - smaller files, higher rankings, zero quality loss."
				>
					<div className="flex flex-col items-center py-10">
						<img src={detectImageUrl} alt="" />
						<h3 className="text-xl font-bold text-thumbpress-title mb-[6px]">
							Ready to Compress Your Images?
						</h3>
						<p className="text-sm text-[#64748B] mb-8">
							Configure your compression settings above and click "Compress Now" to optimize your image library.
						</p>

						<div className="w-full max-w-[675px]">
							<div className="mb-6">
								<div className="flex items-center gap-2 mb-2">
									<label className="text-base font-semibold text-thumbpress-title block">
										Chunk Size
									</label>
									<Tooltip content={tooltip || 'Number of images processed in a single batch per server request.'}>
										<svg className="cursor-help" width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M8.54973 4.97845C8.54973 4.85198 8.65215 4.75046 8.77772 4.75046H10.2223C10.3478 4.75046 10.4503 4.85198 10.4503 4.97845V6.4221C10.4503 6.54767 10.3478 6.65009 10.2223 6.65009H8.77772C8.65215 6.65009 8.54973 6.54767 8.54973 6.4221V4.97845ZM8.54973 8.77772C8.54973 8.65215 8.65215 8.54973 8.77772 8.54973H10.2223C10.3478 8.54973 10.4503 8.65215 10.4503 8.77772V14.0216C10.4503 14.148 10.3478 14.2495 10.2223 14.2495H8.77772C8.65215 14.2495 8.54973 14.148 8.54973 14.0216V8.77772ZM9.5 0C4.25617 0 0 4.25617 0 9.5C0 14.7438 4.25617 19 9.5 19C14.7438 19 19 14.7438 19 9.5C19 4.25617 14.7438 0 9.5 0ZM9.5 17.0995C5.31063 17.0995 1.90055 13.6894 1.90055 9.5C1.90055 5.31063 5.31063 1.90055 9.5 1.90055C13.6894 1.90055 17.0995 5.31063 17.0995 9.5C17.0995 13.6894 13.6894 17.0995 9.5 17.0995Z" fill="#1B2538" />
										</svg>
									</Tooltip>
								</div>
								<input
									type="number"
									value={chunkSize}
									onChange={(e) => setChunkSize(e.target.value)}
									placeholder="e.g. 50"
									className="flex w-full !rounded-lg !border !border-thumbpress-border bg-white !px-4 !h-[56px] text-sm placeholder:text-gray-400 focus:!outline-none focus:!shadow-none"
								/>
							</div>

							<div>
								<div className="flex items-center gap-2 mb-2">
									<label className="text-base font-semibold text-thumbpress-title block">
										Quality Level
									</label>
									<Tooltip content={tooltip || 'Compression quality from 0-100. Higher values preserve more detail but result in larger files. 80+ recommended for best results.'}>
										<svg className="cursor-help" width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M8.54973 4.97845C8.54973 4.85198 8.65215 4.75046 8.77772 4.75046H10.2223C10.3478 4.75046 10.4503 4.85198 10.4503 4.97845V6.4221C10.4503 6.54767 10.3478 6.65009 10.2223 6.65009H8.77772C8.65215 6.65009 8.54973 6.54767 8.54973 6.4221V4.97845ZM8.54973 8.77772C8.54973 8.65215 8.65215 8.54973 8.77772 8.54973H10.2223C10.3478 8.54973 10.4503 8.65215 10.4503 8.77772V14.0216C10.4503 14.148 10.3478 14.2495 10.2223 14.2495H8.77772C8.65215 14.2495 8.54973 14.148 8.54973 14.0216V8.77772ZM9.5 0C4.25617 0 0 4.25617 0 9.5C0 14.7438 4.25617 19 9.5 19C14.7438 19 19 14.7438 19 9.5C19 4.25617 14.7438 0 9.5 0ZM9.5 17.0995C5.31063 17.0995 1.90055 13.6894 1.90055 9.5C1.90055 5.31063 5.31063 1.90055 9.5 1.90055C13.6894 1.90055 17.0995 5.31063 17.0995 9.5C17.0995 13.6894 13.6894 17.0995 9.5 17.0995Z" fill="#1B2538" />
										</svg>
									</Tooltip>
								</div>
								<input
									type="number"
									value={compression}
									onChange={(e) => setCompression(e.target.value)}
									placeholder="e.g. 80 (0-100)"
									className="flex w-full !rounded-lg !border !border-thumbpress-border bg-white !px-4 !h-[56px] text-sm placeholder:text-gray-400 focus:!outline-none focus:!shadow-none"
								/>
							</div>

							<div className="flex justify-center gap-6 mt-8">
								<button
									onClick={openAlert}
									disabled={!chunkSize.trim() || !compression.trim()}
									className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 border border-thumbpress-primary bg-white text-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
								>
									Compress Now
								</button>
								<button
									onClick={openAlert}
									disabled={!chunkSize.trim() || !compression.trim()}
									className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 bg-thumbpress-primary text-white border border-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
								>
									Compress In Background
								</button>
							</div>
						</div>
					</div>

					<ProAlert
						title="Bulk Image Compression is a Pro feature"
						description="Every heavy image loses you a visitor. Compress your entire library in one click and watch page speed - and conversions - climb."
						buttonText="Upgrade for a faster website"
						open={alertOpen}
						onClose={() => setAlertOpen(false)}
					/>
				</Card>
			</PluginPage>
		</>
	);
}
