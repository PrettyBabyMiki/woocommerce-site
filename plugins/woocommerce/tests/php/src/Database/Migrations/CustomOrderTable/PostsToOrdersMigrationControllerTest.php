<?php
/**
 * Tests for PostsToOrdersMigrationController class.
 */

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Testing\Tools\DynamicDecorator;
use Automattic\WooCommerce\Testing\Tools\ReplacementObject;
use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * Class PostsToOrdersMigrationControllerTest.
 */
class PostsToOrdersMigrationControllerTest extends WC_Unit_Test_Case {

	/**
	 * @var PostsToOrdersMigrationController
	 */
	private $sut;

	/**
	 * @var OrdersTableDataStore
	 */
	private $data_store;

	/**
	 * @var array
	 */
	private $executed_transaction_statements;

	/**
	 * Setup data_store and sut.
	 */
	public function setUp(): void {
		parent::setUp();
		OrderHelper::create_order_custom_table_if_not_exist();
		$this->data_store = wc_get_container()->get( OrdersTableDataStore::class );
		$this->sut        = wc_get_container()->get( PostsToOrdersMigrationController::class );
	}

	/**
	 * Run after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'no' );
	}

	/**
	 * Test that migration for a normal order happens as expected.
	 */
	public function test_migration_for_normal_order() {
		$order = $this->create_and_migrate_order();

		$this->assert_core_data_is_migrated( $order );
		$this->assert_order_addresses_are_migrated( $order );
		$this->assert_order_op_data_is_migrated( $order );
		$this->assert_metadata_is_migrated( $order );
	}

	/**
	 * Test that already migrated order isn't migrated twice.
	 */
	public function test_migration_for_already_migrated_order() {
		global $wpdb;
		$order = wc_get_order( OrderHelper::create_complex_wp_post_order() );
		$this->clear_all_orders();

		// Run the migration once.
		$this->sut->migrate_order( $order->get_id() );

		// Run the migration again, assert there are still no duplicates.
		$this->sut->migrate_order( $order->get_id() );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_orders_table_name()}
WHERE id = {$order->get_id()}"
			),
			'Order record is duplicated.'
		);
		$order_id = $wpdb->get_var( "SELECT id FROM {$this->data_store::get_orders_table_name()} WHERE id = {$order->get_id()}" );
		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_addresses_table_name()}
WHERE order_id = {$order_id} AND address_type = 'billing'
"
			)
		);
		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_addresses_table_name()}
WHERE order_id = {$order_id} AND address_type = 'shipping'
"
			)
		);
		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_operational_data_table_name()}
WHERE order_id = {$order_id}
"
			)
		);

		$this->assertEquals(
			1,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_meta_table_name()}
WHERE order_id = {$order_id} AND meta_key = 'unique_key_1' AND meta_value = 'unique_value_1'
"
			)
		);
		$this->assertEquals(
			2,
			$wpdb->get_var(
				"
SELECT COUNT(*) FROM {$this->data_store::get_meta_table_name()}
WHERE order_id = {$order_id} AND meta_key = 'non_unique_key_1' AND meta_value in ( 'non_unique_value_1', 'non_unique_value_2' )
"
			)
		);
		// phpcs:enable
	}

	/**
	 * Test that when an order is partially migrated, it can still be resumed as expected.
	 */
	public function test_interrupted_migration() {
		$this->markTestSkipped();
	}

	/**
	 * Test that invalid order data is not migrated but logged.
	 */
	public function test_migrating_invalid_order_data() {
		$this->markTestSkipped();
	}

	/**
	 * Test when one order is invalid but other one is valid in a migration batch.
	 */
	public function test_migrating_invalid_valid_order_combo() {
		$this->markTestSkipped();
	}

	/**
	 * Helper method to get order object from COT.
	 *
	 * @param WP_Post $post_order Post object for order.
	 *
	 * @return array|object|void|null DB object from COT.
	 */
	private function get_order_from_cot( $post_order ) {
		global $wpdb;
		$order_table = $this->data_store::get_orders_table_name();
		$query       = "SELECT * FROM $order_table WHERE id = {$post_order->get_id()};";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_row( $query );
	}

	/**
	 * Helper method to get address details from DB.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $address_type Address Type.
	 *
	 * @return array|object|void|null DB object.
	 */
	private function get_address_details_from_cot( $order_id, $address_type ) {
		global $wpdb;
		$address_table = $this->data_store::get_addresses_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( "SELECT * FROM $address_table WHERE order_id = $order_id AND address_type = '$address_type';" );
	}

	/**
	 * Helper method to get operational details from COT.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array|object|void|null DB Object.
	 */
	private function get_order_operational_data_from_cot( $order_id ) {
		global $wpdb;
		$operational_data_table = $this->data_store::get_operational_data_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( "SELECT * FROM $operational_data_table WHERE order_id = $order_id;" );
	}

	/**
	 * Helper method to get meta data from custom order tables for given order id.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Meta data for an order ID.
	 */
	private function get_meta_data_from_cot( $order_id ) {
		global $wpdb;
		$metadata_table = $this->data_store::get_meta_table_name();

		// phpcs:ignore
		return $wpdb->get_results( "SELECT * FROM $metadata_table WHERE order_id = $order_id;" );
	}

	/**
	 * Helper method to assert core data is migrated.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function assert_core_data_is_migrated( $order ) {
		$db_order = $this->get_order_from_cot( $order );

		// Verify core data.
		$this->assertEquals( $order->get_id(), $db_order->id );
		$this->assertEquals( 'wc-' . $order->get_status(), $db_order->status );
		$this->assertEquals( 'INR', $db_order->currency );
		$this->assertEquals( $order->get_customer_id(), $db_order->customer_id );
		$this->assertEquals( $order->get_billing_email(), $db_order->billing_email );
		$this->assertEquals( $order->get_payment_method(), $db_order->payment_method );
		$this->assertEquals(
			$order->get_date_created()->date( DATE_ISO8601 ),
			( new WC_DateTime( $db_order->date_created_gmt ) )->date( DATE_ISO8601 )
		);
		$this->assertEquals( $order->get_date_modified()->date( DATE_ISO8601 ), ( new WC_DateTime( $db_order->date_updated_gmt ) )->date( DATE_ISO8601 ) );
		$this->assertEquals( $order->get_parent_id(), $db_order->parent_order_id );
		$this->assertEquals( $order->get_payment_method_title(), $db_order->payment_method_title );
		$this->assertEquals( $order->get_transaction_id(), $db_order->transaction_id );
		$this->assertEquals( $order->get_customer_ip_address(), $db_order->ip_address );
		$this->assertEquals( $order->get_customer_user_agent(), $db_order->user_agent );
	}

	/**
	 * Helper method to assert addresses are migrated.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function assert_order_addresses_are_migrated( $order ) {
		$db_order = $this->get_order_from_cot( $order );

		// Verify order billing address.
		$db_order_address = $this->get_address_details_from_cot( $db_order->id, 'billing' );
		$this->assertEquals( $order->get_billing_first_name(), $db_order_address->first_name );
		$this->assertEquals( $order->get_billing_last_name(), $db_order_address->last_name );
		$this->assertEquals( $order->get_billing_company(), $db_order_address->company );
		$this->assertEquals( $order->get_billing_address_1(), $db_order_address->address_1 );
		$this->assertEquals( $order->get_billing_address_2(), $db_order_address->address_2 );
		$this->assertEquals( $order->get_billing_city(), $db_order_address->city );
		$this->assertEquals( $order->get_billing_postcode(), $db_order_address->postcode );
		$this->assertEquals( $order->get_billing_country(), $db_order_address->country );
		$this->assertEquals( $order->get_billing_email(), $db_order_address->email );
		$this->assertEquals( $order->get_billing_phone(), $db_order_address->phone );

		// Verify order shipping address.
		$db_order_address = $this->get_address_details_from_cot( $db_order->id, 'shipping' );
		$this->assertEquals( $order->get_shipping_first_name(), $db_order_address->first_name );
		$this->assertEquals( $order->get_shipping_last_name(), $db_order_address->last_name );
		$this->assertEquals( $order->get_shipping_company(), $db_order_address->company );
		$this->assertEquals( $order->get_shipping_address_1(), $db_order_address->address_1 );
		$this->assertEquals( $order->get_shipping_address_2(), $db_order_address->address_2 );
		$this->assertEquals( $order->get_shipping_city(), $db_order_address->city );
		$this->assertEquals( $order->get_shipping_postcode(), $db_order_address->postcode );
		$this->assertEquals( $order->get_shipping_country(), $db_order_address->country );
		$this->assertEquals( $order->get_shipping_phone(), $db_order_address->phone );
	}

	/**
	 * Helper method to assert operational data is migrated.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function assert_order_op_data_is_migrated( $order ) {
		$db_order = $this->get_order_from_cot( $order );
		// Verify order operational data.
		$db_order_op_data = $this->get_order_operational_data_from_cot( $db_order->id );
		$this->assertEquals( $order->get_created_via(), $db_order_op_data->created_via );
		$this->assertEquals( $order->get_version(), $db_order_op_data->woocommerce_version );
		$this->assertEquals( $order->get_prices_include_tax(), $db_order_op_data->prices_include_tax );
		$this->assertEquals(
			wc_string_to_bool( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) ),
			$db_order_op_data->coupon_usages_are_counted
		);
		$this->assertEquals(
			wc_string_to_bool( $order->get_data_store()->get_download_permissions_granted( $order ) ),
			$db_order_op_data->download_permission_granted
		);
		$this->assertEquals( $order->get_cart_hash(), $db_order_op_data->cart_hash );
		$this->assertEquals(
			wc_string_to_bool( $order->get_meta( '_new_order_email_sent' ) ),
			$db_order_op_data->new_order_email_sent
		);
		$this->assertEquals( $order->get_order_key(), $db_order_op_data->order_key );
		$this->assertEquals( $order->get_data_store()->get_stock_reduced( $order ), $db_order_op_data->order_stock_reduced );
		$this->assertEquals(
			$order->get_date_paid()->date( DATE_ISO8601 ),
			( new WC_DateTime( $db_order_op_data->date_paid_gmt ) )->date( DATE_ISO8601 )
		);
		$this->assertEquals(
			$order->get_date_completed()->date( DATE_ISO8601 ),
			( new WC_DateTime( $db_order_op_data->date_completed_gmt ) )->date( DATE_ISO8601 )
		);
		$this->assertEquals( (float) $order->get_shipping_tax(), (float) $db_order_op_data->shipping_tax_amount );
		$this->assertEquals( (float) $order->get_shipping_total(), (float) $db_order_op_data->shipping_total_amount );
		$this->assertEquals( (float) $order->get_discount_tax(), (float) $db_order_op_data->discount_tax_amount );
		$this->assertEquals( (float) $order->get_discount_total(), (float) $db_order_op_data->discount_total_amount );
		$this->assertEquals(
			$order->get_data_store()->get_recorded_sales( $order ),
			(bool) $db_order_op_data->recorded_sales
		);
	}

	/**
	 * Helper method to assert that metadata is migrated for an order.
	 *
	 * @param WP_Post $order WP_Post order object.
	 */
	private function assert_metadata_is_migrated( $order ) {
		$db_order  = $this->get_order_from_cot( $order );
		$meta_data = $this->get_meta_data_from_cot( $db_order->id );

		$unique_row = array_filter(
			$meta_data,
			function ( $meta_row ) {
				return 'unique_key_1' === $meta_row->meta_key;
			}
		);

		$this->assertEquals( 1, count( $unique_row ) );
		$this->assertEquals( 'unique_value_1', array_values( $unique_row )[0]->meta_value );

		$non_unique_rows = array_filter(
			$meta_data,
			function ( $meta_row ) {
				return 'non_unique_key_1' === $meta_row->meta_key;
			}
		);
		$this->assertEquals( 2, count( $non_unique_rows ) );
		$this->assertEquals(
			array(
				'non_unique_value_1',
				'non_unique_value_2',
			),
			array_column( $non_unique_rows, 'meta_value' )
		);
	}

	/**
	 * Helper method to clear checkout and truncate order tables.
	 */
	private function clear_all_orders() {
		global $wpdb;
		$order_tables = $this->data_store->get_all_table_names();
		foreach ( $order_tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "TRUNCATE table $table;" );
		}
	}

	/**
	 * @testdox Database errors appearing during migrations are properly logged.
	 */
	public function test_database_errors_during_migrations_are_logged() {
		global $wpdb;

		$fake_logger = $this->use_fake_logger();

		$wpdb_mock = new DynamicDecorator( $wpdb );
		$this->register_legacy_proxy_global_mocks( array( 'wpdb' => $wpdb_mock ) );

		$wpdb_mock->register_method_replacement(
			'get_results',
			function( ...$args ) {
				$wpdb_mock = $args[0];
				$query     = $args[1];

				if ( StringUtil::contains( $query, 'wc_orders' ) ) {
					$wpdb_mock->state = 'Something failed!';
				}
			}
		);

		$wpdb_mock->register_property_get_replacement(
			'last_error',
			function( $replacement_object ) {
				if ( $replacement_object->state ) {
					return $replacement_object->state;
				} else {
					return $replacement_object->original_object->last_error;
				}
			}
		);

		$this->sut->migrate_orders( array( 1, 2, 3 ) );

		$actual_errors = $fake_logger->errors;
		usort(
			$actual_errors,
			function( $a, $b ) {
				return strcmp( $a['message'], $b['message'] );
			}
		);

		$this->assertEquals( 'PostMetaToOrderMetaMigrator: when processing ids 1-3: Something failed!', $actual_errors[0]['message'] );
		$this->assertEquals( array( 1, 2, 3 ), $actual_errors[0]['data']['ids'] );
		$this->assertEquals( PostsToOrdersMigrationController::LOGS_SOURCE_NAME, $actual_errors[0]['data']['source'] );
	}

	/**
	 * @testdox Exceptions thrown during migrations are properly logged.
	 */
	public function test_exceptions_during_migrations_are_logged() {
		global $wpdb;

		$exception = new \Exception( 'Something failed!' );

		$fake_logger = $this->use_fake_logger();

		$wpdb_mock = new DynamicDecorator( $wpdb );
		$this->register_legacy_proxy_global_mocks( array( 'wpdb' => $wpdb_mock ) );

		$wpdb_mock->register_method_replacement(
			'get_results',
			function( ...$args ) use ( $exception ) {
				$query = $args[1];

				if ( StringUtil::contains( $query, 'wc_orders' ) ) {
					throw $exception;
				}
			}
		);

		$this->sut->migrate_orders( array( 1, 2, 3 ) );

		$actual_errors = $fake_logger->errors;
		usort(
			$actual_errors,
			function( $a, $b ) {
				return strcmp( $a['message'], $b['message'] );
			}
		);

		$this->assertTrue( StringUtil::starts_with( $actual_errors[0]['message'], 'PostMetaToOrderMetaMigrator: when processing ids 1-3: (Exception) Something failed!' ) );
		$this->assertEquals( $exception, $actual_errors[0]['data']['exception'] );
		$this->assertEquals( PostsToOrdersMigrationController::LOGS_SOURCE_NAME, $actual_errors[0]['data']['source'] );
	}

	/**
	 * Register a fake logger to be returned by wc_get_logger, and return it.
	 *
	 * @return object The fake logger registered.
	 */
	private function use_fake_logger() {
		// phpcs:disable Squiz.Commenting
		$fake_logger = new class() {
			public $errors = array();

			public function error( $message, $data ) {
				$this->errors[] = array(
					'message' => $message,
					'data'    => $data,
				);
			}
		};
		// phpcs:enable Squiz.Commenting

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function() use ( $fake_logger ) {
					return $fake_logger;
				},
			)
		);

		return $fake_logger;
	}

	/**
	 * @testdox Database transactions aren't used on successful migrations if the corresponding setting isn't set.
	 */
	public function test_no_db_transactions_used_if_not_configured_on_success() {
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'no' );

		$this->use_wpdb_mock();

		$this->create_and_migrate_order();

		$this->assertEmpty( $this->executed_transaction_statements );
	}

	/**
	 * @testdox Database transactions aren't used on migrations with database error if the corresponding setting isn't set.
	 */
	public function test_no_db_transactions_used_if_not_configured_on_db_error() {
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'no' );

		$wpdb_mock = $this->use_wpdb_mock();
		$wpdb_mock->register_method_replacement(
			'get_results',
			function( ...$args ) {
				$wpdb_decorator                              = $args[0];
				$wpdb_decorator->original_object->last_error = 'Something failed!';
				return false;
			}
		);

		$this->create_and_migrate_order();

		$this->assertEmpty( $this->executed_transaction_statements );
	}

	/**
	 * @testdox Database transactions aren't used on migrations throwing exceptions if the corresponding setting isn't set.
	 */
	public function test_no_db_transactions_used_if_not_configured_on_exception() {
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'no' );

		$wpdb_mock = $this->use_wpdb_mock();
		$wpdb_mock->register_method_replacement(
			'get_results',
			function( ...$args ) {
				throw new \Exception( 'Something failed!' );
			}
		);

		$this->create_and_migrate_order();

		$this->assertEmpty( $this->executed_transaction_statements );
	}

	/**
	 * @testdox Database transactions are used and complete on successful migrations.
	 */
	public function test_db_transaction_completes_if_configured_and_no_errors() {
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'yes' );
		update_option( CustomOrdersTableController::DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION, 'SERIALIZABLE' );

		$this->use_wpdb_mock();

		$this->create_and_migrate_order();

		$expected = array(
			'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE',
			'START TRANSACTION',
			'COMMIT',
		);

		$this->assertEquals( $expected, $this->executed_transaction_statements );
	}


	/**
	 * @testdox An exception is thrown if an invalid transaction isolation level is configured.
	 */
	public function test_exception_is_thrown_on_invalid_transaction_isolation_level() {
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'yes' );
		update_option( CustomOrdersTableController::DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION, 'INVALID_LEVEL' );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid database transaction isolation level name INVALID_LEVEL' );

		$this->use_wpdb_mock();

		$this->create_and_migrate_order();
	}

	/**
	 * @testdox Database transactions are used and rolled back on migrations with database error.
	 */
	public function test_db_transaction_is_rolled_back_on_db_error() {
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'yes' );
		update_option( CustomOrdersTableController::DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION, 'SERIALIZABLE' );

		$wpdb_mock = $this->use_wpdb_mock();
		$wpdb_mock->register_method_replacement(
			'get_results',
			function( ...$args ) {
				$wpdb_decorator                              = $args[0];
				$wpdb_decorator->original_object->last_error = 'Something failed!';
				return false;
			}
		);

		$this->create_and_migrate_order();

		$expected = array(
			'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE',
			'START TRANSACTION',
			'ROLLBACK',
		);

		$this->assertEquals( $expected, $this->executed_transaction_statements );
	}

	/**
	 * @testdox Database transactions are used and rolled back on migrations that throw exceptions.
	 */
	public function test_db_transaction_is_rolled_back_on_exception() {
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'yes' );
		update_option( CustomOrdersTableController::DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION, 'SERIALIZABLE' );

		$wpdb_mock = $this->use_wpdb_mock();
		$wpdb_mock->register_method_replacement(
			'get_results',
			function( ...$args ) {
				throw new \Exception( 'Something failed!' );
			}
		);

		$this->create_and_migrate_order();

		$expected = array(
			'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE',
			'START TRANSACTION',
			'ROLLBACK',
		);

		$this->assertEquals( $expected, $this->executed_transaction_statements );
	}

	/**
	 * @testdox Database errors on transaction related queries are logged.
	 */
	public function test_db_transaction_related_errors_are_logged() {
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'yes' );
		update_option( CustomOrdersTableController::DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION, 'SERIALIZABLE' );

		$fake_logger = $this->use_fake_logger();
		$this->use_wpdb_mock( 'error' );

		$this->create_and_migrate_order();

		$actual_error = $fake_logger->errors[0];

		$this->assertEquals( 'PostsToOrdersMigrationController: when executing SET TRANSACTION ISOLATION LEVEL SERIALIZABLE: Something failed!', $actual_error['message'] );
		$this->assertEquals( 'Something failed!', $actual_error['data']['error'] );
		$this->assertEquals( PostsToOrdersMigrationController::LOGS_SOURCE_NAME, $actual_error['data']['source'] );
	}

	/**
	 * @testdox Database errors on transaction related exceptions are logged.
	 */
	public function test_transaction_related_exceptions_are_logged() {
		update_option( CustomOrdersTableController::USE_DB_TRANSACTIONS_OPTION, 'yes' );
		update_option( CustomOrdersTableController::DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION, 'SERIALIZABLE' );

		$fake_logger = $this->use_fake_logger();
		$exception   = new \Exception( 'Something failed!' );
		$this->use_wpdb_mock( $exception );

		$this->create_and_migrate_order();

		$actual_error = $fake_logger->errors[0];

		$this->assertTrue( StringUtil::starts_with( $actual_error['message'], 'PostsToOrdersMigrationController: when executing SET TRANSACTION ISOLATION LEVEL SERIALIZABLE: (Exception) Something failed!' ) );
		$this->assertEquals( $exception, $actual_error['data']['exception'] );
		$this->assertEquals( PostsToOrdersMigrationController::LOGS_SOURCE_NAME, $actual_error['data']['source'] );
	}

	/**
	 * Auxiliary method to create an order.
	 *
	 * @return WC_Order The created order.
	 */
	private function create_and_migrate_order() {
		$order = wc_get_order( OrderHelper::create_complex_wp_post_order() );
		$this->clear_all_orders();
		$this->sut->migrate_order( $order->get_id() );
		return $order;
	}

	/**
	 * Configure a dynamic decorator for $wpdb that logs (and optionally errors) any db related transaction query.
	 *
	 * @param string|\Exception\bool $transaction_fails False if the transaction related queries won't fail, 'error' if they produce a db error, or an Exception object that they will throw.
	 * @return DynamicDecorator
	 */
	private function use_wpdb_mock( $transaction_fails = false ) {
		global $wpdb;

		$this->executed_transaction_statements = array();

		$wpdb_mock = new DynamicDecorator( $wpdb );
		$this->register_legacy_proxy_global_mocks( array( 'wpdb' => $wpdb_mock ) );

		$wpdb_mock->register_method_replacement(
			'query',
			function( ...$args ) use ( $transaction_fails ) {
				$wpdb_decorator = $args[0];
				$query          = $args[1];

				$is_transaction_related_query =
					StringUtil::contains( $query, 'TRANSACTION' ) ||
					StringUtil::contains( $query, 'COMMIT' ) ||
					StringUtil::contains( $query, 'ROLLBACK' );

				if ( $is_transaction_related_query ) {
					if ( $transaction_fails instanceof \Exception ) {
						throw $transaction_fails;
					} elseif ( $transaction_fails ) {
						$wpdb_decorator->decorated_object->last_error = 'Something failed!';
						return false;
					} else {
						$this->executed_transaction_statements[] = $query;
						return true;
					}
				} else {
					return $wpdb_decorator->call_original_method( 'query', $args );
				}
			}
		);

		return $wpdb_mock;
	}

	/**
	 * Test that orders are migrated and verified without errors.
	 */
	public function test_verify_migrated_orders() {
		$order = wc_get_order( OrderHelper::create_complex_wp_post_order() );
		$this->clear_all_orders();

		// Additional test to assert null values are converted properly.
		delete_post_meta( $order->get_id(), '_cart_discount_tax' );

		$this->assertEquals( '', get_post_meta( $order->get_id(), '_cart_discount_tax', true ) );

		$this->sut->migrate_order( $order->get_id() );
		$errors = $this->sut->verify_migrated_orders( array( $order->get_id() ) );

		$this->assertEmpty( $errors );
	}
}
