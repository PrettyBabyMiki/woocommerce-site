<?php
/**
 * Shows an order item
 *
 * @var object $item The item being displayed
 * @var int $item_id The id of the item being displayed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$product_link = $_product ? admin_url( 'post.php?post=' . absint( $_product->id ) . '&action=edit' ) : '';
$thumbnail    = $_product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $_product->get_image( 'shop_thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';

if ( ! empty( $item['product_id'] ) ) {
	$tooltip = '<strong>' . __( 'Product ID:', 'woocommerce' ) . '</strong> ' . esc_html( $item['product_id'] );
} else {
	$tooltip = __( 'This product has no ID', 'woocommerce' );
}

if ( ! empty( $item['variation_id'] ) && 'product_variation' === get_post_type( $item['variation_id'] ) ) {
	$tooltip .= '<br/><strong>' . __( 'Variation ID:', 'woocommerce' ) . '</strong> ' . esc_html( $item['variation_id'] );
} elseif ( ! empty( $item['variation_id'] ) ) {
	$tooltip .=  '<br/><strong>' . __( 'Variation ID:', 'woocommerce' ) . '</strong> ' . esc_html( $item['variation_id'] ) . ' (' . __( 'No longer exists', 'woocommerce' ) . ')';
}

$tooltip .= isset( $_product->variation_data ) ? '<br/>' . wc_get_formatted_variation( $_product->variation_data, true ) : '';
?>
<tr class="item <?php echo apply_filters( 'woocommerce_admin_html_order_item_class', ( ! empty( $class ) ? $class : '' ), $item ); ?>" data-order_item_id="<?php echo $item_id; ?>">
	<td class="thumb">
		<?php
			echo $product_link ? '<a href="' . esc_url( $product_link ) . '" class="wc-order-item-thumbnail tips" data-tip="' . esc_attr( $tooltip ) . '">' : '<div class="wc-order-item-thumbnail tips" data-tip="' . esc_attr( $tooltip ) . '">';
			echo wp_kses_post( $thumbnail );
			echo $product_link ? '</a>' : '</div>';
		?>
	</td>
	<td class="name" data-sort-value="<?php echo esc_attr( $item['name'] ); ?>">
		<?php
			echo $product_link ? '<a href="' . esc_url( $product_link ) . '" class="wc-order-item-name">' .  esc_html( $item['name'] ) . '</a>' : '<div class="class="wc-order-item-name"">' . esc_html( $item['name'] ) . '</div>';
			echo $_product && $_product->get_sku() ? __( 'SKU:', 'woocommerce' ) . ' ' . esc_html( $_product->get_sku() ) : ''; ?>

		<input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr( $item_id ); ?>" />
		<input type="hidden" name="order_item_tax_class[<?php echo absint( $item_id ); ?>]" value="<?php echo isset( $item['tax_class'] ) ? esc_attr( $item['tax_class'] ) : ''; ?>" />

		<?php do_action( 'woocommerce_before_order_itemmeta', $item_id, $item, $_product ) ?>
		<?php include( 'html-order-item-meta.php' ); ?>
		<?php do_action( 'woocommerce_after_order_itemmeta', $item_id, $item, $_product ) ?>
	</td>

	<?php do_action( 'woocommerce_admin_order_item_values', $_product, $item, absint( $item_id ) ); ?>

	<td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr( $order->get_item_subtotal( $item, false, true ) ); ?>">
		<div class="view">
			<?php
				if ( isset( $item['line_total'] ) ) {
					if ( isset( $item['line_subtotal'] ) && $item['line_subtotal'] != $item['line_total'] ) {
						echo '<del>' . wc_price( $order->get_item_subtotal( $item, false, true ), array( 'currency' => $order->get_order_currency() ) ) . '</del> ';
					}
					echo wc_price( $order->get_item_total( $item, false, true ), array( 'currency' => $order->get_order_currency() ) );
				}
			?>
		</div>
	</td>

	<td class="quantity" width="1%">
		<div class="view">
			<?php
				echo '&times;' . ( isset( $item['qty'] ) ? esc_html( $item['qty'] ) : '1' );

				if ( $refunded_qty = $order->get_qty_refunded_for_item( $item_id ) ) {
					echo '<small class="refunded">' . $refunded_qty . '</small>';
				}
			?>
		</div>
		<div class="edit" style="display: none;">
			<?php $item_qty = esc_attr( $item['qty'] ); ?>
			<input type="number" step="<?php echo apply_filters( 'woocommerce_quantity_input_step', '1', $_product ); ?>" min="0" autocomplete="off" name="order_item_qty[<?php echo absint( $item_id ); ?>]" placeholder="0" value="<?php echo $item_qty; ?>" data-qty="<?php echo $item_qty; ?>" size="4" class="quantity" />
		</div>
		<div class="refund" style="display: none;">
			<input type="number" step="<?php echo apply_filters( 'woocommerce_quantity_input_step', '1', $_product ); ?>" min="0" max="<?php echo $item['qty']; ?>" autocomplete="off" name="refund_order_item_qty[<?php echo absint( $item_id ); ?>]" placeholder="0" size="4" class="refund_order_item_qty" />
		</div>
	</td>

	<td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr( isset( $item['line_total'] ) ? $item['line_total'] : '' ); ?>">
		<div class="view">
			<?php
				if ( isset( $item['line_total'] ) ) {
					if ( isset( $item['line_subtotal'] ) && $item['line_subtotal'] != $item['line_total'] ) {
						echo '<del>' . wc_price( $item['line_subtotal'], array( 'currency' => $order->get_order_currency() ) ) . '</del> ';
					}
					echo wc_price( $item['line_total'], array( 'currency' => $order->get_order_currency() ) );
				}

				if ( $refunded = $order->get_total_refunded_for_item( $item_id ) ) {
					echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_order_currency() ) ) . '</small>';
				}
			?>
		</div>
		<div class="edit" style="display: none;">
			<div class="split-input">
				<?php $item_total = ( isset( $item['line_total'] ) ) ? esc_attr( wc_format_localized_price( $item['line_total'] ) ) : ''; ?>
				<input type="text" name="line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo $item_total; ?>" class="line_total wc_input_price tips" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'woocommerce' ); ?>" data-total="<?php echo $item_total; ?>" />

				<?php $item_subtotal = ( isset( $item['line_subtotal'] ) ) ? esc_attr( wc_format_localized_price( $item['line_subtotal'] ) ) : ''; ?>
				<input type="text" name="line_subtotal[<?php echo absint( $item_id ); ?>]" value="<?php echo $item_subtotal; ?>" class="line_subtotal wc_input_price tips" data-tip="<?php esc_attr_e( 'Before pre-tax discounts.', 'woocommerce' ); ?>" data-subtotal="<?php echo $item_subtotal; ?>" />
			</div>
		</div>
		<div class="refund" style="display: none;">
			<input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_total wc_input_price" />
		</div>
	</td>

	<?php
		if ( empty( $legacy_order ) && wc_tax_enabled() ) :
			$line_tax_data = isset( $item['line_tax_data'] ) ? $item['line_tax_data'] : '';
			$tax_data      = maybe_unserialize( $line_tax_data );

			foreach ( $order_taxes as $tax_item ) :
				$tax_item_id       = $tax_item['rate_id'];
				$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
				$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';

				?>
					<td class="line_tax" width="1%">
						<div class="view">
							<?php
								if ( '' != $tax_item_total ) {
									if ( isset( $tax_item_subtotal ) && $tax_item_subtotal != $tax_item_total ) {
										echo '<del>' . wc_price( wc_round_tax_total( $tax_item_subtotal ), array( 'currency' => $order->get_order_currency() ) ) . '</del> ';
									}

									echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_order_currency() ) );
								} else {
									echo '&ndash;';
								}

								if ( $refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id ) ) {
									echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_order_currency() ) ) . '</small>';
								}
							?>
						</div>
						<div class="edit" style="display: none;">
							<div class="split-input">
								<?php $item_total_tax = ( isset( $tax_item_total ) ) ? esc_attr( wc_format_localized_price( $tax_item_total ) ) : ''; ?>
								<input type="text" name="line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo $item_total_tax; ?>" class="line_tax wc_input_price tips" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'woocommerce' ); ?>" data-total_tax="<?php echo $item_total_tax; ?>" />

								<?php $item_subtotal_tax = ( isset( $tax_item_subtotal ) ) ? esc_attr( wc_format_localized_price( $tax_item_subtotal ) ) : ''; ?>
								<input type="text" name="line_subtotal_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" value="<?php echo $item_subtotal_tax; ?>" class="line_subtotal_tax wc_input_price tips" data-tip="<?php esc_attr_e( 'Before pre-tax discounts.', 'woocommerce' ); ?>" data-subtotal_tax="<?php echo $item_subtotal_tax; ?>" />
							</div>
						</div>
						<div class="refund" style="display: none;">
							<input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
						</div>
					</td>
				<?php
			endforeach;
		endif;
	?>

	<td class="wc-order-edit-line-item">
		<div class="wc-order-edit-line-item-actions">
			<?php if ( $order->is_editable() ) : ?>
					<a class="edit-order-item tips" href="#" data-tip="<?php esc_attr_e( 'Edit item', 'woocommerce' ); ?>"></a>
					<a class="delete-order-item tips" href="#" data-tip="<?php esc_attr_e( 'Delete item', 'woocommerce' ); ?>"></a>
			<?php endif; ?>
		</div>
	</td>
</tr>
