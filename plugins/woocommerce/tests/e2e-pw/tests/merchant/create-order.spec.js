const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const simpleProductName = 'Add new order simple product';
const variableProductName = 'Add new order variable product';
const externalProductName = 'Add new order external product';
const groupedProductName = 'Add new order grouped product';
const taxClasses = [
	{
		name: 'Tax Class Simple',
	},
	{
		name: 'Tax Class Variable',
	},
	{
		name: 'Tax Class External',
	},
];
const taxRates = [
	{
		name: 'Tax Rate Simple',
		rate: '10.0000',
		class: 'tax-class-simple',
	},
	{
		name: 'Tax Rate Variable',
		rate: '20.0000',
		class: 'tax-class-variable',
	},
	{
		name: 'Tax Rate External',
		rate: '30.0000',
		class: 'tax-class-external',
	},
];
const taxClassSlugs = [];
const taxTotals = [ '10.00', '20.00', '240.00' ];
let simpleProductId,
	variableProductId,
	externalProductId,
	subProductAId,
	subProductBId,
	groupedProductId,
	orderId;

test.describe( 'WooCommerce Orders > Add new order', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// enable taxes on the account
		await api.put( 'settings/general/woocommerce_calc_taxes', {
			value: 'yes',
		} );
		// add tax classes
		for ( let i = 0; i < taxClasses.length; i++ ) {
			await api
				.post( 'taxes/classes', taxClasses[ i ] )
				.then( ( response ) => {
					taxClassSlugs[ i ] = response.data.slug;
				} );
		}
		// attach rates to the classes
		for ( let i = 0; i < taxRates.length; i++ ) {
			await api.post( 'taxes', taxRates[ i ] );
		}
		// create simple product
		await api
			.post( 'products', {
				name: simpleProductName,
				type: 'simple',
				regular_price: '100',
				tax_class: 'Tax Class Simple',
			} )
			.then( ( resp ) => {
				simpleProductId = resp.data.id;
			} );
		// create variable product
		const variations = [
			{
				regular_price: '100',
				attributes: [
					{
						name: 'Size',
						option: 'Small',
					},
					{
						name: 'Colour',
						option: 'Yellow',
					},
				],
				tax_class: 'Tax Class Variable',
			},
			{
				regular_price: '100',
				attributes: [
					{
						name: 'Size',
						option: 'Medium',
					},
					{
						name: 'Colour',
						option: 'Magenta',
					},
				],
				tax_class: 'Tax Class Variable',
			},
		];
		await api
			.post( 'products', {
				name: variableProductName,
				type: 'variable',
				tax_class: 'Tax Class Variable',
			} )
			.then( ( response ) => {
				variableProductId = response.data.id;
				for ( const key in variations ) {
					api.post(
						`products/${ variableProductId }/variations`,
						variations[ key ]
					);
				}
			} );
		// create external product
		await api
			.post( 'products', {
				name: externalProductName,
				regular_price: '800',
				tax_class: 'Tax Class External',
				external_url: 'https://wordpress.org/plugins/woocommerce',
				type: 'external',
				button_text: 'Buy now',
			} )
			.then( ( response ) => {
				externalProductId = response.data.id;
			} );
		// create grouped product
		await api
			.post( 'products', { name: 'Add-on A', regular_price: '11.95' } )
			.then( ( response ) => {
				subProductAId = response.data.id;
			} );
		await api
			.post( 'products', { name: 'Add-on B', regular_price: '18.97' } )
			.then( ( response ) => {
				subProductBId = response.data.id;
			} );
		await api
			.post( 'products', {
				name: groupedProductName,
				regular_price: '29.99',
				grouped_products: [ subProductAId, subProductBId ],
				type: 'grouped',
			} )
			.then( ( response ) => {
				groupedProductId = response.data.id;
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// cleans up all products after run
		await api.post( 'products/batch', {
			delete: [
				simpleProductId,
				variableProductId,
				externalProductId,
				subProductAId,
				subProductBId,
				groupedProductId,
			],
		} );
		// clean up tax classes and rates
		for ( let i = 0; i < taxClassSlugs.length; i++ ) {
			await api.delete( `taxes/classes/${ taxClassSlugs[ i ] }`, {
				force: true,
			} );
		}
		// turn off taxes
		await api.put( 'settings/general/woocommerce_calc_taxes', {
			value: 'no',
		} );

		// if we're only running the second test, there's no orderId created
		if ( orderId ) {
			await api.delete( `orders/${ orderId }`, { force: true } );
		}
	} );

	test( 'can create new order', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=shop_order' );
		await expect( page.locator( 'h1.wp-heading-inline' ) ).toContainText(
			'Add new order'
		);

		await page.waitForLoadState( 'networkidle' );
		// get order ID from the page
		const orderHtmlElement = await page.$(
			'h2.woocommerce-order-data__heading'
		);
		const orderText = await page.evaluate(
			( element ) => element.textContent,
			orderHtmlElement
		);
		orderId = orderText.match( /([0-9])\w+/ );
		orderId = orderId[ 0 ].toString();

		await page.selectOption( '#order_status', 'wc-processing' );
		await page.fill( 'input[name=order_date]', '2018-12-13' );
		await page.fill( 'input[name=order_date_hour]', '18' );
		await page.fill( 'input[name=order_date_minute]', '55' );

		await page.click( 'button.save_order' );

		await expect(
			page.locator( 'div.updated.notice.notice-success.is-dismissible', {
				has: page.locator( 'p' ),
			} )
		).toContainText( 'Order updated.' );
		await expect( page.locator( '#order_status' ) ).toHaveValue(
			'wc-processing'
		);
		await expect( page.locator( 'div.note_content' ) ).toContainText(
			'Order status changed from Pending payment to Processing.'
		);
	} );

	test( 'can create new complex order with multiple product types & tax classes', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=shop_order' );

		// open modal for adding line items
		await page.click( 'button.add-line-item' );
		await page.click( 'button.add-order-item' );

		// search for each product to add
		await page.click( 'text=Search for a product…' );
		await page.type(
			'input:below(:text("Search for a product…"))',
			simpleProductName
		);
		await page.click(
			'li.select2-results__option.select2-results__option--highlighted'
		);

		await page.click( 'text=Search for a product…' );
		await page.type(
			'input:below(:text("Search for a product…"))',
			variableProductName
		);
		await page.click(
			'li.select2-results__option.select2-results__option--highlighted'
		);

		await page.click( 'text=Search for a product…' );
		await page.type(
			'input:below(:text("Search for a product…"))',
			groupedProductName
		);
		await page.click(
			'li.select2-results__option.select2-results__option--highlighted'
		);

		await page.click( 'text=Search for a product…' );
		await page.type(
			'input:below(:text("Search for a product…"))',
			externalProductName
		);
		await page.click(
			'li.select2-results__option.select2-results__option--highlighted'
		);

		await page.click( 'button#btn-ok' );

		// assert that products added
		await expect( page.locator( 'td.name > a >> nth=0' ) ).toContainText(
			simpleProductName
		);
		await expect( page.locator( 'td.name > a >> nth=1' ) ).toContainText(
			variableProductName
		);
		await expect( page.locator( 'td.name > a >> nth=2' ) ).toContainText(
			groupedProductName
		);
		await expect( page.locator( 'td.name > a >> nth=3' ) ).toContainText(
			externalProductName
		);

		// Recalculate taxes
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page.click( 'text=Recalculate' );

		// verify tax names
		let i = 0;
		for ( const taxRate of taxRates ) {
			await expect(
				page.locator( `th.line_tax >> nth=${ i }` )
			).toHaveText( taxRate.name );
			i++;
		}

		// verify tax amounts
		i = 1; // subtotal line is 0 here
		for ( const taxAmount of taxTotals ) {
			await expect(
				page.locator( `.wc-order-totals td.total >> nth=${ i }` )
			).toContainText( taxAmount );
			i++;
		}
	} );
} );
