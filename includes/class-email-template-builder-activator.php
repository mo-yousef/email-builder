<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Email_Template_Builder
 * @subpackage Email_Template_Builder/includes
 * @author     Jules <your-email@example.com>
 */
class Email_Template_Builder_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'email_templates';
        $charset_collate = $wpdb->get_charset_collate();

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

        // Add an option to store the plugin version, useful for future upgrades/migrations
        add_option( 'etb_db_version', ETB_VERSION );
    }

}
