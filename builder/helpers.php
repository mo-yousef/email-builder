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
function etb_render_section_for_export( $section, $lang, $translatable_labels ) {
    $html_output  = '';
    $section_type = isset( $section['type'] ) ? $section['type'] : 'text'; // Default to text if type is missing
    $content_data = isset( $section['content'] ) ? $section['content'] : array();

    /**
	 * Helper function to get localized content for a specific field within a section's content.
	 *
	 * @param array|string $field_content The content part for a specific field (e.g., $content_data['url'] or $content_data itself for text).
	 * @param string       $current_lang  The target language.
	 * @param string       $default_lang  The fallback language (usually 'en').
	 * @return string The localized string or an empty string.
	 */
    $get_localized_value = function( $field_content, $current_lang, $default_lang = 'en' ) {
        if ( is_array( $field_content ) ) {
            return isset( $field_content[$current_lang] ) && !empty($field_content[$current_lang])
                   ? $field_content[$current_lang]
                   : (isset( $field_content[$default_lang] ) ? $field_content[$default_lang] : '');
        }
        return strval( $field_content ); // If it's already a simple string (e.g. older text content structure)
    };

    // Process snippets for text-based content fields (applies to text, button text, image alt)
    $process_text_for_snippets_and_vars = function( $text_input ) use ( $translatable_labels, $lang ) {
        if ( !is_string($text_input) ) {
            return '';
        }
        // Process snippets
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
    $html_output .= '<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 10px;">'; // Added margin-bottom for spacing
    $html_output .= '<tr><td>'; // Removed default padding, will be handled by inner content tables

    switch ( $section_type ) {
        case 'text':
            $text_content = $get_localized_value( $content_data, $lang ); // For text, content_data is the multilingual text object
            $processed_content = $process_text_for_snippets_and_vars( $text_content );
            $text_style = 'font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #333333; padding:10px;';
            $html_output .= sprintf(
                '<table width="100%%" border="0" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="%s">%s</td></tr></table>',
                esc_attr( $text_style ),
                nl2br( esc_html( $processed_content ) ) // Content is already processed for snippets, now escape and nl2br
            );
            break;

        case 'image':
            $image_url = $get_localized_value( isset($content_data['url']) ? $content_data['url'] : '', $lang );
            $alt_text  = $process_text_for_snippets_and_vars( $get_localized_value( isset($content_data['alt']) ? $content_data['alt'] : '', $lang ) );

            if ( !empty($image_url) ) {
                 $html_output .= sprintf(
                    '<table width="100%%" border="0" cellpadding="0" cellspacing="0" role="presentation"><tr><td align="center" style="padding: 10px;"><img src="%s" alt="%s" style="display:block; max-width:100%%; height:auto; border:0;" /></td></tr></table>',
                    esc_url( $image_url ), // URL should be clean
                    esc_attr( $alt_text )  // Alt text is processed for snippets then escaped
                );
            }
            break;

        case 'button':
            $button_text     = $text_content; // Already processed for snippets
            $button_url      = isset($content_data['url'][$lang]) ? $content_data['url'][$lang] : (isset($content_data['url']['en']) ? $content_data['url']['en'] : '#');
            $button_bg_color = isset($content_data['bgColor']) ? sanitize_hex_color($content_data['bgColor']) : '#007bff'; // Default color

            // Basic button styling - more can be added
            // VML for Outlook is often needed for rounded corners etc., but keeping it simpler for now.
            $button_table_style = 'text-align: center;'; // Centers the button table
            $button_td_style    = sprintf(
                'background-color:%s; border-radius:5px; padding:12px 25px;', // Padding for button size
                esc_attr($button_bg_color)
            );
            $button_link_style  = 'font-family: Arial, sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; display:inline-block;';

            if (!empty($button_text)) {
                $html_output .= sprintf(
                    '<table width="100%%" border="0" cellspacing="0" cellpadding="0" role="presentation" style="%s"><tr><td align="center"><table border="0" cellspacing="0" cellpadding="0" role="presentation"><tr><td align="center" style="%s"><a href="%s" target="_blank" style="%s">%s</a></td></tr></table></td></tr></table>',
                    esc_attr($button_table_style),
                    esc_attr($button_td_style),
                    esc_url($button_url),
                    esc_attr($button_link_style),
                    esc_html($button_text)
                );
            }
            break;

        case 'divider':
            $divider_style = 'border-top:1px solid #dddddd; height:1px; line-height:1px; font-size:0px; margin:15px 0;';
            $html_output .= sprintf(
                '<table width="100%%" border="0" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="padding:10px 0;"><div style="%s">&nbsp;</div></td></tr></table>',
                esc_attr($divider_style)
            );
            break;

        default:
            $html_output .= sprintf(
                '<table width="100%%" border="0" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="padding:10px; color:red; text-align:center;">Unsupported section type: %s</td></tr></table>',
                esc_html( $section['type'] )
            );
            break;
    }

    $html_output .= '</td></tr></table>'; // End of outer spacing table cell and table
    return $html_output;
}

?>
