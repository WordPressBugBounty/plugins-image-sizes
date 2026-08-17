import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { X, Copy, Check, Tag, Clock } from 'lucide-react';
import { toast } from 'sonner';

const COUPON_CODE = 'TPTHANKS';
const TIMER_SECONDS = 10 * 60; // 10 minutes
const TIMER_KEY = 'thumbpress_exit_popup_expiry';

function getRemainingSeconds(): number {
	const expiry = sessionStorage.getItem( TIMER_KEY );
	if ( expiry ) {
		const remaining = Math.floor( ( parseInt( expiry ) - Date.now() ) / 1000 );
		return Math.max( 0, remaining );
	}
	const expiryTs = Date.now() + TIMER_SECONDS * 1000;
	sessionStorage.setItem( TIMER_KEY, String( expiryTs ) );
	return TIMER_SECONDS;
}

function formatTime( seconds: number ): string {
	const m = Math.floor( seconds / 60 ).toString().padStart( 2, '0' );
	const s = ( seconds % 60 ).toString().padStart( 2, '0' );
	return `${ m }:${ s }`;
}

interface Props {
	onClose: () => void;
	onScrollToPricing: () => void;
}

export default function EvergreenExitIntentPopup( { onClose, onScrollToPricing }: Props ) {
	const [ copied, setCopied ] = useState( false );
	const [ timeLeft, setTimeLeft ] = useState( getRemainingSeconds );

	useEffect( () => {
		if ( timeLeft <= 0 ) return;
		const id = setInterval( () => {
			setTimeLeft( getRemainingSeconds() );
		}, 1000 );
		return () => clearInterval( id );
	}, [] );

	const copyCode = ( onDone?: () => void ) => {
		const done = () => {
			onDone?.();
		};
		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( COUPON_CODE ).then( done ).catch( () => fallbackCopy( done ) );
		} else {
			fallbackCopy( done );
		}
	};

	const handleCopy = () => {
		copyCode( () => {
			setCopied( true );
			setTimeout( () => setCopied( false ), 2000 );
		} );
	};

	const fallbackCopy = ( done: () => void ) => {
		const el = document.createElement( 'textarea' );
		el.value = COUPON_CODE;
		el.style.position = 'fixed';
		el.style.opacity = '0';
		document.body.appendChild( el );
		el.focus();
		el.select();
		try {
			document.execCommand( 'copy' );
			done();
		} catch {}
		document.body.removeChild( el );
	};

	const handleGetDiscount = () => {
		copyCode( () => toast.success(
			__( 'Coupon code TPTHANKS copied!', 'image-sizes' ),
			{ description: __( 'Paste it at checkout for 20% off.', 'image-sizes' ) }
		) );
		onScrollToPricing();
	};

	const expired = timeLeft <= 0;

	return (
		<div
			className="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50"
			onClick={ onClose }
		>
			<div
				className="relative mx-4 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<button
					onClick={ onClose }
					className="absolute right-4 top-4 rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
					aria-label={ __( 'Close', 'image-sizes' ) }
				>
					<X size={ 18 } />
				</button>

				<div className="p-8 text-center">
					<div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-thumbpress-primary/10">
						<Tag className="text-thumbpress-primary" size={ 26 } />
					</div>

					<h2 className="mb-2 text-2xl font-bold text-thumbpress-title">
						{ __( 'A Special Offer Just for You!', 'image-sizes' ) }
					</h2>

					<p className="mb-4 text-thumbpress-body">
						{ __( 'As an existing user, we\'re giving you an exclusive 20% discount on ThumbPress Pro. Copy the code and use it at checkout!', 'image-sizes' ) }
					</p>

					<div className={ `mb-5 inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-semibold ${ expired ? 'bg-red-50 text-red-500' : 'bg-amber-50 text-amber-600' }` }>
						<Clock size={ 14 } />
						{ expired
							? __( 'Offer expired', 'image-sizes' )
							: `${ __( 'Offer expires in', 'image-sizes' ) } ${ formatTime( timeLeft ) }`
						}
					</div>

					<div className="mb-6 flex items-center justify-between gap-3 rounded-xl border-2 border-dashed border-thumbpress-primary bg-thumbpress-primary/5 px-5 py-4">
						<span className="text-xl font-extrabold tracking-widest text-thumbpress-primary">
							{ COUPON_CODE }
						</span>
						<button
							onClick={ handleCopy }
							className="flex items-center gap-1.5 rounded-lg bg-thumbpress-primary px-3 py-1.5 text-sm font-medium text-white transition-opacity hover:opacity-90"
						>
							{ copied
								? <><Check size={ 14 } />{ __( 'Copied!', 'image-sizes' ) }</>
								: <><Copy size={ 14 } />{ __( 'Copy', 'image-sizes' ) }</>
							}
						</button>
					</div>

					<button
						onClick={ handleGetDiscount }
						disabled={ expired }
						className="w-full rounded-md bg-thumbpress-primary py-3 text-base font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
					>
						{ __( 'Get 20% Off — View Pricing →', 'image-sizes' ) }
					</button>

					<button
						onClick={ onClose }
						className="mt-3 block w-full text-sm text-gray-400 hover:text-gray-600"
					>
						{ __( 'No thanks, I don\'t want a discount', 'image-sizes' ) }
					</button>
				</div>
			</div>
		</div>
	);
}