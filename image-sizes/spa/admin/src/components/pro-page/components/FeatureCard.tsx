import React from 'react';

interface FeatureCardProps {
	icon: React.ReactNode;
	subtitle: string;
	newBadge?: boolean;
	title: string;
	description: string;
	link: React.ReactNode;
}

const FeatureCard = ({
	icon,
	subtitle,
	newBadge,
	title,
	description,
	link,
}: FeatureCardProps) => {
	return (
		<div
			className="bg-white rounded-xl 2xl:p-6 lg:p-5 border border-[#40189D1A]"
			style={{
				boxShadow: '0px 8px 72px 0px #F2EDFF',
			}}
		>
			<div className="flex justify-between items-start mb-4">
				{icon}

				{subtitle && (
					<span
						className={`text-sm py-1 px-3 rounded-full mb-2 inline-block ${
							subtitle === 'In-plugin editing'
								? 'text-[#CB5026] bg-[#FDF1EC]'
								: 'text-[#FF3668] bg-[#F436361A]'
						}`}
					>
						{subtitle}
					</span>
				)}

				{newBadge && (
					<span className="text-xs font-semibold text-white bg-red-500 px-2.5 py-1 rounded-full">
						New
					</span>
				)}
			</div>

			<h3 className="2xl:text-xl lg:text-base font-medium text-thumbpress-title mb-4">
				{title}
			</h3>

			<p className="text-sm text-thumbpress-body leading-relaxed">
				{description}
			</p>

			{link}
		</div>
	);
};

export default FeatureCard;
