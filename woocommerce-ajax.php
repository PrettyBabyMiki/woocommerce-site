<?php
/**
 * WooCommerce Ajax Handlers
 *
 * Handles AJAX requests via wp_ajax hook (both admin and front-end events)
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions/AJAX
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/** Frontend AJAX events **************************************************/

/**
 * Process ajax login
 *
 * @access public
 * @return void
 */
function woocommerce_sidebar_login_ajax_process() {

	check_ajax_referer( 'woocommerce-sidebar-login-action', 'security' );

	// Get post data
	$creds = array();
	$creds['user_login'] 	= $_REQUEST['user_login'];
	$creds['user_password'] = $_REQUEST['user_password'];
	$creds['remember'] 		= 'forever';
	$redirect_to 			= esc_url( $_REQUEST['redirect_to'] );

	// Check for Secure Cookie
	$secure_cookie = '';

	// If the user wants ssl but the session is not ssl, force a secure cookie.
	if ( ! force_ssl_admin() ) {
		$user_name = sanitize_user( $creds['user_login']  );
		if ( $user = get_user_by('login',  $user_name ) ) {
			if ( get_user_option( 'use_ssl', $user->ID ) ) {
				$secure_cookie = true;
				force_ssl_admin( true );
			}
		}
	}

	if ( force_ssl_admin() ) $secure_cookie = true;
	if ( $secure_cookie == '' && force_ssl_login() ) $secure_cookie = false;

	// Login
	$user = wp_signon( $creds, $secure_cookie );

	// Redirect filter
	if ( $secure_cookie && strstr( $redirect_to, 'wp-admin' ) ) $redirect_to = str_replace( 'http:', 'https:', $redirect_to );

	// Result
	$result = array();

	if ( ! is_wp_error( $user ) ) {
		$result['success'] = 1;
		$result['redirect'] = $redirect_to;
	} else {
		$result['success'] = 0;

		if ( $user->errors ) {
			foreach ( $user->errors as $error ) {
				$result['error'] = esc_html( $error[0] );
				break;
			}
		} else {
			$result['error'] = __( 'Please enter your username and password to login.', 'woocommerce' );
		}
	}

	header( 'content-type: application/json; charset=utf-8' );

	echo esc_js( $_GET['callback'] ) . '(' . json_encode( $result ) . ')';

	die();
}

add_action( 'wp_ajax_nopriv_woocommerce_sidebar_login_process', 'woocommerce_sidebar_login_ajax_process' );


/**
 * AJAX apply coupon on checkout page
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_apply_coupon() {
	global $woocommerce;

	check_ajax_referer( 'apply-coupon', 'security' );

	if ( ! empty( $_POST['coupon_code'] ) ) {
		$woocommerce->cart->add_discount( sanitize_text_field( $_POST['coupon_code'] ) );
	} else {
		$woocommerce->add_error( __( 'Please enter a coupon code.', 'woocommerce' ) );
	}

	$woocommerce->show_messages();

	die();
}

add_action('wp_ajax_woocommerce_apply_coupon', 'woocommerce_ajax_apply_coupon');
add_action('wp_ajax_nopriv_woocommerce_apply_coupon', 'woocommerce_ajax_apply_coupon');


/**
 * AJAX update shipping method on cart page
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_update_shipping_method() {
	global $woocommerce;

	check_ajax_referer( 'update-shipping-method', 'security' );

	if ( ! defined('WOOCOMMERCE_CART') ) define( 'WOOCOMMERCE_CART', true );

	if ( isset( $_POST['shipping_method'] ) )
		$woocommerce->session->chosen_shipping_method = $_POST['shipping_method'];

	$woocommerce->cart->calculate_totals();

	woocommerce_cart_totals();

	die();
}

add_action('wp_ajax_woocommerce_update_shipping_method', 'woocommerce_ajax_update_shipping_method');
add_action('wp_ajax_nopriv_woocommerce_update_shipping_method', 'woocommerce_ajax_update_shipping_method');


/**
 * AJAX update order review on checkout
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_update_order_review() {
	global $woocommerce;

	check_ajax_referer( 'update-order-review', 'security' );

	if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
		define( 'WOOCOMMERCE_CHECKOUT', true );

	if ( sizeof( $woocommerce->cart->get_cart() ) == 0 ) {
		echo '<div class="woocommerce_error">' . __( 'Sorry, your session has expired.', 'woocommerce' ) . ' <a href="' . home_url() . '">' . __( 'Return to homepage &rarr;', 'woocommerce' ) . '</a></div>';
		die();
	}

	do_action( 'woocommerce_checkout_update_order_review', $_POST['post_data'] );

	$woocommerce->session->chosen_shipping_method = empty( $_POST['shipping_method'] ) ? '' : $_POST['shipping_method'];
	$woocommerce->session->chosen_payment_method  = empty( $_POST['payment_method'] ) ? '' : $_POST['payment_method'];

	if ( isset( $_POST['country'] ) )
		$woocommerce->customer->set_country( $_POST['country'] );

	if ( isset( $_POST['state'] ) )
		$woocommerce->customer->set_state( $_POST['state'] );

	if ( isset( $_POST['postcode'] ) )
		$woocommerce->customer->set_postcode( $_POST['postcode'] );

	if ( isset( $_POST['city'] ) )
		$woocommerce->customer->set_city( $_POST['city'] );

	if ( isset( $_POST['address'] ) )
		$woocommerce->customer->set_address( $_POST['address'] );

	if ( isset( $_POST['address_2'] ) )
		$woocommerce->customer->set_address_2( $_POST['address_2'] );

	if ( isset( $_POST['s_country'] ) )
		$woocommerce->customer->set_shipping_country( $_POST['s_country'] );

	if ( isset( $_POST['s_state'] ) )
		$woocommerce->customer->set_shipping_state( $_POST['s_state'] );

	if ( isset( $_POST['s_postcode'] ) )
		$woocommerce->customer->set_shipping_postcode( $_POST['s_postcode'] );

	if ( isset( $_POST['s_city'] ) )
		$woocommerce->customer->set_shipping_city( $_POST['s_city'] );

	if ( isset( $_POST['s_address'] ) )
		$woocommerce->customer->set_shipping_address( $_POST['s_address'] );

	if ( isset( $_POST['s_address_2'] ) )
		$woocommerce->customer->set_shipping_address_2( $_POST['s_address_2'] );

	$woocommerce->cart->calculate_totals();

	do_action( 'woocommerce_checkout_order_review' ); // Display review order table

	die();
}

add_action('wp_ajax_woocommerce_update_order_review', 'woocommerce_ajax_update_order_review');
add_action('wp_ajax_nopriv_woocommerce_update_order_review', 'woocommerce_ajax_update_order_review');


/**
 * AJAX add to cart
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_add_to_cart() {

	global $woocommerce;

	check_ajax_referer( 'add-to-cart', 'security' );

	$product_id = (int) apply_filters('woocommerce_add_to_cart_product_id', $_POST['product_id']);

	$passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, 1);

	if ($passed_validation && $woocommerce->cart->add_to_cart($product_id, 1)) :
		// Return html fragments
		$data = apply_filters('add_to_cart_fragments', array());
		do_action( 'woocommerce_ajax_added_to_cart', $product_id);
	else :
		// If there was an error adding to the cart, redirect to the product page to show any errors
		$data = array(
			'error' => true,
			'product_url' => get_permalink( $product_id )
		);
		$woocommerce->set_messages();
	endif;

	echo json_encode( $data );

	die();
}

add_action('wp_ajax_woocommerce_add_to_cart', 'woocommerce_ajax_add_to_cart');
add_action('wp_ajax_nopriv_woocommerce_add_to_cart', 'woocommerce_ajax_add_to_cart');


/**
 * Process ajax checkout form
 *
 * @access public
 * @return void
 */
function woocommerce_process_checkout() {
	global $woocommerce;

	if (!defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);

	$woocommerce_checkout = $woocommerce->checkout();
	$woocommerce_checkout->process_checkout();

	die(0);
}

add_action('wp_ajax_woocommerce-checkout', 'woocommerce_process_checkout');
add_action('wp_ajax_nopriv_woocommerce-checkout', 'woocommerce_process_checkout');



/** Admin AJAX events *****************************************************/

/**
 * Feature a product from admin
 *
 * @access public
 * @return void
 */
function woocommerce_feature_product() {

	if ( ! is_admin() ) die;

	if ( ! current_user_can('edit_products') ) wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce' ) );

	if ( ! check_admin_referer('woocommerce-feature-product')) wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce' ) );

	$post_id = isset( $_GET['product_id'] ) && (int) $_GET['product_id'] ? (int) $_GET['product_id'] : '';

	if (!$post_id) die;

	$post = get_post($post_id);

	if ( ! $post || $post->post_type !== 'product' ) die;

	$featured = get_post_meta( $post->ID, '_featured', true );

	if ( $featured == 'yes' )
		update_post_meta($post->ID, '_featured', 'no');
	else
		update_post_meta($post->ID, '_featured', 'yes');

	wp_safe_redirect( remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), wp_get_referer() ) );
}

add_action('wp_ajax_woocommerce-feature-product', 'woocommerce_feature_product');


/**
 * Mark an order as complete
 *
 * @access public
 * @return void
 */
function woocommerce_mark_order_complete() {

	if ( !is_admin() ) die;
	if ( !current_user_can('edit_shop_orders') ) wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce' ) );
	if ( !check_admin_referer('woocommerce-mark-order-complete')) wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce' ) );
	$order_id = isset($_GET['order_id']) && (int) $_GET['order_id'] ? (int) $_GET['order_id'] : '';
	if (!$order_id) die;

	$order = new WC_Order( $order_id );
	$order->update_status( 'completed' );

	wp_safe_redirect( wp_get_referer() );

}
add_action('wp_ajax_woocommerce-mark-order-complete', 'woocommerce_mark_order_complete');


/**
 * Mark an order as processing
 *
 * @access public
 * @return void
 */
function woocommerce_mark_order_processing() {

	if ( !is_admin() ) die;
	if ( !current_user_can('edit_shop_orders') ) wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce' ) );
	if ( !check_admin_referer('woocommerce-mark-order-processing')) wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce' ) );
	$order_id = isset($_GET['order_id']) && (int) $_GET['order_id'] ? (int) $_GET['order_id'] : '';
	if (!$order_id) die;

	$order = new WC_Order( $order_id );
	$order->update_status( 'processing' );

	wp_safe_redirect( wp_get_referer() );

}
add_action('wp_ajax_woocommerce-mark-order-processing', 'woocommerce_mark_order_processing');


/**
 * Add a new attribute via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_add_new_attribute() {

	check_ajax_referer( 'add-attribute', 'security' );

	$taxonomy = esc_attr( $_POST['taxonomy'] );
	$term = stripslashes( $_POST['term'] );

	if ( taxonomy_exists( $taxonomy ) ) {

		$result = wp_insert_term( $term, $taxonomy );

		if ( is_wp_error($result) ) {
   			echo json_encode(array(
				'error'			=> $result->get_error_message()
			));
   		} else {
	   		echo json_encode(array(
				'term_id'		=> $result['term_id'],
				'name'			=> $term,
				'slug'  		=> sanitize_title( $term ),
			));
		}
	}

	die();
}

add_action('wp_ajax_woocommerce_add_new_attribute', 'woocommerce_add_new_attribute');


/**
 * Delete variation via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_remove_variation() {

	check_ajax_referer( 'delete-variation', 'security' );
	$variation_id = intval( $_POST['variation_id'] );
	$variation = get_post($variation_id);
	if ( $variation && $variation->post_type == "product_variation" )
		wp_delete_post( $variation_id );
	die();
}

add_action('wp_ajax_woocommerce_remove_variation', 'woocommerce_remove_variation');


/**
 * Delete variations via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_remove_variations() {

	check_ajax_referer( 'delete-variations', 'security' );
	$variation_ids = (array) $_POST['variation_ids'];
	foreach ( $variation_ids as $variation_id ) {
		$variation = get_post($variation_id);
		if ( $variation && $variation->post_type == "product_variation" )
			wp_delete_post( $variation_id );
	}
	die();
}

add_action('wp_ajax_woocommerce_remove_variations', 'woocommerce_remove_variations');


function woocommerce_save_attributes() {
	global $woocommerce;

	check_ajax_referer( 'save-attributes', 'security' );

	// Get post ID
	$post_id 	= absint( $_POST['post_id'] );
	parse_str( $_POST['data'], $data );

	// Save Attributes
	$attributes = array();

	if ( isset( $data['attribute_names'] ) ) {
		$attribute_names = $data['attribute_names'];
		$attribute_values = $data['attribute_values'];

		if ( isset( $data['attribute_visibility'] ) )
			$attribute_visibility = $data['attribute_visibility'];

		if ( isset( $data['attribute_variation'] ) )
			$attribute_variation = $data['attribute_variation'];

		$attribute_is_taxonomy = $data['attribute_is_taxonomy'];
		$attribute_position = $data['attribute_position'];

		$attribute_names_count = sizeof( $attribute_names );

		for ( $i=0; $i < $attribute_names_count; $i++ ) {
			if ( ! $attribute_names[ $i ] )
				continue;

			$is_visible 	= isset( $attribute_visibility[ $i ] ) ? 1 : 0;
			$is_variation 	= isset( $attribute_variation[ $i ] ) ? 1 : 0;
			$is_taxonomy 	= $attribute_is_taxonomy[ $i ] ? 1 : 0;

			if ( $is_taxonomy ) {

				if ( isset( $attribute_values[ $i ] ) ) {

			 		// Format values
			 		if ( is_array( $attribute_values[ $i ] ) ) {
				 		$values = array_map('htmlspecialchars', array_map('stripslashes', $attribute_values[ $i ]));
				 	} else {
				 		// Text based, separate by pipe
				 		$values = htmlspecialchars( stripslashes( $attribute_values[ $i ] ) );
				 		$values = explode( '|', $values );
				 		$values = array_map( 'trim', $values );
				 	}

				 	// Remove empty items in the array
				 	$values = array_filter( $values );

			 	} else {
			 		$values = array();
			 	}

		 		// Update post terms
		 		if ( taxonomy_exists( $attribute_names[ $i ] ) )
		 			wp_set_object_terms( $post_id, $values, $attribute_names[ $i ] );

		 		if ( $values ) {
			 		// Add attribute to array, but don't set values
			 		$attributes[ sanitize_title( $attribute_names[ $i ] ) ] = array(
				 		'name' 			=> htmlspecialchars( stripslashes( $attribute_names[ $i ] ) ),
				 		'value' 		=> '',
				 		'position' 		=> $attribute_position[ $i ],
				 		'is_visible' 	=> $is_visible,
				 		'is_variation' 	=> $is_variation,
				 		'is_taxonomy' 	=> $is_taxonomy
				 	);
			 	}

		 	} else {
		 		if ( ! $attribute_values[ $i ] ) continue;
		 		// Format values
		 		$values = esc_html( stripslashes( $attribute_values[ $i ] ) );
		 		// Text based, separate by pipe
		 		$values = explode( '|', $values );
		 		$values = array_map( 'trim', $values );
		 		$values = implode( '|', $values );

		 		// Custom attribute - Add attribute to array and set the values
			 	$attributes[ sanitize_title( $attribute_names[ $i ] ) ] = array(
			 		'name' 			=> htmlspecialchars( stripslashes( $attribute_names[ $i ] ) ),
			 		'value' 		=> $values,
			 		'position' 		=> $attribute_position[ $i ],
			 		'is_visible' 	=> $is_visible,
			 		'is_variation' 	=> $is_variation,
			 		'is_taxonomy' 	=> $is_taxonomy
			 	);
		 	}

		 }
	}

	if ( ! function_exists( 'attributes_cmp' ) ) {
		function attributes_cmp( $a, $b ) {
		    if ( $a['position'] == $b['position'] ) return 0;
		    return ( $a['position'] < $b['position'] ) ? -1 : 1;
		}
	}
	uasort( $attributes, 'attributes_cmp' );

	update_post_meta( $post_id, '_product_attributes', $attributes );

	die();
}

add_action('wp_ajax_woocommerce_save_attributes', 'woocommerce_save_attributes');

/**
 * Add variation via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_add_variation() {
	global $woocommerce;

	check_ajax_referer( 'add-variation', 'security' );

	$post_id = intval( $_POST['post_id'] );
	$loop = intval( $_POST['loop'] );

	$variation = array(
		'post_title' 	=> 'Product #' . $post_id . ' Variation',
		'post_content' 	=> '',
		'post_status' 	=> 'publish',
		'post_author' 	=> get_current_user_id(),
		'post_parent' 	=> $post_id,
		'post_type' 	=> 'product_variation'
	);

	$variation_id = wp_insert_post( $variation );

	do_action( 'woocommerce_create_product_variation', $variation_id );

	if ( $variation_id ) {

		$variation_post_status = 'publish';
		$variation_data = get_post_custom( $variation_id );
		$variation_data['variation_post_id'] = $variation_id;

		// Get attributes
		$attributes = (array) maybe_unserialize( get_post_meta( $post_id, '_product_attributes', true ) );

		// Get tax classes
		$tax_classes = array_filter(array_map('trim', explode("\n", get_option('woocommerce_tax_classes'))));
		$tax_class_options = array();
		$tax_class_options['parent'] =__( 'Same as parent', 'woocommerce' );
		$tax_class_options[''] = __( 'Standard', 'woocommerce' );
		if ($tax_classes) foreach ( $tax_classes as $class )
			$tax_class_options[sanitize_title($class)] = $class;

		// Get parent data
		$parent_data = array(
			'id'		=> $post_id,
			'attributes' => $attributes,
			'tax_class_options' => $tax_class_options,
			'sku' 		=> get_post_meta( $post_id, '_sku', true ),
			'weight' 	=> get_post_meta( $post_id, '_weight', true ),
			'length' 	=> get_post_meta( $post_id, '_length', true ),
			'width' 	=> get_post_meta( $post_id, '_width', true ),
			'height' 	=> get_post_meta( $post_id, '_height', true ),
			'tax_class' => get_post_meta( $post_id, '_tax_class', true )
		);

		if ( ! $parent_data['weight'] )
			$parent_data['weight'] = '0.00';

		if ( ! $parent_data['length'] )
			$parent_data['length'] = '0';

		if ( ! $parent_data['width'] )
			$parent_data['width'] = '0';

		if ( ! $parent_data['height'] )
			$parent_data['height'] = '0';

		$_tax_class = '';
		$image_id = 0;

		include( 'admin/post-types/writepanels/variation-admin-html.php' );
	}

	die();
}

add_action('wp_ajax_woocommerce_add_variation', 'woocommerce_add_variation');


/**
 * Link all variations via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_link_all_variations() {
	global $woocommerce;

	if ( ! defined( 'WC_MAX_LINKED_VARIATIONS' ) ) {
		define( 'WC_MAX_LINKED_VARIATIONS', 49 );
	}

	check_ajax_referer( 'link-variations', 'security' );

	@set_time_limit(0);

	$post_id = intval( $_POST['post_id'] );

	if ( ! $post_id ) die();

	$variations = array();

	$_product = get_product( $post_id, array( 'product_type' => 'variable' ) );

	// Put variation attributes into an array
	foreach ( $_product->get_attributes() as $attribute ) {

		if ( ! $attribute['is_variation'] ) continue;

		$attribute_field_name = 'attribute_' . sanitize_title( $attribute['name'] );

		if ( $attribute['is_taxonomy'] ) {
			$post_terms = wp_get_post_terms( $post_id, $attribute['name'] );
			$options = array();
			foreach ( $post_terms as $term ) {
				$options[] = $term->slug;
			}
		} else {
			$options = explode('|', $attribute['value']);
		}

		$options = array_map('trim', $options);

		$variations[ $attribute_field_name ] = $options;
	}

	// Quit out if none were found
	if ( sizeof( $variations ) == 0 ) die();

	// Get existing variations so we don't create duplicates
    $available_variations = array();

    foreach( $_product->get_children() as $child_id ) {
    	$child = $_product->get_child( $child_id );

        if ( ! empty( $child->variation_id ) ) {
            $available_variations[] = $child->get_variation_attributes();
        }
    }

	// Created posts will all have the following data
	$variation_post_data = array(
		'post_title' => 'Product #' . $post_id . ' Variation',
		'post_content' => '',
		'post_status' => 'publish',
		'post_author' => get_current_user_id(),
		'post_parent' => $post_id,
		'post_type' => 'product_variation'
	);

	// Now find all combinations and create posts
	if ( ! function_exists( 'array_cartesian' ) ) {
		function array_cartesian( $input ) {
		    $result = array();

		    while ( list( $key, $values ) = each( $input ) ) {
		        // If a sub-array is empty, it doesn't affect the cartesian product
		        if ( empty( $values ) ) {
		            continue;
		        }

		        // Special case: seeding the product array with the values from the first sub-array
		        if ( empty( $result ) ) {
		            foreach ( $values as $value ) {
		                $result[] = array( $key => $value );
		            }
		        }
		        else {
		            // Second and subsequent input sub-arrays work like this:
		            //   1. In each existing array inside $product, add an item with
		            //      key == $key and value == first item in input sub-array
		            //   2. Then, for each remaining item in current input sub-array,
		            //      add a copy of each existing array inside $product with
		            //      key == $key and value == first item in current input sub-array

		            // Store all items to be added to $product here; adding them on the spot
		            // inside the foreach will result in an infinite loop
		            $append = array();
		            foreach( $result as &$product ) {
		                // Do step 1 above. array_shift is not the most efficient, but it
		                // allows us to iterate over the rest of the items with a simple
		                // foreach, making the code short and familiar.
		                $product[ $key ] = array_shift( $values );

		                // $product is by reference (that's why the key we added above
		                // will appear in the end result), so make a copy of it here
		                $copy = $product;

		                // Do step 2 above.
		                foreach( $values as $item ) {
		                    $copy[ $key ] = $item;
		                    $append[] = $copy;
		                }

		                // Undo the side effecst of array_shift
		                array_unshift( $values, $product[ $key ] );
		            }

		            // Out of the foreach, we can add to $results now
		            $result = array_merge( $result, $append );
		        }
		    }

		    return $result;
		}
	}

	$variation_ids = array();
	$added = 0;
	$possible_variations = array_cartesian( $variations );

	foreach ( $possible_variations as $variation ) {

		// Check if variation already exists
		if ( in_array( $variation, $available_variations ) ) continue;

		$variation_id = wp_insert_post( $variation_post_data );

		$variation_ids[] = $variation_id;

		foreach ( $variation as $key => $value ) {
			update_post_meta( $variation_id, $key, $value );
		}

		$added++;

		do_action( 'product_variation_linked', $variation_id );

		if ( $added > WC_MAX_LINKED_VARIATIONS ) break;

	}

	$woocommerce->clear_product_transients( $post_id );

	echo $added;

	die();
}

add_action( 'wp_ajax_woocommerce_link_all_variations', 'woocommerce_link_all_variations' );


/**
 * Delete download permissions via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_revoke_access_to_download() {

	check_ajax_referer( 'revoke-access', 'security' );

	global $wpdb;

	$download_id = $_POST['download_id'];
	$product_id = intval( $_POST['product_id'] );
	$order_id 	= intval( $_POST['order_id'] );

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE order_id = %d AND product_id = %d AND download_id = %d;", $order_id, $product_id, $download_id ) );

	die();
}

add_action('wp_ajax_woocommerce_revoke_access_to_download', 'woocommerce_revoke_access_to_download');


/**
 * Grant download permissions via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_grant_access_to_download() {

	check_ajax_referer( 'grant-access', 'security' );

	global $wpdb;

	$order_id 	= intval( $_POST['order_id'] );
	$product_id = intval( $_POST['product_id'] );
	$loop 		= intval( $_POST['loop'] );
	$file_count = 0;

	$order 		= new WC_Order( $order_id );
	$product 	= get_product( $product_id );

	$user_email = sanitize_email( $order->billing_email );

	$limit		= trim( get_post_meta( $product_id, '_download_limit', true ) );
	$expiry 	= trim( get_post_meta( $product_id, '_download_expiry', true ) );
	$file_paths = apply_filters( 'woocommerce_file_download_paths', get_post_meta( $product_id, '_file_paths', true ), $product_id, $order_id, null );

    $limit 		= empty( $limit ) ? '' : (int) $limit;

    // Default value is NULL in the table schema
	$expiry 	= empty( $expiry ) ? null : (int) $expiry;

	if ( $expiry )
		$expiry = date_i18n( "Y-m-d", strtotime( 'NOW + ' . $expiry . ' DAY' ) );

	$wpdb->hide_errors();

	$response = array();
	if ( $file_paths ) {
		foreach ( $file_paths as $download_id => $file_path ) {

		    $data = array(
		    	'download_id'			=> $download_id,
		        'product_id' 			=> $product_id,
		        'user_id' 				=> (int) $order->user_id,
		        'user_email' 			=> $user_email,
		        'order_id' 				=> $order->id,
		        'order_key' 			=> $order->order_key,
		        'downloads_remaining' 	=> $limit,
		        'access_granted'		=> current_time( 'mysql' ),
		        'download_count'		=> 0
		    );

		    $format = array(
		    	'%s',
		        '%s',
		        '%s',
		        '%s',
		        '%s',
		        '%s',
		        '%s',
		        '%s',
		        '%d'
		    );

		    if ( ! is_null( $expiry ) ) {
		        $data['access_expires'] = $expiry;
		        $format[] = '%s';
		    }

		    // Downloadable product - give access to the customer
		    $success = $wpdb->insert( $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
		        $data,
		        $format
		    );

			if ( $success ) {

				$download = new stdClass();
				$download->product_id 	= $product_id;
				$download->download_id 	= $download_id;
				$download->order_id 	= $order->id;
				$download->order_key	= $order->order_key;
				$download->download_count 		= 0;
				$download->downloads_remaining 	= $limit;
				$download->access_expires 		= $expiry;

				$loop++;
				$file_count++;

				include( 'admin/post-types/writepanels/order-download-permission-html.php' );
			}
		}
	}

	die();
}

add_action('wp_ajax_woocommerce_grant_access_to_download', 'woocommerce_grant_access_to_download');


/**
 * Get customer details via ajax
 *
 * @access public
 * @return void
 */
function woocommerce_get_customer_details() {

	global $woocommerce;

	check_ajax_referer( 'get-customer-details', 'security' );

	$user_id = (int) trim(stripslashes($_POST['user_id']));
	$type_to_load = esc_attr(trim(stripslashes($_POST['type_to_load'])));

	$customer_data = array(
		$type_to_load . '_first_name' => get_user_meta( $user_id, $type_to_load . '_first_name', true ),
		$type_to_load . '_last_name' => get_user_meta( $user_id, $type_to_load . '_last_name', true ),
		$type_to_load . '_company' => get_user_meta( $user_id, $type_to_load . '_company', true ),
		$type_to_load . '_address_1' => get_user_meta( $user_id, $type_to_load . '_address_1', true ),
		$type_to_load . '_address_2' => get_user_meta( $user_id, $type_to_load . '_address_2', true ),
		$type_to_load . '_city' => get_user_meta( $user_id, $type_to_load . '_city', true ),
		$type_to_load . '_postcode' => get_user_meta( $user_id, $type_to_load . '_postcode', true ),
		$type_to_load . '_country' => get_user_meta( $user_id, $type_to_load . '_country', true ),
		$type_to_load . '_state' => get_user_meta( $user_id, $type_to_load . '_state', true ),
		$type_to_load . '_email' => get_user_meta( $user_id, $type_to_load . '_email', true ),
		$type_to_load . '_phone' => get_user_meta( $user_id, $type_to_load . '_phone', true ),
	);

	echo json_encode($customer_data);

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_get_customer_details', 'woocommerce_get_customer_details');


/**
 * Add order item via ajax
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_add_order_item() {
	global $woocommerce, $wpdb;

	check_ajax_referer( 'order-item', 'security' );

	$item_to_add = sanitize_text_field( $_POST['item_to_add'] );
	$order_id = absint( $_POST['order_id'] );

	// Find the item
	if ( ! is_numeric( $item_to_add ) )
		die();

	$post = get_post( $item_to_add );

	if ( ! $post || ( $post->post_type !== 'product' && $post->post_type !== 'product_variation' ) )
		die();

	$_product = get_product( $post->ID );

	$order = new WC_Order( $order_id );
	$class = 'new_row';

	// Set values
	$item = array();

	$item['product_id'] 			= $_product->id;
	$item['variation_id'] 			= isset( $_product->variation_id ) ? $_product->variation_id : '';
	$item['name'] 					= $_product->get_title();
	$item['tax_class']				= $_product->get_tax_class();
	$item['qty'] 					= 1;
	$item['line_subtotal'] 			= number_format( (double) $_product->get_price_excluding_tax(), 2, '.', '' );
	$item['line_subtotal_tax'] 		= '';
	$item['line_total'] 			= number_format( (double) $_product->get_price_excluding_tax(), 2, '.', '' );
	$item['line_tax'] 				= '';

	// Add line item
   	$item_id = woocommerce_add_order_item( $order_id, array(
 		'order_item_name' 		=> $item['name'],
 		'order_item_type' 		=> 'line_item'
 	) );

 	// Add line item meta
 	if ( $item_id ) {
	 	woocommerce_add_order_item_meta( $item_id, '_qty', $item['qty'] );
	 	woocommerce_add_order_item_meta( $item_id, '_tax_class', $item['tax_class'] );
	 	woocommerce_add_order_item_meta( $item_id, '_product_id', $item['product_id'] );
	 	woocommerce_add_order_item_meta( $item_id, '_variation_id', $item['variation_id'] );
	 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal', $item['line_subtotal'] );
	 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal_tax', $item['line_subtotal_tax'] );
	 	woocommerce_add_order_item_meta( $item_id, '_line_total', $item['line_total'] );
	 	woocommerce_add_order_item_meta( $item_id, '_line_tax', $item['line_tax'] );
 	}

	include( 'admin/post-types/writepanels/order-item-html.php' );

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_add_order_item', 'woocommerce_ajax_add_order_item');


/**
 * Add order fee via ajax
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_add_order_fee() {
	global $woocommerce;

	check_ajax_referer( 'order-item', 'security' );

	$order_id 	= absint( $_POST['order_id'] );
	$order 		= new WC_Order( $order_id );

	// Add line item
   	$item_id = woocommerce_add_order_item( $order_id, array(
 		'order_item_name' 		=> '',
 		'order_item_type' 		=> 'fee'
 	) );

 	// Add line item meta
 	if ( $item_id ) {
	 	woocommerce_add_order_item_meta( $item_id, '_tax_class', '' );
	 	woocommerce_add_order_item_meta( $item_id, '_line_total', '' );
	 	woocommerce_add_order_item_meta( $item_id, '_line_tax', '' );
 	}

	include( 'admin/post-types/writepanels/order-fee-html.php' );

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_add_order_fee', 'woocommerce_ajax_add_order_fee');


/**
 * woocommerce_ajax_remove_order_item function.
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_remove_order_item() {
	global $woocommerce, $wpdb;

	check_ajax_referer( 'order-item', 'security' );

	$order_item_ids = $_POST['order_item_ids'];

	if ( sizeof( $order_item_ids ) > 0 ) {
		foreach( $order_item_ids as $id ) {
			woocommerce_delete_order_item( absint( $id ) );
		}
	}

	die();
}

add_action( 'wp_ajax_woocommerce_remove_order_item', 'woocommerce_ajax_remove_order_item' );

/**
 * woocommerce_ajax_refund_order_item function.
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_refund_order_item() {
	global $woocommerce, $wpdb;

	check_ajax_referer( 'order-item', 'security' );

	$order_id = absint( $_POST['order_id'] );
	$order = &new WC_Order( $order_id );

	if ( $order ) {
		global $woocommerce;
		$gateways = $woocommerce->payment_gateways->get_available_payment_gateways();

		$automated_refund = false;
		$order_item_ids = $_POST['order_item_ids'];

		if ( isset( $gateways[ $order->payment_method ] ) ) {
			$gateway = $gateways[ $order->payment_method ];

			if ( in_array( 'refunds', $gateway->supports ) &&  method_exists( $gateway, 'refund' ) ) {
				$automated_refund = true;
			}
		}

		if ( sizeof( $order_item_ids ) > 0 ) {
			$refund_amount = 0;

			foreach( $order_item_ids as $item_id ) {
				$refunded = woocommerce_get_order_item_meta( $item_id, '_refunded', true );

				if ( 1 != $refunded ) {
					$amount = woocommerce_get_order_item_meta( $item_id, '_line_total', true );

					$refund_return = false;

					if ( $automated_refund ) {
						$refund_return = $gateway->refund( $order, absint( $item_id ), $amount );
					}

					if ( ! $automated_refund || $refund_return ) {
						$refund_amount = $refund_amount + $amount;

						woocommerce_update_order_item_meta( $item_id, '_refunded', 1 );
						do_action( 'woocommerce_refund_order_item', $item_id );
					}
				}
			}

			$order_refund_total = get_post_meta( $order_id, '_refund_total', true );

			if ( ! $order_refund_total ) {
				$order_refund_total = $refund_amount;
			} else {
				$order_refund_total = $order_refund_total + $refund_amount;
			}

			update_post_meta( $order_id, '_refund_total', woocommerce_format_total( $order_refund_total ) );
		}
	}

	die();
}

add_action( 'wp_ajax_woocommerce_refund_order_item', 'woocommerce_ajax_refund_order_item' );

/**
 * woocommerce_ajax_reduce_order_item_stock function.
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_reduce_order_item_stock() {
	global $woocommerce, $wpdb;

	check_ajax_referer( 'order-item', 'security' );

	$order_id		= absint( $_POST['order_id'] );
	$order_item_ids	= $_POST['order_item_ids'];
	$order_item_qty	= $_POST['order_item_qty'];
	$order 			= new WC_Order( $order_id );
	$order_items 	= $order->get_items();
	$return 		= array();

	if ( $order && ! empty( $order_items ) && sizeof( $order_item_ids ) > 0 ) {

		foreach ( $order_items as $item_id => $order_item ) {

			// Only reduce checked items
			if ( ! in_array( $item_id, $order_item_ids ) )
				continue;

			$_product = $order->get_product_from_item( $order_item );

			if ( $_product->exists() && $_product->managing_stock() && isset( $order_item_qty[ $item_id ] ) && $order_item_qty[ $item_id ] > 0 ) {

				$old_stock 		= $_product->stock;
				$new_quantity 	= $_product->reduce_stock( $order_item_qty[ $item_id ] );

				$return[] = sprintf( __( 'Item #%s stock reduced from %s to %s.', 'woocommerce' ), $order_item['product_id'], $old_stock, $new_quantity );
				$order->add_order_note( sprintf( __( 'Item #%s stock reduced from %s to %s.', 'woocommerce' ), $order_item['product_id'], $old_stock, $new_quantity) );
				$order->send_stock_notifications( $_product, $new_quantity, $order_item_qty[ $item_id ] );
			}
		}

		do_action( 'woocommerce_reduce_order_stock', $order );

		echo implode( ', ', $return );
	}

	die();
}

add_action( 'wp_ajax_woocommerce_reduce_order_item_stock', 'woocommerce_ajax_reduce_order_item_stock' );

/**
 * woocommerce_ajax_increase_order_item_stock function.
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_increase_order_item_stock() {
	global $woocommerce, $wpdb;

	check_ajax_referer( 'order-item', 'security' );

	$order_id		= absint( $_POST['order_id'] );
	$order_item_ids	= $_POST['order_item_ids'];
	$order_item_qty	= $_POST['order_item_qty'];
	$order 			= new WC_Order( $order_id );
	$order_items 	= $order->get_items();
	$return 		= array();

	if ( $order && ! empty( $order_items ) && sizeof( $order_item_ids ) > 0 ) {

		foreach ( $order_items as $item_id => $order_item ) {

			// Only reduce checked items
			if ( ! in_array( $item_id, $order_item_ids ) )
				continue;

			$_product = $order->get_product_from_item( $order_item );

			if ( $_product->exists() && $_product->managing_stock() && isset( $order_item_qty[ $item_id ] ) && $order_item_qty[ $item_id ] > 0 ) {

				$old_stock 		= $_product->stock;
				$new_quantity 	= $_product->increase_stock( $order_item_qty[ $item_id ] );

				$return[] = sprintf( __( 'Item #%s stock increased from %s to %s.', 'woocommerce' ), $order_item['product_id'], $old_stock, $new_quantity );
				$order->add_order_note( sprintf( __( 'Item #%s stock increased from %s to %s.', 'woocommerce' ), $order_item['product_id'], $old_stock, $new_quantity ) );
			}
		}

		do_action( 'woocommerce_restore_order_stock', $order );

		echo implode( ', ', $return );
	}

	die();
}

add_action( 'wp_ajax_woocommerce_increase_order_item_stock', 'woocommerce_ajax_increase_order_item_stock' );

/**
 * woocommerce_ajax_add_order_item_meta function.
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_add_order_item_meta() {
	global $woocommerce, $wpdb;

	check_ajax_referer( 'order-item', 'security' );

	$meta_id = woocommerce_add_order_item_meta( absint( $_POST['order_item_id'] ), __( 'Name', 'woocommerce' ), __( 'Value', 'woocommerce' ) );

	if ( $meta_id ) {

		echo '<tr data-meta_id="' . $meta_id . '"><td><input type="text" name="meta_key[' . $meta_id . ']" value="" /></td><td><input type="text" name="meta_value[' . $meta_id . ']" value="" /></td><td width="1%"><button class="remove_order_item_meta button">&times;</button></td></tr>';

	}

	die();
}

add_action( 'wp_ajax_woocommerce_add_order_item_meta', 'woocommerce_ajax_add_order_item_meta' );


/**
 * woocommerce_ajax_remove_order_item_meta function.
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_remove_order_item_meta() {
	global $woocommerce, $wpdb;

	check_ajax_referer( 'order-item', 'security' );

	$meta_id = absint( $_POST['meta_id'] );

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_id = %d", $meta_id ) );

	die();
}

add_action( 'wp_ajax_woocommerce_remove_order_item_meta', 'woocommerce_ajax_remove_order_item_meta' );


/**
 * Calc line tax
 *
 * @access public
 * @return void
 */
function woocommerce_calc_line_taxes() {
	global $woocommerce, $wpdb;

	check_ajax_referer( 'calc-totals', 'security' );

	$tax = new WC_Tax();

	$taxes = $tax_rows = $item_taxes = $shipping_taxes = array();

	$order_id 		= absint( $_POST['order_id'] );
	$country 		= strtoupper( esc_attr( $_POST['country'] ) );
	$state 			= strtoupper( esc_attr( $_POST['state'] ) );
	$postcode 		= strtoupper( esc_attr( $_POST['postcode'] ) );
	$city 			= sanitize_title( esc_attr( $_POST['city'] ) );

	$items			= $_POST['items'];
	$shipping		= $_POST['shipping'];
	$item_tax		= 0;

	// Calculate sales tax first
	if ( sizeof( $items ) > 0 ) {
		foreach( $items as $item_id => $item ) {

			$item_id		= absint( $item_id );
			$line_subtotal 	= isset( $item['line_subtotal']  ) ? esc_attr( $item['line_subtotal'] ) : '';
			$line_total		= esc_attr( $item['line_total'] );
			$tax_class 		= esc_attr( $item['tax_class'] );

			if ( ! $item_id || $tax_class == '0' )
				continue;

			// Get product details
			if ( get_post_type( $item_id ) == 'product' ) {
				$_product			= get_product( $item_id );
				$item_tax_status 	= $_product->get_tax_status();
			} else {
				$item_tax_status 	= 'taxable';
			}

			// Only calc if taxable
			if ( $item_tax_status == 'taxable' ) {

				$tax_rates = $tax->find_rates( array(
					'country' 	=> $country,
					'state' 	=> $state,
					'postcode' 	=> $postcode,
					'city'		=> $city,
					'tax_class' => $tax_class
				) );

				$line_subtotal_taxes = $tax->calc_tax( $line_subtotal, $tax_rates, false );
				$line_taxes = $tax->calc_tax( $line_total, $tax_rates, false );

				$line_subtotal_tax = rtrim( rtrim( number_format( array_sum( $line_subtotal_taxes ), 4, '.', '' ), '0' ), '.' );
				$line_tax = rtrim( rtrim( number_format( array_sum( $line_taxes ), 4, '.', '' ), '0' ), '.' );

				if ( $line_subtotal_tax < 0 )
					$line_subtotal_tax = 0;

				if ( $line_tax < 0 )
					$line_tax = 0;

				$item_taxes[ $item_id ] = array(
					'line_subtotal_tax' => $line_subtotal_tax,
					'line_tax' 			=> $line_tax
				);

				$item_tax += $line_tax;

				// Sum the item taxes
				foreach ( array_keys( $taxes + $line_taxes ) as $key )
					$taxes[ $key ] = ( isset( $line_taxes[ $key ] ) ? $line_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
			}

		}
	}

	// Now calculate shipping tax
	$matched_tax_rates = array();

	$tax_rates = $tax->find_rates( array(
		'country' 	=> $country,
		'state' 	=> $state,
		'postcode' 	=> $postcode,
		'city'		=> $city,
		'tax_class' => ''
	) );

	if ( $tax_rates )
		foreach ( $tax_rates as $key => $rate )
			if ( isset( $rate['shipping'] ) && $rate['shipping'] == 'yes' )
				$matched_tax_rates[ $key ] = $rate;

	$shipping_taxes = $tax->calc_shipping_tax( $shipping, $matched_tax_rates );
	$shipping_tax = rtrim( rtrim( number_format( array_sum( $shipping_taxes ), 2, '.', '' ), '0' ), '.' );

	// Remove old tax rows
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = 'tax' )", $order_id ) );

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = 'tax'", $order_id ) );

 	// Get tax rates
	$rates = $wpdb->get_results( "SELECT tax_rate_id, tax_rate_country, tax_rate_state, tax_rate_name, tax_rate_priority FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_name" );

	$tax_codes = array();

	foreach( $rates as $rate ) {
		$code = array();

		$code[] = $rate->tax_rate_country;
		$code[] = $rate->tax_rate_state;
		$code[] = $rate->tax_rate_name ? sanitize_title( $rate->tax_rate_name ) : 'TAX';
		$code[] = absint( $rate->tax_rate_priority );

		$tax_codes[ $rate->tax_rate_id ] = strtoupper( implode( '-', array_filter( $code ) ) );
	}

	// Now merge to keep tax rows
	ob_start();

	foreach ( array_keys( $taxes + $shipping_taxes ) as $key ) {

	 	$item 							= array();
		$item['name'] 					= $tax_codes[ $key ];
		$item['label'] 					= $tax->get_rate_label( $key );
		$item['compound'] 				= $tax->is_compound( $key ) ? 1 : 0;
		$item['tax_amount'] 			= woocommerce_format_total( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
		$item['shipping_tax_amount'] 	= woocommerce_format_total( isset( $shipping_taxes[ $key ] ) ? $shipping_taxes[ $key ] : 0 );

		if ( ! $item['label'] )
			$item['label'] = $woocommerce->countries->tax_or_vat();

		// Add line item
	   	$item_id = woocommerce_add_order_item( $order_id, array(
	 		'order_item_name' 		=> $item['name'],
	 		'order_item_type' 		=> 'tax'
	 	) );

	 	// Add line item meta
	 	if ( $item_id ) {
	 		woocommerce_add_order_item_meta( $item_id, 'rate_id', $key );
	 		woocommerce_add_order_item_meta( $item_id, 'label', $item['label'] );
		 	woocommerce_add_order_item_meta( $item_id, 'compound', $item['compound'] );
		 	woocommerce_add_order_item_meta( $item_id, 'tax_amount', $item['tax_amount'] );
		 	woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', $item['shipping_tax_amount'] );
	 	}

		include( 'admin/post-types/writepanels/order-tax-html.php' );
	}

	$tax_row_html = ob_get_clean();

	// Return
	echo json_encode( array(
		'item_tax' 		=> $item_tax,
		'item_taxes' 	=> $item_taxes,
		'shipping_tax' 	=> $shipping_tax,
		'tax_row_html' 	=> $tax_row_html
	) );

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_calc_line_taxes', 'woocommerce_calc_line_taxes');

/**
 * woocommerce_add_line_tax function.
 *
 * @access public
 * @return void
 */
function woocommerce_add_line_tax() {
	global $woocommerce, $wpdb;

	check_ajax_referer( 'calc-totals', 'security' );

	$order_id 	= absint( $_POST['order_id'] );
	$order 		= new WC_Order( $order_id );

 	// Get tax rates
	$rates = $wpdb->get_results( "SELECT tax_rate_id, tax_rate_country, tax_rate_state, tax_rate_name, tax_rate_priority FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_name" );

	$tax_codes = array();

	foreach( $rates as $rate ) {
		$code = array();

		$code[] = $rate->tax_rate_country;
		$code[] = $rate->tax_rate_state;
		$code[] = $rate->tax_rate_name ? sanitize_title( $rate->tax_rate_name ) : 'TAX';
		$code[] = absint( $rate->tax_rate_priority );

		$tax_codes[ $rate->tax_rate_id ] = strtoupper( implode( '-', array_filter( $code ) ) );
	}

	// Add line item
   	$item_id = woocommerce_add_order_item( $order_id, array(
 		'order_item_name' 		=> '',
 		'order_item_type' 		=> 'tax'
 	) );

 	// Add line item meta
 	if ( $item_id ) {
 		woocommerce_add_order_item_meta( $item_id, 'rate_id', '' );
 		woocommerce_add_order_item_meta( $item_id, 'label', '' );
	 	woocommerce_add_order_item_meta( $item_id, 'compound', '' );
	 	woocommerce_add_order_item_meta( $item_id, 'tax_amount', '' );
	 	woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', '' );
 	}

	include( 'admin/post-types/writepanels/order-tax-html.php' );

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_add_line_tax', 'woocommerce_add_line_tax');

/**
 * woocommerce_add_line_tax function.
 *
 * @access public
 * @return void
 */
function woocommerce_remove_line_tax() {
	global $woocommerce;

	check_ajax_referer( 'calc-totals', 'security' );

	$tax_row_id = absint( $_POST['tax_row_id'] );

	woocommerce_delete_order_item( $tax_row_id );

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_remove_line_tax', 'woocommerce_remove_line_tax');

/**
 * Add order note via ajax
 *
 * @access public
 * @return void
 */
function woocommerce_add_order_note() {

	global $woocommerce;

	check_ajax_referer( 'add-order-note', 'security' );

	$post_id 	= (int) $_POST['post_id'];
	$note		= wp_kses( trim( stripslashes( $_POST['note'] ) ), array( 'a' => array( 'href' => array(), 'title' => array() ), 'br' => array(), 'em' => array(), 'strong' => array() ) );
	$note_type	= $_POST['note_type'];

	$is_customer_note = $note_type == 'customer' ? 1 : 0;

	if ( $post_id > 0 ) {
		$order = new WC_Order( $post_id );
		$comment_id = $order->add_order_note( $note, $is_customer_note );

		echo '<li rel="'.$comment_id.'" class="note ';
		if ($is_customer_note) echo 'customer-note';
		echo '"><div class="note_content">';
		echo wpautop( wptexturize( $note ) );
		echo '</div><p class="meta"><a href="#" class="delete_note">'.__( 'Delete note', 'woocommerce' ).'</a></p>';
		echo '</li>';

	}

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_add_order_note', 'woocommerce_add_order_note');


/**
 * Delete order note via ajax
 *
 * @access public
 * @return void
 */
function woocommerce_delete_order_note() {

	global $woocommerce;

	check_ajax_referer( 'delete-order-note', 'security' );

	$note_id 	= (int) $_POST['note_id'];

	if ($note_id>0) :
		wp_delete_comment( $note_id );
	endif;

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_delete_order_note', 'woocommerce_delete_order_note');


/**
 * Search for products and return json
 *
 * @access public
 * @param string $x (default: '')
 * @param string $post_types (default: array('product'))
 * @return void
 */
function woocommerce_json_search_products( $x = '', $post_types = array('product') ) {

	check_ajax_referer( 'search-products', 'security' );

	$term = (string) urldecode(stripslashes(strip_tags($_GET['term'])));

	if (empty($term)) die();

	if ( is_numeric( $term ) ) {

		$args = array(
			'post_type'			=> $post_types,
			'post_status'	 	=> 'publish',
			'posts_per_page' 	=> -1,
			'post__in' 			=> array(0, $term),
			'fields'			=> 'ids'
		);

		$args2 = array(
			'post_type'			=> $post_types,
			'post_status'	 	=> 'publish',
			'posts_per_page' 	=> -1,
			'post_parent' 		=> $term,
			'fields'			=> 'ids'
		);

		$args3 = array(
			'post_type'			=> $post_types,
			'post_status' 		=> 'publish',
			'posts_per_page' 	=> -1,
			'meta_query' 		=> array(
				array(
				'key' 	=> '_sku',
				'value' => $term,
				'compare' => 'LIKE'
				)
			),
			'fields'			=> 'ids'
		);

		$posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ));

	} else {

		$args = array(
			'post_type'			=> $post_types,
			'post_status' 		=> 'publish',
			'posts_per_page' 	=> -1,
			's' 				=> $term,
			'fields'			=> 'ids'
		);

		$args2 = array(
			'post_type'			=> $post_types,
			'post_status' 		=> 'publish',
			'posts_per_page' 	=> -1,
			'meta_query' 		=> array(
				array(
				'key' 	=> '_sku',
				'value' => $term,
				'compare' => 'LIKE'
				)
			),
			'fields'			=> 'ids'
		);

		$posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 ) ));

	}

	$found_products = array();

	if ($posts) foreach ($posts as $post) {

		$SKU = get_post_meta($post, '_sku', true);

		if (isset($SKU) && $SKU) $SKU = ' (SKU: ' . $SKU . ')';

		$found_products[$post] = get_the_title( $post ) . ' &ndash; #' . $post . $SKU;

	}

	$found_products = apply_filters( 'woocommerce_json_search_found_products', $found_products);

	echo json_encode( $found_products );

	die();
}

add_action('wp_ajax_woocommerce_json_search_products', 'woocommerce_json_search_products');

/**
 * Search for product variations and return json
 *
 * @access public
 * @return void
 * @see woocommerce_json_search_products()
 */
function woocommerce_json_search_products_and_variations() {
	woocommerce_json_search_products( '', array('product', 'product_variation') );
}

add_action('wp_ajax_woocommerce_json_search_products_and_variations', 'woocommerce_json_search_products_and_variations');


/**
 * Search for customers and return json
 *
 * @access public
 * @return void
 */
function woocommerce_json_search_customers() {

	check_ajax_referer( 'search-customers', 'security' );

	$term = urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );

	if ( empty( $term ) )
		die();

	$default = isset( $_GET['default'] ) ? $_GET['default'] : __( 'Guest', 'woocommerce' );

	$found_customers = array( '' => $default );

	$customers_query = new WP_User_Query( array(
		'fields'			=> 'all',
		'orderby'			=> 'display_name',
		'search'			=> '*' . $term . '*',
		'search_columns'	=> array( 'ID', 'user_login', 'user_email', 'user_nicename' )
	) );

	$customers = $customers_query->get_results();

	if ( $customers ) {
		foreach ( $customers as $customer ) {
			$found_customers[ $customer->ID ] = $customer->display_name . ' (#' . $customer->ID . ' &ndash; ' . sanitize_email( $customer->user_email ) . ')';
		}
	}

	echo json_encode( $found_customers );
	die();
}

add_action('wp_ajax_woocommerce_json_search_customers', 'woocommerce_json_search_customers');


/**
 * Search for products for upsells/crosssells
 *
 * @access public
 * @return void
 */
function woocommerce_upsell_crosssell_search_products() {

	check_ajax_referer( 'search-products', 'security' );

	$search = (string) urldecode(stripslashes(strip_tags($_POST['search'])));
	$name = (string) urldecode(stripslashes(strip_tags($_POST['name'])));

	if (empty($search)) die();

	if (is_numeric($search)) :

		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'posts_per_page' => 15,
			'post__in' => array(0, $search)
		);

	else :

		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'posts_per_page' => 15,
			's' => $search
		);

	endif;

	$posts = apply_filters('woocommerce_upsell_crosssell_search_products', get_posts( $args ));

	if ($posts) : foreach ($posts as $post) :

		$SKU = get_post_meta($post->ID, '_sku', true);

		?>
		<li rel="<?php echo $post->ID; ?>"><button type="button" name="Add" class="button add_crosssell" title="Add"><?php _e( 'Cross-sell', 'woocommerce' ); ?> &rarr;</button><button type="button" name="Add" class="button add_upsell" title="Add"><?php _e( 'Up-sell', 'woocommerce' ); ?> &rarr;</button><strong><?php echo $post->post_title; ?></strong> &ndash; #<?php echo $post->ID; ?> <?php if (isset($SKU) && $SKU) echo 'SKU: '.esc_attr( $SKU ); ?><input type="hidden" class="product_id" value="0" /></li>
		<?php

	endforeach; else :

		?><li><?php _e( 'No products found', 'woocommerce' ); ?></li><?php

	endif;

	die();
}

add_action('wp_ajax_woocommerce_upsell_crosssell_search_products', 'woocommerce_upsell_crosssell_search_products');


/**
 * Ajax request handling for categories ordering
 *
 * @access public
 * @return void
 */
function woocommerce_term_ordering() {
	global $wpdb;

	$id = (int) $_POST['id'];
	$next_id  = isset($_POST['nextid']) && (int) $_POST['nextid'] ? (int) $_POST['nextid'] : null;
	$taxonomy = isset($_POST['thetaxonomy']) ? esc_attr( $_POST['thetaxonomy'] ) : null;
	$term = get_term_by('id', $id, $taxonomy);

	if ( !$id || !$term || !$taxonomy ) die(0);

	woocommerce_order_terms( $term, $next_id, $taxonomy );

	$children = get_terms($taxonomy, "child_of=$id&menu_order=ASC&hide_empty=0");

	if ( $term && sizeof($children) ) {
		echo 'children';
		die;
	}
}

add_action('wp_ajax_woocommerce-term-ordering', 'woocommerce_term_ordering');


/**
 * Ajax request handling for product ordering
 *
 * Based on Simple Page Ordering by 10up (http://wordpress.org/extend/plugins/simple-page-ordering/)
 *
 * @access public
 * @return void
 */
function woocommerce_product_ordering() {
	global $wpdb;

	// check permissions again and make sure we have what we need
	if ( ! current_user_can('edit_products') || empty( $_POST['id'] ) || ( ! isset( $_POST['previd'] ) && ! isset( $_POST['nextid'] ) ) )
		die(-1);

	// real post?
	if ( ! $post = get_post( $_POST['id'] ) )
		die(-1);

	$previd = isset( $_POST['previd'] ) ? $_POST['previd'] : false;
	$nextid = isset( $_POST['nextid'] ) ? $_POST['nextid'] : false;
	$new_pos = array(); // store new positions for ajax

	$siblings = $wpdb->get_results( $wpdb->prepare('
		SELECT ID, menu_order FROM %1$s AS posts
		WHERE 	posts.post_type 	= \'product\'
		AND 	posts.post_status 	IN ( \'publish\', \'pending\', \'draft\', \'future\', \'private\' )
		AND 	posts.ID			NOT IN (%2$d)
		ORDER BY posts.menu_order ASC, posts.ID DESC
	', $wpdb->posts, $post->ID) );

	$menu_order = 0;

	foreach( $siblings as $sibling ) {

		// if this is the post that comes after our repositioned post, set our repositioned post position and increment menu order
		if ( $nextid == $sibling->ID ) {
			$wpdb->update(
				$wpdb->posts,
				array(
					'menu_order' => $menu_order
				),
				array( 'ID' => $post->ID ),
				array( '%d' ),
				array( '%d' )
			);
			$new_pos[ $post->ID ] = $menu_order;
			$menu_order++;
		}

		// if repositioned post has been set, and new items are already in the right order, we can stop
		if ( isset( $new_pos[ $post->ID ] ) && $sibling->menu_order >= $menu_order )
			break;

		// set the menu order of the current sibling and increment the menu order
		$wpdb->update(
			$wpdb->posts,
			array(
				'menu_order' => $menu_order
			),
			array( 'ID' => $sibling->ID ),
			array( '%d' ),
			array( '%d' )
		);
		$new_pos[ $sibling->ID ] = $menu_order;
		$menu_order++;

		if ( ! $nextid && $previd == $sibling->ID ) {
			$wpdb->update(
				$wpdb->posts,
				array(
					'menu_order' => $menu_order
				),
				array( 'ID' => $post->ID ),
				array( '%d' ),
				array( '%d' )
			);
			$new_pos[$post->ID] = $menu_order;
			$menu_order++;
		}

	}

	die( json_encode( $new_pos ) );
}

add_action( 'wp_ajax_woocommerce_product_ordering', 'woocommerce_product_ordering' );


/**
 * woocommerce_product_images_box_upload function.
 *
 * @access public
 * @return void
 */
function woocommerce_product_images_box_upload() {

	check_ajax_referer( 'product-images-box-upload' );

	// Get posted data
	$file 			= $_FILES['async-upload'];
	$post_id 		= absint( $_POST['post_id'] );

	// Get size
	$attachments =& get_children( 'post_parent=' . $post_id . '&numberposts=-1&post_type=attachment&post_mime_type=image' );
	$gallery_size 	= absint( sizeof( $attachments ) );

	// Upload
	$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

	if( ! isset( $upload['error'] ) && isset( $upload['file'] ) ) {

		// Create attachment
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$attachment_id = wp_insert_attachment( array(
			'post_mime_type' 	=> $upload['type'],
			'post_title' 		=> preg_replace( '/\.[^.]+$/', '', basename( $upload['file'] ) ),
			'post_content' 		=> '',
			'post_status' 		=> 'inherit',
			'menu_order'		=> ( $gallery_size + 1 )
		), $upload['file'], $post_id );

		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $attachment_data );

		// Set featured image
		$post_thumb_id = get_post_meta( $post_id, '_thumbnail_id', true );

		if ( empty( $post_thumb_id ) )
			update_post_meta( $post_id, '_thumbnail_id', $attachment_id );

		// Return the result
		$image = wp_get_attachment_image_src( $attachment_id, 'full' );

		$result = array(
			'src'		=> $image[0],
			'post_id' 	=> $attachment_id,
			'edit_url'	=> admin_url( 'media-upload.php?post_id=' . $post_id . '&attachment_id=' . $attachment_id . '&tab=library&width=640&height=553&TB_iframe=1' )
		);

	} else {
		$result = array(
			'error'		=> $upload['error']
		);
	}

	echo json_encode( $result );
	die();
}

add_action( 'wp_ajax_woocommerce_product_images_box_upload', 'woocommerce_product_images_box_upload' );


/**
 * woocommerce_product_image_ordering function.
 *
 * @access public
 * @return void
 */
function woocommerce_product_image_ordering() {

	check_ajax_referer( 'product-image-ordering' );

	$post_id				= isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : false;
	$attachment_id			= isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : false;
	$prev_attachment_id		= isset( $_POST['prev_attachment_id'] ) ? absint( $_POST['prev_attachment_id'] ) : false;
	$next_attachment_id 	= isset( $_POST['next_attachment_id'] ) ? absint( $_POST['next_attachment_id'] ) : false;

	if ( ! $post_id || ! $attachment_id )
		die( -1 );

	$siblings = get_children( 'post_parent=' . $post_id . '&numberposts=-1&post_type=attachment&orderby=menu_order&order=ASC&post_mime_type=image' );

	$new_positions = array(); // store new positions for ajax
	$menu_order = 1;

	foreach( $siblings as $sibling_id => $sibling ) {

		if ( $sibling_id == $attachment_id )
			continue;

		// if this is the post that comes after our repositioned post, set our repositioned post position and increment menu order
		if ( $next_attachment_id && $next_attachment_id == $sibling_id ) {

			$attachment = array();
			$attachment['ID'] = $attachment_id;
			$attachment['menu_order'] = $menu_order;

			wp_update_post( $attachment );

			$new_positions[ $attachment_id ] = $menu_order;

			$menu_order++;
		}

		// if repositioned post has been set, and new items are already in the right order, we can stop
		if ( isset( $new_positions[ $attachment_id ] ) && $sibling->menu_order >= $menu_order )
			break;

		// set the menu order of the current sibling and increment the menu order
		$attachment = array();
		$attachment['ID'] = $sibling_id;
		$attachment['menu_order'] = $menu_order;
		wp_update_post( $attachment );

		$new_positions[ $sibling_id ] = $menu_order;
		$menu_order++;

		if ( ! $next_attachment_id && $prev_attachment_id == $sibling_id ) {

			$attachment = array();
			$attachment['ID'] = $attachment_id;
			$attachment['menu_order'] = $menu_order;

			wp_update_post( $attachment );

			$new_positions[ $attachment_id ] = $menu_order;

			$menu_order++;
		}
	}

	// Set featured image
	$new_positions = array_flip( $new_positions );
	if ( isset( $new_positions[1] ) )
		update_post_meta( $post_id, '_thumbnail_id', $new_positions[1] );

	die();
}

add_action( 'wp_ajax_woocommerce_product_image_ordering', 'woocommerce_product_image_ordering' );


/**
 * woocommerce_product_image_delete function.
 *
 * @access public
 * @return void
 */
function woocommerce_product_image_delete() {

	check_ajax_referer( 'product-image-delete' );

	$post_id				= isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : false;
	$attachment_id			= isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : false;

	if ( ! $post_id || ! $attachment_id )
		die( -1 );

	wp_delete_attachment( $attachment_id );

	$thumbnail_id = get_post_thumbnail_id( $post_id );

	if ( $thumbnail_id == $attachment_id ) {
		$attachments = get_children( 'post_parent=' . $post_id . '&numberposts=1&post_type=attachment&orderby=menu_order&order=ASC&post_mime_type=image' );
		foreach ( $attachments as $attachment_id => $attachment ) {
			update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
		}
	}

	die();
}

add_action( 'wp_ajax_woocommerce_product_image_delete', 'woocommerce_product_image_delete' );

/**
 * woocommerce_product_image_refresh function.
 *
 * @access public
 * @return void
 */
function woocommerce_product_image_refresh() {

	check_ajax_referer( 'product-image-refresh' );

	$post_id			= isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : false;

	if ( ! $post_id )
		die();
	?>
	<ul class="product_images">
		<?php
			$thumbnail_id 	= get_post_thumbnail_id( $post_id );

			if ( $thumbnail_id )
				echo '<li class="image" data-post_id="' . $thumbnail_id . '">
					' . wp_get_attachment_image( $thumbnail_id, 'full' ) . '
					<span class="loading"></span>
					<ul class="actions">
						<li><a href="#" class="delete">' . __( 'Delete', 'woocommerce' ) . '</a></li>
						<li><a href="' . admin_url( 'media-upload.php?post_id=' . $post_id . '&attachment_id=' . $thumbnail_id . '&tab=library&width=640&height=553&TB_iframe=1' ) . '" class="view thickbox" onclick="return false;">' . __( 'View', 'woocommerce' ) . '</a></li>
					</ul>
				</li>';

			$attachments =& get_children( 'post_parent=' . $post_id . '&numberposts=-1&post_type=attachment&orderby=menu_order&order=ASC&post_mime_type=image' );

			foreach ( $attachments as $attachment_id => $attachment ) {
				if ( $thumbnail_id == $attachment_id )
					continue;

				$exclude_class = get_post_meta( $attachment_id, '_woocommerce_exclude_image', true ) == 1 ? 'excluded' : '';

				echo '<li class="image ' . $exclude_class . '" data-post_id="' . $attachment_id . '">
					' . wp_get_attachment_image( $attachment_id, 'full' ) . '
					<span class="loading"></span>
					<ul class="actions">
						<li><a href="#" class="delete">' . __( 'Delete', 'woocommerce' ) . '</a></li>
						<li><a href="' . admin_url( 'media-upload.php?post_id=' . $post_id . '&attachment_id=' . $attachment_id . '&tab=library&width=640&height=553&TB_iframe=1' ) . '" class="view thickbox" onclick="return false;">' . __( 'View', 'woocommerce' ) . '</a></li>
					</ul>
				</li>';
			}
		?>
	</ul>
	<?php

	die();
}

add_action( 'wp_ajax_woocommerce_product_image_refresh', 'woocommerce_product_image_refresh' );