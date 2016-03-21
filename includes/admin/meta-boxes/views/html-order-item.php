<?php
/**
 * Shows an order item
 *
 * @var object $item The item being displayed
 * @var int $item_id The id of the item being displayed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$product_link  = $_product ? admin_url( 'post.php?post                                                                                       =' . absint( $_product->id ) . '&action =edit' ) : '';
$thumbnail     = $_product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $_product->get_image( 'shop_thumbnail', array( 'title' => '' ), false ), $item_id, $item )              : '';
$tooltip       = _wc_get_order_item_tooltip_content( $item, $_product );
$tax_data      = empty( $legacy_order ) && wc_tax_enabled() ? maybe_unserialize( isset( $item['line_tax_data'] ) ? $item['line_tax_data']                                                     : '' ) : false;
$item_total    = ( isset( $item['line_total'] ) ) ? esc_attr( wc_format_localized_price( $item['line_total'] ) )                                                                              : '';
$item_subtotal = ( isset( $item['line_subtotal'] ) ) ? esc_attr( wc_format_localized_price( $item['line_subtotal'] ) )                                                                        : '';
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
			echo $_product && $_product->get_sku() ? '<div class="wc-order-item-sku">' . __( 'SKU:', 'woocommerce' ) . ' ' . esc_html( $_product->get_sku() ) . '</div>' : ''; ?>

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
					echo wc_price( $order->get_item_total( $item, false, true ), array( 'currency' => $order->get_order_currency() ) );

					if ( isset( $item['line_subtotal'] ) && $item['line_subtotal'] != $item['line_total'] ) {
						echo '<span class="wc-order-item-discount">' . __( 'Pre-discount:', 'woocommerce' ) . ' ' . wc_price( $order->get_item_subtotal( $item, false, true ), array( 'currency' => $order->get_order_currency() ) ) . '</span>';
					}
				}
			?>
		</div>
	</td>
	<td class="quantity" width="1%">
		<div class="view">
			<?php
				echo '<small class="times">&times;</small> ' . ( isset( $item['qty'] ) ? esc_html( $item['qty'] ) : '1' );

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
					echo wc_price( $item['line_total'], array( 'currency' => $order->get_order_currency() ) );
				}

				if ( $refunded = $order->get_total_refunded_for_item( $item_id ) ) {
					echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_order_currency() ) ) . '</small>';
				}

				if ( isset( $item['line_subtotal'] ) && $item['line_subtotal'] !== $item['line_total'] ) {
					echo '<span class="wc-order-item-discount">' . __( 'Pre-discount:', 'woocommerce' ) . ' '. wc_price( $order->get_line_subtotal( $item, false, true ), array( 'currency' => $order->get_order_currency() ) ) . '</span>';
				}

				// Output a row per tax total
				if ( ! empty( $tax_data ) ) {
					echo '<ul class="wc-order-item-taxes">';
					foreach ( $order_taxes as $tax_item ) {
						$tax_item_id       = $tax_item['rate_id'];
						$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
						$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';
						$tax_class         = wc_get_tax_class_by_tax_id( $tax_item_id );
			            $tax_class_name    = isset( $classes_options[ $tax_class ] ) ? $classes_options[ $tax_class ] : __( 'Tax', 'woocommerce' );
			            $tax_label         = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', 'woocommerce' );
						if ( $tax_item_total ) {
							echo '<li class="tips" data-tip="' . esc_attr( $tax_item['name'] . ' (' . $tax_class_name . ')' ) . '">';
							echo esc_html( $tax_label ) . ': ';
							if ( isset( $tax_item_subtotal ) && $tax_item_subtotal != $tax_item_total ) {
								echo '<del>' . wc_price( wc_round_tax_total( $tax_item_subtotal ), array( 'currency' => $order->get_order_currency() ) ) . '</del> ';
							}
							echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_order_currency() ) );
							echo '</li>';
						}
					}
					echo '</ul>';
				}
			?>
		</div>
		<div class="edit" style="display: none;">
			<div class="split-input">
				<div class="input">
					<label><?php esc_attr_e( 'Pre-discount:', 'woocommerce' ); ?></label>
					<input type="text" name="line_subtotal[<?php echo absint( $item_id ); ?>]" value="<?php echo $item_subtotal; ?>" class="line_subtotal wc_input_price tips" data-subtotal="<?php echo $item_subtotal; ?>" />
				</div>
				<div class="input">
					<label><?php esc_attr_e( 'Total:', 'woocommerce' ); ?></label>
					<input type="text" name="line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo $item_total; ?>" class="line_total wc_input_price" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'woocommerce' ); ?>" data-total="<?php echo $item_total; ?>" />
				</div>
			</div>
			<?php
				// Output a row per tax total
				if ( ! empty( $tax_data ) ) {
					echo '<ul class="wc-order-item-taxes">';
					foreach ( $order_taxes as $tax_item ) {
						$tax_item_id       = $tax_item['rate_id'];
						$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
						$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';
						$tax_class         = wc_get_tax_class_by_tax_id( $tax_item_id );
						$tax_class_name    = isset( $classes_options[ $tax_class ] ) ? $classes_options[ $tax_class ] : __( 'Tax', 'woocommerce' );
						$tax_label         = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', 'woocommerce' );
						$item_subtotal_tax = esc_attr( wc_format_localized_price( $tax_item_subtotal ) );
						$item_total_tax    = esc_attr( wc_format_localized_price( $tax_item_total ) );

						echo '<li class="tips" data-tip="' . esc_attr( $tax_item['name'] . ' (' . $tax_class_name . ')' ) . '">';
						echo '<label class="split-input-label">' . esc_html( $tax_label ) . ':</label>';
						?>
						<div class="split-input">
							<div class="input">
								<label><?php esc_attr_e( 'Pre-discount:', 'woocommerce' ); ?></label>
								<input type="text" name="line_subtotal_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" value="<?php echo $item_subtotal_tax; ?>" class="line_subtotal_tax wc_input_price" data-subtotal_tax="<?php echo $item_subtotal_tax; ?>" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
							</div>
							<div class="input">
								<label><?php esc_attr_e( 'Total:', 'woocommerce' ); ?></label>
								<input type="text" name="line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo $item_total_tax; ?>" class="line_tax wc_input_price" data-total_tax="<?php echo $item_total_tax; ?>" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
							</div>
						</div>
						<?php
						echo '</li>';
					}
					echo '</ul>';
				}
			?>
		</div>
		<div class="refund" style="display: none;">
			<ul class="wc-order-item-refund-fields">
				<li>
					<label><?php esc_html_e( 'Line Total:', 'woocommerce' ); ?></label>
					<input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_total wc_input_price" />
				</li>
				<?php
					// Output a row per tax total
					if ( ! empty( $tax_data ) ) {
						echo '';
						foreach ( $order_taxes as $tax_item ) {
							$tax_item_id       = $tax_item['rate_id'];
							$tax_class         = wc_get_tax_class_by_tax_id( $tax_item_id );
							$tax_class_name    = isset( $classes_options[ $tax_class ] ) ? $classes_options[ $tax_class ] : __( 'Tax', 'woocommerce' );
							$tax_label         = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', 'woocommerce' );
							$item_total_tax    = esc_attr( wc_format_localized_price( $tax_item_total ) );

							echo '<li class="tips" data-tip="' . esc_attr( $tax_item['name'] . ' (' . $tax_class_name . ')' ) . '">';
							echo '<label>' . esc_html( $tax_label ) . ':</label>';
							?>
							<input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
							<?php
						}
					}
				?>
			</ul>
		</div>
	</td>

	<td class="wc-order-edit-line-item" width="1%">
		<div class="wc-order-edit-line-item-actions">
			<?php if ( $order->is_editable() ) : ?>
				<a class="edit-order-item tips" href="#" data-tip="<?php esc_attr_e( 'Edit item', 'woocommerce' ); ?>"></a>
				<a class="delete-order-item tips" href="#" data-tip="<?php esc_attr_e( 'Delete item', 'woocommerce' ); ?>"></a>
			<?php endif; ?>
		</div>
	</td>
</tr>
