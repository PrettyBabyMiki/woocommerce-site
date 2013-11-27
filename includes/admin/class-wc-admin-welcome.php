<?php
/**
 * Welcome Page Class
 *
 * Shows a feature overview for the new version (major) and credits.
 *
 * Adapted from code in EDD (Copyright (c) 2012, Pippin Williamson) and WP.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WC_Admin_Welcome class.
 */
class WC_Admin_Welcome {

	private $plugin;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->plugin             = 'woocommerce/woocommerce.php';

		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
	}

	/**
	 * Add admin menus/screens
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menus() {

		$welcome_page_title = __( 'Welcome to WooCommerce', 'woocommerce' );

		// About
		$about = add_dashboard_page( $welcome_page_title, $welcome_page_title, 'manage_options', 'wc-about', array( $this, 'about_screen' ) );

		// Credits
		$credits = add_dashboard_page( $welcome_page_title, $welcome_page_title, 'manage_options', 'wc-credits', array( $this, 'credits_screen' ) );

		add_action( 'admin_print_styles-'. $about, array( $this, 'admin_css' ) );
		add_action( 'admin_print_styles-'. $credits, array( $this, 'admin_css' ) );
	}

	/**
	 * admin_css function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_css() {
		wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ) );
	}

	/**
	 * Add styles just for this page, and remove dashboard page links.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_head() {

		remove_submenu_page( 'index.php', 'wc-about' );
		remove_submenu_page( 'index.php', 'wc-credits' );

		// Badge for welcome page
		$badge_url = WC()->plugin_url() . '/assets/images/welcome/wc-badge.png';
		?>
		<style type="text/css">
			/*<![CDATA[*/
			.wc-badge:before {
				font-family: WooCommerce !important;
				content: "\e03d";
				color: #fff;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
				font-size: 80px;
				font-weight: normal;
				width: 165px;
				height: 165px;
				line-height: 165px;
				text-align: center;
				position: absolute;
				top: 0;
				left: 0;
				margin: 0;
				vertical-align: middle;
			}
			.wc-badge {
				position: relative;;
				background: #9c5d90;
				text-rendering: optimizeLegibility;
				padding-top: 150px;
				height: 52px;
				width: 165px;
				font-weight: 600;
				font-size: 14px;
				text-align: center;
				color: #ddc8d9;
				margin: 5px 0 0 0;
				-webkit-box-shadow: 0 1px 3px rgba(0,0,0,.2);
				box-shadow: 0 1px 3px rgba(0,0,0,.2);
			}
			.about-wrap .wc-badge {
				position: absolute;
				top: 0;
				right: 0;
			}
			.about-wrap .wc-feature {
				overflow: visible !important;
				*zoom:1;
			}
			.about-wrap .wc-feature:before,
			.about-wrap .wc-feature:after {
				content: " ";
				display: table;
			}
			.about-wrap .wc-feature:after {
				clear: both;
			}
			.about-wrap .feature-rest div {
				width: 50% !important;
				padding-right: 100px;
				-moz-box-sizing: border-box;
				box-sizing: border-box;
				margin: 0 !important;
			}
			.about-wrap .feature-rest div.last-feature {
				padding-left: 100px;
				padding-right: 0;
			}
			.about-wrap div.icon {
				width: 0 !important;
				padding: 0;
				margin: 0;
			}
			.about-wrap .feature-rest div.icon:before { 
				font-family: WooCommerce !important;
				font-weight: normal;
				width: 100%;
				font-size: 170px;
				line-height: 125px;
				color: #9c5d90;
				display: inline-block;
				position: relative;
				text-align: center;
				speak: none;
				margin: 0 0 0 -100px;
				content: "\e01d";
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
			}
			.about-integrations {
				background: #fff;
				margin: 20px 0;
				padding: 1px 20px 10px;
			}
			/*]]>*/
		</style>
		<?php
	}

	/**
	 * Into text/links shown on all about pages.
	 *
	 * @access private
	 * @return void
	 */
	private function intro() {

		// Flush after upgrades
		if ( ! empty( $_GET['wc-updated'] ) || ! empty( $_GET['wc-installed'] ) )
			flush_rewrite_rules();

		// Drop minor version if 0
		$major_version = substr( WC()->version, 0, 3 );
		?>
		<h1><?php printf( __( 'Welcome to WooCommerce %s', 'woocommerce' ), $major_version ); ?></h1>

		<div class="about-text woocommerce-about-text">
			<?php
				if ( ! empty( $_GET['wc-installed'] ) )
					$message = __( 'Thanks, all done!', 'woocommerce' );
				elseif ( ! empty( $_GET['wc-updated'] ) )
					$message = __( 'Thank you for updating to the latest version!', 'woocommerce' );
				else
					$message = __( 'Thanks for installing!', 'woocommerce' );

				printf( __( '%s WooCommerce %s is more powerful, stable, and secure than ever before. We hope you enjoy it.', 'woocommerce' ), $message, $major_version );
			?>
		</div>

		<div class="wc-badge"><?php printf( __( 'Version %s', 'woocommerce' ), WC()->version ); ?></div>

		<p class="woocommerce-actions">
			<a href="<?php echo admin_url('admin.php?page=wc-settings'); ?>" class="button button-primary"><?php _e( 'Settings', 'woocommerce' ); ?></a>
			<a class="docs button button-primary" href="<?php echo esc_url( apply_filters( 'woocommerce_docs_url', 'http://docs.woothemes.com/documentation/plugins/woocommerce/', 'woocommerce' ) ); ?>"><?php _e( 'Docs', 'woocommerce' ); ?></a>
			<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/woocommerce/" data-text="A open-source (free) #ecommerce plugin for #WordPress that helps you sell anything. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="WooCommerce">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</p>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php if ( $_GET['page'] == 'wc-about' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-about' ), 'index.php' ) ) ); ?>">
				<?php _e( "What's New", 'woocommerce' ); ?>
			</a><a class="nav-tab <?php if ( $_GET['page'] == 'wc-credits' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-credits' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Credits', 'woocommerce' ); ?>
			</a>
		</h2>
		<?php
	}

	/**
	 * Output the about screen.
	 *
	 * @access public
	 * @return void
	 */
	public function about_screen() {
		?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>

			<!--<div class="changelog point-releases"></div>-->

			<div class="changelog">
				<h3><?php _e( 'A new RESTful API developers will &#10084;', 'woocommerce' ); ?></h3>
				<div class="wc-feature feature-rest feature-section col three-col">
					<div>
						<h4><?php _e( 'Access your data from 3rd party applications', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Built on top of the WooCommerce API, and targetted directly at developers, the new REST API allows you to get data for <strong>Orders</strong>, <strong>Coupons</strong>, <strong>Customers</strong>, <strong>Products</strong> and <strong>Reports</strong> in both <code>XML</code> and <code>JSON</code> formats.', 'woocommerce' ); ?></p>
					</div>
					<div class="icon"></div>
					<div class="last-feature">
						<h4><?php _e( 'Authentication to keep data secure', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Authentication for the REST API is performed using HTTP Basic Auth if you have SSL enabled, or signed according to the <a href="http://tools.ietf.org/html/rfc5849">OAuth 1.0a</a> specification if you don\'t have SSL. Data is only available to authenticated users.', 'woocommerce' ); ?></p>
					</div>
				</div>
			</div>
			<div class="changelog">
				<h3><?php _e( 'UI and reporting improvements', 'woocommerce' ); ?></h3>
				<div class="wc-feature feature-section col three-col">
					<div>
						<h4><?php _e( 'WordPress 3.8 UI (MP6) compatibility', 'woocommerce' ); ?></h4>
						<p><?php _e( 'WooCommerce 2.1 has had its UI restyled to work with the new MP6 design in WordPress. All bitmap icons have been replaced with a custom, lightweight icon font for razor sharp clarity on retina devices as well as improved performance.', 'woocommerce' ); ?></p>
					</div>
					<div>
						<h4><?php _e( 'Simplified order UI', 'woocommerce' ); ?></h4>
						<p><?php _e( 'The orders panel has seen significant improvement to both the totals panel, and line item display making editing new and existing orders a breeze.', 'woocommerce' ); ?></p>
						<p><?php _e( 'Item meta has also been optimised and can now be viewed as HTML rather than stuck in a text input.', 'woocommerce' ); ?></p>
					</div>
					<div class="last-feature">
						<h4><?php _e( 'Improved Reporting', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Reports have been redesigned with new <strong>filtering</strong> capabilities, a new <strong>customer report</strong> showing orders/spending, and the ability to <strong>export CSVs</strong>.', 'woocommerce' ); ?></p>
						<p><?php _e( 'The dashboard also has a new widget showing you an overview of current orders complete with sparklines for quick at-a-glance stats.', 'woocommerce' ); ?></p>
					</div>
				</div>
			</div>
			<div class="changelog about-integrations">
				<h3><?php _e( 'Separated integrations', 'woocommerce' ); ?></h3>
				<div class="wc-feature feature-section col three-col">
					<div>
						<h4><?php _e( 'New separate plugins', 'woocommerce' ); ?></h4>
						<p><?php _e( 'To make core more lean, some integrations have been removed and turned into dedicated plugins which you can install as and when you need them.', 'woocommerce' ); ?></p>
					</div>
					<div>
						<h4><?php _e( 'Google Analytics', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Add Google Analytics eCommerce tracking to your WooCommerce store.', 'woocommerce' ); ?></p>
						<p><a href="http://wordpress.org/plugins/woocommerce-google-analytics-integration" class="button"><?php _e( 'Download', 'woocommerce' ); ?></a></p>
					</div>
					<div class="last-feature">
						<h4><?php _e( 'Piwik', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Integrate WooCommerce with Piwik and the WP-Piwik plugin.', 'woocommerce' ); ?></p>
						<p><a href="http://wordpress.org/plugins/woocommerce-piwik-integration/" class="button"><?php _e( 'Download', 'woocommerce' ); ?></a></p>
					</div>
					<div>
						<h4><?php _e( 'ShareThis', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Add social network sharing buttons to products using ShareThis.', 'woocommerce' ); ?></p>
						<p><a href="http://wordpress.org/plugins/woocommerce-sharethis-integration/" class="button"><?php _e( 'Download', 'woocommerce' ); ?></a></p>
					</div>
					<div>
						<h4><?php _e( 'Sharedaddy', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Add social network sharing buttons to products using Sharedaddy.', 'woocommerce' ); ?></p>
						<p><a href="http://wordpress.org/plugins/woocommerce-sharedaddy-integration/" class="button"><?php _e( 'Download', 'woocommerce' ); ?></a></p>
					</div>
					<div class="last-feature">
						<h4><?php _e( 'ShareYourCart', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Let users share their carts for a discount using the ShareYourCart service.', 'woocommerce' ); ?></p>
						<p><a href="http://wordpress.org/plugins/shareyourcart/" class="button"><?php _e( 'Download', 'woocommerce' ); ?></a></p>
					</div>
				</div>
			</div>
			<div class="changelog">
				<h3><?php _e( 'Under the Hood', 'woocommerce' ); ?></h3>

				<div class="feature-section col three-col">
					<div>
						<h4><?php _e( 'PayPal PDT support', 'woocommerce' ); ?></h4>
						<p><?php _e( 'PayPal Data Transfer (PDT) is an alterntative for PayPal IPN which sends back the status of an order when a customer returns from PayPal.', 'woocommerce' ); ?></p>
					</div>

					<div>
						<h4><?php _e( 'Stylesheet separation', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Frontend styles have been split into separate appearance/layout/smallscreen stylesheets to help with selective customisation.', 'woocommerce' ); ?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'New endpoints', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Certain pages such as "Pay", "Order Recieved" and some account pages are now endpoints rather than pages to make checkout more reliable.', 'woocommerce' ); ?></p>
					</div>
				</div>
				<div class="feature-section col three-col">

					<div>
						<h4><?php _e( 'Default credit card form for gateways', 'woocommerce' ); ?></h4>
						<p><?php _e( 'We\'ve added a standardized, default credit card form for gateways to use if they support <code>default_credit_card_form</code>.', 'woocommerce' ); ?></p>
					</div>

					<div>
						<h4><?php _e( 'Coupon limits per customer', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Coupon usage limits can now be set per user (using email + ID) rather than global.', 'woocommerce' ); ?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'Streamlined new-account process', 'woocommerce' ); ?></h4>
						<p><?php _e( 'During checkout, username and passwords are optional and can be automatically generated by WooCommerce.', 'woocommerce' ); ?></p>
					</div>

				</div>
				<div class="feature-section col three-col">

					<div>
						<h4><?php _e( 'Additional price display options', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Define whether prices should be shown incl. or excl. of tax on the frontend, and add an optional suffix.', 'woocommerce' ); ?></p>
					</div>

					<div>
						<h4><?php _e( 'Past order linking', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Admins now have the ability to link past orders to a customer (before they registered) by email address.', 'woocommerce' ); ?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'Review improvements', 'woocommerce' ); ?></h4>
						<p><?php _e( 'We\'ve added a new option to restrict reviews to logged in purchasers, and made ratings editable from the backend.', 'woocommerce' ); ?></p>
					</div>

				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-settings' ), 'admin.php' ) ) ); ?>"><?php _e( 'Go to WooCommerce Settings', 'woocommerce' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the credits.
	 *
	 * @access public
	 * @return void
	 */
	public function credits_screen() {
		?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>

			<p class="about-description"><?php _e( 'WooCommerce is developed and maintained by a worldwide team of passionate individuals and backed by an awesome developer community. Want to see your name? <a href="https://github.com/woothemes/woocommerce/blob/master/CONTRIBUTING.md">Contribute to WooCommerce</a>.', 'woocommerce' ); ?></p>

			<?php echo $this->contributors(); ?>

		</div>
		<?php
	}

	/**
	 * Render Contributors List
	 *
	 * @access public
	 * @return string $contributor_list HTML formatted list of contributors.
	 */
	public function contributors() {
		$contributors = $this->get_contributors();

		if ( empty( $contributors ) )
			return '';

		$contributor_list = '<ul class="wp-people-group">';

		foreach ( $contributors as $contributor ) {
			$contributor_list .= '<li class="wp-person">';
			$contributor_list .= sprintf( '<a href="%s" title="%s">',
				esc_url( 'https://github.com/' . $contributor->login ),
				esc_html( sprintf( __( 'View %s', 'woocommerce' ), $contributor->login ) )
			);
			$contributor_list .= sprintf( '<img src="%s" width="64" height="64" class="gravatar" alt="%s" />', esc_url( $contributor->avatar_url ), esc_html( $contributor->login ) );
			$contributor_list .= '</a>';
			$contributor_list .= sprintf( '<a class="web" href="%s">%s</a>', esc_url( 'https://github.com/' . $contributor->login ), esc_html( $contributor->login ) );
			$contributor_list .= '</a>';
			$contributor_list .= '</li>';
		}

		$contributor_list .= '</ul>';

		return $contributor_list;
	}

	/**
	 * Retrieve list of contributors from GitHub.
	 *
	 * @access public
	 * @return mixed
	 */
	public function get_contributors() {
		$contributors = get_transient( 'woocommerce_contributors' );

		if ( false !== $contributors )
			return $contributors;

		$response = wp_remote_get( 'https://api.github.com/repos/woothemes/woocommerce/contributors', array( 'sslverify' => false ) );

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) )
			return array();

		$contributors = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! is_array( $contributors ) )
			return array();

		set_transient( 'woocommerce_contributors', $contributors, 3600 );

		return $contributors;
	}

	/**
	 * Sends user to the welcome page on first activation
	 */
	public function welcome() {

		// Bail if no activation redirect transient is set
	    if ( ! get_transient( '_wc_activation_redirect' ) )
			return;

		// Delete the redirect transient
		delete_transient( '_wc_activation_redirect' );

		// Bail if we are waiting to install or update via the interface update/install links
		if ( get_option( '_wc_needs_update' ) == 1 || get_option( '_wc_needs_pages' ) == 1 )
			return;

		// Bail if activating from network, or bulk, or within an iFrame
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) )
			return;

		if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'woocommerce.php' ) ) )
			return;

		wp_safe_redirect( admin_url( 'index.php?page=wc-about' ) );
		exit;
	}
}

new WC_Admin_Welcome();