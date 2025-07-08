<?php
/**
 * Admin Page for Email Template Builder.
 * Handles the registration of the admin menu and renders the builder page.
 *
 * @package EmailTemplateBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Adds the top-level admin menu page for the Email Template Builder.
 *
 * This function registers the main menu item that users will click to access
 * the email template builder interface.
 *
 * @since 1.0.0
 */
function etb_add_admin_menu_page() {
	add_menu_page(
		__( 'Email Template Builder', 'email-template-builder' ), // Page title that appears in <title> tag
		__( 'Email Templates', 'email-template-builder' ),    // Menu title
		'manage_options', // Capability required to see this menu
		'etb-builder',    // Menu slug
		'etb_render_builder_page', // Function to display the page content
		'dashicons-email-alt2', // Icon URL (or Dashicon class)
		30 // Position in the menu order
	);
}
add_action( 'admin_menu', 'etb_add_admin_menu_page' );

/**
 * Renders the Email Template Builder page content.
 *
 * This function acts as a router based on the 'action' query parameter.
 * It can display a list of existing templates (using WP_List_Table),
 * or the main builder interface for creating/editing a template.
 * Data for the Alpine.js component is prepared and JSON-encoded here.
 *
 * @since 1.0.0
 * @global string $action Current action derived from query parameters.
 * @global int    $template_id Current template ID if editing/cloning.
 */
function etb_render_builder_page() {
    // Determine current action (list, edit, add_new, clone)
    $action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
    $template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;

    $initial_template_data = array(); // Initialize to ensure it's always an array

    // Prepare data for the Alpine.js component based on action
    if ( $action === 'edit' && $template_id ) {
        $post = get_post( $template_id );
        if ($post && $post->post_type === 'email_template') {
            $template_structure_json = get_post_meta($template_id, '_template_structure', true);
            $sections = !empty($template_structure_json) ? json_decode($template_structure_json, true) : array();
            if (json_last_error() !== JSON_ERROR_NONE) $sections = array(); // Reset if JSON is invalid

            $initial_template_data = array(
                'id' => $template_id,
                'title' => $post->post_title,
                'sections' => $sections,
            );
        } else {
            // Invalid template ID or post type, redirect to list or show error
            echo '<div class="notice notice-error"><p>' . esc_html__('Error: Template not found or invalid ID.', 'email-template-builder') . '</p></div>';
            $action = 'list'; // Fallback to list view
        }
    } elseif ($action === 'add_new') {
        // Default structure for a new template
        $initial_template_data = array(
            'id' => null, // No ID for new template
            'title' => 'New Email Template',
            'sections' => array(
                array(
                    'id' => 'section_' . uniqid(),
                    'type' => 'text',
                    'content' => array(
                        'en' => 'This is a default text block.',
                        'pt' => 'Este é um bloco de texto padrão.',
                        'es' => 'Este es un bloque de texto predeterminado.',
                    ),
                ),
            ),
        );
    } elseif ($action === 'clone' && $template_id) {
        $post = get_post($template_id);
        if ($post && $post->post_type === 'email_template') {
            $template_structure_json = get_post_meta($template_id, '_template_structure', true);
            $sections = !empty($template_structure_json) ? json_decode($template_structure_json, true) : array();
            if (json_last_error() !== JSON_ERROR_NONE) $sections = array();

            $initial_template_data = array(
                'id' => null, // Crucial: No ID, so it's treated as new
                'title' => sprintf(esc_html__('Copy of %s', 'email-template-builder'), $post->post_title),
                'sections' => $sections, // Keep the sections
            );
            // Force action to 'add_new' effectively, but with pre-filled data
            // The rest of the builder UI rendering path will handle this as a new template
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Error: Template to clone not found or invalid ID.', 'email-template-builder') . '</p></div>';
            $action = 'list'; // Fallback to list view
        }
    }


    if ($action === 'list'):
        // Display list of templates
        if ( ! class_exists( 'ETB_Templates_List_Table' ) ) {
            require_once dirname( __FILE__ ) . '/class-etb-templates-list-table.php';
        }
        $list_table = new ETB_Templates_List_Table();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Email Templates', 'email-template-builder'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=etb-builder&action=add_new')); ?>" class="page-title-action"><?php esc_html_e('Add New', 'email-template-builder'); ?></a>
            <form method="post" id="etb-list-table-form">
                <?php // The nonce for bulk actions is usually handled by WP_List_Table itself if form is present ?>
                <?php
                $list_table->display();
                ?>
            </form>
        </div>
        <?php
        // Enqueue WP List Table scripts and styles if not already done by WP core for this screen
        // Usually, this is handled automatically when extending WP_List_Table.
        return; // Stop further rendering if we are in list view
    endif;

    // If we are here, it's 'edit' or 'add_new' action, so render the builder UI
    $template_data_json = htmlspecialchars(wp_json_encode($initial_template_data), ENT_QUOTES, 'UTF-8');

    // Define dynamic variables
    $dynamic_variables = array('{{name}}', '{{email}}', '{{company_name}}', '{{event_date}}', '{{booking_id}}', '{{phone}}', '{{custom_note}}');

    // Get translatable labels using the helper function
    $translatable_labels_raw = etb_get_translatable_labels_config();
    $translatable_snippets_for_select = array();
    foreach ($translatable_labels_raw as $key => $translations) {
        $translatable_snippets_for_select[$key] = isset($translations['en']) ? $translations['en'] . " ({{snippet:$key}})" : "Snippet: $key ({{snippet:$key}})";
    }
    ?>
    <div class="wrap etb-wrap" x-data="emailTemplateBuilder(<?php echo $template_data_json; ?>)">
        <h1>
            <?php echo $initial_template_data['id'] ? esc_html__('Edit Email Template', 'email-template-builder') : esc_html__('Add New Email Template', 'email-template-builder'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=etb-builder')); ?>" class="page-title-action"><?php esc_html_e('Back to List', 'email-template-builder'); ?></a>
        </h1>

        <?php
        // Display "Last edited by" information if editing an existing template
        if ( $action === 'edit' && isset($post) && $post ) {
            $last_editor_id = get_post_meta( $post->ID, '_edit_last', true );
            $last_modified_time = get_the_modified_time( __('Y/m/d g:i:s a'), $post ); // Get formatted time

            if ( $last_editor_id ) {
                $editor = get_userdata( $last_editor_id );
                if ( $editor ) {
                    printf(
                        '<p class="etb-last-edited-notice"><small>%s</small></p>',
                        sprintf(
                            esc_html__( 'Last edited by %1$s on %2$s.', 'email-template-builder' ),
                            esc_html( $editor->display_name ),
                            esc_html( $last_modified_time )
                        )
                    );
                }
            }
        }
        ?>

        <div class="etb-top-actions">
            <label for="etb-template-title"><?php esc_html_e('Template Name:', 'email-template-builder'); ?></label>
            <input type="text" id="etb-template-title" x-model="template.title" style="min-width: 300px;">
            <button class="button button-primary" @click="saveTemplate" :disabled="isLoading">
                <span x-show="!isLoading"><?php esc_html_e('Save Template', 'email-template-builder'); ?></span>
                <span x-show="isLoading"><?php esc_html_e('Saving...', 'email-template-builder'); ?></span>
            </button>
            <button class="button" @click="exportCurrentLanguageHTML" :disabled="!template.id"><?php esc_html_e('Export HTML (Current Lang)', 'email-template-builder'); ?></button>
            <button class="button" @click="resetToLastSaved" :disabled="!template.id"><?php esc_html_e('Reset to Last Saved', 'email-template-builder'); ?></button>
        </div>

        <div id="etb-builder-ui" class="etb-builder-ui" x-show="template">
            <div class="etb-sidebar">
                <h2><?php esc_html_e('Controls', 'email-template-builder'); ?></h2>

                <div>
                    <h3><?php esc_html_e('Add New Section', 'email-template-builder'); ?></h3>
                    <div class="etb-add-section-buttons">
                        <button class="button" @click="addSection('text')"><span class="dashicons dashicons-editor-paragraph"></span> <?php esc_html_e('Text', 'email-template-builder'); ?></button>
                        <button class="button" @click="addSection('image')"><span class="dashicons dashicons-format-image"></span> <?php esc_html_e('Image', 'email-template-builder'); ?></button>
                        <button class="button" @click="addSection('button')"><span class="dashicons dashicons-button"></span> <?php esc_html_e('Button', 'email-template-builder'); ?></button>
                        <button class="button" @click="addSection('divider')"><span class="dashicons dashicons-minus"></span> <?php esc_html_e('Divider', 'email-template-builder'); ?></button>
                    </div>
                </div>
                <hr>
                 <div x-data="{ expanded: true }" class="etb-sidebar-group">
                    <h3 @click="expanded = !expanded">
                        <?php esc_html_e('Dynamic Variables', 'email-template-builder'); ?>
                        <span x-show="expanded" class="dashicons dashicons-arrow-up-alt2"></span>
                        <span x-show="!expanded" class="dashicons dashicons-arrow-down-alt2"></span>
                    </h3>
                    <div x-show="expanded" class="etb-sidebar-collapsible">
                        <p><small><?php esc_html_e('Click a variable to copy. Then paste into a text field.', 'email-template-builder'); ?></small></p>
                        <ul class="etb-variables-list">
                            <?php foreach ($dynamic_variables as $var) : ?>
                                <li @click="copyToClipboard('<?php echo esc_js($var); ?>')" title="<?php esc_attr_e('Click to copy', 'email-template-builder'); ?>"><code><?php echo esc_html($var); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <hr>
                 <div x-data="{ expanded: true }" class="etb-sidebar-group">
                    <h3 @click="expanded = !expanded">
                        <?php esc_html_e('Translatable Snippets', 'email-template-builder'); ?>
                        <span x-show="expanded" class="dashicons dashicons-arrow-up-alt2"></span>
                        <span x-show="!expanded" class="dashicons dashicons-arrow-down-alt2"></span>
                    </h3>
                     <div x-show="expanded" class="etb-sidebar-collapsible">
                        <p><small><?php esc_html_e('Select a snippet to insert its placeholder into the active text area.', 'email-template-builder'); ?></small></p>
                        <select @change="insertText($event.target.value); $event.target.value=''">
                            <option value=""><?php esc_html_e('-- Select Snippet --', 'email-template-builder'); ?></option>
                            <?php foreach ($translatable_snippets_for_select as $key => $display_text) : ?>
                                <option value="{{snippet:<?php echo esc_attr($key); ?>}}"><?php echo esc_html($display_text); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <hr>
                <h3><?php esc_html_e('Template Sections', 'email-template-builder'); ?></h3>
                <div id="etb-sections-list" class="etb-sections-list">
                    <template x-for="(section, index) in template.sections" :key="section.id">
                        <div class="etb-section" :data-id="section.id" :class="'etb-section-type-' + section.type">
                            <div class="etb-section-header">
                                <span class="etb-section-icon" :class="getSectionIconClass(section.type)" :title="getSectionTypeTitle(section.type)"></span>
                                <span x-text="getSectionTitle(section)" class="etb-section-title-text"></span>
                                <div class="etb-section-actions">
                                    <button class="etb-section-action-btn" @click="removeSection(index)" title="<?php esc_attr_e('Remove Section', 'email-template-builder'); ?>"><span class="dashicons dashicons-trash"></span></button>
                                    <!-- Add clone section button here if desired -->
                                </div>
                            </div>
                            <div class="etb-section-content">
                                <!-- Text Section -->
                                <div x-show="section.type === 'text'">
                                    <label><?php esc_html_e('Text Content:', 'email-template-builder'); ?></label>
                                    <textarea x-model="section.content[currentLang]" @focus="setActiveTextarea($event.target)" style="width: 100%; min-height: 100px;"></textarea>
                                </div>

                                <!-- Image Section -->
                                <div x-show="section.type === 'image'">
                                    <label :for="'img-url-' + section.id + '-' + currentLang"><?php esc_html_e('Image URL:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                    <input type="url" :id="'img-url-' + section.id + '-' + currentLang" x-model="section.content.url[currentLang]" style="width:100%;">

                                    <label :for="'img-alt-' + section.id + '-' + currentLang"><?php esc_html_e('Alt Text:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                    <input type="text" :id="'img-alt-' + section.id + '-' + currentLang" x-model="section.content.alt[currentLang]" style="width:100%;">
                                </div>

                                <!-- Button Section -->
                                <div x-show="section.type === 'button'">
                                    <label :for="'btn-text-' + section.id + '-' + currentLang"><?php esc_html_e('Button Text:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                    <input type="text" :id="'btn-text-' + section.id + '-' + currentLang" x-model="section.content.text[currentLang]" @focus="setActiveTextarea($event.target)" style="width:100%;">

                                    <label :for="'btn-url-' + section.id + '-' + currentLang"><?php esc_html_e('Button URL:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                    <input type="url" :id="'btn-url-' + section.id + '-' + currentLang" x-model="section.content.url[currentLang]" style="width:100%;">

                                    <label :for="'btn-bgcolor-' + section.id"><?php esc_html_e('Background Color:', 'email-template-builder'); ?></label>
                                    <input type="color" :id="'btn-bgcolor-' + section.id" x-model="section.content.bgColor" style="width:100px; height: 30px;">
                                </div>

                                <!-- Divider Section (No editable content) -->
                                <div x-show="section.type === 'divider'">
                                    <p style="text-align:center; color:#777; font-style:italic;"><?php esc_html_e('Visual Divider', 'email-template-builder'); ?></p>
                                </div>

                                <!-- Greeting Text Section -->
                                <div x-show="section.type === 'greeting_text'">
                                    <label :for="'greeting-' + section.id + '-' + currentLang"><?php esc_html_e('Greeting Text:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                    <input type="text" :id="'greeting-' + section.id + '-' + currentLang" x-model="section.content[currentLang]" @focus="setActiveTextarea($event.target)" style="width:100%;">
                                    <p class="description"><small><?php esc_html_e('Example: Good day {{name}},', 'email-template-builder'); ?></small></p>
                                </div>

                                <!-- Main Paragraph Section -->
                                <div x-show="section.type === 'main_paragraph'">
                                    <label :for="'main-para-' + section.id + '-' + currentLang"><?php esc_html_e('Main Paragraph:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                    <textarea :id="'main-para-' + section.id + '-' + currentLang" x-model="section.content[currentLang]" @focus="setActiveTextarea($event.target)" style="width: 100%; min-height: 120px;"></textarea>
                                </div>

                                <!-- Trading Schedule Section -->
                                <div x-show="section.type === 'trading_schedule'">
                                    <h4><?php esc_html_e('First Schedule Group', 'email-template-builder'); ?></h4>
                                    <label :for="'schedule-header1-' + section.id + '-' + currentLang"><?php esc_html_e('Date Header 1:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                    <input type="text" :id="'schedule-header1-' + section.id + '-' + currentLang" x-model="section.content.date_header_1[currentLang]" @focus="setActiveTextarea($event.target)" style="width:100%;">

                                    <h5><?php esc_html_e('Trading Rows (Day 1):', 'email-template-builder'); ?></h5>
                                    <div class="etb-trading-rows-group">
                                        <template x-for="(row, rowIndex) in section.content.rows_1" :key="row.id">
                                            <div class="etb-trading-row-item">
                                                <label :for="'item-instrument-' + row.id + '-' + currentLang"><?php esc_html_e('Instrument:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                                <input type="text" :id="'item-instrument-' + row.id + '-' + currentLang" x-model="row.instrument[currentLang]" @focus="setActiveTextarea($event.target)">

                                                <label :for="'item-status-' + row.id + '-' + currentLang"><?php esc_html_e('Time/Status:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                                <input type="text" :id="'item-status-' + row.id + '-' + currentLang" x-model="row.time_status[currentLang]" @focus="setActiveTextarea($event.target)">

                                                <button class="button button-link-delete" @click="removeTradingRow(index, 'rows_1', rowIndex)"><?php esc_html_e('Remove Row', 'email-template-builder'); ?></button>
                                            </div>
                                        </template>
                                        <button class="button" @click="addTradingRow(index, 'rows_1')"><?php esc_html_e('Add Row to Day 1', 'email-template-builder'); ?></button>
                                    </div>
                                    <hr style="margin: 15px 0;">
                                    <h4><?php esc_html_e('Second Schedule Group', 'email-template-builder'); ?></h4>
                                    <label :for="'schedule-header2-' + section.id + '-' + currentLang"><?php esc_html_e('Date Header 2:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                    <input type="text" :id="'schedule-header2-' + section.id + '-' + currentLang" x-model="section.content.date_header_2[currentLang]" @focus="setActiveTextarea($event.target)" style="width:100%;">

                                    <h5><?php esc_html_e('Trading Rows (Day 2):', 'email-template-builder'); ?></h5>
                                    <div class="etb-trading-rows-group">
                                        <template x-for="(row, rowIndex) in section.content.rows_2" :key="row.id">
                                            <div class="etb-trading-row-item">
                                                <label :for="'item-instrument-d2-' + row.id + '-' + currentLang"><?php esc_html_e('Instrument:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                                <input type="text" :id="'item-instrument-d2-' + row.id + '-' + currentLang" x-model="row.instrument[currentLang]" @focus="setActiveTextarea($event.target)">

                                                <label :for="'item-status-d2-' + row.id + '-' + currentLang"><?php esc_html_e('Time/Status:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                                <input type="text" :id="'item-status-d2-' + row.id + '-' + currentLang" x-model="row.time_status[currentLang]" @focus="setActiveTextarea($event.target)">

                                                <button class="button button-link-delete" @click="removeTradingRow(index, 'rows_2', rowIndex)"><?php esc_html_e('Remove Row', 'email-template-builder'); ?></button>
                                            </div>
                                        </template>
                                        <button class="button" @click="addTradingRow(index, 'rows_2')"><?php esc_html_e('Add Row to Day 2', 'email-template-builder'); ?></button>
                                    </div>
                                </div>

                                <!-- Closing Text Section -->
                                <div x-show="section.type === 'closing_text'">
                                    <label :for="'closing-' + section.id + '-' + currentLang"><?php esc_html_e('Closing Text:', 'email-template-builder'); ?> (<span x-text="currentLang.toUpperCase()"></span>)</label>
                                    <textarea :id="'closing-' + section.id + '-' + currentLang" x-model="section.content[currentLang]" @focus="setActiveTextarea($event.target)" style="width: 100%; min-height: 80px;"></textarea>
                                </div>

                            </div>
                        </div>
                    </template>
                </div>
                <p x-show="template.sections.length === 0"><?php esc_html_e('No sections yet. Add one above!', 'email-template-builder'); ?></p>
            </div>
            <div class="etb-main-content">
                <div class="etb-preview-tabs">
                    <button :class="{'active': currentLang === 'en'}" @click="switchLang('en')"><?php esc_html_e('English (EN)', 'email-template-builder'); ?></button>
                    <button :class="{'active': currentLang === 'pt'}" @click="switchLang('pt')"><?php esc_html_e('Portuguese (PT)', 'email-template-builder'); ?></button>
                    <button :class="{'active': currentLang === 'es'}" @click="switchLang('es')"><?php esc_html_e('Spanish (ES)', 'email-template-builder'); ?></button>
                </div>
                <div class="etb-preview-iframe-container">
                    <iframe x-ref="previewIframe" style="width: 100%; height: 500px; border: 1px solid #ccc;"></iframe>
                </div>
            </div>
        </div>
        <?php /* Inline styles removed, will be enqueued from admin-builder.css */ ?>
    </div>
    <?php
}

/**
 * Enqueues scripts and styles for the Email Template Builder admin page.
 *
 * This function ensures that Alpine.js, jQuery UI Sortable, the custom builder
 * JavaScript, and the builder's CSS are loaded only on the relevant admin page.
 * It also localizes data for the JavaScript, including nonces and configuration.
 *
 * @since 1.0.0
 * @param string $hook_suffix The hook suffix of the current admin page.
 */
function etb_enqueue_builder_assets( $hook_suffix ) {
	// Determine if we are on the main builder page or a subpage action (edit, add_new).
    // The hook for top-level pages is 'toplevel_page_{menu_slug}'.
    // For pages added via add_submenu_page, it might be '{parent_slug}_page_{menu_slug}'.
    // Since we use one callback for list/edit/add, 'toplevel_page_etb-builder' should cover all.
	$current_screen = get_current_screen();
    if ( 'toplevel_page_etb-builder' !== $current_screen->id ) {
        return;
    }

    // Enqueue main builder stylesheet.
    wp_enqueue_style(
        'etb-admin-builder-style', // Handle for the stylesheet.
        get_template_directory_uri() . '/builder/assets/css/admin-builder.css', // Path to CSS file.
        array(), // Dependencies.
        filemtime( get_template_directory() . '/builder/assets/css/admin-builder.css' ) // Versioning.
    );

	// Enqueue Alpine.js from a CDN.
	wp_enqueue_script(
		'alpinejs', // Handle.
		'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js', // Source.
		array(), // No dependencies.
		'3.13.10', // Version.
		true // Load in footer.
	);

	// Enqueue jQuery UI Sortable (WordPress includes jQuery and jQuery UI Core by default).
    wp_enqueue_script('jquery-ui-sortable');

	// Builder specific script (Alpine.js component logic).
	wp_enqueue_script(
		'etb-builder-script', // Handle.
		get_template_directory_uri() . '/builder/assets/js/builder.js', // Path to JS file.
		array( 'alpinejs', 'jquery-ui-sortable', 'wp-api-fetch' ), // Dependencies.
		filemtime( get_template_directory() . '/builder/assets/js/builder.js' ), // Versioning.
		true // Load in footer.
	);

	// Localize script with data for JavaScript.
    $translatable_labels_raw = etb_get_translatable_labels_config();
	wp_localize_script(
        'etb-builder-script', // Script handle to attach data to.
        'etb_data',          // Object name in JavaScript.
        array(
            'nonce'                       => wp_create_nonce( 'wp_rest' ), // Nonce for REST API.
            'export_nonce'                => wp_create_nonce( 'etb_export_template_html_nonce' ), // Nonce for export action.
            'admin_url'                   => admin_url( 'admin.php' ), // Base URL for admin actions.
            'base_url'                    => rest_url(), // Base URL for REST API.
            'translatable_snippets_full'  => $translatable_labels_raw, // Full translations for JS.
        )
    );
}
add_action( 'admin_enqueue_scripts', 'etb_enqueue_builder_assets' );

?>
