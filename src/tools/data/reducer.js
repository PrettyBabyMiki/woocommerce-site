/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	currentlyRunning: {},
	errorMessages: [],
	cronJobs: false,
	isEmailDisabled: '',
	messages: {},
	params: [],
	status: '',
	dbUpdateVersions: [],
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.ADD_MESSAGE:
			if ( ! action.status ) {
				action.status = 'info';
			}
			return {
				...state,
				messages: {
					...state.messages,
					[ action.source ]: {
						message: action.message,
						status: action.status,
					},
				},
			};
		case TYPES.REMOVE_MESSAGE:
			const messages = { ...state.messages };
			delete messages[ action.source ];
			return {
				...state,
				messages,
			};
		case TYPES.SET_STATUS:
			return {
				...state,
				status: action.status,
			};
		case TYPES.ADD_CURRENTLY_RUNNING:
			return {
				...state,
				currentlyRunning: {
					...state,
					[ action.command ]: true,
				},
			};
		case TYPES.REMOVE_CURRENTLY_RUNNING:
			return {
				...state,
				currentlyRunning: {
					...state,
					[ action.command ]: false,
				},
			};
		case TYPES.SET_CRON_JOBS:
			return {
				...state,
				cronJobs: action.cronJobs,
			};
		case TYPES.IS_EMAIL_DISABLED:
			return {
				...state,
				isEmailDisabled: action.isEmailDisabled,
			};
		case TYPES.ADD_COMMAND_PARAMS:
			return {
				...state,
				params: {
					[ action.source ]: action.params,
				},
			};
		case TYPES.SET_DB_UPDATE_VERSIONS:
			return {
				...state,
				dbUpdateVersions: action.versions,
			};
		default:
			return state;
	}
};

export default reducer;
