<?php
/**
 * Class WC_Gateway_Paypal_Request file.
 *
 * @package WooCommerce\Gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates requests to send to PayPal.
 */
class WC_Gateway_Paypal_Request {

	/**
	 * Stores line items to send to PayPal.
	 *
	 * @var array
	 */
	protected $line_items = array();

	/**
	 * Pointer to gateway making the request.
	 *
	 * @var WC_Gateway_Paypal
	 */
	protected $gateway;

	/**
	 * Endpoint for requests from PayPal.
	 *
	 * @var string
	 */
	protected $notify_url;

	/**
	 * Endpoint for requests to PayPal.
	 *
	 * @var string
	 */
	protected $endpoint;


	/**
	 * Constructor.
	 *
	 * @param WC_Gateway_Paypal $gateway Paypal gateway object.
	 */
	public function __construct( $gateway ) {
		$this->gateway    = $gateway;
		$this->notify_url = WC()->api_request_url( 'WC_Gateway_Paypal' );
	}

	/**
	 * Get the PayPal request URL for an order.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  bool     $sandbox Whether to use sandbox mode or not.
	 * @return string
	 */
	public function get_request_url( $order, $sandbox = false ) {
		if ( $sandbox ) {
			$this->endpoint = 'https://www.sandbox.paypal.com/cgi-bin/webscr?test_ipn=1&';
		} else {
			$this->endpoint = 'https://www.paypal.com/cgi-bin/webscr?';
		}
		$paypal_args = http_build_query( $this->get_paypal_args( $order ), '', '&' );

		WC_Gateway_Paypal::log( 'PayPal Request Args for order ' . $order->get_order_number() . ': ' . wc_print_r( $paypal_args, true ) );

		return $this->endpoint . $paypal_args;
	}

	/**
	 * Limit length of an arg.
	 *
	 * @param  string  $string Argument to limit.
	 * @param  integer $limit Limit size in characters.
	 * @return string
	 */
	protected function limit_length( $string, $limit = 127 ) {
		// As the output is to be used in http_build_query which applies URL encoding, the string needs to be
		// cut as if it was URL-encoded, but returned non-encoded (it will be encoded by http_build_query later).
		$url_encoded_str = rawurlencode( $string );

		if ( strlen( $url_encoded_str ) > $limit ) {
			$string = rawurldecode( substr( $url_encoded_str, 0, $limit - 3 ) . '...' );
		}
		return $string;
	}

	/**
	 * Get args for paypal request, except for line item args.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return array
	 */
	protected function get_non_line_item_args( $order ) {
		return array_merge(
			array(
				'cmd'           => '_cart',
				'business'      => $this->gateway->get_option( 'email' ),
				'no_note'       => 1,
				'currency_code' => get_woocommerce_currency(),
				'charset'       => 'utf-8',
				'rm'            => is_ssl() ? 2 : 1,
				'upload'        => 1,
				'return'        => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $this->gateway->get_return_url( $order ) ) ),
				'cancel_return' => esc_url_raw( $order->get_cancel_order_url_raw() ),
				'page_style'    => $this->gateway->get_option( 'page_style' ),
				'image_url'     => esc_url_raw( $this->gateway->get_option( 'image_url' ) ),
				'paymentaction' => $this->gateway->get_option( 'paymentaction' ),
				'bn'            => 'WooThemes_Cart',
				'invoice'       => $this->limit_length( $this->gateway->get_option( 'invoice_prefix' ) . $order->get_order_number(), 127 ),
				'custom'        => wp_json_encode(
					array(
						'order_id'  => $order->get_id(),
						'order_key' => $order->get_order_key(),
					)
				),
				'notify_url'    => $this->limit_length( $this->notify_url, 255 ),
				'first_name'    => $this->limit_length( $order->get_billing_first_name(), 32 ),
				'last_name'     => $this->limit_length( $order->get_billing_last_name(), 64 ),
				'address1'      => $this->limit_length( $order->get_billing_address_1(), 100 ),
				'address2'      => $this->limit_length( $order->get_billing_address_2(), 100 ),
				'city'          => $this->limit_length( $order->get_billing_city(), 40 ),
				'state'         => $this->get_paypal_state( $order->get_billing_country(), $order->get_billing_state() ),
				'zip'           => $this->limit_length( wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ), 32 ),
				'country'       => $this->limit_length( $order->get_billing_country(), 2 ),
				'email'         => $this->limit_length( $order->get_billing_email() ),
			),
			$this->get_phone_number_args( $order ),
			$this->get_shipping_args( $order )
		);
	}

	/**
	 * If the default request with line items is too long, generate a new one with only one line item.
	 *
	 * @param WC_Order $order Order to be sent to Paypal.
	 * @param array    $paypal_args Arguments sent to Paypal in the request.
	 *
	 * @return array
	 */
	protected function fix_request_length( $order, $paypal_args ) {
		$max_paypal_length = 2083;
		$query_candidate   = http_build_query( $paypal_args, '', '&' );
		// If URL is longer than 2,083 chars, ignore line items and send cart to Paypal as a single item.
		// One item's name can only be 127 characters long, so the URL should not be longer than limit.
		// URL character limit via:
		// https://support.microsoft.com/en-us/help/208427/maximum-url-length-is-2-083-characters-in-internet-explorer.
		if ( strlen( $this->endpoint . $query_candidate ) <= $max_paypal_length ) {
			return $paypal_args;
		}

		return apply_filters(
			'woocommerce_paypal_args', array_merge(
				$this->get_non_line_item_args( $order ),
				$this->get_line_item_args( $order, true )
			), $order
		);

	}

	/**
	 * Get PayPal Args for passing to PP.
	 *
	 * @param  WC_Order $order Order object.
	 * @return array
	 */
	protected function get_paypal_args( $order ) {
		WC_Gateway_Paypal::log( 'Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url );

		$paypal_args = apply_filters(
			'woocommerce_paypal_args', array_merge(
				$this->get_non_line_item_args( $order ),
				$this->get_line_item_args( $order )
			), $order
		);

		return $this->fix_request_length( $order, $paypal_args );
	}

	/**
	 * Get phone number args for paypal request.
	 *
	 * @param  WC_Order $order Order object.
	 * @return array
	 */
	protected function get_phone_number_args( $order ) {
		if ( in_array( $order->get_billing_country(), array( 'US', 'CA' ), true ) ) {
			$phone_number = str_replace( array( '(', '-', ' ', ')', '.' ), '', $order->get_billing_phone() );
			$phone_number = ltrim( $phone_number, '+1' );
			$phone_args   = array(
				'night_phone_a' => substr( $phone_number, 0, 3 ),
				'night_phone_b' => substr( $phone_number, 3, 3 ),
				'night_phone_c' => substr( $phone_number, 6, 4 ),
			);
		} else {
			$phone_args = array(
				'night_phone_b' => $order->get_billing_phone(),
			);
		}
		return $phone_args;
	}

	/**
	 * Get shipping args for paypal request.
	 *
	 * @param  WC_Order $order Order object.
	 * @return array
	 */
	protected function get_shipping_args( $order ) {
		$shipping_args = array();

		if ( 'yes' === $this->gateway->get_option( 'send_shipping' ) ) {
			$shipping_args['address_override'] = $this->gateway->get_option( 'address_override' ) === 'yes' ? 1 : 0;
			$shipping_args['no_shipping']      = 0;

			// If we are sending shipping, send shipping address instead of billing.
			$shipping_args['first_name'] = $this->limit_length( $order->get_shipping_first_name(), 32 );
			$shipping_args['last_name']  = $this->limit_length( $order->get_shipping_last_name(), 64 );
			$shipping_args['address1']   = $this->limit_length( $order->get_shipping_address_1(), 100 );
			$shipping_args['address2']   = $this->limit_length( $order->get_shipping_address_2(), 100 );
			$shipping_args['city']       = $this->limit_length( $order->get_shipping_city(), 40 );
			$shipping_args['state']      = $this->get_paypal_state( $order->get_shipping_country(), $order->get_shipping_state() );
			$shipping_args['country']    = $this->limit_length( $order->get_shipping_country(), 2 );
			$shipping_args['zip']        = $this->limit_length( wc_format_postcode( $order->get_shipping_postcode(), $order->get_shipping_country() ), 32 );
		} else {
			$shipping_args['no_shipping'] = 1;
		}

		return $shipping_args;
	}

	/**
	 * Get shipping cost line item args for paypal request.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  bool     $include_shipping_tax Whether to include shipping tax or not.
	 * @return array
	 */
	protected function get_shipping_cost_line_item( $order, $include_shipping_tax ) {
		$line_item_args = array();
		$shipping_total = $order->get_shipping_total();
		if ( $include_shipping_tax ) {
			$shipping_total += $order->get_shipping_tax();
		}

		// Add shipping costs. Paypal ignores anything over 5 digits (999.99 is the max).
		// We also check that shipping is not the **only** cost as PayPal won't allow payment
		// if the items have no cost.
		if ( $order->get_shipping_total() > 0 && $order->get_shipping_total() < 999.99 && $this->number_format( $order->get_shipping_total() + $order->get_shipping_tax(), $order ) !== $this->number_format( $order->get_total(), $order ) ) {
			$line_item_args['shipping_1'] = $this->number_format( $shipping_total, $order );
		} elseif ( $order->get_shipping_total() > 0 ) {
			/* translators: %s: Order shipping method */
			$this->add_line_item( sprintf( __( 'Shipping via %s', 'woocommerce' ), $order->get_shipping_method() ), 1, $this->number_format( $shipping_total, $order ) );
		}

		return $line_item_args;
	}

	/**
	 * Get line item args for paypal request as a single line item.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  bool     $include_shipping_tax Whether to include shipping tax or not.
	 * @return array
	 */
	protected function get_line_item_args_single_item( $order, $include_shipping_tax ) {
		$this->delete_line_items();

		$all_items_name = $this->get_order_item_names( $order );
		$this->add_line_item( $all_items_name ? $all_items_name : __( 'Order', 'woocommerce' ), 1, $this->number_format( $order->get_total() - $this->round( $order->get_shipping_total() + $order->get_shipping_tax(), $order ), $order ), $order->get_order_number() );
		$line_item_args = $this->get_shipping_cost_line_item( $order, $include_shipping_tax );

		return array_merge( $line_item_args, $this->get_line_items() );
	}

	/**
	 * Get line item args for paypal request.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  bool     $force_one_line_item Create only one item for this order.
	 * @return array
	 */
	protected function get_line_item_args( $order, $force_one_line_item = false ) {

		/**
		 * Try passing a line item per product if supported.
		 */
		if ( ( ! wc_tax_enabled() || ! wc_prices_include_tax() ) && $this->prepare_line_items( $order ) ) {
			$line_item_args       = array();
			$include_shipping_tax = false;
			if ( $force_one_line_item ) {
				$line_item_args = $this->get_line_item_args_single_item( $order, $include_shipping_tax );
			} else {
				$line_item_args['tax_cart'] = $this->number_format( $order->get_total_tax(), $order );

				if ( $order->get_total_discount() > 0 ) {
					$line_item_args['discount_amount_cart'] = $this->number_format( $this->round( $order->get_total_discount(), $order ), $order );
				}

				$line_item_args = array_merge( $line_item_args, $this->get_shipping_cost_line_item( $order, $include_shipping_tax ) );
				$line_item_args = array_merge( $line_item_args, $this->get_line_items() );

			}
		} else {
			/**
			 * Send order as a single item.
			 *
			 * For shipping, we longer use shipping_1 because paypal ignores it if *any* shipping rules are within paypal, and paypal ignores anything over 5 digits (999.99 is the max).
			 */
			$include_shipping_tax = true;
			$line_item_args = $this->get_line_item_args_single_item( $order, $include_shipping_tax );

		}

		return $line_item_args;
	}

	/**
	 * Get order item names as a string.
	 *
	 * @param  WC_Order $order Order object.
	 * @return string
	 */
	protected function get_order_item_names( $order ) {
		$item_names = array();

		foreach ( $order->get_items() as $item ) {
			$item_name = $item->get_name();
			$item_meta = strip_tags(
				wc_display_item_meta(
					$item, array(
						'before'    => '',
						'separator' => ', ',
						'after'     => '',
						'echo'      => false,
						'autop'     => false,
					)
				)
			);

			if ( $item_meta ) {
				$item_name .= ' (' . $item_meta . ')';
			}

			$item_names[] = $item_name . ' x ' . $item->get_quantity();
		}

		return apply_filters( 'woocommerce_paypal_get_order_item_names', implode( ', ', $item_names ), $order );
	}

	/**
	 * Get order item names as a string.
	 *
	 * @param  WC_Order      $order Order object.
	 * @param  WC_Order_Item $item Order item object.
	 * @return string
	 */
	protected function get_order_item_name( $order, $item ) {
		$item_name = $item->get_name();
		$item_meta = strip_tags(
			wc_display_item_meta(
				$item, array(
					'before'    => '',
					'separator' => ', ',
					'after'     => '',
					'echo'      => false,
					'autop'     => false,
				)
			)
		);

		if ( $item_meta ) {
			$item_name .= ' (' . $item_meta . ')';
		}

		return apply_filters( 'woocommerce_paypal_get_order_item_name', $item_name, $order, $item );
	}

	/**
	 * Return all line items.
	 */
	protected function get_line_items() {
		return $this->line_items;
	}

	/**
	 * Remove all line items.
	 */
	protected function delete_line_items() {
		$this->line_items = array();
	}

	/**
	 * Get line items to send to paypal.
	 *
	 * @param  WC_Order $order Order object.
	 * @return bool
	 */
	protected function prepare_line_items( $order ) {
		$this->delete_line_items();
		$calculated_total = 0;

		// Products.
		foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
			if ( 'fee' === $item['type'] ) {
				$item_line_total   = $this->number_format( $item['line_total'], $order );
				$line_item         = $this->add_line_item( $item->get_name(), 1, $item_line_total );
				$calculated_total += $item_line_total;
			} else {
				$product           = $item->get_product();
				$sku               = $product ? $product->get_sku() : '';
				$item_line_total   = $this->number_format( $order->get_item_subtotal( $item, false ), $order );
				$line_item         = $this->add_line_item( $this->get_order_item_name( $order, $item ), $item->get_quantity(), $item_line_total, $sku );
				$calculated_total += $item_line_total * $item->get_quantity();
			}

			if ( ! $line_item ) {
				return false;
			}
		}

		// Check for mismatched totals.
		if ( $this->number_format( $calculated_total + $order->get_total_tax() + $this->round( $order->get_shipping_total(), $order ) - $this->round( $order->get_total_discount(), $order ), $order ) !== $this->number_format( $order->get_total(), $order ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add PayPal Line Item.
	 *
	 * @param  string $item_name Item name.
	 * @param  int    $quantity Item quantity.
	 * @param  float  $amount Amount.
	 * @param  string $item_number Item number.
	 * @return bool successfully added or not
	 */
	protected function add_line_item( $item_name, $quantity = 1, $amount = 0.0, $item_number = '' ) {
		$index = ( count( $this->line_items ) / 4 ) + 1;

		if ( $amount < 0 ) {
			return false;
		}

		$item = apply_filters(
			'woocommerce_paypal_line_item', array(
				'item_name'   => html_entity_decode( wc_trim_string( $item_name ? $item_name : __( 'Item', 'woocommerce' ), 127 ), ENT_NOQUOTES, 'UTF-8' ),
				'quantity'    => (int) $quantity,
				'amount'      => wc_float_to_string( (float) $amount ),
				'item_number' => $item_number,
			), $item_name, $quantity, $amount, $item_number
		);

		$this->line_items[ 'item_name_' . $index ]   = $this->limit_length( $item['item_name'], 127 );
		$this->line_items[ 'quantity_' . $index ]    = $item['quantity'];
		$this->line_items[ 'amount_' . $index ]      = $item['amount'];
		$this->line_items[ 'item_number_' . $index ] = $this->limit_length( $item['item_number'], 127 );

		return true;
	}

	/**
	 * Get the state to send to paypal.
	 *
	 * @param  string $cc Country two letter code.
	 * @param  string $state State code.
	 * @return string
	 */
	protected function get_paypal_state( $cc, $state ) {
		if ( 'US' === $cc ) {
			return $state;
		}

		$states = WC()->countries->get_states( $cc );

		if ( isset( $states[ $state ] ) ) {
			return $states[ $state ];
		}

		return $state;
	}

	/**
	 * Check if currency has decimals.
	 *
	 * @param  string $currency Currency to check.
	 * @return bool
	 */
	protected function currency_has_decimals( $currency ) {
		if ( in_array( $currency, array( 'HUF', 'JPY', 'TWD' ), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Round prices.
	 *
	 * @param  double   $price Price to round.
	 * @param  WC_Order $order Order object.
	 * @return double
	 */
	protected function round( $price, $order ) {
		$precision = 2;

		if ( ! $this->currency_has_decimals( $order->get_currency() ) ) {
			$precision = 0;
		}

		return round( $price, $precision );
	}

	/**
	 * Format prices.
	 *
	 * @param  float|int $price Price to format.
	 * @param  WC_Order  $order Order object.
	 * @return string
	 */
	protected function number_format( $price, $order ) {
		$decimals = 2;

		if ( ! $this->currency_has_decimals( $order->get_currency() ) ) {
			$decimals = 0;
		}

		return number_format( $price, $decimals, '.', '' );
	}
}
