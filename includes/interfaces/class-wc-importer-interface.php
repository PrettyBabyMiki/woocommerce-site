<?php
/**
 * WooCommerce Importer Interface
 *
 * @author   Automattic
 * @category Admin
 * @package  WooCommerce/Import
 * @version  3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Importer_Interface class.
 */
interface WC_Importer_Interface {

	/**
	 * Process importation.
	 * Returns an array with the imported and failed items.
	 * 'imported' contains a list of IDs.
	 * 'failed' contains a list of WP_Error objects.
	 *
	 * Example:
	 * ['imported' => [], 'failed' => []]
	 *
	 * @return array
	 */
	public function import();

	/**
	 * Get file raw keys.
	 *
	 * CSV - Headers.
	 * XML - Element names.
	 * JSON - Keys
	 *
	 * @return array
	 */
	public function get_raw_keys();

	/**
	 * Get file mapped headers.
	 *
	 * @return array
	 */
	public function get_mapped_keys();

	/**
	 * Get raw data.
	 *
	 * @return array
	 */
	public function get_raw_data();

	/**
	 * Get parsed data.
	 *
	 * @return array
	 */
	public function get_parsed_data();
}
