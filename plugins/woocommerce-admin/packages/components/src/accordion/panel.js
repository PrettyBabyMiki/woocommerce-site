/**
 * External dependencies
 */
import { Card, CardHeader, PanelBody, PanelRow } from '@wordpress/components';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Badge } from '../badge';

/**
 * `AccordionPanel` is used to give the panel content an accessible wrapper.
 *
 * @param {Object} props
 * @param {string} props.className
 * @param {string} props.count
 * @param {string} props.children
 * @param {string} props.title
 * @param {string} props.initialOpen
 * @param {boolean} props.collapsible
 * @return {Object} -
 */
const AccordionPanel = ( {
	className,
	count,
	title,
	initialOpen,
	collapsible,
	children,
} ) => {
	const [ isPanelOpen, setIsPanelOpen ] = useState( null );

	useEffect( () => {
		if ( ! collapsible && isPanelOpen ) {
			setIsPanelOpen( ! isPanelOpen );
		}
	}, [ collapsible ] );

	const getTitleAndCount = ( titleText, countUnread ) => {
		return (
			<span className="woocommerce-accordion-header">
				<span className="woocommerce-accordion-title">
					{ titleText }
				</span>
				{ countUnread !== null && <Badge count={ countUnread } /> }
			</span>
		);
	};

	const opened = isPanelOpen === null ? initialOpen : isPanelOpen;

	const onToggle = () => {
		setIsPanelOpen( ! opened );
	};

	return (
		<Card
			size="large"
			className={ classnames( className, 'woocommerce-accordion-card', {
				'is-panel-opened': opened,
			} ) }
		>
			{ collapsible ? (
				<PanelBody
					title={ getTitleAndCount( title, count ) }
					initialOpen={ opened }
					onToggle={ onToggle }
				>
					<PanelRow> { children } </PanelRow>
				</PanelBody>
			) : (
				<CardHeader size="medium">
					{ getTitleAndCount( title, count ) }
				</CardHeader>
			) }
		</Card>
	);
};

AccordionPanel.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * Number of unread elements in the panel that will be shown next to the panel's title.
	 */
	count: PropTypes.number,
	/**
	 * The panel title.
	 */
	title: PropTypes.string,
	/**
	 * Whether or not the panel will start open.
	 */
	initialOpen: PropTypes.bool,
	/**
	 * Whether or not the panel can be collapsed or not.
	 */
	collapsible: PropTypes.bool,
};

AccordionPanel.defaultProps = {
	initialOpen: true,
	collapsible: true,
};

export default AccordionPanel;
