/** @format */

/**
 * External dependencies
 */
import history from 'lib/history';
import { parse, stringify } from 'qs';

/**
 * Get the current path from history.
 *
 * @return {String}  Current path.
 */
export function getPath() {
	return history.location.pathname;
}

/**
 * Get the current query string, parsed into an object, from history.
 *
 * @return {Object}  Current query object, defaults to empty object.
 */
export function getQuery() {
	const search = history.location.search;
	if ( search.length ) {
		return parse( search.substring( 1 ) ) || {};
	}
	return {};
}

/**
 * Returns a string with the site's wp-admin URL appended. JS version of `admin_url`.
 *
 * @param {String} path Relative path.
 * @return {String} Full admin URL.
 */
export const getAdminLink = path => {
	return wcSettings.adminUrl + path;
};

/**
 * Converts a query object to a query string.
 *
 * @param {Object} query parameters to be converted.
 * @return {String} Query string.
 */
export const stringifyQuery = query => {
	return query ? '?' + stringify( query ) : '';
};

/**
 * Return a URL with set query parameters.
 *
 * @param {Object} query object of params to be updated.
 * @param {String} path Relative path (defaults to current path).
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 * @return {String}  Updated URL merging query params into existing params.
 */
export const getNewPath = ( query, path = getPath(), currentQuery = getQuery() ) => {
	const queryString = stringifyQuery( { ...currentQuery, ...query } );
	return `${ path }${ queryString }`;
};

/**
 * Updates the query parameters of the current page.
 *
 * @param {Object} query object of params to be updated.
 * @param {String} path Relative path (defaults to current path).
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 */
export const updateQueryString = ( query, path = getPath(), currentQuery = getQuery() ) => {
	const newPath = getNewPath( query, path, currentQuery );
	history.push( newPath );
};
