/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CardBody } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { Card } from '~/marketing/components';
import { useChannels } from './useChannels';

export const Channels = () => {
	const {
		loading,
		data: { registeredChannels, recommendedChannels },
	} = useChannels();

	if ( loading ) {
		return (
			<Card title={ __( 'Channels', 'woocommerce' ) }>
				<CardBody>
					<Spinner />
				</CardBody>
			</Card>
		);
	}

	const description =
		registeredChannels.length === 0 &&
		recommendedChannels.length > 0 &&
		__( 'Start by adding a channel to your store', 'woocommerce' );

	return (
		<Card
			title={ __( 'Channels', 'woocommerce' ) }
			description={ description }
		>
			{ /* TODO: */ }
			Body
		</Card>
	);
};
