<?php
/**
 * Template Name: Email Template Builder
 *
 * This is the template that displays the Email Template Builder application.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure user is logged in
if ( ! is_user_logged_in() ) {
    get_header();
    echo '<div style="padding: 20px; text-align: center;">'; // Added text-align for better presentation
    echo '<h1>' . esc_html__( 'Access Denied', 'your-theme-textdomain' ) . '</h1>';
    echo '<p>' . esc_html__( 'You must be logged in to use the Email Template Builder. Please log in and try again.', 'your-theme-textdomain' ) . '</p>';
    // Optionally, display a login form or link to login page
    echo '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="button button-primary">' . esc_html__('Log In', 'your-theme-textdomain') . '</a></p>';
    // Example of wp_login_form():
    // if ( !is_user_logged_in() ) {
    //     wp_login_form(array('redirect' => get_permalink()));
    // }
    echo '</div>';
    get_footer();
    return; // Stop further execution
}

// If logged in, display the builder.
// Consider using a more minimal header/footer if your theme provides one for "blank slate" or "canvas" pages.
get_header();
?>

<div id="etb-app-container" class="etb-app-theme-container">
    <div id="etb-sidebar-panel">
        <div class="etb-sidebar-header">
            <h2><?php esc_html_e( 'Template Controls', 'your-theme-textdomain' ); ?></h2>
        </div>
        <div id="etb-template-management-section">
            <h3><?php esc_html_e( 'Manage Templates', 'your-theme-textdomain' ); ?></h3>
            <div class="etb-template-actions">
                <label for="etb-template-select" class="screen-reader-text"><?php esc_html_e( 'Select a Template', 'your-theme-textdomain' ); ?></label>
                <select id="etb-template-select">
                    <option value=""><?php esc_html_e( '-- Select a Template --', 'your-theme-textdomain' ); ?></option>
                    <!-- Template options will be populated by JS -->
                </select>
            </div>
            <div class="etb-template-buttons">
                <button id="etb-load-template-button" class="button" disabled><?php esc_html_e( 'Load', 'your-theme-textdomain' ); ?></button>
                <button id="etb-create-new-template-button" class="button button-secondary"><?php esc_html_e( 'Create New', 'your-theme-textdomain' ); ?></button>
                <button id="etb-clone-template-button" class="button" disabled><?php esc_html_e( 'Clone', 'your-theme-textdomain' ); ?></button>
                <button id="etb-delete-template-button" class="button button-danger" disabled><?php esc_html_e( 'Delete', 'your-theme-textdomain' ); ?></button>
            </div>
        </div>
        <div id="etb-current-template-name-section" style="display:none;">
             <label for="etb-current-template-name"><?php esc_html_e( 'Template Name:', 'your-theme-textdomain' ); ?></label>
             <input type="text" id="etb-current-template-name" placeholder="<?php esc_attr_e( 'Enter template name', 'your-theme-textdomain' ); ?>" />
        </div>
        <div id="etb-controls-section">
            <h4><?php esc_html_e( 'Editable Sections', 'your-theme-textdomain' ); ?></h4>
            <div id="etb-sortable-items-container">
                <!-- Dynamic controls and sortable sections will be loaded here by JS -->
                <p class="etb-no-controls-message"><?php esc_html_e( 'Select or create a template, then load its structure to see editable sections.', 'your-theme-textdomain' ); ?></p>
            </div>
        </div>
    </div>

    <div id="etb-main-preview-area">
        <div id="etb-preview-tabs">
            <ul class="etb-preview-tab-nav">
                <li><a href="#etb-preview-en"><?php esc_html_e( 'EN', 'your-theme-textdomain' ); ?></a></li>
                <li><a href="#etb-preview-pt"><?php esc_html_e( 'PT', 'your-theme-textdomain' ); ?></a></li>
                <li><a href="#etb-preview-es"><?php esc_html_e( 'ES', 'your-theme-textdomain' ); ?></a></li>
            </ul>
            <div id="etb-preview-en" class="etb-preview-tab-content">
                <iframe id="etb-iframe-en" title="<?php esc_attr_e( 'English Preview', 'your-theme-textdomain' ); ?>"></iframe>
            </div>
            <div id="etb-preview-pt" class="etb-preview-tab-content">
                <iframe id="etb-iframe-pt" title="<?php esc_attr_e( 'Portuguese Preview', 'your-theme-textdomain' ); ?>"></iframe>
            </div>
            <div id="etb-preview-es" class="etb-preview-tab-content">
                <iframe id="etb-iframe-es" title="<?php esc_attr_e( 'Spanish Preview', 'your-theme-textdomain' ); ?>"></iframe>
            </div>
        </div>
    </div>

    <div id="etb-action-bar">
        <button id="etb-save-template-button" class="button button-primary" disabled><?php esc_html_e( 'Save Template', 'your-theme-textdomain' ); ?></button>
        <button id="etb-export-html-button" class="button" disabled><?php esc_html_e( 'Export HTML', 'your-theme-textdomain' ); ?></button>
        <button id="etb-reset-template-button" class="button" disabled><?php esc_html_e( 'Reset Changes', 'your-theme-textdomain' ); ?></button>
    </div>
</div>

<?php
// It's important that this template calls wp_footer() for scripts to be loaded.
get_footer();
?>
