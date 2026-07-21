import React from 'react';
import { __ } from '@wordpress/i18n';

const Comparison = () => {
	const comparisonFeatures: Array<{
		category: string;
		features: Array<{ name: string; free: boolean; pro: boolean; key?: string }>;
	}> = [
		{
			category: __( 'IMAGE CONTROL', 'image-sizes' ),
			features: [
				{
					name: __( 'Disable Thumbnails', 'image-sizes' ),
					free: true,
					pro: true,
				},
				{
					name: __( 'Regenerate Thumbnails', 'image-sizes' ),
					free: true,
					pro: true,
				},
				{
					name: __( 'Set Image Upload Limit', 'image-sizes' ),
					free: true,
					pro: true,
				},
			],
		},
		{
			category: __( 'STORAGE & CLEANUP', 'image-sizes' ),
			features: [
				{
					name: __( 'Detect Large Images', 'image-sizes' ),
					free: false,
					pro: true,
				},
				{
					name: __( 'Find Unused Images', 'image-sizes' ),
					free: false,
					pro: true,
				},
				{
					name: __( 'Find Duplicate Images', 'image-sizes' ),
					free: false,
					pro: true,
				},
				{
					name: __( 'Compress Images', 'image-sizes' ),
					free: false,
					pro: true,
				},
			],
		},
		{
			category: __( 'MEDIA MANAGEMENT', 'image-sizes' ),
			features: [
				{
					name: __( 'Replace Images', 'image-sizes' ),
					free: false,
					pro: true,
				},
				{
					name: __( 'Image Editor', 'image-sizes' ),
					free: false,
					pro: true,
				},
				{
					name: __( 'Social Media Thumbnails', 'image-sizes' ),
					free: true,
					pro: true,
				},
				{
					name: __( 'Convert to AVIF', 'image-sizes' ),
					key: 'avif',
					free: true,
					pro: true,
				},
				{
					name: __( 'Convert to WebP', 'image-sizes' ),
					free: true,
					pro: true,
				},
			],
		},
	];

	return (
		<div id="comparison" className="2xl:px-[300px] lg:px-[80px] py-16">
			<div className="text-center mb-10">
				<h2 className="2xl:text-[32px] lg:text-[28px] font-semibold text-thumbpress-title mb-2 max-w-[600px] mx-auto leading-[1.4]">
					{__( 'Free vs. Pro Side by Side', 'image-sizes' )}
				</h2>
				<p className="text-base text-thumbpress-body max-w-[484px] mx-auto">
					{__( 'See a clear comparison between your current version and the Pro version you\'re about to get.', 'image-sizes' )}
				</p>
			</div>
			<div>
				<div className="rounded-xl border border-gray-200 overflow-hidden">
					{/* Table Header */}
					<div className="grid grid-cols-4 bg-thumbpress-primary text-white border-t border-thumbpress-border">
						<div className="px-6 py-4 text-xl font-semibold col-span-2">{__( 'Features', 'image-sizes' )}</div>
						<div className="px-4 py-4 text-xl font-semibold text-center col-span-1">
							{__( 'Free', 'image-sizes' )}
						</div>
						<div className="px-4 py-4 text-xl font-semibold text-center col-span-1">
							{__( 'Pro', 'image-sizes' )}
						</div>
					</div>
					{/* Table Body */}
					{comparisonFeatures.map((section) => (
						<React.Fragment key={section.category}>
							<div className="px-6 py-2.5 bg-thumbpress-primary/5">
								<span className="text-base font-medium text-thumbpress-title tracking-wider">
									{section.category}
								</span>
							</div>
							{section.features.map((feature, idx) => (
								<div
									key={idx}
									className="grid grid-cols-4 border-t border-thumbpress-border"
								>
									<div className="px-6 py-4 col-span-2">
										<span className="text-base text-thumbpress-body">
											{feature.name}
										</span>
									</div>
									
									<div className="flex items-center justify-center border-x border-thumbpress-border col-span-1">
										{feature.key === 'avif' ? (
											<span className="text-sm text-[#777980]">
												{__( 'Future uploads only', 'image-sizes' )}
											</span>
										) : feature.free ? (
											<svg
												width="20"
												height="20"
												viewBox="0 0 20 20"
												fill="none"
												xmlns="http://www.w3.org/2000/svg"
											>
												<rect width="20" height="20" rx="10" fill="#00BC7A" />
												<path
													d="M14.4335 6.00045C14.1718 6.00917 13.9239 6.11962 13.7424 6.30833C11.8167 8.23821 10.346 9.85005 8.55873 11.6742L6.65491 10.0657C6.55389 9.98027 6.43701 9.91563 6.31096 9.87546C6.18492 9.83529 6.05218 9.82039 5.92036 9.8316C5.78855 9.84282 5.66024 9.87993 5.54279 9.94081C5.42534 10.0017 5.32106 10.0852 5.23592 10.1864C5.15078 10.2877 5.08646 10.4047 5.04664 10.5309C5.00682 10.6571 4.99228 10.7898 5.00386 10.9216C5.01544 11.0534 5.05291 11.1816 5.11412 11.2989C5.17533 11.4162 5.25908 11.5202 5.36057 11.6051L7.97439 13.8168C8.1667 13.9788 8.41273 14.0628 8.66397 14.0523C8.9152 14.0419 9.15339 13.9377 9.33156 13.7602C11.497 11.5901 13.0385 9.85683 15.1624 7.72834C15.3083 7.58734 15.4081 7.40552 15.4487 7.20676C15.4893 7.00799 15.4689 6.80158 15.39 6.61465C15.3112 6.42772 15.1776 6.26902 15.0069 6.1594C14.8362 6.04978 14.6363 5.99437 14.4335 6.00045Z"
													fill="white"
												/>
											</svg>
										) : (
											<svg
												width="20"
												height="20"
												viewBox="0 0 20 20"
												fill="none"
												xmlns="http://www.w3.org/2000/svg"
											>
												<rect width="20" height="20" rx="10" fill="#FF4564" />
												<path
													d="M14.0606 14.3507C13.8866 14.5137 13.6577 14.6052 13.4193 14.6073C13.2941 14.608 13.17 14.5831 13.0548 14.5341C12.9396 14.485 12.8357 14.4129 12.7494 14.3222L10.0699 11.5287L7.44272 14.3792C7.35275 14.4736 7.24432 14.5485 7.12417 14.5991C7.00403 14.6498 6.87474 14.6752 6.74435 14.6738C6.5105 14.672 6.28553 14.584 6.11249 14.4267C5.93948 14.2543 5.83951 14.0219 5.83331 13.7778C5.82712 13.5336 5.91519 13.2964 6.07923 13.1155L8.78245 10.1842L6.05072 7.33373C5.93884 7.14888 5.89329 6.93142 5.92161 6.71721C5.94992 6.50299 6.0504 6.30483 6.20647 6.1554C6.36254 6.00597 6.56489 5.91419 6.78013 5.89522C6.99537 5.87624 7.21065 5.93119 7.39046 6.05101L10.0414 8.82074L12.6401 6.0035C12.8178 5.87977 13.0323 5.82041 13.2483 5.83524C13.4643 5.85008 13.6687 5.93822 13.8278 6.08508C13.9868 6.23194 14.091 6.42871 14.1229 6.64282C14.1549 6.85694 14.1128 7.07555 14.0036 7.26247L11.3289 10.1605L14.0891 13.0395C14.2538 13.2198 14.3427 13.4567 14.3374 13.7008C14.3321 13.945 14.233 14.1777 14.0606 14.3507Z"
													fill="white"
												/>
											</svg>
										)}
									</div>

									<div className="flex items-center justify-center bg-[#6646B108] col-span-1">
										{feature.key === 'avif' ? (
											<span className="text-sm text-[#1D1F2C]">
												{__( 'Fully unlocked', 'image-sizes' )}
											</span>
										) : (
											<svg
												width="20"
												height="20"
												viewBox="0 0 20 20"
												fill="none"
												xmlns="http://www.w3.org/2000/svg"
											>
												<rect width="20" height="20" rx="10" fill="#00BC7A" />
												<path
													d="M14.4335 6.00045C14.1718 6.00917 13.9239 6.11962 13.7424 6.30833C11.8167 8.23821 10.346 9.85005 8.55873 11.6742L6.65491 10.0657C6.55389 9.98027 6.43701 9.91563 6.31096 9.87546C6.18492 9.83529 6.05218 9.82039 5.92036 9.8316C5.78855 9.84282 5.66024 9.87993 5.54279 9.94081C5.42534 10.0017 5.32106 10.0852 5.23592 10.1864C5.15078 10.2877 5.08646 10.4047 5.04664 10.5309C5.00682 10.6571 4.99228 10.7898 5.00386 10.9216C5.01544 11.0534 5.05291 11.1816 5.11412 11.2989C5.17533 11.4162 5.25908 11.5202 5.36057 11.6051L7.97439 13.8168C8.1667 13.9788 8.41273 14.0628 8.66397 14.0523C8.9152 14.0419 9.15339 13.9377 9.33156 13.7602C11.497 11.5901 13.0385 9.85683 15.1624 7.72834C15.3083 7.58734 15.4081 7.40552 15.4487 7.20676C15.4893 7.00799 15.4689 6.80158 15.39 6.61465C15.3112 6.42772 15.1776 6.26902 15.0069 6.1594C14.8362 6.04978 14.6363 5.99437 14.4335 6.00045Z"
													fill="white"
												/>
											</svg>
										)}
									</div>
								</div>
							))}
						</React.Fragment>
					))}
				</div>
			</div>
		</div>
	);
};

export default Comparison;
