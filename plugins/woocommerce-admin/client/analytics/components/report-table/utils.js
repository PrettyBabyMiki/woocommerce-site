/** @format */
/**
 * External dependencies
 */
import { first } from 'lodash';

export function extendTableData( select, props, queriedTableData ) {
	const { extendItemsMethodNames, itemIdField, query } = props;
	const itemsData = queriedTableData.items.data;
	if (
		! Array.isArray( itemsData ) ||
		! itemsData.length ||
		! extendItemsMethodNames ||
		! itemIdField
	) {
		return queriedTableData;
	}

	const {
		[ extendItemsMethodNames.isError ]: isErrorMethod,
		[ extendItemsMethodNames.isRequesting ]: isRequestingMethod,
		[ extendItemsMethodNames.load ]: loadMethod,
	} = select( 'wc-api' );
	const extendQuery = {
		include: itemsData.map( item => item[ itemIdField ] ).join( ',' ),
		per_page: itemsData.length,
		...query,
	};
	const extendedItems = loadMethod( extendQuery );
	const isExtendedItemsRequesting = isRequestingMethod ? isRequestingMethod( extendQuery ) : false;
	const isExtendedItemsError = isErrorMethod ? isErrorMethod( extendQuery ) : false;

	const extendedItemsData = itemsData.map( item => {
		const extendedItemData = first(
			extendedItems.filter( extendedItem => item.id === extendedItem.id )
		);
		return {
			...item,
			...extendedItemData,
		};
	} );

	const isRequesting = queriedTableData.isRequesting || isExtendedItemsRequesting;
	const isError = queriedTableData.isError || isExtendedItemsError;

	return {
		...queriedTableData,
		isRequesting,
		isError,
		items: {
			...queriedTableData.items,
			data: extendedItemsData,
		},
	};
}
