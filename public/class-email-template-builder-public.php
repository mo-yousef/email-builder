<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Email_Template_Builder
 * @subpackage Email_Template_Builder/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Email_Template_Builder
 * @subpackage Email_Template_Builder/public
 * @author     Jules <your-email@example.com>
 */
class Email_Template_Builder_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Only enqueue if the shortcode is present or on the specific page template
        // This check will be more robust later
        if ( is_singular() && has_shortcode( get_post_field('post_content', get_the_ID()), 'email_template_builder' ) ) {
            wp_enqueue_style( $this->plugin_name, ETB_PLUGIN_URL . 'public/css/email-template-builder-public.css', array(), $this->version, 'all' );
            // Enqueue jQuery UI styles - using a CDN for now, can be bundled later.
            wp_enqueue_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', array(), '1.13.2' );
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only enqueue if the shortcode is present or on the specific page template
        if ( is_singular() && has_shortcode( get_post_field('post_content', get_the_ID()), 'email_template_builder' ) ) {
            wp_enqueue_script( 'jquery' ); // Ensure jQuery is loaded
            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-dialog' ); // For modals if needed

            wp_enqueue_script( $this->plugin_name, ETB_PLUGIN_URL . 'public/js/email-template-builder-public.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-dialog' ), $this->version, true );

            // Localize script for AJAX
            wp_localize_script( $this->plugin_name, 'etb_ajax_obj', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'etb_nonce_action' ) // Will be used for security
            ) );
        }
    }

    /**
     * Callback for the [email_template_builder] shortcode.
     *
     * @since    1.0.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output for the email template builder.
     */
    public function display_email_template_builder( $atts ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You must be logged in to use the Email Template Builder.', 'email-template-builder' ) . '</p>';
        }

        // Prepare attributes - none defined for now, but good practice
        // $atts = shortcode_atts( array(), $atts, 'email_template_builder' );

        // Start output buffering
        ob_start();

        // We will load the HTML structure of the app here in a later step.
        // For now, a placeholder:
        echo '<div id="etb-app-container">';
        echo '  <div id="etb-sidebar-panel">Sidebar controls will go here.</div>';
        echo '  <div id="etb-main-preview-area">';
        echo '      <div id="etb-preview-tabs"><ul><li><a href="#etb-preview-en">EN</a></li><li><a href="#etb-preview-pt">PT</a></li><li><a href="#etb-preview-es">ES</a></li></ul>';
        echo '          <div id="etb-preview-en"><iframe id="etb-iframe-en"></iframe></div>';
        echo '          <div id="etb-preview-pt"><iframe id="etb-iframe-pt"></iframe></div>';
        echo '          <div id="etb-preview-es"><iframe id="etb-iframe-es"></iframe></div>';
        echo '      </div>';
        echo '  </div>';
        echo '  <div id="etb-action-bar">Save, Export, Reset buttons will go here.</div>';
        echo '</div>';


        // Get the buffered content
        $output = ob_get_clean();
        return $output;
    }

    /**
     * AJAX handler for loading templates.
     * @since 1.0.0
     */
    public function ajax_load_templates() {
        check_ajax_referer( 'etb_nonce_action', 'nonce' );
        // Logic to load templates from DB will go here
        wp_send_json_success( array( 'message' => 'Templates would be loaded here.' ) );
    }

    /**
     * AJAX handler for saving a template.
     * @since 1.0.0
     */
    public function ajax_save_template() {
        check_ajax_referer( 'etb_nonce_action', 'nonce' );
        // Logic to save template to DB will go here
        // Expected $_POST data: template_id (optional), name, html_en, html_pt, html_es, sections_order, etc.
        wp_send_json_success( array( 'message' => 'Template would be saved here.', 'data' => $_POST ) );
    }

    /**
     * AJAX handler for deleting a template.
     * @since 1.0.0
     */
    public function ajax_delete_template() {
        check_ajax_referer( 'etb_nonce_action', 'nonce' );
        // Logic to delete template from DB will go here
        // Expected $_POST data: template_id
        wp_send_json_success( array( 'message' => 'Template would be deleted here.', 'data' => $_POST ) );
    }

    /**
     * AJAX handler for cloning a template.
     * @since 1.0.0
     */
    public function ajax_clone_template() {
        check_ajax_referer( 'etb_nonce_action', 'nonce' );
        // Logic to clone template in DB will go here
        // Expected $_POST data: template_id_to_clone
        wp_send_json_success( array( 'message' => 'Template would be cloned here.', 'data' => $_POST ) );
    }

}
