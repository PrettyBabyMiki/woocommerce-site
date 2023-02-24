/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	receiveRegisteredChannelsSuccess,
	receiveRegisteredChannelsError,
	receiveRecommendedChannelsSuccess,
	receiveRecommendedChannelsError,
	receiveCampaignsSuccess,
	receiveCampaignsError,
} from './actions';
import { awaitResponseJson } from './controls';
import {
	RegisteredChannel,
	RecommendedChannel,
	Campaign,
	ApiFetchError,
} from './types';
import { API_NAMESPACE } from './constants';
import { isApiFetchError } from './guards';

export function* getRegisteredChannels() {
	try {
		const data: RegisteredChannel[] = yield apiFetch( {
			path: `${ API_NAMESPACE }/channels`,
		} );

		yield receiveRegisteredChannelsSuccess( data );
	} catch ( error ) {
		if ( isApiFetchError( error ) ) {
			yield receiveRegisteredChannelsError( error );
		}

		throw error;
	}
}

export function* getRecommendedChannels() {
	try {
		const data: RecommendedChannel[] = yield apiFetch( {
			path: `${ API_NAMESPACE }/recommendations?category=channels`,
		} );

		yield receiveRecommendedChannelsSuccess( data );
	} catch ( error ) {
		if ( isApiFetchError( error ) ) {
			yield receiveRecommendedChannelsError( error );
		}

		throw error;
	}
}

export function* getCampaigns( page: number, perPage: number ) {
	try {
		const response: Response = yield apiFetch( {
			path: `${ API_NAMESPACE }/campaigns?page=${ page }&per_page=${ perPage }`,
			parse: false,
		} );

		const total = parseInt(
			response.headers.get( 'x-wp-total' ) || '0',
			10
		);
		const payload: Campaign[] = yield awaitResponseJson( response );

		yield receiveCampaignsSuccess( {
			payload,
			error: false,
			meta: {
				page,
				perPage,
				total,
			},
		} );
	} catch ( error ) {
		if ( error instanceof Response ) {
			const total =
				parseInt( error.headers.get( 'x-wp-total' ) || '0', 10 ) ||
				undefined;

			const payload: ApiFetchError = yield awaitResponseJson( error );

			yield receiveCampaignsError( {
				payload,
				error: true,
				meta: {
					page,
					perPage,
					total,
				},
			} );
		}

		throw error;
	}
}
