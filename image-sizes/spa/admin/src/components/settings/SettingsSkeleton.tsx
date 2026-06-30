import React from 'react';

const Pulse = ({ className }: { className: string }) => (
	<div className={`animate-pulse bg-[#E2E8F0] rounded ${className}`} />
);

const SkeletonSwitch = () => (
	<div className="w-11 h-6 bg-[#E2E8F0] rounded-full animate-pulse flex-shrink-0" />
);

interface RowProps {
	labelW?: string;
	descW?: string;
	badge?: boolean;
	right?: React.ReactNode;
}

const Row = ({ labelW = 'w-40', descW = 'w-64', badge = false, right }: RowProps) => (
	<div className="flex items-center justify-between py-4 border-b border-[#E2E8F0]">
		<div className="flex flex-col gap-2">
			<div className="flex items-center gap-2">
				<Pulse className={`${labelW} h-4`} />
				{badge && <Pulse className="w-8 h-5 rounded" />}
			</div>
			<Pulse className={`${descW} h-3`} />
		</div>
		{right ?? <SkeletonSwitch />}
	</div>
);

const Buttons = () => (
	<div className="flex items-center justify-end gap-4 pt-6">
		<Pulse className="w-32 h-9 rounded-lg" />
		<Pulse className="w-32 h-9 rounded-lg" />
	</div>
);

function GeneralSkeleton() {
	return (
		<div className="flex flex-col">
			<Row labelW="w-28" descW="w-80" badge />
			<Row labelW="w-52" descW="w-72" badge />
			<Row labelW="w-44" descW="w-64" />
			<Row labelW="w-32" descW="w-56" right={<Pulse className="w-36 h-[46px] rounded-lg" />} />
			<Row labelW="w-40" descW="w-60" right={<Pulse className="w-52 h-[46px] rounded-lg" />} />
			<Row labelW="w-20" descW="w-68" />
			<Row labelW="w-36" descW="w-80" />
			<Buttons />
		</div>
	);
}

function ThumbnailsSkeleton() {
	const rows: Array<[string, string]> = [
		['w-28', 'w-64'],
		['w-36', 'w-72'],
		['w-32', 'w-68'],
		['w-44', 'w-72'],
		['w-32', 'w-72'],
		['w-36', 'w-72'],
		['w-36', 'w-72'],
		['w-32', 'w-72'],
	];
	return (
		<div className="flex flex-col">
			{rows.map(([lw, dw], i) => (
				<Row key={i} labelW={lw} descW={dw} />
			))}
			<Buttons />
		</div>
	);
}

function DuplicateSkeleton() {
	return (
		<div className="flex flex-col">
			<Row labelW="w-44" descW="w-72" badge />
			<Buttons />
		</div>
	);
}

function CompressSkeleton() {
	return (
		<div className="flex flex-col">
			<Row labelW="w-40" descW="w-72" badge />
			<Row labelW="w-44" descW="w-60" badge right={<Pulse className="w-20 h-9 rounded-lg" />} />
			<Row labelW="w-36" descW="w-64" badge />
			<Buttons />
		</div>
	);
}

function TwoSwitchSkeleton() {
	return (
		<div className="flex flex-col">
			<Row labelW="w-40" descW="w-72" />
			<Row labelW="w-44" descW="w-64" />
			<Buttons />
		</div>
	);
}

function SocialSkeleton() {
	const platforms: Array<[string, string]> = [
		['w-20', 'w-64'],
		['w-20', 'w-52'],
		['w-16', 'w-56'],
		['w-20', 'w-60'],
	];
	return (
		<div className="flex flex-col">
			{platforms.map(([lw, dw], i) => (
				<div
					key={i}
					className="flex items-center justify-between py-4 border-b border-[#E2E8F0]"
				>
					<div className="flex items-center gap-4">
						<Pulse className="w-10 h-10 rounded-lg flex-shrink-0" />
						<div className="flex flex-col gap-2">
							<Pulse className={`${lw} h-4`} />
							<Pulse className={`${dw} h-3`} />
						</div>
					</div>
					<SkeletonSwitch />
				</div>
			))}
			<Buttons />
		</div>
	);
}

function DebugSkeleton() {
	type Section = { hw: string; rows: Array<[string, string]> };
	const sections: Section[] = [
		{
			hw: 'w-16',
			rows: [
				['w-24', 'w-16'],
				['w-8',  'w-12'],
				['w-14', 'w-16'],
				['w-12', 'w-24'],
				['w-32', 'w-12'],
				['w-28', 'w-8' ],
				['w-36', 'w-16'],
				['w-40', 'w-10'],
				['w-24', 'w-16'],
			],
		},
		{
			hw: 'w-36',
			rows: [
				['w-20', 'w-20'],
				['w-32', 'w-20'],
				['w-28', 'w-20'],
			],
		},
		{
			hw: 'w-28',
			rows: [
				['w-16', 'w-12'],
				['w-20', 'w-20'],
				['w-28', 'w-20'],
				['w-24', 'w-12'],
				['w-20', 'w-20'],
				['w-36', 'w-20'],
				['w-24', 'w-20'],
				['w-28', 'w-20'],
				['w-24', 'w-16'],
				['w-32', 'w-20'],
				['w-36', 'w-48'],
			],
		},
		{
			hw: 'w-28',
			rows: [
				['w-12', 'w-36'],
				['w-16', 'w-8' ],
			],
		},
		{
			hw: 'w-32',
			rows: [
				['w-24', 'w-20'],
				['w-32', 'w-8' ],
			],
		},
	];

	return (
		<div className="flex flex-col gap-6">
			{sections.map((section, si) => (
				<div key={si}>
					<Pulse className={`${section.hw} h-3 mb-3`} />
					<div className="flex flex-col">
						{section.rows.map(([lw, vw], ri) => (
							<div
								key={ri}
								className="flex items-center justify-between py-2 border-b border-[#F1F5F9] last:border-0"
							>
								<Pulse className={`${lw} h-3`} />
								<Pulse className={`${vw} h-5 rounded`} />
							</div>
						))}
					</div>
				</div>
			))}
		</div>
	);
}

const TAB_SLUGS = [
	'general',
	'thumbnails',
	'duplicate-images',
	'compress-images',
	'convert-to-webp',
	'convert-to-avif',
	'social-share-image',
	'debug',
];

const TAB_WIDTHS = ['w-16', 'w-24', 'w-36', 'w-32', 'w-32', 'w-32', 'w-40', 'w-14'];

const CARD_TITLE_WIDTHS: Record<string, string> = {
	general:             'w-36',
	thumbnails:          'w-44',
	'duplicate-images':  'w-56',
	'compress-images':   'w-52',
	'convert-to-webp':   'w-36',
	'convert-to-avif':   'w-36',
	'social-share-image':'w-48',
	debug:               'w-40',
};

const CONTENT_MAP: Record<string, React.ComponentType> = {
	general:             GeneralSkeleton,
	thumbnails:          ThumbnailsSkeleton,
	'duplicate-images':  DuplicateSkeleton,
	'compress-images':   CompressSkeleton,
	'convert-to-webp':   TwoSwitchSkeleton,
	'convert-to-avif':   TwoSwitchSkeleton,
	'social-share-image':SocialSkeleton,
	debug:               DebugSkeleton,
};

export default function SettingsSkeleton({ activeTab }: { activeTab: string }) {
	const Content = CONTENT_MAP[activeTab] ?? GeneralSkeleton;
	const titleW  = CARD_TITLE_WIDTHS[activeTab] ?? 'w-40';

	return (
		<div className="rounded-xl border border-[#E2E8F0] bg-white p-6 h-full">
			{/* Tab bar */}
			<div className="flex items-center gap-1 border-b border-[#E2E8F0] mb-5 overflow-x-auto">
				{TAB_SLUGS.map((slug, i) => (
					<div
						key={slug}
						className={`flex items-center flex-shrink-0 h-9 px-3 border-b-2 ${
							slug === activeTab
								? 'border-thumbpress-primary'
								: 'border-transparent'
						}`}
					>
						<Pulse className={`${TAB_WIDTHS[i]} h-3`} />
					</div>
				))}
			</div>

			{/* Card */}
			<div className="max-w-[900px] rounded-lg border border-[#E2E8F0] bg-white shadow-sm">
				<div className="px-6 h-[68px] border-b border-[#E2E8F0] flex items-center gap-2">
					<Pulse className={`${titleW} h-5`} />
					<Pulse className="w-4 h-4 rounded-full" />
				</div>
				<div className="p-6">
					<Content />
				</div>
			</div>
		</div>
	);
}
