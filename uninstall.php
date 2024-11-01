<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

require_once('wp-crawlforme.php');

if ( !is_multisite() ) {
	// For Single site
    delete_option( wp_crawlforme::OPTION_NAME );
} else {
	// For Multisite
    global $wpdb;
	
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
    $original_blog_id = get_current_blog_id();
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog( $blog_id );
        delete_site_option( wp_crawlforme::OPTION_NAME );  
    }
    
    switch_to_blog( $original_blog_id );
}
?>