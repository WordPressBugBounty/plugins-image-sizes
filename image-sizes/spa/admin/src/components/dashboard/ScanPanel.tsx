import { useEffect, useRef, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { Check, RefreshCw, Search } from 'lucide-react';
import { toast } from 'sonner';
import { cancelScan, getScanProgress, startScan, type ScanProgress } from '../../api';
import { dateFormat, numberFormat } from '../../lib/i18n';

const POLL_MS = 3000;

/**
 * Owns the library scan: reads where it stands, polls while it runs, and starts or cancels it.
 *
 * A hook, not a panel, because the dashboard shows it in two places: on top while it needs attention
 * (never run, running) and as a quiet line at the bottom once it is complete.
 */
export function useScan({ onComplete, onState }: { onComplete?: () => void; onState?: (progress: ScanProgress) => void }) {
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

	return { progress, busy, run, stop };
}

export type Scan = ReturnType<typeof useScan>;

/** Scanning or never scanned: the person needs to see this before anything else. */
export function ScanBar({ scan, imageCount = 0 }: { scan: Scan; imageCount?: number }) {
	const { progress, busy, run, stop } = scan;

	if (!progress || (progress.is_ready && !progress.is_running)) {
		return null;
	}

	const linkClass = 'font-medium text-[13px] whitespace-nowrap text-thumbpress-primary bg-transparent border-0 p-0 cursor-pointer disabled:opacity-50';
	const iconClass = 'w-6 h-6 rounded-full flex items-center justify-center shrink-0';
	const running = progress.is_running;

	return (
		<section aria-label={__('Library scan', 'image-sizes')} className="bg-white border border-[#E2E8F0] rounded-xl px-5 py-3.5 flex flex-col gap-2.5">
			<div className="flex items-center gap-3">
				{running
					? <span className={`${iconClass} bg-[#F0EBFF] text-thumbpress-primary`}><RefreshCw size={14} strokeWidth={1.75} aria-hidden="true" /></span>
					: <span className={`${iconClass} bg-slate-100 text-[#64748B]`}><Search size={14} strokeWidth={1.75} aria-hidden="true" /></span>}
				<div className="grow flex flex-wrap gap-x-2.5 gap-y-1 items-baseline">
					<span className="font-semibold text-thumbpress-title">{running ? __('Scanning your library', 'image-sizes') : __('Library not scanned yet', 'image-sizes')}</span>
					<span className="text-[13px] text-[#64748B] tabular-nums">
						{running
							? sprintf(
								/* translators: 1: images checked, 2: images in the library, 3: percent done. */
								__('%1$s of %2$s images · %3$s%% · safe to leave this page', 'image-sizes'),
								numberFormat(progress.processed),
								numberFormat(progress.total),
								numberFormat(progress.percent),
							)
							: sprintf(
								/* translators: %s: number of images in the library. */
								__('%s images found. Grades and counts need one pass over your library. Nothing is scanned until you ask.', 'image-sizes'),
								numberFormat(imageCount),
							)}
					</span>
				</div>
				<button onClick={running ? stop : run} disabled={busy} className={linkClass}>
					{running ? __('Cancel scan', 'image-sizes') : __('Run scan', 'image-sizes')}
				</button>
			</div>
			{running && (
				<div
					className="h-2 rounded-full bg-[#F0EBFF] overflow-hidden"
					role="progressbar"
					aria-valuenow={progress.percent}
					aria-valuemin={0}
					aria-valuemax={100}
					aria-label={__('Scan progress', 'image-sizes')}
				>
					<div className="h-full rounded-full bg-thumbpress-primary transition-all" style={{ width: `${progress.percent}%` }} />
				</div>
			)}
		</section>
	);
}

/** A finished scan is a fact, not a task: one quiet line at the end of the page, with the way to run it again. */
export function ScanFooter({ scan }: { scan: Scan }) {
	const { progress, busy, run } = scan;

	if (!progress || !progress.is_ready || progress.is_running) {
		return null;
	}

	return (
		<p aria-label={__('Library scan', 'image-sizes')} className="m-0 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-[13px] text-[#64748B] tabular-nums">
			<Check size={14} strokeWidth={2} className="text-[#047857]" aria-hidden="true" />
			<span>
				{progress.completed_at
					? sprintf(
						/* translators: 1: images indexed, 2: date of the scan. */
						__('Library scanned · %1$s images · %2$s', 'image-sizes'),
						numberFormat(progress.indexed),
						dateFormat(progress.completed_at),
					)
					: sprintf(
						/* translators: %s: images indexed. */
						__('Library scanned · %s images', 'image-sizes'),
						numberFormat(progress.indexed),
					)}
			</span>
			<button onClick={run} disabled={busy} className="font-medium text-thumbpress-primary bg-transparent border-0 p-0 cursor-pointer disabled:opacity-50">
				{__('Scan again', 'image-sizes')}
			</button>
		</p>
	);
}
