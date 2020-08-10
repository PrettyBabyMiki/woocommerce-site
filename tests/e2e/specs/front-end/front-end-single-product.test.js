/**
 * @format
 */

/**
 * Internal dependencies
 */
import { createSimpleProduct, createVariableProduct } from '../../utils/components';
import { CustomerFlow, StoreOwnerFlow } from '../../utils/flows';
import { uiUnblocked } from '../../utils';

import {
	CustomerFlow,
	StoreOwnerFlow,
	createSimpleProduct,
	createVariableProduct,
	uiUnblocked
} from '@woocommerce/e2e-utils';

let simplePostIdValue;
let variablePostIdValue;
const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

describe( 'Single Product Page', () => {
	beforeAll( async () => {
		await StoreOwnerFlow.login();
		simplePostIdValue = await createSimpleProduct();
		await StoreOwnerFlow.logout();
	} );

	it( 'should be able to add simple products to the cart', async () => {
		// Add 5 simple products to cart
		await CustomerFlow.goToProduct( simplePostIdValue );
		await expect( page ).toFill( 'div.quantity input.qty', '5' );
		await CustomerFlow.addToCart();
		await expect( page ).toMatchElement( '.woocommerce-message', { text: 'have been added to your cart.' } );

		// Verify cart contents
		await CustomerFlow.goToCart();
		await CustomerFlow.productIsInCart( simpleProductName, 5 );

		// Remove items from cart
		await CustomerFlow.removeFromCart( simpleProductName );
		await uiUnblocked();
		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );
} );

describe.skip( 'Variable Product Page', () => {
	beforeAll( async () => {
		await StoreOwnerFlow.login();
		variablePostIdValue = await createVariableProduct();
		await StoreOwnerFlow.logout();
	} );

	it( 'should be able to add variation products to the cart', async () => {
		// Add a product with one set of variations to cart
		await CustomerFlow.goToProduct( variablePostIdValue );
		await expect( page ).toSelect( '#attr-1', 'val1' );
		await expect( page ).toSelect( '#attr-2', 'val1' );
		await expect( page ).toSelect( '#attr-3', 'val1' );
		await CustomerFlow.addToCart();
		await expect( page ).toMatchElement( '.woocommerce-message', { text: 'has been added to your cart.' } );

		// Verify cart contents
		await CustomerFlow.goToCart();
		await CustomerFlow.productIsInCart( 'Variable Product with Three Variations' );

		// Remove items from cart
		await CustomerFlow.removeFromCart( 'Variable Product with Three Variations' );
		await uiUnblocked();
		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );
} );
