<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="woocommerce_variation wc-metabox closed">
	<h3>
		<button type="button" class="remove_variation button" rel="<?php echo esc_attr( $variation_id ); ?>"><?php _e( 'Remove', 'woocommerce' ); ?></button>
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<strong>#<?php echo esc_html( $variation_id ); ?> &mdash; </strong>
		<?php
			foreach ( $parent_data['attributes'] as $attribute ) {

				// Only deal with attributes that are variations
				if ( ! $attribute['is_variation'] )
					continue;

				// Get current value for variation (if set)
				$variation_selected_value = isset( $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] ) ? $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] : '';

				// Name will be something like attribute_pa_color
				echo '<select name="attribute_' . sanitize_title( $attribute['name'] ) . '[' . $loop . ']"><option value="">' . __( 'Any', 'woocommerce' ) . ' ' . esc_html( $woocommerce->attribute_label( $attribute['name'] ) ) . '&hellip;</option>';

				// Get terms for attribute taxonomy or value if its a custom attribute
				if ( $attribute['is_taxonomy'] ) {

					$post_terms = wp_get_post_terms( $parent_data['id'], $attribute['name'] );

					foreach ( $post_terms as $term ) {
						echo '<option ' . selected( $variation_selected_value, $term->slug, false ) . ' value="' . esc_attr( $term->slug ) . '">' . apply_filters( 'woocommerce_variation_option_name', esc_html( $term->name ) ) . '</option>';
					}

				} else {

					$options = array_map( 'trim', explode( '|', $attribute['value'] ) );

					foreach ( $options as $option ) {
						echo '<option ' . selected( sanitize_title( $variation_selected_value ), sanitize_title( $option ), false ) . ' value="' . esc_attr( sanitize_title( $option ) ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
					}

				}

				echo '</select>';
			}
		?>
		<input type="hidden" name="variable_post_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />
		<input type="hidden" class="variation_menu_order" name="variation_menu_order[<?php echo $loop; ?>]" value="<?php echo $loop; ?>" />
	</h3>
	<table cellpadding="0" cellspacing="0" class="woocommerce_variable_attributes wc-metabox-content">
		<tbody>
			<tr>
				<td class="sku" colspan="2">
					<?php if ( get_option( 'woocommerce_enable_sku', true ) !== 'no' ) : ?>
						<label><?php _e( 'SKU', 'woocommerce' ); ?>: <a class="tips" data-tip="<?php _e( 'Enter a SKU for this variation or leave blank to use the parent product SKU.', 'woocommerce' ); ?>" href="#">[?]</a></label>
						<input type="text" size="5" name="variable_sku[<?php echo $loop; ?>]" value="<?php if ( isset( $_sku ) ) echo esc_attr( $_sku ); ?>" placeholder="<?php echo esc_attr( $parent_data['sku'] ); ?>" />
					<?php else : ?>
						<input type="hidden" name="variable_sku[<?php echo $loop; ?>]" value="<?php if ( isset( $_sku ) ) echo esc_attr( $_sku ); ?>" />
					<?php endif; ?>
				</td>
				<td class="data" rowspan="2">
					<table cellspacing="0" cellpadding="0" class="data_table">
						<?php if ( get_option( 'woocommerce_manage_stock' ) == 'yes' ) : ?>
							<tr>
								<td>
									<label><?php _e( 'Stock Qty:', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Enter a quantity to enable stock management at variation level, or leave blank to use the parent product\'s options.', 'woocommerce' ); ?>" href="#">[?]</a></label>
									<input type="number" size="5" name="variable_stock[<?php echo $loop; ?>]" value="<?php if ( isset( $_stock ) ) echo esc_attr( $_stock ); ?>" step="any" />
								</td>
								<td>&nbsp;</td>
							</tr>
						<?php endif; ?>

						<tr>
							<td>
								<label><?php echo __( 'Regular Price:', 'woocommerce' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
								<input type="number" size="5" name="variable_regular_price[<?php echo $loop; ?>]" value="<?php if ( isset( $_regular_price ) ) echo esc_attr( $_regular_price ); ?>" step="any" min="0" placeholder="<?php _e( 'Variation price (required)', 'woocommerce' ); ?>" />
							</td>
							<td>
								<label><?php echo __( 'Sale Price:', 'woocommerce' ) . ' ('.get_woocommerce_currency_symbol().')'; ?> <a href="#" class="sale_schedule"><?php _e( 'Schedule', 'woocommerce' ); ?></a><a href="#" class="cancel_sale_schedule" style="display:none"><?php _e( 'Cancel schedule', 'woocommerce' ); ?></a></label>
								<input type="number" size="5" name="variable_sale_price[<?php echo $loop; ?>]" value="<?php if ( isset( $_sale_price ) ) echo esc_attr( $_sale_price ); ?>" step="any" min="0" />
							</td>
						</tr>

						<tr class="sale_price_dates_fields" style="display:none">
							<td>
								<label><?php _e( 'Sale start date:', 'woocommerce' ) ?></label>
								<input type="text" class="sale_price_dates_from" name="variable_sale_price_dates_from[<?php echo $loop; ?>]" value="<?php echo ! empty( $_sale_price_dates_from ) ? date_i18n( 'Y-m-d', $_sale_price_dates_from ) : ''; ?>" placeholder="<?php echo _x( 'From&hellip;', 'placeholder', 'woocommerce' ) ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
							</td>
							<td>
								<label><?php _e( 'Sale end date:', 'woocommerce' ) ?></label>
								<input type="text" name="variable_sale_price_dates_to[<?php echo $loop; ?>]" value="<?php echo ! empty( $_sale_price_dates_to ) ? date_i18n( 'Y-m-d', $_sale_price_dates_to ) : ''; ?>" placeholder="<?php echo _x('To&hellip;', 'placeholder', 'woocommerce') ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
							</td>
						</tr>

						<?php if ( get_option( 'woocommerce_enable_weight', true ) !== 'no' || get_option( 'woocommerce_enable_dimensions', true ) !== 'no' ) : ?>
							<tr>
								<?php if ( get_option( 'woocommerce_enable_weight', true ) !== 'no' ) : ?>
									<td class="hide_if_variation_virtual">
										<label><?php _e( 'Weight', 'woocommerce' ) . ' (' . esc_html( get_option( 'woocommerce_weight_unit' ) ) . '):'; ?> <a class="tips" data-tip="<?php _e( 'Enter a weight for this variation or leave blank to use the parent product weight.', 'woocommerce' ); ?>" href="#">[?]</a></label>
										<input type="number" size="5" name="variable_weight[<?php echo $loop; ?>]" value="<?php if ( isset( $_weight ) ) echo esc_attr( $_weight ); ?>" placeholder="<?php echo esc_attr( $parent_data['weight'] ); ?>" step="any" min="0" />
									</td>
								<?php else : ?>
									<td>&nbsp;</td>
								<?php endif; ?>
								<?php if ( get_option( 'woocommerce_enable_dimensions', true ) !== 'no' ) : ?>
									<td class="dimensions_field hide_if_variation_virtual">
										<label for"product_length"><?php echo __( 'Dimensions (L&times;W&times;H)', 'woocommerce' ); ?></label>
										<input id="product_length" class="input-text" size="6" type="number" step="any" min="0" name="variable_length[<?php echo $loop; ?>]" value="<?php if ( isset( $_length ) ) echo esc_attr( $_length ); ?>" placeholder="<?php echo esc_attr( $parent_data['length'] ); ?>" />
										<input class="input-text" size="6" type="number" step="any" min="0" name="variable_width[<?php echo $loop; ?>]" value="<?php if ( isset( $_width ) ) echo esc_attr( $_width ); ?>" placeholder="<?php echo esc_attr( $parent_data['width'] ); ?>" />
										<input class="input-text last" size="6" type="number" step="any" min="0" name="variable_height[<?php echo $loop; ?>]" value="<?php if ( isset( $_height ) ) echo esc_attr( $_height ); ?>" placeholder="<?php echo esc_attr( $parent_data['height'] ); ?>" />
									</td>
								<?php else : ?>
									<td>&nbsp;</td>
								<?php endif; ?>
							</tr>
						<?php endif; ?>
						<tr>
							<td><label><?php _e( 'Shipping class:', 'woocommerce' ); ?></label> <?php
								$args = array(
									'taxonomy' 			=> 'product_shipping_class',
									'hide_empty'		=> 0,
									'show_option_none' 	=> __( 'Same as parent', 'woocommerce' ),
									'name' 				=> 'variable_shipping_class[' . $loop . ']',
									'id'				=> '',
									'selected'			=> isset( $shipping_class ) ? esc_attr( $shipping_class ) : '',
									'echo'				=> 0
								);

								echo wp_dropdown_categories( $args );
							?></td>
							<td>
								<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>
								<label><?php _e( 'Tax class:', 'woocommerce' ); ?></label>
								<select name="variable_tax_class[<?php echo $loop; ?>]"><?php
									foreach ( $parent_data['tax_class_options'] as $key => $value )
										echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $_tax_class, false ) . '>' . esc_html( $value ) . '</option>';
								?></select>
								<?php endif; ?>
							</td>
						</tr>
						<tr class="show_if_variation_downloadable">
							<td rowspan="2">
								<div class="file_path_field">
									<label><?php _e( 'File paths:', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Enter one or more File Paths, one per line, to make this variation a downloadable product, or leave blank.', 'woocommerce' ); ?>" href="#">[?]</a></label>
									<textarea style="float:left;" class="short file_paths" cols="20" rows="2" placeholder="<?php _e( 'File paths/URLs, one per line', 'woocommerce' ); ?>" name="variable_file_paths[<?php echo $loop; ?>]" wrap="off"><?php if ( isset( $_file_paths ) ) echo esc_textarea( $_file_paths ); ?></textarea>
									<input type="button" class="upload_file_button button" value="<?php _e( 'Choose a file', 'woocommerce' ); ?>" title="<?php _e( 'Upload', 'woocommerce' ); ?>" data-choose="<?php _e( 'Choose a file', 'woocommerce' ); ?>" data-update="<?php _e( 'Insert file URL', 'woocommerce' ); ?>" value="<?php _e( 'Choose a file', 'woocommerce' ); ?>" />
								</div>
							</td>
							<td>
								<div>
									<label><?php _e( 'Download Limit:', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Leave blank for unlimited re-downloads.', 'woocommerce' ); ?>" href="#">[?]</a></label>
									<input type="number" size="5" name="variable_download_limit[<?php echo $loop; ?>]" value="<?php if ( isset( $_download_limit ) ) echo esc_attr( $_download_limit ); ?>" placeholder="<?php _e( 'Unlimited', 'woocommerce' ); ?>" step="1" min="0" />
								</div>
							</td>
						</tr>
						<tr class="show_if_variation_downloadable">
							<td>
								<div>
									<label><?php _e( 'Download Expiry:', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Enter the number of days before a download link expires, or leave blank.', 'woocommerce' ); ?>" href="#">[?]</a></label>
									<input type="number" size="5" name="variable_download_expiry[<?php echo $loop; ?>]" value="<?php if ( isset( $_download_expiry ) ) echo esc_attr( $_download_expiry ); ?>" placeholder="<?php _e( 'Unlimited', 'woocommerce' ); ?>" step="1" min="0" />
								</div>
							</td>
						</tr>
						<?php do_action( 'woocommerce_product_after_variable_attributes', $loop, $variation_data, $variation ); ?>
					</table>
				</td>
			</tr>
			<tr>
				<td class="upload_image">
					<a href="#" class="upload_image_button <?php if ( $image_id > 0 ) echo 'remove'; ?>" rel="<?php echo esc_attr( $variation_id ); ?>"><img src="<?php if ( ! empty( $image ) ) echo esc_attr( $image ); else echo esc_attr( woocommerce_placeholder_img_src() ); ?>" /><input type="hidden" name="upload_image_id[<?php echo $loop; ?>]" class="upload_image_id" value="<?php echo esc_attr( $image_id ); ?>" /><span class="overlay"></span></a>
				</td>
				<td class="options">
					<label><input type="checkbox" class="checkbox" name="variable_enabled[<?php echo $loop; ?>]" <?php checked( $variation_post_status, 'publish' ); ?> /> <?php _e( 'Enabled', 'woocommerce' ); ?></label>

					<label><input type="checkbox" class="checkbox variable_is_downloadable" name="variable_is_downloadable[<?php echo $loop; ?>]" <?php checked( isset( $_downloadable ) ? $_downloadable : '', 'yes' ); ?> /> <?php _e( 'Downloadable', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Enable this option if access is given to a downloadable file upon purchase of a product', 'woocommerce' ); ?>" href="#">[?]</a></label>

					<label><input type="checkbox" class="checkbox variable_is_virtual" name="variable_is_virtual[<?php echo $loop; ?>]" <?php checked( isset( $_virtual ) ? $_virtual : '', 'yes' ); ?> /> <?php _e( 'Virtual', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Enable this option if a product is not shipped or there is no shipping cost', 'woocommerce' ); ?>" href="#">[?]</a></label>

					<?php do_action( 'woocommerce_variation_options', $loop, $variation_data, $variation ); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>
