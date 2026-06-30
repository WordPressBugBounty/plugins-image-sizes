import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { X } from 'lucide-react';

const NOTICE_DISMISSED_KEY = 'thumbpress_easycommerce_notice_dismissed';
const UTM_LINK =
	'https://easycommerce.dev/?utm_source=thumbpress&utm_medium=plugin_dashboard&utm_campaign=thumbpress_promo';

/**
 * PromotionalNotice
 *
 * Promotional notice bar shown only on the ThumbPress free version dashboard.
 * Once dismissed, it is never shown again (stored in localStorage).
 *
 * Visibility is controlled by `window.THUMBPRESS.pro_active`
 */
export default function PromotionalNotice() {
	const isProActive = window.THUMBPRESS?.pro_active;
	const [visible, setVisible] = useState(false);

	useEffect(() => {
		if (isProActive) return;
		const dismissed = localStorage.getItem(NOTICE_DISMISSED_KEY);
		if (!dismissed) setVisible(true);
	}, [isProActive]);

	const dismiss = () => {
		localStorage.setItem(NOTICE_DISMISSED_KEY, '1');
		setVisible(false);
	};

	if (!visible) return null;

	return (
		<div
			role="banner"
			aria-label={__('EasyCommerce promotional notice', 'image-sizes')}
			className="
				relative flex flex-wrap items-center justify-between gap-3
				rounded-lg px-4 py-2 mb-8
				bg-[#ffffff] text-[#000000] border border-[#E9DFFF]
				overflow-hidden
			"
		>

			{/* Left: icon + message */}
			<div className="relative flex items-center gap-2 min-w-0">
				<span className="shrink-0 flex items-center justify-center w-8 h-8 rounded-lg bg-white/10">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" rx="8" fill="#F6C708"/>
                    <g clip-path="url(#clip0_3664_12478)">
                    <path d="M20.7657 14.7181C21.2464 14.396 21.5911 13.9154 21.743 13.3483C21.9092 12.7283 21.8249 12.0826 21.5058 11.5299C21.1867 10.9772 20.6696 10.5813 20.0497 10.4152C19.4825 10.2632 18.894 10.3214 18.3746 10.5767L17.0365 8.25896C16.7517 7.76557 16.24 7.47799 15.672 7.48767C15.1024 7.4981 14.6032 7.80486 14.3365 8.30826C14.1492 8.66193 13.9733 9.02932 13.7871 9.4183C13.0282 11.0037 12.168 12.8005 10.3083 13.8742L7.91876 15.2538C7.09622 15.7287 6.50704 16.4985 6.2597 17.4215C6.01239 18.3446 6.13771 19.3059 6.61259 20.1284C7.27278 21.2719 8.47474 21.9119 9.709 21.9119C10.168 21.912 10.6226 21.823 11.0476 21.6497L12.7181 24.5431C13.0638 25.1417 13.6928 25.4767 14.3389 25.4767C14.6658 25.4768 14.9869 25.3906 15.2697 25.2268C15.7005 24.9781 16.009 24.5752 16.1384 24.0922C16.2678 23.6092 16.2021 23.106 15.9534 22.6753L14.3097 19.8282C16.0402 19.0054 17.8568 19.1451 19.4741 19.2701C19.9044 19.3033 20.3109 19.3347 20.7111 19.3494C21.2796 19.3697 21.7957 19.0913 22.0895 18.6031C22.3833 18.1151 22.3886 17.5292 22.1038 17.0358L20.7656 14.718L20.7657 14.7181ZM19.7969 11.3585C20.1649 11.457 20.4714 11.6913 20.6601 12.0181C21.0243 12.649 20.8478 13.4459 20.2757 13.8694L18.8647 11.4254C19.1567 11.2986 19.4821 11.2741 19.7969 11.3585H19.7969ZM9.70739 20.9356C8.81099 20.9355 7.93786 20.4707 7.45837 19.6401C7.11392 19.0435 7.02321 18.3454 7.20306 17.6743C7.38286 17.0033 7.81048 16.444 8.40708 16.0996L10.374 14.964L12.9683 19.4519L10.999 20.5889C10.5918 20.824 10.1469 20.9356 9.70739 20.9356ZM15.1952 23.8395C15.1333 24.0705 14.9863 24.2629 14.7815 24.3812C14.3559 24.6269 13.8097 24.4806 13.5639 24.0549L11.9101 21.1904L13.4539 20.2991L15.1077 23.1636C15.226 23.3685 15.257 23.6085 15.1951 23.8395H15.1952ZM21.2528 18.0996C21.1418 18.2841 20.9627 18.3819 20.7468 18.3735C20.3664 18.3596 19.9696 18.3289 19.5494 18.2965C17.7845 18.1601 15.8 18.0072 13.8224 18.9781L11.2113 14.4612C13.0432 13.2327 13.9034 11.4372 14.668 9.84002C14.8498 9.46021 15.0215 9.10146 15.1995 8.76537C15.3003 8.57502 15.4745 8.46803 15.6898 8.46412L15.702 8.464C15.9118 8.464 16.0851 8.56428 16.1908 8.74728L21.2581 17.5242C21.3658 17.7107 21.364 17.9151 21.2529 18.0996L21.2528 18.0996ZM19.9844 8.51228L20.9928 6.76572C21.1276 6.53217 21.4262 6.45213 21.6598 6.58701C21.8933 6.72185 21.9733 7.02049 21.8385 7.254L20.8301 9.00057C20.7981 9.05611 20.7554 9.1048 20.7045 9.14385C20.6537 9.1829 20.5956 9.21154 20.5337 9.22813C20.4717 9.24473 20.4071 9.24896 20.3435 9.24057C20.28 9.23219 20.2187 9.21136 20.1631 9.17928C19.9296 9.04443 19.8496 8.7458 19.9844 8.51228ZM24.5177 10.3547L22.7289 11.3875C22.6734 11.4196 22.6121 11.4404 22.5485 11.4488C22.4849 11.4572 22.4203 11.453 22.3584 11.4364C22.2965 11.4198 22.2384 11.3911 22.1875 11.3521C22.1367 11.313 22.094 11.2644 22.0619 11.2088C21.9271 10.9753 22.0071 10.6766 22.2406 10.5418L24.0295 9.509C24.2629 9.37412 24.5616 9.4542 24.6965 9.68771C24.8313 9.92123 24.7513 10.2199 24.5177 10.3547ZM25.8634 13.8679C25.8634 14.1376 25.6448 14.3562 25.3752 14.3562H23.3584C23.0887 14.3562 22.8701 14.1376 22.8701 13.8679C22.8701 13.5982 23.0887 13.3796 23.3584 13.3796H25.3752C25.6448 13.3796 25.8634 13.5982 25.8634 13.8679Z" fill="black"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_3664_12478">
                    <rect width="20" height="20" fill="white" transform="translate(6 6)"/>
                    </clipPath>
                    </defs>
                    </svg>
				</span>

				<p className="text-sm text-[#1B2538] font-normal leading-snug">
					{__('New from the ThumbPress team - Try', 'image-sizes')}{' '}
                    <a
						href={UTM_LINK}
						target="_blank"
						rel="noopener noreferrer"
						className="underline underline-offset-2 text-[#40189D] font-semibold focus:shadow-none"
					>
						{__('EasyCommerce', 'image-sizes')}
					</a>
                    {__('. A fast, AI-powered way to sell on WordPress.', 'image-sizes')}{' '}
					<a
						href={UTM_LINK}
						target="_blank"
						rel="noopener noreferrer"
						className="underline underline-offset-2 text-[#40189D] font-semibold focus:shadow-none"
					>
						{__('Learn more', 'image-sizes')}
					</a>
				</p>
			</div>

			{/* Right: dismiss */}
			<div className="relative flex items-center gap-2 shrink-0">
				<button
					type="button"
					onClick={dismiss}
					aria-label={__('Dismiss EasyCommerce notice', 'image-sizes')}
				>
					<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.1213 11.1213L6.87868 6.87868M6.87868 11.1213L11.1213 6.87868M9 16.5C13.1421 16.5 16.5 13.1421 16.5 9C16.5 4.85786 13.1421 1.5 9 1.5C4.85786 1.5 1.5 4.85786 1.5 9C1.5 13.1421 4.85786 16.5 9 16.5Z" stroke="#FF3668" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
				</button>
			</div>
		</div>
	);
}