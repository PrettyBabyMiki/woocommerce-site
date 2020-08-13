/**
 * External dependencies
 */
import React, { useState, useEffect } from '@wordpress/element';
import { Guide } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { recordEvent } from '../../lib/tracks';
import { LineChartIllustration } from './illustrations/line-chart';
import { InboxIllustration } from './illustrations/inbox';
import { PieChartIllustration } from './illustrations/pie-chart';
import { PageContent } from './page-content';
import './style.scss';

const pages = [
	{
		image: <LineChartIllustration />,
		content: (
			<PageContent
				title={ __(
					'Welcome to your WooCommerce store’s online HQ!',
					'woocommerce-admin'
				) }
				body={ __(
					"Here's where you’ll find setup suggestions, tips and tools, and key data on your store’s performance and earnings — all the basics for store management and growth.",
					'woocommerce-admin'
				) }
			/>
		),
	},
	{
		image: <InboxIllustration />,
		content: (
			<PageContent
				title={ __(
					'A personalized inbox full of relevant advice.',
					'woocommerce-admin'
				) }
				body={ __(
					'Check your inbox for helpful growth tips tailored to your store and notifications about key traffic and sales milestones. We look forward to celebrating them with you!',
					'woocommerce-admin'
				) }
			/>
		),
	},
	{
		image: <PieChartIllustration />,
		content: (
			<PageContent
				title={ __(
					'Good data leads to smart business decisions.',
					'woocommerce-admin'
				) }
				body={
					'Monitor your stats to improve performance, increase sales, and track your progress toward revenue goals. The more you know, the better you can serve your customers and grow your store.'
				}
			/>
		),
	},
];

export const WelcomeModal = ( { onClose } ) => {
	const [ guideIsOpen, setGuideIsOpen ] = useState( true );

	useEffect( () => {
		recordEvent( 'task_list_welcome_modal_open' );
	}, [] );

	return (
		<>
			{ guideIsOpen && (
				<Guide
					onFinish={ () => {
						setGuideIsOpen( false );
						onClose();
						recordEvent( 'task_list_welcome_modal_close' );
					} }
					className={ 'woocommerce__welcome-modal' }
					finishButtonText={ __( "Let's go", 'woocommerce-admin' ) }
					pages={ pages }
				/>
			) }
		</>
	);
};
