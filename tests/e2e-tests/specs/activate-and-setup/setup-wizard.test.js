/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { completeOldSetupWizard, completeOnboardingWizard } from '../../utils/components';
import {
	permalinkSettingsPageSaveChanges,
	setCheckbox,
	settingsPageSaveChanges,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset, verifyValueOfInputField
} from '../../utils';

const config = require( 'config' );

describe( 'Store owner can login and make sure WooCommerce is activated', () => {

	it( 'can login', async () => {
		await StoreOwnerFlow.login();
	} );

	it( 'can make sure WooCommerce is activated. If not, activate it', async () => {
		const slug = 'woocommerce';
		await StoreOwnerFlow.openPlugins();
		const disableLink = await page.$( `tr[data-slug="${ slug }"] .deactivate a` );
		if ( disableLink ) {
			return;
		}
		await page.click( `tr[data-slug="${ slug }"] .activate a` );
		await page.waitForSelector( `tr[data-slug="${ slug }"] .deactivate a` );
	} );

} );

describe( 'Store owner can go through store Setup Wizard', () => {

	it( 'can start Setup Wizard when visiting the site for the first time. Skip all other times.', async () => {
		// Check if Setup Wizard Notice is visible on the screen.
		// If yes - proceed with Setup Wizard, if not - skip Setup Wizard (already been completed).
		const setupWizardNotice = await Promise.race( [
			new Promise( resolve => setTimeout( () => resolve(), 1000 ) ), // resolves without value after 1s
			page.waitForSelector('.updated.woocommerce-message.wc-connect', { visible: true } )
		] );
		if ( setupWizardNotice ) {
			await StoreOwnerFlow.runSetupWizard();
			await completeOnboardingWizard();
		}
	} );
} );

describe( 'Store owner can go through setup Task List', () => {
	it( 'can setup shipping', async () => {
		// Query for all tasks on the list
		const taskListItems = await page.$$( '.woocommerce-list__item-title' );
		expect( taskListItems ).toHaveLength( 5 );

		await Promise.all( [
			// Click on "Set up shipping" task to move to the next step
			taskListItems[2].click(),

			// Wait for shipping setup section to load
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );

		// Query for store location fields
		const storeLocationFields = await page.$$( '.components-text-control__input' );
		expect( storeLocationFields ).toHaveLength( 4 );

		const countryAndStateField = '.woocommerce-select-control__control-input';

		// Verify that store location is set
		await Promise.all( [
			expect( page ).toMatchElement( storeLocationFields[0], { text: config.get( 'addresses.admin.store.addressfirstline' ) } ),
			expect( page ).toMatchElement( storeLocationFields[1], { text: config.get( 'addresses.admin.store.addresssecondline' ) } ),
			expect( page ).toMatchElement( countryAndStateField, { text: config.get( 'addresses.admin.store.countryandstate' ) } ),
			expect( page ).toMatchElement( storeLocationFields[2], { text: config.get( 'addresses.admin.store.city' ) } ),
			expect( page ).toMatchElement( storeLocationFields[3], { text: config.get( 'addresses.admin.store.postcode' ) } ),
		] );

		// Wait for "Continue" button to become active
		await page.waitForSelector( 'button.is-primary:not(:disabled)' );
		// Click on "Continue" button to move to the shipping cost section
		await page.click( 'button.is-primary' );

		// Wait for "Proceed" button to become active
		await page.waitForSelector( 'button.is-primary:not(:disabled)' );

		// Click on "Proceed" button to save shipping settings
		await page.click( 'button.is-primary' );
		await page.waitFor( 3000 );
	} );
} );

describe( 'Store owner can finish initial store setup', () => {

	it( 'can enable tax rates and calculations', async () => {
		// Go to general settings page
		await StoreOwnerFlow.openSettings( 'general' );

		// Make sure the general tab is active
		await expect( page ).toMatchElement( 'a.nav-tab-active', { text: 'General' } );

		// Enable tax rates and calculations
		await setCheckbox( '#woocommerce_calc_taxes' );

		await settingsPageSaveChanges();

		// Verify that settings have been saved
		await Promise.all( [
			expect( page ).toMatchElement( '#message', { text: 'Your settings have been saved.' } ),
			verifyCheckboxIsSet( '#woocommerce_calc_taxes' ),
		] );
	} );

	it( 'can configure permalink settings', async () => {
		// Go to Permalink Settings page
		await StoreOwnerFlow.openPermalinkSettings();

		// Select "Post name" option in common settings section
		await page.click( 'input[value="/%postname%/"]', { text: ' Post name' } );

		// Select "Custom base" in product permalinks section
		await page.click( '#woocommerce_custom_selection' );

		// Fill custom base slug to use
		await expect( page ).toFill( '#woocommerce_permalink_structure', '/product/' );

		await permalinkSettingsPageSaveChanges();

		// Verify that settings have been saved
		await Promise.all( [
			expect( page ).toMatchElement( '#setting-error-settings_updated', { text: 'Permalink structure updated.' } ),
			verifyValueOfInputField( '#permalink_structure', '/%postname%/' ),
			verifyValueOfInputField( '#woocommerce_permalink_structure', '/product/' ),
		] );
	} );
} );
