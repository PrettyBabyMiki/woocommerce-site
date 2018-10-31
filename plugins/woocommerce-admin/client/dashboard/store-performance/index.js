/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	Card,
	EllipsisMenu,
	MenuItem,
	MenuTitle,
	SectionHeader,
	SummaryList,
	SummaryNumber,
} from '@woocommerce/components';
import './style.scss';

class StorePerformance extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			showCustomers: true,
			showProducts: true,
			showOrders: true,
		};

		this.toggle = this.toggle.bind( this );
	}

	toggle( type ) {
		return () => {
			this.setState( state => ( { [ type ]: ! state[ type ] } ) );
		};
	}

	renderMenu() {
		return (
			<EllipsisMenu label={ __( 'Choose which analytics to display', 'wc-admin' ) }>
				<MenuTitle>{ __( 'Display Stats:', 'wc-admin' ) }</MenuTitle>
				<MenuItem onInvoke={ this.toggle( 'showCustomers' ) }>
					<ToggleControl
						label={ __( 'Show Customers', 'wc-admin' ) }
						checked={ this.state.showCustomers }
						onChange={ this.toggle( 'showCustomers' ) }
					/>
				</MenuItem>
				<MenuItem onInvoke={ this.toggle( 'showProducts' ) }>
					<ToggleControl
						label={ __( 'Show Products', 'wc-admin' ) }
						checked={ this.state.showProducts }
						onChange={ this.toggle( 'showProducts' ) }
					/>
				</MenuItem>
				<MenuItem onInvoke={ this.toggle( 'showOrders' ) }>
					<ToggleControl
						label={ __( 'Show Orders', 'wc-admin' ) }
						checked={ this.state.showOrders }
						onChange={ this.toggle( 'showOrders' ) }
					/>
				</MenuItem>
			</EllipsisMenu>
		);
	}

	render() {
		const totalOrders = 10;
		const totalProducts = 1000;
		const { showCustomers, showProducts, showOrders } = this.state;

		return (
			<Fragment>
				<SectionHeader title={ __( 'Store Performance', 'wc-admin' ) } menu={ this.renderMenu() } />
				<Card className="woocommerce-dashboard__store-performance">
					<SummaryList>
						{ showCustomers && (
							<SummaryNumber
								label={ __( 'New Customers', 'wc-admin' ) }
								value={ '2' }
								prevLabel={ __( 'Previous Week:', 'wc-admin' ) }
								prevValue={ 3 }
								delta={ -33 }
							/>
						) }
						{ showProducts && (
							<SummaryNumber
								label={ __( 'Total Products', 'wc-admin' ) }
								value={ totalProducts }
								prevLabel={ __( 'Previous Week:', 'wc-admin' ) }
								prevValue={ totalProducts }
								delta={ 0 }
							/>
						) }
						{ showOrders && (
							<SummaryNumber
								label={ __( 'Total Orders', 'wc-admin' ) }
								value={ totalOrders }
								prevLabel={ __( 'Previous Week:', 'wc-admin' ) }
								prevValue={ totalOrders }
								delta={ 0 }
							/>
						) }
					</SummaryList>
				</Card>
			</Fragment>
		);
	}
}

export default StorePerformance;
