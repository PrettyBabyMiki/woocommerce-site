/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import TransientNotices from '..';

jest.mock( '@wordpress/data' );

useDispatch.mockReturnValue( {
	removeNotice: jest.fn(),
} );

jest.mock( '../snackbar/list', () =>
	jest.fn( ( { notices } ) => {
		return notices.map( ( notice ) => (
			<div key={ notice.title }>{ notice.title }</div>
		) );
	} )
);

describe( 'TransientNotices', () => {
	it( 'combines both notices and notices2 together and passes them to snackbar list', () => {
		useSelect.mockReturnValue( {
			notices: [ { title: 'first' } ],
			notices2: [ { title: 'second' } ],
		} );
		const { queryByText } = render( <TransientNotices /> );
		expect( queryByText( 'first' ) ).toBeInTheDocument();
		expect( queryByText( 'second' ) ).toBeInTheDocument();
	} );

	it( 'should default notices2 to empty array if undefined', () => {
		useSelect.mockReturnValue( {
			notices: [ { title: 'first' } ],
			notices2: undefined,
		} );
		const { queryByText } = render( <TransientNotices /> );
		expect( queryByText( 'first' ) ).toBeInTheDocument();
		expect( queryByText( 'second' ) ).not.toBeInTheDocument();
	} );
} );
