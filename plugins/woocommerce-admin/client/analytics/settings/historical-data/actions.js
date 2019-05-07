/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Fragment } from '@wordpress/element';

function HistoricalDataActions( {
	customersProgress,
	customersTotal,
	hasImportedData,
	inProgress,
	ordersProgress,
	ordersTotal,
} ) {
	const getActions = () => {
		// An import is currently in progress
		if ( inProgress ) {
			return (
				<Fragment>
					<Button
						className="woocommerce-settings-historical-data__action-button"
						isPrimary
						onClick={ () => null }
					>
						{ __( 'Stop Import', 'woocommerce-admin' ) }
					</Button>
					<div className="woocommerce-setting__help woocommerce-settings-historical-data__action-help">
						{ __(
							'Imported data will not be lost if the import is stopped.',
							'woocommerce-admin'
						) }
						<br />
						{ __(
							'Navigating away from this page will not affect the import.',
							'woocommerce-admin'
						) }
					</div>
				</Fragment>
			);
		}

		// Has no imported data
		if ( ! hasImportedData ) {
			return (
				<Button isPrimary onClick={ () => null }>
					{ __( 'Start', 'woocommerce-admin' ) }
				</Button>
			);
		}

		// Has imported all possible data
		if ( customersProgress === customersTotal && ordersProgress === ordersTotal ) {
			return (
				<Fragment>
					<Button isDefault onClick={ () => null }>
						{ __( 'Re-import Data', 'woocommerce-admin' ) }
					</Button>
					<Button isDefault onClick={ () => null }>
						{ __( 'Delete Previously Imported Data', 'woocommerce-admin' ) }
					</Button>
				</Fragment>
			);
		}

		// It's not in progress and has some imported data
		return (
			<Fragment>
				<Button isPrimary onClick={ () => null }>
					{ __( 'Start', 'woocommerce-admin' ) }
				</Button>
				<Button isDefault onClick={ () => null }>
					{ __( 'Delete Previously Imported Data', 'woocommerce-admin' ) }
				</Button>
			</Fragment>
		);
	};

	return (
		<div className="woocommerce-settings__actions woocommerce-settings-historical-data__actions">
			{ getActions() }
		</div>
	);
}

export default HistoricalDataActions;
