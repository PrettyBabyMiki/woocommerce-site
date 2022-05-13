/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState, createElement } from '@wordpress/element';
import { Button, Card } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { EllipsisMenu } from '@woocommerce/components';
import {
	updateQueryString,
	getHistory,
	getNewPath,
} from '@woocommerce/navigation';
import {
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	TaskType,
	useUserPreferences,
	getVisibleTasks,
	TaskListType,
	WCDataSelector,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { List } from '@woocommerce/experimental';
import classnames from 'classnames';
import { History } from 'history';

/**
 * Internal dependencies
 */
import '../tasks/task-list.scss';
import taskHeaders from './task-headers';
import DismissModal from './dismiss-modal';
import TaskListCompleted from './completed';
import { ProgressHeader } from '~/task-lists/progress-header';
import { TaskListItemTwoColumn } from './task-list-item-two-column';
import { TaskListCompletedHeaderWithCES } from './completed-header-with-ces';

export type TaskListProps = TaskListType & {
	eventName?: string;
	twoColumns?: boolean;
	query: {
		task?: string;
	};
	cesHeader?: boolean;
};

export const TaskList: React.FC< TaskListProps > = ( {
	query,
	id,
	eventName,
	eventPrefix,
	tasks,
	twoColumns,
	keepCompletedTaskList,
	isComplete,
	displayProgressHeader,
	cesHeader = true,
	showCESFeedback = false,
} ) => {
	const listEventPrefix = eventName ? eventName + '_' : eventPrefix;
	const { createNotice } = useDispatch( 'core/notices' );
	const { updateOptions, dismissTask, undoDismissTask } = useDispatch(
		OPTIONS_STORE_NAME
	);
	const { profileItems } = useSelect( ( select: WCDataSelector ) => {
		const { getProfileItems } = select( ONBOARDING_STORE_NAME );
		return {
			profileItems: getProfileItems(),
		};
	} );
	const {
		hideTaskList,
		visitedTask,
		keepCompletedTaskList: keepCompletedTasks,
	} = useDispatch( ONBOARDING_STORE_NAME );
	const userPreferences = useUserPreferences();
	const [ headerData, setHeaderData ] = useState< {
		task?: TaskType;
		goToTask?: () => void;
		trackClick?: () => void;
	} >( {} );
	const [ activeTaskId, setActiveTaskId ] = useState( '' );
	const [ showDismissModal, setShowDismissModal ] = useState( false );

	const prevQueryRef = useRef( query );

	const visibleTasks = getVisibleTasks( tasks );
	const recordTaskListView = () => {
		if ( query.task ) {
			return;
		}

		recordEvent( `${ listEventPrefix }view`, {
			number_tasks: visibleTasks.length,
			store_connected: profileItems.wccom_connected,
		} );
	};

	useEffect( () => {
		recordTaskListView();
	}, [] );

	useEffect( () => {
		const { task: prevTask } = prevQueryRef.current;
		const { task } = query;

		if ( prevTask !== task ) {
			window.document.documentElement.scrollTop = 0;
			prevQueryRef.current = query;
		}
	}, [ query ] );

	const incompleteTasks = tasks.filter(
		( task ) => ! task.isComplete && ! task.isDismissed
	);

	const hideTasks = () => {
		hideTaskList( id );
	};

	const keepTasks = () => {
		keepCompletedTasks( id );
	};

	const renderMenu = () => {
		return (
			<div className="woocommerce-card__menu woocommerce-card__header-item">
				<EllipsisMenu
					className={ id }
					label={ __( 'Task List Options', 'woocommerce' ) }
					renderContent={ ( {
						onToggle,
					}: {
						onToggle: () => void;
					} ) => (
						<div className="woocommerce-task-card__section-controls">
							<Button
								onClick={ () => {
									if ( incompleteTasks.length > 0 ) {
										setShowDismissModal( true );
										onToggle();
									} else {
										hideTasks();
									}
								} }
							>
								{ __( 'Hide this', 'woocommerce' ) }
							</Button>
						</div>
					) }
				/>
			</div>
		);
	};

	let selectedHeaderCard = visibleTasks.find(
		( listTask ) => listTask.isComplete === false
	);

	// If nothing is selected, default to the last task since everything is completed.
	if ( ! selectedHeaderCard ) {
		selectedHeaderCard = visibleTasks[ visibleTasks.length - 1 ];
	}

	const getTaskStartedCount = ( taskId: string ) => {
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks;
		if ( ! trackedStartedTasks || ! trackedStartedTasks[ taskId ] ) {
			return 0;
		}
		return trackedStartedTasks[ taskId ];
	};

	// @todo This would be better as a task endpoint that handles updating the count.
	const updateTrackStartedCount = ( taskId: string ) => {
		const newCount = getTaskStartedCount( taskId ) + 1;
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks || {};

		visitedTask( taskId );
		userPreferences.updateUserPreferences( {
			task_list_tracked_started_tasks: {
				...( trackedStartedTasks || {} ),
				[ taskId ]: newCount,
			},
		} );
	};

	const trackClick = ( task: TaskType ) => {
		recordEvent( `${ listEventPrefix }click`, {
			task_name: task.id,
		} );
	};

	const goToTask = ( task: TaskType ) => {
		trackClick( task );
		if ( ! task.isComplete ) {
			updateTrackStartedCount( task.id );
		}
		if ( task.actionUrl ) {
			if ( task.actionUrl.startsWith( 'http' ) ) {
				window.location.href = task.actionUrl;
			} else {
				( getHistory() as History ).push(
					getNewPath( {}, task.actionUrl, {} )
				);
			}
			return;
		}
		updateQueryString( { task: task.id } );
	};

	const showTaskHeader = ( task: TaskType ) => {
		if ( taskHeaders[ task.id ] ) {
			setHeaderData( {
				task,
				goToTask: () => goToTask( task ),
				trackClick: () => trackClick( task ),
			} );
			setActiveTaskId( task.id );
		}
	};

	useEffect( () => {
		if ( selectedHeaderCard ) {
			showTaskHeader( selectedHeaderCard );
		}
	}, [ selectedHeaderCard ] );

	if ( ! visibleTasks.length ) {
		return <div className="woocommerce-task-dashboard__container"></div>;
	}

	if ( isComplete && keepCompletedTaskList !== 'yes' ) {
		return (
			<>
				{ cesHeader ? (
					<TaskListCompletedHeaderWithCES
						hideTasks={ hideTasks }
						keepTasks={ keepTasks }
						showCES={ showCESFeedback }
					/>
				) : (
					<TaskListCompleted
						hideTasks={ hideTasks }
						keepTasks={ keepTasks }
						twoColumns={ false }
					/>
				) }
			</>
		);
	}

	return (
		<>
			{ showDismissModal && (
				<DismissModal
					showDismissModal={ showDismissModal }
					setShowDismissModal={ setShowDismissModal }
					hideTasks={ hideTasks }
				/>
			) }
			{ displayProgressHeader ? (
				<ProgressHeader taskListId={ id } />
			) : null }
			<div
				className={ classnames(
					`woocommerce-task-dashboard__container two-column-experiment woocommerce-task-list__${ id }`,
					{ 'two-columns': twoColumns !== false }
				) }
			>
				<Card
					size="large"
					className="woocommerce-task-card woocommerce-homescreen-card"
				>
					<div className="wooocommerce-task-card__header-container">
						<div className="wooocommerce-task-card__header">
							{ headerData?.task &&
								createElement(
									taskHeaders[ headerData.task.id ],
									headerData
								) }
						</div>
						{ ! displayProgressHeader && renderMenu() }
					</div>
					<List animation="custom">
						{ visibleTasks.map( ( task, index ) => {
							return (
								<TaskListItemTwoColumn
									key={ task.id }
									taskIndex={ ++index }
									activeTaskId={ activeTaskId }
									task={ task }
									goToTask={ () => goToTask( task ) }
									trackClick={ () => trackClick( task ) }
								/>
							);
						} ) }
					</List>
				</Card>
			</div>
		</>
	);
};

export default TaskList;
