<?php
/**
 * Setup Wizard Class
 *
 * Takes new users through some basic steps to setup their store.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Setup_Wizard class.
 */
class WC_Admin_Setup_Wizard {

	/** @var string Current Step */
	private $step   = '';

	/** @var array Steps for the setup wizard */
	private $steps  = array();

	/** @var array Tweets user can optionally send after install */
	private $tweets = array(
		'Someone give me woo-t, I just set up a new store with #WordPress and @WooCommerce!',
		'Someone give me high five, I just set up a new store with #WordPress and @WooCommerce!',
	);

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		if ( apply_filters( 'woocommerce_enable_setup_wizard', true ) && current_user_can( 'manage_woocommerce' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'setup_wizard' ) );
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'wc-setup', '' );
	}

	/**
	 * Show the setup wizard.
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'wc-setup' !== $_GET['page'] ) {
			return;
		}
		$default_steps = array(
			'store_setup' => array(
				'name'    => __( 'Store setup', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_store_setup' ),
				'handler' => array( $this, 'wc_setup_store_setup_save' ),
			),
			'payments' => array(
				'name'    => __( 'Payments', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_payments' ),
				'handler' => array( $this, 'wc_setup_payments_save' ),
			),
			'shipping' => array(
				'name'    => __( 'Shipping', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_shipping' ),
				'handler' => array( $this, 'wc_setup_shipping_save' ),
			),
			'theme' => array(
				'name'    => __( 'Theme', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_theme' ),
				'handler' => array( $this, 'wc_setup_theme_save' ),
			),
			'activate' => array(
				'name'    => __( 'Activate', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_activate' ),
				'handler' => '',
			),
			'next_steps' => array(
				'name'    => __( 'Ready!', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_ready' ),
				'handler' => '',
			),
		);

		// Hide storefront step if using a WooCommerce theme or user cannot modify themes.
		if ( ! current_user_can( 'install_themes' ) || ! current_user_can( 'switch_themes' ) || is_multisite() || current_theme_supports( 'woocommerce' ) ) {
			unset( $default_steps['theme'] );
		}

		$this->steps = apply_filters( 'woocommerce_setup_wizard_steps', $default_steps );
		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );
		$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.0' );
		wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), WC_VERSION );
		wp_localize_script( 'wc-enhanced-select', 'wc_enhanced_select_params', array(
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'search_products_nonce'     => wp_create_nonce( 'search-products' ),
			'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
		) );
		wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		wp_enqueue_style( 'wc-setup', WC()->plugin_url() . '/assets/css/wc-setup.css', array( 'dashicons', 'install' ), WC_VERSION );

		wp_register_script( 'wc-setup', WC()->plugin_url() . '/assets/js/admin/wc-setup' . $suffix . '.js', array( 'jquery', 'wc-enhanced-select', 'jquery-blockui' ), WC_VERSION );
		wp_localize_script( 'wc-setup', 'wc_setup_params', array(
			'locale_info' => json_encode( include( WC()->plugin_path() . '/i18n/locale-info.php' ) ),
		) );

		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	/**
	 * Get the URL for the next step's screen.
	 * @param string step   slug (default: current step)
	 * @return string       URL for next step if a next step exists.
	 *                      Admin URL if it's the last step.
	 *                      Empty string on failure.
	 * @since 3.0.0
	 */
	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ] );
	}

	/**
	 * Setup Wizard Header.
	 */
	public function setup_wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php esc_html_e( 'WooCommerce &rsaquo; Setup Wizard', 'woocommerce' ); ?></title>
			<?php wp_print_scripts( 'wc-setup' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="wc-setup wp-core-ui">
			<h1 id="wc-logo"><a href="https://woocommerce.com/"><img src="<?php echo WC()->plugin_url(); ?>/assets/images/woocommerce_logo.png" alt="WooCommerce" /></a></h1>
		<?php
	}

	/**
	 * Setup Wizard Footer.
	 */
	public function setup_wizard_footer() {
		?>
			<?php if ( 'store_setup' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Not right now', 'woocommerce' ); ?></a>
			<?php elseif ( 'next_steps' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Return to the WordPress Dashboard', 'woocommerce' ); ?></a>
			<?php elseif ( 'activate' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( $this->get_next_step_link() ); ?>"><?php esc_html_e( 'Skip this step', 'woocommerce' ); ?></a>
			<?php endif; ?>
			</body>
		</html>
		<?php
	}

	/**
	 * Output the steps.
	 */
	public function setup_wizard_steps() {
		$output_steps = $this->steps;
		?>
		<ol class="wc-setup-steps">
			<?php foreach ( $output_steps as $step_key => $step ) : ?>
				<li class="<?php
					if ( $step_key === $this->step ) {
						echo 'active';
					} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
						echo 'done';
					}
				?>"><?php echo esc_html( $step['name'] ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	public function setup_wizard_content() {
		echo '<div class="wc-setup-content">';
		call_user_func( $this->steps[ $this->step ]['view'], $this );
		echo '</div>';
	}

	/**
	 * Initial "store setup" step.
	 * Location, product type, page setup, and tracking opt-in.
	 */
	public function wc_setup_store_setup() {
		$address        = WC()->countries->get_base_address();
		$address_2      = WC()->countries->get_base_address_2();
		$city           = WC()->countries->get_base_city();
		$state          = WC()->countries->get_base_state();
		$country        = WC()->countries->get_base_country();
		$postcode       = WC()->countries->get_base_postcode();
		$currency       = get_option( 'woocommerce_currency', 'GBP' );

		if ( empty( $country ) ) {
			$user_location = WC_Geolocation::geolocate_ip();
			$country       = $user_location['country'];
			$state         = $user_location['state'];
		}

		?>
		<h1><?php esc_html_e( 'Welcome to the world of WooCommerce!', 'woocommerce' ); ?></h1>
		<form method="post">
			<p><?php esc_html_e( "This quick setup wizard will help you configure the basic settings, and shouldn't take longer than five minutes. To get started, we need to know a few details about your store.", 'woocommerce' ); ?></p>
			<div>
				<label for="store_country"><?php esc_html_e( 'Where is your store based?', 'woocommerce' ); ?></label>
				<select id="store_country" name="store_country" style="width:100%;" required data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'woocommerce' ); ?>" class="wc-enhanced-select">
					<option value=""><?php esc_html_e( 'Choose a country&hellip;', 'woocommerce' ); ?></option>
				<?php foreach ( WC()->countries->get_countries() as $country_code => $country_name ) : ?>
					<option value="<?php echo esc_attr( $country_code ); ?>" <?php selected( $country_code, $country ); ?>><?php echo esc_html( $country_name ); ?></option>
				<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="store_address"><?php esc_html_e( 'Address', 'woocommerce' ); ?></label>
				<input type="text" id="store_address" name="store_address" style="width:100%;" value="<?php echo esc_attr( $address ); ?>" />
				<input type="text" id="store_address_2" name="store_address_2" style="width:100%;" value="<?php echo esc_attr( $address_2 ); ?>" />

			</div>
			<div style="width:33%; float:left;">
				<label for="store_city"><?php esc_html_e( 'City', 'woocommerce' ); ?></label>
				<input type="text" id="store_city" name="store_city" value="<?php echo esc_attr( $city ); ?>" />
			</div>
			<div style="width:33%; float:left;">
				<label for="store_state"><?php esc_html_e( 'State', 'woocommerce' ); ?></label>
				<select id="store_state" name="store_state" style="width:100%;" required data-placeholder="<?php esc_attr_e( 'Choose a state&hellip;', 'woocommerce' ); ?>" class="wc-enhanced-select">
					<option value=""><?php esc_html_e( 'Choose a state&hellip;', 'woocommerce' ); ?></option>
				<?php foreach ( WC()->countries->get_states( $country ) as $state_code => $state_name ) : ?>
					<option value="<?php echo esc_attr( $state_code ); ?>" <?php selected( $state_code, $state ); ?>><?php echo esc_html( $state_name ); ?></option>
				<?php endforeach; ?>
				</select>
			</div>
			<div style="width:33%; float:left;">
				<label for="store_postcode"><?php esc_html_e( 'Postcode / ZIP', 'woocommerce' ); ?></label>
				<input type="text" id="store_postcode" name="store_postcode" value="<?php echo esc_attr( $postcode ); ?>" />
			</div>
			<div>
				<label for="currency_code"><?php esc_html_e( 'Store currency', 'woocommerce' ); ?></label>
				<select id="currency_code" name="currency_code" style="width:100%;" data-placeholder="<?php esc_attr_e( 'Choose a currency&hellip;', 'woocommerce' ); ?>" class="wc-enhanced-select">
					<option value=""><?php esc_html_e( 'Choose a currency&hellip;', 'woocommerce' ); ?></option>
				<?php foreach ( get_woocommerce_currencies() as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $currency, $code ); ?>><?php printf( esc_html__( '%1$s (%2$s)', 'woocommerce' ), $name, get_woocommerce_currency_symbol( $code ) ); ?></option>
				<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="product_type"><?php esc_html_e( 'What type of product do you plan to sell?', 'woocommerce' ); ?></label>
				<select id="product_type" name="product_type" style="width:100%;" required data-placeholder="<?php esc_attr_e( 'Please choose one&hellip;', 'woocommerce' ); ?>" class="wc-enhanced-select">
					<option value=""><?php esc_html_e( 'Please choose one&hellip;', 'woocommerce' ); ?></option>
					<option value="physical"><?php esc_html_e( 'I plan to sell physical products', 'woocommerce' ); ?></option>
					<option value="virtual"><?php esc_html_e( 'I plan to sell digital products', 'woocommerce' ); ?></option>
					<option value="both"><?php esc_html_e( 'I plan to sell both', 'woocommerce' ); ?></option>
				</select>
			</div>
		<?php if ( 'unknown' === get_option( 'woocommerce_allow_tracking', 'unknown' ) ) : ?>
			<div>
				<input type="checkbox" id="wc_tracker_optin" name="wc_tracker_optin" value="yes" checked />
				<label for="wc_tracker_optin"><?php _e( 'Allow WooCommerce to collect non-sensitive diagnostic data and usage information.', 'woocommerce' ); ?></label>
			</div>
		<?php endif; ?>
			<p><?php esc_html_e( 'Transforming your site into an online store requires WooCommerce to create a few pages for you. These pages are: shop, cart, my account, and checkout.', 'woocommerce' ); ?></p>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( "Let's go!", 'woocommerce' ); ?>" name="save_step" />
			</p>
			<?php wp_nonce_field( 'wc-setup' ); ?>
		</form>
		<?php
	}

	/**
	 * Save initial store settings.
	 */
	public function wc_setup_store_setup_save() {
		check_admin_referer( 'wc-setup' );

		$address        = sanitize_text_field( $_POST['store_address'] );
		$address_2      = sanitize_text_field( $_POST['store_address_2'] );
		$city           = sanitize_text_field( $_POST['store_city'] );
		$store_country  = sanitize_text_field( $_POST['store_country'] );
		$store_state    = sanitize_text_field( $_POST['store_state'] );
		$postcode       = sanitize_text_field( $_POST['store_postcode'] );
		$currency_code  = sanitize_text_field( $_POST['currency_code'] );
		$product_type   = sanitize_text_field( $_POST['product_type'] );
		$tracking       = isset( $_POST['wc_tracker_optin'] ) && ( 'yes' === sanitize_text_field( $_POST['wc_tracker_optin'] ) );

		update_option( 'woocommerce_store_address', $address );
		update_option( 'woocommerce_store_address_2', $address_2 );
		update_option( 'woocommerce_store_city', $city );
		update_option( 'woocommerce_default_country', sprintf( '%s:%s', $store_country, $store_state ) );
		update_option( 'woocommerce_store_postcode', $postcode );
		update_option( 'woocommerce_currency', $currency_code );
		update_option( 'woocommerce_product_type', $product_type );

		if ( $tracking ) {
			update_option( 'woocommerce_allow_tracking', 'yes' );
			WC_Tracker::send_tracking_data( true );
		} else {
			update_option( 'woocommerce_allow_tracking', 'no' );
		}

		WC_Install::create_pages();
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Tout WooCommerce Services for North American stores.
	 */
	protected function wc_setup_wcs_tout() {
		$base_location = wc_get_base_location();

		if ( false === $base_location['country'] ) {
			$base_location = WC_Geolocation::geolocate_ip();
		}

		if ( ! in_array( $base_location['country'], array( 'US', 'CA' ), true ) ) {
			return;
		}

		$default_content = array(
			'title'       => __( 'Enable WooCommerce Shipping (recommended)', 'woocommerce' ),
			'description' => __( 'Print labels and get discounted USPS shipping rates, right from your WooCommerce dashboard. Powered by WooCommerce Services.', 'woocommerce' ),
		);

		switch ( $base_location['country'] ) {
			case 'CA':
				$local_content = array(
					'title'       => __( 'Enable WooCommerce Shipping (recommended)', 'woocommerce' ),
					'description' => __( 'Display live rates from Canada Post at checkout to make shipping a breeze. Powered by WooCommerce Services.', 'woocommerce' ),
				);
				break;
			default:
				$local_content = array();
		}

		$content = wp_parse_args( $local_content, $default_content );
		?>
		<ul class="wc-wizard-shipping-methods">
			<li class="wc-wizard-shipping">
				<div class="wc-wizard-shipping-enable">
					<input type="checkbox" name="woocommerce_install_services" class="input-checkbox" value="woo-services-enabled" checked />
					<label>
						<?php echo esc_html( $content['title'] ) ?>
					</label>
				</div>
				<div class="wc-wizard-shipping-description">
					<p>
						<?php echo esc_html( $content['description'] ); ?>
					</p>
				</div>
			</li>
		</ul>
		<?php
	}

	/**
	 * Helper method to queue the background install of a plugin.
	 *
	 * @param string $plugin_id  Plugin id used for background install.
	 * @param array $plugin_info Plugin info array containing at least main file and repo slug.
	 */
	protected function install_plugin( $plugin_id, $plugin_info ) {
		if ( ! empty( $plugin_info['file'] ) && is_plugin_active( $plugin_info['file'] ) ) {
			return;
		}

		wp_schedule_single_event( time() + 10, 'woocommerce_plugin_background_installer', array( $plugin_id, $plugin_info ) );
	}

	/**
	 * Helper method to install Jetpack.
	 */
	protected function install_jetpack() {
		$this->install_plugin( 'jetpack', array(
			'file'      => 'jetpack/jetpack.php',
			'name'      => __( 'Jetpack', 'woocommerce' ),
			'repo-slug' => 'jetpack',
		) );
	}

	/**
	 * Helper method to install WooCommerce Services and its Jetpack dependency.
	 */
	protected function install_woocommerce_services() {
		$this->install_jetpack();
		$this->install_plugin( 'woocommerce-services', array(
			'file'      => 'woocommerce-services/woocommerce-services.php',
			'name'      => __( 'WooCommerce Services', 'woocommerce' ),
			'repo-slug' => 'woocommerce-services',
		) );
	}

	/**
	 * Shipping.
	 */
	public function wc_setup_shipping() {
		$dimension_unit = get_option( 'woocommerce_dimension_unit', false );
		$weight_unit    = get_option( 'woocommerce_weight_unit', false );
		if ( false === $dimension_unit || false === $weight_unit ) {
			$country = get_option( 'woocommerce_default_country', '' );
			if ( 0 === strpos( $country, 'US:' ) ) {
				$dimension_unit = 'in';
				$weight_unit = 'oz';
			} else {
				$dimension_unit = 'cm';
				$weight_unit = 'kg';
			}
		}
		?>
		<h1><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></h1>
		<form method="post">
			<?php $this->wc_setup_wcs_tout(); ?>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="weight_unit"><?php esc_html_e( 'Weight unit', 'woocommerce' ); ?></label></th>
					<td>
						<select id="weight_unit" name="weight_unit" class="wc-enhanced-select">
							<option value="kg" <?php selected( $weight_unit, 'kg' ); ?>><?php esc_html_e( 'kg', 'woocommerce' ); ?></option>
							<option value="g" <?php selected( $weight_unit, 'g' ); ?>><?php esc_html_e( 'g', 'woocommerce' ); ?></option>
							<option value="lbs" <?php selected( $weight_unit, 'lbs' ); ?>><?php esc_html_e( 'lbs', 'woocommerce' ); ?></option>
							<option value="oz" <?php selected( $weight_unit, 'oz' ); ?>><?php esc_html_e( 'oz', 'woocommerce' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dimension_unit"><?php esc_html_e( 'Dimension unit', 'woocommerce' ); ?></label></th>
					<td>
						<select id="dimension_unit" name="dimension_unit" class="wc-enhanced-select">
							<option value="m" <?php selected( $dimension_unit, 'm' ); ?>><?php esc_html_e( 'm', 'woocommerce' ); ?></option>
							<option value="cm" <?php selected( $dimension_unit, 'cm' ); ?>><?php esc_html_e( 'cm', 'woocommerce' ); ?></option>
							<option value="mm" <?php selected( $dimension_unit, 'mm' ); ?>><?php esc_html_e( 'mm', 'woocommerce' ); ?></option>
							<option value="in" <?php selected( $dimension_unit, 'in' ); ?>><?php esc_html_e( 'in', 'woocommerce' ); ?></option>
							<option value="yd" <?php selected( $dimension_unit, 'yd' ); ?>><?php esc_html_e( 'yd', 'woocommerce' ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce' ); ?>" name="save_step" />
				<input type="submit" class="button button-large button-next" value="<?php esc_attr_e( 'Skip this step', 'woocommerce' ); ?>" name="save_step" />
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Save shipping options.
	 */
	public function wc_setup_shipping_save() {
		check_admin_referer( 'wc-setup' );

		if ( isset( $_POST['save_step'] ) && esc_attr( 'Skip this step', 'woocommerce' ) === $_POST['save_step'] ) {
			update_option( 'woocommerce_ship_to_countries', 'disabled' );
			wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
			exit;
		}

		$current_shipping = get_option( 'woocommerce_ship_to_countries' );
		$install_services = isset( $_POST['woocommerce_install_services'] );
		$weight_unit      = sanitize_text_field( $_POST['weight_unit'] );
		$dimension_unit   = sanitize_text_field( $_POST['dimension_unit'] );

		update_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_weight_unit', $weight_unit );
		update_option( 'woocommerce_dimension_unit', $dimension_unit );

		/*
		 * If this is the initial shipping setup, create a shipping
		 * zone containing the country the store is located in, with
		 * a "free shipping" method preconfigured.
		 */
		if ( false === $current_shipping ) {
			$default_country = get_option( 'woocommerce_default_country' );
			$location        = wc_format_country_state_string( $default_country );

			$zone = new WC_Shipping_Zone( null );
			$zone->set_zone_order( 0 );
			$zone->add_location( $location['country'], 'country' );
			$zone->set_zone_name( $zone->get_formatted_location() );
			$zone->add_shipping_method( 'free_shipping' );
			$zone->save();
		}

		if ( $install_services ) {
			$this->install_woocommerce_services();
		} else {
			WC_Admin_Notices::add_notice( 'no_shipping_methods' );
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * https://stripe.com/global
	 */
	protected function is_stripe_supported_country( $country_code ) {
		$stripe_supported_countries = array(
			'AU' => __( 'Australia', 'woocommerce' ),
			'AT' => __( 'Austria', 'woocommerce' ),
			'BE' => __( 'Belgium', 'woocommerce' ),
			'CA' => __( 'Canada', 'woocommerce' ),
			'DK' => __( 'Denmark', 'woocommerce' ),
			'FI' => __( 'Finland', 'woocommerce' ),
			'FR' => __( 'France', 'woocommerce' ),
			'DE' => __( 'Germany', 'woocommerce' ),
			'HK' => __( 'Hong Kong', 'woocommerce' ),
			'IE' => __( 'Ireland', 'woocommerce' ),
			'JP' => __( 'Japan', 'woocommerce' ),
			'LU' => __( 'Luxembourg', 'woocommerce' ),
			'NL' => __( 'Netherlands', 'woocommerce' ),
			'NZ' => __( 'New Zealand', 'woocommerce' ),
			'NO' => __( 'Norway', 'woocommerce' ),
			'SG' => __( 'Singapore', 'woocommerce' ),
			'ES' => __( 'Spain', 'woocommerce' ),
			'SE' => __( 'Sweden', 'woocommerce' ),
			'CH' => __( 'Switzerland', 'woocommerce' ),
			'GB' => __( 'United Kingdom (UK)', 'woocommerce' ),
			'US' => __( 'United States (US)', 'woocommerce' ),
		);

		return array_key_exists( $country_code, $stripe_supported_countries );
	}

	/**
	 * Simple array of "in cart" gateways to show in wizard.
	 * @return array
	 */
	protected function get_wizard_in_cart_payment_gateways() {
		$country    = WC()->countries->get_base_country();
		$can_stripe = $this->is_stripe_supported_country( $country );

		$gateways = array(
			'stripe' => array(
				'name'        => __( 'Stripe', 'woocommerce' ),
				'image'       => WC()->plugin_url() . '/assets/images/stripe.png',
				'description' => sprintf( __( 'Accept all major debit and credit cards from customers in 135+ countries on your site. <a href="%s" target="_blank">Learn more about Stripe</a>. <p class="description">Fee: 2.9%% + 30¢ per transaction</p>', 'woocommerce' ), 'https://wordpress.org/plugins/woocommerce-gateway-stripe/' ),
				'class'       => $can_stripe ? 'featured featured-row-first checked' : 'featured featured-row-first',
				'repo-slug'   => 'woocommerce-gateway-stripe',
				'settings'    => array(
					'email' => array(
						'label'       => __( 'Stripe email address', 'woocommerce' ),
						'type'        => 'email',
						'value'       => get_option( 'admin_email' ),
						'placeholder' => __( 'Stripe email address', 'woocommerce' ),
					),
				),
				'enabled'     => $can_stripe,
			),
			'braintree_paypal' => array(
				'name'        => __( 'PayPal by Braintree', 'woocommerce' ),
				'image'       => WC()->plugin_url() . '/assets/images/paypal-braintree.png',
				'description' => __( "Safe and secure payments using credit cards or your customer's PayPal account.", 'woocommerce' ) . ' <a href="https://wordpress.org/plugins/woocommerce-gateway-paypal-powered-by-braintree/" target="_blank">' . __( 'Learn more about PayPal', 'woocommerce' ) . '</a>',
				'class'       => 'in-cart',
				'repo-slug'   => 'woocommerce-gateway-paypal-powered-by-braintree',
			),
			'ppec_paypal' => array(
				'name'        => __( 'PayPal Express Checkout', 'woocommerce' ),
				'image'       => WC()->plugin_url() . '/assets/images/paypal.png',
				'description' => __( "Safe and secure payments using credit cards or your customer's PayPal account.", 'woocommerce' ) . ' <a href="https://wordpress.org/plugins/woocommerce-gateway-paypal-express-checkout/" target="_blank">' . __( 'Learn more about PayPal', 'woocommerce' ) . '</a>',
				'class'       => 'in-cart',
				'repo-slug'   => 'woocommerce-gateway-paypal-express-checkout',
			),
			'paypal' => array(
				'name'        => __( 'PayPal Standard', 'woocommerce' ),
				'description' => __( 'Accept payments via PayPal using account balance or credit card.', 'woocommerce' ),
				'image'       => '',
				'class'       => 'in-cart',
				'settings'    => array(
					'email' => array(
						'label'       => __( 'PayPal email address', 'woocommerce' ),
						'type'        => 'email',
						'value'       => get_option( 'admin_email' ),
						'placeholder' => __( 'PayPal email address', 'woocommerce' ),
					),
				),
			),
		);

		$country = WC()->countries->get_base_country();

		if ( 'US' === $country ) {
			unset( $gateways['ppec_paypal'] );
		} else {
			unset( $gateways['braintree_paypal'] );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			unset( $gateways['braintree_paypal'] );
			unset( $gateways['ppec_paypal'] );
			unset( $gateways['stripe'] );
		}

		return $gateways;
	}

	/**
	 * Simple array of "manual" gateways to show in wizard.
	 * @return array
	 */
	protected function get_wizard_manual_payment_gateways() {
		$gateways = array(
			'cheque' => array(
				'name'        => _x( 'Check payments', 'Check payment method', 'woocommerce' ),
				'description' => __( 'A simple offline gateway that lets you accept a check as method of payment.', 'woocommerce' ),
				'image'       => '',
				'class'       => '',
			),
			'bacs' => array(
				'name'        => __( 'Bank transfer (BACS) payments', 'woocommerce' ),
				'description' => __( 'A simple offline gateway that lets you accept BACS payment.', 'woocommerce' ),
				'image'       => '',
				'class'       => '',
			),
			'cod' => array(
				'name'        => __( 'Cash on delivery', 'woocommerce' ),
				'description' => __( 'A simple offline gateway that lets you accept cash on delivery.', 'woocommerce' ),
				'image'       => '',
				'class'       => '',
			),
		);

		return $gateways;
	}

	/**
	 * Payments Step.
	 */
	public function wc_setup_payments() {
		$in_cart_gateways = $this->get_wizard_in_cart_payment_gateways();
		$manual_gateways  = $this->get_wizard_manual_payment_gateways();
		$country          = WC()->countries->get_base_country();
		$can_stripe       = $this->is_stripe_supported_country( $country );
		?>
		<h1><?php esc_html_e( 'Payments', 'woocommerce' ); ?></h1>
		<form method="post" class="wc-wizard-payment-gateway-form">
			<?php if ( $can_stripe ) : ?>
				<p><?php esc_html_e( 'Your store will be setup to accept payments instantly on checkout with Stripe.', 'woocommerce' ); ?></p>
			<?php else: ?>
				<p><?php printf( __( 'WooCommerce can accept both online and offline payments. <a href="%1$s" target="_blank">Additional payment methods</a> can be installed later and managed from the <a href="%2$s" target="_blank">checkout settings</a> screen.', 'woocommerce' ), esc_url( admin_url( 'admin.php?page=wc-addons&view=payment-gateways' ) ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ); ?></p>
			<?php endif; ?>
			<ul class="wc-wizard-payment-gateways in-cart">
				<?php foreach ( $in_cart_gateways as $gateway_id => $gateway ) : ?>
					<li class="wc-wizard-gateway wc-wizard-gateway-<?php echo esc_attr( $gateway_id ); ?> <?php echo esc_attr( $gateway['class'] ); ?>">
						<div class="wc-wizard-gateway-enable">
							<input type="checkbox" name="wc-wizard-gateway-<?php echo esc_attr( $gateway_id ); ?>-enabled" class="input-checkbox" value="yes" <?php checked( isset( $gateway['enabled'] ) && $gateway['enabled'] ); ?>/>
							<label>
								<?php if ( $gateway['image'] ) : ?>
									<img src="<?php echo esc_attr( $gateway['image'] ); ?>" alt="<?php echo esc_attr( $gateway['name'] ); ?>" />
								<?php else : ?>
									<?php echo esc_html( $gateway['name'] ); ?>
								<?php endif; ?>
							</label>
						</div>
						<div class="wc-wizard-gateway-description">
							<?php echo wp_kses_post( wpautop( $gateway['description'] ) ); ?>
						</div>
						<?php if ( ! empty( $gateway['settings'] ) ) : ?>
							<table class="form-table wc-wizard-gateway-settings">
								<?php foreach ( $gateway['settings'] as $setting_id => $setting ) : ?>
									<tr>
										<th scope="row"><label for="<?php echo esc_attr( $gateway_id ); ?>_<?php echo esc_attr( $setting_id ); ?>"><?php echo esc_html( $setting['label'] ); ?>:</label></th>
										<td>
											<input
												type="<?php echo esc_attr( $setting['type'] ); ?>"
												id="<?php echo esc_attr( $gateway_id ); ?>_<?php echo esc_attr( $setting_id ); ?>"
												name="<?php echo esc_attr( $gateway_id ); ?>_<?php echo esc_attr( $setting_id ); ?>"
												class="input-text"
												value="<?php echo esc_attr( $setting['value'] ); ?>"
												placeholder="<?php echo esc_attr( $setting['placeholder'] ); ?>"
												/>
										</td>
									</tr>
								<?php endforeach; ?>
							</table>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<ul class="wc-wizard-payment-gateways manual">
				<?php foreach ( $manual_gateways as $gateway_id => $gateway ) : ?>
					<li class="wc-wizard-gateway wc-wizard-gateway-<?php echo esc_attr( $gateway_id ); ?> <?php echo esc_attr( $gateway['class'] ); ?>">
						<div class="wc-wizard-gateway-enable">
							<input type="checkbox" name="wc-wizard-gateway-<?php echo esc_attr( $gateway_id ); ?>-enabled" class="input-checkbox" value="yes" <?php checked( isset( $gateway['enabled'] ) && $gateway['enabled'] ); ?>/>
							<label>
								<?php if ( $gateway['image'] ) : ?>
									<img src="<?php echo esc_attr( $gateway['image'] ); ?>" alt="<?php echo esc_attr( $gateway['name'] ); ?>" />
								<?php else : ?>
									<?php echo esc_html( $gateway['name'] ); ?>
								<?php endif; ?>
							</label>
						</div>
						<div class="wc-wizard-gateway-description">
							<?php echo wp_kses_post( wpautop( $gateway['description'] ) ); ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce' ); ?>" name="save_step" />
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Payments Step save.
	 */
	public function wc_setup_payments_save() {
		check_admin_referer( 'wc-setup' );

		$gateways = $this->get_wizard_in_cart_payment_gateways();

		foreach ( $gateways as $gateway_id => $gateway ) {
			// If repo-slug is defined, download and install plugin from .org.
			if ( ! empty( $gateway['repo-slug'] ) && ! empty( $_POST[ 'wc-wizard-gateway-' . $gateway_id . '-enabled' ] ) ) {
				$this->install_plugin( $gateway_id, $gateway );
			}

			$settings_key        = 'woocommerce_' . $gateway_id . '_settings';
			$settings            = array_filter( (array) get_option( $settings_key, array() ) );
			$settings['enabled'] = ! empty( $_POST[ 'wc-wizard-gateway-' . $gateway_id . '-enabled' ] ) ? 'yes' : 'no';

			if ( ! empty( $gateway['settings'] ) ) {
				foreach ( $gateway['settings'] as $setting_id => $setting ) {
					$settings[ $setting_id ] = wc_clean( $_POST[ $gateway_id . '_' . $setting_id ] );
				}
			}

			update_option( $settings_key, $settings );
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Theme step.
	 */
	private function wc_setup_theme() {
		?>
		<form method="post" class="wc-wizard-storefront">
			<p class="wc-wizard-storefront-intro">
				<?php echo wp_kses_post( __( '<strong>Storefront</strong> is the free WordPress theme built and maintained by the makers of WooCommerce.', 'woocommerce' ) ); ?>
				<img src="<?php echo esc_url( WC()->plugin_url() . '/assets/images/storefront-intro.png' ); ?>" alt="Storefront" />
			</p>

			<ul class="wc-wizard-storefront-features">
				<li class="wc-wizard-storefront-feature wc-wizard-storefront-feature__bulletproof first"><?php echo wp_kses_post( __( '<strong>Bulletproof WooCommerce integration:</strong> Rest assured the integration between WooCommerce, WooCommerce extensions and Storefront is water-tight.', 'woocommerce' ) ); ?></li>
				<li class="wc-wizard-storefront-feature wc-wizard-storefront-feature__accessibility last"><?php echo wp_kses_post( __( '<strong>Built with accessibility in mind:</strong> Storefront adheres to the strict wordpress.org accessibility guidelines making your store accessible to the widest audience possible.', 'woocommerce' ) ); ?></li>
				<li class="wc-wizard-storefront-feature wc-wizard-storefront-feature__extendable first"><?php echo wp_kses_post( __( '<strong>Child themes and extensions available:</strong> Like WooCommerce, you can extend Storefront with an extension or child theme to make your store truly your own.', 'woocommerce' ) ); ?></li>
				<li class="wc-wizard-storefront-feature wc-wizard-storefront-feature__compatibility last"><?php echo wp_kses_post( __( '<strong>No Shortcodes, sliders or page builders:</strong> Bring your favorite sliders or page builders, Storefront is built to work with the most popular options.', 'woocommerce' ) ); ?></li>
				<li class="wc-wizard-storefront-feature wc-wizard-storefront-feature__mobile first"><?php echo wp_kses_post( __( '<strong>Clean, simple mobile-first design:</strong> The perfect place to start when customizing your store, looks beautiful on any device.', 'woocommerce' ) ); ?></li>
				<li class="wc-wizard-storefront-feature wc-wizard-storefront-feature__search last"><?php echo wp_kses_post( __( '<strong>Optimized for search:</strong> Valid schema markup for improved SEO performance.', 'woocommerce' ) ); ?></li>
			</ul>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Install &amp; activate Storefront', 'woocommerce' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_html_e( 'Skip this step', 'woocommerce' ); ?></a>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php

	}

	/**
	 * Theme step save.
	 */
	private function wc_setup_theme_save() {
		check_admin_referer( 'wc-setup' );
		wp_schedule_single_event( time() + 1, 'woocommerce_theme_background_installer', array( 'storefront' ) );
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Activate step.
	 */
	public function wc_setup_activate() {
		?>
		<form method="post" class="wc-wizard-storefront">
			<h1><?php esc_html_e( 'Connect your store to Jetpack', 'woocommerce' ); ?></h1>
			<p class="wc-wizard-storefront-intro">
				<?php // TODO: tailor this message to the Jetpack-powered services selected earlier ?>
				<?php esc_html_e( "Your store's almost ready. Connect to Jetpack for full access to Stripe payments, automated taxes, USPS live rates, and discounted shipping labels.", 'woocommerce' ); ?>
				<img src="<?php echo esc_url( WC()->plugin_url() . '/assets/images/storefront-intro.png' ); ?>" alt="Storefront" />
			</p>

			<h3><?php esc_html_e( "Reasons you'll ❤️ Jetpack", 'woocommerce' ); ?></h3>
			<ul class="wc-wizard-storefront-features">
				<li class="wc-wizard-storefront-feature first">
					<p class="wc-wizard-storefront-feature-heading">
						<strong><?php esc_html_e( 'Attack protection', 'woocommerce' ); ?></strong>
					</p>
					<p class="wc-wizard-storefront-feature-description">
						<?php esc_html_e( 'Automatically block attacks and protect your site from unauthorized access.', 'woocommerce' ); ?>
					</p>
				</li>
				<li class="wc-wizard-storefront-feature last">
					<p class="wc-wizard-storefront-feature-heading">
						<strong><?php esc_html_e( 'Plugin management', 'woocommerce' ); ?></strong>
					</p>
					<p class="wc-wizard-storefront-feature-description">
						<?php esc_html_e( 'Manage multiple sites and automatic plugin updates from a single dashboard.', 'woocommerce' ); ?>
					</p>
				</li>
				<li class="wc-wizard-storefront-feature first">
					<p class="wc-wizard-storefront-feature-heading">
						<strong><?php esc_html_e( 'Site monitoring', 'woocommerce' ); ?></strong>
					</p>
					<p class="wc-wizard-storefront-feature-description">
						<?php esc_html_e( 'Keep tabs on your site and get an alert the moment downtime is detected.', 'woocommerce' ); ?>
					</p>
				</li>
				<li class="wc-wizard-storefront-feature last">
					<p class="wc-wizard-storefront-feature-heading">
						<strong><?php esc_html_e( 'SEO tools', 'woocommerce' ); ?></strong>
					</p>
					<p class="wc-wizard-storefront-feature-description">
						<?php esc_html_e( 'Better results on search engines and social media.', 'woocommerce' ); ?>
					</p>
				</li>
				<li class="wc-wizard-storefront-feature first">
					<p class="wc-wizard-storefront-feature-heading">
						<strong><?php esc_html_e( 'Single sign-on', 'woocommerce' ); ?></strong>
					</p>
					<p class="wc-wizard-storefront-feature-description">
						<?php esc_html_e( 'Use one account to access multiple sites quickly and securely.', 'woocommerce' ); ?>
					</p>
				</li>
				<li class="wc-wizard-storefront-feature last">
					<p class="wc-wizard-storefront-feature-heading">
						<strong><?php esc_html_e( 'So much more', 'woocommerce' ); ?></strong>
					</p>
					<p class="wc-wizard-storefront-feature-description">
						<?php echo wp_kses_post( sprintf( __( 'You get access to <a href="%s" target="_blank">more features</a> that are only possible through Jetpack.', 'woocommerce' ), 'https://jetpack.com/features/' ) ); ?>
					</p>
				</li>
			</ul>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Connect to Jetpack through WordPress.com', 'woocommerce' ); ?>" name="save_step" />
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php

	}

	/**
	 * Actions on the final step.
	 */
	private function wc_setup_ready_actions() {
		WC_Admin_Notices::remove_notice( 'install' );

		if ( isset( $_GET['wc_tracker_optin'] ) && isset( $_GET['wc_tracker_nonce'] ) && wp_verify_nonce( $_GET['wc_tracker_nonce'], 'wc_tracker_optin' ) ) {
			update_option( 'woocommerce_allow_tracking', 'yes' );
			WC_Tracker::send_tracking_data( true );

		} elseif ( isset( $_GET['wc_tracker_optout'] ) && isset( $_GET['wc_tracker_nonce'] ) && wp_verify_nonce( $_GET['wc_tracker_nonce'], 'wc_tracker_optout' ) ) {
			update_option( 'woocommerce_allow_tracking', 'no' );
		}
	}

	/**
	 * Final step.
	 */
	public function wc_setup_ready() {
		$this->wc_setup_ready_actions();
		$current_user = wp_get_current_user();
		shuffle( $this->tweets );
		?>
		<a href="https://twitter.com/share" class="twitter-share-button" data-url="https://woocommerce.com/" data-text="<?php echo esc_attr( $this->tweets[0] ); ?>" data-via="WooCommerce" data-size="large">Tweet</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

		<h1><?php esc_html_e( 'Your store is ready!', 'woocommerce' ); ?></h1>

		<?php if ( 'unknown' === get_option( 'woocommerce_allow_tracking', 'unknown' ) ) : ?>
			<div class="woocommerce-message woocommerce-tracker">
				<p><?php printf( __( 'Want to help make WooCommerce even more awesome? Allow WooCommerce to collect non-sensitive diagnostic data and usage information. %1$sFind out more%2$s.', 'woocommerce' ), '<a href="https://woocommerce.com/usage-tracking/" target="_blank">', '</a>' ); ?></p>
				<p class="submit">
					<a class="button-primary button button-large" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc_tracker_optin', 'true' ), 'wc_tracker_optin', 'wc_tracker_nonce' ) ); ?>"><?php esc_html_e( 'Allow', 'woocommerce' ); ?></a>
					<a class="button-secondary button button-large skip"  href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc_tracker_optout', 'true' ), 'wc_tracker_optout', 'wc_tracker_nonce' ) ); ?>"><?php esc_html_e( 'No thanks', 'woocommerce' ); ?></a>
				</p>
			</div>
		<?php endif; ?>

		<div class="woocommerce-message woocommerce-newsletter">
			<p><?php esc_html_e( 'Join the WooCommerce mailing list for help getting started, tips, and product updates.', 'woocommerce' ); ?></p>
			<form action="//woocommerce.us8.list-manage.com/subscribe/post?u=2c1434dc56f9506bf3c3ecd21&amp;id=13860df971" method="post" target="_blank" novalidate>
				<input type="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" name="EMAIL" placeholder="<?php esc_attr_e( 'Email address', 'woocommerce' ); ?>" required><input type="submit" value="<?php esc_html_e( 'Subscribe', 'woocommerce' ); ?>" name="subscribe" id="mc-embedded-subscribe" class="button-primary button button-large">
			</form>
		</div>

		<div class="wc-setup-next-steps">
			<div class="wc-setup-next-steps-first">
				<h2><?php esc_html_e( 'Next steps', 'woocommerce' ); ?></h2>
				<ul>
					<li class="setup-product"><a class="button button-primary button-large" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product&tutorial=true' ) ); ?>"><?php esc_html_e( 'Create your first product!', 'woocommerce' ); ?></a></li>
					<li class="setup-product"><a class="button button-large" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=product_importer' ) ); ?>"><?php esc_html_e( 'Import products from a CSV file', 'woocommerce' ); ?></a></li>
				</ul>
			</div>
			<div class="wc-setup-next-steps-last">
				<h2><?php _e( 'Learn more', 'woocommerce' ); ?></h2>
				<ul>
					<li class="video-walkthrough"><a href="https://docs.woocommerce.com/document/woocommerce-guided-tour-videos/?utm_source=setupwizard&utm_medium=product&utm_content=videos&utm_campaign=woocommerceplugin"><?php esc_html_e( 'Watch the Guided Tour videos', 'woocommerce' ); ?></a></li>
					<li class="learn-more"><a href="https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/?utm_source=setupwizard&utm_medium=product&utm_content=docs&utm_campaign=woocommerceplugin"><?php esc_html_e( 'Learn more about getting started', 'woocommerce' ); ?></a></li>
					<li class="newsletter"><a href="https://woocommerce.com/woocommerce-onboarding-email/?utm_source=setupwizard&utm_medium=product&utm_content=newsletter&utm_campaign=woocommerceplugin"><?php esc_html_e( 'Get eCommerce advice in your inbox', 'woocommerce' ); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
	}
}

new WC_Admin_Setup_Wizard();
