<?php
use Thumbpress\Helpers\Utility;

/**
 * Returns the home URL of the WordPress site.
 *
 * @param string $path    Optional. Path relative to the home URL.
 * @param int    $blog_id Optional. ID of the blog in a multisite installation.
 *
 * @return string Home URL with optional path appended.
 */
function thumbpress_home_url( $path = '', $blog_id = null ) {
	return get_home_url( $blog_id, $path );
}

function thumbpress_settings_menus() {

	$pages = Utility::get_posts( array( 'post_type' => 'page' ) );

	return apply_filters(
		'thumbpress_settings_menus',
		array(
			'general' => array(
				'label'    => __( 'General', 'image-sizes' ),
				'desc'     => __( 'General settings', 'image-sizes' ),
				'icon'     => '',
				'submenus' => array(
					'pages' => array(
						'label'    => __( 'Pages', 'image-sizes' ),
						'desc'     => __( 'Page Settings', 'image-sizes' ),
						'sections' => array(
							'main_pages' => array(
								'label'  => __( 'Main Pages', 'image-sizes' ),
								'desc'   => __( 'Main Pages Settings', 'image-sizes' ),
								'fields' => array(
									array(
										'id'      => 'homepage',
										'type'    => 'select',
										'label'   => __( 'Homepage', 'image-sizes' ),
										'options' => $pages,
									),
									array(
										'id'      => 'landing_page',
										'type'    => 'select',
										'label'   => __( 'Landing Page', 'image-sizes' ),
										'options' => $pages,
									),
								),
							),
						),
					),
				),
			),
			'email'   => array(
				'label'    => __( 'Email', 'image-sizes' ),
				'desc'     => __( 'Email settings', 'image-sizes' ),
				'icon'     => '',
				'submenus' => array(
					'new_ticket'    => array(
						'label'    => __( 'New Ticket', 'image-sizes' ),
						'desc'     => __( 'New Ticket Notification', 'image-sizes' ),
						'sections' => array(
							'agent_email'  => array(
								'label'  => __( 'Agent Email', 'image-sizes' ),
								'desc'   => __( 'Email to an Agent', 'image-sizes' ),
								'fields' => array(
									array(
										'id'    => 'agent_header',
										'type'  => 'text',
										'label' => __( 'Header', 'image-sizes' ),
									),
									array(
										'id'    => 'agent_subject',
										'type'  => 'text',
										'label' => __( 'Subject', 'image-sizes' ),
									),
									array(
										'id'    => 'agent_body',
										'type'  => 'wysiwyg',
										'label' => __( 'Body', 'image-sizes' ),
									),
								),
							),
							'client_email' => array(
								'label'  => __( 'Client Email', 'image-sizes' ),
								'desc'   => __( 'Email to a Client', 'image-sizes' ),
								'fields' => array(
									array(
										'id'    => 'client_header',
										'type'  => 'text',
										'label' => __( 'Header', 'image-sizes' ),
									),
									array(
										'id'    => 'client_subject',
										'type'  => 'text',
										'label' => __( 'Subject', 'image-sizes' ),
									),
									array(
										'id'    => 'client_body',
										'type'  => 'wysiwyg',
										'label' => __( 'Body', 'image-sizes' ),
									),
								),
							),
						),
					),
					'agent_replied' => array(
						'label'    => __( 'Agent Reply', 'image-sizes' ),
						'desc'     => __( 'Agent Reply Notification', 'image-sizes' ),
						'sections' => array(
							'agent_email_reply' => array(
								'label'  => __( 'Agent Reply Email', 'image-sizes' ),
								'desc'   => __( 'Email to a Client', 'image-sizes' ),
								'fields' => array(
									array(
										'id'    => 'client_header',
										'type'  => 'text',
										'label' => __( 'Header', 'image-sizes' ),
									),
									array(
										'id'    => 'client_subject',
										'type'  => 'text',
										'label' => __( 'Subject', 'image-sizes' ),
									),
									array(
										'id'    => 'client_body',
										'type'  => 'wysiwyg',
										'label' => __( 'Body', 'image-sizes' ),
									),
								),
							),
						),
					),
				),
			),
		)
	);
}

/**
 * Returns all supported image mime types for ThumbPress operations.
 *
 * @param array $exclude Mime types to exclude (e.g. array('image/webp') when converting to WebP).
 * @return array
 */
function thumbpress_supported_image_mimes( $exclude = array() ) {
	$mimes = array(
		'image/jpeg',
		'image/jpg',
		'image/png',
		'image/gif',
		'image/bmp',
		'image/webp',
		'image/avif',
	);

	return $exclude ? array_values( array_diff( $mimes, $exclude ) ) : $mimes;
}

/**
 * Add bytes to the cumulative space-saved counter shared by all operations.
 *
 * @param int $bytes Bytes saved (must be > 0 to have any effect).
 */
function thumbpress_add_space_saved( $bytes ) {
	$bytes = (int) $bytes;
	if ( $bytes <= 0 ) {
		return;
	}
	$current = (int) get_option( 'thumbpress_space_saved', 0 );
	update_option( 'thumbpress_space_saved', $current + $bytes );
}

function thumbpress_get_field_factory( $type ) {

	if ( $type == 'switch' ) {
		$type = 'switcher';
	} elseif ( $type == 'wysiwyg' ) {
		$type = 'WYSIWYG';
	}

	return '\\Thumbpress\\Helpers\\Field\\' . ucfirst( $type );
}
