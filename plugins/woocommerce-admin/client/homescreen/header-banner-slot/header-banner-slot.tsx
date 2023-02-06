/**
 * External dependencies
 */
import { useSlot } from '@woocommerce/experimental';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import {
	EXPERIMENTAL_WC_HOMESCREEN_HEADER_BANNER_SLOT_NAME,
	WooHomescreenHeaderBannerItem,
} from './utils';

export const WooHomescreenHeaderBanner = ( {
	className,
}: {
	className: string;
} ) => {
	const slot = useSlot( EXPERIMENTAL_WC_HOMESCREEN_HEADER_BANNER_SLOT_NAME );
	const hasFills = Boolean( slot?.fills?.length );

	if ( ! hasFills ) {
		return null;
	}
	return (
		<div
			className={ classnames(
				'woocommerce-homescreen__header',
				className
			) }
		>
			<WooHomescreenHeaderBannerItem.Slot />
		</div>
	);
};
