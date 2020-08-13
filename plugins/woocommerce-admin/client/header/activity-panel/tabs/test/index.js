/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
/**
 * Internal dependencies
 */
import { Tabs } from '../';
import { recordEvent } from '../../../../lib/tracks';

jest.mock( 'lib/tracks', () => ( { recordEvent: jest.fn() } ) );
const generateTabs = () => {
	return [ '0', '1', '2', '3' ].map( ( name ) => ( {
		name,
		title: `Tab ${ name }`,
		icon: <span>icon</span>,
		unread: false,
	} ) );
};

describe( 'Activity Panel Tabs', () => {
	it( 'correctly tracks the selected tab', () => {
		const { getAllByRole } = render(
			<Tabs
				selectedTab={ '3' }
				tabs={ generateTabs() }
				onTabClick={ () => {} }
			/>
		);

		const tabs = getAllByRole( 'tab' );

		fireEvent.click( tabs[ 2 ] );

		expect( tabs[ 2 ] ).toHaveClass( 'is-active' );

		fireEvent.click( tabs[ 3 ] );

		expect( tabs[ 2 ] ).not.toHaveClass( 'is-active' );
		expect( tabs[ 3 ] ).toHaveClass( 'is-active' );
	} );

	it( 'closes a tab if its the same one last opened', () => {
		const { getAllByRole } = render(
			<Tabs
				selectedTab={ '3' }
				tabs={ generateTabs() }
				onTabClick={ () => {} }
			/>
		);

		const tabs = getAllByRole( 'tab' );

		fireEvent.click( tabs[ 2 ] );
		expect( tabs[ 2 ] ).toHaveClass( 'is-active' );
		fireEvent.click( tabs[ 2 ] );
		expect( tabs[ 2 ] ).not.toHaveClass( 'is-active' );
	} );

	it( 'triggers onTabClick with the selected when a tab is clicked', () => {
		const tabClickSpy = jest.fn();
		const generatedTabs = generateTabs();

		const { getAllByRole } = render(
			<Tabs
				selectedTab={ '3' }
				tabs={ generatedTabs }
				onTabClick={ tabClickSpy }
			/>
		);

		const tabs = getAllByRole( 'tab' );

		fireEvent.click( tabs[ 3 ] );

		expect( tabClickSpy ).toHaveBeenCalledWith( generatedTabs[ 3 ], true );
	} );

	it( 'records an event when panels are being opened and when the open panel changes', () => {
		const generatedTabs = generateTabs();

		const { getAllByRole } = render(
			<Tabs
				selectedTab={ '3' }
				tabs={ generatedTabs }
				onTabClick={ () => {} }
			/>
		);

		const tabs = getAllByRole( 'tab' );

		fireEvent.click( tabs[ 3 ] );

		expect( recordEvent ).toHaveBeenCalledWith( 'activity_panel_open', {
			tab: generatedTabs[ 3 ].name,
		} );
	} );
} );
