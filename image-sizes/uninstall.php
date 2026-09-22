<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$deletable_options = [ 'thumbpress_activated', 'image-sizes_db_version', 'thumbpress_significant_version' ];
foreach ( $deletable_options as $option ) {
    delete_option( $option );
}

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
foreach ( [ 'thumbpress_compression_check', 'thumbpress_compression_check_run', 'thumbpress_compression_check_dir' ] as $option ) {
    delete_option( $option );
}