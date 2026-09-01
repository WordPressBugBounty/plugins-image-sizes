import React, { useEffect } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { applyFilters } from '@wordpress/hooks';
import * as Icons from '../icons';
const iconMap: any = applyFilters( 'thumbpress_icon_map', { ...Icons } ) as any;
import { cn } from '../../lib/utils';

interface NavItemData {
	to: string;
	label: string;
	icon: string;
	pro?: boolean;
	isNew?: boolean;
}

interface SidebarProps {
	navItems?: NavItemData[];
	onNavRequest?: ( to: string ) => boolean;
}

export default function Sidebar( { navItems = [], onNavRequest }: SidebarProps ) {
	const location = useLocation();

	// Sync WP admin submenu active state with SPA route
	useEffect( () => {
		// Find ThumbPress submenu by locating a link with page=thumbpress
		const thumbpressLink = document.querySelector( '#adminmenu a[href*="page=thumbpress"]' );
		if ( ! thumbpressLink ) return;
		const submenu = thumbpressLink.closest( 'ul.wp-submenu' ) || thumbpressLink.closest( 'li' )?.querySelector( 'ul.wp-submenu' );
		if ( ! submenu ) return;

		const currentHash = '#' + location.pathname;

		submenu.querySelectorAll( 'li' ).forEach( ( li ) => li.classList.remove( 'current' ) );
		submenu.querySelectorAll( 'a' ).forEach( ( a ) => {
			a.classList.remove( 'current' );
			a.removeAttribute( 'aria-current' );
		} );

		// Match by hash ending or Dashboard special case
		let matched: HTMLAnchorElement | null = null;
		submenu.querySelectorAll( 'a' ).forEach( ( a ) => {
			const href = a.getAttribute( 'href' ) || '';
			const hashIndex = href.indexOf( '#' );
			const linkHash = hashIndex !== -1 ? href.substring( hashIndex ) : '';
			if ( ( linkHash === currentHash ) || ( currentHash === '#/' && ( linkHash === '#' || linkHash === '#/' || linkHash === '' ) ) ) {
				matched = a as HTMLAnchorElement;
			}
		} );

		if ( matched ) {
			( matched as HTMLAnchorElement ).classList.add( 'current' );
			( matched as HTMLAnchorElement ).setAttribute( 'aria-current', 'page' );
			( matched as HTMLAnchorElement ).parentElement?.classList.add( 'current' );
		}
	}, [ location.pathname ] );

	return (
		<nav className="2xl:w-[235px] lg:w-[208px] bg-white border-r border-thumbpress-border">
			<div className="flex items-center justify-center h-[80px] gap-2 border-b border-thumbpress-border">
				<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect width="32" height="32" rx="16" fill="#40189D"/>
					<path d="M9 6V22C9 22.5523 9.44772 23 10 23H26" stroke="white" strokeWidth="1.5" strokeLinecap="round"/>
					<path d="M23 26V10C23 9.44772 22.5523 9 22 9H6" stroke="white" strokeWidth="1.5" strokeLinecap="round"/>
					<path d="M11.1111 19.0172L17.38 13.0004L17.4928 18.9842L11.1111 19.0172Z" fill="white"/>
					<path d="M18.0553 18.9878L20.9438 16.0001L20.9999 18.9758L18.0553 18.9878Z" fill="white"/>
					<circle cx="19.65" cy="14.65" r="0.65" fill="white"/>
				</svg>
				<span className="text-2xl font-semibold text-black">ThumbPress</span>
			</div>

			<div className="flex flex-col p-3 gap-1">
				{navItems.map(({ to, label, icon, pro, isNew }) => {
					const Icon = iconMap[ icon ];
					return (
						<NavLink
							key={ to }
							to={ to }
							end={ to === '/' }
							onClick={ ( e ) => {
								if ( onNavRequest && onNavRequest( to ) ) {
									e.preventDefault();
								}
							} }
							className={({ isActive }) =>
								cn(
									'flex items-center gap-2 rounded-lg py-4 px-3 2xl:text-sm lg:text-xs font-normal transition-colors border focus:shadow-none hover:bg-[#F1EBFF] hover:text-thumbpress-primary focus:text-thumbpress-primary',
									isActive
										? 'bg-[#F1EBFF] text-thumbpress-primary border-[#DBD3ED] rounded-lg'
										: 'text-[#1D2327] border-transparent',
								)
							}
						>
							{ Icon && <Icon className="h-4 w-4 shrink-0" /> }
							<span className="flex-1 flex items-center gap-1.5">
								{ label }
								{ isNew && (
									<svg className="shrink-0" width="34" height="16" viewBox="0 0 34 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect width="34" height="16" rx="8" fill="#FF3A52"/>
										<text
											x="17"
											y="11.5"
											textAnchor="middle"
											fill="#FFFFFF"
											fontSize="9"
											fontWeight="600"
											fontFamily="Inter, -apple-system, sans-serif"
										>
											New
										</text>
									</svg>
								) }
							</span>
							{ pro && (
								<span className="ml-auto shrink-0">
									<svg width="20" height="17" viewBox="0 0 24 20" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path fillRule="evenodd" clipRule="evenodd" d="M12.9589 4.84898C12.5102 4.16232 11.4915 4.16232 11.0429 4.84898L8.77619 8.31565L7.04419 7.33365C6.17619 6.84165 5.13286 7.61232 5.36752 8.57098L6.54486 13.3717C6.57128 13.4774 6.63242 13.5712 6.71848 13.638C6.80455 13.7049 6.91055 13.7409 7.01952 13.7403H16.9822C17.0912 13.7409 17.1972 13.7049 17.2832 13.638C17.3693 13.5712 17.4304 13.4774 17.4569 13.3717L18.6335 8.57165C18.8689 7.61165 17.8255 6.84165 16.9575 7.33365L15.2255 8.31498L12.9589 4.84898ZM12.1375 5.37032C12.1226 5.34777 12.1023 5.32927 12.0784 5.31647C12.0546 5.30367 12.0279 5.29697 12.0009 5.29697C11.9738 5.29697 11.9472 5.30367 11.9233 5.31647C11.8995 5.32927 11.8792 5.34777 11.8642 5.37032L9.34619 9.22165C9.27721 9.32564 9.17138 9.39962 9.05002 9.42869C8.92867 9.45776 8.80081 9.43976 8.69219 9.37832L6.55686 8.16898C6.43286 8.09898 6.28352 8.20898 6.31752 8.34565L7.40419 12.7783H16.5975L17.6842 8.34565C17.7175 8.20898 17.5689 8.09898 17.4442 8.16898L15.3095 9.37832C15.201 9.43956 15.0733 9.45745 14.9521 9.42839C14.8309 9.39932 14.7252 9.32546 14.6562 9.22165L12.1375 5.37032Z" fill="#FA9624"/>
										<path d="M7.44179 14.7031C7.37806 14.7027 7.31486 14.7148 7.25581 14.7388C7.19676 14.7628 7.14301 14.7982 7.09763 14.8429C7.05225 14.8877 7.01614 14.9409 6.99134 14.9997C6.96655 15.0584 6.95356 15.1214 6.95312 15.1851C6.95312 15.4511 7.17246 15.6665 7.44179 15.6665H16.5618C16.6904 15.6674 16.8141 15.6172 16.9057 15.5269C16.9973 15.4367 17.0494 15.3137 17.0505 15.1851C17.05 15.1214 17.037 15.0584 17.0122 14.9997C16.9874 14.9409 16.9513 14.8877 16.906 14.8429C16.8606 14.7982 16.8068 14.7628 16.7478 14.7388C16.6887 14.7148 16.6255 14.7027 16.5618 14.7031H7.44179Z" fill="#FA9624"/>
									</svg>
								</span>
							) }
						</NavLink>
					);
				})}
			</div>
		</nav>
	);
}
