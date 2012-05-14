<?php
/**
 * WooCommerce Write Panels
 * 
 * Sets up the write panels used by products and orders (custom post types)
 *
 * @author 		WooThemes
 * @category 	Admin Write Panels
 * @package 	WooCommerce
 */
 
include_once('writepanel-product_data.php');
include_once('writepanel-coupon_data.php');
include_once('writepanel-order_data.php');
include_once('writepanel-order_notes.php');
include_once('writepanel-order_downloads.php');

/**
 * Init the meta boxes
 * 
 * Inits the write panels for both products and orders. Also removes unused default write panels.
 */
add_action( 'add_meta_boxes', 'woocommerce_meta_boxes' );

function woocommerce_meta_boxes() {
	global $post;
	
	// Products
	add_meta_box( 'woocommerce-product-type', __('Product Type', 'woocommerce'), 'woocommerce_product_type_box', 'product', 'normal', 'high' );
	add_meta_box( 'woocommerce-product-data', __('Product Data', 'woocommerce'), 'woocommerce_product_data_box', 'product', 'normal', 'high' );
	remove_meta_box( 'product_shipping_classdiv', 'product', 'side' );
	
	// Excerpt
	if ( function_exists('wp_editor') ) {
		remove_meta_box( 'postexcerpt', 'product', 'normal' );
		add_meta_box( 'postexcerpt', __('Product Short Description', 'woocommerce'), 'woocommerce_product_short_description_meta_box', 'product', 'normal' );
	}
	
	// Comments/Reviews
	remove_meta_box( 'commentstatusdiv', 'product', 'normal' );
	add_meta_box( 'commentstatusdiv', __('Product Reviews', 'woocommerce'), 'woocommerce_product_review_status_meta_box', 'product', 'normal' );
	
	if ( ('publish' == $post->post_status || 'private' == $post->post_status) ) {
		remove_meta_box( 'commentsdiv', 'product', 'normal' );
		add_meta_box( 'commentsdiv', __('Reviews', 'woocommerce'), 'post_comment_meta_box', 'product', 'normal' );
	}
	
	// Orders
	add_meta_box( 'woocommerce-order-data', __('Order Data', 'woocommerce'), 'woocommerce_order_data_meta_box', 'shop_order', 'normal', 'high' );
	add_meta_box( 'woocommerce-order-items', __('Order Items <small>&ndash; Note: if you edit quantities or remove items from the order you will need to manually change the item\'s stock levels.</small>', 'woocommerce'), 'woocommerce_order_items_meta_box', 'shop_order', 'normal', 'high');
	add_meta_box( 'woocommerce-order-totals', __('Order Totals', 'woocommerce'), 'woocommerce_order_totals_meta_box', 'shop_order', 'side', 'default');
	add_meta_box( 'woocommerce-order-notes', __('Order Notes', 'woocommerce'), 'woocommerce_order_notes_meta_box', 'shop_order', 'side', 'default');
	add_meta_box( 'woocommerce-order-downloads', __('Downloadable Product Permissions <small>&ndash; Note: Permissions for order items will automatically be granted when the order status changes to processing/completed.</small>', 'woocommerce'), 'woocommerce_order_downloads_meta_box', 'shop_order', 'normal', 'default');
	add_meta_box( 'woocommerce-order-actions', __('Order Actions', 'woocommerce'), 'woocommerce_order_actions_meta_box', 'shop_order', 'side', 'high');
	
	remove_meta_box( 'commentsdiv', 'shop_order' , 'normal' );
	remove_meta_box( 'woothemes-settings', 'shop_order' , 'normal' );
	remove_meta_box( 'commentstatusdiv', 'shop_order' , 'normal' );
	remove_meta_box( 'slugdiv', 'shop_order' , 'normal' );
	
	// Coupons
	add_meta_box( 'woocommerce-coupon-data', __('Coupon Data', 'woocommerce'), 'woocommerce_coupon_data_meta_box', 'shop_coupon', 'normal', 'high');
	
	remove_meta_box( 'woothemes-settings', 'shop_coupon' , 'normal' );
	remove_meta_box( 'commentstatusdiv', 'shop_coupon' , 'normal' );
	remove_meta_box( 'slugdiv', 'shop_coupon' , 'normal' );
}

/**
 * Title boxes
 */
add_filter('enter_title_here', 'woocommerce_enter_title_here', 1, 2);

function woocommerce_enter_title_here( $text, $post ) {
	if ($post->post_type=='shop_coupon') return __('Coupon code', 'woocommerce');
	if ($post->post_type=='product') return __('Product name', 'woocommerce');
	return $text;
}

/**
 * Save meta boxes
 * 
 * Runs when a post is saved and does an action which the write panel save scripts can hook into.
 */
add_action( 'save_post', 'woocommerce_meta_boxes_save', 1, 2 );

function woocommerce_meta_boxes_save( $post_id, $post ) {
	if ( empty($post_id) || empty($post) || empty($_POST) ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
	if ( is_int( wp_is_post_revision( $post ) ) ) return;
	if ( is_int( wp_is_post_autosave( $post ) ) ) return;
	if ( empty($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' )) return;
	if ( !current_user_can( 'edit_post', $post_id )) return;
	if ( $post->post_type != 'product' && $post->post_type != 'shop_order' && $post->post_type != 'shop_coupon' ) return;
		
	do_action( 'woocommerce_process_'.$post->post_type.'_meta', $post_id, $post );
}

/**
 * Product Short Description
 * 
 * Replaces excerpt with a visual editor
 */
function woocommerce_product_short_description_meta_box( $post ) {
	
	$settings = array(
		'quicktags' 	=> array( 'buttons' => 'em,strong,link' ),
		'textarea_name'	=> 'excerpt',
		'quicktags' 	=> true,
		'tinymce' 		=> true,
		'editor_css'	=> '<style>#wp-excerpt-editor-container .wp-editor-area{height:175px; width:100%;}</style>'
		);
		
	wp_editor( htmlspecialchars_decode( $post->post_excerpt ), 'excerpt', $settings );
}
	
	
/**
 * Display comments status form fields.
 *
 * @param object $post
 */
function woocommerce_product_review_status_meta_box( $post ) {
	?>
	<input name="advanced_view" type="hidden" value="1" />
	<p class="meta-options">
		<label for="comment_status" class="selectit"><input name="comment_status" type="checkbox" id="comment_status" value="open" <?php checked($post->comment_status, 'open'); ?> /> <?php _e( 'Allow reviews.', 'woocommerce' ) ?></label><br />
		<label for="ping_status" class="selectit"><input name="ping_status" type="checkbox" id="ping_status" value="open" <?php checked($post->ping_status, 'open'); ?> /> <?php printf( __( 'Allow <a href="%s" target="_blank">trackbacks and pingbacks</a> on this page.' ), __( 'http://codex.wordpress.org/Introduction_to_Blogging#Managing_Comments' ) ); ?></label>
		<?php do_action('post_comment_status_meta_box-options', $post); ?>
	</p>
	<?php
}	
	
/**
 * Product data
 * 
 * Forces certain product data based on the product's type, e.g. grouped products cannot have a parent.
 */
add_filter('wp_insert_post_data', 'woocommerce_product_data');

function woocommerce_product_data( $data ) {
	global $post;
	if ($data['post_type']=='product' && isset($_POST['product-type'])) {
		$product_type = stripslashes( $_POST['product-type'] );
		switch($product_type) :
			case "grouped" :
			case "variable" :
				$data['post_parent'] = 0;
			break;
		endswitch;
	}
	return $data;
}

/**
 * Order data
 * 
 * Forces the order posts to have a title in a certain format (containing the date)
 */
add_filter('wp_insert_post_data', 'woocommerce_order_data');

function woocommerce_order_data( $data ) {
	global $post;
	if ($data['post_type']=='shop_order' && isset($data['post_date'])) {
		
		$order_title = 'Order';
		if ($data['post_date']) $order_title.= ' &ndash; '.date('F j, Y @ h:i A', strtotime($data['post_date']));
		
		$data['post_title'] = $order_title;
	}
	return $data;
}


/**
 * Save errors
 * 
 * Stores error messages in a variable so they can be displayed on the edit post screen after saving.
 */
add_action( 'admin_notices', 'woocommerce_meta_boxes_save_errors' );

function woocommerce_meta_boxes_save_errors() {
	$woocommerce_errors = maybe_unserialize(get_option('woocommerce_errors'));
    if ($woocommerce_errors && sizeof($woocommerce_errors)>0) :
    	echo '<div id="woocommerce_errors" class="error fade">';
    	foreach ($woocommerce_errors as $error) :
    		echo '<p>'.$error.'</p>';
    	endforeach;
    	echo '</div>';
    	update_option('woocommerce_errors', '');
    endif; 
}

/**
 * Output write panel form elements
 */
function woocommerce_wp_text_input( $field ) {
	global $thepostid, $post, $woocommerce;
	
	if (!$thepostid) $thepostid = $post->ID;
	if (!isset($field['placeholder'])) $field['placeholder'] = '';
	if (!isset($field['class'])) $field['class'] = 'short';
	if (!isset($field['value'])) $field['value'] = get_post_meta($thepostid, $field['id'], true);
	
	echo '<p class="form-field '.$field['id'].'_field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="text" class="'.$field['class'].'" name="'.$field['id'].'" id="'.$field['id'].'" value="'.esc_attr( $field['value'] ).'" placeholder="'.$field['placeholder'].'" /> ';
	
	if ( isset( $field['description'] ) && $field['description'] ) {
		
		if ( isset( $field['desc_tip'] ) ) {
			echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" />';
		} else {
			echo '<span class="description">' . $field['description'] . '</span>';
		}

	}		
	echo '</p>';
}

function woocommerce_wp_hidden_input( $field ) {
	global $thepostid, $post;
	if (!$thepostid) $thepostid = $post->ID;
	if (!isset($field['value'])) $field['value'] = get_post_meta($thepostid, $field['id'], true);
	echo '<input type="hidden" class="'.$field['class'].'" name="'.$field['id'].'" id="'.$field['id'].'" value="'.esc_attr( $field['value'] ).'" /> ';
}

function woocommerce_wp_textarea_input( $field ) {
	global $thepostid, $post, $woocommerce;
	
	if (!$thepostid) $thepostid = $post->ID;
	if (!isset($field['placeholder'])) $field['placeholder'] = '';
	if (!isset($field['class'])) $field['class'] = 'short';
	if (!isset($field['value'])) $field['value'] = get_post_meta($thepostid, $field['id'], true);
	
	echo '<p class="form-field '.$field['id'].'_field"><label for="'.$field['id'].'">'.$field['label'].'</label><textarea class="'.$field['class'].'" name="'.$field['id'].'" id="'.$field['id'].'" placeholder="'.$field['placeholder'].'" rows="2" cols="20">'.esc_textarea( $field['value'] ).'</textarea> ';
	
	if ( isset( $field['description'] ) && $field['description'] ) {
		
		if ( isset( $field['desc_tip'] ) ) {
			echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" />';
		} else {
			echo '<span class="description">' . $field['description'] . '</span>';
		}

	}		
	echo '</p>';
}

function woocommerce_wp_checkbox( $field ) {
	global $thepostid, $post;
	
	if (!$thepostid) $thepostid = $post->ID;
	if (!isset($field['class'])) $field['class'] = 'checkbox';
	if (!isset($field['wrapper_class'])) $field['wrapper_class'] = '';
	if (!isset($field['value'])) $field['value'] = get_post_meta($thepostid, $field['id'], true);
	
	echo '<p class="form-field '.$field['id'].'_field '.$field['wrapper_class'].'"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="checkbox" class="'.$field['class'].'" name="'.$field['id'].'" id="'.$field['id'].'" ';
	
	checked($field['value'], 'yes');
	
	echo ' /> ';
	
	if (isset($field['description']) && $field['description']) echo '<span class="description">' .$field['description'] . '</span>';
		
	echo '</p>';
}

function woocommerce_wp_select( $field ) {
	global $thepostid, $post, $woocommerce;
	
	if (!$thepostid) $thepostid = $post->ID;
	if (!isset($field['class'])) $field['class'] = 'select short';
	if (!isset($field['value'])) $field['value'] = get_post_meta($thepostid, $field['id'], true);
	
	echo '<p class="form-field '.$field['id'].'_field"><label for="'.$field['id'].'">'.$field['label'].'</label><select id="'.$field['id'].'" name="'.$field['id'].'" class="'.$field['class'].'">';
	
	foreach ($field['options'] as $key => $value) :
		
		echo '<option value="'.$key.'" ';
		selected($field['value'], $key);
		echo '>'.$value.'</option>';
		
	endforeach;
	
	echo '</select> ';
	
	if ( isset( $field['description'] ) && $field['description'] ) {
		
		if ( isset( $field['desc_tip'] ) ) {
			echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" />';
		} else {
			echo '<span class="description">' . $field['description'] . '</span>';
		}

	}
		
	echo '</p>';
}
