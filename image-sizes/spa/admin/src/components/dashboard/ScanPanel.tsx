import { useEffect, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { toast } from 'sonner';
import { cancelScan, getScanProgress, startScan, type ScanProgress } from '../../api';

const POLL_MS = 3000;

/**
 * Runs the library scan on request, and disappears once the library is indexed.
 */
export default function ScanPanel({ onComplete, onState }: { onComplete?: () => void; onState?: (progress: ScanProgress) => void }) {
	const [progress, setProgress] = useState<ScanProgress | null>(null);
	const [busy, setBusy] = useState(false);
	const wasRunning = useRef(false);

	const read = async () => {
		try {
			const res: any = await getScanProgress();
			if (res?.data) {
				setProgress(res.data);
				onState?.(res.data);

				if (wasRunning.current && !res.data.is_running) {
					onComplete?.();
				}

				wasRunning.current = res.data.is_running;
			}
		} catch {
			// A failed poll is not worth a toast; the next one will tell the same story.
		}
	};

	useEffect(() => {
		read();

		// A card can start the scan; the panel owns the progress bar from then on.
		const onStarted = () => {
			wasRunning.current = true;
			read();
		};

		window.addEventListener('thumbpress:scan-started', onStarted);
		return () => window.removeEventListener('thumbpress:scan-started', onStarted);
	}, []);

	useEffect(() => {
		if (!progress?.is_running) {
			return;
		}

		const timer = setInterval(read, POLL_MS);
		return () => clearInterval(timer);
	}, [progress?.is_running]);

	const run = async () => {
		setBusy(true);
		try {
			const res: any = await startScan();
			if (res?.data) {
				setProgress(res.data);
				onState?.(res.data);
				wasRunning.current = true;
			}
			toast.success(__('Scan started. You can leave this page — it runs in the background.', 'image-sizes'));
		} catch {
			toast.error(__('Could not start the scan.', 'image-sizes'));
		} finally {
			setBusy(false);
		}
	};

	const stop = async () => {
		setBusy(true);
		try {
			const res: any = await cancelScan();
			if (res?.data) {
				setProgress(res.data);
				onState?.(res.data);
			}
			toast.success(__('Scan cancelled.', 'image-sizes'));
		} catch {
			toast.error(__('Could not cancel the scan.', 'image-sizes'));
		} finally {
			setBusy(false);
		}
	};

	if (!progress) {
		return null;
	}

	if (progress.is_running) {
		return (
			<div className="mb-5 p-5 rounded-xl border border-thumbpress-border bg-white">
				<div className="flex items-center justify-between gap-4">
					<div>
						<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
							{__('Scanning your media library…', 'image-sizes')}
						</h4>
						<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
							{progress.processed.toLocaleString()} / {progress.total.toLocaleString()}{' '}
							{__('images checked. This runs in the background.', 'image-sizes')}
						</p>
					</div>
					<button
						onClick={stop}
						disabled={busy}
						className="px-6 py-2.5 rounded-lg border border-thumbpress-primary text-thumbpress-primary text-sm font-medium hover:bg-thumbpress-primary/5 transition-colors cursor-pointer disabled:opacity-50"
					>
						{__('Cancel', 'image-sizes')}
					</button>
				</div>
				<div className="mt-4 h-2 w-full rounded-full bg-thumbpress-primary/10 overflow-hidden">
					<div
						className="h-full rounded-full bg-thumbpress-primary transition-all"
						style={{ width: `${progress.percent}%` }}
					/>
				</div>
			</div>
		);
	}

	if (!progress.is_ready) {
		return (
			<div className="mb-5 p-5 rounded-xl border border-thumbpress-border bg-white flex items-center justify-between gap-4">
				<div>
					<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">
						{__('Scan your media library', 'image-sizes')}
					</h4>
					<p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">
						{__('Duplicate and large-image counts need one pass over your library. Nothing is scanned until you ask for it.', 'image-sizes')}
					</p>
				</div>
				<button
					onClick={run}
					disabled={busy}
					className="px-8 py-2.5 rounded-lg bg-thumbpress-primary text-white text-sm font-medium hover:opacity-90 transition-opacity cursor-pointer disabled:opacity-50 whitespace-nowrap"
				>
					{__('Run scan', 'image-sizes')}
				</button>
			</div>
		);
	}

	// Indexed: uploads, deletes and metadata changes keep it current by themselves.
	return null;
}
