<?php
/**
 * WooCommerce Shipping Label banner.
 * NOTE: DO NOT edit this file in WooCommerce core, this is generated from woocommerce-admin.
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features;

use \Automattic\WooCommerce\Admin\Loader;

/**
 * Shows print shipping label banner on edit order page.
 */
class ShippingLabelBanner {

	/**
	 * Singleton for the display rules class
	 *
	 * @var ShippingLabelBannerDisplayRules
	 */
	private $shipping_label_banner_display_rules;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_admin_plugins_whitelist', array( $this, 'get_shipping_banner_allowed_plugins' ), 10, 2 );

		if ( ! is_admin() ) {
			return;
		}
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 6, 2 );
	}

	/**
	 * Gets an array of plugins that can be installed & activated via shipping label prompt.
	 *
	 * @param array $plugins Array of plugin slugs to be allowed.
	 *
	 * @return array
	 */
	public static function get_shipping_banner_allowed_plugins( $plugins ) {
		$shipping_banner_plugins = array(
			'woocommerce-services' => 'woocommerce-services/woocommerce-services.php',
		);
		return array_merge( $plugins, $shipping_banner_plugins );
	}

	/**
	 * Check if WooCommerce Shipping makes sense for this merchant.
	 *
	 * @return bool
	 */
	private function should_show_meta_box() {
		if ( ! $this->shipping_label_banner_display_rules ) {
			$jetpack_version   = null;
			$jetpack_connected = null;
			$wcs_version       = null;
			$wcs_tos_accepted  = null;

			if ( class_exists( '\Jetpack_Data' ) ) {
				$user_token = \Jetpack_Data::get_access_token( JETPACK_MASTER_USER );

				$jetpack_connected = isset( $user_token->external_user_id );
				$jetpack_version   = JETPACK__VERSION;
			}

			if ( class_exists( '\WC_Connect_Loader' ) ) {
				$wcs_version = \WC_Connect_Loader::get_wcs_version();
			}
			if ( class_exists( '\WC_Connect_Options' ) ) {
				$wcs_tos_accepted = \WC_Connect_Options::get_option( 'tos_accepted' );
			}

			$incompatible_plugins = class_exists( '\WC_Shipping_Fedex_Init' ) ||
				class_exists( '\WC_Shipping_UPS_Init' ) ||
				class_exists( '\WC_Integration_ShippingEasy' ) ||
				class_exists( '\WC_ShipStation_Integration' );

			$this->shipping_label_banner_display_rules =
				new ShippingLabelBannerDisplayRules(
					$jetpack_version,
					$jetpack_connected,
					$wcs_version,
					$wcs_tos_accepted,
					$incompatible_plugins
				);
		}

		return $this->shipping_label_banner_display_rules->should_display_banner();
	}

	/**
	 * Add metabox to order page.
	 *
	 * @param string   $post_type current post type.
	 * @param \WP_Post $post Current post object.
	 */
	public function add_meta_boxes( $post_type, $post ) {
		$order = wc_get_order( $post );
		if ( $this->should_show_meta_box() ) {
			add_meta_box(
				'woocommerce-admin-print-label',
				__( 'Shipping Label', 'woocommerce-admin' ),
				array( $this, 'meta_box' ),
				null,
				'normal',
				'high',
				array(
					'context'               => 'shipping_label',
					'order_id'              => $post->ID,
					'shippable_items_count' => $this->count_shippable_items( $order ),
				)
			);
			add_action( 'admin_enqueue_scripts', array( $this, 'add_print_shipping_label_script' ) );
		}
	}

	/**
	 * Count shippable items
	 *
	 * @param \WC_Order $order Current order.
	 * @return int
	 */
	private function count_shippable_items( \WC_Order $order ) {
		$count = 0;
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof \WC_Order_Item_Product ) {
				$product = $item->get_product();
				if ( $product && $product->needs_shipping() ) {
					$count += $item->get_quantity();
				}
			}
		}
		return $count;
	}
	/**
	 * Adds JS to order page to render shipping banner.
	 *
	 * @param string $hook current page hook.
	 */
	public function add_print_shipping_label_script( $hook ) {
		$rtl = is_rtl() ? '-rtl' : '';
		wp_enqueue_style(
			'print-shipping-label-banner-style',
			Loader::get_url( "print-shipping-label-banner/style{$rtl}.css" ),
			array( 'wp-components' ),
			Loader::get_file_version( 'print-shipping-label-banner/style.css' )
		);

		wp_enqueue_script(
			'print-shipping-label-banner',
			Loader::get_url( 'wp-admin-scripts/print-shipping-label-banner.js' ),
			array( 'wp-i18n', 'wp-data', 'wp-element', 'moment', 'wp-api-fetch', WC_ADMIN_APP ),
			Loader::get_file_version( 'wp-admin-scripts/print-shipping-label-banner.js' ),
			true
		);

		$payload = array(
			'nonce'                 => wp_create_nonce( 'wp_rest' ),
			'baseURL'               => get_rest_url(),
			'wcs_server_connection' => true,
		);

		wp_localize_script( 'print-shipping-label-banner', 'wcConnectData', $payload );
	}

	/**
	 * Render placeholder metabox.
	 *
	 * @param \WP_Post $post current post.
	 * @param array    $args empty args.
	 */
	public function meta_box( $post, $args ) {

		?>
		<div id="wc-admin-shipping-banner-root" class="woocommerce <?php echo esc_attr( 'wc-admin-shipping-banner' ); ?>" data-args="<?php echo esc_attr( wp_json_encode( $args['args'] ) ); ?>">
		</div>
		<?php
	}
}
