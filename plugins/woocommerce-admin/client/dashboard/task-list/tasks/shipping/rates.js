/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component, Fragment } from '@wordpress/element';
import { Button, FormToggle } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { Flag, Form, TextControlWithAffixes } from '@woocommerce/components';
import { getSetting, setSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { recordEvent } from 'lib/tracks';
import { CurrencyContext } from 'lib/currency-context';

class ShippingRates extends Component {
	constructor() {
		super( ...arguments );

		this.updateShippingZones = this.updateShippingZones.bind( this );
	}

	getShippingMethods( zone, type = null ) {
		// Sometimes the wc/v3/shipping/zones response does not include a methods attribute, return early if so.
		if ( ! zone || ! zone.methods || ! Array.isArray( zone.methods ) ) {
			return [];
		}

		if ( ! type ) {
			return zone.methods;
		}

		return zone.methods
			? zone.methods.filter( ( method ) => method.method_id === type )
			: [];
	}

	disableShippingMethods( zone, methods ) {
		if ( ! methods.length ) {
			return;
		}

		methods.forEach( ( method ) => {
			apiFetch( {
				method: 'POST',
				path: `/wc/v3/shipping/zones/${ zone.id }/methods/${ method.instance_id }`,
				data: {
					enabled: false,
				},
			} );
		} );
	}

	async updateShippingZones( values ) {
		const { createNotice, shippingZones } = this.props;

		let restOfTheWorld = false;
		let shippingCost = false;
		shippingZones.forEach( ( zone ) => {
			if ( zone.id === 0 ) {
				restOfTheWorld =
					zone.toggleable && values[ `${ zone.id }_enabled` ];
			} else {
				shippingCost =
					values[ `${ zone.id }_rate` ] !== '' &&
					parseFloat( values[ `${ zone.id }_rate` ] ) !==
						parseFloat( 0 );
			}

			const shippingMethods = this.getShippingMethods( zone );
			const methodType =
				parseFloat( values[ `${ zone.id }_rate` ] ) === parseFloat( 0 )
					? 'free_shipping'
					: 'flat_rate';
			const shippingMethod = this.getShippingMethods( zone, methodType )
				.length
				? this.getShippingMethods( zone, methodType )[ 0 ]
				: null;

			if ( zone.toggleable && ! values[ `${ zone.id }_enabled` ] ) {
				// Disable any shipping methods that exist if toggled off.
				this.disableShippingMethods( zone, shippingMethods );
				return;
			} else if ( shippingMethod ) {
				// Disable all methods except the one being updated.
				const methodsToDisable = shippingMethods.filter(
					( method ) =>
						method.instance_id !== shippingMethod.instance_id
				);
				this.disableShippingMethods( zone, methodsToDisable );
			}

			apiFetch( {
				method: 'POST',
				path: shippingMethod
					? // Update the first existing method if one exists, otherwise create a new one.
					  `/wc/v3/shipping/zones/${ zone.id }/methods/${ shippingMethod.instance_id }`
					: `/wc/v3/shipping/zones/${ zone.id }/methods`,
				data: {
					method_id: methodType,
					enabled: true,
					settings: { cost: values[ `${ zone.id }_rate` ] },
				},
			} );
		} );

		recordEvent( 'tasklist_shipping_set_costs', {
			shipping_cost: shippingCost,
			rest_world: restOfTheWorld,
		} );

		// @todo This is a workaround to force the task to mark as complete.
		// This should probably be updated to use wc-api so we can fetch shipping methods.
		setSetting( 'onboarding', {
			...getSetting( 'onboarding', {} ),
			shippingZonesCount: 1,
		} );

		createNotice(
			'success',
			__( 'Your shipping rates have been updated.', 'woocommerce-admin' )
		);

		this.props.onComplete();
	}

	renderInputPrefix() {
		const { symbolPosition, symbol } = this.context.getCurrency();
		if ( symbolPosition.indexOf( 'right' ) === 0 ) {
			return null;
		}
		return (
			<span className="woocommerce-shipping-rate__control-prefix">
				{ symbol }
			</span>
		);
	}

	renderInputSuffix( rate ) {
		const { symbolPosition, symbol } = this.context.getCurrency();
		if ( symbolPosition.indexOf( 'right' ) === 0 ) {
			return (
				<span className="woocommerce-shipping-rate__control-suffix">
					{ symbol }
				</span>
			);
		}

		return parseFloat( rate ) === parseFloat( 0 ) ? (
			<span className="woocommerce-shipping-rate__control-suffix">
				{ __( 'Free shipping', 'woocommerce-admin' ) }
			</span>
		) : null;
	}

	getFormattedRate( value ) {
		const { formatDecimalString } = this.context;
		const currencyString = formatDecimalString( value );
		if ( ! value.length || ! currencyString.length ) {
			return formatDecimalString( 0 );
		}

		return formatDecimalString( value );
	}

	getInitialValues() {
		const { formatDecimalString } = this.context;
		const values = {};

		this.props.shippingZones.forEach( ( zone ) => {
			const shippingMethods = this.getShippingMethods( zone );
			const rate =
				shippingMethods.length && shippingMethods[ 0 ].settings.cost
					? this.getFormattedRate(
							shippingMethods[ 0 ].settings.cost.value
					  )
					: formatDecimalString( 0 );
			values[ `${ zone.id }_rate` ] = rate;

			if ( shippingMethods.length && shippingMethods[ 0 ].enabled ) {
				values[ `${ zone.id }_enabled` ] = true;
			} else {
				values[ `${ zone.id }_enabled` ] = false;
			}
		} );

		return values;
	}

	validate( values ) {
		const errors = {};

		const rates = Object.keys( values ).filter( ( field ) =>
			field.endsWith( '_rate' )
		);

		rates.forEach( ( rate ) => {
			if ( values[ rate ] < 0 ) {
				errors[ rate ] = __(
					'Shipping rates can not be negative numbers.',
					'woocommerce-admin'
				);
			}
		} );

		return errors;
	}

	render() {
		const { buttonText, shippingZones } = this.props;

		if ( ! shippingZones.length ) {
			return null;
		}

		return (
			<Form
				initialValues={ this.getInitialValues() }
				onSubmitCallback={ this.updateShippingZones }
				validate={ this.validate }
			>
				{ ( {
					getInputProps,
					handleSubmit,
					setTouched,
					setValue,
					values,
				} ) => {
					return (
						<Fragment>
							<div className="woocommerce-shipping-rates">
								{ shippingZones.map( ( zone ) => (
									<div
										className="woocommerce-shipping-rate"
										key={ zone.id }
									>
										<div className="woocommerce-shipping-rate__icon">
											{ zone.locations ? (
												zone.locations.map(
													( location ) => (
														<Flag
															size={ 24 }
															code={
																location.code
															}
															key={
																location.code
															}
														/>
													)
												)
											) : (
												// Icon used for zones without locations or "Rest of the world".
												<i className="material-icons-outlined">
													public
												</i>
											) }
										</div>
										<div className="woocommerce-shipping-rate__main">
											<div className="woocommerce-shipping-rate__name">
												{ zone.name }
												{ zone.toggleable && (
													<FormToggle
														{ ...getInputProps(
															`${ zone.id }_enabled`
														) }
													/>
												) }
											</div>
											{ ( ! zone.toggleable ||
												values[
													`${ zone.id }_enabled`
												] ) && (
												<TextControlWithAffixes
													label={ __(
														'Shipping cost',
														'woocommerce-admin'
													) }
													required
													{ ...getInputProps(
														`${ zone.id }_rate`
													) }
													onBlur={ () => {
														setTouched(
															`${ zone.id }_rate`
														);
														setValue(
															`${ zone.id }_rate`,
															this.getFormattedRate(
																values[
																	`${ zone.id }_rate`
																]
															)
														);
													} }
													prefix={ this.renderInputPrefix() }
													suffix={ this.renderInputSuffix(
														values[
															`${ zone.id }_rate`
														]
													) }
													className="muriel-input-text woocommerce-shipping-rate__control-wrapper"
												/>
											) }
										</div>
									</div>
								) ) }
							</div>

							<Button isPrimary onClick={ handleSubmit }>
								{ buttonText ||
									__( 'Update', 'woocommerce-admin' ) }
							</Button>
						</Fragment>
					);
				} }
			</Form>
		);
	}
}

ShippingRates.propTypes = {
	/**
	 * Text displayed on the primary button.
	 */
	buttonText: PropTypes.string,
	/**
	 * Function used to mark the step complete.
	 */
	onComplete: PropTypes.func.isRequired,
	/**
	 * Function to create a transient notice in the store.
	 */
	createNotice: PropTypes.func.isRequired,
	/**
	 * Array of shipping zones returned from the WC REST API with added
	 * `methods` and `locations` properties appended from separate API calls.
	 */
	shippingZones: PropTypes.array,
};

ShippingRates.defaultProps = {
	shippingZones: [],
};

ShippingRates.contextType = CurrencyContext;

export default ShippingRates;
