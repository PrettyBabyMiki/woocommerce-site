/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import { isEqual, xor } from 'lodash';
import PropTypes from 'prop-types';
import { ToggleControl, IconButton, NavigableMenu } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { EllipsisMenu, MenuItem, SectionHeader } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import ChartBlock from './block';
import { getChartFromKey, uniqCharts } from './config';
import withSelect from 'wc-api/with-select';
import './style.scss';

class DashboardCharts extends Component {
	constructor( props ) {
		super( ...arguments );
		this.state = {
			chartType: props.userPrefChartType || 'line',
			hiddenChartKeys: props.userPrefCharts || [],
			query: props.query,
		};

		this.toggle = this.toggle.bind( this );
	}

	componentDidUpdate( {
		userPrefCharts: prevUserPrefCharts,
		userPrefChartType: prevUserPrefChartType,
	} ) {
		const { userPrefCharts, userPrefChartType } = this.props;
		if ( userPrefCharts && ! isEqual( userPrefCharts, prevUserPrefCharts ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				hiddenChartKeys: userPrefCharts,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
		if ( userPrefChartType && userPrefChartType !== prevUserPrefChartType ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				chartType: userPrefChartType,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	toggle( key ) {
		return () => {
			const hiddenChartKeys = xor( this.state.hiddenChartKeys, [ key ] );
			this.setState( { hiddenChartKeys } );
			const userDataFields = {
				[ 'dashboard_charts' ]: hiddenChartKeys,
			};
			this.props.updateCurrentUserData( userDataFields );
		};
	}

	handleTypeToggle( type ) {
		return () => {
			this.setState( { chartType: type } );
			const userDataFields = {
				[ 'dashboard_chart_type' ]: type,
			};
			this.props.updateCurrentUserData( userDataFields );
		};
	}

	renderMenu() {
		return (
			<EllipsisMenu label={ __( 'Choose which charts to display', 'wc-admin' ) }>
				{ uniqCharts.map( chart => {
					return (
						<MenuItem onInvoke={ this.toggle( chart.key ) } key={ chart.key }>
							<ToggleControl
								label={ __( `${ chart.label }`, 'wc-admin' ) }
								checked={ ! this.state.hiddenChartKeys.includes( chart.key ) }
								onChange={ this.toggle( chart.key ) }
							/>
						</MenuItem>
					);
				} ) }
			</EllipsisMenu>
		);
	}

	render() {
		const { path } = this.props;
		const { chartType, hiddenChartKeys } = this.state;
		const query = { ...this.props.query, type: chartType };
		return (
			<Fragment>
				<div className="woocommerce-dashboard__dashboard-charts">
					<SectionHeader title={ __( 'Charts', 'wc-admin' ) } menu={ this.renderMenu() }>
						<NavigableMenu
							className="woocommerce-chart__types"
							orientation="horizontal"
							role="menubar"
						>
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected': ! query.type || query.type === 'line',
								} ) }
								icon={ <Gridicon icon="line-graph" /> }
								title={ __( 'Line chart', 'wc-admin' ) }
								aria-checked={ query.type === 'line' }
								role="menuitemradio"
								tabIndex={ query.type === 'line' ? 0 : -1 }
								onClick={ this.handleTypeToggle( 'line' ) }
							/>
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected': query.type === 'bar',
								} ) }
								icon={ <Gridicon icon="stats-alt" /> }
								title={ __( 'Bar chart', 'wc-admin' ) }
								aria-checked={ query.type === 'bar' }
								role="menuitemradio"
								tabIndex={ query.type === 'bar' ? 0 : -1 }
								onClick={ this.handleTypeToggle( 'bar' ) }
							/>
						</NavigableMenu>
					</SectionHeader>
					<div className="woocommerce-dashboard__columns">
						{ uniqCharts.map( chart => {
							return hiddenChartKeys.includes( chart.key ) ? null : (
								<div key={ chart.key }>
									<ChartBlock
										charts={ getChartFromKey( chart.key ) }
										endpoint={ chart.endpoint }
										path={ path }
										query={ query }
									/>
								</div>
							);
						} ) }
					</div>
				</div>
			</Fragment>
		);
	}
}

DashboardCharts.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default compose(
	withSelect( select => {
		const { getCurrentUserData } = select( 'wc-api' );
		const userData = getCurrentUserData();

		return {
			userPrefCharts: userData.dashboard_charts,
			userPrefChartType: userData.dashboard_chart_type,
		};
	} ),
	withDispatch( dispatch => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( DashboardCharts );
