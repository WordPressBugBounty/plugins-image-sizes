import React from 'react';
import { __ } from '@wordpress/i18n';

const LockedFeatureCard = ( { icon, title, description, value, actionLabel = __( 'Upgrade to View', 'image-sizes' ), needsScan = false, scanning = false, onScan }: {
    icon: React.ReactNode;
    title: string;
    description: string;
    value?: string | number;
    actionLabel?: string;
    needsScan?: boolean;
    scanning?: boolean;
    onScan?: () => void;
} ) => {
	const hasValue = value !== undefined && value !== null;

	// A number nobody has measured is worse than no number, so the card asks for the scan instead.
	if ( needsScan ) {
		return (
			<div className="bg-white rounded-xl border border-gray-100 2xl:p-6 lg:p-4">
				<div className="flex items-center gap-2 mb-3">
					{icon}
					<span className="2xl:text-lg lg:text-sm text-thumbpress-title">{ title }</span>
				</div>

				<button
					onClick={ onScan }
					disabled={ scanning }
					className="mb-2 px-5 py-2 rounded-lg bg-thumbpress-primary text-white text-sm font-medium hover:opacity-90 transition-opacity cursor-pointer disabled:opacity-50 disabled:cursor-default"
				>
					{ scanning ? __( 'Scanning…', 'image-sizes' ) : __( 'Run scan', 'image-sizes' ) }
				</button>

				<p className="2xl:text-base lg:text-sm text-[#777980]">
					{ scanning ? __( 'Scan in progress', 'image-sizes' ) : __( 'Not scanned yet', 'image-sizes' ) }
				</p>
			</div>
		);
	}

	return (
		<div className="bg-white rounded-xl border border-gray-100 2xl:p-6 lg:p-4">
			<div className="flex items-center gap-2 mb-3">
				{icon}
				<span className="2xl:text-lg lg:text-sm text-thumbpress-title">{ title }</span>
			</div>

			<div className={ `flex mb-1 ${ hasValue ? 'gap-5 items-center' : 'gap-3 items-start' }` }>
				<p className="2xl:text-3xl lg:text-2xl font-medium text-thumbpress-title">{ hasValue ? value : '****' }</p>
				<a href="#/pro" className='!text-[#FB8005] border-b border-[#FB8005] text-sm'>{ actionLabel }</a>
			</div>

			<p className="2xl:text-base lg:text-sm text-[#777980]">{ description }</p>
		</div>
	);
};

export default LockedFeatureCard;
