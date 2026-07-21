import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';
import Card from '../components/ui/card';
import ProAlert from '../components/ui/pro-alert';
import { Tooltip } from '../components/ui/tooltip';

export default function DetectDuplicateImages({ tooltip }: { tooltip?: string } = {}) {
	const [alertOpen, setAlertOpen] = useState(false);
	const openAlert = () => setAlertOpen(true);

	const [chunkSize, setChunkSize] = useState('1000');

	const detectImageUrl =
		(window.THUMBPRESS?.assets_url || '') + 'admin/img/no-search-result.png';

	return (
		<>
			<Header title={__('Duplicate Images', 'image-sizes')} />

			<PluginPage>
				<Card
					title={__( 'Find & Remove Duplicate Images', 'image-sizes' )}
					description={__( 'Duplicates inflate storage and bloat backups. Find every redundant file and clean house without risking your originals.', 'image-sizes' )}
				>
					<div className="flex flex-col items-center py-10">
						<img src={detectImageUrl} alt="" />
						<h3 className="text-xl font-bold text-thumbpress-title mb-[6px]">
							{__( 'Ready to Scan for Duplicates?', 'image-sizes' )}
						</h3>
						<p className="text-sm text-[#64748B] mb-8">
							{__( 'Click "Detect Now" to scan your library and identify redundant files taking up unnecessary storage space.', 'image-sizes' )}
						</p>

						<div className="w-full max-w-[675px]">
							<div className="flex items-center gap-2 mb-2">
								<label className="text-base font-semibold text-thumbpress-title block">
									{__( 'Chunk Size', 'image-sizes' )}
								</label>
								<Tooltip content={tooltip || __( 'Number of images processed in a single batch per server request.', 'image-sizes' )}>
									<svg className="cursor-help" width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M8.54973 4.97845C8.54973 4.85198 8.65215 4.75046 8.77772 4.75046H10.2223C10.3478 4.75046 10.4503 4.85198 10.4503 4.97845V6.4221C10.4503 6.54767 10.3478 6.65009 10.2223 6.65009H8.77772C8.65215 6.65009 8.54973 6.54767 8.54973 6.4221V4.97845ZM8.54973 8.77772C8.54973 8.65215 8.65215 8.54973 8.77772 8.54973H10.2223C10.3478 8.54973 10.4503 8.65215 10.4503 8.77772V14.0216C10.4503 14.148 10.3478 14.2495 10.2223 14.2495H8.77772C8.65215 14.2495 8.54973 14.148 8.54973 14.0216V8.77772ZM9.5 0C4.25617 0 0 4.25617 0 9.5C0 14.7438 4.25617 19 9.5 19C14.7438 19 19 14.7438 19 9.5C19 4.25617 14.7438 0 9.5 0ZM9.5 17.0995C5.31063 17.0995 1.90055 13.6894 1.90055 9.5C1.90055 5.31063 5.31063 1.90055 9.5 1.90055C13.6894 1.90055 17.0995 5.31063 17.0995 9.5C17.0995 13.6894 13.6894 17.0995 9.5 17.0995Z" fill="#1B2538" />
									</svg>
								</Tooltip>
							</div>
							<input
								type="number"
								value={chunkSize}
								onChange={(e) => setChunkSize(e.target.value)}
								placeholder={__( 'e.g. 50', 'image-sizes' )}
								className="flex w-full !rounded-lg !border !border-thumbpress-border bg-white !px-4 !h-[56px] text-sm placeholder:text-gray-400 focus:!outline-none focus:!shadow-none"
							/>

							<div className="flex justify-center gap-6 mt-8">
								<button
									onClick={openAlert}
									disabled={!chunkSize.trim()}
									className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 border border-thumbpress-primary bg-white text-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
								>
									{__( 'Detect Now', 'image-sizes' )}
								</button>
								<button
									onClick={openAlert}
									disabled={!chunkSize.trim()}
									className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 bg-thumbpress-primary text-white border border-thumbpress-primary py-2.5 w-[225px] cursor-pointer"
								>
									{__( 'Detect in background', 'image-sizes' )}
								</button>
							</div>
						</div>
					</div>

					<ProAlert
						title={__( 'Duplicate Image Detection is a Pro feature', 'image-sizes' )}
						description={__( 'The same file uploaded twice wastes space every day. Surface every duplicate and reclaim storage you didn\'t know you were losing.', 'image-sizes' )}
						buttonText={__( 'Upgrade to detect duplicate images', 'image-sizes' )}
						open={alertOpen}
						onClose={() => setAlertOpen(false)}
					/>
				</Card>
			</PluginPage>
		</>
	);
}
