/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { find } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import AdvancedFilters from './advanced';
import CompareFilter from './compare';
import DatePicker from './date';
import FilterPicker from './filter';
import { H, Section } from '../section';

/**
 * Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters`
 * or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.
 *
 * @return { object } -
 */
class ReportFilters extends Component {
	constructor() {
		super();
		this.renderCard = this.renderCard.bind( this );
	}

	renderCard( config ) {
		const { advancedFilters, query, path } = this.props;
		const { filters, param } = config;
		if ( ! query[ param ] ) {
			return null;
		}

		if ( 0 === query[ param ].indexOf( 'compare' ) ) {
			const filter = find( filters, { value: query[ param ] } );
			if ( ! filter ) {
				return null;
			}
			const { settings = {} } = filter;
			return (
				<div key={ param } className="woocommerce-filters__advanced-filters">
					<CompareFilter path={ path } query={ query } { ...settings } />
				</div>
			);
		}
		if ( 'advanced' === query[ param ] ) {
			return (
				<div key={ param } className="woocommerce-filters__advanced-filters">
					<AdvancedFilters config={ advancedFilters } path={ path } query={ query } />
				</div>
			);
		}
	}

	render() {
		const { filters, query, path } = this.props;
		return (
			<Fragment>
				<H className="screen-reader-text">{ __( 'Filters', 'wc-admin' ) }</H>
				<Section component="div" className="woocommerce-filters">
					<div className="woocommerce-filters__basic-filters">
						<DatePicker key={ JSON.stringify( query ) } query={ query } path={ path } />
						{ filters.map( config => {
							if ( config.showFilters( query ) ) {
								return (
									<FilterPicker
										key={ config.param }
										config={ config }
										query={ query }
										path={ path }
									/>
								);
							}
						} ) }
					</div>
					{ filters.map( this.renderCard ) }
				</Section>
			</Fragment>
		);
	}
}

ReportFilters.propTypes = {
	/**
	 * Config option passed through to `AdvancedFilters`
	 */
	advancedFilters: PropTypes.object,
	/**
	 * Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.
	 */
	filters: PropTypes.array,
	/**
	 * The `path` parameter supplied by React-Router
	 */
	path: PropTypes.string.isRequired,
	/**
	 * The query string represented in object form
	 */
	query: PropTypes.object,
};

ReportFilters.defaultProps = {
	advancedFilters: {},
	filters: [],
	query: {},
};

export default ReportFilters;
