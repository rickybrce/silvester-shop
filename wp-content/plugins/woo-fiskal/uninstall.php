<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

#globals used inside the plugin
global $wpdb;

#add prefix to the table name
$wf_table = $wpdb->prefix.'woo_fiskal';
    
#delete database table
$wpdb->query("DROP TABLE IF EXISTS {$wf_table}");

#move all WC orders from our "wc-completed-fiskal" status to "wc-completed" - user won't be able to see completed orders if we don't do this! 
$wpdb->query("UPDATE `wp_posts` SET `post_status` = 'wc-completed' WHERE `post_type` = 'shop_order' AND `post_status` ='wc-completed-fiskal'");

?>