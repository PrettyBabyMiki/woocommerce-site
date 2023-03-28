/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, createInterpolateElement } from '@wordpress/element';
import { TextControl } from '@woocommerce/components';
import { useBlockProps } from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

export function Edit() {
	const blockProps = useBlockProps();
	const [ name, setName ] = useEntityProp( 'postType', 'product', 'name' );

	return (
		<div { ...blockProps }>
			<TextControl
				label={ createInterpolateElement(
					__( 'Name <required />', 'woocommerce' ),
					{
						required: (
							<span className="woocommerce-product-form__optional-input">
								{ __( '(required)', 'woocommerce' ) }
							</span>
						),
					}
				) }
				name={ 'woocommerce-product-name' }
				placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
				onChange={ setName }
				value={ name || '' }
			/>
		</div>
	);
}
