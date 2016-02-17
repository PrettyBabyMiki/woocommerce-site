<?php
/**
 * WooCommerce Shipping Settings
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Settings_Shipping' ) ) :

/**
 * WC_Settings_Shipping.
 */
class WC_Settings_Shipping extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shipping';
		$this->label = __( 'Shipping', 'woocommerce' );
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Add this page to settings.
	 */
	public function add_settings_page( $pages ) {
		return wc_shipping_enabled() ? parent::add_settings_page( $pages ) : $pages;
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''        => __( 'Shipping Zones', 'woocommerce' ),
			'options' => __( 'Shipping Options', 'woocommerce' ),
			'classes' => __( 'Shipping Classes', 'woocommerce' )
		);

		if ( ! defined( 'WC_INSTALLING' ) ) {
			// Load shipping methods so we can show any global options they may have
			$shipping_methods = WC()->shipping->load_shipping_methods();

			foreach ( $shipping_methods as $method ) {
				if ( ! $method->has_settings() ) {
					continue;
				}
				$title = empty( $method->method_title ) ? ucfirst( $method->id ) : $method->method_title;
				$sections[ strtolower( get_class( $method ) ) ] = esc_html( $title );
			}
		}

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters( 'woocommerce_shipping_settings', array(

			array( 'title' => __( 'Shipping Options', 'woocommerce' ), 'type' => 'title', 'id' => 'shipping_options' ),

			array(
				'title'         => __( 'Calculations', 'woocommerce' ),
				'desc'          => __( 'Enable the shipping calculator on the cart page', 'woocommerce' ),
				'id'            => 'woocommerce_enable_shipping_calc',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'autoload'      => false
			),

			array(
				'desc'          => __( 'Hide shipping costs until an address is entered', 'woocommerce' ),
				'id'            => 'woocommerce_shipping_cost_requires_address',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'autoload'      => false
			),

			array(
				'title'   => __( 'Shipping Destination', 'woocommerce' ),
				'desc'    => __( 'This controls which shipping address is used by default.', 'woocommerce' ),
				'id'      => 'woocommerce_ship_to_destination',
				'default' => 'billing',
				'type'    => 'radio',
				'options' => array(
					'shipping'     => __( 'Default to customer shipping address', 'woocommerce' ),
					'billing'      => __( 'Default to customer billing address', 'woocommerce' ),
					'billing_only' => __( 'Force shipping to the customer billing address', 'woocommerce' ),
				),
				'autoload'        => false,
				'desc_tip'        =>  true,
				'show_if_checked' => 'option',
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_options' ),

		) );

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section, $hide_save_button;

		// Load shipping methods so we can show any global options they may have
		$shipping_methods = WC()->shipping->load_shipping_methods();

		if ( 'options' === $current_section ) {
			$settings = $this->get_settings();
			WC_Admin_Settings::output_fields( $settings );
			return;
		} elseif ( 'classes' === $current_section ) {
			$hide_save_button = true;
			$this->output_shipping_class_screen();
			return;
		} else {
			foreach ( $shipping_methods as $method ) {
				if ( strtolower( get_class( $method ) ) === strtolower( $current_section ) && $method->has_settings() ) {
					$method->admin_options();
					return;
				}
			}
		}

		// Default to zones screen
		$hide_save_button = true;
		$this->output_zones_screen();
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		switch ( $current_section ) {
			case 'options' :
				WC_Admin_Settings::save_fields( $this->get_settings() );
			break;
			case 'classes' :
			case '' :
			break;
			default :
				$wc_shipping = WC_Shipping::instance();

				foreach ( $wc_shipping->get_shipping_methods() as $method_id => $method ) {
					if ( $current_section === sanitize_title( get_class( $method ) ) ) {
						do_action( 'woocommerce_update_options_' . $this->id . '_' . $method->id );
					}
				}
			break;
		}

		// Increments the transient version to invalidate cache
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Handles output of the shipping zones page in admin.
	 */
	protected function output_zones_screen() {
		if ( isset( $_REQUEST['zone_id'] ) ) {
			$this->zone_methods_screen( absint( $_REQUEST['zone_id'] ) );
		} elseif ( isset( $_REQUEST['instance_id'] ) ) {
			$this->instance_settings_screen( absint( $_REQUEST['instance_id'] ) );
		} else {
			$this->zones_screen();
		}
	}

	/**
	 * Show method for a zone
	 * @param  int $zone_id
	 */
	protected function zone_methods_screen( $zone_id ) {
		$wc_shipping      = WC_Shipping      ::instance();
		$zone             = WC_Shipping_Zones::get_zone( $zone_id );
		$shipping_methods = $wc_shipping ->get_shipping_methods();

		if ( ! $zone ) {
			wp_die( __( 'Zone does not exist!', 'woocommerce' ) );
		}

		wp_localize_script( 'wc-shipping-zone-methods', 'shippingZoneMethodsLocalizeScript', array(
			'methods'                 => $zone->get_shipping_methods(),
			'zone_id'                 => $zone->get_zone_id(),
			'wc_shipping_zones_nonce' => wp_create_nonce( 'wc_shipping_zones_nonce' ),
			'strings'                 => array(
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
				'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce' ),
				'add_method_failed'       => __( 'Shipping method could not be added. Please retry.', 'woocommerce' ),
				'yes'                     => __( 'Yes', 'woocommerce' ),
			),
		) );
		wp_enqueue_script( 'wc-shipping-zone-methods' );

		include_once( 'views/html-admin-page-shipping-zone-methods.php' );
	}

	/**
	 * Show zones
	 */
	protected function zones_screen() {
		$allowed_countries = WC()->countries->get_allowed_countries();
        $continents        = WC()->countries->get_continents();

		wp_localize_script( 'wc-shipping-zones', 'shippingZonesLocalizeScript', array(
            'zones'         => WC_Shipping_Zones::get_zones(),
            'default_zone'  => array(
				'zone_id'    => 0,
				'zone_name'  => '',
				'zone_order' => null,
			),
			'wc_shipping_zones_nonce'  => wp_create_nonce( 'wc_shipping_zones_nonce' ),
			'strings'       => array(
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
				'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce' ),
				'no_methods'              => '<a href="#" class="add_shipping_method button">' . __( 'Add Shipping Method', 'woocommerce' ) . '</a>',
				'add_another_method'      => '<a href="#" class="add_shipping_method button">' . __( 'Add Shipping Method', 'woocommerce' ) . '</a>'
			),
		) );
		wp_enqueue_script( 'wc-shipping-zones' );

		include_once( 'views/html-admin-page-shipping-zones.php' );
	}

	/**
	 * Show instance settings
	 * @param  int $instance_id
	 */
	protected function instance_settings_screen( $instance_id ) {
		$zone            = WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id );
		$shipping_method = WC_Shipping_Zones::get_shipping_method( $instance_id );

		if ( ! $shipping_method ) {
			wp_die( __( 'Invalid shipping method!', 'woocommerce' ) );
		}
		if ( ! $zone ) {
			wp_die( __( 'Zone does not exist!', 'woocommerce' ) );
		}
		if ( ! $shipping_method->has_settings() ) {
			wp_die( __( 'This shipping method does not have any settings to configure.', 'woocommerce' ) );
		}

		if ( ! empty( $_POST['save_method'] ) ) {

			if ( empty( $_POST['woocommerce_save_method_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_save_method_nonce'], 'woocommerce_save_method' )) {
				echo '<div class="updated error"><p>' . __( 'Edit failed. Please try again.', 'woocommerce' ) . '</p></div>';
			}

			$shipping_method->process_admin_options();
			$shipping_method->display_errors();
		}

		include_once( 'views/html-admin-page-shipping-zones-instance.php' );
	}

	/**
	 * Handles output of the shipping class settings screen.
	 */
	protected function output_shipping_class_screen() {
		$wc_shipping = WC_Shipping::instance();
		wp_localize_script( 'wc-shipping-classes', 'shippingClassesLocalizeScript', array(
            'classes'         => $wc_shipping->get_shipping_classes(),
            'default_shipping_class'  => array(
				'term_id'     => 0,
				'name'        => '',
				'description' => '',
			),
			'wc_shipping_classes_nonce'  => wp_create_nonce( 'wc_shipping_classes_nonce' ),
			'strings'       => array(
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
				'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce' )
			),
		) );
		wp_enqueue_script( 'wc-shipping-classes' );

		include_once( 'views/html-admin-page-shipping-classes.php' );
	}
}

endif;

return new WC_Settings_Shipping();
