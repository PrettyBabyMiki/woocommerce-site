<?php
/**
 * Handles the products CSV exporter UI in admin.
 *
 * @author   Automattic
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Product_Export Class.
 */
class WC_Admin_Product_Export {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 55 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_init', array( $this, 'download_export_file' ) );
		add_action( 'wp_ajax_woocommerce_do_ajax_product_export', array( $this, 'do_ajax_product_export' ) );
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=product', __( 'Import / Export', 'woocommerce' ), __( 'Import / Export', 'woocommerce' ), 'edit_products', 'woocommerce_importer', array( $this, 'admin_screen' ) );
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'wc-product-export', WC()->plugin_url() . '/assets/js/admin/wc-product-export' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
	}

	/**
	 * Export page UI.
	 */
	public function admin_screen() {
		include_once( WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php' );
		include_once( dirname( __FILE__ ) . '/views/html-admin-page-product-export.php' );
	}

	/**
	 * Serve the generated file.
	 */
	public function download_export_file() {
		if ( isset( $_GET['action'], $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'product-csv' ) && 'download_product_csv' === $_GET['action'] ) {
			include_once( WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php' );
			$exporter = new WC_Product_CSV_Exporter();
			$exporter->export();
		}
	}

	/**
	 * Export data.
	 */
	public function do_ajax_product_export() {
		include_once( WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php' );

		$step     = absint( $_POST['step'] );
		$exporter = new WC_Product_CSV_Exporter();

		if ( ! empty( $_POST['columns'] ) ) {
			$exporter->set_column_names( $_POST['columns'] );
		}

		if ( ! empty( $_POST['selected_columns'] ) ) {
			$exporter->set_columns_to_export( $_POST['selected_columns'] );
		}

		if ( ! empty( $_POST['export_meta'] ) ) {
			$exporter->enable_meta_export( true );
		}

		if ( ! empty( $_POST['export_types'] ) ) {
			$exporter->set_product_types_to_export( $_POST['export_types'] );
		}

		$exporter->set_page( $step );
		$exporter->generate_file();

		$percentage = $exporter->get_percent_complete();

		if ( 100 === $percentage ) {
			wp_send_json_success( array(
				'step'       => 'done',
				'percentage' => $percentage,
				'url'        => add_query_arg( array( 'nonce' => wp_create_nonce( 'product-csv' ), 'action' => 'download_product_csv' ), admin_url( 'edit.php?post_type=product&page=woocommerce_importer' ) ),
			) );
		} else {
			wp_send_json_success( array(
				'step'       => ++$step,
				'percentage' => $percentage,
				'columns'    => $exporter->get_column_names(),
			) );
		}
	}
}

new WC_Admin_Product_Export();
