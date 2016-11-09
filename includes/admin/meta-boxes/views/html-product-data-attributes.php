<div id="product_attributes" class="panel wc-metaboxes-wrapper hidden">
	<div class="toolbar toolbar-top">
		<span class="expand-close">
			<a href="#" class="expand_all"><?php _e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php _e( 'Close', 'woocommerce' ); ?></a>
		</span>
		<select name="attribute_taxonomy" class="attribute_taxonomy">
			<option value=""><?php _e( 'Custom product attribute', 'woocommerce' ); ?></option>
			<?php
				global $wc_product_attributes;

				// Array of defined attribute taxonomies
				$attribute_taxonomies = wc_get_attribute_taxonomies();

				if ( ! empty( $attribute_taxonomies ) ) {
					foreach ( $attribute_taxonomies as $tax ) {
						$attribute_taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
						$label = $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name;
						echo '<option value="' . esc_attr( $attribute_taxonomy_name ) . '">' . esc_html( $label ) . '</option>';
					}
				}
			?>
		</select>
		<button type="button" class="button add_attribute"><?php _e( 'Add', 'woocommerce' ); ?></button>
	</div>
	<div class="product_attributes wc-metaboxes">
		<?php
			// Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set
			$attributes = $product_object->get_attributes( 'edit' );
			$i          = -1;

			foreach ( $attributes as $attribute ) {
				$i++;
				$metabox_class = array();

				if ( $attribute->is_taxonomy() ) {
					$metabox_class[] = 'taxonomy';
					$metabox_class[] = $attribute->get_name();
				}

				include( 'html-product-attribute.php' );
			}
		?>
	</div>
	<div class="toolbar">
		<span class="expand-close">
			<a href="#" class="expand_all"><?php _e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php _e( 'Close', 'woocommerce' ); ?></a>
		</span>
		<button type="button" class="button save_attributes button-primary"><?php _e( 'Save attributes', 'woocommerce' ); ?></button>
	</div>
	<?php do_action( 'woocommerce_product_options_attributes' ); ?>
</div>
