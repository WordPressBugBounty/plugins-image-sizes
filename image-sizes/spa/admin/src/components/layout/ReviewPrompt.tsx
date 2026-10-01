import React, { useEffect, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { X, Heart, Star } from 'lucide-react';
import { saveReviewAnswer, sendReviewFeedback } from '../../api';

const REVIEW_URL = 'https://wordpress.org/support/plugin/image-sizes/reviews/#new-post';

type Step = 'ask' | 'happy' | 'unhappy' | 'thanks';

interface Props {
	/** Pre-filled reply address; the admin can change it. */
	email: string;
	onClose: () => void;
}

/**
 * Asks once, in a corner card, a while after install, whether ThumbPress is working out. Happy goes to WordPress.org;
 * unhappy can tell us privately, or still leave a public review. Closing it (X, Escape) counts as
 * "Maybe later": the server snoozes it, and a second "later" ends it for good.
 */
export default function ReviewPrompt( { email, onClose }: Props ) {
	const card = useRef<HTMLDivElement>( null );
	const [ step, setStep ] = useState< Step >( 'ask' );
	const [ message, setMessage ] = useState( '' );
	const [ reply, setReply ] = useState( email );
	const [ sending, setSending ] = useState( false );
	const [ error, setError ] = useState( '' );

	const later = () => {
		// "thanks" means the server already has the outcome; closing must not snooze over it.
		if ( step !== 'thanks' ) {
			saveReviewAnswer( 'later' ).catch( () => {} );
		}
		onClose();
	};

	useEffect( () => {
		card.current?.focus();

		const onKey = ( e: KeyboardEvent ) => {
			if ( 'Escape' === e.key ) {
				later();
			}
		};

		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ step ] );

	const leaveReview = () => {
		saveReviewAnswer( 'reviewed' ).catch( () => {} );
		window.open( REVIEW_URL, '_blank', 'noopener,noreferrer' );
		setStep( 'thanks' );
	};

	const never = () => {
		saveReviewAnswer( 'never' ).catch( () => {} );
		onClose();
	};

	const send = async () => {
		setSending( true );
		setError( '' );

		try {
			await sendReviewFeedback( message, reply );
			setStep( 'thanks' );
		} catch ( e: any ) {
			setError( e?.message || __( 'Feedback could not be sent right now. Please try again.', 'image-sizes' ) );
		} finally {
			setSending( false );
		}
	};

	const primary = 'cursor-pointer rounded-lg border-0 bg-thumbpress-primary px-5 py-3 text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50';
	const secondary = 'cursor-pointer rounded-lg border border-solid border-[#E2E8F0] bg-white px-5 py-3 text-sm font-medium text-thumbpress-title transition-colors hover:bg-gray-50';
	const link = 'cursor-pointer border-0 bg-transparent p-0 text-sm text-[#64748B] underline underline-offset-2';

	return (
		<div
			ref={ card }
			tabIndex={ -1 }
			role="dialog"
			aria-labelledby="thumbpress-review-title"
			className="fixed bottom-6 right-6 z-[99998] w-[380px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border-2 border-solid border-thumbpress-primary bg-white p-6 pt-7 shadow-[0_20px_50px_rgba(64,24,157,0.3)] outline-none"
		>
			<div aria-hidden="true" className="absolute inset-x-0 top-0 h-1.5 bg-thumbpress-pro-yellow" />
			<button
				type="button"
				onClick={ step === 'thanks' ? onClose : later }
				className="absolute right-4 top-5 cursor-pointer rounded-full border-0 bg-transparent p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
				aria-label={ __( 'Close', 'image-sizes' ) }
			>
				<X size={ 20 } />
			</button>

			{ step === 'ask' && (
				<>
					<span className="mb-3 inline-flex items-center rounded-full bg-thumbpress-primary/10 px-3 py-1 text-xs font-bold tracking-wide text-thumbpress-primary">
						{ __( 'QUICK QUESTION', 'image-sizes' ) }
					</span>
					<h3 id="thumbpress-review-title" className="m-0 mb-2 pr-8 text-lg font-bold text-thumbpress-title">
						{ __( 'Is ThumbPress working well for you?', 'image-sizes' ) }
					</h3>
					<p className="m-0 mb-5 text-sm leading-relaxed text-[#64748B]">
						{ __( 'It only takes a second, and it helps us a lot.', 'image-sizes' ) }
					</p>
					<div className="flex gap-2">
						<button type="button" className={ primary } onClick={ () => setStep( 'happy' ) }>{ __( 'Yes, I like it', 'image-sizes' ) }</button>
						<button type="button" className={ secondary } onClick={ () => setStep( 'unhappy' ) }>{ __( 'Not really', 'image-sizes' ) }</button>
					</div>
					<div className="mt-3 flex justify-between">
						<button type="button" className={ link } onClick={ later }>{ __( 'Maybe later', 'image-sizes' ) }</button>
						<button type="button" className={ link } onClick={ never }>{ __( 'Don’t ask again', 'image-sizes' ) }</button>
					</div>
				</>
			) }

			{ step === 'happy' && (
				<>
					<h3 id="thumbpress-review-title" className="m-0 mb-2 flex items-center gap-2 pr-8 text-lg font-bold text-thumbpress-title">
						<Star size={ 22 } className="text-thumbpress-pro-yellow" fill="currentColor" />
						{ __( 'That’s great to hear!', 'image-sizes' ) }
					</h3>
					<p className="m-0 mb-5 text-sm leading-relaxed text-[#64748B]">
						{ __( 'Would you share it with others in a quick review on WordPress.org? It helps other site owners find ThumbPress.', 'image-sizes' ) }
					</p>
					<button type="button" className={ `${ primary } w-full` } onClick={ leaveReview }>{ __( 'Leave a review', 'image-sizes' ) }</button>
					<div className="mt-3">
						<button type="button" className={ link } onClick={ later }>{ __( 'Maybe later', 'image-sizes' ) }</button>
					</div>
				</>
			) }

			{ step === 'unhappy' && (
				<>
					<h3 id="thumbpress-review-title" className="m-0 mb-2 pr-8 text-lg font-bold text-thumbpress-title">
						{ __( 'Sorry to hear that. What went wrong?', 'image-sizes' ) }
					</h3>
					<p className="m-0 mb-4 text-base leading-relaxed text-[#64748B]">
						{ __( 'Tell us and we’ll do our best to fix it.', 'image-sizes' ) }
					</p>
					<textarea
						value={ message }
						onChange={ ( e ) => setMessage( e.target.value ) }
						maxLength={ 2000 }
						rows={ 4 }
						placeholder={ __( 'What could be better?', 'image-sizes' ) }
						className="mb-2 w-full resize-none rounded-lg border border-solid border-[#E2E8F0] p-3 text-sm text-thumbpress-title"
					/>
					<input
						type="email"
						value={ reply }
						onChange={ ( e ) => setReply( e.target.value ) }
						placeholder={ __( 'Your email, so we can reply', 'image-sizes' ) }
						className="mb-2 w-full rounded-lg border border-solid border-[#E2E8F0] p-3 text-sm text-thumbpress-title"
					/>
					<p className="m-0 mb-3 text-xs leading-relaxed text-[#94A3B8]">
						{ __( 'Sending shares this message, your email, your name and your site address with the ThumbPress team. Nothing is sent unless you click Send.', 'image-sizes' ) }
					</p>
					{ error && <p role="alert" className="m-0 mb-3 text-xs text-[#FF3A52]">{ error }</p> }
					<div className="flex gap-2">
						<button type="button" className={ primary } disabled={ sending || ! message.trim() || ! reply.trim() } onClick={ send }>
							{ sending ? __( 'Sending…', 'image-sizes' ) : __( 'Send feedback', 'image-sizes' ) }
						</button>
						<button type="button" className={ secondary } onClick={ later }>{ __( 'Maybe later', 'image-sizes' ) }</button>
					</div>
					<div className="mt-3">
						<button type="button" className={ link } onClick={ leaveReview }>{ __( 'Leave a public review instead', 'image-sizes' ) }</button>
					</div>
				</>
			) }

			{ step === 'thanks' && (
				<>
					<h3 id="thumbpress-review-title" className="m-0 mb-2 flex items-center gap-2 pr-8 text-lg font-bold text-thumbpress-title">
						<Heart size={ 22 } className="text-thumbpress-primary" fill="currentColor" />
						{ __( 'Thank you!', 'image-sizes' ) }
					</h3>
					<p className="m-0 text-base leading-relaxed text-[#64748B]">
						{ __( 'We really appreciate it.', 'image-sizes' ) }
					</p>
				</>
			) }
		</div>
	);
}
