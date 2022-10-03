const { test, expect } = require( '@playwright/test' );

const { getOrderExampleSearchTest } = require( '../../data/order' );
const { customerShippingSearchTest } = require( '../../data/shared/customer' );

/**
 * Order to be searched
 */
const order = {
	...getOrderExampleSearchTest(),
	shipping: {
		...customerShippingSearchTest,
		company: 'Murphy LLCsearch',
		phone: '6146524353search',
	},
	shipping_lines: [],
	fee_lines: [],
	coupon_lines: [],
};

/**
 * Search parameters to be used.
 * The following scenarios are not covered in this test suite because they're already covered in the `List all orders > search` test in `orders.test.js`
 * ```
 * can search by billing address 1
 * can search by shipping address 1
 * can search by billing last name
 * can search by billing email
 * can search by item name
 * ```
 */
const searchParams = [
	[ 'orderId', 'orderId' ],
	[ 'billing first name', order.billing.first_name ],
	[ 'billing company name', order.billing.company ],
	[ 'billing address 2', order.billing.address_2 ],
	[ 'billing city name', order.billing.city ],
	[ 'billing post code', order.billing.postcode ],
	[ 'billing phone', order.billing.phone ],
	[ 'billing state', order.billing.state ],
	[ 'shipping first name', order.shipping.first_name ],
	[ 'shipping last name', order.shipping.last_name ],
	[ 'shipping address 2', order.shipping.address_2 ],
	[ 'shipping city', order.shipping.city ],
	[ 'shipping post code', order.shipping.postcode ],
	[ 'shipping state', order.shipping.state ],
];

/**
 * Tests for the WooCommerce Order Search API.
 *
 * @group api
 * @group orders
 *
 */
test.describe( 'Order Search API tests', () => {
	test.beforeAll( async ( { request } ) => {
		// Create an order and save its ID
		const response = await request.post( '/wp-json/wc/v3/orders', {
			data: order,
		} );
		const responseJSON = await response.json();
		order.id = responseJSON.id;
	} );

	test.afterAll( async ( { request } ) => {
		// Cleanup: Delete the order
		await request.delete( `/wp-json/wc/v3/orders/${ order.id }`, {
			data: { force: true },
		} );
	} );

	const titleIndex = 0;
	const paramIndex = 1;

	for ( const searchParamRow of searchParams ) {
		test( `can search by ${ searchParamRow[ titleIndex ] }`, async ( {
			request,
		} ) => {
			const searchValue =
				searchParamRow[ paramIndex ] === 'orderId'
					? order.id
					: searchParamRow[ paramIndex ];
			const response = await request.get( '/wp-json/wc/v3/orders/', {
				params: { search: searchValue },
			} );
			const responseJSON = await response.json();

			expect( response.status() ).toEqual( 200 );
			expect( responseJSON ).toHaveLength( 1 );
			expect( responseJSON[ 0 ].id ).toEqual( order.id );
		} );
	}

	test( 'can return an empty result set when no matches were found', async ( {
		request,
	} ) => {
		const response = await request.get( '/wp-json/wc/v3/orders/', {
			params: { search: 'Chauncey Smith Kunde' },
		} );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON ).toEqual( [] );
	} );
} );
