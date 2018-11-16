/** @format */

/**
 * External dependencies
 */
import { get } from 'lodash';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import { getJsonString } from 'store/utils';

/**
 * Returns report stats details for a specific endpoint query.
 *
 * @param  {Object} state     Current state
 * @param  {String} endpoint  Stats endpoint
 * @param  {Object} query     Report query parameters
 * @return {Object}           Report details
 */
function getReportStats( state, endpoint, query = {} ) {
	const queries = get( state, [ 'reports', 'stats', endpoint ], {} );
	return queries[ getJsonString( query ) ] || null;
}

export default {
	getReportStats,

	/**
	 * Returns true if a stats query is pending.
	 *
	 * @param  {Object} state  Current state
	 * @return {Boolean}        True if the `getReportRevenueStats` request is pending, false otherwise
	 */
	isReportStatsRequesting( state, ...args ) {
		return select( 'core/data' ).isResolving( 'wc-admin', 'getReportStats', args );
	},

	/**
	 * Returns true if a report stat request has returned an error.
	 *
	 * @param  {Object} state     Current state
	 * @param  {String} endpoint  Stats endpoint
	 * @param  {Object} query     Report query parameters
	 * @return {Boolean}          True if the `getReportStats` request has failed, false otherwise
	 */
	isReportStatsError( state, endpoint, query ) {
		return ERROR === getReportStats( state, endpoint, query );
	},
};
