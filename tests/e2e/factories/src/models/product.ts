import { Model } from './model';
import { DeepPartial } from 'fishery';

export class Product extends Model {
	public readonly Name: string = '';
	public readonly RegularPrice: string = '';

	public constructor( partial: DeepPartial<Product> = {} ) {
		super( partial );
		Object.assign( this, partial );
	}
}
