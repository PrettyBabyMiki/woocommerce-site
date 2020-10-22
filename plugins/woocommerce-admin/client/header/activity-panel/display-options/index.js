/**
 * External dependencies
 */
import {
	DropdownMenu,
	MenuGroup,
	MenuItemsChoice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useUserPreferences } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { DisplayIcon } from './icons/display';
import { SingleColumnIcon } from './icons/single-column';
import { TwoColumnsIcon } from './icons/two-columns';

const LAYOUTS = [
	{
		value: 'single_column',
		label: (
			<>
				<SingleColumnIcon />
				{ __( 'Single column', 'woocommerce-admin' ) }
			</>
		),
	},
	{
		value: 'two_columns',
		label: (
			<>
				<TwoColumnsIcon />
				{ __( 'Two columns', 'woocommerce-admin' ) }
			</>
		),
	},
];

export const DisplayOptions = () => {
	const {
		updateUserPreferences,
		homepage_layout: layout,
	} = useUserPreferences();
	return (
		<DropdownMenu
			icon={ <DisplayIcon /> }
			/* translators: button label text should, if possible, be under 16 characters. */
			label={ __( 'Display options', 'woocommerce-admin' ) }
			toggleProps={ {
				className:
					'woocommerce-layout__activity-panel-tab display-options',
				onClick: () => recordEvent( 'homescreen_display_click' ),
			} }
			popoverProps={ {
				className: 'woocommerce-layout__activity-panel-popover',
			} }
		>
			{ () => (
				<MenuGroup
					className="woocommerce-layout__homescreen-display-options"
					label={ __( 'Layout', 'woocommerce-admin' ) }
				>
					<MenuItemsChoice
						choices={ LAYOUTS }
						onSelect={ ( newLayout ) => {
							updateUserPreferences( {
								homepage_layout: newLayout,
							} );
							recordEvent( 'homescreen_display_option', {
								display_option: newLayout,
							} );
						} }
						value={ layout || 'two_columns' }
					/>
				</MenuGroup>
			) }
		</DropdownMenu>
	);
};
