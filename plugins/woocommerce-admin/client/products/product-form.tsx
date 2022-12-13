/**
 * External dependencies
 */
import { Form, FormRef } from '@woocommerce/components';
import { PartialProduct, Product } from '@woocommerce/data';
import { Ref } from 'react';

/**
 * Internal dependencies
 */
import { ProductFormHeader } from './layout/product-form-header';
import { ProductFormLayout } from './layout/product-form-layout';
import { ProductDetailsSection } from './sections/product-details-section';
import { ProductInventorySection } from './sections/product-inventory-section';
import { PricingSection } from './sections/pricing-section';
import { ProductShippingSection } from './sections/product-shipping-section';
import { ProductVariationsSection } from './sections/product-variations-section';
import { ImagesSection } from './sections/images-section';
import './product-page.scss';
import { validate } from './product-validation';
import { AttributesSection } from './sections/attributes-section';
import { ProductFormFooter } from './layout/product-form-footer';
import { ProductFormTab } from './product-form-tab';

export const ProductForm: React.FC< {
	product?: PartialProduct;
	formRef?: Ref< FormRef< Partial< Product > > >;
} > = ( { product, formRef } ) => {
	return (
		<Form< Partial< Product > >
			initialValues={
				product || {
					reviews_allowed: true,
					name: '',
					sku: '',
					stock_quantity: 0,
					stock_status: 'instock',
				}
			}
			ref={ formRef }
			errors={ {} }
			validate={ validate }
		>
			<ProductFormHeader />
			<ProductFormLayout>
				<ProductFormTab name="general" title="General">
					<ProductDetailsSection />
					<ImagesSection />
					<AttributesSection />
				</ProductFormTab>
				<ProductFormTab name="pricing" title="Pricing">
					<PricingSection />
				</ProductFormTab>
				<ProductFormTab name="inventory" title="Inventory">
					<ProductInventorySection />
				</ProductFormTab>
				<ProductFormTab name="shipping" title="Shipping">
					<ProductShippingSection product={ product } />
				</ProductFormTab>
				<ProductFormTab name="options" title="Options">
					<ProductVariationsSection />
				</ProductFormTab>
			</ProductFormLayout>
			<ProductFormFooter />
		</Form>
	);
};
