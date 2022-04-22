module.exports = {
	extends: '@wordpress/stylelint-config/scss',
	ignoreFiles: [ './vendor/**/*.scss' ],
	rules: {
		'at-rule-empty-line-before': null,
		'at-rule-no-unknown': null,
		'comment-empty-line-before': null,
		'declaration-block-no-duplicate-properties': null,
		'declaration-colon-newline-after': null,
		'declaration-property-unit-allowed-list': null,
		'font-weight-notation': null,
		'max-line-length': null,
		'no-descending-specificity': null,
		'no-duplicate-selectors': null,
		'rule-empty-line-before': null,
		'selector-class-pattern': null,
		'string-quotes': 'single',
		'value-keyword-case': null,
		'value-list-comma-newline-after': null,
		// TODO: fix these rules
		// New rules enabled after updating @wordpress/stylelint-config
		'scss/at-import-partial-extension': 'always',
		'scss/at-import-no-partial-leading-underscore': null,
		'scss/no-global-function-names': null,
		'scss/operator-no-unspaced': null,
		'scss/at-extend-no-missing-placeholder': null,
		'scss/selector-no-redundant-nesting-selector': null,
		'selector-id-pattern': null,
		'no-invalid-position-at-import-rule': null,
	},
};
