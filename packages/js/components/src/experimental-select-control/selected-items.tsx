/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Tag from '../tag';
import { getItemLabelType, getItemValueType } from './types';

type SelectedItemsProps< ItemType > = {
	items: ItemType[];
	getItemLabel: getItemLabelType< ItemType >;
	getItemValue: getItemValueType< ItemType >;
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore These are the types provided by Downshift.
	getSelectedItemProps: ( { selectedItem: any, index: any } ) => {
		[ key: string ]: string;
	};
	onRemove: ( item: ItemType ) => void;
};

export const SelectedItems = < ItemType, >( {
	items,
	getItemLabel,
	getItemValue,
	getSelectedItemProps,
	onRemove,
}: SelectedItemsProps< ItemType > ) => {
	return (
		<>
			{ items.map( ( item, index ) => {
				return (
					// Disable reason: We prevent the default action to keep the input focused on click.
					// Keyboard users are unaffected by this change.
					/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
					<div
						key={ `selected-item-${ index }` }
						className="woocommerce-experimental-select-control__selected-item"
						{ ...getSelectedItemProps( {
							selectedItem: item,
							index,
						} ) }
						onClick={ ( event ) => {
							event.preventDefault();
						} }
					>
						{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
						{ /* @ts-ignore Additional props are not required. */ }
						<Tag
							id={ getItemValue( item ) }
							remove={ () => () => onRemove( item ) }
							label={ getItemLabel( item ) }
						/>
					</div>
				);
			} ) }
		</>
	);
};
