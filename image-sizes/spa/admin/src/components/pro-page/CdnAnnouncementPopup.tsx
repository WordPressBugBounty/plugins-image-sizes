import React from 'react';
import { __ } from '@wordpress/i18n';
import { X, Globe, Check } from 'lucide-react';

interface Props {
	/** The X button — the only way to close, persisted server-side. */
	onDismiss: () => void;
	onEnable: () => void;
}

const LEARN_MORE_URL = 'https://thumbpress.co/features/image-cdn';

const BENEFITS = [
	__( 'Images served from the edge location nearest each visitor, not from your server', 'image-sizes' ),
	__( 'Faster page loads and better Core Web Vitals, everywhere in the world', 'image-sizes' ),
	__( 'Image traffic moves off your host, so your server stays fast under load', 'image-sizes' ),
	__( 'Up to 100GB of CDN storage in every Pro plan, at no extra cost', 'image-sizes' ),
];

/**
 * One-time "Meet ThumbPress CDN" announcement, shown on the first ThumbPress
 * admin page load after the CDN release. Closes only on the X or the CTA, never
 * on a backdrop click, and the dismissal is persisted
 * (thumbpress_cdn_announcement_dismissed) so it never reappears.
 */
export default function CdnAnnouncementPopup( { onDismiss, onEnable }: Props ) {
	return (
		<div className="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 p-4">
			<div className="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white">
				<button
					onClick={ onDismiss }
					className="absolute right-6 top-6 rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
					aria-label={ __( 'Close', 'image-sizes' ) }
				>
					<X size={ 20 } />
				</button>

				<div className="px-12 py-10 text-center">
					{ /* Icon + label share one pill so the header costs a single line. */ }
					<span className="mb-4 inline-flex items-center gap-2 rounded-full bg-thumbpress-primary/10 py-1.5 pl-2 pr-3.5">
						<span className="flex h-6 w-6 items-center justify-center rounded-full bg-white">
							<Globe className="text-thumbpress-primary" size={ 15 } />
						</span>
						<span className="text-xs font-bold tracking-wide text-thumbpress-primary">
							{ __( 'A BIG UPDATE', 'image-sizes' ) }
						</span>
					</span>

					<h2 className="mb-3 text-3xl font-bold text-thumbpress-title">
						{ __( 'Meet ThumbPress CDN', 'image-sizes' ) }
					</h2>

					<p className="mx-auto mb-7 max-w-xl text-base text-[#6d6d6d]">
						{ __( 'Your images now load from a global edge network instead of your own server — the biggest speed win ThumbPress has shipped yet.', 'image-sizes' ) }{ ' ' }
						<a
							href={ LEARN_MORE_URL }
							target="_blank"
							rel="noopener noreferrer"
							className="font-medium text-thumbpress-primary underline underline-offset-2 transition-opacity hover:opacity-80"
						>
							{ __( 'Learn more', 'image-sizes' ) }
						</a>
					</p>

					<ul className="mx-auto mb-8 max-w-xl space-y-3.5 text-left">
						{ BENEFITS.map( ( benefit ) => (
							<li key={ benefit } className="flex items-start gap-3">
								<span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-thumbpress-primary/10">
									<Check className="text-thumbpress-primary" size={ 13 } strokeWidth={ 3 } />
								</span>
								<span className="text-base text-[#3C3C42]">{ benefit }</span>
							</li>
						) ) }
					</ul>

					<button
						onClick={ onEnable }
						className="w-full rounded-lg bg-[#ff6600] py-3.5 text-base font-medium text-white transition-opacity hover:opacity-90"
					>
						{ __( 'Enable CDN', 'image-sizes' ) }
					</button>
				</div>
			</div>
		</div>
	);
}
