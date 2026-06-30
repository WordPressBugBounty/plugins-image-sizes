import React, { useState } from 'react';

export interface ProAlertProps {
	title: string;
	description: string;
	buttonText: string;
	open?: boolean;
	onClose?: () => void;
}

const ProAlert: React.FC<ProAlertProps> = ({
	title,
	description,
	buttonText,
	open: controlledOpen,
	onClose,
}) => {
	const [internalOpen, setInternalOpen] = useState(false);

	const isControlled = controlledOpen !== undefined;
	const isOpen = isControlled ? controlledOpen : internalOpen;

	const openModal = () => setInternalOpen(true);
	const closeModal = () => {
		setInternalOpen(false);
		onClose?.();
	};

	// Pro plugin active but license not activated → prompt to activate, not upgrade.
	const proInstalled = !!(window as any).THUMBPRESS?.pro_installed;
	const proActive = !!(window as any).THUMBPRESS?.pro_active;
	const ctaLabel = proInstalled && ! proActive ? 'Activate License' : buttonText;

	return (
		<>
			{!isControlled && (
			<button
				type="button"
				onClick={openModal}
				className="inline-flex items-center gap-1 px-2 py-1 rounded text-[8px] bg-thumbpress-pro-yellow text-thumbpress-title cursor-pointer"
			>
				<svg
					width="10"
					height="10"
					viewBox="0 0 10 10"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				>
					<g clip-path="url(#clip0_902_11673)">
						<path
							d="M8.35699 8.41992H1.43122C1.15801 8.41992 0.936523 8.6414 0.936523 8.91462C0.936523 9.18783 1.15801 9.40931 1.43122 9.40931H8.35699C8.6302 9.40931 8.85169 9.18783 8.85169 8.91462C8.85169 8.6414 8.6302 8.41992 8.35699 8.41992Z"
							fill="#1C1C1C"
						/>
						<path
							d="M9.09662 2.05451C8.92532 1.95666 8.71226 1.96995 8.55444 2.08831L6.95923 3.28472L5.27998 1.18566C5.1861 1.06831 5.04397 1 4.89368 1C4.74341 1 4.60127 1.06831 4.50739 1.18566L2.82814 3.28472L1.23293 2.08831C1.07511 1.96995 0.862048 1.95667 0.69075 2.05451C0.519448 2.15236 0.422658 2.34263 0.444443 2.5387L0.939129 6.99095C0.966966 7.24149 1.17873 7.43102 1.4308 7.43102H8.35657C8.60864 7.43102 8.8204 7.24149 8.84824 6.99095L9.34293 2.5387C9.36471 2.34263 9.26792 2.15236 9.09662 2.05451ZM7.9138 6.44163H1.87357L1.55406 3.5659L2.61808 4.36392C2.70697 4.43059 2.81115 4.46289 2.91458 4.46288C3.05989 4.46287 3.20368 4.39908 3.30119 4.2772L4.89368 2.28659L6.48618 4.2772C6.65309 4.48584 6.95552 4.52424 7.16929 4.36392L8.23332 3.5659L7.9138 6.44163Z"
							fill="#1C1C1C"
						/>
					</g>
					<defs>
						<clipPath id="clip0_902_11673">
							<rect width="10" height="10" fill="white" />
						</clipPath>
					</defs>
				</svg>
				Pro
			</button>
			)}

			{isOpen && (
				<div className="fixed inset-0 z-50 flex items-center justify-center">
					<div className="absolute inset-0 bg-black/60" onClick={closeModal} />
					<div className="relative bg-[#1C1B1F] rounded-xl shadow-2xl w-full max-w-[500px] p-[50px] text-center">
						<button
							type="button"
							onClick={closeModal}
							className="absolute -top-5 -right-5 w-6 h-6 bg-[#1C1B1F] rounded-full shadow-md flex items-center justify-center text-white hover:bg-thumbpress-red duration-300"
						>
							<svg
								width="10"
								height="10"
								viewBox="0 0 10 10"
								fill="none"
								xmlns="http://www.w3.org/2000/svg"
							>
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M0.260418 0.260418C0.607642 -0.086806 1.17015 -0.086806 1.51731 0.260418L5 3.7431L8.48269 0.260418C8.82991 -0.086806 9.39241 -0.086806 9.73958 0.260418C10.0868 0.607642 10.0868 1.17015 9.73958 1.51731L6.2569 5L9.73958 8.48269C10.0868 8.82991 10.0868 9.39241 9.73958 9.73958C9.39236 10.0868 8.82985 10.0868 8.48269 9.73958L5 6.2569L1.51731 9.73958C1.17009 10.0868 0.607587 10.0868 0.260418 9.73958C-0.0867505 9.39236 -0.086806 8.82985 0.260418 8.48269L3.7431 5L0.260418 1.51731C-0.086806 1.17009 -0.086806 0.607587 0.260418 0.260418Z"
									fill="currentColor"
								/>
							</svg>
						</button>

						<div className="flex justify-center mb-5">
							<div className="w-14 h-14 rounded-xl bg-[#FFFFFF33] flex items-center justify-center">
								<svg
									width="18"
									height="17"
									viewBox="0 0 18 17"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									<path
										d="M15.8307 14.8408H1.97914C1.43271 14.8408 0.989746 15.2838 0.989746 15.8302C0.989746 16.3766 1.43271 16.8196 1.97914 16.8196H15.8307C16.3771 16.8196 16.8201 16.3766 16.8201 15.8302C16.8201 15.2838 16.3771 14.8408 15.8307 14.8408Z"
										fill="#FFCF5C"
									/>
									<path
										d="M17.3104 2.10902C16.9678 1.91332 16.5417 1.93989 16.2261 2.17662L13.0357 4.56945L9.67714 0.371324C9.48938 0.136622 9.20512 0 8.90456 0C8.604 0 8.31973 0.136622 8.13197 0.371324L4.77346 4.56945L1.58305 2.17662C1.26741 1.93989 0.841284 1.91333 0.498687 2.10902C0.156084 2.30471 -0.0374975 2.68525 0.00607412 3.07739L0.995446 11.9819C1.05112 12.483 1.47465 12.862 1.97879 12.862H15.8303C16.3345 12.862 16.758 12.483 16.8137 11.9819L17.803 3.07739C17.8466 2.68525 17.653 2.30471 17.3104 2.10902ZM14.9448 10.8833H2.86434L2.2253 5.13179L4.35335 6.72784C4.53113 6.86117 4.73948 6.92579 4.94635 6.92577C5.23696 6.92574 5.52455 6.79817 5.71957 6.5544L8.90456 2.57319L12.0895 6.5544C12.4234 6.97168 13.0282 7.04849 13.4558 6.72784L15.5838 5.13179L14.9448 10.8833Z"
										fill="#FFCF5C"
									/>
								</svg>
							</div>
						</div>

						<h2 className="text-2xl font-bold text-white mb-3">{title}</h2>

						<p className="text-[#F2EFEF] text-base mb-6">{description}</p>

						<a
							href="#/pro"
							className="inline-flex items-center gap-2 bg-thumbpress-pro-yellow hover:bg-yellow-500 !text-thumbpress-title text-base font-medium px-6 py-3 rounded-lg transition-colors no-underline duration-300"
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
									d="M17.3104 2.10902C16.9678 1.91332 16.5417 1.93989 16.2261 2.17662L13.0357 4.56945L9.67714 0.371324C9.48938 0.136622 9.20512 0 8.90456 0C8.604 0 8.31973 0.136622 8.13197 0.371324L4.77346 4.56945L1.58305 2.17662C1.26741 1.93989 0.841284 1.91333 0.498687 2.10902C0.156084 2.30471 -0.0374975 2.68525 0.00607412 3.07739L0.995446 11.9819C1.05112 12.483 1.47465 12.862 1.97879 12.862H15.8303C16.3345 12.862 16.758 12.483 16.8137 11.9819L17.803 3.07739C17.8466 2.68525 17.653 2.30471 17.3104 2.10902Z"
									fill="#1C1C1C"
								/>
							</svg>
							{ctaLabel}
						</a>
					</div>
				</div>
			)}
		</>
	);
};

export default ProAlert;
