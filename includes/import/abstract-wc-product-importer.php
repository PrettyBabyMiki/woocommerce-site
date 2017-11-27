<?php
/**
 * Abstract Product importer
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
 * Include dependencies.
 */
if ( ! class_exists( 'WC_Importer_Interface', false ) ) {
	include_once( WC_ABSPATH . 'includes/interfaces/class-wc-importer-interface.php' );
}

/**
 * WC_Product_Importer Class.
 */
abstract class WC_Product_Importer implements WC_Importer_Interface {

	/**
	 * CSV file.
	 *
	 * @var string
	 */
	protected $file = '';

	/**
	 * The file position after the last read.
	 *
	 * @var int
	 */
	protected $file_position = 0;

	/**
	 * Importer parameters.
	 *
	 * @var array
	 */
	protected $params = array();

	/**
	 * Raw keys - CSV raw headers.
	 *
	 * @var array
	 */
	protected $raw_keys = array();

	/**
	 * Mapped keys - CSV headers.
	 *
	 * @var array
	 */
	protected $mapped_keys = array();

	/**
	 * Raw data.
	 *
	 * @var array
	 */
	protected $raw_data = array();

	/**
	 * Raw data.
	 *
	 * @var array
	 */
	protected $file_positions = array();

	/**
	 * Parsed data.
	 *
	 * @var array
	 */
	protected $parsed_data = array();

	/**
	 * Start time of current import.
	 *
	 * (default value: 0)
	 *
	 * @var int
	 * @access protected
	 */
	protected $start_time = 0;

	/**
	 * Get file raw headers.
	 *
	 * @return array
	 */
	public function get_raw_keys() {
		return $this->raw_keys;
	}

	/**
	 * Get file mapped headers.
	 *
	 * @return array
	 */
	public function get_mapped_keys() {
		return ! empty( $this->mapped_keys ) ? $this->mapped_keys : $this->raw_keys;
	}

	/**
	 * Get raw data.
	 *
	 * @return array
	 */
	public function get_raw_data() {
		return $this->raw_data;
	}

	/**
	 * Get parsed data.
	 *
	 * @return array
	 */
	public function get_parsed_data() {
		return apply_filters( 'woocommerce_product_importer_parsed_data', $this->parsed_data, $this->get_raw_data() );
	}

	/**
	 * Get importer parameters.
	 *
	 * @return array
	 */
	public function get_params() {
		return $this->params;
	}

	/**
	 * Get file pointer position from the last read.
	 *
	 * @return int
	 */
	public function get_file_position() {
		return $this->file_position;
	}

	/**
	 * Get file pointer position as a percentage of file size.
	 *
	 * @return int
	 */
	public function get_percent_complete() {
		$size = filesize( $this->file );
		if ( ! $size ) {
			return 0;
		}

		return absint( min( round( ( $this->file_position / $size ) * 100 ), 100 ) );
	}

	/**
	 * Prepare a single product for create or update.
	 *
	 * @param  array $data     Item data.
	 * @return WC_Product|WP_Error
	 */
	protected function get_product_object( $data ) {
		$id = isset( $data['id'] ) ? absint( $data['id'] ) : 0;

		// Type is the most important part here because we need to be using the correct class and methods.
		if ( isset( $data['type'] ) ) {
			$types   = array_keys( wc_get_product_types() );
			$types[] = 'variation';

			if ( ! in_array( $data['type'], $types, true ) ) {
				return new WP_Error( 'woocommerce_product_importer_invalid_type', __( 'Invalid product type.', 'woocommerce' ), array( 'status' => 401 ) );
			}

			$classname = WC_Product_Factory::get_classname_from_product_type( $data['type'] );

			if ( ! class_exists( $classname ) ) {
				$classname = 'WC_Product_Simple';
			}

			$product = new $classname( $id );
		} elseif ( isset( $data['id'] ) ) {
			$product = wc_get_product( $id );

			if ( ! $product ) {
				return new WP_Error( 'woocommerce_product_csv_importer_invalid_id', sprintf( __( 'Invalid product ID %d.', 'woocommerce' ), $id ), array( 'id' => $id, 'status' => 401 ) );
			}
		} else {
			$product = new WC_Product_Simple( $id );
		}

		return apply_filters( 'woocommerce_product_import_get_product_object', $product, $data );
	}

	/**
	 * Process a single item and save.
	 *
	 * @param  array $data Raw CSV data.
	 * @return array|WC_Error
	 */
	protected function process_item( $data ) {
		try {
			do_action( 'woocommerce_product_import_before_process_item', $data );

			// Get product ID from SKU if created during the importation.
			if ( empty( $data['id'] ) && ! empty( $data['sku'] ) && ( $product_id = wc_get_product_id_by_sku( $data['sku'] ) ) ) {
				$data['id'] = $product_id;
			}

			$object   = $this->get_product_object( $data );
			$updating = false;

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			if ( $object->get_id() && 'importing' !== $object->get_status() ) {
				$updating = true;
			}

			if ( 'external' === $object->get_type() ) {
				unset( $data['manage_stock'], $data['stock_status'], $data['backorders'] );
			}

			if ( 'importing' === $object->get_status() ) {
				$object->set_status( 'publish' );
				$object->set_slug( '' );
			}

			$result = $object->set_props( array_diff_key( $data, array_flip( array( 'meta_data', 'raw_image_id', 'raw_gallery_image_ids', 'raw_attributes' ) ) ) );

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			if ( 'variation' === $object->get_type() ) {
				$this->set_variation_data( $object, $data );
			} else {
				$this->set_product_data( $object, $data );
			}

			$this->set_image_data( $object, $data );
			$this->set_meta_data( $object, $data );

			$object = apply_filters( 'woocommerce_product_import_pre_insert_product_object', $object, $data );
			$object->save();

			do_action( 'woocommerce_product_import_inserted_product_object', $object, $data );

			return array(
				'id'      => $object->get_id(),
				'updated' => $updating,
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_product_importer_error', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Convert raw image URLs to IDs and set.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $data    Item data.
	 */
	protected function set_image_data( &$product, $data ) {
		// Image URLs need converting to IDs before inserting.
		if ( isset( $data['raw_image_id'] ) ) {
			$product->set_image_id( $this->get_attachment_id_from_url( $data['raw_image_id'], $product->get_id() ) );
		}

		// Gallery image URLs need converting to IDs before inserting.
		if ( isset( $data['raw_gallery_image_ids'] ) ) {
			$gallery_image_ids = array();

			foreach ( $data['raw_gallery_image_ids'] as $image_id ) {
				$gallery_image_ids[] = $this->get_attachment_id_from_url( $image_id, $product->get_id() );
			}
			$product->set_gallery_image_ids( $gallery_image_ids );
		}
	}

	/**
	 * Append meta data.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $data    Item data.
	 */
	protected function set_meta_data( &$product, $data ) {
		if ( isset( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $meta ) {
				$product->update_meta_data( $meta['key'], $meta['value'] );
			}
		}
	}

	/**
	 * Set product data.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $data    Item data.
	 *
	 * @throws Exception
	 */
	protected function set_product_data( &$product, $data ) {
		if ( isset( $data['raw_attributes'] ) ) {
			$attributes         = array();
			$default_attributes = array();

			foreach ( $data['raw_attributes'] as $position => $attribute ) {
				$attribute_id = 0;

				// Get ID if is a global attribute.
				if ( ! empty( $attribute['taxonomy'] ) ) {
					$attribute_id = $this->get_attribute_taxonomy_id( $attribute['name'] );
				}

				// Set attribute visibility.
				if ( isset( $attribute['visible'] ) ) {
					$is_visible = $attribute['visible'];
				} else {
					$is_visible = 1;
				}

				// Set if is a variation attribute.
				$is_variation = 0;

				if ( $attribute_id ) {
					$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );

					if ( isset( $attribute['value'] ) ) {
						$options = array_map( 'wc_sanitize_term_text_based', $attribute['value'] );
						$options = array_filter( $options, 'strlen' );
					} else {
						$options = array();
					}

					// Check for default attributes and set "is_variation".
					if ( ! empty( $attribute['default'] ) && in_array( $attribute['default'], $options ) ) {
						$default_term = get_term_by( 'name', $attribute['default'], $attribute_name );

						if ( $default_term && ! is_wp_error( $default_term ) ) {
							$default = $default_term->slug;
						} else {
							$default = sanitize_title( $attribute['default'] );
						}

						$default_attributes[ $attribute_name ] = $default;
						$is_variation = 1;
					}

					if ( ! empty( $options ) ) {
						$attribute_object = new WC_Product_Attribute();
						$attribute_object->set_id( $attribute_id );
						$attribute_object->set_name( $attribute_name );
						$attribute_object->set_options( $options );
						$attribute_object->set_position( $position );
						$attribute_object->set_visible( $is_visible );
						$attribute_object->set_variation( $is_variation );
						$attributes[] = $attribute_object;
					}
				} elseif ( isset( $attribute['value'] ) ) {
					// Check for default attributes and set "is_variation".
					if ( ! empty( $attribute['default'] ) && in_array( $attribute['default'], $attribute['value'] ) ) {
						$default_attributes[ sanitize_title( $attribute['name'] ) ] = $attribute['default'];
						$is_variation = 1;
					}

					$attribute_object = new WC_Product_Attribute();
					$attribute_object->set_name( $attribute['name'] );
					$attribute_object->set_options( $attribute['value'] );
					$attribute_object->set_position( $position );
					$attribute_object->set_visible( $is_visible );
					$attribute_object->set_variation( $is_variation );
					$attributes[] = $attribute_object;
				}
			}

			$product->set_attributes( $attributes );

			// Set variable default attributes.
			if ( $product->is_type( 'variable' ) ) {
				$product->set_default_attributes( $default_attributes );
			}
		}
	}

	/**
	 * Set variation data.
	 *
	 * @param WC_Product $variation Product instance.
	 * @param array      $data    Item data.
	 *
	 * @return WC_Product|WP_Error
	 * @throws Exception
	 */
	protected function set_variation_data( &$variation, $data ) {
		$parent = false;

		// Check if parent exist.
		if ( isset( $data['parent_id'] ) ) {
			$parent = wc_get_product( $data['parent_id'] );

			if ( $parent ) {
				$variation->set_parent_id( $parent->get_id() );
			}
		}

		// Stop if parent does not exists.
		if ( ! $parent ) {
			return new WP_Error( 'woocommerce_product_importer_missing_variation_parent_id', __( 'Variation cannot be imported: Missing parent ID or parent does not exist yet.', 'woocommerce' ), array( 'status' => 401 ) );
		}

		if ( isset( $data['raw_attributes'] ) ) {
			$attributes        = array();
			$parent_attributes = $this->get_variation_parent_attributes( $data['raw_attributes'], $parent );

			foreach ( $data['raw_attributes'] as $attribute ) {
				// Get ID if is a global attribute.
				$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute['name'] );

				if ( $attribute_id ) {
					$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
				} else {
					$attribute_name = sanitize_title( $attribute['name'] );
				}

				if ( ! isset( $parent_attributes[ $attribute_name ] ) || ! $parent_attributes[ $attribute_name ]->get_variation() ) {
					continue;
				}

				$attribute_key   = sanitize_title( $parent_attributes[ $attribute_name ]->get_name() );
				$attribute_value = isset( $attribute['value'] ) ? current( $attribute['value'] ) : '';

				if ( $parent_attributes[ $attribute_name ]->is_taxonomy() ) {
					// If dealing with a taxonomy, we need to get the slug from the name posted to the API.
					$term = get_term_by( 'name', $attribute_value, $attribute_name );

					if ( $term && ! is_wp_error( $term ) ) {
						$attribute_value = $term->slug;
					} else {
						$attribute_value = sanitize_title( $attribute_value );
					}
				}

				$attributes[ $attribute_key ] = $attribute_value;
			}

			$variation->set_attributes( $attributes );
		}
	}

	/**
	 * Get variation parent attributes and set "is_variation".
	 *
	 * @param  array      $attributes Attributes list.
	 * @param  WC_Product $parent     Parent product data.
	 * @return array
	 */
	protected function get_variation_parent_attributes( $attributes, $parent ) {
		$parent_attributes = $parent->get_attributes();
		$require_save      = false;

		foreach ( $attributes as $attribute ) {
			$attribute_id = 0;

			// Get ID if is a global attribute.
			if ( ! empty( $attribute['taxonomy'] ) ) {
				$attribute_id = $this->get_attribute_taxonomy_id( $attribute['name'] );
			}

			if ( $attribute_id ) {
				$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
			} else {
				$attribute_name = sanitize_title( $attribute['name'] );
			}

			// Check if attribute handle variations.
			if ( isset( $parent_attributes[ $attribute_name ] ) && ! $parent_attributes[ $attribute_name ]->get_variation() ) {
				// Re-create the attribute to CRUD save and generate again.
				$parent_attributes[ $attribute_name ] = clone $parent_attributes[ $attribute_name ];
				$parent_attributes[ $attribute_name ]->set_variation( 1 );

				$require_save = true;
			}
		}

		// Save variation attributes.
		if ( $require_save ) {
			$parent->set_attributes( array_values( $parent_attributes ) );
			$parent->save();
		}

		return $parent_attributes;
	}

	/**
	 * Get attachment ID.
	 *
	 * @param  string $url        Attachment URL.
	 * @param  int    $product_id Product ID.
	 * @return int
	 */
	protected function get_attachment_id_from_url( $url, $product_id ) {
		if ( empty( $url ) ) {
			return 0;
		}

		$id         = 0;
		$upload_dir = wp_upload_dir( null, false );
		$base_url   = $upload_dir['baseurl'] . '/';

		// Check first if attachment is inside the WordPress uploads directory, or we're given a filename only.
		if ( false !== strpos( $url, $base_url ) || false === strpos( $url, '://' ) ) {
			// Search for yyyy/mm/slug.extension or slug.extension - remove the base URL.
			$file = str_replace( $base_url, '', $url );
			$args = array(
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'fields'      => 'ids',
				'meta_query'  => array(
					'relation' => 'OR',
					array(
						'key'     => '_wp_attached_file',
						'value'   => '^' . $file,
						'compare' => 'REGEXP',
					),
					array(
						'key'     => '_wp_attached_file',
						'value'   => '/' . $file,
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_wc_attachment_source',
						'value'   => '/' . $file,
						'compare' => 'LIKE',
					),
				),
			);
		} else {
			// This is an external URL, so compare to source.
			$args = array(
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'value' => $url,
						'key'   => '_wc_attachment_source',
					),
				),
			);
		}

		if ( $ids = get_posts( $args ) ) {
			$id = current( $ids );
		}

		// Upload if attachment does not exists.
		if ( ! $id && stristr( $url, '://' ) ) {
			$upload = wc_rest_upload_image_from_url( $url );

			if ( is_wp_error( $upload ) ) {
				throw new Exception( $upload->get_error_message(), 400 );
			}

			$id = wc_rest_set_uploaded_image_as_attachment( $upload, $product_id );

			if ( ! wp_attachment_is_image( $id ) ) {
				throw new Exception( sprintf( __( 'Not able to attach "%s".', 'woocommerce' ), $url ), 400 );
			}

			// Save attachment source for future reference.
			update_post_meta( $id, '_wc_attachment_source', $url );
		}

		if ( ! $id ) {
			throw new Exception( sprintf( __( 'Unable to use image "%s".', 'woocommerce' ), $url ), 400 );
		}

		return $id;
	}

	/**
	 * Get attribute taxonomy ID from the imported data.
	 * If does not exists register a new attribute.
	 *
	 * @param  string $name Attribute name.
	 * @return int
	 */
	protected function get_attribute_taxonomy_id( $raw_name ) {
		global $wpdb, $wc_product_attributes;

		// These are exported as labels, so convert the label to a name if possible first.
		$attribute_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
		$attribute_name   = '';

		if ( ! $attribute_name = array_search( $raw_name, $attribute_labels ) ) {
			$attribute_name = wc_sanitize_taxonomy_name( $raw_name );
		}

		// Get the ID from the name.
		if ( $attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name ) ) {
			return $attribute_id;
		}

		// If the attribute does not exist, create it.
		$attribute_id = wc_create_attribute( array(
			'name'         => $raw_name,
			'slug'         => $attribute_name,
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		) );

		if ( is_wp_error( $attribute_id ) ) {
			throw new Exception( $attribute_id->get_error_message(), 400 );
		}

		// Register as taxonomy while importing.
		$taxonomy_name = wc_attribute_taxonomy_name( $attribute_name );
		register_taxonomy(
			$taxonomy_name,
			apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
			apply_filters( 'woocommerce_taxonomy_args_' . $taxonomy_name, array(
				'labels'       => array(
					'name' => $raw_name,
				),
				'hierarchical' => true,
				'show_ui'      => false,
				'query_var'    => true,
				'rewrite'      => false,
			) )
		);

		// Set product attributes global.
		$wc_product_attributes = array();

		foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
			$wc_product_attributes[ wc_attribute_taxonomy_name( $taxonomy->attribute_name ) ] = $taxonomy;
		}

		return $attribute_id;
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;
		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}
		return apply_filters( 'woocommerce_product_importer_memory_exceeded', $return );
	}

	/**
	 * Get memory limit
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}
		return intval( $memory_limit ) * 1024 * 1024;
	}

	/**
	 * Time exceeded.
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	protected function time_exceeded() {
		$finish = $this->start_time + apply_filters( 'woocommerce_product_importer_default_time_limit', 20 ); // 20 seconds
		$return = false;
		if ( time() >= $finish ) {
			$return = true;
		}
		return apply_filters( 'woocommerce_product_importer_time_exceeded', $return );
	}

	/**
	 * Explode CSV cell values using commas by default, and handling escaped
	 * separators.
	 *
	 * @since  3.2.0
	 * @param  string $value
	 * @return array
	 */
	protected function explode_values( $value ) {
		$value  = str_replace( '\\,', '::separator::', $value );
		$values = explode( ',', $value );
		$values = array_map( array( $this, 'explode_values_formatter' ), $values );

		return $values;
	}

	/**
	 * Remove formatting and trim each value.
	 *
	 * @since  3.2.0
	 * @param  string $value
	 * @return string
	 */
	protected function explode_values_formatter( $value ) {
		return trim( str_replace( '::separator::', ',', $value ) );
	}

	/**
	 * The exporter prepends a ' to fields that start with a - which causes
	 * issues with negative numbers. This removes the ' if the input is still a valid
	 * number after removal.
	 *
	 * @since 3.3.0
	 * @param string $value A numeric string that may or may not have ' prepended.
	 * @return string
	 */
	protected function unescape_negative_number( $value ) {
		if ( 0 === strpos( $value, "'-" ) ) {
			$unescaped = substr_replace( $value, '', 0, 1 );
			if ( is_numeric( $unescaped ) ) {
				return $unescaped;
			}
		}

		return $value;
	}
}
