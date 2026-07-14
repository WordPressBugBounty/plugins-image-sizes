import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { cn } from '../../lib/utils';
import PricingCard from './components/PricingCard';

const Pricing = () => {
	const [billingCycle, setBillingCycle] = useState<'yearly' | 'lifetime'>(
		'yearly',
	);

	const pricingPlansYearly = [
		{
			name: __( 'Personal', 'image-sizes' ),
			price: '$3.50',
			originalPrice: '$5',
			discount: '30% OFF',
			totalPrice: '$41',
			featured: false,
			features: ['1 Site', '1 Year Support', 'All Features Included'],
			url: 'https://my.pluggable.io/order/?edd_action=add_to_cart&download_id=348&edd_options%5Bprice_id%5D=1&discount=FIFA30'
		},
		{
			name: __( 'Professional', 'image-sizes' ),
			price: '$7',
			originalPrice: '$10',
			discount: '30% OFF',
			totalPrice: '$83',
			featured: true,
			features: ['5 Sites', '1 Year Support', 'All Features Included'],
			url: 'https://my.pluggable.io/order/?edd_action=add_to_cart&download_id=348&edd_options%5Bprice_id%5D=2&discount=FIFA30'
		},
		{
			name: __( 'Agency', 'image-sizes' ),
			price: '$17.50',
			originalPrice: '$25',
			discount: '30% OFF',
			totalPrice: '$209',
			featured: false,
			features: ['Unlimited Sites', '1 Year Support', 'All Features Included'],
			url: 'https://my.pluggable.io/order/?edd_action=add_to_cart&download_id=348&edd_options%5Bprice_id%5D=4&discount=FIFA30'
		},
	];

	const pricingPlansLifetime = [
		{
			name: __( 'Personal', 'image-sizes' ),
			price: '$62',
			originalPrice: '$119',
			discount: '48% OFF',
			featured: false,
			features: ['1 Site', 'Lifetime Support', 'All Features Included'],
			url: 'https://my.pluggable.io/order/?edd_action=add_to_cart&download_id=348&edd_options%5Bprice_id%5D=5&discount=FIFA48'
		},
		{
			name: __( 'Professional', 'image-sizes' ),
			price: '$124',
			originalPrice: '$239',
			discount: '48% OFF',
			featured: false,
			features: ['5 Sites', 'Lifetime Support', 'All Features Included'],
			url: 'https://my.pluggable.io/order/?edd_action=add_to_cart&download_id=348&edd_options%5Bprice_id%5D=6&discount=FIFA48'
		},
		{
			name: __( 'Agency', 'image-sizes' ),
			price: '$311',
			originalPrice: '$599',
			discount: '48% OFF',
			featured: true,
			features: ['Unlimited Sites', 'Lifetime Support', 'All Features Included'],
			url: 'https://my.pluggable.io/order/?edd_action=add_to_cart&download_id=348&edd_options%5Bprice_id%5D=8&discount=FIFA48'
		},
	];

	return (
		<div className="px-[80px] py-16" id='thumbpress-pro-pricing'>
			<div className="text-center mb-10">
				<h2 className="2xl:text-[32px] lg:text-[28px] font-semibold text-thumbpress-title mb-2 max-w-[600px] mx-auto leading-[1.4]">
					ThumbPress Pro Pricing
				</h2>
				<p className="text-base text-thumbpress-body max-w-[484px] mx-auto">
					Every plan includes the full set of Pro features. Just pick how many
					sites you need to cover.
				</p>
			</div>

			{/* Billing Toggle */}
			<div className="flex items-center justify-center gap-4 mb-10">
				<button
					onClick={() => setBillingCycle('yearly')}
					className={`text-base text-thumbpress-title font-medium cursor-pointer`}
				>
					Yearly
				</button>

				<button
					className={cn(
						'w-12 h-6 rounded-full border border-thumbpress-primary flex items-center px-[3px] duration-300',
						billingCycle === 'yearly' ? 'justify-start' : 'justify-end',
					)}
					onClick={() =>
						billingCycle === 'yearly'
							? setBillingCycle('lifetime')
							: setBillingCycle('yearly')
					}
				>
					<div className="w-[18px] h-[18px] bg-thumbpress-primary rounded-full" />
				</button>

				<button
					onClick={() => setBillingCycle('lifetime')}
					className={`text-base text-thumbpress-title font-medium cursor-pointer`}
				>
					Lifetime
				</button>
			</div>

			{/* Pricing Cards */}
			<div className="grid grid-cols-3 2xl:gap-6 lg:gap-4 max-w-[1200px] mx-auto">
				{billingCycle === 'yearly' &&
					pricingPlansYearly.map((plan, index) => (
						<PricingCard key={index} duration="Yearly" plan={plan} />
					))}

				{billingCycle === 'lifetime' &&
					pricingPlansLifetime.map((plan, index) => (
						<PricingCard key={index} duration="Lifetime" plan={plan} />
					))}
			</div>

			<div className="flex justify-between items-center max-w-[680px] mx-auto mt-16">
				<div className="flex items-center gap-8">
					<svg
						width="37"
						height="39"
						viewBox="0 0 37 39"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<path
							d="M26.9185 19.6356C30.5721 12.2156 25.1087 3.49709 16.7801 3.49709C10.558 3.49709 5.4973 8.53089 5.4973 14.7198C5.4973 22.0218 12.4569 27.4308 19.6156 25.58C19.6156 28.1222 19.6114 28.0674 19.6411 28.5564C19.4334 28.645 19.2342 28.7462 19.0392 28.8558C17.6787 29.6399 15.8816 29.6399 14.521 28.8558C12.8299 27.8819 11.6473 28.1812 10.8335 28.0126C8.12515 27.4898 8.03191 25.7234 6.56539 24.4291C5.70075 23.6702 4.74285 23.4552 3.94602 22.0892C2.85249 20.2047 3.86125 19.1887 2.56851 16.9711C1.75473 15.5588 1.77168 13.8556 2.56851 12.477C3.85701 10.2636 2.86097 9.22651 3.95026 7.35465C5.06497 5.4322 6.41705 5.86643 7.71826 3.60671C8.53204 2.21124 10.024 1.35963 11.6304 1.35963C12.6476 1.35963 13.6479 1.09403 14.5252 0.588119C15.8816 -0.19604 17.6829 -0.19604 19.0392 0.588119C21.2602 1.87397 22.2944 0.874801 24.189 1.96672C26.092 3.05864 25.719 4.42459 27.957 5.71466C29.4023 6.54098 30.2161 8.05449 30.2161 9.60594C30.2161 12.1397 31.5978 12.5697 31.5978 14.7283C31.5978 16.8868 30.339 17.2114 30.2288 19.585C28.3215 18.7292 28.9827 18.7207 26.9185 19.6482V19.6356ZM24.8883 20.5462L19.9929 22.7427C19.5097 22.9619 19.6199 23.4341 19.6199 24.2647C13.2198 26.1534 6.77308 21.3599 6.77308 14.7198C6.77308 9.23073 11.2616 4.76187 16.7843 4.76187C24.9688 4.76187 29.6439 14.0284 24.8925 20.5462H24.8883ZM17.4159 7.67084V6.5452C17.4159 5.71045 16.1443 5.71045 16.1443 6.5452V7.67084C11.8889 8.41284 12.3212 14.8042 16.5428 14.8042H17.0217C18.1873 14.8042 19.1325 15.7485 19.1325 16.9079C19.1325 18.198 18.0813 19.2477 16.7801 19.2477C15.4789 19.2477 14.4235 18.198 14.4235 16.9079C14.4235 16.0731 13.152 16.0731 13.152 16.9079C13.152 18.6954 14.4532 20.1584 16.1443 20.4535V21.6803C16.1443 22.5151 17.4159 22.5151 17.4159 21.6803V20.4493C19.124 20.1499 20.404 18.6744 20.404 16.9037C20.404 15.0445 18.8866 13.5352 17.0217 13.5352C16.3944 13.5352 15.712 13.5815 15.0508 12.9239C13.7199 11.6001 14.5634 8.87659 16.7843 8.87659C18.0177 8.87659 19.1367 9.84204 19.1367 11.2797C19.1367 12.1144 20.4082 12.1144 20.4082 11.2797C20.4082 9.36142 19.0519 7.96174 17.4201 7.67084H17.4159ZM3.49674 30.6855L4.85305 33.8348L9.70186 29.0118C8.09124 28.451 7.26474 27.494 6.45519 26.2082C5.77704 25.1669 4.96325 24.876 4.5055 24.526L0 29.0075C3.48826 30.4915 3.36111 30.3735 3.49674 30.6855ZM36.4 23.725V27.8397C36.4 31.6003 34.3655 35.1122 31.1019 36.9883L28.6478 38.35L26.2022 36.9925C23.2311 35.2808 21.2687 32.2201 20.9465 28.8178C20.8745 28.0505 20.8957 27.3296 20.8957 23.7208L28.6478 20.2427L36.4 23.7208V23.725ZM27.745 30.6475C26.0624 28.6703 25.6004 28.1264 25.5495 28.0758C25.0409 27.5699 24.2525 28.1728 24.5111 28.7419C24.5704 28.8979 24.3797 28.6492 27.1856 31.9418C27.4102 32.2074 27.8129 32.2411 28.0799 32.0177L34.2596 26.8574C34.9038 26.3178 34.0858 25.3524 33.4415 25.8878L27.745 30.6433V30.6475Z"
							fill="#40189D"
						/>
					</svg>

					<span className="text-thumbpress-title text-base font-medium max-w-[165px]">
						30-day money back guarantee
					</span>
				</div>

				<div className="w-[1px] h-[52px] bg-thumbpress-primary/10"></div>

				<div className="flex items-center gap-8">
					<svg
						width="36"
						height="36"
						viewBox="0 0 36 36"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<path
							d="M1.68652 19.3204H14.9917V8.20111C14.9917 7.59973 14.7462 7.05288 14.3501 6.65675C13.954 6.26062 13.4071 6.01518 12.8057 6.01518H6.25365C4.99776 6.01518 3.85673 6.52899 3.03001 7.35715C2.20185 8.18531 1.68803 9.32637 1.68803 10.5808V19.3202L1.68652 19.3204ZM20.4286 11.6976V3.87385C20.4286 2.80745 20.8635 1.83717 21.5654 1.13676C22.2658 0.436364 23.236 0 24.3024 0H30.1469C31.7572 0 33.2212 0.658808 34.282 1.71804C35.3426 2.77871 36 4.24271 36 5.85314V11.6975C36 12.7654 35.5651 13.7342 34.8632 14.4346C34.1614 15.1365 33.1925 15.5714 32.1261 15.5714H24.3024C23.2345 15.5714 22.2657 15.1365 21.5653 14.4346C20.8634 13.7342 20.4286 12.764 20.4286 11.6976ZM16.6797 19.3204H27.7989C28.8668 19.3204 29.8356 19.7553 30.536 20.4571C31.2364 21.1575 31.6728 22.1278 31.6728 23.1942V29.7463C31.6728 31.4672 30.9695 33.0317 29.8356 34.1628C28.7018 35.2967 27.1387 36 25.4191 36H6.25373C4.53281 36 2.96835 35.2967 1.83717 34.1628C0.703305 33.029 0 31.466 0 29.7463V10.581C0 8.86006 0.703305 7.29704 1.83717 6.16443C2.97103 5.03056 4.53406 4.32726 6.25373 4.32726H12.8058C13.8736 4.32726 14.8425 4.76215 15.5429 5.46403C16.2448 6.1659 16.6797 7.13471 16.6797 8.20111V19.3204ZM27.7989 21.0083H16.6797V34.3134H25.4191C26.6735 34.3134 27.816 33.7982 28.6427 32.9714C29.4709 32.1433 29.9847 31.0022 29.9847 29.7478V23.1957C29.9847 22.5943 29.7393 22.0475 29.3431 21.6514C28.947 21.2552 28.4001 21.0098 27.7988 21.0098L27.7989 21.0083Z"
							fill="#40189D"
						/>
					</svg>

					<span className="text-thumbpress-title text-base font-medium max-w-[249px]">
						Access future features for the subscription period
					</span>
				</div>
			</div>
		</div>
	);
};

export default Pricing;
