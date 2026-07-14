import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { X, Trophy, Clock } from 'lucide-react';
import { toast } from 'sonner';

// Offer deadline: July 19, 2026 at midnight local time.
const OFFER_DEADLINE_TS = new Date( 2026, 6, 19, 0, 0, 0, 0 ).getTime();

/**
 * Seconds remaining until the offer deadline.
 */
function getRemainingSeconds(): number {
	return Math.max( 0, Math.floor( ( OFFER_DEADLINE_TS - Date.now() ) / 1000 ) );
}

/**
 * Split seconds into zero-padded days, hours, minutes and seconds.
 */
function getTimeParts( seconds: number ): { d: string; h: string; m: string; s: string } {
	const d = Math.floor( seconds / 86400 );
	const h = Math.floor( ( seconds % 86400 ) / 3600 );
	const m = Math.floor( ( seconds % 3600 ) / 60 );
	const s = seconds % 60;
	return {
		d: d.toString().padStart( 2, '0' ),
		h: h.toString().padStart( 2, '0' ),
		m: m.toString().padStart( 2, '0' ),
		s: s.toString().padStart( 2, '0' ),
	};
}

interface Props {
	onClose: () => void;
	onScrollToPricing: () => void;
}

export default function ExitIntentPopup( { onClose, onScrollToPricing }: Props ) {
	const [ timeLeft, setTimeLeft ] = useState( getRemainingSeconds );

	useEffect( () => {
		if ( timeLeft <= 0 ) return;
		const id = setInterval( () => {
			setTimeLeft( getRemainingSeconds() );
		}, 1000 );
		return () => clearInterval( id );
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	const handleGetDiscount = () => {
		toast.success(
			__( 'World Cup discount applied!', 'image-sizes' ),
			{ description: __( 'Your up-to-48% discount is auto-applied at checkout.', 'image-sizes' ) }
		);
		onScrollToPricing();
	};

	const expired = timeLeft <= 0;
	const { d, h, m, s } = getTimeParts( timeLeft );
	const units = [
		{ value: d, label: __( 'Days', 'image-sizes' ) },
		{ value: h, label: __( 'Hrs', 'image-sizes' ) },
		{ value: m, label: __( 'Min', 'image-sizes' ) },
		{ value: s, label: __( 'Sec', 'image-sizes' ) },
	];

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
						<Trophy className="text-thumbpress-primary" size={ 26 } />
					</div>

					<h2 className="mb-2 text-2xl font-bold text-thumbpress-title">
						{ __( 'FIFA World Cup Sale is Live!', 'image-sizes' ) }
					</h2>

					<p className="mb-6 text-thumbpress-body">
						{ __( 'Score up to 48% off ThumbPress Pro during our FIFA World Cup sale. The discount is auto-applied at checkout — grab it before the whistle blows!', 'image-sizes' ) }
					</p>

					{ /* Countdown timer — the focal element */ }
					<div className="mb-6 rounded-xl border-2 border-dashed border-thumbpress-primary bg-thumbpress-primary/5 px-5 py-4">
						<div className="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-thumbpress-primary">
							<Clock size={ 14 } />
							{ expired ? __( 'Offer expired', 'image-sizes' ) : __( 'Offer ends July 19', 'image-sizes' ) }
						</div>
						{ ! expired && (
							<div className="flex items-center justify-center gap-2 text-thumbpress-primary">
								{ units.map( ( unit, i ) => (
									<React.Fragment key={ unit.label }>
										{ i > 0 && <span className="pb-4 text-2xl font-bold">:</span> }
										<div className="flex flex-col items-center">
											<span className="min-w-14 rounded-lg bg-white px-3 py-2 text-3xl font-extrabold tabular-nums shadow-sm">
												{ unit.value }
											</span>
											<span className="mt-1 text-[10px] font-semibold uppercase tracking-wide text-thumbpress-primary/70">
												{ unit.label }
											</span>
										</div>
									</React.Fragment>
								) ) }
							</div>
						) }
					</div>

					<button
						onClick={ handleGetDiscount }
						disabled={ expired }
						className="w-full rounded-md bg-thumbpress-primary py-3 text-base font-medium text-white transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
					>
						{ __( 'Get 48% Off — View Pricing →', 'image-sizes' ) }
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
