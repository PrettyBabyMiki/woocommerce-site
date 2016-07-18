<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Structured data's handler and generator using JSON-LD format.
 *
 * @class 		WC_Data_Structurer
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		Clement Cazaud
 */
class WC_Data_Structurer {
  
  /**
   * @var array Partially formatted structured data
   */
  private $structured_data;

  /**
   * Checks if the passed $json variable is an array and stores it into $this->structured_data...
   *
   * @param array $json Partially formatted JSON-LD
   * @return false If the param $json is not an array
   */
  private function set_structured_data( $json ) {
    if ( ! is_array( $json ) ) {
      return false;
    }
    $this->structured_data[] = $json;
  }
  
  /**
   * Formats and returns the structured data...
   *
   * @return mixed bool|array If structured data is set, returns the fully formatted and encoded JSON-LD, otherwise returns false
   */
  private function get_structured_data() {
    if ( ! $this->structured_data ) {
      return false;
    }
    $product_count = 0;

    foreach ( $this->structured_data as $value ) {
      $type = isset( $value['@type'] ) ? $value['@type'] : false;
        
      if ( 'Product' === $type || 'SoftwareApplication' === $type || 'MusicAlbum' === $type ) {
        $products[] = $value;
        $product_count ++;
      }
      elseif ( 'Review' === $type ) {
        $reviews[] = $value;
      }
    }
    if ( $product_count === 1 ) {
      $structured_data = isset( $reviews ) ? $products[0] + array( 'review' => $reviews ) : $products[0];
    }
    elseif ( $product_count > 1 ) {
      $structured_data = array( '@graph' => $products );
    }
    if ( ! isset( $structured_data ) ) {
      return false;
    }
    $context['@context'] = 'http://schema.org/';
    
    return wp_json_encode( $context + $structured_data );
  }

  /**
   * Contructor
   */
  public function __construct() {
    add_action( 'woocommerce_before_shop_loop_item', array( $this, 'init_product_category_structured_data' ) );
    add_action( 'woocommerce_single_product_summary', array( $this, 'init_product_structured_data' ) );
    add_action( 'woocommerce_review_meta', array( $this, 'init_product_review_structured_data' ) );
    add_action( 'wp_footer', array( $this, 'enqueue_structured_data' ) );
  }

  /**
   * If structured data is set, echoes the encoded structured data into the `wp_footer` action hook.
   */
  public function enqueue_structured_data() {
    if ( $structured_data = $this->get_structured_data() ) {
      echo '<script type="application/ld+json">' . $structured_data . '</script>';
    }
  }

  /**
   * Generates the product category structured data...
   * Hooked into the `woocommerce_before_shop_loop_item` action hook...
   */
  public function init_product_category_structured_data() {
    if ( ! is_product_category() ) {
      return;
    }
    $this->init_product_structured_data();
  }
  
  /**
   * Generates the product structured data...
   * Hooked into the `woocommerce_single_product_summary` action hook...
   * Applies the `woocommerce_product_structured_data` filter hook for clean structured data customization...
   */
  public function init_product_structured_data() {
    global $product;

    if ( $product->is_downloadable() ) {
      switch ( $product->download_type ) {
        case 'application' :
          $type = "SoftwareApplication";
        break;
        case 'music' :
          $type = "MusicAlbum";
        break;
        default :
          $type = "Product";
        break;
      }
    } else {
      $type = "Product";
    }
    $json['@type']             = $type;
    $json['@id']               = get_the_permalink();
    $json['name']              = get_the_title();
    $json['image']             = wp_get_attachment_url( $product->get_image_id() );
    $json['description']       = get_the_excerpt();
    $json['url']               = get_the_permalink();
    $json['sku']               = $product->get_sku();

    if ( $brand = $product->get_attribute( __( 'brand', 'woocommerce' ) ) ) {
      $json['brand']             = array(
        '@type'                  => 'Thing',
        'name'                   => $brand
      );
    }
    if ( $product->get_rating_count() ) {
      $json['aggregateRating'] = array(
        '@type'                => 'AggregateRating',
        'ratingValue'          => $product->get_average_rating(),
        'ratingCount'          => $product->get_rating_count(),
        'reviewCount'          => $product->get_review_count()
      );
    }
    $json['offers']            = array(
      '@type'                  => 'Offer',
      'priceCurrency'          => get_woocommerce_currency(),
      'price'                  => $product->get_price(),
      'availability'           => 'http://schema.org/' . $stock = ( $product->is_in_stock() ? 'InStock' : 'OutOfStock' )
    );
    $this->set_structured_data( apply_filters( 'woocommerce_product_structured_data', $json ) );
  }

  /**
   * Generates the product review structured data...
   * Hooked into the `woocommerce_review_meta` action hook...
   * Applies the `woocommerce_product_review_structured_data` filter hook for clean structured data customization...
   */
  public function init_product_review_structured_data() {
    global $comment;
    
    $rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );

    $json['@type']             = 'Review';
    $json['@id']               = '#li-comment-' . get_comment_ID();
    $json['datePublished']     = get_comment_date( 'c' );
    $json['description']       = get_comment_text();
    $json['reviewRating']      = array(
      '@type'                  => 'rating',
      'ratingValue'            => $rating
    );
    $json['author']            = array(
      '@type'                  => 'Person',
      'name'                   => get_comment_author()
    );
    $this->set_structured_data( apply_filters( 'woocommerce_product_review_structured_data', $json ) );
  }
}
