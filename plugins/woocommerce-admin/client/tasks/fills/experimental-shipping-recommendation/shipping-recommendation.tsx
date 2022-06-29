/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { difference } from 'lodash';
import { useEffect, useState } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';
import { Card, CardBody, Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Connect } from './components/connect';
import { Plugins } from './components/plugins';
import { StoreLocation } from './components/store-location';
import { WCSBanner } from './components/wcs-banner';
import { TaskProps, ShippingRecommendationProps } from './types';
import { redirectToWCSSettings } from './utils';

/**
 * Plugins required to automate shipping.
 */
const AUTOMATION_PLUGINS = [ 'jetpack', 'woocommerce-services' ];

export const ShippingRecommendation: React.FC<
	TaskProps & ShippingRecommendationProps
> = ( { activePlugins, isJetpackConnected, isResolving } ) => {
	const [ pluginsToActivate, setPluginsToActivate ] = useState< string[] >(
		[]
	);
	const [ stepIndex, setStepIndex ] = useState( 0 );
	const [ isRedirecting, setIsRedirecting ] = useState( false );

	const nextStep = () => {
		setStepIndex( stepIndex + 1 );
	};

	const redirect = () => {
		setIsRedirecting( true );
		redirectToWCSSettings();
	};

	useEffect( () => {
		const remainingPlugins = difference(
			AUTOMATION_PLUGINS,
			activePlugins
		);

		// Force redirect when all steps are completed.
		if (
			! isResolving &&
			remainingPlugins.length === 0 &&
			isJetpackConnected
		) {
			redirect();
		}

		if ( remainingPlugins.length <= pluginsToActivate.length ) {
			return;
		}
		setPluginsToActivate( remainingPlugins );
	}, [ activePlugins, isJetpackConnected, isResolving, pluginsToActivate ] );

	const steps = [
		{
			key: 'store_location',
			label: __( 'Set store location', 'woocommerce' ),
			description: __(
				'The address from which your business operates',
				'woocommerce'
			),
			content: <StoreLocation nextStep={ nextStep } />,
		},
		{
			key: 'plugins',
			label: pluginsToActivate.includes( 'woocommerce-services' )
				? __(
						'Install Jetpack and WooCommerce Shipping',
						'woocommerce'
				  )
				: __( 'Install Jetpack', 'woocommerce' ),
			description: __(
				'Enable shipping label printing and discounted rates',
				'woocommerce'
			),
			content: (
				<div>
					<WCSBanner />
					<Plugins
						nextStep={ nextStep }
						pluginsToActivate={ pluginsToActivate }
					/>
				</div>
			),
		},
		{
			key: 'connect',
			label: __( 'Connect your store', 'woocommerce' ),
			description: __(
				'Connect your store to WordPress.com to enable WooCommerce Shipping',
				'woocommerce'
			),
			content: isJetpackConnected ? (
				<Button onClick={ redirect } isBusy={ isRedirecting } isPrimary>
					{ __( 'Complete task', 'woocommerce' ) }
				</Button>
			) : (
				<Connect onConnect={ redirect } />
			),
		},
	];

	const step = steps[ stepIndex ];

	return (
		<div className="woocommerce-task-shipping-recommendation">
			<Card className="woocommerce-task-card">
				<CardBody>
					<Stepper
						isPending={ isResolving }
						isVertical={ true }
						currentStep={ step.key }
						steps={ steps }
					/>
				</CardBody>
			</Card>
		</div>
	);
};
