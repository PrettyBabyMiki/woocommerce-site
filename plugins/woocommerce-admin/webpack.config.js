/**
 * External dependencies
 */
const { get } = require( 'lodash' );
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const CustomTemplatedPathPlugin = require( '@wordpress/custom-templated-path-webpack-plugin' );
const BundleAnalyzerPlugin =
	require( 'webpack-bundle-analyzer' ).BundleAnalyzerPlugin;
const MomentTimezoneDataPlugin = require( 'moment-timezone-data-webpack-plugin' );
const ForkTsCheckerWebpackPlugin = require( 'fork-ts-checker-webpack-plugin' );

/**
 * Internal dependencies
 */
const UnminifyWebpackPlugin = require( './unminify' );
const {
	webpackConfig: styleConfig,
} = require( '@woocommerce/internal-style-build' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '../../packages/js/dependency-extraction-webpack-plugin/src/index' );

const NODE_ENV = process.env.NODE_ENV || 'development';
const WC_ADMIN_PHASE = process.env.WC_ADMIN_PHASE || 'development';

const wcAdminPackages = [
	'components',
	'csv-export',
	'currency',
	'customer-effort-score',
	'date',
	'experimental',
	'explat',
	'navigation',
	'notices',
	'number',
	'data',
	'tracks',
	'onboarding',
];
// wpAdminScripts are loaded on wp-admin pages outside the context of WooCommerce Admin
// See ./client/wp-admin-scripts/README.md for more details
const wpAdminScripts = [
	'marketing-coupons',
	'navigation-opt-out',
	'onboarding-homepage-notice',
	'onboarding-product-notice',
	'onboarding-product-import-notice',
	'onboarding-tax-notice',
	'print-shipping-label-banner',
	'beta-features-tracking-modal',
	'payment-method-promotions',
	'onboarding-load-sample-products-notice',
	'product-tracking',
	'add-term-tracking',
	'attributes-tracking',
	'category-tracking',
	'tags-tracking',
	'product-tour',
	'wc-addons-tour',
];
const getEntryPoints = () => {
	const entryPoints = {
		app: './client/index.js',
	};
	wcAdminPackages.forEach( ( name ) => {
		entryPoints[ name ] = `../../packages/js/${ name }`;
	} );
	wpAdminScripts.forEach( ( name ) => {
		entryPoints[ name ] = `./client/wp-admin-scripts/${ name }`;
	} );
	return entryPoints;
};

// WordPress.org’s translation infrastructure ignores files named “.min.js” so we need to name our JS files without min when releasing the plugin.
const outputSuffix = WC_ADMIN_PHASE === 'core' ? '' : '.min';

const webpackConfig = {
	mode: NODE_ENV,
	entry: getEntryPoints(),
	output: {
		filename: ( data ) => {
			// Output wpAdminScripts to wp-admin-scripts folder
			// See https://github.com/woocommerce/woocommerce-admin/pull/3061
			return wpAdminScripts.includes( data.chunk.name )
				? `wp-admin-scripts/[name]${ outputSuffix }.js`
				: `[name]/index${ outputSuffix }.js`;
		},
		chunkFilename: `chunks/[name]${ outputSuffix }.js`,
		path: path.join( __dirname, '/../woocommerce/assets/client/admin' ),
		library: {
			// Expose the exports of entry points so we can consume the libraries in window.wc.[modulename] with WooCommerceDependencyExtractionWebpackPlugin.
			name: [ 'wc', '[modulename]' ],
			type: 'window',
		},
		// A unique name of the webpack build to avoid multiple webpack runtimes to conflict when using globals.
		uniqueName: '__wcAdmin_webpackJsonp',
	},
	module: {
		rules: [
			{
				test: /\.(t|j)sx?$/,
				parser: {
					// Disable AMD to fix an issue where underscore and lodash where clashing
					// See https://github.com/woocommerce/woocommerce-admin/pull/1004 and https://github.com/Automattic/woocommerce-services/pull/1522
					amd: false,
				},
				exclude: [
					// Exclude node_modules/.pnpm
					/node_modules(\/|\\)\.pnpm(\/|\\)/,
				],
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							'@wordpress/babel-preset-default',
							[
								'@babel/preset-env',
								{
									// Add polyfills such as Array.flat based on their usage in the code
									// See https://github.com/woocommerce/woocommerce-admin/pull/6411/
									corejs: '3',
									useBuiltIns: 'usage',
								},
							],
							[ '@babel/preset-typescript' ],
						],
						plugins: [ '@babel/plugin-proposal-class-properties' ],
					},
				},
			},
			{ test: /\.md$/, use: 'raw-loader' },
			{
				test: /\.(png|jpe?g|gif|svg|eot|ttf|woff|woff2)$/,
				type: 'asset',
			},
			...styleConfig.rules,
		],
	},
	resolve: {
		fallback: {
			// Reduce bundle size by omitting Node crypto library.
			// See https://github.com/woocommerce/woocommerce-admin/pull/5768
			crypto: 'empty',
			// Ignore fs, path to skip resolve errors for @automattic/calypso-config
			fs: false,
			path: false,
		},
		extensions: [ '.json', '.js', '.jsx', '.ts', '.tsx' ],
		alias: {
			'~': path.resolve( __dirname + '/client' ),
		},
	},
	plugins: [
		...styleConfig.plugins,
		// Runs TypeScript type checker on a separate process.
		! process.env.STORYBOOK && new ForkTsCheckerWebpackPlugin(),
		new CustomTemplatedPathPlugin( {
			modulename( outputPath, data ) {
				const entryName = get( data, [ 'chunk', 'name' ] );
				if ( entryName ) {
					// Convert the dash-case name to a camel case module name.
					// For example, 'csv-export' -> 'csvExport'
					return entryName.replace( /-([a-z])/g, ( match, letter ) =>
						letter.toUpperCase()
					);
				}
				return outputPath;
			},
		} ),
		// The package build process doesn't handle extracting CSS from JS files, so we copy them separately.
		new CopyWebpackPlugin( {
			patterns: wcAdminPackages.map( ( packageName ) => ( {
				from: `../../packages/js/${ packageName }/build-style/*.css`,
				to: `./${ packageName }/[name][ext]`,
				noErrorOnMissing: true,
				// Overwrites files already in compilation.assets to ensure we use the assets from the build-style.
				// This is required for @woocommerce/component to use @automattic/* packages because scss styles from @automattic/* packages will be automatically generated by mini-css-extract-plugin with the same output name.
				force: true,
			} ) ),
		} ),

		// We reuse this Webpack setup for Storybook, where we need to disable dependency extraction.
		! process.env.STORYBOOK &&
			new WooCommerceDependencyExtractionWebpackPlugin( {
				requestToExternal( request ) {
					if ( request === '@wordpress/components/build/ui' ) {
						// The external wp.components does not include ui components, so we need to skip requesting to external here.
						return null;
					}
				},
			} ),
		// Reduces data for moment-timezone.
		new MomentTimezoneDataPlugin( {
			// This strips out timezone data before the year 2000 to make a smaller file.
			startYear: 2000,
		} ),
		process.env.ANALYZE && new BundleAnalyzerPlugin(),
		// We only want to generate unminified files in the development phase.
		WC_ADMIN_PHASE === 'development' &&
			// Generate unminified files to load the unminified version when `define( 'SCRIPT_DEBUG', true );` is set in wp-config.
			new UnminifyWebpackPlugin( {
				test: /\.js($|\?)/i,
				mainEntry: 'app/index.min.js',
			} ),
	].filter( Boolean ),
	optimization: {
		minimize: NODE_ENV !== 'development',
		splitChunks: {
			// Not to generate chunk names because it caused a stressful workflow when deploying the plugin to WP.org
			// See https://github.com/woocommerce/woocommerce-admin/pull/5229
			name: false,
		},
	},
};

// Use the source map if we're in development mode, .
if (
	webpackConfig.mode === 'development' ||
	WC_ADMIN_PHASE === 'development'
) {
	webpackConfig.devtool = process.env.SOURCEMAP || 'source-map';
}

module.exports = webpackConfig;
