<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Email_Template_Builder
 * @subpackage Email_Template_Builder/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also NTS:
 * - main plugin file: email-template-builder.php
 * - loader: includes/class-email-template-builder-loader.php (will create this)
 * - public hooks: public/class-email-template-builder-public.php (will create this)
 * - admin hooks (if any needed beyond CPT): admin/class-email-template-builder-admin.php (will create this)
 *
 * @since      1.0.0
 * @package    Email_Template_Builder
 * @subpackage Email_Template_Builder/includes
 * @author     Jules <your-email@example.com>
 */
class Email_Template_Builder {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Email_Template_Builder_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'ETB_VERSION' ) ) {
            $this->version = ETB_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'email-template-builder';

        $this->load_dependencies();
        $this->set_locale();
        // $this->define_admin_hooks(); // We might not need admin-specific hooks if it's all frontend
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Email_Template_Builder_Loader. Orchestrates the hooks of the plugin.
     * - Email_Template_Builder_i18n. Defines internationalization functionality.
     * - Email_Template_Builder_Admin. Defines all hooks for the admin area.
     * - Email_Template_Builder_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once ETB_PLUGIN_DIR . 'includes/class-email-template-builder-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once ETB_PLUGIN_DIR . 'includes/class-email-template-builder-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         * We might not need this if the app is purely frontend.
         */
        // require_once ETB_PLUGIN_DIR . 'admin/class-email-template-builder-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once ETB_PLUGIN_DIR . 'public/class-email-template-builder-public.php';

        $this->loader = new Email_Template_Builder_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Email_Template_Builder_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Email_Template_Builder_i18n();

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    // private function define_admin_hooks() {
        // $plugin_admin = new Email_Template_Builder_Admin( $this->get_plugin_name(), $this->get_version() );
        // This is where we would add admin-specific actions and filters.
        // For example, if we needed a settings page.
        // $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_options_page' );
        // $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
    // }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new Email_Template_Builder_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        // Add shortcode
        $this->loader->add_shortcode( 'email_template_builder', $plugin_public, 'display_email_template_builder' );

        // AJAX handlers for loading and saving templates
        $this->loader->add_action( 'wp_ajax_etb_load_templates', $plugin_public, 'ajax_load_templates' );
        $this->loader->add_action( 'wp_ajax_etb_save_template', $plugin_public, 'ajax_save_template' );
        $this->loader->add_action( 'wp_ajax_etb_delete_template', $plugin_public, 'ajax_delete_template' );
        $this->loader->add_action( 'wp_ajax_etb_clone_template', $plugin_public, 'ajax_clone_template' );
        // Note: For AJAX actions available to logged-out users, use wp_ajax_nopriv_action_name as well.
        // Since this is for logged-in users only, wp_ajax_ is sufficient.
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Email_Template_Builder_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
