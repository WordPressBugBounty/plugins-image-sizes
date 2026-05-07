<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$deletable_options = [ 'thumbpress_activated', 'image-sizes_db_version' ];
foreach ( $deletable_options as $option ) {
    delete_option( $option );
}