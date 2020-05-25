/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { Button, Modal } from '@wordpress/components';
import { find } from 'lodash';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';
import { List } from '@woocommerce/components';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';
import { getProductIdsForCart } from 'dashboard/utils';
import sanitizeHTML from 'lib/sanitize-html';
import { recordEvent } from 'lib/tracks';
import { getInAppPurchaseUrl } from 'lib/in-app-purchase';

class CartModal extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			purchaseNowButtonBusy: false,
			purchaseLaterButtonBusy: false,
		};
	}

	onClickPurchaseNow() {
		const { productIds, onClickPurchaseNow } = this.props;
		this.setState( { purchaseNowButtonBusy: true } );
		if ( ! productIds.length ) {
			return;
		}

		recordEvent( 'tasklist_modal_proceed_checkout', {
			product_ids: productIds,
			purchase_install: true,
		} );

		const url = getInAppPurchaseUrl( 'https://woocommerce.com/cart', {
			'wccom-replace-with': productIds.join( ',' ),
		} );

		if ( onClickPurchaseNow ) {
			onClickPurchaseNow( url );
			return;
		}

		window.location = url;
	}

	onClickPurchaseLater() {
		const { productIds } = this.props;

		recordEvent( 'tasklist_modal_proceed_checkout', {
			product_ids: productIds,
			purchase_install: false,
		} );

		this.setState( { purchaseLaterButtonBusy: true } );
		this.props.onClickPurchaseLater();
	}

	onClose() {
		const { onClose, productIds } = this.props;

		recordEvent( 'tasklist_modal_proceed_checkout', {
			product_ids: productIds,
			purchase_install: false,
		} );

		onClose();
	}

	renderProducts() {
		const { productIds } = this.props;
		const { productTypes = {}, themes = [] } = getSetting(
			'onboarding',
			{}
		);
		const listItems = [];

		productIds.forEach( ( productId ) => {
			const productInfo = find( productTypes, ( productType ) => {
				return productType.product === productId;
			} );

			if ( productInfo ) {
				listItems.push( {
					title: productInfo.label,
					content: productInfo.description,
				} );
			}

			const themeInfo = find( themes, ( theme ) => {
				return theme.id === productId;
			} );

			if ( themeInfo ) {
				listItems.push( {
					title: sprintf(
						__( '%s — %s per year', 'woocommerce-admin' ),
						themeInfo.title,
						decodeEntities( themeInfo.price )
					),
					content: (
						<span
							dangerouslySetInnerHTML={ sanitizeHTML(
								themeInfo.excerpt
							) }
						/>
					),
				} );
			}
		} );

		return <List items={ listItems } />;
	}

	render() {
		const { purchaseNowButtonBusy, purchaseLaterButtonBusy } = this.state;
		return (
			<Modal
				title={ __(
					'Would you like to purchase and install the following features now?',
					'woocommerce-admin'
				) }
				onRequestClose={ () => this.onClose() }
				className="woocommerce-cart-modal"
			>
				{ this.renderProducts() }

				<p className="woocommerce-cart-modal__help-text">
					{ __(
						"You won't have access to this functionality until the extensions have been purchased and installed.",
						'woocommerce-admin'
					) }
				</p>

				<div className="woocommerce-cart-modal__actions">
					<Button
						isLink
						isBusy={ purchaseLaterButtonBusy }
						onClick={ () => this.onClickPurchaseLater() }
					>
						{ __( "I'll do it later", 'woocommerce-admin' ) }
					</Button>

					<Button
						isPrimary
						isDefault
						isBusy={ purchaseNowButtonBusy }
						onClick={ () => this.onClickPurchaseNow() }
					>
						{ __( 'Purchase & install now', 'woocommerce-admin' ) }
					</Button>
				</div>
			</Modal>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getProfileItems } = select( 'wc-api' );
		const { getInstalledPlugins } = select( PLUGINS_STORE_NAME );
		const profileItems = getProfileItems();
		const installedPlugins = getInstalledPlugins();
		const productIds = getProductIdsForCart(
			profileItems,
			false,
			installedPlugins
		);

		return { profileItems, productIds };
	} )
)( CartModal );
