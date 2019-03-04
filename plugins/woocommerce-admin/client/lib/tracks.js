/** @format */

/**
 * Record an event to Tracks
 *
 * @param {String} eventName The name of the event to record, always prefixed with wca_
 * @param {Object} eventProperties event properties to include in the event
 */

export function recordEvent( eventName, eventProperties ) {
	if (
		! window.wcTracks ||
		'function' !== typeof window.wcTracks.recordEvent ||
		'development' === process.env.NODE_ENV
	) {
		return false;
	}

	window.wcTracks.recordEvent( eventName, eventProperties );
}

/**
 * Record a page view to Tracks
 *
 * @param {String} path the page/path to record a page view for
 */

export function recordPageView( path ) {
	if ( ! path ) {
		return;
	}
	recordEvent( 'page_view', { path } );
}
