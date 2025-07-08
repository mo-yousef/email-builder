<?php
/**
 * Theme functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email Template Builder: Include CPT registration.
 *
 * This line includes the custom post type definition for the email templates.
 * It's placed at the top to ensure the CPT is registered early.
 */
require_once get_template_directory() . '/builder/post-type.php';

/**
 * Email Template Builder: Include Admin Page and UI.
 *
 * This line includes the admin page registration for the email template builder.
 */
require_once get_template_directory() . '/builder/admin-page.php';

/**
 * Email Template Builder: Include Export Functionality.
 *
 * Handles HTML export of templates.
 */
require_once get_template_directory() . '/builder/export.php';

/**
 * Email Template Builder: Include Helper Functions.
 *
 * Contains utility functions for the builder.
 */
require_once get_template_directory() . '/builder/helpers.php';


// Add other theme setup functions and includes below this line.

/**
 * Example: Enqueue theme stylesheet.
 */
// function theme_enqueue_styles() {
// wp_enqueue_style( 'mytheme-style', get_stylesheet_uri() );
// }
// add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );

?>
