export function getCurrentlyRunning( state ) {
	return state.currentlyRunning;
}

export function getMessages( state ) {
	return state.messages;
}

export function getStatus( state ) {
	return state.status;
}

export function getCommandParams( state ) {
	return state.params;
}

export function getCronJobs( state ) {
	return state.cronJobs;
}
