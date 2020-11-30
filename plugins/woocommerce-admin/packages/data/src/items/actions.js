/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { NAMESPACE } from '../constants';

export function setItem( itemType, id, item ) {
	return {
		type: TYPES.SET_ITEM,
		id,
		item,
		itemType,
	};
}

export function setItems( itemType, query, items, totalCount ) {
	return {
		type: TYPES.SET_ITEMS,
		items,
		itemType,
		query,
		totalCount,
	};
}

export function setItemsTotalCount( itemType, query, totalCount ) {
	return {
		type: TYPES.SET_ITEMS_TOTAL_COUNT,
		itemType,
		query,
		totalCount,
	};
}

export function setError( itemType, query, error ) {
	return {
		type: TYPES.SET_ERROR,
		itemType,
		query,
		error,
	};
}

export function* updateProductStock( product, quantity ) {
	const updatedProduct = { ...product, stock_quantity: quantity };
	const { id, parent_id: parentId, type } = updatedProduct;

	// Optimistically update product stock.
	yield setItem( 'products', id, updatedProduct );

	let url = NAMESPACE;

	switch ( type ) {
		case 'variation':
			url += `/products/${ parentId }/variations/${ id }`;
			break;
		case 'variable':
		case 'simple':
		default:
			url += `/products/${ id }`;
	}

	try {
		yield apiFetch( {
			path: url,
			method: 'PUT',
			data: updatedProduct,
		} );
		return true;
	} catch ( error ) {
		// Update failed, return product back to original state.
		yield setItem( 'products', id, product );
		yield setError( 'products', id, error );
		return false;
	}
}
