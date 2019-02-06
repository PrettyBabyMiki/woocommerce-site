/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { find, first, isEqual, noop, partial, uniq, without } from 'lodash';
import { IconButton, ToggleControl } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import {
	downloadCSVFile,
	generateCSVDataFromTable,
	generateCSVFileName,
} from '@woocommerce/csv-export';
import { getIdsFromQuery, updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import Card from '../card';
import CompareButton from '../filters/compare/button';
import DowloadIcon from './download-icon';
import EllipsisMenu from '../ellipsis-menu';
import MenuItem from '../ellipsis-menu/menu-item';
import MenuTitle from '../ellipsis-menu/menu-title';
import Pagination from '../pagination';
import Search from '../search';
import Table from './table';
import TablePlaceholder from './placeholder';
import TableSummary from './summary';

/**
 * This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).
 * It accepts `headers` for column headers, and `rows` for the table content.
 * `rowHeader` can be used to define the index of the row header (or false if no header).
 *
 * `TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.
 * This includes filtering and comparison functionality for report pages.
 */
class TableCard extends Component {
	constructor( props ) {
		super( props );
		const { query, compareBy } = this.props;
		const showCols = props.headers.map( ( { key, hiddenByDefault } ) => ! hiddenByDefault && key ).filter( Boolean );
		const selectedRows = query.filter ? getIdsFromQuery( query[ compareBy ] ) : [];

		this.state = { showCols, selectedRows };
		this.onColumnToggle = this.onColumnToggle.bind( this );
		this.onClickDownload = this.onClickDownload.bind( this );
		this.onCompare = this.onCompare.bind( this );
		this.onSearch = this.onSearch.bind( this );
		this.selectRow = this.selectRow.bind( this );
		this.selectAllRows = this.selectAllRows.bind( this );
	}

	componentDidUpdate( { query: prevQuery, headers: prevHeaders } ) {
		const { compareBy, headers, query } = this.props;

		if ( query.filter || prevQuery.filter ) {
			const prevIds = prevQuery.filter ? getIdsFromQuery( prevQuery[ compareBy ] ) : [];
			const currentIds = query.filter ? getIdsFromQuery( query[ compareBy ] ) : [];
			if ( ! isEqual( prevIds.sort(), currentIds.sort() ) ) {
				/* eslint-disable react/no-did-update-set-state */
				this.setState( {
					selectedRows: currentIds,
				} );
				/* eslint-enable react/no-did-update-set-state */
			}
		}
		if ( ! isEqual( headers, prevHeaders ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				showCols: headers.map( ( { key, hiddenByDefault } ) => ! hiddenByDefault && key ).filter( Boolean ),
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	getVisibleHeaders() {
		const { headers } = this.props;
		const { showCols } = this.state;
		return headers.filter( ( { key } ) => showCols.includes( key ) );
	}

	getVisibleRows() {
		const { headers, rows } = this.props;
		const { showCols } = this.state;

		return rows.map( row => {
			return headers.map( ( { key }, i ) => {
				return showCols.includes( key ) && row[ i ];
			} ).filter( Boolean );
		} );
	}

	onColumnToggle( key ) {
		const { headers, query, onQueryChange, onColumnsChange } = this.props;

		return ( visible ) => {
			this.setState( prevState => {
				const hasKey = prevState.showCols.includes( key );

				if ( visible && ! hasKey ) {
					const showCols = [ ...prevState.showCols, key ];
					onColumnsChange( showCols );
					return { showCols };
				}
				if ( ! visible && hasKey ) {
					// Handle hiding a sorted column
					if ( query.orderby === key ) {
						const defaultSort = find( headers, { defaultSort: true } ) || first( headers ) || {};
						onQueryChange( 'sort' )( defaultSort.key, 'desc' );
					}

					const showCols = without( prevState.showCols, key );
					onColumnsChange( showCols );
					return { showCols };
				}
				return {};
			} );
		};
	}

	onClickDownload() {
		const { query, onClickDownload, title } = this.props;

		// @todo The current implementation only downloads the contents displayed in the table.
		// Another solution is required when the data set is larger (see #311).
		downloadCSVFile(
			generateCSVFileName( title, query ),
			generateCSVDataFromTable( this.getVisibleHeaders(), this.getVisibleRows() )
		);

		if ( onClickDownload ) {
			onClickDownload();
		}
	}

	onCompare() {
		// Reset selected rows so the user can start a comparison again.
		this.setState( {
			selectedRows: [],
		} );
	}

	onSearch( values ) {
		const { compareParam } = this.props;
		const labels = values.map( v => v.label );
		if ( labels.length ) {
			updateQueryString( {
				filter: undefined,
				[ compareParam ]: undefined,
				search: uniq( labels ).join( ',' ),
			} );
		} else {
			updateQueryString( {
				search: undefined,
			} );
		}
	}

	selectAllRows( event ) {
		const { ids } = this.props;
		if ( event.target.checked ) {
			this.setState( {
				selectedRows: ids,
			} );
		} else {
			this.setState( {
				selectedRows: [],
			} );
		}
	}

	selectRow( i, event ) {
		const { ids } = this.props;
		if ( event.target.checked ) {
			this.setState( ( { selectedRows } ) => ( {
				selectedRows: uniq( [ ids[ i ], ...selectedRows ] ),
			} ) );
		} else {
			this.setState( ( { selectedRows } ) => {
				const index = selectedRows.indexOf( ids[ i ] );
				return {
					selectedRows: [ ...selectedRows.slice( 0, index ), ...selectedRows.slice( index + 1 ) ],
				};
			} );
		}
	}

	getCheckbox( i ) {
		const { ids = [] } = this.props;
		const { selectedRows } = this.state;
		const isChecked = -1 !== selectedRows.indexOf( ids[ i ] );
		return {
			display: (
				<input type="checkbox" onChange={ partial( this.selectRow, i ) } checked={ isChecked } />
			),
			value: false,
		};
	}

	getAllCheckbox() {
		const { ids = [] } = this.props;
		const { selectedRows } = this.state;
		const isAllChecked = ids.length > 0 && ids.length === selectedRows.length;
		return {
			cellClassName: 'is-checkbox-column',
			label: (
				<input
					type="checkbox"
					onChange={ this.selectAllRows }
					aria-label={ __( 'Select All' ) }
					checked={ isAllChecked }
				/>
			),
			required: true,
		};
	}

	render() {
		const {
			compareBy,
			downloadable,
			labels = {},
			isLoading,
			onClickDownload,
			onQueryChange,
			query,
			rowHeader,
			rowsPerPage,
			searchBy,
			showMenu,
			summary,
			title,
			totalRows,
		} = this.props;
		const { selectedRows, showCols } = this.state;
		const searchedValues = query.search ? query.search.split( ',' ) : [];
		const searchedLabels = searchedValues.map( v => ( { id: v, label: v } ) );
		const allHeaders = this.props.headers;
		let headers = this.getVisibleHeaders();
		let rows = this.getVisibleRows();
		if ( compareBy ) {
			rows = rows.map( ( row, i ) => {
				return [ this.getCheckbox( i ), ...row ];
			} );
			headers = [ this.getAllCheckbox(), ...headers ];
		}

		const className = classnames( {
			'woocommerce-table': true,
			'has-compare': !! compareBy,
			'has-search': !! searchBy,
		} );

		return (
			<Card
				className={ className }
				title={ title }
				action={ [
					compareBy && (
						<CompareButton
							key="compare"
							className="woocommerce-table__compare"
							count={ selectedRows.length }
							helpText={
								labels.helpText || __( 'Select at least two items to compare', 'wc-admin' )
							}
							onClick={ this.onCompare }
						>
							{ labels.compareButton || __( 'Compare', 'wc-admin' ) }
						</CompareButton>
					),
					searchBy && (
						<Search
							allowFreeTextSearch={ true }
							inlineTags
							key="search"
							onChange={ this.onSearch }
							placeholder={ labels.placeholder || __( 'Search by item name', 'wc-admin' ) }
							selected={ searchedLabels }
							showClearButton={ true }
							type={ searchBy }
						/>
					),
					( downloadable || onClickDownload ) && (
						<IconButton
							key="download"
							className="woocommerce-table__download-button"
							disabled={ isLoading }
							onClick={ this.onClickDownload }
							isLink
						>
							<DowloadIcon />
							<span className="woocommerce-table__download-button__label">
								{ labels.downloadButton || __( 'Download', 'wc-admin' ) }
							</span>
						</IconButton>
					),
				] }
				menu={
					showMenu && <EllipsisMenu label={ __( 'Choose which values to display', 'wc-admin' ) }>
						<MenuTitle>{ __( 'Columns:', 'wc-admin' ) }</MenuTitle>
						{ allHeaders.map( ( { key, label, required } ) => {
							if ( required ) {
								return null;
							}
							return (
								<MenuItem key={ key } onInvoke={ this.onColumnToggle( key ) }>
									<ToggleControl
										label={ label }
										checked={ showCols.includes( key ) }
										onChange={ this.onColumnToggle( key ) }
									/>
								</MenuItem>
							);
						} ) }
					</EllipsisMenu>
				}
			>
				{ isLoading ? (
					<TablePlaceholder
						numberOfRows={ rowsPerPage }
						headers={ headers }
						rowHeader={ rowHeader }
						caption={ title }
						query={ query }
						onSort={ onQueryChange( 'sort' ) }
					/>
				) : (
					<Table
						rows={ rows }
						headers={ headers }
						rowHeader={ rowHeader }
						caption={ title }
						query={ query }
						onSort={ onQueryChange( 'sort' ) }
					/>
				) }

				<Pagination
					page={ parseInt( query.page ) || 1 }
					perPage={ rowsPerPage }
					total={ totalRows }
					onPageChange={ onQueryChange( 'page' ) }
					onPerPageChange={ onQueryChange( 'per_page' ) }
				/>

				{ summary && <TableSummary data={ summary } /> }
			</Card>
		);
	}
}

TableCard.propTypes = {
	/**
	 * The string to use as a query parameter when comparing row items.
	 */
	compareBy: PropTypes.string,
	/**
	 * Url query parameter compare function operates on
	 */
	compareParam: PropTypes.string,
	/**
	 * An array of column headers (see `Table` props).
	 */
	headers: PropTypes.arrayOf(
		PropTypes.shape( {
			hiddenByDefault: PropTypes.bool,
			defaultSort: PropTypes.bool,
			isSortable: PropTypes.bool,
			key: PropTypes.string,
			label: PropTypes.string,
			required: PropTypes.bool,
		} )
	),
	/**
	 * Custom labels for table header actions.
	 */
	labels: PropTypes.shape( {
		compareButton: PropTypes.string,
		downloadButton: PropTypes.string,
		helpText: PropTypes.string,
		placeholder: PropTypes.string,
	} ),
	/**
	 * A list of IDs, matching to the row list so that ids[ 0 ] contains the object ID for the object displayed in row[ 0 ].
	 */
	ids: PropTypes.arrayOf( PropTypes.number ),
	/**
	 * Defines if the table contents are loading.
	 * It will display `TablePlaceholder` component instead of `Table` if that's the case.
	 */
	isLoading: PropTypes.bool,
	/**
	 * A function which returns a callback function to update the query string for a given `param`.
	 */
	onQueryChange: PropTypes.func,
	/**
	 * A function which returns a callback function which is called upon the user changing the visiblity of columns.
	 */
	onColumnsChange: PropTypes.func,
	/**
	 * Whether the table must be downloadable. If true, the download button will appear.
	 */
	downloadable: PropTypes.bool,
	/**
	 * A callback function called when the "download" button is pressed. Optional, if used, the download button will appear.
	 */
	onClickDownload: PropTypes.func,
	/**
	 *  An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`.
	 */
	query: PropTypes.object,
	/**
	 * An array of arrays of display/value object pairs (see `Table` props).
	 */
	rowHeader: PropTypes.oneOfType( [ PropTypes.number, PropTypes.bool ] ),
	/**
	 * Which column should be the row header, defaults to the first item (`0`) (see `Table` props).
	 */
	rows: PropTypes.arrayOf(
		PropTypes.arrayOf(
			PropTypes.shape( {
				display: PropTypes.node,
				value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number, PropTypes.bool ] ),
			} )
		)
	).isRequired,
	/**
	 * The total number of rows to display per page.
	 */
	rowsPerPage: PropTypes.number.isRequired,
	/**
	 * The string to use as a query parameter when searching row items.
	 */
	searchBy: PropTypes.string,
	/**
	 * Boolean to determine whether or not ellipsis menu is shown.
	 */
	showMenu: PropTypes.bool,
	/**
	 * An array of objects with `label` & `value` properties, which display in a line under the table.
	 * Optional, can be left off to show no summary.
	 */
	summary: PropTypes.arrayOf(
		PropTypes.shape( {
			label: PropTypes.node,
			value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number ] ),
		} )
	),
	/**
	 * The title used in the card header, also used as the caption for the content in this table.
	 */
	title: PropTypes.string.isRequired,
	/**
	 * The total number of rows (across all pages).
	 */
	totalRows: PropTypes.number.isRequired,
};

TableCard.defaultProps = {
	compareParam: 'filter',
	downloadable: false,
	isLoading: false,
	onQueryChange: noop,
	onColumnsChange: noop,
	query: {},
	rowHeader: 0,
	rows: [],
	showMenu: true,
};

export default TableCard;
