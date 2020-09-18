/** @format */
const { jestPuppeteerConfig } = require( '@automattic/puppeteer-utils' );

let puppeteerConfig;

if ( 'no' == global.process.env.node_config_dev ) {
	puppeteerConfig = {
		launch: {
			// Required for the logged out and logged in tests so they don't share app state/token.
			browserContext: 'incognito',
		},
	};
} else {
	puppeteerConfig = {
		launch: {
			...jestPuppeteerConfig.launch,
			ignoreHTTPSErrors: true,
			args: [ '--window-size=1920,1080', '--user-agent=chrome' ],
			devtools: true,
			headless: false,
			defaultViewport: {
				width: 1280,
				height: 800,
			},
		},
	};
}

module.exports = puppeteerConfig;
