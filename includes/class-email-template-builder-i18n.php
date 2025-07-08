<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Email_Template_Builder
 * @subpackage Email_Template_Builder/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Email_Template_Builder
 * @subpackage Email_Template_Builder/includes
 * @author     Jules <your-email@example.com>
 */
class Email_Template_Builder_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain(
            'email-template-builder',
            false,
            dirname( dirname( plugin_basename( ETB_PLUGIN_FILE ) ) ) . '/languages/'
        );

    }

}
