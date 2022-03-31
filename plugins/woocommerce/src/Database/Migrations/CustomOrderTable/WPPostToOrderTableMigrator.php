<?php
/**
 * Class for WPPost To order table migrator.
 */

namespace Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable;

/**
 * Class WPPostToOrderTableMigrator.
 */
class WPPostToOrderTableMigrator extends MetaToCustomTableMigrator {

	/**
	 * Get schema config for wp_posts and wc_order table.
	 *
	 * @return array Config.
	 */
	public function get_schema_config() {
		global $wpdb;

		// TODO: Remove hardcoding.
		$this->table_names = array(
			'orders'    => $wpdb->prefix . 'wc_orders',
			'addresses' => $wpdb->prefix . 'wc_order_addresses',
			'op_data'   => $wpdb->prefix . 'wc_order_operational_data',
			'meta'      => $wpdb->prefix . 'wc_orders_meta',
		);

		return array(
			'source'      => array(
				'entity' => array(
					'table_name'             => $wpdb->posts,
					'meta_rel_column'        => 'ID',
					'destination_rel_column' => 'ID',
					'primary_key'            => 'ID',
				),
				'meta'   => array(
					'table_name'        => $wpdb->postmeta,
					'meta_key_column'   => 'meta_key',
					'meta_value_column' => 'meta_value',
					'entity_id_column'  => 'post_id',
				),
			),
			'destination' => array(
				'table_name'        => $this->table_names['orders'],
				'source_rel_column' => 'post_id',
				'primary_key'       => 'id',
				'primary_key_type'  => 'int',
			),
		);
	}

	/**
	 * Get columns config.
	 *
	 * @return \string[][] Config.
	 */
	public function get_core_column_mapping() {
		return array(
			'ID'                => array(
				'type'        => 'int',
				'destination' => 'post_id',
			),
			'post_status'       => array(
				'type'        => 'string',
				'destination' => 'status',
			),
			'post_date_gmt'     => array(
				'type'        => 'date',
				'destination' => 'date_created_gmt',
			),
			'post_modified_gmt' => array(
				'type'        => 'date',
				'destination' => 'date_updated_gmt',
			),
			'post_parent'       => array(
				'type'        => 'int',
				'destination' => 'parent_order_id',
			),
		);
	}

	/**
	 * Get meta data config.
	 *
	 * @return \string[][] Config.
	 */
	public function get_meta_column_config() {
		return array(
			'_order_currency'       => array(
				'type'        => 'string',
				'destination' => 'currency',
			),
			'_order_tax'            => array(
				'type'        => 'decimal',
				'destination' => 'tax_amount',
			),
			'_order_total'          => array(
				'type'        => 'decimal',
				'destination' => 'total_amount',
			),
			'_customer_user'        => array(
				'type'        => 'int',
				'destination' => 'customer_id',
			),
			'_billing_email'        => array(
				'type'        => 'string',
				'destination' => 'billing_email',
			),
			'_payment_method'       => array(
				'type'        => 'string',
				'destination' => 'payment_method',
			),
			'_payment_method_title' => array(
				'type'        => 'string',
				'destination' => 'payment_method_title',
			),
			'_customer_ip_address'  => array(
				'type'        => 'string',
				'destination' => 'ip_address',
			),
			'_customer_user_agent'  => array(
				'type'        => 'string',
				'destination' => 'user_agent',
			),
			'_transaction_id'       => array(
				'type'        => 'string',
				'destination' => 'transaction_id',
			),
		);
	}
}
