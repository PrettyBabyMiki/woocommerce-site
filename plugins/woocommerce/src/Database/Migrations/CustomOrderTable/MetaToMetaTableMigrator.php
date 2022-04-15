<?php
/**
 * Generic Migration class to move any meta data associated to an entity, to a different meta table associated with a custom entity table.
 */

namespace Automattic\WooCommerce\Database\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Database\Migrations\MigrationHelper;

/**
 * Class MetaToMetaTableMigrator.
 *
 * Generic class for powering migrations from one meta table to another table.
 *
 * @package Automattic\WooCommerce\Database\Migrations\CustomOrderTable
 */
abstract class MetaToMetaTableMigrator {

	/**
	 * Schema config, see __construct for more details.
	 *
	 * @var array
	 */
	private $schema_config;

	/**
	 * Store errors along with entity IDs from migrations.
	 *
	 * @var array
	 */
	protected $errors;

	public abstract function get_meta_config();

	public function __construct() {
		$this->schema_config = $this->get_meta_config();
		$this->errors        = array();
	}

	public function process_migration_batch_for_ids( $entity_ids ) {
		global $wpdb;
		$to_migrate = $this->fetch_data_for_migration_for_ids( $entity_ids );

		$already_migrated = $this->get_already_migrated_records( array_keys( $to_migrate['data'] ) );

		list( $to_insert, $to_update ) = $this->classify_update_insert_records( $to_migrate['data'], $already_migrated );

		if ( ! empty( $to_insert ) ) {
			$insert_queries = $this->generate_insert_sql_for_batch( $to_insert );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $insert_queries should already be escaped in the generating function.
			$result = $wpdb->query( $insert_queries );
			$wpdb->query( 'COMMIT;' );
			if ( count( $to_insert ) !== $result ) {
				// TODO: Find and log entity ids that were not inserted.
			}
		}

		if ( empty( $to_update ) ) {
			return;
		}
		$update_queries = $this->generate_update_sql_for_batch( $to_update );
		$result         = $wpdb->query( $update_queries );
		$wpdb->query( 'COMMIT;' );
		// TODO: Find and log error updates.
	}

	public function generate_update_sql_for_batch( $batch ) {
		global $wpdb;

		$table             = $this->schema_config['destination']['meta']['table_name'];
		$meta_id_column    = $this->schema_config['destination']['meta']['meta_id_column'];
		$meta_key_column   = $this->schema_config['destination']['meta']['meta_key_column'];
		$meta_value_column = $this->schema_config['destination']['meta']['meta_value_column'];
		$entity_id_column  = $this->schema_config['destination']['meta']['entity_id_column'];
		$columns           = array( $meta_id_column, $entity_id_column, $meta_key_column, $meta_value_column );
		$columns_sql       = implode( '`, `', $columns );

		$entity_id_column_placeholder = MigrationHelper::get_wpdb_placeholder_for_type( $this->schema_config['destination']['meta']['entity_id_type'] );
		$placeholder_string           = "%d, $entity_id_column_placeholder, %s, %s";
		$values                       = array();
		foreach ( $batch as $entity_id => $rows ) {
			foreach ( $rows as $meta_key => $meta_details ) {
				$values[] = $wpdb->prepare(
					"( $placeholder_string )",
					array( $meta_details['id'], $entity_id, $meta_key, $meta_details['meta_value'] )
				);
			}
		}
		$value_sql = implode( ',', $values );

		$on_duplicate_key_clause = MigrationHelper::generate_on_duplicate_statement_clause( $columns );

		return "INSERT INTO $table ( `$columns_sql` ) VALUES $value_sql $on_duplicate_key_clause";
	}

	/**
	 * Generate insert sql queries for batches.
	 *
	 * @param array $batch Data to generate queries for.
	 * @param string $insert_switch Insert switch to use.
	 *
	 * @return string
	 */
	public function generate_insert_sql_for_batch( $batch ) {
		global $wpdb;

		$table             = $this->schema_config['destination']['meta']['table_name'];
		$meta_key_column   = $this->schema_config['destination']['meta']['meta_key_column'];
		$meta_value_column = $this->schema_config['destination']['meta']['meta_value_column'];
		$entity_id_column  = $this->schema_config['destination']['meta']['entity_id_column'];
		$column_sql        = "(`$entity_id_column`, `$meta_key_column`, `$meta_value_column`)";

		$entity_id_column_placeholder = MigrationHelper::get_wpdb_placeholder_for_type( $this->schema_config['destination']['meta']['entity_id_type'] );
		$placeholder_string           = "$entity_id_column_placeholder, %s, %s";
		$values                       = array();
		foreach ( $batch as $entity_id => $rows ) {
			foreach ( $rows as $meta_key => $meta_values ) {
				foreach ( $meta_values as $meta_value ) {
					$query_params = array(
						$entity_id,
						$meta_key,
						$meta_value
					);
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholder_string is hardcoded.
					$value_sql = $wpdb->prepare( "$placeholder_string", $query_params );
					$values[]  = $value_sql;
				}
			}
		}

		$values_sql = implode( '), (', $values );

		return "INSERT IGNORE INTO $table $column_sql VALUES ($values_sql)";
	}

	/**
	 * Fetch data for migration.
	 *
	 * @param array $entity_ids Array of IDs to fetch data for.
	 *
	 * @return array[] Data along with errors (if any), will of the form:
	 * array(
	 *  'data' => array(
	 *      'id_1' => array( 'column1' => value1, 'column2' => value2, ...),
	 *      ...,
	 *   ),
	 *  'errors' => array(
	 *      'id_1' => array( 'column1' => error1, 'column2' => value2, ...),
	 *      ...,
	 * )
	 */
	public function fetch_data_for_migration_for_ids( $entity_ids ) {
		global $wpdb;
		if ( empty( $entity_ids ) ) {
			return array(
				'data'   => array(),
				'errors' => array(),
			);
		}

		$meta_query = $this->build_meta_table_query( $entity_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Meta query has interpolated variables, but they should all be escaped for backticks.
		$meta_data_rows = $wpdb->get_results( $meta_query );
		if ( empty( $meta_data_rows ) ) {
			return array(
				'data'   => array(),
				'errors' => array(),
			);
		}

		foreach ( $meta_data_rows as $migrate_row ) {
			if ( ! isset( $to_migrate[ $migrate_row->entity_id ] ) ) {
				$to_migrate[ $migrate_row->entity_id ] = array();
			}

			if ( ! isset( $to_migrate[ $migrate_row->entity_id ][ $migrate_row->meta_key ] ) ) {
				$to_migrate[ $migrate_row->entity_id ][ $migrate_row->meta_key ] = array();
			}

			$to_migrate[ $migrate_row->entity_id ][ $migrate_row->meta_key ][] = $migrate_row->meta_value;
		}

		return array(
			'data'   => $to_migrate,
			'errors' => array(),
		);
	}

	private function get_already_migrated_records( $entity_ids ) {
		global $wpdb;

		$destination_table_name        = $this->schema_config['destination']['meta']['table_name'];
		$destination_id_column         = $this->schema_config['destination']['meta']['meta_id_column'];
		$destination_entity_id_column  = $this->schema_config['destination']['meta']['entity_id_column'];
		$destination_meta_key_column   = $this->schema_config['destination']['meta']['meta_key_column'];
		$destination_meta_value_column = $this->schema_config['destination']['meta']['meta_value_column'];

		$entity_id_type_placeholder = MigrationHelper::get_wpdb_placeholder_for_type( $this->schema_config['destination']['meta']['entity_id_type'] );
		$entity_ids_placeholder     = implode( ',', array_fill( 0, count( $entity_ids ), $entity_id_type_placeholder ) );

		$data_already_migrated = $wpdb->get_results(
			$wpdb->prepare(
				"
SELECT
	   $destination_id_column meta_id,
       $destination_entity_id_column entity_id,
       $destination_meta_key_column meta_key,
       $destination_meta_value_column meta_value
FROM $destination_table_name destination
WHERE destination.$destination_entity_id_column in ( $entity_ids_placeholder ) ORDER BY destination.$destination_entity_id_column
",
				$entity_ids
			)
		);

		$already_migrated = array();

		foreach ( $data_already_migrated as $migrate_row ) {
			if ( ! isset( $already_migrated[ $migrate_row->entity_id ] ) ) {
				$already_migrated[ $migrate_row->entity_id ] = array();
			}

			if ( ! isset( $already_migrated[ $migrate_row->entity_id ][ $migrate_row->meta_key ] ) ) {
				$already_migrated[ $migrate_row->entity_id ][ $migrate_row->meta_key ] = array();
			}

			$already_migrated[ $migrate_row->entity_id ][ $migrate_row->meta_key ][] = array(
				'id'         => $migrate_row->meta_id,
				'meta_value' => $migrate_row->meta_value
			);
		}
		return $already_migrated;
	}

	private function classify_update_insert_records( $to_migrate, $already_migrated ) {
		$to_update = array();
		$to_insert = array();

		foreach ( $to_migrate as $entity_id => $rows ) {
			foreach ( $rows as $meta_key => $meta_values ) {
				// If there is no corresponding record in the destination table then insert.
				// If there is single value in both already migrated and current then update.
				// If there are multiple values in either already_migrated records or in to_migrate_records, then insert instead of updating.
				if ( ! isset( $already_migrated[ $entity_id ][ $meta_key ] ) ) {
					if ( ! isset( $to_insert[ $entity_id ] ) ) {
						$to_insert[ $entity_id ] = array();
					}
					$to_insert[ $entity_id ][ $meta_key ] = $meta_values;
				} else {
					if ( 1 === count( $to_migrate[ $entity_id ][ $meta_key ] ) && 1 === count( $already_migrated[ $entity_id ][ $meta_key ] ) ) {
						if ( ! isset( $to_update[ $entity_id ] ) ) {
							$to_update[ $entity_id ] = array();
						}
						$to_update[ $entity_id ][ $meta_key ] = array(
							'id'         => $already_migrated[ $entity_id ][ $meta_key ][0]['id'],
							'meta_value' => $meta_values[0]
						);
						continue;
					}

					// There are multiple meta entries, let's find the unique entries and insert.
					$unique_meta_values = array_diff( $meta_values, array_column( $already_migrated[ $entity_id ][ $meta_key ], 'meta_value' ) );
					if ( 0 === count( $unique_meta_values ) ) {
						continue;
					}
					if ( ! isset( $to_insert[ $entity_id ] ) ) {
						$to_insert[ $entity_id ] = array();
					}
					$to_insert[ $entity_id ][ $meta_key ] = $unique_meta_values;
				}
			}
		}

		return array( $to_insert, $to_update );
	}

	/**
	 * Helper method to build query used to fetch data from source meta table.
	 *
	 * @param array $entity_ids List of entity IDs to build meta query for.
	 *
	 * @return string Query that can be used to fetch data.
	 */
	private function build_meta_table_query( $entity_ids ) {
		global $wpdb;
		$source_meta_table        = $this->schema_config['source']['meta']['table_name'];
		$source_meta_key_column   = $this->schema_config['source']['meta']['meta_key_column'];
		$source_meta_value_column = $this->schema_config['source']['meta']['meta_value_column'];
		$source_entity_id_column  = $this->schema_config['source']['meta']['entity_id_column'];
		$order_by                 = "source.$source_entity_id_column ASC";

		$where_clause = "source.`$source_entity_id_column` IN (" . implode( ', ', array_fill( 0, count( $entity_ids ), '%d' ) ) . ')';

		$entity_table                  = $this->schema_config['source']['entity']['table_name'];
		$entity_id_column              = $this->schema_config['source']['entity']['id_column'];
		$entity_meta_id_mapping_column = $this->schema_config['source']['entity']['source_id_column'];

		if ( $this->schema_config['source']['excluded_keys'] ) {
			$key_placeholder = implode( ',', array_fill( 0, count( $this->schema_config['source']['excluded_keys'] ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $source_meta_key_column is escated for backticks, $key_placeholder is hardcoded.
			$exclude_clause = $wpdb->prepare( "source.$source_meta_key_column NOT IN ( $key_placeholder )", $this->schema_config['source']['excluded_keys'] );
			$where_clause   = "$where_clause AND $exclude_clause";
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->prepare(
			"
SELECT
	source.`$source_entity_id_column` as source_entity_id,
	entity.`$entity_id_column` as entity_id,
	source.`$source_meta_key_column` as meta_key,
	source.`$source_meta_value_column` as meta_value
FROM `$source_meta_table` source
JOIN `$entity_table` entity ON entity.`$entity_meta_id_mapping_column` = source.`$source_entity_id_column`
WHERE $where_clause ORDER BY $order_by
",
			$entity_ids
		);
		// phpcs:enable
	}
}
