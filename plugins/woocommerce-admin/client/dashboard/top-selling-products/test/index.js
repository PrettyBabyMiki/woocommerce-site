/**
 * External dependencies
 *
 * @format
 */
import TestRenderer from 'react-test-renderer';
import { shallow } from 'enzyme';
import { createRegistry, RegistryProvider } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import TopSellingProductsWithSelect, { TopSellingProducts } from '../';
import { numberFormat } from 'lib/number';
import mockData from '../__mocks__/top-selling-products-mock-data';

// Mock <Table> to avoid tests failing due to it using DOM properties that
// are not available on TestRenderer.
jest.mock( '@woocommerce/components', () => ( {
	...require.requireActual( '@woocommerce/components' ),
	TableCard: () => null,
} ) );

describe( 'TopSellingProducts', () => {
	test( 'should render empty message when there are no rows', () => {
		const topSellingProducts = shallow( <TopSellingProducts data={ {} } /> );

		expect( topSellingProducts.find( 'EmptyTable' ).length ).toBe( 1 );
	} );

	test( 'should render correct data in the table', () => {
		const topSellingProducts = shallow( <TopSellingProducts data={ mockData } /> );
		const table = topSellingProducts.find( 'TableCard' );
		const firstRow = table.props().rows[ 0 ];

		expect( firstRow[ 0 ].value ).toBe( mockData[ 0 ].name );
		expect( firstRow[ 1 ].display ).toBe( numberFormat( mockData[ 0 ].items_sold ) );
		expect( firstRow[ 1 ].value ).toBe( mockData[ 0 ].items_sold );
		expect( firstRow[ 2 ].display ).toBe( numberFormat( mockData[ 0 ].orders_count ) );
		expect( firstRow[ 2 ].value ).toBe( mockData[ 0 ].orders_count );
		expect( firstRow[ 3 ].display ).toBe( formatCurrency( mockData[ 0 ].gross_revenue ) );
		expect( firstRow[ 3 ].value ).toBe( getCurrencyFormatDecimal( mockData[ 0 ].gross_revenue ) );
	} );

	test( 'should load report stats from API', () => {
		const getReportStatsMock = jest.fn().mockReturnValue( { data: mockData } );
		const isReportStatsRequestingMock = jest.fn().mockReturnValue( false );
		const getReportStatsErrorMock = jest.fn().mockReturnValue( undefined );
		const registry = createRegistry();
		registry.registerStore( 'wc-admin', {
			reducer: () => {},
			selectors: {
				getReportStats: getReportStatsMock,
				isReportStatsRequesting: isReportStatsRequestingMock,
				getReportStatsError: getReportStatsErrorMock,
			},
		} );
		const topSellingProductsWrapper = TestRenderer.create(
			<RegistryProvider value={ registry }>
				<TopSellingProductsWithSelect />
			</RegistryProvider>
		);
		const topSellingProducts = topSellingProductsWrapper.root.findByType( TopSellingProducts );

		const endpoint = '/wc/v3/reports/products';
		const query = { orderby: 'items_sold', per_page: 5, extended_info: 1 };

		expect( getReportStatsMock.mock.calls[ 0 ][ 1 ] ).toBe( endpoint );
		expect( getReportStatsMock.mock.calls[ 0 ][ 2 ] ).toEqual( query );
		expect( isReportStatsRequestingMock.mock.calls[ 0 ][ 1 ] ).toBe( endpoint );
		expect( isReportStatsRequestingMock.mock.calls[ 0 ][ 2 ] ).toEqual( query );
		expect( getReportStatsErrorMock.mock.calls[ 0 ][ 1 ] ).toBe( endpoint );
		expect( getReportStatsErrorMock.mock.calls[ 0 ][ 2 ] ).toEqual( query );
		expect( topSellingProducts.props.data ).toBe( mockData );
	} );
} );
