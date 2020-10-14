# Dependency Extraction Webpack Plugin

Extends Wordpress [Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/master/packages/dependency-extraction-webpack-plugin) to automatically include WooCommerce dependencies in addition to WordPress dependencies.

## Installation

Install the module

```
npm install @woocommerce/dependency-extraction-webpack-plugin --save-dev
```

## Usage

Use this as you would [Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/master/packages/dependency-extraction-webpack-plugin). The API is exactly the same, except that WooCommerce packages are also handled automatically.

```js
// webpack.config.js
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

module.exports = {
	// …snip
	plugins: [ new WooCommerceDependencyExtractionWebpackPlugin() ],
};
```
