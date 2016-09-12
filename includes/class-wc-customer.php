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
	protected $data = array(
		'date_created'       => '',
		'date_modified'      => '',
		'email'              => '',
		'first_name'         => '',
		'last_name'          => '',
		'role'               => 'customer',
		'username'           => '',
		'billing'            => array(
			'first_name'     => '',
			'last_name'      => '',
			'company'        => '',
			'address_1'      => '',
			'address_2'      => '',
			'city'           => '',
			'state'          => '',
			'postcode'       => '',
			'country'        => '',
			'email'          => '',
			'phone'          => '',
		),
		'shipping'           => array(
			'first_name'     => '',
			'last_name'      => '',
			'company'        => '',
			'address_1'      => '',
			'address_2'      => '',
			'city'           => '',
			'state'          => '',
			'postcode'       => '',
			'country'        => '',
		),
		'is_paying_customer' => false,
	);

	/**
	 * Keys which are also stored in a session (so we can make sure they get updated...)
	 * @var array
	 */
	protected $session_keys = array(
		'billing_postcode',
		'billing_city',
		'billing_address_1',
		'billing_address',
		'billing_address_2',
		'billing_state',
		'billing_country',
		'shipping_postcode',
		'shipping_city',
		'shipping_address_1',
		'shipping_address',
		'shipping_address_2',
		'shipping_state',
		'shipping_country',
		'is_vat_exempt',
		'calculated_shipping',
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_phone',
		'billing_email',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
	);

	/**
	 * Data stored in meta keys, but not considered "meta"
	 * @since 2.7.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'billing_postcode',
		'billing_city',
		'billing_address_1',
		'billing_address_2',
		'billing_state',
		'billing_country',
		'shipping_postcode',
		'shipping_city',
		'shipping_address_1',
		'shipping_address_2',
		'shipping_state',
		'shipping_country',
		'paying_customer',
		'last_update',
		'first_name',
		'last_name',
		'show_admin_bar_front',
		'use_ssl',
		'admin_color',
		'rich_editing',
		'comment_shortcuts',
		'dismissed_wp_pointers',
		'show_welcome_panel',
		'_woocommerce_persistent_cart',
		'session_tokens',
		'nickname',
		'description',
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_phone',
		'billing_email',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'default_password_nag',
		'primary_blog',
		'source_domain',
	);

	/**
	 * Internal meta type used to store user data.
	 * @var string
	 */
	protected $meta_type = 'user';

	/**
	 * If this is the customer session, this is true. When true, guest accounts will not be saved to the DB.
	 * @var boolean
	 */
	protected $is_session = false;

	/**
	 * Stores a password if this needs to be changed. Write-only and hidden from _data.
	 * @var string
	 */
	protected $password = '';

	/**
	 * Stores if user is VAT exempt for this session.
	 * @var string
	 */
	protected $is_vat_exempt = false;

	/**
	 * Stores if user has calculated shipping in this session.
	 * @var string
	 */
	protected $calculated_shipping = false;

	/**
	 * Load customer data based on how WC_Customer is called.
	 *
	 * If $customer is 'new', you can build a new WC_Customer object. If it's empty, some
	 * data will be pulled from the session for the current user/customer.
	 *
	 * @param int $customer_id Customer ID
	 * @param bool $is_session True if this is the customer session
	 */
	public function __construct( $customer_id = 0, $is_session = false ) {
		if ( $customer_id > 0 ) {
			$this->read( $customer_id );
		}
		if ( $is_session ) {
			$this->is_session = true;
			$this->load_session();
			add_action( 'shutdown', array( $this, 'save_to_session' ), 10 );
		}
	}

	/**
	 * Loads a WC session into the customer class.
	 */
	public function load_session() {
		$data = (array) WC()->session->get( 'customer' );
		if ( ! empty( $data ) ) {
			foreach ( $this->session_keys as $session_key ) {
				$function_key = $session_key;
				if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
					$session_key = str_replace( 'billing_', '', $session_key );
				}
				if ( ! empty( $data[ $session_key ] ) && is_callable( array( $this, "set_{$function_key}" ) ) ) {
					$this->{"set_{$function_key}"}( $data[ $session_key ] );
				}
			}
		}
		$this->load_defaults();
	}

	/**
	 * Load default values if props are unset.
	 */
	protected function load_defaults() {
		$default = wc_get_customer_default_location();

		// Set some defaults if some of our values are still not set.
		if ( ! $this->get_billing_country() ) {
			$this->set_billing_country( $default['country'] );
		}

		if ( ! $this->get_shipping_country() ) {
			$this->set_shipping_country( $this->get_billing_country() );
		}

		if ( ! $this->get_billing_state() ) {
			$this->set_billing_state( $default['state'] );
		}

		if ( ! $this->get_shipping_state() ) {
			$this->set_shipping_state( $this->get_billing_state() );
		}
	}

	/**
	 * Gets the customers last order.
	 * @return WC_Order|false
	 */
	public function get_last_order() {
		global $wpdb;

		$last_order = $wpdb->get_var( "SELECT posts.ID
			FROM $wpdb->posts AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
			WHERE meta.meta_key = '_customer_user'
			AND   meta.meta_value = '" . esc_sql( $this->get_id() ) . "'
			AND   posts.post_type = 'shop_order'
			AND   posts.post_status IN ( '" . implode( "','", array_map( 'esc_sql', array_keys( wc_get_order_statuses() ) ) ) . "' )
			ORDER BY posts.ID DESC
		" );

		if ( $last_order ) {
			return wc_get_order( absint( $last_order ) );
		} else {
			return false;
		}
	}

	/**
	 * Return the number of orders this customer has.
	 * @since 2.7.0
	 * @return integer
	 */
	public function get_order_count() {
		global $wpdb;

		$count = $wpdb->get_var( "SELECT COUNT(*)
			FROM $wpdb->posts as posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			WHERE   meta.meta_key = '_customer_user'
			AND     posts.post_type = 'shop_order'
			AND     posts.post_status IN ( '" . implode( "','", array_map( 'esc_sql', array_keys( wc_get_order_statuses() ) ) ) . "' )
			AND     meta_value = '" . esc_sql( $this->get_id() ) . "'
		" );

		return absint( $count );
	}

	/**
	 * Return how much money this customer has spent.
	 * @since 2.7.0
	 * @return float
	 */
	public function get_total_spent() {
		global $wpdb;

		$spent = $wpdb->get_var( "SELECT SUM(meta2.meta_value)
			FROM $wpdb->posts as posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->postmeta} AS meta2 ON posts.ID = meta2.post_id
			WHERE   meta.meta_key       = '_customer_user'
			AND     meta.meta_value     = '" . esc_sql( $this->get_id() ) . "'
			AND     posts.post_type     = 'shop_order'
			AND     posts.post_status   IN ( 'wc-completed', 'wc-processing' )
			AND     meta2.meta_key      = '_order_total'
		" );

		if ( ! $spent ) {
			$spent = 0;
		}

		return wc_format_decimal( $spent, 2 );
	}

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

	/**
	 * Is customer VAT exempt?
	 * @return bool
	 */
	public function is_vat_exempt() {
		return $this->get_is_vat_exempt();
	}

	/**
	 * Has calculated shipping?
	 * @return bool
	 */
	public function has_calculated_shipping() {
		return $this->get_calculated_shipping();
	}

	/*
	 |--------------------------------------------------------------------------
	 | Getters
	 |--------------------------------------------------------------------------
	 | Methods for getting data from the customer object.
	 */

	/**
	 * Return the customer's username.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_username() {
		return $this->data['username'];
	}

	/**
	 * Return the customer's email.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_email() {
		return $this->data['email'];
	}

	/**
	 * Return customer's first name.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_first_name() {
		return $this->data['first_name'];
	}

	/**
	 * Return customer's last name.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_last_name() {
		return $this->data['last_name'];
	}

	/**
	 * Return customer's user role.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_role() {
		return $this->data['role'];
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
		return absint( $this->data['date_created'] );
	}

	/**
	 * Return the date this customer was last updated.
	 * @since 2.7.0
	 * @return integer
	 */
	public function get_date_modified() {
		return absint( $this->data['date_modified'] );
	}

	/**
	 * Gets customer billing first name.
	 * @return string
	 */
	public function get_billing_first_name() {
		return $this->data['billing']['first_name'];
	}

	/**
	 * Gets customer billing last name.
	 * @return string
	 */
	public function get_billing_last_name() {
		return $this->data['billing']['last_name'];
	}

	/**
	 * Gets customer billing company.
	 * @return string
	 */
	public function get_billing_company() {
		return $this->data['billing']['company'];
	}

	/**
	 * Gets billing phone.
	 * @return string
	 */
	public function get_billing_phone() {
		return $this->data['billing']['phone'];
	}

	/**
	 * Gets billing email.
	 * @return string
	 */
	public function get_billing_email() {
		return $this->data['billing']['email'];
	}

	/**
	 * Gets customer postcode.
	 * @return string
	 */
	public function get_billing_postcode() {
		return wc_format_postcode( $this->data['billing']['postcode'], $this->get_billing_country() );
	}

	/**
	 * Get customer city.
	 * @return string
	 */
	public function get_billing_city() {
		return $this->data['billing']['city'];
	}

	/**
	 * Get customer address.
	 * @return string
	 */
	public function get_billing_address() {
		return $this->data['billing']['address_1'];
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
		return $this->data['billing']['address_2'];
	}

	/**
	 * Get customer state.
	 * @return string
	 */
	public function get_billing_state() {
		return $this->data['billing']['state'];
	}

	/**
	 * Get customer country.
	 * @return string
	 */
	public function get_billing_country() {
		return $this->data['billing']['country'];
	}

	/**
	 * Gets customer shipping first name.
	 * @return string
	 */
	public function get_shipping_first_name() {
		return $this->data['shipping']['first_name'];
	}

	/**
	 * Gets customer shipping last name.
	 * @return string
	 */
	public function get_shipping_last_name() {
		return $this->data['shipping']['last_name'];
	}

	/**
	 * Gets customer shipping company.
	 * @return string
	 */
	public function get_shipping_company() {
		return $this->data['shipping']['company'];
	}

	/**
	 * Get customer's shipping state.
	 * @return string
	 */
	public function get_shipping_state() {
		return $this->data['shipping']['state'];
	}

	/**
	 * Get customer's shipping country.
	 * @return string
	 */
	public function get_shipping_country() {
		return $this->data['shipping']['country'];
	}

	/**
	 * Get customer's shipping postcode.
	 * @return string
	 */
	public function get_shipping_postcode() {
		return wc_format_postcode( $this->data['shipping']['postcode'], $this->get_shipping_country() );
	}

	/**
	 * Get customer's shipping city.
	 * @return string
	 */
	public function get_shipping_city() {
		return $this->data['shipping']['city'];
	}

	/**
	 * Get customer's shipping address.
	 * @return string
	 */
	public function get_shipping_address() {
		return $this->data['shipping']['address_1'];
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
		return $this->data['shipping']['address_2'];
	}

	/**
	 * Get if customer is VAT exempt?
	 * @since 2.7.0
	 * @return bool
	 */
	public function get_is_vat_exempt() {
		return $this->is_vat_exempt;
	}

	/**
	 * Has customer calculated shipping?
	 * @return bool
	 */
	public function get_calculated_shipping() {
		return $this->calculated_shipping;
	}

	/**
	 * Get taxable address.
	 * @return array
	 */
	public function get_taxable_address() {
		$tax_based_on = get_option( 'woocommerce_tax_based_on' );

		// Check shipping method at this point to see if we need special handling
		if ( true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && sizeof( array_intersect( wc_get_chosen_shipping_method_ids(), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
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
		if ( $this->get_id() ) {
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
		return (bool) $this->data['is_paying_customer'];
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
	 * @throws WC_Data_Exception
	 */
	public function set_username( $username ) {
		$this->data['username'] = $username;
	}

	/**
	 * Set customer's email.
	 * @since 2.7.0
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_email( $value ) {
		if ( $value && ! is_email( $value ) ) {
			$this->error( 'customer_invalid_email', __( 'Invalid email address', 'woocommerce' ) );
		}
		$this->data['email'] = sanitize_email( $value );
	}

	/**
	 * Set customer's first name.
	 * @since 2.7.0
	 * @param string $first_name
	 * @throws WC_Data_Exception
	 */
	public function set_first_name( $first_name ) {
		$this->data['first_name'] = $first_name;
	}

	/**
	 * Set customer's last name.
	 * @since 2.7.0
	 * @param string $last_name
	 * @throws WC_Data_Exception
	 */
	public function set_last_name( $last_name ) {
		$this->data['last_name'] = $last_name;
	}

	/**
	 * Set customer's user role(s).
	 * @since 2.7.0
	 * @param mixed $role
	 * @throws WC_Data_Exception
	 */
	public function set_role( $role ) {
		global $wp_roles;

		if ( $role && ! empty( $wp_roles->roles ) && ! in_array( $role, array_keys( $wp_roles->roles ) ) ) {
			$this->error( 'customer_invalid_role', __( 'Invalid role', 'woocommerce' ) );
		}
		$this->data['role'] = $role;
	}

	/**
	 * Set customer's password.
	 * @since 2.7.0
	 * @param string $password
	 * @throws WC_Data_Exception
	 */
	public function set_password( $password ) {
		$this->password = wc_clean( $password );
	}

	/**
	 * Set the date this customer was last updated.
	 * @since 2.7.0
	 * @param integer $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_modified( $timestamp ) {
		$this->data['date_modified'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set the date this customer was last updated.
	 * @since 2.7.0
	 * @param integer $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_created( $timestamp ) {
		$this->data['date_created'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set customer address to match shop base address.
	 * @since 2.7.0
	 * @throws WC_Data_Exception
	 */
	public function set_billing_address_to_base() {
		$base = wc_get_customer_default_location();
		$this->data['billing']['country']  = $base['country'];
		$this->data['billing']['state']    = $base['state'];
		$this->data['billing']['postcode'] = '';
		$this->data['billing']['city']     = '';
	}

	/**
	 * Set customer shipping address to base address.
	 * @since 2.7.0
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_address_to_base() {
		$base = wc_get_customer_default_location();
		$this->data['shipping']['country']  = $base['country'];
		$this->data['shipping']['state']    = $base['state'];
		$this->data['shipping']['postcode'] = '';
		$this->data['shipping']['city']     = '';
	}

	/**
	 * Sets all shipping info at once.
	 * @param string $country
	 * @param string $state
	 * @param string $postcode
	 * @param string $city
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_location( $country, $state = '', $postcode = '', $city = '' ) {
		$this->data['shipping']['country']  = $country;
		$this->data['shipping']['state']    = $state;
		$this->data['shipping']['postcode'] = $postcode;
		$this->data['shipping']['city']     = $city;
	}

	/**
	 * Sets all address info at once.
	 * @param string $country
	 * @param string $state
	 * @param string $postcode
	 * @param string $city
	 * @throws WC_Data_Exception
	 */
	public function set_billing_location( $country, $state, $postcode = '', $city = '' ) {
		$this->data['billing']['country']  = $country;
		$this->data['billing']['state']    = $state;
		$this->data['billing']['postcode'] = $postcode;
		$this->data['billing']['city']     = $city;
	}

	/**
	 * Set billing first name.
	 * @return string
	 * @throws WC_Data_Exception
	 */
	public function set_billing_first_name( $value ) {
		$this->data['billing']['first_name'] = $value;
	}

	/**
	 * Set billing last name.
	 * @return string
	 * @throws WC_Data_Exception
	 */
	public function set_billing_last_name( $value ) {
		$this->data['billing']['last_name'] = $value;
	}

	/**
	 * Set billing company.
	 * @return string
	 * @throws WC_Data_Exception
	 */
	public function set_billing_company( $value ) {
		$this->data['billing']['company'] = $value;
	}

	/**
	 * Set billing phone.
	 * @return string
	 * @throws WC_Data_Exception
	 */
	public function set_billing_phone( $value ) {
		$this->data['billing']['phone'] = $value;
	}

	/**
	 * Set billing email.
	 * @param string $value
	 * @return string
	 * @throws WC_Data_Exception
	 */
	public function set_billing_email( $value ) {
		if ( $value && ! is_email( $value ) ) {
			$this->error( 'customer_invalid_billing_email', __( 'Invalid billing email address', 'woocommerce' ) );
		}
		$this->data['billing']['email'] = sanitize_email( $value );
	}

	/**
	 * Set customer country.
	 * @param mixed $country
	 * @throws WC_Data_Exception
	 */
	public function set_billing_country( $country ) {
		$this->data['billing']['country'] = $country;
	}

	/**
	 * Set customer state.
	 * @param mixed $state
	 * @throws WC_Data_Exception
	 */
	public function set_billing_state( $state ) {
		$this->data['billing']['state'] = $state;
	}

	/**
	 * Sets customer postcode.
	 * @param mixed $postcode
	 * @throws WC_Data_Exception
	 */
	public function set_billing_postcode( $postcode ) {
		$this->data['billing']['postcode'] = $postcode;
	}

	/**
	 * Sets customer city.
	 * @param mixed $city
	 * @throws WC_Data_Exception
	 */
	public function set_billing_city( $city ) {
		$this->data['billing']['city'] = $city;
	}

	/**
	 * Set customer address.
	 * @param mixed $address
	 * @throws WC_Data_Exception
	 */
	public function set_billing_address( $address ) {
		$this->data['billing']['address_1'] = $address;
	}

	/**
	 * Set customer address.
	 * @param mixed $address
	 * @throws WC_Data_Exception
	 */
	public function set_billing_address_1( $address ) {
		$this->set_billing_address( $address );
	}

	/**
	 * Set customer's second address.
	 * @param mixed $address
	 * @throws WC_Data_Exception
	 */
	public function set_billing_address_2( $address ) {
		$this->data['billing']['address_2'] = $address;
	}

	/**
	 * Sets customer shipping first name.
	 * @param string $first_name
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_first_name( $first_name ) {
		$this->data['shipping']['first_name'] = $first_name;
	}

	/**
	 * Sets customer shipping last name.
	 * @param string $last_name
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_last_name( $last_name ) {
		$this->data['shipping']['last_name'] = $last_name;
	}

	/**
	 * Sets customer shipping company.
	 * @param string $company.
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_company( $company ) {
		$this->data['shipping']['company'] = $company;
	}

	/**
	 * Set shipping country.
	 * @param string $country
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_country( $country ) {
		$this->data['shipping']['country'] = $country;
	}

	/**
	 * Set shipping state.
	 * @param string $state
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_state( $state ) {
		$this->data['shipping']['state'] = $state;
	}

	/**
	 * Set shipping postcode.
	 * @param string $postcode
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_postcode( $postcode ) {
		$this->data['shipping']['postcode'] = $postcode;
	}

	/**
	 * Sets shipping city.
	 * @param string $city
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_city( $city ) {
		$this->data['shipping']['city'] = $city;
	}

	/**
	 * Set shipping address.
	 * @param string $address
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_address( $address ) {
		$this->data['shipping']['address_1'] = $address;
	}

	/**
	 * Set customer shipping address.
	 * @param mixed $address
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_address_1( $address ) {
		$this->set_shipping_address( $address );
	}

	/**
	 * Set second shipping address.
	 * @param string $address
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_address_2( $address ) {
		$this->data['shipping']['address_2'] = $address;
	}

	/**
	 * Set if the user a paying customer.
	 * @since 2.7.0
	 * @param boolean $is_paying_customer
	 * @throws WC_Data_Exception
	 */
	function set_is_paying_customer( $is_paying_customer ) {
		$this->data['is_paying_customer'] = (bool) $is_paying_customer;
	}

	/**
	 * Set if customer has tax exemption.
	 * @param bool $is_vat_exempt
	 */
	public function set_is_vat_exempt( $is_vat_exempt ) {
		$this->is_vat_exempt = (bool) $is_vat_exempt;
	}

	/**
	 * Calculated shipping?
	 * @param boolean $calculated
	 */
	public function set_calculated_shipping( $calculated = true ) {
		$this->calculated_shipping = (bool) $calculated;
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
		$customer_id = wc_create_new_customer( $this->get_email(), $this->get_username(), $this->password );

		if ( ! is_wp_error( $customer_id ) ) {
			$this->set_id( $customer_id );
			update_user_meta( $this->get_id(), 'billing_first_name', $this->get_billing_first_name() );
			update_user_meta( $this->get_id(), 'billing_last_name', $this->get_billing_last_name() );
			update_user_meta( $this->get_id(), 'billing_company', $this->get_billing_company() );
			update_user_meta( $this->get_id(), 'billing_phone', $this->get_billing_phone() );
			update_user_meta( $this->get_id(), 'billing_email', $this->get_billing_email() );
			update_user_meta( $this->get_id(), 'billing_postcode', $this->get_billing_postcode() );
			update_user_meta( $this->get_id(), 'billing_city', $this->get_billing_city() );
			update_user_meta( $this->get_id(), 'billing_address_1', $this->get_billing_address() );
			update_user_meta( $this->get_id(), 'billing_address_2', $this->get_billing_address_2() );
			update_user_meta( $this->get_id(), 'billing_state', $this->get_billing_state() );
			update_user_meta( $this->get_id(), 'billing_country', $this->get_billing_country() );
			update_user_meta( $this->get_id(), 'shipping_first_name', $this->get_shipping_first_name() );
			update_user_meta( $this->get_id(), 'shipping_last_name', $this->get_shipping_last_name() );
			update_user_meta( $this->get_id(), 'shipping_company', $this->get_shipping_company() );
			update_user_meta( $this->get_id(), 'shipping_postcode', $this->get_shipping_postcode() );
			update_user_meta( $this->get_id(), 'shipping_city', $this->get_shipping_city() );
			update_user_meta( $this->get_id(), 'shipping_address_1', $this->get_shipping_address() );
			update_user_meta( $this->get_id(), 'shipping_address_2', $this->get_shipping_address_2() );
			update_user_meta( $this->get_id(), 'shipping_state', $this->get_shipping_state() );
			update_user_meta( $this->get_id(), 'shipping_country', $this->get_shipping_country() );
			update_user_meta( $this->get_id(), 'paying_customer', $this->get_is_paying_customer() );
			update_user_meta( $this->get_id(), 'last_update',  $this->get_date_modified() );
			update_user_meta( $this->get_id(), 'first_name', $this->get_first_name() );
			update_user_meta( $this->get_id(), 'last_name', $this->get_last_name() );
			wp_update_user( array( 'ID' => $this->get_id(), 'role' => $this->get_role() ) );
			$wp_user = new WP_User( $this->get_id() );
			$this->set_date_created( strtotime( $wp_user->user_registered ) );
			$this->set_date_modified( get_user_meta( $this->get_id(), 'last_update', true ) );
			$this->read_meta_data();
		}
	}

	/**
	 * Callback which flattens post meta (gets the first value).
	 * @param  array $value
	 * @return mixed
	 */
	private function flatten_post_meta( $value ) {
		return is_array( $value ) ? current( $value ) : $value;
	}

	/**
	 * Read a customer from the database.
	 * @since 2.7.0
	 * @param integer $id
	 */
	public function read( $id ) {
		global $wpdb;

		// User object is required.
		if ( ! $id || ! ( $user_object = get_user_by( 'id', $id ) ) || empty( $user_object->ID ) ) {
			$this->set_id( 0 );
			return;
		}

		// Only users on this site should be read.
		if ( is_multisite() && ! is_user_member_of_blog( $id ) ) {
			$this->set_id( 0 );
			return;
		}

		$this->set_id( $user_object->ID );
		$this->set_props( array_map( array( $this, 'flatten_post_meta' ), get_user_meta( $id ) ) );
		$this->set_props( array(
			'is_paying_customer' => get_user_meta( $id, 'paying_customer', true ),
			'email'              => $user_object->user_email,
			'username'           => $user_object->user_login,
			'date_created'       => strtotime( $user_object->user_registered ),
			'date_modified'      => get_user_meta( $id, 'last_update', true ),
			'role'               => ! empty( $user_object->roles[0] ) ? $user_object->roles[0] : 'customer',
		) );
		$this->read_meta_data();
	}

	/**
	 * Update a customer.
	 * @since 2.7.0
	 */
	public function update() {
		wp_update_user( array( 'ID' => $this->get_id(), 'user_email' => $this->get_email() ) );
		// Only update password if a new one was set with set_password
		if ( ! empty( $this->password ) ) {
			wp_update_user( array( 'ID' => $this->get_id(), 'user_pass' => $this->password ) );
			$this->password = '';
		}

		update_user_meta( $this->get_id(), 'billing_first_name', $this->get_billing_first_name() );
		update_user_meta( $this->get_id(), 'billing_last_name', $this->get_billing_last_name() );
		update_user_meta( $this->get_id(), 'billing_company', $this->get_billing_company() );
		update_user_meta( $this->get_id(), 'billing_phone', $this->get_billing_phone() );
		update_user_meta( $this->get_id(), 'billing_email', $this->get_billing_email() );
		update_user_meta( $this->get_id(), 'billing_postcode', $this->get_billing_postcode() );
		update_user_meta( $this->get_id(), 'billing_city', $this->get_billing_city() );
		update_user_meta( $this->get_id(), 'billing_address_1', $this->get_billing_address() );
		update_user_meta( $this->get_id(), 'billing_address_2', $this->get_billing_address_2() );
		update_user_meta( $this->get_id(), 'billing_state', $this->get_billing_state() );
		update_user_meta( $this->get_id(), 'shipping_first_name', $this->get_shipping_first_name() );
		update_user_meta( $this->get_id(), 'shipping_last_name', $this->get_shipping_last_name() );
		update_user_meta( $this->get_id(), 'shipping_company', $this->get_shipping_company() );
		update_user_meta( $this->get_id(), 'billing_country', $this->get_billing_country() );
		update_user_meta( $this->get_id(), 'shipping_first_name', $this->get_shipping_first_name() );
		update_user_meta( $this->get_id(), 'shipping_last_name', $this->get_shipping_last_name() );
		update_user_meta( $this->get_id(), 'shipping_company', $this->get_shipping_company() );
		update_user_meta( $this->get_id(), 'shipping_postcode', $this->get_shipping_postcode() );
		update_user_meta( $this->get_id(), 'shipping_city', $this->get_shipping_city() );
		update_user_meta( $this->get_id(), 'shipping_address_1', $this->get_shipping_address() );
		update_user_meta( $this->get_id(), 'shipping_address_2', $this->get_shipping_address_2() );
		update_user_meta( $this->get_id(), 'shipping_state', $this->get_shipping_state() );
		update_user_meta( $this->get_id(), 'shipping_country', $this->get_shipping_country() );
		update_user_meta( $this->get_id(), 'paying_customer', $this->get_is_paying_customer() );
		update_user_meta( $this->get_id(), 'first_name', $this->get_first_name() );
		update_user_meta( $this->get_id(), 'last_name', $this->get_last_name() );
		wp_update_user( array( 'ID' => $this->get_id(), 'role' => $this->get_role() ) );
		$this->set_date_modified( get_user_meta( $this->get_id(), 'last_update', true ) );
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
		return wp_delete_user( $this->get_id() );
	}

	/**
	 * Delete a customer and reassign posts..
	 *
	 * @param int $reassign Reassign posts and links to new User ID.
	 * @since 2.7.0
	 */
	public function delete_and_reassign( $reassign = null ) {
		if ( ! $this->get_id() ) {
			return;
		}
		return wp_delete_user( $this->get_id(), $reassign );
	}

	/**
	 * Save data. Create when creating a new user/class, update when editing
	 * an existing user, and save session when working on a logged out guest
	 * session.
	 * @since 2.7.0
	 */
	public function save() {
		if ( $this->is_session ) {
			$this->save_to_session();
		} elseif ( ! $this->get_id() ) {
			$this->create();
		} else {
			$this->update();
		}
	}

	/**
	 * Saves data to the session only (does not overwrite DB values).
	 * @since 2.7.0
	 */
	public function save_to_session() {
		$data = array();
		foreach ( $this->session_keys as $session_key ) {
			$function_key = $session_key;
			if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
				$session_key = str_replace( 'billing_', '', $session_key );
			}
			$data[ $session_key ] = $this->{"get_$function_key"}();
		}
		if ( WC()->session->get( 'customer' ) !== $data ) {
			WC()->session->set( 'customer', $data );
		}
	}

	/**
	 * Callback to remove unwanted meta data.
	 *
	 * @param object $meta
	 * @return bool
	 */
	protected function exclude_internal_meta_keys( $meta ) {
		global $wpdb;
		return ! in_array( $meta->meta_key, $this->get_internal_meta_keys() )
			&& 0 !== strpos( $meta->meta_key, 'closedpostboxes_' )
			&& 0 !== strpos( $meta->meta_key, 'metaboxhidden_' )
			&& 0 !== strpos( $meta->meta_key, 'manageedit-' )
			&& ! strstr( $meta->meta_key, $wpdb->prefix );
	}

}
