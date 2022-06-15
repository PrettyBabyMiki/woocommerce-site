/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import {
	getProductResourceName,
	getTotalProductCountResourceName,
} from './utils';
import { WPDataSelector, WPDataSelectors } from '../types';
import { ProductState } from './reducer';
import { PartialProduct, ProductQuery } from './types';

export const getProducts = createSelector(
	( state: ProductState, query: ProductQuery, defaultValue = undefined ) => {
		const resourceName = getProductResourceName( query );
		const ids = state.products[ resourceName ]
			? state.products[ resourceName ].data
			: undefined;
		if ( ! ids ) {
			return defaultValue;
		}
		if ( query._fields ) {
			return ids.map( ( id ) => {
				return query._fields.reduce(
					(
						product: PartialProduct,
						field: keyof PartialProduct
					) => {
						return {
							...product,
							[ field ]: state.data[ id ][ field ],
						};
					},
					{} as PartialProduct
				);
			} );
		}
		return ids.map( ( id ) => {
			return state.data[ id ];
		} );
	},
	( state, query ) => {
		const resourceName = getProductResourceName( query );
		const ids = state.products[ resourceName ]
			? state.products[ resourceName ].data
			: undefined;
		return [
			state.products[ resourceName ],
			...( ids || [] ).map( ( id: number ) => {
				return state.data[ id ];
			} ),
		];
	}
);

export const getProductsTotalCount = (
	state: ProductState,
	query: ProductQuery,
	defaultValue = undefined
) => {
	const resourceName = getTotalProductCountResourceName( query );
	const totalCount = state.productsCount.hasOwnProperty( resourceName )
		? state.productsCount[ resourceName ]
		: defaultValue;
	return totalCount;
};

export const getProductsError = (
	state: ProductState,
	query: ProductQuery
) => {
	const resourceName = getProductResourceName( query );
	return state.errors[ resourceName ];
};

export const getCreateProductError = (
	state: ProductState,
	query: ProductQuery
) => {
	const resourceName = getProductResourceName( query );
	return state.errors[ resourceName ];
};

export const getUpdateProductError = (
	state: ProductState,
	id: number,
	query: ProductQuery
) => {
	const resourceName = getProductResourceName( query );
	return state.errors[ `update/${ id }/${ resourceName }` ];
};

export const getDeleteProductError = ( state: ProductState, id: number ) => {
	return state.errors[ `delete/${ id }` ];
};

export type ProductsSelectors = {
	getCreateProductError: WPDataSelector< typeof getCreateProductError >;
	getProducts: WPDataSelector< typeof getProducts >;
	getProductsTotalCount: WPDataSelector< typeof getProductsTotalCount >;
	getProductsError: WPDataSelector< typeof getProductsError >;
} & WPDataSelectors;
