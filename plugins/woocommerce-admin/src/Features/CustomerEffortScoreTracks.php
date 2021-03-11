<?php
/**
 * WooCommerce Customer effort score tracks
 *
 * @package WooCommerce\Admin\Features
 */

namespace Automattic\WooCommerce\Admin\Features;

defined( 'ABSPATH' ) || exit;

/**
 * Triggers customer effort score on several different actions.
 */
class CustomerEffortScoreTracks {
	/**
	 * Option name for the CES Tracks queue.
	 */
	const CES_TRACKS_QUEUE_OPTION_NAME = 'woocommerce_ces_tracks_queue';

	/**
	 * Option name for the clear CES Tracks queue for page.
	 */
	const CLEAR_CES_TRACKS_QUEUE_FOR_PAGE_OPTION_NAME =
		'woocommerce_clear_ces_tracks_queue_for_page';

	/**
	 * Option name for the set of actions that have been shown.
	 */
	const SHOWN_FOR_ACTIONS_OPTION_NAME = 'woocommerce_ces_shown_for_actions';

	/**
	 * Action name for settings change.
	 */
	const SETTINGS_CHANGE_ACTION_NAME = 'settings_change';

	/**
	 * Action name for import products.
	 */
	const IMPORT_PRODUCTS_ACTION_NAME = 'import_products';
  
	/**
	 * Action name for search.
	 */
	const SEARCH_ACTION_NAME = 'ces_search';

	/**
	 * Label for the snackbar that appears when a user submits the survey.
	 *
	 * @var string
	 */
	private $onsubmit_label;

	/**
	 * Constructor. Sets up filters to hook into WooCommerce.
	 */
	public function __construct() {
		$this->enable_survey_enqueing_if_tracking_is_enabled();
	}

	/**
	 * Add actions that require woocommerce_allow_tracking.
	 */
	private function enable_survey_enqueing_if_tracking_is_enabled() {
		// Only hook up the action handlers if in wp-admin.
		if ( ! is_admin() ) {
			return;
		}

		// Do not hook up the action handlers if a mobile device is used.
		if ( wp_is_mobile() ) {
			return;
		}

		// Only enqueue a survey if tracking is allowed.
		$allow_tracking = 'yes' === get_option( 'woocommerce_allow_tracking', 'no' );
		if ( ! $allow_tracking ) {
			return;
		}

		add_action(
			'admin_init',
			array(
				$this,
				'maybe_clear_ces_tracks_queue',
			)
		);

		add_action(
			'woocommerce_update_options',
			array(
				$this,
				'run_on_update_options',
			),
			10,
			3
		);
		add_action( 'load-edit.php', array( $this, 'run_on_load_edit_php' ), 10, 3 );

		add_action( 'product_page_product_importer', array( $this, 'run_on_product_import' ), 10, 3 );

		$this->onsubmit_label = __( 'Thank you for your feedback!', 'woocommerce-admin' );
	}

	/**
	 * Get the current published product count.
	 *
	 * @return integer The current published product count.
	 */
	private function get_product_count() {
		$query         = new \WC_Product_Query(
			array(
				'limit'    => 1,
				'paginate' => true,
				'return'   => 'ids',
				'status'   => array( 'publish' ),
			)
		);
		$products      = $query->get_products();
		$product_count = intval( $products->total );

		return $product_count;
	}

	/**
	 * Get the current shop order count.
	 *
	 * @return integer The current shop order count.
	 */
	private function get_shop_order_count() {
		$query            = new \WC_Order_Query(
			array(
				'limit'    => 1,
				'paginate' => true,
				'return'   => 'ids',
			)
		);
		$shop_orders      = $query->get_orders();
		$shop_order_count = intval( $shop_orders->total );

		return $shop_order_count;
	}

	/**
	 * Return whether the action has already been shown.
	 *
	 * @param string $action The action to check.
	 *
	 * @return bool Whether the action has already been shown.
	 */
	private function has_been_shown( $action ) {
		$shown_for_features = get_option( self::SHOWN_FOR_ACTIONS_OPTION_NAME, array() );
		$has_been_shown     = in_array( $action, $shown_for_features, true );

		return $has_been_shown;
	}

	/**
	 * Enqueue the item to the CES tracks queue.
	 *
	 * @param array $item The item to enqueue.
	 */
	private function enqueue_to_ces_tracks( $item ) {
		$queue = get_option(
			self::CES_TRACKS_QUEUE_OPTION_NAME,
			array()
		);

		$has_duplicate = array_filter(
			$queue,
			function ( $queue_item ) use ( $item ) {
				return $queue_item['action'] === $item['action'];
			}
		);
		if ( $has_duplicate ) {
			return;
		}

		$queue[] = $item;

		update_option(
			self::CES_TRACKS_QUEUE_OPTION_NAME,
			$queue
		);
	}

	/**
	 * Enqueue the CES survey on using search dynamically.
	 *
	 * @param string $search_area Search area such as "product" or "shop_order".
	 * @param string $page_now Value of window.pagenow.
	 * @param string $admin_page Value of window.adminpage.
	 */
	public function enqueue_ces_survey_for_search( $search_area, $page_now, $admin_page ) {
		if ( $this->has_been_shown( self::SEARCH_ACTION_NAME ) ) {
			return;
		}

		$this->enqueue_to_ces_tracks(
			array(
				'action'         => self::SEARCH_ACTION_NAME,
				'label'          => __(
					'How easy was it to use search?',
					'woocommerce-admin'
				),
				'onsubmit_label' => $this->onsubmit_label,
				'pagenow'        => $page_now,
				'adminpage'      => $admin_page,
				'props'          => (object) array(
					'search_area' => $search_area,
				),
			)
		);
	}

	/**
	 * Maybe clear the CES tracks queue, executed on every page load. If the
	 * clear option is set it clears the queue. In practice, this executes a
	 * page load after the queued CES tracks are displayed on the client, which
	 * sets the clear option.
	 */
	public function maybe_clear_ces_tracks_queue() {
		$clear_ces_tracks_queue_for_page = get_option(
			self::CLEAR_CES_TRACKS_QUEUE_FOR_PAGE_OPTION_NAME,
			false
		);

		if ( ! $clear_ces_tracks_queue_for_page ) {
			return;
		}

		$queue           = get_option(
			self::CES_TRACKS_QUEUE_OPTION_NAME,
			array()
		);
		$remaining_items = array_filter(
			$queue,
			function ( $item ) use ( $clear_ces_tracks_queue_for_page ) {
				return $clear_ces_tracks_queue_for_page['pagenow'] !== $item['pagenow']
				|| $clear_ces_tracks_queue_for_page['adminpage'] !== $item['adminpage'];
			}
		);

		update_option(
			self::CES_TRACKS_QUEUE_OPTION_NAME,
			array_values( $remaining_items )
		);
		update_option( self::CLEAR_CES_TRACKS_QUEUE_FOR_PAGE_OPTION_NAME, false );
	}

	/**
	 * Maybe enqueue the CES survey on product import, if step is done.
	 */
	public function run_on_product_import() {
		// We're only interested in when the importer completes.
		if ( empty( $_GET['step'] ) || 'done' !== $_GET['step'] ) { // phpcs:ignore CSRF ok.
			return;
		}

		if ( $this->has_been_shown( self::IMPORT_PRODUCTS_ACTION_NAME ) ) {
			return;
		}

		$this->enqueue_to_ces_tracks(
			array(
				'action'         => self::IMPORT_PRODUCTS_ACTION_NAME,
				'label'          => __(
					'How easy was it to import products?',
					'woocommerce-admin'
				),
				'onsubmit_label' => $this->onsubmit_label,
				'pagenow'        => 'product_page_product_importer',
				'adminpage'      => 'product_page_product_importer',
				'props'          => (object) array(),
			)
		);
	}

	/**
	 * Enqueue the CES survey trigger for setting changes.
	 */
	public function run_on_update_options() {
		// $current_tab is set when WC_Admin_Settings::save_settings is called.
		global $current_tab;

		if ( $this->has_been_shown( self::SETTINGS_CHANGE_ACTION_NAME ) ) {
			return;
		}

		$this->enqueue_to_ces_tracks(
			array(
				'action'         => self::SETTINGS_CHANGE_ACTION_NAME,
				'label'          => __(
					'How easy was it to update your settings?',
					'woocommerce-admin'
				),
				'onsubmit_label' => $this->onsubmit_label,
				'pagenow'        => 'woocommerce_page_wc-settings',
				'adminpage'      => 'woocommerce_page_wc-settings',
				'props'          => (object) array(
					'settings_area' => $current_tab,
				),
			)
		);
	}

	/**
	 * Determine on initiating CES survey on searching for product or orders.
	 */
	public function run_on_load_edit_php() {
		$allowed_types = array( 'product', 'shop_order' );
		$post_type     = get_current_screen()->post_type;

		// We're only interested for certain post types.
		if ( ! in_array( $post_type, $allowed_types, true ) ) {
			return;
		}

		// Determine whether request is search by "s" GET parameter.
		if ( empty( $_GET['s'] ) ) { // phpcs:disable WordPress.Security.NonceVerification.Recommended
			return;
		}

		$page_now = 'edit-' . $post_type;
		$this->enqueue_ces_survey_for_search( $post_type, $page_now, 'edit-php' );
	}
}
