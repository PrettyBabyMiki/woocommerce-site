<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<tr class="shipping <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_item_id="<?php echo $item_id; ?>">
	<td class="check-column"><input type="checkbox" /></td>

	<td class="thumb"></td>

	<td class="name">
		<div class="view">
			<?php echo ! empty( $item['name'] ) ? esc_html( $item['name'] ) : __( 'Shipping', 'woocommerce' ); ?>
		</div>
		<div class="edit" style="display:none">
			<input type="text" placeholder="<?php _e( 'Shipping Name', 'woocommerce' ); ?>" name="shipping_method_title[<?php echo $item_id ? $item_id : 'new][]'; ?>]" value="<?php echo ( isset( $item['name'] ) ) ? esc_attr( $item['name'] ) : ''; ?>" />
			<select name="shipping_method[<?php echo $item_id ? $item_id : 'new][]'; ?>]">
				<optgroup label="<?php _e( 'Shipping Method', 'woocommerce' ); ?>">
					<option value=""><?php _e( 'N/A', 'woocommerce' ); ?></option>
					<?php
						$found_method = false;

						foreach ( $shipping_methods as $method ) {
							$method_id = isset( $item['method_id'] ) ? $item['method_id'] : '';
							$current_method = ( 0 === strpos( $method_id, $method->id ) ) ? $method_id : $method->id;

							echo '<option value="' . esc_attr( $current_method ) . '" ' . selected( $method_id == $current_method, true, false ) . '>' . esc_html( $method->get_title() ) . '</option>';

							if ( $method_id == $current_method ) {
								$found_method = true;
							}
						}

						if ( ! $found_method && ! empty( $method_id ) ) {
							echo '<option value="' . esc_attr( $method_id ) . '" selected="selected">' . __( 'Other', 'woocommerce' ) . '</option>';
						} else {
							echo '<option value="other">' . __( 'Other', 'woocommerce' ) . '</option>';
						}
					?>
				</optgroup>
			</select>
			<input type="hidden" name="shipping_method_id[<?php echo $item_id ? $item_id : 'new][]'; ?>]" value="<?php echo esc_attr( $item_id ); ?>" />
		</div>
	</td>

	<?php if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) : ?>

	<td class="tax_class" width="1%"></td>

	<?php endif; ?>

	<td class="quantity" width="1%">1</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php echo ( isset( $item['cost'] ) ) ? wc_price( wc_round_tax_total( $item['cost'] ) ) : ''; ?>
		</div>
		<div class="edit" style="display:none">
			<label><?php _e( 'Total', 'woocommerce' ); ?>: <input type="text" name="shipping_cost[<?php echo $item_id ? $item_id : 'new][]'; ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $item['cost'] ) ) ? esc_attr( wc_format_localized_price( $item['cost'] ) ) : ''; ?>" class="line_total wc_input_price" /></label>
		</div>
	</td>

	<?php if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) : ?>

	<td class="line_tax" width="1%"></td>

	<?php endif; ?>

	<td class="wc-order-item-refund-quantity" width="1%" style="display:none"></td>

	<td class="wc-order-edit-line-item">
		<div class="wc-order-edit-line-item-actions">
			<a class="edit_order_item" href="#"></a><a class="delete_order_item" href="#"></a>
		</div>
	</td>
</tr>
