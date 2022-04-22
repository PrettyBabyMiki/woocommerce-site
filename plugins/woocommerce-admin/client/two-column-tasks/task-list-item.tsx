/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	getHistory,
	getNewPath,
	updateQueryString,
} from '@woocommerce/navigation';
import { OPTIONS_STORE_NAME, TaskType } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { TaskItem, useSlot } from '@woocommerce/experimental';
import { useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import classnames from 'classnames';
import { History } from 'history';

export type TaskListItemProps = {
	task: TaskType;
	eventPrefix?: string;
};

export const TaskListItem: React.FC< TaskListItemProps > = ( {
	task,
	eventPrefix,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const {
		dismissTask,
		undoDismissTask,
		snoozeTask,
		undoSnoozeTask,
	} = useDispatch( OPTIONS_STORE_NAME );

	const slot = useSlot(
		`woocommerce_onboarding_task_list_item_${ task.id }`
	);
	const hasFills = Boolean( slot?.fills?.length );

	const trackClick = () => {
		recordEvent( `${ eventPrefix }_click`, {
			task_name: task.id,
		} );
	};

	const onTaskSelected = () => {
		trackClick();

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

		window.document.documentElement.scrollTop = 0;
		updateQueryString( { task: task.id } );
	};

	const onDismiss = useCallback( () => {
		dismissTask( task.id );
		createNotice( 'success', __( 'Task dismissed' ), {
			actions: [
				{
					label: __( 'Undo', 'woocommerce' ),
					onClick: () => undoDismissTask( task.id ),
				},
			],
		} );
	}, [ task.id ] );

	const onSnooze = useCallback( () => {
		snoozeTask( task.id );
		createNotice(
			'success',
			__( 'Task postponed until tomorrow', 'woocommerce' ),
			{
				actions: [
					{
						label: __( 'Undo', 'woocommerce' ),
						onClick: () => undoSnoozeTask( task.id ),
					},
				],
			}
		);
	}, [ task.id ] );

	const className = classnames( 'woocommerce-task-list__item', {
		complete: task.isComplete,
		'is-disabled': task.isDisabled,
	} );

	const taskItemProps = {
		completed: task.isComplete,
		onSnooze: task.isSnoozeable && onSnooze,
		onDismiss: task.isDismissable && onDismiss,
	};

	const DefaultTaskItem = useCallback(
		( props ) => {
			const onClickActions = () => {
				if ( props.onClick ) {
					trackClick();
					return props.onClick();
				}
				return onTaskSelected();
			};
			return (
				<TaskItem
					key={ task.id }
					className={ className }
					title={ task.title }
					completed={ task.isComplete }
					expanded={ ! task.isComplete }
					additionalInfo={ task.additionalInfo }
					content={ task.content }
					onDismiss={ task.isDismissable && onDismiss }
					action={ () => {} }
					actionLabel={ task.actionLabel }
					{ ...props }
					onClick={ () => {
						if ( task.isDisabled ) {
							return;
						}
						onClickActions();
					} }
				/>
			);
		},
		[
			task.id,
			task.title,
			task.content,
			task.time,
			task.actionLabel,
			task.isComplete,
		]
	);

	return hasFills ? (
		<WooOnboardingTaskListItem.Slot
			id={ task.id }
			fillProps={ {
				defaultTaskItem: DefaultTaskItem,
				isComplete: task.isComplete,
				...taskItemProps,
			} }
		/>
	) : (
		<DefaultTaskItem />
	);
};
