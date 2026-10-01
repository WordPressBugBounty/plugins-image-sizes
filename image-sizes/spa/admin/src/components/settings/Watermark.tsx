import React, { useEffect, useRef, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { toast } from 'sonner';
import { Switch } from '../ui/switch';
import { Checkbox } from '../ui/checkbox';
import ProAlert from '../ui/pro-alert';
import { getWatermarkSampleImage } from '../../api';
import { renderWatermarkPreview, type WatermarkPosition } from '../../lib/watermark-preview';

const GRID: WatermarkPosition[] = [ 'tl', 'tc', 'tr', 'ml', 'c', 'mr', 'bl', 'bc', 'br' ];

const DEFAULTS = {
	enabled: false,
	apply_on_upload: true,
	type: 'text' as 'text' | 'image',
	text: window.THUMBPRESS?.site_name || '',
	font: 'sans-bold' as 'sans' | 'sans-bold',
	color: '#ffffff',
	shadow: true,
	position: 'br' as WatermarkPosition,
	size: 20,
	opacity: 60,
	margin: 20,
	rotation: 0,
	sizes: [ 'full', 'large', 'medium_large' ],
	mimes: [ 'image/jpeg', 'image/png', 'image/webp' ],
	min_width: 400,
	min_height: 300,
};

type Values = typeof DEFAULTS;

const fieldClass = '!w-24 text-center flex !rounded-lg !border !border-thumbpress-border bg-white !px-3 !py-2 text-sm focus:!outline-none focus:!shadow-none';

function loadImage( url: string ): Promise<HTMLImageElement | null> {
	return new Promise( ( resolve ) => {
		if ( ! url ) {
			resolve( null );
			return;
		}
		const img = new Image();
		img.crossOrigin = 'anonymous';
		img.onload = () => resolve( img );
		img.onerror = () => resolve( null );
		img.src = url;
	} );
}

/**
 * Free Watermark settings.
 *
 * Every option can be changed and the preview redraws as you go, so the feature can be judged before
 * buying. Nothing is saved and nothing is applied: Save, Reset-to-saved and the Watermark page belong
 * to Pro and open the upsell. Pro replaces this tab's content (same `watermark` slug) once its
 * licence is active.
 */
export default function Watermark() {
	const [ alertOpen, setAlertOpen ] = useState( false );
	const openAlert = () => setAlertOpen( true );

	const [ v, setV ] = useState<Values>( DEFAULTS );
	const [ logoUrl, setLogoUrl ] = useState( '' );
	const [ sample, setSample ] = useState<HTMLImageElement | null>( null );
	const [ sampleReady, setSampleReady ] = useState( false );
	const [ params, setParams ] = useSearchParams();
	const previewBox = useRef<HTMLDivElement>( null );
	const scrollToPreview = useRef( false );
	const [ logo, setLogo ] = useState<HTMLImageElement | null>( null );
	const [ preview, setPreview ] = useState( '' );
	const [ previewing, setPreviewing ] = useState( false );

	const isProActive = window.THUMBPRESS?.pro_active;

	const set = <K extends keyof Values>( key: K, value: Values[ K ] ) => setV( ( cur ) => ( { ...cur, [ key ]: value } ) );
	const toggle = ( key: 'sizes' | 'mimes', value: string ) => setV( ( cur ) => ( {
		...cur,
		[ key ]: cur[ key ].includes( value ) ? cur[ key ].filter( ( x ) => x !== value ) : [ ...cur[ key ], value ],
	} ) );

	useEffect( () => {
		getWatermarkSampleImage().then( loadImage ).then( ( img ) => {
			setSample( img );
			setSampleReady( true );
		} );
	}, [] );

	useEffect( () => {
		loadImage( logoUrl ).then( setLogo );
	}, [ logoUrl ] );

	const showPreview = () => {
		setPreviewing( true );
		try {
			const canvas = document.createElement( 'canvas' );
			const drawn = renderWatermarkPreview( canvas, sample, logo, v );

			if ( ! drawn ) {
				toast.error( 'image' === v.type ? __( 'Choose a logo to see it on the image.', 'image-sizes' ) : __( 'Type some text to see it on the image.', 'image-sizes' ) );
				return;
			}

			setPreview( canvas.toDataURL( 'image/jpeg', 0.85 ) );
		} catch {
			toast.error( __( 'The preview could not be created.', 'image-sizes' ) );
		} finally {
			setPreviewing( false );
		}
	};

	// "Preview your watermark" on the Watermark page lands here with ?preview=1: draw it once the sample is
	// ready, bring it into view (it sits below the long form), and drop the flag so a reload does not redo it.
	useEffect( () => {
		if ( '1' !== params.get( 'preview' ) || ! sampleReady ) {
			return;
		}

		scrollToPreview.current = true;
		showPreview();

		const next = new URLSearchParams( params );
		next.delete( 'preview' );
		setParams( next, { replace: true } );
	}, [ params, sampleReady ] );

	useEffect( () => {
		if ( preview && scrollToPreview.current ) {
			scrollToPreview.current = false;
			previewBox.current?.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		}
	}, [ preview ] );

	// The logo is chosen from the Media Library, like Pro's. It only feeds the in-browser preview here.
	const pickLogo = () => {
		const wpGlobal = ( window as any ).wp;

		if ( ! wpGlobal?.media ) {
			return;
		}

		const frame = wpGlobal.media( {
			title: __( 'Choose watermark logo', 'image-sizes' ),
			library: { type: [ 'image/png', 'image/jpeg', 'image/webp' ] },
			multiple: false,
		} );

		frame.on( 'select', () => {
			const picked = frame.state().get( 'selection' ).first().toJSON();
			setLogoUrl( picked.sizes?.medium?.url || picked.url );
		} );

		frame.open();
	};

	const proBadge = ! isProActive && (
		<button
			type="button"
			onClick={ openAlert }
			className="inline-flex items-center gap-1 px-2 py-1 rounded text-[8px] bg-thumbpress-pro-yellow text-thumbpress-title cursor-pointer"
		>
			<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><g clipPath="url(#a)"><path d="M8.357 8.42H1.431a.494.494 0 0 0 0 .989H8.357a.494.494 0 0 0 0-.99Z" fill="#1C1C1C"/><path d="M9.097 2.055a.494.494 0 0 0-.543.033L6.959 3.285 5.28 1.186A.494.494 0 0 0 4.894 1a.494.494 0 0 0-.387.186L2.828 3.285 1.233 2.088a.494.494 0 0 0-.734.453l.495 4.452a.494.494 0 0 0 .491.431h6.926a.494.494 0 0 0 .491-.431l.495-4.452a.494.494 0 0 0-.308-.486ZM7.914 6.442H1.874L1.554 3.566l1.064.798a.494.494 0 0 0 .682-.092l1.593-1.99 1.592 1.99a.494.494 0 0 0 .683.092l1.064-.798-.318 2.876Z" fill="#1C1C1C"/></g><defs><clipPath id="a"><rect width="10" height="10" fill="#fff"/></clipPath></defs></svg>
			{ __( 'Pro', 'image-sizes' ) }
		</button>
	);

	const row = ( title: string, desc: string | undefined, control: React.ReactNode, last = false, badge = false ) => (
		<div className={ `flex items-center justify-between gap-6 py-4 ${ last ? '' : 'border-b border-[#E2E8F0]' }` }>
			<div className="min-w-0">
				<div className="flex items-center gap-2">
					<h4 className="2xl:text-base lg:text-sm font-medium text-thumbpress-title">{ title }</h4>
					{ badge && proBadge }
				</div>
				{ desc && <p className="2xl:text-sm lg:text-xs text-[#64748B] mt-1">{ desc }</p> }
			</div>
			<div className="flex-shrink-0">{ control }</div>
		</div>
	);

	const slider = ( key: 'size' | 'opacity' | 'margin' | 'rotation', min: number, max: number, unit: string ) => (
		<div className="flex items-center gap-3">
			<input type="range" min={ min } max={ max } value={ v[ key ] } onChange={ ( e ) => set( key, Number( e.target.value ) ) } className="w-36 accent-[#40189D]" />
			<span className="w-14 text-right text-sm text-thumbpress-title tabular-nums">{ v[ key ] }{ unit }</span>
		</div>
	);

	const sizeLabels: [ string, string ][] = [
		[ 'full', __( 'Full size (or scaled)', 'image-sizes' ) ],
		[ 'large', __( 'Large', 'image-sizes' ) ],
		[ 'medium_large', __( 'Medium Large', 'image-sizes' ) ],
		[ 'medium', __( 'Medium', 'image-sizes' ) ],
		[ 'thumbnail', __( 'Thumbnail', 'image-sizes' ) ],
	];

	const mimeLabels: [ string, string ][] = [ [ 'image/jpeg', 'JPEG' ], [ 'image/png', 'PNG' ], [ 'image/webp', 'WebP' ] ];

	return (
		<div className="flex flex-col">
			{ row(
				__( 'Enable watermark', 'image-sizes' ),
				__( 'Stamp your name or logo on your images so they stay yours when they are copied.', 'image-sizes' ),
				<Switch checked={ v.enabled } onCheckedChange={ ( c: boolean ) => set( 'enabled', c ) } />,
				false,
				true,
			) }

			{ row(
				__( 'Watermark new uploads', 'image-sizes' ),
				__( 'Mark every image as it is uploaded. The untouched original is kept so you can undo it.', 'image-sizes' ),
				<Switch checked={ v.apply_on_upload } onCheckedChange={ ( c: boolean ) => set( 'apply_on_upload', c ) } />,
			) }

			{ row(
				__( 'Type', 'image-sizes' ),
				__( 'Write text over your images, or place a logo.', 'image-sizes' ),
				<div className="inline-flex rounded-lg border border-thumbpress-border overflow-hidden">
					{ ( [ [ 'text', __( 'Text', 'image-sizes' ) ], [ 'image', __( 'Logo image', 'image-sizes' ) ] ] as [ 'text' | 'image', string ][] ).map( ( [ value, label ] ) => (
						<button key={ value } type="button" onClick={ () => set( 'type', value ) } className={ `px-4 py-2 text-sm cursor-pointer border-0 ${ v.type === value ? 'bg-thumbpress-primary text-white' : 'bg-white text-thumbpress-title' }` }>
							{ label }
						</button>
					) ) }
				</div>,
			) }

			{ 'text' === v.type ? (
				<>
					{ row(
						__( 'Text', 'image-sizes' ),
						__( 'Latin, Greek and Cyrillic letters are supported.', 'image-sizes' ),
						<input type="text" maxLength={ 120 } value={ v.text } onChange={ ( e ) => set( 'text', e.target.value ) } className="!w-64 !rounded-lg !border !border-thumbpress-border bg-white !px-3 !py-2 text-sm focus:!outline-none focus:!shadow-none" />,
					) }
					{ row(
						__( 'Font', 'image-sizes' ),
						__( 'Bold reads best over busy photos.', 'image-sizes' ),
						<select value={ v.font } onChange={ ( e ) => set( 'font', e.target.value as Values[ 'font' ] ) } className="!rounded-lg !border !border-thumbpress-border bg-white !px-3 !py-2 text-sm">
							<option value="sans-bold">{ __( 'Sans, bold', 'image-sizes' ) }</option>
							<option value="sans">{ __( 'Sans, regular', 'image-sizes' ) }</option>
						</select>,
					) }
					{ row(
						__( 'Colour', 'image-sizes' ),
						__( 'White suits most photos; choose a darker shade for light images.', 'image-sizes' ),
						<div className="flex items-center gap-2">
							<input type="color" value={ v.color } onChange={ ( e ) => set( 'color', e.target.value ) } className="h-9 w-12 cursor-pointer rounded border border-thumbpress-border bg-white p-1" />
							<input type="text" value={ v.color } onChange={ ( e ) => set( 'color', e.target.value ) } className={ fieldClass } />
						</div>,
					) }
					{ row(
						__( 'Shadow', 'image-sizes' ),
						__( 'A soft outline so the text stays readable on light and dark photos.', 'image-sizes' ),
						<Switch checked={ v.shadow } onCheckedChange={ ( c: boolean ) => set( 'shadow', c ) } />,
					) }
				</>
			) : row(
				__( 'Logo', 'image-sizes' ),
				__( 'PNG with a transparent background works best.', 'image-sizes' ),
				<div className="flex items-center gap-3">
					{ logoUrl && <img src={ logoUrl } alt="" className="h-12 max-w-[120px] rounded border border-thumbpress-border bg-[#F1F5F9] object-contain p-1" /> }
					<button type="button" onClick={ pickLogo } className="px-4 py-2 rounded-lg border border-thumbpress-primary text-thumbpress-primary text-sm font-medium bg-white cursor-pointer">
						{ logoUrl ? __( 'Change', 'image-sizes' ) : __( 'Choose logo', 'image-sizes' ) }
					</button>
					{ logoUrl && (
						<button type="button" onClick={ () => setLogoUrl( '' ) } className="text-sm text-[#64748B] underline bg-transparent border-0 cursor-pointer">
							{ __( 'Remove', 'image-sizes' ) }
						</button>
					) }
				</div>,
			) }

			{ row(
				__( 'Position', 'image-sizes' ),
				__( 'Where the mark sits, or repeat it across the whole image.', 'image-sizes' ),
				<div className="flex items-center gap-4">
					<div className={ `grid grid-cols-3 gap-1 ${ 'tile' === v.position ? 'opacity-40' : '' }` }>
						{ GRID.map( ( pos ) => (
							<button key={ pos } type="button" aria-label={ pos } onClick={ () => set( 'position', pos ) } className={ `h-7 w-7 rounded border cursor-pointer ${ v.position === pos ? 'bg-thumbpress-primary border-thumbpress-primary' : 'bg-white border-thumbpress-border' }` } />
						) ) }
					</div>
					<button type="button" onClick={ () => set( 'position', 'tile' ) } className={ `px-3 py-2 rounded-lg border text-sm cursor-pointer ${ 'tile' === v.position ? 'bg-thumbpress-primary text-white border-thumbpress-primary' : 'bg-white text-thumbpress-title border-thumbpress-border' }` }>
						{ __( 'Tile', 'image-sizes' ) }
					</button>
				</div>,
			) }

			{ row( __( 'Size', 'image-sizes' ), __( 'Width of the mark as a share of the image width.', 'image-sizes' ), slider( 'size', 3, 100, '%' ) ) }
			{ row( __( 'Opacity', 'image-sizes' ), __( 'How see-through the mark is. Lower values are subtler.', 'image-sizes' ), slider( 'opacity', 5, 100, '%' ) ) }
			{ row( __( 'Margin', 'image-sizes' ), __( 'Distance from the edge, or the gap between tiles.', 'image-sizes' ), slider( 'margin', 0, 200, 'px' ) ) }
			{ row( __( 'Rotation', 'image-sizes' ), __( 'Tilt the mark from -90° to 90°; positive values turn it counter-clockwise. An angled mark is harder to crop out.', 'image-sizes' ), slider( 'rotation', -90, 90, '°' ) ) }

			{ row(
				__( 'Image sizes to mark', 'image-sizes' ),
				__( 'Small thumbnails are usually better left clean.', 'image-sizes' ),
				<div className="grid grid-cols-2 gap-x-6 gap-y-2 max-w-[360px]">
					{ sizeLabels.map( ( [ name, label ] ) => (
						<label key={ name } className="flex items-center gap-2 text-sm text-thumbpress-title cursor-pointer">
							<Checkbox checked={ v.sizes.includes( name ) } onCheckedChange={ () => toggle( 'sizes', name ) } />
							{ label }
						</label>
					) ) }
				</div>,
			) }

			{ row(
				__( 'Image types', 'image-sizes' ),
				__( 'GIF and AVIF are never marked.', 'image-sizes' ),
				<div className="flex items-center gap-5">
					{ mimeLabels.map( ( [ mime, label ] ) => (
						<label key={ mime } className="flex items-center gap-2 text-sm text-thumbpress-title cursor-pointer">
							<Checkbox checked={ v.mimes.includes( mime ) } onCheckedChange={ () => toggle( 'mimes', mime ) } />
							{ label }
						</label>
					) ) }
				</div>,
			) }

			{ row(
				__( 'Skip small images', 'image-sizes' ),
				__( 'Images narrower or shorter than this are left alone.', 'image-sizes' ),
				<div className="flex items-center gap-2 text-sm text-[#64748B]">
					<input type="number" min={ 0 } value={ v.min_width } onChange={ ( e ) => set( 'min_width', Number( e.target.value ) || 0 ) } className={ fieldClass } />
					×
					<input type="number" min={ 0 } value={ v.min_height } onChange={ ( e ) => set( 'min_height', Number( e.target.value ) || 0 ) } className={ fieldClass } />
					px
				</div>,
				true,
			) }

			<div className="flex items-center justify-end gap-4 pt-6">
				<button onClick={ showPreview } disabled={ previewing } className="px-6 py-2.5 rounded-lg border border-thumbpress-primary text-thumbpress-primary text-sm font-medium bg-white hover:bg-thumbpress-primary/5 transition-colors disabled:opacity-50 cursor-pointer">
					{ previewing ? __( 'Drawing…', 'image-sizes' ) : __( 'Preview', 'image-sizes' ) }
				</button>
				<button onClick={ () => { setV( DEFAULTS ); setLogoUrl( '' ); } } className="px-8 py-2.5 rounded-lg border border-thumbpress-primary text-thumbpress-primary text-sm font-medium bg-white hover:bg-thumbpress-primary/5 transition-colors cursor-pointer">
					{ __( 'Reset Options', 'image-sizes' ) }
				</button>
				<button onClick={ openAlert } className="px-8 py-2.5 rounded-lg bg-thumbpress-primary text-white text-sm font-medium hover:bg-purple-800 transition-colors cursor-pointer border-0">
					{ __( 'Save Changes', 'image-sizes' ) }
				</button>
			</div>

			{ preview && (
				<div ref={ previewBox } className="mt-6 rounded-xl border border-[#E2E8F0] p-3">
					<p className="text-xs text-[#64748B] mb-2">
						{ sample
							? __( 'Preview on your most recent image. Nothing has been changed. Approximate: Pro draws the real watermark on your server.', 'image-sizes' )
							: __( 'Preview on a sample image. Nothing has been changed. Approximate: Pro draws the real watermark on your server.', 'image-sizes' ) }
					</p>
					<img src={ preview } alt="" className="w-full rounded-lg" />
				</div>
			) }

			{ ! isProActive && (
				<ProAlert
					title={ __( 'Saving a watermark is a Pro feature', 'image-sizes' ) }
					description={ __( 'You have seen how it looks. Unlock Pro to save these settings, watermark every new upload automatically, and apply it to your whole library in one run. The original is always kept, so you can undo it any time.', 'image-sizes' ) }
					buttonText={ __( 'Upgrade to protect your images', 'image-sizes' ) }
					open={ alertOpen }
					onClose={ () => setAlertOpen( false ) }
				/>
			) }
		</div>
	);
}
