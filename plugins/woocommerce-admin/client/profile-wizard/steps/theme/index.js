/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';
import Gridicon from 'gridicons';
import { Button, TabPanel, Tooltip } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';
import { Card, H } from '@woocommerce/components';
import { getSetting, setSetting } from '@woocommerce/wc-admin-settings';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import withSelect from '../../../wc-api/with-select';
import './style.scss';
import { recordEvent } from '../../../lib/tracks';
import ThemeUploader from './uploader';
import ThemePreview from './preview';
import { getPriceValue } from '../../../dashboard/utils';

class Theme extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			activeTab: 'all',
			chosen: null,
			demo: null,
			uploadedThemes: [],
		};

		this.handleUploadComplete = this.handleUploadComplete.bind( this );
		this.onChoose = this.onChoose.bind( this );
		this.onClosePreview = this.onClosePreview.bind( this );
		this.onSelectTab = this.onSelectTab.bind( this );
		this.openDemo = this.openDemo.bind( this );
		this.skipStep = this.skipStep.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { isError, isUpdatingProfileItems, createNotice } = this.props;
		const { chosen } = this.state;
		const isRequestSuccessful =
			! isUpdatingProfileItems &&
			prevProps.isUpdatingProfileItems &&
			! isError &&
			chosen;
		const isRequestError =
			! isUpdatingProfileItems && prevProps.isRequesting && isError;

		if ( isRequestSuccessful ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { chosen: null } );
			/* eslint-enable react/no-did-update-set-state */
			this.props.goToNextStep();
		}

		if ( isRequestError ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { chosen: null } );
			/* eslint-enable react/no-did-update-set-state */
			createNotice(
				'error',
				__(
					'There was a problem selecting your store theme.',
					'woocommerce-admin'
				)
			);
		}
	}

	onChoose( theme, location = '' ) {
		const { updateProfileItems } = this.props;
		const { is_installed: isInstalled, price, slug } = theme;
		const { activeTheme = '' } = getSetting( 'onboarding', {} );

		this.setState( { chosen: slug } );
		recordEvent( 'storeprofiler_store_theme_choose', {
			theme: slug,
			location,
		} );

		if ( slug !== activeTheme && getPriceValue( price ) <= 0 ) {
			if ( isInstalled ) {
				this.activateTheme( slug );
			} else {
				this.installTheme( slug );
			}
		} else {
			updateProfileItems( { theme: slug } );
		}
	}

	installTheme( slug ) {
		const { createNotice } = this.props;

		apiFetch( {
			path: '/wc-admin/onboarding/themes/install?theme=' + slug,
			method: 'POST',
		} )
			.then( ( response ) => {
				createNotice(
					'success',
					sprintf(
						__(
							'%s was installed on your site.',
							'woocommerce-admin'
						),
						response.name
					)
				);
				this.activateTheme( slug );
			} )
			.catch( ( response ) => {
				this.setState( { chosen: null } );
				createNotice( 'error', response.message );
			} );
	}

	activateTheme( slug ) {
		const { createNotice, updateProfileItems } = this.props;

		apiFetch( {
			path: '/wc-admin/onboarding/themes/activate?theme=' + slug,
			method: 'POST',
		} )
			.then( ( response ) => {
				createNotice(
					'success',
					sprintf(
						__(
							'%s was activated on your site.',
							'woocommerce-admin'
						),
						response.name
					)
				);
				setSetting( 'onboarding', {
					...getSetting( 'onboarding', {} ),
					activeTheme: response.slug,
				} );
				updateProfileItems( { theme: slug } );
			} )
			.catch( ( response ) => {
				this.setState( { chosen: null } );
				createNotice( 'error', response.message );
			} );
	}

	onClosePreview() {
		const { demo } = this.state;
		recordEvent( 'storeprofiler_store_theme_demo_close', {
			theme: demo.slug,
		} );
		document.body.classList.remove( 'woocommerce-theme-preview-active' );
		this.setState( { demo: null } );
	}

	openDemo( theme ) {
		recordEvent( 'storeprofiler_store_theme_live_demo', {
			theme: theme.slug,
		} );
		document.body.classList.add( 'woocommerce-theme-preview-active' );
		this.setState( { demo: theme } );
	}

	skipStep() {
		const { activeTheme = '' } = getSetting( 'onboarding', {} );
		recordEvent( 'storeprofiler_store_theme_skip_step', { activeTheme } );
		this.props.goToNextStep();
	}

	renderTheme( theme ) {
		const {
			demo_url: demoUrl,
			has_woocommerce_support: hasSupport,
			image,
			slug,
			title,
		} = theme;
		const { chosen } = this.state;
		const { activeTheme = '' } = getSetting( 'onboarding', {} );

		return (
			<Card className="woocommerce-profile-wizard__theme" key={ slug }>
				{ image && (
					<div
						className="woocommerce-profile-wizard__theme-image"
						style={ { backgroundImage: `url(${ image })` } }
						role="img"
						aria-label={ title }
					/>
				) }
				<div className="woocommerce-profile-wizard__theme-details">
					<H className="woocommerce-profile-wizard__theme-name">
						{ title }
						{ ! hasSupport && (
							<Tooltip
								text={ __(
									'This theme does not support WooCommerce.',
									'woocommerce-admin'
								) }
							>
								<span>
									<Gridicon
										icon="info"
										role="img"
										aria-hidden="true"
										focusable="false"
									/>
								</span>
							</Tooltip>
						) }
					</H>
					<p className="woocommerce-profile-wizard__theme-status">
						{ this.getThemeStatus( theme ) }
					</p>
					<div className="woocommerce-profile-wizard__theme-actions">
						{ slug === activeTheme ? (
							<Button
								isPrimary
								onClick={ () => this.onChoose( theme, 'card' ) }
								isBusy={ chosen === slug }
							>
								{ __(
									'Continue with my active theme',
									'woocommerce-admin'
								) }
							</Button>
						) : (
							<Button
								isSecondary
								onClick={ () => this.onChoose( theme, 'card' ) }
								isBusy={ chosen === slug }
							>
								{ __( 'Choose', 'woocommerce-admin' ) }
							</Button>
						) }
						{ demoUrl && (
							<Button
								isTertiary
								onClick={ () => this.openDemo( theme ) }
							>
								{ __( 'Live demo', 'woocommerce-admin' ) }
							</Button>
						) }
					</div>
				</div>
			</Card>
		);
	}

	getThemeStatus( theme ) {
		const { is_installed: isInstalled, price, slug } = theme;
		const { activeTheme = '' } = getSetting( 'onboarding', {} );

		if ( activeTheme === slug ) {
			return __( 'Currently active theme', 'woocommerce-admin' );
		}
		if ( isInstalled ) {
			return __( 'Installed', 'woocommerce-admin' );
		} else if ( getPriceValue( price ) <= 0 ) {
			return __( 'Free', 'woocommerce-admin' );
		}

		return sprintf(
			__( '%s per year', 'woocommerce-admin' ),
			decodeEntities( price )
		);
	}

	doesActiveThemeSupportWooCommerce() {
		const { activeTheme = '' } = getSetting( 'onboarding', {} );
		const allThemes = this.getThemes();
		const currentTheme = allThemes.find(
			( theme ) => theme.slug === activeTheme
		);
		return currentTheme && currentTheme.has_woocommerce_support;
	}

	onSelectTab( tab ) {
		recordEvent( 'storeprofiler_store_theme_navigate', {
			navigation: tab,
		} );
		this.setState( { activeTab: tab } );
	}

	getPriceValue( string ) {
		return Number( decodeEntities( string ).replace( /[^0-9.-]+/g, '' ) );
	}

	getThemes( activeTab = 'all' ) {
		const { uploadedThemes } = this.state;
		const { activeTheme = '', themes = [] } = getSetting(
			'onboarding',
			{}
		);
		const allThemes = [
			...themes.filter(
				( theme ) =>
					theme &&
					( theme.has_woocommerce_support ||
						theme.slug === activeTheme )
			),
			...uploadedThemes,
		];

		switch ( activeTab ) {
			case 'paid':
				return allThemes.filter(
					( theme ) => getPriceValue( theme.price ) > 0
				);
			case 'free':
				return allThemes.filter(
					( theme ) => getPriceValue( theme.price ) <= 0
				);
			case 'all':
			default:
				return allThemes;
		}
	}

	handleUploadComplete( upload ) {
		if ( upload.status === 'success' && upload.theme_data ) {
			this.setState( {
				uploadedThemes: [
					...this.state.uploadedThemes,
					upload.theme_data,
				],
			} );

			recordEvent( 'storeprofiler_store_theme_upload', {
				theme: upload.theme_data.slug,
			} );
		}
	}

	render() {
		const { activeTab, chosen, demo } = this.state;
		const themes = this.getThemes( activeTab );
		const activeThemeSupportsWooCommerce = this.doesActiveThemeSupportWooCommerce();

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Choose a theme', 'woocommerce-admin' ) }
				</H>
				<H className="woocommerce-profile-wizard__header-subtitle">
					{ __(
						"Choose how your store appears to customers. And don't worry, you can always switch themes and edit them later.",
						'woocommerce-admin'
					) }
				</H>
				<TabPanel
					className="woocommerce-profile-wizard__themes-tab-panel"
					activeClass="is-active"
					onSelect={ this.onSelectTab }
					tabs={ [
						{
							name: 'all',
							title: __( 'All themes', 'woocommerce-admin' ),
						},
						{
							name: 'paid',
							title: __( 'Paid themes', 'woocommerce-admin' ),
						},
						{
							name: 'free',
							title: __( 'Free themes', 'woocommerce-admin' ),
						},
					] }
				>
					{ () => (
						<div className="woocommerce-profile-wizard__themes">
							{ themes &&
								themes.map( ( theme ) =>
									this.renderTheme( theme )
								) }
							<ThemeUploader
								onUploadComplete={ this.handleUploadComplete }
							/>
						</div>
					) }
				</TabPanel>
				{ demo && (
					<ThemePreview
						theme={ demo }
						onChoose={ () => this.onChoose( demo, 'card' ) }
						onClose={ this.onClosePreview }
						isBusy={ chosen === demo.slug }
					/>
				) }
				{ activeThemeSupportsWooCommerce && (
					<p className="woocommerce-profile-wizard__themes-skip-this-step">
						<Button
							isLink
							className="woocommerce-profile-wizard__skip"
							onClick={ () => this.skipStep() }
						>
							{ __( 'Skip this step', 'woocommerce-admin' ) }
						</Button>
					</p>
				) }
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getProfileItems,
			getOnboardingError,
			isOnboardingRequesting,
		} = select( ONBOARDING_STORE_NAME );

		return {
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			isUpdatingProfileItems: isOnboardingRequesting(
				'updateProfileItems'
			),
			profileItems: getProfileItems(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateProfileItems } = dispatch( ONBOARDING_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
		};
	} )
)( Theme );
