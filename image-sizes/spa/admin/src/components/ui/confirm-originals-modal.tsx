import React, { useEffect, useRef } from 'react';
import { __ } from '@wordpress/i18n';
import { AlertTriangle } from 'lucide-react';

interface Props {
	open: boolean;
	/** Label of the confirming button; says what happens, not just "OK". */
	confirmLabel: string;
	onConfirm: () => void;
	onCancel: () => void;
}

const FOCUSABLE = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

/** Asks before a conversion deletes the original files; Cancel, Escape and the backdrop all do nothing. */
export default function ConfirmOriginalsModal( { open, confirmLabel, onConfirm, onCancel }: Props ) {
	const dialog = useRef<HTMLDivElement>( null );
	const cancelRef = useRef<HTMLButtonElement>( null );
	// Callers pass inline arrows, so keep the latest one here and let the effect run only on open/close.
	const cancel = useRef( onCancel );
	cancel.current = onCancel;

	useEffect( () => {
		if ( ! open ) {
			return;
		}

		const opener = document.activeElement as HTMLElement | null;
		cancelRef.current?.focus();

		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) {
				cancel.current();
				return;
			}

			if ( e.key !== 'Tab' || ! dialog.current ) {
				return;
			}

			const items = Array.from( dialog.current.querySelectorAll<HTMLElement>( FOCUSABLE ) );
			const first = items[ 0 ];
			const last = items[ items.length - 1 ];

			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		};
		document.addEventListener( 'keydown', onKey );

		return () => {
			document.removeEventListener( 'keydown', onKey );
			opener?.focus?.();
		};
	}, [ open ] );

	if ( ! open ) {
		return null;
	}

	return (
		<div className="fixed inset-0 z-[99999] flex items-center justify-center p-4">
			<div className="absolute inset-0 bg-black/40" onClick={ onCancel } aria-hidden="true" />
			<div
				ref={ dialog }
				role="dialog"
				aria-modal="true"
				aria-labelledby="tp-originals-title"
				aria-describedby="tp-originals-body"
				className="relative w-full max-w-[480px] rounded-xl bg-white p-6 shadow-xl"
			>
				<div className="flex items-start gap-4">
					<span className="w-10 h-10 rounded-full bg-[#FEF3C7] text-[#B45309] flex items-center justify-center shrink-0">
						<AlertTriangle size={ 20 } strokeWidth={ 1.75 } aria-hidden="true" />
					</span>
					<div>
						<h3 id="tp-originals-title" className="m-0 text-lg font-semibold text-thumbpress-title">
							{ __( 'Original files will be deleted', 'image-sizes' ) }
						</h3>
						<p id="tp-originals-body" className="mt-2 mb-0 text-sm leading-6 text-[#475569]">
							{ __( 'Each image is replaced by its converted copy, and the original files, including every thumbnail size, are deleted. This can\'t be undone. Back up your uploads folder before you continue.', 'image-sizes' ) }
						</p>
					</div>
				</div>
				<div className="flex justify-end gap-3 mt-6">
					<button
						ref={ cancelRef }
						type="button"
						onClick={ onCancel }
						className="px-6 py-2.5 rounded-lg border border-thumbpress-primary bg-white text-thumbpress-primary text-sm font-medium cursor-pointer"
					>
						{ __( 'Cancel', 'image-sizes' ) }
					</button>
					<button
						type="button"
						onClick={ onConfirm }
						className="px-6 py-2.5 rounded-lg border border-thumbpress-primary bg-thumbpress-primary text-white text-sm font-medium cursor-pointer"
					>
						{ confirmLabel }
					</button>
				</div>
			</div>
		</div>
	);
}

/** The same warning as a quiet line under an on-upload switch. */
export function OriginalsInlineWarning() {
	return (
		<p className="2xl:text-sm lg:text-xs text-[#B45309] mt-1">
			{ __( 'The original files are deleted after conversion. This can\'t be undone, so back up your uploads first.', 'image-sizes' ) }
		</p>
	);
}
