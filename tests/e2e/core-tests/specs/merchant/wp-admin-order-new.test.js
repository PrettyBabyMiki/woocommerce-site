/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	verifyPublishAndTrash,
	uiUnblocked
} = require('@woocommerce/e2e-utils');
const config = require('config');
const { HTTPClientFactory,
	VariableProduct,
	GroupedProduct,
	SimpleProduct,
	ProductVariation,
	ExternalProduct
} = require('@woocommerce/api');

let variations;
let products;
const taxRates = [
	{
		name: 'My Standard Tax 1',
		rate: '10',
		class: 'standard'
	},
	{
		name: 'My Reduced Rate Tax 2',
		rate: '20',
		class: 'reduced-rate'
	},
	{
		name: 'My Zero Rate Tax 3',
		rate: '30',
		class: 'zero-rate'
	}
];

const runCreateOrderTest = () => {
	describe('WooCommerce Orders > Add new order', () => {
		beforeAll(async () => {
			// Initialize HTTP client
			const apiUrl = config.get('url');
			const adminUsername = config.get('users.admin.username');
			const adminPassword = config.get('users.admin.password');
			const httpClient = HTTPClientFactory.build(apiUrl)
				.withBasicAuth(adminUsername, adminPassword)
				.create();

			// Initialize products for each product type
			const initProducts = async () => {
				// Initialization functions per product type
				const initSimpleProduct = async () => {
					const repo = SimpleProduct.restRepository(httpClient);
					const props = config.get('products.simple');
					return await repo.create(props);
				};
				const initVariableProduct = async () => {
					const prodProps = config.get('products.variable');
					variations = config.get('products.variations');;
					const productRepo = VariableProduct.restRepository(httpClient);
					const variationRepo = ProductVariation.restRepository(httpClient);
					const variableProduct = await productRepo.create(prodProps);
					for (const v of variations) {
						await variationRepo.create(variableProduct.id, v);
					}

					return variableProduct;
				};
				const initGroupedProduct = async () => {
					const repo = GroupedProduct.restRepository(httpClient);
					const props = config.get('products.grouped');
					return await repo.create(props);
				};
				const initExternalProduct = async () => {
					const repo = ExternalProduct.restRepository(httpClient);
					const props = config.get('products.external');
					return await repo.create(props);
				};

				// Create a product for each product type
				const simpleProduct = await initSimpleProduct();
				const variableProduct = await initVariableProduct();
				const groupedProduct = await initGroupedProduct();
				const externalProduct = await initExternalProduct();

				return [
					simpleProduct,
					variableProduct,
					groupedProduct,
					externalProduct
				];
			};
			products = await initProducts();

			// Initialize tax rates & classes
			const initTaxes = async () => {
				// Enable taxes in settings
				const enableTaxes = async () => {
					const path = '/wc/v3/settings/general/woocommerce_calc_taxes';
					const data = {
						value: 'yes'
					}
					await httpClient.put(path, data);
				}
				await enableTaxes();

				// Delete existing tax rates
				const deleteTaxRates = async () => {
					const path = '/wc/v3/taxes';
					await httpClient.get(path).then(async (response) => {
						for (const { id } of response.data) {
							await httpClient.delete(`${path}/${id}?force=true`);
						}
					});
				};
				await deleteTaxRates();

				// Set up tax rates
				const createTaxRates = async () => {
					const path = '/wc/v3/taxes';
					for (const t of taxRates) {
						await httpClient.post(path, t);
					}
				};
				await createTaxRates();
			};
			await initTaxes();

			// Login
			await merchant.login();
		});

		it('can create new order', async () => {
			// Go to "add order" page
			await merchant.openNewOrder();

			// Make sure we're on the add order page
			await expect(page.title()).resolves.toMatch('Add new order');

			// Set order data
			await expect(page).toSelect('#order_status', 'Processing');
			await expect(page).toFill('input[name=order_date]', '2018-12-13');
			await expect(page).toFill('input[name=order_date_hour]', '18');
			await expect(page).toFill('input[name=order_date_minute]', '55');

			// Create order, verify that it was created. Trash order, verify that it was trashed.
			await verifyPublishAndTrash(
				'.order_actions li .save_order',
				'#message',
				'Order updated.',
				'1 order moved to the Trash.'
			);
		});

		it('can create new complex order with multiple product types & tax classes', async () => {
			// Go to "add order" page
			await merchant.openNewOrder();

			// Open modal window for adding line items
			await expect(page).toClick('button.add-line-item');
			await expect(page).toClick('button.add-order-item');
			await page.waitForSelector('.wc-backbone-modal-header');

			// Search for each product to add, then verify that they are saved
			for (const { name } of products) {
				await expect(page).toClick('.wc-backbone-modal-content tr:last-child .select2-selection__arrow');
				await expect(page).toFill('#wc-backbone-modal-dialog + .select2-container .select2-search__field',
					name
				);
				const firstResult = await page.waitForSelector('li[data-selected]');
				await firstResult.click();
				await expect(page).toMatchElement(
					'.wc-backbone-modal-content tr:nth-last-child(2) .wc-product-search option',
					name
				);
			}

			// Save the line items
			await expect(page).toClick('.wc-backbone-modal-content #btn-ok');
			await uiUnblocked();

			// Recalculate taxes
			await expect(page).toDisplayDialog(async () => {
				await expect(page).toClick('.calculate-action');
			});
			await page.waitForSelector('th.line_tax');

			// Save the order and verify line items
			await expect(page).toClick('button.save_order');
			await page.waitForNavigation();
			for (const { name } of products) {
				await expect(page).toMatchElement('.wc-order-item-name', { text: name });
			}

			// Verify variation details
			const firstVariation = variations[0];
			const attributes = firstVariation.attributes;
			const actualAttributes = await page.$('.display_meta');
			await expect(page).toMatchElement('.wc-order-item-variation', { text: firstVariation.id });
			for (const { name, option } of attributes) {
				await expect(actualAttributes).toMatch(name);
				await expect(actualAttributes).toMatch(option);
			}

			// Verify that the names of each tax class were shown
			for (const { name } of taxRates) {
				await expect(page).toMatchElement('th.line_tax', { text: name });
				await expect(page).toMatchElement('.wc-order-totals td.label', { text: name });
			}

			// Verify tax amounts
			const taxTotals = ['£10.00', '£4.00', '£7.50'];
			for (const amount of taxTotals) {
				await expect(page).toMatchElement('td.line_tax', { text: amount });
				await expect(page).toMatchElement('.wc-order-totals td.total', { text: amount });
			}
		})
	});
}

module.exports = runCreateOrderTest;
