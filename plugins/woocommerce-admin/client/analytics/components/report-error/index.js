/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { EmptyContent } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/navigation';

class ReportError extends Component {
	render() {
		const { className, isError, isEmpty } = this.props;
		let title, actionLabel, actionURL, actionCallback;

		if ( isError ) {
			title = __( 'There was an error getting your stats. Please try again.', 'wc-admin' );
			actionLabel = __( 'Reload', 'wc-admin' );
			actionCallback = () => {
				// TODO Add tracking for how often an error is displayed, and the reload action is clicked.
				window.location.reload();
			};
		} else if ( isEmpty ) {
			title = __( 'No results could be found for this date range.', 'wc-admin' );
			actionLabel = __( 'View Orders', 'wc-admin' );
			actionURL = getAdminLink( 'edit.php?post_type=shop_order' );
		}
		return (
			<EmptyContent
				className={ className }
				title={ title }
				actionLabel={ actionLabel }
				actionURL={ actionURL }
				actionCallback={ actionCallback }
			/>
		);
	}
}

ReportError.propTypes = {
	className: PropTypes.string,
	isError: PropTypes.bool,
	isEmpty: PropTypes.bool,
};

ReportError.defaultProps = {
	className: '',
};

export default ReportError;
