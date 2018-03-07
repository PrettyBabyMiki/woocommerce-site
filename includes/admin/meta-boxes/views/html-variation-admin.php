<?php
/**
 * Outputs a variation for editing.
 *
 * @var int $variation_id
 * @var WP_POST $variation
 * @var WC_Product_Variation $variation_object
 * @var array $variation_data array of variation data @deprecated.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="woocommerce_variation wc-metabox closed">
	<h3>
		<a href="#" class="remove_variation delete" rel="<?php echo esc_attr( $variation_id ); ?>"><?php esc_html_e( 'Remove', 'woocommerce' ); ?></a>
		<div class="handlediv" aria-label="<?php esc_attr_e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<div class="tips sort" data-tip="<?php esc_attr_e( 'Drag and drop, or click to set admin variation order', 'woocommerce' ); ?>"></div>
		<strong>#<?php echo esc_html( $variation_id ); ?> </strong>
		<?php
		$attribute_values = $variation_object->get_attributes( 'edit' );

		foreach ( $product_object->get_attributes( 'edit' ) as $attribute ) {
			if ( ! $attribute->get_variation() ) {
				continue;
			}
			$selected_value = isset( $attribute_values[ sanitize_title( $attribute->get_name() ) ] ) ? $attribute_values[ sanitize_title( $attribute->get_name() ) ] : '';
			?>
			<select name="attribute_<?php echo sanitize_title( $attribute->get_name() ) . "[{$loop}]"; ?>">
				<option value="">
					<?php
					/* translators: %s: attribute label */
					printf( esc_html__( 'Any %s&hellip;', 'woocommerce' ), wc_attribute_label( $attribute->get_name() ) );
					?>
				</option>
				<?php if ( $attribute->is_taxonomy() ) : ?>
					<?php foreach ( $attribute->get_terms() as $option ) : ?>
						<option <?php selected( $selected_value, $option->slug ); ?> value="<?php echo esc_attr( $option->slug ); ?>"><?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $option->name ) ); ?></option>
					<?php endforeach; ?>
				<?php else : ?>
					<?php foreach ( $attribute->get_options() as $option ) : ?>
						<option <?php selected( $selected_value, $option ); ?> value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
			<?php
		}
		?>
		<input type="hidden" name="variable_post_id[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />
		<input type="hidden" class="variation_menu_order" name="variation_menu_order[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $variation_object->get_menu_order( 'edit' ) ); ?>" />
	</h3>
	<div class="woocommerce_variable_attributes wc-metabox-content" style="display: none;">
		<div class="data">
			<p class="form-row form-row-first upload_image">
				<a href="#" class="upload_image_button tips <?php echo $variation_object->get_image_id( 'edit' ) ? 'remove' : ''; ?>" data-tip="<?php echo $variation_object->get_image_id( 'edit' ) ? esc_attr__( 'Remove this image', 'woocommerce' ) : esc_attr__( 'Upload an image', 'woocommerce' ); ?>" rel="<?php echo esc_attr( $variation_id ); ?>">
					<img src="<?php echo $variation_object->get_image_id( 'edit' ) ? esc_url( wp_get_attachment_thumb_url( $variation_object->get_image_id( 'edit' ) ) ) : esc_url( wc_placeholder_img_src() ); ?>" /><input type="hidden" name="upload_image_id[<?php echo esc_attr( $loop ); ?>]" class="upload_image_id" value="<?php echo esc_attr( $variation_object->get_image_id( 'edit' ) ); ?>" />
				</a>
			</p>
			<?php
			if ( wc_product_sku_enabled() ) {
				woocommerce_wp_text_input(
					array(
						'id'            => "variable_sku{$loop}",
						'name'          => "variable_sku[{$loop}]",
						'value'         => $variation_object->get_sku( 'edit' ),
						'placeholder'   => $variation_object->get_sku(),
						'label'         => '<abbr title="' . esc_attr__( 'Stock Keeping Unit', 'woocommerce' ) . '">' . esc_html__( 'SKU', 'woocommerce' ) . '</abbr>',
						'desc_tip'      => true,
						'description'   => __( 'SKU refers to a Stock-keeping unit, a unique identifier for each distinct product and service that can be purchased.', 'woocommerce' ),
						'wrapper_class' => 'form-row form-row-last',
					)
				);
			}
			?>
			<p class="form-row form-row-full options">
				<label>
					<?php esc_html_e( 'Enabled', 'woocommerce' ); ?>:
					<input type="checkbox" class="checkbox" name="variable_enabled[<?php echo $loop; ?>]" <?php checked( in_array( $variation_object->get_status( 'edit' ), array( 'publish', false ), true ), true ); ?> />
				</label>
				<label class="tips" data-tip="<?php esc_html_e( 'Enable this option if access is given to a downloadable file upon purchase of a product', 'woocommerce' ); ?>">
					<?php esc_html_e( 'Downloadable', 'woocommerce' ); ?>:
					<input type="checkbox" class="checkbox variable_is_downloadable" name="variable_is_downloadable[<?php echo esc_attr( $loop ); ?>]" <?php checked( $variation_object->get_downloadable( 'edit' ), true ); ?> />
				</label>
				<label class="tips" data-tip="<?php esc_html_e( 'Enable this option if a product is not shipped or there is no shipping cost', 'woocommerce' ); ?>">
					<?php esc_html_e( 'Virtual', 'woocommerce' ); ?>:
					<input type="checkbox" class="checkbox variable_is_virtual" name="variable_is_virtual[<?php echo esc_attr( $loop ); ?>]" <?php checked( $variation_object->get_virtual( 'edit' ), true ); ?> />
				</label>

				<?php if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) : ?>
					<label class="tips" data-tip="<?php esc_html_e( 'Enable this option to enable stock management at variation level', 'woocommerce' ); ?>">
						<?php esc_html_e( 'Manage stock?', 'woocommerce' ); ?>
						<input type="checkbox" class="checkbox variable_manage_stock" name="variable_manage_stock[<?php echo esc_attr( $loop ); ?>]" <?php checked( $variation_object->get_manage_stock( 'edit' ), true ); ?> />
					</label>
				<?php endif; ?>

				<?php do_action( 'woocommerce_variation_options', $loop, $variation_data, $variation ); ?>
			</p>

			<div class="variable_pricing">
				<?php
				$label = sprintf(
					/* translators: %s: currency symbol */
					__( 'Regular price (%s)', 'woocommerce' ),
					get_woocommerce_currency_symbol()
				);

				woocommerce_wp_text_input(
					array(
						'id'            => "variable_regular_price_{$loop}",
						'name'          => "variable_regular_price[{$loop}]",
						'value'         => wc_format_localized_price( $variation_object->get_regular_price( 'edit' ) ),
						'label'         => $label,
						'data_type'     => 'price',
						'wrapper_class' => 'form-row form-row-first',
						'placeholder'   => __( 'Variation price (required)', 'woocommerce' ),
					)
				);

				$label = sprintf(
					/* translators: %s: currency symbol */
					__( 'Sale price (%s)', 'woocommerce' ),
					get_woocommerce_currency_symbol()
				);

				woocommerce_wp_text_input(
					array(
						'id'            => "variable_sale_price{$loop}",
						'name'          => "variable_sale_price[{$loop}]",
						'value'         => wc_format_localized_price( $variation_object->get_sale_price( 'edit' ) ),
						'data_type'     => 'price',
						'label'         => $label . ' <a href="#" class="sale_schedule">' . esc_html__( 'Schedule', 'woocommerce' ) . '</a><a href="#" class="cancel_sale_schedule hidden">' . esc_html__( 'Cancel schedule', 'woocommerce' ) . '</a>',
						'wrapper_class' => 'form-row form-row-last',
					)
				);

				$sale_price_dates_from = $variation_object->get_date_on_sale_from( 'edit' ) && ( $date = $variation_object->get_date_on_sale_from( 'edit' )->getOffsetTimestamp() ) ? date_i18n( 'Y-m-d', $date ) : '';
				$sale_price_dates_to   = $variation_object->get_date_on_sale_to( 'edit' ) && ( $date = $variation_object->get_date_on_sale_to( 'edit' )->getOffsetTimestamp() ) ? date_i18n( 'Y-m-d', $date ) : '';

				echo '<div class="form-field sale_price_dates_fields hidden">
					<p class="form-row form-row-first">
						<label>' . __( 'Sale start date', 'woocommerce' ) . '</label>
						<input type="text" class="sale_price_dates_from" name="variable_sale_price_dates_from[' . $loop . ']" value="' . esc_attr( $sale_price_dates_from ) . '" placeholder="' . _x( 'From&hellip;', 'placeholder', 'woocommerce' ) . ' YYYY-MM-DD" maxlength="10" pattern="' . esc_attr( apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ) . '" />
					</p>
					<p class="form-row form-row-last">
						<label>' . __( 'Sale end date', 'woocommerce' ) . '</label>
						<input type="text" class="sale_price_dates_to" name="variable_sale_price_dates_to[' . esc_attr( $loop ) . ']" value="' . esc_attr( $sale_price_dates_to ) . '" placeholder="' . esc_html_x( 'To&hellip;', 'placeholder', 'woocommerce' ) . '  YYYY-MM-DD" maxlength="10" pattern="' . esc_attr( apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ) . '" />
					</p>
				</div>';

				/**
				 * woocommerce_variation_options_pricing action.
				 *
				 * @since 2.5.0
				 *
				 * @param int     $loop
				 * @param array   $variation_data
				 * @param WP_Post $variation
				 */
				do_action( 'woocommerce_variation_options_pricing', $loop, $variation_data, $variation );
				?>
			</div>

			<?php if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) : ?>
				<div class="show_if_variation_manage_stock" style="display: none;">
					<?php
					woocommerce_wp_text_input(
						array(
							'id'                => "variable_stock{$loop}",
							'name'              => "variable_stock[{$loop}]",
							'value'             => wc_stock_amount( $variation_object->get_stock_quantity( 'edit' ) ),
							'label'             => __( 'Stock quantity', 'woocommerce' ),
							'desc_tip'          => true,
							'description'       => __( "Enter a quantity to enable stock management at variation level, or leave blank to use the parent product's options.", 'woocommerce' ),
							'type'              => 'number',
							'custom_attributes' => array(
								'step' => 'any',
							),
							'data_type'         => 'stock',
							'wrapper_class'     => 'form-row form-row-first',
						)
					);

					echo '<input type="hidden" name="variable_original_stock[' . esc_attr( $loop ) . ']" value="' . esc_attr( wc_stock_amount( $variation_object->get_stock_quantity( 'edit' ) ) ) . '" />';

					woocommerce_wp_select(
						array(
							'id'            => "variable_backorders{$loop}",
							'name'          => "variable_backorders[{$loop}]",
							'value'         => $variation_object->get_backorders( 'edit' ),
							'label'         => __( 'Allow backorders?', 'woocommerce' ),
							'options'       => wc_get_product_backorder_options(),
							'desc_tip'      => true,
							'description'   => __( 'If managing stock, this controls whether or not backorders are allowed. If enabled, stock quantity can go below 0.', 'woocommerce' ),
							'wrapper_class' => 'form-row form-row-last',
						)
					);

					/**
					 * woocommerce_variation_options_inventory action.
					 *
					 * @since 2.5.0
					 *
					 * @param int     $loop
					 * @param array   $variation_data
					 * @param WP_Post $variation
					 */
					do_action( 'woocommerce_variation_options_inventory', $loop, $variation_data, $variation );
					?>
				</div>
			<?php endif; ?>

			<div>
				<?php
				woocommerce_wp_select(
					array(
						'id'            => "variable_stock_status{$loop}",
						'name'          => "variable_stock_status[{$loop}]",
						'value'         => $variation_object->get_stock_status( 'edit' ),
						'label'         => __( 'Stock status', 'woocommerce' ),
						'options'       => wc_get_product_stock_status_options(),
						'desc_tip'      => true,
						'description'   => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', 'woocommerce' ),
						'wrapper_class' => 'form-row form-row-full hide_if_variation_manage_stock',
					)
				);

				if ( wc_product_weight_enabled() ) {
					$label = sprintf(
						/* translators: %s: weight unit */
						__( 'Weight (%s)', 'woocommerce' ),
						esc_html( get_option( 'woocommerce_weight_unit' ) )
					);

					woocommerce_wp_text_input(
						array(
							'id'            => "variable_weight{$loop}",
							'name'          => "variable_weight[{$loop}]",
							'value'         => wc_format_localized_decimal( $variation_object->get_weight( 'edit' ) ),
							'placeholder'   => wc_format_localized_decimal( $product_object->get_weight() ),
							'label'         => $label,
							'desc_tip'      => true,
							'description'   => __( 'Weight in decimal form', 'woocommerce' ),
							'type'          => 'text',
							'data_type'     => 'decimal',
							'wrapper_class' => 'form-row form-row-first hide_if_variation_virtual',
						)
					);
				}

				if ( wc_product_dimensions_enabled() ) {
					$parent_length = wc_format_localized_decimal( $product_object->get_length() );
					$parent_width  = wc_format_localized_decimal( $product_object->get_width() );
					$parent_height = wc_format_localized_decimal( $product_object->get_height() );

					?>
					<p class="form-field form-row dimensions_field hide_if_variation_virtual form-row-last">
						<label for="product_length">
							<?php
							printf(
								/* translators: %s: dimension unit */
								esc_html__( 'Dimensions (L&times;W&times;H) (%s)', 'woocommerce' ),
								get_option( 'woocommerce_dimension_unit' )
							);
							?>
						</label>
						<?php echo wc_help_tip( __( 'Length x width x height in decimal form', 'woocommerce' ) ); ?>
						<span class="wrap">
							<input id="product_length" placeholder="<?php echo $parent_length ? esc_attr( $parent_length ) : esc_attr__( 'Length', 'woocommerce' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="variable_length[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( wc_format_localized_decimal( $variation_object->get_length( 'edit' ) ) ); ?>" />
							<input placeholder="<?php echo $parent_width ? esc_attr( $parent_width ) : esc_attr__( 'Width', 'woocommerce' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="variable_width[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( wc_format_localized_decimal( $variation_object->get_width( 'edit' ) ) ); ?>" />
							<input placeholder="<?php echo $parent_height ? esc_attr( $parent_height ) : esc_attr__( 'Height', 'woocommerce' ); ?>" class="input-text wc_input_decimal last" size="6" type="text" name="variable_height[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( wc_format_localized_decimal( $variation_object->get_height( 'edit' ) ) ); ?>" />
						</span>
					</p>
					<?php
				}

				/**
				 * woocommerce_variation_options_dimensions action.
				 *
				 * @since 2.5.0
				 *
				 * @param int     $loop
				 * @param array   $variation_data
				 * @param WP_Post $variation
				 */
				do_action( 'woocommerce_variation_options_dimensions', $loop, $variation_data, $variation );
				?>
			</div>

			<div>
				<p class="form-row hide_if_variation_virtual form-row-full">
					<label><?php esc_html_e( 'Shipping class', 'woocommerce' ); ?></label>
					<?php
					wp_dropdown_categories(
						array(
							'taxonomy'         => 'product_shipping_class',
							'hide_empty'       => 0,
							'show_option_none' => __( 'Same as parent', 'woocommerce' ),
							'name'             => 'variable_shipping_class[' . $loop . ']',
							'id'               => '',
							'selected'         => $variation_object->get_shipping_class_id( 'edit' ),
						)
					);
					?>
				</p>

				<?php
				if ( wc_tax_enabled() ) {
					woocommerce_wp_select(
						array(
							'id'            => "variable_tax_class{$loop}",
							'name'          => "variable_tax_class[{$loop}]",
							'value'         => $variation_object->get_tax_class( 'edit' ),
							'label'         => __( 'Tax class', 'woocommerce' ),
							'options'       => array_merge( array( 'parent' => __( 'Same as parent', 'woocommerce' ) ), wc_get_product_tax_class_options() ),
							'desc_tip'      => 'true',
							'description'   => __( 'Choose a tax class for this product. Tax classes are used to apply different tax rates specific to certain types of product.', 'woocommerce' ),
							'wrapper_class' => 'form-row form-row-full',
						)
					);

					/**
					 * woocommerce_variation_options_tax action.
					 *
					 * @since 2.5.0
					 *
					 * @param int     $loop
					 * @param array   $variation_data
					 * @param WP_Post $variation
					 */
					do_action( 'woocommerce_variation_options_tax', $loop, $variation_data, $variation );
				}
				?>
			</div>
			<div>
				<?php
				woocommerce_wp_textarea_input(
					array(
						'id'            => "variable_description{$loop}",
						'name'          => "variable_description[{$loop}]",
						'value'         => $variation_object->get_description( 'edit' ),
						'label'         => __( 'Description', 'woocommerce' ),
						'desc_tip'      => true,
						'description'   => __( 'Enter an optional description for this variation.', 'woocommerce' ),
						'wrapper_class' => 'form-row form-row-full',
					)
				);
				?>
			</div>
			<div class="show_if_variation_downloadable" style="display: none;">
				<div class="form-row form-row-full downloadable_files">
					<label><?php esc_html_e( 'Downloadable files', 'woocommerce' ); ?></label>
					<table class="widefat">
						<thead>
							<div>
								<th><?php esc_html_e( 'Name', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the name of the download shown to the customer.', 'woocommerce' ) ); ?></th>
								<th colspan="2"><?php esc_html_e( 'File URL', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the URL or absolute path to the file which customers will get access to. URLs entered here should already be encoded.', 'woocommerce' ) ); ?></th>
								<th>&nbsp;</th>
							</div>
						</thead>
						<tbody>
							<?php
							if ( $downloads = $variation_object->get_downloads( 'edit' ) ) {
								foreach ( $downloads as $key => $file ) {
									include 'html-product-variation-download.php';
								}
							}
							?>
						</tbody>
						<tfoot>
							<div>
								<th colspan="4">
									<a href="#" class="button insert" data-row="
									<?php
									$key  = '';
									$file = array(
										'file' => '',
										'name' => '',
									);
									ob_start();
									require 'html-product-variation-download.php';
									echo esc_attr( ob_get_clean() );
									?>
									"><?php esc_html_e( 'Add file', 'woocommerce' ); ?></a>
								</th>
							</div>
						</tfoot>
					</table>
				</div>
			</div>
			<div class="show_if_variation_downloadable" style="display: none;">
				<?php
				woocommerce_wp_text_input(
					array(
						'id'                => "variable_download_limit{$loop}",
						'name'              => "variable_download_limit[{$loop}]",
						'value'             => $variation_object->get_download_limit( 'edit' ) < 0 ? '' : $variation_object->get_download_limit( 'edit' ),
						'label'             => __( 'Download limit', 'woocommerce' ),
						'placeholder'       => __( 'Unlimited', 'woocommerce' ),
						'description'       => __( 'Leave blank for unlimited re-downloads.', 'woocommerce' ),
						'type'              => 'number',
						'desc_tip'          => true,
						'custom_attributes' => array(
							'step' => '1',
							'min'  => '0',
						),
						'wrapper_class'     => 'form-row form-row-first',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => "variable_download_expiry{$loop}",
						'name'              => "variable_download_expiry[{$loop}]",
						'value'             => $variation_object->get_download_expiry( 'edit' ) < 0 ? '' : $variation_object->get_download_expiry( 'edit' ),
						'label'             => __( 'Download expiry', 'woocommerce' ),
						'placeholder'       => __( 'Never', 'woocommerce' ),
						'description'       => __( 'Enter the number of days before a download link expires, or leave blank.', 'woocommerce' ),
						'type'              => 'number',
						'desc_tip'          => true,
						'custom_attributes' => array(
							'step' => '1',
							'min'  => '0',
						),
						'wrapper_class'     => 'form-row form-row-last',
					)
				);

				/**
				 * woocommerce_variation_options_download action.
				 *
				 * @since 2.5.0
				 *
				 * @param int     $loop
				 * @param array   $variation_data
				 * @param WP_Post $variation
				 */
				do_action( 'woocommerce_variation_options_download', $loop, $variation_data, $variation );
				?>
			</div>
			<?php do_action( 'woocommerce_product_after_variable_attributes', $loop, $variation_data, $variation ); ?>
		</div>
	</div>
</div>
