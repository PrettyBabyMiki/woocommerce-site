/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { isEqual } from 'lodash';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { getIdsFromQuery, updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import Card from '../card';
import CompareButton from './button';
import Search from '../search';

export { default as CompareButton } from './button';

/**
 * Displays a card + search used to filter results as a comparison between objects.
 */
export class CompareFilter extends Component {
	constructor( { getLabels, param, query } ) {
		super( ...arguments );
		this.state = {
			selected: [],
		};

		this.clearQuery = this.clearQuery.bind( this );
		this.updateQuery = this.updateQuery.bind( this );
		this.updateLabels = this.updateLabels.bind( this );

		if ( query[ param ] ) {
			getLabels( query[ param ], query ).then( this.updateLabels );
		}
	}

	componentDidUpdate(
		{ param: prevParam, query: prevQuery },
		{ selected: prevSelected }
	) {
		const { getLabels, param, query } = this.props;
		const { selected } = this.state;
		if (
			prevParam !== param ||
			( prevSelected.length > 0 && selected.length === 0 )
		) {
			this.clearQuery();
			return;
		}

		const prevIds = getIdsFromQuery( prevQuery[ param ] );
		const currentIds = getIdsFromQuery( query[ param ] );
		if ( ! isEqual( prevIds.sort(), currentIds.sort() ) ) {
			getLabels( query[ param ], query ).then( this.updateLabels );
		}
	}

	clearQuery() {
		const { param, path, query } = this.props;

		this.setState( {
			selected: [],
		} );

		updateQueryString( { [ param ]: undefined }, path, query );
	}

	updateLabels( selected ) {
		this.setState( { selected } );
	}

	updateQuery() {
		const { param, path, query } = this.props;
		const { selected } = this.state;
		const idList = selected.map( ( p ) => p.key );
		updateQueryString( { [ param ]: idList.join( ',' ) }, path, query );
	}

	render() {
		const { labels, type } = this.props;
		const { selected } = this.state;
		return (
			<Card
				title={ labels.title }
				className="woocommerce-filters__compare woocommerce-analytics__card"
			>
				<div className="woocommerce-filters__compare-body">
					<Search
						type={ type }
						selected={ selected }
						placeholder={ labels.placeholder }
						onChange={ ( value ) => {
							this.setState( { selected: value } );
						} }
					/>
				</div>
				<div className="woocommerce-filters__compare-footer">
					<CompareButton
						count={ selected.length }
						helpText={ labels.helpText }
						onClick={ this.updateQuery }
					>
						{ labels.update }
					</CompareButton>
					{ selected.length > 0 && (
						<Button isLink={ true } onClick={ this.clearQuery }>
							{ __( 'Clear all', 'woocommerce-admin' ) }
						</Button>
					) }
				</div>
			</Card>
		);
	}
}

CompareFilter.propTypes = {
	/**
	 * Function used to fetch object labels via an API request, returns a Promise.
	 */
	getLabels: PropTypes.func.isRequired,
	/**
	 * Object of localized labels.
	 */
	labels: PropTypes.shape( {
		/**
		 * Label for the search placeholder.
		 */
		placeholder: PropTypes.string,
		/**
		 * Label for the card title.
		 */
		title: PropTypes.string,
		/**
		 * Label for button which updates the URL/report.
		 */
		update: PropTypes.string,
	} ),
	/**
	 * The parameter to use in the querystring.
	 */
	param: PropTypes.string.isRequired,
	/**
	 * The `path` parameter supplied by React-Router
	 */
	path: PropTypes.string.isRequired,
	/**
	 * The query string represented in object form
	 */
	query: PropTypes.object,
	/**
	 * Which type of autocompleter should be used in the Search
	 */
	type: PropTypes.string.isRequired,
};

CompareFilter.defaultProps = {
	labels: {},
	query: {},
};
