import React, { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { dismissBfcmBanner } from '../../api';

/** Black Friday / Cyber Monday strip that sits flush on top of the hero. */
const BfcmBanner = ( { onDismiss }: { onDismiss: () => void } ) => (
	<div
		role="region"
		aria-label={ __( 'Black Friday Cyber Monday offer', 'image-sizes' ) }
		className="relative flex flex-wrap md:flex-nowrap items-center gap-3.5 md:gap-6 overflow-hidden bg-[#0B0B0F] text-white px-[18px] pt-5 pb-[18px] md:px-6 md:py-5 lg:px-[30px] lg:py-[22px] leading-[1.2]"
	>
		<span
			aria-hidden="true"
			className="pointer-events-none select-none absolute -top-5 right-2 md:-top-10 md:right-[60px] lg:-top-[50px] lg:right-[90px] text-[150px] md:text-[190px] lg:text-[230px] font-black leading-none tracking-[-0.06em] text-[#FA9624] opacity-10"
		>
			70
		</span>

		<div className="relative min-w-0 basis-full md:basis-auto md:flex-1 pr-[34px] md:pr-0">
			<p className="m-0 p-0 text-[30px] sm:text-[34px] md:text-[38px] lg:text-[44px] font-black leading-none tracking-[-0.03em] text-white">
				{ __( 'Up to', 'image-sizes' ) } <em className="not-italic text-[#FA9624]">{ __( '70% OFF', 'image-sizes' ) }</em>
			</p>
			<p className="mt-2.5 mb-0 text-[13px] sm:text-sm lg:text-[15px] leading-[1.4] text-[#BDBDCA]">
				<strong className="font-bold text-white">{ __( 'BFCM 2026', 'image-sizes' ) }</strong>
				{ ' · ' }
				{ __( 'Lowest price of the year on ThumbPress Pro.', 'image-sizes' ) }
			</p>
		</div>

		<button
			type="button"
			onClick={ () => document.getElementById( 'thumbpress-pro-pricing' )?.scrollIntoView( { behavior: 'smooth' } ) }
			className="relative inline-flex w-full md:w-auto shrink-0 items-center justify-center whitespace-nowrap rounded-[9px] border-0 bg-[#FA9624] px-5 lg:px-6 py-[13px] lg:py-3.5 text-sm lg:text-[15px] font-extrabold leading-[1.2] text-[#0B0B0F] cursor-pointer hover:brightness-110 focus-visible:brightness-110 transition duration-300"
		>
			{ __( 'See BFCM pricing', 'image-sizes' ) } &darr;
		</button>

		<button
			type="button"
			onClick={ onDismiss }
			aria-label={ __( 'Dismiss offer', 'image-sizes' ) }
			className="absolute md:relative top-2 right-2 md:top-auto md:right-auto shrink-0 inline-flex h-9 w-9 items-center justify-center md:-mr-2 md:-ml-1 p-0 border-0 rounded-full bg-transparent text-[26px] leading-none text-[#BDBDCA] cursor-pointer hover:text-white hover:bg-white/10 focus-visible:text-white focus-visible:bg-white/10 transition-colors duration-300"
		>
			&times;
		</button>
	</div>
);

const Hero = () => {
	const [ bannerDismissed, setBannerDismissed ] = useState( window.THUMBPRESS?.bfcm_banner_hidden ?? false );
    const assetsUrl = window.THUMBPRESS?.assets_url || '';
    const proImgBase = `${assetsUrl}admin/img/pro/`;

    // Starting price follows the promo gate: the discounted yearly total while the
    // campaign is live, the regular Personal-plan yearly price once it ends.
    const promoActive = window.THUMBPRESS?.promo_active ?? false;
    const startingPrice = promoActive ? '$29.50' : '$59';
    // Licensed users already own Pro, so the sale does not apply to them.
    const ownsPro = !! ( window.THUMBPRESS?.pro_active || window.THUMBPRESS?.pro_installed );
    const showBanner = promoActive && ! ownsPro && ! bannerDismissed;

	return (
		<div className="rounded-2xl overflow-hidden">
			{ showBanner && <BfcmBanner onDismiss={ () => {
				setBannerDismissed( true );
				// Stored server-side (7 days); a failed save only means it shows again next load.
				dismissBfcmBanner().catch( () => {} );
			} } /> }
			<div
			className="relative overflow-hidden"
			style={{
				minHeight: '380px',
				backgroundImage: `url(${proImgBase}top-bg.png)`,
				backgroundSize: 'cover',
				backgroundPosition: 'center',
				backgroundRepeat: 'no-repeat',
			}}
    >
			<div className="relative flex items-center gap-8 px-12 py-14">
				<div className="flex-1">
					<h1 className="2xl:text-5xl lg:text-[28px] font-bold text-white leading-tight mb-4 max-w-[760px]">
						{__( 'Your Media Library is Silently Slowing Down Your Site.', 'image-sizes' )}
					</h1>
					<p className="2xl:text-base lg:text-[12px] text-white mb-8 leading-relaxed max-w-[690px]">
						{__( 'Unused images, oversized files, and missing optimization pile up with every upload. Pro finds them all and fixes them — without touching a single file manually.', 'image-sizes' )}
					</p>
					<div className="flex items-center gap-3">
						<button
							onClick={() => (
								document.getElementById('thumbpress-pro-pricing')?.scrollIntoView({ behavior: 'smooth' })
							)}
							className="inline-flex items-center gap-2.5 bg-white border border-white hover:bg-gray-100 hover:border-gray-100 !text-thumbpress-primary 2xl:text-base lg:text-[14px] font-medium px-6 py-3 rounded-lg transition-colors no-underline duration-300"
						>
							{__( 'Upgrade to Pro', 'image-sizes' )}
							<svg
								width="24"
								height="24"
								viewBox="0 0 24 24"
								fill="none"
								xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M14 16L18 12M18 12L14 8M18 12L6 12"
									stroke="#40189D"
									stroke-width="1.5"
									stroke-linecap="round"
									stroke-linejoin="round"
								/>
							</svg>
						</button>

						<span
							onClick={() => (
								document.getElementById('thumbpress-pro-pricing')?.scrollIntoView({ behavior: 'smooth' })
							)}
							className="text-white 2xl:text-base lg:text-[12px] cursor-pointer"
						>
							{sprintf(
								/* translators: %s is the starting yearly price, e.g. $59. */
								__( 'Plans start from %s/year.', 'image-sizes' ),
								startingPrice
							)}
						</span>
					</div>
				</div>
				<div className="2xl:flex-shrink-0 2xl:w-[408px] lg:w-[237px]">
					<img
						src={`${proImgBase}modules.png`}
						alt={__( 'ThumbPress modules', 'image-sizes' )}
						className="shadow-2xl cursor-pointer hover:opacity-90 transition-opacity duration-300"
						onClick={() => (
							document.getElementById('thumbpress-pro-features')?.scrollIntoView({ behavior: 'smooth' })
						)}
					/>
				</div>
			</div>
			</div>
		</div>
	);
};

export default Hero;
