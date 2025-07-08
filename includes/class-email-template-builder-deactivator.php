<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Email_Template_Builder
 * @subpackage Email_Template_Builder/includes
 * @author     Jules <your-email@example.com>
 */
class Email_Template_Builder_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // We can add any deactivation logic here if needed,
        // e.g., removing scheduled cron jobs.
        // Table deletion will be handled in uninstall.php if desired.
    }

}
