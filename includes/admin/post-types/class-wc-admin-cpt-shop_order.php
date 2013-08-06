<?php
/**
 * Admin functions for the shop order post type
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Post Types
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Admin_CPT' ) )
	include( 'class-wc-admin-cpt.php' );

if ( ! class_exists( 'WC_Admin_CPT_Shop_Order' ) ) :

/**
 * WC_Admin_CPT_Shop_Order Class
 */
class WC_Admin_CPT_Shop_Order extends WC_Admin_CPT {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'shop_order';

		// Before data updates
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ) );

		// Admin Columns
		add_filter( 'manage_edit-' . $this->type . '_columns', array( $this, 'edit_columns' ) );
		add_action( 'manage_' . $this->type . '_posts_custom_column', array( $this, 'custom_columns' ), 2 );

		// Views and filtering
		add_filter( 'views_edit-shop_order', array( $this, 'custom_order_views' ) );
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'bulk_actions' ) );
		add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 1 );
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_orders' ) );
		add_filter( 'request', array( $this, 'orders_by_customer_query' ) );
		add_filter( "manage_edit-shop_order_sortable_columns", array( $this, 'custom_shop_order_sort' ) );
		add_filter( 'request', array( $this, 'custom_shop_order_orderby' ) );
		add_filter( 'parse_query', array( $this, 'shop_order_search_custom_fields' ) );
		add_filter( 'get_search_query', array( $this, 'shop_order_search_label' ) );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_action( 'before_delete_post', array( $this, 'delete_order_items' ) );

		// Call WC_Admin_CPT constructor
		parent::__construct();
	}

	/**
	 * Forces the order posts to have a title in a certain format (containing the date)
	 *
	 * @param array $data
	 * @return array
	 */
	public function wp_insert_post_data( $data ) {
		global $post;

		if ( $data['post_type'] == 'shop_order' && isset( $data['post_date'] ) ) {

			$order_title = 'Order';
			if ( $data['post_date'] )
				$order_title.= ' &ndash; ' . date_i18n( 'F j, Y @ h:i A', strtotime( $data['post_date'] ) );

			$data['post_title'] = $order_title;
		}

		return $data;
	}

	/**
	 * Change the columns shown in admin.
	 */
	public function edit_columns( $existing_columns ) {
		$columns = array();

		$columns["cb"]               = "<input type=\"checkbox\" />";
		$columns["order_status"]     = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'woocommerce' ) . '">' . esc_attr__( 'Status', 'woocommerce' ) . '</span>';
		$columns["order_title"]      = __( 'Order', 'woocommerce' );
		$columns["order_items"]      = __( 'Purchased', 'woocommerce' );
		$columns["shipping_address"] = __( 'Ship to', 'woocommerce' );

		$columns["customer_message"] = '<span class="notes_head tips" data-tip="' . esc_attr__( 'Customer Message', 'woocommerce' ) . '">' . esc_attr__( 'Customer Message', 'woocommerce' ) . '</span>';
		$columns["order_notes"]      = '<span class="order-notes_head tips" data-tip="' . esc_attr__( 'Order Notes', 'woocommerce' ) . '">' . esc_attr__( 'Order Notes', 'woocommerce' ) . '</span>';
		$columns["order_date"]       = __( 'Date', 'woocommerce' );
		$columns["order_total"]      = __( 'Total', 'woocommerce' );
		$columns["order_actions"]    = __( 'Actions', 'woocommerce' );

		return $columns;
	}

	/**
	 * Define our custom columns shown in admin.
	 * @param  string $column
	 */
	public function custom_columns( $column ) {
		global $post, $woocommerce, $the_order;

		if ( empty( $the_order ) || $the_order->id != $post->ID )
			$the_order = new WC_Order( $post->ID );

		switch ( $column ) {
			case "order_status" :

				printf( '<mark class="%s tips" data-tip="%s">%s</mark>', sanitize_title( $the_order->status ), esc_html__( $the_order->status, 'woocommerce' ), esc_html__( $the_order->status, 'woocommerce' ) );

			break;
			case "order_date" :

				if ( '0000-00-00 00:00:00' == $post->post_date ) {
					$t_time = $h_time = __( 'Unpublished', 'woocommerce' );
				} else {
					$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ), $post );

					$gmt_time = strtotime( $post->post_date_gmt . ' UTC' );
					$time_diff = current_time('timestamp', 1) - $gmt_time;

					$h_time = get_the_time( __( 'Y/m/d', 'woocommerce' ), $post );
				}

				echo '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'post_date_column_time', $h_time, $post ) ) . '</abbr>';

			break;
			case "customer_message" :

				if ( $the_order->customer_message )
					echo '<span class="note-on tips" data-tip="' . esc_attr( $the_order->customer_message ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
				else
					echo '<span class="na">&ndash;</span>';

			break;
			case "billing_address" :
				if ( $the_order->get_formatted_billing_address() )
	        		echo '<a target="_blank" href="' . esc_url( 'http://maps.google.com/maps?&q=' . urlencode( $the_order->get_billing_address() ) . '&z=16' ) . '">' . esc_html( preg_replace( '#<br\s*/?>#i', ', ', $the_order->get_formatted_billing_address() ) ) .'</a>';
	        	else
	        		echo '&ndash;';

	        	if ( $the_order->payment_method_title )
	        		echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->payment_method_title ) . '</small>';
			break;
			case "order_items" :

				printf( '<a href="#" class="show_order_items">' . _n( '%d item', '%d items', sizeof( $the_order->get_items() ), 'woocommerce' ) . '</a>', sizeof( $the_order->get_items() ) );

				if ( sizeof( $the_order->get_items() ) > 0 ) {

					echo '<table class="order_items" cellspacing="0">';

					foreach( $the_order->get_items() as $item ) {
						$_product       = apply_filters( 'woocommerce_order_item_product', $the_order->get_product_from_item( $item ), $item );
						$item_meta      = new WC_Order_Item_Meta( $item['item_meta'] );
						$item_meta_html = $item_meta->display( true, true );
						?>
						<tr>
							<td class="qty"><?php echo absint( $item['qty'] ); ?></td>
							<td class="name">
								<?php if ( get_option( 'woocommerce_enable_sku', true ) !== 'no' && $_product && $_product->get_sku() ) echo $_product->get_sku() . ' - '; ?><?php echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item ); ?>
								<?php if ( $item_meta_html ) : ?>
									<a class="tips" href="#" data-tip="<?php echo esc_attr( $item_meta_html ); ?>">[?]</a>
								<?php endif; ?>
							</td>
						</tr>
						<?php
					}

					echo '</table>';

				} else echo '&ndash;';
			break;
			case "shipping_address" :
				if ( $the_order->get_formatted_shipping_address() )
	            	echo '<a target="_blank" href="' . esc_url( 'http://maps.google.com/maps?&q=' . urlencode( $the_order->get_shipping_address() ) . '&z=16' ) . '">'. esc_html( preg_replace( '#<br\s*/?>#i', ', ', $the_order->get_formatted_shipping_address() ) ) .'</a>';
	        	else
	        		echo '&ndash;';

	        	if ( $the_order->shipping_method_title )
	        		echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->shipping_method_title ) . '</small>';

			break;
			case "order_notes" :

				if ( $post->comment_count ) {

					$latest_notes = get_comments( array(
						'post_id' => $post->ID,
						'number'  => 1
						) );

					$latest_note = current( $latest_notes );

					if ( $post->comment_count == 1 )
						echo '<span class="note-on tips" data-tip="' . esc_attr( $latest_note->comment_content ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
					else
						echo '<span class="note-on tips" data-tip="' . esc_attr( $latest_note->comment_content . '<small style="display:block">' . sprintf( _n( 'plus %d other note', 'plus %d other notes', ( $post->comment_count - 1 ), 'woocommerce' ), ( $post->comment_count - 1 ) ) . '</small>' ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';

				} else {
					echo '<span class="na">&ndash;</span>';
				}

			break;
			case "order_total" :
				echo esc_html( strip_tags( $the_order->get_formatted_order_total() ) );

				if ( $the_order->payment_method_title )
	        		echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->payment_method_title ) . '</small>';
			break;
			case "order_title" :

	        	$customer_tip = '';

	        	if ( $address = $the_order->get_formatted_billing_address() )
	        		$customer_tip .= __( 'Billing:' ) . ' ' . $address . '<br/><br/>';

	        	if ( $the_order->billing_phone )
        			$customer_tip .= __( 'Tel:' ) . ' ' . $the_order->billing_phone;

				echo '<div class="tips" data-tip="' . esc_attr( $customer_tip ) . '">';

				if ( $the_order->user_id )
					$user_info = get_userdata( $the_order->user_id );

				if ( ! empty( $user_info ) ) {

	            	$user = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';

	            	if ( $user_info->first_name || $user_info->last_name )
	            		$user .= esc_html( $user_info->first_name . ' ' . $user_info->last_name );
	            	else
	            		$user .= esc_html( $user_info->display_name );

	            	$user .= '</a>';

	           	} else {
	           		$user = __( 'Guest', 'woocommerce' );
	           	}

	           	if ( $the_order->user_id )
					$user_info = get_userdata( $the_order->user_id );

				if ( ! empty( $user_info ) ) {

	            	$username = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';

	            	if ( $user_info->first_name || $user_info->last_name )
	            		$username .= esc_html( ucfirst( $user_info->first_name ) . ' ' . ucfirst( $user_info->last_name ) );
	            	else
	            		$username .= esc_html( ucfirst( $user_info->display_name ) );

	            	$username .= '</a>';

	           	} else {
	           		if ( $the_order->billing_first_name || $the_order->billing_last_name )
	           			$username = trim( $the_order->billing_first_name . ' ' . $the_order->billing_last_name );
	           		else
	           			$username = __( 'Guest', 'woocommerce' );
	           	}

	           	printf( __( '%s by %s', 'woocommerce' ), '<a href="' . admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' ) . '"><strong>' . esc_attr( $the_order->get_order_number() ) . '</strong></a>', $username );

           		if ( $the_order->billing_email )
        			echo '<small class="meta email"><a href="' . esc_url( 'mailto:' . $the_order->billing_email ) . '">' . esc_html( $the_order->billing_email ) . '</a></small>';

        		echo '</div>';

			break;
			case "order_actions" :

				?><p>
					<?php
						do_action( 'woocommerce_admin_order_actions_start', $the_order );

						$actions = array();

						if ( in_array( $the_order->status, array( 'pending', 'on-hold' ) ) )
							$actions['processing'] = array(
								'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce-mark-order-processing&order_id=' . $post->ID ), 'woocommerce-mark-order-processing' ),
								'name' 		=> __( 'Processing', 'woocommerce' ),
								'action' 	=> "processing"
							);

						if ( in_array( $the_order->status, array( 'pending', 'on-hold', 'processing' ) ) )
							$actions['complete'] = array(
								'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce-mark-order-complete&order_id=' . $post->ID ), 'woocommerce-mark-order-complete' ),
								'name' 		=> __( 'Complete', 'woocommerce' ),
								'action' 	=> "complete"
							);

						$actions['view'] = array(
							'url' 		=> admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
							'name' 		=> __( 'View', 'woocommerce' ),
							'action' 	=> "view"
						);

						$actions = apply_filters( 'woocommerce_admin_order_actions', $actions, $the_order );

						foreach ( $actions as $action ) {
							printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
						}

						do_action( 'woocommerce_admin_order_actions_end', $the_order );
					?>
				</p><?php

			break;


		}
	}

	/**
	 * Filters for the order page.
	 *
	 * @access public
	 * @param mixed $views
	 * @return array
	 */
	public function custom_order_views( $views ) {

		unset( $views['publish'] );

		if ( isset( $views['trash'] ) ) {
			$trash = $views['trash'];
			unset( $views['draft'] );
			unset( $views['trash'] );
			$views['trash'] = $trash;
		}

		return $views;
	}

	/**
	 * Remove edit from the bulk actions.
	 *
	 * @access public
	 * @param mixed $actions
	 * @return array
	 */
	public function bulk_actions( $actions ) {

		if ( isset( $actions['edit'] ) )
			unset( $actions['edit'] );

		return $actions;
	}

	/**
	 * Actions for the orders page.
	 *
	 * @access public
	 * @param mixed $actions
	 * @return array
	 */
	public function remove_row_actions( $actions ) {
	    if( get_post_type() === 'shop_order' ) {
	        unset( $actions['view'] );
	        unset( $actions['inline hide-if-no-js'] );
	    }
	    return $actions;
	}

	/**
	 * Show custom filters to filter orders by status/customer.
	 *
	 * @access public
	 * @return void
	 */
	public function restrict_manage_orders() {
		global $woocommerce, $typenow, $wp_query;

		if ( $typenow != 'shop_order' )
			return;

		// Status
		?>
		<select name='shop_order_status' id='dropdown_shop_order_status'>
			<option value=""><?php _e( 'Show all statuses', 'woocommerce' ); ?></option>
			<?php
				$terms = get_terms('shop_order_status');

				foreach ( $terms as $term ) {
					echo '<option value="' . esc_attr( $term->slug ) . '"';

					if ( isset( $wp_query->query['shop_order_status'] ) )
						selected( $term->slug, $wp_query->query['shop_order_status'] );

					echo '>' . esc_html__( $term->name, 'woocommerce' ) . ' (' . absint( $term->count ) . ')</option>';
				}
			?>
			</select>
		<?php

		// Customers
		?>
		<select id="dropdown_customers" name="_customer_user">
			<option value=""><?php _e( 'Show all customers', 'woocommerce' ) ?></option>
			<?php
				if ( ! empty( $_GET['_customer_user'] ) ) {
					$user = get_user_by( 'id', absint( $_GET['_customer_user'] ) );
					echo '<option value="' . absint( $user->ID ) . '" ';
					selected( 1, 1 );
					echo '>' . esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')</option>';
				}
			?>
		</select>
		<?php

		$woocommerce->get_helper( 'inline-javascript' )->add_inline_js( "

			jQuery('select#dropdown_shop_order_status, select[name=m]').css('width', '150px').chosen();

			jQuery('select#dropdown_customers').css('width', '250px').ajaxChosen({
			    method: 		'GET',
			    url: 			'" . admin_url('admin-ajax.php') . "',
			    dataType: 		'json',
			    afterTypeDelay: 100,
			    minTermLength: 	1,
			    data:		{
			    	action: 	'woocommerce_json_search_customers',
					security: 	'" . wp_create_nonce("search-customers") . "',
					default:	'" . __( 'Show all customers', 'woocommerce' ) . "'
			    }
			}, function (data) {

				var terms = {};

			    $.each(data, function (i, val) {
			        terms[i] = val;
			    });

			    return terms;
			});
		" );
	}

	/**
	 * Filter the orders by the posted customer.
	 *
	 * @access public
	 * @param mixed $vars
	 * @return array
	 */
	public function orders_by_customer_query( $vars ) {
		global $typenow, $wp_query;
	    if ( $typenow == 'shop_order' && isset( $_GET['_customer_user'] ) && $_GET['_customer_user'] > 0 ) {

			$vars['meta_key'] = '_customer_user';
			$vars['meta_value'] = (int) $_GET['_customer_user'];

		}

		return $vars;
	}

	/**
	 * Make order columns sortable.
	 *
	 *
	 * https://gist.github.com/906872
	 *
	 * @access public
	 * @param mixed $columns
	 * @return array
	 */
	public function custom_shop_order_sort( $columns ) {
		$custom = array(
			'order_title'	=> 'ID',
			'order_total'	=> 'order_total',
			'order_date'	=> 'date'
		);
		unset( $columns['comments'] );
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Order column orderby/request.
	 *
	 * @access public
	 * @param mixed $vars
	 * @return array
	 */
	public function custom_shop_order_orderby( $vars ) {
		global $typenow, $wp_query;
	    if ( $typenow != 'shop_order' )
	    	return $vars;

	    // Sorting
		if ( isset( $vars['orderby'] ) ) {
			if ( 'order_total' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_order_total',
					'orderby' 	=> 'meta_value_num'
				) );
			}
		}

		return $vars;
	}

	/**
	 * Search custom fields as well as content.
	 *
	 * @access public
	 * @param mixed $wp
	 * @return void
	 */
	public function shop_order_search_custom_fields( $wp ) {
		global $pagenow, $wpdb;

		if ( 'edit.php' != $pagenow || empty( $wp->query_vars['s'] ) || $wp->query_vars['post_type'] != 'shop_order' )
			return $wp;

		$search_fields = array_map( 'esc_attr', apply_filters( 'woocommerce_shop_order_search_fields', array(
			'_order_key',
			'_billing_first_name',
			'_billing_last_name',
			'_billing_company',
			'_billing_address_1',
			'_billing_address_2',
			'_billing_city',
			'_billing_postcode',
			'_billing_country',
			'_billing_state',
			'_billing_email',
			'_billing_phone'
		) ) );

		$search_order_id = str_replace( 'Order #', '', $_GET['s'] );
		if ( ! is_numeric( $search_order_id ) )
			$search_order_id = 0;

		// Search orders
		$post_ids = array_merge(
			$wpdb->get_col(
				$wpdb->prepare( "
					SELECT post_id
					FROM {$wpdb->postmeta}
					WHERE meta_key IN ('" . implode( "','", $search_fields ) . "')
					AND meta_value LIKE '%%%s%%'",
					esc_attr( $_GET['s'] )
				)
			),
			$wpdb->get_col(
				$wpdb->prepare( "
					SELECT order_id
					FROM {$wpdb->prefix}woocommerce_order_items as order_items
					WHERE order_item_name LIKE '%%%s%%'
					",
					esc_attr( $_GET['s'] )
				)
			),
			array( $search_order_id )
		);

		// Remove s - we don't want to search order name
		unset( $wp->query_vars['s'] );

		// so we know we're doing this
		$wp->query_vars['shop_order_search'] = true;

		// Search by found posts
		$wp->query_vars['post__in'] = $post_ids;
	}

	/**
	 * Change the label when searching orders.
	 *
	 * @access public
	 * @param mixed $query
	 * @return string
	 */
	public function shop_order_search_label($query) {
		global $pagenow, $typenow;

	    if ( 'edit.php' != $pagenow ) return $query;
	    if ( $typenow != 'shop_order' ) return $query;
		if ( ! get_query_var( 'shop_order_search' ) ) return $query;

		return $_GET['s'];
	}

	/**
	 * Query vars for custom searches.
	 *
	 * @access public
	 * @param mixed $public_query_vars
	 * @return array
	 */
	public function add_custom_query_var($public_query_vars) {
		$public_query_vars[] = 'sku';
		$public_query_vars[] = 'shop_order_search';
		return $public_query_vars;
	}

	/**
	 * Remove item meta on permanent deletion
	 *
	 * @access public
	 * @return void
	 **/
	public function delete_order_items( $postid ) {
		global $wpdb;

		if ( get_post_type( $postid ) == 'shop_order' )
		{
			$wpdb->query( "
				DELETE {$wpdb->prefix}woocommerce_order_items, {$wpdb->prefix}woocommerce_order_itemmeta
				FROM {$wpdb->prefix}woocommerce_order_items
				JOIN {$wpdb->prefix}woocommerce_order_itemmeta ON {$wpdb->prefix}woocommerce_order_items.order_item_id = {$wpdb->prefix}woocommerce_order_itemmeta.order_item_id
				WHERE {$wpdb->prefix}woocommerce_order_items.order_id = '{$postid}';
				" );
		}
	}

}

endif;

return new WC_Admin_CPT_Shop_Order();