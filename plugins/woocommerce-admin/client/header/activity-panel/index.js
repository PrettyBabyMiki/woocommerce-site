/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clickOutside from 'react-click-outside';
import { Component, lazy, Suspense } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { uniqueId, find } from 'lodash';
import CrossIcon from 'gridicons/dist/cross-small';
import classnames from 'classnames';
import { Icon, help as helpIcon } from '@wordpress/icons';
import { getSetting, getAdminLink } from '@woocommerce/wc-admin-settings';
import { H, Section, Spinner } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './style.scss';
import ActivityPanelToggleBubble from './toggle-bubble';
import {
	getUnreadNotes,
	getUnapprovedReviews,
	getUnreadStock,
} from './unread-indicators';
import { isWCAdmin } from '../../dashboard/utils';
import { Tabs } from './tabs';
import { SetupProgress } from './setup-progress';
import { DisplayOptions } from './display-options';

const HelpPanel = lazy( () =>
	import( /* webpackChunkName: "activity-panels-help" */ './panels/help' )
);

const InboxPanel = lazy( () =>
	import(
		/* webpackChunkName: "activity-panels-inbox" */ '../../inbox-panel'
	)
);
const StockPanel = lazy( () =>
	import( /* webpackChunkName: "activity-panels-stock" */ './panels/stock' )
);
const ReviewsPanel = lazy( () =>
	import( /* webpackChunkName: "activity-panels-inbox" */ './panels/reviews' )
);

const manageStock = getSetting( 'manageStock', 'no' );
const reviewsEnabled = getSetting( 'reviewsEnabled', 'no' );
export class ActivityPanel extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isPanelOpen: false,
			mobileOpen: false,
			currentTab: '',
			isPanelSwitching: false,
		};
	}

	togglePanel( { name: tabName }, isTabOpen ) {
		this.setState( ( state ) => {
			const isPanelSwitching =
				tabName !== state.currentTab &&
				state.currentTab !== '' &&
				isTabOpen &&
				state.isPanelOpen;

			return {
				isPanelOpen: isTabOpen,
				mobileOpen: isTabOpen,
				currentTab: tabName,
				isPanelSwitching,
			};
		} );
	}

	closePanel() {
		this.setState( () => ( {
			isPanelOpen: false,
			currentTab: '',
		} ) );
	}

	clearPanel() {
		this.setState( () => ( {
			isPanelSwitching: false,
		} ) );
	}

	// On smaller screen, the panel buttons are hidden behind a toggle.
	toggleMobile() {
		const tabs = this.getTabs();
		this.setState( ( state ) => ( {
			mobileOpen: ! state.mobileOpen,
			currentTab: state.mobileOpen ? '' : tabs[ 0 ].name,
			isPanelOpen: ! state.mobileOpen,
		} ) );
	}

	handleClickOutside( event ) {
		const { isPanelOpen } = this.state;
		const isClickOnModalOrSnackbar =
			event.target.closest(
				'.woocommerce-inbox-dismiss-confirmation_modal'
			) || event.target.closest( '.components-snackbar__action' );

		if ( isPanelOpen && ! isClickOnModalOrSnackbar ) {
			this.closePanel();
		}
	}

	isHomescreen() {
		const { location } = this.props.getHistory();

		return location.pathname === '/';
	}

	isPerformingSetupTask() {
		const {
			requestingTaskListOptions,
			taskListComplete,
			taskListHidden,
			query,
		} = this.props;

		const isPerformingSetupTask =
			query.task &&
			! query.path &&
			( requestingTaskListOptions === true ||
				( taskListHidden === false && taskListComplete === false ) );

		return isPerformingSetupTask;
	}

	// @todo Pull in dynamic unread status/count
	getTabs() {
		const {
			hasUnreadNotes,
			hasUnapprovedReviews,
			hasUnreadStock,
			isEmbedded,
			taskListComplete,
			taskListHidden,
		} = this.props;

		const isPerformingSetupTask = this.isPerformingSetupTask();

		// Don't show the inbox on the Home screen.
		const showInbox =
			( isEmbedded || ! this.isHomescreen() ) && ! isPerformingSetupTask;

		const showHelp =
			( this.isHomescreen() && ! isEmbedded ) || isPerformingSetupTask;

		const showDisplayOptions =
			! isEmbedded && this.isHomescreen() && ! isPerformingSetupTask;

		const showStockAndReviews =
			( taskListComplete || taskListHidden ) && ! isPerformingSetupTask;

		const showStoreSetup =
			! taskListComplete && ! taskListHidden && ! isPerformingSetupTask;

		const inbox = showInbox
			? {
					name: 'inbox',
					title: __( 'Inbox', 'woocommerce-admin' ),
					icon: <i className="material-icons-outlined">inbox</i>,
					unread: hasUnreadNotes,
			  }
			: null;

		const setup = showStoreSetup
			? {
					name: 'setup',
					title: __( 'Store Setup', 'woocommerce-admin' ),
					icon: <SetupProgress />,
			  }
			: null;

		const stockAndReviews = showStockAndReviews
			? [
					manageStock === 'yes' && {
						name: 'stock',
						title: __( 'Stock', 'woocommerce-admin' ),
						icon: (
							<i className="material-icons-outlined">widgets</i>
						),
						unread: hasUnreadStock,
					},
					reviewsEnabled === 'yes' && {
						name: 'reviews',
						title: __( 'Reviews', 'woocommerce-admin' ),
						icon: (
							<i className="material-icons-outlined">
								star_border
							</i>
						),
						unread: hasUnapprovedReviews,
					},
			  ].filter( Boolean )
			: [];

		const help = showHelp
			? {
					name: 'help',
					title: __( 'Help', 'woocommerce-admin' ),
					icon: <Icon icon={ helpIcon } />,
			  }
			: null;

		const displayOptions = showDisplayOptions
			? {
					component: DisplayOptions,
			  }
			: null;

		return [
			inbox,
			...stockAndReviews,
			setup,
			displayOptions,
			help,
		].filter( Boolean );
	}

	getPanelContent( tab ) {
		const { query, hasUnapprovedReviews } = this.props;
		const { task } = query;

		switch ( tab ) {
			case 'inbox':
				return <InboxPanel />;
			case 'stock':
				return <StockPanel />;
			case 'reviews':
				return (
					<ReviewsPanel
						hasUnapprovedReviews={ hasUnapprovedReviews }
					/>
				);
			case 'help':
				return <HelpPanel taskName={ task } />;
			default:
				return null;
		}
	}

	renderPanel() {
		const { updateOptions, taskListHidden } = this.props;
		const { isPanelOpen, currentTab, isPanelSwitching } = this.state;
		const tab = find( this.getTabs(), { name: currentTab } );

		if ( ! tab ) {
			return (
				<div className="woocommerce-layout__activity-panel-wrapper" />
			);
		}

		const clearPanel = () => {
			this.clearPanel();
		};

		if ( currentTab === 'display-options' ) {
			return null;
		}

		if ( currentTab === 'setup' ) {
			const currentLocation = window.location.href;
			const homescreenLocation = getAdminLink(
				'admin.php?page=wc-admin'
			);

			// Don't navigate if we're already on the homescreen, this will cause an infinite loop
			if ( currentLocation !== homescreenLocation ) {
				// Ensure that if the user is trying to get to the task list they can see it even if
				// it was dismissed.
				if ( taskListHidden === 'no' ) {
					this.redirectToHomeScreen();
				} else {
					updateOptions( {
						woocommerce_task_list_hidden: 'no',
					} ).then( this.redirectToHomeScreen );
				}
			}

			return null;
		}

		const classNames = classnames(
			'woocommerce-layout__activity-panel-wrapper',
			{
				'is-open': isPanelOpen,
				'is-switching': isPanelSwitching,
			}
		);

		return (
			<div
				className={ classNames }
				tabIndex={ 0 }
				role="tabpanel"
				aria-label={ tab.title }
				onTransitionEnd={ clearPanel }
				onAnimationEnd={ clearPanel }
			>
				<div
					className="woocommerce-layout__activity-panel-content"
					key={ 'activity-panel-' + currentTab }
					id={ 'activity-panel-' + currentTab }
				>
					<Suspense fallback={ <Spinner /> }>
						{ this.getPanelContent( currentTab ) }
					</Suspense>
				</div>
			</div>
		);
	}

	redirectToHomeScreen() {
		if ( isWCAdmin( window.location.href ) ) {
			getHistory().push( getNewPath( {}, '/', {} ) );
		} else {
			window.location.href = getAdminLink( 'admin.php?page=wc-admin' );
		}
	}

	render() {
		const tabs = this.getTabs();
		const { mobileOpen, currentTab, isPanelOpen } = this.state;
		const headerId = uniqueId( 'activity-panel-header_' );
		const panelClasses = classnames( 'woocommerce-layout__activity-panel', {
			'is-mobile-open': this.state.mobileOpen,
		} );

		const hasUnread = tabs.some( ( tab ) => tab.unread );
		const viewLabel = hasUnread
			? __(
					'View Activity Panel, you have unread activity',
					'woocommerce-admin'
			  )
			: __( 'View Activity Panel', 'woocommerce-admin' );

		return (
			<div>
				<H id={ headerId } className="screen-reader-text">
					{ __( 'Store Activity', 'woocommerce-admin' ) }
				</H>
				<Section
					component="aside"
					id="woocommerce-activity-panel"
					aria-labelledby={ headerId }
				>
					<Button
						onClick={ () => {
							this.toggleMobile();
						} }
						label={
							mobileOpen
								? __(
										'Close Activity Panel',
										'woocommerce-admin'
								  )
								: viewLabel
						}
						aria-expanded={ mobileOpen }
						className="woocommerce-layout__activity-panel-mobile-toggle"
					>
						{ mobileOpen ? (
							<CrossIcon />
						) : (
							<ActivityPanelToggleBubble
								hasUnread={ hasUnread }
							/>
						) }
					</Button>
					<div className={ panelClasses }>
						<Tabs
							tabs={ tabs }
							tabOpen={ isPanelOpen }
							selectedTab={ currentTab }
							onTabClick={ ( tab, tabOpen ) => {
								this.togglePanel( tab, tabOpen );
							} }
						/>
						{ this.renderPanel() }
					</div>
				</Section>
			</div>
		);
	}
}

ActivityPanel.defaultProps = {
	getHistory,
};

export default compose(
	withSelect( ( select ) => {
		const hasUnreadNotes = getUnreadNotes( select );
		const hasUnreadStock = getUnreadStock();
		const hasUnapprovedReviews = getUnapprovedReviews( select );
		const { getOption, isResolving } = select( OPTIONS_STORE_NAME );

		const taskListComplete =
			getOption( 'woocommerce_task_list_complete' ) === 'yes';
		const taskListHidden =
			getOption( 'woocommerce_task_list_hidden' ) === 'yes';
		const requestingTaskListOptions =
			isResolving( 'getOption', [ 'woocommerce_task_list_complete' ] ) ||
			isResolving( 'getOption', [ 'woocommerce_task_list_hidden' ] );

		return {
			hasUnreadNotes,
			hasUnreadStock,
			hasUnapprovedReviews,
			requestingTaskListOptions,
			taskListComplete,
			taskListHidden,
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		updateOptions: dispatch( OPTIONS_STORE_NAME ).updateOptions,
	} ) ),
	clickOutside
)( ActivityPanel );
