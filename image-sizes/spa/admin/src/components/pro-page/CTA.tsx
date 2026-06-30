import React from 'react';
import { __ } from '@wordpress/i18n';

const CTA = () => {
	const assetsUrl = window.THUMBPRESS?.assets_url || '';
	const proImgBase = `${assetsUrl}admin/img/pro/`;

	return (
		<div
			className="relative rounded-2xl 2xl:mx-[80px] lg:mx-[40px] mb-6 overflow-hidden"
			style={{
				minHeight: '220px',
				backgroundImage: `url(${proImgBase}bottom-bg.png)`,
				backgroundSize: 'cover',
				backgroundPosition: 'center',
				backgroundRepeat: 'no-repeat',
			}}
		>
			<div className="relative z-10 flex flex-col items-center justify-center text-center py-14 px-6">
				<h2 className="2xl:text-5xl lg:text-[38px] lg:leading-[42px] font-bold text-white max-w-[580px]">
					{__( 'Ready to take control of your Images?', 'image-sizes' )}
				</h2>
				<p className="text-lg text-[#E3E3E3] mt-5 mb-[30px] max-w-[580px]">
					{__( 'Join 30,000+ users who manage their media smarter with ThumbPress Pro', 'image-sizes' )}
				</p>
				<button
					onClick={() => (
						document.getElementById('thumbpress-pro-pricing')?.scrollIntoView({ behavior: 'smooth' })
					)}
					rel="noopener noreferrer"
					className="inline-flex items-center gap-2 bg-[#E58D12] hover:bg-[#ce7800] !text-white text-sm font-medium px-6 py-3 rounded-lg transition-colors no-underline duration-300"
				>
					<svg
						width="18"
						height="17"
						viewBox="0 0 18 17"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<path
							d="M15.8312 14.8408H1.97963C1.4332 14.8408 0.990234 15.2838 0.990234 15.8302C0.990234 16.3766 1.4332 16.8196 1.97963 16.8196H15.8312C16.3776 16.8196 16.8206 16.3766 16.8206 15.8302C16.8206 15.2838 16.3776 14.8408 15.8312 14.8408Z"
							fill="white"
						/>
						<path
							d="M17.3104 2.10902C16.9678 1.91332 16.5417 1.93989 16.2261 2.17662L13.0357 4.56945L9.67714 0.371324C9.48938 0.136622 9.20512 0 8.90456 0C8.604 0 8.31973 0.136622 8.13197 0.371324L4.77346 4.56945L1.58305 2.17662C1.26741 1.93989 0.841284 1.91333 0.498687 2.10902C0.156084 2.30471 -0.0374975 2.68525 0.00607412 3.07739L0.995446 11.9819C1.05112 12.483 1.47465 12.862 1.97879 12.862H15.8303C16.3345 12.862 16.758 12.483 16.8137 11.9819L17.803 3.07739C17.8466 2.68525 17.653 2.30471 17.3104 2.10902ZM14.9448 10.8833H2.86434L2.2253 5.13179L4.35335 6.72784C4.53113 6.86117 4.73948 6.92579 4.94635 6.92577C5.23696 6.92574 5.52455 6.79817 5.71957 6.5544L8.90456 2.57319L12.0895 6.5544C12.4234 6.97168 13.0282 7.04849 13.4558 6.72784L15.5838 5.13179L14.9448 10.8833Z"
							fill="white"
						/>
					</svg>
					Upgrade to Pro
				</button>
			</div>
		</div>
	);
};

export default CTA;
