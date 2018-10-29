/** @format */

/**
 * External dependencies
 */
import { setLocaleData } from '@wordpress/i18n';

// Set up `wp.*` aliases.  Doing this because any tests importing wp stuff will
// likely run into this.
global.wp = {
	shortcode: {
		next() {},
		regexp: jest.fn().mockReturnValue( new RegExp() ),
	},
};

global.wc = {};

Object.defineProperty( global.wp, 'element', {
	get: () => require( '@wordpress/element' ),
} );

Object.defineProperty( global.wp, 'date', {
	get: () => require( '@wordpress/date' ),
} );

Object.defineProperty( global.wc, 'components', {
	get: () => require( '@woocommerce/components' ),
} );

global.wcSettings = {
	adminUrl: 'https://vagrant.local/wp/wp-admin/',
	locale: 'en-US',
	currency: { code: 'USD', precision: 2, symbol: '&#36;' },
	date: {
		dow: 0,
	},
	orderStatuses: {
		'wc-pending': 'Pending payment',
		'wc-processing': 'Processing',
		'wc-on-hold': 'On hold',
		'wc-completed': 'Completed',
		'wc-cancelled': 'Cancelled',
		'wc-refunded': 'Refunded',
		'wc-failed': 'Failed',
	},
};

setLocaleData( { '': { domain: 'wc-admin', lang: 'en_US' } }, 'wc-admin' );
