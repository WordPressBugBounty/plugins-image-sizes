import React from 'react';
import { __ } from '@wordpress/i18n';

interface HeaderProps {
	title: string;
}

const Header = ({ title }: HeaderProps) => {
	const isProActive = window.THUMBPRESS?.pro_active;

	return (
		<div className="h-[80px] w-full px-8 flex items-center justify-between bg-white">
			<h1 className="text-2xl font-medium text-thumbpress-title">{title}</h1>

            <div className="flex items-center gap-4 text-sm font-medium text-thumbpress-body">
                <a href="https://community.codexpert.io/c/space/thumbpress" target="_blank" rel="noreferrer" className="flex items-center gap-1.5 hover:text-thumbpress-primary duration-300">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                    </svg>
                    {__('Community', 'image-sizes')}
                </a>

                <span className='text-[#D9D9D9]'>|</span>

                <a href="https://help.pluggable.io/new-ticket/" target="_blank" rel="noreferrer" className="flex items-center gap-1.5 hover:text-thumbpress-primary duration-300">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-2h2v2zm1.07-7.75-.9.92c-.5.51-.8.93-.94 1.83h-2v-.5c0-.66.27-1.28.71-1.72l1.24-1.26c.37-.36.59-.86.59-1.42 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z" />
                    </svg>
                    {__('Support', 'image-sizes')}
                </a>

                <span className='text-[#D9D9D9]'>|</span>

                <a href="https://thumbpress.co/docs" target="_blank" rel="noreferrer" className="flex items-center gap-1.5 hover:text-thumbpress-primary duration-300">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                    </svg>
                    {__('Docs', 'image-sizes')}
                </a>

                { !isProActive && (
                    <a href="#/pro" className="flex items-center gap-2.5 !text-white bg-thumbpress-primary hover:bg-thumbpress-primary-hover duration-300 px-4 py-2.5 rounded-lg">
                        <svg width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15.8307 14.8408H1.97914C1.43271 14.8408 0.989746 15.2838 0.989746 15.8302C0.989746 16.3766 1.43271 16.8196 1.97914 16.8196H15.8307C16.3771 16.8196 16.8201 16.3766 16.8201 15.8302C16.8201 15.2838 16.3771 14.8408 15.8307 14.8408Z" fill="white"/>
                            <path d="M17.3104 2.10902C16.9678 1.91332 16.5417 1.93989 16.2261 2.17662L13.0357 4.56945L9.67714 0.371324C9.48938 0.136622 9.20512 0 8.90456 0C8.604 0 8.31973 0.136622 8.13197 0.371324L4.77346 4.56945L1.58305 2.17662C1.26741 1.93989 0.841284 1.91333 0.498687 2.10902C0.156084 2.30471 -0.0374975 2.68525 0.00607412 3.07739L0.995446 11.9819C1.05112 12.483 1.47465 12.862 1.97879 12.862H15.8303C16.3345 12.862 16.758 12.483 16.8137 11.9819L17.803 3.07739C17.8466 2.68525 17.653 2.30471 17.3104 2.10902ZM14.9448 10.8833H2.86434L2.2253 5.13179L4.35335 6.72784C4.53113 6.86117 4.73948 6.92579 4.94635 6.92577C5.23696 6.92574 5.52455 6.79817 5.71957 6.5544L8.90456 2.57319L12.0895 6.5544C12.4234 6.97168 13.0282 7.04849 13.4558 6.72784L15.5838 5.13179L14.9448 10.8833Z" fill="white"/>
                        </svg>
						{__('Upgrade to Pro', 'image-sizes')}
					</a>
				) }
			</div>
		</div>
	);
};

export default Header;
