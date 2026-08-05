import React from 'react';
import { __, sprintf } from '@wordpress/i18n';

const Hero = () => {
    const assetsUrl = window.THUMBPRESS?.assets_url || '';
    const proImgBase = `${assetsUrl}admin/img/pro/`;

    // Starting price follows the promo gate: the discounted figure while the
    // campaign is live, the regular Personal-plan price once it ends.
    const promoActive = window.THUMBPRESS?.promo_active ?? false;
    const startingPrice = promoActive ? '$3.50' : '$5';

	return (
		<div
			className="relative rounded-2xl overflow-hidden"
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
								/* translators: %s is the starting monthly price, e.g. $5. */
								__( 'Price starts from %s/month.', 'image-sizes' ),
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
	);
};

export default Hero;
