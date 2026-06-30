import { Check } from 'lucide-react';

interface PricingCardProps {
	plan: {
		name: string;
		price: string;
		totalPrice?: string;
		featured?: boolean;
		features: string[];
		url: string;
	};
	duration: string;
}

const PricingCard = ({ plan, duration }: PricingCardProps) => {
	return (
		<div
			key={plan.name}
			className={`rounded-xl relative 2xl:p-[30px] lg:p-5 lg:pt-[36px] overflow-hidden ${plan.featured ? 'border-2 border-thumbpress-primary shadow-lg shadow-thumbpress-primary/10' : 'border border-thumbpress-border'} bg-white`}
		>
			{plan.featured && (
				<div className="absolute top-0 right-0 bg-thumbpress-primary pointer-events-none py-2 px-4 text-white text-sm font-medium rounded-bl-lg">
					Best Value
				</div>
			)}

			<div className='pb-5 border-b border-thumbpress-primary/10 mb-5'>
				<h3 className="text-2xl font-medium text-thumbpress-title mb-8">
					{plan.name}
				</h3>
				<div className="flex items-center gap-1.5 mb-2">
					<span className="text-4xl font-semibold text-thumbpress-primary">
						{plan.price}
					</span>
					{duration === 'Yearly' && (
						<span className="text-base text-thumbpress-body">/ Month</span>
					)}
					
					{duration === 'Lifetime' && (
						<span className="text-base text-thumbpress-body">/ Lifetime</span>
					)}
				</div>

				{duration === 'Yearly' && (
					<span className='text-[#4A4C56] italic text-sm'>Billed annually. You pay {plan.totalPrice} today</span>
				)}
			</div>

			<ul className="space-y-4 mb-8">
				{plan.features.map((feature, index) => (
					<li
						key={index}
						className="flex items-center gap-2 text-base text-thumbpress-body"
					>
						<Check className="w-4 h-4 text-thumbpress-primary flex-shrink-0" />
						{feature}
					</li>
				))}
			</ul>
			<a
				href={plan.url}
				target="_blank"
				rel="noopener noreferrer"
				className={`block text-center py-3 rounded-lg text-sm font-medium no-underline transition-colors duration-300 border-2 border-thumbpress-primary ${plan.featured ? 'bg-thumbpress-primary !text-white' : ' !text-thumbpress-primary hover:bg-thumbpress-primary hover:!text-white'}`}
			>
				Purchase
			</a>
		</div>
	);
};

export default PricingCard;
