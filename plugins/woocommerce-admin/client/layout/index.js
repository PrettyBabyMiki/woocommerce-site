/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { Component, lazy, Suspense } from '@wordpress/element';
import { Router, Route, Switch } from 'react-router-dom';
import PropTypes from 'prop-types';
import { get, isFunction, identity } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { useFilters, Spinner } from '@woocommerce/components';
import { getHistory } from '@woocommerce/navigation';
import { getSetting } from '@woocommerce/wc-admin-settings';
import {
	PLUGINS_STORE_NAME,
	withPluginsHydration,
	withOptionsHydration,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './style.scss';
import { Controller, getPages, PAGES_FILTER } from './controller';
import Header from 'header';
import Notices from './notices';
import { recordPageView } from 'lib/tracks';
import TransientNotices from './transient-notices';
const StoreAlerts = lazy( () =>
	import( /* webpackChunkName: "store-alerts" */ './store-alerts' )
);
import { REPORTS_FILTER } from 'analytics/report';

export class PrimaryLayout extends Component {
	render() {
		const { children } = this.props;
		return (
			<div
				className="woocommerce-layout__primary"
				id="woocommerce-layout__primary"
			>
				{ window.wcAdminFeatures[ 'store-alerts' ] && (
					<Suspense fallback={ <Spinner /> }>
						<StoreAlerts />
					</Suspense>
				) }
				<Notices />
				{ children }
			</div>
		);
	}
}

class _Layout extends Component {
	componentDidMount() {
		this.recordPageViewTrack();
	}

	componentDidUpdate( prevProps ) {
		const previousPath = get( prevProps, 'location.pathname' );
		const currentPath = get( this.props, 'location.pathname' );

		if ( ! previousPath || ! currentPath ) {
			return;
		}

		if ( previousPath !== currentPath ) {
			this.recordPageViewTrack();
		}
	}

	recordPageViewTrack() {
		const {
			activePlugins,
			installedPlugins,
			isEmbedded,
			isJetpackConnected,
		} = this.props;

		if ( isEmbedded ) {
			const path = document.location.pathname + document.location.search;
			recordPageView( path, { is_embedded: true } );
			return;
		}

		const pathname = get( this.props, 'location.pathname' );
		if ( ! pathname ) {
			return;
		}

		// Remove leading slash, and camel case remaining pathname
		let path = pathname.substring( 1 ).replace( /\//g, '_' );

		// When pathname is `/` we are on the dashboard
		if ( path.length === 0 ) {
			path = window.wcAdminFeatures.homescreen
				? 'home_screen'
				: 'dashboard';
		}

		recordPageView( path, {
			jetpack_installed: installedPlugins.includes( 'jetpack' ),
			jetpack_active: activePlugins.includes( 'jetpack' ),
			jetpack_connected: isJetpackConnected,
		} );
	}

	render() {
		const { isEmbedded, ...restProps } = this.props;
		const { breadcrumbs } = this.props.page;

		return (
			<div className="woocommerce-layout">
				<Header
					sections={
						isFunction( breadcrumbs )
							? breadcrumbs( this.props )
							: breadcrumbs
					}
					isEmbedded={ isEmbedded }
				/>
				<TransientNotices />
				{ ! isEmbedded && (
					<PrimaryLayout>
						<div className="woocommerce-layout__main">
							<Controller { ...restProps } />
						</div>
					</PrimaryLayout>
				) }
			</div>
		);
	}
}

_Layout.propTypes = {
	isEmbedded: PropTypes.bool,
	page: PropTypes.shape( {
		container: PropTypes.oneOfType( [
			PropTypes.func,
			PropTypes.object, // Support React.lazy
		] ),
		path: PropTypes.string,
		breadcrumbs: PropTypes.oneOfType( [
			PropTypes.func,
			PropTypes.arrayOf(
				PropTypes.oneOfType( [
					PropTypes.arrayOf( PropTypes.string ),
					PropTypes.string,
				] )
			),
		] ).isRequired,
		wpOpenMenu: PropTypes.string,
	} ).isRequired,
};

const Layout = compose(
	withPluginsHydration( {
		...( window.wcSettings.plugins || {} ),
		jetpackStatus:
			( window.wcSettings.dataEndpoints &&
				window.wcSettings.dataEndpoints.jetpackStatus ) ||
			false,
	} ),
	withSelect( ( select, { isEmbedded } ) => {
		// Embedded pages don't send plugin info to Tracks.
		if ( isEmbedded ) {
			return;
		}

		const {
			getActivePlugins,
			getInstalledPlugins,
			isJetpackConnected,
		} = select( PLUGINS_STORE_NAME );

		return {
			activePlugins: getActivePlugins(),
			isJetpackConnected: isJetpackConnected(),
			installedPlugins: getInstalledPlugins(),
		};
	} )
)( _Layout );

class _PageLayout extends Component {
	render() {
		return (
			<Router history={ getHistory() }>
				<Switch>
					{ getPages().map( ( page ) => {
						return (
							<Route
								key={ page.path }
								path={ page.path }
								exact
								render={ ( props ) => (
									<Layout page={ page } { ...props } />
								) }
							/>
						);
					} ) }
				</Switch>
			</Router>
		);
	}
}

export const PageLayout = compose(
	// Use the useFilters HoC so PageLayout is re-rendered when filters are used to add new pages or reports
	useFilters( [ PAGES_FILTER, REPORTS_FILTER ] ),
	window.wcSettings.preloadOptions
		? withOptionsHydration( {
				...window.wcSettings.preloadOptions,
		  } )
		: identity
)( _PageLayout );

export class EmbedLayout extends Component {
	render() {
		return (
			<Layout
				page={ {
					breadcrumbs: getSetting( 'embedBreadcrumbs', [] ),
				} }
				isEmbedded
			/>
		);
	}
}
