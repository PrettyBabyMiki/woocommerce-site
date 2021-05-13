/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import classnames from 'classnames';
import { OPTIONS_STORE_NAME, USER_STORE_NAME } from '@woocommerce/data';
import PropTypes from 'prop-types';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SnackbarList from './snackbar/list';
import './style.scss';

const QUEUE_OPTION = 'woocommerce_admin_transient_notices_queue';
const QUEUED_NOTICE_FILTER = 'woocommerce_admin_queued_notice_filter';

function TransientNotices( props ) {
	const { createNotice, removeNotice: onRemove } = useDispatch(
		'core/notices2'
	);
	const { removeNotice: onRemove2 } = useDispatch( 'core/notices2' );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const {
		currentUser = {},
		notices = [],
		notices2 = [],
		noticesQueue = {},
	} = useSelect( ( select ) => {
		// NOTE: This uses core/notices2, if this file is copied back upstream
		// to Gutenberg this needs to be changed back to just core/notices.
		return {
			currentUser: select( USER_STORE_NAME ).getCurrentUser(),
			notices: select( 'core/notices' ).getNotices(),
			notices2: select( 'core/notices2' ).getNotices(),
			noticesQueue: select( OPTIONS_STORE_NAME ).getOption(
				QUEUE_OPTION
			),
		};
	} );

	const getCurrentUserNotices = () => {
		return Object.values( noticesQueue ).filter(
			( notice ) => notice.user_id === currentUser.id || ! notice.user_id
		);
	};

	const dequeueNotice = ( id ) => {
		const remainingNotices = { ...noticesQueue };
		delete remainingNotices[ id ];
		updateOptions( {
			[ QUEUE_OPTION ]: remainingNotices,
		} );
	};

	useEffect( () => {
		getCurrentUserNotices().forEach( ( queuedNotice ) => {
			const notice = applyFilters( QUEUED_NOTICE_FILTER, queuedNotice );

			createNotice( notice.status, notice.content, {
				onDismiss: () => {
					dequeueNotice( notice.id );
				},
				...( notice.options || {} ),
			} );
		} );
	}, [] );

	/**
	 * Combines the two notices in the component vs in the useSelect, as we don't want to
	 * create new object references on each useSelect call.
	 */
	const getNotices = () => {
		return notices.concat( notices2 );
	};

	const { className } = props;
	const classes = classnames(
		'woocommerce-transient-notices',
		'components-notices__snackbar',
		className
	);
	const combinedNotices = getNotices();

	return (
		<SnackbarList
			notices={ combinedNotices }
			className={ classes }
			onRemove={ onRemove }
			onRemove2={ onRemove2 }
		/>
	);
}

TransientNotices.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * Array of notices to be displayed.
	 */
	notices: PropTypes.array,
};

export default TransientNotices;
