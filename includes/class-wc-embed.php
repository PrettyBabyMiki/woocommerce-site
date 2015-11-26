<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Embed Controller
 *
 * Embed Class which handles any WooCommerce Products that are embedded on this site or another site
 *
 * @class 		WC_Embed
 * @version		2.5.0
 * @package		WooCommerce/Classes/Embed
 * @category	Class
 * @author 		WooThemes
 */
class WC_Embed {

	/** @var WC_Embed The single instance of the class */
	protected static $_instance = null;

	/**
	 * Main WC_Embed Instance.
	 *
	 * Ensures only one instance of WC_Embed is loaded or can be loaded.
	 *
	 * @since 2.5
	 * @static
	 * @return WC_Embed Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.5
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.5
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '2.1' );
	}

	/**
	 * Init email classes.
	 */
	public static function init() {

        // filter all of the content that's going to be embedded
        add_filter( 'the_title', array( 'WC_Embed', 'the_title' ), 10 );
        add_filter( 'the_excerpt_embed', array( 'WC_Embed', 'the_excerpt' ), 10 );

        // make sure no comments display. Doesn't make sense for products
        add_filter( 'get_comments_number', array( 'WC_Embed', 'get_comments_number' ), 10);
        add_filter( 'comments_open', array( 'WC_Embed', 'comments_open' ), 10 );

	}

    /**
     * Create the title for embedded products - we want to add the price to it
     *
     * @return string
     */
    public static function the_title( $title ) {
        // make sure we're only affecting embedded products
        if ( WC_Embed::is_embedded_product() ) {

            // get product
            $_pf = new WC_Product_Factory();
            $_product = $_pf->get_product( get_the_ID() );

            // add the price
            $title = $title . '<span class="price" style="float: right;">' . $_product->get_price_html() . '</span>';
        }
        return $title;
    }

    /**
     * Check if this is an embedded product - to make sure we don't mess up regular posts
     *
     * @return bool
     */
    public static function is_embedded_product() {
        if ( function_exists( 'is_embed' ) && is_embed() && is_product() ) {
            return true;
        }
        return false;
    }

    /**
     * Create the excerpt for embedded products - we want to add the buy button to it
     *
     * @return string
     */
    public static function the_excerpt( $excerpt ) {
        //  make sure we're only affecting embedded products
        if ( WC_Embed::is_embedded_product() ) {

            // get product
            $_pf = new WC_Product_Factory();
            $_product = $_pf->get_product( get_the_ID() );

            // add the exerpt
            $excerpt = wpautop( $excerpt );

            // add the button
            $excerpt .= WC_Embed::product_button();
        }
        return $excerpt;
    }

    /**
     * Create the button to go to the product page for embedded products.
     *
     * @return string
     */
    public static function product_button( ) {
        $button = '<a href="%s" class="wp-embed-more">%s &rarr;</a>';
        return sprintf( $button, get_the_permalink(), __( 'View The Product', 'woocommerce' ) );
    }

    /**
     * Returns number of comments for embedded products. Since we don't want the comment icon to show up we're going to return 0.
     *
     * @return string
     */
    public static function get_comments_number( $comments ) {
        //  make sure we're only affecting embedded products
        if ( WC_Embed::is_embedded_product() ) {
            return 0;
        }
        return $comments;
    }

    /**
     * Returns whether or not comments are open Since we don't want the comment icon to show up we're going to return false.
     *
     * @return bool
     */
    public static function comments_open( $comments_open ) {
        //  make sure we're only affecting embedded products
        if ( WC_Embed::is_embedded_product() ) {
            return false;
        }
        return $comments_open;
    }
}

WC_Embed::init();
