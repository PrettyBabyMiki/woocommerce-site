<?php
/**
 * Logic for extending WC_REST_Payment_Gateways_Controller.
 */

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\WooCommercePayments;
use Automattic\WooCommerce\Admin\Features\TransientNotices;

defined( 'ABSPATH' ) || exit;

/**
 * PaymentGateway class
 */
class PaymentGatewaysController {

	/**
	 * Initialize payment gateway changes.
	 */
	public static function init() {
		add_filter( 'woocommerce_rest_prepare_payment_gateway', array( __CLASS__, 'extend_response' ), 10, 3 );
		add_filter( 'admin_init', array( __CLASS__, 'possibly_do_connection_return_action' ) );
		add_action( 'woocommerce_admin_payment_gateway_connection_return', array( __CLASS__, 'handle_successfull_connection' ) );
		add_filter( 'woocommerce_payment_gateways_setting_additional_rows', array( __CLASS__, 'add_other_payment_methods_link' ), 10, 2 );
	}

	/**
	 * Add necessary fields to REST API response.
	 *
	 * @param  WP_REST_Response   $response   Response data.
	 * @param  WC_Payment_Gateway $gateway    Payment gateway object.
	 * @param  WP_REST_Request    $request    Request object.
	 * @return WP_REST_Response
	 */
	public static function extend_response( $response, $gateway, $request ) {
		$data = $response->get_data();

		$data['needs_setup']          = $gateway->needs_setup();
		$data['post_install_scripts'] = self::get_post_install_scripts( $gateway );
		$data['settings_url']         = method_exists( $gateway, 'get_settings_url' )
			? $gateway->get_settings_url()
			: admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $gateway->id ) );

		$return_url             = wc_admin_url( '&task=payments&connection-return=' . strtolower( $gateway->id ) . '&_wpnonce=' . wp_create_nonce( 'connection-return' ) );
		$data['connection_url'] = method_exists( $gateway, 'get_connection_url' )
			? $gateway->get_connection_url( $return_url )
			: null;

		$data['setup_help_text'] = method_exists( $gateway, 'get_setup_help_text' )
			? $gateway->get_setup_help_text()
			: null;

		$data['required_settings_keys'] = method_exists( $gateway, 'get_required_settings_keys' )
			? $gateway->get_required_settings_keys()
			: array();

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Get payment gateway scripts for post-install.
	 *
	 * @param  WC_Payment_Gateway $gateway Payment gateway object.
	 * @return array Install scripts.
	 */
	public static function get_post_install_scripts( $gateway ) {
		$scripts    = array();
		$wp_scripts = wp_scripts();

		$handles = method_exists( $gateway, 'get_post_install_script_handles' )
			? $gateway->get_post_install_script_handles()
			: array();

		foreach ( $handles as $handle ) {
			if ( isset( $wp_scripts->registered[ $handle ] ) ) {
				$scripts[] = $wp_scripts->registered[ $handle ];
			}
		}

		return $scripts;
	}

	/**
	 * Call an action after a gating has been successfully returned.
	 */
	public static function possibly_do_connection_return_action() {
		if (
			! isset( $_GET['page'] ) ||
			'wc-admin' !== $_GET['page'] ||
			! isset( $_GET['task'] ) ||
			'payments' !== $_GET['task'] ||
			! isset( $_GET['connection-return'] ) ||
			! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( wc_clean( wp_unslash( $_GET['_wpnonce'] ) ), 'connection-return' )
		) {
			return;
		}

		$gateway_id = sanitize_text_field( wp_unslash( $_GET['connection-return'] ) );

		do_action( 'woocommerce_admin_payment_gateway_connection_return', $gateway_id );
	}

	/**
	 * Handle a successful gateway connection.
	 *
	 * @param string $gateway_id Gateway ID.
	 */
	public static function handle_successfull_connection( $gateway_id ) {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! isset( $_GET['success'] ) || 1 !== intval( $_GET['success'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification

		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		$payment_gateway  = isset( $payment_gateways[ $gateway_id ] ) ? $payment_gateways[ $gateway_id ] : null;

		if ( ! $payment_gateway ) {
			return;
		}

		$payment_gateway->update_option( 'enabled', 'yes' );

		TransientNotices::add(
			array(
				'user_id' => get_current_user_id(),
				'id'      => 'payment-gateway-connection-return-' . str_replace( ',', '-', $gateway_id ),
				'status'  => 'success',
				'content' => sprintf(
					/* translators: the title of the payment gateway */
					__( '%s connected successfully', 'woocommerce' ),
					$payment_gateway->method_title
				),
			)
		);

		wc_admin_record_tracks_event(
			'tasklist_payment_connect_method',
			array(
				'payment_method' => $gateway_id,
			)
		);

		wp_safe_redirect( wc_admin_url() );
	}

	/**
	 * Add "Other payment methods" link in WooCommerce -> Settings -> Payments
	 * When the store is in WC Payments eligible country.
	 * See https://github.com/woocommerce/woocommerce/issues/32130 for more details.
	 *
	 * @return void
	 */
	public static function add_other_payment_methods_link( $rows, $no_of_cols ) {
		if ( WooCommercePayments::is_supported() ) {
			$link               = 'https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/?utm_source=payments_recommendations';
			$link_text          = __( 'Other payment methods', 'woocommerce' );
			$external_link_icon = '<svg style="margin-left: 4px" class="gridicon gridicons-external needs-offset" height="18" width="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path d="M19 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6v2H5v12h12v-6h2zM13 3v2h4.586l-7.793 7.793 1.414 1.414L19 6.414V11h2V3h-8z"></path></g></svg>';
			$row                = "<tr><td style='border-top: 1px solid #c3c4c7; background-color: #fff' colspan='{$no_of_cols}'><a href='{$link}' target='_blank' class='components-button is-tertiary'>{$link_text} {$external_link_icon}</a></td>";
			$rows[]             = $row;
		}
		return $rows;
	}
}
