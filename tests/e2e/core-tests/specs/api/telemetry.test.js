/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const { HTTPClientFactory, Coupon } = require( '@woocommerce/api' );

/**
 * External dependencies
 */
const config = require( 'config' );
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

/**
 * Create the default coupon and tests interactions with it via the API.
 */
const runTelemetryAPITest = () => {
	describe( 'REST API > Telemetry', () => {
		let client;

		beforeAll(async () => {
			const admin = config.get( 'users.admin' );
			const url = config.get( 'url' );

			client = HTTPClientFactory.build( url )
				.withBasicAuth( admin.username, admin.password )
				.withIndexPermalinks()
				.create();
		} );

		it( 'errors for missing fields', async () => {
			await client
				.post( `/wc/v3/telemetry` )
				.catch( err => {
					expect( err.statusCode ).toBe( 400 );
				} );
		} );

		it( 'returns 200 with correct fields', async () => {
			const response = await client
				.post( `/wc/v3/telemetry`, {
					platform: 'ios',
					version: '1.0',
				})

			expect( response.statusCode ).toBe( 200 );
		} );
	} );
};

module.exports = runTelemetryAPITest;
