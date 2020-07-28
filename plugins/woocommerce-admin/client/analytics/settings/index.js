/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Fragment, useEffect, useRef } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { SectionHeader, useFilters, ScrollTo } from '@woocommerce/components';
import { useSettings } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './index.scss';
import { config } from './config';
import Setting from './setting';
import HistoricalData from './historical-data';
import { recordEvent } from 'lib/tracks';

const SETTINGS_FILTER = 'woocommerce_admin_analytics_settings';

const Settings = ( { createNotice, query } ) => {
	const {
		settingsError,
		isRequesting,
		isDirty,
		persistSettings,
		updateAndPersistSettings,
		updateSettings,
		wcAdminSettings,
	} = useSettings( 'wc_admin', [ 'wcAdminSettings' ] );
	const hasSaved = useRef( false );

	useEffect( () => {
		function warnIfUnsavedChanges( event ) {
			if ( isDirty ) {
				event.returnValue = __(
					'You have unsaved changes. If you proceed, they will be lost.',
					'woocommerce-admin'
				);
				return event.returnValue;
			}
		}
		window.addEventListener( 'beforeunload', warnIfUnsavedChanges );
		return () =>
			window.removeEventListener( 'beforeunload', warnIfUnsavedChanges );
	}, [ isDirty ] );

	useEffect( () => {
		if ( isRequesting ) {
			hasSaved.current = true;
			return;
		}
		if ( ! isRequesting && hasSaved.current ) {
			if ( ! settingsError ) {
				createNotice(
					'success',
					__(
						'Your settings have been successfully saved.',
						'woocommerce-admin'
					)
				);
			} else {
				createNotice(
					'error',
					__(
						'There was an error saving your settings. Please try again.',
						'woocommerce-admin'
					)
				);
			}
			hasSaved.current = false;
		}
	}, [ isRequesting, settingsError, createNotice ] );

	const resetDefaults = () => {
		if (
			// eslint-disable-next-line no-alert
			window.confirm(
				__(
					'Are you sure you want to reset all settings to default values?',
					'woocommerce-admin'
				)
			)
		) {
			const resetSettings = Object.keys( config ).reduce(
				( result, setting ) => {
					result[ setting ] = config[ setting ].defaultValue;
					return result;
				},
				{}
			);

			updateAndPersistSettings( 'wcAdminSettings', resetSettings );
			recordEvent( 'analytics_settings_reset_defaults' );
		}
	};

	const saveChanges = () => {
		persistSettings();
		recordEvent( 'analytics_settings_save', wcAdminSettings );

		// On save, reset persisted query properties of Nav Menu links to default
		query.period = undefined;
		query.compare = undefined;
		query.before = undefined;
		query.after = undefined;
		query.interval = undefined;
		query.type = undefined;
		window.wpNavMenuUrlUpdate( query );
	};

	const handleInputChange = ( e ) => {
		const { checked, name, type, value } = e.target;
		const nextSettings = { ...wcAdminSettings };

		if ( type === 'checkbox' ) {
			if ( checked ) {
				nextSettings[ name ] = [ ...nextSettings[ name ], value ];
			} else {
				nextSettings[ name ] = nextSettings[ name ].filter(
					( v ) => v !== value
				);
			}
		} else {
			nextSettings[ name ] = value;
		}
		updateSettings( 'wcAdminSettings', nextSettings );
	};

	return (
		<Fragment>
			<SectionHeader
				title={ __( 'Analytics Settings', 'woocommerce-admin' ) }
			/>
			<div className="woocommerce-settings__wrapper">
				{ Object.keys( config ).map( ( setting ) => (
					<Setting
						handleChange={ handleInputChange }
						value={ wcAdminSettings[ setting ] }
						key={ setting }
						name={ setting }
						{ ...config[ setting ] }
					/>
				) ) }
				<div className="woocommerce-settings__actions">
					<Button isSecondary onClick={ resetDefaults }>
						{ __( 'Reset Defaults', 'woocommerce-admin' ) }
					</Button>
					<Button
						isPrimary
						isBusy={ isRequesting }
						onClick={ saveChanges }
					>
						{ __( 'Save Settings', 'woocommerce-admin' ) }
					</Button>
				</div>
			</div>
			{ query.import === 'true' ? (
				<ScrollTo offset="-56">
					<HistoricalData createNotice={ createNotice } />
				</ScrollTo>
			) : (
				<HistoricalData createNotice={ createNotice } />
			) }
		</Fragment>
	);
};

export default compose(
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
		};
	} )
)( useFilters( SETTINGS_FILTER )( Settings ) );
