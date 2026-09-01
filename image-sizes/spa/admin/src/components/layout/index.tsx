import React, { useEffect, useRef, useState } from 'react';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import Sidebar from './Sidebar';
import ExitIntentPopup from '../pro-page/ExitIntentPopup';
import EvergreenExitIntentPopup from '../pro-page/EvergreenExitIntentPopup';
import CdnAnnouncementPopup from '../pro-page/CdnAnnouncementPopup';
import { saveSettings } from '../../api';

interface NavItemData {
	to: string;
	label: string;
	icon: string;
	pro?: boolean;
}

interface LayoutProps {
	navItems?: NavItemData[];
}

const STORAGE_KEY = 'thumbpress_exit_popup_shown';

export default function Layout( { navItems }: LayoutProps ) {
	const location = useLocation();
	const navigate = useNavigate();
	const [ showPopup, setShowPopup ] = useState( false );
	const pendingPath = useRef< string | null >( null );

	// One-time "Meet ThumbPress CDN" announcement. Shown until the user dismisses
	// it — the server sends show_cdn_announcement as the inverse of the
	// thumbpress_cdn_announcement_dismissed option, so no arming step is needed.
	// Held back 1.5s after mount so it eases in rather than flashing on immediately.
	const [ showCdnPopup, setShowCdnPopup ] = useState( false );

	useEffect( () => {
		if ( ! window.THUMBPRESS?.show_cdn_announcement ) return;
		const id = setTimeout( () => setShowCdnPopup( true ), 1500 );
		return () => clearTimeout( id );
	}, [] );

	// Only the X and the CTA close the popup — there is no backdrop dismissal, so
	// a stray click outside it cannot burn the single showing. Both paths persist.
	const dismissCdnPopup = () => {
		setShowCdnPopup( false );
		saveSettings( 'thumbpress_cdn_announcement_dismissed', true ).catch( () => {} );
	};

	const handleCdnEnable = () => {
		dismissCdnPopup();

		// Pro active with an activated license (thumbpress_is_pro_active filter) —
		// take the user straight to the CDN settings tab.
		if ( window.THUMBPRESS?.pro_active ) {
			navigate( '/cdn' );
			return;
		}

		// Pro plugin activated but license not active — send to the Pro page.
		if ( isProInstalled() ) {
			navigate( '/pro' );
			return;
		}

		// Free (Pro not installed) — Pro page, scrolled to the pricing section.
		navigate( '/pro' );
		setTimeout( () => {
			document.getElementById( 'thumbpress-pro-pricing' )?.scrollIntoView( { behavior: 'smooth' } );
		}, 300 );
	};

	const isProInstalled = () => typeof window.THUMBPRESS_PRO !== 'undefined';

	// Which variant to render: the dated Summer Sale popup while that campaign
	// is live, otherwise the evergreen TPTHANKS25 offer. The popup itself is no
	// longer promo-gated — there's always something to show.
	const isPromoActive = () => window.THUMBPRESS?.promo_active ?? false;

	// Called by Sidebar on nav link click. Returns true = handled (popup shown), false = let NavLink navigate normally.
	const onNavRequest = ( to: string ): boolean => {
		// if ( ! isPromoActive() ) return false;
		if ( location.pathname !== '/pro' ) return false;
		if ( isProInstalled() ) return false;
		if ( sessionStorage.getItem( STORAGE_KEY ) ) return false;

		pendingPath.current = to;
		sessionStorage.setItem( STORAGE_KEY, '1' );
		setShowPopup( true );
		return true;
	};

	// Exit intent: mouse leaves browser viewport from top while on /pro
	useEffect( () => {
		const handleMouseLeave = ( e: MouseEvent ) => {
			// if ( ! isPromoActive() ) return;
			if ( e.clientY > 0 ) return;
			if ( location.pathname !== '/pro' ) return;
			if ( isProInstalled() ) return;
			if ( sessionStorage.getItem( STORAGE_KEY ) ) return;

			sessionStorage.setItem( STORAGE_KEY, '1' );
			setShowPopup( true );
		};

		document.addEventListener( 'mouseleave', handleMouseLeave );
		return () => document.removeEventListener( 'mouseleave', handleMouseLeave );
	}, [ location.pathname ] );

	// WordPress native admin menu clicks (when on /pro page)
	useEffect( () => {
		const handleAdminMenuClick = ( e: MouseEvent ) => {
			const target = e.target as HTMLElement;
			const wpMenuLink = target.closest( '#adminmenu a, #adminmenu li > a' ) as HTMLAnchorElement | null;

			if ( ! wpMenuLink ) return;
			// if ( ! isPromoActive() ) return;
			if ( location.pathname !== '/pro' ) return;
			if ( isProInstalled() ) return;
			if ( sessionStorage.getItem( STORAGE_KEY ) ) return;

			// Don't intercept clicks on ThumbPress menu items
			if ( wpMenuLink.getAttribute( 'href' )?.includes( 'page=thumbpress' ) ) return;

			sessionStorage.setItem( STORAGE_KEY, '1' );
			pendingPath.current = wpMenuLink.href;
			setShowPopup( true );
			e.preventDefault();
		};

		document.addEventListener( 'click', handleAdminMenuClick, true );
		return () => document.removeEventListener( 'click', handleAdminMenuClick, true );
	}, [ location.pathname ] );

	const handleClose = () => {
		setShowPopup( false );
		if ( pendingPath.current ) {
			if ( pendingPath.current.startsWith( 'http' ) || pendingPath.current.includes( '/wp-admin/' ) ) {
				window.location.href = pendingPath.current;
			} else {
				navigate( pendingPath.current );
			}
			pendingPath.current = null;
		}
	};

	const handleScrollToPricing = () => {
		setShowPopup( false );
		pendingPath.current = null;
		setTimeout( () => {
			document.getElementById( 'thumbpress-pro-pricing' )?.scrollIntoView( { behavior: 'smooth' } );
		}, 100 );
	};

	return (
		<div className="flex font-inter">
			<Sidebar navItems={ navItems } onNavRequest={ onNavRequest } />

			<main className="flex-1 ">
				<Outlet />
			</main>

			{ showPopup && (
				isPromoActive()
					? <ExitIntentPopup onClose={ handleClose } onScrollToPricing={ handleScrollToPricing } />
					: <EvergreenExitIntentPopup onClose={ handleClose } onScrollToPricing={ handleScrollToPricing } />
			) }

			{ showCdnPopup && (
				<CdnAnnouncementPopup onDismiss={ dismissCdnPopup } onEnable={ handleCdnEnable } />
			) }
		</div>
	);
}