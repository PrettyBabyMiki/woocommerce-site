/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './load-sample-product-confirm-modal.scss';

type Props = {
	onCancel: () => void;
	onImport: () => void;
};

export const LoadSampleProductConfirmModal: React.VFC< Props > = ( {
	onCancel,
	onImport,
} ) => {
	return (
		<Modal
			className="woocommerce-products-load-sample-product-confirm-modal"
			title="Load sample products"
			onRequestClose={ onCancel }
		>
			<Text className="woocommerce-confirmation-modal__message">
				{ __(
					"We'll import images from woocommerce.com to set up your sample products."
				) }
			</Text>
			<div className="woocommerce-confirmation-modal-actions">
				<Button isSecondary onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button isPrimary onClick={ onImport }>
					{ __( 'Import sample products', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default LoadSampleProductConfirmModal;
