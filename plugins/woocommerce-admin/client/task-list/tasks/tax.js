/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, __experimentalText as Text } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { difference, filter } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { withDispatch, withSelect } from '@wordpress/data';
import { Card, H, Link, Stepper, Plugins } from '@woocommerce/components';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import {
	getAdminLink,
	getSetting,
	setSetting,
} from '@woocommerce/wc-admin-settings';
import {
	SETTINGS_STORE_NAME,
	PLUGINS_STORE_NAME,
	OPTIONS_STORE_NAME,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import Connect from '../../dashboard/components/connect';
import { createNoticesFromResponse } from '../../lib/notices';
import { getCountryCode } from '../../dashboard/utils';
import StoreLocation from './steps/location';
import { recordEvent, queueRecordEvent } from '../../lib/tracks';

class Tax extends Component {
	constructor( props ) {
		super( props );
		const { hasCompleteAddress, pluginsToActivate } = props;

		this.initialState = {
			isPending: false,
			stepIndex: hasCompleteAddress ? 1 : 0,
			// Cache the value of pluginsToActivate so that we can
			// show/hide tasks based on it, but not have them update mid task.
			cachedPluginsToActivate: pluginsToActivate,
		};
		this.state = this.initialState;

		this.completeStep = this.completeStep.bind( this );
	}

	componentDidMount() {
		this.reset();
	}

	reset() {
		this.setState( this.initialState );
	}

	shouldShowSuccessScreen() {
		const {
			isJetpackConnected,
			hasCompleteAddress,
			pluginsToActivate,
		} = this.props;

		return (
			hasCompleteAddress &&
			! pluginsToActivate.length &&
			isJetpackConnected &&
			this.isTaxJarSupported()
		);
	}

	isTaxJarSupported() {
		const { countryCode } = this.props;
		const {
			automatedTaxSupportedCountries = [],
			taxJarActivated,
		} = getSetting( 'onboarding', {} );

		return (
			! taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
			automatedTaxSupportedCountries.includes( countryCode )
		);
	}

	completeStep() {
		const { stepIndex } = this.state;
		const steps = this.getSteps();
		const nextStep = steps[ stepIndex + 1 ];

		if ( nextStep ) {
			this.setState( { stepIndex: stepIndex + 1 } );
		}
	}

	async manuallyConfigureTaxRates() {
		const {
			generalSettings,
			updateAndPersistSettingsForGroup,
		} = this.props;

		if ( generalSettings.woocommerce_calc_taxes !== 'yes' ) {
			this.setState( { isPending: true } );
			updateAndPersistSettingsForGroup( 'general', {
				general: {
					...generalSettings,
					woocommerce_calc_taxes: 'yes',
				},
			} )
				.then( () => this.redirectToTaxSettings() )
				.catch( ( error ) => createNoticesFromResponse( error ) );
		} else {
			this.redirectToTaxSettings();
		}
	}

	updateAutomatedTax( isEnabling ) {
		const {
			createNotice,
			updateAndPersistSettingsForGroup,
			generalSettings,
			taxSettings,
		} = this.props;

		Promise.all( [
			updateAndPersistSettingsForGroup( 'tax', {
				tax: {
					...taxSettings,
					wc_connect_taxes_enabled: isEnabling ? 'yes' : 'no',
				},
			} ),
			updateAndPersistSettingsForGroup( 'general', {
				general: {
					...generalSettings,
					woocommerce_calc_taxes: 'yes',
				},
			} ),
		] )
			.then( () => {
				// @todo This is a workaround to force the task to mark as complete.
				// This should probably be updated to use wc-api so we can fetch tax rates.
				setSetting( 'onboarding', {
					...getSetting( 'onboarding', {} ),
					isTaxComplete: true,
				} );
				if ( isEnabling ) {
					createNotice(
						'success',
						__(
							"You're awesome! One less item on your to-do list ✅",
							'woocommerce-admin'
						)
					);
					getHistory().push( getNewPath( {}, '/', {} ) );
				} else {
					this.redirectToTaxSettings();
				}
			} )
			.catch( () => {
				createNotice(
					'error',
					__(
						'There was a problem updating your tax settings.',
						'woocommerce-admin'
					)
				);
			} );
	}

	redirectToTaxSettings() {
		window.location = getAdminLink(
			'admin.php?page=wc-settings&tab=tax&section=standard&wc_onboarding_active_task=tax'
		);
	}

	getSteps() {
		const {
			generalSettings,
			isJetpackConnected,
			isPending,
			tosAccepted,
			updateOptions,
		} = this.props;
		const { cachedPluginsToActivate } = this.state;

		const steps = [
			{
				key: 'store_location',
				label: __( 'Set store location', 'woocommerce-admin' ),
				description: __(
					'The address from which your business operates',
					'woocommerce-admin'
				),
				content: (
					<StoreLocation
						{ ...this.props }
						onComplete={ ( values ) => {
							const country = getCountryCode(
								values.countryState
							);
							recordEvent( 'tasklist_tax_set_location', {
								country,
							} );
							this.completeStep();
						} }
						isSettingsRequesting={ false }
						settings={ generalSettings }
					/>
				),
				visible: true,
			},
			{
				key: 'plugins',
				label: __(
					'Install Jetpack and WooCommerce Services',
					'woocommerce-admin'
				),
				description: __(
					'Jetpack and WooCommerce Services allow you to automate sales tax calculations',
					'woocommerce-admin'
				),
				content: (
					<Fragment>
						<Plugins
							onComplete={ () => {
								recordEvent(
									'tasklist_tax_install_extensions',
									{
										install_extensions: true,
									}
								);
								updateOptions( {
									woocommerce_setup_jetpack_opted_in: true,
								} );
								this.completeStep();
							} }
							onSkip={ () => {
								queueRecordEvent(
									'tasklist_tax_install_extensions',
									{
										install_extensions: false,
									}
								);
								this.manuallyConfigureTaxRates();
							} }
							skipText={ __(
								'Set up tax rates manually',
								'woocommerce-admin'
							) }
						/>
						{ ! tosAccepted && (
							<Text
								variant="caption"
								className="woocommerce-task__caption"
							>
								{ interpolateComponents( {
									mixedString: __(
										'By installing Jetpack and WooCommerce Services you agree to the {{link}}Terms of Service{{/link}}.',
										'woocommerce-admin'
									),
									components: {
										link: (
											<Link
												href={
													'https://wordpress.com/tos/'
												}
												target="_blank"
												type="external"
											/>
										),
									},
								} ) }
							</Text>
						) }
					</Fragment>
				),
				visible:
					( cachedPluginsToActivate.length || ! tosAccepted ) &&
					this.isTaxJarSupported(),
			},
			{
				key: 'connect',
				label: __( 'Connect your store', 'woocommerce-admin' ),
				description: __(
					'Connect your store to WordPress.com to enable automated sales tax calculations',
					'woocommerce-admin'
				),
				content: (
					<Connect
						{ ...this.props }
						onConnect={ () => {
							recordEvent( 'tasklist_tax_connect_store', {
								connect: true,
							} );
						} }
						onSkip={ () => {
							queueRecordEvent( 'tasklist_tax_connect_store', {
								connect: false,
							} );
							this.manuallyConfigureTaxRates();
						} }
						skipText={ __(
							'Set up tax rates manually',
							'woocommerce-admin'
						) }
					/>
				),
				visible: ! isJetpackConnected && this.isTaxJarSupported(),
			},
			{
				key: 'manual_configuration',
				label: __( 'Configure tax rates', 'woocommerce-admin' ),
				description: __(
					'Head over to the tax rate settings screen to configure your tax rates',
					'woocommerce-admin'
				),
				content: (
					<Fragment>
						<Button
							disabled={ isPending }
							isPrimary
							isBusy={ isPending }
							onClick={ () => {
								recordEvent( 'tasklist_tax_config_rates' );
								this.manuallyConfigureTaxRates();
							} }
						>
							{ __( 'Configure', 'woocommerce-admin' ) }
						</Button>
						<p>
							{ generalSettings.woocommerce_calc_taxes !==
								'yes' &&
								interpolateComponents( {
									mixedString: __(
										/*eslint-disable max-len*/
										'By clicking "Configure" you\'re enabling tax rates and calculations. More info {{link}}here{{/link}}.',
										/*eslint-enable max-len*/
										'woocommerce-admin'
									),
									components: {
										link: (
											<Link
												href="https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce/#section-1"
												target="_blank"
												type="external"
											/>
										),
									},
								} ) }
						</p>
					</Fragment>
				),
				visible: ! this.isTaxJarSupported(),
			},
		];

		return filter( steps, ( step ) => step.visible );
	}

	render() {
		const { stepIndex } = this.state;
		const { isPending, isResolving } = this.props;
		const step = this.getSteps()[ stepIndex ];

		return (
			<div className="woocommerce-task-tax">
				<Card className="is-narrow">
					{ this.shouldShowSuccessScreen() ? (
						<div className="woocommerce-task-tax__success">
							<span
								className="woocommerce-task-tax__success-icon"
								role="img"
								aria-labelledby="woocommerce-task-tax__success-message"
							>
								🎊
							</span>
							<H id="woocommerce-task-tax__success-message">
								{ __( 'Good news!', 'woocommerce-admin' ) }
							</H>
							<p>
								{ interpolateComponents( {
									mixedString: __(
										'{{strong}}Jetpack{{/strong}} and {{strong}}WooCommerce Services{{/strong}} ' +
											'can automate your sales tax calculations for you.',
										'woocommerce-admin'
									),
									components: {
										strong: <strong />,
									},
								} ) }
							</p>
							<Button
								disabled={ isPending }
								isPrimary
								isBusy={ isPending }
								onClick={ () => {
									recordEvent(
										'tasklist_tax_setup_automated_proceed',
										{
											setup_automatically: true,
										}
									);
									this.updateAutomatedTax( true );
								} }
							>
								{ __( 'Yes please', 'woocommerce-admin' ) }
							</Button>
							<Button
								disabled={ isPending }
								isBusy={ isPending }
								onClick={ () => {
									recordEvent(
										'tasklist_tax_setup_automated_proceed',
										{
											setup_automatically: false,
										}
									);
									this.updateAutomatedTax( false );
								} }
							>
								{ __(
									"No thanks, I'll configure taxes manually",
									'woocommerce-admin'
								) }
							</Button>
						</div>
					) : (
						<Stepper
							isPending={ isPending || isResolving }
							isVertical={ true }
							currentStep={ step.key }
							steps={ this.getSteps() }
						/>
					) }
				</Card>
			</div>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getSettings, isUpdateSettingsRequesting } = select(
			SETTINGS_STORE_NAME
		);
		const { getOption } = select( OPTIONS_STORE_NAME );
		const {
			getActivePlugins,
			isJetpackConnected,
			isPluginsRequesting,
		} = select( PLUGINS_STORE_NAME );

		const { general: generalSettings = {} } = getSettings( 'general' );
		const countryCode = getCountryCode(
			generalSettings.woocommerce_default_country
		);
		const {
			woocommerce_store_address: storeAddress,
			woocommerce_default_country: defaultCountry,
			woocommerce_store_postcode: storePostCode,
		} = generalSettings;
		const hasCompleteAddress = Boolean(
			storeAddress && defaultCountry && storePostCode
		);

		const { tax: taxSettings = {} } = getSettings( 'tax' );
		const activePlugins = getActivePlugins();
		const pluginsToActivate = difference(
			[ 'jetpack', 'woocommerce-services' ],
			activePlugins
		);
		const connectOptions = getOption( 'wc_connect_options' ) || {};
		const tosAccepted =
			connectOptions.tos_accepted ||
			getOption( 'woocommerce_setup_jetpack_opted_in' );

		const isPending =
			isUpdateSettingsRequesting( 'tax' ) ||
			isUpdateSettingsRequesting( 'general' );
		const isResolving = isPluginsRequesting( 'getJetpackConnectUrl' );

		return {
			countryCode,
			generalSettings,
			hasCompleteAddress,
			isJetpackConnected: isJetpackConnected(),
			isPending,
			isResolving,
			pluginsToActivate,
			taxSettings,
			tosAccepted,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const { updateAndPersistSettingsForGroup } = dispatch(
			SETTINGS_STORE_NAME
		);

		return {
			createNotice,
			updateAndPersistSettingsForGroup,
			updateOptions,
		};
	} )
)( Tax );
