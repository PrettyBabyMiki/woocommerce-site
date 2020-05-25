/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { without } from 'lodash';

/**
 * Internal dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Gets the country code from a country:state value string.
 *
 * @param {string} countryState Country state string, e.g. US:GA.
 * @return {string} Country string.
 */

export function getCountryCode( countryState ) {
	if ( ! countryState ) {
		return null;
	}

	return countryState.split( ':' )[ 0 ];
}

export function getCurrencyRegion( countryState ) {
	let region = getCountryCode( countryState );
	const euCountries = without(
		getSetting( 'onboarding', { euCountries: [] } ).euCountries,
		'GB'
	);
	if ( euCountries.includes( region ) ) {
		region = 'EU';
	}

	return region;
}

/**
 * Gets the product IDs for items based on the product types and theme selected in the onboarding profiler.
 *
 * @param {Object} profileItems Onboarding profile.
 * @param {boolean} includeInstalledItems Include installed items in returned product IDs.
 * @param {Array} installedPlugins Installed plugins.
 * @return {Array} Product Ids.
 */
export function getProductIdsForCart(
	profileItems,
	includeInstalledItems = false,
	installedPlugins
) {
	const onboarding = getSetting( 'onboarding', {} );

	// The population of onboarding.productTypes only happens if the task list should be shown
	// so bail early if it isn't present.
	if ( ! onboarding.productTypes ) {
		return productIds;
	}

	const productIds = [];
	const productTypes = profileItems.product_types || [];

	productTypes.forEach( ( productType ) => {
		if (
			onboarding.productTypes[ productType ] &&
			onboarding.productTypes[ productType ].product &&
			( includeInstalledItems ||
				! installedPlugins.includes(
					onboarding.productTypes[ productType ].slug
				) )
		) {
			productIds.push( onboarding.productTypes[ productType ].product );
		}
	} );

	const theme = onboarding.themes.find(
		( themeData ) => themeData.slug === profileItems.theme
	);

	if (
		theme &&
		theme.id &&
		getPriceValue( theme.price ) > 0 &&
		( includeInstalledItems || ! theme.is_installed )
	) {
		productIds.push( theme.id );
	}

	return productIds;
}

/**
 * Get the value of a price from a string, removing any non-numeric characters.
 *
 * @param {string} string Price string.
 * @return {number} Number value.
 */
export function getPriceValue( string ) {
	return Number( decodeEntities( string ).replace( /[^0-9.-]+/g, '' ) );
}

/**
 * Returns if the onboarding feature of WooCommerce Admin should be enabled.
 *
 * While we preform an a/b test of onboarding, the feature will be enabled within the plugin build,
 * but only if the user recieved the test/opted in.
 *
 * @return {boolean} True if the onboarding is enabled.
 */
export function isOnboardingEnabled() {
	if ( ! window.wcAdminFeatures.onboarding ) {
		return false;
	}

	return getSetting( 'onboardingEnabled', false );
}
