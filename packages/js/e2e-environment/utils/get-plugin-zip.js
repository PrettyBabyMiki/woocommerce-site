const path = require( 'path' );
const getAppRoot = require( './app-root' );
const fs = require( 'fs' );
const mkdirp = require( 'mkdirp' );
const request = require( 'request' );
const StreamZip = require( 'node-stream-zip' );

/**
 * Upload a plugin zip from a remote location, such as a GitHub URL or other hosted location.
 *
 * @param {string} fileUrl The URL where the zip file is located.
 * @param {string} authorizationToken Authorization token used to authenticate with the GitHub API if required.
 * @return {string} The path where the zip file is located.
 */
const getRemotePluginZip = async ( fileUrl, authorizationToken = '' ) => {
	const appPath = getAppRoot();
	const savePath = path.resolve(
		appPath,
		'plugins/woocommerce/tests/e2e/plugins'
	);
	mkdirp.sync( savePath );

	// Pull the version from the end of the URL and add .zip
	const fileName = fileUrl.split( '/' ).pop() + '.zip';
	let filePath = path.join( savePath, fileName );

	// First, download the zip file
	await downloadZip( fileUrl, filePath, authorizationToken );

	// Check for a nested zip and update the filepath
	filePath = await checkNestedZip( filePath, savePath );

	return filePath;
};

/**
 * Get the latest release zip for a plugin from a GiHub repository.
 *
 * @param {string} repository The repository owner and name. For example: `woocommerce/woocommerce`.
 * @param {string} authorizationToken Authorization token used to authenticate with the GitHub API if required.
 * @param {boolean} getPrerelease Flag on whether to get a prelease or not.
 * @param {number} perPage Limit of entries returned from the latest releases list, defaults to 3.
 * @return {Promise<string>}} Returns the URL for the release zip file.
 */
const getLatestReleaseZipUrl = async (
	repository,
	authorizationToken = '',
	getPrerelease = false,
	perPage = 3
) => {
	let requesturl;

	if ( getPrerelease ) {
		requesturl = `https://api.github.com/repos/${ repository }/releases?per_page=${ perPage }`;
	} else {
		requesturl = `https://api.github.com/repos/${ repository }/releases/latest`;
	}

	const options = {
		url: requesturl,
		method: 'GET',
		json: true,
		headers: { 'user-agent': 'node.js' },
	};

	// If provided with a token, use it for authorization
	if ( authorizationToken ) {
		options.headers.Authorization = `token ${ authorizationToken }`;
	}

	// Wrap in a promise to make the request async
	return new Promise( function ( resolve, reject ) {
		request.get( options, function ( err, resp, body ) {
			if ( err ) {
				reject( err );
			} else if ( getPrerelease ) {
				// Loop until we find the first pre-release, then return it.
				body.forEach( ( release ) => {
					if ( release.prerelease ) {
						resolve( release.zipball_url );
					}
				} );
			} else {
				resolve( body.zipball_url );
			}
		} );
	} );
};

/**
 * Check the zip file for any nested zips. If one is found, extract it.
 *
 * @param {string} zipFilePath The location of the zip file.
 * @param {string} savePath The location where to save a nested zip if found.
 * @return {string} The path where the zip file is located.
 */
const checkNestedZip = async ( zipFilePath, savePath ) => {
	const zip = new StreamZip.async( { file: zipFilePath } );
	const entries = await zip.entries();

	for ( const entry of Object.values( entries ) ) {
		if ( entry.name.match( /\.zip/ ) ) {
			await zip.extract( null, savePath );
			await zip.close();
			return path.join( savePath, entry.name );
		}
	}

	return zipFilePath;
};

/**
 * Download the zip file from a remote location.
 *
 * @param {string} fileUrl The URL where the zip file is located.
 * @param {string} downloadPath The location where to download the zip to.
 * @param {string} authorizationToken Authorization token used to authenticate with the GitHub API if required.
 * @return {Promise<void>}
 */
const downloadZip = async ( fileUrl, downloadPath, authorizationToken ) => {
	const options = {
		url: fileUrl,
		method: 'GET',
		encoding: null,
		headers: { 'user-agent': 'node.js' },
	};

	// If provided with a token, use it for authorization
	if ( authorizationToken ) {
		options.headers.Authorization = `token ${ authorizationToken }`;
	}

	// Wrap in a promise to make the request async
	return new Promise( function ( resolve, reject ) {
		request
			.get( options, function ( err, resp, body ) {
				if ( err ) {
					reject( err );
				} else {
					resolve( body );
				}
			} )
			.pipe( fs.createWriteStream( downloadPath ) );
	} );
};

/**
 * Delete the downloaded plugin files.
 */
const deleteDownloadedPluginFiles = async () => {
	const appPath = getAppRoot();
	const pluginSavePath = path.resolve(
		appPath,
		'plugins/woocommerce/tests/e2e/plugins'
	);

	fs.readdir( pluginSavePath, ( err, files ) => {
		if ( err ) throw err;

		for ( const file of files ) {
			fs.unlink( path.join( pluginSavePath, file ), ( error ) => {
				if ( error ) throw error;
			} );
		}
	} );
};

module.exports = {
	getRemotePluginZip,
	getLatestReleaseZipUrl,
	checkNestedZip,
	downloadZip,
	deleteDownloadedPluginFiles,
};
