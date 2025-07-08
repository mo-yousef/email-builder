<?php
/**
 * ETB_Templates_List_Table class.
 *
 * @package EmailTemplateBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table class for Email Templates.
 */
class ETB_Templates_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Email Template', 'email-template-builder' ),
				'plural'   => __( 'Email Templates', 'email-template-builder' ),
				'ajax'     => false, // True if you want to load items via AJAX.
			)
		);
	}

	/**
	 * Get a list of columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'    => '<input type="checkbox" />', // Checkbox for bulk actions.
			'title' => __( 'Title', 'email-template-builder' ),
			'date'  => __( 'Date', 'email-template-builder' ),
		);
		return $columns;
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'title' => array( 'title', true ), // True means it's sortable.
			'date'  => array( 'date', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Get data for the table.
	 *
	 * @param int $per_page Number of items per page.
	 * @param int $page_number Current page number.
	 * @return array
	 */
	public function get_items_data( $per_page = 20, $page_number = 1 ) {
		$args = array(
			'post_type'      => 'email_template',
			'posts_per_page' => $per_page,
			'paged'          => $page_number,
			'orderby'        => isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'date',
			'order'          => isset( $_REQUEST['order'] ) ? strtoupper( sanitize_key( $_REQUEST['order'] ) ) : 'DESC',
		);

		$query = new WP_Query( $args );
		$items = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$items[] = array(
					'ID'    => get_the_ID(),
					'title' => get_the_title(),
					'date'  => get_the_date(),
				);
			}
		}
		wp_reset_postdata();
		return $items;
	}

	/**
	 * Handles the title column output.
	 *
	 * @param array $item The current item data.
	 * @return string
	 */
	public function column_title( $item ) {
		$page_slug = 'etb-builder'; // The slug for our admin page.

		// Build edit/delete/clone actions.
		$actions = array(
			'edit'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=' . $page_slug . '&action=edit&template_id=' . $item['ID'] ) ),
				__( 'Edit', 'email-template-builder' )
			),
			'delete' => sprintf(
				'<a href="#" class="etb-delete-template" data-id="%d" data-nonce="%s">%s</a>',
				absint( $item['ID'] ),
                wp_create_nonce( 'etb_delete_template_' . $item['ID'] ),
				__( 'Delete', 'email-template-builder' )
			),
            'clone' => sprintf(
                '<a href="%s">%s</a>',
                esc_url( admin_url( 'admin.php?page=' . $page_slug . '&action=clone&template_id=' . $item['ID'] ) ),
                __( 'Clone', 'email-template-builder' )
            ),
		);

		return sprintf( '<strong>%s</strong>%s', esc_html( $item['title'] ), $this->row_actions( $actions ) );
	}


	/**
	 * Handles the checkbox column output.
	 *
	 * @param array $item The current item data.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />',
			$item['ID']
		);
	}

	/**
	 * Handles the default column output.
	 *
	 * @param array  $item The current item data.
	 * @param string $column_name The current column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'date':
				return esc_html( $item[ $column_name ] );
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting.
		}
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'email-template-builder' ),
		);
		return $actions;
	}

    /**
	 * Process bulk actions.
	 */
	public function process_bulk_action() {
        // Detect when a bulk action is being triggered...
        if ( 'bulk-delete' === $this->current_action() ) {
            $ids_to_delete = isset( $_POST['bulk-delete'] ) ? array_map( 'absint', $_POST['bulk-delete'] ) : array();
            $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_key( $_POST['_wpnonce'] ) : '';

            if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
                 wp_die( 'Nonce verification failed for bulk delete!' );
            }

            if ( ! empty( $ids_to_delete ) ) {
                foreach ( $ids_to_delete as $id ) {
                    if ( current_user_can( 'delete_post', $id ) ) {
                        wp_delete_post( $id, true ); // True to force delete, bypass trash.
                    }
                }
                // Add admin notice for success/failure
                add_action( 'admin_notices', function() {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Selected templates deleted.', 'email-template-builder' ) . '</p></div>';
				});
            }
        }
	}


	/**
	 * Prepare items for the table to display.
	 */
	public function prepare_items() {
        $this->process_bulk_action(); // Process bulk actions first

		$columns  = $this->get_columns();
		$hidden   = array(); // Hidden columns.
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page     = $this->get_items_per_page( 'templates_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_total_items_count();

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
		$this->items = $this->get_items_data( $per_page, $current_page );
	}

	/**
	 * Get the total number of items.
	 *
	 * @return int
	 */
	public function get_total_items_count() {
		$query = new WP_Query(
			array(
				'post_type'      => 'email_template',
				'posts_per_page' => -1, // Count all.
				'fields'         => 'ids', // Only get post IDs to optimize.
			)
		);
		return $query->post_count;
	}
}
?>
