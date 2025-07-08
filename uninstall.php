<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Email_Template_Builder
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Optional: Code to remove custom database table.
// Uncomment the following lines to delete the custom table when the plugin is uninstalled.
/*
global $wpdb;
$table_name = $wpdb->prefix . 'email_templates';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
delete_option( 'etb_db_version' ); // Also remove the db version option
*/

// Optional: Code to remove other plugin options if any were added.
// delete_option( 'etb_some_other_option' );

// Clear any scheduled cron jobs.
// Example: wp_clear_scheduled_hook( 'my_hourly_event' );
?>
