<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$deletable_options = [ 'thumbpress_activated', 'image-sizes_db_version', 'thumbpress_significant_version' ];
foreach ( $deletable_options as $option ) {
    delete_option( $option );
}

// Hotlink protection: deactivation already strips the .htaccess rules; this catches a plugin
// removed some other way (files deleted by hand, a deactivation that never ran).
$software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? strtolower( $_SERVER['SERVER_SOFTWARE'] ) : '';
if ( false !== strpos( $software, 'apache' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();
    global $wp_filesystem;

    $uploads = wp_get_upload_dir();
    foreach ( [ ABSPATH . '.htaccess', trailingslashit( $uploads['basedir'] ) . '.htaccess' ] as $htaccess ) {
        if ( ! $wp_filesystem || ! $wp_filesystem->exists( $htaccess ) || ! $wp_filesystem->is_writable( $htaccess ) ) {
            continue;
        }

        $content = (string) $wp_filesystem->get_contents( $htaccess );
        $pattern = '/\n?# BEGIN ThumbPress Hotlink Protection.*?# END ThumbPress Hotlink Protection\n?/s';
        if ( ! preg_match( $pattern, $content ) ) {
            continue;
        }

        $content = preg_replace( $pattern, '', $content );
        if ( '' === trim( $content ) && ABSPATH . '.htaccess' !== $htaccess ) {
            wp_delete_file( $htaccess );
        } else {
            $wp_filesystem->put_contents( $htaccess, $content, FS_CHMOD_FILE );
        }
    }
}
delete_option( 'thumbpress_hotlink_protection' );

// The hash index is ours, so it goes with us.
global $wpdb;
foreach ( [ 'hashes', 'hash_groups' ] as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}thumbpress_{$table}" ); // phpcs:ignore WordPress.DB
}
delete_option( 'thumbpress_hash_index_version' );

// Compression check: its result, any unfinished run, and the folder of preview copies.
$check_dir = get_option( 'thumbpress_compression_check_dir' );
if ( is_string( $check_dir ) && preg_match( '/^thumbpress-check-[A-Za-z0-9]{20}$/', $check_dir ) ) {
    $uploads = wp_get_upload_dir();
    $path    = trailingslashit( $uploads['basedir'] ) . $check_dir;
    if ( is_dir( $path ) ) {
        foreach ( (array) scandir( $path ) as $entry ) {
            if ( is_file( $path . '/' . $entry ) ) {
                wp_delete_file( $path . '/' . $entry );
            }
        }
        rmdir( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
    }
}
foreach ( [ 'thumbpress_compression_check', 'thumbpress_compression_check_run', 'thumbpress_compression_check_dir', 'thumbpress_usage_state', 'thumbpress_usage_scanned_at', 'thumbpress_usage_cancelled', 'thumbpress_missed_since', 'thumbpress_missed_kb', 'thumbpress_grade_history', 'thumbpress_setup_state', 'thumbpress_setup_redirect', 'thumbpress_hash_index_schema', 'thumbpress_installed_at', 'thumbpress_review_state' ] as $option ) {
    delete_option( $option );
}