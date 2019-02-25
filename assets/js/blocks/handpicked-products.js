(function(e, a) { for(var i in a) e[i] = a[i]; }(this, /******/ (function(modules) { // webpackBootstrap
/******/ 	// install a JSONP callback for chunk loading
/******/ 	function webpackJsonpCallback(data) {
/******/ 		var chunkIds = data[0];
/******/ 		var moreModules = data[1];
/******/ 		var executeModules = data[2];
/******/
/******/ 		// add "moreModules" to the modules object,
/******/ 		// then flag all "chunkIds" as loaded and fire callback
/******/ 		var moduleId, chunkId, i = 0, resolves = [];
/******/ 		for(;i < chunkIds.length; i++) {
/******/ 			chunkId = chunkIds[i];
/******/ 			if(installedChunks[chunkId]) {
/******/ 				resolves.push(installedChunks[chunkId][0]);
/******/ 			}
/******/ 			installedChunks[chunkId] = 0;
/******/ 		}
/******/ 		for(moduleId in moreModules) {
/******/ 			if(Object.prototype.hasOwnProperty.call(moreModules, moduleId)) {
/******/ 				modules[moduleId] = moreModules[moduleId];
/******/ 			}
/******/ 		}
/******/ 		if(parentJsonpFunction) parentJsonpFunction(data);
/******/
/******/ 		while(resolves.length) {
/******/ 			resolves.shift()();
/******/ 		}
/******/
/******/ 		// add entry modules from loaded chunk to deferred list
/******/ 		deferredModules.push.apply(deferredModules, executeModules || []);
/******/
/******/ 		// run deferred modules when all chunks ready
/******/ 		return checkDeferredModules();
/******/ 	};
/******/ 	function checkDeferredModules() {
/******/ 		var result;
/******/ 		for(var i = 0; i < deferredModules.length; i++) {
/******/ 			var deferredModule = deferredModules[i];
/******/ 			var fulfilled = true;
/******/ 			for(var j = 1; j < deferredModule.length; j++) {
/******/ 				var depId = deferredModule[j];
/******/ 				if(installedChunks[depId] !== 0) fulfilled = false;
/******/ 			}
/******/ 			if(fulfilled) {
/******/ 				deferredModules.splice(i--, 1);
/******/ 				result = __webpack_require__(__webpack_require__.s = deferredModule[0]);
/******/ 			}
/******/ 		}
/******/ 		return result;
/******/ 	}
/******/
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// object to store loaded and loading chunks
/******/ 	// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 	// Promise = chunk loading, 0 = chunk loaded
/******/ 	var installedChunks = {
/******/ 		"handpicked-products": 0
/******/ 	};
/******/
/******/ 	var deferredModules = [];
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	var jsonpArray = window["webpackJsonp"] = window["webpackJsonp"] || [];
/******/ 	var oldJsonpFunction = jsonpArray.push.bind(jsonpArray);
/******/ 	jsonpArray.push = webpackJsonpCallback;
/******/ 	jsonpArray = jsonpArray.slice();
/******/ 	for(var i = 0; i < jsonpArray.length; i++) webpackJsonpCallback(jsonpArray[i]);
/******/ 	var parentJsonpFunction = oldJsonpFunction;
/******/
/******/
/******/ 	// add entry module to deferred list
/******/ 	deferredModules.push(["./assets/js/blocks/handpicked-products/index.js","editor","style","vendors"]);
/******/ 	// run deferred modules when ready
/******/ 	return checkDeferredModules();
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/blocks/handpicked-products/block.js":
/*!*******************************************************!*\
  !*** ./assets/js/blocks/handpicked-products/block.js ***!
  \*******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ \"./node_modules/@babel/runtime/helpers/defineProperty.js\");\n/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ \"./node_modules/@babel/runtime/helpers/classCallCheck.js\");\n/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ \"./node_modules/@babel/runtime/helpers/createClass.js\");\n/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ \"./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js\");\n/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ \"./node_modules/@babel/runtime/helpers/getPrototypeOf.js\");\n/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ \"./node_modules/@babel/runtime/helpers/inherits.js\");\n/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);\n/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ \"./node_modules/@babel/runtime/helpers/assertThisInitialized.js\");\n/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);\n/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/url */ \"@wordpress/url\");\n/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_9__);\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/api-fetch */ \"@wordpress/api-fetch\");\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__);\n/* harmony import */ var _wordpress_editor__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/editor */ \"@wordpress/editor\");\n/* harmony import */ var _wordpress_editor__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_editor__WEBPACK_IMPORTED_MODULE_11__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__);\n/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! classnames */ \"./node_modules/classnames/index.js\");\n/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_13__);\n/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! lodash */ \"lodash\");\n/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_14__);\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! prop-types */ \"./node_modules/prop-types/index.js\");\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_15__);\n/* harmony import */ var _utils_get_query__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../../utils/get-query */ \"./assets/js/utils/get-query.js\");\n/* harmony import */ var _components_grid_content_control__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../../components/grid-content-control */ \"./assets/js/components/grid-content-control/index.js\");\n/* harmony import */ var _components_icons__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ../../components/icons */ \"./assets/js/components/icons/index.js\");\n/* harmony import */ var _components_products_control__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ../../components/products-control */ \"./assets/js/components/products-control/index.js\");\n/* harmony import */ var _components_product_orderby_control__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ../../components/product-orderby-control */ \"./assets/js/components/product-orderby-control/index.js\");\n/* harmony import */ var _components_product_preview__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ../../components/product-preview */ \"./assets/js/components/product-preview/index.js\");\n\n\n\n\n\n\n\n\n\n/**\n * External dependencies\n */\n\n\n\n\n\n\n\n\n\n/**\n * Internal dependencies\n */\n\n\n\n\n\n\n\n/**\n * Component to handle edit mode of \"Hand-picked Products\".\n */\n\nvar ProductsBlock =\n/*#__PURE__*/\nfunction (_Component) {\n  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(ProductsBlock, _Component);\n\n  function ProductsBlock() {\n    var _this;\n\n    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, ProductsBlock);\n\n    _this = _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(ProductsBlock).apply(this, arguments));\n    _this.state = {\n      products: [],\n      loaded: false\n    };\n    _this.debouncedGetProducts = Object(lodash__WEBPACK_IMPORTED_MODULE_14__[\"debounce\"])(_this.getProducts.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this))), 200);\n    return _this;\n  }\n\n  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(ProductsBlock, [{\n    key: \"componentDidMount\",\n    value: function componentDidMount() {\n      this.getProducts();\n    }\n  }, {\n    key: \"componentDidUpdate\",\n    value: function componentDidUpdate(prevProps) {\n      var _this2 = this;\n\n      var hasChange = ['products', 'columns', 'orderby'].reduce(function (acc, key) {\n        return acc || prevProps.attributes[key] !== _this2.props.attributes[key];\n      }, false);\n\n      if (hasChange) {\n        this.debouncedGetProducts();\n      }\n    }\n  }, {\n    key: \"getProducts\",\n    value: function getProducts() {\n      var _this3 = this;\n\n      if (!this.props.attributes.products.length) {\n        // We've removed all selected products, or products haven't been selected yet.\n        this.setState({\n          products: [],\n          loaded: true\n        });\n        return;\n      }\n\n      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({\n        path: Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_9__[\"addQueryArgs\"])('/wc-pb/v3/products', Object(_utils_get_query__WEBPACK_IMPORTED_MODULE_16__[\"default\"])(this.props.attributes, this.props.name))\n      }).then(function (products) {\n        _this3.setState({\n          products: products,\n          loaded: true\n        });\n      }).catch(function () {\n        _this3.setState({\n          products: [],\n          loaded: true\n        });\n      });\n    }\n  }, {\n    key: \"getInspectorControls\",\n    value: function getInspectorControls() {\n      var _this$props = this.props,\n          attributes = _this$props.attributes,\n          setAttributes = _this$props.setAttributes;\n      var columns = attributes.columns,\n          contentVisibility = attributes.contentVisibility,\n          orderby = attributes.orderby;\n      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_editor__WEBPACK_IMPORTED_MODULE_11__[\"InspectorControls\"], {\n        key: \"inspector\"\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"PanelBody\"], {\n        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Layout', 'woocommerce'),\n        initialOpen: true\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"RangeControl\"], {\n        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Columns', 'woocommerce'),\n        value: columns,\n        onChange: function onChange(value) {\n          return setAttributes({\n            columns: value\n          });\n        },\n        min: wc_product_block_data.min_columns,\n        max: wc_product_block_data.max_columns\n      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"PanelBody\"], {\n        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Content', 'woocommerce'),\n        initialOpen: true\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_grid_content_control__WEBPACK_IMPORTED_MODULE_17__[\"default\"], {\n        settings: contentVisibility,\n        onChange: function onChange(value) {\n          return setAttributes({\n            contentVisibility: value\n          });\n        }\n      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"PanelBody\"], {\n        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Order By', 'woocommerce'),\n        initialOpen: false\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_product_orderby_control__WEBPACK_IMPORTED_MODULE_20__[\"default\"], {\n        setAttributes: setAttributes,\n        value: orderby\n      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"PanelBody\"], {\n        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Products', 'woocommerce'),\n        initialOpen: false\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_products_control__WEBPACK_IMPORTED_MODULE_19__[\"default\"], {\n        selected: attributes.products,\n        onChange: function onChange() {\n          var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];\n          var ids = value.map(function (_ref) {\n            var id = _ref.id;\n            return id;\n          });\n          setAttributes({\n            products: ids\n          });\n        }\n      })));\n    }\n  }, {\n    key: \"renderEditMode\",\n    value: function renderEditMode() {\n      var _this$props2 = this.props,\n          attributes = _this$props2.attributes,\n          debouncedSpeak = _this$props2.debouncedSpeak,\n          setAttributes = _this$props2.setAttributes;\n\n      var onDone = function onDone() {\n        setAttributes({\n          editMode: false\n        });\n        debouncedSpeak(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Showing Hand-picked Products block preview.', 'woocommerce'));\n      };\n\n      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"Placeholder\"], {\n        icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_icons__WEBPACK_IMPORTED_MODULE_18__[\"IconWidgets\"], null),\n        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Hand-picked Products', 'woocommerce'),\n        className: \"wc-block-products-grid wc-block-handpicked-products\"\n      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Display a selection of hand-picked products in a grid', 'woocommerce'), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(\"div\", {\n        className: \"wc-block-handpicked-products__selection\"\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_products_control__WEBPACK_IMPORTED_MODULE_19__[\"default\"], {\n        selected: attributes.products,\n        onChange: function onChange() {\n          var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];\n          var ids = value.map(function (_ref2) {\n            var id = _ref2.id;\n            return id;\n          });\n          setAttributes({\n            products: ids\n          });\n        }\n      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"Button\"], {\n        isDefault: true,\n        onClick: onDone\n      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Done', 'woocommerce'))));\n    }\n  }, {\n    key: \"render\",\n    value: function render() {\n      var _classnames;\n\n      var setAttributes = this.props.setAttributes;\n      var _this$props$attribute = this.props.attributes,\n          columns = _this$props$attribute.columns,\n          contentVisibility = _this$props$attribute.contentVisibility,\n          editMode = _this$props$attribute.editMode;\n      var _this$state = this.state,\n          loaded = _this$state.loaded,\n          _this$state$products = _this$state.products,\n          products = _this$state$products === void 0 ? [] : _this$state$products;\n      var hasSelectedProducts = products.length > 0;\n      var classes = classnames__WEBPACK_IMPORTED_MODULE_13___default()((_classnames = {\n        'wc-block-products-grid': true,\n        'wc-block-handpicked-products': true\n      }, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, \"cols-\".concat(columns), columns), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, 'is-loading', !loaded), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, 'is-not-found', loaded && !hasSelectedProducts), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, 'is-hidden-title', !contentVisibility.title), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, 'is-hidden-price', !contentVisibility.price), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, 'is-hidden-button', !contentVisibility.button), _classnames));\n      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"Fragment\"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_editor__WEBPACK_IMPORTED_MODULE_11__[\"BlockControls\"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"Toolbar\"], {\n        controls: [{\n          icon: 'edit',\n          title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Edit'),\n          onClick: function onClick() {\n            return setAttributes({\n              editMode: !editMode\n            });\n          },\n          isActive: editMode\n        }]\n      })), this.getInspectorControls(), editMode ? this.renderEditMode() : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(\"div\", {\n        className: classes\n      }, hasSelectedProducts ? products.map(function (product) {\n        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_product_preview__WEBPACK_IMPORTED_MODULE_21__[\"default\"], {\n          product: product,\n          key: product.id\n        });\n      }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"Placeholder\"], {\n        icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_icons__WEBPACK_IMPORTED_MODULE_18__[\"IconWidgets\"], null),\n        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Hand-picked Products', 'woocommerce')\n      }, !loaded ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"Spinner\"], null) : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('No products are selected.', 'woocommerce'))));\n    }\n  }]);\n\n  return ProductsBlock;\n}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"Component\"]);\n\nProductsBlock.propTypes = {\n  /**\n   * The attributes for this block\n   */\n  attributes: prop_types__WEBPACK_IMPORTED_MODULE_15___default.a.object.isRequired,\n\n  /**\n   * The register block name.\n   */\n  name: prop_types__WEBPACK_IMPORTED_MODULE_15___default.a.string.isRequired,\n\n  /**\n   * A callback to update attributes\n   */\n  setAttributes: prop_types__WEBPACK_IMPORTED_MODULE_15___default.a.func.isRequired,\n  // from withSpokenMessages\n  debouncedSpeak: prop_types__WEBPACK_IMPORTED_MODULE_15___default.a.func.isRequired\n};\n/* harmony default export */ __webpack_exports__[\"default\"] = (Object(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__[\"withSpokenMessages\"])(ProductsBlock));\n\n//# sourceURL=webpack:///./assets/js/blocks/handpicked-products/block.js?");

/***/ }),

/***/ "./assets/js/blocks/handpicked-products/editor.scss":
/*!**********************************************************!*\
  !*** ./assets/js/blocks/handpicked-products/editor.scss ***!
  \**********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

eval("\nvar content = __webpack_require__(/*! !../../../../node_modules/mini-css-extract-plugin/dist/loader.js!../../../../node_modules/css-loader/dist/cjs.js??ref--5-2!../../../../node_modules/postcss-loader/src!../../../../node_modules/sass-loader/lib/loader.js??ref--5-4!./editor.scss */ \"./node_modules/mini-css-extract-plugin/dist/loader.js!./node_modules/css-loader/dist/cjs.js?!./node_modules/postcss-loader/src/index.js!./node_modules/sass-loader/lib/loader.js?!./assets/js/blocks/handpicked-products/editor.scss\");\n\nif(typeof content === 'string') content = [[module.i, content, '']];\n\nvar transform;\nvar insertInto;\n\n\n\nvar options = {\"hmr\":true}\n\noptions.transform = transform\noptions.insertInto = undefined;\n\nvar update = __webpack_require__(/*! ../../../../node_modules/style-loader/lib/addStyles.js */ \"./node_modules/style-loader/lib/addStyles.js\")(content, options);\n\nif(content.locals) module.exports = content.locals;\n\nif(false) {}\n\n//# sourceURL=webpack:///./assets/js/blocks/handpicked-products/editor.scss?");

/***/ }),

/***/ "./assets/js/blocks/handpicked-products/index.js":
/*!*******************************************************!*\
  !*** ./assets/js/blocks/handpicked-products/index.js ***!
  \*******************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! classnames */ \"./node_modules/classnames/index.js\");\n/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/blocks */ \"@wordpress/blocks\");\n/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./editor.scss */ \"./assets/js/blocks/handpicked-products/editor.scss\");\n/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_editor_scss__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var _block__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./block */ \"./assets/js/blocks/handpicked-products/block.js\");\n/* harmony import */ var _utils_get_shortcode__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../utils/get-shortcode */ \"./assets/js/utils/get-shortcode.js\");\n/* harmony import */ var _components_icons__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../components/icons */ \"./assets/js/components/icons/index.js\");\n\n\n/**\n * External dependencies\n */\n\n\n\n\n/**\n * Internal dependencies\n */\n\n\n\n\n\nObject(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__[\"registerBlockType\"])('woocommerce/handpicked-products', {\n  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Hand-picked Products', 'woocommerce'),\n  icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_components_icons__WEBPACK_IMPORTED_MODULE_7__[\"IconWidgets\"], null),\n  category: 'woocommerce',\n  keywords: [Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('WooCommerce', 'woocommerce')],\n  description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Display a selection of hand-picked products in a grid.', 'woocommerce'),\n  supports: {\n    align: ['wide', 'full']\n  },\n  attributes: {\n    /**\n     * Alignment of product grid\n     */\n    align: {\n      type: 'string'\n    },\n\n    /**\n     * Number of columns.\n     */\n    columns: {\n      type: 'number',\n      default: wc_product_block_data.default_columns\n    },\n\n    /**\n     * Toggle for edit mode in the block preview.\n     */\n    editMode: {\n      type: 'boolean',\n      default: true\n    },\n\n    /**\n     * Content visibility setting\n     */\n    contentVisibility: {\n      type: 'object',\n      default: {\n        title: true,\n        price: true,\n        button: true\n      }\n    },\n\n    /**\n     * How to order the products: 'date', 'popularity', 'price_asc', 'price_desc' 'rating', 'title'.\n     */\n    orderby: {\n      type: 'string',\n      default: 'date'\n    },\n\n    /**\n     * The list of product IDs to display\n     */\n    products: {\n      type: 'array',\n      default: []\n    }\n  },\n\n  /**\n   * Renders and manages the block.\n   */\n  edit: function edit(props) {\n    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_block__WEBPACK_IMPORTED_MODULE_5__[\"default\"], props);\n  },\n\n  /**\n   * Save the block content in the post content. Block content is saved as a products shortcode.\n   *\n   * @return string\n   */\n  save: function save(props) {\n    var _props$attributes = props.attributes,\n        align = _props$attributes.align,\n        contentVisibility = _props$attributes.contentVisibility;\n    /* eslint-disable-line react/prop-types */\n\n    var classes = classnames__WEBPACK_IMPORTED_MODULE_2___default()(align ? \"align\".concat(align) : '', {\n      'is-hidden-title': !contentVisibility.title,\n      'is-hidden-price': !contentVisibility.price,\n      'is-hidden-button': !contentVisibility.button\n    });\n    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"RawHTML\"], {\n      className: classes\n    }, Object(_utils_get_shortcode__WEBPACK_IMPORTED_MODULE_6__[\"default\"])(props, 'woocommerce/handpicked-products'));\n  }\n});\n\n//# sourceURL=webpack:///./assets/js/blocks/handpicked-products/index.js?");

/***/ }),

/***/ "./assets/js/components/grid-content-control/index.js":
/*!************************************************************!*\
  !*** ./assets/js/components/grid-content-control/index.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/objectSpread */ \"./node_modules/@babel/runtime/helpers/objectSpread.js\");\n/* harmony import */ var _babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! prop-types */ \"./node_modules/prop-types/index.js\");\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);\n\n\n\n/**\n * External dependencies\n */\n\n\n\n\n/**\n * A combination of range controls for product grid layout settings.\n */\n\nvar GridContentControl = function GridContentControl(_ref) {\n  var _onChange = _ref.onChange,\n      settings = _ref.settings;\n  var button = settings.button,\n      price = settings.price,\n      title = settings.title;\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"Fragment\"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__[\"ToggleControl\"], {\n    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product title', 'woocommerce'),\n    help: title ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product title is visible.', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product title is hidden.', 'woocommerce'),\n    checked: title,\n    onChange: function onChange() {\n      return _onChange(_babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0___default()({}, settings, {\n        title: !title\n      }));\n    }\n  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__[\"ToggleControl\"], {\n    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product price', 'woocommerce'),\n    help: price ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product price is visible.', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product price is hidden.', 'woocommerce'),\n    checked: price,\n    onChange: function onChange() {\n      return _onChange(_babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0___default()({}, settings, {\n        price: !price\n      }));\n    }\n  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__[\"ToggleControl\"], {\n    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Add to Cart button', 'woocommerce'),\n    help: button ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Add to Cart button is visible.', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Add to Cart button is hidden.', 'woocommerce'),\n    checked: button,\n    onChange: function onChange() {\n      return _onChange(_babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0___default()({}, settings, {\n        button: !button\n      }));\n    }\n  }));\n};\n\nGridContentControl.propTypes = {\n  /**\n   * The current title visibility.\n   */\n  settings: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.shape({\n    button: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.bool.isRequired,\n    price: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.bool.isRequired,\n    title: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.bool.isRequired\n  }).isRequired,\n\n  /**\n   * Callback to update the layout settings.\n   */\n  onChange: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.func.isRequired\n};\n/* harmony default export */ __webpack_exports__[\"default\"] = (GridContentControl);\n\n//# sourceURL=webpack:///./assets/js/components/grid-content-control/index.js?");

/***/ }),

/***/ "./assets/js/components/icons/checkbox-checked.js":
/*!********************************************************!*\
  !*** ./assets/js/components/icons/checkbox-checked.js ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);\n\n\n/**\n * External dependencies\n */\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (function () {\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__[\"Icon\"], {\n    icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"svg\", {\n      xmlns: \"http://www.w3.org/2000/svg\",\n      width: \"24\",\n      height: \"24\",\n      viewBox: \"0 0 24 24\"\n    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"path\", {\n      fill: \"#1E8CBE\",\n      d: \"M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z\"\n    }))\n  });\n});\n\n//# sourceURL=webpack:///./assets/js/components/icons/checkbox-checked.js?");

/***/ }),

/***/ "./assets/js/components/icons/checkbox-unchecked.js":
/*!**********************************************************!*\
  !*** ./assets/js/components/icons/checkbox-unchecked.js ***!
  \**********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);\n\n\n/**\n * External dependencies\n */\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (function () {\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__[\"Icon\"], {\n    icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"svg\", {\n      xmlns: \"http://www.w3.org/2000/svg\",\n      width: \"24\",\n      height: \"24\",\n      viewBox: \"0 0 24 24\"\n    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"path\", {\n      fill: \"#6C7781\",\n      d: \"M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z\"\n    }))\n  });\n});\n\n//# sourceURL=webpack:///./assets/js/components/icons/checkbox-unchecked.js?");

/***/ }),

/***/ "./assets/js/components/icons/index.js":
/*!*********************************************!*\
  !*** ./assets/js/components/icons/index.js ***!
  \*********************************************/
/*! exports provided: IconCheckChecked, IconCheckUnchecked, IconNewReleases, IconRadioSelected, IconRadioUnselected, IconWidgets */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _checkbox_checked__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./checkbox-checked */ \"./assets/js/components/icons/checkbox-checked.js\");\n/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, \"IconCheckChecked\", function() { return _checkbox_checked__WEBPACK_IMPORTED_MODULE_0__[\"default\"]; });\n\n/* harmony import */ var _checkbox_unchecked__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./checkbox-unchecked */ \"./assets/js/components/icons/checkbox-unchecked.js\");\n/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, \"IconCheckUnchecked\", function() { return _checkbox_unchecked__WEBPACK_IMPORTED_MODULE_1__[\"default\"]; });\n\n/* harmony import */ var _new_releases__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./new-releases */ \"./assets/js/components/icons/new-releases.js\");\n/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, \"IconNewReleases\", function() { return _new_releases__WEBPACK_IMPORTED_MODULE_2__[\"default\"]; });\n\n/* harmony import */ var _radio_selected__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./radio-selected */ \"./assets/js/components/icons/radio-selected.js\");\n/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, \"IconRadioSelected\", function() { return _radio_selected__WEBPACK_IMPORTED_MODULE_3__[\"default\"]; });\n\n/* harmony import */ var _radio_unselected__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./radio-unselected */ \"./assets/js/components/icons/radio-unselected.js\");\n/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, \"IconRadioUnselected\", function() { return _radio_unselected__WEBPACK_IMPORTED_MODULE_4__[\"default\"]; });\n\n/* harmony import */ var _widgets__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./widgets */ \"./assets/js/components/icons/widgets.js\");\n/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, \"IconWidgets\", function() { return _widgets__WEBPACK_IMPORTED_MODULE_5__[\"default\"]; });\n\n// Export each icon as a named component.\n\n\n\n\n\n\n\n//# sourceURL=webpack:///./assets/js/components/icons/index.js?");

/***/ }),

/***/ "./assets/js/components/icons/new-releases.js":
/*!****************************************************!*\
  !*** ./assets/js/components/icons/new-releases.js ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);\n\n\n/**\n * External dependencies\n */\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (function () {\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__[\"Icon\"], {\n    className: \"material-icon\",\n    icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"svg\", {\n      xmlns: \"http://www.w3.org/2000/svg\",\n      width: \"24\",\n      height: \"24\",\n      viewBox: \"0 0 24 24\"\n    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"path\", {\n      d: \"M0 0h24v24H0z\",\n      fill: \"none\"\n    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"path\", {\n      d: \"M23 12l-2.44-2.78.34-3.68-3.61-.82-1.89-3.18L12 3 8.6 1.54 6.71 4.72l-3.61.81.34 3.68L1 12l2.44 2.78-.34 3.69 3.61.82 1.89 3.18L12 21l3.4 1.46 1.89-3.18 3.61-.82-.34-3.68L23 12zm-10 5h-2v-2h2v2zm0-4h-2V7h2v6z\"\n    }))\n  });\n});\n\n//# sourceURL=webpack:///./assets/js/components/icons/new-releases.js?");

/***/ }),

/***/ "./assets/js/components/icons/radio-selected.js":
/*!******************************************************!*\
  !*** ./assets/js/components/icons/radio-selected.js ***!
  \******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);\n\n\n/**\n * External dependencies\n */\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (function () {\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__[\"Icon\"], {\n    icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"svg\", {\n      xmlns: \"http://www.w3.org/2000/svg\",\n      width: \"24\",\n      height: \"24\",\n      viewBox: \"0 0 24 24\"\n    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"path\", {\n      fill: \"#1E8CBE\",\n      d: \"M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zm0-5C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z\"\n    }))\n  });\n});\n\n//# sourceURL=webpack:///./assets/js/components/icons/radio-selected.js?");

/***/ }),

/***/ "./assets/js/components/icons/radio-unselected.js":
/*!********************************************************!*\
  !*** ./assets/js/components/icons/radio-unselected.js ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);\n\n\n/**\n * External dependencies\n */\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (function () {\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__[\"Icon\"], {\n    icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"svg\", {\n      xmlns: \"http://www.w3.org/2000/svg\",\n      width: \"24\",\n      height: \"24\",\n      viewBox: \"0 0 24 24\"\n    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"path\", {\n      fill: \"#6C7781\",\n      d: \"M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z\"\n    }))\n  });\n});\n\n//# sourceURL=webpack:///./assets/js/components/icons/radio-unselected.js?");

/***/ }),

/***/ "./assets/js/components/icons/widgets.js":
/*!***********************************************!*\
  !*** ./assets/js/components/icons/widgets.js ***!
  \***********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);\n\n\n/**\n * External dependencies\n */\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (function () {\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__[\"Icon\"], {\n    className: \"material-icon\",\n    icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"svg\", {\n      xmlns: \"http://www.w3.org/2000/svg\",\n      width: \"24\",\n      height: \"24\",\n      viewBox: \"0 0 24 24\"\n    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"path\", {\n      d: \"M13 13v8h8v-8h-8zM3 21h8v-8H3v8zM3 3v8h8V3H3zm13.66-1.31L11 7.34 16.66 13l5.66-5.66-5.66-5.65z\"\n    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"path\", {\n      d: \"M0 0h24v24H0z\",\n      fill: \"none\"\n    }))\n  });\n});\n\n//# sourceURL=webpack:///./assets/js/components/icons/widgets.js?");

/***/ }),

/***/ "./assets/js/components/product-orderby-control/index.js":
/*!***************************************************************!*\
  !*** ./assets/js/components/product-orderby-control/index.js ***!
  \***************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! prop-types */ \"./node_modules/prop-types/index.js\");\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_3__);\n\n\n/**\n * External dependencies\n */\n\n\n\n/**\n * A pre-configured SelectControl for product orderby settings.\n */\n\nvar ProductOrderbyControl = function ProductOrderbyControl(_ref) {\n  var value = _ref.value,\n      setAttributes = _ref.setAttributes;\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__[\"SelectControl\"], {\n    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Order products by', 'woocommerce'),\n    value: value,\n    options: [{\n      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Newness - newest first', 'woocommerce'),\n      value: 'date'\n    }, {\n      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Price - low to high', 'woocommerce'),\n      value: 'price_asc'\n    }, {\n      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Price - high to low', 'woocommerce'),\n      value: 'price_desc'\n    }, {\n      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Rating - highest first', 'woocommerce'),\n      value: 'rating'\n    }, {\n      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Sales - most first', 'woocommerce'),\n      value: 'popularity'\n    }, {\n      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Title - alphabetical', 'woocommerce'),\n      value: 'title'\n    }, {\n      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Menu Order', 'woocommerce'),\n      value: 'menu_order'\n    }],\n    onChange: function onChange(orderby) {\n      return setAttributes({\n        orderby: orderby\n      });\n    }\n  });\n};\n\nProductOrderbyControl.propTypes = {\n  /**\n   * Callback to update the order setting.\n   */\n  setAttributes: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.func.isRequired,\n\n  /**\n   * The selected order setting.\n   */\n  value: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.string.isRequired\n};\n/* harmony default export */ __webpack_exports__[\"default\"] = (ProductOrderbyControl);\n\n//# sourceURL=webpack:///./assets/js/components/product-orderby-control/index.js?");

/***/ }),

/***/ "./assets/js/components/product-preview/index.js":
/*!*******************************************************!*\
  !*** ./assets/js/components/product-preview/index.js ***!
  \*******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! prop-types */ \"./node_modules/prop-types/index.js\");\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./style.scss */ \"./assets/js/components/product-preview/style.scss\");\n/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_3__);\n\n\n/**\n * External dependencies\n */\n\n\n/**\n * Internal dependencies\n */\n\n\n/**\n * Display a preview for a given product.\n */\n\nvar ProductPreview = function ProductPreview(_ref) {\n  var product = _ref.product;\n  var _wc_product_block_dat = wc_product_block_data,\n      placeholderImgSrc = _wc_product_block_dat.placeholderImgSrc;\n  /* eslint-disable-line camelcase */\n\n  var image = null;\n\n  if (product.images.length) {\n    image = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"img\", {\n      src: product.images[0].src,\n      alt: \"\"\n    });\n  } else {\n    image = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"img\", {\n      src: placeholderImgSrc,\n      alt: \"\"\n    });\n  }\n\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"div\", {\n    className: \"wc-product-preview\"\n  }, image, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"div\", {\n    className: \"wc-product-preview__title\"\n  }, product.name), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"div\", {\n    className: \"wc-product-preview__price\",\n    dangerouslySetInnerHTML: {\n      __html: product.price_html\n    }\n  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"span\", {\n    className: \"wp-block-button\"\n  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"span\", {\n    className: \"wc-product-preview__add-to-cart wp-block-button__link\"\n  }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Add to cart', 'woocommerce'))));\n};\n\nProductPreview.propTypes = {\n  /**\n   * The product object as returned from the API.\n   */\n  product: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.shape({\n    id: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.number,\n    images: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.array,\n    name: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string,\n    price_html: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string\n  }).isRequired\n};\n/* harmony default export */ __webpack_exports__[\"default\"] = (ProductPreview);\n\n//# sourceURL=webpack:///./assets/js/components/product-preview/index.js?");

/***/ }),

/***/ "./assets/js/components/products-control/index.js":
/*!********************************************************!*\
  !*** ./assets/js/components/products-control/index.js ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ \"./node_modules/@babel/runtime/helpers/classCallCheck.js\");\n/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ \"./node_modules/@babel/runtime/helpers/createClass.js\");\n/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ \"./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js\");\n/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ \"./node_modules/@babel/runtime/helpers/getPrototypeOf.js\");\n/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ \"./node_modules/@babel/runtime/helpers/inherits.js\");\n/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);\n/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/url */ \"@wordpress/url\");\n/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_7__);\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/api-fetch */ \"@wordpress/api-fetch\");\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_8__);\n/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! lodash */ \"lodash\");\n/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_9__);\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! prop-types */ \"./node_modules/prop-types/index.js\");\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_10__);\n/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @woocommerce/components */ \"./node_modules/@woocommerce/components/build-module/index.js\");\n\n\n\n\n\n\n\n/**\n * External dependencies\n */\n\n\n\n\n\n\n\n\nvar ProductsControl =\n/*#__PURE__*/\nfunction (_Component) {\n  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(ProductsControl, _Component);\n\n  function ProductsControl() {\n    var _this;\n\n    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ProductsControl);\n\n    _this = _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(this, _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(ProductsControl).apply(this, arguments));\n    _this.state = {\n      list: [],\n      loading: true\n    };\n    return _this;\n  }\n\n  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ProductsControl, [{\n    key: \"componentDidMount\",\n    value: function componentDidMount() {\n      var _this2 = this;\n\n      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_8___default()({\n        path: Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_7__[\"addQueryArgs\"])('/wc-pb/v3/products', {\n          per_page: -1,\n          catalog_visibility: 'visible',\n          status: 'publish'\n        })\n      }).then(function (list) {\n        _this2.setState({\n          list: list,\n          loading: false\n        });\n      }).catch(function () {\n        _this2.setState({\n          list: [],\n          loading: false\n        });\n      });\n    }\n  }, {\n    key: \"render\",\n    value: function render() {\n      var _this$state = this.state,\n          list = _this$state.list,\n          loading = _this$state.loading;\n      var _this$props = this.props,\n          onChange = _this$props.onChange,\n          selected = _this$props.selected;\n      var messages = {\n        clear: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__[\"__\"])('Clear all products', 'woocommerce'),\n        list: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__[\"__\"])('Products', 'woocommerce'),\n        noItems: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__[\"__\"])(\"Your store doesn't have any products.\", 'woocommerce'),\n        search: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__[\"__\"])('Search for products to display', 'woocommerce'),\n        selected: function selected(n) {\n          return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__[\"sprintf\"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__[\"_n\"])('%d product selected', '%d products selected', n, 'woocommerce'), n);\n        },\n        updated: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__[\"__\"])('Product search results updated.', 'woocommerce')\n      };\n      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__[\"createElement\"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__[\"Fragment\"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__[\"createElement\"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__[\"SearchListControl\"], {\n        className: \"woocommerce-products\",\n        list: list,\n        isLoading: loading,\n        selected: selected.map(function (id) {\n          return Object(lodash__WEBPACK_IMPORTED_MODULE_9__[\"find\"])(list, {\n            id: id\n          });\n        }).filter(Boolean),\n        onChange: onChange,\n        messages: messages\n      }));\n    }\n  }]);\n\n  return ProductsControl;\n}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__[\"Component\"]);\n\nProductsControl.propTypes = {\n  /**\n   * Callback to update the selected products.\n   */\n  onChange: prop_types__WEBPACK_IMPORTED_MODULE_10___default.a.func.isRequired,\n\n  /**\n   * The list of currently selected IDs.\n   */\n  selected: prop_types__WEBPACK_IMPORTED_MODULE_10___default.a.array.isRequired\n};\n/* harmony default export */ __webpack_exports__[\"default\"] = (ProductsControl);\n\n//# sourceURL=webpack:///./assets/js/components/products-control/index.js?");

/***/ }),

/***/ "./assets/js/utils/get-query.js":
/*!**************************************!*\
  !*** ./assets/js/utils/get-query.js ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"default\", function() { return getQuery; });\nfunction getQuery(blockAttributes, name) {\n  var attributes = blockAttributes.attributes,\n      attrOperator = blockAttributes.attrOperator,\n      categories = blockAttributes.categories,\n      catOperator = blockAttributes.catOperator,\n      orderby = blockAttributes.orderby,\n      products = blockAttributes.products;\n  var columns = blockAttributes.columns || wc_product_block_data.default_columns;\n  var rows = blockAttributes.rows || wc_product_block_data.default_rows;\n  var query = {\n    status: 'publish',\n    per_page: rows * columns,\n    catalog_visibility: 'visible'\n  };\n\n  if (categories && categories.length) {\n    query.category = categories.join(',');\n\n    if (catOperator && 'all' === catOperator) {\n      query.cat_operator = 'AND';\n    }\n  }\n\n  if (orderby) {\n    if ('price_desc' === orderby) {\n      query.orderby = 'price';\n      query.order = 'desc';\n    } else if ('price_asc' === orderby) {\n      query.orderby = 'price';\n      query.order = 'asc';\n    } else if ('title' === orderby) {\n      query.orderby = 'title';\n      query.order = 'asc';\n    } else if ('menu_order' === orderby) {\n      query.orderby = 'menu_order';\n      query.order = 'asc';\n    } else {\n      query.orderby = orderby;\n    }\n  }\n\n  if (attributes && attributes.length > 0) {\n    query.attribute_term = attributes.map(function (_ref) {\n      var id = _ref.id;\n      return id;\n    }).join(',');\n    query.attribute = attributes[0].attr_slug;\n\n    if (attrOperator) {\n      query.attr_operator = 'all' === attrOperator ? 'AND' : 'IN';\n    }\n  } // Toggle query parameters depending on block type.\n\n\n  switch (name) {\n    case 'woocommerce/product-best-sellers':\n      query.orderby = 'popularity';\n      break;\n\n    case 'woocommerce/product-top-rated':\n      query.orderby = 'rating';\n      break;\n\n    case 'woocommerce/product-on-sale':\n      query.on_sale = 1;\n      break;\n\n    case 'woocommerce/product-new':\n      query.orderby = 'date';\n      break;\n\n    case 'woocommerce/handpicked-products':\n      query.include = products;\n      query.per_page = products.length;\n      break;\n  }\n\n  return query;\n}\n\n//# sourceURL=webpack:///./assets/js/utils/get-query.js?");

/***/ }),

/***/ "./assets/js/utils/get-shortcode.js":
/*!******************************************!*\
  !*** ./assets/js/utils/get-shortcode.js ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"default\", function() { return getShortcode; });\n/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ \"./node_modules/@babel/runtime/helpers/slicedToArray.js\");\n/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__);\n\nfunction getShortcode(props, name) {\n  var blockAttributes = props.attributes;\n  var attributes = blockAttributes.attributes,\n      attrOperator = blockAttributes.attrOperator,\n      categories = blockAttributes.categories,\n      catOperator = blockAttributes.catOperator,\n      orderby = blockAttributes.orderby,\n      products = blockAttributes.products;\n  var columns = blockAttributes.columns || wc_product_block_data.default_columns;\n  var rows = blockAttributes.rows || wc_product_block_data.default_rows;\n  var shortcodeAtts = new Map();\n  shortcodeAtts.set('limit', rows * columns);\n  shortcodeAtts.set('columns', columns);\n\n  if (categories && categories.length) {\n    shortcodeAtts.set('category', categories.join(','));\n\n    if (catOperator && 'all' === catOperator) {\n      shortcodeAtts.set('cat_operator', 'AND');\n    }\n  }\n\n  if (attributes && attributes.length) {\n    shortcodeAtts.set('terms', attributes.map(function (_ref) {\n      var id = _ref.id;\n      return id;\n    }).join(','));\n    shortcodeAtts.set('attribute', attributes[0].attr_slug);\n\n    if (attrOperator && 'all' === attrOperator) {\n      shortcodeAtts.set('terms_operator', 'AND');\n    }\n  }\n\n  if (orderby) {\n    if ('price_desc' === orderby) {\n      shortcodeAtts.set('orderby', 'price');\n      shortcodeAtts.set('order', 'DESC');\n    } else if ('price_asc' === orderby) {\n      shortcodeAtts.set('orderby', 'price');\n      shortcodeAtts.set('order', 'ASC');\n    } else if ('date' === orderby) {\n      shortcodeAtts.set('orderby', 'date');\n      shortcodeAtts.set('order', 'DESC');\n    } else {\n      shortcodeAtts.set('orderby', orderby);\n    }\n  } // Toggle shortcode atts depending on block type.\n\n\n  switch (name) {\n    case 'woocommerce/product-best-sellers':\n      shortcodeAtts.set('best_selling', '1');\n      break;\n\n    case 'woocommerce/product-top-rated':\n      shortcodeAtts.set('orderby', 'rating');\n      break;\n\n    case 'woocommerce/product-on-sale':\n      shortcodeAtts.set('on_sale', '1');\n      break;\n\n    case 'woocommerce/product-new':\n      shortcodeAtts.set('orderby', 'date');\n      shortcodeAtts.set('order', 'DESC');\n      break;\n\n    case 'woocommerce/handpicked-products':\n      if (!products.length) {\n        return '';\n      }\n\n      shortcodeAtts.set('ids', products.join(','));\n      shortcodeAtts.set('limit', products.length);\n      break;\n\n    case 'woocommerce/product-category':\n      if (!categories || !categories.length) {\n        return '';\n      }\n\n      break;\n\n    case 'woocommerce/products-by-attribute':\n      if (!attributes || !attributes.length) {\n        return '';\n      }\n\n      break;\n  } // Build the shortcode string out of the set shortcode attributes.\n\n\n  var shortcode = '[products';\n  var _iteratorNormalCompletion = true;\n  var _didIteratorError = false;\n  var _iteratorError = undefined;\n\n  try {\n    for (var _iterator = shortcodeAtts[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {\n      var _step$value = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_step.value, 2),\n          key = _step$value[0],\n          value = _step$value[1];\n\n      shortcode += ' ' + key + '=\"' + value + '\"';\n    }\n  } catch (err) {\n    _didIteratorError = true;\n    _iteratorError = err;\n  } finally {\n    try {\n      if (!_iteratorNormalCompletion && _iterator.return != null) {\n        _iterator.return();\n      }\n    } finally {\n      if (_didIteratorError) {\n        throw _iteratorError;\n      }\n    }\n  }\n\n  shortcode += ']';\n  return shortcode;\n}\n\n//# sourceURL=webpack:///./assets/js/utils/get-shortcode.js?");

/***/ }),

/***/ "./node_modules/mini-css-extract-plugin/dist/loader.js!./node_modules/css-loader/dist/cjs.js?!./node_modules/postcss-loader/src/index.js!./node_modules/sass-loader/lib/loader.js?!./assets/js/blocks/handpicked-products/editor.scss":
/*!*****************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/mini-css-extract-plugin/dist/loader.js!./node_modules/css-loader/dist/cjs.js??ref--5-2!./node_modules/postcss-loader/src!./node_modules/sass-loader/lib/loader.js??ref--5-4!./assets/js/blocks/handpicked-products/editor.scss ***!
  \*****************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

eval("// extracted by mini-css-extract-plugin\n\n//# sourceURL=webpack:///./assets/js/blocks/handpicked-products/editor.scss?./node_modules/mini-css-extract-plugin/dist/loader.js!./node_modules/css-loader/dist/cjs.js??ref--5-2!./node_modules/postcss-loader/src!./node_modules/sass-loader/lib/loader.js??ref--5-4");

/***/ }),

/***/ "./node_modules/moment/locale sync recursive ^\\.\\/.*$":
/*!**************************************************!*\
  !*** ./node_modules/moment/locale sync ^\.\/.*$ ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

eval("var map = {\n\t\"./af\": \"./node_modules/moment/locale/af.js\",\n\t\"./af.js\": \"./node_modules/moment/locale/af.js\",\n\t\"./ar\": \"./node_modules/moment/locale/ar.js\",\n\t\"./ar-dz\": \"./node_modules/moment/locale/ar-dz.js\",\n\t\"./ar-dz.js\": \"./node_modules/moment/locale/ar-dz.js\",\n\t\"./ar-kw\": \"./node_modules/moment/locale/ar-kw.js\",\n\t\"./ar-kw.js\": \"./node_modules/moment/locale/ar-kw.js\",\n\t\"./ar-ly\": \"./node_modules/moment/locale/ar-ly.js\",\n\t\"./ar-ly.js\": \"./node_modules/moment/locale/ar-ly.js\",\n\t\"./ar-ma\": \"./node_modules/moment/locale/ar-ma.js\",\n\t\"./ar-ma.js\": \"./node_modules/moment/locale/ar-ma.js\",\n\t\"./ar-sa\": \"./node_modules/moment/locale/ar-sa.js\",\n\t\"./ar-sa.js\": \"./node_modules/moment/locale/ar-sa.js\",\n\t\"./ar-tn\": \"./node_modules/moment/locale/ar-tn.js\",\n\t\"./ar-tn.js\": \"./node_modules/moment/locale/ar-tn.js\",\n\t\"./ar.js\": \"./node_modules/moment/locale/ar.js\",\n\t\"./az\": \"./node_modules/moment/locale/az.js\",\n\t\"./az.js\": \"./node_modules/moment/locale/az.js\",\n\t\"./be\": \"./node_modules/moment/locale/be.js\",\n\t\"./be.js\": \"./node_modules/moment/locale/be.js\",\n\t\"./bg\": \"./node_modules/moment/locale/bg.js\",\n\t\"./bg.js\": \"./node_modules/moment/locale/bg.js\",\n\t\"./bm\": \"./node_modules/moment/locale/bm.js\",\n\t\"./bm.js\": \"./node_modules/moment/locale/bm.js\",\n\t\"./bn\": \"./node_modules/moment/locale/bn.js\",\n\t\"./bn.js\": \"./node_modules/moment/locale/bn.js\",\n\t\"./bo\": \"./node_modules/moment/locale/bo.js\",\n\t\"./bo.js\": \"./node_modules/moment/locale/bo.js\",\n\t\"./br\": \"./node_modules/moment/locale/br.js\",\n\t\"./br.js\": \"./node_modules/moment/locale/br.js\",\n\t\"./bs\": \"./node_modules/moment/locale/bs.js\",\n\t\"./bs.js\": \"./node_modules/moment/locale/bs.js\",\n\t\"./ca\": \"./node_modules/moment/locale/ca.js\",\n\t\"./ca.js\": \"./node_modules/moment/locale/ca.js\",\n\t\"./cs\": \"./node_modules/moment/locale/cs.js\",\n\t\"./cs.js\": \"./node_modules/moment/locale/cs.js\",\n\t\"./cv\": \"./node_modules/moment/locale/cv.js\",\n\t\"./cv.js\": \"./node_modules/moment/locale/cv.js\",\n\t\"./cy\": \"./node_modules/moment/locale/cy.js\",\n\t\"./cy.js\": \"./node_modules/moment/locale/cy.js\",\n\t\"./da\": \"./node_modules/moment/locale/da.js\",\n\t\"./da.js\": \"./node_modules/moment/locale/da.js\",\n\t\"./de\": \"./node_modules/moment/locale/de.js\",\n\t\"./de-at\": \"./node_modules/moment/locale/de-at.js\",\n\t\"./de-at.js\": \"./node_modules/moment/locale/de-at.js\",\n\t\"./de-ch\": \"./node_modules/moment/locale/de-ch.js\",\n\t\"./de-ch.js\": \"./node_modules/moment/locale/de-ch.js\",\n\t\"./de.js\": \"./node_modules/moment/locale/de.js\",\n\t\"./dv\": \"./node_modules/moment/locale/dv.js\",\n\t\"./dv.js\": \"./node_modules/moment/locale/dv.js\",\n\t\"./el\": \"./node_modules/moment/locale/el.js\",\n\t\"./el.js\": \"./node_modules/moment/locale/el.js\",\n\t\"./en-au\": \"./node_modules/moment/locale/en-au.js\",\n\t\"./en-au.js\": \"./node_modules/moment/locale/en-au.js\",\n\t\"./en-ca\": \"./node_modules/moment/locale/en-ca.js\",\n\t\"./en-ca.js\": \"./node_modules/moment/locale/en-ca.js\",\n\t\"./en-gb\": \"./node_modules/moment/locale/en-gb.js\",\n\t\"./en-gb.js\": \"./node_modules/moment/locale/en-gb.js\",\n\t\"./en-ie\": \"./node_modules/moment/locale/en-ie.js\",\n\t\"./en-ie.js\": \"./node_modules/moment/locale/en-ie.js\",\n\t\"./en-il\": \"./node_modules/moment/locale/en-il.js\",\n\t\"./en-il.js\": \"./node_modules/moment/locale/en-il.js\",\n\t\"./en-nz\": \"./node_modules/moment/locale/en-nz.js\",\n\t\"./en-nz.js\": \"./node_modules/moment/locale/en-nz.js\",\n\t\"./eo\": \"./node_modules/moment/locale/eo.js\",\n\t\"./eo.js\": \"./node_modules/moment/locale/eo.js\",\n\t\"./es\": \"./node_modules/moment/locale/es.js\",\n\t\"./es-do\": \"./node_modules/moment/locale/es-do.js\",\n\t\"./es-do.js\": \"./node_modules/moment/locale/es-do.js\",\n\t\"./es-us\": \"./node_modules/moment/locale/es-us.js\",\n\t\"./es-us.js\": \"./node_modules/moment/locale/es-us.js\",\n\t\"./es.js\": \"./node_modules/moment/locale/es.js\",\n\t\"./et\": \"./node_modules/moment/locale/et.js\",\n\t\"./et.js\": \"./node_modules/moment/locale/et.js\",\n\t\"./eu\": \"./node_modules/moment/locale/eu.js\",\n\t\"./eu.js\": \"./node_modules/moment/locale/eu.js\",\n\t\"./fa\": \"./node_modules/moment/locale/fa.js\",\n\t\"./fa.js\": \"./node_modules/moment/locale/fa.js\",\n\t\"./fi\": \"./node_modules/moment/locale/fi.js\",\n\t\"./fi.js\": \"./node_modules/moment/locale/fi.js\",\n\t\"./fo\": \"./node_modules/moment/locale/fo.js\",\n\t\"./fo.js\": \"./node_modules/moment/locale/fo.js\",\n\t\"./fr\": \"./node_modules/moment/locale/fr.js\",\n\t\"./fr-ca\": \"./node_modules/moment/locale/fr-ca.js\",\n\t\"./fr-ca.js\": \"./node_modules/moment/locale/fr-ca.js\",\n\t\"./fr-ch\": \"./node_modules/moment/locale/fr-ch.js\",\n\t\"./fr-ch.js\": \"./node_modules/moment/locale/fr-ch.js\",\n\t\"./fr.js\": \"./node_modules/moment/locale/fr.js\",\n\t\"./fy\": \"./node_modules/moment/locale/fy.js\",\n\t\"./fy.js\": \"./node_modules/moment/locale/fy.js\",\n\t\"./gd\": \"./node_modules/moment/locale/gd.js\",\n\t\"./gd.js\": \"./node_modules/moment/locale/gd.js\",\n\t\"./gl\": \"./node_modules/moment/locale/gl.js\",\n\t\"./gl.js\": \"./node_modules/moment/locale/gl.js\",\n\t\"./gom-latn\": \"./node_modules/moment/locale/gom-latn.js\",\n\t\"./gom-latn.js\": \"./node_modules/moment/locale/gom-latn.js\",\n\t\"./gu\": \"./node_modules/moment/locale/gu.js\",\n\t\"./gu.js\": \"./node_modules/moment/locale/gu.js\",\n\t\"./he\": \"./node_modules/moment/locale/he.js\",\n\t\"./he.js\": \"./node_modules/moment/locale/he.js\",\n\t\"./hi\": \"./node_modules/moment/locale/hi.js\",\n\t\"./hi.js\": \"./node_modules/moment/locale/hi.js\",\n\t\"./hr\": \"./node_modules/moment/locale/hr.js\",\n\t\"./hr.js\": \"./node_modules/moment/locale/hr.js\",\n\t\"./hu\": \"./node_modules/moment/locale/hu.js\",\n\t\"./hu.js\": \"./node_modules/moment/locale/hu.js\",\n\t\"./hy-am\": \"./node_modules/moment/locale/hy-am.js\",\n\t\"./hy-am.js\": \"./node_modules/moment/locale/hy-am.js\",\n\t\"./id\": \"./node_modules/moment/locale/id.js\",\n\t\"./id.js\": \"./node_modules/moment/locale/id.js\",\n\t\"./is\": \"./node_modules/moment/locale/is.js\",\n\t\"./is.js\": \"./node_modules/moment/locale/is.js\",\n\t\"./it\": \"./node_modules/moment/locale/it.js\",\n\t\"./it.js\": \"./node_modules/moment/locale/it.js\",\n\t\"./ja\": \"./node_modules/moment/locale/ja.js\",\n\t\"./ja.js\": \"./node_modules/moment/locale/ja.js\",\n\t\"./jv\": \"./node_modules/moment/locale/jv.js\",\n\t\"./jv.js\": \"./node_modules/moment/locale/jv.js\",\n\t\"./ka\": \"./node_modules/moment/locale/ka.js\",\n\t\"./ka.js\": \"./node_modules/moment/locale/ka.js\",\n\t\"./kk\": \"./node_modules/moment/locale/kk.js\",\n\t\"./kk.js\": \"./node_modules/moment/locale/kk.js\",\n\t\"./km\": \"./node_modules/moment/locale/km.js\",\n\t\"./km.js\": \"./node_modules/moment/locale/km.js\",\n\t\"./kn\": \"./node_modules/moment/locale/kn.js\",\n\t\"./kn.js\": \"./node_modules/moment/locale/kn.js\",\n\t\"./ko\": \"./node_modules/moment/locale/ko.js\",\n\t\"./ko.js\": \"./node_modules/moment/locale/ko.js\",\n\t\"./ky\": \"./node_modules/moment/locale/ky.js\",\n\t\"./ky.js\": \"./node_modules/moment/locale/ky.js\",\n\t\"./lb\": \"./node_modules/moment/locale/lb.js\",\n\t\"./lb.js\": \"./node_modules/moment/locale/lb.js\",\n\t\"./lo\": \"./node_modules/moment/locale/lo.js\",\n\t\"./lo.js\": \"./node_modules/moment/locale/lo.js\",\n\t\"./lt\": \"./node_modules/moment/locale/lt.js\",\n\t\"./lt.js\": \"./node_modules/moment/locale/lt.js\",\n\t\"./lv\": \"./node_modules/moment/locale/lv.js\",\n\t\"./lv.js\": \"./node_modules/moment/locale/lv.js\",\n\t\"./me\": \"./node_modules/moment/locale/me.js\",\n\t\"./me.js\": \"./node_modules/moment/locale/me.js\",\n\t\"./mi\": \"./node_modules/moment/locale/mi.js\",\n\t\"./mi.js\": \"./node_modules/moment/locale/mi.js\",\n\t\"./mk\": \"./node_modules/moment/locale/mk.js\",\n\t\"./mk.js\": \"./node_modules/moment/locale/mk.js\",\n\t\"./ml\": \"./node_modules/moment/locale/ml.js\",\n\t\"./ml.js\": \"./node_modules/moment/locale/ml.js\",\n\t\"./mn\": \"./node_modules/moment/locale/mn.js\",\n\t\"./mn.js\": \"./node_modules/moment/locale/mn.js\",\n\t\"./mr\": \"./node_modules/moment/locale/mr.js\",\n\t\"./mr.js\": \"./node_modules/moment/locale/mr.js\",\n\t\"./ms\": \"./node_modules/moment/locale/ms.js\",\n\t\"./ms-my\": \"./node_modules/moment/locale/ms-my.js\",\n\t\"./ms-my.js\": \"./node_modules/moment/locale/ms-my.js\",\n\t\"./ms.js\": \"./node_modules/moment/locale/ms.js\",\n\t\"./mt\": \"./node_modules/moment/locale/mt.js\",\n\t\"./mt.js\": \"./node_modules/moment/locale/mt.js\",\n\t\"./my\": \"./node_modules/moment/locale/my.js\",\n\t\"./my.js\": \"./node_modules/moment/locale/my.js\",\n\t\"./nb\": \"./node_modules/moment/locale/nb.js\",\n\t\"./nb.js\": \"./node_modules/moment/locale/nb.js\",\n\t\"./ne\": \"./node_modules/moment/locale/ne.js\",\n\t\"./ne.js\": \"./node_modules/moment/locale/ne.js\",\n\t\"./nl\": \"./node_modules/moment/locale/nl.js\",\n\t\"./nl-be\": \"./node_modules/moment/locale/nl-be.js\",\n\t\"./nl-be.js\": \"./node_modules/moment/locale/nl-be.js\",\n\t\"./nl.js\": \"./node_modules/moment/locale/nl.js\",\n\t\"./nn\": \"./node_modules/moment/locale/nn.js\",\n\t\"./nn.js\": \"./node_modules/moment/locale/nn.js\",\n\t\"./pa-in\": \"./node_modules/moment/locale/pa-in.js\",\n\t\"./pa-in.js\": \"./node_modules/moment/locale/pa-in.js\",\n\t\"./pl\": \"./node_modules/moment/locale/pl.js\",\n\t\"./pl.js\": \"./node_modules/moment/locale/pl.js\",\n\t\"./pt\": \"./node_modules/moment/locale/pt.js\",\n\t\"./pt-br\": \"./node_modules/moment/locale/pt-br.js\",\n\t\"./pt-br.js\": \"./node_modules/moment/locale/pt-br.js\",\n\t\"./pt.js\": \"./node_modules/moment/locale/pt.js\",\n\t\"./ro\": \"./node_modules/moment/locale/ro.js\",\n\t\"./ro.js\": \"./node_modules/moment/locale/ro.js\",\n\t\"./ru\": \"./node_modules/moment/locale/ru.js\",\n\t\"./ru.js\": \"./node_modules/moment/locale/ru.js\",\n\t\"./sd\": \"./node_modules/moment/locale/sd.js\",\n\t\"./sd.js\": \"./node_modules/moment/locale/sd.js\",\n\t\"./se\": \"./node_modules/moment/locale/se.js\",\n\t\"./se.js\": \"./node_modules/moment/locale/se.js\",\n\t\"./si\": \"./node_modules/moment/locale/si.js\",\n\t\"./si.js\": \"./node_modules/moment/locale/si.js\",\n\t\"./sk\": \"./node_modules/moment/locale/sk.js\",\n\t\"./sk.js\": \"./node_modules/moment/locale/sk.js\",\n\t\"./sl\": \"./node_modules/moment/locale/sl.js\",\n\t\"./sl.js\": \"./node_modules/moment/locale/sl.js\",\n\t\"./sq\": \"./node_modules/moment/locale/sq.js\",\n\t\"./sq.js\": \"./node_modules/moment/locale/sq.js\",\n\t\"./sr\": \"./node_modules/moment/locale/sr.js\",\n\t\"./sr-cyrl\": \"./node_modules/moment/locale/sr-cyrl.js\",\n\t\"./sr-cyrl.js\": \"./node_modules/moment/locale/sr-cyrl.js\",\n\t\"./sr.js\": \"./node_modules/moment/locale/sr.js\",\n\t\"./ss\": \"./node_modules/moment/locale/ss.js\",\n\t\"./ss.js\": \"./node_modules/moment/locale/ss.js\",\n\t\"./sv\": \"./node_modules/moment/locale/sv.js\",\n\t\"./sv.js\": \"./node_modules/moment/locale/sv.js\",\n\t\"./sw\": \"./node_modules/moment/locale/sw.js\",\n\t\"./sw.js\": \"./node_modules/moment/locale/sw.js\",\n\t\"./ta\": \"./node_modules/moment/locale/ta.js\",\n\t\"./ta.js\": \"./node_modules/moment/locale/ta.js\",\n\t\"./te\": \"./node_modules/moment/locale/te.js\",\n\t\"./te.js\": \"./node_modules/moment/locale/te.js\",\n\t\"./tet\": \"./node_modules/moment/locale/tet.js\",\n\t\"./tet.js\": \"./node_modules/moment/locale/tet.js\",\n\t\"./tg\": \"./node_modules/moment/locale/tg.js\",\n\t\"./tg.js\": \"./node_modules/moment/locale/tg.js\",\n\t\"./th\": \"./node_modules/moment/locale/th.js\",\n\t\"./th.js\": \"./node_modules/moment/locale/th.js\",\n\t\"./tl-ph\": \"./node_modules/moment/locale/tl-ph.js\",\n\t\"./tl-ph.js\": \"./node_modules/moment/locale/tl-ph.js\",\n\t\"./tlh\": \"./node_modules/moment/locale/tlh.js\",\n\t\"./tlh.js\": \"./node_modules/moment/locale/tlh.js\",\n\t\"./tr\": \"./node_modules/moment/locale/tr.js\",\n\t\"./tr.js\": \"./node_modules/moment/locale/tr.js\",\n\t\"./tzl\": \"./node_modules/moment/locale/tzl.js\",\n\t\"./tzl.js\": \"./node_modules/moment/locale/tzl.js\",\n\t\"./tzm\": \"./node_modules/moment/locale/tzm.js\",\n\t\"./tzm-latn\": \"./node_modules/moment/locale/tzm-latn.js\",\n\t\"./tzm-latn.js\": \"./node_modules/moment/locale/tzm-latn.js\",\n\t\"./tzm.js\": \"./node_modules/moment/locale/tzm.js\",\n\t\"./ug-cn\": \"./node_modules/moment/locale/ug-cn.js\",\n\t\"./ug-cn.js\": \"./node_modules/moment/locale/ug-cn.js\",\n\t\"./uk\": \"./node_modules/moment/locale/uk.js\",\n\t\"./uk.js\": \"./node_modules/moment/locale/uk.js\",\n\t\"./ur\": \"./node_modules/moment/locale/ur.js\",\n\t\"./ur.js\": \"./node_modules/moment/locale/ur.js\",\n\t\"./uz\": \"./node_modules/moment/locale/uz.js\",\n\t\"./uz-latn\": \"./node_modules/moment/locale/uz-latn.js\",\n\t\"./uz-latn.js\": \"./node_modules/moment/locale/uz-latn.js\",\n\t\"./uz.js\": \"./node_modules/moment/locale/uz.js\",\n\t\"./vi\": \"./node_modules/moment/locale/vi.js\",\n\t\"./vi.js\": \"./node_modules/moment/locale/vi.js\",\n\t\"./x-pseudo\": \"./node_modules/moment/locale/x-pseudo.js\",\n\t\"./x-pseudo.js\": \"./node_modules/moment/locale/x-pseudo.js\",\n\t\"./yo\": \"./node_modules/moment/locale/yo.js\",\n\t\"./yo.js\": \"./node_modules/moment/locale/yo.js\",\n\t\"./zh-cn\": \"./node_modules/moment/locale/zh-cn.js\",\n\t\"./zh-cn.js\": \"./node_modules/moment/locale/zh-cn.js\",\n\t\"./zh-hk\": \"./node_modules/moment/locale/zh-hk.js\",\n\t\"./zh-hk.js\": \"./node_modules/moment/locale/zh-hk.js\",\n\t\"./zh-tw\": \"./node_modules/moment/locale/zh-tw.js\",\n\t\"./zh-tw.js\": \"./node_modules/moment/locale/zh-tw.js\"\n};\n\n\nfunction webpackContext(req) {\n\tvar id = webpackContextResolve(req);\n\treturn __webpack_require__(id);\n}\nfunction webpackContextResolve(req) {\n\tif(!__webpack_require__.o(map, req)) {\n\t\tvar e = new Error(\"Cannot find module '\" + req + \"'\");\n\t\te.code = 'MODULE_NOT_FOUND';\n\t\tthrow e;\n\t}\n\treturn map[req];\n}\nwebpackContext.keys = function webpackContextKeys() {\n\treturn Object.keys(map);\n};\nwebpackContext.resolve = webpackContextResolve;\nmodule.exports = webpackContext;\nwebpackContext.id = \"./node_modules/moment/locale sync recursive ^\\\\.\\\\/.*$\";\n\n//# sourceURL=webpack:///./node_modules/moment/locale_sync_^\\.\\/.*$?");

/***/ }),

/***/ 0:
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("/* (ignored) */\n\n//# sourceURL=webpack:///util_(ignored)?");

/***/ }),

/***/ 1:
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("/* (ignored) */\n\n//# sourceURL=webpack:///util_(ignored)?");

/***/ }),

/***/ 2:
/*!************************!*\
  !*** buffer (ignored) ***!
  \************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("/* (ignored) */\n\n//# sourceURL=webpack:///buffer_(ignored)?");

/***/ }),

/***/ 3:
/*!************************!*\
  !*** crypto (ignored) ***!
  \************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("/* (ignored) */\n\n//# sourceURL=webpack:///crypto_(ignored)?");

/***/ }),

/***/ "@wordpress/api-fetch":
/*!*******************************************!*\
  !*** external {"this":["wp","apiFetch"]} ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"apiFetch\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22apiFetch%22%5D%7D?");

/***/ }),

/***/ "@wordpress/blocks":
/*!*****************************************!*\
  !*** external {"this":["wp","blocks"]} ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"blocks\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22blocks%22%5D%7D?");

/***/ }),

/***/ "@wordpress/components":
/*!*********************************************!*\
  !*** external {"this":["wp","components"]} ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"components\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22components%22%5D%7D?");

/***/ }),

/***/ "@wordpress/compose":
/*!******************************************!*\
  !*** external {"this":["wp","compose"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"compose\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22compose%22%5D%7D?");

/***/ }),

/***/ "@wordpress/data":
/*!***************************************!*\
  !*** external {"this":["wp","data"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"data\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22data%22%5D%7D?");

/***/ }),

/***/ "@wordpress/editor":
/*!*****************************************!*\
  !*** external {"this":["wp","editor"]} ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"editor\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22editor%22%5D%7D?");

/***/ }),

/***/ "@wordpress/element":
/*!******************************************!*\
  !*** external {"this":["wp","element"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"element\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22element%22%5D%7D?");

/***/ }),

/***/ "@wordpress/i18n":
/*!***************************************!*\
  !*** external {"this":["wp","i18n"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"i18n\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22i18n%22%5D%7D?");

/***/ }),

/***/ "@wordpress/url":
/*!**************************************!*\
  !*** external {"this":["wp","url"]} ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"url\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22url%22%5D%7D?");

/***/ }),

/***/ "lodash":
/*!*************************!*\
  !*** external "lodash" ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"lodash\"]; }());\n\n//# sourceURL=webpack:///external_%22lodash%22?");

/***/ })

/******/ })));