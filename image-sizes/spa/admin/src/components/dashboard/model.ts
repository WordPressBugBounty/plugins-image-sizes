import { __, _n, sprintf } from '@wordpress/i18n';
import type { ActivityRow, CategoryKey, DashboardStats } from '../../api';
import { numberFormat, formatBytes, dateFormat } from '../../lib/i18n';

/** What the dashboard reads: the three stat groups, plus the scan and Pro state around them. */
export type DashboardModelStats = DashboardStats & { scan_running: boolean };

/**
 * pro    - the fix is Pro's: the number is shown, the action is locked.
 * free   - the fix is free and one click away.
 * ok     - nothing to do.
 * plain  - a number with a link, no lock (Pro users, or a fact).
 * soon   - a real finding whose fix Pro does not have yet: shown, never sold.
 */
export type LineTier = 'pro' | 'free' | 'ok' | 'plain' | 'soon';

export interface DashboardLine {
	key: string;
	num?: string;
	label: string;
	note?: string;
	tier: LineTier;
	/** Label for an ok line, e.g. "On". */
	status?: string;
	/** Computed from a sample or a heuristic, so it carries the Estimate chip. */
	estimate?: boolean;
	/** In-app route; the whole line links there when set. */
	to?: string;
	/** A WordPress admin page (relative URL) the whole line links to, for fixes done by hand. */
	href?: string;
	/** Old Pro releases register a card filter per line; this is its name. */
	filter?: 'large' | 'unused' | 'compress' | 'duplicate' | 'avif';
}

export interface DashboardAction {
	label: string;
	/** In-app route, or empty when the action starts the scan or opens the fix list. */
	to: string;
	scan?: boolean;
	/** Opens the "Fix everything" list instead of navigating. */
	fixAll?: boolean;
	/** A WordPress admin page (relative URL), instead of an in-app route. */
	href?: string;
}

export interface DashboardCard {
	key: CategoryKey;
	name: string;
	score: number | null;
	grade: string;
	lines: DashboardLine[];
	fix: DashboardAction;
}

export interface GradeStyle {
	fg: string;
	bg: string;
}

const GRADE_STYLES: Record<string, GradeStyle> = {
	A: { fg: '#047857', bg: '#D1FAE5' },
	B: { fg: '#15803D', bg: '#DCFCE7' },
	C: { fg: '#B45309', bg: '#FEF3C7' },
	D: { fg: '#C2410C', bg: '#FFEDD5' },
	F: { fg: '#B91C1C', bg: '#FEE2E2' },
};

const NO_GRADE: GradeStyle = { fg: '#64748B', bg: '#F1F5F9' };

/** Colours for a letter. The letter is always drawn too, so colour is never the only signal. */
export function gradeStyle( grade: string ): GradeStyle {
	return GRADE_STYLES[ grade ] ?? NO_GRADE;
}

/** Letter for a 0-100 score; the same bands as Helpers/Scorecard.php. */
export function gradeFor( score: number ): string {
	return score >= 90 ? 'A' : score >= 80 ? 'B' : score >= 65 ? 'C' : score >= 50 ? 'D' : 'F';
}

/**
 * Where the score would land once Pro had compressed everything, fixed every heavy image and merged
 * every duplicate. It applies the health formula's own weights (compress 25, large 25, duplicate 10)
 * to the shares still open, so it is arithmetic on real counts, not a promise.
 */
export function proProjection( s: DashboardModelStats ): number {
	const total = Math.max( 1, s.total_images );
	const gain = 25 * ( s.not_compressed / total ) + 25 * ( s.large_images / total ) + 10 * ( s.duplicate_images / total );

	return Math.min( 100, Math.round( s.health_score + gain ) );
}

/** Below this the missed-savings line reads as nagging on a small site. */
export const MISSED_SAVINGS_FLOOR = 50 * 1024 * 1024;

/** Where alt text and file names are edited by hand today. */
const MEDIA_LIBRARY = 'upload.php?mode=list';

const notScanned = ( key: string, note: string ): DashboardLine[] => [
	{ key, label: __( 'Not scanned yet', 'image-sizes' ), note, tier: 'plain' },
];

function speedLines( s: DashboardModelStats, pro: boolean ): DashboardLine[] {
	if ( ! s.scanned && ! s.scan_running ) {
		return notScanned( 'speed-scan', __( 'Heavy images, formats and lazy loading', 'image-sizes' ) );
	}

	const lines: DashboardLine[] = [];

	if ( s.large_images > 0 ) {
		lines.push( {
			key: 'large',
			num: numberFormat( s.large_images ),
			label: __( 'over 1 MB', 'image-sizes' ),
			note: __( 'The main thing slowing your pages', 'image-sizes' ),
			tier: pro ? 'plain' : 'pro',
			to: pro ? '/large-images' : undefined,
			filter: 'large',
		} );
	} else {
		lines.push( { key: 'large', label: __( 'Heavy images', 'image-sizes' ), status: __( 'None', 'image-sizes' ), note: __( 'No image is over 1 MB', 'image-sizes' ), tier: 'ok', filter: 'large' } );
	}

	if ( pro && s.not_compressed > 0 ) {
		lines.push( {
			key: 'compress',
			num: numberFormat( s.not_compressed ),
			label: __( 'not compressed', 'image-sizes' ),
			note: __( 'Compress them in one run', 'image-sizes' ),
			tier: 'plain',
			to: '/compress-images',
			filter: 'compress',
		} );
	}

	if ( s.not_webp > 0 ) {
		lines.push( {
			key: 'webp',
			num: numberFormat( s.not_webp ),
			label: __( 'not WebP yet', 'image-sizes' ),
			note: __( 'Convert them in one click', 'image-sizes' ),
			tier: 'free',
			to: '/convert-to-webp',
		} );
	}

	return lines.slice( 0, 3 );
}

function seoLines( s: DashboardModelStats ): DashboardLine[] {
	if ( ! s.scanned && ! s.scan_running ) {
		return notScanned( 'seo-scan', __( 'Alt text, filenames and share images', 'image-sizes' ) );
	}

	const lines: DashboardLine[] = [];

	if ( s.missing_alt > 0 ) {
		lines.push( {
			key: 'alt',
			num: numberFormat( s.missing_alt ),
			label: __( 'no alt text', 'image-sizes' ),
			note: __( 'Google can’t read them. Add it in the Media Library', 'image-sizes' ),
			tier: 'soon',
			href: MEDIA_LIBRARY,
		} );
	} else {
		lines.push( { key: 'alt', label: __( 'Alt text', 'image-sizes' ), status: __( 'Done', 'image-sizes' ), note: __( 'Every image has it', 'image-sizes' ), tier: 'ok' } );
	}

	if ( s.bad_names > 0 ) {
		lines.push( {
			key: 'names',
			num: numberFormat( s.bad_names ),
			label: _n( 'vague filename', 'vague filenames', s.bad_names, 'image-sizes' ),
			note: __( 'Like IMG_4021.jpg', 'image-sizes' ),
			tier: 'soon',
		} );
	}

	return lines;
}

function storageLines( s: DashboardModelStats, pro: boolean ): DashboardLine[] {
	if ( ! s.scanned && ! s.scan_running ) {
		return notScanned( 'storage-scan', __( 'Duplicates, unused files and extra sizes', 'image-sizes' ) );
	}

	const lines: DashboardLine[] = [];

	if ( s.duplicate_images > 0 ) {
		lines.push( {
			key: 'duplicate',
			num: numberFormat( s.duplicate_images ),
			label: _n( 'duplicate', 'duplicates', s.duplicate_images, 'image-sizes' ),
			note: sprintf(
				/* translators: %s: size of the repeat files, e.g. "212 MB". */
				__( '%s of repeat files', 'image-sizes' ),
				formatBytes( s.duplicate_bytes ),
			),
			tier: pro ? 'plain' : 'pro',
			to: pro ? '/duplicate-images' : undefined,
			filter: 'duplicate',
		} );
	}

	// Pro's exact detector wins over the scan's estimate whenever it has a number.
	if ( s.unused_images > 0 ) {
		lines.push( {
			key: 'unused',
			num: numberFormat( s.unused_images ),
			label: __( 'unused', 'image-sizes' ),
			note: __( 'In no post or page', 'image-sizes' ),
			tier: pro ? 'plain' : 'pro',
			to: pro ? '/unused-images' : undefined,
			filter: 'unused',
		} );
	} else if ( s.likely_unused && s.likely_unused.count > 0 ) {
		lines.push( {
			key: 'unused',
			num: '~' + numberFormat( s.likely_unused.count ),
			label: __( 'likely unused', 'image-sizes' ),
			note: sprintf(
				/* translators: %s: size of the likely-unused files, e.g. "1.8 GB". */
				__( '~%s in no post or page', 'image-sizes' ),
				formatBytes( s.likely_unused.bytes, true ),
			),
			tier: pro ? 'plain' : 'pro',
			estimate: true,
			to: pro ? '/unused-images' : undefined,
			filter: 'unused',
		} );
	}

	if ( s.disabled_sizes > 0 ) {
		lines.push( {
			key: 'sizes',
			num: numberFormat( s.disabled_sizes ),
			label: _n( 'size stopped', 'sizes stopped', s.disabled_sizes, 'image-sizes' ),
			status: __( 'Stopped', 'image-sizes' ),
			note: __( 'No longer made on upload', 'image-sizes' ),
			tier: 'ok',
		} );
	} else if ( s.total_sizes > 0 ) {
		lines.push( {
			key: 'sizes',
			num: numberFormat( s.total_sizes ),
			label: _n( 'size made per upload', 'sizes made per upload', s.total_sizes, 'image-sizes' ),
			note: __( 'Turn off the ones your theme never shows', 'image-sizes' ),
			tier: 'free',
			to: '/settings?tab=thumbnails',
		} );
	}

	if ( lines.length === 0 ) {
		lines.push( { key: 'clean', label: __( 'Library', 'image-sizes' ), status: __( 'Lean', 'image-sizes' ), note: __( 'No duplicates or unused files found', 'image-sizes' ), tier: 'ok' } );
	}

	return lines.slice( 0, 3 );
}

function protectLines( s: DashboardModelStats, pro: boolean ): DashboardLine[] {
	const lines: DashboardLine[] = [
		s.hotlink
			? { key: 'hotlink', label: __( 'Hotlink', 'image-sizes' ), status: __( 'On', 'image-sizes' ), note: __( 'Others can’t embed your photos', 'image-sizes' ), tier: 'ok' }
			: { key: 'hotlink', label: __( 'Hotlink', 'image-sizes' ), note: __( 'Off · others can embed photos', 'image-sizes' ), tier: 'free', to: '/settings?tab=general' },
		s.right_click
			? { key: 'right-click', label: __( 'Right-click', 'image-sizes' ), status: __( 'On', 'image-sizes' ), note: __( 'Visitors can’t save images', 'image-sizes' ), tier: 'ok' }
			: { key: 'right-click', label: __( 'Right-click', 'image-sizes' ), note: __( 'Off · visitors can save images', 'image-sizes' ), tier: 'free', to: '/settings?tab=general' },
	];

	if ( s.watermark ) {
		lines.push( { key: 'watermark', label: __( 'Watermark', 'image-sizes' ), status: __( 'On', 'image-sizes' ), note: __( 'Your name is on new photos', 'image-sizes' ), tier: 'ok' } );
	} else if ( ! pro ) {
		// Pro stamps text or a logo on every photo, so this is an offer, not a plan.
		lines.push( { key: 'watermark', label: __( 'Watermark', 'image-sizes' ), note: __( 'Put your name on every photo', 'image-sizes' ), tier: 'pro', to: '/settings?tab=watermark' } );
	} else if ( s.watermark_available ) {
		// A lapsed licence or a server without GD gives nothing to switch on, so only offer it when it can run.
		lines.push( { key: 'watermark', label: __( 'Watermark', 'image-sizes' ), note: __( 'Off · photos carry no mark', 'image-sizes' ), tier: 'plain', to: '/watermark' } );
	}

	return lines;
}

/** The four category cards, in the order the design lays them out. */
export function buildCards( s: DashboardModelStats, pro: boolean ): DashboardCard[] {
	const empty = s.total_images === 0;
	const scanNeeded = ! s.scanned && ! s.scan_running && ! empty;
	const startClean = ( key: string ): DashboardLine[] => [
		{ key, label: __( 'No images yet', 'image-sizes' ), note: __( 'Upload some and this fills in', 'image-sizes' ), tier: 'plain' },
	];
	const scanAction: DashboardAction = { label: __( 'Run scan', 'image-sizes' ), to: '', scan: true };

	const card = ( key: CategoryKey, name: string, lines: DashboardLine[], fix: DashboardAction, needsScan: boolean ): DashboardCard => ( {
		key,
		name,
		// Before the first scan no category has a grade, protect included: it would read as a fail.
		score: scanNeeded ? null : s.scores?.[ key ]?.score ?? null,
		grade: scanNeeded ? '' : s.scores?.[ key ]?.grade ?? '',
		lines,
		fix: needsScan ? scanAction : fix,
	} );

	if ( empty ) {
		const settings: DashboardAction = { label: __( 'Review settings', 'image-sizes' ), to: '/settings?tab=general' };

		return [
			card( 'speed', __( 'Speed', 'image-sizes' ), startClean( 'speed-empty' ), settings, false ),
			card( 'seo', __( 'SEO', 'image-sizes' ), startClean( 'seo-empty' ), settings, false ),
			card( 'storage', __( 'Storage', 'image-sizes' ), startClean( 'storage-empty' ), settings, false ),
			card( 'protect', __( 'Protect', 'image-sizes' ), protectLines( s, pro ), settings, false ),
		];
	}

	return [
		card( 'speed', __( 'Speed', 'image-sizes' ), speedLines( s, pro ), {
			label: pro ? __( 'View details', 'image-sizes' ) : __( 'Fix speed', 'image-sizes' ),
			to: s.not_webp > 0 && s.large_images === 0 ? '/convert-to-webp' : pro ? '/compress-images' : '/large-images',
		}, scanNeeded ),
		card( 'seo', __( 'SEO', 'image-sizes' ), seoLines( s ), {
			label: __( 'Fix SEO', 'image-sizes' ),
			to: '',
			href: MEDIA_LIBRARY,
		}, scanNeeded ),
		card( 'storage', __( 'Storage', 'image-sizes' ), storageLines( s, pro ), {
			label: __( 'Clean up', 'image-sizes' ),
			to: '/duplicate-images',
		}, scanNeeded ),
		card( 'protect', __( 'Protect', 'image-sizes' ), protectLines( s, pro ), {
			label: __( 'Review', 'image-sizes' ),
			to: '/settings?tab=general',
		}, false ),
	];
}

/** Lines a person could act on: what the hero's "N fixes found" counts. */
export function countFixes( cards: DashboardCard[] ): { fixes: number; free: number } {
	let fixes = 0;
	let free = 0;

	for ( const card of cards ) {
		for ( const line of card.lines ) {
			if ( line.tier === 'pro' || line.tier === 'free' ) {
				fixes++;
			}
			if ( line.tier === 'free' ) {
				free++;
			}
		}
	}

	return { fixes, free };
}

export interface HeroModel {
	/** No grade yet: the library has not been scanned. */
	never: boolean;
	partial: boolean;
	sentence: string;
	trend: string;
	trendDir: 'up' | 'down' | 'flat' | 'none';
	cta: DashboardAction;
	ctaNote: string;
}

/** The one sentence under the grade: the biggest thing wrong, in plain words. */
export function heroSentence( s: DashboardModelStats ): string {
	if ( s.large_images > 0 ) {
		return sprintf(
			/* translators: %s: number of images over 1 MB. */
			_n( '%s image is over 1 MB. That’s the main thing slowing your pages down.', '%s images are over 1 MB. That’s the main thing slowing your pages down.', s.large_images, 'image-sizes' ),
			numberFormat( s.large_images ),
		);
	}

	if ( s.missing_alt > 0 ) {
		return sprintf(
			/* translators: %s: number of images with no alt text. */
			_n( '%s image has no alt text. Google can’t read it.', '%s images have no alt text. Google can’t read them.', s.missing_alt, 'image-sizes' ),
			numberFormat( s.missing_alt ),
		);
	}

	if ( s.duplicate_images > 0 ) {
		return sprintf(
			/* translators: 1: number of duplicate images, 2: size of the repeat files. */
			__( '%1$s duplicate images are taking up %2$s.', 'image-sizes' ),
			numberFormat( s.duplicate_images ),
			formatBytes( s.duplicate_bytes ),
		);
	}

	if ( s.not_webp > 0 ) {
		return sprintf(
			/* translators: %s: number of images not in WebP. */
			__( '%s images are not in WebP yet. Convert them in one click.', 'image-sizes' ),
			numberFormat( s.not_webp ),
		);
	}

	return __( 'Your library is in good shape.', 'image-sizes' );
}

export function buildHero( s: DashboardModelStats, cards: DashboardCard[] ): HeroModel {
	if ( s.total_images === 0 ) {
		return {
			never: true,
			partial: false,
			sentence: __( 'You’re starting clean. Turn on the free settings below to keep your library light from the first upload.', 'image-sizes' ),
			trend: '',
			trendDir: 'none',
			cta: { label: __( 'Review settings', 'image-sizes' ), to: '/settings?tab=general' },
			ctaNote: __( 'Nothing changes until you say so', 'image-sizes' ),
		};
	}

	const never = ! s.scanned && ! s.scan_running;

	if ( never ) {
		return {
			never: true,
			partial: false,
			sentence: sprintf(
				/* translators: 1: number of images, 2: number of thumbnails. */
				__( 'You have %1$s images and %2$s thumbnails. Run your first scan to get your grade. It runs in the background, so you can leave this page.', 'image-sizes' ),
				numberFormat( s.total_images ),
				numberFormat( s.total_thumbnails ),
			),
			trend: '',
			trendDir: 'none',
			cta: { label: __( 'Run your first scan', 'image-sizes' ), to: '', scan: true },
			ctaNote: __( 'Nothing changes until you say so', 'image-sizes' ),
		};
	}

	const { fixes, free } = countFixes( cards );
	const previous = s.trend?.previous;

	let trend = '';
	let trendDir: HeroModel['trendDir'] = 'none';

	if ( previous !== null && previous !== undefined ) {
		const delta = s.health_score - previous;
		trendDir = delta > 0 ? 'up' : delta < 0 ? 'down' : 'flat';
		trend = delta === 0
			? __( 'No change since last week', 'image-sizes' )
			: sprintf(
				/* translators: 1: arrow, 2: points gained or lost. */
				__( '%1$s %2$s since last week', 'image-sizes' ),
				delta > 0 ? '▲' : '▼',
				numberFormat( Math.abs( delta ) ),
			);
	} else if ( s.scan_completed_at ) {
		trend = sprintf(
			/* translators: %s: date of the first scan. */
			__( 'First check · %s', 'image-sizes' ),
			dateFormat( s.scan_completed_at ),
		);
	}

	const cta: DashboardAction = { label: __( 'Fix everything', 'image-sizes' ), to: '', fixAll: true };

	return {
		never: false,
		partial: s.scan_running,
		sentence: heroSentence( s ) + ( s.scan_running ? ' ' + __( 'Numbers will grow as the scan finishes.', 'image-sizes' ) : '' ),
		trend,
		trendDir,
		cta,
		ctaNote: fixes > 0
			? sprintf(
				/* translators: 1: number of fixes found, 2: how many of them are free. */
				__( '%1$s fixes found · %2$s free', 'image-sizes' ),
				numberFormat( fixes ),
				numberFormat( free ),
			)
			: __( 'Nothing to fix', 'image-sizes' ),
	};
}

export interface ActivityItem {
	key: string;
	when: string;
	text: string;
	/** A Pro action would have handled it. */
	locked?: boolean;
}

function whenLabel( ts: number ): string {
	const now = new Date();
	const then = new Date( ts * 1000 );

	if ( now.toDateString() === then.toDateString() ) {
		return __( 'Today', 'image-sizes' );
	}

	return dateFormat( ts );
}

/** Real events only: uploads and scans. Nothing here is invented to fill the card. */
export function buildActivity( rows: ActivityRow[] = [], pro: boolean ): ActivityItem[] {
	const items: ActivityItem[] = [];

	for ( const row of rows ) {
		if ( row.type === 'checkup' ) {
			items.push( {
				key: `checkup-${ row.ts }`,
				when: whenLabel( row.ts ),
				text: row.grade
					? sprintf(
						/* translators: %s: grade letter the checkup found. */
						__( 'Checkup finished · grade %s', 'image-sizes' ),
						row.grade,
					)
					: __( 'Checkup finished', 'image-sizes' ),
			} );

			if ( row.count > 0 ) {
				items.push( {
					key: `checkup-fixes-${ row.ts }`,
					when: whenLabel( row.ts ),
					text: sprintf(
						/* translators: %s: number of free settings switched on. */
						_n( '%s free setting turned on', '%s free settings turned on', row.count, 'image-sizes' ),
						numberFormat( row.count ),
					),
				} );
			}
			continue;
		}

		if ( row.type === 'scan' ) {
			items.push( {
				key: `scan-${ row.ts }`,
				when: whenLabel( row.ts ),
				text: sprintf(
					/* translators: %s: number of images the scan covered. */
					__( 'Scan finished · %s images', 'image-sizes' ),
					numberFormat( row.count ),
				),
			} );
			continue;
		}

		const modern = row.modern ?? 0;
		items.push( {
			key: `uploads-${ row.ts }`,
			when: whenLabel( row.ts ),
			text: modern > 0
				? sprintf(
					/* translators: 1: number of uploads, 2: how many are WebP or AVIF. */
					_n( '%1$s upload · %2$s in WebP or AVIF', '%1$s uploads · %2$s in WebP or AVIF', row.count, 'image-sizes' ),
					numberFormat( row.count ),
					numberFormat( modern ),
				)
				: sprintf(
					/* translators: %s: number of uploads. */
					_n( '%s upload', '%s uploads', row.count, 'image-sizes' ),
					numberFormat( row.count ),
				),
		} );

		if ( ! pro && ( row.large ?? 0 ) > 0 ) {
			items.push( {
				key: `large-${ row.ts }`,
				when: whenLabel( row.ts ),
				text: sprintf(
					/* translators: %s: number of uploads over 1 MB. */
					_n( '%s upload over 1 MB kept at full size', '%s uploads over 1 MB kept at full size', row.large ?? 0, 'image-sizes' ),
					numberFormat( row.large ?? 0 ),
				),
				locked: true,
			} );
		}
	}

	return items.slice( 0, 4 );
}
