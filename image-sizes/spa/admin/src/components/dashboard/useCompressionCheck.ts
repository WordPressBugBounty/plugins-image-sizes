import { useEffect, useRef, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import {
	startCompressionCheck,
	stepCompressionCheck,
	discardCompressionCheck,
	type CompressionCheckResult,
	type CompressionCheckSample,
} from '../../api';
import { numberFormat } from '../../lib/i18n';

/**
 * Runs the compression check on request and holds what it found.
 *
 * The check compresses throwaway copies, so previews live only as long as the screen shows them and
 * a check stops when the screen goes.
 */
export function useCompressionCheck( initialResult: CompressionCheckResult | null ) {
	const [ result, setResult ] = useState< CompressionCheckResult | null >( initialResult );
	const [ samples, setSamples ] = useState< CompressionCheckSample[] | null >( null );
	const [ progress, setProgress ] = useState< { index: number; planned: number } | null >( null );
	const mounted = useRef( true );

	// The result arrives with a later request than the first paint.
	useEffect( () => {
		if ( initialResult ) {
			setResult( ( current ) => current ?? initialResult );
		}
	}, [ initialResult ] );

	useEffect( () => {
		mounted.current = true;

		return () => {
			mounted.current = false;
			discardCompressionCheck().catch( () => {} );
		};
	}, [] );

	const [ showSamples, setShowSamples ] = useState( false );

	// Hiding the samples keeps them: the previews stay until the screen goes or the next check.
	const closeSamples = () => setShowSamples( false );

	const run = async () => {
		setProgress( { index: 0, planned: 0 } );

		try {
			const start: any = await startCompressionCheck();
			const { token, planned } = start.data;
			let last: any = null;

			for ( let index = 0; index < planned; index++ ) {
				if ( ! mounted.current ) {
					return;
				}

				setProgress( { index, planned } );
				last = await stepCompressionCheck( token, index );
			}

			if ( mounted.current && last?.data?.result ) {
				setResult( last.data.result );
				setSamples( last.data.samples ?? [] );
				setShowSamples( true );
			}
		} catch ( error: any ) {
			toast.error( error?.data?.message || error?.message || __( 'The check could not finish. Try again.', 'image-sizes' ) );
		} finally {
			setProgress( null );
		}
	};

	const running = progress !== null;
	const runLabel = progress && progress.planned > 0
		? sprintf(
			/* translators: 1: number of the image being tested, 2: number of images the check tests. */
			__( 'Testing image %1$s of %2$s…', 'image-sizes' ),
			numberFormat( progress.index + 1 ),
			numberFormat( progress.planned ),
		)
		: __( 'Starting…', 'image-sizes' );

	return {
		result,
		samples,
		running,
		runLabel,
		run,
		showSamples,
		openSamples: () => setShowSamples( true ),
		closeSamples,
	};
}
