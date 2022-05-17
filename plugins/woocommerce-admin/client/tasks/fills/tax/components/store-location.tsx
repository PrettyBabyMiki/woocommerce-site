/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SETTINGS_STORE_NAME, WCDataSelector } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getCountryCode } from '~/dashboard/utils';
import { hasCompleteAddress, SettingsSelector } from '../utils';
import { default as StoreLocationForm } from '~/tasks/fills/steps/location';

export const StoreLocation: React.FC< {
	nextStep: () => void;
} > = ( { nextStep } ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const { updateAndPersistSettingsForGroup } = useDispatch(
		SETTINGS_STORE_NAME
	);
	const { generalSettings, isResolving } = useSelect( ( select ) => {
		const { getSettings, hasFinishedResolution } = select(
			SETTINGS_STORE_NAME
		) as SettingsSelector;

		return {
			generalSettings: getSettings( 'general' )?.general,
			isResolving: ! hasFinishedResolution( 'getSettings', [
				'general',
			] ),
		};
	} );

	useEffect( () => {
		if ( isResolving || ! hasCompleteAddress( generalSettings ) ) {
			return;
		}
		nextStep();
	}, [ isResolving ] );

	if ( isResolving ) {
		return null;
	}

	return (
		<StoreLocationForm
			onComplete={ ( values: { [ key: string ]: string } ) => {
				const country = getCountryCode( values.countryState );
				recordEvent( 'tasklist_tax_set_location', {
					country,
				} );
				nextStep();
			} }
			isSettingsRequesting={ false }
			settings={ generalSettings }
			updateAndPersistSettingsForGroup={
				updateAndPersistSettingsForGroup
			}
			createNotice={ createNotice }
			isSettingsError={ false }
		/>
	);
};
