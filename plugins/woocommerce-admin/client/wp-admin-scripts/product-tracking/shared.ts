/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { waitUntilElementIsPresent } from './utils';
import { addCustomerEffortScoreExitPageListener } from '~/customer-effort-score-tracks/customer-effort-score-exit-page';

/**
 * Get the product data.
 *
 * @return object
 */

const isElementVisible = ( element: HTMLElement ) =>
	! ( window.getComputedStyle( element ).display === 'none' );

const getProductData = () => {
	const isBlockEditor =
		document.querySelectorAll( '.block-editor' ).length > 0;

	let description_value = '';
	let tagsText = '';

	if ( ! isBlockEditor ) {
		tagsText = (
			document.querySelector(
				'[name="tax_input[product_tag]"]'
			) as HTMLInputElement
		 ).value;
		const content = document.querySelector(
			'#content'
		) as HTMLInputElement;
		if ( content && isElementVisible( content ) ) {
			description_value = content.value;
		} else if ( typeof tinymce === 'object' && tinymce.get( 'content' ) ) {
			description_value = tinymce.get( 'content' ).getContent();
		}
	} else {
		description_value = (
			document.querySelector(
				'.block-editor-rich-text__editable'
			) as HTMLInputElement
		 )?.value;
	}

	const productData = {
		product_id: ( document.querySelector( '#post_ID' ) as HTMLInputElement )
			?.value,
		product_type: (
			document.querySelector( '#product-type' ) as HTMLInputElement
		 )?.value,
		is_downloadable: (
			document.querySelector( '#_downloadable' ) as HTMLInputElement
		 )?.checked
			? 'Yes'
			: 'No',
		is_virtual: (
			document.querySelector( '#_virtual' ) as HTMLInputElement
		 )?.checked
			? 'Yes'
			: 'No',
		manage_stock: (
			document.querySelector( '#_manage_stock' ) as HTMLInputElement
		 )?.checked
			? 'Yes'
			: 'No',
		attributes: document.querySelectorAll( '.woocommerce_attribute' )
			.length,
		categories: document.querySelectorAll(
			'[name="tax_input[product_cat][]"]:checked'
		).length,
		cross_sells: document.querySelectorAll( '#crosssell_ids option' ).length
			? 'Yes'
			: 'No',
		description: description_value.trim() !== '' ? 'Yes' : 'No',
		enable_reviews: (
			document.querySelector( '#comment_status' ) as HTMLInputElement
		 )?.checked
			? 'Yes'
			: 'No',
		is_block_editor: isBlockEditor,
		menu_order:
			parseInt(
				( document.querySelector( '#menu_order' ) as HTMLInputElement )
					?.value ?? 0,
				10
			) !== 0
				? 'Yes'
				: 'No',
		product_gallery: document.querySelectorAll(
			'#product_images_container .product_images > li'
		).length,
		product_image:
			parseInt(
				(
					document.querySelector(
						'#_thumbnail_id'
					) as HTMLInputElement
				 )?.value,
				10
			) > 0
				? 'Yes'
				: 'No',
		purchase_note: (
			document.querySelector( '#_purchase_note' ) as HTMLInputElement
		 )?.value.length
			? 'Yes'
			: 'No',
		sale_price: (
			document.querySelector( '#_sale_price' ) as HTMLInputElement
		 )?.value
			? 'Yes'
			: 'No',
		short_description: (
			document.querySelector( '#excerpt' ) as HTMLInputElement
		 )?.value.length
			? 'Yes'
			: 'No',
		tags: tagsText.length > 0 ? tagsText.split( ',' ).length : 0,
		upsells: document.querySelectorAll( '#upsell_ids option' ).length
			? 'Yes'
			: 'No',
		weight: ( document.querySelector( '#_weight' ) as HTMLInputElement )
			?.value
			? 'Yes'
			: 'No',
	};
	return productData;
};

/**
 * Get the publish date as a string.
 *
 * @param  prefix Prefix for date element selectors.
 * @return string
 */
const getPublishDate = ( prefix = '' ) => {
	const month = (
		document.querySelector( `#${ prefix }mm` ) as HTMLInputElement
	 )?.value;
	const day = (
		document.querySelector( `#${ prefix }jj` ) as HTMLInputElement
	 )?.value;
	const year = (
		document.querySelector( `#${ prefix }aa` ) as HTMLInputElement
	 )?.value;
	const hours = (
		document.querySelector( `#${ prefix }hh` ) as HTMLInputElement
	 )?.value;
	const seconds = (
		document.querySelector( `#${ prefix }mn` ) as HTMLInputElement
	 )?.value;

	return `${ month }-${ day }-${ year } ${ hours }:${ seconds }`;
};

/**
 * Get the data from the publishing widget.
 *
 * @return object
 */
const getPublishingWidgetData = () => {
	return {
		status: ( document.querySelector( '#post_status' ) as HTMLInputElement )
			?.value,
		visibility: (
			document.querySelector(
				'input[name="visibility"]:checked'
			) as HTMLInputElement
		 )?.value,
		date: getPublishDate() !== getPublishDate( 'hidden_' ) ? 'yes' : 'no',
		catalog_visibility: (
			document.querySelector(
				'input[name="_visibility"]:checked'
			) as HTMLInputElement
		 )?.value,
		featured: ( document.querySelector( '#_featured' ) as HTMLInputElement )
			?.checked,
	};
};

/**
 * Prefix all object keys with a string.
 *
 * @param  obj    Object to create keys from.
 * @param  prefix Prefix used before all keys.
 * @return object
 */
const prefixObjectKeys = (
	obj: { [ key: string ]: unknown },
	prefix: string
) => {
	return Object.fromEntries(
		Object.entries( obj ).map( ( [ k, v ] ) => [ `${ prefix }${ k }`, v ] )
	);
};

/**
 * Initialize all product screen tracks.
 */

export const initProductScreenTracks = () => {
	const initialPublishingData = getPublishingWidgetData();

	document
		.querySelector( '#post-preview' )
		?.addEventListener( 'click', () => {
			recordEvent( 'product_preview_changes' );
		} );

	document
		.querySelector( '.submitduplicate' )
		?.addEventListener( 'click', () => {
			recordEvent( 'product_copy', getProductData() );
		} );

	document
		.querySelector( '.submitdelete' )
		?.addEventListener( 'click', () => {
			recordEvent( 'product_delete', getProductData() );
		} );

	document
		.querySelectorAll(
			'.edit-post-status, .edit-visibility, .edit-timestamp, .edit-catalog-visibility'
		)
		.forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				recordEvent( 'product_publish_widget_edit', {
					...getPublishingWidgetData(),
					...getProductData(),
				} );
			} );
		} );

	document
		.querySelectorAll(
			'.save-post-status, .save-post-visibility, .save-timestamp, .save-post-visibility'
		)
		.forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				recordEvent( 'product_publish_widget_save', {
					...prefixObjectKeys( getPublishingWidgetData(), 'new_' ),
					...prefixObjectKeys( initialPublishingData, 'current_' ),
					...getProductData(),
				} );
			} );
		} );

	document
		.querySelectorAll( '.handle-order-lower, .handle-order-higher' )
		.forEach( ( button ) => {
			button.addEventListener( 'click', ( event ) => {
				const postBox = ( event.target as HTMLElement ).closest(
					'.postbox'
				);

				if ( ! postBox ) {
					return;
				}

				recordEvent( 'product_widget_order_change', {
					widget: postBox.id,
				} );
			} );
		} );

	document
		.querySelector( '#show-settings-link' )
		?.addEventListener( 'click', () => {
			recordEvent( 'product_screen_options_open' );
		} );

	document
		.querySelectorAll( '#adv-settings .metabox-prefs input[type=checkbox]' )
		.forEach( ( input ) => {
			input.addEventListener( 'change', () => {
				recordEvent( 'product_screen_elements', {
					selected_element: ( input as HTMLInputElement ).value,
					checkbox: ( input as HTMLInputElement ).checked,
				} );
			} );
		} );

	document
		.querySelectorAll( 'input[name="screen_columns"]' )
		.forEach( ( input ) => {
			input.addEventListener( 'change', () => {
				recordEvent( 'product_layout', {
					selected_layout: ( input as HTMLInputElement ).value,
				} );
			} );
		} );

	document
		.querySelector( '#editor-expand-toggle' )
		?.addEventListener( 'change', ( event ) => {
			recordEvent( 'product_additional_settings', {
				checkbox: ( event.target as HTMLInputElement ).checked,
			} );
		} );

	// Product tags

	function deleteTagEventListener( event: Event ) {
		recordEvent( 'product_tags_delete', {
			page: 'product',
			tag_list_size:
				document.querySelector( '.tagchecklist' )?.children.length || 0,
		} );
	}

	function addTagsDeleteTracks() {
		const tagsDeleteButtons = document.querySelectorAll(
			'#product_tag .ntdelbutton'
		);
		tagsDeleteButtons.forEach( ( button ) => {
			button.removeEventListener( 'click', deleteTagEventListener );
			button.addEventListener( 'click', deleteTagEventListener );
		} );
	}
	waitUntilElementIsPresent(
		'#product_tag .tagchecklist',
		addTagsDeleteTracks
	);

	document
		.querySelector( '.tagadd' )
		?.addEventListener( 'click', ( event ) => {
			const tagInput = document.querySelector< HTMLInputElement >(
				'#new-tag-product_tag'
			);
			if ( tagInput && tagInput.value && tagInput.value.length > 0 ) {
				recordEvent( 'product_tags_add', {
					page: 'product',
					tag_string_length: tagInput.value.length,
					tag_list_size:
						( document.querySelector( '.tagchecklist' )?.children
							.length || 0 ) + 1,
					most_used: false,
				} );
				setTimeout( () => {
					addTagsDeleteTracks();
				}, 500 );
			}
		} );

	function addMostUsedTagEventListener( event: Event ) {
		recordEvent( 'product_tags_add', {
			page: 'product',
			tag_string_length: ( event.target as HTMLAnchorElement ).textContent
				?.length,
			tag_list_size:
				document.querySelector( '.tagchecklist' )?.children.length || 0,
			most_used: true,
		} );
		addTagsDeleteTracks();
	}

	function addMostUsedTagsTracks() {
		const tagCloudLinks = document.querySelectorAll(
			'#tagcloud-product_tag .tag-cloud-link'
		);
		tagCloudLinks.forEach( ( button ) => {
			button.removeEventListener( 'click', addMostUsedTagEventListener );
			button.addEventListener( 'click', addMostUsedTagEventListener );
		} );
	}

	document
		.querySelector( '.tagcloud-link' )
		?.addEventListener( 'click', () => {
			waitUntilElementIsPresent(
				'#tagcloud-product_tag',
				addMostUsedTagsTracks
			);
		} );

	// Attribute tracks.

	function addNewTermEventHandler() {
		recordEvent( 'product_attributes_add_term', {
			page: 'product',
		} );
	}

	function addNewAttributeTermTracks() {
		const addNewTermButtons = document.querySelectorAll(
			'.woocommerce_attribute .add_new_attribute'
		);
		addNewTermButtons.forEach( ( button ) => {
			button.removeEventListener( 'click', addNewTermEventHandler );
			button.addEventListener( 'click', addNewTermEventHandler );
		} );
	}
	addNewAttributeTermTracks();

	document
		.querySelector( '.add_attribute' )
		?.addEventListener( 'click', () => {
			setTimeout( () => {
				addNewAttributeTermTracks();
			}, 1000 );
		} );

	const attributesCount = document.querySelectorAll(
		'.woocommerce_attribute'
	).length;

	document
		.querySelector( '.save_attributes' )
		?.addEventListener( 'click', () => {
			const newAttributesCount = document.querySelectorAll(
				'.woocommerce_attribute'
			).length;
			if ( newAttributesCount > attributesCount ) {
				recordEvent( 'product_attributes_add', {
					page: 'product',
					enable_archive: '',
					default_sort_order: '',
				} );
			}
		} );

	document
		.querySelector(
			'#woocommerce-product-updated-message-view-product__link'
		)
		?.addEventListener( 'click', () => {
			recordEvent( 'product_view_product_click', getProductData() );
		} );

	const dismissProductUpdatedButtonSelector =
		'.notice-success.is-dismissible > button';

	waitUntilElementIsPresent( dismissProductUpdatedButtonSelector, () => {
		document
			.querySelector( dismissProductUpdatedButtonSelector )
			?.addEventListener( 'click', () => {
				recordEvent( 'product_view_product_dismiss', getProductData() );
			} );
	} );
};

export function addExitPageListener( pageId: string ) {
	let productChanged = false;
	let triggeredDelete = false;

	const deleteButton = document.querySelector( '#submitpost a.submitdelete' );

	if ( deleteButton ) {
		deleteButton.addEventListener( 'click', function () {
			triggeredDelete = true;
		} );
	}

	function checkIfSubmitButtonsDisabled() {
		const submitButtonSelectors = [
			'#submitpost [type="submit"]',
			'#submitpost #post-preview',
		];
		let isDisabled = false;
		for ( const sel of submitButtonSelectors ) {
			document.querySelectorAll( sel ).forEach( ( element ) => {
				if ( element.classList.contains( 'disabled' ) ) {
					isDisabled = true;
				}
			} );
		}
		return isDisabled;
	}
	window.addEventListener( 'beforeunload', function ( event ) {
		// Check if button disabled or triggered delete to see if user saved or deleted the product instead.
		if ( checkIfSubmitButtonsDisabled() || triggeredDelete ) {
			productChanged = false;
			triggeredDelete = false;
			return;
		}
		const editor = window.tinymce && window.tinymce.get( 'content' );

		if ( window.wp.autosave ) {
			productChanged = window.wp.autosave.server.postChanged();
		} else if ( editor ) {
			productChanged = ! editor.isHidden() && editor.isDirty();
		}
	} );

	addCustomerEffortScoreExitPageListener( pageId, () => {
		return productChanged;
	} );
}
