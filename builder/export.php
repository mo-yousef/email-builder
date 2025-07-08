<?php
/**
 * Handles HTML export of email templates.
 *
 * @package EmailTemplateBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Action hook for handling the HTML export of an email template.
 *
 * This function is triggered by visiting `admin.php?action=etb_export_template_html`.
 * It verifies nonce and user permissions, then fetches the template data,
 * renders it into an HTML string using helper functions, and serves it as
 * a downloadable HTML file.
 *
 * @since 1.0.0
 * @uses etb_get_translatable_labels_config()
 * @uses etb_render_section_for_export()
 * @return void Outputs HTML file or dies with an error message.
 */
add_action( 'admin_action_etb_export_template_html', 'etb_handle_export_template_html' );

/**
 * Handles the actual logic for exporting an email template to HTML.
 *
 * @since 1.0.0
 */
function etb_handle_export_template_html() {
    // Verify nonce.
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'etb_export_template_html_nonce' ) ) {
        wp_die( esc_html__( 'Security check failed. Could not export template.', 'email-template-builder' ), 'Nonce Verification Failed', array( 'response' => 403 ) );
    }

    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) { // Or a more specific capability
        wp_die( esc_html__( 'You do not have sufficient permissions to export templates.', 'email-template-builder' ), 'Permission Denied', array( 'response' => 403 ) );
    }

    $template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;
    $lang_to_export = isset( $_GET['lang'] ) ? sanitize_key( $_GET['lang'] ) : 'en'; // Default to English

    if ( ! $template_id ) {
        wp_die( esc_html__( 'No template ID provided for export.', 'email-template-builder' ), 'Missing ID', array( 'response' => 400 ) );
    }

    $post = get_post( $template_id );
    if ( ! $post || $post->post_type !== 'email_template' ) {
        wp_die( esc_html__( 'Invalid template ID or template not found.', 'email-template-builder' ), 'Invalid ID', array( 'response' => 404 ) );
    }

    $template_structure_json = get_post_meta( $template_id, '_template_structure', true );
    $sections = ! empty( $template_structure_json ) ? json_decode( $template_structure_json, true ) : array();
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        wp_die( esc_html__( 'Error decoding template structure.', 'email-template-builder' ), 'JSON Error', array( 'response' => 500 ) );
    }

    // Get all translatable labels (similar to how it's done in admin-page.php for JS)
    // This should ideally come from a shared source or helper function.
    $translatable_labels_raw = etb_get_translatable_labels_config(); // Centralized function

    $sections_html = '';
    foreach ( $sections as $section ) {
        $sections_html .= etb_render_section_for_export( $section, $lang_to_export, $translatable_labels_raw );
    }

    // Basic Email Wrapper Styles - these should be inlined for max compatibility
    $email_body_style = "margin:0; padding:0; background-color:#f0f0f0; font-family: Arial, sans-serif; font-size: 14px; line-height:1.6;";
    $email_container_style = "max-width:600px; margin:20px auto; background-color:#ffffff; border:1px solid #dddddd; padding:20px;";
    // More specific styles will be inlined per section by etb_render_section_for_export

    $full_html = sprintf(
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="%s">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>%s</title>
    <!-- It is generally recommended to inline all CSS for email -->
    <style type="text/css">
        body { %s }
        .email-container { %s }
        /* Other global styles that might not be fully inlined by all tools, but primarily rely on inline */
        img { display: block; max-width: 100%%; height: auto; }
    </style>
</head>
<body style="%s">
    <table width="100%%" border="0" cellpadding="0" cellspacing="0" bgcolor="#f0f0f0">
        <tr>
            <td align="center" valign="top">
                <table class="email-container" width="600" border="0" cellpadding="0" cellspacing="0" style="%s">
                    <tr>
                        <td>
                            %s
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>',
        esc_attr( $lang_to_export ),
        esc_html( $post->post_title . " (" . strtoupper($lang_to_export) . ")" ),
        $email_body_style, // For <style> block
        $email_container_style, // For <style> block
        $email_body_style, // For <body> inline style
        $email_container_style, // For .email-container table inline style
        $sections_html // Content is already processed by etb_render_section_for_export
    );

    $filename = sanitize_file_name( $post->post_title . '_' . $lang_to_export . '.html' );
    header( 'Content-Type: text/html; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    echo $full_html; //
    exit;
}

?>
