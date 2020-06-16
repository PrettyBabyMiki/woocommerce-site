/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { Button } from '@wordpress/components';
import { useState } from 'react';
import PropTypes from 'prop-types';
import { withDispatch, withSelect } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { H } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import { PLUGINS_STORE_NAME, useUserPreferences } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import Connect from 'dashboard/components/connect';
import { recordEvent } from 'lib/tracks';
import { createNoticesFromResponse } from 'lib/notices';

function InstallJetpackCta( {
	isJetpackInstalled,
	isJetpackActivated,
	isJetpackConnected,
	installAndActivatePlugins,
	isInstalling,
} ) {
	const { updateUserPreferences, ...userPrefs } = useUserPreferences();
	const [ isConnecting, setIsConnecting ] = useState( false );
	const [ isDismissed, setIsDismissed ] = useState(
		( userPrefs.homepage_stats || {} ).installJetpackDismissed
	);

	async function install() {
		recordEvent( 'statsoverview_install_jetpack' );

		installAndActivatePlugins( [ 'jetpack' ] )
			.then( () => {
				setIsConnecting( ! isJetpackConnected );
			} )
			.catch( ( error ) => {
				createNoticesFromResponse( error );
			} );
	}

	function dismiss() {
		if ( isInstalling || isConnecting ) {
			return;
		}

		const homepageStats = userPrefs.homepage_stats || {};

		homepageStats.installJetpackDismissed = true;

		updateUserPreferences( { homepage_stats: homepageStats } );

		setIsDismissed( true );
		recordEvent( 'statsoverview_dismiss_install_jetpack' );
	}

	function getConnector() {
		return (
			<Connect
				autoConnect
				onError={ () => setIsConnecting( false ) }
				redirectUrl={ getAdminLink( 'admin.php?page=wc-admin' ) }
			/>
		);
	}

	const doNotShow =
		isDismissed ||
		( isJetpackInstalled && isJetpackActivated && isJetpackConnected );
	if ( doNotShow ) {
		return null;
	}

	function getInstallJetpackText() {
		if ( ! isJetpackInstalled ) {
			return __( 'Get Jetpack', 'woocommerce-admin' );
		}

		if ( ! isJetpackActivated ) {
			return __( 'Activate Jetpack', 'woocommerce-admin' );
		}

		if ( ! isJetpackConnected ) {
			return __( 'Connect Jetpack', 'woocommerce-admin' );
		}

		return '';
	}

	return (
		<article className="woocommerce-stats-overview__install-jetpack-promo">
			<H>
				{ __( 'Get traffic stats with Jetpack', 'woocommerce-admin' ) }
			</H>
			<p>
				{ __(
					'Keep an eye on your views and visitors metrics with ' +
						'Jetpack. Requires Jetpack plugin and a WordPress.com ' +
						'account.',
					'woocommerce-admin'
				) }
			</p>
			<footer>
				<Button isSecondary onClick={ install } isBusy={ isInstalling }>
					{ getInstallJetpackText() }
				</Button>
				<Button onClick={ dismiss } isBusy={ isInstalling }>
					{ __( 'No thanks', 'woocommerce-admin' ) }
				</Button>
			</footer>

			{ isConnecting && getConnector() }
		</article>
	);
}

InstallJetpackCta.propTypes = {
	/**
	 * Is the Jetpack plugin connected.
	 */
	isJetpackConnected: PropTypes.bool.isRequired,
};

export default compose(
	withSelect( ( select ) => {
		const {
			isJetpackConnected,
			isPluginsRequesting,
			getInstalledPlugins,
			getActivePlugins,
		} = select( PLUGINS_STORE_NAME );

		return {
			isInstalling:
				isPluginsRequesting( 'installPlugins' ) ||
				isPluginsRequesting( 'activatePlugins' ),
			isJetpackInstalled: getInstalledPlugins().includes( 'jetpack' ),
			isJetpackActivated: getActivePlugins().includes( 'jetpack' ),
			isJetpackConnected: isJetpackConnected(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );

		return {
			installAndActivatePlugins,
		};
	} )
)( InstallJetpackCta );
