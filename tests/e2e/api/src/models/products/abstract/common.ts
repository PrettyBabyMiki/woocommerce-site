import { AbstractProductData } from './data';
import { ModelID } from '../../model';
import {
	CatalogVisibility,
	ProductTerm,
	ProductLinks,
} from '../shared';

/**
 * The common parameters that all products can use in search.
 */
export type ProductSearchParams = { search: string };

/**
 * The base product URL.
 *
 * @return {string} RESTful Url.
 */
export const baseProductURL = () => '/wc/v3/products/';

/**
 * A common product URL builder.
 *
 * @param {ModelID} id the id of the product.
 * @return {string} RESTful Url.
 */
export const buildProductURL = ( id: ModelID ) => baseProductURL() + id;

/**
 * The base for all product types.
 */
export abstract class AbstractProduct extends AbstractProductData {
	/**
	 * The name of the product.
	 *
	 * @type {string}
	 */
	public readonly name: string = '';

	/**
	 * The slug of the product.
	 *
	 * @type {string}
	 */
	public readonly slug: string = '';

	/**
	 * The GMT datetime when the product was created.
	 *
	 * @type {Date}
	 */
	public readonly created: Date = new Date();

	/**
	 * The GMT datetime when the product was last modified.
	 *
	 * @type {Date}
	 */
	public readonly modified: Date = new Date();

	/**
	 * The product's short description.
	 *
	 * @type {string}
	 */
	public readonly shortDescription: string = '';

	/**
	 * An array of the categories this product is in.
	 *
	 * @type {ReadonlyArray.<ProductTerm>}
	 */
	public readonly categories: readonly ProductTerm[] = [];

	/**
	 * An array of the tags this product has.
	 *
	 * @type {ReadonlyArray.<ProductTerm>}
	 */
	public readonly tags: readonly ProductTerm[] = [];

	/**
	 * Indicates whether or not the product should be featured.
	 *
	 * @type {boolean}
	 */
	public readonly isFeatured: boolean = false;

	/**
	 * Indicates whether or not the product should be visible in the catalog.
	 *
	 * @type {CatalogVisibility}
	 */
	public readonly catalogVisibility: CatalogVisibility = CatalogVisibility.Everywhere;

	/**
	 * The current price of the product.
	 *
	 * @type {string}
	 */
	public readonly price: string = '';

	/**
	 * The rendered HTML for the current price of the product.
	 *
	 * @type {string}
	 */
	public readonly priceHtml: string = '';

	/**
	 * The regular price of the product when not discounted.
	 *
	 * @type {string}
	 */
	public readonly regularPrice: string = '';

	/**
	 * Indicates whether or not the product is currently on sale.
	 *
	 * @type {boolean}
	 */
	public readonly onSale: boolean = false;

	/**
	 * The price of the product when on sale.
	 *
	 * @type {string}
	 */
	public readonly salePrice: string = '';

	/**
	 * The GMT datetime when the product should start to be on sale.
	 *
	 * @type {Date|null}
	 */
	public readonly saleStart: Date | null = null;

	/**
	 * The GMT datetime when the product should no longer be on sale.
	 *
	 * @type {Date|null}
	 */
	public readonly saleEnd: Date | null = null;

	/**
	 * The count of sales of the product
	 *
	 * @type {number}
	 */
	public readonly totalSales: number = 0;

	/**
	 * Indicates whether or not a product allows reviews.
	 *
	 * @type {boolean}
	 */
	public readonly allowReviews: boolean = false;

	/**
	 * The average rating for the product.
	 *
	 * @type {number}
	 */
	public readonly averageRating: number = -1;

	/**
	 * The number of ratings for the product.
	 *
	 * @type {number}
	 */
	public readonly numRatings: number = -1;

	/**
	 * An array of IDs of related products.
	 *
	 * @type {ReadonlyArray.<number>}
	 */
	public readonly relatedIds: Array<number> = [];

	/**
	 * The products links.
	 *
	 * @type {ReadonlyArray.<ProductLinks>}
	 */
	public readonly links: ProductLinks = {
		collection: [ { href: '' } ],
		self: [ { href: '' } ],
	};
}

export interface IProductCommon extends AbstractProduct {}
