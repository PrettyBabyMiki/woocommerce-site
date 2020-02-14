/**
 * External dependencies
 */
import { mount, shallow } from 'enzyme';

/**
 * WooCommerce dependencies
 */
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency-format';
import { numberFormat } from 'lib/number-format';

/**
 * Internal dependencies
 */
import { Leaderboard } from '../';
import mockData from '../data/top-selling-products-mock-data';

const rows = mockData.map( ( row ) => {
	const { name, items_sold: itemsSold, net_revenue: netRevenue, orders_count: ordersCount } = row;
	return [
		{
			display: '<a href="#">' + name + '</a>',
			value: name,
		},
		{
			display: numberFormat( itemsSold ),
			value: itemsSold,
		},
		{
			display: numberFormat( ordersCount ),
			value: ordersCount,
		},
		{
			display: formatCurrency( netRevenue ),
			value: getCurrencyFormatDecimal( netRevenue ),
		},
	];
} );

const headers = [
	{
		label: 'Name',
	},
	{
		label: 'Items Sold',
	},
	{
		label: 'Orders',
	},
	{
		label: 'Net Sales',
	},
];

describe( 'Leaderboard', () => {
	test( 'should render empty message when there are no rows', () => {
		const leaderboard = shallow(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ [] }
				rows={ [] }
				totalRows={ 5 }
			/>
		);

		expect( leaderboard.find( 'EmptyTable' ).length ).toBe( 1 );
	} );

	test( 'should render correct data in the table', () => {
		const leaderboard = mount(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ headers }
				rows={ rows }
				totalRows={ 5 }
			/>
		);
		const table = leaderboard.find( 'TableCard' );
		const firstRow = table.props().rows[ 0 ];
		const tableItems = leaderboard.find( '.woocommerce-table__item' );

		expect( firstRow[ 0 ].value ).toBe( mockData[ 0 ].name );
		expect( firstRow[ 1 ].value ).toBe( mockData[ 0 ].items_sold );
		expect( firstRow[ 2 ].value ).toBe( mockData[ 0 ].orders_count );
		expect( firstRow[ 3 ].value ).toBe(
			getCurrencyFormatDecimal( mockData[ 0 ].net_revenue )
		);

		expect(
			leaderboard.render().find( '.woocommerce-table__item a' ).length
		).toBe( 5 );
		expect( tableItems.at( 0 ).text() ).toBe( mockData[ 0 ].name );
		expect( tableItems.at( 1 ).text() ).toBe(
			numberFormat( mockData[ 0 ].items_sold )
		);
		expect( tableItems.at( 2 ).text() ).toBe(
			numberFormat( mockData[ 0 ].orders_count )
		);
		expect( tableItems.at( 3 ).text() ).toBe(
			formatCurrency( mockData[ 0 ].net_revenue )
		);
	} );
} );
