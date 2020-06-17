/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { withDispatch, withSelect } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { PLUGINS_STORE_NAME } from '@woocommerce/data';

class Connect extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isConnecting: false,
		};

		this.connectJetpack = this.connectJetpack.bind( this );
		props.setIsPending( true );
	}

	componentDidMount() {
		const { autoConnect, jetpackConnectUrl } = this.props;

		if ( autoConnect && jetpackConnectUrl ) {
			this.connectJetpack();
		}
	}

	componentDidUpdate( prevProps ) {
		const {
			autoConnect,
			createNotice,
			error,
			isRequesting,
			jetpackConnectUrl,
			onError,
			setIsPending,
		} = this.props;

		if ( prevProps.isRequesting && ! isRequesting ) {
			setIsPending( false );
		}

		if ( error && error !== prevProps.error ) {
			if ( onError ) {
				onError();
			}
			createNotice( 'error', error );
		}

		if ( autoConnect && jetpackConnectUrl ) {
			this.connectJetpack();
		}
	}

	async connectJetpack() {
		const { jetpackConnectUrl, onConnect } = this.props;

		this.setState( {
			isConnecting: true,
		}, () => {
			if ( onConnect ) {
				onConnect();
			}
			window.location = jetpackConnectUrl;
		} );
	}

	render() {
		const {
			autoConnect,
			hasErrors,
			isRequesting,
			onSkip,
			skipText,
		} = this.props;

		if ( autoConnect ) {
			return null;
		}

		return (
			<Fragment>
				{ hasErrors ? (
					<Button
						isPrimary
						onClick={ () => window.location.reload() }
					>
						{ __( 'Retry', 'woocommerce-admin' ) }
					</Button>
				) : (
					<Button
						disabled={ isRequesting }
						isBusy={ this.state.isConnecting }
						isPrimary
						onClick={ this.connectJetpack }
					>
						{ __( 'Connect', 'woocommerce-admin' ) }
					</Button>
				) }
				{ onSkip && (
					<Button onClick={ onSkip }>
						{ skipText || __( 'No thanks', 'woocommerce-admin' ) }
					</Button>
				) }
			</Fragment>
		);
	}
}

Connect.propTypes = {
	/**
	 * If connection should happen automatically, or requires user confirmation.
	 */
	autoConnect: PropTypes.bool,
	/**
	 * Method to create a displayed notice.
	 */
	createNotice: PropTypes.func.isRequired,
	/**
	 * Human readable error message.
	 */
	error: PropTypes.string,
	/**
	 * Bool to determine if the "Retry" button should be displayed.
	 */
	hasErrors: PropTypes.bool,
	/**
	 * Bool to check if the connection URL is still being requested.
	 */
	isRequesting: PropTypes.bool,
	/**
	 * Generated Jetpack connection URL.
	 */
	jetpackConnectUrl: PropTypes.string,
	/**
	 * Called before the redirect to Jetpack.
	 */
	onConnect: PropTypes.func,
	/**
	 * Called when the plugin has an error retrieving the jetpackConnectUrl.
	 */
	onError: PropTypes.func,
	/**
	 * Called when the plugin connection is skipped.
	 */
	onSkip: PropTypes.func,
	/**
	 * Redirect URL to encode as a URL param for the connection path.
	 */
	redirectUrl: PropTypes.string,
	/**
	 * Text used for the skip connection button.
	 */
	skipText: PropTypes.string,
	/**
	 * Control the `isPending` logic of the parent containing the Stepper.
	 */
	setIsPending: PropTypes.func,
};

Connect.defaultProps = {
	autoConnect: false,
	setIsPending: () => {},
};

export default compose(
	withSelect( ( select, props ) => {
		const {
			getJetpackConnectUrl,
			isPluginsRequesting,
			getPluginsError,
		} = select( PLUGINS_STORE_NAME );

		const queryArgs = {
			redirect_url: props.redirectUrl || window.location.href,
		};
		const isRequesting = isPluginsRequesting( 'getJetpackConnectUrl' );
		const error = getPluginsError( 'getJetpackConnectUrl' ) || '';
		const jetpackConnectUrl = getJetpackConnectUrl( queryArgs );

		return {
			error,
			isRequesting,
			jetpackConnectUrl,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
		};
	} )
)( Connect );
