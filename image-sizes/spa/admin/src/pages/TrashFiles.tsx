import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import Header from '../components/layout/Header';
import PluginPage from '../components/layout/PluginPage';
import Card from '../components/ui/card';
import { Checkbox } from '../components/ui/checkbox';
import ProAlert from '../components/ui/pro-alert';

export default function TrashFiles() {
	const proInstalled = !!(window as any).THUMBPRESS?.pro_installed;
	const proActive    = !!(window as any).THUMBPRESS?.pro_active;
	const [alertOpen, setAlertOpen] = useState( proInstalled && ! proActive );

	return (
		<>
			<Header title={__( 'Trashed Images', 'image-sizes' )} />

			<PluginPage>
				<Card
					title="Manage Trashed Images"
					description="Leftover files from old conversions and failed uploads quietly pile up. Delete the junk and reclaim real disk space in seconds."
				>
					<div className="border border-thumbpress-border rounded-xl overflow-hidden">
						<div className="flex items-center gap-3 p-4 h-[48px] bg-thumbpress-primary/5 border-b border-thumbpress-border text-thumbpress-title text-base">
							<div className="w-max">
								<Checkbox checked={false} onCheckedChange={() => {}} />
							</div>
							<div className="w-[45%]">File Name</div>
							<div className="w-[15%] text-center">Image</div>
							<div className="w-[15%] text-center">Upload Date</div>
							<div className="w-[10%] text-center">File Size</div>
							<div className="w-[10%] text-center">Action</div>
						</div>

						<div className="p-12 text-center text-thumbpress-body">
							No trashed images found.
						</div>
					</div>

					<ProAlert
						title="Manage Trashed Images is a Pro feature"
						description="Restore or permanently delete images you've moved to trash. Keep your library clean and reclaim disk space."
						buttonText="Activate License to manage trashed images"
						open={alertOpen}
						onClose={() => setAlertOpen(false)}
					/>
				</Card>
			</PluginPage>
		</>
	);
}
