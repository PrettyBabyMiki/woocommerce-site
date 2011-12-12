<?php
/**
 * WooCommerce Template Functions
 *
 * Functions used in the template files to output content - in most cases hooked in via the template actions. All functions are pluggable.
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */

/** Global ****************************************************************/

if (!function_exists('woocommerce_output_content_wrapper')) {
	function woocommerce_output_content_wrapper() {
		woocommerce_get_template('shop/wrapper.php', false);
	}
}
if (!function_exists('woocommerce_output_content_wrapper_end')) {
	function woocommerce_output_content_wrapper_end() {
		woocommerce_get_template('shop/wrapper-end.php', false);
	}
}

/**
 * Sidebar
 **/
if (!function_exists('woocommerce_get_sidebar')) {
	function woocommerce_get_sidebar() {
		woocommerce_get_template('shop/sidebar.php', false);
	}
}

/**
 * Prevent Cache
 **/
if (!function_exists('woocommerce_prevent_sidebar_cache')) {
	function woocommerce_prevent_sidebar_cache() {
		echo '<!--mfunc get_sidebar() --><!--/mfunc-->';
	}
}

/** Loop ******************************************************************/

/**
 * Products Loop
 **/
if (!function_exists('woocommerce_template_loop_add_to_cart')) {
	function woocommerce_template_loop_add_to_cart() {
		woocommerce_get_template('loop/add-to-cart.php', false);
	}
}
if (!function_exists('woocommerce_template_loop_product_thumbnail')) {
	function woocommerce_template_loop_product_thumbnail() {
		echo woocommerce_get_product_thumbnail();
	}
}
if (!function_exists('woocommerce_template_loop_price')) {
	function woocommerce_template_loop_price() {
		woocommerce_get_template('loop/price.php', false);
	}
}
if (!function_exists('woocommerce_show_product_loop_sale_flash')) {
	function woocommerce_show_product_loop_sale_flash() {
		woocommerce_get_template('loop/sale_flash.php', false);
	}
}

/**
 * WooCommerce Product Thumbnail
 **/
if (!function_exists('woocommerce_get_product_thumbnail')) {
	function woocommerce_get_product_thumbnail( $size = 'shop_catalog', $placeholder_width = 0, $placeholder_height = 0 ) {
		global $post, $woocommerce;

		if (!$placeholder_width) $placeholder_width = $woocommerce->get_image_size('shop_catalog_image_width');
		if (!$placeholder_height) $placeholder_height = $woocommerce->get_image_size('shop_catalog_image_height');

		if ( has_post_thumbnail() ) return get_the_post_thumbnail($post->ID, $size); else return '<img src="'.$woocommerce->plugin_url(). '/assets/images/placeholder.png" alt="Placeholder" width="'.$placeholder_width.'" height="'.$placeholder_height.'" />';

	}
}

/**
 * Check product visibility in loop
 **/
if (!function_exists('woocommerce_check_product_visibility')) {
	function woocommerce_check_product_visibility() {
		global $post, $product;
		if (!$product->is_visible( true ) && $post->post_parent > 0) : wp_safe_redirect(get_permalink($post->post_parent)); exit; endif;
		if (!$product->is_visible( true )) : wp_safe_redirect(home_url()); exit; endif;
	}
}

/**
 * Pagination
 **/
if (!function_exists('woocommerce_pagination')) {
	function woocommerce_pagination() {
		woocommerce_get_template('loop/pagination.php', false);
	}
}

/**
 * Sorting
 **/
if (!function_exists('woocommerce_catalog_ordering')) {
	function woocommerce_catalog_ordering() {
		if (!isset($_SESSION['orderby'])) $_SESSION['orderby'] = apply_filters('woocommerce_default_catalog_orderby', 'title');
		woocommerce_get_template('loop/sorting.php', false);
	}
}

/** Single Product ********************************************************/

/**
 * Before Single Products Summary Div
 **/
if (!function_exists('woocommerce_show_product_images')) {
	function woocommerce_show_product_images() {
		woocommerce_get_template('single-product/product-image.php', false);
	}
}
if (!function_exists('woocommerce_show_product_thumbnails')) {
	function woocommerce_show_product_thumbnails() {
		woocommerce_get_template('single-product/product-thumbnails.php', false);
	}
}
if (!function_exists('woocommerce_output_product_data_tabs')) {
	function woocommerce_output_product_data_tabs() {
		woocommerce_get_template('single-product/tabs.php', false);
	}
}
if (!function_exists('woocommerce_template_single_price')) {
	function woocommerce_template_single_price() {
		woocommerce_get_template('single-product/price.php', false);
	}
}
if (!function_exists('woocommerce_template_single_excerpt')) {
	function woocommerce_template_single_excerpt() {
		woocommerce_get_template('single-product/short-description.php', false);
	}
}
if (!function_exists('woocommerce_template_single_meta')) {
	function woocommerce_template_single_meta() {
		woocommerce_get_template('single-product/meta.php', false);
	}
}
if (!function_exists('woocommerce_template_single_sharing')) {
	function woocommerce_template_single_sharing() {
		woocommerce_get_template('single-product/share.php', false);
	}
}
if (!function_exists('woocommerce_show_product_sale_flash')) {
	function woocommerce_show_product_sale_flash() {
		woocommerce_get_template('single-product/sale_flash.php', false);
	}
}

/**
 * Product Add to cart buttons
 **/
if (!function_exists('woocommerce_template_single_add_to_cart')) {
	function woocommerce_template_single_add_to_cart() {
		global $product;
		do_action( 'woocommerce_' . $product->product_type . '_add_to_cart' );
	}
}
if (!function_exists('woocommerce_simple_add_to_cart')) {
	function woocommerce_simple_add_to_cart() {
		woocommerce_get_template('single-product/add-to-cart/simple.php', false);
	}
}
if (!function_exists('woocommerce_grouped_add_to_cart')) {
	function woocommerce_grouped_add_to_cart() {
		woocommerce_get_template('single-product/add-to-cart/grouped.php', false);
	}
}
if (!function_exists('woocommerce_variable_add_to_cart')) {
	function woocommerce_variable_add_to_cart() {
		global $woocommerce, $available_variations, $attributes, $selected_attributes, $product, $post;

		$attributes = $product->get_available_attribute_variations();
		$default_attributes = (array) maybe_unserialize(get_post_meta( $post->ID, '_default_attributes', true ));
		$selected_attributes = apply_filters( 'woocommerce_product_default_attributes', $default_attributes );
		
		// Put available variations into an array and put in a Javascript variable (JSON encoded)
		$available_variations = array();
		
		foreach($product->get_children() as $child_id) {
		
		    $variation = $product->get_child( $child_id );
		
		    if($variation instanceof woocommerce_product_variation) {
		
		    	if (get_post_status( $variation->get_variation_id() ) != 'publish') continue; // Disabled
		
		        $variation_attributes = $variation->get_variation_attributes();
		        $availability = $variation->get_availability();
		        $availability_html = (!empty($availability['availability'])) ? apply_filters( 'woocommerce_stock_html', '<p class="stock '.$availability['class'].'">'. $availability['availability'].'</p>', $availability['availability'] ) : '';
		
		        if (has_post_thumbnail($variation->get_variation_id())) {
		            $attachment_id = get_post_thumbnail_id( $variation->get_variation_id() );
		            $large_thumbnail_size = apply_filters('single_product_large_thumbnail_size', 'shop_single');
		            $image = current(wp_get_attachment_image_src( $attachment_id, $large_thumbnail_size ));
		            $image_link = current(wp_get_attachment_image_src( $attachment_id, 'full' ));
		        } else {
		            $image = '';
		            $image_link = '';
		        }
		
		        $available_variations[] = array(
		            'variation_id' => $variation->get_variation_id(),
		            'attributes' => $variation_attributes,
		            'image_src' => $image,
		            'image_link' => $image_link,
		            'price_html' => '<span class="price">'.$variation->get_price_html().'</span>',
		            'availability_html' => $availability_html,
		        );
		    }
		}
		woocommerce_get_template('single-product/add-to-cart/variable.php', false);
	}
}
if (!function_exists('woocommerce_external_add_to_cart')) {
	function woocommerce_external_add_to_cart() {
		woocommerce_get_template('single-product/add-to-cart/external.php', false);
	}
}

/**
 * Quantity inputs
 **/
if (!function_exists('woocommerce_quantity_input')) {
	function woocommerce_quantity_input( $args = array() ) {
		global $input_name, $input_value;
		
		$defaults = array(
			'input_name' 	=> 'quantity',
			'input_value' 	=> '1'
		);

		$args = wp_parse_args( $args, $defaults );
					
		extract( $args );
		
		woocommerce_get_template('single-product/add-to-cart/quantity.php', false);
	}
}

/**
 * Product page tabs
 **/
if (!function_exists('woocommerce_product_description_tab')) {
	function woocommerce_product_description_tab() {
		woocommerce_get_template('single-product/tabs/description-tab.php', false);
	}
}
if (!function_exists('woocommerce_product_attributes_tab')) {
	function woocommerce_product_attributes_tab() {
		woocommerce_get_template('single-product/tabs/attributes-tab.php', false);
	}
}
if (!function_exists('woocommerce_product_reviews_tab')) {
	function woocommerce_product_reviews_tab() {
		woocommerce_get_template('single-product/tabs/reviews-tab.php', false);
	}
}

/**
 * Product page tab panels
 **/
if (!function_exists('woocommerce_product_description_panel')) {
	function woocommerce_product_description_panel() {
		woocommerce_get_template('single-product/tabs/description.php', false);
	}
}
if (!function_exists('woocommerce_product_attributes_panel')) {
	function woocommerce_product_attributes_panel() {
		woocommerce_get_template('single-product/tabs/attributes.php', false);
	}
}
if (!function_exists('woocommerce_product_reviews_panel')) {
	function woocommerce_product_reviews_panel() {
		woocommerce_get_template('single-product/tabs/reviews.php', false);
	}
}



/**
 * WooCommerce Related Products
 **/
if (!function_exists('woocommerce_output_related_products')) {
	function woocommerce_output_related_products() {
		woocommerce_related_products( 2, 2 );
	}
}

if (!function_exists('woocommerce_related_products')) {
	function woocommerce_related_products( $pp = 4, $pc = 4, $ob = 'rand' ) {
		global $product, $woocommerce_loop, $posts_per_page, $orderby;
		
		$woocommerce_loop['columns'] = $pc;
		$posts_per_page = $pp;
		$orderby = $ob;

		woocommerce_get_template('single-product/related.php', false);
	}
}

/** Cart ******************************************************************/

/**
 * WooCommerce Shipping Calculator
 **/
if (!function_exists('woocommerce_shipping_calculator')) {
	function woocommerce_shipping_calculator() {
		woocommerce_get_template('cart/shipping_calculator.php', false);
	}
}

/**
 * WooCommerce Cart totals
 **/
if (!function_exists('woocommerce_cart_totals')) {
	function woocommerce_cart_totals() {
		woocommerce_get_template('cart/totals.php', false);
	}
}

/** Login *****************************************************************/

/**
 * WooCommerce Login Form
 **/
if (!function_exists('woocommerce_login_form')) {
	function woocommerce_login_form( $message = '' ) {
		global $woocommerce;

		if (is_user_logged_in()) return;

		?>
		<form method="post" class="login">
			<?php if ($message) echo wpautop(wptexturize($message)); ?>
			<p class="form-row form-row-first">
				<label for="username"><?php _e('Username', 'woothemes'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="username" id="username" />
			</p>
			<p class="form-row form-row-last">
				<label for="password"><?php _e('Password', 'woothemes'); ?> <span class="required">*</span></label>
				<input class="input-text" type="password" name="password" id="password" />
			</p>
			<div class="clear"></div>

			<p class="form-row">
				<?php $woocommerce->nonce_field('login', 'login') ?>
				<input type="submit" class="button" name="login" value="<?php _e('Login', 'woothemes'); ?>" />
				<a class="lost_password" href="<?php echo esc_url( wp_lostpassword_url( home_url() ) ); ?>"><?php _e('Lost Password?', 'woothemes'); ?></a>
			</p>
		</form>
		<?php
	}
}

/**
 * WooCommerce Checkout Login Form
 **/
if (!function_exists('woocommerce_checkout_login_form')) {
	function woocommerce_checkout_login_form() {

		if (is_user_logged_in()) return;

		if (get_option('woocommerce_enable_signup_and_login_from_checkout')=="no") return;

		$info_message = apply_filters('woocommerce_checkout_login_message', __('Already registered?', 'woothemes'));

		?><p class="info"><?php echo $info_message; ?> <a href="#" class="showlogin"><?php _e('Click here to login', 'woothemes'); ?></a></p><?php

		woocommerce_login_form( __('If you have shopped with us before, please enter your username and password in the boxes below. If you are a new customer please proceed to the Billing &amp; Shipping section.', 'woothemes') );
	}
}

/**
 * WooCommerce Breadcrumb
 **/
if (!function_exists('woocommerce_breadcrumb')) {
	function woocommerce_breadcrumb( $args = array() ) {
	 	global $delimiter, $wrap_before, $wrap_after, $before, $after, $home;
		
		$defaults = array(
			'delimiter' 	=> ' &rsaquo; ',
			'wrap_before' 	=> '<div id="breadcrumb">',
			'wrap_after'	=> '</div>',
			'before' 		=> '',
			'after' 		=> '',
			'home' 			=> null
		);

		$args = wp_parse_args( $args, $defaults );
					
		extract( $args );

		woocommerce_get_template('shop/breadcrumb.php', false);
	}
}

/**
 * Display Up Sells
 **/
if (!function_exists('woocommerce_upsell_display')) {
	function woocommerce_upsell_display() {
		global $product;
		$upsells = $product->get_upsells();
		if (sizeof($upsells)>0) :
			echo '<div class="upsells products"><h2>'.__('You may also like&hellip;', 'woothemes').'</h2>';
			$args = array(
				'post_type'	=> 'product',
				'ignore_sticky_posts'	=> 1,
				'posts_per_page' => 4,
				'orderby' => 'rand',
				'post__in' => $upsells
			);
			query_posts($args);
			woocommerce_get_template_part( 'loop', 'shop' );
			echo '</div>';
		endif;
		wp_reset_query();
	}
}

/**
 * Display Cross Sells
 **/
if (!function_exists('woocommerce_cross_sell_display')) {
	function woocommerce_cross_sell_display() {
		global $woocommerce_loop, $woocommerce;
		$woocommerce_loop['columns'] = 2;
		$crosssells = $woocommerce->cart->get_cross_sells();
	
		if (sizeof($crosssells)>0) :
			echo '<div class="cross-sells"><h2>'.__('You may be interested in&hellip;', 'woothemes').'</h2>';
			$args = array(
				'post_type'	=> 'product',
				'ignore_sticky_posts'	=> 1,
				'posts_per_page' => 2,
				'orderby' => 'rand',
				'post__in' => $crosssells
			);
			query_posts($args);
			woocommerce_get_template_part( 'loop', 'shop' );
			echo '</div>';
		endif;
		wp_reset_query();
	}
}

/**
 * Order review table for checkout
 **/
if (!function_exists('woocommerce_order_review')) {
	function woocommerce_order_review() {
		woocommerce_get_template('checkout/review_order.php', false);
	}
}

/**
 * Demo Banner
 *
 * Adds a demo store banner to the site if enabled
 **/
if (!function_exists('woocommerce_demo_store')) {
	function woocommerce_demo_store() {
		if (get_option('woocommerce_demo_store')=='no') return;
		
		echo apply_filters('woocommerce_demo_store', '<p class="demo_store">'.__('This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woothemes').'</p>' );
	}
}

/**
 * display product sub categories as thumbnails
 **/
if (!function_exists('woocommerce_product_subcategories')) {
	function woocommerce_product_subcategories() {
		global $woocommerce, $woocommerce_loop, $wp_query, $wp_the_query, $_chosen_attributes, $product_categories, $product_category_found, $product_category_parent;
	
		if ($wp_query !== $wp_the_query) return; // Detect main query
	
		if (sizeof($_chosen_attributes)>0 || (isset($_GET['max_price']) && isset($_GET['min_price']))) return; // Don't show when filtering
	
		if (is_search()) return;
		if (!is_product_category() && !is_shop()) return;
		if (is_product_category() && get_option('woocommerce_show_subcategories')=='no') return;
		if (is_shop() && get_option('woocommerce_shop_show_subcategories')=='no') return;
		if (is_paged()) return;
	
		$product_cat_slug 	= get_query_var('product_cat');
	
		if ($product_cat_slug) :
			$product_cat 		= get_term_by('slug', $product_cat_slug, 'product_cat');
			$product_category_parent = $product_cat->term_id;
		else :
			$product_category_parent = 0;
		endif;
	
		// NOTE: using child_of instead of parent - this is not ideal but due to a WP bug (http://core.trac.wordpress.org/ticket/15626) pad_counts won't work
		$args = array(
		    'child_of'                  => $product_category_parent,
		    'menu_order'                => 'ASC',
		    'hide_empty'               	=> 1,
		    'hierarchical'             	=> 1,
		    'taxonomy'                  => 'product_cat',
		    'pad_counts'				=> 1
		    );
		$product_categories = get_categories( $args );
	
		if ($product_categories) :
	
			woocommerce_get_template('loop-product-cats.php', false);
			
			// If we are hiding products disable the loop and pagination
			if ($product_category_found==true && get_option('woocommerce_hide_products_when_showing_subcategories')=='yes') :
				$woocommerce_loop['show_products'] = false;
				$wp_query->max_num_pages = 0;
			endif;
	
		endif;
	}
}

/**
 * Show subcategory thumbnail
 **/
if (!function_exists('woocommerce_subcategory_thumbnail')) {
	function woocommerce_subcategory_thumbnail( $category ) {
		global $woocommerce;
	
		$small_thumbnail_size 	= apply_filters('single_product_small_thumbnail_size', 'shop_catalog');
		$image_width 			= $woocommerce->get_image_size('shop_catalog_image_width');
		$image_height 			= $woocommerce->get_image_size('shop_catalog_image_height');
	
		$thumbnail_id 	= get_woocommerce_term_meta( $category->term_id, 'thumbnail_id', true );
	
		if ($thumbnail_id) :
			$image = wp_get_attachment_image_src( $thumbnail_id, $small_thumbnail_size );
			$image = $image[0];
		else :
			$image = $woocommerce->plugin_url().'/assets/images/placeholder.png';
		endif;
	
		echo '<img src="'.$image.'" alt="'.$category->slug.'" width="'.$image_width.'" height="'.$image_height.'" />';
	}
}

/**
 * Displays order details in a table
 **/
if (!function_exists('woocommerce_order_details_table')) {
	function woocommerce_order_details_table( $id ) {
		global $order_id; 
		
		if (!$id) return;
	
		$order_id = $id;
		
		woocommerce_get_template('order/order-details-table.php', false);
	}	
}

/**
 * Review comments template
 **/
if (!function_exists('woocommerce_comments')) {
	function woocommerce_comments($comment, $args, $depth) {
		$GLOBALS['comment'] = $comment;
		woocommerce_get_template('single-product/review.php', false);
	}
}