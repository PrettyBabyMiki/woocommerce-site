/**
 * External dependencies
 */
import { registerCoreBlocks } from '@wordpress/block-library';

/**
 * Internal dependencies
 */
import { init as initName } from '../details-name-block';
import { init as initSummary } from '../details-summary-block';
import { init as initSection } from '../section';
import { init as initTab } from '../tab';
import { init as initPricing } from '../pricing-block';
import { init as initCollapsible } from '../collapsible-block';

export const initBlocks = () => {
	registerCoreBlocks();
	initName();
	initSummary();
	initSection();
	initTab();
	initPricing();
	initCollapsible();
};
