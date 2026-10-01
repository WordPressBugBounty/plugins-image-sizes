import React, { useEffect, useRef } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { X, LayoutDashboard, ScanSearch, Stamp, Sparkles, type LucideIcon } from 'lucide-react';
import type { WhatsNewItem } from '../../types';

interface Props {
	/** The announced release, e.g. "6.9.0". */
	version: string;
	/** The unseen items, newest release first (app/Config/whats-new.php). */
	items: WhatsNewItem[];
	onDismiss: () => void;
	/** Dismiss for good, then go there. */
	onGo: ( path: string ) => void;
}

/** The icon names whats-new.php may use. An unknown name falls back to the sparkle. */
const ICONS: Record< string, LucideIcon > = {
	dashboard: LayoutDashboard,
	checkup: ScanSearch,
	watermark: Stamp,
};

/**
 * One-time "What's new" popup. It closes on the X, on "Got it" and on Escape, never on a click outside
 * (a stray click must not use up the only showing), and every close is remembered by the server.
 */
export default function WhatsNewPopup( { version, items, onDismiss, onGo }: Props ) {
	const dialog = useRef<HTMLDivElement>( null );
	// 6.9.2 -> 6.9: the release, not the patch.
	const label = version.split( '.' ).slice( 0, 2 ).join( '.' );

	useEffect( () => {
		// Focus the dialog, not a button: Escape works and screen readers announce it, without a focus ring.
		dialog.current?.focus();

		const onKey = ( e: KeyboardEvent ) => {
			if ( 'Escape' === e.key ) {
				onDismiss();
			}
		};

		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [] );

	return (
		<div className="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 p-4">
			<div ref={ dialog } tabIndex={ -1 } role="dialog" aria-modal="true" aria-labelledby="thumbpress-whats-new-title" className="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white outline-none">
				<button
					type="button"
					onClick={ onDismiss }
					className="absolute right-6 top-6 cursor-pointer rounded-full border-0 bg-transparent p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
					aria-label={ __( 'Close', 'image-sizes' ) }
				>
					<X size={ 20 } />
				</button>

				<div className="px-10 py-10">
					<div className="text-center">
						<span className="mb-4 inline-flex items-center rounded-full bg-thumbpress-primary/10 px-3.5 py-1.5 text-xs font-bold tracking-wide text-thumbpress-primary">
							{ __( 'WHAT’S NEW', 'image-sizes' ) }
						</span>
						<h2 id="thumbpress-whats-new-title" className="mb-2 text-3xl font-bold text-thumbpress-title">
							{ sprintf(
								/* translators: %s: version, e.g. 6.9 */
								__( 'New in ThumbPress %s', 'image-sizes' ),
								label,
							) }
						</h2>
						<p className="mx-auto mb-8 max-w-xl text-base text-[#6d6d6d]">
							{ __( 'Here is what changed since you last updated.', 'image-sizes' ) }
						</p>
					</div>

					<ul className="m-0 mb-8 list-none space-y-4 p-0">
						{ items.map( ( item ) => {
							const Icon = ICONS[ item.icon ] || Sparkles;
							return (
								<li key={ item.key } className="flex items-start gap-4 rounded-2xl border border-[#E2E8F0] p-5">
									<span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#F0EBFF] text-thumbpress-primary">
										<Icon size={ 22 } strokeWidth={ 1.75 } />
									</span>
									<div className="min-w-0 flex-1">
										<h3 className="m-0 mb-1 flex items-center gap-2 text-base font-semibold text-thumbpress-title">
											{ item.title }
											{ item.pro && (
												<span className="rounded-full bg-thumbpress-pro-yellow px-2 py-px text-xs font-bold text-thumbpress-title">
													{ __( 'PRO', 'image-sizes' ) }
												</span>
											) }
										</h3>
										<p className="m-0 text-sm leading-relaxed text-[#64748B]">{ item.description }</p>
										{ item.to && item.cta && (
											<button
												type="button"
												onClick={ () => onGo( item.to as string ) }
												className="mt-2 cursor-pointer border-0 bg-transparent p-0 text-sm font-semibold text-thumbpress-primary underline underline-offset-2"
											>
												{ item.cta }
											</button>
										) }
									</div>
								</li>
							);
						} ) }
					</ul>

					<button
							type="button"
						onClick={ onDismiss }
						className="w-full cursor-pointer rounded-lg border-0 bg-thumbpress-primary py-3.5 text-base font-medium text-white transition-opacity hover:opacity-90"
					>
						{ __( 'Got it', 'image-sizes' ) }
					</button>
				</div>
			</div>
		</div>
	);
}
