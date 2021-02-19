/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ActivityPanel } from '../';

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUser: jest.fn().mockReturnValue( { currentUserCan: () => true } ),
} ) );

// We aren't testing the <DisplayOptions /> component here.
jest.mock( '../display-options', () => ( {
	DisplayOptions: jest.fn().mockReturnValue( '[DisplayOptions]' ),
} ) );

jest.mock( '../highlight-tooltip', () => ( {
	HighlightTooltip: jest.fn().mockReturnValue( '[HighlightTooltip]' ),
} ) );

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

describe( 'Activity Panel', () => {
	beforeEach( () => {
		useSelect.mockImplementation( () => ( {
			hasUnreadNotes: false,
			requestingTaskListOptions: false,
			setupTaskListComplete: false,
			setupTaskListHidden: false,
			trackedCompletedTasks: [],
		} ) );
	} );

	it( 'should render inbox tab on embedded pages', () => {
		render( <ActivityPanel isEmbedded query={ {} } /> );

		expect( screen.getByText( 'Inbox' ) ).toBeDefined();
	} );

	it( 'should render inbox tab if not on home screen', () => {
		render(
			<ActivityPanel query={ { page: 'wc-admin', path: '/customers' } } />
		);

		expect( screen.getByText( 'Inbox' ) ).toBeDefined();
	} );

	it( 'should not render inbox tab on home screen', () => {
		render( <ActivityPanel query={ { page: 'wc-admin' } } /> );

		expect( screen.queryByText( 'Inbox' ) ).toBeNull();
	} );

	it( 'should not render help tab if not on home screen', () => {
		render(
			<ActivityPanel query={ { page: 'wc-admin', path: '/customers' } } />
		);

		expect( screen.queryByText( 'Help' ) ).toBeNull();
	} );

	it( 'should render help tab if on home screen', () => {
		render( <ActivityPanel query={ { page: 'wc-admin' } } /> );

		expect( screen.getByText( 'Help' ) ).toBeDefined();
	} );

	it( 'should render help tab before options load', async () => {
		useSelect.mockImplementation( () => ( {
			requestingTaskListOptions: true,
		} ) );
		render(
			<ActivityPanel
				query={ {
					task: 'products',
				} }
			/>
		);

		const tabs = await screen.findAllByRole( 'tab' );

		// Expect that the only tab is "Help".
		expect( tabs ).toHaveLength( 1 );
		expect( screen.getByText( 'Help' ) ).toBeDefined();
	} );

	it( 'should not render help tab when not on main route', () => {
		render(
			<ActivityPanel
				query={ {
					page: 'wc-admin',
					task: 'products',
					path: '/customers',
				} }
			/>
		);

		// Expect that "Help" tab is absent.
		expect( screen.queryByText( 'Help' ) ).toBeNull();
	} );

	it( 'should render display options if on home screen', () => {
		render(
			<ActivityPanel
				query={ {
					page: 'wc-admin',
				} }
			/>
		);

		expect( screen.getByText( '[DisplayOptions]' ) ).toBeDefined();
	} );

	it( 'should only render the store setup link when TaskList is not complete', () => {
		const { queryByText, rerender } = render(
			<ActivityPanel
				query={ {
					task: 'products',
				} }
			/>
		);

		expect( queryByText( 'Store Setup' ) ).toBeDefined();

		useSelect.mockImplementation( () => ( {
			requestingTaskListOptions: false,
			setupTaskListComplete: true,
			setupTaskListHidden: false,
		} ) );

		rerender(
			<ActivityPanel
				query={ {
					task: 'products',
				} }
			/>
		);

		expect( queryByText( 'Store Setup' ) ).toBeNull();
	} );

	it( 'should not render the store setup link when on the home screen and TaskList is not complete', () => {
		const { queryByText } = render(
			<ActivityPanel
				query={ {
					page: 'wc-admin',
					task: '',
				} }
			/>
		);

		expect( queryByText( 'Store Setup' ) ).toBeNull();
	} );

	it( 'should render the store setup link when on embedded pages and TaskList is not complete', () => {
		const { getByText } = render(
			<ActivityPanel isEmbedded query={ {} } />
		);

		expect( getByText( 'Store Setup' ) ).toBeInTheDocument();
	} );

	it( 'should not render the store setup link when a user does not have capabilties', () => {
		useUser.mockImplementation( () => ( {
			currentUserCan: () => false,
		} ) );

		const { queryByText } = render(
			<ActivityPanel
				query={ {
					task: 'products',
				} }
			/>
		);

		expect( queryByText( 'Store Setup' ) ).toBeDefined();
	} );

	describe( 'help panel tooltip', () => {
		it( 'should render highlight tooltip when task count is at-least 2, task is not completed, and tooltip not shown yet', () => {
			const { getByText } = render(
				<ActivityPanel
					userPreferencesData={ {
						task_list_tracked_started_tasks: { payment: 2 },
					} }
					isEmbedded
					query={ { task: 'payment' } }
				/>
			);

			expect( getByText( '[HighlightTooltip]' ) ).toBeInTheDocument();
		} );

		it( 'should not render highlight tooltip when task is not visited more then once', () => {
			useSelect.mockImplementation( () => ( {
				requestingTaskListOptions: false,
				setupTaskListComplete: false,
				setupTaskListHidden: false,
				trackedCompletedTasks: [],
			} ) );
			render(
				<ActivityPanel
					userPreferencesData={ {
						task_list_tracked_started_tasks: { payment: 1 },
					} }
					isEmbedded
					query={ { task: 'payment' } }
				/>
			);

			expect( screen.queryByText( '[HighlightTooltip]' ) ).toBeNull();

			render(
				<ActivityPanel
					userPreferencesData={ {
						task_list_tracked_started_tasks: {},
					} }
					isEmbedded
					query={ { task: 'payment' } }
				/>
			);

			expect( screen.queryByText( '[HighlightTooltip]' ) ).toBeNull();
		} );

		it( 'should not render highlight tooltip when task is visited twice, but completed already', () => {
			useSelect.mockImplementation( () => ( {
				requestingTaskListOptions: false,
				setupTaskListComplete: false,
				setupTaskListHidden: false,
				trackedCompletedTasks: [ 'payment' ],
			} ) );

			const { queryByText } = render(
				<ActivityPanel
					userPreferencesData={ {
						task_list_tracked_started_tasks: { payment: 2 },
					} }
					isEmbedded
					query={ { task: 'payment' } }
				/>
			);

			expect( queryByText( '[HighlightTooltip]' ) ).toBeNull();
		} );

		it( 'should not render highlight tooltip when task is visited twice, not completed, but already shown', () => {
			const { queryByText } = render(
				<ActivityPanel
					userPreferencesData={ {
						task_list_tracked_started_tasks: { payment: 2 },
						help_panel_highlight_shown: 'yes',
					} }
					isEmbedded
					query={ { task: 'payment' } }
				/>
			);

			expect( queryByText( '[HighlightTooltip]' ) ).toBeNull();
		} );
	} );
} );
