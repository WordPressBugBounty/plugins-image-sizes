import React, {useState, useEffect} from 'react';
import { __, sprintf, _n } from '@wordpress/i18n';
import { getDashboardStats, type DashboardStats } from '../../api';
import FeatureCard from './components/FeatureCard';

const Features = () => {
    const [stats, setStats] = useState<DashboardStats | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        getDashboardStats()
            .then((res: any) => {
                if (res?.data) {
                    setStats(res.data);
                }
            })
            .finally(() => setLoading(false));
    }, []);

	return (
		<div id="thumbpress-pro-features" className="2xl:px-[80px] lg:px-6 py-16">
			<div className="text-center mb-10">
				<h2 className="2xl:text-[32px] lg:text-[28px] font-semibold text-thumbpress-title mb-2 max-w-[600px] mx-auto leading-[1.4]">
					{__( 'What Pro Fixes Today', 'image-sizes' )}
				</h2>
				<p className="text-base text-thumbpress-body max-w-[500px] mx-auto">
					{__( 'Every Pro feature is designed to solve a specific real problem. Here\'s what exactly an upgrade unlocks.', 'image-sizes' )}
				</p>
			</div>
			<div className="grid grid-cols-3 2xl:gap-5 lg:gap-4">
                <FeatureCard
                    icon={(
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="10" fill="#B658FF" fill-opacity="0.08"/>
                            <g clip-path="url(#clip0_2765_6693)">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M13.0625 19.625C13.0625 23.2494 16.0006 26.1875 19.625 26.1875C23.2494 26.1875 26.1875 23.2494 26.1875 19.625C26.1875 16.0006 23.2494 13.0625 19.625 13.0625C16.0006 13.0625 13.0625 16.0006 13.0625 19.625ZM19.625 27.3125C15.3793 27.3125 11.9375 23.8707 11.9375 19.625C11.9375 15.3793 15.3793 11.9375 19.625 11.9375C23.8707 11.9375 27.3125 15.3793 27.3125 19.625C27.3125 21.5454 26.6083 23.3013 25.4441 24.6487L27.8977 27.1023C28.1174 27.3219 28.1174 27.6781 27.8977 27.8977C27.6781 28.1174 27.3219 28.1174 27.1023 27.8977L24.6487 25.4441C23.3013 26.6083 21.5454 27.3125 19.625 27.3125Z" fill="#B658FF"/>
                            </g>
                            <defs>
                            <clipPath id="clip0_2765_6693">
                            <rect width="18" height="18" fill="white" transform="translate(11 11)"/>
                            </clipPath>
                            </defs>
                        </svg>
                    )}
                    subtitle={!loading && stats?.large_images && stats.large_images > 0 ? sprintf( /* translators: %d is the number of images found. */ _n( '%d image found!', '%d images found!', stats.large_images, 'image-sizes' ), stats.large_images ) : ''}
                    title={__( 'Detect Large Images', 'image-sizes' )}
                    description={__( 'Scan your media library and find oversized images instantly. See exactly which files are bloating your pages and fix them before they cost you rankings.', 'image-sizes' )}
                    link={(
                        <button
                            onClick={() => (
								document.getElementById('thumbpress-pro-pricing')?.scrollIntoView({ behavior: 'smooth' })
							)}
                            className="!text-[#B658FF] mt-6 inline-block text-sm border-b border-[#B658FF]"
                        >
                            {__( 'Detect Now', 'image-sizes' )}
                        </button>
                    )}
                />
                
                <FeatureCard
                    icon={(
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="10" fill="#E48603" fill-opacity="0.1"/>
                            <path d="M14.417 17V24.5C14.417 26.1569 15.7601 27.5 17.417 27.5H21.917C23.5738 27.5 24.917 26.1569 24.917 24.5V17M21.167 19.25V23.75M18.167 19.25L18.167 23.75M22.667 14.75L21.6123 13.1679C21.3341 12.7507 20.8657 12.5 20.3642 12.5H18.9698C18.4682 12.5 17.9999 12.7507 17.7217 13.1679L16.667 14.75M22.667 14.75H16.667M22.667 14.75H26.417M16.667 14.75H12.917" stroke="#E08E06" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    )}
                    subtitle={!loading && stats?.unused_images && stats.unused_images > 0 ? sprintf( /* translators: %d is the number of images found. */ _n( '%d image found!', '%d images found!', stats.unused_images, 'image-sizes' ), stats.unused_images ) : ''}
                    title={__( 'Delete Unused Images', 'image-sizes' )}
                    description={__( 'Scan your media library and find unused images instantly. See exactly which files are taking up space and delete them to reclaim server space.', 'image-sizes' )}
                    link={(
                        <button
                            onClick={() => (
								document.getElementById('thumbpress-pro-pricing')?.scrollIntoView({ behavior: 'smooth' })
							)}
                            className="!text-[#E08E06] mt-6 inline-block text-sm border-b border-[#E08E06]"
                        >
                            {__( 'Delete Now', 'image-sizes' )}
                        </button>
                    )}
                />

                <FeatureCard
                    icon={(
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="10" fill="#059669" fill-opacity="0.08"/>
                            <path d="M16.7627 13.3819C16.7627 13.3819 13.788 13.1151 13.3036 13.5995C12.8191 14.0839 13.0861 17.0586 13.0861 17.0586" stroke="#059669" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16.7627 26.7352C16.7627 26.7352 13.788 27.0022 13.3036 26.5177C12.8191 26.0332 13.0861 23.0586 13.0861 23.0586" stroke="#059669" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22.7627 13.3819C22.7627 13.3819 25.7374 13.1151 26.2218 13.5995C26.7063 14.0839 26.4393 17.0586 26.4393 17.0586" stroke="#059669" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22.7627 26.7352C22.7627 26.7352 25.7374 27.0022 26.2218 26.5177C26.7063 26.0332 26.4393 23.0586 26.4393 23.0586" stroke="#059669" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21.2705 18.5583L25.8094 14.0195" stroke="#059669" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18.2602 21.5605L13.4922 26.3443" stroke="#059669" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18.2607 18.5608L13.647 13.9531" stroke="#059669" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21.2476 21.5605L26.1589 26.4325" stroke="#059669" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    )}
                    subtitle={!loading && stats?.unoptimized_images && stats.unoptimized_images > 0 ? sprintf( /* translators: %d is the number of images found. */ _n( '%d image found!', '%d images found!', stats.unoptimized_images, 'image-sizes' ), stats.unoptimized_images ) : ''}
                    title={__( 'Compress Large Images', 'image-sizes' )}
                    description={__( 'Large images are costing you speed. Optimize them without visible loss and deliver a faster, smoother browsing experience.', 'image-sizes' )}
                    link={(
                        <button
                            onClick={() => (
								document.getElementById('thumbpress-pro-pricing')?.scrollIntoView({ behavior: 'smooth' })
							)}
                            className="!text-[#059669] mt-6 inline-block text-sm border-b border-[#059669]"
                        >
                            {__( 'Compress Now', 'image-sizes' )}
                        </button>
                    )}
                />

                <FeatureCard
                    icon={(
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="10" fill="#0EB9F2" fill-opacity="0.08"/>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M22.1583 11.0822C22.4808 10.9592 22.8419 11.121 22.9649 11.4436L23.9466 14.0192C24.0411 14.267 23.9689 14.5474 23.7665 14.7188C23.5642 14.8901 23.2758 14.9152 23.0469 14.7812C22.1529 14.258 21.1124 13.9579 19.9999 13.9579C16.6632 13.9579 13.9583 16.6628 13.9583 19.9995C13.9583 21.101 14.2524 22.1319 14.7661 23.0199C14.939 23.3187 14.8369 23.701 14.5381 23.8739C14.2393 24.0467 13.857 23.9446 13.6841 23.6458C13.0633 22.5727 12.7083 21.3266 12.7083 19.9995C12.7083 15.9724 15.9728 12.7079 19.9999 12.7079C20.7821 12.7079 21.5361 12.8312 22.2431 13.0596L21.7969 11.8888C21.6739 11.5662 21.8357 11.2051 22.1583 11.0822ZM25.4618 16.1252C25.7605 15.9523 26.1429 16.0544 26.3157 16.3532C26.9365 17.4264 27.2916 18.6724 27.2916 19.9995C27.2916 24.0266 24.027 27.2912 19.9999 27.2912C19.2177 27.2912 18.4637 27.1678 17.7567 26.9394L18.203 28.1102C18.3259 28.4328 18.1641 28.7939 17.8416 28.9169C17.519 29.0398 17.1579 28.878 17.035 28.5555L16.0532 25.9798C15.9587 25.732 16.0309 25.4516 16.2333 25.2803C16.4356 25.1089 16.7241 25.0839 16.9529 25.2178C17.8469 25.7411 18.8874 26.0412 19.9999 26.0412C23.3366 26.0412 26.0416 23.3362 26.0416 19.9995C26.0416 18.898 25.7474 17.8671 25.2337 16.9791C25.0609 16.6803 25.163 16.298 25.4618 16.1252Z" fill="#0EB9F2"/>
                        </svg>
                    )}
                    subtitle={!loading && stats?.not_avif && stats.not_avif > 0 ? sprintf( /* translators: %d is the number of images found. */ _n( '%d image found!', '%d images found!', stats.not_avif, 'image-sizes' ), stats.not_avif ) : ''}
                    title={__( 'Bulk Convert to AVIF', 'image-sizes' )}
                    description={__( "Your images shouldn't be stuck in the past. Convert them all to AVIF - the format the modern web is built on - in a single click.", 'image-sizes' )}
                    link={(
                        <button
                            onClick={() => (
								document.getElementById('thumbpress-pro-pricing')?.scrollIntoView({ behavior: 'smooth' })
							)}
                            className="!text-[#0EB9F2] mt-6 inline-block text-sm border-b border-[#0EB9F2]"
                        >
                            {__( 'Convert Now', 'image-sizes' )}
                        </button>
                    )}
                />

                <FeatureCard
                    icon={(
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="10" fill="#F9733B" fill-opacity="0.08"/>
                            <g clip-path="url(#clip0_2765_6746)">
                            <path d="M19.583 27.5009C18.7546 27.5009 18.083 26.8293 18.083 26.0009V19.2519C18.083 18.4231 18.7552 17.7514 19.5841 17.752L26.3341 17.7567C27.1621 17.7573 27.833 18.4287 27.833 19.2567V26.0009C27.833 26.8293 27.1615 27.5009 26.333 27.5009H19.583Z" stroke="#F9733B" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M24.083 17.3223V14.0077C24.083 13.1797 23.4121 12.5083 22.5841 12.5077L14.3341 12.502C13.5052 12.5014 12.833 13.1731 12.833 14.002V22.2509C12.833 23.0794 13.5046 23.7509 14.333 23.7509H17.6544" stroke="#F9733B" stroke-width="1.125" stroke-linejoin="round"/>
                            </g>
                            <defs>
                            <clipPath id="clip0_2765_6746">
                            <rect width="18" height="18" fill="white" transform="translate(11.333 11)"/>
                            </clipPath>
                            </defs>
                        </svg>
                    )}
                    subtitle={!loading && stats?.duplicate_images && stats.duplicate_images > 0 ? sprintf( /* translators: %d is the number of images found. */ _n( '%d image found!', '%d images found!', stats.duplicate_images, 'image-sizes' ), stats.duplicate_images ) : ''}
                    title={__( 'Merge Duplicate Images', 'image-sizes' )}
                    description={__( 'Find images that have been uploaded more than once. Clean up the clutter and stop wasting space on files you already have.', 'image-sizes' )}
                    link={(
                        <button
                            onClick={() => (
								document.getElementById('thumbpress-pro-pricing')?.scrollIntoView({ behavior: 'smooth' })
							)}
                            className="!text-[#F9733B] mt-6 inline-block text-sm border-b border-[#F9733B]"
                        >
                            {__( 'Merge Now', 'image-sizes' )}
                        </button>
                    )}
                />

                <FeatureCard
                    icon={(
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="10" fill="#06A10D" fill-opacity="0.05"/>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M11.792 14.75C11.792 14.3358 12.1278 14 12.542 14H23.417C24.6597 14 25.667 15.0074 25.667 16.25V27.125C25.667 27.5392 25.3312 27.875 24.917 27.875C24.5028 27.875 24.167 27.5392 24.167 27.125V16.25C24.167 15.8358 23.8312 15.5 23.417 15.5H12.542C12.1278 15.5 11.792 15.1642 11.792 14.75Z" fill="#06A10D"/>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M14.417 12.125C14.8312 12.125 15.167 12.4608 15.167 12.875V14.75C15.167 15.1642 14.8312 15.5 14.417 15.5C14.0028 15.5 13.667 15.1642 13.667 14.75V12.875C13.667 12.4608 14.0028 12.125 14.417 12.125ZM14.417 16.625C14.8312 16.625 15.167 16.9608 15.167 17.375V23.75C15.167 24.1642 15.5028 24.5 15.917 24.5H21.917C22.3312 24.5 22.667 24.8358 22.667 25.25C22.667 25.6642 22.3312 26 21.917 26H15.917C14.6744 26 13.667 24.9927 13.667 23.75V17.375C13.667 16.9608 14.0028 16.625 14.417 16.625ZM24.167 25.25C24.167 24.8358 24.5028 24.5 24.917 24.5H26.792C27.2062 24.5 27.542 24.8358 27.542 25.25C27.542 25.6642 27.2062 26 26.792 26H24.917C24.5028 26 24.167 25.6642 24.167 25.25Z" fill="#06A10D"/>
                        </svg>
                    )}
                    subtitle={''}
                    title={__( 'CDN Offloading', 'image-sizes' )}
                    description={__( 'Serve your images from a global edge network instead of a single server. Pages load faster for visitors everywhere, with zero setup required.', 'image-sizes' )}
                    newBadge={true}
                    link={(
                        <button
                            onClick={() => (
								document.getElementById('thumbpress-pro-pricing')?.scrollIntoView({ behavior: 'smooth' })
							)}
                            className="!text-[#06A10D] mt-6 inline-block text-sm border-b border-[#06A10D]"
                        >
                            {__( 'Enable Now', 'image-sizes' )}
                        </button>
                    )}
                />
			</div>
		</div>
	);
};

export default Features;
