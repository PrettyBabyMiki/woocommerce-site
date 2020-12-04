/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { recordEvent } from '@woocommerce/tracks';
import CustomerEffortScore from '@woocommerce/customer-effort-score';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME, WEEK } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';

const SHOWN_FOR_ACTIONS_OPTION_NAME = 'woocommerce_ces_shown_for_actions';
const ADMIN_INSTALL_TIMESTAMP_OPTION_NAME =
	'woocommerce_admin_install_timestamp';
const ALLOW_TRACKING_OPTION_NAME = 'woocommerce_allow_tracking';

/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * @param {Object}   props                    Component props.
 * @param {string}   props.action             The action name sent to Tracks.
 * @param {Object}   props.trackProps         Additional props sent to Tracks.
 * @param {string}   props.label              The label displayed in the modal.
 * @param {string}   props.onSubmitLabel      The label displayed upon survey submission.
 * @param {Array}    props.cesShownForActions The array of actions that the CES modal has been shown for.
 * @param {boolean}  props.allowTracking      Whether tracking is allowed or not.
 * @param {boolean}  props.resolving          Are values still being resolved.
 * @param {number}   props.storeAgeInWeeks    The age of the store in weeks.
 * @param {Function} props.updateOptions      Function to update options.
 * @param {Function} props.createNotice       Function to create a snackbar.
 */
function CustomerEffortScoreTracks( {
	action,
	trackProps,
	label,
	onSubmitLabel = __( 'Thank you for your feedback!', 'woocommerce-admin' ),
	cesShownForActions,
	allowTracking,
	resolving,
	storeAgeInWeeks,
	updateOptions,
	createNotice,
} ) {
	const [ modalShown, setModalShown ] = useState( false );

	if ( resolving ) {
		return null;
	}

	// Don't show if tracking is disallowed.
	if ( ! allowTracking ) {
		return null;
	}

	// We only want to return null early if the modal was already shown
	// for this action *before* this component was initially instantiated.
	//
	// We want to make sure we still render CustomerEffortScore below
	// (we don't want to return null early), if the modal was shown for this
	// instantiation, so that the component doesn't go away while we are
	// still showing it.
	if ( cesShownForActions.indexOf( action ) !== -1 && ! modalShown ) {
		return null;
	}

	const onNoticeShown = () => {
		recordEvent( 'ces_snackbar_view', {
			action,
			store_age: storeAgeInWeeks,
			...trackProps,
		} );
	};

	const addActionToShownOption = () => {
		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				action,
				...cesShownForActions,
			],
		} );
	};

	const onNoticeDismissed = () => {
		recordEvent( 'ces_snackbar_dismiss', {
			action,
			store_age: storeAgeInWeeks,
			...trackProps,
		} );

		addActionToShownOption();
	};

	const onModalShown = () => {
		setModalShown( true );

		recordEvent( 'ces_view', {
			action,
			store_age: storeAgeInWeeks,
			...trackProps,
		} );

		addActionToShownOption();
	};

	const recordScore = ( score, comments ) => {
		recordEvent( 'ces_feedback', {
			action,
			score,
			comments: comments || '',
			store_age: storeAgeInWeeks,
			...trackProps,
		} );
		createNotice( 'success', onSubmitLabel );
	};

	return (
		<CustomerEffortScore
			recordScoreCallback={ recordScore }
			label={ label }
			onNoticeShownCallback={ onNoticeShown }
			onNoticeDismissedCallback={ onNoticeDismissed }
			onModalShownCallback={ onModalShown }
			icon={
				<span
					style={ { height: 21, width: 21 } }
					role="img"
					aria-label={ __( 'Pencil icon', 'woocommerce-admin' ) }
				>
					✏️
				</span>
			}
		/>
	);
}

CustomerEffortScoreTracks.propTypes = {
	/**
	 * The action name sent to Tracks.
	 */
	action: PropTypes.string.isRequired,
	/**
	 * Additional props sent to Tracks.
	 */
	trackProps: PropTypes.object,
	/**
	 * The label displayed in the modal.
	 */
	label: PropTypes.string.isRequired,
	/**
	 * The label for the snackbar that appears upon survey submission.
	 */
	onSubmitLabel: PropTypes.string,
	/**
	 * The array of actions that the CES modal has been shown for.
	 */
	cesShownForActions: PropTypes.arrayOf( PropTypes.string ).isRequired,
	/**
	 * Whether tracking is allowed or not.
	 */
	allowTracking: PropTypes.bool,
	/**
	 * Whether props are still being resolved.
	 */
	resolving: PropTypes.bool.isRequired,
	/**
	 * The age of the store in weeks.
	 */
	storeAgeInWeeks: PropTypes.number,
	/**
	 * Function to update options.
	 */
	updateOptions: PropTypes.func,
	/**
	 * Function to create a snackbar
	 */
	createNotice: PropTypes.func,
};

export default compose(
	withSelect( ( select ) => {
		const { getOption, isResolving } = select( OPTIONS_STORE_NAME );

		const cesShownForActions =
			getOption( SHOWN_FOR_ACTIONS_OPTION_NAME ) || [];

		const adminInstallTimestamp =
			getOption( ADMIN_INSTALL_TIMESTAMP_OPTION_NAME ) || 0;
		// Date.now() is ms since Unix epoch, adminInstallTimestamp is in
		// seconds since Unix epoch.
		const storeAgeInMs = Date.now() - adminInstallTimestamp * 1000;
		const storeAgeInWeeks = Math.round( storeAgeInMs / WEEK );

		const allowTrackingOption =
			getOption( ALLOW_TRACKING_OPTION_NAME ) || 'no';
		const allowTracking = allowTrackingOption === 'yes';

		const resolving =
			isResolving( 'getOption', [ SHOWN_FOR_ACTIONS_OPTION_NAME ] ) ||
			isResolving( 'getOption', [
				ADMIN_INSTALL_TIMESTAMP_OPTION_NAME,
			] ) ||
			isResolving( 'getOption', [ ALLOW_TRACKING_OPTION_NAME ] );

		return {
			cesShownForActions,
			allowTracking,
			storeAgeInWeeks,
			resolving,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			updateOptions,
			createNotice,
		};
	} )
)( CustomerEffortScoreTracks );
