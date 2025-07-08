<?php
/**
 * Registers the Custom Post Type for Email Templates.
 *
 * @package EmailTemplateBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the 'email_template' Custom Post Type.
 *
 * This CPT is used to store the email templates created with the builder.
 * It supports a title and custom meta fields for storing the template structure.
 *
 * @since 1.0.0
 */
function etb_register_template_post_type() {
	/**
	 * Labels for the Custom Post Type.
	 *
	 * @var array
	 */
	$labels = array(
		'name'               => _x( 'Email Templates', 'post type general name', 'email-template-builder' ),
		'singular_name'      => _x( 'Email Template', 'post type singular name', 'email-template-builder' ),
		'menu_name'          => _x( 'Email Templates', 'admin menu', 'email-template-builder' ),
		'name_admin_bar'     => _x( 'Email Template', 'add new on admin bar', 'email-template-builder' ),
		'add_new'            => _x( 'Add New', 'email template', 'email-template-builder' ),
		'add_new_item'       => __( 'Add New Email Template', 'email-template-builder' ),
		'new_item'           => __( 'New Email Template', 'email-template-builder' ),
		'edit_item'          => __( 'Edit Email Template', 'email-template-builder' ),
		'view_item'          => __( 'View Email Template', 'email-template-builder' ),
		'all_items'          => __( 'All Email Templates', 'email-template-builder' ),
		'search_items'       => __( 'Search Email Templates', 'email-template-builder' ),
		'parent_item_colon'  => __( 'Parent Email Templates:', 'email-template-builder' ),
		'not_found'          => __( 'No email templates found.', 'email-template-builder' ),
		'not_found_in_trash' => __( 'No email templates found in Trash.', 'email-template-builder' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => false, // Not public on the front-end, only admin accessible.
		'publicly_queryable' => false,
		'show_ui'            => true, // Show in admin.
		'show_in_menu'       => false, // Will be added under a custom top-level menu page.
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'email-templates' ),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => 20, // Below Pages.
		'menu_icon'          => 'dashicons-email-alt', // WordPress Dashicon.
		'supports'           => array( 'title' ), // We'll use post meta for content.
		'show_in_rest'       => true, // Enable REST API support.
		'public'             => false, // Not public on the front-end.
		'publicly_queryable' => false, // Not queryable on the front-end.
		'show_ui'            => true, // Show in admin UI.
		'show_in_menu'       => false, // Will be under our custom top-level menu.
	);

	register_post_type( 'email_template', $args );

	/**
	 * Register meta field for storing the template structure.
	 * This allows the '_template_structure' meta to be accessed via the REST API.
	 *
	 * @see register_post_meta()
	 */
	register_post_meta(
		'email_template',
		'_template_structure',
		array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string', // JSON stored as a string.
			'auth_callback' => function () {
				// Ensure only users who can edit posts (of this CPT) can update this meta.
				// Adjust capability check if more specific capabilities are defined for the CPT.
				return current_user_can( 'edit_posts' ); // General check, CPT specific would be better if defined
			},
		)
	);

	// Example of registering other meta fields if needed in the future:
	/*
	register_post_meta( 'email_template', '_template_content_en', array(
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'auth_callback' => function() { return current_user_can('edit_posts'); }
	) );
	register_post_meta( 'email_template', '_template_content_pt', array(
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'auth_callback' => function() { return current_user_can('edit_posts'); }
	) );
	register_post_meta( 'email_template', '_template_content_es', array(
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'auth_callback' => function() { return current_user_can('edit_posts'); }
	) );
	*/
}
add_action( 'init', 'etb_register_template_post_type' );

?>
