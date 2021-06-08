<?php
/**
 * Filterer class file.
 */

namespace Automattic\WooCommerce\Internal\ProductAttributesLookup;

defined( 'ABSPATH' ) || exit;


/**
 * Helper class for filtering products using the product attributes lookup table.
 */
class Filterer {

	/**
	 * The product attributes lookup data store to use.
	 *
	 * @var LookupDataStore
	 */
	private $data_store;

	/**
	 * The name of the product attributes lookup table.
	 *
	 * @var string
	 */
	private $lookup_table_name;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param LookupDataStore $data_store The data store to use.
	 */
	final public function init( LookupDataStore $data_store ) {
		$this->data_store        = $data_store;
		$this->lookup_table_name = $data_store->get_lookup_table_name();
	}

	/**
	 * Checks if the product attribute filtering via lookup table feature is enabled.
	 *
	 * @return bool
	 */
	public function filtering_via_lookup_table_is_active() {
		return 'yes' === get_option( 'woocommerce_attribute_lookup__enabled' );
	}

	/**
	 * Adds post clauses for filtering via lookup table.
	 * This method should be invoked within a 'posts_clauses' filter.
	 *
	 * @param array     $args Product query clauses as supplied to the 'posts_clauses' filter.
	 * @param \WP_Query $wp_query Current product query as supplied to the 'posts_clauses' filter.
	 * @param array     $attributes_to_filter_by Attribute filtering data as generated by WC_Query::get_layered_nav_chosen_attributes.
	 * @return array The updated product query clauses.
	 */
	public function filter_by_attribute_post_clauses( array $args, \WP_Query $wp_query, array $attributes_to_filter_by ) {
		global $wpdb;

		if ( ! $wp_query->is_main_query() || ! $this->filtering_via_lookup_table_is_active() ) {
			return $args;
		}

		$clause_root = " {$wpdb->prefix}posts.ID IN (";
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$in_stock_clause = ' AND in_stock = 1';
		} else {
			$in_stock_clause = '';
		}

		foreach ( $attributes_to_filter_by as $taxonomy => $data ) {
			$all_terms                  = get_terms( $taxonomy, array( 'hide_empty' => false ) );
			$term_ids_by_slug           = wp_list_pluck( $all_terms, 'term_id', 'slug' );
			$term_ids_to_filter_by      = array_values( array_intersect_key( $term_ids_by_slug, array_flip( $data['terms'] ) ) );
			$term_ids_to_filter_by      = array_map( 'absint', $term_ids_to_filter_by );
			$term_ids_to_filter_by_list = '(' . join( ',', $term_ids_to_filter_by ) . ')';
			$is_and_query               = 'and' === $data['query_type'];

			$count = count( $term_ids_to_filter_by );
			if ( 0 !== $count ) {
				if ( $is_and_query ) {
					$clauses[] = "
						{$clause_root}
						SELECT product_or_parent_id
						FROM {$this->lookup_table_name} lt
						WHERE is_variation_attribute=0
						{$in_stock_clause}
						AND term_id in {$term_ids_to_filter_by_list}
						GROUP BY product_id
						HAVING COUNT(product_id)={$count}
						UNION
						SELECT product_or_parent_id
						FROM {$this->lookup_table_name} lt
						WHERE is_variation_attribute=1
						{$in_stock_clause}
						AND term_id in {$term_ids_to_filter_by_list}
					)";
				} else {
					$clauses[] = "
							{$clause_root}
							SELECT product_or_parent_id
							FROM {$this->lookup_table_name} lt
							WHERE term_id in {$term_ids_to_filter_by_list}
							{$in_stock_clause}
						)";
				}
			}
		}

		if ( ! empty( $clauses ) ) {
			$args['where'] .= ' AND (' . join( ' AND ', $clauses ) . ')';
		} elseif ( ! empty( $attributes_to_filter_by ) ) {
			$args['where'] .= ' AND 1=0';
		}

		return $args;
	}

	/**
	 * Count products within certain terms, taking the main WP query into consideration,
	 * for the WC_Widget_Layered_Nav widget.
	 *
	 * This query allows counts to be generated based on the viewed products, not all products.
	 *
	 * @param  array  $term_ids Term IDs.
	 * @param  string $taxonomy Taxonomy.
	 * @param  string $query_type Query Type.
	 * @return array
	 */
	public function get_filtered_term_product_counts( $term_ids, $taxonomy, $query_type ) {
		global $wpdb;

		$use_lookup_table = $this->filtering_via_lookup_table_is_active();

		$tax_query  = \WC_Query::get_main_tax_query();
		$meta_query = \WC_Query::get_main_meta_query();
		if ( 'or' === $query_type ) {
			foreach ( $tax_query as $key => $query ) {
				if ( is_array( $query ) && $taxonomy === $query['taxonomy'] ) {
					unset( $tax_query[ $key ] );
				}
			}
		}

		$meta_query = new \WP_Meta_Query( $meta_query );
		$tax_query  = new \WP_Tax_Query( $tax_query );

		if ( $use_lookup_table ) {
			$query = $this->get_product_counts_query_using_lookup_table( $tax_query, $meta_query, $taxonomy, $term_ids );
		} else {
			$query = $this->get_product_counts_query_not_using_lookup_table( $tax_query, $meta_query, $term_ids );
		}

		$query     = apply_filters( 'woocommerce_get_filtered_term_product_counts_query', $query );
		$query_sql = implode( ' ', $query );

		// We have a query - let's see if cached results of this query already exist.
		$query_hash = md5( $query_sql );
		// Maybe store a transient of the count values.
		$cache = apply_filters( 'woocommerce_layered_nav_count_maybe_cache', true );
		if ( true === $cache ) {
			$cached_counts = (array) get_transient( 'wc_layered_nav_counts_' . sanitize_title( $taxonomy ) );
		} else {
			$cached_counts = array();
		}
		if ( ! isset( $cached_counts[ $query_hash ] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results                      = $wpdb->get_results( $query_sql, ARRAY_A );
			$counts                       = array_map( 'absint', wp_list_pluck( $results, 'term_count', 'term_count_id' ) );
			$cached_counts[ $query_hash ] = $counts;
			if ( true === $cache ) {
				set_transient( 'wc_layered_nav_counts_' . sanitize_title( $taxonomy ), $cached_counts, DAY_IN_SECONDS );
			}
		}
		return array_map( 'absint', (array) $cached_counts[ $query_hash ] );
	}

	/**
	 * Get the query for counting products by terms using the product attributes lookup table.
	 *
	 * @param \WP_Tax_Query  $tax_query The current main tax query.
	 * @param \WP_Meta_Query $meta_query The current main meta query.
	 * @param string         $taxonomy The attribute name to get the term counts for.
	 * @param string         $term_ids The term ids to include in the search.
	 * @return array An array of SQL query parts.
	 */
	private function get_product_counts_query_using_lookup_table( $tax_query, $meta_query, $taxonomy, $term_ids ) {
		global $wpdb;

		$meta_query_sql    = $meta_query->get_sql( 'post', $this->lookup_table_name, 'product_or_parent_id' );
		$tax_query_sql     = $tax_query->get_sql( $this->lookup_table_name, 'product_or_parent_id' );
		$hide_out_of_stock = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' );
		$in_stock_clause   = $hide_out_of_stock ? ' AND in_stock = 1' : '';

		$query['select'] = 'SELECT COUNT(DISTINCT product_or_parent_id) as term_count, term_id as term_count_id';
		$query['from']   = "FROM {$this->lookup_table_name}";
		$query['join']   = "INNER JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$this->lookup_table_name}.product_or_parent_id";

		$term_ids_sql   = $this->get_term_ids_sql( $term_ids );
		$query['where'] = "
			WHERE {$wpdb->posts}.post_type IN ( 'product' )
			AND {$wpdb->posts}.post_status = 'publish'
			{$tax_query_sql['where']} {$meta_query_sql['where']}
			AND {$this->lookup_table_name}.taxonomy='{$taxonomy}'
			AND {$this->lookup_table_name}.term_id IN $term_ids_sql
			{$in_stock_clause}";

		if ( ! empty( $term_ids ) ) {
			$attributes_to_filter_by = \WC_Query::get_layered_nav_chosen_attributes();

			if ( ! empty( $attributes_to_filter_by ) ) {
				$all_terms_to_filter_by = array();
				foreach ( $attributes_to_filter_by as $taxonomy => $data ) {
					$all_terms                  = get_terms( $taxonomy, array( 'hide_empty' => false ) );
					$term_ids_by_slug           = wp_list_pluck( $all_terms, 'term_id', 'slug' );
					$term_ids_to_filter_by      = array_values( array_intersect_key( $term_ids_by_slug, array_flip( $data['terms'] ) ) );
					$all_terms_to_filter_by     = array_merge( $all_terms_to_filter_by, $term_ids_to_filter_by );
					$term_ids_to_filter_by_list = '(' . join( ',', $term_ids_to_filter_by ) . ')';

					$count = count( $term_ids_to_filter_by );
					if ( 0 !== $count ) {
						$query['where'] .= ' AND product_or_parent_id IN (';
						if ( 'and' === $attributes_to_filter_by[ $taxonomy ]['query_type'] ) {
							$query['where'] .= "
								SELECT product_or_parent_id
								FROM {$this->lookup_table_name} lt
								WHERE is_variation_attribute=0
								{$in_stock_clause}
								AND term_id in {$term_ids_to_filter_by_list}
								GROUP BY product_id
								HAVING COUNT(product_id)={$count}
								UNION
								SELECT product_or_parent_id
								FROM {$this->lookup_table_name} lt
								WHERE is_variation_attribute=1
								{$in_stock_clause}
								AND term_id in {$term_ids_to_filter_by_list}
							)";
						} else {
							$query['where'] .= "
								SELECT product_or_parent_id FROM {$this->lookup_table_name}
								WHERE term_id in {$term_ids_to_filter_by_list}
								{$in_stock_clause}
							)";
						}
					}
				}
			} else {
				$query['where'] .= $in_stock_clause;
			}
		} elseif ( $hide_out_of_stock ) {
			$query['where'] .= " AND {$this->lookup_table_name}.in_stock=1";
		}

		$search_query_sql = \WC_Query::get_main_search_query_sql();
		if ( $search_query_sql ) {
			$query['where'] .= ' AND ' . $search_query_sql;
		}

		$query['group_by'] = 'GROUP BY terms.term_id';
		$query['group_by'] = "GROUP BY {$this->lookup_table_name}.term_id";

		return $query;
	}

	/**
	 * Get the query for counting products by terms NOT using the product attributes lookup table.
	 *
	 * @param \WP_Tax_Query  $tax_query The current main tax query.
	 * @param \WP_Meta_Query $meta_query The current main meta query.
	 * @param string         $term_ids The term ids to include in the search.
	 * @return array An array of SQL query parts.
	 */
	private function get_product_counts_query_not_using_lookup_table( $tax_query, $meta_query, $term_ids ) {
		global $wpdb;

		$meta_query_sql = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
		$tax_query_sql  = $tax_query->get_sql( $wpdb->posts, 'ID' );

		// Generate query.
		$query           = array();
		$query['select'] = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) AS term_count, terms.term_id AS term_count_id";
		$query['from']   = "FROM {$wpdb->posts}";
		$query['join']   = "
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON {$wpdb->posts}.ID = term_relationships.object_id
			INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
			INNER JOIN {$wpdb->terms} AS terms USING( term_id )
			" . $tax_query_sql['join'] . $meta_query_sql['join'];

		$term_ids_sql   = $this->get_term_ids_sql( $term_ids );
		$query['where'] = "
			WHERE {$wpdb->posts}.post_type IN ( 'product' )
			AND {$wpdb->posts}.post_status = 'publish'
			{$tax_query_sql['where']} {$meta_query_sql['where']}
			AND terms.term_id IN $term_ids_sql";

		$search_query_sql = \WC_Query::get_main_search_query_sql();
		if ( $search_query_sql ) {
			$query['where'] .= ' AND ' . $search_query_sql;
		}

		$query['group_by'] = 'GROUP BY terms.term_id';

		return $query;
	}

	/**
	 * Formats a list of term ids as "(id,id,id)".
	 *
	 * @param array $term_ids The list of terms to format.
	 * @return string The formatted list.
	 */
	private function get_term_ids_sql( $term_ids ) {
		return '(' . implode( ',', array_map( 'absint', $term_ids ) ) . ')';
	}
}
