import { Check } from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';

interface PricingCardProps {
	plan: {
		name: string;
		price: string;
		originalPrice?: string;
		discount?: string;
		totalPrice?: string;
		featured?: boolean;
		sites: string;
		support: string;
		cdnStorage: string;
		url: string;
	};
	duration: string;
}

/** Red "New" ribbon shown against the CDN Space row. */
const NewBadge = () => (
	<svg
		width="30"
		height="14"
		viewBox="0 0 30 14"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		className="shrink-0"
	>
		<path
			d="M6.65704 13.9643H26.611C27.3233 13.9643 28.0065 13.6813 28.5102 13.1776C29.0139 12.6739 29.2969 11.9907 29.2969 11.2784V2.68594C29.2969 1.97358 29.0139 1.2904 28.5102 0.786693C28.0065 0.282982 27.3233 0 26.611 0H6.65704C6.28655 3.85734e-06 5.92006 0.0766568 5.58063 0.225139C5.24119 0.373622 4.93614 0.590723 4.68466 0.862793L0.713528 5.159C0.254784 5.65531 0 6.30631 0 6.98215C0 7.65799 0.254784 8.30899 0.713528 8.80529L4.68466 13.1015C4.93614 13.3736 5.24119 13.5907 5.58063 13.7392C5.92006 13.8876 6.28655 13.9643 6.65704 13.9643Z"
			fill="#F91F3E"
		/>
		<path
			d="M10.6826 4.96815L12.6654 7.96857V4.94038C12.6654 4.74358 12.7077 4.59593 12.7923 4.49741C12.8345 4.44862 12.8871 4.41 12.9464 4.38445C13.0056 4.3589 13.0699 4.34708 13.1343 4.34987C13.2001 4.34675 13.2657 4.35839 13.3264 4.38391C13.387 4.40944 13.4412 4.44822 13.485 4.49741C13.5707 4.59597 13.6137 4.74362 13.6139 4.94038V8.94433C13.6139 9.3912 13.4286 9.6146 13.0582 9.61452C12.9732 9.61574 12.8886 9.60226 12.8082 9.57468C12.7312 9.54631 12.6604 9.50322 12.5998 9.44782C12.5322 9.3871 12.4716 9.31898 12.4193 9.24474C12.3636 9.16716 12.3081 9.08782 12.2526 9.00673L10.3177 6.04118V9.02431C10.3177 9.2188 10.2726 9.36583 10.1825 9.4654C10.1389 9.51402 10.0853 9.55254 10.0253 9.5783C9.96533 9.60405 9.90046 9.61641 9.83521 9.61452C9.76916 9.6171 9.70338 9.60488 9.64267 9.57876C9.58195 9.55265 9.52784 9.5133 9.48429 9.46358C9.39624 9.36292 9.3523 9.21644 9.35245 9.02413V5.09659C9.3472 4.96346 9.36602 4.83047 9.408 4.70401C9.45299 4.59845 9.5294 4.50931 9.62685 4.44872C9.72458 4.38391 9.83929 4.34944 9.95656 4.34964C10.033 4.34661 10.1093 4.35991 10.1803 4.38866C10.2512 4.4174 10.3152 4.46093 10.3681 4.51634C10.4255 4.57712 10.4767 4.64355 10.5209 4.71462C10.573 4.79571 10.6269 4.88022 10.6826 4.96815ZM17.99 5.23206H15.6668V6.48228H17.806C17.9635 6.48228 18.081 6.51757 18.1585 6.58815C18.197 6.62347 18.2274 6.66676 18.2475 6.71501C18.2676 6.76326 18.2769 6.8153 18.2749 6.86753C18.2768 6.92012 18.2676 6.97251 18.2478 7.02129C18.2281 7.07007 18.1983 7.11413 18.1603 7.1506C18.0838 7.22372 17.9657 7.26024 17.806 7.26017H15.6668V8.70819H18.0699C18.2319 8.70819 18.354 8.74585 18.4363 8.82116C18.4773 8.85937 18.5095 8.906 18.5308 8.95785C18.552 9.0097 18.5617 9.06553 18.5593 9.12151C18.5613 9.17639 18.5513 9.23104 18.53 9.28168C18.5088 9.33232 18.4768 9.37775 18.4363 9.41483C18.354 9.4903 18.2319 9.52794 18.0699 9.52774H15.2674C15.0427 9.52774 14.8813 9.47796 14.783 9.37839C14.6847 9.27882 14.6355 9.11788 14.6354 8.89558V5.06882C14.6309 4.94437 14.6535 4.82043 14.7015 4.70554C14.7429 4.61429 14.8164 4.54144 14.908 4.50087C15.0219 4.45413 15.1444 4.43216 15.2674 4.43642H17.99C18.1543 4.43642 18.2764 4.47284 18.3564 4.54569C18.396 4.58188 18.4272 4.62625 18.4478 4.67572C18.4685 4.72519 18.4782 4.77858 18.4761 4.83216C18.4784 4.88627 18.4688 4.94023 18.4481 4.99029C18.4275 5.04035 18.3962 5.08532 18.3564 5.12208C18.2765 5.19552 18.1543 5.23218 17.99 5.23206ZM22.8772 8.72548L22.054 5.67274L21.2205 8.72548C21.1558 8.9572 21.1043 9.12337 21.0662 9.22399C21.024 9.32994 20.9552 9.42321 20.8664 9.49476C20.7588 9.57887 20.6244 9.62138 20.488 9.61452C20.3788 9.6189 20.2704 9.59441 20.1737 9.54351C20.0905 9.49513 20.0216 9.42559 19.974 9.34194C19.9187 9.24501 19.8767 9.14109 19.849 9.03298C19.8165 8.91372 19.7875 8.80312 19.762 8.70116L18.9147 5.27378C18.8719 5.12555 18.8463 4.97288 18.8385 4.8188C18.8373 4.75664 18.849 4.69491 18.8729 4.63752C18.8968 4.58013 18.9324 4.52835 18.9774 4.48546C19.0227 4.44091 19.0764 4.40595 19.1355 4.38267C19.1946 4.35938 19.2578 4.34825 19.3212 4.34993C19.5087 4.34993 19.6349 4.41013 19.6997 4.53052C19.7797 4.69685 19.8369 4.87326 19.8696 5.05493L20.5364 8.02763L21.2831 5.24601C21.3236 5.08118 21.3735 4.91881 21.4325 4.75968C21.4778 4.64645 21.552 4.54705 21.6476 4.47134C21.7648 4.38476 21.9085 4.34176 22.0539 4.34976C22.1998 4.34077 22.3439 4.38594 22.4586 4.47661C22.5471 4.55051 22.6158 4.64538 22.6583 4.75259C22.6952 4.8522 22.745 5.01661 22.8077 5.24583L23.5612 8.02745L24.2279 5.05476C24.2522 4.9317 24.2829 4.81001 24.32 4.69019C24.3499 4.60051 24.4011 4.51946 24.4693 4.45405C24.5111 4.41717 24.5597 4.38896 24.6125 4.37105C24.6652 4.35315 24.721 4.34591 24.7766 4.34976C24.8396 4.3484 24.9023 4.3595 24.961 4.38244C25.0197 4.40538 25.0734 4.43969 25.1188 4.48341C25.1645 4.52623 25.2007 4.57822 25.2249 4.63599C25.2492 4.69376 25.2609 4.756 25.2594 4.81862C25.2502 4.97255 25.2247 5.12506 25.1833 5.2736L24.3359 8.70099C24.278 8.93274 24.2299 9.10241 24.1918 9.20999C24.1523 9.31944 24.0853 9.4169 23.9973 9.49306C23.8877 9.58006 23.7496 9.6233 23.61 9.6144C23.4738 9.62126 23.3396 9.57945 23.2315 9.49646C23.1432 9.42688 23.0749 9.33518 23.0336 9.23067C22.9965 9.13251 22.9443 8.96411 22.8772 8.72548Z"
			fill="white"
		/>
	</svg>
);

const PricingCard = ({ plan, duration }: PricingCardProps) => {
	return (
		<div
			key={plan.name}
			className={`rounded-xl relative 2xl:p-[30px] lg:p-5 lg:pt-[36px] overflow-hidden ${plan.featured ? 'border-2 border-thumbpress-primary shadow-lg shadow-thumbpress-primary/10' : 'border border-thumbpress-border'} bg-white`}
		>
			{plan.featured && (
				<div className="absolute top-0 right-0 bg-thumbpress-primary pointer-events-none py-2 px-4 text-white text-sm font-medium rounded-bl-lg">
					{__( 'Best Value', 'image-sizes' )}
				</div>
			)}

			<div className='pb-5 border-b border-thumbpress-primary/10 mb-5'>
				<h3 className="text-2xl font-medium text-thumbpress-title mb-8">
					{plan.name}
				</h3>
				<div className="flex items-baseline gap-1.5 mb-2">
					{plan.originalPrice && (
						<span className="text-xl font-medium text-thumbpress-body line-through">
							{plan.originalPrice}
						</span>
					)}
					<span className="text-4xl font-semibold text-thumbpress-primary">
						{plan.price}
					</span>
					{duration === 'Yearly' && (
						<span className="text-base text-thumbpress-body">{__( '/ Month', 'image-sizes' )}</span>
					)}

					{duration === 'Lifetime' && (
						<span className="text-base text-thumbpress-body">{__( '/ Lifetime', 'image-sizes' )}</span>
					)}
					{plan.discount && (
						<span className="ml-auto self-center bg-[#F97316] text-white text-xs font-semibold px-2.5 py-1 rounded">
							{plan.discount}
						</span>
					)}
				</div>

				{duration === 'Yearly' && (
					<span className='text-[#4A4C56] italic text-sm'>{sprintf( /* translators: %s is the total yearly price. */ __( 'Billed annually. You pay %s today', 'image-sizes' ), plan.totalPrice ?? '' )}</span>
				)}
			</div>

			<ul className="space-y-4 mb-8">
				<li className="flex items-center gap-2 text-base text-thumbpress-body">
					<Check className="w-4 h-4 text-thumbpress-primary flex-shrink-0" />
					{plan.sites}
				</li>
				<li className="flex items-center gap-2 text-base text-thumbpress-body">
					<Check className="w-4 h-4 text-thumbpress-primary flex-shrink-0" />
					<span>
						<span className="font-bold text-thumbpress-primary">{plan.cdnStorage} </span>
						{__( 'CDN Space', 'image-sizes' )}
					</span>
					<NewBadge />
				</li>
				<li className="flex items-center gap-2 text-base text-thumbpress-body">
					<Check className="w-4 h-4 text-thumbpress-primary flex-shrink-0" />
					{plan.support}
				</li>
				<li className="flex items-center gap-2 text-base text-thumbpress-body">
					<Check className="w-4 h-4 text-thumbpress-primary flex-shrink-0" />
					{__( 'All Pro Features Included', 'image-sizes' )}
				</li>
			</ul>
			<a
				href={plan.url}
				target="_blank"
				rel="noopener noreferrer"
				className={`block text-center py-3 rounded-lg text-sm font-medium no-underline transition-colors duration-300 border-2 border-thumbpress-primary ${plan.featured ? 'bg-thumbpress-primary !text-white' : ' !text-thumbpress-primary hover:bg-thumbpress-primary hover:!text-white'}`}
			>
				{__( 'Purchase', 'image-sizes' )}
			</a>
		</div>
	);
};

export default PricingCard;
