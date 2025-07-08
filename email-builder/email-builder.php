<?php
/**
 * Main file for the Email Template Builder feature.
 * To be included in the theme's functions.php
 *
 * Example: require_once( get_template_directory() . '/email-builder/email-builder.php' );
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants for the email builder feature
define( 'ETB_THEME_DIR', get_template_directory() . '/email-builder' );
define( 'ETB_THEME_URL', get_template_directory_uri() . '/email-builder' );
define( 'ETB_VERSION', '1.0.0' ); // Version for the builder feature

// Include necessary files
require_once ETB_THEME_DIR . '/includes/db-setup.php';
// Shortcode approach not taken, page template is used.
// require_once ETB_THEME_DIR . '/includes/shortcode.php';
require_once ETB_THEME_DIR . '/includes/ajax-handlers.php';
require_once ETB_THEME_DIR . '/includes/enqueue-scripts.php';


// Initialize database setup (e.g., on theme activation or admin notice)
// This is now handled by an admin notice and action in db-setup.php

/**
 * Function to initialize the email template builder components.
 */
function etb_initialize() {
    // Database table creation is prompted by an admin notice in db-setup.php.
    // No explicit call to etb_create_custom_table() here unless a different trigger is desired.

    // Initialize AJAX handlers
    // Ensure the function etb_register_ajax_handlers exists in ajax-handlers.php
    if (function_exists('etb_register_ajax_handlers')) {
        etb_register_ajax_handlers();
    }

    // Initialize script and style enqueuing
    // Ensure the function etb_register_enqueue_hooks exists in enqueue-scripts.php
    if (function_exists('etb_register_enqueue_hooks')) {
        etb_register_enqueue_hooks();
    }

    // Page template (email-builder/templates/page-template-email-builder.php) is automatically detected by WordPress.
    // No specific loading logic needed here for it.
}
add_action( 'after_setup_theme', 'etb_initialize', 20 ); // Initialize after theme setup


// Remove placeholder creation for page template as it's now a fixed file.

// Placeholder for shortcode file content (keeping file for now, but not using)
if ( ! file_exists( ETB_THEME_DIR . '/includes/shortcode.php' ) ) {
    file_put_contents( ETB_THEME_DIR . '/includes/shortcode.php', "<?php\n// Shortcode logic - NOT CURRENTLY USED. Page template is preferred.\nif ( ! defined( 'ABSPATH' ) ) exit;\n?>" );
}

// Placeholder for ajax handlers file content
if ( ! file_exists( ETB_THEME_DIR . '/includes/ajax-handlers.php' ) ) {
    file_put_contents( ETB_THEME_DIR . '/includes/ajax-handlers.php', "<?php\n// AJAX handlers will go here.\nif ( ! defined( 'ABSPATH' ) ) exit;\n\nfunction etb_register_ajax_handlers() {\n // add_action( 'wp_ajax_...', ... );\n}\n?>" );
}

// Placeholder for enqueue scripts file content
if ( ! file_exists( ETB_THEME_DIR . '/includes/enqueue-scripts.php' ) ) {
    file_put_contents( ETB_THEME_DIR . '/includes/enqueue-scripts.php', "<?php\n// Script and style enqueuing logic will go here.\nif ( ! defined( 'ABSPATH' ) ) exit;\n\nfunction etb_register_enqueue_hooks(){\n // add_action( 'wp_enqueue_scripts', ... );\n}\n?>" );
}


// Placeholder for assets
if ( ! is_dir( ETB_THEME_DIR . '/assets/css' ) ) {
    mkdir( ETB_THEME_DIR . '/assets/css', 0755, true );
}
if ( ! file_exists( ETB_THEME_DIR . '/assets/css/email-builder.css' ) ) {
    file_put_contents( ETB_THEME_DIR . '/assets/css/email-builder.css', "/* Email Builder Styles */\n" );
}

if ( ! is_dir( ETB_THEME_DIR . '/assets/js' ) ) {
    mkdir( ETB_THEME_DIR . '/assets/js', 0755, true );
}
if ( ! file_exists( ETB_THEME_DIR . '/assets/js/email-builder.js' ) ) {
    file_put_contents( ETB_THEME_DIR . '/assets/js/email-builder.js', "// Email Builder JS\n(function($) {\n\t'use strict';\n\t$(function() {\n\t\t// Code here\n\t});\n})(jQuery);\n" );
}

// Placeholder for index.php files
$index_content = "<?php\n// Silence is golden.\n";
if ( ! file_exists( ETB_THEME_DIR . '/index.php' ) ) {
    file_put_contents( ETB_THEME_DIR . '/index.php', $index_content );
}
if ( ! file_exists( ETB_THEME_DIR . '/includes/index.php' ) ) {
    file_put_contents( ETB_THEME_DIR . '/includes/index.php', $index_content );
}
if ( ! file_exists( ETB_THEME_DIR . '/templates/index.php' ) ) {
     if ( ! is_dir( ETB_THEME_DIR . '/templates' ) ) { mkdir( ETB_THEME_DIR . '/templates', 0755, true ); }
    file_put_contents( ETB_THEME_DIR . '/templates/index.php', $index_content );
}
if ( ! file_exists( ETB_THEME_DIR . '/assets/index.php' ) ) {
     if ( ! is_dir( ETB_THEME_DIR . '/assets' ) ) { mkdir( ETB_THEME_DIR . '/assets', 0755, true ); }
    file_put_contents( ETB_THEME_DIR . '/assets/index.php', $index_content );
}
if ( ! file_exists( ETB_THEME_DIR . '/assets/css/index.php' ) ) {
    file_put_contents( ETB_THEME_DIR . '/assets/css/index.php', $index_content );
}
if ( ! file_exists( ETB_THEME_DIR . '/assets/js/index.php' ) ) {
    file_put_contents( ETB_THEME_DIR . '/assets/js/index.php', $index_content );
}

?>
