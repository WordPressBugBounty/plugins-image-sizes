/**
 * Draws a watermark preview in the browser.
 *
 * This mirrors how Pro places a mark (size as a share of the image width, opacity, rotation, the 3x3
 * grid or tiling) so the free settings screen can show what a choice would look like. It only ever
 * draws on a canvas: nothing is saved, no image leaves the page, and no file is written. Pro draws the
 * real thing on the server with GD, so fonts differ slightly.
 */

export type WatermarkPosition = 'tl' | 'tc' | 'tr' | 'ml' | 'c' | 'mr' | 'bl' | 'bc' | 'br' | 'tile';

export interface WatermarkPreviewSettings {
	type: 'text' | 'image';
	text: string;
	font: 'sans' | 'sans-bold';
	color: string;
	shadow: boolean;
	position: WatermarkPosition;
	size: number;
	opacity: number;
	margin: number;
	rotation: number;
}

const WIDTH = 800;

function hexToRgb( hex: string ): [ number, number, number ] {
	const m = /^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec( hex );

	return m ? [ parseInt( m[ 1 ], 16 ), parseInt( m[ 2 ], 16 ), parseInt( m[ 3 ], 16 ) ] : [ 255, 255, 255 ];
}

function makeCanvas( w: number, h: number ): [ HTMLCanvasElement, CanvasRenderingContext2D ] {
	const canvas = document.createElement( 'canvas' );
	canvas.width = Math.max( 1, Math.ceil( w ) );
	canvas.height = Math.max( 1, Math.ceil( h ) );

	return [ canvas, canvas.getContext( '2d' ) as CanvasRenderingContext2D ];
}

function textOverlay( s: WatermarkPreviewSettings, imageWidth: number ): HTMLCanvasElement | null {
	const text = s.text.trim();

	if ( '' === text ) {
		return null;
	}

	const family = '"DejaVu Sans", "Helvetica Neue", Arial, sans-serif';
	const weight = 'sans-bold' === s.font ? 'bold ' : '';
	const [ , pctx ] = makeCanvas( 1, 1 );

	// Measure at a fixed size, then scale so the text spans the wanted share of the image width.
	pctx.font = `${ weight }100px ${ family }`;
	const probeWidth = Math.max( 1, pctx.measureText( text ).width );
	const points = Math.max( 6, 100 * ( imageWidth * s.size / 100 ) / probeWidth );

	pctx.font = `${ weight }${ points }px ${ family }`;
	const m = pctx.measureText( text );
	const ascent = m.actualBoundingBoxAscent || points * 0.8;
	const descent = m.actualBoundingBoxDescent || points * 0.2;
	const pad = Math.ceil( points * 0.15 );
	const [ canvas, ctx ] = makeCanvas( m.width + pad * 2, ascent + descent + pad * 2 );

	ctx.font = `${ weight }${ points }px ${ family }`;
	ctx.textBaseline = 'alphabetic';

	const [ r, g, b ] = hexToRgb( s.color );

	if ( s.shadow ) {
		const offset = Math.max( 1, Math.round( points / 24 ) );
		const light = ( r + g + b ) / 3 > 128;
		ctx.fillStyle = light ? 'rgba(0,0,0,0.37)' : 'rgba(255,255,255,0.37)';
		ctx.fillText( text, pad + offset, pad + ascent + offset );
	}

	ctx.fillStyle = `rgb(${ r },${ g },${ b })`;
	ctx.fillText( text, pad, pad + ascent );

	return canvas;
}

function logoOverlay( logo: HTMLImageElement | null, s: WatermarkPreviewSettings, imageWidth: number ): HTMLCanvasElement | null {
	if ( ! logo || ! logo.naturalWidth ) {
		return null;
	}

	const w = Math.max( 8, Math.round( imageWidth * s.size / 100 ) );
	const [ canvas, ctx ] = makeCanvas( w, w * ( logo.naturalHeight / logo.naturalWidth ) );
	ctx.drawImage( logo, 0, 0, canvas.width, canvas.height );

	return canvas;
}

function rotated( source: HTMLCanvasElement, degrees: number ): HTMLCanvasElement {
	if ( 0 === degrees ) {
		return source;
	}

	// GD turns positive angles counter-clockwise, the canvas clockwise.
	const rad = ( -degrees * Math.PI ) / 180;
	const w = Math.abs( source.width * Math.cos( rad ) ) + Math.abs( source.height * Math.sin( rad ) );
	const h = Math.abs( source.width * Math.sin( rad ) ) + Math.abs( source.height * Math.cos( rad ) );
	const [ canvas, ctx ] = makeCanvas( w, h );

	ctx.translate( canvas.width / 2, canvas.height / 2 );
	ctx.rotate( rad );
	ctx.drawImage( source, -source.width / 2, -source.height / 2 );

	return canvas;
}

function anchor( position: string, width: number, height: number, ow: number, oh: number, margin: number ): [ number, number ] {
	const col = position[ position.length - 1 ];
	const row = 1 === position.length ? 'm' : position[ 0 ];

	let x = ( width - ow ) / 2;
	if ( 'l' === col ) {
		x = margin;
	} else if ( 'r' === col ) {
		x = width - ow - margin;
	}

	let y = ( height - oh ) / 2;
	if ( 't' === row ) {
		y = margin;
	} else if ( 'b' === row ) {
		y = height - oh - margin;
	}

	return [ Math.max( 0, x ), Math.max( 0, y ) ];
}

/**
 * Paint the base image (or a neutral placeholder) with the watermark on top.
 *
 * @return false when there is nothing to draw yet (empty text, no logo chosen).
 */
export function renderWatermarkPreview(
	canvas: HTMLCanvasElement,
	base: HTMLImageElement | null,
	logo: HTMLImageElement | null,
	s: WatermarkPreviewSettings,
): boolean {
	const ratio = base && base.naturalWidth ? base.naturalHeight / base.naturalWidth : 2 / 3;
	const height = Math.round( WIDTH * ratio );

	canvas.width = WIDTH;
	canvas.height = height;

	const ctx = canvas.getContext( '2d' ) as CanvasRenderingContext2D;

	if ( base && base.naturalWidth ) {
		ctx.drawImage( base, 0, 0, WIDTH, height );
	} else {
		const gradient = ctx.createLinearGradient( 0, 0, WIDTH, height );
		gradient.addColorStop( 0, '#94A3B8' );
		gradient.addColorStop( 1, '#475569' );
		ctx.fillStyle = gradient;
		ctx.fillRect( 0, 0, WIDTH, height );
	}

	const raw = 'image' === s.type ? logoOverlay( logo, s, WIDTH ) : textOverlay( s, WIDTH );

	if ( ! raw ) {
		return false;
	}

	const overlay = rotated( raw, s.rotation );

	ctx.globalAlpha = Math.max( 0.05, Math.min( 1, s.opacity / 100 ) );

	if ( 'tile' === s.position ) {
		const stepX = overlay.width + Math.max( s.margin, 10 );
		const stepY = overlay.height + Math.max( s.margin, 10 );

		for ( let y = Math.floor( stepY / 2 ) - overlay.height; y < height; y += stepY ) {
			const row = Math.floor( ( y + overlay.height ) / stepY );
			for ( let x = ( row % 2 ? Math.floor( stepX / 2 ) : 0 ) - overlay.width; x < WIDTH; x += stepX ) {
				ctx.drawImage( overlay, x, y );
			}
		}
	} else {
		const [ x, y ] = anchor( s.position, WIDTH, height, overlay.width, overlay.height, s.margin );
		ctx.drawImage( overlay, x, y );
	}

	ctx.globalAlpha = 1;

	return true;
}
