<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Payment Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class       WC_Payment_Gateway
 * @extends     WC_Settings_API
 * @version     2.1.0
 * @package     WooCommerce/Abstracts
 * @category    Abstract Class
 * @author      WooThemes
 */
abstract class WC_Payment_Gateway extends WC_Settings_API {

	/**
	 * Set if the place order button should be renamed on selection.
	 * @var string
	 */
	public $order_button_text;

	/**
	 * yes or no based on whether the method is enabled.
	 * @var string
	 */
	public $enabled = 'yes';

	/**
	 * Payment method title for the frontend.
	 * @var string
	 */
	public $title;

	/**
	 * Payment method description for the frontend.
	 * @var string
	 */
	public $description;

	/**
	 * Chosen payment method id.
	 * @var bool
	 */
	public $chosen;

	/**
	 * Gateway title.
	 * @var string
	 */
	public $method_title = '';

	/**
	 * Gateway description.
	 * @var string
	 */
	public $method_description = '';

	/**
	 * True if the gateway shows fields on the checkout.
	 * @var bool
	 */
	public $has_fields;

	/**
	 * Countries this gateway is allowed for.
	 * @var array
	 */
	public $countries;

	/**
	 * Available for all counties or specific.
	 * @var string
	 */
	public $availability;

	/**
	 * Icon for the gateway.
	 * @var string
	 */
	public $icon;

	/**
	 * Supported features such as 'default_credit_card_form', 'refunds'.
	 * @var array
	 */
	public $supports = array( 'products' );

	/**
	 * Maximum transaction amount, zero does not define a maximum.
	 * @var int
	 */
	public $max_amount = 0;

	/**
	 * Optional URL to view a transaction.
	 * @var string
	 */
	public $view_transaction_url = '';

	/**
	 * Optional label to show for "new payment method" in the payment
	 * method/token selection radio selection.
	 * @var string
	 */
	public $new_method_label = '';

	/**
	 * Contains a users saved tokens for this gateway.
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * Returns a users saved tokens for this gateway.
	 * @since 2.6.0
	 * @return array
	 */
	public function get_tokens() {
		if ( sizeof( $this->tokens ) > 0 ) {
			return $this->tokens;
		}

		if ( is_user_logged_in() && $this->supports( 'tokenization' ) ) {
			$this->tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), $this->id );
		}

		return $this->tokens;
	}

	/**
	 * Return the title for admin screens.
	 * @return string
	 */
	public function get_method_title() {
		return apply_filters( 'woocommerce_gateway_method_title', $this->method_title, $this );
	}

	/**
	 * Return the description for admin screens.
	 * @return string
	 */
	public function get_method_description() {
		return apply_filters( 'woocommerce_gateway_method_description', $this->method_description, $this );
	}

	/**
	 * Output the gateway settings screen.
	 */
	public function admin_options() {
		echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		echo wp_kses_post( wpautop( $this->get_method_description() ) );
		parent::admin_options();
	}

	/**
	 * Init settings for gateways.
	 */
	public function init_settings() {
		parent::init_settings();
		$this->enabled  = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
	}

	/**
	 * Get the return url (thank you page).
	 *
	 * @param WC_Order $order
	 * @return string
	 */
	public function get_return_url( $order = null ) {
		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = wc_get_endpoint_url( 'order-received', '', wc_get_page_permalink( 'checkout' ) );
		}

		if ( is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' ) {
			$return_url = str_replace( 'http:', 'https:', $return_url );
		}

		return apply_filters( 'woocommerce_get_return_url', $return_url, $order );
	}

	/**
	 * Get a link to the transaction on the 3rd party gateway size (if applicable).
	 *
	 * @param  WC_Order $order the order object.
	 * @return string transaction URL, or empty string.
	 */
	public function get_transaction_url( $order ) {

		$return_url = '';
		$transaction_id = $order->get_transaction_id();

		if ( ! empty( $this->view_transaction_url ) && ! empty( $transaction_id ) ) {
			$return_url = sprintf( $this->view_transaction_url, $transaction_id );
		}

		return apply_filters( 'woocommerce_get_transaction_url', $return_url, $order, $this );
	}

	/**
	 * Get the order total in checkout and pay_for_order.
	 *
	 * @return float
	 */
	protected function get_order_total() {

		$total = 0;
		$order_id = absint( get_query_var( 'order-pay' ) );

		// Gets order total from "pay for order" page.
		if ( 0 < $order_id ) {
			$order = wc_get_order( $order_id );
			$total = (float) $order->get_total();

		// Gets order total from cart/checkout.
		} elseif ( 0 < WC()->cart->total ) {
			$total = (float) WC()->cart->total;
		}

		return $total;
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = ( 'yes' === $this->enabled );

		if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Check if the gateway has fields on the checkout.
	 *
	 * @return bool
	 */
	public function has_fields() {
		return $this->has_fields ? true : false;
	}

	/**
	 * Return the gateway's title.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_gateway_title', $this->title, $this->id );
	}

	/**
	 * Return the gateway's description.
	 *
	 * @return string
	 */
	public function get_description() {
		return apply_filters( 'woocommerce_gateway_description', $this->description, $this->id );
	}

	/**
	 * Return the gateway's icon.
	 *
	 * @return string
	 */
	public function get_icon() {

		$icon = $this->icon ? '<img src="' . WC_HTTPS::force_https_url( $this->icon ) . '" alt="' . esc_attr( $this->get_title() ) . '" />' : '';

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Set as current gateway.
	 *
	 * Set this as the current gateway.
	 */
	public function set_current() {
		$this->chosen = true;
	}

	/**
	 * Process Payment.
	 *
	 * Process the payment. Override this in your gateway. When implemented, this should.
	 * return the success and redirect in an array. e.g:
	 *
	 *        return array(
	 *            'result'   => 'success',
	 *            'redirect' => $this->get_return_url( $order )
	 *        );
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		return array();
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @return bool|WP_Error True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return false;
	}

	/**
	 * Validate frontend fields.
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @return bool
	 */
	public function validate_fields() { return true; }

	/**
	 * If There are no payment fields show the description if set.
	 * Override this in your gateway if you have some.
	 */
	public function payment_fields() {
		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		if ( $this->supports( 'default_credit_card_form' ) ) {
			$this->credit_card_form(); // Deprecated, will be removed in a future version.
		}
	}

	/**
	 * Check if a gateway supports a given feature.
	 *
	 * Gateways should override this to declare support (or lack of support) for a feature.
	 * For backward compatibility, gateways support 'products' by default, but nothing else.
	 *
	 * @param string $feature string The name of a feature to test support for.
	 * @return bool True if the gateway supports the feature, false otherwise.
	 * @since 1.5.7
	 */
	public function supports( $feature ) {
		return apply_filters( 'woocommerce_payment_gateway_supports', in_array( $feature, $this->supports ) ? true : false, $feature, $this );
	}

	/**
	 * Enqueues our tokenization script to handle some of the new form options.
	 * @since 2.6.0
	 */
	public function tokenization_script() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script(
			'woocommerce-tokenization-form',
			plugins_url(  '/assets/js/frontend/tokenization-form' . $suffix . '.js', WC_PLUGIN_FILE ),
			array( 'jquery' ),
			WC()->version
		);
		wp_localize_script( 'woocommerce-tokenization-form', 'woocommerceTokenizationParams', array(
			'gatewayID'    => $this->id,
			'userLoggedIn' => (bool) is_user_logged_in(),
		) );
	}

	/**
	 * Grab and display our saved payment methods.
	 * @since 2.6.0
	 */
	public function saved_payment_methods() {
		$html = '<p>';
		foreach ( $this->get_tokens() as $token ) {
			$html .= $this->saved_payment_method( $token );
		}
		$html .= '</p><span id="wc-' . esc_attr( $this->id ) . '-method-count" data-count="' . esc_attr( count( $this->get_tokens() ) ) . '"></span>';
		$html .= '<div class="clear"></div>';
		echo apply_filters( 'wc_payment_gateway_form_saved_payment_methods_html', $html, $this );
	}

	/**
	 * Outputs a saved payment method from a token.
	 * @since 2.6.0
	 * @param  WC_Payment_Token $token Payment Token
	 * @return string                  Generated payment method HTML
	 */
	public function saved_payment_method( $token ) {
		$html = sprintf(
			'<input type="radio" id="wc-%1$s-payment-token-%2$s" name="wc-%1$s-payment-token" style="width:auto;" class="wc-gateway-payment-token wc-%1$s-payment-token" value="%2$s" %3$s/>',
			esc_attr( $this->id ),
			esc_attr( $token->get_id() ),
			checked( $token->is_default(), true, false )
		);

		$html .= sprintf( '<label class="wc-gateway-payment-form-saved-payment-method wc-gateway-payment-token-label" for="wc-%s-payment-token-%s">',
			esc_attr( $this->id ),
			esc_attr( $token->get_id() )
		);

		$html .= $this->saved_payment_method_title( $token );
		$html .= '</label><br />';

		return apply_filters( 'wc_payment_gateway_form_saved_payment_method_html', $html, $token, $this );
	}

	/**
	 * Outputs a saved payment method's title based on the passed token.
	 * @since 2.6.0
	 * @param  WC_Payment_Token $token Payment Token
	 * @return string                  Generated payment method title HTML
	 */
	public function saved_payment_method_title( $token ) {
		if ( 'CC' == $token->get_type() && is_callable( array( $token, 'get_card_type' ) ) ) {
			$type = esc_html__( wc_get_credit_card_type_label( $token->get_card_type() ), 'woocommerce' );
		} else if ( 'eCheck' === $token->get_type() ) {
			$type = esc_html__( 'eCheck', 'woocommerce' );
		}

		$type  = apply_filters( 'wc_payment_gateway_form_saved_payment_method_title_type_html', $type, $token, $this );
		$title = $type;

		if ( is_callable( array( $token, 'get_last4' ) ) ) {
			$title .= '&nbsp;' . sprintf( esc_html__( 'ending in %s', 'woocommerce' ), $token->get_last4() );
		}

		if ( is_callable( array( $token, 'get_expiry_month' ) ) && is_callable( array( $token, 'get_expiry_year' ) ) ) {
			$title .= ' ' . sprintf( esc_html__( '(expires %s)', 'woocommerce' ), $token->get_expiry_month() . '/' . substr( $token->get_expiry_year(), 2 ) );
		}

		return apply_filters( 'wc_payment_gateway_form_saved_payment_method_title_html', $title, $token, $this );
	}

	/**
	 * Outputs a checkbox for saving a new payment method to the database.
	 * @since 2.6.0
	 */
	public function save_payment_method_checkbox() {
		$html = sprintf(
			'<p class="form-row" id="wc-%s-new-payment-method-wrap">',
			esc_attr( $this->id )
		);
		$html .= sprintf(
			'<input name="wc-%1$s-new-payment-method" id="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;"/>',
			esc_attr( $this->id )
		);
		$html .= sprintf(
			'<label for="wc-%s-new-payment-method" style="display:inline;">%s</label>',
			esc_attr( $this->id ),
			esc_html__( 'Save to Account', 'woocommerce' )
		);
		$html .= '</p><div class="clear"></div>';
		echo $html;
	}

	/**
	 * Displays a radio button for entering a new payment method (new CC details) instead of using a saved method.
	 * Only displayed when a gateway supports tokenization.
	 * @since 2.6.0
	 */
	public function use_new_payment_method_checkbox() {
		$label = ( ! empty( $this->new_method_label ) ? esc_html( $this->new_method_label ) : esc_html__( 'Use a new payment method', 'woocommerce' ) );
		$html = '<input type="radio" id="wc-' . esc_attr( $this->id ). '-new" name="wc-' . esc_attr( $this->id ) . '-payment-token" value="new" style="width:auto;">';
		$html .= '<label class="wc-' . esc_attr( $this->id ) . '-payment-form-new-checkbox wc-gateway-payment-token-label" for="wc-' . esc_attr( $this->id ) . '-new">';
		$html .=  apply_filters( 'woocommerce_payment_gateway_form_new_method_label', $label, $this );
		$html .= '</label>';
		echo '<div class="wc-' . esc_attr( $this->id ) . '-payment-form-new-checkbox-wrap">' . $html . '</div>';
	}

	/**
	 * Core credit card form which gateways can used if needed. Deprecated - inheirt WC_Payment_Gateway_CC instead.
	 * @param  array $args
	 * @param  array $fields
	 */
	public function credit_card_form( $args = array(), $fields = array() ) {
		_deprecated_function( 'credit_card_form', '2.6', 'WC_Payment_Gateway_CC->form' );
		$cc_form = new WC_Payment_Gateway_CC;
		$cc_form->id       = $this->id;
		$cc_form->supports = $this->supports;
		$cc_form->form();
	}
}
