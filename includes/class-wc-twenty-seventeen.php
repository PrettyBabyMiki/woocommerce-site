<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$template = get_option( 'template' );

if ( 'twentyseventeen' != $template ) {
	return; // Only do any of the things if Twenty Seventeen is the active theme
}

/**
 * Twenty Seventeen suport.
 *
 * @class   WC_Twenty_Seventeen
 * @since   2.7.0
 * @version 2.7.0
 * @package WooCommerce/Classes
 */
class WC_Twenty_Seventeen {

	/**
	 * Constructor.
	 */
	public function __construct() {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

		add_action( 'woocommerce_before_main_content', array( $this, 'output_content_wrapper' ), 10 );
		add_action( 'woocommerce_after_main_content', array( $this, 'output_content_wrapper_end' ), 10 );

		add_filter( 'woocommerce_enqueue_styles', array( $this, 'enqueue_styles' ) );
	}

	public function enqueue_styles( $styles ) {
		unset( $styles['woocommerce-general'] );

		$styles['woocommerce-twenty-seventeen'] = array(
			'src'     => str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/css/twenty-seventeen.css',
			'deps'    => '',
			'version' => WC_VERSION,
			'media'   => 'all',
		);

		return apply_filters( 'woocommerce_twenty_seventeen_styles', $styles );
	}

	/**
	 * Open the Twenty Seventeen wrapper
	 * @return void
	 */
	public function output_content_wrapper() { ?>
		<div class="wrap">
			<div id="primary" class="content-area twentyseventeen">
				<main id="main" class="site-main" role="main">
		<?php
	}

	/**
	 * Close the Twenty Seventeen wrapper
	 * @return void
	 */
	public function output_content_wrapper_end () {?>
				</main>
			</div>
		</div>
		<?php
	}
}

return new WC_Twenty_Seventeen();

