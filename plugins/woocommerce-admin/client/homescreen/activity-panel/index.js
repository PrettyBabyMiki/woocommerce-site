/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { Badge } from '@woocommerce/components';
import {
	Button,
	Panel,
	PanelBody,
	PanelRow,
	__experimentalText as Text,
} from '@wordpress/components';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './style.scss';
import {
	getLowStockCount,
	getOrderStatuses,
	getUnreadOrders,
} from './orders/utils';
import { getAllPanels } from './panels';
import { getUnapprovedReviews } from './reviews/utils';

export const ActivityPanel = () => {
	const panelsData = useSelect( ( select ) => {
		const totalOrderCount = getSetting( 'orderCount', 0 );
		const orderStatuses = getOrderStatuses( select );
		const reviewsEnabled = getSetting( 'reviewsEnabled', 'no' );
		const countUnreadOrders = getUnreadOrders( select, orderStatuses );
		const manageStock = getSetting( 'manageStock', 'no' );
		const countLowStockProducts = getLowStockCount( select );
		const countUnapprovedReviews = getUnapprovedReviews( select );
		const publishedProductCount = getSetting( 'publishedProductCount', 0 );
		const { getOption } = select( OPTIONS_STORE_NAME );
		const isTaskListHidden = getOption( 'woocommerce_task_list_hidden' );
		return {
			countLowStockProducts,
			countUnapprovedReviews,
			countUnreadOrders,
			isTaskListHidden,
			manageStock,
			publishedProductCount,
			reviewsEnabled,
			totalOrderCount,
			orderStatuses,
		};
	} );

	const panels = getAllPanels( panelsData );

	if ( panels.length === 0 ) {
		return null;
	}

	return (
		<Panel className="woocommerce-activity-panel">
			{ panels.map( ( panelData ) => {
				const {
					className,
					count,
					id,
					initialOpen,
					panel,
					title,
					collapsible,
				} = panelData;
				return collapsible ? (
					<PanelBody
						title={ [
							<Text key={ title } variant="title.small">
								{ title }
							</Text>,
							count !== null && (
								<Badge
									key={ `${ title }-badge` }
									count={ count }
								/>
							),
						] }
						key={ id }
						className={ className }
						initialOpen={ initialOpen }
						collapsible={ collapsible }
						disabled={ ! collapsible }
					>
						<PanelRow>{ panel }</PanelRow>
					</PanelBody>
				) : (
					<div className="components-panel__body" key={ id }>
						<h2 className="components-panel__body-title">
							<Button
								className="components-panel__body-toggle"
								aria-expanded={ false }
								disabled={ true }
							>
								<Text variant="title.small">{ title }</Text>
								{ count !== null && <Badge count={ count } /> }
							</Button>
						</h2>
					</div>
				);
			} ) }
		</Panel>
	);
};
