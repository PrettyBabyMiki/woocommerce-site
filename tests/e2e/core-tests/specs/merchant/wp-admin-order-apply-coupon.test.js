/* eslint-disable jest/no-export, jest/no-standalone-expect */

/**
 * Internal dependencies
 */
const {
	merchant,
	createSimpleProduct,
	createSimpleOrder,
	createCoupon,
	uiUnblocked,
	addProductToOrder,
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const simpleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';
const discountedPrice = simpleProductPrice - 5.00;

const couponDialogMessage = 'Enter a coupon code to apply. Discounts are applied to line totals, before taxes.';

let couponCode;
let orderId;

const runOrderApplyCouponTest = () => {
	describe('WooCommerce Orders > Apply coupon', () => {
		beforeAll(async () => {
			await merchant.login();
			await Promise.all([
				await createSimpleProduct(),
				couponCode = await createCoupon(),
				orderId = await createSimpleOrder('Pending payment', simpleProductName),
				await addProductToOrder(orderId, simpleProductName),

				// We need to remove any listeners on the `dialog` event otherwise we can't catch the dialog below
				page.removeAllListeners('dialog'),
			]);

			// Make sure the simple product price is greater than the coupon amount
			await expect(Number(simpleProductPrice)).toBeGreaterThan(5.00);
		} );

		it('can apply a coupon', async () => {
			const couponDialog = await expect(page).toDisplayDialog(async () => {
				await expect(page).toClick('button.add-coupon');
			});

			expect(couponDialog.message()).toMatch(couponDialogMessage);

			// Accept the dialog with the coupon code
			await couponDialog.accept(couponCode);

			await uiUnblocked();

			// Verify the coupon list is showing
			await page.waitForSelector('.wc-used-coupons');
			await expect(page).toMatchElement('.wc_coupon_list', { text: 'Coupon(s)' });
			await expect(page).toMatchElement('.wc_coupon_list li.code.editable', { text: couponCode });

			// Check that the coupon has been applied
			await expect(page).toMatchElement('.wc-order-item-discount', { text: '5.00' });
			await expect(page).toMatchElement('.line_cost > .view > .woocommerce-Price-amount', { text: discountedPrice });
		});

		it('can remove a coupon', async () => {
			// Make sure we have a coupon on the page to use
			await page.waitForSelector('.wc-used-coupons');
			await expect(page).toMatchElement('.wc_coupon_list li.code.editable', { text: couponCode });

			// We need to use this here as `expect(page).toClick()` was unable to find the element
			// See: https://github.com/puppeteer/puppeteer/issues/1769#issuecomment-637645219
			page.$eval('a.remove-coupon', elem => elem.click());

			await uiUnblocked();

			// Verify the coupon pricing has been removed
			await expect(page).not.toMatchElement('.wc_coupon_list li.code.editable', { text: couponCode });
			await expect(page).not.toMatchElement('.wc-order-item-discount', { text: '5.00' });
			await expect(page).not.toMatchElement('.line-cost .view .woocommerce-Price-amount', { text: discountedPrice });

			// Verify the original price is the order total
			await expect(page).toMatchElement('.line_cost > .view > .woocommerce-Price-amount', { text: simpleProductPrice });
		});

	});

};

module.exports = runOrderApplyCouponTest;
