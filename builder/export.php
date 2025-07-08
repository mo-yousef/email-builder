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
    $translatable_labels_raw = etb_get_translatable_labels_config();

    // Load the master HTML layout
    $master_layout_path = get_template_directory() . '/builder/layouts/holiday-notification-master.html.php';
    if ( ! file_exists( $master_layout_path ) ) {
        wp_die( esc_html__( 'Master layout file not found.', 'email-template-builder' ), 'Layout Error', array( 'response' => 500 ) );
    }
    ob_start();
    include $master_layout_path; // Include it to execute PHP if any, but for now it's treated as plain HTML
    $full_html = ob_get_clean();

    // Replace placeholders with content
    $full_html = str_replace( '<!-- ETB_TEMPLATE_TITLE -->', esc_html( $post->post_title ), $full_html );

    // Prepare content for each placeholder
    $placeholder_contents = array(
        '<!-- ETB_GREETING_TEXT_START -->' => '', // Start and end will be handled together
        '<!-- ETB_MAIN_PARAGRAPH_START -->' => '',
        '<!-- ETB_ADJUSTED_TIMETABLE_TITLE_START -->' => '', // Assuming static for now or handled by a snippet
        '<!-- ETB_SCHEDULE_DATE_HEADER_1_START -->' => '',
        '<!-- ETB_TRADING_ROWS_THURSDAY_START -->' => '',
        '<!-- ETB_SCHEDULE_DATE_HEADER_2_START -->' => '',
        '<!-- ETB_TRADING_ROWS_FRIDAY_START -->' => '',
        '<!-- ETB_CLOSING_TEXT_START -->' => '',
        '<!-- ETB_FOOTER_CONTENT_START -->' => '' // Assuming static for now
    );

    $trading_rows_day1_html = '';
    $trading_rows_day2_html = '';

    foreach ( $sections as $section ) {
        $section_type = isset( $section['type'] ) ? $section['type'] : 'text';
        $content_html = etb_render_specialized_section_content_for_export( $section, $lang_to_export, $translatable_labels_raw );

        switch ( $section_type ) {
            case 'greeting_text':
                $placeholder_contents['<!-- ETB_GREETING_TEXT_START -->'] = $content_html;
                break;
            case 'main_paragraph':
                $placeholder_contents['<!-- ETB_MAIN_PARAGRAPH_START -->'] = $content_html;
                break;
            case 'trading_schedule':
                // Date Header 1
                $date_header_1_content = isset($section['content']['date_header_1'])
                    ? etb_get_localized_text_from_content($section['content']['date_header_1'], $lang_to_export)
                    : '';
                $placeholder_contents['<!-- ETB_SCHEDULE_DATE_HEADER_1_START -->'] = esc_html($date_header_1_content);

                // Rows for Day 1
                if (isset($section['content']['rows_1']) && is_array($section['content']['rows_1'])) {
                    foreach ($section['content']['rows_1'] as $row_item) {
                        $trading_rows_day1_html .= etb_render_specialized_section_content_for_export(
                            array('type' => 'trading_row_item', 'content' => $row_item), // Adapt to expected structure
                            $lang_to_export,
                            $translatable_labels_raw
                        );
                    }
                }
                $placeholder_contents['<!-- ETB_TRADING_ROWS_THURSDAY_START -->'] = $trading_rows_day1_html;

                // Date Header 2
                 $date_header_2_content = isset($section['content']['date_header_2'])
                    ? etb_get_localized_text_from_content($section['content']['date_header_2'], $lang_to_export)
                    : '';
                $placeholder_contents['<!-- ETB_SCHEDULE_DATE_HEADER_2_START -->'] = esc_html($date_header_2_content);

                // Rows for Day 2
                 if (isset($section['content']['rows_2']) && is_array($section['content']['rows_2'])) {
                    foreach ($section['content']['rows_2'] as $row_item) {
                        $trading_rows_day2_html .= etb_render_specialized_section_content_for_export(
                            array('type' => 'trading_row_item', 'content' => $row_item), // Adapt to expected structure
                            $lang_to_export,
                            $translatable_labels_raw
                        );
                    }
                }
                $placeholder_contents['<!-- ETB_TRADING_ROWS_FRIDAY_START -->'] = $trading_rows_day2_html;
                break;
            case 'closing_text':
                $placeholder_contents['<!-- ETB_CLOSING_TEXT_START -->'] = $content_html;
                break;
        }
    }

    // Replace all placeholders
    foreach ($placeholder_contents as $placeholder_start => $content) {
        $placeholder_end = str_replace('_START -->', '_END -->', $placeholder_start);
        // For simple replacement of single line content
        if (strpos($placeholder_start, '_ROWS_') === false && strpos($placeholder_start, '_HEADER_') === false && $placeholder_start !== '<!-- ETB_FOOTER_CONTENT_START -->') {
             // Find the original block in master layout and replace its content
            $pattern = '/' . preg_quote($placeholder_start, '/') . '.*?' . preg_quote($placeholder_end, '/') . '/s';
            $replacement = $placeholder_start . "\n" . $content . "\n" . $placeholder_end;
            $full_html = preg_replace($pattern, $replacement, $full_html, 1);
        } else {
            // For row groups or headers that are directly replaced
            $full_html = str_replace($placeholder_start, $content, $full_html);
            $full_html = str_replace($placeholder_end, '', $full_html); // Remove end placeholder for these
        }
    }
    // Remove any remaining END placeholders if their START was not found or content was empty
    $full_html = preg_replace('/<!-- ETB_[A-Z0-9_]+_END -->/', '', $full_html);


    $filename = sanitize_file_name( $post->post_title . '_' . $lang_to_export . '.html' );
    header( 'Content-Type: text/html; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    echo $full_html; //
    exit;
}

?>
