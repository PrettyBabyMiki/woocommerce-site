<?php
/**
 * Register the scripts, styles, and includes needed for pieces of the WooCommerce Admin experience.
 * NOTE: DO NOT edit this file in WooCommerce core, this is generated from woocommerce-admin.
 *
 * @package Woocommerce Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Events Class.
 */
class WC_Admin_Events {
	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Cron event handlers.
	 */
	public function init() {
		add_action( 'wc_admin_daily', array( __CLASS__, 'do_wc_admin_daily' ) );
	}

	/**
	 * Daily events to run.
	 *
	 * Note: WC_Admin_Notes_Order_Milestones::other_milestones is hooked to this as well.
	 */
	protected function do_wc_admin_daily() {
		WC_Admin_Notes_New_Sales_Record::possibly_add_sales_record_note();
		WC_Admin_Notes_Giving_Feedback_Notes::add_notes_for_admin_giving_feedback();
		WC_Admin_Notes_Mobile_App::possibly_add_mobile_app_note();
	}
}

WC_Admin_Events::instance()->init();
