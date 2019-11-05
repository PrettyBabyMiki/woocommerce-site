/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, CheckboxControl } from 'newspack-components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import { recordEvent } from 'lib/tracks';
import { without, get } from 'lodash';

/**
 * Internal depdencies
 */
import { getCountryCode } from 'dashboard/utils';
import { H, Card, Form } from '@woocommerce/components';
import { getCurrencyData } from '@woocommerce/currency';
import withSelect from 'wc-api/with-select';
import {
	StoreAddress,
	validateStoreAddress,
} from '../../components/settings/general/store-address';
import UsageModal from './usage-modal';
import { getSetting } from '@woocommerce/wc-admin-settings';

class StoreDetails extends Component {
	constructor( props ) {
		super( ...arguments );
		const settings = get( props, 'settings', false );
		const profileItems = get( props, 'profileItems', {} );

		this.state = {
			showUsageModal: false,
		};

		this.initialValues = {
			addressLine1: settings.woocommerce_store_address || '',
			addressLine2: settings.woocommerce_store_address_2 || '',
			city: settings.woocommerce_store_city || '',
			countryState: settings.woocommerce_default_country || '',
			postCode: settings.woocommerce_store_postcode || '',
			isClient: profileItems.setup_client || false,
		};

		this.onContinue = this.onContinue.bind( this );
		this.onSubmit = this.onSubmit.bind( this );
	}

	componentWillUnmount() {
		apiFetch( { path: '/wc-admin/v1/onboarding/tasks/create_store_pages', method: 'POST' } );
	}

	deriveCurrencySettings( countryState ) {
		if ( ! countryState ) {
			return null;
		}

		let region = getCountryCode( countryState );
		const euCountries = without(
			getSetting( 'onboarding', { euCountries: [] } ).euCountries,
			'GB'
		);
		if ( euCountries.includes( region ) ) {
			region = 'EU';
		}

		const currencyData = getCurrencyData();
		return currencyData[ region ] || currencyData.US;
	}

	onSubmit( values ) {
		const { profileItems } = this.props;

		if ( 'already-installed' === profileItems.plugins ) {
			this.setState( { showUsageModal: true } );
			return;
		}

		this.onContinue( values );
	}

	async onContinue( values ) {
		const {
			createNotice,
			goToNextStep,
			isSettingsError,
			updateSettings,
			updateProfileItems,
			isProfileItemsError,
		} = this.props;

		const currencySettings = this.deriveCurrencySettings( values.countryState );

		recordEvent( 'storeprofiler_store_details_continue', {
			store_country: getCountryCode( values.countryState ),
			derived_currency: currencySettings.code,
			setup_client: values.isClient,
		} );

		await updateSettings( {
			general: {
				woocommerce_store_address: values.addressLine1,
				woocommerce_store_address_2: values.addressLine2,
				woocommerce_default_country: values.countryState,
				woocommerce_store_city: values.city,
				woocommerce_store_postcode: values.postCode,
				woocommerce_currency: currencySettings.code,
				woocommerce_currency_pos: currencySettings.position,
				woocommerce_price_thousand_sep: currencySettings.grouping,
				woocommerce_price_decimal_sep: currencySettings.decimal,
				woocommerce_price_num_decimals: currencySettings.precision,
			},
		} );

		await updateProfileItems( { setup_client: values.isClient } );

		if ( ! isSettingsError && ! isProfileItemsError ) {
			goToNextStep();
		} else {
			createNotice(
				'error',
				__( 'There was a problem saving your store details.', 'woocommerce-admin' )
			);
		}
	}

	render() {
		const { showUsageModal } = this.state;

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Where is your store based?', 'woocommerce-admin' ) }
				</H>
				<H className="woocommerce-profile-wizard__header-subtitle">
					{ __(
						'This will help us configure your store and get you started quickly',
						'woocommerce-admin'
					) }
				</H>

				<Card>
					<Form
						initialValues={ this.initialValues }
						onSubmitCallback={ this.onSubmit }
						validate={ validateStoreAddress }
					>
						{ ( { getInputProps, handleSubmit, values, isValidForm, setValue } ) => (
							<Fragment>
								{ showUsageModal && (
									<UsageModal
										onContinue={ () => this.onContinue( values ) }
										onClose={ () => this.setState( { showUsageModal: false } ) }
									/>
								) }
								<StoreAddress getInputProps={ getInputProps } setValue={ setValue } />
								<CheckboxControl
									label={ __( "I'm setting up a store for a client", 'woocommerce-admin' ) }
									{ ...getInputProps( 'isClient' ) }
								/>

								<Button isPrimary onClick={ handleSubmit } disabled={ ! isValidForm }>
									{ __( 'Continue', 'woocommerce-admin' ) }
								</Button>
							</Fragment>
						) }
					</Form>
				</Card>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const {
			getSettings,
			getSettingsError,
			isGetSettingsRequesting,
			getProfileItemsError,
			getProfileItems,
		} = select( 'wc-api' );

		const settings = getSettings( 'general' );
		const isSettingsError = Boolean( getSettingsError( 'general' ) );
		const isSettingsRequesting = isGetSettingsRequesting( 'general' );

		const profileItems = getProfileItems();
		const isProfileItemsError = Boolean( getProfileItemsError() );

		return {
			getSettings,
			isProfileItemsError,
			profileItems,
			isSettingsError,
			isSettingsRequesting,
			settings,
		};
	} ),
	withDispatch( dispatch => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateSettings, updateProfileItems } = dispatch( 'wc-api' );

		return {
			createNotice,
			updateSettings,
			updateProfileItems,
		};
	} )
)( StoreDetails );
