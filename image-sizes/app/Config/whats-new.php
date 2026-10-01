<?php
/**
 * "What's new" announcements, keyed by the full release that ships them (x.y.z).
 *
 * Adding an entry IS the announcement: a site reaching that release is redirected to the dashboard
 * once and shown the items once. A release with nothing to announce adds nothing. A site that skips
 * releases sees every entry it missed, newest first (capped by Activator::WHATS_NEW_MAX_ITEMS).
 *
 * Each value is a function so the keys can be read before translations load (activate() runs on
 * plugins_loaded). Item keys: key, icon (dashboard | checkup | watermark, mapped in
 * WhatsNewPopup.tsx), title, description, optional to (SPA route), cta, pro.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'6.9.0' => static function () {
		return array(
			array(
				'key'         => 'dashboard',
				'icon'        => 'dashboard',
				'title'       => __( 'A dashboard that grades your images', 'image-sizes' ),
				'description' => __( 'One score for your library, split into Speed, SEO, Storage and Protect, with a Fix everything button for the free fixes.', 'image-sizes' ),
				'to'          => '/',
				'cta'         => __( 'Open the dashboard', 'image-sizes' ),
			),
			array(
				'key'         => 'checkup',
				'icon'        => 'checkup',
				'title'       => __( 'Site Image Checkup', 'image-sizes' ),
				'description' => __( 'A guided check that finds what can be improved and shows how much smaller Pro could make your images, on your own photos.', 'image-sizes' ),
				'to'          => '/setup/start',
				'cta'         => __( 'Run the checkup', 'image-sizes' ),
			),
			array(
				'key'         => 'watermark',
				'icon'        => 'watermark',
				'title'       => __( 'Watermark your images', 'image-sizes' ),
				'description' => __( 'Put your name or logo on every photo, as it is uploaded or across your whole library. The original is always kept, so you can undo it. Try the settings and preview for free.', 'image-sizes' ),
				'to'          => '/watermark',
				'cta'         => __( 'Try the preview', 'image-sizes' ),
				'pro'         => true,
			),
		);
	},
);
