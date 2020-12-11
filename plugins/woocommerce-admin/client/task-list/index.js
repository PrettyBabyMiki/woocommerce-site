/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
import CartModal from '../dashboard/components/cart-modal';
import { getAllTasks } from './tasks';
import { getCountryCode } from '../dashboard/utils';
import TaskList from './list';

export class TaskDashboard extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isCartModalOpen: false,
		};
	}
	componentDidMount() {
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-task-dashboard__body' );
	}

	getAllTasks() {
		const {
			activePlugins,
			countryCode,
			createNotice,
			installAndActivatePlugins,
			installedPlugins,
			isJetpackConnected,
			onboardingStatus,
			profileItems,
			query,
		} = this.props;

		return getAllTasks( {
			activePlugins,
			countryCode,
			createNotice,
			installAndActivatePlugins,
			installedPlugins,
			isJetpackConnected,
			onboardingStatus,
			profileItems,
			query,
			toggleCartModal: this.toggleCartModal.bind( this ),
		} );
	}

	toggleCartModal() {
		const { isCartModalOpen } = this.state;

		if ( ! isCartModalOpen ) {
			recordEvent( 'tasklist_purchase_extensions' );
		}

		this.setState( { isCartModalOpen: ! isCartModalOpen } );
	}

	render() {
		const {
			dismissedTasks,
			isExtendedTaskListComplete,
			isExtendedTaskListHidden,
			isSetupTaskListHidden,
			isTaskListComplete,
			query,
			trackedCompletedTasks,
		} = this.props;
		const { isCartModalOpen } = this.state;
		const allTasks = this.getAllTasks();
		const { extension: extensionTasks, setup: setupTasks } = allTasks;

		return (
			<>
				{ setupTasks && ! isSetupTaskListHidden && (
					<TaskList
						dismissedTasks={ dismissedTasks }
						isTaskListComplete={ isTaskListComplete }
						isExtended={ false }
						query={ query }
						tasks={ allTasks }
						trackedCompletedTasks={ trackedCompletedTasks }
					/>
				) }
				{ extensionTasks && ! isExtendedTaskListHidden && (
					<TaskList
						dismissedTasks={ dismissedTasks }
						isExtendedTaskListComplete={
							isExtendedTaskListComplete
						}
						isExtended={ true }
						query={ query }
						tasks={ allTasks }
						trackedCompletedTasks={ trackedCompletedTasks }
					/>
				) }
				{ isCartModalOpen && (
					<CartModal
						onClose={ () => this.toggleCartModal() }
						onClickPurchaseLater={ () => this.toggleCartModal() }
					/>
				) }
			</>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getProfileItems, getTasksStatus } = select(
			ONBOARDING_STORE_NAME
		);
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { getOption } = select( OPTIONS_STORE_NAME );
		const {
			getActivePlugins,
			getInstalledPlugins,
			isJetpackConnected,
		} = select( PLUGINS_STORE_NAME );
		const profileItems = getProfileItems();
		const { general: generalSettings = {} } = getSettings( 'general' );
		const countryCode = getCountryCode(
			generalSettings.woocommerce_default_country
		);

		const activePlugins = getActivePlugins();
		const installedPlugins = getInstalledPlugins();
		const onboardingStatus = getTasksStatus();

		return {
			activePlugins,
			countryCode,
			dismissedTasks:
				getOption( 'woocommerce_task_list_dismissed_tasks' ) || [],
			isExtendedTaskListComplete:
				getOption( 'woocommerce_extended_task_list_complete' ) ===
				'yes',
			isExtendedTaskListHidden:
				getOption( 'woocommerce_extended_task_list_hidden' ) === 'yes',
			isJetpackConnected: isJetpackConnected(),
			isSetupTaskListHidden:
				getOption( 'woocommerce_task_list_hidden' ) === 'yes',
			isTaskListComplete:
				getOption( 'woocommerce_task_list_complete' ) === 'yes',
			installedPlugins,
			onboardingStatus,
			profileItems,
			trackedCompletedTasks:
				getOption( 'woocommerce_task_list_tracked_completed_tasks' ) ||
				[],
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );

		return {
			createNotice,
			installAndActivatePlugins,
		};
	} )
)( TaskDashboard );
