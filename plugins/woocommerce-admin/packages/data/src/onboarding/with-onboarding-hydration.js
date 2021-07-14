/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { createElement, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

export const withOnboardingHydration = ( data ) => {
	let hydratedProfileItems = false;
	let hydratedTasksStatus = false;

	return createHigherOrderComponent(
		( OriginalComponent ) => ( props ) => {
			const onboardingRef = useRef( data );

			useSelect( ( select, registry ) => {
				if ( ! onboardingRef.current ) {
					return;
				}

				const { isResolving, hasFinishedResolution } = select(
					STORE_NAME
				);
				const {
					startResolution,
					finishResolution,
					setProfileItems,
					setTasksStatus,
				} = registry.dispatch( STORE_NAME );

				const { profileItems, tasksStatus } = onboardingRef.current;

				if (
					profileItems &&
					! hydratedProfileItems &&
					! isResolving( 'getProfileItems', [] ) &&
					! hasFinishedResolution( 'getProfileItems', [] )
				) {
					startResolution( 'getProfileItems', [] );
					setProfileItems( profileItems, true );
					finishResolution( 'getProfileItems', [] );

					hydratedProfileItems = true;
				}

				if (
					tasksStatus &&
					! hydratedTasksStatus &&
					! isResolving( 'getTasksStatus', [] ) &&
					! hasFinishedResolution( 'getTasksStatus', [] )
				) {
					startResolution( 'getTasksStatus', [] );
					setTasksStatus( tasksStatus, true );
					finishResolution( 'getTasksStatus', [] );

					hydratedTasksStatus = true;
				}
			}, [] );

			return <OriginalComponent { ...props } />;
		},
		'withOnboardingHydration'
	);
};
