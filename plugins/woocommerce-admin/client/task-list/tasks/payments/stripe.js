/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import apiFetch from '@wordpress/api-fetch';
import { withDispatch, withSelect } from '@wordpress/data';
import interpolateComponents from 'interpolate-components';
import { Button } from '@wordpress/components';

/**
 * WooCommerce dependencies
 */
import { Form, Link, Stepper, TextControl } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import { getQuery } from '@woocommerce/navigation';
import { WCS_NAMESPACE } from 'wc-api/constants';
import { PLUGINS_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';

class Stripe extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			oAuthConnectFailed: false,
			connectURL: null,
			isPending: false,
		};

		this.updateSettings = this.updateSettings.bind( this );
	}

	componentDidMount() {
		const { stripeSettings } = this.props;
		const query = getQuery();

		// Handle redirect back from Stripe.
		if ( query[ 'stripe-connect' ] && query[ 'stripe-connect' ] === '1' ) {
			const isStripeConnected =
				stripeSettings.publishable_key && stripeSettings.secret_key;

			if ( isStripeConnected ) {
				this.completeMethod();
				return;
			}
		}

		if ( ! this.requiresManualConfig() ) {
			this.fetchOAuthConnectURL();
		}
	}

	componentDidUpdate( prevProps ) {
		const { activePlugins } = this.props;

		if (
			! prevProps.activePlugins.includes(
				'woocommerce-gateway-stripe'
			) &&
			activePlugins.includes( 'woocommerce-gateway-stripe' )
		) {
			this.fetchOAuthConnectURL();
		}
	}

	requiresManualConfig() {
		const { activePlugins, isJetpackConnected } = this.props;
		const { oAuthConnectFailed } = this.state;

		return (
			! isJetpackConnected ||
			! activePlugins.includes( 'woocommerce-services' ) ||
			oAuthConnectFailed
		);
	}

	completeMethod() {
		const { createNotice, markConfigured } = this.props;

		this.setState( { isPending: false } );

		createNotice(
			'success',
			__( 'Stripe connected successfully.', 'woocommerce-admin' )
		);

		markConfigured( 'stripe' );
	}

	async fetchOAuthConnectURL() {
		const { activePlugins } = this.props;
		if ( ! activePlugins.includes( 'woocommerce-gateway-stripe' ) ) {
			return;
		}

		try {
			this.setState( { isPending: true } );
			const result = await apiFetch( {
				path: WCS_NAMESPACE + '/connect/stripe/oauth/init',
				method: 'POST',
				data: {
					returnUrl: getAdminLink(
						'admin.php?page=wc-admin&task=payments&method=stripe&stripe-connect=1'
					),
				},
			} );
			if ( ! result || ! result.oauthUrl ) {
				this.setState( {
					oAuthConnectFailed: true,
					isPending: false,
				} );
				return;
			}
			this.setState( {
				connectURL: result.oauthUrl,
				isPending: false,
			} );
		} catch ( error ) {
			this.setState( {
				oAuthConnectFailed: true,
				isPending: false,
			} );
		}
	}

	renderConnectButton() {
		const { connectURL } = this.state;
		return (
			<Button isPrimary href={ connectURL }>
				{ __( 'Connect', 'woocommerce-admin' ) }
			</Button>
		);
	}

	async updateSettings( values ) {
		const { updateOptions, stripeSettings, createNotice } = this.props;

		const update = await updateOptions( {
			woocommerce_stripe_settings: {
				...stripeSettings,
				publishable_key: values.publishable_key,
				secret_key: values.secret_key,
				enabled: 'yes',
			},
		} );

		if ( update.success ) {
			this.completeMethod();
		} else {
			createNotice(
				'error',
				__(
					'There was a problem saving your payment setings',
					'woocommerce-admin'
				)
			);
		}
	}

	getInitialConfigValues() {
		return {
			publishable_key: '',
			secret_key: '',
		};
	}

	validateManualConfig( values ) {
		const errors = {};

		if ( values.publishable_key.match( /^pk_live_/ ) === null ) {
			errors.publishable_key = __(
				'Please enter a valid publishable key. Valid keys start with "pk_live".',
				'woocommerce-admin'
			);
		}
		if ( values.secret_key.match( /^[rs]k_live_/ ) === null ) {
			errors.secret_key = __(
				'Please enter a valid secret key. Valid keys start with "sk_live" or "rk_live".',
				'woocommerce-admin'
			);
		}

		return errors;
	}

	renderManualConfig() {
		const { isOptionsUpdating } = this.props;
		const stripeHelp = interpolateComponents( {
			mixedString: __(
				'Your API details can be obtained from your {{docsLink}}Stripe account{{/docsLink}}.  Don’t have a Stripe account? {{registerLink}}Create one.{{/registerLink}}',
				'woocommerce-admin'
			),
			components: {
				docsLink: (
					<Link
						href="https://stripe.com/docs/keys"
						target="_blank"
						type="external"
					/>
				),
				registerLink: (
					<Link
						href="https://dashboard.stripe.com/register"
						target="_blank"
						type="external"
					/>
				),
			},
		} );

		return (
			<Form
				initialValues={ this.getInitialConfigValues() }
				onSubmitCallback={ this.updateSettings }
				validate={ this.validateManualConfig }
			>
				{ ( { getInputProps, handleSubmit } ) => {
					return (
						<Fragment>
							<TextControl
								label={ __(
									'Live Publishable Key',
									'woocommerce-admin'
								) }
								required
								{ ...getInputProps( 'publishable_key' ) }
							/>
							<TextControl
								label={ __(
									'Live Secret Key',
									'woocommerce-admin'
								) }
								required
								{ ...getInputProps( 'secret_key' ) }
							/>

							<Button
								isPrimary
								isBusy={ isOptionsUpdating }
								onClick={ handleSubmit }
							>
								{ __( 'Proceed', 'woocommerce-admin' ) }
							</Button>

							<p>{ stripeHelp }</p>
						</Fragment>
					);
				} }
			</Form>
		);
	}

	getConnectStep() {
		const { connectURL, isPending, oAuthConnectFailed } = this.state;

		const connectStep = {
			key: 'connect',
			label: __( 'Connect your Stripe account', 'woocommerce-admin' ),
		};

		if ( isPending ) {
			return connectStep;
		}

		if ( ! oAuthConnectFailed && connectURL ) {
			return {
				...connectStep,
				description: __(
					'A Stripe account is required to process payments.',
					'woocommerce-admin'
				),
				content: this.renderConnectButton(),
			};
		}

		return {
			...connectStep,
			description: __(
				'Connect your store to your Stripe account. Don’t have a Stripe account? Create one.',
				'woocommerce-admin'
			),
			content: this.renderManualConfig(),
		};
	}

	render() {
		const { installStep, isOptionsUpdating } = this.props;
		const { isPending } = this.state;

		return (
			<Stepper
				isVertical
				isPending={
					! installStep.isComplete || isOptionsUpdating || isPending
				}
				currentStep={ installStep.isComplete ? 'connect' : 'install' }
				steps={ [ installStep, this.getConnectStep() ] }
			/>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getOption, isOptionsUpdating } = select( OPTIONS_STORE_NAME );
		const { getActivePlugins, isJetpackConnected } = select(
			PLUGINS_STORE_NAME
		);

		return {
			activePlugins: getActivePlugins(),
			isJetpackConnected: isJetpackConnected(),
			isOptionsUpdating: isOptionsUpdating(),
			stripeSettings: getOption( 'woocommerce_stripe_settings' ) || [],
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		return {
			createNotice,
			updateOptions,
		};
	} )
)( Stripe );
