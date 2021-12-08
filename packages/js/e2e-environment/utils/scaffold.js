/**
 * External dependencies.
 */
const fs = require( 'fs' );
const readlineSync = require( 'readline-sync' );
const { resolveLocalE2ePath, resolvePackagePath } = require( './test-config' );

/**
 * Create a path relative to the local `tests/e2e` folder.
 * @param relativePath
 * @return {string}
 */
const createLocalE2ePath = ( relativePath ) => {
	let specFolderPath = '';
	const folders = [ '../../tests', '../e2e', relativePath ];
	folders.forEach( ( folder ) => {
		specFolderPath = resolveLocalE2ePath( folder );
		if ( ! fs.existsSync( specFolderPath ) ) {
			console.log( `Creating folder ${specFolderPath}` );
			fs.mkdirSync( specFolderPath );
		}
	} );

	return specFolderPath;
};

/**
 * Prompt the console for confirmation.
 *
 * @param {string} prompt Prompt for the user.
 * @param {string} choices valid responses.
 * @return {string}
 */
const confirm = ( prompt, choices ) => {
	const answer = readlineSync.keyIn( prompt, choices );
	return answer;
};

/**
 *
 * @param {string} localE2ePath Destination path
 * @param {string} packageE2ePath Source path
 * @param {string} packageName Source package. Default @woocommerce/e2e-environment package.
 * @return {boolean}
 */
const confirmLocalCopy = ( localE2ePath, packageE2ePath, packageName = '' ) => {
	const localPath = resolveLocalE2ePath( localE2ePath );
	const packagePath = resolvePackagePath( packageE2ePath, packageName );
	const confirmPrompt = `${localE2ePath} already exists. Overwrite? [Y]es/[n]o: `;

	let overwriteFiles;
	if ( fs.existsSync( localPath ) ) {
		overwriteFiles = confirm( confirmPrompt, 'ny' );
		overwriteFiles = overwriteFiles.toLowerCase();
	} else {
		overwriteFiles = 'y';
	}
	if ( overwriteFiles == 'y' ) {
		fs.copyFileSync( packagePath, localPath );
		return true;
	}

	return false;
};

/**
 * Prompt for confirmation before deleting a local E2E file.
 *
 * @param {string} localE2ePath Relative path to local E2E file.
 */
const confirmLocalDelete = ( localE2ePath ) => {
	const localPath = resolveLocalE2ePath( localE2ePath );
	const confirmPrompt = `${localE2ePath} exists. Delete? [y]es/[n]o: `;

	if ( ! fs.existsSync( localPath ) ) {
		return;
	}
	const deleteFile = confirm( confirmPrompt, 'ny' );
	if ( deleteFile == 'y' ) {
		fs.unlinkSync( localPath );
	}
};

/**
 * Get the install data for a tests package.
 *
 * @param {string} packageName npm package name
 * @return {string}
 */
const getPackageData = ( packageName ) => {
	const packageSlug = packageName.replace( '@', '' ).replace( /\//g, '.' );
	const installFiles = require( `${packageName}/installFiles` );

	return { packageSlug, ...installFiles };
};

/**
 * Install test runner and test container defaults
 */
const installDefaults = () => {
	createLocalE2ePath( 'docker' );
	console.log( 'Writing tests/e2e/docker/initialize.sh' );
	confirmLocalCopy( 'docker/initialize.sh', 'installFiles/initialize.sh' );

	createLocalE2ePath( 'config' );
	console.log( 'Writing tests/e2e/config/jest.config.js' );
	confirmLocalCopy( 'config/jest.config.js', 'installFiles/jest.config.js' );
	console.log( 'Writing tests/e2e/config/jest.setup.js' );
	confirmLocalCopy( 'config/jest.setup.js', 'installFiles/jest.setup.js' );
};

module.exports = {
	createLocalE2ePath,
	confirm,
	confirmLocalCopy,
	confirmLocalDelete,
	getPackageData,
	installDefaults,
};
