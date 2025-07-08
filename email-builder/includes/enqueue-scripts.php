<?php
/**
 * Enqueue scripts and styles for the Email Template Builder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueues scripts and styles for the email template builder.
 *
 * This function is hooked into 'wp_enqueue_scripts'.
 * It only enqueues assets if the current page is using the email builder page template.
 */
function etb_enqueue_assets() {
    // Check if we are on the correct page template.
    // The template filename can be 'page-template-email-builder.php' (if in theme root)
    // or 'templates/page-template-email-builder.php' or 'email-builder/templates/page-template-email-builder.php'
    // get_page_template_slug() returns the path relative to the theme root.
    $template_slug = get_page_template_slug();
    $expected_slug = 'email-builder/templates/page-template-email-builder.php';

    if ( $template_slug === $expected_slug ) {

        // Enqueue styles
        wp_enqueue_style(
            'jquery-ui-css',
            'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', // Using a specific stable version
            array(),
            '1.13.2'
        );
        wp_enqueue_style(
            'etb-styles', // Handle for the builder's main CSS
            ETB_THEME_URL . '/assets/css/email-builder.css',
            array('jquery-ui-css'), // Depends on jQuery UI styles
            ETB_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script( 'jquery' ); // Ensure jQuery is loaded (it's a WP dependency)
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-tabs' ); // Specifically for the tabs
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'jquery-ui-dialog' ); // For potential modal dialogs

        wp_enqueue_script(
            'etb-script', // Handle for the builder's main JS
            ETB_THEME_URL . '/assets/js/email-builder.js',
            array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-sortable', 'jquery-ui-dialog' ),
            ETB_VERSION,
            true // Load in footer
        );

        // Prepare data to pass to the JavaScript file
        $localized_data = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'etb_ajax_nonce' ), // Nonce for securing AJAX requests
                // It's crucial for the theme developer to set 'etb_default_template_html' option
                // with the base email HTML structure, marked up with data-etb-* attributes.
                'initial_template_html' => stripslashes(get_option('etb_default_template_html', '')),
                'text_strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this template? This action cannot be undone.', 'your-theme-textdomain'),
                'error_saving' => __('An error occurred while saving the template. Please try again.', 'your-theme-textdomain'),
                'template_loaded' => __('Template loaded successfully.', 'your-theme-textdomain'),
                'template_saved' => __('Template saved successfully.', 'your-theme-textdomain'),
                'template_deleted' => __('Template deleted successfully.', 'your-theme-textdomain'),
                'template_cloned' => __('Template cloned successfully. You are now editing the clone.', 'your-theme-textdomain'),
                'select_template_to_load' => __('Please select a template to load.', 'your-theme-textdomain'),
                'select_template_to_clone' => __('Please select a template to clone.', 'your-theme-textdomain'),
                'select_template_to_delete' => __('Please select a template to delete.', 'your-theme-textdomain'),
                'enter_template_name' => __('Please enter a name for the new template.', 'your-theme-textdomain'),
                    'no_changes_to_save' => __('No changes to save.', 'your-theme-textdomain'),
                    'no_template_to_export' => __('Load or create a template to export.', 'your-theme-textdomain'),
                    'template_exported' => __('Template HTML exported.', 'your-theme-textdomain'),
                    'no_changes_to_reset' => __('No unsaved changes to reset.', 'your-theme-textdomain'),
                    'confirm_reset_changes' => __('Are you sure you want to discard unsaved changes?', 'your-theme-textdomain'),
                    'changes_reset' => __('Changes have been reset.', 'your-theme-textdomain'),
                )
            )
        );

        wp_localize_script(
            'etb-script',      // Handle of the script to attach data to
            'etb_ajax_obj',    // Object name in JavaScript
            $localized_data    // Data to pass
        );
    }
}

/**
 * Registers the hook for enqueuing assets.
 * This function is called by etb_initialize() in email-builder.php.
 */
function etb_register_enqueue_hooks() {
    add_action( 'wp_enqueue_scripts', 'etb_enqueue_assets' );
}

// The main email-builder.php file calls etb_register_enqueue_hooks()
// within its etb_initialize() function, which is hooked to 'after_setup_theme'.
?>
