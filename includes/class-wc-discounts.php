<?php
/**
 * Discount calculation
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 * @version 3.2.0
 * @since   3.2.0
 */

/**
 * Discounts class.
 */
class WC_Discounts {

	/**
	 * An array of items to discount.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Get items.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Set cart/order items which will be discounted.
	 *
	 * @since 3.2.0
	 * @param array $raw_items
	 * @todo Create https://github.com/woocommerce/woocommerce/pull/11889/files#diff-d9e4f5367e9d615985099b0d135629b8 class.
	 */
	public function set_items( $raw_items ) {
		foreach ( $raw_items as $raw_item ) {
			$item = array(
				'price'    => 0, // Unit price without discounts.
				'qty'      => 0, // Line qty.
				'discount' => 0, // Total discounts to apply.
			);

			if ( is_a( $raw_item, 'WC_Cart_Item' ) ) {

			} elseif ( is_a( $raw_item, 'WC_Order_Item_Product' ) ) {

			} else {
				// @todo remove when we implement WC_Cart_Item. This is the old cart item schema.
				$item['qty']   = $raw_item['quantity'];
				$item['price'] = $raw_item['data']->get_price();
			}

			$this->items[] = $item;
		}
	}

	/**
	 * Get all discount totals.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_discounts() {
		return array(
			'items',
			'discount_totals' => array(
				// 'code' => 'amount'
			)
		);
	}

	/**
	 * Apply a discount to all items using a coupon.
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon
	 * @return bool True if applied.
	 */
	public function apply_discount( $coupon ) {
		if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
			return false;
		}
		// Do something to the items.
	}
}
