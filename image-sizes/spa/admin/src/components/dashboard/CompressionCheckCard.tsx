import React, { useEffect, useRef, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { CompressedImagesIcon } from '../icons';
import LockedFeatureCard from './LockedFeatureCard';
import CompressionCheckModal from './CompressionCheckModal';
import {
	startCompressionCheck,
	stepCompressionCheck,
	discardCompressionCheck,
	type CompressionCheckResult,
	type CompressionCheckSample,
} from '../../api';
import { numberFormat, formatBytes, percentFormat, dateFormat } from '../../lib/i18n';
import { COMPRESSION_CHECK_PRO_LINK, compressionCheckCtaLabel, compressionHealthGain, leadsWithPercent } from './compression-check';

const CompressionCheckCard = ( { notCompressed, totalImages, scanned, scanning, onScan, initialResult }: {
	notCompressed: number;
	totalImages: number;
	scanned: boolean;
	scanning: boolean;
	onScan: () => void;
	initialResult: CompressionCheckResult | null;
} ) => {
	const [ result, setResult ] = useState< CompressionCheckResult | null >( initialResult );
	const [ samples, setSamples ] = useState< CompressionCheckSample[] | null >( null );
	const [ progress, setProgress ] = useState< { index: number; planned: number } | null >( null );
	const mounted = useRef( true );

	// Previews live only as long as the screen shows them, and a check stops when the screen goes.
	useEffect( () => {
		mounted.current = true;

		return () => {
			mounted.current = false;
			discardCompressionCheck().catch( () => {} );
		};
	}, [] );

	const closeModal = () => {
		setSamples( null );
		discardCompressionCheck().catch( () => {} );
	};

	const runCheck = async () => {
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
			}
		} catch ( error: any ) {
			toast.error( error?.data?.message || error?.message || __( 'The check could not finish. Try again.', 'image-sizes' ) );
		} finally {
			setProgress( null );
		}
	};

	const title = __( 'Uncompressed Images', 'image-sizes' );

	// The estimate is extrapolated from the scan's file sizes, so it asks for the scan first.
	if ( ! scanned && ! result ) {
		return (
			<LockedFeatureCard
				icon={ <CompressedImagesIcon /> }
				title={ title }
				description={ __( 'Images need compression', 'image-sizes' ) }
				needsScan
				scanning={ scanning }
				onScan={ onScan }
			/>
		);
	}

	const running = progress !== null;
	const runLabel = progress && progress.planned > 0
		? sprintf(
			/* translators: 1: number of the image being tested, 2: number of images the check tests. */
			__( 'Testing image %1$s of %2$s…', 'image-sizes' ),
			numberFormat( progress.index + 1 ),
			numberFormat( progress.planned ),
		)
		: __( 'Starting…', 'image-sizes' );

	const modal = samples && result && (
		<CompressionCheckModal
			result={ result }
			samples={ samples }
			healthGain={ compressionHealthGain( notCompressed, totalImages ) }
			onClose={ closeModal }
		/>
	);

	if ( ! result || running ) {
		return (
			<div className="bg-white rounded-xl border border-gray-100 2xl:p-6 lg:p-4">
				<div className="flex items-center gap-2 mb-3">
					<CompressedImagesIcon />
					<span className="2xl:text-lg lg:text-sm text-thumbpress-title">{ title }</span>
				</div>

				<button
					onClick={ runCheck }
					disabled={ running }
					className="mb-2 px-5 py-2 rounded-lg bg-thumbpress-primary text-white text-sm font-medium hover:opacity-90 transition-opacity cursor-pointer disabled:opacity-50 disabled:cursor-default"
				>
					{ running ? runLabel : __( 'Check my images', 'image-sizes' ) }
				</button>

				<p className="2xl:text-base lg:text-sm text-[#777980]">
					{ running
						? __( 'Testing copies. Your library is not changed.', 'image-sizes' )
						: sprintf(
							/* translators: %s: number of images not compressed yet. */
							_n( '%s image not compressed yet. See what compression would save, tested on copies.', '%s images not compressed yet. See what compression would save, tested on copies.', notCompressed, 'image-sizes' ),
							numberFormat( notCompressed ),
						) }
				</p>
				{ modal }
			</div>
		);
	}

	return (
		<div className="bg-white rounded-xl border border-gray-100 2xl:p-6 lg:p-4">
			<div className="flex items-center gap-2 mb-3">
				<CompressedImagesIcon />
				<span className="2xl:text-lg lg:text-sm text-thumbpress-title">{ title }</span>
			</div>

			<div className="flex mb-1 gap-5 items-center">
				<p className="2xl:text-3xl lg:text-2xl font-medium text-thumbpress-title">
					{ leadsWithPercent( result.saved_bytes )
						? percentFormat( result.saved_pct )
						: sprintf(
							/* translators: %s: estimated size compression would save, e.g. "640 MB". */
							__( '~%s', 'image-sizes' ),
							formatBytes( result.saved_bytes, true ),
						) }
				</p>
				<a href={ COMPRESSION_CHECK_PRO_LINK } className="!text-[#FB8005] border-b border-[#FB8005] text-sm">
					{ compressionCheckCtaLabel() }
				</a>
			</div>

			<p className="2xl:text-base lg:text-sm text-[#777980]">
				{ leadsWithPercent( result.saved_bytes )
					? __( 'smaller images with Pro, plus every thumbnail and new upload', 'image-sizes' )
					: sprintf(
						/* translators: 1: number of images, 2: percentage saved, e.g. "35%". */
						_n( 'could be saved on %1$s image (about %2$s)', 'could be saved across %1$s images (about %2$s)', result.image_count, 'image-sizes' ),
						numberFormat( result.image_count ),
						percentFormat( result.saved_pct ),
					) }
			</p>

			<p className="text-xs text-[#777980] mt-2">
				{ sprintf(
					/* translators: %s: date the check ran. */
					__( 'Estimate from %s.', 'image-sizes' ),
					dateFormat( result.measured_at ),
				) }{ ' ' }
				<button onClick={ runCheck } className="text-thumbpress-primary underline cursor-pointer">
					{ __( 'Check again', 'image-sizes' ) }
				</button>
			</p>
			{ modal }
		</div>
	);
};

export default CompressionCheckCard;
