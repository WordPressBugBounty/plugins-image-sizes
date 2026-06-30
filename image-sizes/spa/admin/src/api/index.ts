import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';


const BASE_URL = window.THUMBPRESS?.api_base || '/wp-json/thumbpress/v1';

( apiFetch as any ).use( ( apiFetch as any ).createNonceMiddleware( window.THUMBPRESS?.nonce || '' ) );

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
}

export interface DashboardAnalysisStats {
	large_images: number;
	duplicate_images: number;
	unused_images: number;
	health_score: number;
	health_issue: string;
	quick_facts: Array<{ ok: boolean; text: string }>;
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
	return apiFetch<{ success: boolean; data?: { value: unknown } }>( {
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
		processed_count?: number;
	};
	message?: string;
}

export function regenerateNow( offset: number, limit: number, thumbsDeleted: number, thumbsCreated: number, spaceSaved: number = 0, notFound: number = 0, processedCount: number = 0 ) {
	return apiFetch<RegenerateResponse>( {
		url: `${ BASE_URL }/regenerate/now`,
		method: 'POST',
		data: {
			offset,
			limit,
			thumbs_deleteds: thumbsDeleted,
			thumbs_createds: thumbsCreated,
			space_saved: spaceSaved,
			not_found: notFound,
			processed_count: processedCount,
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
	};
}

export function convertNow( lastId: number, limit: number, file_formats: string[], space_saved: number = 0, notFound: number = 0, processed: number = 0, converted: number = 0 ) {
	return apiFetch<ConvertResponse>( {
		url: `${ BASE_URL }/convert/now`,
		method: 'POST',
		data: { last_id: lastId, limit, file_formats, space_saved, not_found: notFound, processed, converted },
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
		avif_on_upload: boolean;
		avif_single_convert: boolean;
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
	avif_on_upload: boolean;
	avif_single_convert: boolean;
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
	avif_on_upload?: boolean;
	avif_single_convert?: boolean;
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
