/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */

/**
 * Internal dependencies
 */
const {
	merchant,
	searchForOrder,
	createSimpleProduct,
	addProductToOrder,
	clickUpdateOrder,
	factories,
} = require( '@woocommerce/e2e-utils' );

const searchString = 'John Doe';
const itemName = 'Wanted Product';

const customerBilling = {
	first_name: 'John',
	last_name: 'Doe',
	company: 'Automattic',
	country: 'US',
	address_1: 'address1',
	address_2: 'address2',
	city: 'San Francisco',
	state: 'CA',
	postcode: '94107',
	phone: '123456789',
	email: 'john.doe@example.com',
};
const customerShipping = {
	first_name: 'Tim',
	last_name: 'Clark',
	company: 'Automattic',
	country: 'US',
	address_1: 'Oxford Ave',
	address_2: 'Linwood Ave',
	city: 'Buffalo',
	state: 'NY',
	postcode: '14201',
	phone: '123456789',
	email: 'john.doe@example.com',
};

/**
 * Set the billing fields for the customer account for this test suite.
 *
 * @returns {Promise<void>}
 */
const updateCustomerBilling = async () => {
	const client = factories.api.withDefaultPermalinks;
	const customerEndpoint = 'wc/v3/customers/';
	const customers = await client.get( customerEndpoint, {
		search: 'Jane',
		role: 'all',
	} );
	if ( ! customers.data | ! customers.data.length ) {
		return;
	}

	const customerId = customers.data[0].id;
	const customerData = {
		id: customerId,
		billing: customerBilling,
		shipping: customerShipping,
	};
	await client.put( customerEndpoint + customerId, customerData );
};

/**
 * Function for creating individual search terms.
 * 
 * @param f field to be printed in the test results
 * @param v the search term to be typed into the search box
 * @returns object containing the field and search term
 */
const createData = (f, v) => {
	return {
		field: f,
		value: v
	};
};

/**
 * Data table to be fed into `it.each()`.
 */
const queries = [
	createData('billing first name', customerBilling.first_name),
	createData('billing last name', customerBilling.last_name),
	createData('billing company name', customerBilling.company),
	createData('billing first address', customerBilling.address_1),
	createData('billing second address', customerBilling.address_2),
	createData('billing city name', customerBilling.city),
	createData('billing post code', customerBilling.postcode),
	createData('billing email', customerBilling.email),
	createData('billing phone', customerBilling.phone),
	createData('billing state', customerBilling.state),
	createData('shipping first name', customerShipping.first_name),
	createData('shipping last name', customerShipping.last_name),
	createData('shipping first address', customerShipping.address_1),
	createData('shipping second address', customerShipping.address_2),
	createData('shipping city name', customerShipping.city),
	createData('shipping post code', customerShipping.postcode),
	createData('shipping item name', itemName)
];

const runOrderSearchingTest = () => {
	describe('WooCommerce Orders > Search orders', () => {
		let orderId;
		beforeAll( async () => {
			await createSimpleProduct(itemName);
			await updateCustomerBilling();

			// Create new order for testing
			await merchant.login();
			await merchant.openNewOrder();
			await page.waitForSelector('#order_status');
			await page.click('#customer_user');
			await page.click('span.select2-search > input.select2-search__field');
			await page.type('span.select2-search > input.select2-search__field', 'Jane Smith');
			await page.waitFor(2000); // to avoid flakyness
			await page.keyboard.press('Enter');

			// Get the post id
			const variablePostId = await page.$('#post_ID');
			orderId = (await(await variablePostId.getProperty('value')).jsonValue());

			// Save new order and add desired product to order
			await clickUpdateOrder('Order updated.', true);
			await addProductToOrder(orderId, itemName);

			// Open All Orders view
			await merchant.openAllOrdersView();
		});

		it('can search for order by order id', async () => {
			await searchForOrder(orderId, orderId, searchString);
		});

		it.each(queries)('can search for order by %o', async ({ value }) => {
			await searchForOrder(value, orderId, searchString);
		});

		/**
		 * shipping state is abbreviated. This test passes if billing and shipping state are the same
		 */
		it.skip('can search for order by shipping state name', async () => {
			await searchForOrder('New York', orderId, searchString);
		})
	});
};

module.exports = runOrderSearchingTest;
