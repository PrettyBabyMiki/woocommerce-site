<?php
/**
 * WooCommerce Webhooks Table List
 *
 * @author   Automattic
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Webooks table list class.
 */
class WC_Admin_Webhooks_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'webhook',
			'plural'   => 'webhooks',
			'ajax'     => false,
		) );
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Name', 'woocommerce' ),
			'status'       => __( 'Status', 'woocommerce' ),
			'topic'        => __( 'Topic', 'woocommerce' ),
			'delivery_url' => __( 'Delivery URL', 'woocommerce' ),
		);
	}

	/**
	 * Column cb.
	 *
	 * @param  WC_Webhook $webhook Webhook instance.
	 * @return string
	 */
	public function column_cb( $webhook ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $webhook->ID );
	}

	/**
	 * Get Webhook object.
	 *
	 * @param  WC_Webhook $webhook Webhook instance.
	 * @return WC_Webhook
	 */
	private function get_webbook_object( $webhook ) {
		global $the_webhook;

		if ( empty( $the_webhook ) || $the_webhook->id !== $webhook->ID ) {
			$the_webhook = new WC_Webhook( $webhook->ID );
		}

		return $the_webhook;
	}

	/**
	 * Return title column.
	 *
	 * @param  WC_Webhook $webhook Webhook instance.
	 * @return string
	 */
	public function column_title( $webhook ) {
		$the_webhook      = $this->get_webbook_object( $webhook );
		$edit_link        = admin_url( 'admin.php?page=wc-settings&amp;tab=api&amp;section=webhooks&amp;edit-webhook=' . $the_webhook->id );
		$title            = _draft_or_post_title( $the_webhook->get_post_data() );
		$post_type_object = get_post_type_object( $the_webhook->get_post_data()->post_type );
		$post_status      = $the_webhook->get_post_data()->post_status;

		// Title.
		$output = '<strong>';
		if ( 'trash' === $post_status ) {
			$output .= esc_html( $title );
		} else {
			$output .= '<a href="' . esc_url( $edit_link ) . '" class="row-title">' . esc_html( $title ) . '</a>';
		}
		$output .= '</strong>';

		// Get actions.
		$actions = array(
			/* translators: %s: webhook ID. */
			'id' => sprintf( __( 'ID: %d', 'woocommerce' ), $the_webhook->id ),
		);

		if ( current_user_can( $post_type_object->cap->edit_post, $the_webhook->id ) && 'trash' !== $post_status ) {
			$actions['edit'] = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Edit', 'woocommerce' ) . '</a>';
		}

		if ( current_user_can( $post_type_object->cap->delete_post, $the_webhook->id ) ) {
			if ( 'trash' === $post_status ) {
				$actions['untrash'] = '<a aria-label="' . esc_attr__( 'Restore this item from the Trash', 'woocommerce' ) . '" href="' . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $the_webhook->id ) ), 'untrash-post_' . $the_webhook->id ) . '">' . esc_html__( 'Restore', 'woocommerce' ) . '</a>';
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Move this item to the Trash', 'woocommerce' ) . '" href="' . get_delete_post_link( $the_webhook->id ) . '">' . esc_html__( 'Trash', 'woocommerce' ) . '</a>';
			}
			if ( 'trash' === $post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Delete this item permanently', 'woocommerce' ) . '" href="' . get_delete_post_link( $the_webhook->id, '', true ) . '">' . esc_html__( 'Delete permanently', 'woocommerce' ) . '</a>';
			}
		}

		$actions     = apply_filters( 'post_row_actions', $actions, $the_webhook->get_post_data() );
		$row_actions = array();

		foreach ( $actions as $action => $link ) {
			$row_actions[] = '<span class="' . esc_attr( $action ) . '">' . $link . '</span>';
		}

		$output .= '<div class="row-actions">' . implode( ' | ', $row_actions ) . '</div>';

		return $output;
	}

	/**
	 * Return status column.
	 *
	 * @param  WC_Webhook $webhook Webhook instance.
	 * @return string
	 */
	public function column_status( $webhook ) {
		return $this->get_webbook_object( $webhook )->get_i18n_status();
	}

	/**
	 * Return topic column.
	 *
	 * @param  WC_Webhook $webhook Webhook instance.
	 * @return string
	 */
	public function column_topic( $webhook ) {
		return $this->get_webbook_object( $webhook )->get_topic();
	}

	/**
	 * Return delivery URL column.
	 *
	 * @param  WC_Webhook $webhook Webhook instance.
	 * @return string
	 */
	public function column_delivery_url( $webhook ) {
		return $this->get_webbook_object( $webhook )->get_delivery_url();
	}

	/**
	 * Get the status label for webhooks.
	 *
	 * @param  string   $status_name Status name.
	 * @param  stdClass $status      Status data.
	 *
	 * @return array
	 */
	private function get_status_label( $status_name, $status ) {
		switch ( $status_name ) {
			case 'publish':
				$label = array(
					/* translators: %s: count */
					'singular' => __( 'Activated <span class="count">(%s)</span>', 'woocommerce' ),
					/* translators: %s: count */
					'plural'   => __( 'Activated <span class="count">(%s)</span>', 'woocommerce' ),
					'context'  => '',
					'domain'   => 'woocommerce',
				);
				break;
			case 'draft':
				$label = array(
					/* translators: %s: count */
					'singular' => __( 'Paused <span class="count">(%s)</span>', 'woocommerce' ),
					/* translators: %s: count */
					'plural'   => __( 'Paused <span class="count">(%s)</span>', 'woocommerce' ),
					'context'  => '',
					'domain'   => 'woocommerce',
				);
				break;
			case 'pending':
				$label = array(
					/* translators: %s: count */
					'singular' => __( 'Disabled <span class="count">(%s)</span>', 'woocommerce' ),
					/* translators: %s: count */
					'plural'   => __( 'Disabled <span class="count">(%s)</span>', 'woocommerce' ),
					'context'  => '',
					'domain'   => 'woocommerce',
				);
				break;

			default:
				$label = $status->label_count;
				break;
		}

		return $label;
	}

	/**
	 * Table list views.
	 *
	 * @return array
	 */
	protected function get_views() {
		$status_links       = array();
		$num_posts          = wp_count_posts( 'shop_webhook', 'readable' );
		$class              = '';
		$total_posts        = array_sum( (array) $num_posts );
		$non_admin_statuses = get_post_stati( array(
			'show_in_admin_all_list' => false,
		) );
		$statuses           = get_post_stati( array(
			'show_in_admin_status_list' => true,
		), 'objects' );

		// Subtract post types that are not included in the admin all list.
		foreach ( $statuses as $status ) {
			$total_posts -= $num_posts->$status;
		}

		$class = empty( $class ) && empty( $_REQUEST['status'] ) ? ' class="current"' : ''; // WPCS: input var okay. CSRF ok.
		/* translators: %s: count */
		$status_links['all'] = "<a href='admin.php?page=wc-settings&amp;tab=api&amp;section=webhooks'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts', 'woocommerce' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( $statuses as $status ) {
			$class = '';
			$status_name = $status->name;

			if ( ! in_array( $status_name, array(
				'publish',
				'draft',
				'pending',
				'trash',
				'future',
				'private',
				'auto-draft',
			), true ) ) {
				continue;
			}

			if ( empty( $num_posts->$status_name ) ) {
				continue;
			}

			if ( isset( $_REQUEST['status'] ) && sanitize_key( wp_unslash( $_REQUEST['status'] ) ) === $status_name ) { // WPCS: input var okay, CSRF ok.
				$class = ' class="current"';
			}

			$label = $this->get_status_label( $status_name, $status );

			$status_links[ $status_name ] = "<a href='admin.php?page=wc-settings&amp;tab=api&amp;section=webhooks&amp;status=$status_name'$class>" . sprintf( translate_nooped_plural( $label, $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return $status_links;
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		if ( isset( $_GET['status'] ) && 'trash' === sanitize_key( wp_unslash( $_GET['status'] ) ) ) { // WPCS: input var okay, CSRF ok.
			return array(
				'untrash' => __( 'Restore', 'woocommerce' ),
				'delete'  => __( 'Delete permanently', 'woocommerce' ),
			);
		}

		return array(
			'trash' => __( 'Move to trash', 'woocommerce' ),
		);
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which && isset( $_GET['status'] ) && 'trash' === sanitize_key( wp_unslash( $_GET['status'] ) ) && current_user_can( 'delete_shop_webhooks' ) ) { // WPCS: input var okay, CSRF ok.
			echo '<div class="alignleft actions"><a class="button apply" href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&status=trash&empty_trash=1' ), 'empty_trash' ) ) . '">' . esc_html__( 'Empty trash', 'woocommerce' ) . '</a></div>';
		}
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		$per_page = apply_filters( 'woocommerce_webhooks_settings_posts_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers.
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();

		// Query args.
		$args = array(
			'post_type'           => 'shop_webhook',
			'posts_per_page'      => $per_page,
			'ignore_sticky_posts' => true,
			'paged'               => $current_page,
		);

		// Handle the status query.
		if ( ! empty( $_REQUEST['status'] ) ) { // WPCS: input var okay, CSRF ok.
			$args['post_status'] = sanitize_key( wp_unslash( $_REQUEST['status'] ) ); // WPCS: input var okay, CSRF ok.
		}

		if ( ! empty( $_REQUEST['s'] ) ) {  // WPCS: input var okay, CSRF ok.
			$args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );  // WPCS: input var okay, CSRF ok.
		}

		// Get the webhooks.
		$webhooks    = new WP_Query( $args );
		$this->items = $webhooks->posts;

		// Set the pagination.
		$this->set_pagination_args( array(
			'total_items' => $webhooks->found_posts,
			'per_page'    => $per_page,
			'total_pages' => $webhooks->max_num_pages,
		) );
	}
}
