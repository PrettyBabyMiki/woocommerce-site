/**
 * Internal dependencies
 */
const { productsApi } = require('../../endpoints/products');

/**
 * Tests for the WooCommerce Products API.
 *
 * @group api
 * @group products
 *
 */
 describe( 'Products API tests', () => {

	describe( 'List all products', () => {

		it( 'defaults', async () => {
			const result = await productsApi.listAll.products();
			expect( result.statusCode ).toEqual( 200 );
			expect( result.headers['x-wp-total'] ).toEqual( '18' );
			expect( result.headers['x-wp-totalpages'] ).toEqual( '2' );
		} );

		it( 'pagination', async () => {
			const pageSize = 4;
			const page1 = await productsApi.listAll.products( {
				per_page: pageSize,
			} );
			const page2 = await productsApi.listAll.products( {
				per_page: pageSize,
				page: 2,
			} );
			expect( page1.statusCode ).toEqual( 200 );
			expect( page2.statusCode ).toEqual( 200 );

			// Verify total page count.
			expect( page1.headers['x-wp-total'] ).toEqual( '18' );
			expect( page1.headers['x-wp-totalpages'] ).toEqual( '5' );
			
			// Verify we get pageSize'd arrays.
			expect( Array.isArray( page1.body ) ).toBe( true );
			expect( Array.isArray( page2.body ) ).toBe( true );
			expect( page1.body ).toHaveLength( pageSize );
			expect( page2.body ).toHaveLength( pageSize );

			// Ensure all of the product IDs are unique (no page overlap).
			const allProductIds = page1.body.concat( page2.body ).reduce( ( acc, product ) => {
				acc[ product.id ] = 1;
				return acc;
			}, {} );
			expect( Object.keys( allProductIds ) ).toHaveLength( pageSize * 2 );

			// Verify the last page only has 2 products as we expect.
			const page5 = await productsApi.listAll.products( {
				per_page: pageSize,
				page: 5,
			} );
			expect( Array.isArray( page5.body ) ).toBe( true );
			expect( page5.body ).toHaveLength( 2 );

			// Verify a page outside the total page count is empty.
			const page6 = await productsApi.listAll.products( {
				per_page: pageSize,
				page: 6,
			} );
			expect( Array.isArray( page6.body ) ).toBe( true );
			expect( page6.body ).toHaveLength( 0 );
		} );

	} );

} );
