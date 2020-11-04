/**
 * External dependencies
 */
import React from '@wordpress/element';
import { external, Icon } from '@wordpress/icons';
import { __experimentalText as Text } from '@wordpress/components';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';

export const QuickLink = ( { icon, title, href, linkType, onClick } ) => {
	const isExternal = linkType === 'external';

	return (
		<div className="woocommerce-quick-links__item">
			<Link
				onClick={ onClick }
				href={ href }
				linkType={ linkType }
				className="woocommerce-quick-links__item-link"
			>
				<Icon
					className="woocommerce-quick-links__item-link__icon"
					icon={ icon }
				/>
				<Text
					className="woocommerce-quick-links__item-link__text"
					as="div"
					variant="button"
				>
					{ title }
				</Text>
				{ isExternal && <Icon icon={ external } /> }
			</Link>
		</div>
	);
};
