<?php
/**
 * AJAX Handlers for Email Template Builder
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers all AJAX action hooks.
 * Called by etb_initialize()
 */
function etb_register_ajax_handlers() {
    $ajax_actions = array(
        'load_templates_list',
        'load_template_data',
        'save_template',
        'delete_template',
        'clone_template'
    );

    foreach ( $ajax_actions as $action ) {
        add_action( 'wp_ajax_etb_' . $action, 'etb_ajax_handle_' . $action );
    }
}

/**
 * AJAX Handler: Load list of templates.
 */
function etb_ajax_handle_load_templates_list() {
    check_ajax_referer( 'etb_ajax_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) { // Basic capability check, adjust if needed
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'your-theme-textdomain' ) ), 403 );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'email_templates';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $templates = $wpdb->get_results( "SELECT id, name FROM {$table_name} ORDER BY name ASC", ARRAY_A );

    if ( $wpdb->last_error ) {
        wp_send_json_error( array( 'message' => __( 'Error fetching templates: ', 'your-theme-textdomain' ) . $wpdb->last_error ), 500 );
    } else {
        wp_send_json_success( $templates );
    }
}

/**
 * AJAX Handler: Load data for a specific template.
 */
function etb_ajax_handle_load_template_data() {
    check_ajax_referer( 'etb_ajax_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'your-theme-textdomain' ) ), 403 );
    }

    $template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

    if ( ! $template_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid template ID.', 'your-theme-textdomain' ) ), 400 );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'email_templates';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
    $template_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $template_id ), ARRAY_A );

    if ( $wpdb->last_error ) {
        wp_send_json_error( array( 'message' => __( 'Error fetching template data: ', 'your-theme-textdomain' ) . $wpdb->last_error ), 500 );
    } elseif ( ! $template_data ) {
        wp_send_json_error( array( 'message' => __( 'Template not found.', 'your-theme-textdomain' ) ), 404 );
    } else {
        // Ensure longtext fields are properly handled (stripslashes if magic quotes were on, though less common now)
        $template_data['content_en'] = stripslashes($template_data['content_en']);
        $template_data['content_pt'] = stripslashes($template_data['content_pt']);
        $template_data['content_es'] = stripslashes($template_data['content_es']);
        $template_data['sections_order'] = isset($template_data['sections_order']) ? stripslashes($template_data['sections_order']) : null;
        $template_data['settings'] = isset($template_data['settings']) ? stripslashes($template_data['settings']) : null;
        wp_send_json_success( $template_data );
    }
}

/**
 * AJAX Handler: Save (Create or Update) a template.
 */
function etb_ajax_handle_save_template() {
    check_ajax_referer( 'etb_ajax_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'your-theme-textdomain' ) ), 403 );
    }

    $template_id    = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
    $template_name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

    // For content, we expect HTML. Using wp_kses_post is a good balance for allowing rich content
    // while stripping potentially harmful elements if not used carefully.
    // Consider the implications if users can inject arbitrary script tags if not intended.
    // For this tool, assuming the generated HTML is mostly safe but applying kses_post as a precaution.
    $content_en     = isset( $_POST['content_en'] ) ? wp_unslash( $_POST['content_en'] ) : '';
    $content_pt     = isset( $_POST['content_pt'] ) ? wp_unslash( $_POST['content_pt'] ) : '';
    $content_es     = isset( $_POST['content_es'] ) ? wp_unslash( $_POST['content_es'] ) : '';

    // For JSON data, ensure it's valid JSON before saving or use appropriate sanitization.
    // Using sanitize_text_field for now as a basic measure.
    $sections_order = isset( $_POST['sections_order'] ) ? sanitize_text_field( wp_unslash( $_POST['sections_order'] ) ) : '';
    $settings       = isset( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : '';

    if ( empty( $template_name ) ) {
        wp_send_json_error( array( 'message' => __( 'Template name cannot be empty.', 'your-theme-textdomain' ) ), 400 );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'email_templates';
    $data = array(
        'name'            => $template_name,
        'content_en'      => $content_en, // Not using kses_post here to allow full HTML from builder
        'content_pt'      => $content_pt,
        'content_es'      => $content_es,
        'sections_order'  => $sections_order,
        'settings'        => $settings,
    );
    $format = array( '%s', '%s', '%s', '%s', '%s', '%s' );

    if ( $template_id ) { // Update existing
        $data['updated_at'] = current_time( 'mysql', 1 ); // GMT
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $result = $wpdb->update( $table_name, $data, array( 'id' => $template_id ), $format, array( '%d' ) );
        $new_template_id = $template_id;
    } else { // Create new
        $data['created_at'] = current_time( 'mysql', 1 ); // GMT
         // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->insert( $table_name, $data, $format );
        $new_template_id = $wpdb->insert_id;
    }

    if ( false === $result ) {
        wp_send_json_error( array( 'message' => __( 'Database error: ', 'your-theme-textdomain' ) . $wpdb->last_error ), 500 );
    } else {
        wp_send_json_success( array(
            'message'     => __( 'Template saved successfully.', 'your-theme-textdomain' ),
            'template_id' => $new_template_id,
            'name'        => $template_name // Send back the name for UI updates
        ) );
    }
}


/**
 * AJAX Handler: Delete a template.
 */
function etb_ajax_handle_delete_template() {
    check_ajax_referer( 'etb_ajax_nonce', 'nonce' );

    if ( ! current_user_can( 'delete_posts' ) ) { // Or a more specific capability
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'your-theme-textdomain' ) ), 403 );
    }

    $template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

    if ( ! $template_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid template ID.', 'your-theme-textdomain' ) ), 400 );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'email_templates';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
    $result = $wpdb->delete( $table_name, array( 'id' => $template_id ), array( '%d' ) );

    if ( false === $result ) {
        wp_send_json_error( array( 'message' => __( 'Error deleting template: ', 'your-theme-textdomain' ) . $wpdb->last_error ), 500 );
    } elseif ( 0 === $result ) {
        wp_send_json_error( array( 'message' => __( 'Template not found or already deleted.', 'your-theme-textdomain' ) ), 404 );
    }
    else {
        wp_send_json_success( array( 'message' => __( 'Template deleted successfully.', 'your-theme-textdomain' ) ) );
    }
}

/**
 * AJAX Handler: Clone a template.
 */
function etb_ajax_handle_clone_template() {
    check_ajax_referer( 'etb_ajax_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) { // Capability to create new content
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'your-theme-textdomain' ) ), 403 );
    }

    $template_id_to_clone = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

    if ( ! $template_id_to_clone ) {
        wp_send_json_error( array( 'message' => __( 'Invalid template ID to clone.', 'your-theme-textdomain' ) ), 400 );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'email_templates';

    // Fetch the original template
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
    $original_template = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $template_id_to_clone ), ARRAY_A );

    if ( ! $original_template ) {
        wp_send_json_error( array( 'message' => __( 'Original template not found.', 'your-theme-textdomain' ) ), 404 );
    }

    // Prepare data for the new (cloned) template
    $cloned_data = $original_template;
    unset( $cloned_data['id'] ); // Remove original ID
    $cloned_data['name'] = $original_template['name'] . ' ' . __( '(Copy)', 'your-theme-textdomain' );
    $cloned_data['created_at'] = current_time( 'mysql', 1 );
    $cloned_data['updated_at'] = current_time( 'mysql', 1 );

    // Define formats for each column type
    $format = array(
        '%s', // name
        '%s', // content_en
        '%s', // content_pt
        '%s', // content_es
        '%s', // sections_order
        '%s', // settings
        '%s', // created_at
        '%s'  // updated_at
    );


    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $result = $wpdb->insert( $table_name, $cloned_data, $format );
    $new_template_id = $wpdb->insert_id;

    if ( false === $result ) {
        wp_send_json_error( array( 'message' => __( 'Error cloning template: ', 'your-theme-textdomain' ) . $wpdb->last_error ), 500 );
    } else {
        // Fetch the newly cloned template to return its full data
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $new_template_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $new_template_id ), ARRAY_A );
        if ($new_template_data) {
            $new_template_data['content_en'] = stripslashes($new_template_data['content_en']);
            $new_template_data['content_pt'] = stripslashes($new_template_data['content_pt']);
            $new_template_data['content_es'] = stripslashes($new_template_data['content_es']);
            $new_template_data['sections_order'] = isset($new_template_data['sections_order']) ? stripslashes($new_template_data['sections_order']) : null;
            $new_template_data['settings'] = isset($new_template_data['settings']) ? stripslashes($new_template_data['settings']) : null;
        }


        wp_send_json_success( array(
            'message'     => __( 'Template cloned successfully.', 'your-theme-textdomain' ),
            'new_template' => $new_template_data // Send full data of the new clone
        ) );
    }
}

?>
