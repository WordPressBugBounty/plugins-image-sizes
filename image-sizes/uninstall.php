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