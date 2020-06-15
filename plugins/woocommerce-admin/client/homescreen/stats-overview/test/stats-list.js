/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { StatsList } from '../stats-list';
import { recordEvent } from 'lib/tracks';

jest.mock( 'lib/tracks' );

const stats = [
	{ stat: 'revenue/net_revenue', label: 'Net Sales' },
	{ stat: 'orders/orders_count', label: 'Orders' },
];

const data = {
	data: [
		{
			stat: 'revenue/net_revenue',
			chart: 'net_revenue',
			label: 'Net Sales',
			format: 'currency',
			value: 100,
			_links: {
				api: [
					{
						href:
							'http://tangaroa.test/wp-json/wc-analytics/reports/revenue/stats',
					},
				],
				report: [ { href: '/analytics/revenue' } ],
			},
		},
		{
			stat: 'orders/orders_count',
			chart: 'orders_count',
			label: 'Orders',
			format: 'number',
			value: 100,
			_links: {
				api: [
					{
						href:
							'http://tangaroa.test/wp-json/wc-analytics/reports/orders/stats',
					},
				],
				report: [ { href: '/analytics/orders' } ],
			},
		},
	],
};

describe( 'StatsList', () => {
	it( 'should render SummaryNumbers', () => {
		render(
			<StatsList
				stats={ stats }
				primaryData={ data }
				secondaryData={ data }
				query={ {
					period: 'today',
					compare: 'previous_period',
				} }
			/>
		);

		// Check that there should be two.
		expect( screen.getByText( 'Net Sales' ) ).toBeDefined();
		expect( screen.getByText( 'Orders' ) ).toBeDefined();
	} );

	it( 'should render placeholders when data is fetching', () => {
		render( <StatsList stats={ stats } primaryRequesting={ true } /> );

		// Check that there should be two.
		expect( screen.getAllByTestId( 'summary-placeholder' ) ).toHaveLength(
			2
		);
	} );

	it( 'should record an event on click of SummaryNumbers', () => {
		render(
			<StatsList
				stats={ stats }
				primaryData={ data }
				secondaryData={ data }
				query={ {
					period: 'today',
					compare: 'previous_period',
				} }
			/>
		);

		fireEvent.click( screen.getByText( 'Net Sales' ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'statsoverview_indicators_click',
			{
				key: 'revenue/net_revenue',
			}
		);
	} );
} );
