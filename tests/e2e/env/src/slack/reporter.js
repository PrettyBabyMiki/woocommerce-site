const { createReadStream } = require( 'fs' );
const { WebClient, ErrorCode } = require( '@slack/web-api' );
const {
	GITHUB_ACTIONS,
	GITHUB_REF,
	GITHUB_SHA,
	GITHUB_REPOSITORY,
	GITHUB_RUN_ID,
	TRAVIS_PULL_REQUEST_BRANCH,
	TRAVIS_COMMIT,
	TRAVIS_BUILD_WEB_URL,
	E2E_SLACK_TOKEN,
	E2E_SLACK_CHANNEL,
	WC_E2E_SCREENSHOTS,
} = process.env;

let web;

/**
 * Initialize the Slack web client.
 *
 * @returns {WebClient}
 */
const initializeWeb = () => {
	if ( ! web ) {
		web = new WebClient( E2E_SLACK_TOKEN );
	}
	return web;
};

/**
 * Initialize Slack parameters if tests are running in CI.
 * @returns {Object|boolean}
 */
const initializeSlack = () => {
	if ( ! WC_E2E_SCREENSHOTS || ! E2E_SLACK_TOKEN ) {
		return false;
	}
	if ( ! GITHUB_ACTIONS && ! TRAVIS_PULL_REQUEST_BRANCH ) {
		return false;
	}
	// Build PR info
	if ( GITHUB_ACTIONS ) {
		const refArray = GITHUB_REF.split( '/' );
		const branch = refArray.pop();
		return {
			branch,
			commit: GITHUB_SHA,
			webUrl: `https://github.com/${ GITHUB_REPOSITORY }/actions/runs/${ GITHUB_RUN_ID }`,
		};
	}

	return {
		branch: TRAVIS_PULL_REQUEST_BRANCH,
		commit: TRAVIS_COMMIT,
		webUrl: TRAVIS_BUILD_WEB_URL,
	};
};

/**
 * Post a message to a Slack channel for a failed test.
 *
 * @param testName
 * @returns {Promise<void>}
 */
export async function sendFailedTestMessageToSlack( testName ) {
	const pr = initializeSlack();
	if ( ! pr ) {
		return;
	}
	const web = initializeWeb();

	try {
		// For details, see: https://api.slack.com/methods/chat.postMessage
		await web.chat.postMessage({
			channel: E2E_SLACK_CHANNEL,
			token: E2E_SLACK_TOKEN,
			text: `Test failed on *${ pr.branch }* branch. \n
            The commit this build is testing is *${ pr.commit }*. \n
            The name of the test that failed: *${ testName }*. \n
            See screenshot of the failed test below. *Build log* could be found here: ${ pr.webUrl }`,
		});
	} catch ( error ) {
		// Check the code property and log the response
		if ( error.code === ErrorCode.PlatformError || error.code === ErrorCode.RequestError ||
			error.code === ErrorCode.RateLimitedError || error.code === ErrorCode.HTTPError ) {
			console.log( error.data );
		} else {
			// Some other error, oh no!
			console.log( 'The error occurred does not match an error we are checking for in this block.' );
		}
	}
}

/**
 * Post a screenshot to a Slack channel for a failed test.
 * @param screenshotOfFailedTest
 * @returns {Promise<void>}
 */
export async function sendFailedTestScreenshotToSlack( screenshotOfFailedTest ) {
	const pr = initializeSlack();
	if ( ! pr ) {
		return;
	}
	const web = initializeWeb();
	const filename = 'screenshot_of_failed_test.png';

	try {
		// For details, see: https://api.slack.com/methods/files.upload
		await web.files.upload({
			channels: E2E_SLACK_CHANNEL,
			token: E2E_SLACK_TOKEN,
			filename,
			file: createReadStream( screenshotOfFailedTest ),
		});
	} catch ( error ) {
		// Check the code property and log the response
		if ( error.code === ErrorCode.PlatformError || error.code === ErrorCode.RequestError ||
			error.code === ErrorCode.RateLimitedError || error.code === ErrorCode.HTTPError ) {
			console.log( error.data );
		} else {
			// Some other error, oh no!
			console.log( 'The error occurred does not match an error we are checking for in this block.' );
		}
	}
}
