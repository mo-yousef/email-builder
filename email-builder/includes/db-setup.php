<?php
/**
 * Database setup for Email Template Builder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Creates the custom database table for email templates.
 * This function should be called appropriately during theme setup.
 * For example, on 'after_switch_theme' or via a theme options page.
 */
function etb_create_custom_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_templates'; // Consistent table name
    $charset_collate = $wpdb->get_charset_collate();

    // Check if table already exists or if the version is old
    $current_db_version = get_option( 'etb_db_version', '0' );

    // Only proceed if table needs to be created or updated
    // For simplicity, we'll run dbDelta if version doesn't match.
    // More complex migration logic would be needed for schema changes in existing tables.
    if ( version_compare( $current_db_version, ETB_VERSION, '<' ) ) {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            content_en longtext DEFAULT NULL,
            content_pt longtext DEFAULT NULL,
            content_es longtext DEFAULT NULL,
            sections_order text DEFAULT NULL,
            settings text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Check for errors (dbDelta can be a bit tricky)
        if (empty($wpdb->last_error)) {
            update_option( 'etb_db_version', ETB_VERSION );
            update_option( 'etb_db_installed', ETB_VERSION ); // Simple flag
            // You might want to add an admin notice here for success
        } else {
            // Log error or add an admin notice for failure
            // error_log("ETB DB Error: " . $wpdb->last_error);
        }
    }
}

/**
 * Example of how to trigger table creation.
 * You might want a more robust way, e.g., a button in theme options.
 * 'after_switch_theme' runs only once when the theme is activated.
 */
// add_action( 'after_switch_theme', 'etb_create_custom_table' );

/**
 * You could also add an admin notice with a button to trigger the setup.
 * Example:
 */
function etb_admin_notice_db_setup() {
    if (get_option('etb_db_installed') !== ETB_VERSION && current_user_can('manage_options')) {
        $setup_url = wp_nonce_url(add_query_arg('etb_action', 'setup_db'), 'etb_setup_db_nonce');
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p>' . sprintf( __('The Email Template Builder needs to set up its database table. <a href="%s">Click here to set it up.</a>', 'your-theme-textdomain'), esc_url($setup_url) ) . '</p>';
        echo '</div>';
    }
}
add_action( 'admin_notices', 'etb_admin_notice_db_setup' );

function etb_handle_db_setup_action() {
    if (isset($_GET['etb_action']) && $_GET['etb_action'] === 'setup_db' && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'etb_setup_db_nonce') && current_user_can('manage_options')) {
            etb_create_custom_table();
            // Redirect to avoid re-running on refresh, or just let the notice disappear.
            wp_redirect(remove_query_arg(array('etb_action', '_wpnonce')));
            exit;
        }
    }
}
add_action( 'admin_init', 'etb_handle_db_setup_action' );

?>
