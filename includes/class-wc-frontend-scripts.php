<?php
/**
 * WC_Frontend_Scripts
 */
class WC_Frontend_Scripts {

	/**
	 * Constructor
	 */
	public function __construct () {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_print_scripts', array( $this, 'check_jquery' ), 25 );
	}

	/**
	 * Register/queue frontend scripts.
	 *
	 * @access public
	 * @return void
	 */
	public function load_scripts() {
		global $post, $wp, $woocommerce;

		$suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$lightbox_en          = get_option( 'woocommerce_enable_lightbox' ) == 'yes' ? true : false;
		$ajax_cart_en         = get_option( 'woocommerce_enable_ajax_add_to_cart' ) == 'yes' ? true : false;
		$assets_path          = str_replace( array( 'http:', 'https:' ), '', $woocommerce->plugin_url() ) . '/assets/';
		$frontend_script_path = $assets_path . 'js/frontend/';

		// Register any scripts for later use, or used as dependencies
		wp_register_script( 'chosen', $assets_path . 'js/chosen/chosen.jquery' . $suffix . '.js', array( 'jquery' ), '0.9.14', true );
		wp_register_script( 'jquery-blockui', $assets_path . 'js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.60', true );
		wp_register_script( 'jquery-placeholder', $assets_path . 'js/jquery-placeholder/jquery.placeholder' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );

		wp_register_script( 'wc-add-to-cart-variation', $frontend_script_path . 'add-to-cart-variation' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
		wp_register_script( 'wc-single-product', $frontend_script_path . 'single-product' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
		wp_register_script( 'wc-country-select', $frontend_script_path . 'country-select' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
		wp_register_script( 'jquery-cookie', $assets_path . 'js/jquery-cookie/jquery.cookie' . $suffix . '.js', array( 'jquery' ), '1.3.1', true );

		// Queue frontend scripts conditionally
		if ( $ajax_cart_en )
			wp_enqueue_script( 'wc-add-to-cart', $frontend_script_path . 'add-to-cart' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );

		if ( is_cart() )
			wp_enqueue_script( 'wc-cart', $frontend_script_path . 'cart' . $suffix . '.js', array( 'jquery', 'wc-country-select' ), $woocommerce->version, true );

		if ( is_checkout() ) {

			if ( get_option( 'woocommerce_enable_chosen' ) == 'yes' ) {
				wp_enqueue_script( 'wc-chosen', $frontend_script_path . 'chosen-frontend' . $suffix . '.js', array( 'chosen' ), $woocommerce->version, true );
				wp_enqueue_style( 'woocommerce_chosen_styles', $assets_path . 'css/chosen.css' );
			}

			wp_enqueue_script( 'wc-checkout', $frontend_script_path . 'checkout' . $suffix . '.js', array( 'jquery', 'woocommerce', 'wc-country-select' ), $woocommerce->version, true );
		}

		if ( $lightbox_en && ( is_product() || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) ) {
			wp_enqueue_script( 'prettyPhoto', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto' . $suffix . '.js', array( 'jquery' ), '3.1.5', true );
			wp_enqueue_script( 'prettyPhoto-init', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto.init' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
			wp_enqueue_style( 'woocommerce_prettyPhoto_css', $assets_path . 'css/prettyPhoto.css' );
		}

		if ( is_product() )
			wp_enqueue_script( 'wc-single-product' );

		// Global frontend scripts
		wp_enqueue_script( 'woocommerce', $frontend_script_path . 'woocommerce' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-placeholder' ), $woocommerce->version, true );
		wp_enqueue_script( 'wc-cart-fragments', $frontend_script_path . 'cart-fragments' . $suffix . '.js', array( 'jquery', 'jquery-cookie' ), $woocommerce->version, true );

		// Variables for JS scripts
		wp_localize_script( 'woocommerce', 'woocommerce_params', apply_filters( 'woocommerce_params', array(
			'ajax_url'                         => $woocommerce->ajax_url(),
			'ajax_loader_url'                  => apply_filters( 'woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif' ),
		) ) );

		wp_localize_script( 'wc-single-product', 'wc_single_product_params', apply_filters( 'wc_single_product_params', array(
			'i18n_required_rating_text'        => esc_attr__( 'Please select a rating', 'woocommerce' ),
			'review_rating_required'           => get_option( 'woocommerce_review_rating_required' ),
		) ) );

		wp_localize_script( 'wc-checkout', 'wc_checkout_params', apply_filters( 'wc_checkout_params', array(
			'ajax_url'                         => $woocommerce->ajax_url(),
			'ajax_loader_url'                  => apply_filters( 'woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif' ),
			'i18n_required_text'               => esc_attr__( 'required', 'woocommerce' ),
			'update_order_review_nonce'        => wp_create_nonce( "update-order-review" ),
			'apply_coupon_nonce'               => wp_create_nonce( "apply-coupon" ),
			'option_guest_checkout'            => get_option( 'woocommerce_enable_guest_checkout' ),
			'checkout_url'                     => add_query_arg( 'action', 'woocommerce-checkout', $woocommerce->ajax_url() ),
			'is_checkout'                      => is_page( woocommerce_get_page_id( 'checkout' ) ) && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) ? 1 : 0,
			'locale'                           => json_encode( $woocommerce->countries->get_country_locale() )
		) ) );

		wp_localize_script( 'wc-cart', 'wc_cart_params', apply_filters( 'wc_cart_params', array(
			'ajax_url'                         => $woocommerce->ajax_url(),
			'ajax_loader_url'                  => apply_filters( 'woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif' ),
			'update_shipping_method_nonce'     => wp_create_nonce( "update-shipping-method" ),
		) ) );

		wp_localize_script( 'wc-cart-fragments', 'wc_cart_fragments_params', apply_filters( 'wc_cart_fragments_params', array(
			'ajax_url'                         => $woocommerce->ajax_url()
		) ) );

		wp_localize_script( 'wc-add-to-cart', 'wc_add_to_cart_params', apply_filters( 'wc_add_to_cart_params', array(
			'ajax_url'                         => $woocommerce->ajax_url(),
			'ajax_loader_url'                  => apply_filters( 'woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif' ),
			'i18n_view_cart'                   => esc_attr__( 'View Cart &rarr;', 'woocommerce' ),
			'cart_url'                         => get_permalink( woocommerce_get_page_id( 'cart' ) ),
			'cart_redirect_after_add'          => get_option( 'woocommerce_cart_redirect_after_add' )
		) ) );

		wp_localize_script( 'wc-add-to-cart-variation', 'wc_add_to_cart_variation_params', apply_filters( 'wc_add_to_cart_variation_params', array(
			'i18n_no_matching_variations_text' => esc_attr__( 'Sorry, no products matched your selection. Please choose a different combination.', 'woocommerce' ),
		) ) );

		wp_localize_script( 'wc-country-select', 'wc_country_select_params', apply_filters( 'wc_country_select_params', array(
			'countries'                        => json_encode( $woocommerce->countries->get_allowed_country_states() ),
			'i18n_select_state_text'           => esc_attr__( 'Select an option&hellip;', 'woocommerce' ),
		) ) );

		// CSS Styles
		wp_register_style( 'woocommerce_frontend_styles_layout', $assets_path . 'css/woocommerce-layout.css' );
		wp_register_style( 'woocommerce_frontend_styles_smallscreen', $assets_path . 'css/woocommerce-smallscreen.css', 'woocommerce_frontend_styles_layout', '', 'only screen and (max-width: ' . apply_filters( 'woocommerce_smallscreen_breakpoint', $breakpoint = '768px' ) . ' )' );
		wp_register_style( 'woocommerce_frontend_styles', $assets_path . 'css/woocommerce.css' );

		if ( defined( 'WOOCOMMERCE_USE_CSS' ) )
			_deprecated_function( 'WOOCOMMERCE_USE_CSS', '2.1', 'Styles should be removed using wp_deregister_style rather than the constant.' );

		$load_styles = defined( 'WOOCOMMERCE_USE_CSS' ) ? WOOCOMMERCE_USE_CSS : true;

		if ( $load_styles ) {
			wp_enqueue_style( 'woocommerce_frontend_styles_layout' );
			wp_enqueue_style( 'woocommerce_frontend_styles_smallscreen' );
			wp_enqueue_style( 'woocommerce_frontend_styles' );
		}
	}

	/**
	 * WC requires jQuery 1.7 since it uses functions like .on() for events.
	 * If, by the time wp_print_scrips is called, jQuery is outdated (i.e not
	 * using the version in core) we need to deregister it and register the
	 * core version of the file.
	 *
	 * @access public
	 * @return void
	 */
	public function check_jquery() {
		global $wp_scripts;

		// Enforce minimum version of jQuery
		if ( ! empty( $wp_scripts->registered['jquery']->ver ) && ! empty( $wp_scripts->registered['jquery']->src ) && $wp_scripts->registered['jquery']->ver < '1.7' ) {
			wp_deregister_script( 'jquery' );
			wp_register_script( 'jquery', '/wp-includes/js/jquery/jquery.js', array(), '1.7' );
			wp_enqueue_script( 'jquery' );
		}
	}
}

new WC_Frontend_Scripts();