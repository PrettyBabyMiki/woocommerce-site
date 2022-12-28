<?php
/**
 * Handles the registration of marketing channels and acts as their repository.
 */

namespace Automattic\WooCommerce\Admin\Marketing;

use Exception;

/**
 * MarketingChannels repository class
 *
 * @since x.x.x
 */
class MarketingChannels {
	/**
	 * The registered marketing channels.
	 *
	 * @var MarketingChannelInterface[]
	 */
	private $registered_channels = [];

	/**
	 * Registers a marketing channel.
	 *
	 * Note that only a predetermined list of third party extensions can be registered as a marketing channel.
	 *
	 * @param MarketingChannelInterface $channel The marketing channel to register.
	 *
	 * @return void
	 *
	 * @throws Exception If the given marketing channel is already registered.
	 */
	public function register( MarketingChannelInterface $channel ): void {
		if ( isset( $this->registered_channels[ $channel->get_slug() ] ) ) {
			throw new Exception( __( 'Marketing channel cannot be registered because there is already a channel registered with the same slug!', 'woocommerce' ) );
		}

		$this->registered_channels[ $channel->get_slug() ] = $channel;
	}

	/**
	 * Returns an array of all registered marketing channels.
	 *
	 * @return MarketingChannelInterface[]
	 */
	public function get_registered_channels(): array {
		/**
		 * Filter the list of registered marketing channels.
		 *
		 * Note that only a predetermined list of third party extensions can be registered as a marketing channel.
		 * Any new plugins added to this array will be cross-checked with that list, which is obtained from WooCommerce.com API.
		 *
		 * @param MarketingChannelInterface[] $channels Array of registered marketing channels.
		 *
		 * @since x.x.x
		 */
		$channels = apply_filters( 'woocommerce_marketing_channels', $this->registered_channels );

		return array_values( $channels );
	}
}
