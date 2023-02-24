/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { TreeProps } from '../types';

export function useTree( {
	ref,
	items,
	level = 1,
	getItemLabel,
	shouldItemBeExpanded,
	...props
}: TreeProps ) {
	return {
		level,
		items,
		treeProps: {
			...props,
		},
		treeItemProps: {
			level,
			getLabel: getItemLabel,
			shouldItemBeExpanded,
		},
	};
}
