export interface ThumbPressGlobals {
	nonce: string;
	rest_url: string;
	plugin_version: string;
	user: {
		id: number;
		display_name: string;
		email: string;
	};
}

declare global {
	interface Window {
		thumbpress: ThumbPressGlobals;
		thumbpress_nav?: {
			items?: Array<{ to: string; label: string; icon: string }>;
			routes?: Array<{ path: string; component: string }>;
		};
		THUMBPRESS_PRO?: Record<string, unknown>;
		THUMBPRESS?: {
			api_base?: string;
			nonce?: string;
			assets_url?: string;
			pro_active?: boolean;
			is_new_user?: boolean;
			/** Whether the promo campaign is live (server-computed date gate). */
			promo_active?: boolean;
			/** UTC Unix timestamp (seconds) when the promo ends. */
			promo_end?: number;
			menus?: Record<string, unknown>;
			/** BCP-47 locale tag from PHP (get_locale() with "_" → "-"), e.g. "bn-BD". */
			locale?: string;
		};
	}
}

export interface ApiResponse<T = unknown> {
	success: boolean;
	data: T;
}

