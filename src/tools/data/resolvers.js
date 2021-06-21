/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './constants';
import { setCronJobs } from './actions';

export function* getCronJobs() {
	const path = `${ API_NAMESPACE }/tools/get-cron-list/v1`;

	try {
		const response = yield apiFetch( {
			path,
			method: 'GET',
		} );
		yield setCronJobs( response );
	} catch ( error ) {
		throw new Error( error );
	}
}
