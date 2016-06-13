<?php
include_once( 'legacy/class-wc-legacy-customer.php' );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WooCommerce customer class handles storage of the current customer's data, such as location.
 *
 * @class    WC_Customer
 * @version  2.7.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_Customer extends WC_Legacy_Customer {

	/**
	 * Stores customer data.
	 * @var array
	 */
	protected $_data = array(
		'id'				  => 0,
		'email'               => '',
		'first_name'          => '',
		'last_name'           => '',
		'role'				  => 'customer',
		'last_order_id'       => null, // read only
		'last_order_date'     => null, // read only
		'orders_count'        => 0, // read only
		'total_spent'         => 0, // read only
		'username'            => '', // read only on existing users
		'password'            => '', // write only
		'date_created'        => '', // read only
		'date_modified'		  => '', // read only
		'billing_postcode'    => '',
		'billing_city'        => '',
		'billing_address_1'   => '',
		'billing_address_2'   => '',
		'billing_state'       => '',
		'billing_country'     => '',
		'shipping_postcode'   => '',
		'shipping_city'       => '',
		'shipping_address_1'  => '',
		'shipping_address_2'  => '',
		'shipping_state'      => '',
		'shipping_country'    => '',
		'is_paying_customer'  => false,
		'is_vat_exempt'       => false, // session only.
		'calculated_shipping' => false, // session only
	);

	/**
	 * Keys which are also stored in a session (so we can make sure they get updated...)
	 * @var array
	 */
	protected $_session_keys = array(
		'billing_postcode', 'billing_city', 'billing_address_1', 'billing_address', 'billing_address_2',
		'billing_state', 'billing_country', 'shipping_postcode', 'shipping_city', 'shipping_address_1', 'shipping_address',
		'shipping_address_2', 'shipping_state', 'shipping_country', 'is_vat_exempt', 'calculated_shipping',
	);

	/**
	 * Data stored in meta keys, but not considered "meta" for a coupon.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_internal_meta_keys = array(
		'billing_postcode', 'billing_city', 'billing_address_1', 'billing_address_2', 'billing_state',
		'billing_country', 'shipping_postcode', 'shipping_city', 'shipping_address_1',
		'shipping_address_2', 'shipping_state', 'shipping_country', 'paying_customer',
		'last_update', 'first_name', 'last_name'
	);

	/**
	 *  Internal meta type used to store user data.
	 * @var string
	 */
	protected $_meta_type = 'user';

	/**
	 * Was data changed in the database for this class?
	 * @var boolean
	 */
	protected $_changed = false;

	/**
	 * If some of the customer information is loaded by session (instead of just from the DB).
	 * @var boolean
	 */
	protected $_from_session = false;

	/**
	 * WC_Customer can also return an object for a logged out user (session).
	 * $_is_user will be false in this case. It will be true for all other cases
	 * (logged in users or getting a WC_Customer for another object)
	 * @var boolean
	 */
	protected $_is_user = false;

	/**
	 * Load customer data based on how WC_Customer is called.
	 * @param mixed $customer WC_Customer object or customer ID is accepted.
	 * if $customer is 'new', you can build a new WC_Customer object. If it's empty, some
	 * data will be pulled from the session for the current user/customer.
	 */
	public function __construct( $customer = '' ) {
		if ( $customer instanceof WC_Customer ) {
			$this->_is_user = true;
			$this->read( absint( $customer->get_id() ) );
		} else if ( is_numeric( $customer ) ) {
			$this->_is_user = true;
			$this->read( $customer );
		} else if ( empty( $customer ) ) {
			$this->_is_user = true; // unless load_session gets called after.
		}

		if ( $this->_from_session ) {
			add_action( 'shutdown', array( $this, 'save_session_if_changed' ), 10 );
		}
	}

	/**
	 * Saves customer information to the current session if any data changed.
	 * @since 2.7.0
	 */
	public function save_session_if_changed() {
		if ( $this->_changed ) {
			$this->save_to_session();
		}
	}

	/**
	 * Loads a WC session into the customer class.
	 */
	public function load_session() {
		$this->_from_session = true;
		if ( is_user_logged_in() ) {
			$this->_is_user = true;
			$this->read( get_current_user_id() );
		} else {
			$this->_is_user = false;
			$this->read( WC()->session->get_customer_id() );
		}
	}

	/*
	 |--------------------------------------------------------------------------
	 | Getters
	 |--------------------------------------------------------------------------
	 | Methods for getting data from the customer object.
	 */

	/**
	 * Return a customer's user ID. If the current customer is logged out, this will be a session key.
	 * @since 2.7.0
	 * @return mixed
	 */
	public function get_id() {
		return $this->_data['id'];
	}

	/**
	 * Return the customer's username.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_username() {
		return $this->_data['username'];
	}

	/**
	 * Return the customer's email.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_email() {
		return sanitize_email( $this->_data['email'] );
	}

	/**
	 * Return customer's first name.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_first_name() {
		return $this->_data['first_name'];
	}

	/**
	 * Return customer's last name.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_last_name() {
		return $this->_data['last_name'];
	}

	/**
	 * Return customer's user role.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_role() {
		return $this->_data['role'];
	}

	/**
	 * Return customer's last order ID.
	 * @since 2.7.0
	 * @return integer|null
	 */
	public function get_last_order_id() {
		return ( is_null( $this->_data['last_order_id'] ) ? null : intval( $this->_data['last_order_id'] ) );
	}

	/**
	 * Return the date of the customer's last order.
	 * @since 2.7.0
	 * @return integer|null
	 */
	public function get_last_order_date() {
		return ( is_null( $this->_data['last_order_date'] ) ? null : intval( $this->_data['last_order_date'] ) );
	}

	/**
	 * Return the number of orders this customer has.
	 * @since 2.7.0
	 * @return integer
	 */
	public function get_orders_count() {
		return intval( $this->_data['orders_count'] );
	}

	/**
	 * Return how much money this customer has spent.
	 * @since 2.7.0
	 * @return float
	 */
	public function get_total_spent() {
		return wc_format_decimal( $this->_data['total_spent'] );
	}

	/**
	 * Return this customer's avatar.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_avatar_url() {
		$avatar_html = get_avatar( $this->get_email() );

		// Get the URL of the avatar from the provided HTML
		preg_match( '/src=["|\'](.+)[\&|"|\']/U', $avatar_html, $matches );

		if ( isset( $matches[1] ) && ! empty( $matches[1] ) ) {
			return esc_url( $matches[1] );
		}

		return '';
	}

	/**
	 * Return the date this customer was created.
	 * @since 2.7.0
	 * @return integer
	 */
	public function get_date_created() {
		return absint( $this->_data['date_created'] );
	}

	/**
	 * Return the date this customer was last updated.
	 * @since 2.7.0
	 * @return integer
	 */
	public function get_date_modified() {
		return absint( $this->_data['date_modified'] );
	}

	/**
	 * Gets customer postcode.
	 * @return string
	 */
	public function get_billing_postcode() {
		return wc_format_postcode( $this->_data['billing_postcode'], $this->get_billing_country() );
	}

	/**
	 * Get customer city.
	 * @return string
	 */
	public function get_billing_city() {
		return $this->_data['billing_city'];
	}

	/**
	 * Get customer address.
	 * @return string
	 */
	public function get_billing_address() {
		return $this->_data['billing_address_1'];
	}

	/**
	 * Get customer address.
	 * @return string
	 */
	public function get_billing_address_1() {
		return $this->get_billing_address();
	}

	/**
	 * Get customer's second address.
	 * @return string
	 */
	public function get_billing_address_2() {
		return $this->_data['billing_address_2'];
	}

	/**
	 * Get customer state.
	 * @return string
	 */
	public function get_billing_state() {
		return $this->_data['billing_state'];
	}

	/**
	 * Get customer country.
	 * @return string
	 */
	public function get_billing_country() {
		return $this->_data['billing_country'];
	}

	/**
	 * Get customer's shipping state.
	 * @return string
	 */
	public function get_shipping_state() {
		return $this->_data['shipping_state'];
	}

	/**
	 * Get customer's shipping country.
	 * @return string
	 */
	public function get_shipping_country() {
		return $this->_data['shipping_country'];
	}

	/**
	 * Get customer's shipping postcode.
	 * @return string
	 */
	public function get_shipping_postcode() {
		return wc_format_postcode( $this->_data['shipping_postcode'], $this->get_shipping_country() );
	}

	/**
	 * Get customer's shipping city.
	 * @return string
	 */
	public function get_shipping_city() {
		return $this->_data['shipping_city'];
	}

	/**
	 * Get customer's shipping address.
	 * @return string
	 */
	public function get_shipping_address() {
		return $this->_data['shipping_address_1'];
	}

	/**
	 * Get customer address.
	 * @return string
	 */
	public function get_shipping_address_1() {
		return $this->get_shipping_address();
	}

	/**
	 * Get customer's second shipping address.
	 * @return string
	 */
	public function get_shipping_address_2() {
		return $this->_data['shipping_address_2'];
	}

	/**
	 * Get if customer is VAT exempt?
	 * @since 2.7.0
	 * @return bool
	 */
	public function get_is_vat_exempt() {
		return ( ! empty( $this->_data['is_vat_exempt'] ) ) ? true : false;
	}

	/**
	 * Has customer calculated shipping?
	 * @return bool
	 */
	public function get_calculated_shipping() {
		return ! empty( $this->_data['calculated_shipping'] );
	}

	/**
	 * Get taxable address.
	 * @return array
	 */
	public function get_taxable_address() {
		$tax_based_on = get_option( 'woocommerce_tax_based_on' );

		// Check shipping method at this point to see if we need special handling
		if ( true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && sizeof( array_intersect( WC()->session->get( 'chosen_shipping_methods', array() ), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
			$tax_based_on = 'base';
		}

		if ( 'base' === $tax_based_on ) {
			$country  = WC()->countries->get_base_country();
			$state    = WC()->countries->get_base_state();
			$postcode = WC()->countries->get_base_postcode();
			$city     = WC()->countries->get_base_city();
		} elseif ( 'billing' === $tax_based_on ) {
			$country  = $this->get_billing_country();
			$state    = $this->get_billing_state();
			$postcode = $this->get_billing_postcode();
			$city     = $this->get_billing_city();
		} else {
			$country  = $this->get_shipping_country();
			$state    = $this->get_shipping_state();
			$postcode = $this->get_shipping_postcode();
			$city     = $this->get_shipping_city();
		}

		return apply_filters( 'woocommerce_customer_taxable_address', array( $country, $state, $postcode, $city ) );
	}

	/**
	 * Gets a customer's downloadable products.
	 * @return array Array of downloadable products
	 */
	public function get_downloadable_products() {
		$downloads = array();
		if ( $this->_is_user ) {
			$downloads = wc_get_customer_available_downloads( $this->get_id() );
		}
		return apply_filters( 'woocommerce_customer_get_downloadable_products', $downloads );
	}

	/**
	 * Is the user a paying customer?
	 * @since 2.7.0
	 * @return bool
	 */
	function get_is_paying_customer() {
		return (bool) $this->_data['is_paying_customer'];
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	| Functions for setting customer data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/

	/**
	 * Set customer's username.
	 * @since 2.7.0
	 * @param string $username
	 */
	public function set_username( $username ) {
		$this->_data['username'] = $username;
	}

	/**
	 * Set customer's email.
	 * @since 2.7.0
	 * @param string $email
	 */
	public function set_email( $email ) {
		$this->_data['email'] = sanitize_email( $email );
	}

	/**
	 * Set customer's first name.
	 * @since 2.7.0
	 * @param string $first_name
	 */
	public function set_first_name( $first_name ) {
		$this->_data['first_name'] = $first_name;
	}

	/**
	 * Set customer's last name.
	 * @since 2.7.0
	 * @param string $last_name
	 */
	public function set_last_name( $last_name ) {
		$this->_data['last_name'] = $last_name;
	}

	/**
	 * Set customer's user role(s).
	 * @since 2.7.0
	 * @param mixed $role
	 */
	public function set_role( $role ) {
		$this->_data['role'] = $role;
	}

	/**
	 * Set customer's last order ID.
	 * @since 2.7.0
	 * @param integer|null $last_order_id
	 */
	public function set_last_order_id( $last_order_id ) {
		$this->_data['last_order_id'] = $last_order_id;
	}

	/**
	 * Set the date of the customer's last order.
	 * @since 2.7.0
	 * @param string|null $last_order_date
	 */
	public function set_last_order_date( $last_order_date ) {
		$this->_data['last_order_date'] = $last_order_date;
 	}

	/**
	 * Set the number of orders this customer has.
	 * @since 2.7.0
	 * @param integer $order_count
	 */
	public function set_orders_count( $orders_count ) {
		$this->_data['orders_count'] = $orders_count;
	}

	/**
	 * Return how much money this customer has spent.
	 * @since 2.7.0
	 * @param float $total_spent
	 */
	public function set_total_spent( $total_spent ) {
		$this->_data['total_spent'] = $total_spent;
	}

	/**
	 * Set customer's password.
	 * @since 2.7.0
	 * @param string $password
	 */
	public function set_password( $password ) {
		$this->_data['password'] = wc_clean( $password );
	}

	/**
	 * Set the date this customer was last updated.
	 * @since 2.7.0
	 * @param integer $timestamp
	 */
	public function set_date_modified( $timestamp ) {
		$this->_data['date_modified'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set the date this customer was last updated.
	 * @since 2.7.0
	 * @param integer $timestamp
	 */
	public function set_date_created( $timestamp ) {
		$this->_data['date_created'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set customer address to match shop base address.
	 * @since 2.7.0
	 */
	public function set_billing_address_to_base() {
		$base = wc_get_customer_default_location();
		$this->_data['billing_country']  = $base['country'];
		$this->_data['billing_state']    = $base['state'];
		$this->_data['billing_postcode'] = '';
		$this->_data['billing_city']     = '';
	}

	/**
	 * Set customer shipping address to base address.
	 * @since 2.7.0
	 */
	public function set_shipping_address_to_base() {
		$base = wc_get_customer_default_location();
		$this->_data['shipping_country']  = $base['country'];
		$this->_data['shipping_state']    = $base['state'];
		$this->_data['shipping_postcode'] = '';
		$this->_data['shipping_city']     = '';
	}

	/**
	 * Sets all shipping info at once.
	 * @param string $country
	 * @param string $state
	 * @param string $postcode
	 * @param string $city
	 */
	public function set_shipping_location( $country, $state = '', $postcode = '', $city = '' ) {
		$this->_data['shipping_country']  = $country;
		$this->_data['shipping_state']    = $state;
		$this->_data['shipping_postcode'] = $postcode;
		$this->_data['shipping_city']     = $city;
	}

	/**
	 * Sets all address info at once.
	 * @param string $country
	 * @param string $state
	 * @param string $postcode
	 * @param string $city
	 */
	public function set_billing_location( $country, $state, $postcode = '', $city = '' ) {
		$this->_data['billing_country']  = $country;
		$this->_data['billing_state']    = $state;
		$this->_data['billing_postcode'] = $postcode;
		$this->_data['billing_city']     = $city;
	}

	/**
	 * Set customer country.
	 * @param mixed $country
	 */
	public function set_billing_country( $country ) {
		$this->_data['billing_country'] = $country;
	}

	/**
	 * Set customer state.
	 * @param mixed $state
	 */
	public function set_billing_state( $state ) {
		$this->_data['billing_state'] = $state;
	}

	/**
	 * Sets customer postcode.
	 * @param mixed $postcode
	 */
	public function set_billing_postcode( $postcode ) {
		$this->_data['billing_postcode'] = $postcode;
	}

	/**
	 * Sets customer city.
	 * @param mixed $city
	 */
	public function set_billing_city( $city ) {
		$this->_data['billing_city'] = $city;
	}

	/**
	 * Set customer address.
	 * @param mixed $address
	 */
	public function set_billing_address( $address ) {
		$this->_data['billing_address_1'] = $address;
	}

	/**
	 * Set customer address.
	 * @param mixed $address
	 */
	public function set_billing_address_1( $address ) {
		$this->set_billing_address( $address );
	}

	/**
	 * Set customer's second address.
	 * @param mixed $address
	 */
	public function set_billing_address_2( $address ) {
		$this->_data['billing_address_2'] = $address;
	}

	/**
	 * Set shipping country.
	 * @param string $country
	 */
	public function set_shipping_country( $country ) {
		$this->_data['shipping_country'] = $country;
	}

	/**
	 * Set shipping state.
	 * @param string $state
	 */
	public function set_shipping_state( $state ) {
		$this->_data['shipping_state'] = $state;
	}

	/**
	 * Set shipping postcode.
	 * @param string $postcode
	 */
	public function set_shipping_postcode( $postcode ) {
		$this->_data['shipping_postcode'] = $postcode;
	}

	/**
	 * Sets shipping city.
	 * @param string $city
	 */
	public function set_shipping_city( $city ) {
		$this->_data['shipping_city'] = $city;
	}

	/**
	 * Set shipping address.
	 * @param string $address
	 */
	public function set_shipping_address( $address ) {
		$this->_data['shipping_address_1'] = $address;
	}

	/**
	 * Set customer shipping address.
	 * @param mixed $address
	 */
	public function set_shipping_address_1( $address ) {
		$this->set_shipping_address( $address );
	}

	/**
	 * Set second shipping address.
	 * @param string $address
	 */
	public function set_shipping_address_2( $address ) {
		$this->_data['shipping_address_2'] = $address;
	}

	/**
	 * Set if customer has tax exemption.
	 * @param bool $is_vat_exempt
	 */
	public function set_is_vat_exempt( $is_vat_exempt ) {
		$this->_data['is_vat_exempt'] = $is_vat_exempt;
	}

	/**
	 * Calculated shipping?
	 * @param boolean $calculated
	 */
	public function set_calculated_shipping( $calculated = true ) {
		$this->_data['calculated_shipping'] = $calculated;
	}

	/**
	 * Set if the user a paying customer.
	 * @since 2.7.0
	 * @param boolean $is_paying_customer
	 */
	function set_is_paying_customer( $is_paying_customer ) {
		$this->_data['is_paying_customer'] = (bool) $is_paying_customer;
	}

	/*
	|--------------------------------------------------------------------------
	| Other methods
	|--------------------------------------------------------------------------
	| Other functions for interacting with customers.
	*/

	/**
	 * Is customer outside base country (for tax purposes)?
	 * @return bool
	 */
	public function is_customer_outside_base() {
		list( $country, $state ) = $this->get_taxable_address();
		if ( $country ) {
			$default = wc_get_base_location();
			if ( $default['country'] !== $country ) {
				return true;
			}
			if ( $default['state'] && $default['state'] !== $state ) {
				return true;
			}
		}
		return false;
	}

	/*
	 |--------------------------------------------------------------------------
	 | CRUD methods
	 |--------------------------------------------------------------------------
	 | Methods which create, read, update and delete from the database.
	 |
	 | A save method is included for convenience (chooses update or create based
	 | on if the order exists yet).
	 */

	 /**
	  * Create a customer.
	  * @since 2.7.0.
	  */
	public function create() {
		$customer_id = wc_create_new_customer( $this->get_email(), $this->get_username(), $this->_data['password'] );
		unset( $this->_data['password'] );
		if ( $customer_id ) {
			$this->_data['id'] = $customer_id;
			update_user_meta( $this->get_id(), 'billing_postcode', $this->get_billing_postcode() );
			update_user_meta( $this->get_id(), 'billing_city', $this->get_billing_city() );
			update_user_meta( $this->get_id(), 'billing_address_1', $this->get_billing_address() );
			update_user_meta( $this->get_id(), 'billing_address_2', $this->get_billing_address_2() );
			update_user_meta( $this->get_id(), 'billing_state', $this->get_billing_state() );
			update_user_meta( $this->get_id(), 'billing_country', $this->get_billing_country() );
			update_user_meta( $this->get_id(), 'shipping_postcode', $this->get_shipping_postcode() );
			update_user_meta( $this->get_id(), 'shipping_city', $this->get_shipping_city() );
			update_user_meta( $this->get_id(), 'shipping_address_1', $this->get_shipping_address() );
			update_user_meta( $this->get_id(), 'shipping_address_2', $this->get_shipping_address_2() );
			update_user_meta( $this->get_id(), 'shipping_state', $this->get_shipping_state() );
			update_user_meta( $this->get_id(), 'shipping_country', $this->get_shipping_country() );
			update_user_meta( $this->get_id(), 'paying_customer', $this->get_is_paying_customer() );
			$this->set_date_modified( time() );
			update_user_meta( $this->get_id(), 'last_update',  $this->get_date_modified() );
			update_user_meta( $this->get_id(), 'first_name', $this->get_first_name() );
			update_user_meta( $this->get_id(), 'last_name', $this->get_last_name() );
			wp_update_user( array( 'ID' => $this->get_id(), 'role' => $this->get_role() ) );
			$wp_user = new WP_User( $this->get_id() );
			$this->set_date_created( strtotime( $wp_user->user_registered ) );
			$this->save_meta_data();
		}
	}

	/**
	 * Read a customer from the database.
	 * @since 2.7.0
	 * @param integer $id
	 */
	public function read( $id ) {
		global $wpdb;
		$pull_from_db = true;
		$data         = array();
		if ( $this->_from_session ) {
			$data = (array) WC()->session->get( 'customer' );
			if ( ! empty( $data ) ) {
				$pull_from_db  = false;
				foreach ( $this->_session_keys as $session_key ) {
					$function_key = $session_key;
					if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
						$session_key = str_replace( 'billing_', '', $session_key );
					}
					if ( ! empty( $data[ $session_key ] ) && is_callable( array( $this, "set_{$function_key}" ) ) ) {
						$this->{"set_{$function_key}"}( $data[ $session_key ] );
					}
				}
			}
		}

		if ( $this->_is_user ) {

			// Only continue reading if the customer exists.
			$user_object = get_userdata( $id );
			if ( empty( $user_object ) || empty ( $user_object->ID ) ) {
				$this->_data['id'] = 0;
				return;
			}

			foreach ( array_keys( $this->_data ) as $key ) {
				if ( 'billing_' === substr( $key, 0, 8 ) ) {
					$session_key = str_replace( 'billing_', '', $key );
				}
				if ( ! $pull_from_db && ! empty( $data[ $session_key ] ) ) {
					continue;
				}

				$meta_value = get_user_meta( $id, $key, true );
				if ( $meta_value && is_callable( array( $this, "set_{$key}" ) ) ) {
					$this->{"set_{$key}"}( $meta_value );
				}
			}
		}

		if ( $this->_is_user ) {
			$this->set_is_paying_customer( get_user_meta( $id, 'paying_customer', true ) );
			$wp_user = new WP_User( $id );
			$this->set_email( $wp_user->user_email );
			$this->set_username( $wp_user->user_login );
			$this->set_date_created( strtotime( $wp_user->user_registered ) );
			$this->set_date_modified( get_user_meta( $id, 'last_update', true ) );
			$this->set_role( ( ! empty ( $wp_user->roles[0] ) ? $wp_user->roles[0] : 'customer' ) );

			// Get info about user's last order
			$last_order = $wpdb->get_row( "SELECT id, post_date_gmt
				FROM $wpdb->posts AS posts
				LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
				WHERE meta.meta_key = '_customer_user'
				AND   meta.meta_value = {$id}
				AND   posts.post_type = 'shop_order'
				AND   posts.post_status IN ( '" . implode( "','", array_keys( wc_get_order_statuses() ) ) . "' )
				ORDER BY posts.ID DESC
			" );

			$this->set_last_order_id( is_object( $last_order ) ? $last_order->id : null );
			$this->set_last_order_date( is_object( $last_order ) ? strtotime( $last_order->post_date_gmt ) : null );

			// WC_Customer can't use wc_get_customer_order_count because get_order_types might not be loaded by the time a customer/session is

			$count = $wpdb->get_var( "SELECT COUNT(*)
				FROM $wpdb->posts as posts

				LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id

				WHERE   meta.meta_key       = '_customer_user'
				AND     posts.post_type = 'shop_order'
				AND     posts.post_status   IN ('" . implode( "','", array_keys( wc_get_order_statuses() ) )  . "')
				AND     meta_value          = $id
			" );

			$spent = $wpdb->get_var( "SELECT SUM(meta2.meta_value)
				FROM $wpdb->posts as posts

				LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
				LEFT JOIN {$wpdb->postmeta} AS meta2 ON posts.ID = meta2.post_id

				WHERE   meta.meta_key       = '_customer_user'
				AND     meta.meta_value     = $id
				AND     posts.post_type     = 'shop_order'
				AND     posts.post_status   IN ( 'wc-completed', 'wc-processing' )
				AND     meta2.meta_key      = '_order_total'
			" );
			if ( ! $spent ) {
				$spent = 0;
			}

			$this->set_orders_count( $count );
			$this->set_total_spent( $spent );

			$this->read_meta_data();
		}

		$this->_data['id'] = $id;

		$default = wc_get_customer_default_location();

		// Set some defaults if some of our values are still not set.
		if ( empty( $this->get_billing_country() ) ) {
			$this->set_billing_country( $default['country'] );
		}

		if ( empty( $this->get_shipping_country() ) ) {
			$this->set_shipping_country( $this->get_billing_country() );
		}

		if ( empty( $this->get_billing_state() ) ) {
			$this->set_billing_state( $default['state'] );
		}

		if ( empty( $this->get_shipping_state() ) ) {
			$this->set_shipping_state( $this->get_billing_state() );
		}

		unset( $this->_data['password'] ); // password is write only, never ever read it
	}

	/**
	 * Update a customer.
	 * @since 2.7.0
	 */
	public function update() {
		$customer_ID = $this->get_id();

		wp_update_user( array( 'ID' => $customer_ID, 'user_email' => $this->get_email() ) );
		// Only update password if a new one was set with set_password
		if ( isset( $this->_data['password'] ) ) {
			wp_update_user( array( 'ID' => $customer_ID, 'user_pass' => $this->_data['password'] ) );
			unset( $this->_data['password'] );
		}

		update_user_meta( $this->get_id(), 'billing_postcode', $this->get_billing_postcode() );
		update_user_meta( $this->get_id(), 'billing_city', $this->get_billing_city() );
		update_user_meta( $this->get_id(), 'billing_address_1', $this->get_billing_address() );
		update_user_meta( $this->get_id(), 'billing_address_2', $this->get_billing_address_2() );
		update_user_meta( $this->get_id(), 'billing_state', $this->get_billing_state() );
		update_user_meta( $this->get_id(), 'billing_country', $this->get_billing_country() );
		update_user_meta( $this->get_id(), 'shipping_postcode', $this->get_shipping_postcode() );
		update_user_meta( $this->get_id(), 'shipping_city', $this->get_shipping_city() );
		update_user_meta( $this->get_id(), 'shipping_address_1', $this->get_shipping_address() );
		update_user_meta( $this->get_id(), 'shipping_address_2', $this->get_shipping_address_2() );
		update_user_meta( $this->get_id(), 'shipping_state', $this->get_shipping_state() );
		update_user_meta( $this->get_id(), 'shipping_country', $this->get_shipping_country() );
		update_user_meta( $this->get_id(), 'paying_customer', $this->get_is_paying_customer() );
		$this->set_date_modified( time() );
		update_user_meta( $this->get_id(), 'last_update',  $this->get_date_modified() );
		update_user_meta( $this->get_id(), 'first_name', $this->get_first_name() );
		update_user_meta( $this->get_id(), 'last_name', $this->get_last_name() );
		wp_update_user( array( 'ID' => $this->get_id(), 'role' => $this->get_role() ) );
		$this->save_meta_data();
	}

	/**
	 * Delete a customer.
	 * @since 2.7.0
	 */
	public function delete() {
		if ( ! $this->get_id() ) {
			return;
		}
		wp_delete_user( $this->get_id() );
	}

	/**
	 * Save data (either create or update depending on if we are working on an existing customer).
	 * @since 2.7.0
	 */
	public function save() {
		if ( ! $this->_is_user ) {
			$this->create();
		} else {
			if ( ! $this->get_id() ) {
				$this->create();
			} else {
				$this->update();
			}
		}
	}

	/**
	 * Saves data to the session only (does not overwrite DB values).
	 * @since 2.7.0
	 */
	public function save_to_session() {
		if ( ! $this->_from_session ) {
			return;
		}
		$data = array();
		foreach ( $this->_session_keys as $session_key ) {
			$function_key = $session_key;
			if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
				$session_key = str_replace( 'billing_', '', $session_key );
			}
			$data[ $session_key ] = $this->{"get_$function_key"}();
		}
		WC()->session->set( 'customer', $data );
	}

}
