/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { Router, Route, Switch } from 'react-router-dom';
import { Slot } from 'react-slot-fill';
import PropTypes from 'prop-types';
import { get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { history } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './style.scss';
import { Controller, getPages } from './controller';
import Header from 'header';
import Notices from './notices';
import { recordPageView } from 'lib/tracks';

class Layout extends Component {
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
		const pathname = get( this.props, 'location.pathname' );
		if ( ! pathname ) {
			return;
		}

		// Remove leading slash, and camel case remaining pathname
		let path = pathname.substring( 1 ).replace( /\//g, '_' );

		// When pathname is `/` we are on the dashboard
		if ( path.length === 0 ) {
			path = 'dashboard';
		}

		recordPageView( path );
	}

	render() {
		const { isEmbeded, ...restProps } = this.props;
		return (
			<div className="woocommerce-layout">
				<Slot name="header" />

				<div className="woocommerce-layout__primary" id="woocommerce-layout__primary">
					<Notices />
					{ ! isEmbeded && (
						<div className="woocommerce-layout__main">
							<Controller { ...restProps } />
						</div>
					) }
				</div>
			</div>
		);
	}
}

Layout.propTypes = {
	isEmbededLayout: PropTypes.bool,
};

export class PageLayout extends Component {
	render() {
		return (
			<Router history={ history }>
				<Switch>
					{ getPages().map( page => {
						return <Route key={ page.path } path={ page.path } exact component={ Layout } />;
					} ) }
					<Route component={ Layout } />
				</Switch>
			</Router>
		);
	}
}

export class EmbedLayout extends Component {
	render() {
		return (
			<Fragment>
				<Header sections={ wcSettings.embedBreadcrumbs } isEmbedded />
				<Layout isEmbeded />
			</Fragment>
		);
	}
}
