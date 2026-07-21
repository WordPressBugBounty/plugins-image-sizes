import React from 'react';
import { __ } from '@wordpress/i18n';

const LockedFeatureCard = ( { icon, title, description, value, actionLabel = __( 'Upgrade to View', 'image-sizes' ) }: {
    icon: React.ReactNode;
    title: string;
    description: string;
    value?: string | number;
    actionLabel?: string;
} ) => {
	const hasValue = value !== undefined && value !== null;

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
