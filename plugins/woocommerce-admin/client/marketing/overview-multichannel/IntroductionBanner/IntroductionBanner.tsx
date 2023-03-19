/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Card, Flex, FlexItem, FlexBlock, Button } from '@wordpress/components';
import { Icon, trendingUp, megaphone, closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { CreateNewCampaignModal } from '~/marketing/components';
import {
	useRegisteredChannels,
	useRecommendedChannels,
} from '~/marketing/hooks';
import './IntroductionBanner.scss';
import wooIconUrl from './woo.svg';
import illustrationUrl from './illustration.svg';
import illustrationLargeUrl from './illustration-large.svg';

type IntroductionBannerProps = {
	onDismiss: () => void;
	onAddChannels: () => void;
};

export const IntroductionBanner = ( {
	onDismiss,
	onAddChannels,
}: IntroductionBannerProps ) => {
	const [ open, setOpen ] = useState( false );
	const { data: dataRegistered } = useRegisteredChannels();
	const { data: dataRecommended } = useRecommendedChannels();

	const showCreateCampaignButton = !! dataRegistered?.length;

	/**
	 * Boolean to display the "Add channels" button in the introduction banner.
	 *
	 * This depends on the number of registered channels,
	 * because if there are no registered channels,
	 * the Channels card will not have the "Add channels" toggle button,
	 * and it does not make sense to display the "Add channels" button in this introduction banner
	 * that will do nothing upon click.
	 *
	 * If there are registered channels and recommended channels,
	 * the Channels card will display the  "Add channels" toggle button,
	 * and clicking on the "Add channels" button in this introduction banner
	 * will scroll to the button in Channels card.
	 */
	const showAddChannelsButton =
		!! dataRegistered?.length && !! dataRecommended?.length;

	return (
		<Card className="woocommerce-marketing-introduction-banner">
			<div className="woocommerce-marketing-introduction-banner-content">
				<div className="woocommerce-marketing-introduction-banner-title">
					{ __(
						'Reach new customers and increase sales without leaving WooCommerce',
						'woocommerce'
					) }
				</div>
				<Flex
					className="woocommerce-marketing-introduction-banner-features"
					direction="column"
					gap={ 1 }
					expanded={ false }
				>
					<FlexItem>
						<Flex>
							<Icon icon={ trendingUp } />
							<FlexBlock>
								{ __(
									'Reach customers on other sales channels',
									'woocommerce'
								) }
							</FlexBlock>
						</Flex>
					</FlexItem>
					<FlexItem>
						<Flex>
							<Icon icon={ megaphone } />
							<FlexBlock>
								{ __(
									'Advertise with marketing campaigns',
									'woocommerce'
								) }
							</FlexBlock>
						</Flex>
					</FlexItem>
					<FlexItem>
						<Flex>
							<img
								src={ wooIconUrl }
								alt={ __( 'WooCommerce logo', 'woocommerce' ) }
								width="24"
								height="24"
							/>
							<FlexBlock>
								{ __( 'Built by WooCommerce', 'woocommerce' ) }
							</FlexBlock>
						</Flex>
					</FlexItem>
				</Flex>
				{ ( showCreateCampaignButton || showAddChannelsButton ) && (
					<Flex
						className="woocommerce-marketing-introduction-banner-buttons"
						justify="flex-start"
					>
						{ showCreateCampaignButton && (
							<Button
								variant="primary"
								onClick={ () => {
									setOpen( true );
								} }
							>
								{ __( 'Create a campaign', 'woocommerce' ) }
							</Button>
						) }
						{ showAddChannelsButton && (
							<Button
								variant="secondary"
								onClick={ onAddChannels }
							>
								{ __( 'Add channels', 'woocommerce' ) }
							</Button>
						) }
					</Flex>
				) }
				{ open && (
					<CreateNewCampaignModal
						onRequestClose={ () => setOpen( false ) }
					/>
				) }
			</div>
			<div className="woocommerce-marketing-introduction-banner-illustration">
				<Button
					isSmall
					className="woocommerce-marketing-introduction-banner-close-button"
					onClick={ onDismiss }
				>
					<Icon icon={ closeSmall } />
				</Button>
				<img
					src={
						showCreateCampaignButton || showAddChannelsButton
							? illustrationLargeUrl
							: illustrationUrl
					}
					alt={ __(
						'WooCommerce Marketing introduction banner illustration',
						'woocommerce'
					) }
				/>
			</div>
		</Card>
	);
};
