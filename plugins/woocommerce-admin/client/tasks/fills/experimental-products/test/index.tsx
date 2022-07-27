/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Products } from '../';
import { defaultSurfacedProductTypes, productTypes } from '../constants';
import { getAdminSetting } from '~/utils/admin-settings';
import { useProductTaskExperiment } from '../use-product-layout-experiment';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: jest.fn(),
} ) );

jest.mock( '../use-product-layout-experiment', () => ( {
	useProductTaskExperiment: jest.fn().mockReturnValue( {
		isLoading: false,
		experimentLayout: 'stacked',
	} ),
} ) );

jest.mock( '../use-create-product-by-type', () => ( {
	useCreateProductByType: jest
		.fn()
		.mockReturnValue( { createProductByType: jest.fn() } ),
} ) );

global.fetch = jest.fn().mockImplementation( () =>
	Promise.resolve( {
		json: () => Promise.resolve( {} ),
		status: 200,
	} )
);

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

const confirmModalText =
	"We'll import images from woocommerce.com to set up your sample products.";

describe( 'Products', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getSettings: () => ( {
					general: {
						woocommerce_default_country: 'US',
					},
				} ),
			} ) )
		);
	} );

	it( 'should render default products types when onboardingData.profile.productType is null', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: null,
			},
		} ) );
		const { queryByText } = render( <Products /> );

		productTypes.forEach( ( { key, title } ) => {
			if ( defaultSurfacedProductTypes.includes( key ) ) {
				expect( queryByText( title ) ).toBeInTheDocument();
			}
		} );
	} );

	it( 'should render digital products type with view more button', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'downloads' ],
			},
		} ) );
		const { queryByText, queryByRole } = render( <Products /> );

		expect( queryByText( 'Digital product' ) ).toBeInTheDocument();
		expect( queryByRole( 'menu' )?.childElementCount ).toBe( 1 );
		expect( queryByText( 'View more product types' ) ).toBeInTheDocument();
	} );

	it( 'should not render subscriptions products type when store is not in the US', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'subscriptions' ],
			},
		} ) );
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getSettings: () => ( {
					general: {
						woocommerce_default_country: 'GB',
					},
				} ),
			} ) )
		);
		const { queryByText } = render( <Products /> );

		expect( queryByText( 'Subscription product' ) ).not.toBeInTheDocument();
	} );

	it( 'should not render subscriptions products type when store country is unknown', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'subscriptions' ],
			},
		} ) );
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getSettings: () => ( {
					general: {
						woocommerce_default_country: undefined,
					},
				} ),
			} ) )
		);
		const { queryByText } = render( <Products /> );

		expect( queryByText( 'Subscription product' ) ).not.toBeInTheDocument();
	} );

	it( 'should render subscriptions products type when store is in the US', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'subscriptions' ],
			},
		} ) );
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getSettings: () => ( {
					general: {
						woocommerce_default_country: 'US',
					},
				} ),
			} ) )
		);
		const { queryByText } = render( <Products /> );

		expect( queryByText( 'Subscription product' ) ).toBeInTheDocument();
	} );

	it( 'clicking on suggested product should fire event tasklist_add_product with method: product_template, tasklist_product_template_selection with is_suggested:true and task_completion_time', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'downloads' ],
			},
		} ) );
		const { getByRole } = render( <Products /> );

		userEvent.click(
			getByRole( 'menuitem', {
				name: 'Digital product A digital product like service, downloadable book, music or video.',
			} )
		);

		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'tasklist_add_product',
			{ method: 'product_template' }
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'tasklist_product_template_selection',
			{ is_suggested: true, product_type: 'digital' }
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			3,
			'task_completion_time',
			{ task_name: 'products', time: '0-2s' }
		);
	} );

	it( 'clicking on not-suggested product should fire event tasklist_add_product with method: product_template, tasklist_product_template_selection with is_suggested:false and task_completion_time', async () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'downloads' ],
			},
		} ) );
		const { queryByText, getByRole, queryByRole } = render( <Products /> );

		expect( queryByText( 'View more product types' ) ).toBeInTheDocument();

		userEvent.click(
			getByRole( 'button', { name: 'View more product types' } )
		);

		await waitFor( () =>
			expect( queryByRole( 'menu' )?.childElementCount ).toBe(
				productTypes.length
			)
		);
		userEvent.click(
			getByRole( 'menuitem', {
				name: 'Grouped product A collection of related products.',
			} )
		);

		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'tasklist_view_more_product_types_click'
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'tasklist_add_product',
			{ method: 'product_template' }
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			3,
			'tasklist_product_template_selection',
			{ is_suggested: false, product_type: 'grouped' }
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			4,
			'task_completion_time',
			{ task_name: 'products', time: '0-2s' }
		);
	} );

	it( 'should render all products type when clicking view more button', async () => {
		( getAdminSetting as jest.Mock ).mockImplementation( () => ( {
			profile: {
				product_types: [ 'downloads' ],
			},
		} ) );
		const { queryByText, getByRole, queryByRole } = render( <Products /> );

		expect( queryByText( 'View more product types' ) ).toBeInTheDocument();

		userEvent.click(
			getByRole( 'button', { name: 'View more product types' } )
		);

		await waitFor( () =>
			expect( queryByRole( 'menu' )?.childElementCount ).toBe(
				productTypes.length
			)
		);

		expect( queryByText( 'View less product types' ) ).toBeInTheDocument();
	} );

	it( 'should send a request to load sample products when the "Import sample products" button is clicked', async () => {
		const fetchMock = jest.spyOn( global, 'fetch' );
		const { queryByText, getByRole } = render( <Products /> );

		userEvent.click(
			getByRole( 'button', { name: 'View more product types' } )
		);
		expect( queryByText( 'Load Sample Products' ) ).toBeInTheDocument();

		userEvent.click(
			getByRole( 'link', { name: 'Load Sample Products' } )
		);
		await waitFor( () =>
			expect( queryByText( confirmModalText ) ).toBeInTheDocument()
		);

		userEvent.click(
			getByRole( 'button', { name: 'Import sample products' } )
		);
		await waitFor( () =>
			expect( queryByText( confirmModalText ) ).not.toBeInTheDocument()
		);

		expect( fetchMock ).toHaveBeenCalledWith(
			'/wc-admin/onboarding/tasks/import_sample_products?_locale=user',
			{
				body: undefined,
				credentials: 'include',
				headers: { Accept: 'application/json, */*;q=0.1' },
				method: 'POST',
			}
		);
	} );

	it( 'should close the confirmation modal when the cancel button is clicked', async () => {
		const { queryByText, getByRole } = render( <Products /> );

		userEvent.click(
			getByRole( 'button', { name: 'View more product types' } )
		);
		expect( queryByText( 'Load Sample Products' ) ).toBeInTheDocument();

		userEvent.click(
			getByRole( 'link', { name: 'Load Sample Products' } )
		);
		await waitFor( () =>
			expect( queryByText( confirmModalText ) ).toBeInTheDocument()
		);

		userEvent.click( getByRole( 'button', { name: 'Cancel' } ) );
		expect( queryByText( confirmModalText ) ).not.toBeInTheDocument();
		expect( recordEvent ).toHaveBeenCalledWith(
			'tasklist_cancel_load_sample_products_click'
		);
	} );

	it( 'should show spinner when layout experiment is loading', async () => {
		( useProductTaskExperiment as jest.Mock ).mockImplementation( () => {
			return {
				isLoading: true,
				experimentLayout: 'card',
			};
		} );
		const { container } = render( <Products /> );
		expect(
			container.getElementsByClassName( 'components-spinner' )
		).toHaveLength( 1 );
	} );

	it( 'should render card layout when experiment is assigned', async () => {
		( useProductTaskExperiment as jest.Mock ).mockImplementation( () => {
			return {
				isLoading: false,
				experimentLayout: 'card',
			};
		} );
		const { container } = render( <Products /> );
		expect(
			container.getElementsByClassName(
				'woocommerce-products-card-layout'
			)
		).toHaveLength( 1 );
	} );

	it( 'should render stacked layout when experiment is assigned', async () => {
		( useProductTaskExperiment as jest.Mock ).mockImplementation( () => {
			return {
				isLoading: false,
				experimentLayout: 'stacked',
			};
		} );
		const { container } = render( <Products /> );
		expect(
			container.getElementsByClassName( 'woocommerce-products-stack' )
		).toHaveLength( 1 );
	} );
} );
