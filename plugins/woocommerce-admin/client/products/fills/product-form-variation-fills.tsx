/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import {
	__experimentalWooProductTabItem as WooProductTabItem,
	__experimentalWooProductSectionItem as WooProductSectionItem,
} from '@woocommerce/product-editor';
import { PartialProduct } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductVariationDetailsSection } from '../sections/product-variation-details-section';
import {
	VARIANT_TAB_GENERAL_ID,
	VARIANT_TAB_SHIPPING_ID,
	VARIANT_TAB_PRICING_ID,
	VARIANT_TAB_INVENTORY_ID,
	VARIANT_SHIPPING_SECTION_BASIC_ID,
	VARIANT_SHIPPING_SECTION_DIMENSIONS_ID,
	VARIANT_PRICING_SECTION_BASIC_ID,
	VARIANT_PRICING_SECTION_TAXES_ID,
	VARIANT_PRICING_SECTION_TAXES_ADVANCED_ID,
	VARIANT_INVENTORY_SECTION_ID,
	VARIANT_INVENTORY_SECTION_ADVANCED_ID,
	PLUGIN_ID,
} from './constants';
import { ShippingSectionFills } from './shipping-section';
import { PricingSectionFills } from './pricing-section';
import { InventorySectionFills } from './inventory-section';

const tabPropData = {
	general: {
		name: 'general',
		title: __( 'General', 'woocommerce' ),
	},
	pricing: {
		name: 'pricing',
		title: __( 'Pricing', 'woocommerce' ),
	},
	inventory: {
		name: 'inventory',
		title: __( 'Inventory', 'woocommerce' ),
	},
	shipping: {
		name: 'shipping',
		title: __( 'Shipping', 'woocommerce' ),
	},
	options: {
		name: 'options',
		title: __( 'Options', 'woocommerce' ),
	},
};

const Tabs = () => (
	<>
		<WooProductTabItem
			id={ VARIANT_TAB_GENERAL_ID }
			templates={ [ { name: 'tab/variation', order: 1 } ] }
			pluginId={ PLUGIN_ID }
			tabProps={ tabPropData.general }
		>
			<ProductVariationDetailsSection />
		</WooProductTabItem>
		<WooProductTabItem
			id={ VARIANT_TAB_PRICING_ID }
			templates={ [ { name: 'tab/variation', order: 3 } ] }
			pluginId={ PLUGIN_ID }
			tabProps={ tabPropData.pricing }
		>
			<WooProductSectionItem.Slot tab={ VARIANT_TAB_PRICING_ID } />
		</WooProductTabItem>
		<WooProductTabItem
			id={ VARIANT_TAB_INVENTORY_ID }
			templates={ [ { name: 'tab/variation', order: 5 } ] }
			pluginId={ PLUGIN_ID }
			tabProps={ tabPropData.inventory }
		>
			<WooProductSectionItem.Slot tab={ VARIANT_TAB_INVENTORY_ID } />
		</WooProductTabItem>
		<WooProductTabItem
			id={ VARIANT_TAB_SHIPPING_ID }
			templates={ [ { name: 'tab/variation', order: 7 } ] }
			pluginId={ PLUGIN_ID }
			tabProps={ tabPropData.shipping }
		>
			{ ( { product }: { product: PartialProduct } ) => (
				<WooProductSectionItem.Slot
					tab={ VARIANT_TAB_SHIPPING_ID }
					fillProps={ { product } }
				/>
			) }
		</WooProductTabItem>
	</>
);

registerPlugin( 'wc-admin-product-editor-form-variation-fills', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => (
		<>
			<Tabs />
			<ShippingSectionFills
				tabId={ VARIANT_TAB_SHIPPING_ID }
				basicSectionId={ VARIANT_SHIPPING_SECTION_BASIC_ID }
				dimensionsSectionId={ VARIANT_SHIPPING_SECTION_DIMENSIONS_ID }
			/>
			<PricingSectionFills
				tabId={ VARIANT_TAB_PRICING_ID }
				basicSectionId={ VARIANT_PRICING_SECTION_BASIC_ID }
				taxesSectionId={ VARIANT_PRICING_SECTION_TAXES_ID }
				taxesAdvancedSectionId={
					VARIANT_PRICING_SECTION_TAXES_ADVANCED_ID
				}
			/>
			<InventorySectionFills
				tabId={ VARIANT_TAB_INVENTORY_ID }
				basicSectionId={ VARIANT_INVENTORY_SECTION_ID }
				advancedSectionId={ VARIANT_INVENTORY_SECTION_ADVANCED_ID }
			/>
		</>
	),
} );
