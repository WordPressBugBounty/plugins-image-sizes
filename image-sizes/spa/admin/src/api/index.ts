import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';


const BASE_URL = window.THUMBPRESS?.api_base || '/wp-json/thumbpress/v1';

( apiFetch as any ).use( ( apiFetch as any ).createNonceMiddleware( window.THUMBPRESS?.nonce || '' ) );

// A host rule can overwrite our no-store headers after PHP, so keep poll URLs unique.
( apiFetch as any ).use( ( options: any, next: any ) => {
	const method = ( options.method || 'GET' ).toUpperCase();
	const target = options.url || options.path || '';

	if ( 'GET' !== method || ! /thumbpress(-pro)?\/v1\//.test( target ) ) {
		return next( options );
	}

	const key = options.url ? 'url' : 'path';

	return next( {
		...options,
		[ key ]: target + ( target.includes( '?' ) ? '&' : '?' ) + '_ts=' + Date.now(),
	} );
} );

export interface DashboardCountsStats {
	total_images: number;
	total_sizes: number;
	disabled_sizes: number;
	total_space_saved: number;
	not_webp: number;
	not_avif: number;
	lazy_load: boolean;
	pro_active: boolean;
}

export interface DashboardOptimizationStats {
	total_thumbnails: number;
	unoptimized_images: number;
	compressed: number;
	not_compressed: number;
	compression_check?: CompressionCheckResult | null;
}

export interface CompressionCheckResult {
	saved_bytes: number;
	saved_pct: number;
	library_bytes: number;
	image_count: number;
	unmeasured: number;
	samples_tested: number;
	samples_planned: number;
	level: number;
	quality: number;
	approximate: boolean;
	worth: boolean;
	groups: Record< string, { images: number; bytes: number; samples: number; ratio: number } >;
	measured_at: number;
}

export interface CompressionCheckSample {
	id: number;
	name: string;
	before: number;
	after: number;
	saved_pct: number;
	before_url: string;
	after_url: string;
}

export interface CompressionCheckStep {
	index: number;
	done: boolean;
	sample: CompressionCheckSample | null;
	samples?: CompressionCheckSample[];
	result?: CompressionCheckResult;
}

export function startCompressionCheck() {
	return apiFetch< { success: boolean; data: { token: string; planned: number } } >( {
		url: `${ BASE_URL }/compression-check/start`,
		method: 'POST',
	} );
}

export function stepCompressionCheck( token: string, index: number ) {
	return apiFetch< { success: boolean; data: CompressionCheckStep } >( {
		url: `${ BASE_URL }/compression-check/step`,
		method: 'POST',
		data: { token, index },
	} );
}

export function discardCompressionCheck() {
	return apiFetch< { success: boolean } >( {
		url: `${ BASE_URL }/compression-check/discard`,
		method: 'POST',
	} );
}

export type CategoryKey = 'speed' | 'seo' | 'storage' | 'protect';

export interface CategoryScore {
	/** Null until the scan has data to score it. */
	score: number | null;
	grade: string;
}

export interface ActivityRow {
	type: 'uploads' | 'scan' | 'checkup';
	ts: number;
	count: number;
	/** Uploads only: how many are WebP / AVIF. */
	modern?: number;
	/** Uploads only: how many are over 1 MB. */
	large?: number;
	/** Checkup only: the grade it found. */
	grade?: string;
}

export interface DashboardAnalysisStats {
	scanned: boolean;
	large_images: number;
	duplicate_images: number;
	unused_images: number;
	health_score: number;
	health_issue: string;
	quick_facts: Array<{ ok: boolean; text: string }>;
	/** Letter for health_score, empty until the library is scanned. */
	grade: string;
	scores: Record<CategoryKey, CategoryScore>;
	missing_alt: number;
	bad_names: number;
	duplicate_bytes: number;
	library_bytes: number;
	/** Null until the usage pass that follows a scan has finished. */
	likely_unused: { count: number; bytes: number } | null;
	hotlink: boolean;
	right_click: boolean;
	watermark: boolean;
	/** Whether any watermark tool exists to switch on. */
	watermark_available: boolean;
	/** The score from about a week ago, when there is one to compare with. */
	trend: { previous: number | null; since: number | null };
	/** Bytes that landed on the server uncompressed since install, at the measured ratio; null with no measurement. */
	missed_bytes: number | null;
	/** Unix time the missed-savings count started. */
	missed_since: number;
	activity: ActivityRow[];
	scan_completed_at: number;
}

export type DashboardStats = DashboardCountsStats & DashboardOptimizationStats & DashboardAnalysisStats;

export function getDashboardStats() {
	return apiFetch<{ success: boolean; data: DashboardStats }>( {
		url: `${ BASE_URL }/dashboard/stats`,
	} );
}

export function getDashboardCountsStats() {
	return apiFetch<{ success: boolean; data: DashboardCountsStats }>( {
		url: `${ BASE_URL }/dashboard/stats/counts`,
	} );
}

export function getDashboardOptimizationStats( refresh = false ) {
	return apiFetch<{ success: boolean; data: DashboardOptimizationStats }>( {
		url: refresh
			? addQueryArgs( `${ BASE_URL }/dashboard/stats/optimization`, { refresh: 1 } )
			: `${ BASE_URL }/dashboard/stats/optimization`,
	} );
}

export function getDashboardAnalysisStats() {
	return apiFetch<{ success: boolean; data: DashboardAnalysisStats }>( {
		url: `${ BASE_URL }/dashboard/stats/analysis`,
	} );
}

export function getSettings( key: string ) {
	return apiFetch<{ value: unknown }>( {
		url: addQueryArgs( `${ BASE_URL }/option`, { key } ),
	} );
}

export function saveSettings( key: string, value: unknown ) {
	return apiFetch<{ success: boolean }>( {
		url: `${ BASE_URL }/option`,
		method: 'POST',
		data: { key, value },
	} );
}

export interface RegenerateResponse {
	success: boolean;
	data?: {
		offset: number;
		last_id?: number;
		progress: number;
		thumbs_deleted: number;
		thumbs_created: number;
		total_images_count: number;
		total?: number;
		message: string;
		action_id?: number;
		space_saved?: number;
		space_saved_label?: string;
		not_found?: number;
		failed?: number;
		processed_count?: number;
	};
	message?: string;
}

export function regenerateNow( offset: number, limit: number, thumbsDeleted: number, thumbsCreated: number, spaceSaved: number = 0, notFound: number = 0, processedCount: number = 0, failed: number = 0, lastId: number = 0 ) {
	return apiFetch<RegenerateResponse>( {
		url: `${ BASE_URL }/regenerate/now`,
		method: 'POST',
		data: {
			offset,
			last_id: lastId,
			limit,
			thumbs_deleteds: thumbsDeleted,
			thumbs_createds: thumbsCreated,
			space_saved: spaceSaved,
			not_found: notFound,
			processed_count: processedCount,
			failed,
		},
	} );
}

export function regenerateBackground( limit: number ) {
	return apiFetch<RegenerateResponse>( {
		url: `${ BASE_URL }/regenerate/background`,
		method: 'POST',
		data: { limit },
	} );
}

export interface ProgressResponse {
	success: boolean;
	data?: {
		progress: number;
		processed: number;
		deleted: number;
		created: number;
		total: number;
		space_saved_label: string;
		not_found: number;
		failed?: number;
		is_complete: boolean;
	};
}

export function getRegenerateProgress() {
	return apiFetch<ProgressResponse>( {
		url: `${ BASE_URL }/regenerate/progress`,
	} );
}

export function cancelRegenerate() {
	return apiFetch<{ success: boolean }>( {
		url: `${ BASE_URL }/regenerate/cancel`,
		method: 'POST',
	} );
}

export function cancelConvert() {
	return apiFetch<{ success: boolean }>( {
		url: `${ BASE_URL }/convert/cancel`,
		method: 'POST',
	} );
}

export interface ScanProgress {
	is_running: boolean;
	is_ready: boolean;
	total: number;
	processed: number;
	percent: number;
	indexed: number;
	completed_at: number;
}

export function startScan() {
	return apiFetch< { success: boolean; data: ScanProgress } >( {
		url: `${ BASE_URL }/scan/start`,
		method: 'POST',
	} );
}

export function getScanProgress() {
	return apiFetch< { success: boolean; data: ScanProgress } >( {
		url: `${ BASE_URL }/scan/progress`,
	} );
}

export function cancelScan() {
	return apiFetch< { success: boolean; data: ScanProgress } >( {
		url: `${ BASE_URL }/scan/cancel`,
		method: 'POST',
	} );
}

export interface ThumbnailsSizes {
	[ key: string ]: {
		name: string;
		width: number;
		height: number;
		crop: boolean;
	};
}

export interface ThumbnailsResponse {
	success: boolean;
	data?: ThumbnailsSizes | string[];
	message?: string;
}

export function getAllThumbnails() {
	return apiFetch<ThumbnailsResponse>( {
		url: `${ BASE_URL }/thumbnails`,
	} );
}

export function getDisabledThumbnails() {
	return apiFetch<ThumbnailsResponse>( {
		url: `${ BASE_URL }/thumbnails/disabled`,
	} );
}

export function saveDisabledThumbnails( sizes: string[] ) {
	return apiFetch<ThumbnailsResponse>( {
		url: `${ BASE_URL }/thumbnails/disabled`,
		method: 'POST',
		data: { sizes },
	} );
}

/**
 * Convert Images API
 */
export interface ConvertResponse {
	success: boolean;
	data?: {
		last_id?: number;
		processed?: number;
		progress: number;
		converted: number;
		remaining?: number;
		total: number;
		space_saved?: number;
		message: string;
		action_id?: number;
		not_found?: number;
		failed?: number;
		is_complete?: boolean;
	};
	message?: string;
}

export interface ConvertProgressResponse {
	success: boolean;
	data?: {
		progress: number;
		processed: number;
		converted: number;
		remaining: number;
		total: number;
		space_saved: number;
		is_complete: boolean;
		completed_time: string;
		not_found?: number;
		failed?: number;
	};
}

export function convertNow( lastId: number, limit: number, file_formats: string[], space_saved: number = 0, notFound: number = 0, processed: number = 0, converted: number = 0, failed: number = 0 ) {
	return apiFetch<ConvertResponse>( {
		url: `${ BASE_URL }/convert/now`,
		method: 'POST',
		data: { last_id: lastId, limit, file_formats, space_saved, not_found: notFound, processed, converted, failed },
	} );
}

export function convertBackground( limit: number, file_formats: string[] ) {
	return apiFetch<ConvertResponse>( {
		url: `${ BASE_URL }/convert/background`,
		method: 'POST',
		data: { limit, file_formats },
	} );
}

export function getConvertProgress() {
	return apiFetch<ConvertProgressResponse>( {
		url: `${ BASE_URL }/convert/progress`,
	} );
}

/**
 * Plugin Settings API
 */
export interface PluginSettingsResponse {
	success: boolean;
	data?: {
		php_upload_max: string;
		right_click_disable: boolean;
		lazy_load: boolean;
		hotlink_protection: boolean;
		image_editor: boolean;
		replace_images: boolean;
		max_size: string;
		max_size_unit: string;
		max_width: string;
		max_height: string;
		webp_on_upload: boolean;
		webp_single_convert: boolean;
		webp_file_formats: string[];
		avif_on_upload: boolean;
		avif_single_convert: boolean;
		avif_file_formats: string[];
		social_facebook: boolean;
		social_linkedin: boolean;
		social_twitter: boolean;
		social_pinterest: boolean;
		auto_set_featured_image: boolean;
	};
}

export interface PluginSettings {
	php_upload_max: string;
	right_click_disable: boolean;
	lazy_load: boolean;
	hotlink_protection: boolean;
	image_editor: boolean;
	replace_images: boolean;
	max_size: string;
	max_size_unit: string;
	max_width: string;
	max_height: string;
	webp_on_upload: boolean;
	webp_single_convert: boolean;
	webp_file_formats: string[];
	avif_on_upload: boolean;
	avif_single_convert: boolean;
	avif_file_formats: string[];
	social_facebook: boolean;
	social_linkedin: boolean;
	social_twitter: boolean;
	social_pinterest: boolean;
	auto_set_featured_image: boolean;
}

export function getPluginSettings() {
	return apiFetch<PluginSettingsResponse>( {
		url: `${ BASE_URL }/settings`,
	} );
}

export function savePluginSettings( settings: {
	right_click_disable?: boolean;
	lazy_load?: boolean;
	hotlink_protection?: boolean;
	image_editor?: boolean;
	replace_images?: boolean;
	max_size?: string;
	max_size_unit?: string;
	max_width?: string;
	max_height?: string;
	webp_on_upload?: boolean;
	webp_single_convert?: boolean;
	webp_file_formats?: string[];
	avif_on_upload?: boolean;
	avif_single_convert?: boolean;
	avif_file_formats?: string[];
	social_facebook?: boolean;
	social_linkedin?: boolean;
	social_twitter?: boolean;
	social_pinterest?: boolean;
	auto_set_featured_image?: boolean;
} ) {
	return apiFetch<{ success: boolean }>( {
		url: `${ BASE_URL }/settings`,
		method: 'POST',
		data: settings,
	} );
}

export interface DebugInfo {
	wordpress_version: string;
	php_version: string;
	mysql_version: string;
	server_software: string;
	wp_debug: boolean;
	wp_debug_display: boolean;
	wp_debug_log: boolean;
	wp_memory_limit: string;
	php_memory_limit: string;
	php_max_execution: string;
	php_upload_max: string;
	total_images: number;
	active_plugins: Array<{ name: string; version: string; slug: string }>;
	active_theme: { name: string; version: string; parent?: { name: string; version: string } };
	thumbpress: {
		thumbpress_version: string;
		pro_active: boolean;
		pro_version: string | null;
		license_status: string | null;
		license_key_set: boolean;
		lazy_load: boolean;
		right_click_disable: boolean;
		hotlink_protection: boolean;
		webp_on_upload: boolean;
		avif_on_upload: boolean;
		disabled_sizes: string[];
		max_file_size: string;
		max_dimensions: string;
	};
}

export function getDebugInfo() {
	return apiFetch<{ success: boolean; data: DebugInfo }>( {
		url: `${ BASE_URL }/debug/info`,
	} );
}


export type SiteType = 'blog' | 'store' | 'photo' | 'business' | 'agency';

export interface SetupState {
	version: number;
	started_at: number;
	completed_at: number;
	skipped_at: number;
	invite_dismissed_at: number;
	mode: '' | 'fresh' | 'upgrade' | 'manual';
	site_type: '' | SiteType;
	grade_before: string;
	score_before: number | null;
	free_fixes_applied: string[];
	offer_expires: number;
}

export interface SetupPayload {
	state: SetupState;
	detected_type: SiteType;
	/** A discount to show after the checkup; null unless one is configured and still running. */
	offer: { code: string; percent: number; hours: number; expires: number } | null;
	pro_active: boolean;
}

export function getSetupState() {
	return apiFetch< { success: boolean; data: SetupPayload } >( {
		url: `${ BASE_URL }/setup/state`,
	} );
}

export function saveSetupState( changes: {
	site_type?: SiteType;
	mode?: 'fresh' | 'upgrade' | 'manual';
	started?: boolean;
	completed?: boolean;
	skipped?: boolean;
	invite_dismissed?: boolean;
	grade_before?: string;
	score_before?: number;
	free_fixes_applied?: string[];
} ) {
	return apiFetch< { success: boolean; data: SetupPayload } >( {
		url: `${ BASE_URL }/setup/state`,
		method: 'POST',
		data: changes,
	} );
}


/** The newest image in the library, for the watermark preview. Empty when the library has none. */
export async function getWatermarkSampleImage(): Promise<string> {
	// Works for pretty and plain permalinks alike: the root ends in `/wp-json/` or `?rest_route=/`.
	const root = BASE_URL.replace( /thumbpress\/v1\/?$/, '' );

	try {
		const media: any = await ( apiFetch as any )( {
			url: addQueryArgs( `${ root }wp/v2/media`, { per_page: 1, media_type: 'image', orderby: 'date', order: 'desc', _fields: 'source_url,media_details' } ),
		} );
		const item = Array.isArray( media ) ? media[ 0 ] : null;

		return item?.media_details?.sizes?.large?.source_url || item?.source_url || '';
	} catch {
		return '';
	}
}


/** Record that the "What's new" popup was dismissed. The server stamps the version itself. */
export function dismissWhatsNew() {
	return apiFetch<{ success: boolean }>( {
		url: `${ BASE_URL }/whats-new/dismiss`,
		method: 'POST',
	} );
}

/** Record the answer to the review prompt. The server sets the timestamps and the snooze. */
export function saveReviewAnswer( action: 'later' | 'never' | 'reviewed' ) {
	return apiFetch<{ success: boolean }>( {
		url: `${ BASE_URL }/review/state`,
		method: 'POST',
		data: { action },
	} );
}

/** Send "not happy" feedback to ThumbPress. Rejects with the server's message when it could not be sent. */
export function sendReviewFeedback( message: string, email: string ) {
	return apiFetch<{ success: boolean }>( {
		url: `${ BASE_URL }/review/feedback`,
		method: 'POST',
		data: { message, email },
	} );
}
