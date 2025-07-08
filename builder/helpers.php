<?php
/**
 * Helper functions for the Email Template Builder.
 *
 * @package EmailTemplateBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Retrieves the configuration for translatable labels.
 * This function serves as a central point for defining or fetching the
 * dictionary of predefined translatable labels used throughout the builder.
 *
 * @since 1.0.0
 * @return array The array of translatable labels, structured as [key => [lang_code => translation]].
 */
function etb_get_translatable_labels_config() {
    // This could be expanded to load from a filter, theme option, or a dedicated config file.
    // Example: return apply_filters('etb_translatable_labels', $default_labels);
	return array(
        'greeting_team' => array( 'en' => 'Hi Team', 'pt' => 'Olá equipe', 'es' => 'Hola equipo'),
        'thank_you'     => array( 'en' => 'Thank you', 'pt' => 'Obrigado', 'es' => 'Gracias'),
        'signature'     => array( 'en' => 'Best regards', 'pt' => 'Atenciosamente', 'es' => 'Saludos cordiales'),
        // Add more predefined snippets here
    );
}

/**
 * Renders a single section into email-compatible HTML for export.
 *
 * This function takes a section object (as stored in the post meta) and generates
 * the corresponding HTML markup suitable for email clients. It handles different
 * section types, processes translatable snippets, and ensures basic email styling
 * (table-based layout, inline styles where appropriate).
 *
 * @since 1.0.0
 * @param array  $section             The section data array. Expected keys: 'type', 'content'.
 *                                    'content' structure varies by type.
 * @param string $lang                The language code (e.g., 'en', 'pt', 'es') for which to render the content.
 * @param array  $translatable_labels The full array of translatable labels, passed from etb_get_translatable_labels_config().
 * @return string                     The generated HTML string for the section.
 */
function etb_render_section_for_export( $section, $lang, $translatable_labels ) { // This function might become obsolete or only for generic fallback
    // This function's logic is being superseded by etb_render_specialized_section_content_for_export
    // and the master template approach for the "Holiday Notification" template.
    // For now, retain its structure for any generic sections that might still be used or for other template types.

    $html_output  = '';
    $section_type = isset( $section['type'] ) ? $section['type'] : 'text';
    $content_data = isset( $section['content'] ) ? $section['content'] : array();

    $get_localized_value_func = function( $field_content, $current_lang, $default_lang = 'en' ) {
        if ( is_array( $field_content ) ) {
            return isset( $field_content[$current_lang] ) && !empty($field_content[$current_lang])
                   ? $field_content[$current_lang]
                   : (isset( $field_content[$default_lang] ) ? $field_content[$default_lang] : '');
        }
        return strval( $field_content );
    };

    $process_text_func = function( $text_input ) use ( $translatable_labels, $lang ) {
        if ( !is_string($text_input) ) {
            return '';
        }
        $processed_text = preg_replace_callback(
            '/\{\{snippet:([a-zA-Z0-9_]+)\}\}/',
            function( $matches ) use ( $translatable_labels, $lang ) {
                $snippet_key = $matches[1];
                if ( isset( $translatable_labels[$snippet_key] ) && isset( $translatable_labels[$snippet_key][$lang] ) ) {
                    return $translatable_labels[$snippet_key][$lang];
                }
                return $matches[0]; // Return original placeholder if not found
            },
            $text_input
        );
        // Note: Dynamic variables {{variable}} are intentionally left as is (not processed by this function).
        // They will be part of the $processed_text.
        return $processed_text;
    };


    // --- Outer Table for Spacing (common email practice) ---
    // This provides consistent spacing around each content block.
    $html_output .= '<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 10px;">';
    $html_output .= '<tr><td>';

    // This function will now primarily handle generic types if they are still used.
    // Specialized types for "Holiday Notification" will be handled by etb_render_specialized_section_content_for_export.
    switch ( $section_type ) {
        case 'text': // Generic text
            $text_content = $get_localized_value_func( $content_data, $lang );
            $processed_content = $process_text_for_snippets_and_vars( $text_content );
            $text_style = 'font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #333333; padding:10px;';
            $html_output .= sprintf(
                '<table width="100%%" border="0" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="%s">%s</td></tr></table>',
                esc_attr( $text_style ),
                nl2br( esc_html( $processed_content ) )
            );
            break;
        case 'image': // Generic image
            $image_url = $get_localized_value( isset($content_data['url']) ? $content_data['url'] : '', $lang );
            $alt_text  = $process_text_for_snippets_and_vars( $get_localized_value( isset($content_data['alt']) ? $content_data['alt'] : '', $lang ) );
            if ( !empty($image_url) ) {
                 $html_output .= sprintf(
                    '<table width="100%%" border="0" cellpadding="0" cellspacing="0" role="presentation"><tr><td align="center" style="padding: 10px;"><img src="%s" alt="%s" style="display:block; max-width:100%%; height:auto; border:0;" /></td></tr></table>',
                    esc_url( $image_url ),
                    esc_attr( $alt_text )
                );
            }
            break;
        case 'button': // Generic button
            $button_text_raw = $get_localized_value_func( isset($content_data['text']) ? $content_data['text'] : '', $lang );
            $button_text     = $process_text_func( $button_text_raw );
            $button_url      = $get_localized_value_func( isset($content_data['url']) ? $content_data['url'] : '#', $lang );
            $button_bg_color = isset($content_data['bgColor']) ? sanitize_hex_color($content_data['bgColor']) : '#007bff';

            $button_table_style = 'text-align: center;';
            $button_td_style    = sprintf('background-color:%s; border-radius:5px; padding:12px 25px;', esc_attr($button_bg_color));
            $button_link_style  = 'font-family: Arial, sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; display:inline-block;';

            if (!empty($button_text)) {
                $html_output .= sprintf(
                    '<table width="100%%" border="0" cellspacing="0" cellpadding="0" role="presentation" style="%s"><tr><td align="center" style="padding:10px;"><table border="0" cellspacing="0" cellpadding="0" role="presentation"><tr><td align="center" style="%s"><a href="%s" target="_blank" style="%s">%s</a></td></tr></table></td></tr></table>',
                    esc_attr($button_table_style), esc_attr($button_td_style), esc_url($button_url), esc_attr($button_link_style), esc_html($button_text)
                );
            }
            break;
        case 'divider': // Generic divider
            $divider_style = 'border-top:1px solid #dddddd; height:1px; line-height:1px; font-size:0px; margin:15px 0; padding:10px 0;'; // Added padding to td
            $html_output .= sprintf(
                '<table width="100%%" border="0" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="%s"><div style="margin:0 auto; width:100%%; border-top:1px solid #dddddd; height:1px; line-height:1px; font-size:0px;">&nbsp;</div></td></tr></table>', // Simpler divider
                'padding:10px 0;'
            );
            break;
        default: // Fallback for unknown or specialized types not handled here explicitly
            $html_output .= sprintf(
                '<table width="100%%" border="0" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="padding:10px; color:red; text-align:center;">Info: Section type "%s" content will be injected directly by master template logic.</td></tr></table>',
                esc_html( $section_type )
            );
            break;
    }

    $html_output .= '</td></tr></table>';
    return $html_output;
}

/**
 * Gets localized text from a content array.
 *
 * @param array|string $content_array The content array (e.g., $section['content']['date_header_1']).
 * @param string       $lang          The target language code.
 * @param string       $default_lang  Optional. The default/fallback language code.
 * @return string The localized text or an empty string.
 */
function etb_get_localized_text_from_content( $content_array, $lang, $default_lang = 'en' ) {
    if ( is_array( $content_array ) ) {
        if ( isset( $content_array[$lang] ) && ! empty( $content_array[$lang] ) ) {
            return $content_array[$lang];
        } elseif ( isset( $content_array[$default_lang] ) ) {
            return $content_array[$default_lang];
        }
    } elseif ( is_string( $content_array) ) { // For non-multilingual fields or older data
        return $content_array;
    }
    return '';
}


/**
 * Renders the specific content snippet for specialized sections for the Holiday Notification template.
 * This function returns only the inner HTML content/data, not full table wrappers,
 * as those are expected to be part of the master layout.
 *
 * @since 1.0.0
 * @param array  $section             The section data array.
 * @param string $lang                The language code.
 * @param array  $translatable_labels The array of translatable labels.
 * @return string                     The HTML content snippet for the section.
 */
function etb_render_specialized_section_content_for_export( $section, $lang, $translatable_labels ) {
    $content_data = isset( $section['content'] ) ? $section['content'] : array();
    $section_type = isset( $section['type'] ) ? $section['type'] : 'text';

    // Re-use helper functions defined in etb_render_section_for_export or make them global if needed
    // For simplicity, re-defining them here scoped to this function or assuming they are available.
    $get_localized_value_func = function( $field_content, $current_lang, $default_lang = 'en' ) {
         if ( is_array( $field_content ) ) {
            return isset( $field_content[$current_lang] ) && !empty($field_content[$current_lang])
                   ? $field_content[$current_lang]
                   : (isset( $field_content[$default_lang] ) ? $field_content[$default_lang] : '');
        }
        return strval( $field_content );
    };
    $process_text_func = function( $text_input ) use ( $translatable_labels, $lang ) {
        if ( !is_string($text_input) ) return '';
        $processed = preg_replace_callback(
            '/\{\{snippet:([a-zA-Z0-9_]+)\}\}/',
            function( $matches ) use ( $translatable_labels, $lang ) {
                $key = $matches[1];
                return isset( $translatable_labels[$key][$lang] ) ? $translatable_labels[$key][$lang] : $matches[0];
            }, $text_input);
        // Dynamic variables {{var}} are left as is
        return $processed;
    };

    switch ( $section_type ) {
        case 'greeting_text':
        case 'main_paragraph':
        case 'closing_text':
            $text_content = $get_localized_value_func( $content_data, $lang );
            $processed_content = $process_text_func( $text_content );
            // For these types, the master template already has the <p> tags or appropriate wrappers.
            // We just return the processed text, which will be nl2br(esc_html()) by the calling function if needed.
            return nl2br( esc_html( $processed_content ) ); // Match styling of original template text blocks

        case 'trading_row_item': // This renders a single <tr> for the trading schedule
            $instrument  = $process_text_func( $get_localized_value_func( isset($content_data['instrument']) ? $content_data['instrument'] : '', $lang ) );
            $time_status = $process_text_func( $get_localized_value_func( isset($content_data['time_status']) ? $content_data['time_status'] : '', $lang ) );

            // This HTML should match one row of the tables in the master layout (e.g. row-8's table)
            // The master template has the surrounding table structure, border, padding for the group.
            // This returns just the two cells for a single row.
            return sprintf(
                '<tr>
                    <td width="50%%" style="vertical-align: top; padding: 10px; word-break: break-word;"><strong>%s</strong></td>
                    <td width="50%%" style="vertical-align: top; padding: 10px; word-break: break-word;">%s</td>
                </tr>',
                esc_html( $instrument ),
                esc_html( $time_status )
            );

        // Generic types - if they are still used directly and need specific content extraction
        // For the "Holiday Notification" template, these might not be directly injected using these specific placeholder types.
        case 'text':
             $text_content = $get_localized_value_func( $content_data, $lang );
             return nl2br(esc_html($process_text_func($text_content)));
        case 'image':
            $image_url = $get_localized_value_func( isset($content_data['url']) ? $content_data['url'] : '', $lang );
            $alt_text  = $process_text_func( $get_localized_value_func( isset($content_data['alt']) ? $content_data['alt'] : '', $lang ) );
            if (!empty($image_url)) {
                return sprintf('<img src="%s" alt="%s" style="display:block; max-width:100%%; height:auto; border:0;" />', esc_url($image_url), esc_attr($alt_text));
            }
            return '';
        case 'button':
            $button_text_raw = $get_localized_value_func( isset($content_data['text']) ? $content_data['text'] : '', $lang );
            $button_text     = $process_text_func( $button_text_raw );
            $button_url      = $get_localized_value_func( isset($content_data['url']) ? $content_data['url'] : '#', $lang );
            $button_bg_color = isset($content_data['bgColor']) ? sanitize_hex_color($content_data['bgColor']) : '#007bff';
            $button_td_style = sprintf('background-color:%s; border-radius:5px; padding:12px 25px;', esc_attr($button_bg_color));
            $button_link_style  = 'font-family: Arial, sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; display:inline-block;';
             if (!empty($button_text)) {
                // This returns the inner part of a button, assuming master template has surrounding table for centering
                return sprintf('<table border="0" cellspacing="0" cellpadding="0" role="presentation"><tr><td align="center" style="%s"><a href="%s" target="_blank" style="%s">%s</a></td></tr></table>',
                    esc_attr($button_td_style), esc_url($button_url), esc_attr($button_link_style), esc_html($button_text)
                );
            }
            return '';
        case 'divider':
            return '<div style="border-top:1px solid #dddddd; height:1px; line-height:1px; font-size:0px; margin:15px 0;">&nbsp;</div>';

        default:
            return sprintf( '<p style="color:red;">Cannot render content for section type: %s</p>', esc_html( $section_type ) );
    }
}
?>
