/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
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

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const singleProductPrice = config.get( 'products.simple.price' );
const twoProductPrice = singleProductPrice * 2;

const runCartPageTest = () => {
	describe('Cart page', () => {
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct();
			await merchant.logout();
		});

		it('should display no item in the cart', async () => {
			await shopper.goToCart();
			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});

		it('should add the product to the cart when "Add to cart" is clicked', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);

			await shopper.goToCart();
			await shopper.productIsInCart(simpleProductName);
		});

		it('should increase item qty when "Add to cart" of the same product is clicked', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);

			await shopper.goToCart();
			await shopper.productIsInCart(simpleProductName, 2);
		});

		it('should update qty when updated via qty input', async () => {
			await shopper.goToCart();
			await shopper.setCartQuantity(simpleProductName, 4);
			await expect(page).toClick('button', {text: 'Update cart'});
			await uiUnblocked();

			await shopper.productIsInCart(simpleProductName, 4);
		});

		it('should remove the item from the cart when remove is clicked', async () => {
			await shopper.goToCart();
			await shopper.removeFromCart(simpleProductName);
			await uiUnblocked();

			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});

		it('should update subtotal in cart totals when adding product to the cart', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);

			await shopper.goToCart();
			await shopper.productIsInCart(simpleProductName, 1);
			await expect(page).toMatchElement('.cart-subtotal .amount', {text: `$${ singleProductPrice }`});

			await shopper.setCartQuantity(simpleProductName, 2);
			await expect(page).toClick('button', {text: 'Update cart'});
			await uiUnblocked();

			await expect(page).toMatchElement('.cart-subtotal .amount', {text: `$${ twoProductPrice }`});
		});

		it('should go to the checkout page when "Proceed to Checkout" is clicked', async () => {
			await shopper.goToCart();
			await Promise.all([
				page.waitForNavigation({waitUntil: 'networkidle0'}),
				expect(page).toClick('.checkout-button', {text: 'Proceed to checkout'}),
			]);

			await expect(page).toMatchElement('#order_review');
		});
	});
};

module.exports = runCartPageTest;
