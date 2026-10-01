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

/** One "What's new" item, as app/Config/whats-new.php defines it. */
export interface WhatsNewItem {
	key: string;
	/** dashboard | checkup | watermark, mapped to an icon in WhatsNewPopup. */
	icon: string;
	title: string;
	description: string;
	/** Where "try it" goes; omitted when the item is the screen you are already on. */
	to?: string;
	cta?: string;
	pro?: boolean;
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
			/** Pro is installed, whether or not its license is activated. */
			pro_installed?: boolean;
			/** The site title, for the watermark preview's default text. */
			site_name?: string;
			/** Show the "What's new" popup (server-decided: right version, not yet dismissed). */
			show_whats_new?: boolean;
			/** The running plugin version, e.g. "6.9.2". */
			version?: string;
			/** The announced release, for the popup's title, e.g. "6.9.0". */
			whats_new_version?: string;
			/** The unseen "What's new" items, from app/Config/whats-new.php. */
			whats_new_items?: WhatsNewItem[];
			/** Show the review prompt (server-decided: due after install, not yet answered). */
			show_review?: boolean;
			/** The current admin's email, to pre-fill the feedback form. */
			review_email?: string;
			is_new_user?: boolean;
			/** The dashboard draws Pro's lines itself, so Pro's legacy dashboard card filters are not needed. */
			dashboard_lines?: boolean;
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

