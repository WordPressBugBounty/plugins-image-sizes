import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { getDebugInfo, DebugInfo } from '../../api';

interface DebugProps {
	onDataLoaded?: (info: DebugInfo) => void;
}

function Badge({ ok }: { ok: boolean }) {
	return (
		<span
			className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
				ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
			}`}
		>
			{ok ? __( 'Enabled', 'image-sizes' ) : __( 'Disabled', 'image-sizes' )}
		</span>
	);
}

function StatusBadge({ status }: { status: string | null }) {
	if (!status) return <span className="text-[#94A3B8] text-sm">—</span>;
	const ok = status.toLowerCase() === 'active' || status.toLowerCase() === 'valid';
	return (
		<span
			className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
				ok ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'
			}`}
		>
			{status}
		</span>
	);
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
	return (
		<tr className="border-b border-[#F1F5F9] last:border-0">
			<td className="py-2.5 pr-6 text-sm text-[#64748B] w-48 align-top whitespace-nowrap">{label}</td>
			<td className="py-2.5 text-sm text-thumbpress-title font-medium">{value}</td>
		</tr>
	);
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
	return (
		<div className="mb-6 last:mb-0">
			<h3 className="text-xs font-semibold uppercase tracking-wide text-[#94A3B8] mb-2">{title}</h3>
			<table className="w-full">
				<tbody>{children}</tbody>
			</table>
		</div>
	);
}

const P = ({ w, h = 'h-3' }: { w: string; h?: string }) => (
	<div className={`animate-pulse bg-[#E2E8F0] rounded ${w} ${h}`} />
);

function DebugLoadingSkeleton() {
	type Sec = { hw: string; rows: Array<[string, string]> };
	const sections: Sec[] = [
		{
			hw: 'w-16',
			rows: [
				['w-24', 'w-16'], ['w-8',  'w-12'], ['w-14', 'w-16'],
				['w-12', 'w-24'], ['w-32', 'w-12'], ['w-28', 'w-8' ],
				['w-36', 'w-16'], ['w-40', 'w-10'], ['w-24', 'w-16'],
			],
		},
		{
			hw: 'w-36',
			rows: [['w-20', 'w-20'], ['w-32', 'w-20'], ['w-28', 'w-20']],
		},
		{
			hw: 'w-28',
			rows: [
				['w-16', 'w-12'], ['w-20', 'w-20'], ['w-28', 'w-20'],
				['w-24', 'w-12'], ['w-20', 'w-20'], ['w-36', 'w-20'],
				['w-24', 'w-20'], ['w-28', 'w-20'], ['w-24', 'w-16'],
				['w-32', 'w-20'], ['w-36', 'w-48'],
			],
		},
		{
			hw: 'w-28',
			rows: [['w-12', 'w-36'], ['w-16', 'w-8']],
		},
		{
			hw: 'w-32',
			rows: [['w-24', 'w-20'], ['w-32', 'w-8']],
		},
	];

	return (
		<div className="flex flex-col gap-6">
			{sections.map((sec, si) => (
				<div key={si}>
					<P w={sec.hw} h="h-3" />
					<table className="w-full mt-2">
						<tbody>
							{sec.rows.map(([lw, vw], ri) => (
								<tr key={ri} className="border-b border-[#F1F5F9] last:border-0">
									<td className="py-2.5 pr-6 w-48"><P w={lw} /></td>
									<td className="py-2.5"><P w={vw} h="h-5" /></td>
								</tr>
							))}
						</tbody>
					</table>
				</div>
			))}
		</div>
	);
}

export default function Debug({ onDataLoaded }: DebugProps) {
	const [info, setInfo] = useState<DebugInfo | null>(null);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState('');

	useEffect(() => {
		getDebugInfo()
			.then((res) => {
				if (res.success && res.data) {
					setInfo(res.data);
					onDataLoaded?.(res.data);
				} else {
					setError(__( 'Failed to load debug info.', 'image-sizes' ));
				}
			})
			.catch(() => setError(__( 'Failed to load debug info.', 'image-sizes' )))
			.finally(() => setLoading(false));
	}, []);

	if (loading) {
		return <DebugLoadingSkeleton />;
	}

	if (error || !info) {
		return <div className="text-sm text-red-500 py-4">{error || __( 'No data.', 'image-sizes' )}</div>;
	}

	const tp = info.thumbpress;

	return (
		<div>
			<Section title="System">
				<Row label="WordPress" value={info.wordpress_version} />
				<Row label="PHP" value={info.php_version} />
				<Row label="MySQL" value={info.mysql_version} />
				<Row label="Server" value={info.server_software} />
				<Row label="PHP Memory Limit" value={info.php_memory_limit} />
				<Row label="WP Memory Limit" value={info.wp_memory_limit} />
				<Row label="Max Execution Time" value={`${info.php_max_execution}s`} />
				<Row label="Upload Max Filesize" value={info.php_upload_max} />
				<Row label="Total Images" value={info.total_images.toLocaleString()} />
			</Section>

			<Section title="WordPress Config">
				<Row label="WP_DEBUG" value={<Badge ok={info.wp_debug} />} />
				<Row label="WP_DEBUG_DISPLAY" value={<Badge ok={info.wp_debug_display} />} />
				<Row label="WP_DEBUG_LOG" value={<Badge ok={info.wp_debug_log} />} />
			</Section>

			<Section title="ThumbPress">
				<Row label="Version" value={tp.thumbpress_version} />
				<Row
					label="Pro Plugin"
					value={
						tp.pro_active ? (
							<span className="inline-flex items-center gap-1.5">
								<Badge ok={true} />
								{tp.pro_version && (
									<span className="text-[#94A3B8] text-xs font-normal">v{tp.pro_version}</span>
								)}
							</span>
						) : (
							<Badge ok={false} />
						)
					}
				/>
				<Row
					label={__( 'License Status', 'image-sizes' )}
					value={<StatusBadge status={tp.license_status} />}
				/>
				<Row label="Lazy Load" value={<Badge ok={tp.lazy_load} />} />
				<Row label="Right-Click Disable" value={<Badge ok={tp.right_click_disable} />} />
				<Row label="Hotlink Protection" value={<Badge ok={tp.hotlink_protection} />} />
				<Row label="WebP on Upload" value={<Badge ok={tp.webp_on_upload} />} />
				<Row label="AVIF on Upload" value={<Badge ok={tp.avif_on_upload} />} />
				<Row label="Max Image Size" value={tp.max_file_size} />
				<Row label="Max Image Dimensions" value={tp.max_dimensions} />
				<Row
					label="Disabled Sizes"
					value={
						tp.disabled_sizes.length > 0 ? (
							<span className="font-mono text-xs">{tp.disabled_sizes.join(', ')}</span>
						) : (
							<span className="text-[#94A3B8]">{__( 'None', 'image-sizes' )}</span>
						)
					}
				/>
			</Section>

			<Section title="Active Theme">
				<Row label="Name" value={info.active_theme.name} />
				<Row label="Version" value={info.active_theme.version} />
				{info.active_theme.parent && (
					<Row
						label="Parent Theme"
						value={`${info.active_theme.parent.name} v${info.active_theme.parent.version}`}
					/>
				)}
			</Section>

			<Section title={__( `Active Plugins (${info.active_plugins.length})`, 'image-sizes' )}>
				{info.active_plugins.map((plugin) => (
					<Row
						key={plugin.slug}
						label={plugin.name}
						value={<span className="text-[#94A3B8] font-normal">v{plugin.version}</span>}
					/>
				))}
			</Section>
		</div>
	);
}
