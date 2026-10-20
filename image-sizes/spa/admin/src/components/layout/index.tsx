import React, { useEffect, useState } from 'react';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import Sidebar from './Sidebar';
import ExitIntentPopup from '../pro-page/ExitIntentPopup';
import EvergreenExitIntentPopup from '../pro-page/EvergreenExitIntentPopup';
import WhatsNewPopup from './WhatsNewPopup';
import ReviewPrompt from './ReviewPrompt';
import { dismissWhatsNew } from '../../api';

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

	// "What's new": the server decides (right release, not dismissed yet). Held back briefly so it eases in.
	const [ showWhatsNew, setShowWhatsNew ] = useState( false );

	useEffect( () => {
		if ( ! window.THUMBPRESS?.show_whats_new ) return;
		const id = setTimeout( () => setShowWhatsNew( true ), 800 );
		return () => clearTimeout( id );
	}, [] );

	// Every way out is remembered, so it shows once. The server stamps the version itself.
	const dismissWhatsNewPopup = () => {
		setShowWhatsNew( false );
		dismissWhatsNew().catch( () => {} );
	};

	const goFromWhatsNew = ( path: string ) => {
		dismissWhatsNewPopup();
		navigate( path );
	};

	// Review prompt: the server decides (due after install, not yet answered). It answers itself.
	const [ showReview, setShowReview ] = useState( false );

	useEffect( () => {
		if ( ! window.THUMBPRESS?.show_review ) return;
		const id = setTimeout( () => setShowReview( true ), 2500 );
		return () => clearTimeout( id );
	}, [] );

	const isProInstalled = () => typeof window.THUMBPRESS_PRO !== 'undefined';

	// Which variant to render: the dated Summer Sale popup while that campaign
	// is live, otherwise the evergreen TPTHANKS offer.
	const isPromoActive = () => window.THUMBPRESS?.promo_active ?? false;

	// Exit intent: mouse leaves browser viewport from top while on /pro. This is the only trigger:
	// clicking a menu item always navigates straight away (#591).
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

	const handleClose = () => setShowPopup( false );

	const handleScrollToPricing = () => {
		setShowPopup( false );
		setTimeout( () => {
			document.getElementById( 'thumbpress-pro-pricing' )?.scrollIntoView( { behavior: 'smooth' } );
		}, 100 );
	};

	return (
		<div className="flex font-inter">
			<Sidebar navItems={ navItems } />

			<main className="flex-1 ">
				<Outlet />
			</main>

			{ showWhatsNew && (
				<WhatsNewPopup
					version={ window.THUMBPRESS?.whats_new_version || '' }
					items={ window.THUMBPRESS?.whats_new_items || [] }
					onDismiss={ dismissWhatsNewPopup }
					onGo={ goFromWhatsNew }
				/>
			) }

			{ showReview && ! showWhatsNew && (
				<ReviewPrompt
					email={ window.THUMBPRESS?.review_email || '' }
					onClose={ () => setShowReview( false ) }
				/>
			) }

			{ showPopup && (
				isPromoActive()
					? <ExitIntentPopup onClose={ handleClose } onScrollToPricing={ handleScrollToPricing } />
					: <EvergreenExitIntentPopup onClose={ handleClose } onScrollToPricing={ handleScrollToPricing } />
			) }
		</div>
	);
}