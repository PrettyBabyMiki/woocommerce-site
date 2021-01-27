/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect, jest/no-standalone-expect */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createCoupon,
	createSimpleProduct,
	uiUnblocked
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

/**
 * Apply a coupon code to the cart.
 *
 * @param couponCode string
 * @returns {Promise<void>}
 */
const applyCouponToCart = async ( couponCode ) => {
	await expect(page).toClick('a', {text: 'Click here to enter your code'});
	await uiUnblocked();
	await expect(page).toFill('#coupon_code', couponCode);
	await expect(page).toClick('button', {text: 'Apply coupon'});
	await uiUnblocked();
};

/**
 * Remove one coupon from the cart.
 *
 * @returns {Promise<void>}
 */
const removeCouponFromCart = async () => {
	await expect(page).toClick('.woocommerce-remove-coupon', {text: '[Remove]'});
	await uiUnblocked();
	await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon has been removed.'});
}
const runCheckoutApplyCouponsTest = () => {
	let couponFixedCart;
	let couponPercentage;
	let couponFixedProduct;

	describe('Checkout applying coupons', () => {
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct();
			couponFixedCart = await createCoupon();
			couponPercentage = await createCoupon('50', 'Percentage discount');
			couponFixedProduct = await createCoupon('5', 'Fixed product discount');
			await merchant.logout();
			await shopper.goToShop();
			await shopper.addToCartFromShopPage('Simple product');
			await uiUnblocked();
			await shopper.goToCheckout();
		});

		it('allows customer to apply fixed cart coupon', async () => {
			await applyCouponToCart( couponFixedCart );
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});

			// Wait for page to expand total calculations to avoid flakyness
			await page.waitForSelector('.order-total');

			// Verify discount applied and order total
			await expect(page).toMatchElement('.cart-discount .amount', {text: '$5.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$4.99'});
			await removeCouponFromCart();
		});

		it('allows customer to apply percentage coupon', async () => {
			await applyCouponToCart( couponPercentage );
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});

			// Verify discount applied and order total
			await expect(page).toMatchElement('.cart-discount .amount', {text: '$4.99'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$5.00'});
			await removeCouponFromCart();
		});

		it('allows customer to apply fixed product coupon', async () => {
			await applyCouponToCart( couponFixedProduct );
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});
			await expect(page).toMatchElement('.cart-discount .amount', {text: '$5.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$4.99'});
			await removeCouponFromCart();
		});

		it('prevents customer applying same coupon twice', async () => {
			await applyCouponToCart( couponFixedProduct );
			await applyCouponToCart( couponFixedProduct );
			await expect(page).toMatchElement('.woocommerce-error', { text: 'Coupon code already applied!' });
		});

		it('allows customer to apply multiple coupon', async () => {
			await applyCouponToCart( couponFixedCart );
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$0.00'});
		});

		it('restores cart total when coupons are removed', async () => {
			await removeCouponFromCart();
			await removeCouponFromCart();
			await expect(page).toMatchElement('.order-total .amount', {text: '$9.99'});
		});
	});
};

module.exports = runCheckoutApplyCouponsTest;
