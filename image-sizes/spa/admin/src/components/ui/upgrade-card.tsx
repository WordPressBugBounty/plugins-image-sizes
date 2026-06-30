import * as React from 'react';

interface UpgradeCardProps {
	title: string;
	description: string;
	buttonText?: string;
	buttonUrl?: string;
	backgroundImage?: string;
}

export default function UpgradeCard({
	title,
	description,
	buttonText = 'Upgrade for a Faster Website',
	buttonUrl = 'https://thumbpress.co/pricing/',
	backgroundImage = 'table-shadow.png',
}: UpgradeCardProps) {
	return (
		<div
			className="relative rounded-xl overflow-hidden h-full min-h-[700px]"
			style={{
				backgroundImage: `url(${window.THUMBPRESS?.assets_url || ''}admin/img/${backgroundImage})`,
				backgroundSize: 'cover',
				backgroundPosition: 'top left',
				backgroundRepeat: 'no-repeat',
			}}
		>
			<div className="absolute inset-0 flex items-center justify-center">
				<div className="bg-black/70 rounded-xl p-[60px] text-center w-[80%] max-w-[660px]">
					<h3 className="text-white text-2xl font-semibold mb-4">{title}</h3>
					<p className="text-[#F2EFEF] text-base mb-8">{description}</p>

					<a
						href={buttonUrl}
						target="_blank"
						rel="noopener noreferrer"
						className="inline-flex items-center gap-2 bg-thumbpress-pro-yellow hover:bg-yellow-500 !text-thumbpress-title text-base font-medium px-6 py-3 transition-colors no-underline duration-300 rounded-md"
					>
						<svg
							width="18"
							height="17"
							viewBox="0 0 18 17"
							fill="none"
							xmlns="http://www.w3.org/2000/svg"
						>
							<path
								d="M15.8307 14.8408H1.97914C1.43271 14.8408 0.989746 15.2838 0.989746 15.8302C0.989746 16.3766 1.43271 16.8196 1.97914 16.8196H15.8307C16.3771 16.8196 16.8201 16.3766 16.8201 15.8302C16.8201 15.2838 16.3771 14.8408 15.8307 14.8408Z"
								fill="#1C1C1C"
							/>
							<path
								d="M17.3104 2.10902C16.9678 1.91332 16.5417 1.93989 16.2261 2.17662L13.0357 4.56945L9.67714 0.371324C9.48938 0.136622 9.20512 0 8.90456 0C8.604 0 8.31973 0.136622 8.13197 0.371324L4.77346 4.56945L1.58305 2.17662C1.26741 1.93989 0.841284 1.91333 0.498687 2.10902C0.156084 2.30471 -0.0374975 2.68525 0.00607412 3.07739L0.995446 11.9819C1.05112 12.483 1.47465 12.862 1.97879 12.862H15.8303C16.3345 12.862 16.758 12.483 16.8137 11.9819L17.803 3.07739C17.8466 2.68525 17.653 2.30471 17.3104 2.10902ZM14.9448 10.8833H2.86434L2.2253 5.13179L4.35335 6.72784C4.53113 6.86117 4.73948 6.92579 4.94635 6.92577C5.23696 6.92574 5.52455 6.79817 5.71957 6.5544L8.90456 2.57319L12.0895 6.5544C12.4234 6.97168 13.0282 7.04849 13.4558 6.72784L15.5838 5.13179L14.9448 10.8833Z"
								fill="#1C1C1C"
							/>
						</svg>

						{buttonText}
					</a>
				</div>
			</div>
		</div>
	);
}
