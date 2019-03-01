<?php
/**
 * WooCommerce Admin Setup Wizard Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of the WooCommerce Onboarding Wizard.
 */
class WC_Admin_Setup_Wizard_Tracking {

	/**
	 * Class instance.
	 *
	 * @var WC_Admin_Setup_Wizard_Tracking instance
	 */
	protected static $instance = false;

	/**
	 * Steps for the setup wizard
	 *
	 * @var array
	 */
	private $steps = array();

	/**
	 * Get class instance
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init tracking.
	 */
	public static function init() {
		if ( empty( $_GET['page'] ) || 'wc-setup' !== $_GET['page'] ) { // WPCS: CSRF ok, input var ok.
			return;
		}

		add_filter( 'woocommerce_setup_wizard_steps', array( __CLASS__, 'set_obw_steps' ) );
		add_action( 'shutdown', array( __CLASS__, 'track_skip_step' ), 1 );
		add_action( 'add_option_woocommerce_allow_tracking', array( __CLASS__, 'track_start' ), 10, 2 );
		self::add_step_save_events();
	}

	/**
	 * Track when tracking is opted into and OBW has started.
	 *
	 * @param string $option Option name.
	 * @param string $value  Option value.
	 * @return void
	 */
	public static function track_start( $option, $value ) {
		if ( 'yes' !== $value || empty( $_GET['page'] ) || 'wc-setup' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			return;
		}

		WC_Tracks::record_event( 'obw_start' );
	}

	/**
	 * Track various events when a step is saved.
	 */
	public static function add_step_save_events() {
		if ( empty( $_POST['save_step'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			return;
		}

		$current_step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
		update_option( 'woocommerce_obw_last_completed_step', $current_step );

		switch ( $current_step ) {
			case '':
			case 'store_setup':
				add_action( 'admin_init', array( __CLASS__, 'track_store_setup' ), 1 );
				break;
			case 'payment':
				add_action( 'admin_init', array( __CLASS__, 'track_payments' ), 1 );
				break;
			case 'shipping':
				add_action( 'admin_init', array( __CLASS__, 'track_shipping' ), 1 );
				break;
			case 'recommended':
				add_action( 'admin_init', array( __CLASS__, 'track_recommended' ), 1 );
				break;
			case 'activate':
				add_action( 'admin_init', array( __CLASS__, 'track_jetpack_activate' ), 1 );
				break;
		}
	}

	/**
	 * Track store setup and store properties on save.
	 *
	 * @return void
	 */
	public static function track_store_setup() {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput
		$properties = array(
			'country'        => isset( $_POST['store_country'] ) ? sanitize_text_field( $_POST['store_country'] ) : '',
			'currency_code'  => isset( $_POST['currency_code'] ) ? sanitize_text_field( $_POST['currency_code'] ) : '',
			'product_type'   => isset( $_POST['product_type'] ) ? sanitize_text_field( $_POST['product_type'] ) : '',
			'sell_in_person' => isset( $_POST['sell_in_person'] ) && ( 'yes' === sanitize_text_field( $_POST['sell_in_person'] ) ),
		);
		// phpcs:enable

		WC_Tracks::record_event( 'obw_store_setup', $properties );
	}

	/**
	 * Track payment gateways selected.
	 *
	 * @return void
	 */
	public static function track_payments() {
		$selected_gateways     = array();
		$created_accounts      = array();
		$wc_admin_setup_wizard = new WC_Admin_Setup_Wizard();
		$gateways              = array_merge( $wc_admin_setup_wizard->get_wizard_in_cart_payment_gateways(), $wc_admin_setup_wizard->get_wizard_manual_payment_gateways() );

		foreach ( $gateways as $gateway_id => $gateway ) {
			if ( ! empty( $_POST[ 'wc-wizard-service-' . $gateway_id . '-enabled' ] ) ) { // WPCS: CSRF ok, input var ok.
				$selected_gateways[] = $gateway_id;
			}
		}

		// Stripe account being created.
		if (
			! empty( $_POST['wc-wizard-service-stripe-enabled'] ) && // WPCS: CSRF ok, input var ok.
			! empty( $_POST['stripe_create_account'] ) // WPCS: CSRF ok, input var ok.
		) {
			$created_accounts[] = 'stripe';
		}
		// PayPal account being created.
		if (
			! empty( $_POST['wc-wizard-service-ppec_paypal-enabled'] ) && // WPCS: CSRF ok, input var ok.
			! empty( $_POST['ppec_paypal_reroute_requests'] ) // WPCS: CSRF ok, input var ok.
		) {
			$created_accounts[] = 'ppec_paypal';
		}

		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput
		$properties = array(
			'selected_gateways' => implode( ', ', $selected_gateways ),
			'created_accounts'  => implode( ', ', $created_accounts ),
		);
		// phpcs:enable

		WC_Tracks::record_event( 'obw_payments', $properties );
	}

	/**
	 * Track shipping units and whether or not labels are set.
	 *
	 * @return void
	 */
	public static function track_shipping() {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput
		$properties = array(
			'weight_unit'       => isset( $_POST['weight_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['weight_unit'] ) ) : '',
			'dimension_unit'    => isset( $_POST['dimension_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['dimension_unit'] ) ) : '',
			'setup_wcs_labels'  => isset( $_POST['setup_woocommerce_services'] ) && 'yes' === $_POST['setup_woocommerce_services'],
			'setup_shipstation' => isset( $_POST['setup_shipstation'] ) && 'yes' === $_POST['setup_shipstation'],
		);
		// phpcs:enable

		WC_Tracks::record_event( 'obw_shipping', $properties );
	}

	/**
	 * Track recommended plugins selected for install.
	 *
	 * @return void
	 */
	public static function track_recommended() {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification
		$properties = array(
			'setup_storefront'    => isset( $_POST['setup_storefront_theme'] ) && 'yes' === $_POST['setup_storefront_theme'],
			'setup_automated_tax' => isset( $_POST['setup_automated_taxes'] ) && 'yes' === $_POST['setup_automated_taxes'],
			'setup_mailchimp'     => isset( $_POST['setup_mailchimp'] ) && 'yes' === $_POST['setup_mailchimp'],
		);
		// phpcs:enable

		WC_Tracks::record_event( 'obw_recommended', $properties );
	}

	/**
	 * Tracks when Jetpack is activated through the OBW.
	 *
	 * @return void
	 */
	public static function track_jetpack_activate() {
		WC_Tracks::record_event( 'obw_activate' );
	}

	/**
	 * Track skipped steps.
	 *
	 * @return void
	 */
	public static function track_skip_step() {
		$previous_step = get_option( 'woocommerce_obw_last_completed_step' );
		$current_step  = isset( $_GET['step'] ) ? sanitize_text_field( $_GET['step'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput
		if ( ! $previous_step || ! $current_step ) {
			return;
		}

		$wc_admin_setup_wizard_tracking = self::get_instance();
		$steps                          = array_keys( $wc_admin_setup_wizard_tracking->steps );
		$current_step_index             = array_search( $current_step, $steps, true );
		$previous_step_index            = array_search( $previous_step, $steps, true );

		// If we're going forward more than 1 completed step.
		if ( $current_step_index > $previous_step_index + 1 ) {
			$properties = array(
				'step' => $steps[ $current_step_index - 1 ],
			);
			WC_Tracks::record_event( 'obw_skip_step', $properties );
		}
	}

	/**
	 * Set the OBW steps inside this class instance.
	 *
	 * @param array $steps Array of OBW steps.
	 */
	public static function set_obw_steps( $steps ) {
		$wc_admin_setup_wizard_tracking        = self::get_instance();
		$wc_admin_setup_wizard_tracking->steps = $steps;

		return $steps;
	}
}
