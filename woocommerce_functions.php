<?php
/**
 * WooCommerce Core Functions
 * 
 * Functions available on both the front-end admin admin
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */

/**
 * WooCommerce conditionals
 **/
function is_woocommerce() {
	// Returns true if on a page which uses WooCommerce templates (cart and checkout are standard pages with shortcodes and thus are not included)
	if (is_shop() || is_product_category() || is_product_tag() || is_product()) return true; else return false;
}
if (!function_exists('is_shop')) {
	function is_shop() {
		if (is_post_type_archive( 'product' ) || is_page(get_option('woocommerce_shop_page_id'))) return true; else return false;
	}
}
if (!function_exists('is_product_category')) {
	function is_product_category() {
		return is_tax( 'product_cat' );
	}
}
if (!function_exists('is_product_tag')) {
	function is_product_tag() {
		return is_tax( 'product_tag' );
	}
}
if (!function_exists('is_product')) {
	function is_product() {
		return is_singular( array('product') );
	}
}
if (!function_exists('is_cart')) {
	function is_cart() {
		return is_page(get_option('woocommerce_cart_page_id'));
	}
}
if (!function_exists('is_checkout')) {
	function is_checkout() {
		if (is_page(get_option('woocommerce_checkout_page_id')) || is_page(get_option('woocommerce_pay_page_id'))) return true; else return false;
	}
}
if (!function_exists('is_account_page')) {
	function is_account_page() {
		if ( is_page(get_option('woocommerce_myaccount_page_id')) || is_page(get_option('woocommerce_edit_address_page_id')) || is_page(get_option('woocommerce_view_order_page_id')) || is_page(get_option('woocommerce_change_password_page_id')) ) return true; else return false;
		return is_page(get_option('woocommerce_myaccount_page_id'));
	}
}
if (!function_exists('is_ajax')) {
	function is_ajax() {
		if ( defined('DOING_AJAX') ) return true;
		if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) return true; else return false;
	}
}

/**
 * Force SSL (if enabled)
 **/
if (!is_admin() && get_option('woocommerce_force_ssl_checkout')=='yes') add_action( 'wp', 'woocommerce_force_ssl');

function woocommerce_force_ssl() {
	if (is_checkout() && !is_ssl()) :
		wp_safe_redirect( str_replace('http:', 'https:', get_permalink(get_option('woocommerce_checkout_page_id'))), 301 );
		exit;
	// Break out of SSL if we leave the checkout (anywhere but thanks page)
	elseif (get_option('woocommerce_unforce_ssl_checkout')=='yes' && is_ssl() && $_SERVER['REQUEST_URI'] && !is_checkout() && !is_page(get_option('woocommerce_thanks_page_id')) && !is_ajax()) :
		wp_safe_redirect( str_replace('https:', 'http:', home_url($_SERVER['REQUEST_URI']) ) );
		exit;
	endif;
}

/**
 * Force SSL for images
 **/
add_filter('post_thumbnail_html', 'woocommerce_force_ssl_images');
add_filter('widget_text', 'woocommerce_force_ssl_images');
add_filter('wp_get_attachment_url', 'woocommerce_force_ssl_images');
add_filter('wp_get_attachment_image_attributes', 'woocommerce_force_ssl_images');
add_filter('wp_get_attachment_url', 'woocommerce_force_ssl_images');

function woocommerce_force_ssl_images( $content ) {
	if (is_ssl()) :
		if (is_array($content)) :
			$content = array_map('woocommerce_force_ssl_images', $content);
		else :
			$content = str_replace('http:', 'https:', $content);
		endif;
	endif;
	return $content;
}

/**
 * Force SSL for stylsheet/script urls etc. Modified code by Chris Black (http://cjbonline.org)
 **/
add_filter('option_siteurl', 'woocommerce_force_ssl_urls');
add_filter('option_home', 'woocommerce_force_ssl_urls');
add_filter('option_url', 'woocommerce_force_ssl_urls');
add_filter('option_wpurl', 'woocommerce_force_ssl_urls');
add_filter('option_stylesheet_url', 'woocommerce_force_ssl_urls');
add_filter('option_template_url', 'woocommerce_force_ssl_urls');
add_filter('script_loader_src', 'woocommerce_force_ssl_urls');
add_filter('style_loader_src', 'woocommerce_force_ssl_urls');

function woocommerce_force_ssl_urls( $url ) {
	if (is_ssl()) :
		$url = str_replace('http:', 'https:', $url);
	endif;
	return $url;
}

/**
 * Currency
 **/
function get_woocommerce_currency_symbol() {
	$currency = get_option('woocommerce_currency');
	$currency_symbol = '';
	switch ($currency) :
		case 'AUD' :
		case 'BRL' :
		case 'CAD' :
		case 'MXN' :
		case 'NZD' :
		case 'HKD' :
		case 'SGD' :
		case 'USD' : $currency_symbol = '&#36;'; break;
		case 'EUR' : $currency_symbol = '&euro;'; break;
		case 'JPY' : $currency_symbol = '&yen;'; break;
		case 'TRY' : $currency_symbol = 'TL'; break;
		case 'NOK' : $currency_symbol = 'kr'; break;
		case 'ZAR' : $currency_symbol = 'R'; break;
		case 'CZK' : $currency_symbol = '&#75;&#269;'; break;

		case 'DKK' :
		case 'HUF' :
		case 'ILS' :
		case 'MYR' :
		case 'PHP' :
		case 'PLN' :
		case 'SEK' :
		case 'CHF' :
		case 'TWD' :
		case 'THB' : $currency_symbol = $currency; break;
		
		case 'GBP' : 
		default    : $currency_symbol = '&pound;'; break;
	endswitch;
	return apply_filters('woocommerce_currency_symbol', $currency_symbol, $currency);
}

/**
 * Price Formatting
 **/
function woocommerce_price( $price, $args = array() ) {
	global $woocommerce;
	
	extract(shortcode_atts(array(
		'ex_tax_label' 	=> '0'
	), $args));
	
	$return = '';
	$num_decimals = (int) get_option('woocommerce_price_num_decimals');
	$currency_pos = get_option('woocommerce_currency_pos');
	$currency_symbol = get_woocommerce_currency_symbol();
	$price = number_format( (double) $price, $num_decimals, get_option('woocommerce_price_decimal_sep'), get_option('woocommerce_price_thousand_sep') );
	
	if (get_option('woocommerce_price_trim_zeros')=='yes') :
		$trimmed_price = rtrim(rtrim($price, '0'), get_option('woocommerce_price_decimal_sep'));
		$after_decimal = explode(get_option('woocommerce_price_decimal_sep'), $trimmed_price);
		if (!isset($after_decimal[1]) || (isset($after_decimal[1]) && (strlen($after_decimal[1]) == 0 && strlen($after_decimal[1]) == $num_decimals))) $price = $trimmed_price;
	endif;
	
	switch ($currency_pos) :
		case 'left' :
			$return = $currency_symbol . $price;
		break;
		case 'right' :
			$return = $price . $currency_symbol;
		break;
		case 'left_space' :
			$return = $currency_symbol . ' ' . $price;
		break;
		case 'right_space' :
			$return = $price . ' ' . $currency_symbol;
		break;
	endswitch;

	if ($ex_tax_label && get_option('woocommerce_calc_taxes')=='yes') $return .= ' <small>'.$woocommerce->countries->ex_tax_or_vat().'</small>';
	
	return $return;
}	
	
/**
 * Clean variables
 **/
function woocommerce_clean( $var ) {
	return trim(strip_tags(stripslashes($var)));
}

/**
 * Rating field for comments
 **/
function woocommerce_add_comment_rating($comment_id) {
	if ( isset($_POST['rating']) ) :
		global $post;
		if (!$_POST['rating'] || $_POST['rating'] > 5 || $_POST['rating'] < 0) $_POST['rating'] = 5; 
		add_comment_meta( $comment_id, 'rating', esc_attr($_POST['rating']), true );
		delete_transient( esc_attr($post->ID) . '_woocommerce_average_rating' );
	endif;
}
add_action( 'comment_post', 'woocommerce_add_comment_rating', 1 );

function woocommerce_check_comment_rating($comment_data) {
	
	global $woocommerce;
	
	// If posting a comment (not trackback etc) and not logged in
	if ( isset($_POST['rating']) && !$woocommerce->verify_nonce('comment_rating') )
		wp_die( __('You have taken too long. Please go back and refresh the page.', 'woothemes') );
		
	elseif ( isset($_POST['rating']) && empty($_POST['rating']) && $comment_data['comment_type']== '' ) {
		wp_die( __('Please rate the product.',"woothemes") );
		exit;
	}
	return $comment_data;
}
add_filter('preprocess_comment', 'woocommerce_check_comment_rating', 0);	

/**
 * Review comments template
 **/
function woocommerce_comments($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment; global $post; ?>
	
	<li itemprop="reviews" itemscope itemtype="http://schema.org/Review" <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
		<div id="comment-<?php comment_ID(); ?>" class="comment_container">

  			<?php echo get_avatar( $comment, $size='60' ); ?>
			
			<div class="comment-text">
			
				<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="star-rating" title="<?php echo esc_attr( get_comment_meta( $comment->comment_ID, 'rating', true ) ); ?>">
					<span style="width:<?php echo get_comment_meta( $comment->comment_ID, 'rating', true )*16; ?>px"><span itemprop="ratingValue"><?php echo get_comment_meta( $comment->comment_ID, 'rating', true ); ?></span> <?php _e('out of 5', 'woothemes'); ?></span>
				</div>
				
				<?php if ($comment->comment_approved == '0') : ?>
					<p class="meta"><em><?php _e('Your comment is awaiting approval', 'woothemes'); ?></em></p>
				<?php else : ?>
					<p class="meta">
						<?php _e('Rating by', 'woothemes'); ?> <strong itemprop="author"><?php comment_author(); ?></strong> <?php _e('on', 'woothemes'); ?> <time itemprop="datePublished" time datetime="<?php echo get_comment_date('c'); ?>"><?php echo get_comment_date('M jS Y'); ?></time>:
					</p>
				<?php endif; ?>
				
  				<div itemprop="description" class="description"><?php comment_text(); ?></div>
  				<div class="clear"></div>
  			</div>
			<div class="clear"></div>			
		</div>
	<?php
}


/**
 * Exclude order comments from queries
 *
 * This code should exclude shop_order comments from queries. Some queries (like the recent comments widget on the dashboard) are hardcoded
 * and are not filtered, however, the code current_user_can( 'read_post', $comment->comment_post_ID ) should keep them safe since only admin and
 * shop managers can view orders anyway.
 *
 * The frontend view order pages get around this filter by using remove_filter('comments_clauses', 'woocommerce_exclude_order_comments');
 **/
function woocommerce_exclude_order_comments( $clauses ) {
	global $wpdb, $typenow;
	
	if (is_admin() && $typenow=='shop_order') return $clauses; // Don't hide when viewing orders in admin
	
	$clauses['join'] = "LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID";
	
	if ($clauses['where']) $clauses['where'] .= ' AND ';
	
	$clauses['where'] .= "
		$wpdb->posts.post_type NOT IN ('shop_order')
	";
	
	return $clauses;	

}
add_filter( 'comments_clauses', 'woocommerce_exclude_order_comments', 10, 1);


/**
 * Exclude order comments from comments RSS
 **/
function woocommerce_exclude_order_comments_from_feed( $where ) {
	global $wpdb;
	
    if ($where) $where .= ' AND ';
	
	$where .= "$wpdb->posts.post_type NOT IN ('shop_order')";
    
    return $where;
}
add_action( 'comment_feed_where', 'woocommerce_exclude_order_comments_from_feed' );


/**
 * readfile_chunked
 *
 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
 *
 * @access   public
 * @param    string    file
 * @param    boolean    return bytes of file
 * @return   void
 */
if ( ! function_exists('readfile_chunked')) {
    function readfile_chunked($file, $retbytes=TRUE) {
    
		$chunksize = 1 * (1024 * 1024);
		$buffer = '';
		$cnt = 0;
		
		$handle = fopen($file, 'r');
		if ($handle === FALSE) return FALSE;
				
		while (!feof($handle)) :
		   $buffer = fread($handle, $chunksize);
		   echo $buffer;
		   ob_flush();
		   flush();
		
		   if ($retbytes) $cnt += strlen($buffer);
		endwhile;
		
		$status = fclose($handle);
		
		if ($retbytes AND $status) return $cnt;
		
		return $status;
    }
}

/**
 * Cache
 **/
function woocommerce_prevent_sidebar_cache() {
	echo '<!--mfunc get_sidebar() --><!--/mfunc-->';
}
add_action('get_sidebar', 'woocommerce_prevent_sidebar_cache');

/**
 * Hex darker/lighter/contrast functions for colours
 **/
if (!function_exists('woocommerce_hex_darker')) {
	function woocommerce_hex_darker( $color, $factor = 30 ) {
		$color = str_replace('#', '', $color);
		
		$base['R'] = hexdec($color{0}.$color{1});
		$base['G'] = hexdec($color{2}.$color{3});
		$base['B'] = hexdec($color{4}.$color{5});
		
		$color = '#';
		
		foreach ($base as $k => $v) :
	        $amount = $v / 100;
	        $amount = round($amount * $factor);
	        $new_decimal = $v - $amount;
	
	        $new_hex_component = dechex($new_decimal);
	        if(strlen($new_hex_component) < 2) :
	        	$new_hex_component = "0".$new_hex_component;
	        endif;
	        $color .= $new_hex_component;
		endforeach;
		        
		return $color;        
	}
}
if (!function_exists('woocommerce_hex_lighter')) {
	function woocommerce_hex_lighter( $color, $factor = 30 ) {
		$color = str_replace('#', '', $color);
		
		$base['R'] = hexdec($color{0}.$color{1});
		$base['G'] = hexdec($color{2}.$color{3});
		$base['B'] = hexdec($color{4}.$color{5});
		
		$color = '#';
	     
	    foreach ($base as $k => $v) :
	        $amount = 255 - $v; 
	        $amount = $amount / 100; 
	        $amount = round($amount * $factor); 
	        $new_decimal = $v + $amount; 
	     
	        $new_hex_component = dechex($new_decimal); 
	        if(strlen($new_hex_component) < 2) :
	        	$new_hex_component = "0".$new_hex_component;
	        endif;
	        $color .= $new_hex_component; 
	   	endforeach;
	         
	   	return $color;          
	}
}
if (!function_exists('woocommerce_light_or_dark')) {
	function woocommerce_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
	    return (hexdec($color) > 0xffffff/2) ? $dark : $light;
	}
}

/**
 * Variation Formatting
 *
 * Gets a formatted version of variation data or item meta
 **/
function woocommerce_get_formatted_variation( $variation = '', $flat = false ) {
	global $woocommerce;

	if (is_array($variation)) :

		if (!$flat) $return = '<dl class="variation">'; else $return = '';

		$variation_list = array();

		foreach ($variation as $name => $value) :

			if (!$value) continue;

			// If this is a term slug, get the term's nice name
            if (taxonomy_exists(esc_attr(str_replace('attribute_', '', $name)))) :
            	$term = get_term_by('slug', $value, esc_attr(str_replace('attribute_', '', $name)));
            	if (!is_wp_error($term) && $term->name) :
            		$value = $term->name;
            	endif;
            else :
            	$value = ucfirst($value);
            endif;

			if ($flat) :
				$variation_list[] = $woocommerce->attribute_label(str_replace('attribute_', '', $name)).': '.$value;
			else :
				$variation_list[] = '<dt>'.$woocommerce->attribute_label(str_replace('attribute_', '', $name)).':</dt><dd>'.$value.'</dd>';
			endif;

		endforeach;

		if ($flat) :
			$return .= implode(', ', $variation_list);
		else :
			$return .= implode('', $variation_list);
		endif;

		if (!$flat) $return .= '</dl>';

		return $return;

	endif;
}

/**
 * Order Status completed - GIVE DOWNLOADABLE PRODUCT ACCESS TO CUSTOMER
 **/
add_action('woocommerce_order_status_completed', 'woocommerce_downloadable_product_permissions');

function woocommerce_downloadable_product_permissions( $order_id ) {
	global $wpdb;
	
	$order = &new woocommerce_order( $order_id );
	
	if (sizeof($order->items)>0) foreach ($order->items as $item) :
	
		if ($item['id']>0) :
			$_product = $order->get_product_from_item( $item );
			
			if ( $_product->exists && $_product->is_downloadable() ) :
			
				$download_id = ($item['variation_id']>0) ? $item['variation_id'] : $item['id'];
				
				$user_email = $order->billing_email;
				
				if ($order->user_id>0) :
					$user_info = get_userdata($order->user_id);
					if ($user_info->user_email) :
						$user_email = $user_info->user_email;
					endif;
				else :
					$order->user_id = 0;
				endif;
				
				$limit = trim(get_post_meta($download_id, 'download_limit', true));
				
				if (!empty($limit)) :
					$limit = (int) $limit;
				else :
					$limit = '';
				endif;
				
				// Downloadable product - give access to the customer
				$wpdb->insert( $wpdb->prefix . 'woocommerce_downloadable_product_permissions', array( 
					'product_id' => $download_id, 
					'user_id' => $order->user_id,
					'user_email' => $user_email,
					'order_id' => $order->id,
					'order_key' => $order->order_key,
					'downloads_remaining' => $limit
				), array( 
					'%s', 
					'%s', 
					'%s', 
					'%s', 
					'%s',
					'%s'
				) );	
				
			endif;
			
		endif;
	
	endforeach;
}

/**
 * Filter to allow product_cat in the permalinks for products.
 *
 * @since 1.1
 *
 * @param string $permalink The existing permalink URL.
 */
function woocommerce_product_cat_filter_post_link( $permalink, $post, $leavename, $sample ) {
    // Abort if post is not a product
    if ($post->post_type!=='product') return $permalink;
    
    // Abort early if the placeholder rewrite tag isn't in the generated URL
    if ( false === strpos( $permalink, '%product_cat%' ) ) return $permalink;

    // Get the custom taxonomy terms in use by this post
    $terms = get_the_terms( $post->ID, 'product_cat' );

    if ( empty( $terms ) ) :
    	// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
        $permalink = str_replace( '%product_cat%', __('product', 'woothemes'), $permalink );
    else :
    	// Replace the placeholder rewrite tag with the first term's slug
        $first_term = array_shift( $terms );
        $permalink = str_replace( '%product_cat%', $first_term->slug, $permalink );
    endif;

    return $permalink;
}
add_filter( 'post_type_link', 'woocommerce_product_cat_filter_post_link', 10, 4 );


/**
 * Add term ordering to get_terms
 * 
 * It enables the support a 'menu_order' parameter to get_terms for the product_cat taxonomy.
 * By default it is 'ASC'. It accepts 'DESC' too
 * 
 * To disable it, set it ot false (or 0)
 * 
 */
add_filter( 'terms_clauses', 'woocommerce_terms_clauses', 10, 3);

function woocommerce_terms_clauses($clauses, $taxonomies, $args ) {
	global $wpdb, $woocommerce;

	// No sorting when menu_order is false
	if ( isset($args['menu_order']) && $args['menu_order'] == false ) return $clauses;
	
	// No sorting when orderby is non default
	if ( isset($args['orderby']) && $args['orderby'] != 'name' ) return $clauses;
	
	// No sorting in admin when sorting by a column
	if ( isset($_GET['orderby']) ) return $clauses;

	// wordpress should give us the taxonomies asked when calling the get_terms function. Only apply to categories and pa_ attributes
	$found = false;
	foreach ((array) $taxonomies as $taxonomy) :
		if ($taxonomy=='product_cat' || strstr($taxonomy, 'pa_')) :
			$found = true;
			break;
		endif;
	endforeach;
	if (!$found) return $clauses;
	
	// Meta name
	if (strstr($taxonomies[0], 'pa_')) :
		$meta_name =  'order_' . esc_attr($taxonomies[0]);
	else :
		$meta_name = 'order';
	endif;

	// query fields
	if( strpos('COUNT(*)', $clauses['fields']) === false ) $clauses['fields']  .= ', tm.* ';

	//query join
	$clauses['join'] .= " LEFT JOIN {$wpdb->woocommerce_termmeta} AS tm ON (t.term_id = tm.woocommerce_term_id AND tm.meta_key = '". $meta_name ."') ";
	
	// default to ASC
	if( ! isset($args['menu_order']) || ! in_array( strtoupper($args['menu_order']), array('ASC', 'DESC')) ) $args['menu_order'] = 'ASC';

	$order = "ORDER BY CAST(tm.meta_value AS SIGNED) " . $args['menu_order'];
	
	if ( $clauses['orderby'] ):
		$clauses['orderby'] = str_replace('ORDER BY', $order . ',', $clauses['orderby'] );
	else:
		$clauses['orderby'] = $order;
	endif;
	
	return $clauses;
}

/**
 * WooCommerce Term Meta API
 * 
 * API for working with term meta data. Adapted from 'Term meta API' by Nikolay Karev
 * 
 */
add_action( 'init', 'woocommerce_taxonomy_metadata_wpdbfix', 0 );
add_action( 'switch_blog', 'woocommerce_taxonomy_metadata_wpdbfix', 0 );

function woocommerce_taxonomy_metadata_wpdbfix() {
	global $wpdb;

	$variable_name = 'woocommerce_termmeta';
	$wpdb->$variable_name = $wpdb->prefix . $variable_name;	
	$wpdb->tables[] = $variable_name;
} 

function update_woocommerce_term_meta($term_id, $meta_key, $meta_value, $prev_value = ''){
	return update_metadata('woocommerce_term', $term_id, $meta_key, $meta_value, $prev_value);
}

function add_woocommerce_term_meta($term_id, $meta_key, $meta_value, $unique = false){
	return add_metadata('woocommerce_term', $term_id, $meta_key, $meta_value, $unique);
}

function delete_woocommerce_term_meta($term_id, $meta_key, $meta_value = '', $delete_all = false){
	return delete_metadata('woocommerce_term', $term_id, $meta_key, $meta_value, $delete_all);
}

function get_woocommerce_term_meta($term_id, $key, $single = true){
	return get_metadata('woocommerce_term', $term_id, $key, $single);
}

/**
 * WooCommerce Dropdown categories
 * 
 * Stuck with this until a fix for http://core.trac.wordpress.org/ticket/13258
 * We use a custom walker, just like WordPress does it
 */
function woocommerce_product_dropdown_categories( $show_counts = 1, $hierarchal = 1 ) {
	global $wp_query;
	
	$r = array();
	$r['pad_counts'] = 1;
	$r['hierarchal'] = $hierarchal;
	$r['hide_empty'] = 1;
	$r['show_count'] = 1;
	$r['selected'] = (isset($wp_query->query['product_cat'])) ? $wp_query->query['product_cat'] : '';
	
	$terms = get_terms( 'product_cat', $r );
	if (!$terms) return;
	
	$output  = "<select name='product_cat' id='dropdown_product_cat'>";
	$output .= '<option value="">'.__('Show all categories', 'woothemes').'</option>';
	$output .= woocommerce_walk_category_dropdown_tree( $terms, 0, $r );
	$output .="</select>";
	
	echo $output;
}

/**
 * Walk the Product Categories.
 */
function woocommerce_walk_category_dropdown_tree() {
	$args = func_get_args();
	// the user's options are the third parameter
	if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') )
		$walker = new Woocommerce_Walker_CategoryDropdown;
	else
		$walker = $args[2]['walker'];

	return call_user_func_array(array( &$walker, 'walk' ), $args );
}

/**
 * Create HTML dropdown list of Product Categories.
 */
class Woocommerce_Walker_CategoryDropdown extends Walker {

	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id', 'slug' => 'slug' );

	function start_el(&$output, $category, $depth, $args) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		$cat_name = apply_filters('list_product_cats', $category->name, $category);
		$output .= "\t<option class=\"level-$depth\" value=\"".$category->slug."\"";
		if ( $category->slug == $args['selected'] )
			$output .= ' selected="selected"';
		$output .= '>';
		$output .= $pad.$cat_name;
		if ( $args['show_count'] )
			$output .= '&nbsp;('. $category->count .')';
		$output .= "</option>\n";
	}
}