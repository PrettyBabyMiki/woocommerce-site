<?php
/**
 * Class for implementing migration from wp_posts and wp_postmeta to custom order tables.
 */

namespace Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable;

use Automattic\WooCommerce\DataBase\Migrations\MigrationErrorLogger;

/**
 * Class WPPostToCOTMigrator
 *
 * @package Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable
 */
class WPPostToCOTMigrator {

	/**
	 * Error logger for migration errors.
	 *
	 * @var MigrationErrorLogger $error_logger
	 */
	private $error_logger;

	/**
	 * Migrator instance to migrate data into wc_order table.
	 *
	 * @var MetaToCustomTableMigrator
	 */
	private $order_table_migrator;

	/**
	 * Migrator instance to migrate billing data into address table.
	 *
	 * @var MetaToCustomTableMigrator
	 */
	private $billing_address_table_migrator;

	/**
	 * Migrator instance to migrate shipping data into address table.
	 *
	 * @var MetaToCustomTableMigrator
	 */
	private $shipping_address_table_migrator;

	/**
	 * Names of different order tables.
	 *
	 * @var array
	 */
	private $table_names;

	/**
	 * WPPostToCOTMigrator constructor.
	 */
	public function __construct() {
		global $wpdb;

		// TODO: Remove hardcoding.
		$this->table_names = array(
			'orders'    => $wpdb->prefix . 'wc_orders',
			'addresses' => $wpdb->prefix . 'wc_order_addresses',
			'op_data'   => $wpdb->prefix . 'wc_order_operational_data',
		);

		$order_config                          = $this->get_config_for_order_table();
		$billing_address_config                = $this->get_config_for_address_table_billing();
		$shipping_address_config               = $this->get_config_for_address_table_shipping();
		$this->order_table_migrator            = new MetaToCustomTableMigrator( $order_config['schema'], $order_config['meta'], $order_config['core'] );
		$this->billing_address_table_migrator  = new MetaToCustomTableMigrator( $billing_address_config['schema'], $billing_address_config['meta'], $billing_address_config['core'] );
		$this->shipping_address_table_migrator = new MetaToCustomTableMigrator( $shipping_address_config['schema'], $shipping_address_config['meta'], $shipping_address_config['core'] );
		$this->error_logger                    = new MigrationErrorLogger();
	}

	/**
	 * Returns migration configuration for order table.
	 *
	 * @return array Config for order table.
	 */
	public function get_config_for_order_table() {
		global $wpdb;
		$order_table_schema_config = array(
			'entity_schema'        => array(
				'primary_id' => 'ID',
				'table_name' => $wpdb->posts,
			),
			'entity_meta_schema'   => array(
				'meta_key_column'   => 'meta_key',
				'meta_value_column' => 'meta_value',
				'table_name'        => $wpdb->postmeta,
			),
			'destination_table'    => $wpdb->prefix . 'wc_orders',
			'entity_meta_relation' => array(
				'entity_rel_column' => 'ID',
				'meta_rel_column'   => 'post_id',
			),

		);

		$order_table_core_config = array(
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

		$order_table_meta_config = array(
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
		);

		return array(
			'schema' => $order_table_schema_config,
			'core'   => $order_table_core_config,
			'meta'   => $order_table_meta_config,
		);
	}

	/**
	 * Get configuration for billing addresses for migration.
	 *
	 * @return array Billing address migration config.
	 */
	public function get_config_for_address_table_billing() {
		return $this->get_config_for_address_table( 'billing' );
	}

	/**
	 * Get configuration for shipping addresses for migration.
	 *
	 * @return array Shipping address migration config.
	 */
	public function get_config_for_address_table_shipping() {
		return $this->get_config_for_address_table( 'shipping' );
	}

	/**
	 * Generate config for address data.
	 *
	 * @param string $type Type of address, this will be using in fetching meta keys.
	 *
	 * @return array Config for address table.
	 */
	private function get_config_for_address_table( $type ) {
		global $wpdb;
		// We join order core table and post meta table to get data  for address, since we need order ID.
		// So order core record needs to be already present.
		$schema_config = array(
			'entity_schema'        => array(
				'primary_id' => 'post_id',
				'table_name' => $this->table_names['orders'],
			),
			'entity_meta_schema'   => array(
				'meta_key_column'   => 'meta_key',
				'meta_value_column' => 'meta_value',
				'table_name'        => $wpdb->postmeta,
			),
			'destination_table'    => $this->table_names['addresses'],
			'entity_meta_relation' => array(
				'entity_rel_column' => 'post_id',
				'meta_rel_column'   => 'post_id',
			),
		);

		$core_config = array(
			'id'   => array(
				'type'        => 'int',
				'destination' => 'order_id',
			),
			'type' => array(
				'type'          => 'string',
				'destination'   => 'address_type',
				'select_clause' => "'$type'",
			),
		);

		$meta_config = array(
			"_{$type}_first_name" => array(
				'type'        => 'string',
				'destination' => 'first_name',
			),
			"_{$type}_last_name"  => array(
				'type'        => 'string',
				'destination' => 'last_name',
			),
			"_{$type}_company"    => array(
				'type'        => 'string',
				'destination' => 'company',
			),
			"_{$type}_address_1"  => array(
				'type'        => 'string',
				'destination' => 'address_1',
			),
			"_{$type}_address_2"  => array(
				'type'        => 'string',
				'destination' => 'address_2',
			),
			"_{$type}_city"       => array(
				'type'        => 'string',
				'destination' => 'city',
			),
			"_{$type}_state"      => array(
				'type'        => 'string',
				'destination' => 'state',
			),
			"_{$type}_postcode"   => array(
				'type'        => 'string',
				'destination' => 'postcode',
			),
			"_{$type}_country"    => array(
				'type'        => 'string',
				'destination' => 'country',
			),
			"_{$type}_email"      => array(
				'type'        => 'string',
				'destination' => 'email',
			),
			"_{$type}_phone"      => array(
				'type'        => 'string',
				'destination' => 'phone',
			),
		);

		return array(
			'schema' => $schema_config,
			'core'   => $core_config,
			'meta'   => $meta_config,
		);
	}

	/**
	 * Process next migration batch, uses option `wc_cot_migration` to checkpoints of what have been processed so far.
	 *
	 * @param int $batch_size Batch size of records to migrate.
	 *
	 * @return bool True if migration is completed, false if there are still records to process.
	 */
	public function process_next_migration_batch( $batch_size = 100 ) {
		global $wpdb;
		$order_by = 'ID ASC';

		$order_data = $this->order_table_migrator->fetch_data_for_migration( $this->get_where_clause(), $batch_size, $order_by );

		foreach ( $order_data['errors'] as $post_id => $error ) {
			$this->error_logger->log( 'info', "Error in importing post id $post_id: " . print_r( $error, true ) );
		}

		if ( count( $order_data['data'] ) === 0 ) {
			return true;
		}

		$queries = $this->order_table_migrator->generate_insert_sql_for_batch( $order_data['data'], 'insert' );
		$result  = $wpdb->query( $queries ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $queries is already prepared.
		if ( count( $order_data['data'] ) !== $result ) {
			// Some rows were not inserted.
			// TODO: Find and log the entity ids that were not inserted.
			echo ' error ';
		}

		$order_post_ids = array_column( $order_data['data'], 'post_id' );
		$this->process_next_address_batch( $this->billing_address_table_migrator, $order_post_ids, $order_by );
		$this->process_next_address_batch( $this->shipping_address_table_migrator, $order_post_ids, $order_by );

		$last_post_migrated = max( array_keys( $order_data['data'] ) );
		$this->update_checkpoint( $last_post_migrated );

		return false;
	}

	/**
	 * Process next batch for a given address type.
	 *
	 * @param MetaToCustomTableMigrator $migrator Migrator instance for address type.
	 * @param array                     $order_post_ids Array of post IDs for orders.
	 * @param string                    $order_by Order by clause.
	 */
	private function process_next_address_batch( $migrator, $order_post_ids, $order_by ) {
		global $wpdb;
		$post_ids_where_clause = $this->get_where_id_clause( $order_post_ids, 'post_id' );
		$batch_size            = count( $order_post_ids );
		$address_data          = $migrator->fetch_data_for_migration( $post_ids_where_clause, $batch_size, $order_by );
		foreach ( $address_data['errors'] as $order_id => $error ) {
			$this->error_logger->log( 'info', "Error in importing address data for Order ID $order_id: " . print_r( $error, true ) );
		}
		$address_queries = $migrator->generate_insert_sql_for_batch( $address_data['data'], 'insert' );
		$result          = $wpdb->query( $address_queries ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Insert statements should already be escaped.
		if ( count( $address_data['data'] ) !== $result ) {
			// Some rows were not inserted.
			// TODO: Find and log the entity ids that were not inserted.
			echo 'error';
		}
	}

	/**
	 * Method to migrate single record.
	 *
	 * @param int $post_id Post ID of record to migrate.
	 *
	 * @return bool|\WP_Error
	 */
	public function process_single( $post_id ) {
		global $wpdb;

		$where_clause = $wpdb->prepare( 'ID = %d', $post_id );
		$data         = $this->order_table_migrator->fetch_data_for_migration( $where_clause, 1, 'ID ASC' );
		if ( isset( $data['errors'][ $post_id ] ) ) {
			return new \WP_Error( $data['errors'][ $post_id ] );
		}

		$queries = $this->order_table_migrator->generate_insert_sql_for_batch( $data['data'], 'replace' );
		$result  = $wpdb->query( $queries ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $queries is already prepared.
		if ( 1 !== $result ) {
			// TODO: Fetch and return last error.
			echo 'error';

			return new \WP_Error( 'error' );
		}

		return true;
	}

	/**
	 * Helper function to get where clause to send to MetaToCustomTableMigrator instance.
	 *
	 * @return string|void Where clause.
	 */
	private function get_where_clause() {
		global $wpdb;

		$checkpoint   = $this->get_checkpoint();
		$where_clause = $wpdb->prepare(
			'post_type = "shop_order" AND ID > %d',
			$checkpoint['id']
		);

		return $where_clause;
	}

	/**
	 * Helper method to create `ID in (.., .., ...)` clauses.
	 *
	 * @param array  $ids List of IDs.
	 * @param string $column_name Name of the ID column.
	 *
	 * @return string Prepared clause for where.
	 */
	private function get_where_id_clause( $ids, $column_name = 'ID' ) {
		global $wpdb;

		if ( 0 === count( $ids ) ) {
			return '';
		}

		$id_placeholder_array = '(' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')';

		return $wpdb->prepare( "`$column_name` IN $id_placeholder_array", $ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Both $column_name and $id_placeholder_array should already be prepared.
	}

	/**
	 * Current checkpoint status.
	 *
	 * @return false|mixed|void
	 */
	private function get_checkpoint() {
		return get_option( 'wc_cot_migration', array( 'id' => 0 ) );
	}

	/**
	 * Updates current checkpoint
	 *
	 * @param int $id Order ID.
	 */
	public function update_checkpoint( $id ) {
		update_option( 'wc_cot_migration', array( 'id' => $id ) );
	}
}
