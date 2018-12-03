/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * `ChartPlaceholder` displays a large loading indiciator for use in place of a `Chart` while data is loading.
 */
class ChartPlaceholder extends Component {
	render() {
		const { height } = this.props;

		return (
			<div aria-hidden="true" className="woocommerce-chart-placeholder" style={ { height } } />
		);
	}
}

ChartPlaceholder.propTypes = {
	height: PropTypes.number,
};

ChartPlaceholder.defaultProps = {
	height: 0,
};

export default ChartPlaceholder;
