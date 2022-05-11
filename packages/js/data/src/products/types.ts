/**
 * External dependencies
 */
import { Schema } from '@wordpress/core-data';

export type Product = Schema.Post & {
	id: number;
	name: string;
	slug: string;
	permalink: string;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	type: 'simple' | 'grouped' | 'external' | 'variable';
	status: 'draft' | 'pending' | 'private' | 'publish' | 'any' | 'future';
	featured: boolean;
	description: string;
	short_description: string;
	sku: string;
	price: string;
	regular_price: string;
	sale_price: string;
};

export type PartialProduct = Partial< Product > & Pick< Product, 'id' >;

export type ProductQuery = {
	context: string;
	page: number;
	per_page: number;
	search: string;
	_fields: string[];
	after: string;
	before: string;
	exclude: string;
	include: string;
	offset: number;
	order: 'asc' | 'desc';
	orderby:
		| 'date'
		| 'id'
		| 'include'
		| 'title'
		| 'slug'
		| 'price'
		| 'popularity'
		| 'rating';
	parent: number[];
	parent_exclude: number[];
	slug: string;
	status: 'any' | 'draft' | 'pending' | 'private' | 'publish' | 'future';
	type: 'simple' | 'grouped' | 'external' | 'variable';
	sku: string;
	featured: string;
	category: string;
	tag: string;
	shipping_class: string;
	attribute: string;
	attribute_term: string;
	tax_class: string;
	on_sale: boolean;
	min_price: string;
	max_price: string;
	stock_status: string;
};
