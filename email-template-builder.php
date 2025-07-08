<?php
/**
 * Plugin Name: Email Template Builder
 * Description: A custom web app to build and manage email templates using jQuery, embedded in a WordPress page.
 * Version: 1.0.0
 * Author: Jules
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: email-template-builder
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Define constants
 */
define( 'ETB_VERSION', '1.0.0' );
define( 'ETB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ETB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ETB_PLUGIN_FILE', __FILE__ );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-email-template-builder-activator.php
 */
function activate_email_template_builder() {
    require_once ETB_PLUGIN_DIR . 'includes/class-email-template-builder-activator.php';
    Email_Template_Builder_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-email-template-builder-deactivator.php
 */
function deactivate_email_template_builder() {
    require_once ETB_PLUGIN_DIR . 'includes/class-email-template-builder-deactivator.php';
    Email_Template_Builder_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_email_template_builder' );
register_deactivation_hook( __FILE__, 'deactivate_email_template_builder' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require ETB_PLUGIN_DIR . 'includes/class-email-template-builder.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_email_template_builder() {

    $plugin = new Email_Template_Builder();
    $plugin->run();

}
run_email_template_builder();
