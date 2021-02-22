import {
	AbstractProduct,
	IProductCommon,
	IProductExternal,
	IProductSalesTax,
	IProductUpSells,
	ProductSearchParams,
} from './abstract';
import {
	ProductCommonUpdateParams,
	ProductExternalUpdateParams,
	ProductSalesTaxUpdateParams,
	ProductUpSellUpdateParams,
	Taxability,
} from './shared';
import { HTTPClient } from '../../http';
import { externalProductRESTRepository } from '../../repositories';
import {
	CreatesModels,
	DeletesModels,
	ListsModels,
	ModelRepositoryParams,
	ReadsModels,
	UpdatesModels,
} from '../../framework';

/**
 * The parameters that external products can update.
 */
type ExternalProductUpdateParams = ProductCommonUpdateParams
	& ProductExternalUpdateParams
	& ProductSalesTaxUpdateParams
	& ProductUpSellUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export type ExternalProductRepositoryParams =
	ModelRepositoryParams< ExternalProduct, never, ProductSearchParams, ExternalProductUpdateParams >;

/**
 * An interface for listing simple products using the repository.
 *
 * @typedef ListsExternalProducts
 * @alias ListsModels.<ExternalProduct>
 */
export type ListsExternalProducts = ListsModels< ExternalProductRepositoryParams >;

/**
 * An interface for creating simple products using the repository.
 *
 * @typedef CreatesExternalProducts
 * @alias CreatesModels.<ExternalProduct>
 */
export type CreatesExternalProducts = CreatesModels< ExternalProductRepositoryParams >;

/**
 * An interface for reading simple products using the repository.
 *
 * @typedef ReadsExternalProducts
 * @alias ReadsModels.<ExternalProduct>
 */
export type ReadsExternalProducts = ReadsModels< ExternalProductRepositoryParams >;

/**
 * An interface for updating simple products using the repository.
 *
 * @typedef UpdatesExternalProducts
 * @alias UpdatesModels.<ExternalProduct>
 */
export type UpdatesExternalProducts = UpdatesModels< ExternalProductRepositoryParams >;

/**
 * An interface for deleting simple products using the repository.
 *
 * @typedef DeletesExternalProducts
 * @alias DeletesModels.<ExternalProduct>
 */
export type DeletesExternalProducts = DeletesModels< ExternalProductRepositoryParams >;

/**
 * The base for the simple product object.
 */
export class ExternalProduct extends AbstractProduct implements
	IProductCommon,
	IProductExternal,
	IProductSalesTax,
	IProductUpSells {
	/**
	 * @see ./abstracts/external.ts
	 */
	public readonly buttonText: string = ''
	public readonly externalUrl: string = ''

	/**
	 * @see ./abstracts/upsell.ts
	 */
	public readonly upSellIds: Array<number> = [];

	/**
	 * @see ./abstracts/sales-tax.ts
	 */
	public readonly taxStatus: Taxability = Taxability.ProductAndShipping;
	public readonly taxClass: string = '';

	/**
	 * Creates a new simple product instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties?: Partial< ExternalProduct > ) {
		super();
		Object.assign( this, properties );
	}

	/**
	 * Creates a model repository configured for communicating via the REST API.
	 *
	 * @param {HTTPClient} httpClient The client for communicating via HTTP.
	 */
	public static restRepository( httpClient: HTTPClient ): ReturnType< typeof externalProductRESTRepository > {
		return externalProductRESTRepository( httpClient );
	}
}
